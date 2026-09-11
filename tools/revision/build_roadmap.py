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

# ---------------------------------------------------------------- horaires
# CONVENTION D'AFFICHAGE, PAS UNE MESURE.
#
# Le plan connaît des DURÉES, jamais des heures d'horloge : rien dans les
# fichiers canoniques ne dit à quelle heure le candidat ouvre un cours. Une
# grille d'agenda a pourtant besoin d'un début et d'une fin, et les inventer
# en silence ferait passer une commodité d'affichage pour une donnée du plan.
#
# L'heure de départ est donc dérivée des disponibilités DÉCLARÉES par le
# candidat (1 h à 2 h en semaine, 2 h à 3 h le week-end), posée ici, et
# affichée comme telle sur la page. Les durées, elles, restent mesurées.
DAY_START = {0:'18:00', 1:'18:00', 2:'18:00', 3:'18:00', 4:'18:00',
             5:'09:00', 6:'09:00'}
PAUSE_AFTER = 50   # min de travail continu au-delà desquelles on insère 10 min
PAUSE_MIN   = 10
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
    return days.setdefault(d, {'new':[], 'rev':[], 'lab':None, 'assess':[],
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
# Les mocks d'abord, les corrections ensuite — en DEUX passes.
#
# En une seule passe, la correction du Mock 1 était posée au 29 novembre, puis
# le Mock 2 la remplaçait en s'y installant au tour suivant. Idem pour le
# Mock 3, effacé par le Mock 5. Deux débriefs sur cinq disparaissaient du plan
# sans trace, et le débrief est la partie du mock où l'on apprend.
mock_dates = []
for n,(name, note) in enumerate(MOCKS):
    if slots:
        md = slots[n]
    elif name == 'Mock 4':
        md = max(md, last_study)
        while md.weekday() != 5: md += datetime.timedelta(days=1)
    day(md)['mock'] = (name, note)
    mock_dates.append((name, md))
    if not slots: md += datetime.timedelta(days=7)

for name, md in mock_dates:
    cd_ = md + datetime.timedelta(days=1)
    guard = 0
    while day(cd_)['mock'] is not None:
        cd_ += datetime.timedelta(days=1)
        guard += 1
        assert guard < 60, 'aucun jour libre pour la correction du '+name

    day(cd_)['mock'] = ('Correction ' + name,
        'analyse par item, plan de correction, re-révision ciblée')
# 7. assessments de lot, APRÈS les mocks.
#
#    L'ordre compte : placés avant, ils voyaient les 29 et 30 novembre libres,
#    et les mocks atterrissaient ensuite par-dessus — 259 minutes planifiées
#    sur un budget de 180. La capacité doit être mesurée sur la journée telle
#    qu'elle sera, pas telle qu'elle est à mi-construction.
#    `day(s)['assess'] = lot` écrasait silencieusement : vingt-six lots se
#    terminent, huit assessments survivaient. Sept dimanches en recevaient
#    plusieurs, et celui du 29 novembre en recevait DOUZE — les lots 14 à 26
#    sont courts et se terminent la même semaine. Dix-huit contrôles de fin de
#    lot disparaissaient du plan sans que rien ne le signale, alors que la
#    roadmap en promet vingt-six.
#
#    Ils sont donc reportés plutôt qu'écrasés : chacun cherche le premier jour,
#    à partir de son dimanche d'échéance, dont la capacité restante accepte
#    trente minutes. Un contrôle passé en retard reste un contrôle ; un contrôle
#    écrasé n'existe pas.
ASSESS_MIN = 30

def _capacity(d):
    v = day(d)
    taken = sum(m for _, m, _ in v['new']) \
        + sum(m for _, _, m in v['rev']) \
        + ASSESS_MIN * len(v['assess'])
    if v['mock']:
        taken += 60 if v['mock'][0].startswith('Correction') else 90
    return v['budget'] - taken

for lot, dd in sorted(lot_done_date.items(), key=lambda kv: (kv[1], kv[0])):
    s = dd
    while s.weekday() != 6:
        s += datetime.timedelta(days=1)

    guard = 0
    while _capacity(s) < ASSESS_MIN:
        s += datetime.timedelta(days=1)
        guard += 1
        assert guard < 400, 'aucun jour ne peut accueillir l assessment de '+lot

    day(s)['assess'].append(lot)


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

# ---------------------------------------------------------------- événements
# Chaque jour devient une suite de rendez-vous à heure de début et de fin, pour
# que la grille d'agenda affiche des créneaux plutôt qu'un paragraphe. Le
# découpage suit l'ordre de travail de la journée : mock d'abord s'il y en a un
# (il est chronométré), puis les révisions dues, puis les nouveautés, puis le
# lab et la consolidation.
KIND_ORDER = {'EXAM':0, 'MOCK':1, 'REVIEW':2, 'NEW':3, 'LAB':4, 'ASSESS':5,
              'CONSOLIDATION':6}

def _hhmm(minutes):
    return f'{minutes // 60:02d}:{minutes % 60:02d}'

for k in sorted(days):
    v = days[k]
    blocks = []   # (kind, title, lot, objective, minutes)

    if v['mock']:
        name, note = v['mock']
        if name.startswith('EXAMEN'):
            # L'heure de convocation n'est pas connue de ce dépôt et ne sera
            # pas inventée : la journée porte un jalon, pas un créneau.
            blocks.append(('EXAM', name, None, note, 0, []))
        elif name.startswith('Correction'):
            blocks.append(('MOCK', name, None, note, 60, []))
        else:
            blocks.append(('MOCK', name, None, note, 90, []))

    by_off = collections.defaultdict(list)
    for (it, off, mins) in v['rev']:
        by_off[off].append((it, mins))
    for off in sorted(by_off):
        group = by_off[off]
        mins = sum(m for _, m in group)
        lots = sorted({i['lot'] for i, _ in group})
        blocks.append((
            'REVIEW',
            f'Révision espacée J+{off}',
            lots[0] if len(lots) == 1 else None,
            ' · '.join(f"{i['topic']} : {i['name']}" for i, _ in group),
            mins,
            [i['id'] for i, _ in group]))

    for (it, mins, full) in v['new']:
        det = []
        if it['nq']:
            det.append(f"{it['nq']} questions")
        det.append(f"{it['nfc']} flashcards" if it['nfc'] else 'aucune flashcard')
        blocks.append((
            'NEW',
            ('Nouveau' if full else 'Nouveau (suite)') + f" — {it['name']}",
            it['lot'],
            f"{it['topic']} · {it['level']} · {it['words']} mots · " + ', '.join(det),
            mins,
            [it['id']]))

    if v['lab']:
        # Le lab occupe ce qui RESTE du budget, pas le budget entier : les
        # révisions dues du samedi sont déjà placées, et leur ajouter un bloc
        # plein ferait déborder la journée de son propre budget.
        blocks.append((
            'LAB',
            'Source tour et mise en pratique',
            None,
            f"{len(v['lab'])} items de la semaine : "
            + ' · '.join(i['name'] for i in v['lab']),
            max(0, v['budget'] - sum(b[4] for b in blocks)),
            [i['id'] for i in v['lab']]))

    for lot in v['assess']:
        blocks.append((
            'ASSESS',
            f'Assessment {lot} — {LOT_NAME[lot]}',
            lot,
            'contrôle de maîtrise, analyse des écarts, plan de correction',
            ASSESS_MIN,
            []))

    if k.weekday() == 6:
        blocks.append((
            'CONSOLIDATION',
            'Consolidation et rattrapage',
            None,
            'reprendre les questions ratées de la semaine, rattraper ce qui a débordé',
            max(0, v['budget'] - sum(b[4] for b in blocks)),
            []))

    blocks.sort(key=lambda b: KIND_ORDER[b[0]])

    cursor = int(DAY_START[k.weekday()][:2]) * 60
    since_pause = 0
    evs = []
    for (kind, title, lot, objective, mins, ids) in blocks:
        if mins <= 0:
            continue
        if since_pause >= PAUSE_AFTER and kind != 'EXAM':
            cursor += PAUSE_MIN
            since_pause = 0
        evs.append({'start': _hhmm(cursor), 'end': _hhmm(cursor + mins),
                    'minutes': mins, 'kind': kind, 'title': title,
                    'lot': lot, 'objective': objective, 'items': ids})
        cursor += mins
        since_pause = 0 if kind in ('EXAM', 'MOCK') else since_pause + mins
    v['events'] = evs
    v['used'] = sum(e['minutes'] for e in evs)
    v['milestone'] = None
    if v['mock'] and v['mock'][0].startswith('EXAMEN'):
        v['milestone'] = list(v['mock'])

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
               'events':v.get('events', []),
               'milestone':v.get('milestone'),
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
           'lost_reviews_minutes':lost_min,
           'day_start':DAY_START,
           'lot_order':ORDER,
           # Les paramètres qui ont produit ce plan, publiés avec lui.
           # L'agenda replanifie dans le navigateur quand le candidat change de
           # date ou d'horaires ; il doit le faire avec CES valeurs, jamais avec
           # une copie recopiée à la main dans le TypeScript — une constante
           # dupliquée est une divergence qui attend son heure.
           'params':{'max_new':MAX_NEW,
                     'budget':{str(k):v for k,v in BUDGET.items()},
                     'day_start':DAY_START,
                     'review':REVIEW,
                     'offsets':OFFSETS,
                     'offsets_plus':OFFSETS_PLUS,
                     'assess_min':ASSESS_MIN,
                     'pause_after':PAUSE_AFTER,
                     'pause_min':PAUSE_MIN,
                     'mocks':[[n, note] for n, note in MOCKS]},
           'order':[i['id'] for i in items]},
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
