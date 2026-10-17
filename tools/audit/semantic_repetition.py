#!/usr/bin/env python3
"""Semantic repetition between exam-bank questions (remediation P3.1).

DUP-001 catches a repeated prompt, or a prompt and an answer both close
character by character. It cannot see the same question asked in other words:
« Which events does ConsoleEvents declare? » and the same with « (Choose 2) »
share a stimulus and a fact but not an answer string. This audit compares
meaning as far as a lexical model can: every LEARNING and VALIDATION question
becomes a TF-IDF vector of its prompt and its correct answers (identifiers
split, the answer weighted twice), and every pair at cosine >= 0.45 is
flagged.

A flag is not a verdict. Each visible pair is reviewed once and recorded in
docs/audit/semantic-repetition/reviewed.yml:

- DISTINCT — the pair shares words, not knowledge;
- PARALLEL — same outcome, but a learner who memorized one answer must still
  reason to answer the other (a parallel form, which is what assessment wants);
- REPEAT   — the same stimulus with the same conclusion: one answer gives the
  other. A REPEAT is a finding until one of the two is rewritten.

The audit fails on a flagged pair that is unreviewed, reviewed against an
older version of either question, or still REPEAT. HOLDOUT questions are
compared like the others and reported by count alone — pool pair and band,
never an id or a word — because reading them is not this audit's to do.

    python3 tools/audit/semantic_repetition.py           # audit, exit 1 on a finding
    python3 tools/audit/semantic_repetition.py --pairs   # the flagged visible pairs, for review
    python3 tools/audit/semantic_repetition.py --prove   # the audit fires on injected defects

The model is lexical: two questions that test one fact with no word in common
are not seen. The threshold was set by reading every pair above 0.45 on
2026-10-17, not tuned to a count.
"""
from __future__ import annotations

import copy
import math
import re
import sys
from collections import Counter
from pathlib import Path

import yaml

ROOT = Path(__file__).resolve().parents[2]
REVIEWED = ROOT / 'docs/audit/semantic-repetition/reviewed.yml'
THRESHOLD = 0.45
VERDICTS = {'DISTINCT', 'PARALLEL', 'REPEAT'}
STOP = set('''a an the of to in on for and or is are be by with as at from that this which what when does do did
it its into than then there their they them can could should would will not no yes if else only also more most
same other any each all one two three both who whom whose how why where after before while about between
under over such these those have has had been being was were your you we our us i me my he she his her'''.split())


def _split_identifier(token: str) -> list[str]:
    return [p.lower() for p in re.findall(r'[A-Z]+(?=[A-Z][a-z])|[A-Z]?[a-z]+|[A-Z]+|\d+', token) if p]


def _stem(word: str) -> str:
    for suffix in ('ing', 'ed', 'es', 's'):
        if len(word) > len(suffix) + 3 and word.endswith(suffix):
            return word[: -len(suffix)]
    return word


def tokens(text: str) -> list[str]:
    out: list[str] = []
    for raw in re.findall(r'[A-Za-z_][A-Za-z0-9_\\:]*|\d+', text or ''):
        low = raw.lower()
        if low in STOP or len(low) < 2:
            continue
        out.append(_stem(low))
        # An identifier counts whole and by its parts: setSharedMaxAge also
        # says shared, max, age.
        if any(c.isupper() for c in raw[1:]) or '_' in raw or '::' in raw or '\\' in raw:
            out.extend(_stem(p) for p in _split_identifier(raw.replace('\\', ' ').replace('::', ' '))
                       if p not in STOP and len(p) > 1)
    return out


def load_questions() -> list[dict]:
    questions = []
    for path in sorted((ROOT / 'content/questions').glob('*.yml')):
        for q in yaml.safe_load(path.read_text())['questions']:
            correct = ' '.join(c['text'] for c in q['choices'] if c.get('correct'))
            questions.append({
                'id': q['id'], 'pool': q['pool'], 'item': q['official_item'], 'version': q.get('version', 1),
                'terms': tokens(q['question']) + tokens(correct) * 2,
            })
    return questions


def flag(questions: list[dict]) -> list[tuple[float, dict, dict]]:
    df = Counter()
    for q in questions:
        df.update(set(q['terms']))
    n = len(questions)
    for q in questions:
        tf = Counter(q['terms'])
        vec = {t: (1 + math.log(c)) * math.log((n + 1) / (df[t] + 1)) for t, c in tf.items()}
        norm = math.sqrt(sum(v * v for v in vec.values())) or 1.0
        q['vector'] = {t: v / norm for t, v in vec.items()}
    flagged = []
    for i in range(n):
        a = questions[i]['vector']
        for j in range(i + 1, n):
            b = questions[j]['vector']
            small, large = (a, b) if len(a) <= len(b) else (b, a)
            score = sum(v * large.get(t, 0.0) for t, v in small.items())
            if score >= THRESHOLD:
                flagged.append((score, questions[i], questions[j]))
    flagged.sort(key=lambda f: -f[0])
    return flagged


def pair_key(a: str, b: str) -> str:
    return ' '.join(sorted((a, b)))


def load_reviewed(path: Path = REVIEWED) -> dict[str, dict]:
    if not path.is_file():
        return {}
    return {pair_key(*r['pair']): r for r in yaml.safe_load(path.read_text())['pairs']}


