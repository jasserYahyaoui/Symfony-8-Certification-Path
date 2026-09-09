"""AUD-09 — course sections carry content, not just a heading.

Master Plan §4.3: "Use only relevant sections, not a mandatory empty template."
The failure mode it forbids is a heading with nothing under it. Counting
headings cannot see that, and this project has already been bitten by a
heading-count proxy: the audit that drove the 2026-09-09 trap campaign reported
"94 of 163 courses have a Pièges d'examen section", and one of the 69 it counted
as missing (`Status codes`) already had the content under the heading
"Distinctions et pièges". The proxy was right 68 times out of 69.

This audit reads what is under each heading.

  SEC-1  a section heading is followed by fewer than MIN_WORDS words
  SEC-2  a course declares a heading twice
  SEC-3  a course has no content at all between two headings
  SEC-4  a trap section exists but only restates the Points clés verbatim

Exit code 1 if any finding.
"""
import pathlib
import re
import sys

import yaml

ROOT = pathlib.Path(__file__).resolve().parents[2]
COURSES = ROOT / 'content/courses'

# A heading whose body is shorter than this is a template, not a section.
# Calibrated on the corpus: the shortest legitimate section on 2026-09-09 is a
# 7-word `## Objectif`, and every `## Sources officielles` is a short link list.
MIN_WORDS = 6
LIST_SECTIONS = {'Sources officielles', 'Prérequis'}


def sections(body: str):
    """Yield (heading, content) for every `##`-level heading."""
    parts = re.split(r'^(##+ *.+)$', body, flags=re.M)
    for i in range(1, len(parts), 2):
        yield parts[i].lstrip('# ').strip(), parts[i + 1]


def main() -> int:
    findings = []
    checked = 0

    for path in sorted(COURSES.glob('*.md')):
        raw = path.read_text(encoding='utf-8')
        front, body = raw.split('---', 2)[1], raw.split('---', 2)[2]
        title = yaml.safe_load(front)['title']
        seen = {}

        for heading, content in sections(body):
            checked += 1
            words = len(content.split())

            if heading in seen:
                findings.append(f'SEC-2  {title}: heading "{heading}" declared twice')
            seen[heading] = content

            if 0 == words:
                findings.append(f'SEC-3  {title}: heading "{heading}" has no content at all')
            elif words < MIN_WORDS and heading not in LIST_SECTIONS:
                findings.append(
                    f'SEC-1  {title}: heading "{heading}" carries only {words} word(s)'
                )

        trap = next((c for h, c in seen.items() if 'Pièges' in h), None)
        keys = seen.get('Points clés')
        if trap and keys:
            t = {' '.join(s.split()) for s in re.split(r'[.\n]', trap) if len(s.split()) > 6}
            k = {' '.join(s.split()) for s in re.split(r'[.\n]', keys) if len(s.split()) > 6}
            if t and t <= k:
                findings.append(f'SEC-4  {title}: the trap section only restates Points clés')

    print(f'  course sections read                         {checked}')
    print(f'  courses                                      {len(list(COURSES.glob("*.md")))}')
    for f in findings:
        print(f)
    print(f'\nFINDINGS: {len(findings)}')
    return 1 if findings else 0


sys.exit(main())
