# Roadmap de révision — candidat Symfony 8.0

**Profil déclaré** : 4/10 sur tous les domaines · 1 h à 2 h en semaine · 2 h à 3 h
le samedi · 2 h à 3 h le dimanche · aucune indisponibilité · apprentissage en
français, termes techniques en anglais.

**Départ** : 1er octobre 2026. **Examen visé** : **15 décembre 2026**.
**Objectif** : passer la certification du premier coup — un `PASS`, pas un score
maximal.

---

## Avertissement de vocabulaire, à lire avant tout le reste

Ce dépôt emploie déjà `EXAM_READY`, et **cela ne veut pas dire ce que vous
croyez**. `EXAM_READY` est un statut de **contenu** : il dit qu'un item du
syllabus a un cours, ses questions et ses sources vérifiées. Les 163 items sont
`EXAM_READY` **depuis le 8 septembre 2026**. Cela ne dit rien de vous.

Votre préparation se mesure avec un autre indicateur, défini dans
[`exam-readiness.md`](exam-readiness.md) et appelé ici **PRÊT-CANDIDAT**, pour
qu'aucun document ne laisse croire qu'un dépôt à 100 % de couverture signifie un
candidat prêt.

---

## Le modèle d'effort

Les volumes viennent des fichiers canoniques, mesurés par script. Les **taux de
conversion en minutes sont des hypothèses** : ils sont isolés en haut du
générateur pour que vous puissiez les corriger après une semaine de mesure
réelle sur vous-même.

| Paramètre | Valeur | Statut |
|---|---|---|
| Vitesse de lecture | 110 mots/min | **hypothèse** — 1re lecture attentive, français avec blocs de code |
| Temps par question | `estimated_time_seconds` × 1,6 | **mesuré × hypothèse** — le champ est réel, le facteur 1,6 couvre la lecture de l'explication |
| Temps par flashcard | 45 s | **hypothèse**, 1re passe |
| Nouveautés par jour de semaine | **4 maximum** | **décision**, justifiée ci-dessous |
| Budget semaine / week-end | **120 min / 180 min** | **décision** — le haut de votre fourchette déclarée |

**Pourquoi quatre nouveautés par jour, ni trois ni six.** Sans plafond, le
générateur tenait les 163 items en tenant le budget horaire — mais en plaçant
**sept notions PHP le premier jour**, soit douze minutes par notion. Pour un
profil 4/10, ce n'est pas de l'apprentissage.

Le chiffre exact est décidé par l'examen du 15 décembre, pas par le confort.
Le plan complet programme 901 révisions espacées ; la date en rend une partie
impossible, et le rythme décide laquelle :

| Rythme | Fin des lots | Révisions perdues | Détail |
|---|---|---:|---|
| 3/jour, 1 h 30 | 18 déc. | **190 / 901** | dont 13 J+1 et 13 J+3 |
| 3/jour, 2 h | 16 déc. | 166 / 901 | dont 7 J+1 et 7 J+3 |
| **4/jour, 2 h** | **27 nov.** | **59 / 901** | **35 J+30, 24 J+45/J+60** |
| 5/jour, 2 h | 20 nov. | 41 / 901 | 19 J+30, 22 J+45/J+60 |
| 6/jour, 2 h | 18 nov. | 32 / 901 | 12 J+30, 20 J+45/J+60 |

**Quatre est le rythme le plus doux qui ne perd aucun J+1, J+3, J+7 ni J+14.**
Les deux premières lignes sacrifient des J+1 et J+3 — les échéances qui séparent
une notion vue d'une notion apprise. Perdre un J+30 coûte de la consolidation ;
perdre un J+1 coûte la notion. Cinq et six ne rachètent que 18 à 27 J+30 de plus,
pour un rythme nettement plus dur.

### Volumes mesurés

| | Mesure |
|---|---|
| Items officiels | **163** |
| Mots de corps de cours | **71 578** |
| Questions hors HOLDOUT | **519** |
| Flashcards | **174** |
| Items sans aucune flashcard | **0 sur 163** — 37 avant PED-010 |
| Items avec un exercice | **0 sur 163** |

### Charge de travail

