/**
 * The browser scheduler must agree with the PHP one, or it is a liability.
 *
 * `src/lib/review.ts` ports `src/Review/ReviewScheduler.php` (the reference,
 * pinned by tests/Unit/ReviewSchedulerTest.php) so a flashcard can be
 * rescheduled where it is read. This script replays the same outcome
 * sequences through both — every sequence of one to six outcomes (5 460), plus
 * long runs that reach the ease bounds and the 180-day ceiling — and compares
 * every intermediate state, field by field.
 *
 *   node website/tools/verify-review.mjs
 *
 * A mismatch prints the first differing sequence and exits non-zero.
 *
 * What it cannot catch, stated rather than glossed over (proved by injection,
 * 2026-10-16): dropping the 180-day ceiling fails it; two other changes pass,
 * because both are unreachable from a fresh card. Replacing the PHP-style
 * rounding of `ease` by Math.round() changes nothing, since ease only moves in
 * steps of 0.05 and every value it takes rounds the same way. Removing the
 * `intervalDays + 1` floor changes nothing either: the third success starts
 * from 3 days, and HARD's 1.2 or an ease of at least 1.3 already gives
 * round(n x 1.2) >= n + 1 for every n >= 3. The port keeps both, mirroring the
 * source, but the claim is only that they agree on every reachable state.
 */
import {readFileSync} from 'node:fs';
import {execFileSync} from 'node:child_process';
import {fileURLToPath} from 'node:url';
import {dirname, resolve} from 'node:path';
import ts from 'typescript';

const here = dirname(fileURLToPath(import.meta.url));
const root = resolve(here, '..', '..');

const source = readFileSync(resolve(root, 'website/src/lib/review.ts'), 'utf8');
const js = ts.transpileModule(source, {
  compilerOptions: {module: ts.ModuleKind.ESNext, target: ts.ScriptTarget.ES2022},
}).outputText;
const {next, freshState, OUTCOMES} = await import(`data:text/javascript;base64,${Buffer.from(js).toString('base64')}`);

const sequences = [];
let layer = [[]];
for (let length = 1; length <= 6; length += 1) {
  layer = layer.flatMap((prefix) => OUTCOMES.map((o) => [...prefix, o]));
  sequences.push(...layer);
}
for (const outcome of OUTCOMES) {
  sequences.push(Array(40).fill(outcome));
}
sequences.push(Array.from({length: 60}, (_, i) => OUTCOMES[i % 4]));
sequences.push(Array.from({length: 60}, (_, i) => (i % 7 === 6 ? 'AGAIN' : 'EASY')));
sequences.push(Array.from({length: 60}, (_, i) => (i % 3 === 2 ? 'HARD' : 'GOOD')));

const reference = JSON.parse(
  execFileSync('php', [resolve(root, 'tools/review/replay.php')], {input: JSON.stringify(sequences), maxBuffer: 64 * 1024 * 1024}).toString(),
);

let compared = 0;
for (let s = 0; s < sequences.length; s += 1) {
  let state = freshState();
  for (let i = 0; i < sequences[s].length; i += 1) {
    state = next(state, sequences[s][i]);
    const got = [state.intervalDays, state.ease, state.repetitions, state.lapses, state.dueInSession];
    const want = reference[s][i];
    compared += 1;
    if (JSON.stringify(got) !== JSON.stringify(want)) {
      console.error(`MISMATCH after ${sequences[s].slice(0, i + 1).join(',')}: browser ${JSON.stringify(got)}, PHP ${JSON.stringify(want)}`);
      process.exit(1);
    }
  }
}

const ceiling = reference[sequences.length - 4].at(-1)[0];
console.log(`review port matches ReviewScheduler.php: ${sequences.length} sequences, ${compared} states (40 x EASY ends at ${ceiling} days)`);
