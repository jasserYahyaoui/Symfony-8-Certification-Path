"""Proof that AUD-02's and AUD-03's checks fire.

An audit that has only ever printed FINDINGS: 0 proves nothing — it may be
inspecting the wrong field, or nothing at all. This script injects one
deliberate defect per check into a real canonical file, runs the audit,
asserts the audit reports that specific check, and restores the file
byte-identically. It fails loudly if any restoration is not byte-identical.
"""
import hashlib
import pathlib
import subprocess
import sys

ROOT = pathlib.Path(__file__).resolve().parents[2]
AUD02 = ROOT / 'tools/audit/aud02_version_contamination.py'
AUD03 = ROOT / 'tools/audit/aud03_source_anchor.py'


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
]

failures = []
for script, args, relpath, old, new, expected in CASES:
    p = ROOT / relpath
    before_digest, before_bytes = digest(p), p.read_bytes()
    text = p.read_text(encoding='utf-8')
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