| Poste | Plan complet | Chemin minimal |
|---|---:|---:|
| Première passe (cours 12,2 + questions 13,8 + flashcards 2,9) | 28,9 h | 28,7 h |
| Révisions espacées **engendrées par le modèle** | 57,3 h | 37,0 h |
| *dont tombant après le 15 décembre — non planifiables* | *−3,1 h* | — |
| Révisions espacées **réellement planifiées** | **54,2 h** | 37,0 h |
| Assessments de lot (26 × 30 min) | 13,0 h | 13,0 h |
| Mocks (5 × 90 min + correction) | 12,5 h | 12,5 h |
| **Sous-total : le travail sur le contenu** | **108,6 h** | **91,2 h** |
| Source tours du samedi | 19,2 h | — |
| Consolidation et rattrapage du dimanche | 15,8 h | — |
| **Total occupé au calendrier** | **143,6 h** | — |

Les 3,1 h retranchées sont les **59 révisions** que la date d'examen rend
impossibles. Elles sont soustraites plutôt que comptées : un total qui les
inclut annonce un travail que le calendrier ne contient pas, et ce n'est pas
une approximation favorable — c'est un chiffre faux. Le calendrier s'arrête
donc au 15 décembre, et le détail des pertes est en tête de
[`study-calendar.md`](study-calendar.md).

**Deux totaux, et il faut les deux.** 108,6 h est le travail sur le contenu :
lire, répondre, réviser, s'évaluer. 143,6 h est ce que le plan **occupe
réellement** de vos disponibilités, parce que les samedis et les dimanches sont
donnés en entier au source tour et à la consolidation. Ne retenir que le premier
chiffre sous-estimerait de **35 h** ce que le calendrier vous demande — c'est
précisément l'écart qu'on ne découvre qu'en décembre. Le second est celui que
l'**agenda** affiche, créneau par créneau — la page *Agenda* de la barre de
navigation du site.

La première passe ne pèse que **29 h sur 109**. Le reste, c'est la rétention.
C'est le résultat le plus utile de ce chiffrage : **votre calendrier n'est pas
contraint par la lecture des cours, il est contraint par les révisions.** Sauter
des révisions pour « avancer » est exactement le mauvais arbitrage.

---

## Deux constats du corpus qui contraignent ce plan

**Aucun exercice n'existe.** Le champ `exercise_refs` est vide sur les 163 items.
Un samedi « labs et exercices » planifierait du matériel inexistant. Le samedi est
donc bâti sur ce qui existe réellement : les `official_sources` de chaque item,
des URL ancrées sur la branche `8.0` de `symfony/symfony` et `symfony/symfony-docs`.
C'est un **source tour auto-dirigé**, pas un exercice corrigé. La différence est
importante : personne ne vous dira que vous vous êtes trompé.

**Les 37 items sans flashcard ont été comblés.** PED-010 a porté la couverture
de 126/163 à **163/163** : chaque item dispose désormais d'au moins une carte
exploitable, et la règle `FLC-002` empêche la lacune de revenir. Le plan ci-dessous
intègre les 174 cartes.

---

## L'ordre des lots, et pourquoi celui-là

L'ordre du plan n'est **pas** l'ordre des numéros de lot. Il suit les
dépendances réelles, puis le caractère structurant.

| # | Lot | Raison de la position |
|---:|---|---|
| 1 | 01 — PHP | Prérequis de langage. Attributes, Enums, Interfaces et Closures reviennent dans presque tous les lots Symfony. |
| 2 | 02 — HTTP | Prérequis de HttpFoundation, Controllers et Routing. On ne comprend pas `Response` sans les codes de statut. |
| 3 | 03 — Symfony Architecture | **La colonne vertébrale.** HttpKernel, les événements du noyau, le cycle requête/réponse. Tout le reste s'y accroche. |
| 4 | 04 — Controllers | Applique directement 02 et 03. |
| 5 | 05 — Routing | Complète Controllers. |
| 6 | 09 — Dependency Injection | **Prérequis de Security, Messenger, Forms et de tout service.** Placé avant eux, pas après. |
| 7 | 08 — Data Validation | Prérequis conceptuel des Forms : les contraintes s'apprennent avant leur usage. |
| 8 | 07 — Forms | S'appuie sur 08 et 09. |
| 9 | 10 — Security | S'appuie sur 03 (événements) et 09 (services). Le lot le plus dense en concepts. |
| 10 | 11 — Messenger | S'appuie sur 09. |
| 11 | 06 — Templating with Twig | Autonome ; placé après parce qu'il n'est prérequis de rien. |
| 12 | 12 — Console | Autonome. |
| 13 | 13 — Automated Tests | Autonome, et profite d'avoir déjà vu le reste. |
| 14–26 | 14 à 26 — Miscellaneous | 19 items courts, 3,6 h au total. Vidés en fin de parcours. |

