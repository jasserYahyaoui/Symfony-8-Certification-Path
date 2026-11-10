/**
 * Every diagram of the site is drawn as an image, in a real browser.
 *
 *   php bin/cert build && npm --prefix website run build
 *   node website/tools/verify-diagrams-drawn.mjs
 *
 * The owner, 2026-11-08: « ils doivent être visibles en tant qu'image comme
 * mermaid, pas un code ni texte ». Two things are checked on the built site,
 * served locally, for every generated page:
 *
 * 1. Each ```mermaid block of the page is drawn as an SVG, with its accessible
 *    title and description, and no Mermaid error diagram. A syntax error draws
 *    nothing or an error image; either fails here (proven on 2026-10-09 for the
 *    first case). The accessibility audit checks two pages this way; this
 *    checks them all.
 * 2. A « Le schéma » section holds a drawn diagram and no code block: a
 *    diagram written as text in a fence is shown as code, which is what the
 *    owner refused.
 *
 * The production smoke test cannot see either, since Mermaid draws in the
 * browser. The check proves it can fail: a page whose diagram is replaced by a
 * syntax error, and a schema section holding a text fence, must both fail.
 */
import {chromium} from 'playwright';
import {createServer} from 'node:http';
import {readFile, readdir, stat} from 'node:fs/promises';
import {extname, join, relative} from 'node:path';

const WEBSITE = new URL('../', import.meta.url).pathname;
const ROOT = join(WEBSITE, 'build');
const DOCS = join(WEBSITE, 'docs');
const PORT = 4611;
const BASE = '/Symfony-8-Certification-Path';
const TYPES = {'.html': 'text/html', '.js': 'text/javascript', '.css': 'text/css', '.json': 'application/json', '.svg': 'image/svg+xml'};
const SCHEMA_HEADING = 'Le schéma';

async function markdownPages(dir) {
  const out = [];
  for (const entry of await readdir(dir, {withFileTypes: true})) {
    const full = join(dir, entry.name);
    if (entry.isDirectory()) out.push(...await markdownPages(full));
    else if (entry.name.endsWith('.md')) out.push(full);
  }
  return out;
}

/** The URL Docusaurus serves a generated page at: its path, without index or extension. */
function urlOf(file) {
  const rel = relative(WEBSITE, file).replace(/\.md$/, '').replace(/\/index$/, '');
  return `${BASE}/${rel}`;
}

