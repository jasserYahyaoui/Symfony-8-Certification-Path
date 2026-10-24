/**
 * A flashcard front's code, as the site's MDX compiler renders it.
 *
 *   node website/tools/verify-summary-code.mjs
 *
 * A card front is rendered on one `<summary>` line, a JSX context where no
 * literal brace may appear. DocsGenerator::summaryText() therefore emits a
 * code span that holds a brace as a `<code>` element, its ASCII punctuation
 * written as numeric character references. That is safe only while the
 * compiler renders it exactly as it would have rendered the code span.
 *
 * For each such element in the pages written by `php bin/cert build`, this
 * rebuilds the code span, renders both lines with @mdx-js/mdx, and requires
 * the same HTML. It also requires that no summary line holds a brace, and
 * proves the comparison can fail: a conversion that leaves `*` and `\` as
 * they are must render differently.
 */
import {readFileSync, readdirSync, statSync} from 'node:fs';
import {fileURLToPath} from 'node:url';
import {dirname, join, resolve} from 'node:path';
import {evaluate} from '@mdx-js/mdx';
import * as runtime from 'react/jsx-runtime';
import {renderToStaticMarkup} from 'react-dom/server';

const here = dirname(fileURLToPath(import.meta.url));
const docs = resolve(here, '..', 'docs');

const render = async (line) => {
  const {default: Content} = await evaluate(line, {...runtime, development: false});
  return renderToStaticMarkup(Content({}));
};
const decode = (s) => s.replace(/&#(\d+);/g, (_, n) => String.fromCharCode(Number(n)));
const asSpans = (line) => line.replace(/<code>(.*?)<\/code>/g, (_, inner) => '`' + decode(inner) + '`');

function pages(dir) {
  return readdirSync(dir).flatMap((name) => {
    const path = join(dir, name);
    return statSync(path).isDirectory() ? pages(path) : path.endsWith('.md') ? [path] : [];
  });
}

let failed = 0;
let checked = 0;
let summaries = 0;
for (const page of pages(docs)) {
  for (const line of readFileSync(page, 'utf8').split('\n')) {
    if (!line.startsWith('<summary>')) continue;
    summaries += 1;
    if (line.includes('{') || line.includes('}')) {
      console.log(`FAIL ${page}: a brace reaches the JSX context\n  ${line}`);
      failed += 1;
    }
    if (!line.includes('<code>')) continue;
    checked += 1;
    const emitted = await render(line);
    const expected = await render(asSpans(line));
    if (emitted !== expected) {
      console.log(`FAIL ${page}\n  emitted  ${emitted}\n  expected ${expected}`);
      failed += 1;
    }
  }
}

// The comparison is not vacuous: referencing the braces alone, and leaving
// `*` and `\` bare, renders differently from the code span.
const naive = '<summary><code>a*b*c &#123; d\\*e &#125;</code></summary>';
const proof = (await render(naive)) !== (await render(asSpans(naive)));
if (!proof) {
  console.log('FAIL the comparison did not tell a naive conversion from a code span');
  failed += 1;
}

if (checked === 0) {
  console.log('FAIL no summary carries a <code> element: the check would be vacuous');
  failed += 1;
}

console.log(
  failed === 0
    ? `verify-summary-code: ${checked} <code> summaries render as their code spans, ${summaries} summaries brace-free, naive conversion refused`
    : `verify-summary-code: ${failed} failure(s)`,
);
process.exit(failed === 0 ? 0 : 1);
