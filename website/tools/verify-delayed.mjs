/**
 * Delayed comprehension checks: when a lot is due, and which questions come
 * back (remediation P3.2).
 *
 *   node website/tools/verify-delayed.mjs
 *
 * Runs website/src/lib/delayed.ts over hand-built histories and asserts the
 * schedule (7, then 14, then 28 days after the last answer on the lot) and
 * the selection (missed first, then unseen, then the oldest right answers).
 */
import {readFileSync} from 'node:fs';
import {fileURLToPath} from 'node:url';
import {dirname, resolve} from 'node:path';
import ts from 'typescript';

const here = dirname(fileURLToPath(import.meta.url));
const js = ts.transpileModule(readFileSync(resolve(here, '..', 'src', 'lib', 'delayed.ts'), 'utf8'), {
  compilerOptions: {module: ts.ModuleKind.ESNext, target: ts.ScriptTarget.ES2022},
}).outputText;
const {schedule, selectDelayed, intervalDays, SERIES_SIZE} = await import(`data:text/javascript;base64,${Buffer.from(js).toString('base64')}`);

let failed = 0;
function check(name, actual, expected) {
  const a = JSON.stringify(actual);
  const e = JSON.stringify(expected);
  console.log(a === e ? `PASS ${name}` : `FAIL ${name}\n  got      ${a}\n  expected ${e}`);
  failed += a === e ? 0 : 1;
}

const answer = (q, lot, correct, at, delayed = false) => ({
  question_id: q, question_version: 1, official_item: 'OIT-x', lot, correct, answered_at: at, ...(delayed ? {delayed: true} : {}),
});
const now = new Date('2026-10-20T12:00:00Z');

check('intervals double, then stay', [0, 1, 2, 3, 9].map(intervalDays), [7, 14, 28, 28, 28]);
check('nothing answered, nothing scheduled', schedule([], now), []);

// Lot 02 last answered 8 days ago: due. Lot 05, 3 days ago: not yet.
const history = [
  answer('A', 'lot-02', true, '2026-10-12T09:00:00Z'),
  answer('B', 'lot-02', false, '2026-10-12T09:01:00Z'),
  answer('C', 'lot-05', true, '2026-10-17T09:00:00Z'),
];
const s = schedule(history, now);
check('due after 7 days', s.map((l) => [l.lot, l.due]), [['lot-02', true], ['lot-05', false]]);
check('next due 7 days after the last answer', s.find((l) => l.lot === 'lot-05').dueAt, '2026-10-24T09:00:00.000Z');

// One delayed check done on lot-02 today: the next waits 14 days, from today.
const afterOne = [...history, answer('B', 'lot-02', true, '2026-10-20T10:00:00Z', true)];
const lot02 = schedule(afterOne, now).find((l) => l.lot === 'lot-02');
check('after one delayed check: 14 days', [lot02.delayedSessions, lot02.intervalDays, lot02.due], [1, 14, false]);
check('counted from the delayed answer', lot02.dueAt, '2026-11-03T10:00:00.000Z');

// Selection: B wrong last, D wrong earlier, E unseen, A and C right (C oldest).
const ids = ['A', 'B', 'C', 'D', 'E'];
const sel = [
  answer('A', 'lot-02', true, '2026-10-10T00:00:00Z'),
  answer('B', 'lot-02', false, '2026-10-09T00:00:00Z'),
  answer('C', 'lot-02', true, '2026-10-01T00:00:00Z'),
  answer('D', 'lot-02', false, '2026-10-02T00:00:00Z'),
  answer('D', 'lot-02', false, '2026-10-03T00:00:00Z'),
];
check('missed (oldest first), unseen, then oldest right', selectDelayed(ids, sel), ['D', 'B', 'E', 'C', 'A']);
check('a question answered right since its miss is no longer missed',
  selectDelayed(['A', 'B'], [answer('B', 'lot-02', false, '2026-10-01T00:00:00Z'), answer('B', 'lot-02', true, '2026-10-05T00:00:00Z')]),
  ['A', 'B']);
check('at most the series size', selectDelayed(Array.from({length: 20}, (_, i) => `Q${i}`), []).length, SERIES_SIZE);

console.log(failed === 0 ? 'verify-delayed: all cases pass' : `verify-delayed: ${failed} case(s) failed`);
process.exit(failed === 0 ? 0 : 1);
