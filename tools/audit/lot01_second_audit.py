"""Lot 01 refinement — second independent audit.

Independent of the refinement's own reasoning: it re-reads the corpus and asks
whether the claims the unit made are true of what is now in the repository.

It does not re-run the existing gates. validate, phpunit, the build and the
accessibility audit report their own results and are taken as given.
"""
import re, sys, collections, pathlib
import yaml

ROOT = pathlib.Path(__file__).resolve().parents[2]
sys.path.insert(0, str(pathlib.Path(__file__).resolve().parent))
import collect  # noqa: E402

findings, counts = [], {}
note = lambda c, d: findings.append((c, d))
def bump(k, n=1): counts[k] = counts.get(k, 0) + n

matrix = yaml.safe_load((ROOT / 'docs/syllabus/syllabus-matrix.yml').read_text(encoding='utf-8'))
lot1 = {i['id']: i for i in matrix['items'] if i.get('lot') == 'lot-01'}
qs = collect.questions()
by_item = collections.defaultdict(list)
for q in qs:
    if q.get('official_item') in lot1:
        by_item[q['official_item']].append(q)

bump('lot-01 items', len(lot1))
bump('questions attached', sum(len(v) for v in by_item.values()))

# --- L1-1 every item can be exercised in exam mode -------------------------
# POOL-002 requires this of STANDARD and DEEP items. The refinement's claim is
# stronger and is what is checked here: after it, EVERY lot-01 item has one.
for iid, it in lot1.items():
    if not any(q.get('pool') == 'VALIDATION' for q in by_item[iid]):
        note('L1-1 an item cannot be served in exam mode', f'{it["official_item"]} has no VALIDATION question')

# --- L1-2 no item rests on recall alone ------------------------------------
# The false-mastery shape: every question KNOW/RECOGNIZE and easy. An item like
# that reports EXAM_READY while nothing has tested application.
for iid, it in lot1.items():
    ql = by_item[iid]
    if ql and all(q.get('cognitive_level') == 'KNOW' for q in ql) \
           and all(q.get('exam_skill') == 'RECOGNIZE' for q in ql):
        note('L1-2 an item is assessed by recall only', it['official_item'])

# --- L1-3 the four traps are verified now ----------------------------------
# The pattern is matched against the CORRECT choices only, never the whole
# question. An earlier version searched the stem and every distractor too, so
# breaking the answer key left the trap still "verified" - the fail-proof caught
# that and it is the reason this check reads only what a right answer says.
TRAPS = {
    'property hooks are not cached': ('OIT-46ry8d7dypmb', r'three times'),
    'private blocks override':       ('OIT-ywrx9x3hg9z8', r'not overridden'),
    'abstract with final':           ('OIT-webdvbgbrfth', r'abstract final'),
    'catch order decides':           ('OIT-bvnvx2b6yt2y', r'first matching catch'),
    'interface constant override':   ('OIT-vt0p9cacpkpd', r'overrides the interface constant'),
    'interface property vs readonly': ('OIT-vt0p9cacpkpd', r'readonly'),
}
for label, (iid, pat) in TRAPS.items():
    keys = ' '.join(c.get('text', '') for q in by_item.get(iid, [])
                    for c in (q.get('choices') or []) if c.get('correct'))
    if not re.search(pat, keys, re.I):
        note('L1-3 a trap the refinement claims to verify is not tested by an answer key', label)
    else:
        bump('traps verified by an answer key')

# --- L1-4 the training mocks stay drawable ---------------------------------
# Mocks 1/2/3 take at most one eligible question per atomic item. A second
# VALIDATION question on one item would break that silently.
per_item = collections.Counter(
    q['official_item'] for q in qs if q.get('pool') == 'VALIDATION' and q.get('official_item'))
for iid, n in per_item.items():
    if n > 1:
        note('L1-4 an item carries two VALIDATION questions', f'{iid}: {n}')

# --- L1-5 question counts stay inside the bank rule ------------------------
for iid, it in lot1.items():
    n = len(by_item[iid])
    if not 2 <= n <= 5:
        note('L1-5 an item is outside the 2..5 question rule', f'{it["official_item"]}: {n}')

# --- L1-6 nothing was added without a version-anchored source --------------
for iid in lot1:
    for q in by_item[iid]:
        srcs = collect.sources_of(q)
        if not srcs:
            note('L1-6 a question carries no source', q['id'])
        for s in srcs:
            if '/current/' in str(s.get('url', '')):
                note('L1-6 a source is not version-anchored', f'{q["id"]}: {s.get("url")}')
            if not (s.get('symbol_or_lines') or s.get('anchor')):
                note('L1-6 a source carries no anchor', q['id'])

# --- L1-7 the official wording is untouched --------------------------------
for iid, it in lot1.items():
    if it['official_item'] != it['official_wording']:
        note('L1-7 official_item and official_wording diverge', iid)

lv = collections.Counter(i['content_level'] for i in lot1.values())
for k, v in lv.items():
    bump(f'level {k}', v)
bump('items with exam-mode evidence',
     sum(1 for iid in lot1 if any(q.get('pool') == 'VALIDATION' for q in by_item[iid])))

print('Lot 01 refinement — second independent audit')
print('=' * 60)
for k in sorted(counts):
    print(f'  {k:44s} {counts[k]}')
print()
print('  Level distribution is an observation, never a target.')
print()
if findings:
    by = {}
    for c, d in findings:
        by.setdefault(c, []).append(d)
    print(f'FINDINGS: {len(findings)} across {len(by)} checks')
    for c in sorted(by):
        print(f'\n  [{c}] {len(by[c])}')
        for d in by[c][:12]:
            print(f'      {d}')
    sys.exit(1)
print('FINDINGS: 0')
