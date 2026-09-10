"""AUD-11 — question-level triage of the answer-length cue (READ-ONLY).

AUD-10 measures the corpus rate: the correct answer is strictly the longest in
50.5% of single-answer questions against a 25% chance baseline. That figure is a
symptom. It does not say which questions are actually exploitable, and acting on
the rate alone would mean editing verified content to move a number.

This script triages every question so the correction scope can be decided from
evidence. It modifies nothing.

    NO_BIAS                     no usable cue
    JUSTIFIED_LENGTH            the extra length carries a condition the answer needs
    REVIEW_REQUIRED             the margin could reveal the answer — needs human reading
    ARTIFICIAL_DISTRACTOR_RISK  shortening or padding here would produce a worse question
    CONFIRMED_LENGTH_CUE        NEVER assigned by this script. It is the verdict of a
                                human who has read the question and its distractors.

Two rules govern the classifier, and both exist to stop it manufacturing work:

  A correct answer is allowed to be the longest. A condition, an exception or a
  reason is part of the answer, and removing it to equalise character counts
  would make the answer wrong. Those land in JUSTIFIED_LENGTH.

  25% is a statistical reference, not a per-lot quota. Nothing here compares a
  lot against 25%; the unit of judgement is the question.

Thresholds, all reported so they can be disagreed with:

  MIN_REL_MARGIN  0.15  a correct answer up to 15% longer than the longest
                        distractor is not a cue a candidate can act on.
  For a multiple-answer question the margin is measured from the SHORTEST
  correct answer, because "pick the N longest" only works if every one of them
  outranks every distractor.
  STRONG_MARGIN   1.00  at least twice the longest distractor — flagged inside
                        REVIEW_REQUIRED as the subset to read first.
  SHORT_CHOICE    25    below this, choices are tokens (a status code, a class
                        name); padding a distractor to match would be absurd.
"""
import collections
import glob
import pathlib
import re
import sys

import yaml

ROOT = pathlib.Path(__file__).resolve().parents[2]

MIN_REL_MARGIN = 0.15
STRONG_MARGIN = 1.00
SHORT_CHOICE = 25

# A clause that makes an answer longer because the answer needs it.
NUANCE = re.compile(
    r'\b(unless|except|only if|only when|but not|because|since|as long as|'
    r'provided|when the|if the|which is why|so that|rather than|instead of|'
    r'sauf|à moins|parce que|puisque|tant que|alors que|plutôt que|au lieu de)\b',
    re.I,
)


def load_questions():
    out = []
    for path in sorted(glob.glob(str(ROOT / 'content/questions/*.yml'))):
        for q in yaml.safe_load(pathlib.Path(path).read_text(encoding='utf-8'))['questions']:
            out.append(q)
    return out


def triage(q):
    """@return (label, margin_abs, margin_rel, strong)"""
    correct = [c['text'] for c in q['choices'] if c.get('correct')]
    distractors = [c['text'] for c in q['choices'] if not c.get('correct')]
    if not correct or not distractors:
        return 'NO_BIAS', 0, 0.0, False

    # Single answer compares the answer with the longest distractor.
    #
    # Multiple answer compares NOTHING unless the cue actually works: the
    # exploitable strategy is "pick the N longest", so the cue exists only if
    # the N longest choices ARE the correct set. Comparing means was the first
    # implementation and it was wrong — QST-f2m90kz2ya28 has its two correct
    # answers at 125 and 12 characters, the longest choice and the shortest, so
    # the mean flagged it at +145% while "pick the two longest" gets it wrong.
    if q['answer_mode'] == 'single':
        c_len, d_len = len(correct[0]), max(len(d) for d in distractors)
    else:
        ranked = sorted(q['choices'], key=lambda c: -len(c['text']))
        top = ranked[:len(correct)]
        if not all(c.get('correct') for c in top):
            return 'NO_BIAS', 0, 0.0, False
        c_len = min(len(c['text']) for c in top)
        d_len = max(len(c['text']) for c in q['choices'] if not c.get('correct'))

    margin_abs = c_len - d_len
    margin_rel = margin_abs / d_len if d_len else 0.0
    strong = margin_rel >= STRONG_MARGIN

    if margin_abs <= 0 or margin_rel < MIN_REL_MARGIN:
        return 'NO_BIAS', margin_abs, margin_rel, False

    # The extra length carries a condition no distractor states.
    if any(NUANCE.search(c) for c in correct) and not any(NUANCE.search(d) for d in distractors):
        return 'JUSTIFIED_LENGTH', margin_abs, margin_rel, strong

    # Token-sized choices: padding a distractor to match would be absurd.
    if max(len(d) for d in distractors) < SHORT_CHOICE:
        return 'ARTIFICIAL_DISTRACTOR_RISK', margin_abs, margin_rel, strong

    return 'REVIEW_REQUIRED', margin_abs, margin_rel, strong


def main() -> int:
    matrix = yaml.safe_load((ROOT / 'docs/syllabus/syllabus-matrix.yml').read_text(encoding='utf-8'))
    lot_of = {i['id']: i['lot'] for i in matrix['items']}
    topic_of = {i['id']: i['official_topic'] for i in matrix['items']}

    questions = load_questions()
    rows = []
    for q in questions:
        label, m_abs, m_rel, strong = triage(q)
        rows.append({
            'id': q['id'], 'lot': lot_of[q['official_item']], 'topic': topic_of[q['official_item']],
            'pool': q['pool'], 'difficulty': q['difficulty'], 'mode': q['answer_mode'],
            'code': bool(q.get('code_language')), 'label': label,
            'margin_abs': m_abs, 'margin_rel': m_rel, 'strong': strong,
        })

    print(f'  questions triaged                            {len(rows)}\n')
    counts = collections.Counter(r['label'] for r in rows)
    for label in ('NO_BIAS', 'JUSTIFIED_LENGTH', 'ARTIFICIAL_DISTRACTOR_RISK', 'REVIEW_REQUIRED'):
        print(f'  {label:28} {counts[label]:4}  ({counts[label] / len(rows) * 100:4.1f}%)')
    print(f'  {"CONFIRMED_LENGTH_CUE":28}    0  (never assigned by a script — human reading)')

    review = [r for r in rows if r['label'] == 'REVIEW_REQUIRED']
    strong = [r for r in review if r['strong']]
    print(f'\n  REVIEW_REQUIRED of which "strong" (>= 2x longest distractor)  {len(strong)}')

    def breakdown(key, label):
        print(f'\n  REVIEW_REQUIRED by {label}:')
        c = collections.Counter(r[key] for r in review)
        tot = collections.Counter(r[key] for r in rows)
        for k in sorted(c, key=lambda k: -c[k]):
            print(f'    {str(k):12} {c[k]:3} / {tot[k]:3} = {c[k] / tot[k] * 100:5.1f}%')

    breakdown('pool', 'pool')
    breakdown('mode', 'answer mode')
    breakdown('lot', 'lot')

    print('\n  strongest candidates for human reading (top 12 by relative margin):')
    for r in sorted(strong, key=lambda r: -r['margin_rel'])[:12]:
        print(f'    {r["id"]}  {r["lot"]:7} {r["pool"]:10} +{r["margin_abs"]:5.0f} chars '
              f'({r["margin_rel"] * 100:5.0f}%)  {r["topic"][:28]}')

    print('\nFINDINGS: 0  (triage is read-only and reports; it fails nothing)')
    return 0


sys.exit(main())
