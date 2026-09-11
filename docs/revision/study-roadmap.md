# Roadmap de révision — candidat Symfony 8.0

**Profil déclaré** : 4/10 sur tous les domaines · 1 h à 2 h en semaine · 2 h à 3 h
le samedi · 2 h à 3 h le dimanche · aucune indisponibilité · apprentissage en
français, termes techniques en anglais.

**Départ** : 1er octobre 2026. **Objectif** : passer la certification du premier
coup — un `PASS`, pas un score maximal.

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
| Nouveautés par jour de semaine | 3 maximum | **décision**, justifiée ci-dessous |

**Pourquoi trois nouveautés par jour et pas plus.** Sans plafond, le générateur
tenait les 163 items en tenant le budget horaire — mais en plaçant **sept notions
PHP le premier jour**, soit douze minutes par notion. Pour un profil 4/10, ce
n'est pas de l'apprentissage. Le plafond coûte **deux semaines de calendrier** et
porte chaque notion à une trentaine de minutes. Le coût est assumé et chiffré.

### Volumes mesurés

| | Mesure |
|---|---|
| Items officiels | **163** |
| Mots de corps de cours | **71 578** |
| Questions hors HOLDOUT | **519** |
| Flashcards | **137** |
| Items sans aucune flashcard | **37 sur 163** |
| Items avec un exercice | **0 sur 163** |

### Charge de travail

| Poste | Plan complet | Chemin minimal |
|---|---:|---:|
| Première passe (cours + questions + flashcards) | 28,1 h | 28,1 h |
| Révisions espacées | 57,3 h | 37,0 h |
| Assessments de lot (26 × 30 min) | 13,0 h | 13,0 h |
| Mocks (5 × 90 min + correction) | 12,5 h | 12,5 h |
| **Total** | **110,9 h** | **90,6 h** |

La première passe ne pèse que **28 h sur 111**. Le reste, c'est la rétention.
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

**37 items n'ont aucune flashcard.** Pour ceux-là, la révision espacée s'appuie
seulement sur les questions et la section *Pièges d'examen* du cours. C'est plus
faible. Ces items sont signalés dans
[`mastery-checkpoints.md`](mastery-checkpoints.md#items-sans-flashcard).

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
| Lundi à vendredi | Cours, questions, flashcards + révisions dues | 90 min |
| Samedi | Source tour sur les items de la semaine, lecture du code Symfony `8.0` | 150 min |
| Dimanche | Consolidation, révisions dues, rattrapage, assessment de lot | 150 min |

Le budget planifié est de **10,9 h par semaine en médiane**, contre 12,5 h
déclarées disponibles. La marge est volontaire : elle absorbe un item qui
résiste sans faire glisser tout le calendrier.

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

## Le chemin le plus court vers PRÊT-CANDIDAT, à 100 % de couverture

La contrainte est explicite : **conserver 100 % de couverture du syllabus.** Les
163 items sont donc tous étudiés — aucun n'est sacrifié. Le raccourci ne peut
donc porter que sur la **profondeur de révision**, pas sur l'étendue.

| Variante | Révisions | Charge | Fin des lots | Mock 4 |
|---|---|---:|---|---|
| **Plan complet** (celui du calendrier) | J+1, 3, 7, 14, 30 · +45, 60 transverses | 110,9 h | 18 déc. 2026 | 23 janv. 2027 |
| **Chemin minimal** | J+1, 3, 7 seulement | 90,6 h | ≈ 11 déc. 2026 | ≈ 9 janv. 2027 |

Le chemin minimal économise **20,3 h et environ deux semaines**. Il supprime les
échéances J+14 et J+30, c'est-à-dire précisément celles qui transforment une
notion apprise en notion retenue un mois plus tard.

**Recommandation : ne le prenez pas par défaut.** Il existe pour un cas précis —
si vous deviez avancer la date d'examen pour une raison extérieure. Le prendre
pour gagner deux semaines sur un examen non planifié serait échanger de la
rétention contre du calendrier, alors que le calendrier ne vous contraint pas.

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
| [`study-calendar.md`](study-calendar.md) | Le calendrier jour par jour, du 1er octobre 2026 au 24 janvier 2027 |
| [`mastery-checkpoints.md`](mastery-checkpoints.md) | Le contrôle de fin de lot, l'analyse des faiblesses, le plan de correction |
| [`exam-readiness.md`](exam-readiness.md) | Les dates prévisionnelles, la charge, et le critère PRÊT-CANDIDAT |

## Régénérer plutôt que corriger

Le calendrier est **généré**, pas écrit à la main :

```bash
python3 tools/revision/build_roadmap.py            # écrit docs/revision/plan.json
python3 tools/revision/render_calendar.py          # écrit docs/revision/study-calendar.md
```

Options utiles : `--start AAAA-MM-JJ` pour décaler le départ, `--max-new N` pour
changer le plafond de nouveautés quotidiennes.

`plan.json` est un intermédiaire machine : il est régénéré à la demande et n'est
pas suivi par git.

### Empreinte du corpus

Cette roadmap a été calculée le **11 septembre 2026** sur un corpus de **163
items, 594 questions, 71 578 mots de cours, 137 flashcards**, dont l'empreinte
est :

```text
CORPUS_SHA256  e9e8a72bb5cd7f30   (content/** + docs/syllabus/syllabus-matrix.yml)
```

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
