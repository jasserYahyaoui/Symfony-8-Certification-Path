/**
 * The learner-facing figures of « Ma progression » (remediation F5).
 *
 *   node website/tools/verify-observed.mjs
 *
 * Runs website/src/lib/observed.ts over hand-built histories: none, partial,
 * repeated, contradictory, and one mixing channels. Each case asserts the cell
 * values a reader would see, so a change that merges channels, counts a retry
 * as a first answer, or concludes on too few questions fails here.
 */
import {readFileSync} from 'node:fs';
import {fileURLToPath} from 'node:url';
import {dirname, resolve} from 'node:path';
import ts from 'typescript';

const here = dirname(fileURLToPath(import.meta.url));
const root = resolve(here, '..', '..');
const js = ts.transpileModule(readFileSync(resolve(root, 'website/src/lib/observed.ts'), 'utf8'), {
  compilerOptions: {module: ts.ModuleKind.ESNext, target: ts.ScriptTarget.ES2022},
}).outputText;
const {observe, enough, MIN_QUESTIONS} = await import(`data:text/javascript;base64,${Buffer.from(js).toString('base64')}`);

const topicOf = (item) => ({'OIT-a': 'HTTP', 'OIT-b': 'Forms'})[item];
let t = 0;
const at = () => new Date(Date.UTC(2026, 9, 13, 0, 0, t++)).toISOString();
const attempt = (q, item, correct, mode = 'practice') => ({
  question_id: q, question_version: 1, official_item: item, correct, chosen: [], answered_at: at(), mode,
});
const cmp = (q, item, correct) => ({question_id: q, question_version: 1, official_item: item, lot: 'lot-02', correct, answered_at: at()});

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

// No history: nothing observed, no row at all.
check('empty history', observe([], [], topicOf).size, 0);

// Partial: four distinct questions is below the floor.
const partial = observe([1, 2, 3, 4].map((i) => attempt(`Q${i}`, 'OIT-a', true)), [], topicOf);
check('partial: counted', partial.get('HTTP').practice.questions, 4);
check('partial: not concluded', enough(partial.get('HTTP').practice), false);
check('floor is five', MIN_QUESTIONS, 5);

// Repeated: wrong first, right later — first answer stays wrong.
const repeated = observe([
  attempt('Q1', 'OIT-a', false), attempt('Q1', 'OIT-a', true),
  ...[2, 3, 4, 5].map((i) => attempt(`Q${i}`, 'OIT-a', true)),
], [], topicOf);
check('repeated: cell', repeated.get('HTTP').practice, {questions: 5, firstCorrect: 4, latestCorrect: 5, repeated: 1});

// Contradictory: right first, wrong later — latest is wrong, first is right.
const contra = observe([attempt('Q9', 'OIT-b', true), attempt('Q9', 'OIT-b', false)], [], topicOf);
check('contradictory: cell', contra.get('Forms').practice, {questions: 1, firstCorrect: 1, latestCorrect: 0, repeated: 1});

// Channels stay apart: one question id seen in practice and in Mock 4 counts once in each.
const mixed = observe(
  [attempt('Q1', 'OIT-a', false), attempt('Q1', 'OIT-a', true, 'mock'), attempt('Q2', 'OIT-a', true, 'mock-2')],
  [cmp('C1', 'OIT-a', false)],
  topicOf,
);
const row = mixed.get('HTTP');
check('channels: practice', row.practice, {questions: 1, firstCorrect: 0, latestCorrect: 0, repeated: 0});
check('channels: mock4', row.mock4, {questions: 1, firstCorrect: 1, latestCorrect: 1, repeated: 0});
check('channels: training', row.training.questions, 1);
check('channels: comprehension', row.comprehension, {questions: 1, firstCorrect: 0, latestCorrect: 0, repeated: 0});

// An item the index does not know is grouped, not dropped.
check('unknown item kept', observe([attempt('Q7', 'OIT-zzz', true)], [], topicOf).get('Item inconnu').practice.questions, 1);

console.log(failed === 0 ? 'verify-observed: all cases pass' : `verify-observed: ${failed} case(s) failed`);
process.exit(failed === 0 ? 0 : 1);
