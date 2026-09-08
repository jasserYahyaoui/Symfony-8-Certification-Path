"""AUD-07 — English readiness audit (§14, §5; §22 clause 6).

Clause 6 asks for a "functioning English timed simulation". The artefact half
is settled — exam mode and Mock 4 exist and are deployed — so this audit tests
the half a build cannot: is the corpus's English actually usable under exam
conditions, and does it meet §5's own thresholds rather than a comfortable
reading of them?

§5, verbatim in docs/policy/language-policy.md:
  - at least 50% of advanced questions must be in English;
  - Mock 3 must be primarily in English;
  - Mock 4, the official-format simulation, must be 100% in English;
  - final EXAM_READY status requires acceptable timed performance in English.

The fourth clause is the human sitting and no script closes it.
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
hard = [q for q in questions if q.get('difficulty') == 'hard']
validation = [q for q in questions if q.get('pool') == 'VALIDATION']
holdout = [q for q in questions if q.get('pool') == 'HOLDOUT']


def english(qs):
    return [q for q in qs if q.get('language') == 'en']


# --- ENG-1 §5's advanced-question threshold --------------------------------
ratio = len(english(hard)) / len(hard) if hard else 0.0
bump('hard questions', len(hard))
bump('hard questions in English', len(english(hard)))
if ratio < 0.50:
    note('ENG-1 fewer than half the advanced questions are in English',
         f'{len(english(hard))}/{len(hard)} = {ratio:.1%}, §5 requires at least 50%')

# --- ENG-2 Mock 3 primarily English (VALIDATION is its bank) ----------------
vratio = len(english(validation)) / len(validation) if validation else 0.0
bump('VALIDATION questions', len(validation))
bump('VALIDATION in English', len(english(validation)))
if vratio <= 0.50:
    note('ENG-2 Mock 3 is not primarily English',
         f'VALIDATION {len(english(validation))}/{len(validation)} = {vratio:.1%}')

# --- ENG-3 Mock 4 is 100% English ------------------------------------------
non_english_holdout = [q['id'] for q in holdout if q.get('language') != 'en']
bump('HOLDOUT questions', len(holdout))
for qid in non_english_holdout:
    note('ENG-3 Mock 4 carries a question that is not English', qid)

# --- ENG-4 a French question must be permitted where it sits ---------------
# §5 permits French for beginner practice. It does not permit it in a bank the
# thresholds bind. A French VALIDATION or HOLDOUT question is a defect even
# while the ratio above still passes.
for q in questions:
    if q.get('language') != 'en' and q.get('pool') in ('VALIDATION', 'HOLDOUT'):
        note('ENG-4 a non-English question sits in a bank §5 binds',
             f'{q["id"]}: pool {q["pool"]}, language {q.get("language")}')
french = [q for q in questions if q.get('language') == 'fr']
bump('French questions', len(french))
bump('French questions in LEARNING', sum(1 for q in french if q.get('pool') == 'LEARNING'))

# --- ENG-5 the English that exists must be readable under time -------------
# A question in English is not usable if it is unreadable at exam pace. The
# measure is the one the corpus already uses: estimated_time_seconds against
# the length of what must actually be read.
WORDS = re.compile(r'\S+')
for q in questions:
    if q.get('language') != 'en':
        continue
    text = ' '.join(filter(None, [
        q.get('question'),
        *[c.get('text') for c in (q.get('choices') or [])],
    ]))
    words = len(WORDS.findall(text))
    seconds = q.get('estimated_time_seconds') or 0
    # 200 wpm is the same reading convention AUD-04 uses, labelled as such.
    # A question whose stem and choices cannot be *read* in its own budget is
    # mistimed, whatever a candidate then does about answering it.
    reading = words / 200 * 60
    if seconds and reading > seconds:
        note('ENG-5 the stem and choices cannot be read inside the estimated time',
             f'{q["id"]}: {words} words needs ~{reading:.0f}s, budget {seconds}s')
bump('English questions timed', sum(1 for q in questions if q.get('language') == 'en'))

# --- ENG-6 the glossary §5 requires exists and is populated ----------------
glossary = collect._load(collect.ROOT / 'docs/syllabus/glossary.yml')
entries = glossary.get('entries') or glossary.get('glossary') or []
bump('glossary entries', len(entries))
if not entries:
    note('ENG-6 the §5 French-to-English glossary is empty or missing', 'docs/syllabus/glossary.yml')

by_pool_lang = collections.Counter((q.get('pool'), q.get('language')) for q in questions)
for (pool, lang), n in sorted(by_pool_lang.items()):
    bump(f'{pool} / {lang}', n)

print('AUD-07 — English readiness audit')
print('=' * 60)
for k in sorted(counts):
    print(f'  {k:44s} {counts[k]}')
print()
print('  Scope statement: §5\'s fourth clause — "final EXAM_READY status')
print('  requires acceptable timed performance in English" — is the human')
print('  sitting. No script closes it, and this audit does not claim to.')
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
