# Lot 02 — reprise

**Sous-topic courant** : `02-01` — HTTP Specification (RFC 9110)
**Phase** : SOURCE_ANALYST lancé

**Dernière action terminée** : détection outils, identification de l'ordre réel des
10 sous-topics du lot 02, création de l'état.

**Fichiers nécessaires à la reprise**
- `docs/progress/lot-02-state.json`
- `docs/progress/lot-02-01-knowledge.json` (produit par SOURCE_ANALYST)
- `docs/progress/lot-02-source-index.json`
- `content/courses/CRS-depbnbc0g82x.md`

**Prochaine action exacte** : lire `lot-02-01-knowledge.json`, lancer COURSE_EXPERT
sur `CRS-depbnbc0g82x.md` en respectant `REV-001` (MINIMAL, plafond 700 mots de
corps ; 395 actuellement).

**Blocages** : aucun.

**Ordre réel des sous-topics** (`official_item_order`)
1 RFC 9110 · 2 Status codes · 3 HTTP request · 4 HTTP response · 5 HTTP methods
6 Cookies · 7 Caching · 8 Content negotiation · 9 Language detection · 10 HttpClient

**Commandes de reprise**
```bash
php bin/cert validate
python3 tools/audit/aud10_answer_length_bias.py | grep lot-02
```
