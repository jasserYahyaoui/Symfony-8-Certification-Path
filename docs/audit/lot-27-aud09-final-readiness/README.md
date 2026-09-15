# AUD-09 — Rationalité finale et évaluation de préparation

**Date d'exécution :** 2026-09-15 · **Base :** `master` à `ef52ab3`, plus les
quatre correctifs que cet audit a produits · **Verdict :** `PASS_WITH_BLOCKER`

AUD-09 est le dernier des neuf audits de §14. Sa question n'est pas « le corpus
est-il grand » mais **« ce que le projet dit de lui-même est-il vrai »**. Il
relit donc chaque clause de §22 contre l'état mesuré, et il relit les
affirmations du projet contre son propre code.

Chaque chiffre ci-dessous a été **redérivé des fichiers canoniques par script**
(`content/**`, `docs/syllabus/syllabus-matrix.yml`), jamais d'un rapport
antérieur ni de mémoire. Le script est reproductible ; chaque porte a été
exécutée seule, son code de sortie lu (PROC-1).

## 1. Ce que l'audit a trouvé

Un audit qui ne trouve rien mérite d'abord qu'on se demande s'il a regardé.
Celui-ci a produit **six trouvailles, toutes corrigées dans le même
passage**. Quatre portent sur le travail le plus récent — le double champ
de citation — et **quatre au total sont des écarts entre ce que le projet
affirme et ce qu'il fait**, ce qui est exactement l'objet d'AUD-09.

| # | Trouvaille | Gravité | État |
|---|---|---|---|
| A-1 | `PracticeFeedback.tsx` envoyait l'apprenant vers l'URL **brute**, alors que `CLAUDE.md` et le document de vérification affirmaient déjà que « le site pointe vers `readable_url` ». L'affirmation précédait le code. | **Haute** — une affirmation fausse dans le fichier de gouvernance | **Corrigé** |
| A-2 | `SRC-002` exigeait l'égalité pour *toute* citation. Pour une source non dérivable (une page de doc, la forme `refs/heads/`), une URL rendue **correcte** écrite à la main devenait une erreur bloquante, et la seule option verte était de publier l'URL brute illisible : la règle poussait le contenu dans le mauvais sens. | Moyenne | **Corrigé** — avertissement, plus erreur, quand aucune dérivation n'existe |
| A-3 | `practice-smoke.py` terminait son motif par `.+` là où `SourceUrl::RAW` a `[^?#]+`. Une citation portant `?` ou `#` était un **avertissement dans `composer gate` et un échec dans le smoke de production** : porte de dépôt verte, déploiement cassé. | Moyenne | **Corrigé** — motif aligné caractère pour caractère |
| A-4 | Le même script affichait « each carrying … a rendered readable_url » **inconditionnellement**, alors qu'une citation non dérivable passe en portant deux fois la même URL. Un `PASS` non mérité, du type que §16 et §19 interdisent. | Moyenne | **Corrigé** — compté, plus affirmé |
| A-6 | Le même fichier **se contredisait** : un paragraphe affirmait « Clause 5 is therefore not `PASS` … AUD-03 fails the anchor half », trois lignes sous une ligne de table qui donnait AUD-03 `PASS` depuis le 2026-09-08. Vrai à l'écriture, laissé debout après la correction. Un document de gouvernance qui se contredit est pire qu'un chiffre périmé : les deux moitiés ont l'air de faire autorité. | Moyenne | **Corrigé** |
| A-5 | `docs/policy/final-readiness.md` datait du 2026-09-07 et sous-estimait le corpus de **172 questions, 92 tests, 16 surfaces d'accessibilité et 5 règles**. Un document d'état périmé qui se lit comme un document d'état courant. | Moyenne | **Corrigé** — table remesurée ci-dessous |

Deux observations, qui ne sont **pas** des défauts et sont enregistrées pour
qu'on cesse de les redécouvrir :

- **36 des 75 questions HOLDOUT ne portent pas `assesses_outcomes`, 39 en
  portent.** Ce n'est pas une violation : `PED-003` refuse précisément qu'une
  question HOLDOUT décharge un outcome, donc le lien n'est jamais compté pour
  elles. L'incohérence est cosmétique et sans effet mesurable.
- **« 15 topics » contre 14 dans la matrice.** Déjà établi et documenté par le
  *gate* syllabus : le PDF officiel dit 15 et rend 14 titres. Reconfirmé ici,
  ce n'est pas une trouvaille nouvelle.

## 2. §22 clause par clause, contre l'état mesuré

