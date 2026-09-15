/**
 * Every published page must be reachable by clicking (Lot 27 follow-up).
 *
 * WHY THIS EXISTS. The five mock pages were built, deployed, smoke-tested and
 * accessibility-audited for weeks while being linked from NOWHERE. A learner
 * could only reach Mock 4 — the final assessment — by typing its address.
 *
 * Every gate was green, and each for a good reason:
 *
 *   - the production smoke test fetches them by a HARDCODED URL list;
 *   - the accessibility audit visits them by a HARDCODED path list;
 *   - `onBrokenLinks: 'throw'` catches a link that points nowhere, and is
 *     silent about a page that nothing points to.
 *
 * So the tooling reached pages the USER could not. This check closes that gap
 * the only way that measures it: walk the built site from the home page,
 * following internal links the way a visitor does, and fail on any page the
 * walk never arrives at.
 *
 * It reads the BUILT html, not the config. A page can be linked from a React
 * component, a generated doc or the sidebar, and a config-reading check would
 * miss all three — and would also pass on a link the build never rendered.
 *
 *   npm --prefix website run build && node website/tools/verify-navigation.mjs
 */
import {readFile, readdir} from 'node:fs/promises';
import {join, relative, sep} from 'node:path';

const ROOT = new URL('../build/', import.meta.url).pathname;
const BASE = '/Symfony-8-Certification-Path';
const HREF = /<a\b[^>]*?href="([^"#?]*)/gi;

/**
 * Pages that are allowed to be unreachable, each with the reason.
 * A page is added here only when being unlinked is its PURPOSE.
 */
const ALLOWED_ORPHANS = new Map([
  ['/404', 'the not-found page: the server serves it, nothing links to it'],
]);

async function htmlFiles(dir) {
  const out = [];
  for (const entry of await readdir(dir, {withFileTypes: true})) {
    const path = join(dir, entry.name);
    if (entry.isDirectory()) {
      out.push(...(await htmlFiles(path)));
    } else if (entry.name.endsWith('.html')) {
      out.push(path);
    }
  }
  return out;
}

function routeOf(path) {
  let rel = relative(ROOT, path).split(sep).join('/');
  if (rel.endsWith('/index.html')) {
    rel = rel.slice(0, -'/index.html'.length);
  } else if (rel.endsWith('.html')) {
    rel = rel.slice(0, -'.html'.length);
  }
  return rel === 'index' || rel === '' ? '/' : `/${rel}`;
}

const pages = new Map();
for (const file of await htmlFiles(ROOT)) {
  pages.set(routeOf(file), file);
}

if (!pages.has('/')) {
  console.error('FAIL  navigation  no home page in the build; nothing to walk from');
  process.exit(1);
}

async function linksOf(file) {
  const html = await readFile(file, 'utf8');
  const out = new Set();
  for (const [, href] of html.matchAll(HREF)) {
    if (!href.startsWith(BASE)) {
      continue;
    }
    const route = (href.slice(BASE.length) || '/').replace(/\/$/, '') || '/';
    out.add(route);
  }
  return out;
}

const seen = new Set();
const queue = ['/'];
while (queue.length > 0) {
  const route = queue.shift();
  if (seen.has(route) || !pages.has(route)) {
    continue;
  }
  seen.add(route);
  for (const next of await linksOf(pages.get(route))) {
    if (pages.has(next) && !seen.has(next)) {
      queue.push(next);
    }
  }
}

const orphans = [...pages.keys()].filter((r) => !seen.has(r));
const unexpected = orphans.filter((r) => !ALLOWED_ORPHANS.has(r));

for (const route of orphans.filter((r) => ALLOWED_ORPHANS.has(r))) {
  console.log(`ok    navigation  ${route} orpheline par conception — ${ALLOWED_ORPHANS.get(route)}`);
}

for (const route of unexpected) {
  console.error(
    `FAIL  navigation  ${route} est publiée mais aucun lien n'y mène : ` +
    'un apprenant ne peut y arriver qu\'en tapant l\'adresse',
  );
}

// A walk that reaches only the home page would report zero orphans on a broken
// build and read as success. The count is stated so the number is checked.
console.log(
  `${unexpected.length === 0 ? 'ok   ' : 'FAIL '} navigation  ` +
  `${seen.size} page(s) atteignable(s) au clic sur ${pages.size} publiée(s)`,
);

if (seen.size < 2) {
  console.error('FAIL  navigation  la marche n\'a atteint que la page d\'accueil; ' +
    'le walk est casse, pas le site');
  process.exit(1);
}

process.exit(unexpected.length === 0 ? 0 : 1);
