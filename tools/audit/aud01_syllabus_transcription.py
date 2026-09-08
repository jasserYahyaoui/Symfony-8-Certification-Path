"""AUD-01 — independent syllabus audit against the owner-supplied PDF.

Verifies transcription fidelity: every atomic official item in the matrix must
appear in the PDF text, character-for-character after two mechanical
normalisations that the import itself documents (f-ligatures, column wraps).
"""
import re, sys, hashlib, pathlib
sys.path.insert(0, 'tools/audit')
import collect
import pypdf

PDF = '/root/.claude/uploads/e9913c87-ab1b-52a7-9cac-ebb7da388008/9f18a75a-Symfony_Certification.PDF'
raw = pathlib.Path(PDF).read_bytes()
print('pdf sha256 :', hashlib.sha256(raw).hexdigest())
print('pdf bytes  :', len(raw))

r = pypdf.PdfReader(PDF)
text = '\n'.join((p.extract_text() or '') for p in r.pages)
print('pdf pages  :', len(r.pages))

LIG = {'ﬀ':'ff','ﬁ':'fi','ﬂ':'fl','ﬃ':'ffi','ﬄ':'ffl','’':"'"}
def norm(s):
    for k, v in LIG.items():
        s = s.replace(k, v)
    return re.sub(r'\s+', ' ', s).strip()

flat = norm(text)                      # wraps collapse to single spaces
items = collect.matrix()
missing, found = [], 0
for it in items:
    w = norm(it.get('official_wording') or it.get('title') or '')
    if not w:
        missing.append((it.get('id'), '<no wording field>'))
        continue
    if w in flat:
        found += 1
    else:
        missing.append((it.get('id'), w))

print(f'\nmatrix atomic items      : {len(items)}')
print(f'found verbatim in the PDF: {found}')
print(f'NOT found                : {len(missing)}')
for i, (rid, w) in enumerate(missing[:40]):
    print(f'  {rid}  {w!r}')
if len(missing) > 40:
    print(f'  … and {len(missing)-40} more')
sys.exit(1 if missing else 0)
