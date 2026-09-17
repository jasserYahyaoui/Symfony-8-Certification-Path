# Lot 02 — journal de décisions

Une ligne par décision. Pas d'historique narratif.

| Date | Décision | Motif |
|---|---|---|
| 2026-09-16 | RTK non installé : commandes natives à sortie filtrée | `command -v rtk` → absent. Constaté, non contourné. |
| 2026-09-16 | `grill-me` / `grill-me-with-docs` absents : sous-agent examinateur indépendant en substitut | Aucun skill de ce nom dans la liste disponible. |
| 2026-09-16 | Canonique = YAML `content/questions/` ; le Markdown étudiant est **généré** | ADR-0003 : `website/docs/` est généré et gitignoré ; l'écrire à la main serait écrasé au prochain `bin/cert build`. |
| 2026-09-16 | Les notions atomiques sont frappées comme `OUT` et déclarées sur l'item | `PED-003` refuse une question dont l'`assesses_outcomes` n'est pas déclaré sur son item. Pas de taxonomie parallèle. |
| 2026-09-16 | Départ sur `02-01 HTTP Specification (RFC 9110)` | `official_item_order = 1`. Premier sous-topic réel du dépôt. |
| 2026-09-17 | 02-01 : les 7 questions restent `LEARNING` | L'item est `MINIMAL` ; `POOL-002` n'exige aucune `VALIDATION`, et n'en ajouter aucune évite de déplacer les effectifs des mocks. |
| 2026-09-17 | Archétype corrigé plutôt que compétence, sur 2 questions refusées par `ARC-001` | `DEFINITION_RECALL` est leur forme réelle. Gonfler `exam_skill` aurait contenté la règle en mentant sur la question. |
| 2026-09-17 | Pièges sûre/idempotente retirés de 02-01 | Ils appartiennent à l'item *HTTP methods* du même lot ; un renvoi d'une ligne les remplace. |