| # | Clause §22 | État | Preuve mesurée le 2026-09-15 |
|---|---|---|---|
| 1 | 100% de couverture atomique | **PASS** | `bin/cert coverage` sortie 0, **100 % (163/163)** `EXAM_READY`, **aucune différence** de rapport. Formule §3.5 uniquement |
| 2 | 0 lacune critique de syllabus | **PASS tel que mesuré, limite permanente** | 163/163 items, 14 sujets, 603 outcomes identifiés. La limite ne se lève pas : l'import vient du même PDF que lui-même, aucun second témoin, `certification.symfony.com` injoignable |
| 3 | 0 réponse scorée fausse connue | **PASS tel que connu** | **23 règles**, 0 violation bloquante sur **716 questions** / 2 872 choix. `AUD-05` (banque de questions) 0 *finding*. « Connu » reste le mot : aucun relecteur humain n'a revu les 695 questions anglaises |
| 4 | 0 dépendance OUT_OF_SCOPE scorée | **PASS** | **716/716** `classification: OFFICIAL` |
| 5 | Sources Symfony 8.0 vérifiées | **PASS** | **1 138 citations**, **177 URLs distinctes**, **177/177 en `200`** interrogées réellement ; **0** occurrence de `/current/` ; `AUD-02` lit ses 1 138 sources, 0 *finding* ; 163/163 cours `VERIFIED` |
| 6 | Simulation anglaise chronométrée | **PASS pour l'artefact — `PENDING_HUMAN_VALIDATION` pour la passation** | `/exam` (136/136 anglais) et `/mock-4` (75 questions, 90 min, **75/75** anglais) déployés et *smoke-testés*. **Le Mock 4 n'a pas été passé.** Voir §3 |
| 7 | Holdout protégé et inédit | **PASS sur la définition Option A, avec le qualificatif permanent** | **75 questions, 75 items distincts, 308 choix, 75/75 anglais**. `practice.json` (505 LEARNING), `exam.json` (136 VALIDATION) et les quatre mocks d'entraînement n'en portent **aucun identifiant ni choix** ; `mock-4.json` porte le holdout entier et rien d'autre. Isolation fonctionnelle **oui** ; **confidentialité du dépôt : NON**, le dépôt est public et les réponses y sont lisibles |
| 8 | Charge de révision gérable | **PASS tel que mesuré** | 163 cours, **71 680 mots de corps** (médiane 421, moyenne 440, étendue 242–880), 174 *flashcards*, 716 questions. `AUD-04` 0 *finding* |
| 9 | Portes technique, pédagogique, accessibilité, production | **PASS, la pédagogique restant la moins instrumentée** | Détail en §4. `AUD-08` 0 *finding* |

**§22 est une conjonction.** Huit clauses sur neuf tiennent sur preuve mesurée.
La neuvième — la moitié « passation » de la clause 6 — ne tient pas, et la
dernière ligne de §22 interdit qu'un tableau de `PASS` la compense.

## 3. Le seul blocage : la passation du Mock 4

**`PENDING_HUMAN_VALIDATION`.** L'instrument existe, est déployé et est vérifié
en production. La passation appartient au candidat : 75 questions, 90 minutes,
100 % anglais, conditions d'examen, sans aide extérieure, et elle doit
enregistrer score, temps, réponses fausses, questions non répondues et sujets
faibles.

Ce résultat ne peut être ni fabriqué, ni inféré, ni estimé, ni substitué — ni
depuis un Mock 1, 2, 3 ou 5, qui puisent dans du matériel déjà rencontré, ni
depuis `exam.json`, ni depuis une passation partielle ou non chronométrée, ni
depuis l'existence de la page.

## 4. Les portes, réellement exécutées

Chaque commande lancée seule, code de sortie lu.

| Porte | Commande | Résultat réel |
|---|---|---|
| Règles de contenu | `php bin/cert validate` | **23 règles**, 1 avertissement `PED-003` connu, **0 bloquant** |
| Couverture | `php bin/cert coverage` | sortie 0, **aucune différence** de rapport |
| Tests | `vendor/bin/phpunit` | **287 tests, 15 576 assertions, OK** |
| Audits de contenu | 12 scripts sous `tools/audit/` | **0 *finding*** partout |
| Non-vacuité | 4 prouveurs | `prove_framework_rules_fail` **9 cas** · `prove_flashcard_coverage_fails` OK · `aud10 --prove` OK · `lot27_practice_audit --prove` **12 cas** |
| Génération | `php bin/cert build` | sortie 0 |
| Site | `npm run build` | `SUCCESS`, **210 pages** |
| Types | `npm run typecheck` | sortie 0 |
| Navigation | `verify-navigation.mjs` | **209 atteignables au clic sur 210** (`/404` orpheline par conception) |
| Accessibilité | `npm run a11y` | **28 surfaces, 0 violation** (WCAG 2.1 AA + `h1` unique, ordre des titres, affordance de focus, défilement horizontal) |
| Interfaces | 3 vérificateurs navigateur | practice **18 ok** · simulations **11 ok** · agenda ok |
| Production | *smoke test* GitHub Pages | dernier vert sur `ef52ab3` |

## 5. Ce que cet audit ne prouve pas

Dit explicitement, parce qu'un audit qui tait ses limites en fabrique.

1. **La qualité pédagogique des 716 questions.** Aucun script ne l'établit, et
   aucun relecteur humain n'a revu le corpus anglais. `TECHNICALLY_INCORRECT`
   vaut 0 parce que rien ne peut le mesurer, pas parce que rien n'existe.
2. **La fidélité du scope à un second témoin.** L'import vient du PDF officiel ;
   rien ici ne le corrobore contre une autre source, et l'hôte officiel est
   injoignable.
3. **La confidentialité du holdout.** Le dépôt est public : les 75 questions et
   leurs 308 choix sont lisibles par qui ouvre délibérément les sources. Seule
   l'isolation *fonctionnelle* est prouvée.
4. **La joignabilité des URLs `github.com/blob`.** `github.com` répond `403`
   pour un dépôt amont sous la politique d'accès de la session. Établie sur la
   forme brute et **héritée par construction** — déduction, pas mesure.
5. **Le résultat du Mock 4.** Il n'existe pas.

## 6. Verdict

**`PASS_WITH_BLOCKER`.** Les huit clauses instrumentables de §22 tiennent sur
preuve mesurée ; la neuvième attend une passation humaine qui n'appartient pas
à ce dépôt. Aucun rapport ne doit décrire le projet comme *prêt*, *terminé* ou
*validé* tant que cette ligne lit `PENDING_HUMAN_VALIDATION`.
