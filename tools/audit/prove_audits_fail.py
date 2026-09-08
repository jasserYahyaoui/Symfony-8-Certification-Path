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

CASES = [
    (s, a, f, o,
     ('\n\n' + _LONG_LINE + '\n') if e == 'VOL-2'
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
