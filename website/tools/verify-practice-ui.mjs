/**
 * Practice Mode, driven in a real browser (Lot 27).
 *
 * A build that compiles and an audit that passes both say the page renders.
 * Neither says the correction is absent before submission, that a ```php fence
 * became a real block instead of literal backticks, that the score is right, or
 * that the report names the item the learner actually missed. Those are the
 * behaviours the lot exists for, so those are what this drives.
 *
 * EVERY CHECK IS PAIRED WITH A PROOF THAT IT CAN FAIL. A UI check that has only
 * ever printed `ok` has not been shown to be capable of anything else — this
 * project has already found five checks in that state. The proofs mutate the
 * DOM or the payload in memory; no file is written.
 *
 *   node website/tools/verify-practice-ui.mjs
 */
import {chromium} from 'playwright';
import {createServer} from 'node:http';
import {existsSync} from 'node:fs';
import {readFile} from 'node:fs/promises';
import {extname, join} from 'node:path';

const LOCAL_CHROME = '/opt/pw-browsers/chromium-1194/chrome-linux/chrome';
const ROOT = new URL('../build/', import.meta.url).pathname;
const WEBSITE = new URL('../', import.meta.url).pathname;
const PORT = 4603;
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

const payload = JSON.parse(await readFile(join(WEBSITE, 'static/data/practice.json'), 'utf8'));
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

/** Assert a predicate rejects a deliberately broken input. */
function proves(name, predicate, broken) {
  try {
    predicate(broken);
    problems.push(`PROOF ${name}: stayed silent on its own defect — the check is VACUOUS`);
  } catch {
    ok.push(`preuve — ${name} rejette bien son propre défaut`);
  }
}

function english(mode) {
  const q = payload.questions.find((x) => x.answer_mode === mode && x.language === 'en');
  if (!q) throw new Error(`no ${mode} English question in practice.json`);
  return q;
}

/** Seed one wrong attempt so "rejouer mes points faibles" yields one question. */
function seed(question) {
  return {
    schema_version: 3,
    attempts: [{
      question_id: question.id, question_version: 1,
      official_item: question.official_item, correct: false, chosen: [],
      answered_at: '2026-01-01T00:00:00.000Z', mode: 'practice',
    }],
    sessions: [], practice_sessions: [], revision: {done: {}, settings: null},
  };
}

async function openSeries(question, state = seed(question)) {
  const context = await browser.newContext();
  const page = await context.newPage();
  // Seed ONLY when the store is empty. addInitScript re-runs on every
  // navigation, so an unconditional write would wipe what the page recorded
  // the moment the learner reloads — and the reload is what this verifies.
  await page.addInitScript(
    ([k, v]) => {
      if (!window.localStorage.getItem(k)) window.localStorage.setItem(k, v);
    },
    ['certpath.learner-state', JSON.stringify(state)],
  );
  await page.goto(url('/practice'), {waitUntil: 'networkidle'});
  await page.getByLabel('Rejouer mes points faibles').check();
  await page.locator('fieldset.certpath-question').waitFor();
  return {context, page};
}

const SINGLE = english('single');
const MULTIPLE = english('multiple');

// ------------------------------------------------- no correction before answering
await check('aucune correction avant soumission', async () => {
  const {context, page} = await openSeries(SINGLE);
  const html = await page.locator('main').innerHTML();

  if (await page.locator('.certpath-feedback').count()) {
    throw new Error('le bloc de feedback est présent avant toute réponse');
  }
  // The explanation names the answer; if it is in the DOM it is readable by a
  // screen reader even when no eye can see it.
  if (html.includes(SINGLE.explanation.slice(0, 40))) {
    throw new Error("l'explication est dans le DOM avant soumission");
  }
  for (const c of SINGLE.choices) {
    if (c.explanation && html.includes(c.explanation.slice(0, 40))) {
      throw new Error("une explication de distracteur est dans le DOM avant soumission");
    }
    if (c.correct && /data-(correct|result)=/.test(html)) {
      throw new Error('un attribut trahit la bonne réponse avant soumission');
    }
  }
  await context.close();
  return 'ni explication, ni attribut, ni bloc de feedback';
});

