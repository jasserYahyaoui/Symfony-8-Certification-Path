# Lot 02 — reprise

**Sous-topic courant** : `02-01` — HTTP Specification (RFC 9110)
**Phase** : revue indépendante **à relancer**

**Dernière action terminée** : SOURCE_ANALYST (57 notions, 0 UNVERIFIED),
COURSE_EXPERT (liste `Obsoletes` corrigée, pièges recentrés sur l'item),
EXAM_WRITER (6 questions, 3 `OUT` frappés et déclarés). Toutes portes vertes.

**Bloquant** : la revue indépendante s'est arrêtée sur une limite de session API
(429). Les 6 questions sont `PENDING_REVIEW`. Voir `lot-02-01-review.md`.

**Fichiers nécessaires à la reprise**
- `docs/progress/lot-02-01-review.md` (statuts)
- `docs/progress/lot-02-coverage.csv` (1 trou nommé : K-0201-037)
- `content/courses/CRS-depbnbc0g82x.md` · `content/questions/lot-02-http.yml`

**Prochaine action exacte**
1. Relancer INDEPENDENT_REVIEWER sur les 6 `QST-…` listées.
2. Corriger les non-approuvées, re-soumettre.
3. Couvrir `K-0201-037` (noms de champs insensibles à la casse).
4. Alors seulement, passer à `02-02 Status codes`.

**Ordre réel des sous-topics** (`official_item_order`)
1 RFC 9110 · 2 Status codes · 3 HTTP request · 4 HTTP response · 5 HTTP methods
6 Cookies · 7 Caching · 8 Content negotiation · 9 Language detection · 10 HttpClient

**Commandes de reprise**
```bash
php bin/cert validate
python3 tools/audit/aud10_answer_length_bias.py | grep lot-02
```
