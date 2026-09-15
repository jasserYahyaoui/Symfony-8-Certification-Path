"""AUD-10 — the length of a choice must not give the answer away.

A candidate who knows nothing can still score by picking the longest option, if
the bank writes correct answers longer than distractors. That is test-wiseness,
not knowledge: it inflates every practice score and teaches a heuristic the real
exam will not reward.

Measured on the corpus at 2026-09-09, over 537 single-answer questions:

    correct answer strictly the longest   271/537 = 50.5%
    expected by chance (4 choices)                  25.0%
    mean length   correct 55 chars, distractor 42
    median length correct 49 chars, distractor 41

No existing rule sees this. CRS-001 guards the course against revealing an
answer; DUP-001 guards against repetition; nothing measures the shape of the
choices against each other.

THRESHOLD. A lot fails when the count of longest-correct answers is higher than
chance explains — an exact one-sided Poisson-binomial tail, not a comparison of
two percentages.

WHY THE TEST CHANGED, 2026-09-15. Until today the rule failed a lot whenever its
observed rate exceeded the chance baseline. That comparison has no notion of
sampling variability, so it called luck a defect:

    n=4  obs=2   P(X>=2 | chance) = 0.26   <- old verdict: FAIL
    n=5  obs=2   P(X>=2 | chance) = 0.37   <- old verdict: FAIL
    n=9  obs=3   P(X>=3 | chance) = 0.40   <- old verdict: FAIL
    n=69 obs=18  P(X>=18| chance) = 0.46   <- old verdict: FAIL

A lot of four questions with two longest-correct occurs 26% of the time from
pure chance, and was failed as biased. Three consecutive refinement units
reported the symptom (lots 14-17, 18-21, 22-26) and lot 19 was edited over a
ONE-character gap to satisfy it. The cause was not a small denominator; it was a
test that never computed the probability it was implicitly claiming.

This is NOT the rule being relaxed to go green. Nothing was red under either
test when the change was made: the smallest per-lot probability across all
twenty-six lots was 0.61. And the bias the audit was written for is still caught
with room to spare — the 2026-09-09 corpus, 271 of 537, gives P = 9e-37.

Each question contributes its own 1/len(choices), so a three-choice question is
not assumed to be a four-choice one; that is why the tail is Poisson-binomial
rather than binomial.

ALPHA = 0.01, not 0.05, because twenty-six lots are tested on every push: at
0.05 roughly one lot per run would fail on noise alone, which teaches the reader
to ignore the gate. It is the multiplicity that sets the level, not taste.

HONEST LIMIT, quantified rather than waved at. A lot of three questions can
never fail: even if all three correct answers were the longest, P = 0.0156,
above alpha. Four is the smallest denominator this test can act on. That is a
property of three observations, not a loophole — and it is printed on every run
rather than left for a reader to derive.

The bar was at first staged to the refined lots, exactly as ARC-001, PED-003 and
REV-001 were, so that 550 questions written before the axis existed did not fail
the build. That staging was removed on 2026-09-15 with ADR-0007's exit act: all
twenty-six lots carrying atomic official items are recorded at framework
version 2, so it covered nobody, and a tolerance that covers nothing still tells
the next reader the bar is optional.

Exit code 1 if any lot's excess is beyond chance at alpha.

`--prove` re-runs the measurement over the same corpus with every correct answer
lengthened IN MEMORY, and asserts the finding fires. No file is
touched, so the proof is safe to run in CI on every push: a check that has only
ever printed FINDINGS: 0 has not been shown to be capable of anything else.
"""
import collections
import glob
import pathlib
import statistics
import sys

import yaml

ROOT = pathlib.Path(__file__).resolve().parents[2]


def measure(questions, lot_of, pad: int = 0):
    """Return (per-lot buckets, correct lengths, distractor lengths).

    `pad` lengthens every correct answer by that many characters — the injection
    used by --prove, applied to the values read from disk and never written back.
    """
    per = collections.defaultdict(lambda: {'longest': 0, 'total': 0, 'chance': 0.0, 'ps': []})
    correct_len, distractor_len = [], []

    for q in questions:
        if q['answer_mode'] != 'single':
            continue
        lot = lot_of[q['official_item']]
        lengths = [
            (len(c['text']) + (pad if c.get('correct') else 0), bool(c.get('correct')))
            for c in q['choices']
        ]
        longest = max(length for length, _ in lengths)
        winners = [is_correct for length, is_correct in lengths if length == longest]

        bucket = per[lot]
        bucket['total'] += 1
        bucket['chance'] += 1 / len(q['choices'])
        bucket['ps'].append(1 / len(q['choices']))
        if len(winners) == 1 and winners[0]:
            bucket['longest'] += 1

        for length, is_correct in lengths:
            (correct_len if is_correct else distractor_len).append(length)

    return per, correct_len, distractor_len


ALPHA = 0.01


