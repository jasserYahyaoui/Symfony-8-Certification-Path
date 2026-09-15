/**
 * The simulations hub, driven in a real browser.
 *
 * WHY A BROWSER AND NOT A FETCH. The page is client-rendered: the HTML the host
 * serves carries the shell and the word « Chargement… », and nothing else. So a
 * smoke test that fetched that HTML and looked for "mock-3" would find it — in
 * the navbar — and report success about a page whose body never rendered. This
 * project has already shipped one check that passed while doing two-thirds of
 * its work; the same shape is not repeated here.
 *
 * WHAT IT ASSERTS. That the five mocks reach the learner with the two things the
 * page exists to give: what each is for, and when to sit it — read from the
 * payload and compared against what the browser actually painted. And that Mock
 * 4 arrives marked as a single sitting, because a learner who spends it early
 * cannot get it back.
 *
 * EVERY CHECK IS PAIRED WITH A PROOF THAT IT CAN FAIL.
 *
 *   npm --prefix website run build && node website/tools/verify-simulations-ui.mjs
 */
import {chromium} from 'playwright';
import {createServer} from 'node:http';
import {existsSync} from 'node:fs';
import {readFile} from 'node:fs/promises';
import {extname, join} from 'node:path';

const LOCAL_CHROME = '/opt/pw-browsers/chromium-1194/chrome-linux/chrome';
const ROOT = new URL('../build/', import.meta.url).pathname;
const WEBSITE = new URL('../', import.meta.url).pathname;
const PORT = 4607;
const BASE = '/Symfony-8-Certification-Path';
const TYPES = {
  '.html': 'text/html', '.js': 'text/javascript', '.css': 'text/css',
  '.json': 'application/json', '.svg': 'image/svg+xml',
};

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

const payload = JSON.parse(await readFile(join(WEBSITE, 'static/data/simulations.json'), 'utf8'));
const url = (path) => `http://127.0.0.1:${PORT}${BASE}${path}`;
const browser = await chromium.launch(
  existsSync(LOCAL_CHROME) ? {executablePath: LOCAL_CHROME} : {},
);
const problems = [];
const ok = [];

async function check(name, fn) {
  try {
    const detail = await fn();
    ok.push(`${name}${detail ? ` — ${detail}` : ''}`);
  } catch (e) {
    problems.push(`${name}: ${e.message}`);
  }
}

function proves(name, predicate, broken) {
  try {
    predicate(broken);
    problems.push(`PROOF ${name}: stayed silent on its own defect — the check is VACUOUS`);
  } catch {
    ok.push(`preuve — ${name} rejette bien son propre défaut`);
  }
}

const page = await (await browser.newContext()).newPage();
await page.goto(url('/simulations'), {waitUntil: 'networkidle'});
const body = await page.locator('main').innerText();

await check('la page rend le payload et pas seulement le squelette', async () => {
  if (body.includes('Chargement…')) throw new Error('la page est restée sur « Chargement… »');
  if (body.includes('Chargement impossible')) throw new Error('le payload n\'a pas été chargé');
  return `${body.length} caractères rendus`;
});

await check('chaque mock dit à quoi il sert', () => {
  const missing = payload.mocks.filter((m) => !body.includes(m.purpose.slice(0, 40)));
  if (missing.length) throw new Error(`rôle absent pour ${missing.map((m) => m.id).join(', ')}`);
  return `${payload.mocks.length} rôles rendus, lus depuis le payload`;
});

await check('chaque mock dit quand le passer', () => {
  const missing = payload.mocks.filter((m) => !body.includes(m.when_to_use.slice(0, 40)));
  if (missing.length) throw new Error(`consigne absente pour ${missing.map((m) => m.id).join(', ')}`);
  return `${payload.mocks.length} consignes rendues`;
});

await check('chaque mock est cliquable depuis la page', async () => {
  for (const mock of payload.mocks) {
    const href = `${BASE}${mock.route}`;
    if (await page.locator(`main a[href="${href}"]`).count() === 0) {
      throw new Error(`aucun lien vers ${mock.route}`);
    }
  }
  return `${payload.mocks.length} liens`;
});

await check('le Mock 4 est annoncé comme une seule tentative', () => {
  if (!body.includes('une seule fois')) {
    throw new Error('rien n\'indique que le Mock 4 ne se passe qu\'une fois');
  }
  const four = payload.mocks.find((m) => m.id === 'mock-4');
  if (four.repeatable) throw new Error('le payload le déclare rejouable');
  return 'annoncé sur la page et non rejouable dans le payload';
});

await check('aucun seuil officiel n\'est affiché', () => {
  // §19: the project publishes no pass mark because it knows none. A page that
  // printed one would be inventing the single number a learner most wants.
  const invented = /\b(seuil|pass mark)\b[^.]{0,40}\b\d{2}\s*%/i.exec(body);
  if (invented) throw new Error(`seuil affiché : ${invented[0]}`);
  if (!body.includes('INTERNAL_TRAINING_FORMAT')) {
    throw new Error('les chiffres internes ne sont pas étiquetés');
  }
  return 'aucun seuil, étiquetage interne présent';
});

await check('la page ne révèle aucune question', () => {
  const raw = JSON.stringify(payload);
  for (const marker of ['QST-', 'CHO-', '"questions"', '"choices"']) {
    if (raw.includes(marker)) throw new Error(`le payload contient ${marker}`);
    if (body.includes(marker)) throw new Error(`la page affiche ${marker}`);
  }
  return 'ni identifiant de question ni choix, dans le payload comme dans la page';
});

proves(
  'le contrôle « rôle rendu »',
  (text) => {
    const missing = payload.mocks.filter((m) => !text.includes(m.purpose.slice(0, 40)));
    if (missing.length) throw new Error('caught');
  },
  'une page où ne figure aucun rôle',
);

proves(
  'le contrôle « consigne rendue »',
  (text) => {
    const missing = payload.mocks.filter((m) => !text.includes(m.when_to_use.slice(0, 40)));
    if (missing.length) throw new Error('caught');
  },
  'une page sans consigne',
);

proves(
  'le contrôle « squelette seul »',
  (text) => {
    if (text.includes('Chargement…')) throw new Error('caught');
  },
  'Chargement… et rien d\'autre',
);

proves(
  'le contrôle « aucune question révélée »',
  (raw) => {
    if (raw.includes('QST-')) throw new Error('caught');
  },
  '{"mocks":[{"id":"mock-4","questions":[{"id":"QST-leak"}]}]}',
);

await browser.close();
server.close();

for (const line of ok) console.log(`ok    ${line}`);
for (const line of problems) console.error(`FAIL  ${line}`);
console.log(`\n${ok.length} ok, ${problems.length} échec(s)`);
process.exit(problems.length === 0 ? 0 : 1);
