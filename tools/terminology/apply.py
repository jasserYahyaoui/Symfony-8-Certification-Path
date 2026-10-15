"""Rewrite French prose so Symfony, PHP and HTTP terms stay in English.

The list and the owner's decision are in docs/policy/terminology.yml; CI
enforces it by running this script with --check.

    python3 tools/terminology/apply.py            # rewrite in place
    python3 tools/terminology/apply.py --check    # list, write nothing; exit 1 if any

Scope: course bodies (front matter untouched), French flashcards (front, back,
explanation, memorization_justification), the French fields of the matrix
(content_level_justification, outcome, minimum_evidence) and the « what's
new » pages, and the notes of the FR / EN glossary. Code fences other than ```mermaid, inline code, link targets and
HTML tags are never touched. The rewrite is idempotent.

A replacement that changes the grammatical gender (une étiquette → un tag) is
listed on stdout: the determiner is rewritten, but an adjective after the noun
may still need its agreement fixed by hand.
"""
import glob
import pathlib
import re
import sys

import yaml

ROOT = pathlib.Path(__file__).resolve().parents[2]
TERMS = yaml.safe_load((ROOT / 'docs/policy/terminology.yml').read_text(encoding='utf-8'))['terms']

# \x02 stands for an apostrophe of a single-quoted YAML scalar (written '' on
# disk); \x01 for an apostrophe this script writes. Both are turned back into
# the right form once the scalar's quoting is known.
APOS = "['’\x02]"
DET = (r"(?P<det>\b(?:"
       r"[Ll]es|[Ll]e|[Ll]a|[Ll]" + APOS + r"|[Uu]ne|[Uu]n|[Dd]u|[Dd]e la|[Dd]e [Ll]" + APOS +
       r"|[Dd]es|[Aa]ux|[Aa]u|[Àà] la|[Àà] [Ll]" + APOS +
       r"|[Cc]ette|[Cc]et|[Cc]es|[Cc]e|[Ss]on|[Ss]a|[Ss]es|[Mm]on|[Mm]a|[Mm]es|[Tt]on|[Tt]a"
       r"|[Ll]eurs|[Ll]eur|[Qq]uelle|[Qq]uel|[Cc]haque|[Tt]oute|[Tt]out|[Dd]" + APOS + r")(?: |(?<=['’])))?")

PLURAL_DETS = {'les', 'des', 'ces', 'ses', 'mes', 'leurs', 'aux'}


def plural_form(term, form):
    head = form.split()[0]
    first = term['fr'][0].split()[0]
    return head != first and head[-1] in 'sx'


ENTRIES = []
for t in TERMS:
    for form in t['fr']:
        ENTRIES.append((form, t, plural_form(t, form)))
ENTRIES.sort(key=lambda e: -len(e[0]))

PATTERNS = [
    (re.compile(DET + r"(?P<w>(?<![\w-])" + re.escape(form).replace("'", APOS) + r")(?![\w-])(?P<adj> [a-zàâäéèêëîïôöùûüç]+)?", re.IGNORECASE), t, plural)
    for form, t, plural in ENTRIES
]


def cap(word, like):
    return word[:1].upper() + word[1:] if like[:1].isupper() else word


# Feminine → masculine for the adjective right after a noun whose gender
# changes (une balise fermante → un tag fermant). Only these suffixes are
# touched; an epicene adjective (vide, interne, unique) has none of them.
MASCULINE = [('ées', 'és'), ('ée', 'é'), ('ies', 'is'), ('ie', 'i'), ('ues', 'us'), ('ue', 'u'),
             ('antes', 'ants'), ('ante', 'ant'), ('entes', 'ents'), ('ente', 'ent'),
             ('elles', 'els'), ('elle', 'el'), ('ives', 'ifs'), ('ive', 'if'),
             ('ales', 'aux'), ('ale', 'al'), ('ètes', 'ets'), ('ète', 'et'),
             ('euses', 'eux'), ('euse', 'eux')]
NOT_ADJECTIVES = {'de', 'du', 'des', 'la', 'le', 'les', 'et', 'ou', 'en', 'est', 'qui', 'que', 'dans', 'par',
                  'pour', 'sur', 'avec', 'sans', 'une', 'un', 'à', 'au', 'aux', 'ne', 'se', 'sa', 'son',
                  'reste', 'vie', 'partie', 'sortie', 'entrée', 'valeur', 'requête', 'réponse'}


def masculine(word):
    if word in NOT_ADJECTIVES:
        return word
    for fem, masc in MASCULINE:
        if word.endswith(fem) and len(word) > len(fem) + 2:
            return word[:-len(fem)] + masc
    return word


