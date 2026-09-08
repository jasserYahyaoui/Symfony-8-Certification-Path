"""Proof that AUD-02's and AUD-03's checks fire.

An audit that has only ever printed FINDINGS: 0 proves nothing — it may be
inspecting the wrong field, or nothing at all. This script injects one
deliberate defect per check into a real canonical file, runs the audit,
asserts the audit reports that specific check, and restores the file
byte-identically. It fails loudly if any restoration is not byte-identical.
"""
import hashlib
import pathlib
import re
import subprocess
import sys

ROOT = pathlib.Path(__file__).resolve().parents[2]
AUD02 = ROOT / 'tools/audit/aud02_version_contamination.py'
AUD03 = ROOT / 'tools/audit/aud03_source_anchor.py'
AUD04 = ROOT / 'tools/audit/aud04_content_volume.py'
AUD06 = ROOT / 'tools/audit/aud06_holdout_integrity.py'
AUD05 = ROOT / 'tools/audit/aud05_question_bank.py'
AUD07 = ROOT / 'tools/audit/aud07_english_readiness.py'

# Some defects cannot be injected by swapping an existing substring: a
# duplicated prose line, a course grown past the outlier threshold, one
# document copied over another. APPEND marks a case whose `old` is ignored and
# whose `new` is appended to the file instead.
APPEND = '\x00APPEND\x00'


def digest(p):
    return hashlib.sha256(p.read_bytes()).hexdigest()


def run(script, *args):
    r = subprocess.run([sys.executable, str(script), *args],
                       capture_output=True, text=True, cwd=ROOT)
    return r.returncode, r.stdout + r.stderr


