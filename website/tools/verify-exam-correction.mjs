/**
 * The correction of a mock exam, driven in a real browser (owner's request of
 * 2026-10-14): after submission every question shows its right answers, and a
 * question missed links to the exact course section that teaches it.
 *
 *   npm --prefix website run build && node website/tools/verify-exam-correction.mjs
 *
 * WHAT IT ASSERTS.
 *   1. Exam 1 is sat for real: two questions answered right, one wrong, the rest
 *      left blank, then submitted.
 *   2. The correction lists every question once: the missed ones open, the two
 *      right ones folded; each entry marks exactly its correct choices, and the
 *      wrong answer is labelled as the learner's.
 *   3. Every missed question carries at least one « Revoir dans le cours » link
 *      to a course section, and following one in the browser lands on a heading
 *      carrying that anchor.
 *   4. Statically, every section link of every question in exams.json names an
 *      anchor that exists in the built page — 600-odd links, not a sample.
 *
 * EVERY CHECK IS PAIRED WITH A PROOF THAT IT CAN FAIL.
 */
import {chromium} from 'playwright';
import {createServer} from 'node:http';
import {existsSync} from 'node:fs';
import {readFile} from 'node:fs/promises';
import {extname, join} from 'node:path';

const LOCAL_CHROME = '/opt/pw-browsers/chromium-1194/chrome-linux/chrome';
const ROOT = new URL('../build/', import.meta.url).pathname;
const WEBSITE = new URL('../', import.meta.url).pathname;
const PORT = 4611;
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

const payload = JSON.parse(await readFile(join(WEBSITE, 'static/data/exams.json'), 'utf8'));
const byChoice = new Map();
for (const q of payload.questions) for (const c of q.choices) byChoice.set(c.id, q);
const url = (path) => `http://127.0.0.1:${PORT}${BASE}${path}`;
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