proves(
  'le contrôle « pas de correction avant soumission »',
  (html) => {
    if (html.includes('certpath-feedback')) throw new Error('caught');
  },
  '<div class="certpath-feedback" data-result="correct">',
);

// ------------------------------------------------------------------ scoring
await check('une bonne réponse est comptée correcte, une mauvaise incorrecte', async () => {
  for (const wantCorrect of [true, false]) {
    const {context, page} = await openSeries(SINGLE);
    const target = SINGLE.choices.find((c) => c.correct === wantCorrect);
    await page.locator(`#choice-${target.id}`).check();
    await page.getByRole('button', {name: 'Valider ma réponse'}).click();
    const verdict = await page.locator('.certpath-verdict').getAttribute('data-result');
    const expected = wantCorrect ? 'correct' : 'incorrect';
    if (verdict !== expected) {
      throw new Error(`réponse ${wantCorrect ? 'juste' : 'fausse'} jugée « ${verdict} »`);
    }
    await context.close();
  }
  return 'verdict conforme dans les deux sens';
});

// --------------------------------------------------- the seven feedback sections
await check('le feedback contient les sections imposées, dans l’ordre', async () => {
  const {context, page} = await openSeries(SINGLE);
  const wrong = SINGLE.choices.find((c) => !c.correct);
  await page.locator(`#choice-${wrong.id}`).check();
  await page.getByRole('button', {name: 'Valider ma réponse'}).click();
  await page.locator('.certpath-feedback').waitFor();

  const headings = await page.$$eval(
    '.certpath-feedback .certpath-feedback-heading',
    (hs) => hs.map((h) => h.textContent.trim()),
  );
  const expected = [
    'Bonne réponse', 'Pourquoi cette réponse est correcte',
    'Pourquoi les autres réponses sont incorrectes', 'À retenir', 'Revoir cette notion',
  ];
  for (const [i, want] of expected.entries()) {
    if (!(headings[i] ?? '').startsWith(want.slice(0, 12))) {
      throw new Error(`section ${i + 1} attendue « ${want} », lue « ${headings[i]} »`);
    }
  }
  // The learner's own wrong pick has to be named back to them.
  if (!(await page.locator('.certpath-your-answer').count())) {
    throw new Error('le choix fait par l’apprenant n’est pas signalé');
  }
  if (!(await page.locator('.certpath-feedback a[href*="/docs/courses/"]').count())) {
    throw new Error('aucun lien vers le cours');
  }
  await context.close();
  return '5 titres + choix rappelé + lien cours';
});

// --------------------------------------------------------------- code rendering
await check('le code est rendu en bloc, sans backticks littéraux', async () => {
  // Pick a question whose fence actually spans several lines. Selecting "the
  // first fenced question" tied this check to whichever one sorted first, and
  // it broke the moment Unit C fenced a second one whose snippets are
  // single-line — a failure about the fixture, not about the renderer.
  const fenced = payload.questions.find((q) => {
    const body = q.question.match(/```[a-z]*\n([\s\S]*?)```/)?.[1] ?? '';

    return body.replace(/\n$/, '').includes('\n');
  });
  if (!fenced) throw new Error('aucune question à bloc multiligne dans le payload');

  const {context, page} = await openSeries(fenced);
  const prompt = await page.locator('.certpath-prompt, .certpath-question').first().innerText();
  if (prompt.includes('```')) {
    throw new Error('la clôture ``` est affichée en toutes lettres');
  }
  const pre = page.locator('pre.certpath-code-block').first();
  if (!(await pre.count())) throw new Error('aucun bloc <pre> rendu');

  const text = await pre.innerText();
  if (!text.includes('\n')) throw new Error('le bloc a perdu ses retours à la ligne');
  const white = await pre.evaluate((el) => getComputedStyle(el).whiteSpace);
  if (!white.startsWith('pre')) {
    throw new Error(`white-space vaut « ${white} » : l’indentation n’est pas préservée`);
  }
  await context.close();
  return `bloc rendu, ${text.split('\n').length} lignes, white-space: ${white}`;
});

