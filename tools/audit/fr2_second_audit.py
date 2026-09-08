"""FR-2 second independent audit (spec §17, §20.2).

Independent of the correction tools: it re-reads the corpus and asks whether
any defect REMAINS. It deliberately does not import the table that was applied,
because an audit that reuses the fix's own vocabulary can only confirm the fix
did what it did.

FR2-4 is the check that matters. FR-3's recorded failure was an incomplete
table -- it held `requetes` but not `requete` -- so this audit reports every
still-unaccented French-looking word that the lots-12+ evidence CANNOT judge,
rather than reporting success because the evidence it has is clean.
"""
import re, sys, collections, unicodedata, pathlib
import yaml

ROOT = pathlib.Path(__file__).resolve().parents[2]
MATRIX = ROOT / 'docs/syllabus/syllabus-matrix.yml'
FIELDS = ['content_level_justification', 'learning_outcomes', 'minimum_evidence']
WORD = re.compile(r"[A-Za-zÀ-ÿœŒ][A-Za-zÀ-ÿœŒ'’-]*")
# shapes that are never a French word: identifiers (spec §10)
IDENT = re.compile(r'^(?:[A-Z][a-z0-9]+){2,}$|^[a-z]+(?:[A-Z][a-z0-9]+)+$|^[A-Z_]{2,}$')

findings, counts = [], {}


def note(c, d):
    findings.append((c, d))


def bump(k, n=1):
    counts[k] = counts.get(k, 0) + n


def fold(w):
    return ''.join(c for c in unicodedata.normalize('NFD', w.lower())
                   if unicodedata.category(c) != 'Mn').replace('œ', 'oe')


def lotn(it):
    m = re.match(r'lot-(\d+)$', it.get('lot') or '')
    return int(m.group(1)) if m else -1


def strs(v):
    if isinstance(v, str):
        return [v]
    if isinstance(v, list):
        return [x for x in v if isinstance(x, str)]
    return []


items = yaml.safe_load(MATRIX.read_text(encoding='utf-8'))['items']
early_forms = collections.defaultdict(collections.Counter)
late_forms = collections.defaultdict(collections.Counter)
early_n = late_n = 0
for it in items:
    n = lotn(it)
    for f in FIELDS:
        for s in strs(it.get(f)):
            if 1 <= n <= 11:
                early_n += 1
            elif n >= 12:
                late_n += 1
            for w in WORD.findall(s):
                if 1 <= n <= 11:
                    early_forms[fold(w)][w] += 1
                elif n >= 12:
                    late_forms[fold(w)][w] += 1
bump('strings, lots 01-11', early_n)
bump('strings, lots 12+', late_n)

def accented(w):
    return any(ord(c) > 127 for c in w)

# --- FR2-1 no word remains unaccented that lots 12+ always accent ----------
for k, ef in early_forms.items():
    lf = late_forms.get(k)
    if not lf:
        continue
    if any(accented(w) for w in lf) and not any(not accented(w) for w in lf):
        for w in ef:
            if not accented(w) and not IDENT.match(w):
                note('FR2-1 a form lots 12+ always accent is still unaccented',
                     f'{w!r} x{ef[w]} (lots 12+ write {list(lf)[0]!r})')

# --- FR2-2 the decided ambiguous classes are resolved ---------------------
for it in items:
    if not (1 <= lotn(it) <= 11):
        continue
    for f in FIELDS:
        for s in strs(it.get(f)):
            for w in ['depasse', 'decide', 'separe', 'echoue', 'leve', 'abonne',
                      'declares', 'releve', 'declenche', 'reserves']:
                if re.search(r"(?<![\w'’])" + w + r"(?![\w'’])", s):
                    note('FR2-2 an ambiguous form decided in pass 2 is still present',
                         f'{it["id"]}.{f}: {w!r}')
            if re.search(r"(?<![\w'’])des qu'", s):
                note('FR2-2 "des qu\'" was not corrected to "dès qu\'"', it['id'])
            if re.search(r"qu'a(?![\w'’])", s):
                note('FR2-2 "qu\'a" was not corrected to "qu\'à"', it['id'])

# --- FR2-3 the accent profile now matches the clean lots ------------------
def profile(lo, hi):
    tot = acc = 0
    for it in items:
        n = lotn(it)
        if not (lo <= n <= hi):
            continue
        for f in FIELDS:
            for s in strs(it.get(f)):
                tot += 1
                if any(ord(c) > 127 for c in s):
                    acc += 1
    return acc, tot

a1, t1 = profile(1, 11)
a2, t2 = profile(12, 26)
bump('accented strings, lots 01-11', a1)
bump('accented strings, lots 12+', a2)
r1 = a1 / t1 if t1 else 0
r2 = a2 / t2 if t2 else 0
bump('accented ratio lots 01-11 (%)', round(r1 * 100))
bump('accented ratio lots 12+ (%)', round(r2 * 100))
if r1 < r2 * 0.6:
    note('FR2-3 lots 01-11 remain far less accented than the clean lots',
         f'{r1:.0%} against {r2:.0%} — the repair looks incomplete')

# --- FR2-4 words this method CANNOT judge --------------------------------
# The completeness hole FR-3 fell into. Reported, never silently passed.
unjudgeable = []
for k, ef in early_forms.items():
    if k in late_forms:
        continue
    for w in ef:
        if accented(w) or IDENT.match(w) or len(w) < 4:
            continue
        unjudgeable.append((ef[w], w))
unjudgeable.sort(reverse=True)
bump('word types with no lots-12+ witness', len(unjudgeable))

print('FR-2 second independent audit')
print('=' * 62)
for k in sorted(counts):
    print(f'  {k:44s} {counts[k]}')
print()
print('  Scope: this audit judges only what the corpus can witness. Words with')
print('  no lots-12+ occurrence are listed below for reading, NOT passed.')
print()
if unjudgeable:
    print(f'  UNJUDGEABLE BY EVIDENCE — {len(unjudgeable)} types, read individually:')
    for n, w in unjudgeable[:60]:
        print(f'      {n:3d}  {w}')
    if len(unjudgeable) > 60:
        print(f'      … and {len(unjudgeable) - 60} more')
    print()
if findings:
    by = {}
    for c, d in findings:
        by.setdefault(c, []).append(d)
    print(f'FINDINGS: {len(findings)} across {len(by)} checks')
    for c in sorted(by):
        print(f'\n  [{c}] {len(by[c])}')
        for d in by[c][:15]:
            print(f'      {d}')
        if len(by[c]) > 15:
            print(f'      … and {len(by[c]) - 15} more')
    sys.exit(1)
print('FINDINGS: 0')