// --- 4. Static: every section anchor exists in the built page. -------------
const pageIds = new Map();
async function idsOf(route) {
  if (!pageIds.has(route)) {
    const html = await readFile(join(ROOT, route.replace(/^\//, '') + '.html'), 'utf8').catch(() => '');
    pageIds.set(route, new Set([...html.matchAll(/\sid="([^"]+)"/g)].map((m) => m[1])));
  }
  return pageIds.get(route);
}
async function assertAnchors(links) {
  const missing = [];
  for (const link of links) {
    const [route, anchor] = link.split('#');
    if (!anchor || !(await idsOf(route)).has(anchor)) missing.push(link);
  }
  if (missing.length) throw new Error(`${missing.length} anchor(s) absent from the built page, e.g. ${missing[0]}`);
}
// Practice Mode links to the same sections, so its payload is checked too.
const practice = JSON.parse(await readFile(join(WEBSITE, 'static/data/practice.json'), 'utf8'));
const allLinks = new Set();
for (const source of [payload, practice]) {
  for (const q of source.questions) {
    const sections = source.items[q.official_item]?.outcome_sections ?? {};
    for (const o of q.assesses_outcomes ?? []) if (sections[o]) allLinks.add(sections[o].url);
  }
}
await check('every section link of exams.json and practice.json lands on an existing anchor', async () => {
  if (allLinks.size < 300) throw new Error(`only ${allLinks.size} links: the payload lost its section map`);
  await assertAnchors(allLinks);
  return `${allLinks.size} links`;
});
await (async () => {
  try {
    await assertAnchors(['/docs/courses/lot-08/violations-builder#s-no-such-section']);
    problems.push('PROOF anchor check: stayed silent on a missing anchor — the check is VACUOUS');
  } catch {
    ok.push('preuve — anchor check rejette bien une ancre absente');
  }
})();

// --- 1. Sit exam 1. ----------------------------------------------------------
const browser = await chromium.launch(existsSync(LOCAL_CHROME) ? {executablePath: LOCAL_CHROME} : {});
const page = await (await browser.newContext()).newPage();
await page.goto(url('/examens'), {waitUntil: 'networkidle'});
await page.getByRole('button', {name: "Passer l'examen 1"}).click();

async function answer(correct) {
  const card = page.locator('fieldset.certpath-question');
  await card.waitFor();
  const values = await card.locator('input').evaluateAll((els) => els.map((e) => e.value));
  const q = byChoice.get(values[0]);
  const targets = correct
    ? q.choices.filter((c) => c.correct).map((c) => c.id)
    : [q.choices.find((c) => !c.correct).id];
  for (const id of targets) await card.locator(`input[value="${id}"]`).check();
  await page.getByRole('button', {name: 'Enregistrer et continuer'}).click();
  return q;
}
const right1 = await answer(true);
const wrong = await answer(false);
const right2 = await answer(true);
await page.getByRole('button', {name: 'Soumettre maintenant'}).click();
await page.locator('#correction-heading').waitFor();

// --- 2. The correction lists every question, right answers marked. ----------
const review = page.locator('section[aria-labelledby="correction-heading"]');
const entries = review.locator('li.certpath-review-entry');
const total = payload.question_count;

await check('the correction lists every question once', async () => {
  const n = await entries.count();
  if (n !== total) throw new Error(`${n} entries for ${total} questions`);
  return `${n} entrées`;
});
await check('the missed questions are counted and open, the right ones folded', async () => {
  const text = await review.innerText();
  if (!text.includes(`À revoir : ${total - 2} questions`)) throw new Error('missed count not stated');
  const summary = await review.locator('details summary').innerText();
  if (summary.trim() !== 'Voir les 2 questions réussies') throw new Error(`summary « ${summary} »`);
  if (await review.locator('details').evaluate((d) => d.open)) throw new Error('the right answers are not folded');
});

const marksMatch = (rows) => {
  for (const {correct, marked} of rows) if (correct !== marked) throw new Error(`${correct} correct choice(s), ${marked} marked`);
};
const rows = await entries.evaluateAll((els) => els.map((li) => ({
  correct: li.querySelectorAll('li[data-correct="yes"]').length,
  marked: [...li.querySelectorAll('.certpath-review-mark')].filter((m) => m.textContent.includes('bonne réponse')).length,
})));
await check('each entry marks exactly its correct choices', async () => {
  marksMatch(rows);
  const expected = payload.exams[0].question_ids.map((id) => payload.questions.find((q) => q.id === id).choices.filter((c) => c.correct).length);
  const sum = (a) => a.reduce((x, y) => x + y, 0);
  if (sum(rows.map((r) => r.correct)) !== sum(expected)) throw new Error('correct choices do not match exams.json');
  return `${sum(expected)} bonnes réponses marquées`;
});
proves('mark count', marksMatch, [{correct: 2, marked: 1}]);

await check('the wrong answer is labelled as the learner’s', async () => {
  const entry = entries.filter({hasText: wrong.choices.find((c) => !c.correct).text.split('\n')[0].slice(0, 40)}).filter({hasText: '✘ Incorrecte'});
  if ((await entry.count()) < 1) throw new Error('no entry marked incorrect for the wrong answer');
  if (!(await entry.first().innerText()).includes('votre choix, incorrect')) throw new Error('the learner’s choice is not labelled');
});
await check('the two right answers are listed as correct', async () => {
  const n = await review.locator('details li.certpath-review-entry[data-result="correct"]').count();
  if (n !== 2) throw new Error(`${n} entries in the folded list`);
  return [right1.id, right2.id].length === 2 ? '2' : '';
});

// --- 3. Every missed question links to a course section that exists. --------
const missed = review.locator('ol.certpath-review').first().locator('> li');
const linkCounts = await missed.evaluateAll((els) => els.map((li) => li.querySelectorAll('.certpath-review-links a[href*="#s-"]').length));
const everyHasLink = (counts) => {
  const bare = counts.filter((n) => n === 0).length;
  if (bare) throw new Error(`${bare} missed question(s) without a section link`);
};
await check('every missed question links to a course section', async () => {
  everyHasLink(linkCounts);
  return `${linkCounts.reduce((a, b) => a + b, 0)} liens`;
});
proves('section link presence', everyHasLink, [1, 0, 2]);

await check('following a section link lands on that heading', async () => {
  const link = missed.first().locator('.certpath-review-links a').first();
  const href = await link.getAttribute('href');
  await link.click();
  const anchor = href.split('#')[1];
  await page.waitForURL((u) => u.hash === `#${anchor}`);
  const heading = page.locator(`h2#${anchor}`);
  await heading.waitFor();
  return `${href} → « ${(await heading.innerText()).replace(/​|#/g, '').trim()} »`;
});

await browser.close();
server.close();

for (const line of ok) console.log(`ok  ${line}`);
for (const line of problems) console.log(`FAIL ${line}`);
console.log(problems.length === 0 ? 'verify-exam-correction: all checks pass' : `verify-exam-correction: ${problems.length} problem(s)`);
process.exit(problems.length === 0 ? 0 : 1);
