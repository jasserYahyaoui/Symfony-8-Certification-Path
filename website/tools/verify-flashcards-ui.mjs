/**
 * Flashcard recall, driven in a real browser (remediation P2.3).
 *
 * What is asked for: a recall rating and a due day per card, the scheduler
 * actually wired to the page, migration, export/import, the card's version, a
 * reset — and no confusion between "back shown" and "answer known". Each of
 * those is exercised here, by keyboard where the learner would use one.
 *
 *   node website/tools/verify-flashcards-ui.mjs
 */
import {chromium} from 'playwright';
import {createServer} from 'node:http';
import {existsSync} from 'node:fs';
import {readFile} from 'node:fs/promises';
import {extname, join} from 'node:path';

const LOCAL_CHROME = '/opt/pw-browsers/chromium-1194/chrome-linux/chrome';
const ROOT = new URL('../build/', import.meta.url).pathname;
const STORAGE_VERSION = Number(
  (await readFile(new URL('../src/lib/storage.ts', import.meta.url).pathname, 'utf8')).match(/STORAGE_VERSION\s*=\s*(\d+)/)?.[1],
);
if (!Number.isInteger(STORAGE_VERSION)) {
  throw new Error('impossible de lire STORAGE_VERSION dans storage.ts');
}
const INDEX = JSON.parse(await readFile(join(ROOT, 'data/flashcards.json'), 'utf8'));
const PORT = 4607;
const BASE = '/Symfony-8-Certification-Path';
const TYPES = {'.html': 'text/html', '.js': 'text/javascript', '.css': 'text/css', '.json': 'application/json', '.svg': 'image/svg+xml'};
const KEY = 'certpath.learner-state';

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

const url = (path) => `http://127.0.0.1:${PORT}${BASE}${path}`;
const browser = await chromium.launch(existsSync(LOCAL_CHROME) ? {executablePath: LOCAL_CHROME} : {});
const page = await (await browser.newContext()).newPage();
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
const stored = () => page.evaluate((k) => JSON.parse(window.localStorage.getItem(k) ?? '{}'), KEY);
const today = () => page.evaluate(() => {
  const d = new Date();
  const pad = (n) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
});

const card = INDEX.cards.find((c) => c.url.startsWith('/docs/courses/lot-02/symfony-httpclient-component'));
if (!card) {
  throw new Error('aucune carte du cours HttpClient dans flashcards.json');
}
const anchor = card.url.split('#')[1];
const coursePath = card.url.split('#')[0];

await check('ouvrir une carte n enregistre rien', async () => {
  await page.goto(url(coursePath), {waitUntil: 'networkidle'});
  await page.evaluate((k) => window.localStorage.removeItem(k), KEY);
  await page.reload({waitUntil: 'networkidle'});
  const details = page.locator(`#${anchor}`);
  await details.locator('summary').focus();
  await page.keyboard.press('Enter');
  if (!(await details.evaluate((d) => d.open))) {
    throw new Error('Entrée sur le résumé n ouvre pas la carte');
  }
  await details.getByText("Ouvrir la carte n'enregistre rien.").waitFor();
  const state = await stored();
  if (state.flashcards && Object.keys(state.flashcards).length > 0) {
    throw new Error('une carte ouverte a été enregistrée sans notation');
  }
  return 'carte ouverte au clavier, état vide';
});

await check('noter « Bien » au clavier planifie la carte à J+1', async () => {
  const details = page.locator(`#${anchor}`);
  const good = details.getByRole('button', {name: /^Bien/});
  await good.focus();
  await page.keyboard.press('Enter');
  const status = details.getByRole('status');
  await status.waitFor();
  if (!(await status.evaluate((el) => el === document.activeElement))) {
    throw new Error('le focus ne passe pas au message de planification');
  }
  const record = (await stored()).flashcards?.[card.id];
  const day = await today();
  const tomorrow = await page.evaluate(() => {
    const d = new Date();
    const n = new Date(d.getFullYear(), d.getMonth(), d.getDate() + 1);
    const pad = (x) => String(x).padStart(2, '0');
    return `${n.getFullYear()}-${pad(n.getMonth() + 1)}-${pad(n.getDate())}`;
  });
  if (!record || record.card_hash !== card.version || record.interval_days !== 1 || record.repetitions !== 1 || record.due_on !== tomorrow) {
    throw new Error(`enregistrement inattendu : ${JSON.stringify(record)} (aujourd'hui ${day})`);
  }
  return `due_on ${record.due_on}, version ${record.card_hash}`;
});

await check('une deuxième note « Bien » passe à 3 jours, comme ReviewScheduler.php', async () => {
  await page.locator(`#${anchor}`).getByRole('button', {name: /^Bien/}).click();
  const record = (await stored()).flashcards[card.id];
  if (record.interval_days !== 3 || record.history.length !== 2) {
    throw new Error(`interval ${record.interval_days}, historique ${record.history.length}`);
  }
  return 'J+3, deux notations dans l historique';
});

