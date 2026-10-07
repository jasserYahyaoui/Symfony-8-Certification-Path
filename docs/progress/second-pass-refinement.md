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
| 4 | HTTP response | **corrigée** — voir ci-dessous |
| 5 | HTTP methods | relue, exacte — voir ci-dessous |
| 6 | Cookies | relue, exacte — voir ci-dessous |
| 7 | Caching | relue, exacte — voir ci-dessous |
| 8 | Content negotiation | relue, exacte — voir ci-dessous |
| 9 | Language detection | **précisée** — voir ci-dessous |
| 10 | Symfony HttpClient component | relue, exacte — voir ci-dessous |

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

### Page 4 — *HTTP response*

`CRS-hdsq2qdz7qdv` · STANDARD · **823 → 856 mots** sur 900.

**Déploiement précédent, lu en production** : page 2 de la seconde passe
(PR #341, `9229a32`), run Pages 37669985069, success — `ok  second-pass
lot-02 status codes: a POST may become a GET after 301/302, not guaranteed`.

**Une affirmation fausse.** La page disait, des en-têtes déjà partis, que
`sendHeaders()` « réémet seulement la ligne de statut […] Aucune exception, aucun
avertissement » ; la carte `FLC-8jmvzzwwb0q4` répondait « Aucune » erreur. Lu
dans `Response::sendHeaders()` (8.0) : hors `cli`, `phpdbg` et `embed`, la
méthode appelle `header()` alors que `headers_sent()` est vrai. Exécuté sous
`php -S`, `output_buffering=0`, un `echo` avant `send()` d'une réponse 404 :
`E_WARNING` *Cannot modify header information - headers already sent*, levé
depuis `Response.php` l. 322, et la réponse arrive au client en **200**. Avec
le tampon de sortie par défaut du serveur intégré, les en-têtes n'étaient pas
encore partis et le 404 passait : l'exécution sans tampon est celle qui
reproduit le cas décrit. Page et carte corrigées — la carte au-delà de son
niveau.

Confirmé par exécution sur HttpFoundation 8.0.15 : une `Response` neuve en
`1.0`, promue `1.1` par `prepare()` qui pose le `Content-Type` et vide le corps
d'un `HEAD` et d'un `204` ; `isRedirect()` et `isRedirection()` sur neuf
statuts ; `RedirectResponse` à `302` par défaut, `201` accepté, `304` refusé
par une `InvalidArgumentException` ; `DEFAULT_ENCODING_OPTIONS` = `15`, les
quatre `JSON_HEX_*` ; `fromJsonString()` sans réencodage ; `isOk()` faux sur
`201`. Lu dans `HttpKernelRunner::run()` (8.0) : `send(false)`, puis
`fastcgi_finish_request()` hors debug, puis `terminate()`.

**Questions.** Aucune question non holdout ne porte sur ce point.

**Aiguilles de smoke test.** `Cannot modify header` et `un statut perdu`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-07** : `php bin/cert validate`
0 bloquant ; `php bin/cert coverage` 163 / 163, inchangé ; `php bin/cert build`
exit 0 ; 11 audits exit 0, FINDINGS 0 ; 34 blocs `run:` parsent ;
`composer gate-full` exit 0 — 299 tests, 17 641 assertions, TOTAL VIOLATIONS: 0 ;
`prove_framework_rules_fail.py` et `prove_flashcard_coverage_fails.py` PROOF
OK ; `aud10 --prove`, `lot27 --prove` exit 0 ; empreinte SHA-256 de `content/`
et `docs/` identique avant / après les preuves.

### Page 5 — *HTTP methods*

Relue sans défaut. Exécuté sur HttpFoundation 8.0.15 : `isMethodSafe()`,
`isMethodIdempotent()` et `isMethodCacheable()` sur onze méthodes, conformes au
tableau de la page (`PURGE` idempotente, `QUERY` sûre, idempotente et
cacheable, `CONNECT` aucune des trois) ; `isNotModified()` s'ouvre bien sur
`isMethodCacheable()`. Confirmé dans `rfc9110.html` : §9.2.2 (PUT, DELETE et
les méthodes sûres idempotentes), §9.2.3 (GET, HEAD et POST), §9.1 (casse).
Page inchangée.

### Page 6 — *Cookies*

Relue sans défaut. Exécuté sur HttpFoundation 8.0.15 : les défauts de
`Cookie::create()` (`path` `/`, `httpOnly`, `samesite=lax`, `secure` faux,
expiration `0`, non partitionné) ; `fromString()` sans `secure`, `httponly` ni
`samesite` ; `partitioned` sérialisé après `samesite` ; l'immuabilité ;
`secure` posé par `prepare()` sur une requête HTTPS, pas en HTTP ;
`clearCookie()` reprend `path` et `domain` ; `removeCookie()` retire de la
réponse ; `__Host-session` sans `Secure` émis sans plainte. Confirmé dans
`draft-ietf-httpbis-rfc6265bis.md` : le cookie `SameSite=None` sans `Secure`
ignoré (étape 19), les préfixes reconnus sans la casse par l'agent. Page
inchangée. Observation, sans défaut de la page : `Cookie::create('theme')` sans
valeur se sérialise en cookie d'effacement (`theme=deleted`, expiration
passée) ; la page, à 900 mots sur 900, ne l'ajoute pas.

### Page 7 — *Caching*

Relue sans défaut. Exécuté : `no-cache, private` par défaut ; `private,
must-revalidate` avec `Last-Modified` ou `Expires` ; `setMaxAge()` seul →
`max-age=3600, private` ; `setSharedMaxAge()` rend public ; ETag faible ;
`isNotModified()` rend `true` et un 304 vide quand la réponse n'a pas d'ETag
malgré un `If-None-Match`, et `false` sur un `POST` à ETag correspondant. La
note de `http_cache/expiration.rst` (8.0) sur `stale-if-error` est confirmée.
Page inchangée.

### Page 8 — *Content negotiation*

Relue sans défaut. Exécuté : l'exemple de `getAcceptableContentTypes()` ; le tri
par qualité puis ordre d'écriture, `q=0` conservé ; `getPreferredFormat()` rend
`xml` quand `_format` ou `setRequestFormat()` vaut `xml` malgré
`Accept: application/json`. Confirmé dans `rfc9110.html` : `q` insensible à la
casse, trois décimales, `0.001`, `Accept-Charset` déprécié, l'avertissement de
§12.5.4, `Vary: *` interdit à un proxy. Page inchangée.

### Page 9 — *Language detection*

`CRS-qa0x33akw264` · MINIMAL · **605 → 647 mots** sur 700.

**Déploiement précédent, lu en production** : page 4 de la seconde passe
(PR #342, `96618ff`), run Pages 37672004479, success — `ok  second-pass
lot-02 http response: headers already sent raise a warning and lose the
status`.

**Une lacune.** La page enseignait que `q=0` signifie « refusé » sans dire que
Symfony ne l'applique pas. Exécuté sur HttpFoundation 8.0.15 :
`Accept-Language: fr;q=0,en` donne `getLanguages()` = `['en', 'fr']`, et
`getPreferredLanguage(['de', 'fr'])` rend **`fr`** — la langue refusée,
préférée à la valeur par défaut `de`. Lu dans `Request::getPreferredLanguage()`
(8.0) : les combinaisons de toutes les langues de `getLanguages()` sont
essayées, sans regarder la qualité. Ajouté en piège. La page sur la négociation
de contenu le disait déjà pour `getAcceptableContentTypes()`.

Le reste est confirmé : la liste normalisée de l'exemple, `zh_Hans`,
`fr_Latn_FR`, `en_US`, une valeur hors grammaire rendue en minuscules, le
dédoublonnage, `fr_CA` choisi pour `fr` quand il vient en premier, le premier
locale fourni à défaut de correspondance, la première langue du client sans
argument.

**Questions et cartes.** Aucune ne prétend que Symfony filtre `q=0`.

**Aiguilles de smoke test.** `pas filtré par Symfony` et `la langue que le
client refuse`, absentes de la version `master`.

**Contrôles réellement exécutés le 2026-10-07** : `php bin/cert validate`
0 bloquant ; `php bin/cert coverage` 163 / 163, inchangé ; `php bin/cert build`
exit 0 ; 11 audits exit 0, FINDINGS 0 ; 34 blocs `run:` parsent ;
`composer gate-full` exit 0 — 299 tests, 17 641 assertions, TOTAL VIOLATIONS: 0 ;
`prove_framework_rules_fail.py` et `prove_flashcard_coverage_fails.py` PROOF
OK ; `aud10 --prove`, `lot27 --prove` exit 0 ; empreinte SHA-256 de `content/`
et `docs/` identique avant / après les preuves.

### Page 10 — *Symfony HttpClient component*

Relue sans défaut. Exécuté sur HttpClient 8.0.16 avec `MockHttpClient` :
`max_duration` `0`, `max_redirects` `20` ; la hiérarchie des interfaces
d'exception et la seule `getResponse()` des exceptions HTTP ; `getStatusCode()`
rend 404 sans lever, `getContent()` lève une `ClientExceptionInterface`,
`getContent(false)` rend le corps ; `toArray(false)` lève une `JsonException`,
qui est une `DecodingExceptionInterface` ; `json` et `body` ensemble refusés ;
un 302 avec `max_redirects` à `0` fait lever `getHeaders()` d'une
`RedirectionException`. Page inchangée.

## Bilan du lot 02

10 pages relues : **3 corrigées** (2 *Status codes*, 4 *HTTP response*, 9
*Language detection*), 1 carte corrigée (`FLC-8jmvzzwwb0q4`), 7 inchangées.
