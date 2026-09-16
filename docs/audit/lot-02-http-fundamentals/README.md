# Lot 02 — HTTP Fundamentals : audit, correction, validation

Mission : faire du lot 02 une référence de révision fiable sur les fondamentaux
HTTP attendus à la certification Symfony 8.0.

## Où se trouve réellement le lot 02

La mission désigne `docs/courses/lot-02/`. **Ce répertoire n'existe pas.**

Les onze pages sont rendues dans `website/docs/courses/lot-02/`, qui est
**généré et gitignoré** ([ADR-0003](../../adr/0003-docusaurus-presentation-layer.md)) :
`CLAUDE.md` énonce que les éditer à la main est « always wrong », parce que le
prochain `php bin/cert build` écrase la modification.

Le travail porte donc sur les **fichiers canoniques qui produisent ces pages** :

| Rendu | Canonique |
|---|---|
| `website/docs/courses/lot-02/<slug>.md` | `content/courses/CRS-*.md` (front matter + corps) |
| les outcomes affichés | `docs/syllabus/syllabus-matrix.yml` |
| les questions servies | `content/questions/lot-02-*.yml` |
| `index.md` | généré par `src/Build/DocsGenerator.php` |

## Fichiers de ce dossier

| Fichier | Rôle |
|---|---|
| `SESSION_STATE.md` | **à lire en premier** — statut, dernière action, prochaine action |
| `INVENTORY.md` | inventaire exhaustif des pages et de leur état initial |
| `AUDIT.md` | anomalies P0–P3, page par page, avec la source officielle |
| `SOURCES.md` | correspondance page ↔ source officielle consultée |
| `CHANGES.md` | chaque modification, son motif, sa source, son statut |
| `VALIDATION.md` | commandes exécutées et résultats réels |
| `GRILL.md` | challenge de type examen et lacunes révélées |
| `REVIEW.md` | revue indépendante, score, verdict |
