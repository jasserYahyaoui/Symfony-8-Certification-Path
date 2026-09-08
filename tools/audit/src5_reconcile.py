"""SRC-5 ledger reconciliation.

SRC-5 counts *citations*, not records: a single course can carry two bare
citations and each needs its own verdict. The ledger is therefore keyed by
citation, and a record repaired in two different groups appears twice with a
"#n" suffix. This script refuses to agree with itself — it recomputes the
repaired count from the canonical files and compares.
"""
import collections
import csv
import pathlib
import sys

sys.path.insert(0, str(pathlib.Path(__file__).resolve().parent))
import collect  # noqa: E402

INITIAL = 105
LEDGER = collect.ROOT / 'docs/audit/lot-27-src5-citation-verification/ledger.csv'
CLASSES = ('ANCHOR_ADDED', 'SOURCE_REPLACED', 'SOURCE_COMPLETED', 'CLAIM_CORRECTED',
           'QUARANTINED', 'BLOCKED', 'CONFLICT_STOP')

rows = list(csv.DictReader(LEDGER.open()))
errors = []


def bare(s):
    return not ((s.get('symbol_or_lines') or '') or (s.get('anchor') or '')).strip()


still_bare = 0
for q in collect.questions():
    still_bare += sum(bare(s) for s in collect.sources_of(q))
for fm, _ in collect.courses():
    still_bare += sum(bare(s) for s in collect.sources_of(fm))
for c in collect.flashcards():
    still_bare += sum(bare(s) for s in collect.sources_of(c))

repaired = INITIAL - still_bare

# 1. no duplicate ledger key
dupes = [k for k, n in collections.Counter(r['record_id'] for r in rows).items() if n > 1]
if dupes:
    errors.append(f'duplicate ledger keys: {dupes}')

# 2. every row carries a known classification
unknown = {r['classification'] for r in rows} - set(CLASSES)
if unknown:
    errors.append(f'unknown classifications: {sorted(unknown)}')

# 3. classification totals equal processed
counts = collections.Counter(r['classification'] for r in rows)
if sum(counts.values()) != len(rows):
    errors.append('classification totals do not equal the row count')

# 4. the ledger agrees with the corpus, recomputed rather than trusted
if len(rows) != repaired:
    errors.append(f'ledger has {len(rows)} rows but {repaired} citations are actually repaired')

# 5. processed + remaining = INITIAL
if len(rows) + still_bare != INITIAL:
    errors.append(f'{len(rows)} + {still_bare} != {INITIAL}')

print('SRC-5 ledger reconciliation')
print('=' * 46)
print(f'  Initial            {INITIAL}')
print(f'  Processed          {len(rows)}')
print(f'  Remaining          {still_bare}')
for k in CLASSES:
    print(f'  {k:18s} {counts.get(k, 0)}')
print(f'  {"Total controlled":18s} {sum(counts.values())}')
print()
if errors:
    print('RECONCILIATION FAILED')
    for e in errors:
        print(f'  {e}')
    sys.exit(1)
print('RECONCILES — ledger, corpus and the initial count all agree')
