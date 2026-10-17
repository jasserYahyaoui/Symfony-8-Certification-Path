/**
 * Remediation P3.2, driven in a real browser: the reasoning asked before the
 * correction in Practice Mode, and the delayed comprehension check.
 *
 * What is asked for: an explanation requested before the reveal, then compared
 * with the expected one; checks that come back after the reading, not only
 * right after it. Each is exercised here, by keyboard where the learner would
 * use one.
 *
 *   node website/tools/verify-transfer-ui.mjs
 */
import {chromium} from 'playwright';
import {createServer} from 'node:http';
import {existsSync} from 'node:fs';
import {readFile} from 'node:fs/promises';
import {extname, join} from 'node:path';

const LOCAL_CHROME = '/opt/pw-browsers/chromium-1194/chrome-linux/chrome';
const ROOT = new URL('../build/', import.meta.url).pathname;
const PORT = 4608;
const BASE = '/Symfony-8-Certification-Path';
const TYPES = {'.html': 'text/html', '.js': 'text/javascript', '.css': 'text/css', '.json': 'application/json', '.svg': 'image/svg+xml'};
const KEY = 'certpath.learner-state';
const COMPREHENSION = JSON.parse(await readFile(join(ROOT, 'data/comprehension.json'), 'utf8'));

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

async function answerFirstChoice() {
  const card = page.locator('fieldset.certpath-question');
  await card.waitFor();
  await card.locator('input').first().check();
  await page.getByRole('button', {name: 'Valider ma réponse'}).click();
}

await check('raisonnement : aucune correction avant l explication', async () => {
  await page.goto(url('/practice'), {waitUntil: 'networkidle'});
  await page.evaluate(() => window.localStorage.clear());
  await page.reload({waitUntil: 'networkidle'});
  await page.getByLabel('Écrire mon raisonnement avant la correction').check();
  await answerFirstChoice();
  const field = page.getByLabel('Avant la correction : pourquoi cette réponse ?');
  await field.waitFor();
  if (!(await field.evaluate((el) => el === document.activeElement))) {
    throw new Error('le champ ne reçoit pas le focus');
  }
  if ((await page.locator('.certpath-verdict').count()) !== 0) {
    throw new Error('la correction est déjà dans le DOM');
  }
  const show = page.getByRole('button', {name: 'Voir la correction'});
  await field.fill('trop court');
  if (!(await show.isDisabled())) {
    throw new Error('une explication de moins de 15 caractères ouvre la correction');
  }
  return 'champ focalisé, correction absente du DOM, bouton bloqué sous 15 caractères';
});

await check('raisonnement : affiché à côté de l explication, auto-évaluation enregistrée', async () => {
  const text = 'Parce que la configuration est lue avant tout le reste.';
  await page.getByLabel('Avant la correction : pourquoi cette réponse ?').fill(text);
  await page.keyboard.press('Tab');
  await page.keyboard.press('Enter');
  await page.locator('.certpath-verdict').waitFor();
  const shown = await page.locator('.certpath-reasoning-text').innerText();
  if (shown !== text) {
    throw new Error(`raisonnement affiché : ${shown}`);
  }
  await page.getByRole('button', {name: 'Non', exact: true}).click();
  await page.getByText(/^Noté/).waitFor();
  const attempts = (await stored()).attempts;
  if (attempts.length !== 1 || attempts[0].reasoning !== 'missed') {
    throw new Error(`tentative : ${JSON.stringify(attempts)}`);
  }
  if (JSON.stringify(await stored()).includes('configuration est lue')) {
    throw new Error('le texte du raisonnement a été enregistré');
  }
  return 'reasoning = missed, texte non stocké';
});

await check('raisonnement : « Je ne sais pas l expliquer » enregistre skipped', async () => {
  await page.getByRole('button', {name: 'Question suivante'}).click();
  await answerFirstChoice();
  await page.getByRole('button', {name: "Je ne sais pas l'expliquer"}).click();
  await page.getByText(/Vous n'avez pas su l'expliquer/).waitFor();
  const attempts = (await stored()).attempts;
  if (attempts.at(-1).reasoning !== 'skipped') {
    throw new Error(`tentative : ${JSON.stringify(attempts.at(-1))}`);
  }
  await page.reload({waitUntil: 'networkidle'});
  if (!(await page.getByLabel('Écrire mon raisonnement avant la correction').isChecked())) {
    throw new Error('l option n est pas retenue');
  }
  return 'skipped, option retenue au rechargement';
});

const lot = 'lot-02';
const lotQuestions = COMPREHENSION.questions.filter((q) => q.lot === lot);
const missed = lotQuestions[3];

await check('contrôle différé : dû 8 jours après, listé sur la progression', async () => {
  const eightDaysAgo = new Date(Date.now() - 8 * 86_400_000).toISOString();
  await page.evaluate(({k, lot, qs, missedId, at}) => {
    window.localStorage.setItem(k, JSON.stringify({
      schema_version: 5, attempts: [], sessions: [], practice_sessions: [], revision: {done: {}, settings: null}, flashcards: {},
      comprehension: qs.map((q) => ({question_id: q.id, question_version: q.version, official_item: q.official_item, lot, correct: q.id !== missedId, answered_at: at})),
    }));
  }, {k: KEY, lot, qs: lotQuestions.slice(0, 5), missedId: missed.id, at: eightDaysAgo});
  await page.goto(url('/progression'), {waitUntil: 'networkidle'});
  await page.getByText(/1 contrôle différé à faire aujourd'hui/).waitFor();
  return '1 dû sur 1 lot contrôlé';
});

await check('contrôle différé : la question manquée revient en premier, la réponse est marquée', async () => {
  await page.goto(url('/controle-differe'), {waitUntil: 'networkidle'});
  await page.getByRole('link', {name: /Lot 02 — contrôle différé/}).click();
  await page.getByText('Question 1 sur 6').waitFor();
  const first = await page.locator('fieldset.certpath-question input').first().getAttribute('value');
  if (!missed.choices.some((c) => c.id === first)) {
    throw new Error('la première question n est pas celle manquée');
  }
  for (const c of missed.choices.filter((c) => c.correct)) {
    await page.locator(`input[value="${c.id}"]`).check();
  }
  await page.getByRole('button', {name: 'Valider ma réponse'}).click();
  await page.locator('.certpath-verdict').waitFor();
  const last = (await stored()).comprehension.at(-1);
  if (last.question_id !== missed.id || last.delayed !== true || last.correct !== true) {
    throw new Error(`réponse enregistrée : ${JSON.stringify(last)}`);
  }
  return 'question manquée servie en 1re, delayed: true';
});

await check('contrôle différé : le suivant attend 14 jours', async () => {
  await page.goto(url('/controle-differe'), {waitUntil: 'networkidle'});
  await page.getByText("Aucun contrôle différé n'est dû aujourd'hui.").waitFor();
  await page.getByText(/intervalle de 14 jours/).waitFor();
  return 'plus dû, prochain à 14 jours';
});

await browser.close();
server.close();
for (const line of ok) console.log(`ok  ${line}`);
for (const line of problems) console.error(`FAIL  ${line}`);
console.log(`${ok.length} ok, ${problems.length} échec(s)`);
process.exit(problems.length > 0 ? 1 : 0);
