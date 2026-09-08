"""FR-2 pass 1 — apply the derived table textually (spec §8, §10, §17).

Textual, line-based and case-preserving. The YAML is never re-dumped: loading
and re-serialising would reformat all 163 items and bury the real change.

Safety, per spec §10: replacement is token-based using the same word regex the
table was derived with, so it is whole-word by construction and can never touch
a camelCase, PascalCase or ALL-CAPS identifier — none of those is a key in the
table.
"""
import re, sys, pathlib, collections
sys.path.insert(0, str(pathlib.Path(__file__).resolve().parent))
from derive_table import build, WORD, ROOT, MATRIX, FIELDS  # noqa: E402

ITEM = re.compile(r'^  - id: (\S+)')
LOT = re.compile(r'^    lot: (\S+)')
KEY = re.compile(r'^    ([a-z_]+):')


def in_scope(lot):
    m = re.match(r'lot-(\d+)$', lot or '')
    return bool(m) and 1 <= int(m.group(1)) <= 11


def main(write):
    table, _ = build()
    lines = MATRIX.read_text(encoding='utf-8').split('\n')

    # a first sweep records each item's lot, since `lot:` precedes the fields
    lot_of, cur = {}, None
    for ln in lines:
        m = ITEM.match(ln)
        if m:
            cur = m.group(1)
        elif cur:
            m = LOT.match(ln)
            if m:
                lot_of[cur] = m.group(1)

    out, applied = [], collections.Counter()
    cur, field = None, None
    for ln in lines:
        m = ITEM.match(ln)
        if m:
            cur, field = m.group(1), None
            out.append(ln)
            continue
        m = KEY.match(ln)
        if m:
            field = m.group(1) if m.group(1) in FIELDS else None
        active = field is not None and cur is not None and in_scope(lot_of.get(cur))
        if not active:
            out.append(ln)
            continue

        def sub(mo):
            w = mo.group(0)
            if w in table:
                applied[w] += 1
                return table[w]['to']
            return w

        out.append(WORD.sub(sub, ln))

    new = '\n'.join(out)
    total = sum(applied.values())
    print(f'pass 1: {total} occurrences replaced across {len(applied)} forms')
    for w in sorted(applied, key=lambda x: -applied[x])[:12]:
        print(f'   {applied[w]:4d}  {w} -> {table[w]["to"]}')
    expected = sum(v['occ'] for v in table.values())
    print(f'expected from the derived table: {expected}')
    if total != expected:
        print(f'MISMATCH: applied {total}, table predicts {expected}')
        for w in sorted(table):
            if applied[w] != table[w]['occ']:
                print(f'   {w}: applied {applied[w]}, expected {table[w]["occ"]}')
        return 1
    if write:
        MATRIX.write_text(new, encoding='utf-8')
        print('written')
    else:
        print('(dry run — nothing written)')
    return 0


if __name__ == '__main__':
    sys.exit(main('--write' in sys.argv))
