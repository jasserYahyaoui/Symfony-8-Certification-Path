# Lot 02 — contrôles exécutés

> **Ces sorties datent du HEAD courant.** La revue n° 4 a relevé, à juste titre,
> que la version précédente de ce fichier datait de `d084d78`, soit **quatre
> commits de cours en amont** : le build du site et l'audit d'accessibilité y
> attestaient d'un arbre qui n'existait plus, pendant que `SESSION_STATE.md`
> annonçait « toutes les portes au vert ». C'était un `PASS` sans preuve
> applicable, ce que §16 interdit. Tout a été relancé.

**Base :** dernier commit avant relance — `a6fd2c7` plus les corrections des quatre
`P1` de la revue n° 4. Chaque commande lancée **seule**, avec
`set -o pipefail`, code de sortie lu (PROC-1).

| Commande | Résultat réel | Code |
|---|---|---|
| `php bin/cert validate` | **23 règles**, 1 avertissement `PED-003` pré-existant, **0 bloquant** | **0** |
| `vendor/bin/phpunit` | **OK (287 tests, 15 576 assertions)** | **0** |
| `php bin/cert build` | arbre régénéré | **0** |
| `npm --prefix website run build` | `SUCCESS` | **0** |
| `node website/tools/verify-navigation.mjs` | **209 pages atteignables au clic sur 210** (`/404` orpheline par conception) | **0** |
| `npm --prefix website run a11y` | **28 surfaces auditées, `TOTAL VIOLATIONS: 0`** | **0** |
| `aud02_version_contamination` | `FINDINGS: 0` | **0** |
| `aud03_source_anchor --offline` | `FINDINGS: 0` | **0** |
| `aud04_content_volume` | `FINDINGS: 0` | **0** |
| `aud05_question_bank` | `FINDINGS: 0` | **0** |
| `aud06_holdout_integrity` | `FINDINGS: 0` | **0** |
| `aud07_english_readiness` | `FINDINGS: 0` | **0** |
| `aud09_course_sections` | `FINDINGS: 0` | **0** |
| `aud10_answer_length_bias` | `FINDINGS: 0` | **0** |

`aud06` est relancé explicitement parce que cette itération **modifie
l'explication d'un distracteur d'une question du pool `HOLDOUT`** : la clé de
réponse n'est pas touchée, mais le payload `mock-4.json` change, et
`assertMockMatchesBlueprint()` doit continuer de passer. Il passe.

## Budgets REV-001 au HEAD

| Cours | Niveau | Mots | Plafond |
|---|---|---:|---:|
| RFC 9110 | MINIMAL | 395 | 400 |
| Status codes | MINIMAL | 397 | 400 |
| Language detection | MINIMAL | 274 | 400 |
| HTTP request | STANDARD | 894 | 900 |
| Caching | STANDARD | 885 | 900 |
| Cookies | STANDARD | 645 | 900 |
| HTTP methods | STANDARD | 608 | 900 |
| HttpClient | STANDARD | 529 | 900 |
| HTTP response | STANDARD | 484 | 900 |
| Content negotiation | STANDARD | 438 | 900 |

**10/10 sous plafond. Aucune promotion de niveau n'a été faite à aucune
itération** — `REV-001` a bloqué **cinq fois** au cours de cet audit (472, 428,
418, 414, 405 mots sur des plafonds `MINIMAL` de 400) et la réponse a toujours
été de resserrer la prose. Quatre cours sont désormais à moins de 15 mots de
leur plafond : toute correction future devra se financer par un retrait.

## Vérification des liens

| Mesure | Résultat |
|---|---|
| Liens `/blob/` dans les cours du lot | **11 distincts, 11 × HTTP 200** |
| Ancres `#…` vérifiées présentes dans le document cible | **5/5** |
| URLs `raw.githubusercontent.com` dans les **corps** du lot | **0** |

**Non mesuré :** la joignabilité de la forme `/blob/` elle-même — `github.com`
répond `403` pour un dépôt amont sous la politique d'accès de cette session.
Établie sur la forme `raw`, héritée par construction.

## Défaut de mon propre outillage, conservé au journal

La première version du vérificateur de liens extrayait les URLs avec
`https://github\.com/[^\s)]+`, qui capturait le **guillemet fermant** du YAML et
produisait **140 faux `404`**. Corrigée, elle donne 11/11. Publier des anomalies
inventées aurait été plus grave que n'en trouver aucune.