def render_det(det, gender, elide):
    """Rewrite a singular determiner for the English term's gender and sound."""
    raw = det.strip()
    low = raw.lower().replace('’', "'").replace('\x02', "'")
    m = gender == 'm'
    if low in ("le", "la", "l'"):
        out = "l'" if elide else ('le ' if m else 'la ')
    elif low in ('un', 'une'):
        out = 'un ' if m else 'une '
    elif low in ('du', 'de la', "de l'"):
        out = "de l'" if elide else ('du ' if m else 'de la ')
    elif low in ('au', 'à la', "à l'"):
        out = "à l'" if elide else ('au ' if m else 'à la ')
    elif low in ('ce', 'cet', 'cette'):
        out = ('cet ' if elide else 'ce ') if m else 'cette '
    elif low in ('son', 'sa'):
        out = 'son ' if (m or elide) else 'sa '
    elif low in ('mon', 'ma'):
        out = 'mon ' if (m or elide) else 'ma '
    elif low in ('ton', 'ta'):
        out = 'ton ' if (m or elide) else 'ta '
    elif low in ('quel', 'quelle'):
        out = 'quel ' if m else 'quelle '
    elif low in ('tout', 'toute'):
        out = 'tout ' if m else 'toute '
    elif low == "d'":
        out = "d'" if elide else 'de '
    else:
        return det
    return cap(out, raw).replace("'", '\x01')


def rewrite(text, report, where):
    for pattern, t, plural in PATTERNS:
        def sub(m):
            start, end = m.start('w'), m.end('w')
            window = text_ref[0][max(0, start - 40):end + 40]
            for keep in t.get('keep', []):
                if re.search(keep, window, re.IGNORECASE):
                    return m.group(0)
            det = m.group('det') or ''
            if t.get('needs_determiner') and not det:
                return m.group(0)
            word = m.group('w')
            adj = m.group('adj') or ''
            en = t['en_plural'] if plural else t['en']
            en = cap(en, word) if not det else en
            if det and not plural and det.strip().lower() not in PLURAL_DETS:
                new_det = render_det(det, t['gender'], t.get('elide', False))
            else:
                new_det = det
            if t.get('fr_gender', t['gender']) != t['gender']:
                if adj:
                    adj = ' ' + masculine(adj[1:])
                if not plural:
                    report.append(f'{where}: …{text_ref[0][max(0, m.start() - 30):end + 40].replace(chr(10), " ")}… → {new_det + en + adj}')
            return new_det + en + adj
        text_ref = [text]
        text = pattern.sub(sub, text)
    return text


# A fenced block, from its opening line to its closing one. Matched as a whole,
# so the closing ``` of one block can never be taken for the opening of the
# next: until 2026-10-15 a single pattern excluded ```mermaid by a lookahead and
# then read the CLOSING fence of a diagram as an opening one, protecting all the
# prose after it up to the next fence. « écouteur » and « formulaire » survived
# that way under the form-events diagram.
FENCE = re.compile(r"(^```[^\n]*\n.*?^```[ \t]*$)", re.DOTALL | re.MULTILINE)

PROTECT = re.compile(
    r"(`[^`\n]*`"                       # inline code
    r"|\]\([^)]*\)"                     # link targets
    r"|<[^>\n]+>"                       # HTML tags
    r"|https?://\S+)",                  # bare URLs
    re.DOTALL,
)


def rewrite_inline(text, report, where):
    parts = PROTECT.split(text)
    return ''.join(p if i % 2 else rewrite(p, report, where) for i, p in enumerate(parts))


def rewrite_prose(text, report, where, single_quoted=False):
    if single_quoted:
        text = text.replace("''", '\x02')
    out = ''
    for i, part in enumerate(FENCE.split(text)):
        if not i % 2:
            out += rewrite_inline(part, report, where)
        elif part.startswith('```mermaid'):
            # A diagram's labels are prose the learner reads: rewritten too,
            # its fence lines and directives (accTitle: …) left as they are.
            first, _, rest = part.partition('\n')
            out += first + '\n' + rewrite_inline(rest, report, where)
        else:
            out += part
    apostrophe = "''" if single_quoted else "'"
    return out.replace('\x02', apostrophe).replace('\x01', apostrophe)


HEADING = re.compile(r'^## (.+?)\s*$', re.MULTILINE)


def headings(body):
    return HEADING.findall(FENCE.sub('', body))


