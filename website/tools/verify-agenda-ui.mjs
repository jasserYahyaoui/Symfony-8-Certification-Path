/**
 * The three agenda behaviours, driven in a real browser.
 *
 * A build that compiles and an audit that passes both say the page renders.
 * Neither says a checkbox survives a reload, that a course link points at a
 * page that exists, or that changing the exam date moves anything. Those are
 * the three things asked for, so those are the three things exercised here.
 *
 *   node website/tools/verify-agenda-ui.mjs
 */
import {chromium} from 'playwright';
import {createServer} from 'node:http';
import {existsSync} from 'node:fs';
import {readFile} from 'node:fs/promises';
import {extname, join} from 'node:path';

// Same pinning as the accessibility audit: the sandbox ships a Chromium whose
// build number the bundled Playwright does not expect, so point at it when it
// is there and let Playwright resolve its own otherwise (CI installs one).
const LOCAL_CHROME = '/opt/pw-browsers/chromium-1194/chrome-linux/chrome';

const ROOT = new URL('../build/', import.meta.url).pathname;
const PORT = 4601;
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

const url = (path) => `http://127.0.0.1:${PORT}${BASE}${path}`;
const browser = await chromium.launch(
  existsSync(LOCAL_CHROME) ? {executablePath: LOCAL_CHROME} : {},
);
const context = await browser.newContext();
const page = await context.newPage();
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

// ------------------------------------------------------------- links
await check('les liens de cours mènent à une page qui existe', async () => {
  await page.goto(url('/calendar?view=day&date=2026-10-01'), {waitUntil: 'networkidle'});
  const hrefs = await page.$$eval('main a[href*="/docs/courses/"]', (as) =>
    [...new Set(as.map((a) => a.getAttribute('href')))],
  );
  if (hrefs.length === 0) {
    throw new Error('aucun lien de cours sur une journée de nouveaux items');
  }
  for (const href of hrefs) {
    const res = await page.request.get(`http://127.0.0.1:${PORT}${href}`);
    if (!res.ok()) {
      throw new Error(`${href} renvoie HTTP ${res.status()}`);
    }
  }
  return `${hrefs.length} liens, tous en 200`;
});

// ------------------------------------------------------------- ticking
await check('cocher une session survit au rechargement', async () => {
  await page.goto(url('/calendar?view=day&date=2026-10-01'), {waitUntil: 'networkidle'});
  const box = page.locator('main input[type="checkbox"]').first();
  await box.waitFor();
  if (await box.isChecked()) {
    throw new Error('la case est déjà cochée avant toute action');
  }
  await box.check();
  const before = await page.locator('main').innerText();
  if (!/1 \/ \d+\s+sessions cochées/.test(before)) {
    throw new Error('le compteur ne rend pas compte de la coche');
  }

  await page.reload({waitUntil: 'networkidle'});
  const again = page.locator('main input[type="checkbox"]').first();
  await again.waitFor();
  if (!(await again.isChecked())) {
    throw new Error('la coche est perdue au rechargement');
  }

  const stored = await page.evaluate(() =>
    JSON.parse(window.localStorage.getItem('certpath.learner-state') ?? '{}'),
  );
  if (Object.keys(stored?.revision?.done ?? {}).length !== 1) {
    throw new Error('la coche n est pas dans l état apprenant');
  }
  if (stored.schema_version !== 2) {
    throw new Error(`schema_version ${stored.schema_version}, attendu 2`);
  }

  await again.uncheck();
  return 'cochée, rechargée, toujours cochée, puis décochée';
});

// ------------------------------------------------------------- replanning
await check("changer la date d'examen replanifie le calendrier", async () => {
  await page.goto(url('/calendar?view=month&date=2026-10-01'), {waitUntil: 'networkidle'});

  const examBefore = await page.inputValue('input[type="date"] >> nth=1');
  if (examBefore !== '2026-12-15') {
    throw new Error(`date d examen initiale ${examBefore}, attendu 2026-12-15`);
  }

  await page.fill('input[type="date"] >> nth=1', '2027-01-30');
  await page.getByRole('button', {name: 'Replanifier'}).click();
  await page.waitForSelector('text=Plan replanifié');

  const note = await page.locator('p', {hasText: 'Plan replanifié'}).last().innerText();
  if (!note.includes('2027-01-30')) {
    throw new Error(`la note ne cite pas la nouvelle date : ${note}`);
  }

  // The whole plan must move, not just the label: the exam month must now hold
  // the exam milestone, and it did not before.
  await page.goto(url('/calendar?view=day&date=2027-01-30'), {waitUntil: 'networkidle'});
  const dayText = await page.locator('main').innerText();
  if (!dayText.includes('EXAMEN Symfony 8.0')) {
    throw new Error("le 30 janvier 2027 ne porte pas l'examen après replanification");
  }

  await page.goto(url('/calendar'), {waitUntil: 'networkidle'});
  await page.getByRole('button', {name: 'Revenir au plan publié'}).click();
  await page.waitForSelector('text=Plan replanifié', {state: 'detached'});
  const back = await page.inputValue('input[type="date"] >> nth=1');
  if (back !== '2026-12-15') {
    throw new Error(`retour au plan publié : ${back}`);
  }
  return 'examen déplacé au 2027-01-30, plan recalculé, puis retour au plan publié';
});

// ------------------------------------------------- an impossible plan is refused
await check('un plan infaisable est refusé, pas affiché de travers', async () => {
  await page.goto(url('/calendar'), {waitUntil: 'networkidle'});
  await page.fill('input[type="date"] >> nth=1', '2026-10-20');
  await page.getByRole('button', {name: 'Replanifier'}).click();
  await page.waitForSelector('[role="alert"]');
  const alert = await page.locator('[role="alert"]').first().innerText();
  if (!alert.includes('infaisable')) {
    throw new Error(`alerte inattendue : ${alert}`);
  }
  await page.getByRole('button', {name: 'Revenir au plan publié'}).click();
  return 'une date trop proche est refusée avec un motif';
});

await browser.close();
server.close();

for (const line of ok) {
  console.log(`ok    ${line}`);
}
for (const line of problems) {
  console.error(`FAIL  ${line}`);
}
process.exit(problems.length === 0 ? 0 : 1);
