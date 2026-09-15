# Practice Mode — contrat d'interface et de données (Lot 27, Unité A)

Ce document fixe ce que l'Unité B doit rendre, et **d'où chaque élément vient**.
Aucun fait technique ne peut être écrit dans un composant React : si une donnée
manque, elle est ajoutée au générateur et au schéma, pas au JSX.

## 0. État mesuré, lu et non supposé

| | |
|---|---:|
| Questions, tous pools | 716 |
| `LEARNING` | 505 |
| dont **anglaises** (périmètre de cette unité) | **484** |
| dont françaises (non-régression seulement) | 21 |
| Questions `answer_mode: multiple` | 10 |
| Politique de scoring, toutes questions | `all-or-nothing` (valeur unique) |
| Questions portant `code_language` | 12 |

`practice.json` contient **505 questions, toutes `LEARNING`** ; la clé `pool`
du payload vaut `LEARNING` et aucune question ne porte de champ `pool`
individuel — l'isolement est structurel, pas par filtrage côté page.

## 1. Ce que le payload transporte aujourd'hui

Relevé sur `website/static/data/practice.json` après `php bin/cert build`, pas
sur la classe PHP :

```text
id · version · official_topic · official_item · domain · subtopic · language
difficulty · cognitive_level · exam_skill · answer_mode · required_answer_count
question · code_language · shuffle_choices · negative_wording
estimated_time_seconds · scoring_policy · choices[] · explanation
official_sources[] · tags[]
choices[] : id · text · correct · explanation
official_sources[] : url · anchor
```

## 2. Ce qui manque pour tenir les objectifs 6 et 7

Quatre manques, tous au niveau du **modèle de données**, aucun au niveau du
contenu :

| Manque | Objectif bloqué | Où l'information existe déjà |
|---|---|---|
| Texte des learning outcomes | 6 — *key takeaway* | `syllabus-matrix.yml`, et déjà exporté dans les payloads de mock via `items` |
| URL du cours | 7 — *review this concept* | dérivée dans `DocsGenerator` : `/docs/courses/<slug(lot)>/<slug(official_item)>` |
| Libellé lisible de l'atomic item | 7 | `official_item` du payload porte l'**identifiant** `OIT-…`, pas le libellé |
| Corps de code séparé du prompt | 1 | aucun champ `code` n'existe ; le code vit dans `question` |

`PayloadBuilder::itemIndex()` produit déjà exactement les trois premiers pour
`mock-4.json` et les mocks d'entraînement. **Le correctif est d'appeler le même
index pour `practice.json`**, pas d'inventer une structure.

## 3. Rendu du code — ce que l'audit a réellement trouvé

Sur les 484 questions anglaises :

| | Questions | Surfaces |
|---|---:|---:|
| `CODE_BLOCK_REQUIRED` — multiligne ou déjà clôturé | **2** | 2 |
| `CODE_INLINE_PRESENT` — fragments courts | **120** | 242 |

Les 242 surfaces inline se répartissent entre énoncé, texte de choix,
explication générale et explication de distracteur : **le rendu doit couvrir
les quatre**, pas seulement l'énoncé.

**Le défaut est unique et systémique** : aucun composant ne rend le Markdown.
`QuestionCard` écrit `<p className="certpath-prompt">{question.question}</p>`,
donc :

- `QST-57p6cnh69c68` affiche ses *backticks* et son ```` ```php ```` **en
  toutes lettres** à l'apprenant ;
- `QST-cfhm8d3qscwq` voit ses deux attributs `#[Route]` aplatis en paragraphe ;
- les 242 fragments inline s'affichent avec leurs *backticks* littéraux.

Ce n'est **pas** 122 défauts de contenu. C'est un rendu manquant. Aucune de ces
questions n'a besoin d'être réécrite pour l'Unité B.

**Règle de rendu retenue.** Bloc si et seulement si la surface est multiligne ou
déjà clôturée par l'auteur ; sinon `<code>` inline. Transformer
`$request->getLocale()` en bloc casserait la phrase qui le porte — la consigne
« conserver les fragments courts inline » est donc appliquée telle quelle.

## 4. Ordre imposé du feedback

Rien de tout cela n'est visible avant soumission — ni texte, ni couleur, ni
attribut accessible.

| # | Section | Source canonique |
|---|---|---|
| 1 | Result | calcul local sur `choices[].correct` |
| 2 | Correct answer | `choices[].text` où `correct: true` |
| 3 | Why this answer is correct | `explanation` |
| 4 | Why the other answers are incorrect | `choices[].explanation` de chaque choix faux |
| 5 | Key takeaway | learning outcome de l'item **(à ajouter au payload)** |
| 6 | Review this concept | topic · item · outcome · lien cours **(à ajouter)** · `official_sources` |
| 7 | Next question | navigation |

Aucune section n'est générée dynamiquement. Si une source canonique est vide,
la section est **omise**, jamais remplie par une phrase de remplissage.

## 5. Questions à réponses multiples

Dix questions, toutes en `all-or-nothing`. Avant réponse : afficher le nombre
attendu (`required_answer_count`) et signaler une sélection excédentaire. Après
réponse, les quatre états doivent être distingués — *correct selected*,
*correct missed*, *incorrect selected*, *incorrect avoided*.

**Aucun crédit partiel n'est inventé** : la politique canonique est
`all-or-nothing` et le score reste binaire.

## 6. Résultat final

Le bilan de fin de session n'existe pas aujourd'hui : `ExamSession` couvre
`exam` et les mocks, et **exclut `practice`**. Le rendre suppose un
enregistrement de session côté Practice.

Mentions obligatoires : `Practice Mode`, et que le score **n'est pas un
résultat officiel Symfony**. Aucun seuil officiel n'existe dans ce projet ; tout
seuil interne est étiqueté `INTERNAL_TRAINING_FORMAT`.

Formulations prudentes imposées pour l'analyse par item : *Correct in this
session*, *Needs review*, *Insufficient evidence*. Une bonne réponse ne prouve
pas une maîtrise.

## 7. Persistance

`localStorage`, clé `certpath.learner-state`, `STORAGE_VERSION = 2`, chaîne de
migrations déjà en place. `Attempt` porte `question_id`, `question_version`,
`official_item`, `correct`, `chosen`, `answered_at`, `mode`.

Topic et learning outcomes **ne sont pas stockés et n'ont pas à l'être** : ils
se dérivent de `official_item` au rendu. Les stocker dupliquerait une donnée
canonique dans le navigateur, où elle se périmerait en silence.

**`PER_QUESTION_TIMING_NOT_IMPLEMENTED`.** Aucun temps par question n'est mesuré
aujourd'hui. La durée totale ne sera affichée que si elle est réellement
mesurée ; à défaut, elle est omise. Ce signal ne sera jamais simulé.

## 8. Porte pédagogique anglaise

Une question anglaise `LEARNING` n'est publiable que si : `explanation` non vide
et non générique, une explication par distracteur, nombre de réponses correctes
égal à `required_answer_count`, atomic item existant, outcomes liés, `course_ref`
résolvable, source officielle présente, code affichable sans perte.

**Les 484 questions anglaises passent cette porte aujourd'hui** — voir
`english-feedback-audit.csv`. Les manques du §2 sont des manques de transport,
pas de contenu.
