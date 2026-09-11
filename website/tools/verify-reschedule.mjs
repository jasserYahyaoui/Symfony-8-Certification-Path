/**
 * The port must agree with the source of truth, or it is a liability.
 *
 * `src/lib/reschedule.ts` re-implements `tools/revision/build_roadmap.py` so
 * the agenda can replan in the browser. Two implementations of one algorithm
 * drift; this script is the reason keeping both is defensible. It runs the
 * TypeScript port over the parameters that produced the published plan and
 * asserts the result is identical — every day, every slot, every minute — to
 * what Python wrote in docs/revision/plan.json.
 *
 *   node website/tools/verify-reschedule.mjs
 *
 * A mismatch prints the first differing day and exits non-zero.
 *
 * What it cannot catch, stated rather than glossed over: a golden master only
 * exercises the paths the canonical plan takes. The branch that splits an item
 * across two days (`free >= 20`) is never taken here — all 163 items fit whole,
 * 0 partial entries — so a change to that threshold passes this check. It would
 * bite only for budgets small enough to split an item. Verified by injection:
 * removing the mock debriefs, the Sunday consolidation, or raising the daily
 * new-item cap each fails it; moving that one threshold does not.
 */
import {readFileSync} from 'node:fs';
import {fileURLToPath} from 'node:url';
import {dirname, resolve} from 'node:path';
import ts from 'typescript';

const here = dirname(fileURLToPath(import.meta.url));
const root = resolve(here, '..', '..');

const source = readFileSync(resolve(root, 'website/src/lib/reschedule.ts'), 'utf8');
const js = ts.transpileModule(source, {
  compilerOptions: {module: ts.ModuleKind.ESNext, target: ts.ScriptTarget.ES2022},
}).outputText;
const {reschedule} = await import(
  `data:text/javascript;base64,${Buffer.from(js).toString('base64')}`
);

const plan = JSON.parse(readFileSync(resolve(root, 'docs/revision/plan.json'), 'utf8'));

const items = {};
for (const item of plan.items) {
  items[item.id] = item;
}

const settings = {
  start: plan.start,
  exam: plan.exam,
  maxNew: plan.params.max_new,
  weekday: plan.params.budget['0'],
  weekend: plan.params.budget['5'],
  weekdayStart: plan.params.day_start['0'],
  weekendStart: plan.params.day_start['5'],
};

const got = reschedule(items, plan.order, plan.params, plan.lot_name, settings);

const problems = [];

if (got.error) {
  problems.push(`the port refused to plan: ${got.error}`);
}

const expectedDays = Object.keys(plan.days).sort();
const gotDays = Object.keys(got.days).sort();

if (expectedDays.join(',') !== gotDays.join(',')) {
  const missing = expectedDays.filter((d) => !gotDays.includes(d));
  const extra = gotDays.filter((d) => !expectedDays.includes(d));
  problems.push(
    `day sets differ — ${expectedDays.length} in plan.json, ${gotDays.length} from the port` +
      (missing.length ? `; missing ${missing.slice(0, 3).join(', ')}` : '') +
      (extra.length ? `; extra ${extra.slice(0, 3).join(', ')}` : ''),
  );
}

for (const date of expectedDays) {
  if (problems.length > 6) {
    break;
  }

  const want = plan.days[date];
  const mine = got.days[date];

  if (!mine) {
    continue;
  }

  if (want.used !== mine.used || want.budget !== mine.budget) {
    problems.push(`${date}: used/budget ${want.used}/${want.budget} vs ${mine.used}/${mine.budget}`);
    continue;
  }

  if (want.events.length !== mine.events.length) {
    problems.push(`${date}: ${want.events.length} slots in plan.json, ${mine.events.length} from the port`);
    continue;
  }

  for (let i = 0; i < want.events.length; i += 1) {
    const a = want.events[i];
    const b = mine.events[i];
    const keys = ['start', 'end', 'minutes', 'kind', 'title', 'lot', 'objective'];
    for (const k of keys) {
      if (JSON.stringify(a[k]) !== JSON.stringify(b[k])) {
        problems.push(`${date} slot ${i} ${k}: ${JSON.stringify(a[k])} vs ${JSON.stringify(b[k])}`);
        break;
      }
    }
    if (JSON.stringify(a.items) !== JSON.stringify(b.items)) {
      problems.push(`${date} slot ${i} items differ`);
    }
  }
}

if (problems.length > 0) {
  console.error('The browser port disagrees with build_roadmap.py:\n');
  for (const p of problems.slice(0, 8)) {
    console.error(`  ${p}`);
  }
  process.exit(1);
}

const slots = Object.values(got.days).reduce((s, d) => s + d.events.length, 0);
console.log(`reschedule port matches build_roadmap.py: ${gotDays.length} days, ${slots} slots`);
