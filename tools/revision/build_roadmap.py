# -*- coding: utf-8 -*-
"""Construit le plan de révision candidat depuis les fichiers canoniques.

Les VOLUMES sont mesurés (mots de corps de cours, nombre de questions hors
HOLDOUT, nombre de flashcards, estimated_time_seconds). Les TAUX DE CONVERSION
en minutes sont des hypothèses, isolées ci-dessous pour être corrigées après une
semaine de mesure réelle sur le candidat.

    python3 tools/revision/build_roadmap.py [--start AAAA-MM-JJ]
    python3 tools/revision/render_calendar.py

Le premier écrit le plan en JSON, le second en rend docs/revision/study-calendar.md.
Ne corrigez jamais le calendrier à la main : régénérez-le.
"""
import argparse, os, sys
import yaml, glob, re, collections, datetime, math, json

# ---------------------------------------------------------------- paramètres
WPM          = 110   # HYPOTHÈSE : mots/min, 1re lecture attentive, FR + code
Q_FACTOR     = 1.6   # HYPOTHÈSE : × estimated_time_seconds, pour lire l'explication
FC_SEC       = 45    # HYPOTHÈSE : s par flashcard, 1re passe
MAX_NEW      = 3     # DÉCISION : nouveautés max par jour de semaine, pour un profil 4/10
BUDGET       = {0:90, 1:90, 2:90, 3:90, 4:90, 5:150, 6:150}   # lundi=0 … dimanche=6
REVIEW = {  # minutes, par niveau, par échéance
 'MINIMAL' : {1:4, 3:3, 7:3, 14:2, 30:2, 45:2, 60:2},
 'STANDARD': {1:6, 3:4, 7:4, 14:3, 30:3, 45:3, 60:3},
 'DEEP'    : {1:8, 3:5, 7:5, 14:4, 30:4, 45:4, 60:4},
}
OFFSETS      = [1, 3, 7, 14, 30]
OFFSETS_PLUS = [1, 3, 7, 14, 30, 45, 60]   # items transverses / DEEP
_ap = argparse.ArgumentParser(description=__doc__)
_ap.add_argument('--start', default='2026-10-01', help='premier jour du plan')
_ap.add_argument('--out', default='docs/revision/plan.json')
_ap.add_argument('--max-new', type=int, default=None, help='nouveautés max par jour de semaine')
_ap.add_argument('--weekday', type=int, default=None, help='budget minutes lundi-vendredi')
_ap.add_argument('--weekend', type=int, default=None, help='budget minutes samedi-dimanche')
_ap.add_argument('--exam', default=None, help="date d'examen AAAA-MM-JJ : les mocks sont calés avant")
_args = _ap.parse_args()
START        = datetime.date.fromisoformat(_args.start)
if _args.max_new: MAX_NEW = _args.max_new
if _args.weekday: BUDGET.update({i: _args.weekday for i in range(5)})
if _args.weekend: BUDGET.update({5: _args.weekend, 6: _args.weekend})
EXAM = datetime.date.fromisoformat(_args.exam) if _args.exam else None

# ordre pédagogique : prérequis, puis structurants, puis le reste
ORDER = ['lot-01','lot-02','lot-03','lot-04','lot-05','lot-09','lot-08','lot-07',
         'lot-10','lot-11','lot-06','lot-12','lot-13',
         'lot-14','lot-15','lot-16','lot-17','lot-18','lot-19','lot-20',
         'lot-21','lot-22','lot-23','lot-24','lot-25','lot-26']
TRANSVERSE_LOTS = {'lot-01','lot-03','lot-09'}   # prérequis de plusieurs lots

LOT_NAME = {}

# ---------------------------------------------------------------- données
M = yaml.safe_load(open('docs/syllabus/syllabus-matrix.yml',encoding='utf-8'))['items']
QS = [q for f in sorted(glob.glob('content/questions/*.yml'))
      for q in yaml.safe_load(open(f,encoding='utf-8'))['questions']]
byq = collections.defaultdict(list)
for q in QS:
    if q['pool'] != 'HOLDOUT': byq[q.get('official_item')].append(q)
words = {}
for f in glob.glob('content/courses/*.md'):
    s = open(f,encoding='utf-8').read()
    cid = re.search(r'id:\s*(CRS-\w+)', s)
    if cid: words[cid.group(1)] = len(re.findall(r'\S+', s.split('---',2)[-1]))
fc = collections.Counter()
for f in glob.glob('content/flashcards/*.yml'):
    for c in (yaml.safe_load(open(f,encoding='utf-8')).get('flashcards') or []):
        fc[c.get('official_item')] += 1

