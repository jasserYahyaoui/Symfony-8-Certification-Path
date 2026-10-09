"""Proof that the refinement-framework rules of ADR-0007 actually fire.

ARC-001, PED-003 and REV-001 were added to a corpus that satisfies all three,
and DIA-001 (ADR-0009) to one whose two diagrams satisfy it,
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

# CRS-003 needs a course whose BODY carries a rendered link to turn back into a
# raw one; the course above has citations but no body link at all.
LINKED_COURSE = 'content/courses/CRS-0a0d5bp6769e.md'

# The Traits outcome "what a trait may contain, and why it is not a type" is
# named by one LEARNING question and by one HOLDOUT question. Removing the
# LEARNING link leaves only the holdout one, which must not discharge it.
# QST-4rr5p2mvc7g5 is the LEARNING half; QST-95yb2ee8eb52 the HOLDOUT one.
LOT01_QUESTION = 'content/questions/lot-01-php.yml'

# The first course to carry Mermaid diagrams (ADR-0009): lot 03, Request
# handling, DEEP, 1199 body words of 1200 once its two diagrams are excluded.
# That margin of one word is what makes the REV-001 case below a real test of
# the exemption: prose added next to a diagram must still be counted.
DIAGRAM_COURSE = 'content/courses/CRS-mpwjc4g3vmj7.md'

# The pilot comprehension bank (ADR-0010): lot 12, Console. Its first
# question is the only one assessing OUT-b3610kc2cj3b, and the only one whose
# subtopic is `component`, which makes both unique anchors.
COMPREHENSION_BANK = 'content/comprehension/lot-12-console.yml'
COMPREHENSION_FIRST = '- id: QST-nkndry7djpj0\n  question_archetype: SCENARIO_CHOICE\n'

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
        'ARC-001 requires the archetype on every question',
        [(
            LOT01_QUESTION,
            '  question_archetype: VERSION_ATTRIBUTION\n  assesses_outcomes:\n    - OUT-svjaxs75amyd\n',
            '  assesses_outcomes:\n    - OUT-svjaxs75amyd\n',
        )],
        '[ERROR] ARC-001',
    ),
    (
        # Was a PED-003 case until 2026-09-15. ADR-0007's exit act removed the
        # bare-string form from MatrixLoader, so an unidentified outcome is now
        # refused at PARSE time and the run never reaches the rules. The defect
        # is the same; the check that catches it moved earlier, and this case
        # follows it rather than being deleted for going quiet.
        'An unidentified outcome is refused before the rules run',
        [(
            'docs/syllabus/syllabus-matrix.yml',
            '      - id: OUT-svjaxs75amyd\n        outcome: ',
            '      - ',
        )],
        'The bare-string form was removed',
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
    # CRS-002 guards the OTHER copy of the content level. The matrix holds the
    # authority; each course front matter repeats it for whoever opens the file,
    # and until 2026-09-16 nothing read that repeat. Two courses had drifted
    # from their item and every gate was green. The defect is not a malformed
    # value -- it is a VALID level that belongs to another item's judgement.
    (
        'CRS-003 rejects a course linking a learner to a raw file',
        [(LINKED_COURSE,
          '](https://github.com/symfony/symfony-docs/blob/8.0/',
          '](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/')],
        '[ERROR] CRS-003',
    ),
    (
        'CRS-002 rejects a course whose content_level left its item behind',
        [(LOT01_COURSE, 'content_level: STANDARD\n', 'content_level: MINIMAL\n')],
        '[ERROR] CRS-002',
    ),
    # SRC-002 guards a duplication: `url` is the raw file this project fetches
    # to verify a claim, `readable_url` the rendered page a learner opens. The
    # defect that matters is not a malformed URL, it is a PLAUSIBLE one — the
    # right repository and file at the WRONG ref, which reads correctly to a
    # human and sends the learner to a different version of the same page.
    (
        'SRC-002 rejects a readable_url pointing at another ref than its url',
        [(
            LOT01_COURSE,
            'readable_url: "https://github.com/php/doc-en/blob/master/language/oop5/abstract.xml"',
            'readable_url: "https://github.com/php/doc-en/blob/PHP-8.3/language/oop5/abstract.xml"',
        )],
        '[ERROR] SRC-002',
    ),
    # The other realistic mistake: pasting the raw URL into both fields. The
    # citation then verifies fine and the learner is sent to unrendered bytes,
    # which is precisely the thing readable_url exists to stop.
    (
        'SRC-002 rejects a readable_url left on the raw host',
        [(
            LOT01_COURSE,
            'readable_url: "https://github.com/php/doc-en/blob/master/language/oop5/abstract.xml"',
            'readable_url: "https://raw.githubusercontent.com/php/doc-en/master/language/oop5/abstract.xml"',
        )],
        '[ERROR] SRC-002',
    ),
    # DIA-001 (ADR-0009). Mermaid blocks are outside the revision budget, so
    # the rule that makes each one a real, accessible diagram is the only thing
    # standing between that exemption and prose hidden inside a fence.
    (
        'DIA-001 rejects a diagram without an accessible title',
        [(DIAGRAM_COURSE, "  accTitle: Le trajet d'une requête principale sans exception\n", '')],
        '[ERROR] DIA-001',
    ),
    (
        'DIA-001 rejects a diagram without an accessible description',
        [(DIAGRAM_COURSE, '  accDescr: Une Error que handle_all_throwables', '  %% Une Error que handle_all_throwables')],
        '[ERROR] DIA-001',
    ),
    (
        'DIA-001 rejects prose written where the diagram type belongs',
        [(DIAGRAM_COURSE,
          "flowchart TD\n  accTitle: Le trajet d'une",
          "Ce paragraphe se cache dans un schema pour echapper au budget.\n  accTitle: Le trajet d'une")],
        '[ERROR] DIA-001',
    ),
    (
        'REV-001 still counts the prose written beside a diagram',
        [(DIAGRAM_COURSE,
          "## Trois façons d'échouer, trois exceptions\n",
          ('mot ' * 10).strip() + "\n\n## Trois façons d'échouer, trois exceptions\n")],
        '[ERROR] REV-001',
    ),
    # ADR-0010. Comprehension checks: complete, independent, and held to the
    # bar of the exam banks. Each case breaks the real pilot bank.
    (
        'CMP-001 rejects a lot whose comprehension check leaves an outcome untested',
        [(COMPREHENSION_BANK,
          '  assesses_outcomes:\n  - OUT-b3610kc2cj3b\n',
          '  assesses_outcomes:\n  - OUT-a1qc1gqac172\n')],
        '[ERROR] CMP-001',
    ),
    (
        'CMP-002 rejects a comprehension prompt copied from an exam bank',
        [(COMPREHENSION_BANK,
          "  question: 'An application has three commands: debug:config, debug:container and debug:router. Which\n    input runs debug:router?'\n",
          '  question: Which command lists everything the application can run?\n')],
        '[ERROR] CMP-002',
    ),
    (
        'CMP-003 rejects a comprehension question that is not in English',
        [(COMPREHENSION_BANK, '  subtopic: component\n  language: en\n', '  subtopic: component\n  language: fr\n')],
        '[ERROR] CMP-003',
    ),
    (
        'CMP-003 rejects a synthesis question reaching into another lot',
        [(COMPREHENSION_BANK, '  related_items:\n  - OIT-dv5400dtksfg\n', '  related_items:\n  - OIT-c6wd3f444qjn\n')],
        '[ERROR] CMP-003',
    ),
    (
        'CMP-003 runs the exam-bank rules on the comprehension bank (ARC-001 here)',
        [(COMPREHENSION_BANK, COMPREHENSION_FIRST, COMPREHENSION_FIRST.replace('SCENARIO_CHOICE', 'CODE_DIAGNOSIS'))],
        '[ERROR] ARC-001: comprehension bank:',
    ),
    (
        'the loader refuses an exam-pool question inside a comprehension bank',
        [(COMPREHENSION_BANK,
          "  pool: COMPREHENSION\n  verification_status: VERIFIED\n  reviewers:\n  - tech-lead\n  reviewed_at: '2026-10-09'\n  tags:\n  - component\n",
          "  pool: LEARNING\n  verification_status: VERIFIED\n  reviewers:\n  - tech-lead\n  reviewed_at: '2026-10-09'\n  tags:\n  - component\n")],
        'COMPREHENSION questions only, found LEARNING',
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