def tail(ps: list[float], k: int) -> float:
    """P(X >= k) where X is a sum of independent Bernoulli(p_i). Exact.

    Each question carries its own probability of the correct answer being the
    longest by chance — 1/len(choices) — so the distribution is Poisson-binomial
    rather than binomial, and a three-choice question is not silently counted as
    a four-choice one. Computed by convolution, which is exact in the arithmetic
    that matters here: the corpus is ~700 questions, not a sample where a normal
    approximation would be needed.
    """
    dist = [1.0]
    for p in ps:
        nxt = [0.0] * (len(dist) + 1)
        for i, v in enumerate(dist):
            nxt[i] += v * (1 - p)
            nxt[i + 1] += v * p
        dist = nxt

    return sum(dist[k:])


def smallest_actionable(p: float = 0.25) -> int:
    """How many questions a lot needs before this test can ever fail it.

    Below it, even a lot whose every correct answer is the longest sits above
    alpha. Printed on every run so the limit is read rather than derived.
    """
    n = 1
    while p ** n >= ALPHA:
        n += 1

    return n


def gate(per):
    """@return list[str] one finding per lot whose excess chance cannot explain."""
    out = []
    for lot in sorted(per):
        b = per[lot]
        p = tail(b['ps'], b['longest'])
        if p < ALPHA:
            rate = b['longest'] / b['total'] * 100
            out.append(
                f'LEN-1  {lot} has its correct answer longest in {b["longest"]} of '
                f'{b["total"]} questions ({rate:.1f}%); chance explains that with '
                f'probability {p:.2g}, below alpha {ALPHA}'
            )
    return out


def main() -> int:
    matrix = yaml.safe_load((ROOT / 'docs/syllabus/syllabus-matrix.yml').read_text(encoding='utf-8'))
    lot_of = {i['id']: i['lot'] for i in matrix['items']}

    questions = []
    for path in sorted(glob.glob(str(ROOT / 'content/questions/*.yml'))):
        questions += yaml.safe_load(pathlib.Path(path).read_text(encoding='utf-8'))['questions']
    single = [q for q in questions if q['answer_mode'] == 'single']

    per, correct_len, distractor_len = measure(single, lot_of)

    if '--prove' in sys.argv:
        proved, _, _ = measure(single, lot_of, pad=120)
        found = gate(proved)
        if not found:
            print('PROOF FAILED — LEN-1 stayed silent on a corpus whose correct '
                  'answers were all made the longest; the check is VACUOUS')
            return 1
        floor = smallest_actionable()
        silent = sorted(lot for lot, b in proved.items() if b['total'] < floor)
        print(f'PROOF OK — LEN-1 fires on {len(found)} of {len(proved)} lots when every '
              'correct answer is made the longest:')
        for f in found:
            print('  ' + f)
        if silent:
            print(f'  NOT fired, and correctly so: {", ".join(silent)} — under {floor} '
                  'questions, total bias still sits above alpha. Stated here so the '
                  'proof cannot be read as covering what it does not cover.')
        # A test relaxed far enough to miss what the audit was written for would
        # still pass the injection above, because pad=120 is absurd. This second
        # assertion pins the real historical defect instead: the 2026-09-09
        # corpus, 271 longest-correct of 537 four-choice questions.
        historical = tail([0.25] * 537, 271)
        if historical >= ALPHA:
            print(f'PROOF FAILED — the 2026-09-09 corpus (271/537) would now pass at '
                  f'P={historical:.3g}; the test no longer catches the bias it exists for')
            return 1
        print(f'  and the defect this audit was written for — the 2026-09-09 corpus, '
              f'271 of 537 — still fails at P={historical:.2g}')
        print('  (measured in memory; no file was read for writing or modified)')

    total = sum(b['total'] for b in per.values())
    longest = sum(b['longest'] for b in per.values())
    chance = sum(b['chance'] for b in per.values())
    print(f'  single-answer questions                      {total}')
    print(f'  correct answer strictly the longest          {longest} = {longest / total * 100:.1f}%')
    print(f'  expected by chance                           {chance / total * 100:.1f}%')
    print(f'  mean length   correct / distractor           '
          f'{statistics.mean(correct_len):.0f} / {statistics.mean(distractor_len):.0f} chars')
    print(f'  median length correct / distractor           '
          f'{statistics.median(correct_len):.0f} / {statistics.median(distractor_len):.0f} chars')
    print(f'  lots measured                                {len(per)}')
    print(f'  corpus, P(this many or more by chance)       {tail([p for b in per.values() for p in b["ps"]], longest):.3g}')

    floor = smallest_actionable()
    print(f'\n  per lot — every lot is gated; P is the probability chance alone')
    print(f'  produces this many or more. A lot under {floor} questions cannot fail even')
    print(f'  when every correct answer is the longest, and is marked so.')
    for lot in sorted(per):
        b = per[lot]
        rate = b['longest'] / b['total'] * 100
        p = tail(b['ps'], b['longest'])
        mark = 'too small' if b['total'] < floor else ''
        print(f'    {lot}  {b["longest"]:3}/{b["total"]:3} = {rate:5.1f}%   P={p:7.4f}  {mark}')

    findings = gate(per)

    for f in findings:
        print(f)
    print(f'\nFINDINGS: {len(findings)}')
    return 1 if findings else 0


sys.exit(main())
