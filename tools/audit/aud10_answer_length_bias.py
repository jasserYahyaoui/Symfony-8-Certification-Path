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

THRESHOLD. A lot must not exceed its chance baseline: at that point the length
carries no signal. The figure is not invented — lot-01, the first lot refined
under framework version 2, measured 21% against its own 25% baseline.

The bar was at first staged to the refined lots, exactly as ARC-001, PED-003 and
REV-001 were, so that 550 questions written before the axis existed did not fail
the build. That staging was removed on 2026-09-15 with ADR-0007's exit act: all
twenty-six lots carrying atomic official items are recorded at framework
version 2, so it covered nobody, and a tolerance that covers nothing still tells
the next reader the bar is optional.

KNOWN LIMIT, unresolved. Under a small denominator the step of this measure is
larger than the effect it measures: with four questions the possible values are
0/25/50/75/100%, so one biased question is tolerated and the second fails the
lot. Three consecutive refinement units reported it (lots 14-17, 18-21, 22-26)
and lot 19 was edited over a ONE-character gap. The rule has never been weakened
to accommodate it; the governance decision is open.

Exit code 1 if any lot exceeds its chance baseline.

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
    per = collections.defaultdict(lambda: {'longest': 0, 'total': 0, 'chance': 0.0})
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
        if len(winners) == 1 and winners[0]:
            bucket['longest'] += 1

        for length, is_correct in lengths:
            (correct_len if is_correct else distractor_len).append(length)

    return per, correct_len, distractor_len


def gate(per):
    """@return list[str] one finding per lot above its chance baseline."""
    out = []
    for lot in sorted(per):
        b = per[lot]
        rate = b['longest'] / b['total'] * 100
        base = b['chance'] / b['total'] * 100
        if rate > base:
            out.append(
                f'LEN-1  {lot} has its correct answer longest in '
                f'{rate:.1f}% of questions, above the {base:.1f}% chance baseline'
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
        print('PROOF OK — LEN-1 fires when a lot leans long:')
        for f in found:
            print('  ' + f)
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

    print('\n  per lot (every lot is gated since ADR-0007\'s exit act):')
    for lot in sorted(per):
        b = per[lot]
        rate = b['longest'] / b['total'] * 100
        base = b['chance'] / b['total'] * 100
        print(f'    GATED {lot}  {b["longest"]:3}/{b["total"]:3} = {rate:5.1f}%  (chance {base:.1f}%)')

    findings = gate(per)

    for f in findings:
        print(f)
    print(f'\nFINDINGS: {len(findings)}')
    return 1 if findings else 0


sys.exit(main())