def course(path, report):
    s = path.read_text(encoding='utf-8')
    if not s.startswith('---\n'):
        return s
    end = s.index('\n---\n', 4) + 5
    if 'language: fr' not in s[:end]:
        return s
    front, body = s[:end], s[end:]
    new_body = rewrite_prose(body, report, path.name)
    # A renamed heading carries its outcome_sections entries with it (rule
    # SEC-001): the map names headings, and the headings keep their order.
    before, after = headings(body), headings(new_body)
    if len(before) == len(after):
        for old, new in zip(before, after):
            if old != new:
                front = front.replace(': "' + old + '"\n', ': "' + new + '"\n')
    return front + new_body


KEY = re.compile(r"^(?P<ind>\s*)(?P<dash>- )?(?P<key>[A-Za-z_]+):(?: (?P<rest>.*))?$")


def yaml_fields(path, fields, report, entry_marker, language_gate):
    """Rewrite the listed scalar fields of a YAML file, line by line.

    The layout is kept as is. A field runs from its key line through every
    following line indented deeper than the key, so a prose line that happens
    to contain « mot: » is not taken for a key.
    """
    lines = path.read_text(encoding='utf-8').split('\n')
    starts = [i for i, l in enumerate(lines) if entry_marker.match(l)] + [len(lines)]
    french = set()
    for a, b in zip(starts, starts[1:]):
        block = '\n'.join(lines[a:b])
        if not language_gate or re.search(r'^\s*language: fr\s*$', block, re.M):
            french.update(range(a, b))
    current, column, quoted = None, -1, None
    for i, line in enumerate(lines):
        indent = len(line) - len(line.lstrip(' '))
        if current and line.strip() and indent > column:
            if i in french:
                lines[i] = transform_scalar(line, quoted, report, path.name)
            continue
        m = KEY.match(line)
        if not m:
            if line.strip():
                current = None
            continue
        column = len(m.group('ind')) + (2 if m.group('dash') else 0)
        current = m.group('key') if m.group('key') in fields else None
        rest = (m.group('rest') or '').strip()
        quoted = rest[:1] if rest[:1] in ("'", '"') else None
        if current and i in french and rest:
            prefix = line[:line.index(m.group('key') + ':') + len(m.group('key')) + 1]
            lines[i] = prefix + transform_scalar(line[len(prefix):], quoted, report, path.name)
    return '\n'.join(lines)


def transform_scalar(text, quoted, report, where):
    return rewrite_prose(text, report, where, single_quoted=quoted == "'")


def main():
    check = '--check' in sys.argv
    report = []
    changed = {}
    for p in sorted((ROOT / 'content/courses').glob('*.md')) + sorted((ROOT / 'docs/whats-new').glob('*.md')):
        before = p.read_text(encoding='utf-8')
        after = course(p, report) if p.parent.name == 'courses' else whats_new(p, report)
        if after != before:
            changed[p] = after
    flash = re.compile(r'^\s*- id: FLC-')
    for p in sorted((ROOT / 'content/flashcards').glob('*.yml')):
        before = p.read_text(encoding='utf-8')
        after = yaml_fields(p, {'front', 'back', 'explanation', 'memorization_justification'}, report, flash, True)
        if after != before:
            changed[p] = after
    p = ROOT / 'docs/syllabus/syllabus-matrix.yml'
    before = p.read_text(encoding='utf-8')
    after = yaml_fields(p, {'content_level_justification', 'outcome', 'minimum_evidence'}, report, re.compile(r'^  - id: OIT-'), False)
    if after != before:
        changed[p] = after
    # The glossary's « fr » column is the French rendering on purpose; only its
    # notes are prose, and they follow the courses.
    p = ROOT / 'docs/syllabus/glossary.yml'
    before = p.read_text(encoding='utf-8')
    after = yaml_fields(p, {'note'}, report, re.compile(r'^  - en: '), False)
    if after != before:
        changed[p] = after
    for line in report:
        print('GENDER', line)
    for p, text in changed.items():
        if check:
            print('FRENCH TERM', p.relative_to(ROOT))
        else:
            p.write_text(text, encoding='utf-8')
    print(f'{len(changed)} file(s) {"would change" if check else "changed"}, {len(report)} gender change(s) to review')
    # --check is the guard CI runs: a listed French form back in the prose fails
    # the build, and running the script without --check is the fix.
    return 1 if check and changed else 0


def whats_new(path, report):
    s = path.read_text(encoding='utf-8')
    if s.startswith('---\n'):
        end = s.index('\n---\n', 4) + 5
        return s[:end] + rewrite_prose(s[end:], report, path.name)
    return rewrite_prose(s, report, path.name)


sys.exit(main())