CASES = [
    # (audit, extra args, file, old substring, new substring, expected check id)
    (AUD02, (), 'content/courses/CRS-0a0d5bp6769e.md',
     'symfony-docs/8.0/service_container/service_subscribers_locators.rst',
     'symfony-docs/7.4/service_container/service_subscribers_locators.rst',
     'CONTAM-2'),
    (AUD02, (), 'content/courses/CRS-0a0d5bp6769e.md',
     'branch: "8.0"', 'branch: "7.4"',
     'CONTAM-3'),
    (AUD02, (), 'content/courses/CRS-0a0d5bp6769e.md',
     'https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container',
     'https://symfony.com/doc/current/service_container',
     'CONTAM-4'),
    (AUD03, ('--offline',), 'content/courses/CRS-0a0d5bp6769e.md',
     '    symbol_or_lines: "ServiceSubscriberInterface, getSubscribedServices, AutowireLocator"\n',
     '',
     'ANCHOR-4'),
    (AUD03, ('--offline',), 'content/courses/CRS-0a0d5bp6769e.md',
     '    verified_at: "2026-09-01"\n', '    verified_at: "2099-01-01"\n',
     'ANCHOR-7'),
    (AUD03, (), 'content/courses/CRS-0a0d5bp6769e.md',
     'service_subscribers_locators.rst', 'service_subscribers_locators_NOPE.rst',
     'ANCHOR-8'),
    # A URL pinned to a commit must record that commit as a field too,
    # otherwise the pin is only as reproducible as the reader's attention.
    (AUD02, (), 'content/questions/lot-01-php.yml',
     '    commit_sha: 0c2fc141fcf9edb13b57b34c3843ed75e24ddcf5\n', '',
     'CONTAM-8'),
    # A pinned commit still has to belong to the authorised branch.
    (AUD02, (), 'content/questions/lot-01-php.yml',
     '    branch: PHP-8.4\n    commit_sha:', '    branch: PHP-8.3\n    commit_sha:',
     'CONTAM-9'),
    # An unquoted branch is a float, and reads identically in the file.
    (AUD02, (), 'content/flashcards/lot-12-console.yml',
     "    branch: '8.0'", '    branch: 8.0',
     'CONTAM-10'),

    # AUD-04. A course grown past four times the corpus median.
    (AUD04, (), 'content/courses/CRS-0a0d5bp6769e.md',
     APPEND, '\n\n' + ('padding ' * 2000),
     'VOL-1'),
    # The same long prose line present in two courses.
    (AUD04, (), 'content/courses/CRS-0a0d5bp6769e.md',
     APPEND, None,  # filled in below with a real long line from another course
     'VOL-2'),
    # One course's whole body copied over another's.
    (AUD04, (), 'content/courses/CRS-2s6e4qgkcqza.md',
     APPEND, None,  # filled in below from a real sibling course
     'VOL-3'),
    # Two flashcards asking the same question.
    (AUD04, (), 'content/flashcards/lot-10-security.yml',
     APPEND, None,  # filled in below from this deck's own first card
     'VOL-4'),

    # AUD-06. A holdout question removed, so the bank no longer matches the
    # Mock 4 blueprint's count.
    (AUD06, (), 'docs/mocks/mock-4-blueprint.yml',
     '  questions: 75', '  questions: 74',
     'HOLD-1'),
    # Two holdout questions on one atomic item.
    (AUD06, (), 'content/questions/mock-04-holdout.yml',
     None, None,  # filled in below: retarget one question's official_item
     'HOLD-2'),
    # A holdout question that is not English.
    (AUD06, (), 'content/questions/mock-04-holdout.yml',
     '  language: en\n', '  language: fr\n',
     'HOLD-3'),
    # A holdout answer reproduced in a DIFFERENT item's course — the leak the
    # CRS-001 exemption must not hide.
    (AUD06, (), 'content/courses/CRS-0a0d5bp6769e.md',
     APPEND, None,  # filled in below with a real holdout answer
     'HOLD-5'),
    # A build-time guard deleted.
    (AUD06, (), 'src/Build/PayloadBuilder.php',
     'public static function assertNoHoldoutLeak',
     'public static function assertNoHoldoutLeakRENAMED',
     'HOLD-6'),

    # AUD-05. A declared answer count that no longer matches the keys.
    (AUD05, (), 'content/questions/lot-01-php.yml',
     '  required_answer_count: 1\n', '  required_answer_count: 2\n',
     'QB-1'),
    # answer_mode single carrying two keys.
    (AUD05, (), 'content/questions/lot-01-php.yml',
     None, None,  # filled in below: flip a distractor to correct
     'QB-2'),
    # A catch-all distractor.
    (AUD05, (), 'content/questions/lot-01-php.yml',
     None, None,  # filled in below: rename a distractor to "All of the above"
     'QB-5'),
    # An implausible estimated time.
    (AUD05, (), 'content/questions/lot-01-php.yml',
     '  estimated_time_seconds: 45\n', '  estimated_time_seconds: 4500\n',
     'QB-6'),
    # An official_item that resolves to nothing.
    (AUD05, (), 'content/questions/lot-01-php.yml',
     '  official_item: OIT-46ry8d7dypmb\n', '  official_item: OIT-doesnotexist\n',
     'QB-7'),
    # A question with only two choices.
    (AUD05, (), 'content/questions/lot-01-php.yml', APPEND, None, 'QB-3'),
    # A question whose two distractors carry the same text.
    (AUD05, (), 'content/questions/lot-01-php.yml', APPEND, None, 'QB-4'),
    # A question reusing a choice id that already exists in the bank.
    (AUD05, (), 'content/questions/lot-01-php.yml', APPEND, None, 'QB-8'),
    # An atomic item that carries no question at all.
    (AUD05, (), 'docs/syllabus/syllabus-matrix.yml', APPEND, None, 'QB-9'),

    # AUD-07. Enough advanced questions in French to break §5's 50% threshold.
    (AUD07, (), 'content/questions/validation-pool.yml', APPEND, None, 'ENG-1'),
    # A non-English question inside a bank §5 binds.
    (AUD07, (), 'content/questions/mock-04-holdout.yml',
     '  language: en\n', '  language: fr\n',
     'ENG-3'),
    # A stem and choices that cannot be read inside their own budget.
    (AUD07, (), 'content/questions/lot-01-php.yml', APPEND, None, 'ENG-5'),
    # The §5 glossary emptied.
    (AUD07, (), 'docs/syllabus/glossary.yml', None, None, 'ENG-6'),
    # Mock 3's bank pushed below "primarily English".
    (AUD07, (), 'content/questions/validation-pool.yml', APPEND, None, 'ENG-2'),
    # One French question in a bank §5 binds — a defect even while the ratio
    # above still passes, which is the whole point of separating ENG-4.
    (AUD07, (), 'content/questions/validation-pool.yml', APPEND, None, 'ENG-4'),
]

