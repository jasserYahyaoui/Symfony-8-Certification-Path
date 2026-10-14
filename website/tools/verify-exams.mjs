/**
 * The menu of /examens (owner's request, 2026-10-14).
 *
 *   node website/tools/verify-exams.mjs
 *
 * Runs website/src/lib/examSeries.ts on the real exams.json (written by
 * `php bin/cert build`) and on hand-built histories: what is recommended with
 * no history, part-way, after the six fixed exams and after a draw; that a
 * fixed exam is its 75 questions; and that a draw keeps the topic spread, asks
 * once per item and takes unseen questions first.
 */
import {readFileSync} from 'node:fs';
import {fileURLToPath} from 'node:url';
import {dirname, resolve} from 'node:path';
import ts from 'typescript';

const here = dirname(fileURLToPath(import.meta.url));
const root = resolve(here, '..', '..');
const js = ts.transpileModule(readFileSync(resolve(root, 'website/src/lib/examSeries.ts'), 'utf8'), {
  compilerOptions: {module: ts.ModuleKind.ESNext, target: ts.ScriptTarget.ES2022},
}).outputText;
const lib = await import(`data:text/javascript;base64,${Buffer.from(js).toString('base64')}`);
const payload = JSON.parse(readFileSync(resolve(root, 'website/static/data/exams.json'), 'utf8'));

let failed = 0;
function check(name, actual, expected) {
  const a = JSON.stringify(actual);
  const e = JSON.stringify(expected);
  if (a === e) {
    console.log(`PASS ${name}`);
  } else {
    console.log(`FAIL ${name}\n  got      ${a}\n  expected ${e}`);
    failed += 1;
  }
}
const session = (n, correct = 50) => ({
  mode: `blanc-${n}`, question_count: 75, correct, unanswered: 0, elapsed_seconds: 1, timed_out: false,
  finished_at: '2026-10-14T00:00:00.000Z',
});

check('no history: exam 1 is recommended', lib.recommended(payload, []), 1);
check('after 1, 2 and 3: exam 4', lib.recommended(payload, [session(1), session(2), session(3)]), 4);
check('a skipped exam comes back first', lib.recommended(payload, [session(1), session(3)]), 2);
const allSix = [1, 2, 3, 4, 5, 6].map((n) => session(n));
check('after the six: a draw, numbered 7', lib.recommended(payload, allSix), 7);
check('after draw 7: draw 8', lib.nextDrawNumber(payload, [...allSix, session(7)]), 8);
check('other mocks do not count', lib.recommended(payload, [{...session(1), mode: 'mock-1'}]), 1);
check('last result of an exam is the latest session', lib.statuses(payload, [session(2, 40), session(2, 60)])[1].last.correct, 60);

const fixed = lib.fixedExam(payload, 3);
check('a fixed exam is its 75 questions', fixed.length, 75);
check('an unknown exam is empty', lib.fixedExam(payload, 99).length, 0);

// A draw: same spread, one per item, unseen first.
let seed = 1;
const random = () => ((seed = (seed * 16807) % 2147483647) / 2147483647);
const seenIds = new Set(lib.fixedExam(payload, 1).map((q) => q.id));
const attempts = [...seenIds].map((id) => ({question_id: id, question_version: 1, official_item: 'x', correct: true, chosen: [], answered_at: 'x', mode: 'blanc-1'}));
const draw = lib.drawExam(payload, attempts, random);
check('a draw has the official count', draw.questions.length, payload.question_count);
const spread = {};
for (const q of draw.questions) spread[q.official_topic] = (spread[q.official_topic] ?? 0) + 1;
check('a draw keeps the topic spread', Object.fromEntries(Object.entries(spread).sort()), payload.topic_spread);
check('a draw asks once per item', new Set(draw.questions.map((q) => q.official_item)).size, draw.questions.length);
check('a draw takes unseen questions first (exam 1 already sat)', draw.alreadySeen, 0);
check('the count of seen questions is reported', draw.questions.filter((q) => seenIds.has(q.id)).length, draw.alreadySeen);

// When every question has been met, the draw still works and says so.
const everything = payload.questions.map((q) => ({question_id: q.id, question_version: 1, official_item: 'x', correct: true, chosen: [], answered_at: 'x', mode: 'practice'}));
check('all questions met: every drawn question is reported as seen', lib.drawExam(payload, everything, random).alreadySeen, payload.question_count);

console.log(failed === 0 ? 'verify-exams: all cases pass' : `verify-exams: ${failed} case(s) failed`);
process.exit(failed === 0 ? 0 : 1);
