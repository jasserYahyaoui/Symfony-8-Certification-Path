"""Derive FR-2's correction table from the corpus itself (spec §6b).

A word type is proposed only when EVERY occurrence in lots 12+ is accented and
occurrences in lots 01-11 are not. Ambiguity classes named in spec §9 are
excluded here and read individually instead.
"""
import yaml, re, collections, unicodedata, json, sys, pathlib

ROOT = pathlib.Path(__file__).resolve().parents[2]
MATRIX = ROOT / 'docs/syllabus/syllabus-matrix.yml'
FIELDS = ['content_level_justification', 'learning_outcomes', 'minimum_evidence']

# Spec §9: both forms are valid French; a table can never decide these.
AMBIGUOUS = {
    'a', 'la', 'ou', 'des', 'du', 'sur', 'ca', 'pres',
    'different', 'differents', 'differente', 'differentes',
    'decide', 'decides', 'depasse', 'depasses', 'limite', 'limites',
    'separe', 'separes', 'oriente', 'orientes', 'implemente', 'implementes',
    'associe', 'associes', 'declare', 'declares', 'utilise', 'utilises',
    'termine', 'termines', 'considere', 'consideres', 'repete', 'repetes',
    'delegue', 'delegues', 'passe', 'passes',
    # Reviewed out of the derived table on 2026-09-08: lots 12+ happen to use
    # one form, but both the present tense and the past participle are valid
    # French here, so only reading the sentence can decide. This is the
    # oriente / implemente trap of FR-1 and FR-3, caught before application.
    'leve', 'leves', 'releve', 'releves', 'integre', 'integres',
    'reserve', 'reserves', 'adapte', 'adaptes', 'abonne', 'abonnes',
    'echoue', 'echoues', 'declenche', 'declenches',
}

WORD = re.compile(r"[A-Za-zÀ-ÿœŒ][A-Za-zÀ-ÿœŒ'’-]*")
CODE = re.compile(r'`[^`]*`')


def fold(w):
    return ''.join(c for c in unicodedata.normalize('NFD', w.lower())
                   if unicodedata.category(c) != 'Mn').replace('œ', 'oe')


def lotn(it):
    m = re.match(r'lot-(\d+)', it.get('lot') or '')
    return int(m.group(1)) if m else -1


def strs(v):
    if isinstance(v, str):
        return [v]
    if isinstance(v, list):
        return [x for x in v if isinstance(x, str)]
    return []


def tokens(it):
    for f in FIELDS:
        for s in strs(it.get(f)):
            yield from WORD.findall(CODE.sub(' ', s))


def build():
    items = yaml.safe_load(MATRIX.read_text(encoding='utf-8'))['items']
    early_forms = collections.defaultdict(collections.Counter)
    late_forms = collections.defaultdict(collections.Counter)
    for it in items:
        n = lotn(it)
        for w in tokens(it):
            k = fold(w)
            if 1 <= n <= 11:
                early_forms[k][w] += 1
            elif n >= 12:
                late_forms[k][w] += 1

    table = {}
    skipped = []
    for k, ef in early_forms.items():
        if k in AMBIGUOUS:
            skipped.append((k, sum(ef.values()), 'ambiguous per spec §9'))
            continue
        lf = late_forms.get(k)
        if not lf:
            continue
        acc = [w for w in lf if any(ord(c) > 127 for c in w)]
        plain_late = [w for w in lf if not any(ord(c) > 127 for c in w)]
        plain_early = [w for w in ef if not any(ord(c) > 127 for c in w)]
        if not (acc and not plain_late and plain_early):
            continue
        # canonical accented form = the most common one used in lots 12+
        target = max(acc, key=lambda w: lf[w])
        for src in plain_early:
            # preserve the case of the lots-01-11 form
            if src[0].isupper():
                dst = target[0].upper() + target[1:]
            else:
                dst = target[0].lower() + target[1:]
            if fold(dst) != fold(src) or dst == src:
                continue
            table[src] = {'to': dst, 'occ': ef[src], 'late_uses': sum(lf.values())}
    return table, skipped


if __name__ == '__main__':
    table, skipped = build()
    if '--json' in sys.argv:
        print(json.dumps(table, ensure_ascii=False, indent=1))
    else:
        tot = sum(v['occ'] for v in table.values())
        print(f'PROPOSED TABLE: {len(table)} forms, {tot} occurrences')
        for src in sorted(table, key=lambda s: -table[s]['occ']):
            v = table[src]
            print(f"  {v['occ']:4d}  {src:24s} -> {v['to']:24s} (lots12+ uses: {v['late_uses']})")
        print(f'\nEXCLUDED as ambiguous (read individually): {len(skipped)} forms')
        for k, n, why in sorted(skipped, key=lambda x: -x[1]):
            print(f'  {n:4d}  {k}')