items = []
for lot in ORDER:
    for i in M:
        if i['lot'] != lot: continue
        LOT_NAME[lot] = i['official_topic']
        w  = sum(words.get(c,0) for c in i['course_refs'])
        qq = byq[i['id']]
        c_min  = math.ceil(w / WPM)
        q_min  = math.ceil(sum(x['estimated_time_seconds'] for x in qq) / 60 * Q_FACTOR)
        f_min  = math.ceil(fc.get(i['id'],0) * FC_SEC / 60)
        items.append(dict(id=i['id'], lot=lot, topic=i['official_topic'],
                          name=i['official_item'], level=i['content_level'],
                          words=w, nq=len(qq), nfc=fc.get(i['id'],0),
                          c=c_min, q=q_min, f=f_min, total=c_min+q_min+f_min,
                          deep=i['content_level']=='DEEP',
                          transverse=(lot in TRANSVERSE_LOTS) or i['content_level']=='DEEP'))
assert len(items) == 163, len(items)

# ---------------------------------------------------------------- ordonnancement
days = collections.OrderedDict()
reviews = collections.defaultdict(list)      # date -> [(item, offset, minutes)]
def day(d):
    return days.setdefault(d, {'new':[], 'rev':[], 'lab':None, 'assess':None,
                               'mock':None, 'used':0, 'budget':BUDGET[d.weekday()]})

queue = list(items)
cur = None; remaining = {}
d = START
introduced_this_week = []
lot_done_date = {}
guard = 0
while queue or any(k >= d for k in reviews):
    guard += 1
    assert guard < 2000, 'boucle non bornée'
    D = day(d); wd = d.weekday()
    # 1. révisions dues — obligations à date fixe
    for (it, off, mins) in reviews.pop(d, []):
        D['rev'].append((it, off, mins)); D['used'] += mins
    # 2. samedi : source tour, pas de nouvel item
    if wd == 5:
        if introduced_this_week:
            D['lab'] = list(introduced_this_week)
            D['used'] = max(D['used'], D['budget'])
        introduced_this_week = []
    # 3. dimanche : consolidation / rattrapage, pas de nouvel item
    elif wd == 6:
        D['used'] = max(D['used'], D['budget'])
    # 4. lundi-vendredi : nouveaux items
    else:
        while queue and D['used'] < D['budget'] and len([x for x in D['new'] if x[2]]) < MAX_NEW:
            it = queue[0]
            left = remaining.get(it['id'], it['total'])
            free = D['budget'] - D['used']
            if left <= free:
                D['new'].append((it, left, left == it['total']))
                D['used'] += left; remaining.pop(it['id'], None); queue.pop(0)
                introduced_this_week.append(it)
                offs = OFFSETS_PLUS if it['transverse'] else OFFSETS
                for off in offs:
                    reviews[d + datetime.timedelta(days=off)].append(
                        (it, off, REVIEW[it['level']][off]))
                if not queue or queue[0]['lot'] != it['lot']:
                    lot_done_date[it['lot']] = d
            elif free >= 20:
                D['new'].append((it, free, left == it['total']))
                remaining[it['id']] = left - free; D['used'] += free
                if it not in introduced_this_week: introduced_this_week.append(it)
                break
            else:
                break
    d += datetime.timedelta(days=1)

last_study = max(days)
# 5. assessments de lot : le dimanche suivant la fin du lot
for lot, dd in lot_done_date.items():
    s = dd
    while s.weekday() != 6: s += datetime.timedelta(days=1)
    day(s)['assess'] = lot

# 6. mocks : dès que TOUS les lots sont introduits (exigence 4), un par week-end.
#    Le tail de révisions J+30/J+45/J+60 continue en parallèle : il occupe la
#    semaine, le mock occupe le samedi et sa correction le dimanche.
all_items_in = max(lot_done_date.values())
MOCKS = [('Mock 1','61 questions — premier étalonnage, sans enjeu'),
         ('Mock 2','84 questions'),
         ('Mock 3','68 questions'),
         ('Mock 5','tirage dans les 519 questions éligibles'),
         ('Mock 4','75 questions / 90 min — holdout, format fixé par §10, une seule fois')]
if EXAM:
    # Exigence : aucun mock avant que TOUS les lots soient étudiés. Avec une date
    # d'examen imposée, le nombre de week-ends restants peut être inférieur au
    # nombre de mocks — on en place alors deux par week-end plutôt que d'entamer
    # la période d'étude. Mock 4 reste seul et dernier.
    m4 = EXAM - datetime.timedelta(days=1)
    while m4.weekday() != 5: m4 -= datetime.timedelta(days=1)
    free = []
    d0 = all_items_in + datetime.timedelta(days=1)
    while d0 < m4:
        if d0.weekday() >= 5: free.append(d0)
        d0 += datetime.timedelta(days=1)
    need = len(MOCKS) - 1
    if len(free) < need:
        raise SystemExit(
            f'Pas de place : {need} mocks à caser entre {all_items_in} et {m4}, '
            f'{len(free)} jours de week-end disponibles. Avancer la fin des lots '
            f'(--max-new plus haut) ou reculer la date d examen.')
    slots = free[-need:] + [m4]
else:
    md = all_items_in + datetime.timedelta(days=1)
    while md.weekday() != 5: md += datetime.timedelta(days=1)
    slots = None