# VOL-2's payload is a real prose line lifted from another course, so the
# duplication it creates is genuine and the case needs no external seeding.
# An earlier version planted a synthetic line via a wrapper, and that seed
# landed in the file VOL-3 targets — the two cases then interfered and VOL-3
# appeared not to fire when it did.
_DONOR = (ROOT / 'content/courses/CRS-awb7sd999c5j.md').read_text(encoding='utf-8')


def _collectable_line(text):
    """A line the audit itself would collect: outside fences, long enough, prose.

    Picking one by eye is how the first attempt failed — the chosen line sat
    inside a fenced block, which the audit strips before it looks at anything,
    so the injected duplicate was invisible to the check it was meant to trip.
    """
    # Strip the YAML front matter first: the audit reads courses through
    # collect.courses(), which hands back the body alone. A line picked from
    # the front matter is invisible to the check and the case silently no-ops
    # — which is exactly what happened on the first two attempts.
    text = re.sub(r'\A---\n.*?\n---\n', '', text, flags=re.S)
    body = re.sub(r'```.*?```', ' ', text, flags=re.S)
    for line in body.split('\n'):
        s = line.strip()
        if len(s) >= 60 and not s.startswith(('#', '|', '-', '*', '>')):
            return s
    raise SystemExit('fail-proof: no collectable line in the donor course')


_LONG_LINE = _collectable_line(_DONOR)

# VOL-3's payload is a real course body, so the similarity is genuine rather
# than an artefact of repeated filler.
_TWIN = (ROOT / 'content/courses/CRS-awb7sd999c5j.md').read_text(encoding='utf-8')
_TWIN_BODY = _TWIN.split('---', 2)[2] if _TWIN.count('---') >= 2 else _TWIN

# VOL-4's payload duplicates the front of an existing card in the same deck.
import yaml as _yaml  # noqa: E402
_DECK = _yaml.safe_load((ROOT / 'content/flashcards/lot-10-security.yml').read_text(encoding='utf-8'))
_FIRST = _DECK['flashcards'][0]
_DUP_CARD = (
    '- id: FLC-provefail0001\n'
    '  official_item: ' + _FIRST['official_item'] + '\n'
    '  front: ' + _yaml.dump(_FIRST['front']).strip() + '\n'
    '  back: duplicate front, for the fail-proof only\n'
    '  explanation: duplicate front, for the fail-proof only\n'
    '  memorization_justification: duplicate front, for the fail-proof only\n'
    '  language: fr\n'
    '  verification_status: VERIFIED\n'
    '  official_sources:\n'
    '  - url: https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security.rst\n'
    "    symbol_or_lines: 'Roles'\n"
    "    branch: '8.0'\n"
    "    verified_at: '2026-09-08'\n"
)

# HOLD-2 needs two holdout questions to share an item, so one question's
# official_item is retargeted onto another's. HOLD-5 needs a real holdout
# answer, long enough to clear the audit's 25-character floor, planted in a
# course belonging to a different item.
_HOLD = _yaml.safe_load((ROOT / 'content/questions/mock-04-holdout.yml').read_text(encoding='utf-8'))
_HQ = _HOLD['questions']
_HOLD2_OLD = f"official_item: {_HQ[1]['official_item']}"
_HOLD2_NEW = f"official_item: {_HQ[0]['official_item']}"
# QB-2 needs a single-answer question to gain a second key, and QB-5 a
# distractor renamed to a catch-all. Both are expressed against the first
# question of the PHP bank, read from the file rather than assumed.
def _q(qid, item, choices, **over):
    """A minimally valid question, as AUD-05 reads them."""
    body = [
        f'- id: {qid}', '  version: 1', '  official_topic: PHP',
        f'  official_item: {item}', '  domain: php', '  language: en',
        '  difficulty: medium', '  cognitive_level: KNOW', '  exam_skill: RECOGNIZE',
        '  type: mcq',
        f"  answer_mode: {over.get('answer_mode', 'single')}",
        f"  required_answer_count: {over.get('required_answer_count', 1)}",
        f'  question: Fail-proof fixture {qid}?',
        '  scoring_policy: all-or-nothing', '  shuffle_choices: true',
        '  negative_wording: false',
        f"  estimated_time_seconds: {over.get('estimated_time_seconds', 45)}",
        '  classification: OFFICIAL', '  pool: LEARNING',
        '  verification_status: VERIFIED', '  reviewers:', '  - tech-lead',
        "  reviewed_at: '2026-09-08'", '  tags:', '  - fail-proof', '  choices:',
    ]
    for cid, text, correct in choices:
        body += [f'  - id: {cid}', f'    text: {text}', f'    correct: {str(correct).lower()}']
        if not correct:
            body.append('    explanation: fixture distractor')
    body += [
        '  explanation: fail-proof fixture', '  official_sources:',
        '  - url: https://raw.githubusercontent.com/php/doc-en/master/language/enumerations.xml',
        '    branch: master', "    symbol_or_lines: 'fixture'", "    verified_at: '2026-09-08'",
    ]
    return '\n' + '\n'.join(body) + '\n'



