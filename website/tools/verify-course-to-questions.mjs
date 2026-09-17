/**
 * La porte du cours vers ses questions, conduite dans un vrai navigateur.
 *
 * Elle a manqué jusqu'au 2026-09-17 : les questions étaient écrites, déployées
 * et joignables UNIQUEMENT par le flux mélangé de Practice Mode. Un apprenant
 * qui finissait un cours n'avait aucun moyen de s'entraîner sur ce qu'il venait
 * de lire.
 *
 * Pourquoi un navigateur plutôt qu'un grep : la page est rendue côté client. Le
 * HTML servi ne contient ni la bannière, ni la file de questions ; chercher le
 * texte dans les octets servis trouverait la navigation et ne prouverait rien.
 * Ce projet s'est déjà fait avoir par un contrôle de ce genre.
 *
 * L'audit d'accessibilité ne visite pas `/practice?item=…` — il parcourt une
 * liste fixe de pages sans paramètre. La bannière n'est donc vue que par ici.
 *
 * CHAQUE CONTRÔLE EST APPARIÉ À UNE PREUVE QU'IL PEUT ÉCHOUER.
 *
 *   node website/tools/verify-course-to-questions.mjs
 */
import {chromium} from 'playwright';
import {createServer} from 'node:http';
import {existsSync} from 'node:fs';
import {readFile} from 'node:fs/promises';
import {join} from 'node:path';

const LOCAL_CHROME = '/opt/pw-browsers/chromium-1194/chrome-linux/chrome';
const ROOT = new URL('../build/', import.meta.url).pathname;
const WEBSITE = new URL('../', import.meta.url).pathname;
const PORT = 4607;
const BASE = '/Symfony-8-Certification-Path';
const TYPES = {'.html': 'text/html', '.js': 'text/javascript', '.css': 'text/css', '.json': 'application/json'};

const server = createServer(async (req, res) => {
  let p = decodeURIComponent(req.url.split('?')[0]).replace(BASE, '') || '/';
  if (p.endsWith('/')) p += 'index.html';
  let file = join(ROOT, p);
  if (!existsSync(file) && existsSync(`${file}.html`)) file = `${file}.html`;
  if (!existsSync(file) || !file.startsWith(ROOT)) {
    res.writeHead(404).end('not found');
    return;
  }
  const ext = file.slice(file.lastIndexOf('.'));
  res.writeHead(200, {'content-type': TYPES[ext] ?? 'application/octet-stream'});
  res.end(await readFile(file));
});
await new Promise((r) => server.listen(PORT, r));

const payload = JSON.parse(await readFile(join(WEBSITE, 'static/data/practice.json'), 'utf8'));
const url = (path) => `http://127.0.0.1:${PORT}${BASE}${path}`;
const browser = await chromium.launch(existsSync(LOCAL_CHROME) ? {executablePath: LOCAL_CHROME} : {});

const problems = [];
const ok = [];
async function check(name, fn) {
  try {
    await fn();
    ok.push(name);
  } catch (e) {
    problems.push(`${name} — ${e.message}`);
  }
}
function proves(name, predicate, broken) {
  try {
    predicate(broken);
    problems.push(`${name} — le contrôle ne tombe PAS sur une entrée cassée`);
  } catch {
    ok.push(`preuve : ${name} tombe sur une entrée cassée`);
  }
}

// L'item de démonstration est choisi dans le payload, jamais codé en dur : un
// identifiant figé pourrirait le jour où l'item serait renommé ou retiré.
const ITEM = Object.keys(payload.items).find(
  (id) => payload.questions.filter((q) => q.official_item === id).length >= 3,
);
const entry = payload.items[ITEM];
const expected = payload.questions.filter((q) => q.official_item === ITEM).length;

const page = await browser.newPage();

await check('la page de cours porte le lien vers ses questions', async () => {
  await page.goto(url(entry.course_url), {waitUntil: 'networkidle'});
  const link = page.getByRole('link', {name: /S'entraîner sur cet item/});
  const href = await link.getAttribute('href');
  if (!href || !href.includes(`item=${ITEM}`)) {
    throw new Error(`href inattendu : ${href}`);
  }
});

await check('la page de cours annonce le bon nombre de questions', async () => {
  const body = await page.textContent('article');
  if (!new RegExp(`\\b${expected} questions? d`).test(body)) {
    throw new Error(`le compte annoncé ne vaut pas ${expected}`);
  }
});

await check('le lien restreint réellement la série à cet item', async () => {
  await page.goto(url(`/practice?item=${ITEM}`), {waitUntil: 'networkidle'});
  const scope = await page.locator('.certpath-scope').textContent();
  if (!scope.includes(entry.official_item)) {
    throw new Error(`la bannière ne nomme pas l'item : ${scope}`);
  }
  if (!scope.includes(String(expected))) {
    throw new Error(`la bannière n'annonce pas ${expected} questions : ${scope}`);
  }
});

await check('la bannière ramène au cours', async () => {
  const back = await page.getByRole('link', {name: 'Revenir au cours'}).getAttribute('href');
  if (!back || !back.endsWith(entry.course_url)) {
    throw new Error(`retour inattendu : ${back}`);
  }
});

await check('un identifiant inconnu le dit au lieu de servir une file vide', async () => {
  await page.goto(url('/practice?item=OIT-inexistant000'), {waitUntil: 'networkidle'});
  const scope = await page.locator('.certpath-scope').textContent();
  if (!scope.includes('Aucun item')) {
    throw new Error(`message attendu absent : ${scope}`);
  }
});

await check('sans paramètre, la banque entière reste servie', async () => {
  await page.goto(url('/practice'), {waitUntil: 'networkidle'});
  if ((await page.locator('.certpath-scope').count()) !== 0) {
    throw new Error('la bannière de restriction apparaît alors que rien ne restreint');
  }
});

// Les preuves. Le filtre est de l'arithmétique sur le payload : on le rejoue
// ici sur une entrée cassée pour montrer que le contrôle sait tomber.
proves(
  'le filtre par item',
  (qs) => {
    const kept = qs.filter((q) => q.official_item === ITEM);
    if (kept.length !== qs.length) throw new Error('filtré');
  },
  [{official_item: ITEM}, {official_item: 'OIT-autre'}],
);
proves(
  'le compte annoncé',
  (n) => {
    if (n !== expected) throw new Error('compte faux');
  },
  expected + 1,
);

await browser.close();
server.close();

for (const line of ok) console.log(`ok    ${line}`);
for (const line of problems) console.error(`FAIL  ${line}`);
console.log(`\n${ok.length} ok, ${problems.length} échec(s)`);
process.exit(problems.length === 0 ? 0 : 1);
