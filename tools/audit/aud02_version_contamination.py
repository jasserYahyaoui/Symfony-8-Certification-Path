"""AUD-02 — Version-contamination audit (Master Plan §14; §22 clause 5).

Asks one question: does anything in the corpus anchor to a Symfony version
other than 8.0, to Twig other than the examinable 3.22, or to a moving
reference that cannot be pinned?

Checks are declarative and every finding names the file and the offending
string. Exit 1 on any finding.
"""
import re
import sys

sys.path.insert(0, __file__.rsplit('/', 1)[0])
import collect  # noqa: E402

findings = []
counts = {}


def note(check, detail):
    findings.append((check, detail))


def bump(k, n=1):
    counts[k] = counts.get(k, 0) + n


# --- the authorities this project is allowed to anchor to, from source-map.yml
smap = collect.source_map()
allowed_branch = {}
RAW = re.compile(r'raw\.githubusercontent\.com/([^/]+/[^/]+)/([^/]+)/')

for group in ('technical_authority', 'scope_authority'):
    for entry in smap.get(group) or []:
        # An authority may be declared either as repository + branch, or as a
        # bare url — SRC-RFC9110 is the second kind. Reading only the first
        # kind reported six legitimate RFC citations as unknown repositories.
        if entry.get('repository'):
            allowed_branch[entry['repository']] = entry.get('branch')
            continue
        m = RAW.search(entry.get('url') or '')
        if m:
            allowed_branch[m.group(1)] = m.group(2)

# every record that can carry official_sources
records = []
for q in collect.questions():
    records.append(('question', q.get('id'), q['_file'], collect.sources_of(q)))
for fm, _body in collect.courses():
    records.append(('course', fm.get('id'), fm['_file'], collect.sources_of(fm)))
for c in collect.flashcards():
    records.append(('flashcard', c.get('id'), c['_file'], collect.sources_of(c)))

for kind, rid, fname, srcs in records:
    for s in srcs:
        url = s.get('url', '')
        bump('sources')
        m = RAW.search(url)
        if not m:
            bump('non-raw sources')
            continue
        repo, ref = m.group(1), m.group(2)
        bump(f'ref {repo}@{ref}')
        expected = allowed_branch.get(repo)
        if expected is None:
            note('CONTAM-1 unknown repository',
                 f'{kind} {rid} ({fname}): {repo} is not in source-map.yml')
        elif ref != expected:
            note('CONTAM-2 wrong ref',
                 f'{kind} {rid} ({fname}): {repo}@{ref}, source-map says {expected}')
        declared = s.get('branch')
        if declared is not None and declared != ref:
            note('CONTAM-3 declared branch disagrees with URL',
                 f'{kind} {rid} ({fname}): branch: {declared} but URL carries {ref}')

# --- /current/ and other moving references, over every canonical file
MOVING = [
    ('CONTAM-4 /doc/current', re.compile(r'symfony\.com/doc/current')),
    ('CONTAM-5 doc/master', re.compile(r'symfony-docs/master/')),
    ('CONTAM-6 symfony@main', re.compile(r'/symfony/symfony/main/')),
]
paths = list((collect.ROOT / 'content').rglob('*.yml')) \
    + list((collect.ROOT / 'content').rglob('*.md')) \
    + list((collect.ROOT / 'docs/syllabus').glob('*.yml')) \
    + list((collect.ROOT / 'docs/syllabus').glob('*.md'))
for p in paths:
    text = p.read_text(encoding='utf-8')
    for name, rx in MOVING:
        for m in rx.finditer(text):
            note(name, f'{p.relative_to(collect.ROOT)}: {m.group(0)}')

# --- prose naming a Symfony version that is not 8.0
# A version number is only contamination when the text presents it as the
# version being taught. Historical statements ("removed in 7.0", "since 6.4")
# are legitimate and common in deprecation material, so the pattern is
# deliberately narrow: it fires on a claim about *this* project's target.
# `Symfony 8` and `Symfony 8.0` both name the target version; only a
# different major or minor is a candidate for contamination.
TARGET = re.compile(r'\bSymfony\s+(?!8\b)(\d+\.\d+|\d+)\b(?!\s*(?:à|to|-|–)\s*8)')
CONTEXTUAL = re.compile(
    r'(depuis|since|jusqu|until|avant|before|removed|supprim|déprécié|deprecat'
    r'|introduit|introduced|added|ajouté|migration|upgrade|mise à niveau'
    r'|antérieur|earlier|older|ancienne|previous|LTS|calendrier|calendar)',
    re.I)
for fm, body in collect.courses():
    for m in TARGET.finditer(body):
        line_start = body.rfind('\n', 0, m.start()) + 1
        line_end = body.find('\n', m.end())
        line = body[line_start:line_end if line_end != -1 else len(body)]
        bump('version mentions in prose')
        if not CONTEXTUAL.search(line):
            note('CONTAM-7 prose names a non-8.0 Symfony without historical context',
                 f'{fm["_file"]}: {line.strip()[:160]}')

def report(title):
    print(title)
    print('=' * 60)
    for k in sorted(counts):
        print(f'  {k:44s} {counts[k]}')
    print()
    if not findings:
        print('FINDINGS: 0')
        return 0
    # Grouped by check, so a noisy check can never truncate a quiet one out of
    # the report. A dead source hidden behind 103 missing anchors is exactly
    # the failure this format exists to prevent.
    by_check = {}
    for check, detail in findings:
        by_check.setdefault(check, []).append(detail)
    print(f'FINDINGS: {len(findings)} across {len(by_check)} checks')
    for check in sorted(by_check):
        rows = by_check[check]
        print(f'\n  [{check}] {len(rows)}')
        for detail in rows[:12]:
            print(f'      {detail}')
        if len(rows) > 12:
            print(f'      … and {len(rows) - 12} more of this check')
    return 1


sys.exit(report('AUD-02 — version-contamination audit'))
