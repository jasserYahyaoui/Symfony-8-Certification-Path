/**
 * First exposure and parallel forms (remediation P3.2,
 * docs/policy/first-exposure.md).
 *
 *   node website/tools/verify-exposure.mjs
 *
 * Runs website/src/lib/exposure.ts over hand-built banks and histories and
 * asserts that the « série inédite » serves only questions never answered in
 * their current version, never two of one lot in a row while another lot has
 * one left, and that the parallel replay of a weak point never serves the
 * missed question itself, prefers a shared learning outcome to a shared item,
 * and says how many weak points it could not cover.
 */
import {readFileSync} from 'node:fs';
import {fileURLToPath} from 'node:url';
import {dirname, resolve} from 'node:path';
import ts from 'typescript';

const here = dirname(fileURLToPath(import.meta.url));
const js = ts.transpileModule(readFileSync(resolve(here, '..', 'src', 'lib', 'exposure.ts'), 'utf8'), {
  compilerOptions: {module: ts.ModuleKind.ESNext, target: ts.ScriptTarget.ES2022},
}).outputText;
const {unseenMixedSeries, parallelReplay, lotOf, isUnseen, answeredVersions, UNSEEN_SERIES_SIZE} =
  await import(`data:text/javascript;base64,${Buffer.from(js).toString('base64')}`);

let failed = 0;
function check(name, actual, expected) {
  const a = JSON.stringify(actual);
  const e = JSON.stringify(expected);
  console.log(a === e ? `PASS ${name}` : `FAIL ${name}\n  got      ${a}\n  expected ${e}`);
  failed += a === e ? 0 : 1;
}

/** A seeded generator, so a failure replays. */
function seeded(seed) {
  let s = seed >>> 0;
  return () => {
    s = (s * 1664525 + 1013904223) >>> 0;
    return s / 2 ** 32;
  };
}

const q = (id, lot, item, outcomes = [], version = 1) => ({id, version, official_item: item, assesses_outcomes: outcomes, lot});
const answer = (id, correct, at, version = 1, item = 'OIT-x') => ({
  question_id: id, question_version: version, official_item: item, correct, answered_at: at,
});
const lotOfQ = (question) => question.lot;

check('the lot of a course URL', lotOf('/docs/courses/lot-08/violations-builder'), 'lot-08');

// A rewritten question is new to whoever answered its earlier version.
const answered = answeredVersions([answer('A', true, '2026-10-01T00:00:00Z', 1)]);
check('answered in its version: seen; rewritten since: unseen',
  [isUnseen(q('A', 'lot-01', 'I1'), answered), isUnseen(q('A', 'lot-01', 'I1', [], 2), answered)],
  [false, true]);

// Bank: lot-01 has six questions, lot-02 three, lot-03 one. Two are answered.
const bank = [
  ...['a1', 'a2', 'a3', 'a4', 'a5', 'a6'].map((id) => q(id, 'lot-01', 'I1')),
  ...['b1', 'b2', 'b3'].map((id) => q(id, 'lot-02', 'I2')),
  q('c1', 'lot-03', 'I3'),
];
const history = [answer('a1', true, '2026-10-01T00:00:00Z'), answer('b1', false, '2026-10-01T00:00:00Z')];

let neverSeen = true;
let interleaved = true;
let firstRoundSpread = true;
for (let seed = 1; seed <= 200; seed += 1) {
  const series = unseenMixedSeries(bank, lotOfQ, history, 10, seeded(seed));
  if (series.some((s) => s.id === 'a1' || s.id === 'b1') || new Set(series.map((s) => s.id)).size !== series.length) {
    neverSeen = false;
  }
  // Consecutive questions of one lot are allowed only once every other lot is spent.
  for (let i = 1; i < series.length; i += 1) {
    if (series[i].lot === series[i - 1].lot) {
      const rest = new Set(series.slice(i).map((s) => s.lot));
      if (rest.size > 1) interleaved = false;
    }
  }
  if (new Set(series.slice(0, 3).map((s) => s.lot)).size !== 3) firstRoundSpread = false;
}
check('only questions never answered, each once (200 seeds)', neverSeen, true);
check('never two of one lot in a row while another lot has one left (200 seeds)', interleaved, true);
check('the first round draws one question from each lot (200 seeds)', firstRoundSpread, true);
check('all eight unseen questions served when the series is longer than the bank',
  unseenMixedSeries(bank, lotOfQ, history, 10, seeded(7)).length, 8);
check('at most the series size', unseenMixedSeries(bank, lotOfQ, [], 4, seeded(3)).length, 4);
check('ten questions by default', UNSEEN_SERIES_SIZE, 10);
check('nothing left to serve: an empty series',
  unseenMixedSeries([q('z', 'lot-01', 'I1')], lotOfQ, [answer('z', true, '2026-10-01T00:00:00Z')]).length, 0);

// Parallel replay. W1 missed (outcome O1, item I1); W2 missed then right (not weak);
// W3 missed in Exam Mode, absent from this bank (item I2); W4 missed, nothing left on it.
const pbank = [
  q('W1', 'lot-01', 'I1', ['OUT-1']),
  q('P-outcome', 'lot-01', 'I1', ['OUT-1', 'OUT-9']),
  q('P-item', 'lot-01', 'I1', ['OUT-2']),
  q('W2', 'lot-01', 'I1', ['OUT-3']),
  q('P-item2', 'lot-02', 'I2', ['OUT-5']),
  q('W4', 'lot-03', 'I4', ['OUT-7']),
];
const phistory = [
  answer('W1', false, '2026-10-01T00:00:00Z', 1, 'I1'),
  answer('W2', false, '2026-10-01T00:00:00Z', 1, 'I1'),
  answer('W2', true, '2026-10-02T00:00:00Z', 1, 'I1'),
  answer('W3', false, '2026-10-03T00:00:00Z', 1, 'I2'),
  answer('W4', false, '2026-10-04T00:00:00Z', 1, 'I4'),
];
let outcomeFirst = true;
let neverTheMiss = true;
for (let seed = 1; seed <= 100; seed += 1) {
  const r = parallelReplay(pbank, phistory, seeded(seed));
  if (r.stands_for.get('P-outcome')?.missed !== 'W1' || r.stands_for.get('P-outcome')?.match !== 'outcome') outcomeFirst = false;
  if (r.questions.some((s) => ['W1', 'W2', 'W3', 'W4'].includes(s.id))) neverTheMiss = false;
}
check('a shared outcome is preferred to a shared item (100 seeds)', outcomeFirst, true);
check('never the missed question, nor any question already answered (100 seeds)', neverTheMiss, true);
const replay = parallelReplay(pbank, phistory, seeded(1));
check('a weak point is a question whose latest answer was wrong', replay.weak, 3);
check('a question missed in Exam Mode is matched on its item',
  replay.stands_for.get('P-item2'), {missed: 'W3', match: 'item'});
check('a weak point with nothing left is counted, not hidden', replay.uncovered, 1);
check('one question per weak point covered', replay.questions.length, 2);

console.log(failed === 0 ? 'verify-exposure: all cases pass' : `verify-exposure: ${failed} case(s) failed`);
process.exit(failed === 0 ? 0 : 1);
