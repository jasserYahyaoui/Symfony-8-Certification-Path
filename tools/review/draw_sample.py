#!/usr/bin/env python3
"""A reproducible sample for an independent reviewer (remediation P3.3).

docs/review/independent-evaluation-brief.md asks a reviewer who did not write
the content to read part of it against Symfony 8.0. Reading everything is not
possible in one sitting, and a sample chosen by the authors would be chosen by
the party under review. This draws one from a seed the reviewer picks:

    python3 tools/review/draw_sample.py --seed 4242
    python3 tools/review/draw_sample.py --seed 4242 --courses 10 --questions 20
    python3 tools/review/draw_sample.py --prove

Courses: one per lot, from lots drawn at random. Questions: LEARNING and
VALIDATION only, spread over the official topics. The same seed gives the same
sample on the same commit, so a reviewer's report can be checked.

The HOLDOUT pool is never drawn: it is Mock 4's, and a reviewer who has read
it has spent it. Its ids are read to be excluded and are never printed —
`--prove` injects one into the candidates and fails unless the draw refuses
it, without naming it.
"""
from __future__ import annotations

import argparse
import random
import re
import sys
from pathlib import Path

import yaml

ROOT = Path(__file__).resolve().parents[2]
FRONT = re.compile(r'\A---\n(.*?)\n---\n', re.S)
SITE = 'https://jasseryahyaoui.github.io/Symfony-8-Certification-Path'


class HoldoutDrawn(Exception):
    """A HOLDOUT question reached the sample. The id is never carried."""


def questions() -> list[dict]:
    out = []
    for path in sorted((ROOT / 'content/questions').glob('*.yml')):
        for q in (yaml.safe_load(path.read_text()) or {}).get('questions') or []:
            out.append({'id': q['id'], 'pool': q['pool'], 'item': q['official_item'],
                        'topic': q['official_topic'], 'file': str(path.relative_to(ROOT))})
    return out


def courses() -> list[dict]:
    lot_of = {i['id']: i['lot'] for i in yaml.safe_load((ROOT / 'docs/syllabus/syllabus-matrix.yml').read_text())['items']}
    out = []
    for path in sorted((ROOT / 'content/courses').glob('*.md')):
        front = yaml.safe_load(FRONT.match(path.read_text()).group(1))
        out.append({'id': front['id'], 'title': front['title'], 'item': front['official_item'],
                    'lot': lot_of.get(front['official_item'], '?'), 'file': str(path.relative_to(ROOT))})
    return out


def draw(seed: int, n_courses: int, n_questions: int, bank: list[dict], holdout: set[str]) -> dict:
    rng = random.Random(seed)

    by_lot: dict[str, list[dict]] = {}
    for c in courses():
        by_lot.setdefault(c['lot'], []).append(c)
    lots = sorted(by_lot)
    rng.shuffle(lots)
    picked_courses = [rng.choice(by_lot[lot]) for lot in sorted(lots[:n_courses])]

    # The filter is the pool; the assertion below is the guarantee.
    visible = [q for q in bank if q['pool'] in ('LEARNING', 'VALIDATION')]
    by_topic: dict[str, list[dict]] = {}
    for q in visible:
        by_topic.setdefault(q['topic'], []).append(q)
    for pile in by_topic.values():
        rng.shuffle(pile)
    topics = sorted(by_topic)
    rng.shuffle(topics)
    picked_questions: list[dict] = []
    while len(picked_questions) < n_questions and any(by_topic.values()):
        for t in topics:
            if by_topic[t] and len(picked_questions) < n_questions:
                picked_questions.append(by_topic[t].pop())

    if any(q['id'] in holdout for q in picked_questions):
        raise HoldoutDrawn('a HOLDOUT question reached the sample (id withheld)')

    return {'courses': picked_courses, 'questions': sorted(picked_questions, key=lambda q: (q['topic'], q['id']))}


def prove() -> int:
    bank = questions()
    holdout = {q['id'] for q in bank if q['pool'] == 'HOLDOUT'}
    if not holdout:
        print('VACUOUS  no HOLDOUT question in the bank: the guard has nothing to refuse')
        return 1
    one = next(q for q in bank if q['pool'] == 'HOLDOUT')
    # The defect: a HOLDOUT question that slipped into the candidates under a
    # visible pool label, the way a wrong filter would let it through.
    injected = [dict(one, pool='LEARNING', topic='__injected__')] + [q for q in bank if q['pool'] != 'HOLDOUT']
    try:
        # One question per topic in the first round: the injected topic is drawn.
        draw(1, 1, 200, injected, holdout)
    except HoldoutDrawn:
        print('PROVED  a HOLDOUT question among the candidates is refused, its id not printed')
        return 0
    print('FAILED  a HOLDOUT question among the candidates was drawn without being refused')
    return 1


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__.split('\n\n')[0])
    ap.add_argument('--seed', type=int)
    ap.add_argument('--courses', type=int, default=10)
    ap.add_argument('--questions', type=int, default=20)
    ap.add_argument('--prove', action='store_true')
    args = ap.parse_args()
    if args.prove:
        return prove()
    if args.seed is None:
        ap.error('--seed is required: the reviewer picks it, so the authors do not pick the sample')

    bank = questions()
    holdout = {q['id'] for q in bank if q['pool'] == 'HOLDOUT'}
    sample = draw(args.seed, args.courses, args.questions, bank, holdout)

    print(f'# Review sample — seed {args.seed}\n')
    print(f'## Courses ({len(sample["courses"])}, one per lot)\n')
    for c in sample['courses']:
        print(f'- {c["lot"]} · {c["id"]} · {c["title"]} — `{c["file"]}`')
    print(f'\n## Questions ({len(sample["questions"])}, LEARNING and VALIDATION only)\n')
    for q in sample['questions']:
        print(f'- {q["topic"]} · {q["id"]} ({q["pool"]}) · item {q["item"]} — `{q["file"]}`')
    print(f'\nHOLDOUT: {len(holdout)} questions excluded, none read for this sample.')
    return 0


if __name__ == '__main__':
    sys.exit(main())
