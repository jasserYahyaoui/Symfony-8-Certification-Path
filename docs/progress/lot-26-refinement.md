# Raffinement pédagogique — Lot 26 (Miscellaneous)

Suite de la mission ouverte au lot 02 : approfondir les **pages de cours**
existantes — pièges d'examen, comportements implicites, flashcards aux quatre
niveaux — un lot à la fois, dans l'ordre numérique. Le lot 01 reste hors
périmètre sur instruction explicite (voir le journal du lot 02).

Même méthode qu'aux lots 03 à 25 : chaque affirmation vérifiée contre le code
de la branche 8.0 de Symfony ou la documentation correspondante, par exécution
chaque fois que c'est possible, jamais de mémoire ; budget `REV-001` respecté
sans promotion de niveau ; une branche, une PR, une CI verte, une fusion et un
smoke test de production **lu** par page.

## État par page (ordre officiel de l'item)

Chiffres relevés le 2026-10-03 par script sur les fichiers canoniques
(`syllabus-matrix.yml`, `content/**`) de `master` à `2d076a0`, avant la
première page.

| # | Page | Niveau | Mots / plafond | Flashcards | Statut |
|---|---|---|---|---|---|
| 1 | Serializer | STANDARD | 456 / 900 | 1 | à faire |

## Page 1 — *Serializer* — RAFFINÉE

`CRS-3z96cp2s1dka` · `OIT-fr58jzaj6jtb` · STANDARD · **456 → 777 mots** sur 900.
Aucun niveau promu. Exécutions sur Serializer 8.0.15 : `ObjectNormalizer` avec
`ClassMetadataFactory` et `AttributeLoader`, les quatre encodeurs.

### Déploiement précédent, lu en production

| Fusion | Run Pages | Ligne de smoke test |
|---|---|---|
| rapport de fin de lot 25 (PR #338, `2d076a0`) | 37119949536, success | `ok  lot-25  the runtime page carries its four flashcard levels, the real include order, the line run once and the silent early autoload` — le rapport ne touchant aucune page, c'est la dernière ligne de lot dans le journal du smoke test |

### Une affirmation fausse : « toutes les propriétés »

La page disait qu'`ObjectNormalizer` retient **toutes** les propriétés.
Exécuté : une propriété publique sort, une privée sort par son getter, une
protégée ou privée sans accesseur ne sort pas. La documentation 8.0 dit que le
normaliseur passe par le composant d'accès aux propriétés, qui ne lit
directement que le public. Corrigé.

### Confirmé par l'exécution

| Cas | Résultat |
|---|---|
| `groups` : aucun / `public-view` / `*` / `['Default']` | tout / le groupe / tout / **rien** |
| `#[SerializedName]` sans / avec `MetadataAwareNameConverter` | ignoré / appliqué ; le service du framework le branche |
| cycle `Org` ↔ `Mem`, limite par défaut / à `2` | `CircularReferenceException` dans les deux cas |
| gestionnaire de référence circulaire | identifiant rendu, aucune exception |
| même objet dans deux propriétés sœurs, limite `1` | aucune exception |
| format `toml` | `UnsupportedFormatException` |

**`QST-j0bscrddekrm` (LEARNING) → v2.** La bonne réponse disait que les
propriétés sont prises sans réserve, et un distracteur — rendre la propriété
privée — fonctionne quand aucun accesseur ne l'expose. Bonne réponse précisée
(*public properties*), distracteur remplacé, deux nouveaux identifiants de
choix ; l'énoncé ne suppose plus une propriété privée.

**Questions.** `QST-jyjz9an9yt07` (VALIDATION), `QST-xeygxgdd5586`,
`QST-0a0tb3jj6h5v` et `QST-k1ma4azm85yw` (LEARNING) relues : exactes,
inchangées. L'item a une question holdout : non lue, non modifiée.

**Flashcards.** 10 ajoutées ; `FLC-50daktpqmkv9` reçoit le niveau RECALL. L'item
en porte **11** (3 RECALL, 3 UNDERSTANDING, 2 APPLICATION, 3 TRAP), décompte
relevé par script sur tous les fichiers de cartes.

**Aiguilles de smoke test.** Les quatre titres de niveau, plus
`préfixe inconnu`, `contrairement au Validator` et `propriétés sœurs`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-03**

| Contrôle | Résultat |
|---|---|
| exécutions Symfony 8.0 (versions citées ci-dessus) | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `build_roadmap` + `render_calendar` (160/220) | régénérés ; `readiness` inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun (dont `aud10`) |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 641 assertions ; TOTAL VIOLATIONS: 0 |
| `verify-reschedule` | exit 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

## Prochaine étape

Rapport de fin de lot 26, réconcilié par script, dans sa propre PR.
