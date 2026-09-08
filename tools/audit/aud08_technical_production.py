"""AUD-08 — technical, accessibility and production audit (§14, §13, §17; §22 clause 9).

Clause 9 asks for "§17's gates, each green". Running those gates again would
only reproduce what `composer gate-full` and CI already print. This audit asks
the question a green gate cannot answer: **does each gate actually cover the
thing it is supposed to be a gate on?**

A gate that runs cleanly over half the site is green and useless, and nothing
in the build says so. So the subject here is coverage of the gates themselves:
every route the site publishes against the smoke test, every published page
against the accessibility audit, every declared rule against the rules actually
executed, and every local gate against CI.
"""
import json
import os
import re
import subprocess
import sys

sys.path.insert(0, __file__.rsplit('/', 1)[0])
import collect  # noqa: E402

ROOT = collect.ROOT
findings = []
counts = {}


def note(check, detail):
    findings.append((check, detail))


def bump(k, n=1):
    counts[k] = counts.get(k, 0) + n


def read(rel):
    with open(ROOT / rel, encoding='utf-8') as fh:
        return fh.read()


PAGES_WF = read('.github/workflows/pages.yml')
A11Y = read('website/tools/a11y-audit.mjs')

# --- what the smoke test actually fetches ----------------------------------
# The loop is a shell `for path in "" "docs" …` list; read it rather than
# restating it, so the audit tracks the workflow instead of a copy of it.
loop = re.search(r'for path in ((?:.|\n)*?); do', PAGES_WF)
if not loop:
    note('TECH-0 the production smoke test\'s URL list could not be parsed',
         'the `for path in …` loop shape in .github/workflows/pages.yml changed')
    smoke = set()
else:
    smoke = set(re.findall(r'"([^"]*)"', loop.group(1)))
bump('smoke URLs', len(smoke))

# --- what the accessibility audit actually visits --------------------------
block = re.search(r'const PAGES = \[((?:.|\n)*?)\n\];', A11Y)
audited = set(re.findall(r"\['[^']*',\s*'([^']+)'\]", block.group(1) if block else ''))
bump('accessibility pages', len(audited))


def as_path(route):
    return route.lstrip('/')


# --- TECH-1 every application route is smoke-tested ------------------------
routes = set()
for f in sorted(os.listdir(ROOT / 'website/src/pages')):
    if f.endswith('.tsx'):
        stem = f[:-4]
        routes.add('' if stem == 'index' else stem)
bump('application routes', len(routes))
for r in sorted(routes):
    if r not in smoke:
        note('TECH-1 an application route is published but never smoke-tested',
             f'/{r} has no entry in the pages.yml URL list')

# --- TECH-2 every generated syllabus page is smoke-tested ------------------
# Every generated page OUTSIDE the course tree, not just docs/syllabus/. The
# first version scanned that one folder, so a page generated at the docs root
# was published, unaudited and unsmoked without this check noticing - which is
# the exact shape of defect TECH-4 exists to catch.
syl = set()
d = ROOT / 'website/docs'
if d.is_dir():
    for f in sorted(os.listdir(d)):
        if f.endswith('.md') and f != 'index.md':
            syl.add(f'docs/{f[:-3]}')
    sub = d / 'syllabus'
    if sub.is_dir():
        for f in sorted(os.listdir(sub)):
            if f.endswith('.md'):
                syl.add(f'docs/syllabus/{f[:-3]}')
bump('generated syllabus pages', len(syl))
if not syl:
    note('TECH-2 no generated syllabus page was found',
         'run `php bin/cert build` first — this audit reads the generated tree')
for r in sorted(syl):
    if r not in smoke:
        note('TECH-2 a generated syllabus page is published but never smoke-tested', f'/{r}')

# --- TECH-3 every generated payload is smoke-tested ------------------------
payloads = set()
d = ROOT / 'website/static/data'
if d.is_dir():
    for f in sorted(os.listdir(d)):
        if f.endswith('.json'):
            payloads.add(f'data/{f}')
bump('generated payloads', len(payloads))
if not payloads:
    note('TECH-3 no generated payload was found',
         'run `php bin/cert build` first — this audit reads the generated tree')
for r in sorted(payloads):
    if r not in smoke:
        note('TECH-3 a generated payload is published but never smoke-tested', f'/{r}')

# --- TECH-4 every published page is accessibility-audited ------------------
# §13 and §17 make accessibility a gate, and CLAUDE.md states MISSING is not an
# acceptable value for it. A page served to a learner and never audited is that
# same MISSING, hidden behind a green run over the other pages.
audited_paths = {as_path(a) for a in audited}
for r in sorted(routes | syl):
    if r not in audited_paths:
        note('TECH-4 a published page is served to learners but never accessibility-audited',
             f'/{r} is smoke-tested but absent from the a11y PAGES list')

