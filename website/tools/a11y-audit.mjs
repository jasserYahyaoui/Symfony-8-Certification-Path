/**
 * Reproducible accessibility audit of the built site (Master Plan §13, §17).
 *
 * Audits the artefact that is deployed, served locally, because the production
 * host is unreachable from the build container. Same bytes, same commit.
 *
 *   php bin/cert build && npm --prefix website run build
 *   node website/tools/a11y-audit.mjs
 */
import {chromium} from 'playwright';
import {AxeBuilder} from '@axe-core/playwright';
import {createServer} from 'node:http';
import {readFile, stat, readdir} from 'node:fs/promises';
import {extname, join} from 'node:path';

const ROOT = new URL('../build/', import.meta.url).pathname;
const WEBSITE = new URL('../', import.meta.url).pathname;
const REPO = new URL('../../', import.meta.url).pathname;
const PORT = 4599;
const BASE = `/Symfony-8-Certification-Path`;
const TYPES = {'.html':'text/html','.js':'text/javascript','.css':'text/css','.json':'application/json','.svg':'image/svg+xml'};

/**
 * Refuse to audit a build that is older than its inputs.
 *
 * A passing audit is only evidence about the artefact it actually loaded. In
 * Lot 05 the site build failed, the failure was hidden by a `tail`, and this
 * script happily audited the PREVIOUS lot's build directory and reported 6/6
 * PASS — a real result about the wrong bytes. The gate now refuses rather than
 * producing a reassuring number nobody can trust.
 */
async function newestMtime(dir, skip = new Set(['node_modules', 'build', '.git', '.docusaurus', 'vendor'])) {
  let newest = 0;
  let entries;
  try {
    entries = await readdir(dir, {withFileTypes: true});
  } catch {
    return 0;
  }
  for (const entry of entries) {
    if (entry.name.startsWith('.') || skip.has(entry.name)) continue;
    const full = join(dir, entry.name);
    if (entry.isDirectory()) {
      newest = Math.max(newest, await newestMtime(full, skip));
    } else {
      const {mtimeMs} = await stat(full);
      newest = Math.max(newest, mtimeMs);
    }
  }
  return newest;
}

async function assertBuildIsFresh() {
  let built;
  try {
    ({mtimeMs: built} = await stat(join(ROOT, 'index.html')));
  } catch {
    console.error('\nFAIL  no build to audit: website/build/index.html is missing.');
    console.error('      Run `php bin/cert build && npm --prefix website run build` first.\n');
    process.exit(2);
  }

  // What the rendered pages are built from: the generated docs tree and the
  // payloads, the React pages and CSS, and the Docusaurus configuration.
  const inputs = await Promise.all([
    newestMtime(join(WEBSITE, 'docs')),
    newestMtime(join(WEBSITE, 'static')),
    newestMtime(join(WEBSITE, 'src')),
    newestMtime(join(REPO, 'content')),
    stat(join(WEBSITE, 'docusaurus.config.ts')).then((s) => s.mtimeMs, () => 0),
  ]);
  const newestInput = Math.max(...inputs);

  if (newestInput > built) {
    const age = Math.round((newestInput - built) / 1000);
    console.error(`\nFAIL  stale build: an input is ${age}s newer than website/build/index.html.`);
    console.error('      Auditing it would report on bytes that are not the ones under review.');
    console.error('      Run `php bin/cert build && npm --prefix website run build` first.\n');
    process.exit(2);
  }
}

await assertBuildIsFresh();

const server = createServer(async (req, res) => {
  let p = decodeURIComponent(req.url.split('?')[0]).replace(BASE, '') || '/';
  if (p.endsWith('/')) p += 'index.html';
  if (!extname(p)) p += '.html';
  try {
    const body = await readFile(join(ROOT, p));
    res.writeHead(200, {'content-type': TYPES[extname(p)] ?? 'application/octet-stream'});
    res.end(body);
  } catch { res.writeHead(404); res.end('not found'); }
});
await new Promise((r) => server.listen(PORT, r));

// One page per interactive surface, plus a generated item page carrying the
// <details> flashcards introduced by Lot 0.5.
const PAGES = [
  ['landing', '/'],
  ['docs index', '/docs'],
  ['item page with flashcards', '/docs/courses/lot-02/status-codes'],
  // §5 glossary: a generated table, so its header scope and reading order
  // are worth auditing rather than assumed.
  ['glossary', '/docs/syllabus/glossary'],
  ['practice', '/practice'],
  ['exam', '/exam'],
  // Mock 4. Only the briefing screen is reachable without interaction, so
  // that is what this audits; the sitting reuses QuestionCard, covered through
  // practice and exam, and the results screen is built from the same table and
  // list primitives as the coverage page above. Neither is audited here, and
  // saying so is better than implying the whole flow is.
  ['mock 4', '/mock-4'],
  // Mock 1's briefing screen. Same limit as Mock 4: only the briefing is
  // reachable without interaction. Its sitting and results share the
  // TrainingMock component, whose tables mirror the audited coverage page.
  ['mock 1', '/mock-1'],
  ['mock 2', '/mock-2'],
  ['progression', '/progression'],
];

// Use the image's Chromium when present (the build container ships one whose
// revision the pinned Playwright does not match); otherwise let Playwright
// resolve its own, which is what CI does.
const {existsSync} = await import('node:fs');
const LOCAL_CHROME = '/opt/pw-browsers/chromium-1194/chrome-linux/chrome';
const browser = await chromium.launch(
  existsSync(LOCAL_CHROME) ? {executablePath: LOCAL_CHROME} : {},
);
let total = 0;
for (const [name, path] of PAGES) {
  // axe-core/playwright requires a real context, not the default page.
  const context = await browser.newContext();
  const page = await context.newPage();
  await page.goto(`http://127.0.0.1:${PORT}${BASE}${path}`, {waitUntil: 'networkidle'});

  const {violations} = await new AxeBuilder({page})
    .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
    .analyze();

  // Structural checks axe cannot make on its own.
  const extra = await page.evaluate(() => {
    const out = [];
    if (document.querySelectorAll('h1').length !== 1) out.push('h1-count');
    const levels = [...document.querySelectorAll('h1,h2,h3,h4')].map((h) => +h.tagName[1]);
    for (let i = 1; i < levels.length; i++) if (levels[i] - levels[i - 1] > 1) out.push('heading-skip');
    for (const c of document.querySelectorAll('input,select,button,a[href],summary')) {
      const s = getComputedStyle(c);
      if (s.outlineStyle === 'none' && !s.boxShadow.length) out.push('no-focus-affordance:' + c.tagName);
    }
    return [...new Set(out)];
  });

  total += violations.length + extra.length;
  const ids = violations.map((v) => `${v.id}(${v.impact},${v.nodes.length})`);
  if (process.env.A11Y_DETAIL) {
    for (const v of violations) {
      for (const n of v.nodes.slice(0, 3)) {
        console.log(`      ${v.id} :: ${n.target} :: ${(n.failureSummary || '').split('\n').filter(Boolean).slice(-1)}`);
      }
    }
  }
  console.log(`${violations.length + extra.length === 0 ? 'PASS' : 'FAIL'}  ${name.padEnd(28)} axe=${violations.length} structural=${extra.length} ${[...ids, ...extra].join(' ')}`);
  await context.close();
}
await browser.close();
server.close();
console.log(`\nTOTAL VIOLATIONS: ${total}`);
process.exit(total === 0 ? 0 : 1);