function count(markdown) {
  let fence = null;
  let diagrams = 0;
  let schema = false;
  for (const line of markdown.split('\n')) {
    const open = line.match(/^(`{3,})\s*([\w-]*)/);
    if (fence === null && open) {
      fence = open[1];
      if (open[2] === 'mermaid') diagrams += 1;
      continue;
    }
    if (fence !== null && line.startsWith(fence)) {
      fence = null;
      continue;
    }
    if (fence === null && /^## /.test(line) && line.slice(3).replace(/\s*\{#.*\}\s*$/, '').trim() === SCHEMA_HEADING) schema = true;
  }
  return {diagrams, schema};
}

async function serve() {
  const server = createServer(async (req, res) => {
    let p = decodeURIComponent(req.url.split('?')[0]).replace(BASE, '') || '/';
    if (p.endsWith('/')) p += 'index.html';
    if (!extname(p)) p += '.html';
    try {
      const body = await readFile(join(ROOT, p));
      res.writeHead(200, {'content-type': TYPES[extname(p)] ?? 'application/octet-stream'});
      res.end(body);
    } catch {
      res.writeHead(404);
      res.end('not found');
    }
  });
  await new Promise((r) => server.listen(PORT, r));
  return server;
}

/** What one page shows: its drawn diagrams, Mermaid errors, and what « Le schéma » holds. */
async function inspect(page, url, expected) {
  await page.goto(`http://127.0.0.1:${PORT}${url}`, {waitUntil: 'load'});
  const svgs = page.locator('.docusaurus-mermaid-container svg');
  if (expected > 0) {
    try {
      await svgs.nth(expected - 1).waitFor({timeout: 20000});
    } catch {
      // fall through: the count below reports what was drawn
    }
  }
  return page.evaluate((heading) => {
    const drawn = [...document.querySelectorAll('.docusaurus-mermaid-container svg')];
    const errors = drawn.filter((s) => s.querySelector('.error-icon, .error-text') || /Syntax error/.test(s.textContent ?? ''));
    const unlabelled = drawn.filter((s) => {
      const title = document.getElementById(s.getAttribute('aria-labelledby') || '')?.textContent?.trim();
      const desc = document.getElementById(s.getAttribute('aria-describedby') || '')?.textContent?.trim();
      return !title || !desc;
    });
    const rawMermaid = document.querySelectorAll('pre code.language-mermaid, pre.language-mermaid').length;
    let schema = null;
    const h2 = [...document.querySelectorAll('article h2')].find((h) => h.textContent.replace(/[#​]/g, '').trim() === heading);
    if (h2) {
      let diagrams = 0;
      let code = 0;
      for (let n = h2.nextElementSibling; n && n.tagName !== 'H2'; n = n.nextElementSibling) {
        diagrams += n.matches('.docusaurus-mermaid-container') ? 1 : n.querySelectorAll('.docusaurus-mermaid-container').length;
        code += n.matches('pre, div[class*="codeBlockContainer"]') ? 1 : n.querySelectorAll('pre').length;
      }
      schema = {diagrams, code};
    }
    return {drawn: drawn.length, errors: errors.length, unlabelled: unlabelled.length, rawMermaid, schema};
  }, SCHEMA_HEADING);
}

function problemsOf(seen, expected) {
  const problems = [];
  if (seen.drawn !== expected.diagrams) problems.push(`${expected.diagrams} mermaid block(s), ${seen.drawn} drawn`);
  if (seen.errors) problems.push(`${seen.errors} diagram(s) drawn as a Mermaid error`);
  if (seen.unlabelled) problems.push(`${seen.unlabelled} diagram(s) without an accessible title and description`);
  if (seen.rawMermaid) problems.push(`${seen.rawMermaid} mermaid block(s) shown as code`);
  if (expected.schema) {
    if (seen.schema === null) problems.push(`« ${SCHEMA_HEADING} » is in the source but not on the page`);
    else {
      if (seen.schema.diagrams === 0) problems.push(`« ${SCHEMA_HEADING} » draws no diagram`);
      if (seen.schema.code > 0) problems.push(`« ${SCHEMA_HEADING} » shows ${seen.schema.code} code block(s): a diagram written as text`);
    }
  }
  return problems;
}

const built = (await stat(join(ROOT, 'index.html')).catch(() => null))?.mtimeMs;
if (!built) {
  console.error('FAIL  no build: run `php bin/cert build && npm --prefix website run build` first.');
  process.exit(2);
}

const targets = [];
for (const file of await markdownPages(DOCS)) {
  const expected = count(await readFile(file, 'utf8'));
  if (expected.diagrams > 0 || expected.schema) targets.push({file, url: urlOf(file), expected});
}
if (targets.length === 0) {
  console.error('FAIL  no page carries a diagram or a schema section: the check would prove nothing');
  process.exit(1);
}

const server = await serve();
const browser = await chromium.launch();
const page = await browser.newPage();
let failed = 0;
let diagrams = 0;
let schemas = 0;
try {
  for (const {file, url, expected} of targets) {
    const seen = await inspect(page, url, expected.diagrams);
    const problems = problemsOf(seen, expected);
    diagrams += seen.drawn - seen.errors;
    if (expected.schema && seen.schema && seen.schema.diagrams > 0 && seen.schema.code === 0) schemas += 1;
    if (problems.length) {
      failed += 1;
      console.log(`FAIL ${relative(WEBSITE, file)}: ${problems.join('; ')}`);
    }
  }

  // The check can fail. A page whose diagram is a syntax error, and a schema
  // section that shows a text fence, are both reported.
  const broken = await browser.newPage();
  const sample = targets.find((t) => t.expected.diagrams > 0 && t.expected.schema);
  await broken.route('**/*', (route) => route.continue());
  await broken.goto(`http://127.0.0.1:${PORT}${sample.url}`, {waitUntil: 'load'});
  await broken.locator('.docusaurus-mermaid-container svg').first().waitFor({timeout: 20000});
  const mutated = await broken.evaluate((heading) => {
    const h2 = [...document.querySelectorAll('article h2')].find((h) => h.textContent.replace(/[#​]/g, '').trim() === heading);
    const container = document.querySelector('.docusaurus-mermaid-container');
    container.innerHTML = '';
    const pre = document.createElement('pre');
    pre.textContent = 'A --> B';
    h2.insertAdjacentElement('afterend', pre);
    return true;
  }, SCHEMA_HEADING);
  const after = await broken.evaluate((heading) => {
    const drawn = document.querySelectorAll('.docusaurus-mermaid-container svg').length;
    const h2 = [...document.querySelectorAll('article h2')].find((h) => h.textContent.replace(/[#​]/g, '').trim() === heading);
    let code = 0;
    for (let n = h2.nextElementSibling; n && n.tagName !== 'H2'; n = n.nextElementSibling) code += n.matches('pre') ? 1 : n.querySelectorAll('pre').length;
    return {drawn, errors: 0, unlabelled: 0, rawMermaid: 0, schema: {diagrams: 0, code}};
  }, SCHEMA_HEADING);
  const proof = problemsOf(after, sample.expected);
  if (!mutated || proof.length < 2) {
    failed += 1;
    console.log(`FAIL the check did not fire on a broken copy of ${sample.url}: ${JSON.stringify(proof)}`);
  } else {
    console.log(`ok  proof: a broken copy of ${sample.url} fails (${proof.join('; ')})`);
  }
  await broken.close();
} finally {
  await browser.close();
  server.close();
}

console.log(`${targets.length} page(s) checked; ${diagrams} diagram(s) drawn; ${schemas} « ${SCHEMA_HEADING} » section(s) drawn as an image; ${failed} failure(s)`);
process.exit(failed ? 1 : 0);
