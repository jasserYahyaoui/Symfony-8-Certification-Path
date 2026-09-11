# -*- coding: utf-8 -*-
import json, datetime, collections, sys
P = json.load(open(sys.argv[1] if len(sys.argv)>1 else 'docs/revision/plan.json', encoding='utf-8'))
IT = {i['id']: i for i in P['items']}
JOURS = ['lundi','mardi','mercredi','jeudi','vendredi','samedi','dimanche']
MOIS = ['janvier','février','mars','avril','mai','juin','juillet','août',
        'septembre','octobre','novembre','décembre']
def fr(d): return f"{d.day} {MOIS[d.month-1]} {d.year}"

out = []
w = out.append
w("# Calendrier de révision — jour par jour")
w("")
w("Généré depuis les fichiers canoniques par `tools/revision/build_roadmap.py`.")
w("Les durées viennent du corpus mesuré ; le modèle d'effort et ses **hypothèses**")
w("sont dans [`study-roadmap.md`](study-roadmap.md#le-modèle-deffort).")
w("")
w("**Convention.** `NOUVEAU` = première passe. `J+n` = révision espacée de l'item")
w("étudié n jours plus tôt. Les minutes sont un budget, pas un chronomètre : si un")
w("item résiste, la place est prise sur le dimanche de rattrapage, jamais sur les")
w("révisions dues.")
w("")

cur_month = None
for k in sorted(P['days']):
    d = datetime.date.fromisoformat(k); v = P['days'][k]
    if not (v['new'] or v['rev'] or v['lab'] or v['assess'] or v['mock']):
        continue
    if (d.year, d.month) != cur_month:
        cur_month = (d.year, d.month)
        w(f"\n## {MOIS[d.month-1].capitalize()} {d.year}\n")
    w(f"### {JOURS[d.weekday()].capitalize()} {fr(d)}")
    w("")
    if v['mock']:
        name, note = v['mock']
        w(f"- **{name}** — {note}")
    if v['assess']:
        lot = v['assess']
        w(f"- **Assessment {lot} — {P['lot_name'][lot]}** : voir "
          f"[`mastery-checkpoints.md`](mastery-checkpoints.md#{lot})")
    for (iid, mins, full) in v['new']:
        i = IT[iid]
        tag = "NOUVEAU" if full else "NOUVEAU (suite)"
        det = []
        if i['nq']: det.append(f"{i['nq']} questions")
        if i['nfc']: det.append(f"{i['nfc']} flashcards")
        else: det.append("aucune flashcard")
        w(f"- {tag} · **{i['topic']} : {i['name']}** ({i['level']}) — "
          f"{mins} min · {i['words']} mots · " + ", ".join(det))
    if v['lab']:
        w(f"- **Source tour et mise en pratique** sur les {len(v['lab'])} items de la semaine :")
        for iid in v['lab']:
            i = IT[iid]
            w(f"  - {i['topic']} : {i['name']}")
        w("  - méthode : ouvrir les `official_sources` de chaque item dans "
          "`docs/syllabus/syllabus-matrix.yml`, lire le code ou la doc ancrée sur `8.0`, "
          "reproduire le comportement décrit")
    if v['rev']:
        by = collections.defaultdict(list)
        for (iid, off, mins) in v['rev']: by[off].append((IT[iid], mins))
        for off in sorted(by):
            tot = sum(m for _, m in by[off])
            names = " · ".join(f"{i['topic']} : {i['name']}" for i, _ in by[off])
            w(f"- Révision **J+{off}** ({tot} min) — {names}")
    if d.weekday() == 6 and not v['mock']:
        w("- Consolidation : reprendre les questions ratées de la semaine, "
          "rattrapage de ce qui a débordé")
    w("")
    w(f"*Budget du jour : {v['used']} / {v['budget']} min*")
    w("")

open('docs/revision/study-calendar.md', 'w', encoding='utf-8').write("\n".join(out) + "\n")
print('study-calendar.md :', len(out), 'lignes')
