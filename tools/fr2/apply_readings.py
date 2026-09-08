"""FR-2 pass 2 — the ambiguous occurrences, decided by reading (spec §9).

Nothing here comes from a table. Every occurrence in these classes was printed
with its sentence, read, and classified by hand on 2026-09-08; this file
records those decisions so they can be re-applied and audited.

The decisive class is `a` / `à`: 170 occurrences, of which exactly 12 are the
verb *avoir* and keep their bare `a`. They are identified by the words on
either side, and the script ASSERTS that exactly 12 match — if the corpus ever
changes so a 13th appears, this fails loudly rather than silently accenting a
verb.

A folded scalar (`>-`) wraps one sentence over several lines, so the word before
an occurrence can sit on the previous line. Deciding from per-line context
misreads every occurrence at a line boundary — it under-counted the verb cases
9 to 12 on the first attempt. Field content is therefore substituted as ONE
block; no replacement contains a newline, so splitting back on '\n' restores
the layout exactly.
"""
import re, sys, pathlib, collections
sys.path.insert(0, str(pathlib.Path(__file__).resolve().parent))
from derive_table import MATRIX, FIELDS  # noqa: E402
from apply_table import ITEM, LOT, KEY, in_scope  # noqa: E402

VERB_A = {
    ("n'y", 'rien'), ('étape', 'un'), ('cascade', 'trois'), ("n'y", 'aucun'),
    ('—', 'un'), ('ligne', 'un'), ("d'enchainement", 'une'), ('argument', 'un'),
    ('qui', 'une'), ('qui', 'le'), ('interface', 'deux'),
}
EXPECTED_VERB_A = 12

GLOBAL = {
    'depasse': 'dépasse', 'decide': 'décide', 'separe': 'sépare',
    'echoue': 'échoue', 'leve': 'lève', 'abonne': 'abonné',
    'reserves': 'réservés', 'reserve': 'réservé', 'declares': 'déclarés',
    'releve': 'relève', 'integre': 'intégré', 'integres': 'intégrés',
    'declenche': 'déclenché', 'adapte': 'adapté',
    'differente': 'différente', 'differentes': 'différentes',
    'differents': 'différents',
}

PHRASES = [
    ("leve la ou l'on attend", "lève là où l'on attend"),
    ('le moment ou la réponse', 'le moment où la réponse'),
    ('priorité ou le premier servi', 'priorité où le premier servi'),
    ('Trois endroits ou écrire', 'Trois endroits où écrire'),
    ('Deux endroits ou construire', 'Deux endroits où construire'),
    ('multi-etapes, ou chaque etage', 'multi-etapes, où chaque etage'),
    ("d'authentification ou un badge", "d'authentification où un badge"),
    ('périmètre est limite aux', 'périmètre est limité aux'),
    ("d'exécution limite a la compilation", "d'exécution limité a la compilation"),
    ('dont les types different,', 'dont les types diffèrent,'),
    ('dont les défauts different.', 'dont les défauts diffèrent.'),
    ('mécanisme different par sous-systeme', 'mécanisme différent par sous-systeme'),
    ('un effet different, donc', 'un effet différent, donc'),
    ('csrf_token_id different par formulaire', 'csrf_token_id différent par formulaire'),
    ('menace differente,', 'menace différente,'),
    ('de capture differentes,', 'de capture différentes,'),
]

WORD = re.compile(r"[A-Za-zÀ-ÿœŒ][A-Za-zÀ-ÿœŒ'’-]*")
BARE_A = re.compile(r"(?<![\w'’])a(?![\w'’])")
QU_A = re.compile(r"qu'a(?![\w'’])")
DES_QU = re.compile(r"(?<![\w'’])des qu'")
stats = collections.Counter()


def process(block):
    s = block
    # A folded scalar wraps mid-phrase, so a read decision can straddle a line
    # break. Match on whitespace-insensitive patterns and put the ORIGINAL
    # whitespace back, so the file's line layout is never disturbed.
    for old, new_ in PHRASES:
        parts = old.split(' ')
        pat = re.compile(r'\s+'.join(re.escape(p) for p in parts))
        rep = new_.split(' ')
        if len(rep) != len(parts):
            raise SystemExit(f'phrase word count differs: {old!r} -> {new_!r}')

        def _sub(mo, rep=rep, parts=parts):
            gaps = re.findall(r'\s+', mo.group(0))
            out = rep[0]
            for i, g in enumerate(gaps):
                out += g + rep[i + 1]
            stats['phrase'] += 1
            return out
        s = pat.sub(_sub, s)
    n = len(DES_QU.findall(s))
    if n:
        stats["dès qu'"] += n
        s = DES_QU.sub("dès qu'", s)
    n = len(QU_A.findall(s))
    if n:
        stats["qu'à"] += n
        s = QU_A.sub("qu'à", s)

    def a_sub(mo):
        before = s[:mo.start()].split()
        after = s[mo.end():].split()
        pair = (before[-1] if before else '<S>',
                after[0].rstrip(',.;:') if after else '<E>')
        if pair in VERB_A:
            stats['verb_a'] += 1
            return 'a'
        stats['a_to_à'] += 1
        return 'à'
    s = BARE_A.sub(a_sub, s)

    def g_sub(mo):
        w = mo.group(0)
        if w in GLOBAL:
            stats[w] += 1
            return GLOBAL[w]
        return w
    return WORD.sub(g_sub, s)


def main(write):
    lines = MATRIX.read_text(encoding='utf-8').split('\n')
    lot_of, cur = {}, None
    for ln in lines:
        m = ITEM.match(ln)
        if m:
            cur = m.group(1)
        elif cur:
            m = LOT.match(ln)
            if m:
                lot_of[cur] = m.group(1)

    out, buf, cur, field = [], [], None, None

    def flush():
        if buf:
            out.extend(process('\n'.join(buf)).split('\n'))
            buf.clear()

    for ln in lines:
        m = ITEM.match(ln)
        if m:
            flush(); cur, field = m.group(1), None
            out.append(ln); continue
        m = KEY.match(ln)
        if m:
            flush()
            field = m.group(1) if m.group(1) in FIELDS else None
        if field is None or cur is None or not in_scope(lot_of.get(cur)):
            flush(); out.append(ln); continue
        buf.append(ln)
    flush()

    print(f"a -> à                   : {stats['a_to_à']}")
    print(f"a kept as the verb avoir : {stats['verb_a']} (expected {EXPECTED_VERB_A})")
    qua = stats["qu'à"]
    desqu = stats["dès qu'"]
    print(f"qu'a -> qu'a-grave       : {qua}")
    print(f"des qu -> des-grave qu   : {desqu}")
    print(f"read phrases applied     : {stats['phrase']}")
    for w in sorted(GLOBAL):
        if stats[w]:
            print(f"  {w} -> {GLOBAL[w]}: {stats[w]}")
    if stats['verb_a'] != EXPECTED_VERB_A:
        print(f"\nFAIL: expected {EXPECTED_VERB_A} verb-avoir occurrences, found {stats['verb_a']}")
        return 1
    if write:
        MATRIX.write_text('\n'.join(out), encoding='utf-8')
        print('\nwritten')
    else:
        print('\n(dry run — nothing written)')
    return 0


if __name__ == '__main__':
    sys.exit(main('--write' in sys.argv))
