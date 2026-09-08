"""AUD-05 — Question-bank audit (§14, §7.1–§7.3, §9; §22 clause 3).

Clause 3 asks for "0 known incorrect scored answer". No script can read a
question and know whether its key is right — that is what the mid-path
professor audit (AUD-1) and the human sitting are for. What a script can do is
find the shapes in which a wrong answer hides, and the ones the 18 mandatory
rules do not already reject.

QST-001 already requires: a resolvable official item, at least one correct
answer, an explanation on every distractor and on the question, a cited source,
and no scored UNKNOWN_NEEDS_VERIFICATION. DUP-001 covers duplicates and
near-duplicates, COG-001 the cognitive level, POOL-001/002 the pools, SCOPE-001
the exclusions. This audit does not repeat any of those. It asks what is left.
"""
import collections
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


questions = collect.questions()
bump('questions', len(questions))

matrix_items = {i['id'] for i in collect.matrix()}
choice_ids = collections.Counter()
question_ids = collections.Counter()
per_item = collections.Counter()

for q in questions:
    qid = q['id']
    question_ids[qid] += 1
    per_item[q['official_item']] += 1
    choices = q.get('choices') or []
    keys = [c for c in choices if c.get('correct')]
    mode = q.get('answer_mode')
    declared = q.get('required_answer_count')

    for c in choices:
        choice_ids[c['id']] += 1

    # --- QB-1 the declared answer count is the real one -------------------
    # QST-001 requires "at least one correct answer" and stops there. A
    # question declaring two keys and carrying three is scored wrongly for
    # every learner and no rule sees it.
    if declared is not None and len(keys) != declared:
        note('QB-1 required_answer_count disagrees with the keys',
             f'{qid}: declares {declared}, carries {len(keys)}')

    # --- QB-2 the answer mode matches the key count ------------------------
    if mode == 'single' and len(keys) != 1:
        note('QB-2 answer_mode single with the wrong number of keys',
             f'{qid}: {len(keys)} keys')
    if mode == 'multiple' and len(keys) < 2:
        note('QB-2 answer_mode multiple with fewer than two keys',
             f'{qid}: {len(keys)} keys')

    # --- QB-3 a question a learner cannot get wrong -------------------------
    # Every choice correct, or only one choice: no discrimination at all.
    if choices and len(keys) == len(choices):
        note('QB-3 every choice is correct, so the question discriminates nothing', qid)
    if len(choices) < 3:
        note('QB-3 fewer than three choices', f'{qid}: {len(choices)}')

    # --- QB-4 two choices with the same text -------------------------------
    # Compared case-sensitively on purpose: the first version of this check
    # lowercased and flagged QST-whsz8qfwgcby, whose whole point is that
    # getLanguages() normalises case — fr_FR and FR_fr are different answers.
    seen = collections.Counter(' '.join((c.get('text') or '').split()) for c in choices)
    for text, n in seen.items():
        if n > 1:
            note('QB-4 two choices carry the same text', f'{qid}: "{text[:60]}" x{n}')

    # --- QB-5 a distractor that gives itself away --------------------------
    GIVEAWAY = re.compile(r'^\s*(all of the above|none of the above|toutes ces réponses'
                          r'|aucune de ces réponses|both a and b)\s*$', re.I)
    for c in choices:
        if GIVEAWAY.match(c.get('text') or ''):
            note('QB-5 catch-all choice', f'{qid}: "{c.get("text")}"')

    # --- QB-6 estimated time inside a plausible band -----------------------
    t = q.get('estimated_time_seconds')
    if not isinstance(t, int) or not (20 <= t <= 180):
        note('QB-6 estimated_time_seconds outside 20-180',
             f'{qid}: {t!r}')

    # --- QB-7 the item exists (QST-001 covers it; measured here for the
    #     coverage figure below, and reported if it ever disagrees) ---------
    if q['official_item'] not in matrix_items:
        note('QB-7 official_item does not resolve', f'{qid}: {q["official_item"]}')

# --- QB-8 identifiers are unique across the whole bank ---------------------
# Minted ids (ADR-0002) are random, so a collision means a copy-paste, and a
# duplicated choice id makes a learner's answer ambiguous to the scorer.
for qid, n in question_ids.items():
    if n > 1:
        note('QB-8 duplicate question id', f'{qid} appears {n} times')
for cid, n in choice_ids.items():
    if n > 1:
        note('QB-8 duplicate choice id', f'{cid} appears {n} times')
bump('distinct question ids', len(question_ids))
bump('distinct choice ids', len(choice_ids))
bump('choices', sum(choice_ids.values()))

# --- QB-9 every atomic item carries at least one question -------------------
without = sorted(matrix_items - set(per_item))
for item in without:
    note('QB-9 atomic item with no question at all', item)
bump('items carrying questions', len(per_item))
bump('questions per item, min', min(per_item.values()))
bump('questions per item, max', max(per_item.values()))

pools = collections.Counter(q['pool'] for q in questions)
for pool, n in pools.items():
    bump(f'pool {pool}', n)
modes = collections.Counter(q.get('answer_mode') for q in questions)
for mode, n in modes.items():
    bump(f'answer_mode {mode}', n)

print('AUD-05 — question-bank audit')
print('=' * 60)
for k in sorted(counts):
    print(f'  {k:44s} {counts[k]}')
print()
print('  Scope statement: no script can decide whether an answer key is')
print('  correct. This audit finds the shapes in which a wrong answer hides.')
print('  Key correctness rests on the AUD-1 professor audit and, ultimately,')
print('  on the human sitting — clause 3 is never closed by this file alone.')
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
