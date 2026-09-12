"""Proof that the refinement-framework rules of ADR-0007 actually fire.

ARC-001, PED-003 and REV-001 were added to a corpus that satisfies all three,
so each of them reports nothing on the canonical data. A rule that has only
ever been silent has not been shown to be capable of speaking: five checks in
this project were found to be vacuous, four of them comparing a value with
itself.

This script injects one deliberate defect per case into a real canonical file,
runs `php bin/cert validate`, asserts the expected rule id appears in the
output, and restores every file byte-identically — verified with SHA-256, not
assumed. It exits non-zero if any rule stays silent on its own defect, or if
any file is not restored exactly.
"""
import hashlib
import pathlib
import subprocess
import sys

ROOT = pathlib.Path(__file__).resolve().parents[2]

# Lot 01 now ships at framework version 2, so it is already inside the gated
# set and the ERROR paths can be exercised against it directly. The cases that
# used to bump the log entry no longer need to.

LOT07_QUESTION = 'content/questions/lot-07-forms.yml'
LOT01_COURSE = 'content/courses/CRS-0jtjh77tabt1.md'   # OIT-webdvbgbrfth, STANDARD, 314 body words

# The Traits outcome "what a trait may contain, and why it is not a type" is
# named by one LEARNING question and by one HOLDOUT question. Removing the
# LEARNING link leaves only the holdout one, which must not discharge it.
# QST-4rr5p2mvc7g5 is the LEARNING half; QST-95yb2ee8eb52 the HOLDOUT one.
LOT01_QUESTION = 'content/questions/lot-01-php.yml'

# These anchors carry the fields QST-a4xhs81g86kj really has, because the
# injection must REPLACE them. Inserting a second `question_archetype:` or
# `assesses_outcomes:` after the id produces a duplicate YAML key, and the
# parser keeps the LAST one — so the defect is overwritten by the real value
# and the rule stays silent on a question that is in fact well formed. That is
# exactly what happened when lot 07 was annotated under framework version 2:
# three cases went quiet and were reported as VACUOUS rules, which was the
# prover telling the truth about itself rather than about ARC-001 or PED-003.
#
# Keeping the real values in the anchor also makes the next such change fail
# loudly: the anchor then matches zero times and the run stops with
# "anchor matched 0 times", instead of injecting a no-op.
LOT07_ANCHOR = '- id: QST-a4xhs81g86kj\n  question_archetype: DEFINITION_RECALL\n'
LOT07_OUTCOME_LINK = (
    '- id: QST-a4xhs81g86kj\n  question_archetype: DEFINITION_RECALL\n'
    '  assesses_outcomes:\n  - OUT-xysy1a4vx444\n'
)
# The bare outcome id would match twice: two questions of that item name it.

CASES = [
    (
        'ARC-001 rejects an archetype that contradicts the question written',
        [(
            LOT07_QUESTION,
            LOT07_ANCHOR,
            '- id: QST-a4xhs81g86kj\n  question_archetype: VERSION_ATTRIBUTION\n',
        )],
        '[ERROR] ARC-001',
    ),
    (
        'ARC-001 requires the field once a lot claims refinement',
        [(
            LOT01_QUESTION,
            '  question_archetype: VERSION_ATTRIBUTION\n  assesses_outcomes:\n    - OUT-svjaxs75amyd\n',
            '  assesses_outcomes:\n    - OUT-svjaxs75amyd\n',
        )],
        '[ERROR] ARC-001',
    ),
    (
        'PED-003 requires identified outcomes once a lot claims refinement',
        [(
            'docs/syllabus/syllabus-matrix.yml',
            '      - id: OUT-svjaxs75amyd\n        outcome: ',
            '      - ',
        )],
        '[ERROR] PED-003',
    ),
    (
        'PED-003 rejects a question claiming an outcome its item does not declare',
        [(
            LOT07_QUESTION,
            LOT07_OUTCOME_LINK,
            '- id: QST-a4xhs81g86kj\n  question_archetype: DEFINITION_RECALL\n'
            '  assesses_outcomes:\n  - OUT-abcdefghjkmn\n',
        )],
        '[ERROR] PED-003',
    ),
    (
        'ARC-001 rejects a BEHAVIOR_* archetype on a question that ships a listing',
        [(
            LOT07_QUESTION,
            LOT07_ANCHOR,
            '- id: QST-a4xhs81g86kj\n  question_archetype: BEHAVIOR_PREDICTION\n'
            '  code_language: php\n',
        )],
        '[ERROR] ARC-001',
    ),
    (
        'PED-003 refuses a HOLDOUT question as the assessment of an outcome',
        [(
            LOT01_QUESTION,
            '- id: QST-4rr5p2mvc7g5\n  question_archetype: BEHAVIOR_DIAGNOSIS\n'
            '  assesses_outcomes:\n    - OUT-x8c9nce1wqh5\n',
            '- id: QST-4rr5p2mvc7g5\n  question_archetype: BEHAVIOR_DIAGNOSIS\n'
            '  assesses_outcomes: []\n',
        )],
        '[ERROR] PED-003',
    ),
    (
        'REV-001 rejects a course grown past the budget for its level',
        [(LOT01_COURSE, None, '\n' + ('mot ' * 700).strip() + '\n')],
        '[ERROR] REV-001',
    ),
]


def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def validate():
    r = subprocess.run(
        ['php', 'bin/cert', 'validate'],
        capture_output=True, text=True, cwd=ROOT,
    )
    return r.stdout + r.stderr


def main():
    baseline = validate()
    failures = []

    for label, edits, expected in CASES:
        # The comparison is against the exact severity as well as the rule id:
        # PED-003 and REV-001 both report WARNINGs on the clean corpus, and a
        # proof that matched the bare rule id would pass on the baseline.
        if expected in baseline:
            failures.append(f'{expected}: already present in the clean baseline; the proof would be vacuous')
            continue

        touched = []
        try:
            for relpath, old, new in edits:
                path = ROOT / relpath
                before = path.read_bytes()
                touched.append((path, before, digest(path)))

                text = before.decode('utf-8')
                if old is None:            # append instead of substitute
                    text += new
                else:
                    if text.count(old) != 1:
                        raise RuntimeError(
                            f'anchor for {relpath} matched {text.count(old)} times, expected 1'
                        )
                    text = text.replace(old, new)

                path.write_text(text, encoding='utf-8')

            output = validate()
            if expected not in output:
                failures.append(f'{expected}: stayed silent on "{label}" — the rule is VACUOUS, not passing')
            else:
                print(f'OK   {expected}  {label}')
        except RuntimeError as e:
            failures.append(f'{expected}: {e}')
        finally:
            for path, before, before_digest in touched:
                path.write_bytes(before)
                if digest(path) != before_digest:
                    failures.append(f'{expected}: {path} was NOT restored byte-identically')

    after = validate()
    if after != baseline:
        failures.append('the validator output changed after restoration; the corpus is not as it was found')

    if failures:
        print('\nPROOF FAILED')
        for f in failures:
            print('  - ' + f)
        return 1

    print(f'\nPROOF OK — {len(CASES)} cases, every rule fired on its own defect, every file restored byte-identically')
    return 0


sys.exit(main())