_PHP = _yaml.safe_load((ROOT / 'content/questions/lot-01-php.yml').read_text(encoding='utf-8'))
_Q0 = _PHP['questions'][0]
_DISTRACTOR = next(c for c in _Q0['choices'] if not c.get('correct'))
_QB2_OLD = f"    text: {_DISTRACTOR['text']}\n    correct: false"
_QB2_NEW = f"    text: {_DISTRACTOR['text']}\n    correct: true"
_QB5_OLD = f"text: {_DISTRACTOR['text']}"
_QB5_NEW = "text: All of the above"
_ITEM0 = _Q0['official_item']
_EXISTING_CHOICE_ID = _Q0['choices'][0]['id']

_QB3 = _q('QST-failproof0003', _ITEM0, [('CHO-failproof0031', 'only', True),
                                        ('CHO-failproof0032', 'two', False)])
_QB4 = _q('QST-failproof0004', _ITEM0, [('CHO-failproof0041', 'key', True),
                                        ('CHO-failproof0042', 'same text', False),
                                        ('CHO-failproof0043', 'same text', False)])
_QB8 = _q('QST-failproof0008', _ITEM0, [(_EXISTING_CHOICE_ID, 'reused id', True),
                                        ('CHO-failproof0082', 'b', False),
                                        ('CHO-failproof0083', 'c', False)])
# ENG-1 needs §5's advanced-question ratio pushed under 50%: 210 French `hard`
# questions against the corpus's 205 real ones, which is the smallest block
# that can move a 99.5% ratio below the line.
_ENG1 = ''.join(
    _q(f'QST-failproofE{i:04d}', _ITEM0,
       [(f'CHO-fpE{i:04d}a', 'a', True), (f'CHO-fpE{i:04d}b', 'b', False),
        (f'CHO-fpE{i:04d}c', 'c', False)])
    .replace('  language: en', '  language: fr')
    .replace('  difficulty: medium', '  difficulty: hard')
    for i in range(210)
)

# ENG-2: 140 French VALIDATION questions against the corpus's 135 English
# ones, which takes the pool below "primarily English".
_ENG2 = ''.join(
    _q(f'QST-failproofV{i:04d}', _ITEM0,
       [(f'CHO-fpV{i:04d}a', 'a', True), (f'CHO-fpV{i:04d}b', 'b', False),
        (f'CHO-fpV{i:04d}c', 'c', False)])
    .replace('  language: en', '  language: fr')
    .replace('  pool: LEARNING', '  pool: VALIDATION')
    for i in range(140)
)

# ENG-4: a single French VALIDATION question. The ratio stays far above 50%,
# so ENG-2 does not fire and ENG-4 is isolated — §5 permits French for
# beginner practice, never in a bank its thresholds bind.
_ENG4 = (_q('QST-failproofF0001', _ITEM0,
            [('CHO-fpF0001a', 'a', True), ('CHO-fpF0001b', 'b', False),
             ('CHO-fpF0001c', 'c', False)])
         .replace('  language: en', '  language: fr')
         .replace('  pool: LEARNING', '  pool: VALIDATION'))

# ENG-5: a stem far too long to read inside a 20-second budget.
_ENG5 = _q('QST-failproofE9999', _ITEM0,
           [('CHO-fpE9999a', 'a', True), ('CHO-fpE9999b', 'b', False),
            ('CHO-fpE9999c', 'c', False)],
           estimated_time_seconds=20).replace(
    'question: Fail-proof fixture QST-failproofE9999?',
    'question: ' + ('word ' * 400))

