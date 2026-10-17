/**
 * Mock 5's weak items follow question versions (remediation P3.3).
 *
 *   node website/tools/verify-weakness.mjs
 *
 * Runs website/src/lib/weakness.ts — with the storage module it reads, over a
 * stubbed localStorage — on hand-built histories. A failure given to version 1
 * of a question since rewritten must not make its item weak; the same failure
 * on the current version must. A right answer the learner judged reached by
 * a wrong reasoning counts as a failure (remediation P3.2).
 */
import {mkdtempSync, readFileSync, rmSync, writeFileSync} from 'node:fs';
import {tmpdir} from 'node:os';
import {dirname, join, resolve} from 'node:path';
import {fileURLToPath, pathToFileURL} from 'node:url';
import ts from 'typescript';

const here = dirname(fileURLToPath(import.meta.url));
const lib = resolve(here, '..', 'src', 'lib');
const out = mkdtempSync(join(tmpdir(), 'verify-weakness-'));
for (const name of ['review', 'storage', 'weakness']) {
  const js = ts.transpileModule(readFileSync(join(lib, `${name}.ts`), 'utf8'), {
    compilerOptions: {module: ts.ModuleKind.ESNext, target: ts.ScriptTarget.ES2022},
  }).outputText.replace(/from '\.\/(review|storage|types)'/g, "from './$1.mjs'");
  writeFileSync(join(out, `${name}.mjs`), js);
}
writeFileSync(join(out, 'types.mjs'), 'export {};\n');

const store = new Map();
globalThis.window = {
  localStorage: {
    getItem: (k) => (store.has(k) ? store.get(k) : null),
    setItem: (k, v) => store.set(k, String(v)),
    removeItem: (k) => store.delete(k),
  },
};
const storage = await import(pathToFileURL(join(out, 'storage.mjs')).href);
const {weaknessSignals} = await import(pathToFileURL(join(out, 'weakness.mjs')).href);

const question = (id, item, version) => ({id, official_item: item, version, exam_skill: 'APPLY', official_topic: 'HTTP', language: 'en'});
const pool = new Map([
  ['Q1', question('Q1', 'OIT-rewritten', 2)],
  ['Q2', question('Q2', 'OIT-plain', 1)],
]);
const now = Date.UTC(2026, 9, 17);
function history(attempts) {
  storage.clearState();
  for (const a of attempts) {
    storage.recordAttempt({chosen: [], answered_at: new Date(now).toISOString(), mode: 'practice', ...a});
  }
}

let failed = 0;
function check(name, actual, expected) {
  const a = JSON.stringify(actual);
  const e = JSON.stringify(expected);
  console.log(a === e ? `PASS ${name}` : `FAIL ${name}\n  got      ${a}\n  expected ${e}`);
  failed += a === e ? 0 : 1;
}

history([
  {question_id: 'Q1', question_version: 1, official_item: 'OIT-rewritten', correct: false},
  {question_id: 'Q2', question_version: 1, official_item: 'OIT-plain', correct: false},
]);
check('a failure on version 1 of a rewritten question is not a weakness', weaknessSignals(pool, now).items, ['OIT-plain']);

history([{question_id: 'Q1', question_version: 2, official_item: 'OIT-rewritten', correct: false}]);
check('the same failure on the current version is', weaknessSignals(pool, now).items, ['OIT-rewritten']);

// Remediation P3.2: a right choice the learner judged reached by a wrong
// reasoning counts as a failure; judged right or partly right, it does not.
history([{question_id: 'Q2', question_version: 1, official_item: 'OIT-plain', correct: true, reasoning: 'missed'}]);
check('a right answer for a wrong reason is a weakness', weaknessSignals(pool, now).items, ['OIT-plain']);
history([{question_id: 'Q2', question_version: 1, official_item: 'OIT-plain', correct: true, reasoning: 'partial'}]);
check('a partly matched reasoning is not', weaknessSignals(pool, now).items, []);

rmSync(out, {recursive: true, force: true});
console.log(failed === 0 ? 'verify-weakness: all cases pass' : `verify-weakness: ${failed} case(s) failed`);
process.exit(failed === 0 ? 0 : 1);
