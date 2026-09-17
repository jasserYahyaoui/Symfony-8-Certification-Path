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

/**
 * Lot 27: the Practice Mode states that only exist after an interaction.
 *
 * Until now this file only checked the state a screen is in on arrival, and
 * said so honestly for the mocks. That is not enough for Practice Mode, whose whole
 * point is what appears AFTER an answer: the correction, the code blocks inside
 * it, and the end-of-series report. Auditing only the empty question form would
 * be auditing the one state the learner spends the least time in.
 *
 * The states are reached through the product's own behaviour, not a test hook.
 * Seeding one wrong attempt in localStorage and ticking "rejouer mes points
 * faibles" narrows the queue to a single known question — the feature exists
 * for learners, and reusing it keeps the audited DOM the real one.
 */
const practicePayload = JSON.parse(
  await readFile(join(WEBSITE, 'static/data/practice.json'), 'utf8'),
);

function pickQuestion(mode) {
  const found = practicePayload.questions.find(
    (q) => q.answer_mode === mode && q.language === 'en',
  );
  if (!found) {
    throw new Error(`a11y: no ${mode} English question in practice.json`);
  }
  return found;
}

/** Seed a weakness for exactly one question, at the current storage version. */
function seedWeakness(questionId, officialItem) {
  return {
    schema_version: 3,
    attempts: [{
      question_id: questionId,
      question_version: 1,
      official_item: officialItem,
      correct: false,
      chosen: [],
      answered_at: '2026-01-01T00:00:00.000Z',
      mode: 'practice',
    }],
    sessions: [],
    practice_sessions: [],
    revision: {done: {}, settings: null},
  };
}

async function openWeakSeries(page, question) {
  await page.getByLabel('Rejouer mes points faibles').check();
  await page.locator('fieldset.certpath-question').waitFor();
}

/** Answer the single-answer question, correctly or not, then submit. */
function answerThen(question, wantCorrect, after) {
  return async (page) => {
    await openWeakSeries(page, question);
    const target = question.choices.find((c) => c.correct === wantCorrect);
    await page.locator(`#choice-${target.id}`).check();
    await page.getByRole('button', {name: 'Valider ma réponse'}).click();
    await page.locator('.certpath-feedback').waitFor();
    if (after) {
      await after(page);
    }
  };
}

const SINGLE = pickQuestion('single');
const MULTIPLE = pickQuestion('multiple');

