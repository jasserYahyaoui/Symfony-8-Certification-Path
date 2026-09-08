"""AUD-06 — Holdout integrity audit (§14, §7.3, ADR-0005/0006; §22 clause 7).

Clause 7 asks for a "protected unseen holdout assessment". The project already
enforces parts of that at build time and in production, so this audit is not a
fourth copy of those checks. It asks the questions nothing else asks, and it
asks them of the canonical data rather than of a payload:

  is the holdout the size and shape the blueprint says;
  is every holdout question reachable only through Mock 4;
  is any holdout question also referenced as learning material;
  does any holdout answer appear in a course, a flashcard, or another
  question — the leak that a pool label cannot see;
  and is the whole holdout still English, as §10 requires of Mock 4.

What this audit deliberately does NOT claim: confidentiality. The repository is
public and the answers are readable in content/questions/*.yml. Option A
(ADR-0005) settled that "unseen" means never served by a learning mode, and
every statement here is bounded by that.
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
holdout = [q for q in questions if q.get('pool') == 'HOLDOUT']
others = [q for q in questions if q.get('pool') != 'HOLDOUT']
hold_ids = {q['id'] for q in holdout}

bump('questions total', len(questions))
bump('holdout questions', len(holdout))
bump('learning + validation', len(others))
bump('holdout choices', sum(len(q.get('choices') or []) for q in holdout))
bump('holdout distinct items', len({q['official_item'] for q in holdout}))

# --- HOLD-1 shape against the blueprint ------------------------------------
blueprint = collect._load(collect.ROOT / 'docs/mocks/mock-4-blueprint.yml')
# The count lives at official_constraints.questions. An earlier version of this
# check read `question_count` or `questions`, neither of which exists, and fell
# through to a hardcoded `or 75` — so it compared the holdout against a literal
# rather than against the blueprint, and could not have failed. A missing key
# is now a finding, never a default.
constraints = blueprint.get('official_constraints') or {}
if 'questions' not in constraints:
    note('HOLD-1 the Mock 4 blueprint states no question count',
         'official_constraints.questions is absent, so the holdout size cannot be checked')
    expected = None
else:
    expected = int(constraints['questions'])
if expected is not None and len(holdout) != expected:
    note('HOLD-1 holdout size disagrees with the Mock 4 blueprint',
         f'{len(holdout)} holdout questions, blueprint says {expected}')
if expected is not None:
    bump('blueprint official_constraints.questions', expected)

# --- HOLD-2 one question per atomic item -----------------------------------
per_item = collections.Counter(q['official_item'] for q in holdout)
for item, n in per_item.items():
    if n > 1:
        note('HOLD-2 more than one holdout question on one atomic item',
             f'{item}: {n} questions')

# --- HOLD-3 the holdout is entirely English (§10, Mock 4) -------------------
for q in holdout:
    if q.get('language') != 'en':
        note('HOLD-3 holdout question is not English',
             f'{q["id"]}: language {q.get("language")!r}')

# --- HOLD-4 no holdout question referenced as learning material ------------
# POOL-001 guards this in the matrix. Here it is checked from the content side:
# a course, flashcard or matrix field must not point at a holdout question.
matrix_refs = set()
for item in collect.matrix():
    for field in ('question_refs', 'exam_refs', 'exercise_refs'):
        for ref in (item.get(field) or []):
            matrix_refs.add((item['id'], field, str(ref)))
for item_id, field, ref in matrix_refs:
    if ref in hold_ids and field != 'exam_refs':
        note('HOLD-4 holdout question referenced as learning material',
             f'{item_id}.{field} -> {ref}')
bump('matrix question references', len(matrix_refs))

# --- HOLD-5 no holdout ANSWER text reproduced outside the holdout -----------
# The check a pool label cannot make. A correct answer is the thing worth
# protecting, so its exact text is searched across every course body, every
# flashcard face, and every non-holdout question.
def norm(s):
    return ' '.join(re.sub(r'\s+', ' ', (s or '')).strip().lower().split())


answers = []
holdout_item_of = {}
for q in holdout:
    holdout_item_of[q['id']] = q['official_item']
    for choice in (q.get('choices') or []):
        if choice.get('correct'):
            text = norm(choice.get('text'))
            # Very short answers ("Oui", "404") collide by coincidence, and a
            # coincidence is not a leak. The floor is stated, not hidden.
            if len(text) >= 25:
                answers.append((q['id'], text))
bump('holdout correct answers checked', len(answers))
bump('holdout answers below the 25-char floor',
     sum(1 for q in holdout for c in (q.get('choices') or [])
         if c.get('correct') and len(norm(c.get('text'))) < 25))

# CRS-001's rule, applied here unchanged: a course may show the code its OWN
# item teaches inside a fenced block, even when a question on that item tests
# it. Under Option A "unseen" means never served by a learning mode, not that
# the underlying fact is secret — the learner is meant to learn it from the
# course, and Mock 4 tests whether they did. A check without this exemption
# flags every course that correctly teaches its own material, which is what the
# first run of this audit did.
FENCE = re.compile(r'```.*?```', re.S)

haystacks = []
for fm, body in collect.courses():
    outside_fences = norm(FENCE.sub(' ', body))
    haystacks.append((f'course {fm["id"]}', outside_fences, fm['official_item']))
    # Fenced code counts too, but only for a *different* item.
    haystacks.append((f'course {fm["id"]} (fenced)', norm(body), fm['official_item'], True))
for c in collect.flashcards():
    haystacks.append((f'flashcard {c["id"]}', norm(f'{c.get("front")} {c.get("back")} {c.get("explanation")}'),
                      c.get('official_item')))
for q in others:
    parts = [q.get('question'), q.get('explanation')]
    for ch in (q.get('choices') or []):
        parts += [ch.get('text'), ch.get('explanation')]
    haystacks.append((f'question {q["id"]}', norm(' '.join(p or '' for p in parts)), q.get('official_item')))
bump('haystacks searched', len(haystacks))

for qid, answer in answers:
    for entry in haystacks:
        where, hay, owner = entry[0], entry[1], entry[2]
        fenced_only = len(entry) > 3
        if answer not in hay:
            continue
        # The CRS-001 exemption: fenced code in the course of the same item.
        if fenced_only and owner == holdout_item_of[qid]:
            bump('own-item fenced occurrences (exempt, CRS-001)')
            continue
        note('HOLD-5 a holdout correct answer appears outside the holdout',
             f'{qid} answer "{answer[:60]}…" found in {where}'
             f' (item {owner}, holdout item {holdout_item_of[qid]})')

# --- HOLD-6 holdout ids absent from the learning payloads -------------------
# assertNoHoldoutLeak() enforces this at build time and the smoke test proves
# it in production. Repeating it here would be a third copy; instead this
# checks the *generator's* wiring is still in place, because a check that was
# removed reports nothing.
builder = (collect.ROOT / 'src/Build/PayloadBuilder.php').read_text(encoding='utf-8')
# Matched as a declaration, not a substring. `assertNoHoldoutLeak` is a prefix
# of `assertNoHoldoutLeakRENAMED`, so a bare `in` test reports the guard
# present after it has been renamed away — the same trap already recorded here
# for `esi` matching inside `SameSite`.
for guard in ('assertNoHoldoutLeak', 'assertMockMatchesBlueprint', 'assertTrainingMockMatchesBlueprint'):
    declared = re.search(r'function\s+' + re.escape(guard) + r'\s*\(', builder) is not None
    if not declared:
        note('HOLD-6 a build-time holdout guard has disappeared',
             f'PayloadBuilder no longer defines {guard}()')
    else:
        bump('build-time guards present')

print('AUD-06 — holdout integrity audit')
print('=' * 60)
for k in sorted(counts):
    print(f'  {k:44s} {counts[k]}')
print()
print('  Scope statement, per ADR-0005 Option A: this audit tests functional')
print('  isolation and application-level unseen-ness. It does NOT test')
print('  confidentiality — the repository is public and holdout answers are')
print('  readable in content/questions/*.yml.')
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
