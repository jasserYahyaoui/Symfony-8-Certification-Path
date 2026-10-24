#!/usr/bin/env python3
"""Every exam pitfall is rehearsed by a flashcard, or says why not.

Each course closes its teaching with « Pièges d'examen »: the traps a question
can be built on. A trap read once is not retained, and the flashcard is the
project's instrument for retention (Master Plan §6). This audit pairs each
pitfall with the card that rehearses it, from a registry written by reading
both, never by a score:

    docs/audit/pitfall-flashcards/reviewed.yml

- COVERED, with `card` — that card's question and answer rehearse the trap
  (a card of another item counts: the trap is what is judged, not its owner);
- GAP — no card does yet. Listed, never hidden; `--max-gaps N` makes the
  count a ceiling, and CI runs `--max-gaps 0`;
- NO_CARD, with `reason` — no card is warranted, and the reason says why.

A pitfall is a paragraph of the section, or a dash bullet with its
continuation lines. Its key is the course and the SHA-1 of its text with
whitespace collapsed, so a reworded pitfall is unreviewed again: a verdict
cannot outlive the sentence it judged. The audit fails on a pitfall without a
verdict, a verdict whose pitfall is gone, a COVERED card that does not exist, a
NO_CARD without a reason, an unknown verdict, a duplicate entry, a course with
no « Pièges d'examen » section or an empty one, and more GAPs than allowed.

    python3 tools/audit/pitfall_flashcards.py                # audit
    python3 tools/audit/pitfall_flashcards.py --max-gaps 0   # and no GAP
    python3 tools/audit/pitfall_flashcards.py --suggest      # candidates for the unreviewed
    python3 tools/audit/pitfall_flashcards.py --prove        # each failure, injected in memory

`--suggest` ranks an item's cards by TF-IDF cosine against each unreviewed
pitfall. It orders the reading and decides nothing. On 2026-10-24 every one of
the 788 pitfalls was read against all the cards of its item: above 0.5 the
best-ranked card covered the pitfall in the 32 cases sampled, but 26 covering
cards of the same item ranked below the top three and 27 pitfalls were covered
by a card of another item. The score is an aid to reading, not a substitute.
"""
from __future__ import annotations

import argparse
import collections
import copy
import hashlib
import math
import re
import sys
import unicodedata
from pathlib import Path

import yaml

ROOT = Path(__file__).resolve().parents[2]
REGISTRY = ROOT / 'docs/audit/pitfall-flashcards/reviewed.yml'
FRONT = re.compile(r'\A---\n(.*?)\n---\n(.*)\Z', re.S)
SECTION = re.compile(r"^## Pièges d'examen\n(.*?)(?=^## |\Z)", re.S | re.M)
VERDICTS = {'COVERED', 'GAP', 'NO_CARD'}


def units(section: str) -> list[str]:
    """The pitfalls of a section: paragraphs, and dash bullets with their continuation."""
    out: list[str] = []
    cur: list[str] = []
    for line in section.split('\n'):
        if not line.strip():
            if cur:
                out.append(' '.join(cur))
                cur = []
            continue
        if line.startswith('- ') and cur:
            out.append(' '.join(cur))
            cur = []
        cur.append(line.strip()[2:].strip() if line.startswith('- ') else line.strip())
    if cur:
        out.append(' '.join(cur))
    return out


def key_of(text: str) -> str:
    return hashlib.sha1(' '.join(text.split()).encode()).hexdigest()[:12]


def courses() -> dict[str, dict]:
    """course id => {item, title, pitfalls: [text], section: bool}."""
    out = {}
    for path in sorted((ROOT / 'content/courses').glob('*.md')):
        m = FRONT.match(path.read_text(encoding='utf-8'))
        front = yaml.safe_load(m.group(1))
        section = SECTION.search(m.group(2))
        out[front['id']] = {'item': front['official_item'], 'title': front['title'],
                            'section': section is not None,
                            'pitfalls': units(section.group(1)) if section else []}
    return out


def cards() -> dict[str, dict]:
    out = {}
    for path in sorted((ROOT / 'content/flashcards').glob('*.yml')):
        for card in (yaml.safe_load(path.read_text(encoding='utf-8')) or {}).get('flashcards') or []:
            out[card['id']] = card
    return out


def registry() -> list[dict]:
    return (yaml.safe_load(REGISTRY.read_text(encoding='utf-8')) or {}).get('pitfalls') or []


