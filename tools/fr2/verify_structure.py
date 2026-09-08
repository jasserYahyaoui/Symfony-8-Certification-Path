"""FR-2 structural verifier (spec §17).

Compares the matrix against a reference copy and asserts that the ONLY thing
that changed is diacritics inside the three in-scope fields of lots 01-11.

The decisive assertion is fold-equality: every modified string must reduce to
the SAME ascii text as before. That single check proves no word was added,
removed, reordered or re-spelled, and that no technical identifier was touched
- because any of those would change the folded form.
"""
import sys, re, unicodedata, pathlib, yaml

ROOT = pathlib.Path(__file__).resolve().parents[2]
FIELDS = ['content_level_justification', 'learning_outcomes', 'minimum_evidence']


def fold(s):
    return ''.join(c for c in unicodedata.normalize('NFD', s)
                   if unicodedata.category(c) != 'Mn').replace('œ', 'oe').replace('Œ', 'OE')


def lotn(it):
    m = re.match(r'lot-(\d+)$', it.get('lot') or '')
    return int(m.group(1)) if m else -1


def load(p):
    return yaml.safe_load(pathlib.Path(p).read_text(encoding='utf-8'))['items']


def main(ref_path, cur_path=None):
    ref = load(ref_path)
    cur = load(cur_path or (ROOT / 'docs/syllabus/syllabus-matrix.yml'))
    errs, changed_strings, changed_items = [], 0, set()

    if len(ref) != len(cur):
        print(f'FAIL item count {len(ref)} -> {len(cur)}')
        return 1
    for a, b in zip(ref, cur):
        if a['id'] != b['id']:
            errs.append(f'item order changed at {a["id"]}')
            continue
        if set(a) != set(b):
            errs.append(f'{a["id"]}: field set changed')
            continue
        scope = 1 <= lotn(a) <= 11
        for k in a:
            if k not in FIELDS:
                if a[k] != b[k]:
                    errs.append(f'{a["id"]}.{k}: OUT-OF-SCOPE FIELD CHANGED')
                continue
            if not scope:
                if a[k] != b[k]:
                    errs.append(f'{a["id"]}.{k}: changed in an OUT-OF-SCOPE lot ({a.get("lot")})')
                continue
            av, bv = a[k], b[k]
            if isinstance(av, list) != isinstance(bv, list):
                errs.append(f'{a["id"]}.{k}: type changed'); continue
            av = av if isinstance(av, list) else [av]
            bv = bv if isinstance(bv, list) else [bv]
            if len(av) != len(bv):
                errs.append(f'{a["id"]}.{k}: string count {len(av)} -> {len(bv)}'); continue
            for i, (x, y) in enumerate(zip(av, bv)):
                if x == y:
                    continue
                changed_strings += 1
                changed_items.add(a['id'])
                if fold(x) != fold(y):
                    errs.append(f'{a["id"]}.{k}[{i}]: NOT a diacritics-only change\n'
                                f'      before: {x[:110]}\n      after : {y[:110]}')
                if len(x.split()) != len(y.split()):
                    errs.append(f'{a["id"]}.{k}[{i}]: word count {len(x.split())} -> {len(y.split())}')

    print(f'strings changed : {changed_strings}')
    print(f'items touched   : {len(changed_items)}')
    if errs:
        print(f'\nFAIL — {len(errs)} structural violation(s):')
        for e in errs[:25]:
            print('  ', e)
        return 1
    print('\nSTRUCTURE OK — diacritics-only, in-scope fields of lots 01-11 only,')
    print('word counts and string counts unchanged, every other field identical')
    return 0


if __name__ == '__main__':
    sys.exit(main(sys.argv[1], sys.argv[2] if len(sys.argv) > 2 else None))