# --- TECH-5 no accessibility justification cites an unaudited page ---------
# The audit's own comments excuse un-visited screens by saying they reuse the
# primitives of a page it does audit. That reasoning is only as good as the
# claim it rests on, and nothing checks the claim.
for m in re.finditer(r'audited ([a-z0-9 ]+?) page', A11Y):
    name = m.group(1).strip()
    if not any(name in a.lower() for a in audited) and \
       not any(name in lbl.lower() for lbl in re.findall(r"\['([^']+)'", block.group(1) if block else '')):
        note('TECH-5 an accessibility justification cites a page the audit does not cover',
             f'the comment appeals to "the audited {name} page", which is not in PAGES')

# --- TECH-6 no test is skipped or incomplete at runtime --------------------
# A skipped test is invisible in "OK (194 tests)". PHPUnit only names skips
# when asked, so ask.
proc = subprocess.run(
    ['vendor/bin/phpunit', '--display-skipped', '--display-incomplete'],
    cwd=ROOT, capture_output=True, text=True)
out = proc.stdout + proc.stderr
skipped = len(re.findall(r'^\s*\d+\)\s', out, re.M)) if 'skipped test' in out.lower() else 0
bump('phpunit skipped or incomplete', skipped)
if proc.returncode != 0:
    note('TECH-6 the test suite does not pass', f'phpunit exit {proc.returncode}')
for m in re.finditer(r'There (?:was|were) \d+ skipped test', out):
    note('TECH-6 a test is skipped, so its assertions never run',
         'run `vendor/bin/phpunit --display-skipped` to see which')

# --- TECH-7 every rule that exists is actually registered ------------------
# NOT the count validate prints. `bin/cert` reports
# `count($validator->rules())`, which counts the same array this file would
# parse — the two can never disagree, so comparing them proves nothing. The
# invariant worth checking is that a rule class cannot exist on disk and be
# silently absent from `RuleSet::mandatory()`, which is how an invariant stops
# being enforced while every gate stays green.
ruleset = read('src/Validation/RuleSet.php')
body = re.search(r'function mandatory\(\)[^{]*\{((?:.|\n)*?)\n    \}', ruleset)
registered = set(re.findall(r'new ([A-Za-z]+Rule)\(', body.group(1) if body else ''))
on_disk = {f[:-4] for f in os.listdir(ROOT / 'src/Validation/Rule') if f.endswith('.php')}
bump('rule classes on disk', len(on_disk))
bump('rules registered in RuleSet::mandatory()', len(registered))
v = subprocess.run(['php', 'bin/cert', 'validate'], cwd=ROOT, capture_output=True, text=True)
m = re.search(r'Rules executed:\s*(\d+)', v.stdout)
bump('rules executed by bin/cert validate', int(m.group(1)) if m else -1)
if not registered:
    note('TECH-7 no rule could be parsed from RuleSet::mandatory()',
         'the method shape changed and this check has stopped measuring anything')
for r in sorted(on_disk - registered):
    note('TECH-7 a rule class exists but is never registered, so it never runs',
         f'src/Validation/Rule/{r}.php is absent from RuleSet::mandatory()')
for r in sorted(registered - on_disk):
    note('TECH-7 a registered rule has no class file', r)

# --- TECH-8 the generated trees are untracked (ADR-0003) -------------------
tracked = subprocess.run(
    ['git', 'ls-files', 'website/docs', 'website/static/data'],
    cwd=ROOT, capture_output=True, text=True).stdout.split()
bump('generated files tracked in git', len(tracked))
for f in tracked[:10]:
    note('TECH-8 a generated file is tracked in git, against ADR-0003', f)

# --- TECH-9 every gate composer runs is also run by CI ---------------------
composer = json.loads(read('composer.json'))
gate_full = composer.get('scripts', {}).get('gate-full') or []
ci = read('.github/workflows/ci.yml')
GATE_CMD = {
    '@validate-content': 'bin/cert validate',
    '@coverage': 'bin/cert coverage',
    '@test': 'phpunit',
    '@build': 'bin/cert build',
    '@readiness': 'bin/cert readiness',
    'npm --prefix website run gate': 'run a11y',
}
bump('gates in composer gate-full', len(gate_full))
for step in gate_full:
    needle = GATE_CMD.get(step)
    if needle is None:
        note('TECH-9 a gate-full step is not recognised by this audit',
             f'{step} — the check cannot say whether CI runs it')
    elif needle not in ci:
        note('TECH-9 a gate runs locally but not in CI', f'{step} (looked for "{needle}")')

print('AUD-08 — technical, accessibility and production audit')
print('=' * 60)
for k in sorted(counts):
    print(f'  {k:44s} {counts[k]}')
print()
print('  Scope statement: this audit tests the COVERAGE of §17\'s gates,')
print('  never their verdicts. A green gate that visits half the site is')
print('  still green, and only this file asks whether it visited all of it.')
print()
if findings:
    by = {}
    for check, detail in findings:
        by.setdefault(check, []).append(detail)
    print(f'FINDINGS: {len(findings)} across {len(by)} checks')
    for check in sorted(by):
        rows = by[check]
        print(f'\n  [{check}] {len(rows)}')
        for d in rows[:12]:
            print(f'      {d}')
        if len(rows) > 12:
            print(f'      … and {len(rows) - 12} more of this check')
    sys.exit(1)
print('FINDINGS: 0')
