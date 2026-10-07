# Seconde passe — lots 02 à 16

Décidée par le propriétaire le 2026-10-07, après la campagne de raffinement des
lots 02 à 26. Les lots 17 à 26 ont révélé des erreurs qu'une première lecture
n'avait pas vues : effets supposés absents (`isReadable()` lit), ordres repris
de la documentation (Runtime), valeurs par défaut incomplètes (`stop()` et
`SIGTERM`), généralisations (« toutes les propriétés »). La seconde passe relit
les pages des lots 02 à 16, déjà raffinées, à la recherche de ces mêmes
défauts.

Méthode : chaque affirmation qui n'avait pas été exécutée lors de la première
passe est confrontée à la source admise ou au code 8.0, par exécution chaque
fois que c'est possible ; une page n'est modifiée que si un défaut est établi.
Une page corrigée suit le circuit habituel — branche, PR, CI verte, fusion,
déploiement, smoke test de production **lu**. Une page relue sans défaut n'est
pas modifiée et est consignée ici comme telle. Aucun niveau n'est promu ; le
budget `REV-001` reste un plafond.

## Lot 02

| # | Page | Résultat |
|---|---|---|
| 1 | HTTP Specification (RFC 9110) | relue, exacte — résumé, en-tête *Obsoletes* / *Updates*, Table 1 et §3.1 confrontés à `rfc9110.html` |
| 2 | Status codes | **corrigée** — voir ci-dessous |
| 3 | HTTP request | relue, exacte — voir ci-dessous |

### Page 2 — *Status codes*

**Déploiement précédent, lu en production** : rapport de fin de lot 26 (PR #340,
`18fb176`), run Pages 37121520267, success — `ok  lot-26  the serializer page
carries its four flashcard levels, the public reach, the missing Default group
and the sibling repeat`.

`CRS-984c2bv3wh09` · MINIMAL · **666 → 679 mots** sur 700.

**Une généralisation.** La page disait que les agents « transforment
historiquement `301`/`302` en `GET` » et, en points clés, que `301`/`302` ne
préservent pas la méthode. RFC 9110 §15.4.2 et §15.4.3 : « *For historical
reasons, a user agent MAY change the request method from POST to GET* » — une
permission, et pour `POST` seulement. Corrigé : un agent **peut** changer un
`POST` en `GET` ; `301`/`302` ne **garantissent** pas la méthode. Une carte du
lot disait déjà `MAY` ; la page la contredisait.

Confirmé par exécution sur HttpFoundation 8.0.15 : les dix-huit constantes
`Response::HTTP_*` citées ont la valeur indiquée, `HTTP_PERMANENT_REDIRECT`
n'existe pas. Confirmé dans `rfc9110.html` : le code inconnu traité comme le
`x00` de sa classe (§15), la liste des codes *heuristically cacheable*.

**Questions et cartes.** Aucune question non holdout ne porte sur ce point ;
aucune carte à corriger.

**Aiguilles de smoke test.** `pour des raisons` et `ne la garantissent pas`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-07**

| Contrôle | Résultat |
|---|---|
| exécutions HttpFoundation 8.0.15 | résultats cités ci-dessus |
| `php bin/cert validate` | 0 bloquant |
| `php bin/cert coverage` | 163 / 163, rapport inchangé |
| `php bin/cert build` | exit 0 |
| 11 audits `tools/audit/` | exit 0, FINDINGS 0 chacun |
| blocs `run:` des workflows | 34 parsent (`bash -n`) |
| `composer gate-full` | exit 0 — 299 tests, 17 641 assertions ; TOTAL VIOLATIONS: 0 |
| `prove_framework_rules_fail.py` | PROOF OK (11 cas, restauration byte-identique) |
| `prove_flashcard_coverage_fails.py` | PROOF OK |
| `aud10 --prove`, `lot27 --prove` | exit 0 |
| empreinte SHA-256 de `content/` et `docs/` avant / après les preuves | identique |

### Page 3 — *HTTP request*

Relue sans défaut. Exécuté sur HttpFoundation 8.0.15 : les sept propriétés
publiques et leurs types ; `Request::get()` absente ; `InputBag::get()` et
`all()` sur une valeur du mauvais type lèvent `BadRequestException`, `all()`
d'une clé absente rend `[]` ; `getPayload()` rend un `InputBag` depuis JSON et
form-data ; `X-HTTP-Method-Override` lu sans activation sur un `POST`, ignoré
vers `GET`, `HEAD`, `CONNECT`, `TRACE` et hors de la liste de
`setAllowedHttpMethodOverride()` ; `_method` seulement après
`enableHttpMethodParameterOverride()` ; les deux exemples de `getClientIp()`
(`203.0.113.7`, `5.6.7.8`) ; l'ordre de `getHost()`, le port retiré, la casse
abaissée ; `setTrustedHosts(['example.com'])` accepte
`example.com.attaquant.test` et `exampleXcom` ; un hôte invalide lève même sans
liste. Page inchangée.
