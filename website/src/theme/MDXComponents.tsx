import type {ComponentProps, ReactNode} from 'react';
import {Children, isValidElement} from 'react';
import MDXComponents from '@theme-original/MDXComponents';

/**
 * The text of a React subtree: what a sighted reader sees in a header cell.
 */
function textOf(node: ReactNode): string {
  if (typeof node === 'string' || typeof node === 'number') return String(node);
  if (Array.isArray(node)) return node.map(textOf).join('');
  if (isValidElement<{children?: ReactNode}>(node)) return textOf(node.props.children);
  return '';
}

/** The header cells of a Markdown table, in reading order. */
function headersOf(children: ReactNode): string[] {
  const out: string[] = [];
  const walk = (node: ReactNode): void => {
    Children.forEach(node, (child) => {
      if (!isValidElement<{children?: ReactNode}>(child)) return;
      if (child.type === 'th') {
        const text = textOf(child.props.children).trim();
        if (text) out.push(text);
        return;
      }
      if (child.type === 'thead' || child.type === 'tr') walk(child.props.children);
    });
  };
  walk(children);
  return out;
}

/**
 * A course table scrolls inside its own region, never the page (§13).
 *
 * Infima makes the TABLE the scrolling element, and a scrolling element must be
 * reachable by keyboard or its off-screen columns are unreadable without a
 * mouse. The React pages already wrapped their tables this way; the course
 * tables, written in Markdown, did not, and nothing saw it: course pages were
 * only audited at desktop width, where they do not scroll. The first course
 * audited at phone width (lot 03, Request handling, with its diagrams) failed
 * axe `scrollable-region-focusable` on its table.
 *
 * The region is named after the table's own column headers, so two tables on
 * one page are told apart by what they hold rather than by a generic label.
 */
function ScrollableTable(props: ComponentProps<'table'>) {
  const headers = headersOf(props.children);
  const label = headers.length > 0 ? `Tableau : ${headers.join(', ')}` : 'Tableau';
  return (
    <div className="certpath-table-scroll" tabIndex={0} role="region" aria-label={label}>
      <table {...props} />
    </div>
  );
}

export default {
  ...MDXComponents,
  table: ScrollableTable,
};