md = all_items_in + datetime.timedelta(days=1)
while md.weekday() != 5: md += datetime.timedelta(days=1)
mock_dates = []
for n,(name, note) in enumerate(MOCKS):
    if slots:
        md = slots[n]
    elif name == 'Mock 4':
        md = max(md, last_study)
        while md.weekday() != 5: md += datetime.timedelta(days=1)
    day(md)['mock'] = (name, note)
    day(md + datetime.timedelta(days=1))['mock'] = ('Correction ' + name,
        'analyse par item, plan de correction, re-révision ciblée')
    mock_dates.append((name, md))
    if not slots: md += datetime.timedelta(days=7)
last_day = max(days)

# 7. Troncature à la date d'examen.
#
#    Le calendrier courait jusqu'au 2027-01-09 pour un examen le 2026-12-15 :
#    vingt-cinq jours de révisions J+45/J+60 planifiées APRÈS l'épreuve. Une
#    révision qui tombe après l'examen n'est pas une révision, et la laisser au
#    plan gonfle la charge affichée d'un travail qui ne peut servir à rien.
#
#    Elles ne sont pas supprimées en silence : chacune est comptée et reversée
#    dans `lost_reviews`, parce que la perte est le coût réel de la date choisie
#    et que le candidat doit pouvoir le lire. Le jour de l'examen lui-même ne
#    porte aucune révision — il porte l'épreuve.
lost_reviews = []
if EXAM:
    for k in sorted(days):
        if k < EXAM:
            continue
        for (it, off, mins) in days[k]['rev']:
            lost_reviews.append((it, off, mins, k))
        if k == EXAM:
            days[k]['rev'] = []
            days[k]['used'] = 0
    for k in [k for k in days if k > EXAM]:
        del days[k]
    D = day(EXAM)
    D['mock'] = ('EXAMEN Symfony 8.0',
                 "jour de l'épreuve — aucune révision n'est planifiée")
    D['used'] = 0
    last_day = max(days)
    studied = [k for k,v in days.items()
               if v['new'] or v['rev'] or v['lab'] or v['assess']]
    last_study = max(studied) if studied else last_day

lost_by_off = collections.Counter()
lost_min = 0
for (it, off, mins, k) in lost_reviews:
    lost_by_off[off] += 1
    lost_min += mins

json.dump({'items':items,
           'days':{k.isoformat():{
               'new':[(i['id'],m,full) for i,m,full in v['new']],
               'rev':[(i['id'],o,m) for i,o,m in v['rev']],
               'lab':[i['id'] for i in (v['lab'] or [])],
               'assess':v['assess'], 'mock':v['mock'],
               'used':v['used'], 'budget':v['budget']} for k,v in days.items()},
           'lot_done':{k:v.isoformat() for k,v in lot_done_date.items()},
           'lot_name':LOT_NAME,
           'mock_dates':[(n,d.isoformat()) for n,d in mock_dates],
           'all_items_in':all_items_in.isoformat(),
           'start':START.isoformat(), 'last_study':last_study.isoformat(),
           'last_day':last_day.isoformat(),
           'exam':EXAM.isoformat() if EXAM else None,
           'lost_reviews':[(i['id'],o,m,k.isoformat()) for i,o,m,k in lost_reviews],
           'lost_reviews_by_offset':{str(o):n for o,n in sorted(lost_by_off.items())},
           'lost_reviews_minutes':lost_min},
          open(_args.out,'w',encoding='utf-8'), ensure_ascii=False)

tot_first = sum(i['total'] for i in items)
tot_rev   = sum(REVIEW[i['level']][o] for i in items
                for o in (OFFSETS_PLUS if i['transverse'] else OFFSETS))
print(f"items {len(items)} | 1re passe {tot_first} min = {tot_first/60:.1f} h")
print(f"  dont cours {sum(i['c'] for i in items)/60:.1f} h · questions {sum(i['q'] for i in items)/60:.1f} h · flashcards {sum(i['f'] for i in items)/60:.1f} h")
print(f"révisions espacées {tot_rev} min = {tot_rev/60:.1f} h")
print(f"jours planifiés {len(days)} | début {START}")
print(f"tous les items introduits : {all_items_in}")
print(f"fin du tail de révisions  : {last_study}")
for n,dd in mock_dates: print(f"  {n:8} {dd}")
print(f"dernier jour planifié     : {last_day}")
if EXAM:
    print(f"examen                    : {EXAM}")
    print(f"révisions perdues (après l examen) : {len(lost_reviews)} = {lost_min} min "
          f"= {lost_min/60:.1f} h")
    for o,n in sorted(lost_by_off.items()):
        print(f"  J+{o:<3} {n}")
# `tot_rev` compte toutes les révisions engendrées par le modèle, y compris
# celles que la date d'examen fait tomber. La charge réellement planifiée est
# la différence : afficher `tot_first + tot_rev` surestimerait le travail à
# faire d'exactement les heures qu'on ne peut pas faire.
print(f"charge totale engendrée par le modèle (hors mocks) {(tot_first+tot_rev)/60:.1f} h")
print(f"charge réellement planifiée     (hors mocks) {(tot_first+tot_rev-lost_min)/60:.1f} h")