def audit(crs: dict, deck: dict, entries: list[dict], max_gaps: int | None) -> tuple[list[str], dict]:
    """Errors, and the counts the report prints. Pure: --prove calls it on altered copies."""
    errors: list[str] = []
    expected: dict[tuple[str, str], tuple[str, str]] = {}
    for cid, c in crs.items():
        if not c['section']:
            errors.append(f'{cid}: no « Pièges d\'examen » section')
        elif not c['pitfalls']:
            errors.append(f'{cid}: an empty « Pièges d\'examen » section')
        for text in c['pitfalls']:
            expected[(cid, key_of(text))] = (c['item'], text)

    seen: set[tuple[str, str]] = set()
    counts = collections.Counter()
    gaps, waived = [], []
    for e in entries:
        k = (e.get('course'), e.get('key'))
        if k in seen:
            errors.append(f'{k[0]} {k[1]}: reviewed twice')
            continue
        seen.add(k)
        if k not in expected:
            errors.append(f'{k[0]} {k[1]}: no such pitfall any more (reworded or removed) — review it again')
            continue
        verdict = e.get('verdict')
        if verdict not in VERDICTS:
            errors.append(f'{k[0]} {k[1]}: unknown verdict {verdict!r}')
        elif verdict == 'COVERED':
            card = deck.get(e.get('card'))
            if card is None:
                errors.append(f'{k[0]} {k[1]}: COVERED by {e.get("card")!r}, which is not a flashcard')
            else:
                counts['COVERED, same item' if card['official_item'] == expected[k][0] else 'COVERED, card of another item'] += 1
        elif verdict == 'NO_CARD':
            if not str(e.get('reason') or '').strip():
                errors.append(f'{k[0]} {k[1]}: NO_CARD without a reason')
            else:
                counts['NO_CARD'] += 1
                waived.append((k, e['reason']))
        else:
            counts['GAP'] += 1
            gaps.append(k)
    for k in expected:
        if k not in seen:
            errors.append(f'{k[0]} {k[1]}: unreviewed pitfall « {expected[k][1][:70]} »')
    if max_gaps is not None and len(gaps) > max_gaps:
        errors.append(f'{len(gaps)} GAP(s), more than the {max_gaps} allowed')
    counts['courses'] = len(crs)
    counts['pitfalls'] = len(expected)
    return errors, {'counts': counts, 'gaps': gaps, 'waived': waived, 'expected': expected}


# --------------------------------------------------------------------- suggest
STOP = set('''le la les un une des de du d l à a au aux et ou en est sont ne pas n que qui quoi dont dans pour
par sur avec sans se s il elle ils elles on ce c cet cette ces son sa ses leur leurs plus moins tout tous toute
toutes y lui même mais donc car si comme être avoir fait faire peut doit rien seul seule seulement aussi alors
encore jamais toujours quand où the an of to in on for and or is are be by with as at from that this which what
when does do it its into than then not no if only also more same other any each all one two both how why where
was were'''.split())


def _fold(s: str) -> str:
    return ''.join(ch for ch in unicodedata.normalize('NFD', s) if unicodedata.category(ch) != 'Mn')


def _stem(w: str) -> str:
    for suffix in ('ements', 'ement', 'ions', 'ion', 'ees', 'ee', 'es', 's', 'x'):
        if len(w) > len(suffix) + 3 and w.endswith(suffix):
            return w[:-len(suffix)]
    return w


def _tokens(text: str) -> list[str]:
    out = []
    for raw in re.findall(r"[A-Za-zÀ-ÿ_][A-Za-zÀ-ÿ0-9_\\:]*|\d+", text or ''):
        low = _fold(raw.lower())
        if low in STOP or len(low) < 2:
            continue
        out.append(_stem(low))
        if any(c.isupper() for c in raw[1:]) or '_' in raw or '::' in raw or '\\' in raw:
            parts = re.findall(r'[A-Z]+(?=[A-Z][a-z])|[A-Z]?[a-z]+|[A-Z]+|\d+', raw.replace('\\', ' ').replace('::', ' '))
            out += [_stem(_fold(p.lower())) for p in parts if p.lower() not in STOP and len(p) > 1]
    return out


def suggest(crs: dict, deck: dict, unreviewed: list[tuple[str, str]], expected: dict) -> None:
    by_item = collections.defaultdict(list)
    for card in deck.values():
        by_item[card['official_item']].append(card)
    for cid, key in unreviewed:
        item, text = expected[(cid, key)]
        pool = by_item[item]
        docs = [_tokens(f"{c['front']} {c['back']} {c.get('explanation', '')}") for c in pool]
        q = _tokens(text)
        df = collections.Counter(w for d in docs + [q] for w in set(d))
        n = len(docs) + 1

        def vec(ts):
            return {w: (1 + math.log(k)) * math.log((n + 1) / (df.get(w, 0) + 1)) for w, k in collections.Counter(ts).items()}

        def cos(a, b):
            num = sum(a[w] * b.get(w, 0) for w in a)
            den = math.sqrt(sum(x * x for x in a.values())) * math.sqrt(sum(x * x for x in b.values()))
            return num / den if den else 0.0

        qv = vec(q)
        ranked = sorted(((cos(qv, vec(d)), c) for d, c in zip(docs, pool)), key=lambda x: -x[0])[:3]
        print(f'## {cid} {key}\n   {text}')
        for score, c in ranked:
            print(f"   {score:.2f} {c['id']}: {' '.join(c['front'].split())} → {' '.join(c['back'].split())}")
        print()


