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

THRESHOLD. A lot recorded as refined under the current framework must not exceed
the chance baseline: at that point the length carries no signal. The figure is
not invented — lot-01, the only lot refined under framework version 2, measures
21%, below its own 25% baseline. The rest of the corpus is reported and not
failed, exactly as ARC-001, PED-003 and REV-001 are staged: the bar bites where
refinement is claimed.

Exit code 1 if a refined lot exceeds its chance baseline.

`--prove` re-runs the measurement over the same corpus with every correct answer
in a refined lot lengthened IN MEMORY, and asserts the finding fires. No file is
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


def refined_lots() -> set[str]:
    log = ROOT / 'docs/progress/refinement-log.yml'
    if not log.is_file():
        return set()
    doc = yaml.safe_load(log.read_text(encoding='utf-8'))
    return {
        str(e['lot'])
        for e in (doc.get('lots') or [])
        if isinstance(e, dict) and int(e.get('framework_version', 1)) >= 2
    }


def measure(questions, lot_of, refined, pad: int = 0):
    """Return (per-lot buckets, correct lengths, distractor lengths).

    `pad` lengthens every correct answer in a refined lot by that many
    characters — the injection used by --prove, applied to the values read from
    disk and never written back.
    """
    per = collections.defaultdict(lambda: {'longest': 0, 'total': 0, 'chance': 0.0})
    correct_len, distractor_len = [], []

    for q in questions:
        if q['answer_mode'] != 'single':
            continue
        lot = lot_of[q['official_item']]
        bump = pad if (pad and lot in refined) else 0
        lengths = [
            (len(c['text']) + (bump if c.get('correct') else 0), bool(c.get('correct')))
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


def gate(per, refined):
    """@return list[str] one finding per refined lot above its chance baseline."""
    out = []
    for lot in sorted(per):
        b = per[lot]
        rate = b['longest'] / b['total'] * 100
        base = b['chance'] / b['total'] * 100
        if lot in refined and rate > base:
            out.append(
                f'LEN-1  {lot} is refined but its correct answer is the longest in '
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

    refined_for_measure = refined_lots()
    per, correct_len, distractor_len = measure(single, lot_of, refined_for_measure)

    if '--prove' in sys.argv:
        proved, _, _ = measure(single, lot_of, refined_for_measure, pad=120)
        found = gate(proved, refined_for_measure)
        if not found:
            print('PROOF FAILED — LEN-1 stayed silent on a corpus whose refined-lot '
                  'answers were all made the longest; the check is VACUOUS')
            return 1
        print('PROOF OK — LEN-1 fires when a refined lot leans long:')
        for f in found:
            print('  ' + f)
        print('  (measured in memory; no file was read for writing or modified)')

    total = sum(b['total'] for b in per.values())
    longest = sum(b['longest'] for b in per.values())
    chance = sum(b['chance'] for b in per.values())
    refined = refined_for_measure

    print(f'  single-answer questions                      {total}')
    print(f'  correct answer strictly the longest          {longest} = {longest / total * 100:.1f}%')
    print(f'  expected by chance                           {chance / total * 100:.1f}%')
    print(f'  mean length   correct / distractor           '
          f'{statistics.mean(correct_len):.0f} / {statistics.mean(distractor_len):.0f} chars')
    print(f'  median length correct / distractor           '
          f'{statistics.median(correct_len):.0f} / {statistics.median(distractor_len):.0f} chars')
    print(f'  lots refined under the current framework     {len(refined)}'
          f' ({", ".join(sorted(refined)) or "none"})')

    print('\n  per lot (refined lots are gated, the rest reported):')
    for lot in sorted(per):
        b = per[lot]
        rate = b['longest'] / b['total'] * 100
        base = b['chance'] / b['total'] * 100
        mark = 'GATED ' if lot in refined else '      '
        print(f'    {mark}{lot}  {b["longest"]:3}/{b["total"]:3} = {rate:5.1f}%  (chance {base:.1f}%)')

    findings = gate(per, refined)

    for f in findings:
        print(f)
    print(f'\nFINDINGS: {len(findings)}')
    return 1 if findings else 0


sys.exit(main())