# ENG-6: the glossary emptied, by renaming the key its entries live under.
_ENG6_OLD = 'entries:'
_ENG6_NEW = 'entries: []\nunused_entries:'

_QB9 = (
    '\n  - id: OIT-failproof9999\n'
    '    official_topic_order: 99\n'
    '    official_topic: "PHP"\n'
    '    official_item_order: 99\n'
    '    official_item: "Fail-proof fixture item"\n'
    '    official_wording: "Fail-proof fixture item"\n'
    '    learning_domain: "php"\n'
    '    lot: "lot-01"\n'
    '    chapter: "fixture"\n'
    '    classification: "OFFICIAL"\n'
    '    content_level: "MINIMAL"\n'
    '    content_level_justification: "fixture"\n'
    '    learning_outcomes: []\n'
    '    required_assessment_modes: []\n'
    '    minimum_evidence: "fixture"\n'
    '    exclusion_boundaries: "None stated by the syllabus for this item."\n'
    '    version_constraints: "Symfony 8.0"\n'
    '    official_sources: []\n'
    '    course_refs: []\n'
    '    flashcard_refs: []\n'
    '    question_refs: []\n'
    '    exercise_refs: []\n'
    '    exam_refs: []\n'
    '    prerequisites: []\n'
    '    status: "NOT_STARTED"\n'
    '    verification_status: "UNVERIFIED"\n'
    '    exam_ready: false\n'
    '    last_verified_at: "2026-09-08"\n'
    '    reviewed_by: "fixture"\n'
    '    notes: "fail-proof fixture"\n'
)


_HOLD5_ANSWER = next(
    c['text'] for q in _HQ for c in q['choices']
    if c.get('correct') and len(' '.join(str(c['text']).split())) >= 25
)

CASES = [
    (s, a, f,
     (_HOLD2_OLD if e == 'HOLD-2'
      else _QB2_OLD if e == 'QB-2'
      else _QB5_OLD if e == 'QB-5'
      else _ENG6_OLD if e == 'ENG-6'
      else o),
     _HOLD2_NEW if e == 'HOLD-2'
     else _QB2_NEW if e == 'QB-2'
     else _QB5_NEW if e == 'QB-5'
     else _QB3 if e == 'QB-3'
     else _QB4 if e == 'QB-4'
     else _QB8 if e == 'QB-8'
     else _QB9 if e == 'QB-9'
     else _ENG1 if e == 'ENG-1'
     else _ENG2 if e == 'ENG-2'
     else _ENG4 if e == 'ENG-4'
     else _ENG5 if e == 'ENG-5'
     else _ENG6_NEW if e == 'ENG-6'
     else ('\n\nUne prose de contexte : ' + _HOLD5_ANSWER + '\n') if e == 'HOLD-5'
     else ('\n\n' + _LONG_LINE + '\n') if e == 'VOL-2'
     else _TWIN_BODY if e == 'VOL-3'
     else _DUP_CARD if e == 'VOL-4'
     else n, e)
    for s, a, f, o, n, e in CASES
]

failures = []
for script, args, relpath, old, new, expected in CASES:
    p = ROOT / relpath
    before_digest, before_bytes = digest(p), p.read_bytes()
    text = p.read_text(encoding='utf-8')
    if old == APPEND:
        p.write_text(text + new, encoding='utf-8')
    else:
        if text.count(old) < 1:
            failures.append(f'{expected}: fixture string not found in {relpath}')
            continue
        p.write_text(text.replace(old, new, 1), encoding='utf-8')
    try:
        code, out = run(script, *args)
        if code == 0:
            failures.append(f'{expected}: audit still exited 0 with the defect injected')
        elif expected not in out:
            failures.append(f'{expected}: audit failed but did not report {expected}')
        else:
            print(f'  proved  {expected:10s} fires on an injected defect')
    finally:
        p.write_bytes(before_bytes)
    if digest(p) != before_digest:
        failures.append(f'{expected}: {relpath} was NOT restored byte-identically')

print()
if failures:
    print('PROOF FAILED')
    for f in failures:
        print(f'  {f}')
    sys.exit(1)
print(f'PROOF OK — {len(CASES)} checks each proved to fire, every file restored byte-identically')