// One page per interactive surface, plus a generated item page carrying the
// <details> flashcards introduced by Lot 0.5.
const PAGES = [
  ['landing', '/'],
  ['docs index', '/docs'],
  ['item page with flashcards', '/docs/courses/lot-02/status-codes'],
  // The same page shape once its deck declares levels: the flashcard block
  // then emits `###` headings under its `##`, which is precisely what the
  // heading-order check exists to catch. Auditing only a deck without levels
  // would leave that structure unaudited while reporting a full pass.
  ['item page with levelled flashcards', '/docs/courses/lot-02/http-specification-rfc-9110'],
  // §5 glossary: a generated table, so its header scope and reading order
  // are worth auditing rather than assumed.
  ['glossary', '/docs/syllabus/glossary'],
  // AUD-08 found these two served in production, smoke-tested, and never
  // audited — while the comments below already excused the mock results
  // screens by appealing to "the audited coverage page". The justification
  // now rests on an audit that happens. Both are generated tables, the same
  // shape the glossary above is audited for.
  ['coverage', '/docs/syllabus/coverage'],
  ['exclusions', '/docs/syllabus/exclusions'],
  // Certification Readiness: a generated table published at the docs root.
  // AUD-08's TECH-4 did not see it at first, because it only scanned
  // docs/syllabus/ - that blind spot is fixed in the audit too.
  ['readiness', '/docs/readiness'],
  // Plan de révision candidat. Quatre pages générées depuis docs/revision/ :
  // prose dense, tableaux, et pour le calendrier ~1 100 lignes de listes
  // imbriquées — la surface la plus longue du site. Auditées parce qu'elles
  // sont atteignables depuis la navigation principale, pas parce qu'on suppose
  // qu'elles ressemblent aux autres pages générées.
  ['roadmap de révision', '/docs/revision/roadmap'],
  ['calendrier de révision', '/docs/revision/calendar'],
  ['contrôles de maîtrise', '/docs/revision/checkpoints'],
  ['PRÊT-CANDIDAT', '/docs/revision/readiness'],
  // L'agenda : une grille CSS de créneaux horaires, dont chaque événement est
  // un bouton. C'est la surface la plus interactive du site après les modes
  // d'entraînement, et la seule qui empile du texte blanc sur sept fonds
  // colorés — donc celle où un contraste insuffisant passerait le plus
  // facilement inaperçu à l'œil.
  ['agenda — vue mois', '/calendar'],
  // Les vues semaine et jour sont derrière un clic ; sans ces deux URL, la
  // grille horaire — celle qui empile 410 boutons positionnés en lignes de
  // grille — ne serait jamais auditée, et c'est la plus susceptible de casser.
  ['agenda — vue semaine', '/calendar?view=week&date=2026-11-23'],
  ['agenda — vue jour', '/calendar?view=day&date=2026-11-24'],
  ['practice', '/practice'],
  // The six Practice Mode states Lot 27 requires to be audited rather than
  // assumed. Each drives the real UI; none uses a test-only code path.
  ['practice — après bonne réponse', '/practice',
    {state: () => seedWeakness(SINGLE.id, SINGLE.official_item),
     drive: answerThen(SINGLE, true)}],
  ['practice — après mauvaise réponse', '/practice',
    {state: () => seedWeakness(SINGLE.id, SINGLE.official_item),
     drive: answerThen(SINGLE, false)}],
  ['practice — question multiple', '/practice',
    {state: () => seedWeakness(MULTIPLE.id, MULTIPLE.official_item),
     drive: async (page) => {
       await openWeakSeries(page, MULTIPLE);
     }}],
  ['practice — bilan de série', '/practice',
    {state: () => seedWeakness(SINGLE.id, SINGLE.official_item),
     drive: answerThen(SINGLE, false, async (page) => {
       await page.getByRole('button', {name: 'Voir mon bilan'}).click();
       await page.locator('.certpath-results').waitFor();
     })}],
  ['practice — revue des erreurs ouverte', '/practice',
    {state: () => seedWeakness(SINGLE.id, SINGLE.official_item),
     drive: answerThen(SINGLE, false, async (page) => {
       await page.getByRole('button', {name: 'Voir mon bilan'}).click();
       await page.locator('.certpath-review').first().waitFor();
       await page.locator('.certpath-review summary').first().click();
     })}],
  ['exam', '/exam'],
  // The simulations hub: a generated comparison table plus one section per
  // mock. It reaches a rendered page, so it is a gate surface, not a note.
  ['simulations', '/simulations'],
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
  ['mock 3', '/mock-3'],
  ['mock 5', '/mock-5'],
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
for (const [name, path, script] of PAGES) {
  // axe-core/playwright requires a real context, not the default page.
  // Interactive states are audited at phone width: a correction that forces a
  // horizontal page scroll is a real defect and only shows up narrow.
  const context = await browser.newContext(
    script ? {viewport: {width: 390, height: 780}} : {},
  );
  const page = await context.newPage();
  if (script?.state) {
    const seeded = script.state();
    // Idempotent: addInitScript re-runs on every navigation, and a state the
    // page itself wrote must survive one.
    await page.addInitScript(
      ([key, value]) => {
        if (!window.localStorage.getItem(key)) window.localStorage.setItem(key, value);
      },
      ['certpath.learner-state', JSON.stringify(seeded)],
    );
  }
  await page.goto(`http://127.0.0.1:${PORT}${BASE}${path}`, {waitUntil: 'networkidle'});
  if (script?.drive) {
    await script.drive(page);
  }

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
    // Lot 27: a code block may scroll; the document may not. A learner on a
    // phone must never be pushed sideways by one wide snippet.
    if (document.documentElement.scrollWidth > document.documentElement.clientWidth + 1) {
      out.push('page-scrolls-horizontally');
    }
    // A scrollable region has to be reachable by keyboard, or its content is
    // unreadable without a mouse.
    for (const pre of document.querySelectorAll('pre.certpath-code-block')) {
      if (pre.scrollWidth > pre.clientWidth && pre.tabIndex < 0) {
        out.push('unfocusable-scrolling-code');
      }
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