# ----------------------------------------------------------------------- prove
def prove() -> int:
    crs, deck, entries = courses(), cards(), registry()
    if not entries:
        print('VACUOUS  the registry is empty: there is nothing to prove against')
        return 1
    errors, _ = audit(crs, deck, entries, 0)
    failed = 0
    if errors:
        print(f'FAILED  the real registry does not pass ({len(errors)} error(s)); --prove needs a clean base')
        return 1
    print('PROVED  the real registry passes with --max-gaps 0')

    covered = next(i for i, e in enumerate(entries) if e['verdict'] == 'COVERED')
    some_course = next(iter(crs))

    def altered(fn):
        c2, d2, e2 = copy.deepcopy(crs), dict(deck), copy.deepcopy(entries)
        fn(c2, d2, e2)
        return c2, d2, e2

    def drop(c, d, e):
        del e[covered]

    def stale(c, d, e):
        e.append({'course': some_course, 'key': '000000000000', 'verdict': 'GAP'})

    def ghost_card(c, d, e):
        e[covered] = dict(e[covered], card='FLC-000000000000')

    def bare_waiver(c, d, e):
        e[covered] = {k: v for k, v in e[covered].items() if k != 'card'} | {'verdict': 'NO_CARD', 'reason': ' '}

    def odd_verdict(c, d, e):
        e[covered] = dict(e[covered], verdict='MAYBE')

    def twice(c, d, e):
        e.append(dict(e[covered]))

    def no_section(c, d, e):
        c[some_course] = dict(c[some_course], section=False, pitfalls=[])
        e[:] = [x for x in e if x['course'] != some_course]

    def reworded(c, d, e):
        cid = e[covered]['course']
        texts = c[cid]['pitfalls']
        i = next(i for i, t in enumerate(texts) if key_of(t) == e[covered]['key'])
        c[cid] = dict(c[cid], pitfalls=texts[:i] + [texts[i] + ' (reworded)'] + texts[i + 1:])

    def one_gap(c, d, e):
        e[covered] = {k: v for k, v in e[covered].items() if k != 'card'} | {'verdict': 'GAP'}

    cases = {
        'a pitfall without a verdict': drop,
        'a verdict whose pitfall is gone': stale,
        'a COVERED card that does not exist': ghost_card,
        'a NO_CARD without a reason': bare_waiver,
        'an unknown verdict': odd_verdict,
        'a pitfall reviewed twice': twice,
        'a course without « Pièges d\'examen »': no_section,
        'a reworded pitfall keeps its old verdict': reworded,
        'a GAP over --max-gaps 0': one_gap,
    }
    for name, fn in cases.items():
        errs, _ = audit(*altered(fn), 0)
        if errs:
            print(f'PROVED  {name}: refused ({errs[0][:90]})')
        else:
            print(f'FAILED  {name}: accepted')
            failed += 1
    errs, _ = audit(*altered(one_gap), None)
    if errs:
        print('FAILED  a GAP without a ceiling is refused, though it should only be reported')
        failed += 1
    else:
        print('PROVED  a GAP without --max-gaps is reported, not refused')
    return 1 if failed else 0


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__.split('\n\n')[0])
    ap.add_argument('--max-gaps', type=int, default=None)
    ap.add_argument('--suggest', action='store_true')
    ap.add_argument('--prove', action='store_true')
    args = ap.parse_args()
    if args.prove:
        return prove()

    crs, deck, entries = courses(), cards(), registry()
    errors, report = audit(crs, deck, entries, args.max_gaps)
    c = report['counts']
    print(f"Pitfall → flashcard audit: {c['courses']} courses, {c['pitfalls']} pitfalls")
    for label in ('COVERED, same item', 'COVERED, card of another item', 'NO_CARD', 'GAP'):
        print(f'  {label:<32} {c[label]}')
    for (cid, key), reason in report['waived']:
        print(f'  NO_CARD {cid} {key}: {reason}')
    for cid, key in report['gaps']:
        print(f"  GAP     {cid} {key}: {report['expected'][(cid, key)][1][:90]}")
    if args.suggest:
        reviewed = {(e.get('course'), e.get('key')) for e in entries}
        todo = [k for k in report['expected'] if k not in reviewed]
        print(f'\n{len(todo)} unreviewed pitfall(s); best-ranked cards of the same item:\n')
        suggest(crs, deck, todo, report['expected'])
    for e in errors:
        print(f'ERROR {e}')
    print(f'{len(errors)} error(s)')
    return 1 if errors else 0


if __name__ == '__main__':
    sys.exit(main())