Les onze sujets que vous avez nommés comme structurants sont **tous dans les dix
premiers lots** de cette séquence. Ce n'est pas une pondération d'examen — le
projet n'en connaît aucune d'officielle et n'en invente pas — c'est une
conséquence des dépendances.

---

## Révision espacée : le barème appliqué

Chaque item étudié déclenche automatiquement **J+1, J+3, J+7, J+14, J+30**.

| Niveau de l'item | J+1 | J+3 | J+7 | J+14 | J+30 |
|---|---:|---:|---:|---:|---:|
| `MINIMAL` | 4 min | 3 | 3 | 2 | 2 |
| `STANDARD` | 6 min | 4 | 4 | 3 | 3 |
| `DEEP` | 8 min | 5 | 5 | 4 | 4 |

### Révisions renforcées (exigence : transverses, prérequis multiples, forte densité)

Deux échéances supplémentaires, **J+45 et J+60**, sont ajoutées aux items qui
sont soit prérequis de plusieurs lots, soit à forte densité conceptuelle :

- **tout le lot 01 (PHP)** — prérequis de langage de tous les lots Symfony ;
- **tout le lot 03 (Symfony Architecture)** — HttpKernel et les événements du
  noyau sont réutilisés par Controllers, Security et Messenger ;
- **tout le lot 09 (Dependency Injection)** — prérequis de Security, Messenger,
  Forms, et de tout item qui parle de service ;
- **les 11 items `DEEP` du syllabus**, quel que soit leur lot :

| Lot | Item `DEEP` |
|---|---|
| 03 | Request handling · Event dispatcher and kernel events |
| 04 | Argument value resolvers |
| 06 | Twig syntax up to 3.22 version |
| 07 | Form events |
| 08 | Group sequence |
| 09 | Compiler passes · Services autowiring |
| 10 | Authenticators, Passports and Badges · Voters and voting strategies |
| 11 | Retries and failures |

---

## Rythme hebdomadaire

| Jour | Contenu | Budget planifié |
|---|---|---:|
| Lundi à vendredi | Cours, questions, flashcards + révisions dues | **120 min** |
| Samedi | Source tour sur les items de la semaine, lecture du code Symfony `8.0` | **180 min** |
| Dimanche | Consolidation, révisions dues, rattrapage, assessment de lot | **180 min** |

