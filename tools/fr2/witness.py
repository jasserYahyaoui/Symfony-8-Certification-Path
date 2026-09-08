"""FR-2 pass 3 — widen the witness corpus (spec §15 risk 3).

The lots-12+ evidence judged only vocabulary that happens to appear in both
bands, and the second audit reported 1288 word types it could not judge. That
is FR-3's incomplete-table trap, and reporting "0 findings" on a table that
cannot see most of the corpus would have repeated it exactly.

The wider witness is the repository's own correct French:

  content/courses/*.md      accented uniformly in EVERY lot (34-59 accented
                            characters per course in lots 01-11, comparable to
                            53-148 later), so the courses do not carry FR-2's
                            defect and can testify against it
  content/flashcards/*.yml  repaired under FR-1
  content/questions/*.yml   repaired under FR-3
  matrix lots 12+           never affected
  src/Build/DocsGenerator.php, website/src/**  French UI prose

Code is excluded: fenced blocks, backticked spans, and any identifier-shaped
token (spec §10).
"""
import re, collections, unicodedata, pathlib
import yaml

ROOT = pathlib.Path(__file__).resolve().parents[2]
MATRIX = ROOT / 'docs/syllabus/syllabus-matrix.yml'
FIELDS = ['content_level_justification', 'learning_outcomes', 'minimum_evidence']
WORD = re.compile(r"[A-Za-zÀ-ÿœŒ][A-Za-zÀ-ÿœŒ'’-]*")
FENCE = re.compile(r'```.*?```', re.S)
SPAN = re.compile(r'`[^`]*`')
FRONT = re.compile(r'\A---\n.*?\n---\n', re.S)
IDENT = re.compile(r'^(?:[A-Z][a-z0-9]+){2,}$|^[a-z]+(?:[A-Z][a-z0-9]+)+$|^[A-Z_]{2,}$')


def fold(w):
    return ''.join(c for c in unicodedata.normalize('NFD', w.lower())
                   if unicodedata.category(c) != 'Mn').replace('œ', 'oe')


# A YAML slug list (`tags:\n  - securite`) is metadata, not prose, and its
# unaccented slugs are correct as slugs. One such tag was enough to make the
# witness call `securite` ambiguous and leave 11 real defects unrepaired, so
# bare slug lines are dropped before the witness is built.
SLUG_LINE = re.compile(r'^\s*-\s*[a-z0-9][a-z0-9-]*\s*$', re.M)


def clean(t):
    t = SLUG_LINE.sub(' ', t)
    return SPAN.sub(' ', FENCE.sub(' ', t))


def lotn(it):
    m = re.match(r'lot-(\d+)$', it.get('lot') or '')
    return int(m.group(1)) if m else -1


def strs(v):
    if isinstance(v, str):
        return [v]
    if isinstance(v, list):
        return [x for x in v if isinstance(x, str)]
    return []


def witness_forms():
    forms = collections.defaultdict(collections.Counter)

    def add(text):
        for w in WORD.findall(clean(text)):
            if not IDENT.match(w):
                forms[fold(w)][w] += 1

    for f in (ROOT / 'content/courses').glob('*.md'):
        add(FRONT.sub('', f.read_text(encoding='utf-8')))
    # Only FRENCH records may testify. 523 of the 544 questions are English,
    # and English "reference", "declaration", "generation", "representation"
    # are correct English words -- feeding them in made the witness call the
    # French "référence" ambiguous and would have left real defects standing.
    PROSE = ('question', 'explanation', 'front', 'back',
             'memorization_justification', 'text')

    def add_records(path):
        y = yaml.safe_load(path.read_text(encoding='utf-8'))
        recs = y if isinstance(y, list) else (y.get('questions') or y.get('flashcards')
                                              or y.get('items') or [])
        if isinstance(recs, dict):
            recs = list(recs.values())
        for r in recs:
            if not isinstance(r, dict) or r.get('language') != 'fr':
                continue
            for k in PROSE:
                v = r.get(k)
                if isinstance(v, str):
                    add(v)
            for c in (r.get('choices') or []):
                if isinstance(c, dict) and isinstance(c.get('text'), str):
                    add(c['text'])

    for sub in ('flashcards', 'questions'):
        d = ROOT / 'content' / sub
        if d.is_dir():
            for f in d.glob('*.yml'):
                add_records(f)
    for f in [ROOT / 'src/Build/DocsGenerator.php']:
        if f.exists():
            add(f.read_text(encoding='utf-8'))
    for f in (ROOT / 'website/src').rglob('*.tsx'):
        add(f.read_text(encoding='utf-8'))
    items = yaml.safe_load(MATRIX.read_text(encoding='utf-8'))['items']
    for it in items:
        if lotn(it) >= 12:
            for fl in FIELDS:
                for s in strs(it.get(fl)):
                    add(s)
    return forms


def target_forms():
    items = yaml.safe_load(MATRIX.read_text(encoding='utf-8'))['items']
    forms = collections.defaultdict(collections.Counter)
    for it in items:
        if 1 <= lotn(it) <= 11:
            for fl in FIELDS:
                for s in strs(it.get(fl)):
                    for w in WORD.findall(s):
                        if not IDENT.match(w):
                            forms[fold(w)][w] += 1
    return forms


def accented(w):
    return any(ord(c) > 127 for c in w)


def proposals(ambiguous=frozenset()):
    wit, tgt = witness_forms(), target_forms()
    out, unwitnessed = {}, []
    for k, tf in tgt.items():
        plain = [w for w in tf if not accented(w)]
        if not plain:
            continue
        wf = wit.get(k)
        if not wf:
            unwitnessed.append((sum(tf[w] for w in plain), plain[0]))
            continue
        acc = [w for w in wf if accented(w)]
        pl = [w for w in wf if not accented(w)]
        if not acc:
            continue          # witness says this word is correctly unaccented
        if pl:
            unwitnessed.append((sum(tf[w] for w in plain), plain[0]))
            continue          # witness writes it both ways -> ambiguous
        if k in ambiguous:
            continue
        best = max(acc, key=lambda w: wf[w])
        for src in plain:
            dst = (best[0].upper() + best[1:]) if src[0].isupper() else (best[0].lower() + best[1:])
            if fold(dst) == fold(src) and dst != src:
                out[src] = {'to': dst, 'occ': tf[src], 'witness': sum(wf.values())}
    unwitnessed.sort(reverse=True)
    return out, unwitnessed


if __name__ == '__main__':
    tbl, unw = proposals()
    print(f'WIDENED PROPOSALS: {len(tbl)} forms, {sum(v["occ"] for v in tbl.values())} occurrences')
    for s in sorted(tbl, key=lambda x: -tbl[x]['occ'])[:70]:
        v = tbl[s]
        print(f'  {v["occ"]:4d}  {s:22s} -> {v["to"]:22s} (witness {v["witness"]})')
    print(f'\nSTILL UNWITNESSED / ambiguous in the witness: {len(unw)} types')
    for n, w in unw[:40]:
        print(f'  {n:4d}  {w}')