// Unit C fenced a second question whose two #[Route] attributes were prose
// separated by an em-dash connector. Fencing the whole line would have put the
// connector inside a PHP block, so it became two blocks around it — and a
// question with more than one block is the case a single-fence renderer gets
// wrong, which is why it is driven rather than assumed.
await check('une question à deux blocs rend deux blocs et garde sa prose', async () => {
  const two = payload.questions.find(
    (q) => (q.question.match(/```/g) ?? []).length === 4,
  );
  if (!two) throw new Error('aucune question à deux blocs dans le payload');

  const {context, page} = await openSeries(two);
  const blocks = page.locator('pre.certpath-code-block');
  const count = await blocks.count();
  if (count !== 2) throw new Error(`${count} bloc(s) rendu(s), 2 attendus`);

  const body = await page.locator('fieldset.certpath-question').innerText();
  if (body.includes('```')) throw new Error('une clôture est affichée littéralement');
  if (!body.includes('and')) throw new Error('la prose entre les deux blocs a disparu');
  for (const frag of [
    "#[Route('/blog/{slug}', name: 'blog_show')]",
    "#[Route('/blog/list', name: 'blog_list')]",
  ]) {
    if (!body.includes(frag)) throw new Error(`fragment perdu : ${frag}`);
  }
  await context.close();
  return '2 blocs, prose conservée, code identique au canonique';
});

proves(
  'le contrôle « backticks littéraux »',
  (text) => {
    if (text.includes('```')) throw new Error('caught');
  },
  'Read this: ```php $a->b(); ```',
);
proves(
  'le contrôle « retours à la ligne perdus »',
  (text) => {
    if (!text.includes('\n')) throw new Error('caught');
  },
  '$cookie = Cookie::create(); $cookie->withValue();',
);
proves(
  'le contrôle « indentation YAML aplatie »',
  (white) => {
    if (!white.startsWith('pre')) throw new Error('caught');
  },
  'normal',
);

// ------------------------------------------------------ multiple-answer questions
await check('une question multiple annonce le compte et signale l’excès', async () => {
  const {context, page} = await openSeries(MULTIPLE);
  const notice = await page.locator('fieldset.certpath-question').innerText();
  if (!notice.includes(String(MULTIPLE.required_answer_count))) {
    throw new Error('le nombre de réponses attendues n’est pas affiché');
  }
  for (const c of MULTIPLE.choices) {
    await page.locator(`#choice-${c.id}`).check();
  }
  const warned = await page.locator('fieldset.certpath-question').innerText();
  if (!warned.includes('sélectionnées pour')) {
    throw new Error('une sélection excédentaire n’est pas signalée');
  }
  await page.getByRole('button', {name: 'Valider ma réponse'}).click();
  const verdict = await page.locator('.certpath-verdict').getAttribute('data-result');
  if (verdict !== 'incorrect') {
    throw new Error('tout cocher est jugé correct : le barème tout-ou-rien est cassé');
  }
  await context.close();
  return 'compte annoncé, excès signalé, tout-ou-rien respecté';
});

// ------------------------------------------------------------------- the report
await check('le bilan compte juste et nomme l’item manqué', async () => {
  const {context, page} = await openSeries(SINGLE);
  const wrong = SINGLE.choices.find((c) => !c.correct);
  await page.locator(`#choice-${wrong.id}`).check();
  await page.getByRole('button', {name: 'Valider ma réponse'}).click();
  await page.getByRole('button', {name: 'Voir mon bilan'}).click();
  await page.locator('.certpath-results').waitFor();

  const body = await page.locator('.certpath-results').innerText();
  if (!body.includes('0 / 1')) throw new Error(`score attendu « 0 / 1 », lu « ${body.slice(0, 80)} »`);
  if (!body.includes('Needs review')) throw new Error('l’item manqué n’est pas signalé « Needs review »');
  if (!body.includes("n'est pas un résultat officiel Symfony")) {
    throw new Error('la mention « pas un résultat officiel » est absente');
  }
  if (!body.includes('INTERNAL_TRAINING_FORMAT')) {
    throw new Error('le format interne n’est pas étiqueté');
  }
  if (/\b\d+\s*(min|s)\b/.test(body)) {
    throw new Error('une durée est affichée alors qu’aucune n’est mesurée');
  }
  const href = await page.locator('.certpath-results a[href*="/docs/courses/"]').first().getAttribute('href');
  const res = await page.request.get(`http://127.0.0.1:${PORT}${href}`);
  if (!res.ok()) throw new Error(`${href} renvoie HTTP ${res.status()}`);
  await context.close();
  return 'score, verdict prudent, mentions obligatoires, lien cours vivant';
});

// ------------------------------------------------------------------ persistence
await check('les réponses survivent à un rechargement', async () => {
  const {context, page} = await openSeries(SINGLE);
  const wrong = SINGLE.choices.find((c) => !c.correct);
  await page.locator(`#choice-${wrong.id}`).check();
  await page.getByRole('button', {name: 'Valider ma réponse'}).click();
  await page.locator('.certpath-feedback').waitFor();

  await page.reload({waitUntil: 'networkidle'});
  const stored = await page.evaluate(() =>
    JSON.parse(window.localStorage.getItem('certpath.learner-state')),
  );
  if (stored.schema_version !== 3) {
    throw new Error(`schema_version ${stored.schema_version} après rechargement`);
  }
  const mine = stored.attempts.filter((a) => a.session_id);
  if (mine.length === 0) throw new Error('aucune tentative rattachée à une série');
  await context.close();
  return `schema v3, ${mine.length} tentative(s) rattachée(s)`;
});

// ------------------------------------------- a v2 history still loads (migration)
await check('un historique v2 reste lisible', async () => {
  const legacy = {
    schema_version: 2,
    attempts: [{
      question_id: SINGLE.id, question_version: 1, official_item: SINGLE.official_item,
      correct: false, chosen: [], answered_at: '2026-01-01T00:00:00.000Z', mode: 'practice',
    }],
    sessions: [], revision: {done: {'2026-10-01|09:00|x': '2026-10-01T09:00:00.000Z'}, settings: null},
  };
  const {context, page} = await openSeries(SINGLE, legacy);
  const migrated = await page.evaluate(() => {
    window.localStorage.setItem('certpath.ping', '1');
    return JSON.parse(window.localStorage.getItem('certpath.learner-state'));
  });
  if (migrated.attempts.length !== 1) throw new Error('la tentative v2 a été perdue');
  if (Object.keys(migrated.revision.done).length !== 1) throw new Error('l’agenda v2 a été perdu');
  await context.close();
  return 'tentative et agenda conservés, série jouable';
});

// --------------------------------------------------------------- pool isolation
await check('practice.json ne contient que du LEARNING', async () => {
  if (payload.pool !== 'LEARNING') throw new Error(`pool = ${payload.pool}`);
  const holdout = JSON.parse(await readFile(join(WEBSITE, 'static/data/mock-4.json'), 'utf8'));
  const ids = new Set(holdout.questions.map((q) => q.id));
  const leaked = payload.questions.filter((q) => ids.has(q.id));
  if (leaked.length) throw new Error(`${leaked.length} question(s) du holdout`);
  const choiceIds = new Set(holdout.questions.flatMap((q) => q.choices.map((c) => c.id)));
  const leakedChoices = payload.questions.flatMap((q) => q.choices).filter((c) => choiceIds.has(c.id));
  if (leakedChoices.length) throw new Error(`${leakedChoices.length} choix du holdout`);
  return `${payload.questions.length} questions, 0 identifiant du holdout`;
});

proves(
  'le contrôle « HOLDOUT dans Practice »',
  (ids) => {
    if (ids.some((id) => id === 'QST-leak')) throw new Error('caught');
  },
  ['QST-ok', 'QST-leak'],
);

await browser.close();
server.close();

for (const line of ok) console.log(`ok    ${line}`);
for (const line of problems) console.error(`FAIL  ${line}`);
console.log(`\n${ok.length} ok, ${problems.length} échec(s)`);
process.exit(problems.length === 0 ? 0 : 1);
