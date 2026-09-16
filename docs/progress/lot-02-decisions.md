# Lot 02 — journal de décisions

Une ligne par décision. Pas d'historique narratif.

| Date | Décision | Motif |
|---|---|---|
| 2026-09-16 | RTK non installé : commandes natives à sortie filtrée | `command -v rtk` → absent. Constaté, non contourné. |
| 2026-09-16 | `grill-me` / `grill-me-with-docs` absents : sous-agent examinateur indépendant en substitut | Aucun skill de ce nom dans la liste disponible. |
| 2026-09-16 | Canonique = YAML `content/questions/` ; le Markdown étudiant est **généré** | ADR-0003 : `website/docs/` est généré et gitignoré ; l'écrire à la main serait écrasé au prochain `bin/cert build`. |
| 2026-09-16 | Les notions atomiques sont frappées comme `OUT` et déclarées sur l'item | `PED-003` refuse une question dont l'`assesses_outcomes` n'est pas déclaré sur son item. Pas de taxonomie parallèle. |
| 2026-09-16 | Départ sur `02-01 HTTP Specification (RFC 9110)` | `official_item_order = 1`. Premier sous-topic réel du dépôt. |
