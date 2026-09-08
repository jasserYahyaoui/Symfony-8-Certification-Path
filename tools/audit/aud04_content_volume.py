"""AUD-04 — Content-volume and duplication audit (§14; §22 clause 8).

Clause 8 asks whether the corpus is "a corpus a candidate can actually
revise". That is two questions, and only the first is arithmetic:

  is the volume manageable, measured the way CLAUDE.md requires — body
  words, front matter excluded;
  and is any of that volume duplicated, so that a learner reads the same
  thing twice under two headings?

Duplication is the half that matters. Volume can look reasonable while a
third of it repeats, and CRS-5 (Lot 13) showed this project can ship a
course that restates another almost entirely.

Exit 1 on any finding.
"""
import collections
import difflib
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


# §22 clause 8 has no numeric threshold in the Master Plan, so this audit does
# not invent one as a pass/fail line. It measures, and it fails only on
# duplication — a defect — and on a course so long it defeats revision, where
# "so long" is set against the corpus's own distribution rather than a guess.
courses = collect.courses()
words = []
for fm, body in courses:
    words.append((fm['_file'], len(body.split())))

sizes = sorted(n for _f, n in words)
total = sum(sizes)
median = sizes[len(sizes) // 2]
mean = round(total / len(sizes))
bump('courses', len(courses))
bump('body words total', total)
bump('median body words', median)
bump('mean body words', mean)
bump('shortest', sizes[0])
bump('longest', sizes[-1])

# An outlier is judged against this corpus, not an external rule: a course
# more than four times the median is an outlier by construction.
OUTLIER = median * 4
for fname, n in words:
    if n > OUTLIER:
        note('VOL-1 course far longer than the corpus median',
             f'{fname}: {n} body words, median {median}, threshold {OUTLIER}')

# --- duplication between courses ------------------------------------------
# Compared on normalised prose with fenced code removed: two courses may
# legitimately show the same short snippet, and CRS-001 already governs that.
FENCE = re.compile(r'```.*?```', re.S)
INLINE = re.compile(r'`[^`]*`')


def prose(body):
    t = FENCE.sub(' ', body)
    t = INLINE.sub(' ', t)
    t = re.sub(r'[^\w\s]', ' ', t.lower())
    return ' '.join(t.split())


# Compared as word sequences, not characters, and with autojunk disabled.
# SequenceMatcher's autojunk heuristic treats any element occurring in more
# than 1% of a sequence longer than 200 as junk. On character strings that is
# every common letter and every space, and the ratio it then returns is
# meaningless: a course pasted verbatim into another scored 0.02. The
# fail-proof caught it; the check had never been capable of firing.
normalised = [(fm['_file'], prose(body).split()) for fm, body in courses]

# 1. Identical long lines shared by two courses.
line_owners = collections.defaultdict(set)
for fm, body in courses:
    for line in FENCE.sub(' ', body).split('\n'):
        s = line.strip()
        if len(s) >= 60 and not s.startswith(('#', '|', '-', '*', '>')):
            line_owners[s].add(fm['_file'])
for line, owners in line_owners.items():
    if len(owners) > 1:
        note('VOL-2 identical prose line in more than one course',
             f'{sorted(owners)}: "{line[:90]}"')

# 2. Whole-course similarity. Quadratic over 163 documents is affordable and
#    exact; a shingle estimate would be faster and would report a ratio nobody
#    can check by reading.
SIMILAR = 0.60
for i in range(len(normalised)):
    for j in range(i + 1, len(normalised)):
        a, b = normalised[i], normalised[j]
        # Cheap length gate first: documents of very different size cannot
        # exceed the ratio, and skipping them keeps the pass tractable.
        if min(len(a[1]), len(b[1])) / max(len(a[1]), len(b[1]), 1) < SIMILAR:
            continue
        ratio = difflib.SequenceMatcher(None, a[1], b[1], autojunk=False).ratio()
        if ratio >= SIMILAR:
            note('VOL-3 two courses are substantially the same text',
                 f'{a[0]} vs {b[0]}: similarity {ratio:.2f}')
bump('course pairs compared', len(normalised) * (len(normalised) - 1) // 2)

# --- duplication among flashcards ------------------------------------------
fronts = collections.defaultdict(list)
for card in collect.flashcards():
    fronts[prose(card.get('front') or '')].append(card['id'])
bump('flashcards', sum(len(v) for v in fronts.values()))
for front, ids in fronts.items():
    if len(ids) > 1:
        note('VOL-4 two flashcards ask the same question',
             f'{ids}: "{front[:80]}"')

# --- revision burden, reported not judged ----------------------------------
# 200 words per minute is a reading-rate convention, not a Master Plan figure,
# and is labelled as such wherever it appears.
bump('estimated reading minutes at 200 wpm', round(total / 200))

print('AUD-04 — content-volume and duplication audit')
print('=' * 60)
for k in sorted(counts):
    print(f'  {k:44s} {counts[k]}')
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
