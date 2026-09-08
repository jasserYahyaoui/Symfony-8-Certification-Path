"""FR-2 pass 3 — apply the widened-witness corrections (spec §15 risk 3).

Pass 1 could only judge vocabulary shared with lots 12+, and the second audit
reported 1288 word types it could not see. This pass judges them against the
repository's own correct French (tools/fr2/witness.py).

Four proposals were REJECTED after reading, and are recorded here rather than
silently applied. Each is the present tense where the witness happened to use a
participle -- the trap FR-1 hit with `oriente` and FR-3 with `implemente`.
"""
import re, sys, pathlib, collections
sys.path.insert(0, str(pathlib.Path(__file__).resolve().parent))
from derive_table import MATRIX, FIELDS  # noqa: E402
from apply_table import ITEM, LOT, KEY, in_scope  # noqa: E402
from witness import proposals, WORD  # noqa: E402

# Read on 2026-09-08; the witness proposal is wrong for these.
REJECTED = {
    'lie':     'verb "lie" (L\'omission de Vary lie ... au cache), not the participle "lié"',
    'apparie': 'verb "apparie" (ce qui apparie handler et message), not "apparié"',
    'experimental': 'the Symfony annotation keyword @experimental, listed beside '
                    '@internal as an exclusion — a technical identifier, not French (spec §10)',
}
OVERRIDE = {
    'resume': 'résume',   # "Flex se résume à quatre faits" - present, not "résumé"
}
# `deroule` is genuinely both, so it is decided per phrase.
PHRASES = [
    ('on ne la deroule pas', 'on ne la déroule pas'),   # verb
    ('Le deroule de la requête', 'Le déroulé de la requête'),  # noun
]


def main(write):
    table, unwitnessed = proposals()
    for k in REJECTED:
        table.pop(k, None)
    for k, v in OVERRIDE.items():
        if k in table:
            table[k]['to'] = v
    table.pop('deroule', None)

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

    stats = collections.Counter()

    def process(block):
        s = block
        for old, new in PHRASES:
            parts = old.split(' ')
            pat = re.compile(r'\s+'.join(re.escape(p) for p in parts))
            rep = new.split(' ')

            def _sub(mo, rep=rep):
                gaps = re.findall(r'\s+', mo.group(0))
                out = rep[0]
                for i, g in enumerate(gaps):
                    out += g + rep[i + 1]
                stats['phrase'] += 1
                return out
            s = pat.sub(_sub, s)

        def g(mo):
            w = mo.group(0)
            if w in table:
                stats[w] += 1
                return table[w]['to']
            return w
        return WORD.sub(g, s)

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

    total = sum(v for k, v in stats.items() if k != 'phrase')
    print(f'pass 3: {total} occurrences over {len([k for k in stats if k != "phrase"])} forms')
    print(f'        {stats["phrase"]} phrase decisions (deroule: verb vs noun)')
    print(f'rejected after reading: {len(REJECTED)} forms')
    for k, why in REJECTED.items():
        print(f'   {k}: {why}')
    print(f'overridden after reading: {list(OVERRIDE.items())}')
    expected = sum(v['occ'] for v in table.values())
    if total != expected:
        print(f'MISMATCH applied {total} vs table {expected}')
        for k in sorted(table):
            if stats[k] != table[k]['occ']:
                print(f'   {k}: {stats[k]} vs {table[k]["occ"]}')
        return 1
    if write:
        MATRIX.write_text('\n'.join(out), encoding='utf-8')
        print('written')
    else:
        print('(dry run)')
    return 0


if __name__ == '__main__':
    sys.exit(main('--write' in sys.argv))
