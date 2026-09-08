"""FR-2 pass 5 — the words no witness can judge (spec §9 rule 3).

Passes 1 and 3 used evidence: lots 12+, then the repository's own French. Both
are now exhausted. What remains is vocabulary that appears nowhere else in the
project, so no evidence exists and the only admissible method is reading each
one in its sentence. That is what this file records.

Forms whose correct spelling depends on the sentence are NOT in the table; they
are handled as phrases below, so that `recommande` can be the participle in one
item and the present tense in another without the table having to choose.
"""
import re, sys, pathlib, collections
sys.path.insert(0, str(pathlib.Path(__file__).resolve().parent))
from derive_table import MATRIX, FIELDS  # noqa: E402
from apply_table import ITEM, LOT, KEY, in_scope  # noqa: E402
from witness import WORD  # noqa: E402

# Read one by one on 2026-09-08 in their own sentences.
TABLE = {
    'derouler': 'dérouler', 'procedure': 'procédure', 'reference': 'référence',
    'reduit': 'réduit', 'multi-etapes': 'multi-étapes', 'granularite': 'granularité',
    'fiabilite': 'fiabilité', 'implementee': 'implémentée', 'revision': 'révision',
    'superposes': 'superposés', 'themes': 'thèmes', 'theme': 'thème',
    'visee': 'visée', 'tolerance': 'tolérance', 'taguee': 'taguée',
    'surnumeraires': 'surnuméraires', 'soupconne': 'soupçonne', 'signee': 'signée',
    'sequencement': 'séquencement', 'scenario': 'scénario', 'revisable': 'révisable',
    'repondus': 'répondus', 'reels': 'réels', 'preservant': 'préservant',
    'preferences': 'préférences', 'precises': 'précises', 'pontee': 'pontée',
    'piegeuses': 'piégeuses', 'ordonnees': 'ordonnées', 'opposee': 'opposée',
    'operer': 'opérer', 'negligee': 'négligée', 'necessaires': 'nécessaires',
    'meconnaissance': 'méconnaissance', 'materialisent': 'matérialisent',
    "l'indisponibilite": "l'indisponibilité", "l'impossibilite": "l'impossibilité",
    'imposee': 'imposée', 'imbriquee': 'imbriquée', 'imbrique': 'imbriqué',
    'evident': 'évident', 'etroites': 'étroites', 'etroit': 'étroit',
    'etages': 'étages', 'etabli': 'établi', 'equivalente': 'équivalente',
    'epuisent': 'épuisent', 'enchainement': 'enchaînement', 'emettant': 'émettant',
    'disponibilite': 'disponibilité', 'determination': 'détermination',
    'detailler': 'détailler', 'dependants': 'dépendants', 'depassent': 'dépassent',
    'demontrer': 'démontrer', 'defaillant': 'défaillant', 'defaillance': 'défaillance',
    'dedies': 'dédiés', 'decoration': 'décoration', "d'enchainement": "d'enchaînement",
    "d'element": "d'élément", "d'ambiguite": "d'ambiguïté", "d'agregation": "d'agrégation",
    'cumules': 'cumulés', 'completer': 'compléter', 'categorie': 'catégorie',
    'approprie': 'approprié', 'ameliorer': 'améliorer', 'Tolerer': 'Tolérer',
    'Predire': 'Prédire', 'Delimiter': 'Délimiter', "L'idee": "L'idée",
    'oeuvre': 'œuvre', 'committe': 'committé', 'dispatche': 'dispatché',
    'complete': 'complète', 'documente': 'documenté', 'nom-propriete': 'nom-propriété',
    'ordonne': 'ordonné', 'organise': 'organisé', 'supprime': 'supprimé',
    'modifies': 'modifiés', 'nomme': 'nommé', 'teste': 'testé',
}

# Decided by sentence, because the same form is right both ways in this corpus.
PHRASES = [
    ("ICU recommande, syntaxe", "ICU recommande, syntaxe"),      # present: unchanged
    ("bundle applicatif n'est recommande", "bundle applicatif n'est recommandé"),
]

# Read and deliberately LEFT unaccented; recorded so a later pass cannot
# "helpfully" change them.
KEPT = {
    'caches': 'noun, "leurs caches respectifs"',
    'croit': 'verb croire, "celui qu\'on croit"',
    'devine': 'verb, "ne se devine pas"',
    'relie': 'verb, "— relie l\'item au lot 03"',
    'consulte': 'verb, "Le catalogue se consulte"',
    'modifie': 'verb, "Chaque étage modifie la réponse"',
    'recommande': 'present in one item, participle in another - see PHRASES',
}


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
    stats = collections.Counter()

    def process(block):
        s = block
        for old, new in PHRASES:
            if old == new:
                continue
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
            if w in TABLE:
                stats[w] += 1
                return TABLE[w]
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
    print(f'pass 5: {total} occurrences over {len([k for k in stats if k != "phrase"])} read forms')
    print(f'        {stats["phrase"]} phrase decisions')
    print(f'        {len(KEPT)} forms read and deliberately left unaccented')
    missing = [k for k in TABLE if not stats[k]]
    if missing:
        print(f'\nFAIL: {len(missing)} table entries matched nothing: {missing[:20]}')
        return 1
    if write:
        MATRIX.write_text('\n'.join(out), encoding='utf-8')
        print('written')
    else:
        print('(dry run)')
    return 0


if __name__ == '__main__':
    sys.exit(main('--write' in sys.argv))