Le budget planifié est de **12,4 h par semaine en médiane**, avec un pic à
**14,7 h**. Sept semaines dépassent les 12,5 h de votre fourchette médiane, de
+1,2 h à +2,2 h — soit environ **25 minutes de plus par jour** sur ces
semaines-là. Elles sont nommées une par une dans
[`exam-readiness.md`](exam-readiness.md#les-sept-semaines-qui-dépassent-votre-disponibilité-déclarée),
pour que vous puissiez les bloquer à l'avance plutôt que les découvrir.

La marge est mince : c'est le prix de l'examen au 15 décembre. Les trois
dernières semaines retombent à 4–6 h, l'étude étant terminée.

**Les nouveautés ne sont introduites qu'en semaine.** Le samedi et le dimanche
n'ajoutent jamais de notion nouvelle — ils la font tenir.

---

## Stratégie de réussite minimale

L'objectif est un `PASS`, pas un score. Cela se traduit par trois règles.

**1. Une bonne réponse suffit ; une notion à moitié sue ne sert à rien.** Mieux
vaut 130 items solides et 33 items faibles identifiés que 163 items flous.
L'assessment de fin de lot sert exactement à faire ce tri.

**2. Les items `MINIMAL` se révisent, ils ne s'approfondissent pas.** Le projet
leur a assigné ce niveau parce que le sujet s'y réduit à quelques faits. Y passer
plus de temps que le budget est du temps volé aux items `DEEP`.

**3. Le seuil de passage n'est pas connu de ce projet.** Aucun document ne cite
de score minimal officiel, et aucun n'est inventé ici. Le critère
PRÊT-CANDIDAT retenu est donc un critère **interne**, défini et justifié dans
[`exam-readiness.md`](exam-readiness.md).

---

## Le chemin le plus court, et pourquoi il ne s'applique plus

Avant que la date du 15 décembre ne soit fixée, ce document proposait un
« chemin minimal » : garder les 163 items mais ne conserver que J+1, J+3 et J+7,
pour économiser 20 h et deux semaines.

**Cette option est caduque, et il vaut mieux le dire que la laisser traîner.**
La date impose déjà un sacrifice de révisions — 59 sur 901 — et il est choisi :
il porte sur les J+30 et sur le renfort J+45/J+60, jamais sur les échéances
courtes. Ajouter le chemin minimal par-dessus reviendrait à supprimer aussi les
J+14 et les J+30 restants, soit à cumuler deux réductions dont la seconde ne
rachète plus rien : le calendrier n'est plus le facteur limitant, la date l'est.

Ce qui reste vrai : **100 % de couverture du syllabus est conservé dans tous les
cas**. Les 163 items sont étudiés. Aucun raccourci envisagé ici n'a jamais porté
sur l'étendue.

Si la date devait reculer, le chemin complet — J+1, 3, 7, 14, 30 plus J+45 et
J+60 sur les transverses — redevient atteignable et redevient le bon choix.

---

## Ce que ce plan ne peut pas vous donner

- **Aucun exercice corrigé** : le corpus n'en contient aucun. Le samedi est un
  travail auto-dirigé sur les sources officielles.
- **Aucune pondération d'examen** : le projet n'en connaît pas d'officielle.
  L'ordre des lots suit les dépendances, pas une répartition supposée des
  questions.
- **Aucun score prédit.** Ce plan ne simule pas votre résultat et n'en avancera
  aucun. Seuls vos mocks réellement passés le diront.

---

## Fichiers

| Fichier | Contenu |
|---|---|
| [`study-calendar.md`](study-calendar.md) | Le calendrier jour par jour, du 1er octobre 2026 au 9 janvier 2027 |
| [`mastery-checkpoints.md`](mastery-checkpoints.md) | Le contrôle de fin de lot, l'analyse des faiblesses, le plan de correction |
| [`exam-readiness.md`](exam-readiness.md) | Les dates prévisionnelles, la charge, et le critère PRÊT-CANDIDAT |

## Régénérer plutôt que corriger

Le calendrier est **généré**, pas écrit à la main :

```bash
python3 tools/revision/build_roadmap.py \
    --start 2026-10-01 --exam 2026-12-15 \
    --max-new 4 --weekday 120 --weekend 180
python3 tools/revision/render_calendar.py
```

Options : `--start` et `--exam` pour les dates, `--max-new` pour le plafond de
nouveautés quotidiennes, `--weekday` et `--weekend` pour les budgets en minutes.

Le générateur **refuse** de produire un plan où les cinq mocks ne tiennent pas
après la fin des lots : il s'arrête en nommant le nombre de week-ends manquants,
plutôt que d'avancer un mock avant que tout soit étudié.

`plan.json` est un intermédiaire machine : il est régénéré à la demande et n'est
pas suivi par git.

### Empreinte du corpus

Cette roadmap a été calculée le **11 septembre 2026** sur un corpus de **163
items, 594 questions, 71 578 mots de cours, 174 flashcards** (après PED-010).
Son empreinte est recalculée à chaque régénération par la commande ci-dessous ;
si elle ne correspond plus, le calendrier est périmé.

Si cette empreinte ne correspond plus, **le calendrier est périmé** : le corpus a
bougé et les durées avec lui. Régénérez-le. La commande qui la recalcule :

```bash
python3 - <<'EOF'
import hashlib, glob
h = hashlib.sha256()
for f in sorted(glob.glob('content/**/*.*', recursive=True)) + ['docs/syllabus/syllabus-matrix.yml']:
    h.update(open(f, 'rb').read())
print(h.hexdigest()[:16])
EOF
```
