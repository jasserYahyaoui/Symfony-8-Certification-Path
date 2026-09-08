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

# Bumping the log entry to the current framework version puts lot-01 inside the
# gated set. It is the honest way to exercise the ERROR paths: the rules are
# staged to bite where refinement is claimed, so a proof that avoided claiming
# refinement would only be proving the staging.
CLAIM_LOT01_IS_V2 = (
    'docs/progress/refinement-log.yml',
    '    framework_version: 1\n',
    '    framework_version: 2\n',
)

LOT07_QUESTION = 'content/questions/lot-07-forms.yml'
LOT01_COURSE = 'content/courses/CRS-0jtjh77tabt1.md'   # OIT-webdvbgbrfth, STANDARD, 314 body words

CASES = [
    (
        'ARC-001 rejects an archetype that contradicts the question written',
        [(
            LOT07_QUESTION,
            '- id: QST-a4xhs81g86kj\n',
            '- id: QST-a4xhs81g86kj\n  question_archetype: VERSION_ATTRIBUTION\n',
        )],
        '[ERROR] ARC-001',
    ),
    (
        'ARC-001 requires the field once a lot claims refinement',
        [CLAIM_LOT01_IS_V2],
        '[ERROR] ARC-001',
    ),
    (
        'PED-003 requires identified outcomes once a lot claims refinement',
        [CLAIM_LOT01_IS_V2],
        '[ERROR] PED-003',
    ),
    (
        'PED-003 rejects a question claiming an outcome its item does not declare',
        [(
            LOT07_QUESTION,
            '- id: QST-a4xhs81g86kj\n',
            '- id: QST-a4xhs81g86kj\n  assesses_outcomes:\n    - OUT-abcdefghjkmn\n',
        )],
        '[ERROR] PED-003',
    ),
    (
        'REV-001 rejects a course grown past the budget for its level',
        [
            CLAIM_LOT01_IS_V2,
            (LOT01_COURSE, None, '\n' + ('mot ' * 700).strip() + '\n'),
        ],
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
