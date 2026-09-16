# SESSION_STATE — Lot 02 HTTP Fundamentals

> Fichier à lire en premier pour reprendre le travail dans une nouvelle session.

## Contexte

- **Mission** : auditer, corriger, enrichir et valider le lot 02 jusqu'à en faire
  une référence de révision fiable pour la certification Symfony 8.0.
- **Périmètre exact** : les 10 items officiels du lot 02, via leurs fichiers
  canoniques `content/courses/CRS-*.md`. `docs/courses/lot-02/` **n'existe pas** ;
  `website/docs/courses/lot-02/` est généré et gitignoré (ADR-0003).
- **Version ciblée** : Symfony **8.0**.
- **Interdiction active** : ne rien commencer sur le lot 03.

## Statut global

`BLOCKED`

Quatre revues indépendantes rendues, **toutes NON VALIDÉ** : 64, 82,5, 76,5 puis
77/100. La cinquième a échoué sur une limite d'API sans produire de verdict.
Les quatre `P1` de la revue n° 4 sont corrigés, mais **aucune revue n'a validé
l'état corrigé** : le dernier verdict opposable reste `NON VALIDÉ`.

## Avancement

- Fichiers découverts : **10 items / 11 pages rendues** (dont `index.md` généré)
- Fichiers analysés : **10 / 10**, lus intégralement
- Fichiers modifiés : **10** (4 sur le fond, 10 sur les sources cliquables et le titre)
- Fichiers validés : **10** — toutes les portes du dépôt au vert
- Restant à analyser : 0
- Restant à corriger : 0 anomalie P0 ou P1 ouverte

## Dernière action effectuée

Examinateur indépendant exécuté : 25 questions, 10 COUVERT / 4 PARTIEL /
11 NON COUVERT, et **deux erreurs factuelles** dont un **P0 que j'avais
introduit moi-même** (`getClientIp()` « la plus à gauche » — faux, et de
sécurité). Les deux erreurs et douze lacunes corrigées, chacune vérifiée par
moi dans la source avant écriture. Toutes les portes relancées, vertes. Reviewer
indépendant lancé.

## Prochaine action exacte

**Relancer une revue indépendante sur le HEAD `afeea75`** — les quatre `P1` de
la revue n° 4 y sont corrigés et n'ont jamais été soumis à revue. Lui demander
en outre de trancher la question ouverte : le poste « couverture » peut-il
atteindre 20/20 alors que quatre cours sont à moins de 15 mots de leur plafond
`REV-001` et que la promotion de niveau est interdite ? Si la réponse est non,
le seuil de 95/100 est inatteignable par construction et **la décision revient
à l'owner** : abaisser le seuil, réviser les budgets, ou accepter le lot avec
ses `P2` nommés.

## Décisions prises

- **D-1** — Travailler sur `content/courses/` et non sur `website/docs/`, parce
  que le second est généré et écrasé au prochain build (ADR-0003, CLAUDE.md).
- **D-2** — Réutiliser la convention `docs/audit/` du dépôt plutôt que créer
  `docs/audits/`, comme la mission le demande quand une convention existe.
- **D-3** — Tout ajout reste sous le plafond REV-001 de son niveau et
  **ne provoque aucune promotion de niveau** : le niveau est un constat, jamais
  une cible (CLAUDE.md).

## Sources consultées

Voir `SOURCES.md` : 8 sources officielles récupérées réellement (code `200`) —
`components/http_foundation.rst`, `Request.php`, `Response.php`, `Cookie.php`,
`http_cache.rst`, `http_client.rst`, `controller.rst`, RFC 9110.

## Skills et agents utilisés

| Outil | Objectif | Résultat | Limitation |
|---|---|---|---|
| `ListPlugins` | inventorier les plugins | **aucun plugin activé** | — |
| `SearchSkills` (grill me, quiz, challenge, exam, certification) | trouver Grill Me | **0 résultat** | — |
| `SearchPlugins` (mêmes mots-clés) | trouver Grill Me au catalogue | 10 plugins, **aucun Grill Me** | — |

**Grill Me est indisponible dans cet environnement.** Repli appliqué, conforme à
la consigne : un agent examinateur indépendant tiendra ce rôle, et le résultat
sera archivé dans `GRILL.md` sans jamais être présenté comme produit par Grill Me.

## Anomalies ouvertes

| ID | Fichier | Description | Criticité | Source | Correction attendue | Statut |
|---|---|---|---|---|---|---|
**État au HEAD courant, après quatre revues indépendantes.**

`P0` ouverts : **0**. `P1` ouverts : **0** — les quatre `P1` de la revue n° 4
sont corrigés dans cette itération.

Restent ouvertes et **nommées**, toutes `P2`/`P3` : `Partitioned`/CHIPS et les
préfixes `__Host-`/`__Secure-` ; `setTrustedHosts()` et l'empoisonnement de
`Host` ; `send()`/`sendHeaders()` ; la hiérarchie d'exceptions du client HTTP ;
`415` ; `reviewed_at` non rafraîchi sur les 10 cours ; l'ordre alphabétique des
directives dans les trois annotations d'en-tête cumulé de *Caching* ;
`QST-055ctb1t92na`, dont la règle de spécificité s'applique à deux types
distincts là où RFC 9110 §12.5.1 ne tranche qu'entre plages du **même** type.

Le détail de chaque anomalie, sa criticité et sa source sont dans `AUDIT.md`
et `REVIEW.md` ; la modification correspondante dans `CHANGES.md`.

## Contrôles exécutés

Voir `VALIDATION.md` : 12 commandes, toutes à 0. 143/143 liens `/blob/`
vérifiés.

## Dernier verdict indépendant

**Quatre revues indépendantes, quatre sous-agents distincts, aucun n'ayant
participé à la rédaction ni aux revues précédentes.**

| Revue | Score | Verdict | Ce qu'elle a trouvé |
|---|---:|---|---|
| n° 1 | 64/100 | NON VALIDÉ | 1 `P0` (`max-age` « caches privés ») + 4 `P1` |
| n° 2 | 82,5/100 | NON VALIDÉ | 1 `P1` : la définition du message venait de RFC 9112 |
| n° 3 | 76,5/100 | NON VALIDÉ | 2 `P0`, dont un **introduit par la correction précédente** |
| n° 4 | 77/100 | NON VALIDÉ | **0 `P0`**, 4 `P1` — dont deux dans la **banque de questions**, un jamais traité depuis deux revues, un `VALIDATION.md` périmé |

**Aucun verdict de validation n'a été prononcé.** Le lot reste bloqué tant
qu'une revue indépendante n'atteint pas 95/100 sans `P0` ni `P1`.

Constat de méthode de la revue n° 4, retenu : les cours sont désormais propres
— aucun `P0` malgré exécution systématique. Les défauts restants ne sont plus
dans les cours mais dans la **banque de questions** et dans les **rapports
eux-mêmes**. Quatre revues avaient vérifié que « aucun fait testé n'est absent
des cours » et jamais la réciproque : « aucune explication publiée ne contredit
un cours ».
