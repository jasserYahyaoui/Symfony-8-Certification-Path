# Lot 02 — contrôles exécutés

Chaque commande lancée seule, code de sortie lu (PROC-1 : un `tail` en pipeline
masque le code de sortie de la commande auditée).

| Commande | Résultat réel | Code |
|---|---|---|
| `php bin/cert validate` | **23 règles**, 1 avertissement `PED-003` connu et pré-existant, **0 bloquant** | 0 |
| `vendor/bin/phpunit` | **287 tests, 15 576 assertions, OK** | 0 |
| `python3 tools/audit/aud02_version_contamination.py` | `FINDINGS: 0` | 0 |
| `python3 tools/audit/aud03_source_anchor.py --offline` | `FINDINGS: 0` | 0 |
| `python3 tools/audit/aud04_content_volume.py` | `FINDINGS: 0` | 0 |
| `python3 tools/audit/aud05_question_bank.py` | `FINDINGS: 0` | 0 |
| `python3 tools/audit/aud07_english_readiness.py` | `FINDINGS: 0` | 0 |
| `python3 tools/audit/aud09_course_sections.py` | `FINDINGS: 0` — relancé après le renommage de titre | 0 |
| `php bin/cert build` | arbre régénéré | 0 |
| `npm --prefix website run build` | `SUCCESS` | 0 |
| `node website/tools/verify-navigation.mjs` | **209 pages atteignables au clic sur 210** (`/404` orpheline par conception) | 0 |
| `npm --prefix website run a11y` | **28 surfaces, 0 violation** (WCAG 2.1 AA + `h1` unique, ordre des titres, focus, défilement horizontal) | 0 |

## Vérification des liens

| Mesure | Résultat |
|---|---|
| Liens `/blob/` dans `content/courses/` | **143** |
| Dont l'objet existe (vérifié via l'équivalent `raw`, `200`) | **143 / 143** |
| URLs `raw.githubusercontent.com` dans les **corps** du lot 02 | **0** (déjà 0 avant l'intervention) |
| URLs `raw` restantes en **front matter** | conservées **à dessein** : c'est le champ `url`, celui que le projet récupère pour vérifier ; le champ `readable_url` porte la forme `/blob/`, et `SRC-002` impose que l'un soit la dérivation exacte de l'autre |

**Ce qui n'est pas mesuré :** la joignabilité de la forme `/blob/` elle-même.
`github.com` répond `403` pour un dépôt amont sous la politique d'accès de cette
session. Elle est **héritée par construction** — même propriétaire, dépôt,
référence et chemin —, ce qui est une déduction et non une mesure.

## Défaut de mon propre outillage, consigné

La première version du vérificateur de liens extrayait les URLs avec
`https://github\.com/[^\s)]+`, qui capturait le **guillemet fermant** du YAML.
Elle a produit **140 faux `404`**. Corrigée en excluant `"` et `'`, elle donne
143/143. Publier ces 140 anomalies inventées aurait été plus grave que n'en
trouver aucune.
