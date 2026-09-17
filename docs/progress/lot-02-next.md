# Lot 02 — reprise

**Sous-topic courant** : `02-02` — Status codes — **à démarrer**
**Dernier terminé** : `02-01` HTTP Specification (RFC 9110) — revue passée, 0 anomalie ouverte

**Fichiers nécessaires à la reprise**
- `docs/progress/lot-02-state.json` · `lot-02-coverage.csv` · `lot-02-01-review.md`
- `content/courses/CRS-984c2bv3wh09.md` (le cours de 02-02)

**Prochaine action exacte**
Lancer SOURCE_ANALYST sur `02-02 Status codes` : cours `CRS-984c2bv3wh09.md`,
item `OIT-512se83ab7qj`, niveau `MINIMAL` (plafond 700 mots de corps).
Réutiliser `lot-02-source-index.json` : RFC 9110 y est déjà indexée et analysée,
ne pas la re-télécharger.

**Blocages** : aucun.

**Ordre réel des sous-topics** (`official_item_order`)
1 ✅ RFC 9110 · **2 Status codes** · 3 HTTP request · 4 HTTP response · 5 HTTP methods
6 Cookies · 7 Caching · 8 Content negotiation · 9 Language detection · 10 HttpClient

**Commandes de reprise**
```bash
php bin/cert validate
python3 tools/audit/aud10_answer_length_bias.py | grep lot-02
```
