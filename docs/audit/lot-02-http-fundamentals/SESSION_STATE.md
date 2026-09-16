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

`DEPLOYED_WITHOUT_VALIDATION` — **dérogation explicite de l'owner**

## Décision de l'owner, 2026-09-16

Quatre revues indépendantes ont rendu `LOT 02 NON VALIDÉ` (64 · 82,5 · 76,5 ·
77 sur 100, seuil 95). La cinquième n'a pas abouti. Les quatre `P1` de la revue
n° 4 sont corrigés mais n'ont jamais été soumis à revue.

Mis devant les trois options — déployer tel quel, réviser les budgets
`REV-001`, ou laisser en l'état — **l'owner a répondu « Déployer »**.

Ce déploiement est donc une **dérogation documentée**, pas une validation :

- le lot **n'a pas** de verdict `VALIDÉ` et n'en recevra pas rétroactivement ;
- aucun rapport ne doit le décrire comme validé ;
- les anomalies `P2`/`P3` restent **ouvertes et nommées** dans `REVIEW.md` ;
- la question de fond — 95/100 est-il atteignable alors que quatre cours sont à
  moins de 15 mots de leur plafond `REV-001` ? — a été **tranchée le
  2026-09-16** : l'owner a choisi de relever le budget ([ADR-0008]
  (../../adr/0008-revision-budget-recalibration.md)). `MINIMAL` passe de 400 à
  700 mots et *HTTP request* passe `STANDARD` → `DEEP` sur justification.

  **Ce que cela ne fait pas.** Des cinq notions que les revues ont laissées
  ouvertes, `REV-001` n'en bloquait que **deux** — `415` sur *Status codes*
  (2 mots de marge) et `setTrustedHosts()` sur *HTTP request* (6). Les trois
  autres — `Partitioned`/CHIPS, `send()`/`sendHeaders()`, la hiérarchie
  d'exceptions HttpClient — portaient 255, 416 et 371 mots de marge : elles
  n'ont jamais été empêchées, elles n'ont pas été écrites. Relever un budget
  n'écrit pas un paragraphe.

  **Les cinq ont été écrites le 2026-09-16.** Chaque affirmation est vérifiée
  contre une source récupérée pendant la rédaction, jamais depuis la mémoire :
  `Request.php` l. 642 et 1132, `Cookie.php` l. 75/262/313, `Response.php`
  l. 316/385/399, `HttpKernelRunner.php` l. 36/48, les sept interfaces de
  `Contracts/HttpClient/Exception/`, RFC 9110 §15.5.16 et le brouillon httpbis
  « Cookies » l. 842-893.

  Deux affirmations que je m'apprêtais à écrire ont été **abandonnées après
  vérification** : Symfony 8.0 n'implémente aucun préfixe `__Host-`/`__Secure-`
  (aucune occurrence dans `Cookie.php` ni `ResponseHeaderBag.php`), et le
  runner appelle `send(false)`, pas `send()` — la valeur de `$flush` par défaut
  n'est donc jamais celle qu'utilise Symfony. La première est publiée comme
  fait négatif, la seconde a remplacé une phrase fausse écrite deux heures plus
  tôt.

  **Le lot reste non validé.** Aucune revue n'a vu cet état. Les questions
  couvrant ces cinq notions n'existent pas encore : la couverture des cours a
  changé, l'évaluation non.

Ce qui est vrai et vérifié au moment du déploiement : les portes du dépôt sont
vertes, les cours ne contiennent plus de `P0` selon la revue n° 4, et le lot est
mesurablement meilleur qu'avant l'audit. Ce n'est pas la même chose qu'être
validé.

## Déploiement — preuves réelles, lues après coup

Chaque valeur ci-dessous a été relevée sur GitHub après exécution. Aucune n'est
prévue ni reconstruite.

| Étape | Preuve |
|---|---|
| CI sur la PR | `Technical gate` **success** — run `35060059043`, job `104678287650`, 2026-09-16 05:34:35 → 05:39:31 Z |
| Correctif qui a débloqué la CI | `5a5430c` — `docs/revision/plan.json` et `study-calendar.md` régénérés : ils dérivent de la taille des cours, que l'audit avait changée |
| Merge | PR **#149** fusionnée, merge commit **`f68880e95cc3cc4679e82f9c2ad87169a981a074`** |
| GitHub Pages | run **`35060419570`** (n° 166) **success**, 05:40:00 → 05:42:04 Z — `Build` `104679372326`, `Deploy` `104679684388`, `Production smoke test` `104679725051`, les trois `success` |
| Smoke test de production | 31 URL à **200** sur `https://jasseryahyaoui.github.io/Symfony-8-Certification-Path` ; `practice` 505 questions, `exam` 136, `mock-4` 75 — *le holdout entier et rien d'autre* ; **518 citations, 518 liant une page GitHub rendue, 0 sans équivalent dérivable** ; 3 cours échantillonnés à 200 |

**Ce que le smoke test ne prouvait pas.** Son échantillon de cours est de 3 sur
163 et tombait sur les lots 08, 13 et 04 — **aucune page du Lot 02**. Le run
ci-dessus établit donc que le site est en ligne, pas que les corrections de ce
lot ont atteint le lecteur.

**Le contrôle a parlé, en production.** Run Pages **`35061666616`** (n° 167),
job `Production smoke test` **`104683438091`**, 2026-09-16 06:00:20.328 Z :

```
ok  lot-02  the Caching and HTTP request pages carry the three P0 corrections
```

C'est la ligne qui manquait : les trois `P0` sont désormais vérifiés sur les
octets que GitHub Pages sert, pas seulement sur ceux du dépôt. La chaîne
`MERGE → ACTIONS → PAGES → SMOKE TEST → VERIFY CONTENT → DOCUMENT EVIDENCE`
est complète.

**Ce qui a été ajouté pour combler ce trou.** Une étape du workflow Pages lit
les pages *Caching* et *HTTP request* publiées et y cherche quatre chaînes
correspondant aux trois `P0`. Les quatre ont été choisies pour **discriminer** :
chacune est absente du texte pré-audit (`eef67f6`) et présente après, vérifié
avant écriture de l'étape. Une cinquième candidate, « mais seulement pour les
caches partagés », a été **écartée** parce qu'elle était déjà présente avant
l'audit : elle aurait passé quoi qu'il arrive. L'ensemble a été essayé dans les
deux sens sur le build local — `ok` sur le contenu corrigé, échec nommant les
quatre défauts sur le texte pré-audit.

**Vérification directe impossible depuis le conteneur.** `jasseryahyaoui.github.io`
est refusé par le proxy d'egress (`CONNECT` → 403, `recentRelayFailures` du proxy
à 05:44:39 Z). Aucune page de production n'a donc été lue par moi-même ; toutes
les preuves de production viennent du job CI, qui, lui, atteint l'hôte.

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
