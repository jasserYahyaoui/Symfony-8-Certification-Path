import React from 'react';

/**
 * Canonical question text, rendered so that code reads as code (Lot 27).
 *
 * The Lot 27 audit found one systemic defect behind what looked like many:
 * nothing rendered the bank's own markup. `QuestionCard` wrote the stem into a
 * `<p>`, so one question showed its ```php fence to the learner in full, another
 * had two `#[Route]` attributes flattened into a paragraph, and 242 inline
 * fragments came out carrying literal backticks.
 *
 * WHAT THIS IS NOT. It is not a Markdown renderer, and deliberately so. The
 * only markup the bank uses for code is the fence and the backtick, and a
 * general Markdown pass would also interpret `*`, `_` and `#` — characters that
 * appear inside PHP, YAML and Twig as themselves. Rendering `#[Attribute]` as a
 * heading would corrupt the very content this exists to show.
 *
 * WHY THERE IS NO dangerouslySetInnerHTML. Every fragment below is placed as a
 * React text child, so `{}`, `{{ }}`, `{% %}`, `{# #}`, `<`, `>`, `&`, `#` and
 * `:` reach the DOM as characters. That is what makes Twig safe here: the
 * content is never parsed as JSX or MDX, it is never parsed at all.
 *
 * A short fragment stays inline. Turning `$request->getLocale()` into a block
 * would break the sentence that carries it, and the audit counted 242 such
 * fragments against 2 real blocks.
 */

interface Props {
  /** Canonical text: a prompt, a choice, an explanation. */
  children: string;
  /** `code_language` when the question declares one; used for fenced blocks. */
  language?: string | null;
  /** Element used for plain runs. Blocks always break out of it. */
  as?: 'p' | 'span' | 'div';
  className?: string;
}

type Segment =
  | {kind: 'text'; value: string}
  | {kind: 'inline'; value: string}
  | {kind: 'block'; value: string; language: string | null};

const FENCE = /```([a-z0-9+#-]*)\n?([\s\S]*?)```/g;
const INLINE = /`([^`\n]+)`/g;

/** Split on fenced blocks first: a fence may legitimately contain backticks. */
export function parse(text: string, language?: string | null): Segment[] {
  const segments: Segment[] = [];
  let cursor = 0;

  for (const match of text.matchAll(FENCE)) {
    const start = match.index ?? 0;
    if (start > cursor) {
      segments.push(...inlineSegments(text.slice(cursor, start)));
    }
    segments.push({
      kind: 'block',
      // Only the trailing newline the fence syntax adds is dropped. Leading
      // whitespace is the code's own indentation and is never touched.
      value: match[2].replace(/\n$/, ''),
      language: match[1] || language || null,
    });
    cursor = start + match[0].length;
  }

  if (cursor < text.length) {
    segments.push(...inlineSegments(text.slice(cursor)));
  }

  return segments;
}

function inlineSegments(text: string): Segment[] {
  const segments: Segment[] = [];
  let cursor = 0;

  for (const match of text.matchAll(INLINE)) {
    const start = match.index ?? 0;
    if (start > cursor) {
      segments.push({kind: 'text', value: text.slice(cursor, start)});
    }
    segments.push({kind: 'inline', value: match[1]});
    cursor = start + match[0].length;
  }

  if (cursor < text.length) {
    segments.push({kind: 'text', value: text.slice(cursor)});
  }

  return segments;
}

/** True when the text carries a multiline run the author did not fence. */
export function hasUnfencedBlock(text: string): boolean {
  return parse(text).some((s) => s.kind === 'text' && s.value.trim().includes('\n'));
}

export default function RichText({
  children,
  language = null,
  as: Wrapper = 'p',
  className,
}: Props): React.JSX.Element {
  const segments = parse(children, language);
  const blocks = segments.filter((s) => s.kind === 'block');

  // No fence: one wrapper, with inline code where the author used backticks.
  // An unfenced multiline run still has its newlines preserved by the CSS
  // (`white-space: pre-wrap`), so nothing the author wrote is lost.
  if (blocks.length === 0) {
    return (
      <Wrapper className={className}>
        {segments.map((segment, i) => renderInline(segment, i))}
      </Wrapper>
    );
  }

  // Mixed content: a block cannot live inside a <p> without the browser
  // closing the paragraph for us and detaching the rest.
  return (
    <div className={className}>
      {groupRuns(segments).map((run, i) =>
        run.kind === 'block' ? (
          <CodeBlock key={i} value={run.value} language={run.language} />
        ) : (
          <Wrapper key={i}>{run.segments.map((s, j) => renderInline(s, j))}</Wrapper>
        ),
      )}
    </div>
  );
}

type Run =
  | {kind: 'block'; value: string; language: string | null}
  | {kind: 'prose'; segments: Segment[]};

function groupRuns(segments: Segment[]): Run[] {
  const runs: Run[] = [];

  for (const segment of segments) {
    if (segment.kind === 'block') {
      runs.push({kind: 'block', value: segment.value, language: segment.language});
      continue;
    }
    const last = runs[runs.length - 1];
    if (last && last.kind === 'prose') {
      last.segments.push(segment);
    } else {
      runs.push({kind: 'prose', segments: [segment]});
    }
  }

  // A fence surrounded by nothing but whitespace leaves an empty paragraph.
  return runs.filter(
    (run) =>
      run.kind === 'block' ||
      run.segments.some((s) => s.kind !== 'text' || s.value.trim() !== ''),
  );
}

function renderInline(segment: Segment, key: number): React.ReactNode {
  if (segment.kind === 'inline') {
    return (
      <code className="certpath-code-inline" key={key}>
        {segment.value}
      </code>
    );
  }
  if (segment.kind === 'block') {
    return <CodeBlock key={key} value={segment.value} language={segment.language} />;
  }

  return <React.Fragment key={key}>{segment.value}</React.Fragment>;
}

function CodeBlock({
  value,
  language,
}: {
  value: string;
  language: string | null;
}): React.JSX.Element {
  return (
    <pre
      className="certpath-code-block"
      // Scrollable regions must be reachable by keyboard, or a learner who
      // cannot use a mouse cannot read a wide snippet at all (§13).
      tabIndex={0}
      role="group"
      aria-label={language ? `Code, ${language}` : 'Code'}
      data-language={language ?? undefined}>
      <code>{value}</code>
    </pre>
  );
}