await check('la planification survit au rechargement et le schéma est à jour', async () => {
  await page.reload({waitUntil: 'networkidle'});
  const details = page.locator(`#${anchor}`);
  await details.locator('summary').click();
  await details.getByText(/Prochaine révision/).waitFor();
  const state = await stored();
  if (state.schema_version !== STORAGE_VERSION) {
    throw new Error(`schema_version ${state.schema_version}, attendu ${STORAGE_VERSION}`);
  }
  return `schéma ${STORAGE_VERSION}`;
});

await check('une carte dont le recto ou le verso a changé repart de zéro', async () => {
  await page.evaluate(({k, id}) => {
    const s = JSON.parse(window.localStorage.getItem(k));
    s.flashcards[id].card_hash = 'ancienne-ver';
    window.localStorage.setItem(k, JSON.stringify(s));
  }, {k: KEY, id: card.id});
  await page.reload({waitUntil: 'networkidle'});
  const details = page.locator(`#${anchor}`);
  await details.locator('summary').click();
  await details.getByText("Ouvrir la carte n'enregistre rien.").waitFor();
  await details.getByRole('button', {name: /^Facile/}).click();
  const record = (await stored()).flashcards[card.id];
  if (record.repetitions !== 1 || record.history.length !== 1 || record.card_hash !== card.version) {
    throw new Error(`la planification de l ancienne version a été reprise : ${JSON.stringify(record)}`);
  }
  return 'reprise à la première répétition';
});

await check('« Oublier cette carte » efface sa planification', async () => {
  await page.locator(`#${anchor}`).getByRole('button', {name: 'Oublier cette carte'}).click();
  if ((await stored()).flashcards[card.id]) {
    throw new Error('la carte est toujours planifiée');
  }
  return 'entrée supprimée';
});

await check('un état v4 migre vers v5 sans rien perdre, et /progression liste la carte due', async () => {
  const day = await today();
  await page.evaluate(({k}) => {
    window.localStorage.setItem(k, JSON.stringify({
      schema_version: 4, attempts: [{question_id: 'QST-x', question_version: 1, official_item: 'OIT-x', correct: true, chosen: [], answered_at: '2026-10-01T00:00:00Z', mode: 'practice'}],
      sessions: [], practice_sessions: [], comprehension: [], revision: {done: {}, settings: null},
    }));
  }, {k: KEY});
  await page.goto(url(coursePath), {waitUntil: 'networkidle'});
  const details = page.locator(`#${anchor}`);
  await details.locator('summary').click();
  await details.getByRole('button', {name: /^À revoir/}).click();
  // Make it due today, as it would be tomorrow.
  await page.evaluate(({k, id, d}) => {
    const s = JSON.parse(window.localStorage.getItem(k));
    s.flashcards[id].due_on = d;
    window.localStorage.setItem(k, JSON.stringify(s));
  }, {k: KEY, id: card.id, d: day});
  const state = await stored();
  if (state.attempts.length !== 1 || state.schema_version !== STORAGE_VERSION) {
    throw new Error('la migration a perdu les tentatives ou n a pas changé de version');
  }
  await page.goto(url('/progression'), {waitUntil: 'networkidle'});
  const main = page.locator('main');
  await main.getByText(/1 à revoir aujourd'hui/).waitFor();
  const link = main.getByRole('link', {name: card.front});
  if ((await link.count()) !== 1) {
    throw new Error('la carte due n est pas listée');
  }
  const href = await link.getAttribute('href');
  if (!href.endsWith(`#${anchor}`)) {
    throw new Error(`le lien ne mène pas à la carte : ${href}`);
  }
  return 'tentative conservée, carte listée avec son ancre';
});

await check('exporter puis importer restitue l état ; un JSON étranger est refusé', async () => {
  page.on('dialog', (d) => d.accept());
  const before = await stored();
  await page.getByRole('button', {name: /Exporter ma progression/}).click();
  const exported = await page.locator('#export-output').inputValue();
  await page.evaluate((k) => window.localStorage.removeItem(k), KEY);
  await page.locator('#import-input').fill('{"hello": 1}');
  await page.getByRole('button', {name: 'Importer'}).click();
  await page.getByText(/Import refusé/).waitFor();
  await page.locator('#import-input').fill(exported);
  await page.getByRole('button', {name: 'Importer'}).click();
  await page.getByText(/Progression importée/).waitFor();
  const after = await stored();
  if (JSON.stringify(after) !== JSON.stringify(before)) {
    throw new Error('l état importé diffère de l état exporté');
  }
  return 'aller-retour identique, JSON sans schema_version refusé';
});

await browser.close();
server.close();
for (const line of ok) console.log(`ok  ${line}`);
for (const line of problems) console.error(`FAIL  ${line}`);
console.log(`${ok.length} ok, ${problems.length} échec(s)`);
process.exit(problems.length > 0 ? 1 : 0);
