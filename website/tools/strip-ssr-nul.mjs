/**
 * Remove the NUL bytes that the server-side render leaves in the built HTML.
 *
 * Docusaurus renders each page with react-dom's renderToPipeableStream
 * (@docusaurus/core, lib/client/renderToHtml.js). With react-dom 18.3.1 the
 * output carries U+0000 immediately before some multi-byte characters — é, à,
 * the em dash — sometimes twice. Measured on 2026-10-09: 169 HTML pages, 241
 * bytes, identical with --no-minify, while the compiled JavaScript chunks are
 * clean. In a text node a browser drops the byte; in an attribute it becomes
 * U+FFFD, so 20 aria-labels read "D�velopper la catégorie" and a
 * table-of-contents link pointed to "#points-cl�és". It also broke a smoke
 * needle that happened to straddle one of them.
 *
 * Every byte is an insertion, never a replacement: removing them restores the
 * intended text exactly. This runs as the `postbuild` script, so CI, the Pages
 * deployment and `composer gate-full` all get it, and it fails the build if a
 * NUL survives in an HTML file or appears in any other text artefact — the
 * cleanup must not become a place where a new defect can hide.
 */
import {readdir, readFile, writeFile} from 'node:fs/promises';
import {extname, join, relative} from 'node:path';

const ROOT = new URL('../build/', import.meta.url).pathname;
const TEXT = new Set(['.html', '.js', '.css', '.json', '.xml', '.txt', '.svg', '.webmanifest']);

async function* walk(dir) {
  for (const entry of await readdir(dir, {withFileTypes: true})) {
    const path = join(dir, entry.name);
    if (entry.isDirectory()) {
      yield* walk(path);
    } else {
      yield path;
    }
  }
}

let files = 0;
let removed = 0;
const elsewhere = [];

for await (const path of walk(ROOT)) {
  const ext = extname(path);
  if (!TEXT.has(ext)) {
    continue;
  }
  const bytes = await readFile(path);
  if (bytes.indexOf(0) === -1) {
    continue;
  }
  if (ext !== '.html') {
    elsewhere.push(relative(ROOT, path));
    continue;
  }
  const kept = bytes.filter((byte) => byte !== 0);
  if (kept.indexOf(0) !== -1) {
    throw new Error(`NUL byte left in ${relative(ROOT, path)}`);
  }
  removed += bytes.length - kept.length;
  files += 1;
  await writeFile(path, kept);
}

if (elsewhere.length > 0) {
  console.error(`strip-ssr-nul: NUL bytes in non-HTML artefacts, not handled: ${elsewhere.join(', ')}`);
  process.exit(1);
}

console.log(`strip-ssr-nul: removed ${removed} NUL byte(s) from ${files} HTML file(s)`);