def findings(flagged, reviewed) -> tuple[list[str], Counter]:
    problems: list[str] = []
    holdout: Counter = Counter()
    for score, a, b in flagged:
        if 'HOLDOUT' in (a['pool'], b['pool']):
            other = b['pool'] if a['pool'] == 'HOLDOUT' else a['pool']
            band = f'{int(score * 10) / 10:.1f}'
            holdout[(other, 'same item' if a['item'] == b['item'] else 'other item', band)] += 1
            continue
        where = f'{a["id"]} ~ {b["id"]} ({score:.2f})'
        entry = reviewed.get(pair_key(a['id'], b['id']))
        if entry is None:
            problems.append(f'{where}: flagged and not reviewed')
            continue
        if entry['verdict'] not in VERDICTS:
            problems.append(f'{where}: unknown verdict {entry["verdict"]}')
            continue
        versions = {a['id']: a['version'], b['id']: b['version']}
        if entry.get('versions') != versions:
            problems.append(f'{where}: reviewed at versions {entry.get("versions")}, now {versions} — review again')
            continue
        if entry['verdict'] == 'REPEAT':
            problems.append(f'{where}: REPEAT — {entry["reason"]}')
    return problems, holdout


def stale(flagged, reviewed) -> list[str]:
    """Reviewed pairs no longer flagged: kept, but said, so the file does not rot."""
    live = {pair_key(a['id'], b['id']) for _, a, b in flagged}
    return sorted(k for k in reviewed if k not in live)


def report(flagged, reviewed) -> int:
    problems, holdout = findings(flagged, reviewed)
    visible = [f for f in flagged if 'HOLDOUT' not in (f[1]['pool'], f[2]['pool'])]
    verdicts = Counter(reviewed[pair_key(a['id'], b['id'])]['verdict'] for _, a, b in visible
                       if pair_key(a['id'], b['id']) in reviewed)
    print(f'{len(flagged)} pair(s) at cosine >= {THRESHOLD}: {len(visible)} visible, '
          f'{len(flagged) - len(visible)} involving a HOLDOUT question')
    print('visible, by verdict: ' + ', '.join(f'{v} {verdicts.get(v, 0)}' for v in sorted(VERDICTS)))
    for (other, scope, band), count in sorted(holdout.items()):
        print(f'HOLDOUT ~ {other}, {scope}, cosine {band}+: {count} (ids withheld)')
    for key in stale(flagged, reviewed):
        print(f'note  {key}: reviewed, no longer flagged')
    for line in problems:
        print(f'FINDING  {line}')
    print(f'FINDINGS: {len(problems)}')
    return 1 if problems else 0


def pairs(flagged) -> int:
    questions = {}
    for path in sorted((ROOT / 'content/questions').glob('*.yml')):
        for q in yaml.safe_load(path.read_text())['questions']:
            if q['pool'] != 'HOLDOUT':
                questions[q['id']] = q
    for score, a, b in flagged:
        if 'HOLDOUT' in (a['pool'], b['pool']):
            continue
        print(f'--- {score:.3f}')
        for q in (questions[a['id']], questions[b['id']]):
            correct = ' | '.join(c['text'] for c in q['choices'] if c.get('correct'))
            print(f'  {q["id"]} v{q.get("version", 1)} {q["pool"]} {q["official_item"]}: {q["question"].strip()}')
            print(f'      => {correct}')
    return 0


def prove(flagged, reviewed) -> int:
    """Each finding fires on one injected defect, in memory."""
    visible = [f for f in flagged if 'HOLDOUT' not in (f[1]['pool'], f[2]['pool'])]
    clean, _ = findings(flagged, reviewed)
    if clean or not visible:
        print('the audit is not clean, or nothing is flagged: nothing to prove against', file=sys.stderr)
        return 1
    _, a, b = visible[0]
    key = pair_key(a['id'], b['id'])
    cases = {
        'an unreviewed pair': ({k: v for k, v in reviewed.items() if k != key}, 'not reviewed'),
        'a REPEAT left open': ({**reviewed, key: {**reviewed[key], 'verdict': 'REPEAT'}}, 'REPEAT'),
        'a question rewritten since its review': (
            {**reviewed, key: {**reviewed[key], 'versions': {a['id']: a['version'] - 1, b['id']: b['version']}}},
            'review again'),
        'an unknown verdict': ({**reviewed, key: {**reviewed[key], 'verdict': 'FINE'}}, 'unknown verdict'),
    }
    failed = 0
    for name, (broken, needle) in cases.items():
        problems, _ = findings(flagged, broken)
        hit = any(needle in p for p in problems)
        failed += 0 if hit else 1
        print(f'{"PROVED" if hit else "VACUOUS"}  {name}: {len(problems)} finding(s)')
    # The model itself, at its weakest claim: the same terms in another order
    # are flagged. It says nothing about a paraphrase sharing no words, which
    # a lexical model cannot see (see the docstring).
    probe = copy.deepcopy(a)
    probe['id'] = 'QST-probe'
    probe['terms'] = list(reversed(a['terms']))
    seen = any(p[1]['id'] == 'QST-probe' or p[2]['id'] == 'QST-probe' for p in flag([a, probe, *[copy.deepcopy(q) for _, q, _ in visible[1:6]]]))
    failed += 0 if seen else 1
    print(f'{"PROVED" if seen else "VACUOUS"}  the same terms in another order are flagged')
    return 1 if failed else 0


def main(argv: list[str]) -> int:
    flagged = flag(load_questions())
    if argv == ['--pairs']:
        return pairs(flagged)
    if argv == ['--prove']:
        return prove(flagged, load_reviewed())
    return report(flagged, load_reviewed())


if __name__ == '__main__':
    sys.exit(main(sys.argv[1:]))
