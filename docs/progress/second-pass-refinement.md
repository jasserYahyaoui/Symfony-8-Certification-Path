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

## Lot 03

| # | Page | Résultat |
|---|---|---|
| 1 | HttpFoundation component | relue, exacte |
| 2 | Symfony Flex | relue, exacte |
| 3 | License | relue, exacte |
| 4 | Components and Bridges | **corrigée** — voir ci-dessous |
| 5 | Code organization | relue, exacte |
| 6 | Request handling | **corrigée** — voir ci-dessous |
| 7 | Exception handling | **corrigée** — voir ci-dessous |
| 8 | Event dispatcher and kernel events | **corrigée** — voir ci-dessous |
| 9 | Official best practices | **corrigée** — voir ci-dessous |
| 10 | Backward compatibility promise | relue, exacte |
| 11 | Deprecations best practices | relue, exacte |
| 12 | Framework overloading | relue, exacte |
| 13 | Release management and roadmap schedule | relue, exacte |
| 14 | Framework interoperability and PSRs | **corrigée** — voir ci-dessous |
| 15 | Naming conventions | relue, exacte |

**Pages 1, 2, 3 et 5, relues sans défaut.** Page 1 : `composer.json` de
HttpFoundation 8.0 n'exige que `php` et `symfony/polyfill-mbstring`, un
polyfill et non un composant ; `RequestStack` exécuté — trois accesseurs à
`null` sur une pile vide, `getParentRequest()` à `null` avec une seule requête,
`getSession()` qui lève `SessionNotFoundException`, docblocks de `push()`,
`pop()` et l'avertissement ESI. Page 2 : confrontée à `setup.rst` et
`quick_tour/flex_recipes.rst` (plugin Composer, `symfony.lock` à committer, les
deux dépôts de recettes, les trois ajouts de la recette Twig, `debug-pack`,
`api-pack` et ses cinq recettes). Page 3 : `LICENSE` et la clé `license` de
`composer.json` sur la branche 8.0. Page 5 : `Kernel` et `MicroKernelTrait`
(8.0) lus — défauts des quatre accesseurs, `kernel.share_dir` conditionnel,
`getEnvDir()` qui ajoute l'environnement, `APP_LOG_DIR` pris tel quel — et
`configuration/override_dir_structure.rst` pour les clés `extra`, `config` et
de bundle.

### Page 4 — *Components and Bridges*

`CRS-1bxqx6ks853z` · STANDARD · **836 → 881 mots** sur 900.

**Déploiement précédent, lu en production** : page 9 du lot 02 en seconde passe
(PR #343, `5ffb2e9`), run Pages 37674195484, success — `ok  second-pass
lot-02 language detection: a refused q=0 language can still be chosen`.

**Un dénombrement faux.** La page disait que trois bridges sont listés dans
`replace` et qu'« un quatrième répertoire existe, `PhpUnit` ». Listé par un
clone sans blobs de la branche 8.0 (`6f841c0`) : `src/Symfony/Bridge/` compte
**cinq** répertoires — `Doctrine`, `Monolog`, `PhpUnit`, `PsrHttpMessage`,
`Twig`. `symfony/psr-http-message-bridge` (type `symfony-bridge`, exige
`psr/http-message` et `symfony/http-foundation`) est, comme `PhpUnit`, absent
de `replace`. Corrigé : cinq répertoires, deux hors `replace`, `PhpUnit` seul à
démentir la définition. Les cartes `FLC-tysgghm0xyx0` (« un quatrième
répertoire ») et `FLC-ttytsw9z60pf` (« trois des quatre répertoires ») sont
corrigées au-delà de leur niveau. Le reste est confirmé : `replace` à 65
paquets, dont cinq bundles ; `provide` limité aux `*-implementation` ; les
`require` des bridges Twig et PHPUnit.

**Questions.** Aucune question non holdout ne compte les bridges.

**Aiguilles de smoke test.** `PsrHttpMessage` et `en compte pourtant`, absentes
de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-07** : `php bin/cert validate`
0 bloquant ; `php bin/cert coverage` 163 / 163, inchangé ; `php bin/cert build`
exit 0 ; 11 audits exit 0, FINDINGS 0 ; 34 blocs `run:` parsent ;
`composer gate-full` exit 0 — 299 tests, 17 641 assertions, TOTAL VIOLATIONS: 0 ;
`prove_framework_rules_fail.py` et `prove_flashcard_coverage_fails.py` PROOF
OK ; `aud10 --prove`, `lot27 --prove` exit 0 ; empreinte SHA-256 de `content/`
et `docs/` identique avant / après les preuves.

### Page 6 — *Request handling*

`CRS-mpwjc4g3vmj7` · DEEP · **1181 → 1194 mots** sur 1200.

**Déploiement précédent, lu en production** : page 4 du lot 03 en seconde passe
(PR #344, `ea3f1fd`), run Pages 37676008721, success — `ok  second-pass
lot-03 components and bridges: five bridge directories, two outside replace`.

**Une garantie trop large.** La page disait que `kernel.finish_request` a lieu
sur « les trois » chemins, « la seule garantie de ce type dans tout le cycle » ;
la carte `FLC-5rm5a89fpm9v` aussi. Lu dans `HttpKernel::handle()` (8.0),
l. 78 : `if ($e instanceof \Error && !$this->handleAllThrowables) throw $e;`,
avant `finishRequest()`. Exécuté avec un `HttpKernel` nu et un contrôleur qui
lève :

| Levée | `handleAllThrowables` | `$catch` | Événements |
|---|---|---|---|
| `RuntimeException` | faux | vrai | `request`, `exception`, `finish_request` |
| `RuntimeException` | faux | faux | `request`, `finish_request` |
| `TypeError` | faux | vrai ou faux | `request` seulement |
| `TypeError` | vrai | vrai | `request`, `exception`, `finish_request` |
| `TypeError` | vrai | faux | `request`, `finish_request` |

`framework.handle_all_throwables` vaut `true` par défaut (`Configuration.php`
de FrameworkBundle 8.0) : dans une application complète la garantie tient ;
le constructeur d'`HttpKernel`, lui, met `handleAllThrowables` à `false`.
Corrigé sur la page — paragraphe, astuce et piège — et dans la carte, au-delà
de son niveau. Pour tenir le plafond, la phrase de renvoi vers la page
suivante est retirée.

**Questions.** Aucune question non holdout ne porte sur l'`\Error`.

**Aiguilles de smoke test.** `handle_all_throwables` et `à une condition`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-07** : `php bin/cert validate`
0 bloquant ; `php bin/cert coverage` 163 / 163, inchangé ; `php bin/cert build`
exit 0 ; 11 audits exit 0, FINDINGS 0 ; 34 blocs `run:` parsent ;
`composer gate-full` exit 0 — 299 tests, 17 641 assertions, TOTAL VIOLATIONS: 0 ;
`prove_framework_rules_fail.py` et `prove_flashcard_coverage_fails.py` PROOF
OK ; `aud10 --prove`, `lot27 --prove` exit 0 ; empreinte SHA-256 de `content/`
et `docs/` identique avant / après les preuves.

### Page 7 — *Exception handling*

`CRS-kd67y4vnd0mm` · STANDARD · **882 → 898 mots** sur 900.

**Déploiement précédent, lu en production** : page 6 du lot 03 en seconde passe
(PR #345, `7f74f2b`), run Pages 37677869273, success — `ok  second-pass
lot-03 request handling: an Error skips finish_request unless all throwables
are handled`.

**Une conclusion qui omettait une branche.** La page disait qu'une réponse
`200` ou `204` fournie dans `kernel.exception` « ressort en `500` » ; la carte
`FLC-79nmwswd5a50` aussi. La cascade de `HttpKernel::handleThrowable()` (8.0)
a trois branches : statut de la réponse s'il est 3xx, 4xx ou 5xx, sinon
`getStatusCode()` de l'exception si elle implémente `HttpExceptionInterface`,
sinon `500`. Exécuté avec un `HttpKernel` nu et un écouteur qui pose
`new Response('', 204)` : `404` après une `NotFoundHttpException`, `500` après
une `InvalidArgumentException`. Corrigé sur la page et dans la carte : le `500`
n'est que le dernier cas. Pour tenir le plafond, « construite dans
`kernel.exception` » est retiré.

**Questions.** `QST-0rxv1000z941` (LEARNING) posait la même situation sans
nommer l'exception ; sa réponse `500` dépendait d'une hypothèse non écrite.
Passée en version 2 : l'énoncé nomme une `InvalidArgumentException`, la réponse
reste `500`, l'explication cite le cas `404`.

**Aiguilles de smoke test.** `prend le statut` et `dans le seul cas`, absentes
de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-07** : `php bin/cert validate`
0 bloquant ; `php bin/cert coverage` 163 / 163, inchangé ; `php bin/cert build`
exit 0 ; 11 audits exit 0, FINDINGS 0 ; 34 blocs `run:` parsent ;
`composer gate-full` exit 0 — 299 tests, 17 641 assertions, TOTAL VIOLATIONS: 0 ;
`prove_framework_rules_fail.py` et `prove_flashcard_coverage_fails.py` PROOF
OK ; `aud10 --prove`, `lot27 --prove` exit 0 ; empreinte SHA-256 de `content/`
et `docs/` identique avant / après les preuves.

### Page 8 — *Event dispatcher and kernel events*

`CRS-2hpcy2rscq7m` · DEEP · **1038 → 1060 mots** sur 1200.

**Déploiement précédent, lu en production** : page 7 du lot 03 en seconde passe
(PR #346, `ca3d1e2`), run Pages 37679923798, success — `ok  second-pass
lot-03 exception handling: a 200 response takes the exception status, 500 only
otherwise`.

**Une conséquence fausse.** La page disait que retirer le type de l'argument
« rend l'attribut inopérant » pour un `#[AsEventListener]` sans `event` ; la
carte `FLC-ns8r1qzptegb` aussi. Lu dans `RegisterListenersPass` (8.0), l. 188 à
190, et exécuté : un `ContainerBuilder` avec l'autoconfiguration de
`AsEventListener` et la passe, compilé —

| Argument de la méthode | Résultat de `compile()` |
|---|---|
| non typé | `InvalidArgumentException` : *Service "l" must define the "event" attribute on "kernel.event_listener" tags.* |
| `EvA\|EvB` | écouteur abonné à `EvA` et à `EvB` |

L'attribut n'est pas ignoré : la compilation échoue. Corrigé sur la page et dans
la carte, avec le cas du type union.

**Questions.** Aucune question non holdout ne porte sur l'argument non typé.

**Aiguilles de smoke test.** `fait échouer la compilation` et `Un type union`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-07** : `php bin/cert validate`
0 bloquant ; `php bin/cert coverage` 163 / 163, inchangé ; `php bin/cert build`
exit 0 ; 11 audits exit 0, FINDINGS 0 ; 34 blocs `run:` parsent ;
`composer gate-full` exit 0 — 299 tests, 17 641 assertions, TOTAL VIOLATIONS: 0 ;
`prove_framework_rules_fail.py` et `prove_flashcard_coverage_fails.py` PROOF
OK ; `aud10 --prove`, `lot27 --prove` exit 0 ; empreinte SHA-256 de `content/`
et `docs/` identique avant / après les preuves.

### Page 9 — *Official best practices*

`CRS-k2xvr11x936e` · STANDARD · **869 → 878 mots** sur 900.

**Déploiement précédent, lu en production** : page 8 du lot 03 en seconde passe
(PR #347, `af30693`), run Pages 37682319346, success — `ok  second-pass
lot-03 event dispatcher: an untyped listener without event fails the
compilation`.

**Un dénombrement faux.** La page annonçait « trois recommandations » sur les
URL de test et n'en citait que deux. Compté dans `best_practices.rst` (8.0) :
dix sections soulignées de `-`, trente recommandations soulignées de `~` — les
deux totaux de la page sont exacts —, et la section *Tests* n'en contient que
deux, *Smoke Test your URLs* et *Hard-code URLs in a Functional Test*. Corrigé :
deux, les deux seules de la section.

**Questions.** `QST-nz6jxdj32j14` (LEARNING) porte sur le contenu de la
recommandation, pas sur leur nombre ; inchangée.

**Aiguille de smoke test.** `les deux seules de la`, absente de la version
`master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-07** : `php bin/cert validate`
0 bloquant ; `php bin/cert coverage` 163 / 163, inchangé ; `php bin/cert build`
exit 0 ; 11 audits exit 0, FINDINGS 0 ; 34 blocs `run:` parsent ;
`composer gate-full` exit 0 — 299 tests, 17 641 assertions, TOTAL VIOLATIONS: 0 ;
`prove_framework_rules_fail.py` et `prove_flashcard_coverage_fails.py` PROOF
OK ; `aud10 --prove`, `lot27 --prove` exit 0 ; empreinte SHA-256 de `content/`
et `docs/` identique avant / après les preuves.

**Pages 10 à 13 et 15, relues sans défaut**, chacune confrontée à sa source
8.0 : page 10 à `contributing/code/bc.rst` ; page 11 à
`contributing/code/conventions.rst` ; page 12 à `bundles/override.rst` ; page 13
à `contributing/community/releases.rst` ; page 15 à
`contributing/code/standards.rst` (section *Naming Conventions*) et à
`conventions.rst`.

### Page 14 — *Framework interoperability and PSRs*

`CRS-f74jcpnrrkhz` · STANDARD · **709 → 717 mots** sur 900.

**Déploiement précédent, lu en production** : page 9 du lot 03 en seconde passe
(PR #348, `a956450`), run Pages 37683870775, success — `ok  second-pass
lot-03 official best practices: the Tests section holds two recommendations`.

**Une version fausse.** Le tableau regroupait `psr/container-implementation`
et `psr/link-implementation` sous « `1.0`, `2.0` ». Lu dans la clé `provide` du
`composer.json` de la branche 8.0 (`6f841c0`) : `psr/container-implementation`
vaut `1.1|2.0`, `psr/link-implementation` `1.0|2.0`. Corrigé : deux lignes, la
première marquée « **pas** `1.0` », comme l'était déjà `psr/cache-implementation`.

**Une carte d'une autre page, corrigée au passage.** `FLC-6m7k069wqxwq`
(page 4, *Components and Bridges*) disait que `provide` couvre
`psr/log-implementation`, `psr/cache-implementation`,
`psr/container-implementation` « et onze autres ». La clé compte **quinze**
entrées : ce sont douze autres. La correction de la page 4 (PR #344) ne l'avait
pas vue.

**Questions.** `QST-rqn99khrxjbz` (LEARNING) porte sur la différence entre une
implémentation déclarée et PSR-12, pas sur les versions ; inchangée.

**Aiguille de smoke test.** `<code>1.1</code>`, absente de la version `master`
de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-07** : `php bin/cert validate`
0 bloquant ; `php bin/cert coverage` 163 / 163, inchangé ; `php bin/cert build`
exit 0 ; 11 audits exit 0, FINDINGS 0 ; 34 blocs `run:` parsent ;
`composer gate-full` exit 0 — 299 tests, 17 641 assertions, TOTAL VIOLATIONS: 0 ;
`prove_framework_rules_fail.py` et `prove_flashcard_coverage_fails.py` PROOF
OK ; `aud10 --prove`, `lot27 --prove` exit 0 ; empreinte SHA-256 de `content/`
et `docs/` identique avant / après les preuves.

## Bilan du lot 03

15 pages relues : **6 corrigées** (4 *Components and Bridges*, 6 *Request
handling*, 7 *Exception handling*, 8 *Event dispatcher and kernel events*,
9 *Official best practices*, 14 *Framework interoperability and PSRs*),
9 inchangées. Cartes corrigées : `FLC-tysgghm0xyx0`, `FLC-ttytsw9z60pf`,
`FLC-6m7k069wqxwq` (page 4), `FLC-5rm5a89fpm9v` (page 6), `FLC-79nmwswd5a50`
(page 7), `FLC-ns8r1qzptegb` (page 8). Question : `QST-0rxv1000z941`
(LEARNING) passée en version 2. Aucune question holdout lue ni modifiée.

## Lot 04

| # | Page | Résultat |
|---|---|---|
| 1 | HttpKernel component and FrameworkBundle | **corrigée** — voir ci-dessous |
| 2 | Naming conventions | relue, exacte |
| 3 | The base AbstractController class | **précisée** — voir ci-dessous |
| 4 | The request | relue, exacte |
| 5 | The response | relue, exacte |
| 6 | The cookies | relue, exacte |
| 7 | The session | **corrigée** — voir ci-dessous |
| 8 | The flash messages | relue, exacte |
| 9 | HTTP redirects | relue, exacte |
| 10 | Internal redirects | relue, exacte |
| 11 | Generate 404 pages | **précisée** — voir ci-dessous |
| 12 | File upload | relue, exacte |
| 13 | Built-in internal controllers | **corrigée** — voir ci-dessous |
| 14 | Argument value resolvers | relue, exacte |

**Pages relues sans défaut**, avec ce qui a été exécuté sur les composants 8.0
du bac à sable (FrameworkBundle 8.0.15) : page 2, les noms de route générés par
`AttributeClassLoader` seul et par `AttributeRouteControllerLoader` — dont le
suffixe `_1`, le préfixe de classe et les alias FQCN —, et `controller.rst`,
`templates.rst`, `routing.rst` pour les citations ; page 4, `getPayload()` dans
ses cinq cas (formulaire prioritaire, corps vide, JSON, JSON invalide, nombre) ;
page 5, `doRender()` et `file()` lus ; page 6, `ResponseHeaderBag` lu
(`removeCookie()`, `clearCookie()`, `getCookies()`) ; page 8, `FlashBag` et
`AppVariable::getFlashes()` lus, et un `add()` de flash exécuté dans un noyau
complet, qui pose bien le cookie de session ; page 9, `RedirectResponse` sur
201, 301 à 308 et 200, et l'URL vide ; page 10, `Request::duplicate()` comme
l'appelle `forward()` ; page 12, `RequestPayloadValueResolver::mapUploadedFile()`
et le défaut 422 ; page 14, `ArgumentResolver::getArguments()`, les priorités
de `framework-bundle/Resources/config/web.php` et les défauts 404 / 422 des
attributs `Map*`.

### Page 1 — *HttpKernel component and FrameworkBundle*

`CRS-tygmreqkkds3` · STANDARD · **717 → 762 mots** sur 900.

**Déploiement précédent, lu en production** : page 14 du lot 03 en seconde
passe (PR #349, `be5abbf`), run Pages 37686033051, success — `ok  second-pass
lot-03 psrs: the container implementation is declared at 1.1 and 2.0`.

**Un dénombrement faux.** La page attribuait à `KernelInterface` « seize
méthodes » en plus de `handle()` hérité. Compté dans `KernelInterface.php`
(HttpKernel 8.0) : **dix-sept** déclarations `public function`.

**Une cause fausse.** La page expliquait que `configureContainer()` et
`configureRoutes()` peuvent être `private` « parce que » `MicroKernelTrait` les
appelle par réflexion, une méthode privée n'étant « pas appelable
normalement ». Exécuté sur PHP 8.4 : une méthode d'un trait appelle **sans
réflexion** une méthode privée de la classe qui l'utilise, même avec des
arguments en trop. La réflexion sert à autre chose — lu dans
`MicroKernelTrait::registerContainerConfiguration()` (8.0) : lire le type du
premier paramètre de `configureContainer()`, qui reçoit `($container,
$loader)` s'il est typé `ContainerBuilder`, un `ContainerConfigurator`
sinon. Corrigé sur la page (paragraphe et point clé) et dans la carte
`FLC-77v79574hbs0`, dont la réponse reposait sur la même cause.

**Une affirmation vérifiée et conservée.** La route de prévisualisation
`_error/{statusCode}` est celle que donne `controller/error_pages.rst` (8.0),
une fois `errors.php` importé sous le préfixe `/_error`.

**Questions.** Aucune question non holdout ne compte les méthodes ni ne porte
sur la visibilité des méthodes du trait.

**Aiguilles de smoke test.** `dix-sept méthodes` et `ne doit rien à la`,
absentes de la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-07** : `php bin/cert validate`
0 bloquant ; `php bin/cert coverage` 163 / 163, inchangé ; `php bin/cert build`
exit 0 ; 11 audits exit 0, FINDINGS 0 ; 34 blocs `run:` parsent ;
`composer gate-full` exit 0 — 299 tests, 17 641 assertions, TOTAL VIOLATIONS: 0 ;
`prove_framework_rules_fail.py` et `prove_flashcard_coverage_fails.py` PROOF
OK ; `aud10 --prove`, `lot27 --prove` exit 0 ; empreinte SHA-256 de `content/`
et `docs/` identique avant / après les preuves.

### Incident — le smoke test de la PR #350 a échoué

**Déploiement de la page 1, lu en production** : PR #350 (`d8aab13`), run Pages
37687772282 — build et déploiement `success`, **Production smoke test
`failure`**. Le contrôle de seconde passe est passé (`ok  second-pass  lot-04
httpkernel component: seventeen methods, and private visibility owes nothing to
reflection`) ; c'est un contrôle **plus ancien**, posé au raffinement du lot 04,
qui a échoué : `Lot 04 refinement missing from the HttpKernel page:
httpkernel:no-interface-table`. Il cherchait la chaîne `seize méthodes` — le
texte faux que la page 1 venait de corriger.

**Correction.** L'aiguille devient `dix-sept méthodes` : le contrôle garde la
même force — la présence du tableau des interfaces, avec la bonne valeur — et
son commentaire dit pourquoi il a changé. Aucune autre aiguille n'est touchée.

**Cause et parade.** Mes vérifications d'aiguilles portaient sur les
**nouvelles** aiguilles (absentes de `master`), jamais sur les **anciennes** qu'un
remplacement peut faire disparaître. Un contrôle s'ajoute avant chaque PR : toute
aiguille de `pages.yml` présente dans la version `master` d'un fichier de
`content/` modifié doit l'être encore dans la version corrigée. Rejoué sur l'état
d'avant #350, il signale `seize méthodes` : il aurait arrêté l'erreur. Les six
corrections déjà préparées ne cassent aucune aiguille.

### Page 3 — *The base AbstractController class*

`CRS-h8s87edcx8ae` · STANDARD · **753 → 777 mots** sur 900.

**Une généralisation trop large.** L'astuce disait que le préfixe `?` signifie
que « le raccourci concerné lèvera une exception explicite si on l'appelle
quand même ». Exécuté avec un `AbstractController` dont le conteneur est un
`ServiceLocator` vide : `json()` ne lève rien et rend une `JsonResponse` `200`
`{"a":1}` — sans Serializer, il se rabat sur `json_encode()` (lu, l. 150 à 165) ;
`generateUrl()` lève une `ServiceNotFoundException` du localisateur, pas la
`LogicException` qui nomme un paquet. Précisé dans l'astuce. Le paragraphe sur
Twig et Security, lui, était exact et reste inchangé, comme la carte
`FLC-9m8aqbqef34y`, bornée à ces deux cas.

**Le reste, confirmé par réflexion sur FrameworkBundle 8.0.15** : 24 méthodes
`protected`, 2 `public`, 2 `private` ; 11 services souscrits, tous préfixés de
`?` ; `setContainer()` retourne `?ContainerInterface` et porte `#[Required]` ;
`addLink()` et `sendEarlyHints()` conformes au code.

**Questions.** Aucune question non holdout ne porte sur `json()` sans
Serializer.

**Aiguilles de smoke test.** `il se rabat sur` et `du localisateur`, absentes de
la version `master` de la page et des fichiers de cartes.

**Contrôles réellement exécutés le 2026-10-07** : `php bin/cert validate`
0 bloquant ; `php bin/cert coverage` 163 / 163, inchangé ; `php bin/cert build`
exit 0 ; 11 audits exit 0, FINDINGS 0 ; 34 blocs `run:` parsent, relancé après
la correction de l'aiguille ; `composer gate-full` exit 0 — 299 tests,
17 641 assertions, TOTAL VIOLATIONS: 0 ; `prove_framework_rules_fail.py` et
`prove_flashcard_coverage_fails.py` PROOF OK ; `aud10 --prove`,
`lot27 --prove` exit 0 ; empreinte SHA-256 de `content/` et `docs/` identique
avant / après les preuves ; contrôle d'aiguilles anciennes : aucune régression.

### Page 7 — *The session*

`CRS-a51fqgqynr2d` · STANDARD · **733 → 779 mots** sur 900.

**Déploiement précédent, lu en production** : page 3 du lot 04 et réparation
de l'aiguille (PR #351, `59d0e85`), run Pages 37731211296, **success** — le
smoke test est revenu au vert : `ok  second-pass  lot-04 abstractcontroller:
json() falls back to JsonResponse without the Serializer`, et le contrôle du
raffinement du lot 04 passe sur `dix-sept méthodes`.

**Une conséquence fausse.** La page disait qu'un simple `has()` suffit à
démarrer la session « et à faire apparaître le cookie » ; le piège et l'astuce
le répétaient (« le cookie part »), et la carte `FLC-7r316smhg8bj` répondait
« Oui » à la question « émet-elle un cookie de session ? ». Lu dans
`AbstractSessionListener::onKernelResponse()` (HttpKernel 8.0) : le cookie
n'est posé que si la session **n'est pas vide** ; la réponse devient privée dès
que la session a servi. Exécuté dans un noyau complet (FrameworkBundle 8.0.15,
session `mock_file`), trois routes d'un visiteur sans cookie :

| Route | `Set-Cookie` | `Cache-Control` |
|---|---|---|
| ne touche pas la session | aucun | `max-age=60, public` |
| `has('x')` seul | **aucun** | `max-age=0, must-revalidate, private` |
| `set('x', 1)` | `MOCKSESSID` | `max-age=0, must-revalidate, private` |

Corrigé sur la page (paragraphe, piège, astuce) et dans deux cartes :
`FLC-7r316smhg8bj` répond désormais « Non, pas à un visiteur sans session », et
`FLC-24afz5hynrda`, qui attribuait la cachabilité à l'absence de cookie, la
rattache à l'usage de la session — le cas `has()` réfute l'ancienne cause : pas
de cookie, et pourtant plus cachable. Les deux citent
`AbstractSessionListener.php`.

**Questions.** `QST-b5rsrffwd1ja` (LEARNING) — une page qui ne touche pas la
session n'émet pas de cookie — reste exacte ; inchangée.

**Aiguilles de smoke test.** `tant que la session reste vide` et `elle devient
privée`, absentes de la version `master` de la page et des fichiers de cartes ;
contrôle d'aiguilles anciennes : aucune régression.

**Contrôles réellement exécutés le 2026-10-08** : `php bin/cert validate`
0 bloquant ; `php bin/cert coverage` 163 / 163, inchangé ; `php bin/cert build`
exit 0 ; 11 audits exit 0, FINDINGS 0 ; 34 blocs `run:` parsent ;
`composer gate-full` exit 0 — 299 tests, 17 641 assertions, TOTAL VIOLATIONS: 0 ;
`prove_framework_rules_fail.py` et `prove_flashcard_coverage_fails.py` PROOF
OK ; `aud10 --prove`, `lot27 --prove` exit 0 ; empreinte SHA-256 de `content/`
et `docs/` identique avant / après les preuves.

### Page 11 — *Generate 404 pages*

`CRS-p57qsnnfpd2a` · STANDARD · **594 → 606 mots** sur 900.

**Déploiement précédent, lu en production** : page 7 du lot 04 (PR #352,
`bf12ae3`), run Pages 37732444935, success — `ok  second-pass  lot-04 session:
has() alone makes the response private and sets no cookie`.

**Une liste incomplète.** La page citait trois exceptions de HttpFoundation qui
implémentent `RequestExceptionInterface` — « toutes trois » — et donnent un 400.
Le répertoire `HttpFoundation/Exception/` (8.0) en compte **cinq** : s'y
ajoutent `JsonException`, que lève `getPayload()` sur un corps invalide (page 4),
et `SessionNotFoundException`. Exécuté avec
`FlattenException::createFromThrowable()` : les cinq donnent `400`, une
`LogicException` donne `500`. Précisé ; les deux classes ajoutées sont citées
en source.

**Le reste, confirmé** : l'ordre de `FlattenException` (lu) ;
`TwigErrorRenderer::findTemplate()` — `error<code>`, `error`, puis `null` et le
rendu HTML intégré, TwigBundle 8.0 n'embarquant aucun gabarit
`Exception/` ; la prévisualisation par sous-requête à `showException: false`.

**Questions.** Aucune question non holdout ne nomme ces exceptions.

**Aiguilles de smoke test.** `Cinq exceptions de HttpFoundation` et `sur un
corps invalide`, absentes de la version `master` de la page et des fichiers de
cartes ; contrôle d'aiguilles anciennes : aucune régression.

**Contrôles réellement exécutés le 2026-10-08** : `php bin/cert validate`
0 bloquant ; `php bin/cert coverage` 163 / 163, inchangé ; `php bin/cert build`
exit 0 ; 11 audits exit 0, FINDINGS 0 ; 34 blocs `run:` parsent ;
`composer gate-full` exit 0 — 299 tests, 17 641 assertions, TOTAL VIOLATIONS: 0 ;
`prove_framework_rules_fail.py` et `prove_flashcard_coverage_fails.py` PROOF
OK ; `aud10 --prove`, `lot27 --prove` exit 0 ; empreinte SHA-256 de `content/`
et `docs/` identique avant / après les preuves.

### Page 13 — *Built-in internal controllers*

`CRS-zmg0wrxvqqdq` · STANDARD · **665 → 687 mots** sur 900.

**Déploiement précédent, lu en production** : page 11 du lot 04 (PR #353,
`61787b8`), run Pages 37733505922, success — `ok  second-pass  lot-04 generate
404 pages: five request exceptions give a 400, JsonException included`.

**Une règle trop absolue.** La page disait qu'en mode `path` de
`RedirectController` la chaîne de requête « est toujours recopiée » ; le piège et
les points clés le répétaient, comme les cartes `FLC-422dng3d9mpz` et
`FLC-q651qspyt2g7`. Lu dans `urlRedirectAction()` (FrameworkBundle 8.0,
l. 120 à 127) : une URL complète — schéma présent, ou `//…` complété par le
schéma — est rendue **avant** la recopie de la chaîne de requête. Exécuté, depuis
`http://localhost/from?page=2` :

| `path` | `Location` |
|---|---|
| `/target` | `http://localhost/target?page=2` |
| `https://example.org/target` | `https://example.org/target` |
| `//cdn.example.org/target` | `http://cdn.example.org/target` |

Corrigé sur la page et dans les deux cartes : recopiée sur un chemin, jamais
sur une URL complète.

**Le reste, confirmé** : `templateAction()` (`maxAge: 0` ne pose rien,
`sharedAge: 0` pose `s-maxage=0`, `private` absent rend publique dès qu'un âge
est fourni) ; `redirectAction()` (307 / 308 avec `keepRequestMethod`, URL
générée en `ABSOLUTE_URL`, `RuntimeException` pour `route` et `path` ensemble
ou absents, 404 / 410 pour une cible vide).

**Questions.** Aucune question non holdout ne porte sur la chaîne de requête en
mode `path`.

**Aiguilles de smoke test.** `recopiée sur un chemin` et `jamais une URL
complète`, absentes de la version `master` de la page et des fichiers de cartes ;
contrôle d'aiguilles anciennes : aucune régression.

**Contrôles réellement exécutés le 2026-10-08** : `php bin/cert validate`
0 bloquant ; `php bin/cert coverage` 163 / 163, inchangé ; `php bin/cert build`
exit 0 ; 11 audits exit 0, FINDINGS 0 ; 34 blocs `run:` parsent ;
`composer gate-full` exit 0 — 299 tests, 17 641 assertions, TOTAL VIOLATIONS: 0 ;
`prove_framework_rules_fail.py` et `prove_flashcard_coverage_fails.py` PROOF
OK ; `aud10 --prove`, `lot27 --prove` exit 0 ; empreinte SHA-256 de `content/`
et `docs/` identique avant / après les preuves.

## Bilan du lot 04

14 pages relues : **5 corrigées ou précisées** (1 *HttpKernel component and
FrameworkBundle*, 3 *The base AbstractController class*, 7 *The session*,
11 *Generate 404 pages*, 13 *Built-in internal controllers*), 9 inchangées —
la page 3 et la page 11 sont des précisions, les trois autres des corrections.
Cartes corrigées : `FLC-77v79574hbs0` (page 1), `FLC-7r316smhg8bj` et
`FLC-24afz5hynrda` (page 7), `FLC-422dng3d9mpz` et `FLC-q651qspyt2g7`
(page 13). Aucune question modifiée. Un incident : le smoke test rouge de #350,
réparé par #351 (voir plus haut). Une question HOLDOUT a été affichée par
erreur dans le terminal de travail pendant la relecture de la page 3 ; elle n'a
été ni reprise ni modifiée, et les recherches dans les questions passent
désormais par un filtre qui masque le texte des questions HOLDOUT.

## Lot 05

| # | Page | Résultat |
|---|---|---|
| 1 | Routing component and FrameworkBundle | relue, exacte |
| 2 | Configuration (YAML and PHP attributes) | relue, exacte |
| 3 | Restrict URL parameters | relue, exacte |
| 4 | Set default values to URL parameters | relue, exacte |
| 5 | URLs generation | relue, exacte |
| 6 | Trigger redirects | **corrigée** — voir ci-dessous |
| 7 | Special internal routing attributes | relue, exacte |
| 8 | Domain name matching | relue, exacte |
| 9 | Conditional request matching | relue, exacte |
| 10 | HTTP methods matching | **corrigée** — voir ci-dessous |
| 11 | User's locale guessing | relue, exacte |
| 12 | Router debugging | relue, exacte |

**Pages relues sans défaut**, avec ce qui a été lu ou exécuté sur Routing et
FrameworkBundle 8.0 : page 1, `composer.json` de Routing (PHP 8.4 et
`deprecation-contracts` seuls), `RouterListener` (priorité 32, saut sur
`_controller`, `_route_params`, 405 et `Allow`), `AttributeServicesLoader` et
l'autoconfiguration de `#[Route]`, `RedirectableCompiledUrlMatcher::redirect()` ;
page 2, les seize paramètres de `#[Route]` par réflexion, `AVAILABLE_KEYS` du
chargeur YAML, l'alias réduit à `alias` et `deprecated`, l'héritage de
`priority` ; page 3, l'énumération `Requirement` sans cas, `DIGITS` et
`POSITIVE_INT`, `strict_requirements` (défaut `true`, chaîne vide pour
`false`) ; page 4, exécuté : `/{page}/x` toujours obligatoire, `{!page}`
obligatoire à l'appariement et présent à la génération, `{page?}` à `null` ;
page 5, exécuté : paramètres en trop, défaut égal retiré, `_query` prioritaire
et refusé hors tableau, objet à propriétés publiques en tableau,
`Stringable` en chaîne, les quatre types de référence ; page 7, exécuté :
`_query` par défaut ignoré, `_fragment` lu aux deux endroits ; page 8,
exécuté : casse de l'hôte, défaut d'hôte toujours exigé, `NETWORK_PATH` vers un
autre hôte ; page 9, exécuté : condition fausse → 404 et non 405, génération
indifférente à la condition ; page 11, `LocaleListener` et le repli
`fr_CA` → `fr` → nom nu du générateur ; page 12, `RouterDebugCommand`,
`RouterMatchCommand` et le filtre `--method` du descripteur.

### Page 6 — *Trigger redirects*

`CRS-tgbv3wp66rcc` · STANDARD · **823 → 829 mots** sur 900.

**Déploiement précédent, lu en production** : page 13 du lot 04 (PR #354,
`635a254`), run Pages 37734723326, success — `ok  second-pass  lot-04 internal
controllers: in path mode a full URL drops the query string`.

**Un terme faux.** La page appelait `/login` un « chemin relatif », deux fois.
`/login` est un chemin **absolu** — `ABSOLUTE_PATH`, le défaut de `path()` ;
`RELATIVE_PATH` produit `../blog/2`, et la page 5 du même lot fait cette
distinction. Exécuté : depuis HTTP, `generate('login')` sur une route
`schemes: ['https']` rend `https://example.com/login` ; depuis HTTPS, `/login`.
Corrigé en « chemin absolu, sans hôte ».

**Le reste, confirmé par exécution** (`RedirectableCompiledUrlMatcher` sur des
routes compilées) : barre finale redirigée en `GET` et `HEAD`, `POST` sans
redirection ; schéma redirigé vers le premier listé ; barre et schéma corrigés
en une seule redirection ; `permanent` à `true`.

**Questions.** Aucune question non holdout n'emploie le terme.

**Aiguilles de smoke test.** `produit le chemin absolu` et `un chemin sans
hôte`, absentes de la version `master` de la page et des fichiers de cartes ;
contrôle d'aiguilles anciennes : aucune régression.

**Contrôles réellement exécutés le 2026-10-08** : `php bin/cert validate`
0 bloquant ; `php bin/cert coverage` 163 / 163, inchangé ; `php bin/cert build`
exit 0 ; 11 audits exit 0, FINDINGS 0 ; 34 blocs `run:` parsent ;
`composer gate-full` exit 0 — 299 tests, 17 641 assertions, TOTAL VIOLATIONS: 0 ;
`prove_framework_rules_fail.py` et `prove_flashcard_coverage_fails.py` PROOF
OK ; `aud10 --prove`, `lot27 --prove` exit 0 ; empreinte SHA-256 de `content/`
et `docs/` identique avant / après les preuves.

### Page 10 — *HTTP methods matching*

`CRS-h3q0qxnq8eq0` · MINIMAL · **474 → 496 mots** sur 700.

**Déploiement précédent, lu en production** : page 6 du lot 05 (PR #355,
`ce86c61`), run Pages 37735740537, success — `ok  second-pass  lot-05 trigger
redirects: path() yields an absolute path, not a relative one`.

**Une condition fausse.** La page disait qu'« un nom qui contient autre chose
que des lettres majuscules lève une `SuspiciousOperationException` ». Lu dans
`Request::getMethod()` (HttpFoundation 8.0, l. 1224 à 1236) : le nom est passé
par `strtoupper()` **avant** le contrôle `strspn(…, 'A…Z')`. Exécuté avec
`enableHttpMethodParameterOverride()` et un `POST` portant `_method` :

| `_method` | `getMethod()` |
|---|---|
| `put`, `Put` | `PUT` |
| `delete` | `DELETE` |
| `P-UT`, `PUT2` | `SuspiciousOperationException` |

Une minuscule n'est donc pas refusée ; seul un caractère qui n'est pas une
lettre l'est. Corrigé.

**Le reste, confirmé** : remplacement seulement depuis un `POST` ; en-tête
`X-HTTP-METHOD-OVERRIDE` lu en premier et indépendant de
`http_method_override` (défaut `false`) ; `GET`, `HEAD`, `CONNECT` et `TRACE`
ignorés à l'exécution et refusés par la configuration de
`allowed_http_method_override` ; `[]` qui coupe tout ; le thème de formulaire
qui ajoute `_method` dès que la méthode n'est ni `GET` ni `POST`.

**Questions.** Aucune question non holdout ne porte sur la casse de `_method`.

**Aiguilles de smoke test.** `mis en majuscules` et `chiffre, un tiret`,
absentes de la version `master` de la page et des fichiers de cartes ; contrôle
d'aiguilles anciennes : aucune régression.

**Contrôles réellement exécutés le 2026-10-08** : `php bin/cert validate`
0 bloquant ; `php bin/cert coverage` 163 / 163, inchangé ; `php bin/cert build`
exit 0 ; 11 audits exit 0, FINDINGS 0 ; 34 blocs `run:` parsent ;
`composer gate-full` exit 0 — 299 tests, 17 641 assertions, TOTAL VIOLATIONS: 0 ;
`prove_framework_rules_fail.py` et `prove_flashcard_coverage_fails.py` PROOF
OK ; `aud10 --prove`, `lot27 --prove` exit 0 ; empreinte SHA-256 de `content/`
et `docs/` identique avant / après les preuves.

## Bilan du lot 05

12 pages relues : **2 corrigées** (6 *Trigger redirects*, 10 *HTTP methods
matching*), 10 inchangées. Aucune carte ni question modifiée.

## Lot 06

| # | Page | Résultat |
|---|---|---|
| 1 | TwigBundle | relue, exacte |
| 2 | Twig syntax up to 3.22 version | **corrigée** — voir ci-dessous |
| 3 | Auto escaping | relue, exacte |
| 4 | Template inheritance | relue, exacte |
| 5 | Global variables | relue, exacte |
| 6 | Filters and functions | relue, exacte |
| 7 | Template includes | relue, exacte |
| 8 | Loops and conditions | relue, exacte |
| 9 | URLs generation | relue, exacte |
| 10 | Controller rendering | relue, exacte |
| 11 | Translations and pluralization | relue, exacte |
| 12 | String interpolation | relue, exacte |
| 13 | Assets management | relue, exacte |
| 14 | Debugging variables | relue, exacte |

**Version de Twig des exécutions.** Les sondes de ce lot ont d'abord tourné sur
Twig 3.30 ; le syllabus s'arrête à 3.22. Toutes ont été **rejouées sur Twig
3.22.2** (avec Twig Bridge 8.0.15), avec des sorties identiques — y compris le
message `A block definition cannot be nested under non-capturing nodes.` que
cite la page 4 et que Twig 3.30 a changé.

**Pages relues sans défaut** : page 1, `TwigExtension` et `Configuration` de
TwigBundle 8.0 (défauts, `@`/`@@`, surcharge sous `templates/bundles/`, espace
`!Nom`), options de `lint:twig` et `debug:twig` ; page 3, exécuté : stratégie par
extension, expression statique non échappée, `raw` en dernier filtre, double
échappement avec une stratégie variable ; page 4, exécuté : contenu hors bloc,
`extends` multiples ou dans un bloc, liste de parents, `block()` et `is
defined`, `use` et renommage ; page 5, les onze propriétés d'`AppVariable` et
leurs exceptions, globales visibles dans une macro ; page 6, `slice`/`trim`,
arguments nommés, `truncate` inconnu, attributs `#[AsTwig*]` (3.21) ; page 7,
contexte, `only`, `with_context`, `ignore missing` et son ordre, `embed` ;
page 8, `loop.last` absent sur un générateur, portée de boucle, tests de
`CoreExtension` 3.22, vérité et vacuité de vingt valeurs ; page 9,
`isUrlGenerationSafe()` et `UrlHelper` ; page 10, `FragmentHandler` et
`InlineFragmentRenderer` ; page 11, filtre et balise `trans` (huit cas) ;
page 12, interpolation et séquences d'échappement, dépréciation 3.12 ;
page 13, `PathPackage` et stratégies de version (lus dans le clone 8.0) ;
page 14, `dump()` et `{% dump %}` avec et sans débogage, arguments nommés
refusés.

### Page 2 — *Twig syntax up to 3.22 version*

`CRS-x2f8reencvcs` · DEEP · **980 → 1016 mots** sur 1200.

**Déploiement précédent, lu en production** : page 10 du lot 05 (PR #356,
`424dd11`), run Pages 37737084904, success — `ok  second-pass  lot-05 http
methods: a lowercase _method is uppercased, only non-letters raise`.

**Premier défaut, un exemple faux.** La page affirmait qu'une classe déclarant
`const STATUS` et `getStatus()` verrait `{{ order.status }}` lire la constante.
`CoreExtension::getAttribute()` (Twig v3.22.0, l. 1807) teste
`\defined($object::class.'::'.$item)` — sensible à la casse — alors que les
méthodes sont cherchées en minuscules. Exécuté sur Twig 3.22.2 :

| Classe | `{{ o.status }}` |
|---|---|
| `const STATUS` + `getStatus()` | `getter` |
| `const status` + `const STATUS` + `getStatus()` | `const_lower` |

La règle « constante avant méthode » tient, mais seulement pour une constante
du même nom à la casse près. Corrigé dans le paragraphe et dans le piège.

**Second défaut, une version fausse.** Le tableau des opérateurs datait de 3.15
l'opérateur `...` sur les séquences et les tableaux. Le `CHANGELOG` de Twig
v3.22.0 l'inscrit en **3.7.0** (« Add support for the ...spread operator on
arrays and hashes ») ; 3.15 n'a ajouté que l'expansion des **arguments d'un
appel**, comme le dit la `versionadded` de `templates.rst`. Corrigé dans le
tableau et dans la carte `FLC-weyvdp72vd5e` (« il existe depuis Twig 3.15 ») ;
le `CHANGELOG` est cité en source sur la page et sur la carte.

**Questions.** `QST-2xamk5ahfyyn` (LEARNING) porte sur le premier accès tenté,
le tableau ; inchangée. Aucune question non holdout ne porte sur la constante ni
sur la version de `...`.

**Aiguilles de smoke test.** `correspond à la casse près` et `ne masque pas`,
absentes de la version `master` de la page et des fichiers de cartes ; contrôle
d'aiguilles anciennes : aucune régression.

**Contrôles réellement exécutés le 2026-10-08** : `php bin/cert validate`
0 bloquant ; `php bin/cert coverage` 163 / 163, inchangé ; `php bin/cert build`
exit 0 ; 11 audits exit 0, FINDINGS 0 ; 34 blocs `run:` parsent ;
`composer gate-full` exit 0 — 299 tests, 17 641 assertions, TOTAL VIOLATIONS: 0 ;
`prove_framework_rules_fail.py` et `prove_flashcard_coverage_fails.py` PROOF
OK ; `aud10 --prove`, `lot27 --prove` exit 0 ; empreinte SHA-256 de `content/`
et `docs/` identique avant / après les preuves.

## Bilan du lot 06

14 pages relues : **1 corrigée** (2 *Twig syntax up to 3.22 version*, deux
défauts), 13 inchangées. Carte corrigée : `FLC-weyvdp72vd5e`. Aucune question
modifiée.

## Lot 07

| # | Page | Résultat |
|---|---|---|
| 1 | Form component | relue, exacte |
| 2 | Forms creation | relue, exacte |
| 3 | Forms handling | relue, exacte |
| 4 | Form types (built-in and custom) | relue, exacte |
| 5 | Forms rendering with Twig | relue, exacte |
| 6 | Forms theming | relue, exacte |
| 7 | CSRF protection | **précisée** — voir ci-dessous |
| 8 | Handling file upload | relue, exacte |
| 9 | Built-in form types | relue, exacte |
| 10 | Data transformers | relue, exacte |
| 11 | Form events | relue, exacte |
| 12 | Form type extensions | relue, exacte |
| 13 | Form options (OptionsResolver component) | relue, exacte |

**Environnement des exécutions.** Un bac à sable dédié porte `symfony/form`
8.0.15 et ses dépendances. Composer y avait d'abord tiré cinq dépendances en 8.1
(`options-resolver`, `property-access`, `event-dispatcher`…) faute d'épinglage ;
toutes ont été épinglées en 8.0, et **les treize sondes du lot ont été
rejouées** : sorties identiques. Le rendu passe par Twig 3.22.2 et Twig Bridge
8.0.15.

**Pages relues sans défaut**, chacune exécutée : page 1, les trois couches d'un
`DateType` pour les trois `input` ; page 2, `NoSuchPropertyException` sans
`mapped: false` (un soupçon contraire, tiré d'une lecture du `DataMapper`, a été
**réfuté** par l'exécution), ordre de lecture des accesseurs, `data_class`
devinée ; page 3, `GET` ignoré, `isValid()` non soumis, `getPayload()->get()`
sur un tableau, `clearMissing` ; page 4, préfixes de bloc et parents ; page 5,
rendu exact de `form_row()`, `form_end()` avec et sans `render_rest`, double
rendu ; page 6, recherche de blocs, ordre des thèmes, les trois exemples
documentés ; page 8, chemin temporaire sur un champ mappé, chaîne → `null`,
`extensions` sans Mime ; page 9, lignée des 38 types, `choices`,
`RepeatedType`, préfixes d'un bouton ; page 10, ordre des transformateurs, échec
avec et sans Validator ; page 11, `add()` par événement et données portées par
chaque événement ; page 12, `FormPass` et `#[AsTaggedItem]` (lu : la priorité de
l'attribut n'est jamais lue quand le tag est passé en chaîne) ; page 13, douze
cas d'`OptionsResolver`.

### Page 7 — *CSRF protection*

`CRS-0yhqc0b1q7hz` · STANDARD · **595 → 641 mots** sur 900.

**Déploiement précédent, lu en production** : page 2 du lot 06 (PR #357,
`9bd3050`), run Pages 37738262507, success — `ok  second-pass  lot-06 twig
syntax: const STATUS does not hide getStatus(), the constant lookup is
case-sensitive`.

**Une règle documentée que le code complète.** La page reprend la documentation
(`security/csrf.rst`, 8.0, l. 417 à 419) : un jeton sans état est accepté si
`Origin` ou `Referer` correspond à l'origine de l'application. Lu dans
`SameOriginCsrfTokenManager::isValidOrigin()` (security-csrf 8.0) : l'en-tête
`Sec-Fetch-Site`, s'il est présent, est consulté **d'abord** et décide seul.
Exécuté sur une requête `POST` vers `https://example.com/form` :

| En-têtes | Jeton |
|---|---|
| `Origin` correct | accepté |
| `Referer` correct | accepté |
| `Origin` étranger, `Referer` correct | accepté |
| `Origin` correct, `Sec-Fetch-Site: cross-site` | **refusé** |
| `Origin` étranger, `Sec-Fetch-Site: same-origin` | **accepté** |
| aucun | refusé |

La page ajoute ce comportement en précisant que la règle documentée reste celle
à citer à l'examen ; la source est ajoutée.

**Questions.** Aucune question non holdout ne porte sur `Sec-Fetch-Site`.

**Aiguilles de smoke test.** `Sec-Fetch-Site` et `décide seul`, absentes de la
version `master` de la page et des fichiers de cartes ; contrôle d'aiguilles
anciennes : aucune régression.

**Contrôles réellement exécutés le 2026-10-08** : `php bin/cert validate`
0 bloquant ; `php bin/cert coverage` 163 / 163, inchangé ; `php bin/cert build`
exit 0 ; 11 audits exit 0, FINDINGS 0 ; 34 blocs `run:` parsent ;
`composer gate-full` exit 0 — 299 tests, 17 641 assertions, TOTAL VIOLATIONS: 0 ;
`prove_framework_rules_fail.py` et `prove_flashcard_coverage_fails.py` PROOF
OK ; `aud10 --prove`, `lot27 --prove` exit 0 ; empreinte SHA-256 de `content/`
et `docs/` identique avant / après les preuves.

## Bilan du lot 07

13 pages relues : **1 précisée** (7 *CSRF protection*), 12 inchangées. Aucune
carte ni question modifiée.

## Lot 08

| # | Page | Résultat |
|---|---|---|
| 1 | Validator component | relue, exacte |
| 2 | PHP object validation | relue, exacte |
| 3 | Built-in validation constraints | relue, exacte |
| 4 | Validation scopes | relue, exacte |
| 5 | Validation groups | relue, exacte |
| 6 | Group sequence | relue, exacte |
| 7 | Custom callback validators | relue, exacte |
| 8 | Violations builder | relue, exacte |

**Toutes exécutées sur `symfony/validator` 8.0.15** (dépendances épinglées en
8.0) : page 1, quatorze cas — attributs ignorés sans `enableAttributeMapping()`,
liste vide vraie en booléen, exceptions de configuration ; page 2, cascade par
`Valid` et `Cascade`, héritage fusionné, `loadValidatorMetadata()` non statique,
`#[ExtendsValidationFor]` ; page 3, la table `NotBlank` / `NotNull`,
`EqualTo` / `IdenticalTo`, `Sequentially`, `AtLeastOneOf`, et le rangement des
familles relu dans `map.rst.inc` ; page 4, les sept cas d'accesseurs et de
classe ; page 5, groupes implicites et cascade par groupe ; page 6, onze cas de
séquence et de provider ; page 7, huit cas de `Callback` ; page 8, les dix
méthodes du constructeur de violation, chaîne non terminée, `atPath()` et valeur
fautive.

## Bilan du lot 08

8 pages relues : **aucune erreur**. Aucune carte ni question modifiée.

## Lot 09

| # | Page | Résultat |
|---|---|---|
| 1 | Dependency Injection component | relue, exacte |
| 2 | Service container | relue, exacte |
| 3 | Built-in services | relue, exacte |
| 4 | Configuration parameters | relue, exacte |
| 5 | Services registration (YAML and PHP attributes) | relue, exacte |
| 6 | Service decoration | relue, exacte |
| 7 | Tags | relue, exacte |
| 8 | Semantic configuration | relue, exacte |
| 9 | Factories | relue, exacte |
| 10 | Compiler passes | relue, exacte |
| 11 | Services autowiring | relue, exacte |
| 12 | Service locators | relue, exacte |

**Toutes exécutées dans un noyau Symfony 8.0** (paquets `symfony/*` en 8.0.x) :
page 1, le tableau du composant seul ; page 2, options de `debug:container` et
`#[Autoconfigure]` ; page 3, les treize types injectés, l'échec sans
TwigBundle et deux messages d'autowiring ; page 4, `%%` échappé, `%` isolé pris
pour une dépendance, `json:base64` et `base64:json` dans les deux ordres,
`default:`, variable absente, et le message d'un `bind` inutilisé lu ; page 5,
huit cas d'enregistrement dans un noyau complet ; page 6, priorités, ordre,
`on_invalid` et nom interne ; page 7, itérateur étiqueté avec et sans
`index_by` — l'ordre d'enregistrement a été inversé pour que la sonde puisse
trancher : un service de priorité 5 est 2ᵉ en itérateur simple et perd sa place
sous `index_by` ; page 8, huit cas avec un `AbstractBundle` ; page 9, les cinq
formes de fabrique ; page 10, ordre des sept passes, visibilité des tags,
service retiré ; page 11, les dix cas de désambiguïsation ; page 12, locator
paresseux, même instance, `has()` sur un service absent, message d'un service
non déclaré, dépendance dure.

## Bilan du lot 09

12 pages relues : **aucune erreur**. Aucune carte ni question modifiée.

## Lot 10

| # | Page | Résultat |
|---|---|---|
| 1 | Security Core, CSRF and PasswordHasher components | relue, exacte |
| 2 | Authentication | relue, exacte |
| 3 | Authorization | relue, exacte |
| 4 | Configuration | relue, exacte |
| 5 | Providers | relue, exacte |
| 6 | Firewalls | relue, exacte |
| 7 | Users | relue, exacte |
| 8 | Password hashers | relue, exacte |
| 9 | Roles | relue, exacte |
| 10 | Access Control Rules | relue, exacte |
| 11 | Authenticators, Passports and Badges | relue, exacte |
| 12 | Voters and voting strategies | relue, exacte |

**Pages exécutées ou lues dans le code 8.0** : page 1, dépendances des trois
paquets lues et `symfony/password-hasher` installé **seul** dans un projet vide ;
page 2, migration de session et pare-feu `stateless` — la première sonde était
**biaisée** (le contrôleur écrivait lui-même en session, et la migration testée
était une reconnexion du même utilisateur, que le code exempte) ; corrigée, elle
confirme la page : identifiant de session changé, aucun cookie en `stateless`,
401 ensuite ; page 3, court-circuit d'`affirmative`, réponses de refus selon le
point d'entrée, message « Access Denied. The user doesn't have ROLE_ADMIN. » lu
dans `RoleVoter` et `AccessDecision::getMessage()` ; page 4, ordre des
pare-feux exécuté **avec** connexion (sans identifiants, aucun cookie n'est posé
quel que soit l'ordre, ce qui ne permet pas de trancher) ; page 5,
`ContextListener`, `InMemoryUser::isEqualTo()` et clés de fournisseurs ;
page 6, partage d'un pare-feu par `context` exécuté, `$isLazy` lu dans
`SecurityExtension` ; page 7, `UserInterface`, retrait d'`eraseCredentials()`
lu dans le CHANGELOG, `UserCheckerListener` ; page 8, sept cas de hachage ;
page 9, commande, six attributs d'`AuthenticatedVoter`, préfixe ; page 10, le
tableau des stratégies sous `access_control` ré-exécuté ; page 11, interface,
constructeurs, flux et messages lus ; page 12, les quatre stratégies sur six
combinaisons de votes.

## Bilan du lot 10

12 pages relues : **aucune erreur**. Aucune carte ni question modifiée.

## Lot 11

| # | Page | Résultat |
|---|---|---|
| 1 | Messenger component | relue, exacte |
| 2 | Transports | relue, exacte |
| 3 | Messages and handlers | relue, exacte |
| 4 | Workers | relue, exacte |
| 5 | Retries and failures | relue, exacte |
| 6 | Middleware | relue, exacte |
| 7 | Events | relue, exacte |

**Messenger 8.0.15** : page 1, tableau et exceptions exécutés ;
page 2, huit cas de routage exécutés ; page 3, versionnement d'un message
exécuté par `unserialize()` (trois cas) et règles de `MessengerPass` lues ;
page 4, les onze options de `messenger:consume`, la boucle de priorité,
`ResetServicesListener` et `InMemoryTransport` lus ; page 5, valeurs par défaut
de `retry_strategy` et ordre de `shouldRetry()` lus ; page 6, ordre de la pile
de middleware lu dans `FrameworkExtension` ; page 7, liste des événements et
priorités des écouteurs de `WorkerMessageFailedEvent` (200, 100, −100) lues.

## Bilan du lot 11

7 pages relues : **aucune erreur**. Aucune carte ni question modifiée.

## Lot 12

| # | Page | Résultat |
|---|---|---|
| 1 | Console component | relue, exacte |
| 2 | Built-in commands | relue, exacte |
| 3 | Custom commands | relue, exacte |
| 4 | Configuration | relue, exacte |
| 5 | Options and arguments | relue, exacte |
| 6 | Input and Output objects | relue, exacte |
| 7 | Built-in helpers | relue, exacte |
| 8 | Console events | relue, exacte |
| 9 | Verbosity levels | relue, exacte |

**Console 8.0.15** : page 1, codes de sortie et appel d'`interact()` exécutés par
de vrais processus ; pages 2 et 3, `#[Ask]` exécuté et autoconfiguration lue ;
page 4, le tag `console.command` ne reçoit ni `usages` ni `aliases` de
l'attribut (lu) ; page 5, modes déduits et quatre erreurs exécutés ; page 6,
tableaux exécutés ; page 7, sept cas d'helpers ; page 8, les sept lignes du
tableau d'événements exécutées avec un dispatcher, `RETURN_CODE_DISABLED`,
`abortExit()` et `getInterruptingSignal()` lus ; page 9, le tableau de
verbosité exécuté dans de vrais processus, `SHELL_VERBOSITY` seul et combiné à
`-q` et `-v`, `configureIO()` lu (`-q` et `--silent` rendent l'entrée non
interactive) ; la phrase « `--silent` masque les erreurs, il ne les perd pas »
a été exécutée dans une application FrameworkBundle sans Monolog : sous
`--silent`, la console n'affiche rien et le logger écrit encore la ligne
`[critical] Error thrown while running command`.

## Bilan du lot 12

9 pages relues : **aucune erreur**. Aucune carte ni question modifiée.

## Lot 13

| # | Page | Résultat |
|---|---|---|
| 1 | Unit tests with PHPUnit | relue, exacte |
| 2 | Functional tests with PHPUnit | relue, exacte |
| 3 | Client object | **corrigée** — voir ci-dessous |
| 4 | Crawler object (CssSelector and DomCrawler components) | **précisée** — voir ci-dessous |
| 5 | Profiler object (WebProfiler bundle) | relue, exacte |
| 6 | Framework objects access | relue, exacte |
| 7 | Client configuration | relue, exacte |
| 8 | Request and response objects introspection | **précisée** — voir ci-dessous |
| 9 | Handling legacy deprecated code | relue, exacte |

**Pages relues sans défaut**, exécutées avec PHPUnit 11.5.56 et FrameworkBundle
8.0.15, et confrontées à `testing.rst` 8.0 : page 1, un `TestCase` qui passe
sans configuration et un `KernelTestCase` qui échoue sans `KERNEL_CLASS`
(message relevé), le suffixe `Test` (un fichier `…Tests.php` ignoré au parcours
du répertoire, exécuté s'il est désigné), la priorité des trois fichiers de
configuration ; page 2, `bootKernel()` puis `createClient()` (`LogicException`
relevée), une réponse 201 acceptée par `assertResponseIsSuccessful()`, l'échec
sur un 404 avec en-têtes et corps, puis en-têtes seuls après
`setBrowserKitAssertionsAsVerbose(false)` ou avec `$verbose` à `false`, les
dix-sept assertions citées présentes dans les traits de FrameworkBundle, et le
tableau Dotenv dans ses quatre cas ; page 5, les cinq lignes du tableau du
profileur sous trois configurations, la liste des collecteurs et la route lue
par le collecteur `request`, `enableProfiler()` et `getProfile()` lus,
`testing/profiling.rst` pour la configuration et l'idiome ; page 6, le tableau
du conteneur de test et du conteneur du noyau (`ClockInterface`, `Clock`,
`Unused`), `set()` avant et après `get()`, `getContainer()` qui démarre le
noyau et rend un `TestContainer`, deux conteneurs pour deux tests ; page 7,
les trois clés d'en-tête, `CONTENT_TYPE`, l'agent utilisateur par défaut,
`HTTP_HOST` conservé à la deuxième requête, les trois cas de session ; page 9,
exécuté sur PHP 8.4 : `trigger_deprecation()` sans gestionnaire (rien),
`trigger_error()` sans `@` (`Deprecated:` affiché), le gestionnaire d'erreurs de
Symfony 8.0 avec un journal (entrée `info` « User Deprecated: Since acme/pkg
1.2: … »), et un test PHPUnit 11.5 qui passe malgré `failOnDeprecation="true"` ;
`trigger_deprecation()`, le commentaire d'`ErrorHandler`, le défaut `true` de
`php_errors.log` (code et référence), `upgrade_minor.rst`, `upgrade_major.rst`
et `conventions.rst` lus.

### Page 3 — *Client object*

`CRS-k5wg55q2mg0p` · STANDARD · **762 → 830 mots** sur 900.

**Déploiement précédent, lu en production** : page 7 du lot 07 (PR #358,
`6f40f52`), run Pages 37739595552, success — `ok  second-pass  lot-07 csrf:
Sec-Fetch-Site decides alone when present, beyond Origin and Referer`.

**Une généralisation.** La page affirmait que `back()` et `forward()` sautent
les redirections, « exécuté » sur `/created` puis `/go` suivi jusqu'à `/hello`,
`back()` ramenant à `/created`. Ce n'est vrai que si le client suit les
redirections de lui-même. Lu dans `AbstractBrowser::request()` (browser-kit
8.0) : l'URL qui redirige n'est inscrite dans `$redirects`, la liste que
`back()` et `forward()` sautent, que lorsque `followRedirects` est vrai — et la
même page rappelle que le client de test ne suit pas les redirections par
défaut. Exécuté dans un `WebTestCase` :

| Mode du client | Après `back()` | Après `forward()` |
|---|---|---|
| `followRedirects()` avant les requêtes | `/created`, 201 | `/hello`, 200 |
| par défaut, puis `followRedirect()` | `/go`, de nouveau 302 | `/hello`, 200 |
| `followRedirects()`, puis `followRedirects(false)` avant `back()` | `/created`, 201 | — |

La page précise désormais que seules les redirections suivies en mode
`followRedirects()` sont sautées, avec ce tableau réduit à deux lignes, et un
piège d'examen est ajouté. La source `AbstractBrowser` de la page est
re-vérifiée.

**Carte.** `FLC-n4wy9v7h0cps` (TRAP) répondait « Non : `back()` et `forward()`
sautent les redirections » à la question « `back()` revient-il sur la page de
redirection ? ». Réponse corrigée : oui après un `followRedirect()` manuel, non
en mode `followRedirects()` ; explication refaite sur l'exécution, source
`AbstractBrowser` ajoutée.

**Le reste de la page, confirmé par exécution** (FrameworkBundle et
SecurityBundle 8.0.15) : `KernelBrowser` rendu par `createClient()`,
`request()` qui rend un `Crawler`, `xmlHttpRequest()` vu comme AJAX, 302 sans
suivi puis 200 sur `/hello` après `followRedirect()`, deux conteneurs
différents entre deux requêtes et le même après `disableReboot()`, `loginUser()`
conservé à la deuxième requête avec état et perdu en `stateless`, 500 par
défaut puis `RuntimeException('kaboom')` après `catchExceptions(false)` ;
`restart()`, `loginUser()` et `doRequest()` lus, et le passage de
`testing.rst` sur `disableReboot()` et l'étiquette `kernel.reset`.

**Questions.** Aucune question non holdout ne porte sur ce point. Une recherche
filtrée a touché une question `HOLDOUT` ; son texte n'a pas été affiché, et
elle n'est désignée ici par aucun identifiant, item ni contenu. Signal pour le
propriétaire : **au moins une question holdout du lot 13 mérite sa revue.**

**Aiguilles de smoke test.** `seulement celles que le client a suivies seul` et
`de nouveau 302`, absentes de la version `master` de la page et des fichiers de
cartes ; contrôle d'aiguilles anciennes : aucune régression.

**Contrôles réellement exécutés le 2026-10-08** : `php bin/cert validate`
0 bloquant ; `php bin/cert coverage` 163 / 163, inchangé ; `php bin/cert build`
exit 0 ; 11 audits exit 0, FINDINGS 0 ; 34 blocs `run:` parsent ;
`composer gate-full` exit 0 — 299 tests, 17 641 assertions, TOTAL VIOLATIONS: 0 ;
`prove_framework_rules_fail.py` et `prove_flashcard_coverage_fails.py` PROOF
OK ; `aud10 --prove`, `lot27 --prove` exit 0 ; empreinte SHA-256 de `content/`
et `docs/` identique avant / après les preuves.

### Page 4 — *Crawler object (CssSelector and DomCrawler components)*

`CRS-zg5jg6hqp2mj` · STANDARD · **786 → 805 mots** sur 900.

**Déploiement précédent, lu en production** : page 3 du lot 13 (PR #359,
`e45e1b1`), run Pages 37825878998, success — `ok  second-pass  lot-13 client:
back() skips only redirects followed in followRedirects() mode`.

**Une généralisation.** Le tableau de parcours disait que `reduce($fn)` « ne
garde que les nœuds pour lesquels la fonction rend `true` » — la formule de
`testing/dom_crawler.rst` (8.0). Lu dans `Crawler::reduce()` (dom-crawler 8.0) :
un nœud n'est retiré que si la fonction rend **strictement** `false`
(`false !== $closure(...)`), ce que dit aussi `components/dom_crawler.rst` :
« To remove a node, the anonymous function must return `false` ». Les deux
lectures coïncident pour une fonction qui rend un booléen, et divergent sinon.
Exécuté sur trois `<li>` : une fonction qui rend `$i === 1` en garde un ; une
fonction sans `return`, trois ; une fonction qui rend `0`, trois. La cellule
reprend désormais la règle du code, et le tableau d'exécution porte le cas sans
`return`. La source `Crawler` de la page est re-vérifiée.

**Le reste de la page, confirmé par exécution** (DomCrawler 8.0.15) : les sept
lignes du tableau d'extraction et de parcours, `eq()`, `first()`, `last()`,
`nextAll()`, `previousAll()`, `children()`, `extract()`, un parcours qui ne
modifie pas le `Crawler` d'origine, et les trois origines d'un `Form` (bouton,
`<form>`, champ) avec les valeurs envoyées ; le message de `filter()` sans
CssSelector lu dans `Crawler`, et le premier argument de `submitForm()` relu
dans `testing.rst`.

**Questions et cartes.** Aucune carte ni question non holdout ne porte sur
`reduce()`.

**Aiguilles de smoke test.** `tout autre retour garde le` et `une fonction
sans`, absentes de la version `master` de la page et des fichiers de cartes ;
contrôle d'aiguilles anciennes : aucune régression.

### Page 8 — *Request and response objects introspection*

`CRS-xkp7v8142jt1` · STANDARD · **767 → 805 mots** sur 900.

**Déploiement précédent, lu en production** : le même que pour la page 4
ci-dessus (PR #359, run 37825878998).

**Une généralisation.** La page disait que `_controller` porte le contrôleur
« au format `Classe::méthode` ». Lu dans
`AttributeRouteControllerLoader::configureRoute()` (FrameworkBundle 8.0) : pour
une méthode `__invoke`, la valeur est le **nom de la classe seul**. Exécuté
dans un `WebTestCase` : `…\Pages::hello` pour une route posée par attribut sur
une méthode, `…\Inv` pour une classe invocable — et, pour une route déclarée
en PHP avec `controller([Arr::class, 'a'])`, un tableau. La page précise les
deux formes de l'attribut ; la source est ajoutée.

**Carte.** `FLC-b385mvgx5jvc` (RECALL) répondait « `_controller`, au format
`Classe::méthode` ». Réponse précisée : `Classe::méthode` pour une route posée
par attribut sur une méthode, la classe seule pour un contrôleur invocable ;
explication complétée, source ajoutée.

**Le reste de la page, confirmé par exécution** (FrameworkBundle 8.0.15) : les
classes rendues par les sept accesseurs, avant et après la première requête
(`BadMethodCallException` et son message, historique vide, pot de cookies
utilisable), les trois lectures d'URI, les deux messages d'échec
(`assertSame()` contre `assertResponseStatusCodeSame()`), le tableau de
redirection et `back()` qui revient sur `/go` — cohérent avec la correction de
la page 3.

**Questions.** La question VALIDATION sur `_route` est exacte ; aucune
question non holdout ne porte sur la forme de `_controller`.

**Aiguilles de smoke test.** `Pour un contrôleur invocable` et `omet le nom de
la méthode`, absentes de la version `master` de la page et des fichiers de
cartes ; contrôle d'aiguilles anciennes : aucune régression.

**Contrôles réellement exécutés le 2026-10-08**, pour les pages 4 et 8 :
`php bin/cert validate` 0 bloquant ; `php bin/cert coverage` 163 / 163,
inchangé ; `php bin/cert build` exit 0 ; 11 audits exit 0, FINDINGS 0 ;
34 blocs `run:` parsent ; `composer gate-full` exit 0 — 299 tests,
17 641 assertions, TOTAL VIOLATIONS: 0 ; `prove_framework_rules_fail.py` et
`prove_flashcard_coverage_fails.py` PROOF OK ; `aud10 --prove`,
`lot27 --prove` exit 0 ; empreinte SHA-256 de `content/` et `docs/` identique
avant / après les preuves.

## Bilan du lot 13

9 pages relues : **1 corrigée** (3 *Client object*), **2 précisées** (4
*Crawler object*, 8 *Request and response objects introspection*),
6 inchangées. Deux cartes corrigées (`FLC-n4wy9v7h0cps`, `FLC-b385mvgx5jvc`) ;
aucune question non holdout modifiée. Signal pour le propriétaire : au moins une
question holdout du lot 13 mérite sa revue (voir la page 3).

## Lot 14

| # | Page | Résultat |
|---|---|---|
| 1 | Configuration (including DotEnv and ExpressionLanguage components) | **précisée** — voir ci-dessous |
| 2 | Error handling | relue, exacte |
| 3 | Code debugging | relue, exacte |

**Pages relues sans défaut**, exécutées avec les composants 8.0 du bac à sable :
page 2, dans un **vrai contrôleur frontal** (Runtime 8.0, serveur web de PHP) —
une clé de tableau absente rend 500 et une `ErrorException` en debug, 200 et un
avertissement journalisé sans debug ; dans un `WebTestCase`, ce même cas rend
200 en debug, car le gestionnaire d'erreurs de PHPUnit intercepte
l'avertissement avant celui de Symfony, d'où le choix du contrôleur frontal —,
les cinq statuts du tableau dans les deux modes, les gabarits
`error404.html.twig` et `error.html.twig` sans debug, l'ordre de
`ErrorListener::logKernelException()` et de `FlattenException` lu, le défaut
`%kernel.debug%` de `php_errors.throw` lu dans le code et la référence,
`error_pages.rst` pour les quatre niveaux, les variables du gabarit, la
sécurité sur une 404 et les routes `/_error` ; page 3, les `composer.json` de
FrameworkBundle et d'ErrorHandler (VarDumper requis hors `--dev`), puis, noyau
en `prod` sans DebugBundle derrière le serveur web de PHP, `function_exists('dump')`
vrai, un `dump()` qui rend 200 avec le dump HTML avant le contenu et les
en-têtes de la réponse perdus (`Cache-Control` et un en-tête maison absents,
présents sur une route sans dump), `router:match` avec et sans `-v`, et
`debug:config framework exceptions` qui affiche `log_level: null` pour une
classe déclarée sans cette clé ; `var_dumper.rst` lu.

### Page 1 — *Configuration (including DotEnv and ExpressionLanguage components)*

`CRS-b7wpm5anh7c6` · STANDARD · **897 → 897 mots** sur 900.

**Déploiement précédent, lu en production** : pages 4 et 8 du lot 13
(PR #360, `15109f0`), run Pages 37827682223, success — `ok  second-pass
lot-13 crawler: reduce() removes a node only when the closure returns false`
et `ok  second-pass  lot-13 introspection: _controller of an invokable
controller is the class name alone`.

**Une généralisation que la documentation contredit elle-même.** La page
disait qu'une vraie variable d'environnement « l'emporte **toujours** » sur les
fichiers `.env`, qui « ne font qu'ajouter ce qui manque ». `configuration.rst`
(8.0) écrit bien « always win », mais décrit deux paragraphes plus loin
l'exception : `loadEnv()`, `bootEnv()` et `populate()` acceptent
`overrideExistingVars: true`, qui laisse les fichiers écraser une variable du
système. Exécuté avec Dotenv 8.0.15, `K=fromshell` dans le shell et
`K=fromdotenv` dans `.env` :

| Appel | `$_SERVER['K']` | `getenv('K')` |
|---|---|---|
| `loadEnv()` | `fromshell` | `fromshell` |
| `loadEnv(…, overrideExistingVars: true)` | `fromdotenv` | `fromshell` |
| `bootEnv(…, overrideExistingVars: true)` | `fromdotenv` | `fromshell` |

La page précise la règle (« par défaut ») et nomme l'option, dans le corps, les
pièges et les points clés. Le budget `REV-001` étant presque atteint, la phrase
sur `variables_order` est resserrée pour que le volume reste à 897 mots ; la
source `Dotenv` de la page est re-vérifiée.

**Carte.** `FLC-nb0mh2t8j7r9` (TRAP) répondait « La vraie variable
d'environnement, toujours […] ils n'écrasent rien », alors que sa propre source
cite le défaut `false` de `$overrideExistingVars`. Réponse et explication
précisées ; la section *Overriding Environment Variables Defined By The System*
de `configuration.rst` est ajoutée comme source.

**Question.** `QST-8h7ynmfznjpe` (LEARNING) : la bonne réponse — la vraie
variable — reste juste par défaut ; son explication disait « never
overwritten ». Explication précisée (« not overwritten, unless Dotenv is called
with overrideExistingVars set to true ») ; choix et version inchangés, comme
pour la correction d'explication de la PR #295. Aucune autre question non
holdout ne porte sur ce point.

**Le reste de la page, confirmé par exécution** : la cascade `.env` dans ses
trois cas et deux environnements, `bootEnv()` avec un `.env.local.php` (une clé
absente n'est plus définie), la syntaxe (`'${E}'` littéral, `"${E}"` interpolé,
`${NOPE:-defaut}`), les quatre références du tableau des processeurs sur un
conteneur compilé, `evaluate()`, `compile()`, `parse()`, la signature de
`lint()` et ses cinq cas ; `dotenv:dump` présent dans le composant, et
`configuration.rst` pour la lecture à chaque requête.

**Aiguilles de smoke test.** `sauf si Dotenv est appelé avec` et `qui
ajoutent seulement ce qui manque`, absentes de la version `master` de la page et
des fichiers de cartes ; contrôle d'aiguilles anciennes : aucune régression.

**Contrôles réellement exécutés le 2026-10-08** : `php bin/cert validate`
0 bloquant ; `php bin/cert coverage` 163 / 163, inchangé ; `php bin/cert build`
exit 0 ; 11 audits exit 0, FINDINGS 0 ; 34 blocs `run:` parsent ;
`composer gate-full` exit 0 — 299 tests, 17 641 assertions, TOTAL VIOLATIONS: 0 ;
`prove_framework_rules_fail.py` et `prove_flashcard_coverage_fails.py` PROOF
OK ; `aud10 --prove`, `lot27 --prove` exit 0 ; empreinte SHA-256 de `content/`
et `docs/` identique avant / après les preuves.

## Bilan du lot 14

3 pages relues : **1 précisée** (1 *Configuration*), 2 inchangées. Une carte
précisée (`FLC-nb0mh2t8j7r9`) et une explication de question LEARNING précisée
(`QST-8h7ynmfznjpe`, choix et version inchangés).

## Lot 15

| # | Page | Résultat |
|---|---|---|
| 1 | Deployment best practices | relue, exacte |
| 2 | Web Profiler, Web Debug Toolbar and Data collectors | relue, exacte |

**Pages relues sans défaut** : page 1, `deployment.rst` 8.0 pour les étapes,
les commandes, `.env.local.php`, `--empty`, `dotenv:dump` et
`requirements-checker`, `Kernel::getProjectDir()` lu puis exécuté (noyau dans
`app/src/` : `app/src` sans `composer.json`, `app` avec) ; page 2, le tableau
d'injection de la barre ré-exécuté dans un `WebTestCase` avec WebProfilerBundle
8.0.15 (HTML avec `</body>` seul injecté ; JSON, 302, HTML sans `</body>` et XHR
sans barre ; `X-Debug-Token-Link` présent dans les cinq cas), et lus dans le
code 8.0 : les conditions de `WebDebugToolbarListener::onKernelResponse()`, la
purge une fois sur dix au-delà de `2 * 86400` secondes dans
`FileProfilerStorage`, `ProfilerListener` et `Profiler::saveProfile()` pour
`collect()` et `lateCollect()` ; `profiler.rst` pour le reste.

## Bilan du lot 15

2 pages relues : **aucune erreur**. Aucune carte ni question modifiée.

## Lot 16

| # | Page | Résultat |
|---|---|---|
| 1 | Internationalization and localization | relue, exacte |

**Page relue sans défaut**, exécutée avec Translation 8.0.15 et Twig : `strtr`
sans `+intl-icu` (`Hello {Ann}!` avec la clé `name`, `Hello Ann!` avec
`{name}`), clé absente rendue telle quelle, repli de `es_AR` calculé
`es_419 > es > en` et résolu sur `es_419`, échappement du paramètre par le
filtre `|trans` et non par la balise `{% trans %}` ; `setFallbackLocales()`
avec `default_locale` à défaut de `fallbacks` lu dans `FrameworkExtension` ;
`translation.rst` pour les emplacements, la priorité clé par clé, le
`cache:clear`, YAML ou XLIFF, `trans_default_domain` et la locale parente.

## Bilan du lot 16

1 page relue : **aucune erreur**. Aucune carte ni question modifiée.

## Bilan de la seconde passe

Relecture des lots 02 à 16 terminée le 2026-10-08. Chiffres **recomptés par
script** : les résultats par page depuis les tableaux de ce journal, les cartes
et questions par comparaison `git` des fichiers `content/flashcards/` et
`content/questions/` entre `18fb176` (dernier commit avant la seconde passe) et
`a7eb972` (PR #361).

| Lot | Pages | Corrigées | Précisées | Exactes |
|---|---|---|---|---|
| 02 | 10 | 2 | 1 | 7 |
| 03 | 15 | 6 | 0 | 9 |
| 04 | 14 | 3 | 2 | 9 |
| 05 | 12 | 2 | 0 | 10 |
| 06 | 14 | 1 | 0 | 13 |
| 07 | 13 | 0 | 1 | 12 |
| 08 | 8 | 0 | 0 | 8 |
| 09 | 12 | 0 | 0 | 12 |
| 10 | 12 | 0 | 0 | 12 |
| 11 | 7 | 0 | 0 | 7 |
| 12 | 9 | 0 | 0 | 9 |
| 13 | 9 | 1 | 2 | 6 |
| 14 | 3 | 0 | 1 | 2 |
| 15 | 2 | 0 | 0 | 2 |
| 16 | 1 | 0 | 0 | 1 |
| **Total** | **141** | **15** | **7** | **119** |

Les 141 pages relues sont exactement les 141 items atomiques officiels des lots
02 à 16 dans la matrice (`lot-02` : 10 … `lot-16` : 1). « Corrigée » : une
affirmation était fausse ; « précisée » : vraie dans le cas courant mais
généralisée, ou complétée par un comportement du code 8.0 que la page taisait.
Aucun niveau n'a été promu ; le budget `REV-001` est resté un plafond (la page
la plus serrée, lot 14 page 1, est restée à 897 mots sur 900).

| Mesure | Valeur |
|---|---|
| PR de la seconde passe | 21, #341 à #361, toutes fusionnées par squash (un seul parent, vérifié) |
| Déploiements | 21 runs Pages, conclusions relues par l'API le 2026-10-08 : 20 smoke tests verts, 1 rouge (#350, run 37687772282), réparé par #351 |
| Cartes modifiées | 16 (aucune ajoutée ni retirée) |
| Questions non holdout modifiées | 2, LEARNING : `QST-0rxv1000z941` (lot 03), `QST-8h7ynmfznjpe` (lot 14, explication seule) |
| Questions holdout modifiées | **0** |
| Couverture | 163 / 163, inchangée |

**Dernier déploiement lu en production** : page 1 du lot 14 (PR #361,
`a7eb972`), run Pages 37829726088, success — `ok  second-pass  lot-14
configuration: a real env var wins by default, overrideExistingVars: true
reverses it`.

**Ce qui a été trouvé.** Les défauts recherchés — effets supposés absents,
ordres repris de la documentation, généralisations — étaient concentrés dans
les lots 02 à 07, 13 et 14 ; les lots 08 à 12, 15 et 16 n'en contenaient aucun,
chaque page ayant été exécutée ou lue dans le code 8.0. Trois cas de la fin
(lots 13 et 14) ont la même forme : une règle vraie par défaut, énoncée sans
son exception (`back()` et le mode `followRedirects()`, `_controller` d'un
contrôleur invocable, `overrideExistingVars`).

**Signaux holdout pour le propriétaire**, sans identifiant ni contenu : au
moins une question holdout du lot 13 mérite sa revue (point de la page 3).
Celui du lot 06 reste ouvert. Les questions holdout n'ont été ni lues ni
modifiées par la seconde passe, à l'exception de l'affichage accidentel
consigné au lot 04.

**Incidents.** Le smoke rouge de #350 (aiguille ancienne), réparé par #351 et
prévenu depuis par le contrôle d'aiguilles anciennes ; l'affichage d'une
question holdout au lot 04 ; et, le 2026-10-08, un heredoc non protégé qui a
exécuté localement le texte entre accents graves d'une entrée de journal
(`php bin/cert validate`, `coverage`, `build` ; `composer` a refusé de
tourner) — aucun fichier suivi n'a changé, l'entrée a été réécrite avant le
commit de #359.

**Confidentialité.** L'isolement holdout est **fonctionnel** — absent de
`practice.json` et d'`exam.json`, vérifié à chaque déploiement par le smoke
test — et non une confidentialité : les payloads publiés portent les bonnes
réponses.

# Extension — lots 17 à 26, puis 01

Le 2026-10-09, après le bilan des lots 02 à 16, le propriétaire a répondu « Go »
; avec l'autorisation durable « ensuite continue les autres lots », la même
méthode est appliquée aux 22 pages restantes : les lots 17 à 26 (13 pages), puis
le lot 01 (9 pages). Ces pages ont été raffinées plus tard, avec exécution ; la
relecture vise surtout la forme de défaut trouvée en fin de seconde passe — une
règle vraie par défaut, énoncée sans son exception.

## Lot 17

| # | Page | Résultat |
|---|---|---|
| 1 | HTTP Caching (reverse proxies, expiration, validation) | **précisée** — voir ci-dessous |

### Page 1 — *HTTP Caching*

`CRS-1k0ce9dvdr99` · STANDARD · **898 → 897 mots** sur 900.

**Déploiement précédent, lu en production** : bilan final de la seconde passe
(PR #362, `59a6c05`), run Pages 37832080709, success ; job *Production smoke
test* en succès.

**Deux défauts de la même famille.** Exécuté avec `HttpCache` et `Store`
(HttpKernel 8.0.15), sans cookie :

| Réponse de l'application | `Cache-Control` servi | Deux `GET` |
|---|---|---|
| `setMaxAge(60)` | `max-age=60, private` | `miss` puis `miss` |
| en-tête brut `max-age=60` | `max-age=60, private` | `miss` puis `miss` |
| `setPublic()` + `setMaxAge(60)` | `max-age=60, public` | `miss, store` puis `fresh` |
| `setSharedMaxAge(60)` | `public, s-maxage=60` | `miss, store` puis `fresh` |

1. La page décrivait son exécution comme « deux `GET` sur une route à
   `max-age=60` » donnant `miss, store` puis `fresh`. Une réponse à `max-age=60`
   seul est `private` — le défaut que la page *Caching* du lot 02 enseigne — et
   n'est jamais stockée. Le cas exécuté était nécessairement `public` ; la page
   le dit désormais.
2. La page disait que deux utilisateurs sur la même URL « reçoivent donc la
   même entrée ». Lu dans `HttpCache::forward()` (8.0) : une requête portant un
   en-tête de l'option `private_headers` — `Authorization` et `Cookie` par
   défaut — rend la réponse `private`, sauf si elle est explicitement `public`.
   Exécuté avec deux cookies : réponse non `public`, `miss` pour Alice, `miss`
   pour Bob ; réponse `public`, `miss, store` pour Alice puis `fresh` pour Bob,
   qui reçoit la page d'Alice. La page dit maintenant les deux.

Le budget `REV-001` étant presque atteint, la phrase remplacée est réécrite plus
courte : 897 mots. La source `HttpCache` est ajoutée.

**Le reste de la page, confirmé** : `trace_level` (`full` en debug, `none`
sinon, `short` sur la requête principale) lu dans `HttpCache` ; la clé de
`Store::generateCacheKey()`, URI plus corps pour `QUERY` ; les noms de
`#[Cache]` lus par réflexion (`maxage`, `smaxage`, `mustRevalidate`,
`lastModified`) ; les clés de `setCache()`, `expire()` et `setNotModified()` lus
dans `Response`.

**Questions et cartes.** Aucune carte ni question non holdout n'affirme qu'un
`max-age=60` seul est stocké, ni ne porte sur les en-têtes privés.

**Aiguilles de smoke test.** `Bob reçoit la page` et `Sinon, une requête`,
absentes de la version `master` de la page et des fichiers de cartes ; contrôle
d'aiguilles anciennes : aucune régression.

**Contrôles réellement exécutés le 2026-10-09** : `php bin/cert validate`
0 bloquant ; `php bin/cert coverage` 163 / 163, inchangé ; `php bin/cert build`
exit 0 ; 11 audits exit 0, FINDINGS 0 ; 34 blocs `run:` parsent ;
`composer gate-full` exit 0 — 299 tests, 17 641 assertions, TOTAL VIOLATIONS: 0 ;
`prove_framework_rules_fail.py` et `prove_flashcard_coverage_fails.py` PROOF
OK ; `aud10 --prove`, `lot27 --prove` exit 0 ; empreinte SHA-256 de `content/`
et `docs/` identique avant / après les preuves.

## Bilan du lot 17

1 page relue : **1 précisée**. Aucune carte ni question modifiée.

## Lot 18

| # | Page | Résultat |
|---|---|---|
| 1 | Cache | **précisée** — voir ci-dessous |

### Page 1 — *Cache*

`CRS-p6yhxmpxrzbs` · STANDARD · **896 → 897 mots** sur 900.

**Déploiement précédent, lu en production** : lot 17 (PR #363, `8c13819`), run
Pages 37901282428, success — `ok  second-pass  lot-17 http caching: a public
entry serves everyone, a Cookie request otherwise yields a private response`.

**La documentation se contredit, le code tranche.** La page disait que
`cache.adapter.system` choisit « fichiers PHP, **ou** APCu quand il est là ».
`cache.rst` (8.0) l'écrit ainsi dans une note (« either PHP files or APCu »),
mais la section *System Cache* du même fichier dit « writes to the filesystem
and chains APCu when available ». Lu dans `AbstractAdapter::createSystemCache()`
(8.0) : un `PhpFilesAdapter` seul, ou, si APCu est pris en charge (et activé en
CLI), un `ChainAdapter([ApcuAdapter, PhpFilesAdapter])` — APCu devant les
fichiers, pas à leur place. Exécuté sans APCu : un `PhpFilesAdapter`. La page
dit désormais « fichiers PHP, chaînés derrière APCu » ; la source est ajoutée.

**Le reste de la page, confirmé par exécution** (Cache 8.0.15) : `$save` à
`false` (rappel appelé à chaque `get()`, rien d'enregistré), `beta` à `INF` (rappel
sur une clé présente, `isHit()` vrai) et à `0`, `tag()` puis `invalidateTags()`
(seul l'item étiqueté disparaît) ; lus : les défauts `cache.adapter.filesystem`
et `cache.adapter.system` des pools, la liste des adaptateurs préconfigurés et
l'étanchéité des pools par espace de noms dans `cache.rst`.

**Questions et cartes.** Aucune carte ni question non holdout ne porte sur
l'adaptateur système.

**Aiguille de smoke test.** `chaînés derrière`, absente de la version `master`
de la page et des fichiers de cartes ; contrôle d'aiguilles anciennes : aucune
régression.

## Bilan du lot 18

1 page relue : **1 précisée**. Aucune carte ni question modifiée.

## Lot 19

| # | Page | Résultat |
|---|---|---|
| 1 | Clock | relue, exacte |

**Page relue sans défaut** : les affirmations non exécutées au raffinement ont
été confrontées au code 8.0 — `now()` et le constructeur de `DatePoint`, qui
valide le modificateur par le constructeur de `DateTimeImmutable` puis
l'applique par `modify()` à l'heure de `Clock`, conformément à `clock.rst`
(« any string accepted by the DateTime constructor »).

## Bilan du lot 19

1 page relue : **aucune erreur**.

## Lot 20

| # | Page | Résultat |
|---|---|---|
| 1 | EventDispatcher | relue, exacte |
| 2 | Event | relue, exacte |

**Pages relues sans défaut** : page 1, les trois arguments passés à un écouteur
(événement, nom, répartiteur) et les trois paramètres d'`addListener()`
exécutés, la méthode par défaut et le repli `__invoke()` lus dans
`RegisterListenersPass`, `event_dispatcher.rst` pour le choix écouteur ou
abonné ; page 2, l'API de `GenericEvent` et d'`Event` relue par réflexion
(`StoppableEventInterface`, `ArrayAccess`, `IteratorAggregate`, les six méthodes
d'arguments).

## Bilan du lot 20

2 pages relues : **aucune erreur**.

## Lot 21

| # | Page | Résultat |
|---|---|---|
| 1 | Filesystem | **précisée** — voir ci-dessous |
| 2 | Finder | relue, exacte |

**Page relue sans défaut** : page 2, la citation « stateful » confrontée à
`finder.rst` (8.0), les jokers, les protocoles d'URL, `followLinks()` et la
racine des `.gitignore`.

### Page 1 — *Filesystem*

`CRS-h9nryezt8wnb` · STANDARD · **654 → 689 mots** sur 900.

**Déploiement précédent, lu en production** : le même que pour le lot 18
ci-dessus (PR #363, run 37901282428).

**Une règle reprise de la documentation, que le code restreint.** La page
disait que le troisième argument de `symlink()` « duplique le répertoire quand
le système ne gère pas les liens symboliques » — la formule de `filesystem.rst`
(8.0). Lu dans `Filesystem::symlink()` (8.0) : l'argument s'appelle
`$copyOnWindows`, et la copie par `mirror()` n'a lieu que si
`DIRECTORY_SEPARATOR` est une barre oblique inverse, c'est-à-dire sous Windows.
Exécuté sous Linux avec `true` : un lien symbolique est créé, rien n'est copié.
La page précise la condition et cite la formule de la documentation ; la source
`Filesystem` est re-vérifiée. `makePathRelative()` est exécuté (`../` et
`videos/`, les deux exemples de la documentation).

**Un défaut de rendu.** La phrase sur `symlink()` suivait le tableau
d'exécution sans ligne vide ; en GFM, elle devenait deux lignes du tableau —
vérifié dans le HTML du build (`<td><code>symlink()</code> accepte … quand</td>`).
Une ligne vide est ajoutée. Une recherche par script sur les 163 pages ne trouve
aucun autre texte collé à un tableau.

**Questions et cartes.** Aucune carte ni question non holdout ne porte sur
`symlink()`.

**Aiguilles de smoke test.** `sous Windows seulement` et `bien un lien qui est
créé`, absentes de la version `master` de la page et des fichiers de cartes ;
contrôle d'aiguilles anciennes : aucune régression.

**Contrôles réellement exécutés le 2026-10-09**, pour les lots 18 et 21 :
`php bin/cert validate` 0 bloquant ; `php bin/cert coverage` 163 / 163,
inchangé ; `php bin/cert build` exit 0 ; 11 audits exit 0, FINDINGS 0 ;
34 blocs `run:` parsent ; `composer gate-full` exit 0 — 299 tests,
17 641 assertions, TOTAL VIOLATIONS: 0 ; `prove_framework_rules_fail.py` et
`prove_flashcard_coverage_fails.py` PROOF OK ; `aud10 --prove`,
`lot27 --prove` exit 0 ; empreinte SHA-256 de `content/` et `docs/` identique
avant / après les preuves.

## Bilan du lot 21

2 pages relues : **1 précisée** (1 *Filesystem*), 1 inchangée. Aucune carte ni
question modifiée.

## Lot 22

| # | Page | Résultat |
|---|---|---|
| 1 | Mailer | **défaut établi, en attente de décision du propriétaire** — voir ci-dessous |
| 2 | Mime | relue, exacte |

**Page relue sans défaut** : page 2, `mime.rst` (8.0) pour `multipart/alternative`
(forme préférée en dernier), l'ordre de priorité des tableaux de `MimeTypes`, la
devinette par le contenu, l'extension `fileinfo` et le tag
`mime.mime_type_guesser`.

### Page 1 — *Mailer*, en attente

`mailer.rst` (8.0) écrit : « if your application has the Messenger component
installed, all emails will be sent asynchronously by default ». Exécuté dans un
noyau FrameworkBundle 8.0.15, `null://null`, un écouteur de `SentMessageEvent` :

| Messenger | Routage de `SendEmailMessage` | Pendant `send()` |
|---|---|---|
| activé | aucun | le courriel part (1 `SentMessageEvent`) |
| activé | vers `in-memory://` | rien ne part ; 1 message en attente |

Le code ne diffère l'envoi que si `SendEmailMessage` est routé vers un
transport asynchrone. La page, la carte `FLC-xnqfscd5c1cy` et la question
LEARNING `QST-x1q4fk8g682j` reprennent la phrase de la documentation ; la
question va plus loin : son énoncé pose « no mail-specific routing was
configured » et sa bonne réponse est « Asynchronously » — fausse pour ce cas
exécuté, où c'est le distracteur « Synchronously, until a mail message class is
explicitly routed to a transport » qui décrit le comportement. Changer la clé
d'une question contre une phrase explicite de la documentation relève d'une
contradiction de sources (CLAUDE.md, *Human approval*) : **rien n'est modifié**
avant la décision du propriétaire.

## Bilan du lot 22

2 pages relues : 1 exacte, 1 défaut établi en attente de décision.

## Lot 23

| # | Page | Résultat |
|---|---|---|
| 1 | Process | relue, exacte |

**Page relue sans défaut** : la seule affirmation non exécutée au raffinement —
une fonction de rappel reste possible après `disableOutput()` — est exécutée
(Process 8.0) : `run()` avec rappel ne lève rien et le rappel reçoit `"hello\n"`.

## Bilan du lot 23

1 page relue : **aucune erreur**.

## Lot 24

| # | Page | Résultat |
|---|---|---|
| 1 | PropertyAccess | **précisée** — voir ci-dessous |

### Page 1 — *PropertyAccess*

`CRS-5vz7w1ny9shd` · STANDARD · **751 → 795 mots** sur 900.

**Déploiement précédent, lu en production** : lots 18 et 21 (PR #364,
`52bf506`), run Pages 37902813120, success — `ok  second-pass  lot-18 cache:
cache.adapter.system chains APCu in front of the PHP files` et `ok  second-pass
lot-21 filesystem: symlink() third argument copies on Windows only`.

**Un « seulement » de trop.** La page disait que les deux défauts opposés
(index absent → `null`, propriété absente → exception) se renversent
« seulement en passant par `PropertyAccess::createPropertyAccessorBuilder()` ».
Lu dans le constructeur de `PropertyAccessor` (8.0) : il prend les mêmes
réglages en drapeaux (`$magicMethodsFlags`, `$throw` avec
`THROW_ON_INVALID_INDEX` et `THROW_ON_INVALID_PROPERTY_PATH`) ; et FrameworkBundle
les expose sous `framework.property_access` (`magic_call`, `magic_get`,
`magic_set`, `throw_exception_on_invalid_index`,
`throw_exception_on_invalid_property_path`). Exécuté :
`new PropertyAccessor(MAGIC_GET | MAGIC_SET, THROW_ON_INVALID_INDEX)` lève une
`NoSuchIndexException` sur un index absent et rend `null` sur une propriété
absente. La page cite le constructeur d'accesseur, puis les deux autres voies ;
la source `PropertyAccessor` est re-vérifiée. Aucune carte ni question non
holdout ne dit « seulement ».

**Aiguilles de smoke test.** `pas la seule voie` et `avec le seul drapeau`.

## Bilan du lot 24

1 page relue : **1 précisée**.

## Lot 25

| # | Page | Résultat |
|---|---|---|
| 1 | Runtime | **précisée** — voir ci-dessous |

### Page 1 — *Runtime*

`CRS-pm5kj5kh3gt2` · STANDARD · **755 → 770 mots** sur 900.

**Déploiement précédent** : le même que pour le lot 24 ci-dessus.

**Un point clé plus général que le corps.** Le corps de la page disait déjà,
d'après `GenericRuntime::getArgument()`, que `$_ENV` n'est ajouté à
`array $context` que si `$_SERVER` ne porte pas `PATH` — relu dans le code 8.0 ;
les *Points clés* écrivaient pourtant `array $context` = `$_SERVER` + `$_ENV`,
sans condition. La ligne nomme désormais la formule de la documentation et la
condition du code ; la source `GenericRuntime` est ajoutée. Aucune carte ni
question non holdout ne porte sur `$_ENV`.

**Aiguille de smoke test.** `selon la documentation ; le code`.

## Bilan du lot 25

1 page relue : **1 précisée**.

## Lot 26

| # | Page | Résultat |
|---|---|---|
| 1 | Serializer | **précisée** — voir ci-dessous |

### Page 1 — *Serializer*

`CRS-3z96cp2s1dka` · STANDARD · **777 → 824 mots** sur 900.

**Déploiement précédent** : le même que pour le lot 24 ci-dessus.

**Une condition non dite.** La page disait qu'une propriété sans groupe ne sort
pas quand un groupe est demandé, et que `#[Ignore]` exclut « définitivement ».
Lu dans `AbstractNormalizer::getAllowedAttributes()` (8.0) : sans fabrique de
métadonnées, la méthode rend `false` et aucun filtrage n'a lieu. Exécuté sur
Serializer 8.0.15, une classe à quatre propriétés publiques — deux avec groupe,
une sans, une marquée `#[Ignore]` — et `groups` à `public-view` :

| Normaliseur | Sortie |
|---|---|
| `new ObjectNormalizer()` | les quatre, y compris la propriété `#[Ignore]` |
| avec `ClassMetadataFactory(new AttributeLoader())` | la seule propriété du groupe |

La page précise que groupes et `#[Ignore]` sont des métadonnées, lues par le
service `serializer` du framework et ignorées par un normaliseur construit sans
fabrique — comme elle le disait déjà pour `#[SerializedName]` ; le piège porte la
condition. La source `AbstractNormalizer` est re-vérifiée. Les cartes et
questions restent justes dans le cadre du service du framework.

**Aiguilles de smoke test.** `construit sans fabrique de` et `y compris celle
marquée`.

**Contrôles réellement exécutés le 2026-10-09**, pour les lots 24, 25 et 26 :
`php bin/cert validate` 0 bloquant ; `php bin/cert coverage` 163 / 163,
inchangé ; `php bin/cert build` exit 0 ; 11 audits exit 0, FINDINGS 0 ;
34 blocs `run:` parsent ; `composer gate-full` exit 0 — 299 tests,
17 641 assertions, TOTAL VIOLATIONS: 0 ; `prove_framework_rules_fail.py` et
`prove_flashcard_coverage_fails.py` PROOF OK ; `aud10 --prove`,
`lot27 --prove` exit 0 ; empreinte SHA-256 de `content/` et `docs/` identique
avant / après les preuves. Contrôle d'aiguilles anciennes : aucune régression.

## Bilan du lot 26

1 page relue : **1 précisée**. Aucune carte ni question modifiée.

## Lot 01

| # | Page | Résultat |
|---|---|---|
| 1 | PHP API up to PHP 8.4 version | **corrigée** — voir ci-dessous |
| 2 | Object Oriented Programming | **corrigée** — voir ci-dessous |
| 3 | Attributes | **précisée** — voir ci-dessous |
| 4 | Interfaces | **corrigée** — voir ci-dessous |
| 5 | Anonymous functions and closures | **précisée** — voir ci-dessous |
| 6 | Abstract classes | **corrigée** — voir ci-dessous |
| 7 | Exception and error handling | **corrigée** — voir ci-dessous |
| 8 | Traits | **corrigée** — voir ci-dessous |
| 9 | Enums | **corrigée** — voir ci-dessous |

Les neuf pages du lot 01 ne portaient aucune trace d'exécution. Chaque
affirmation testable l'a été sur **PHP 8.4.19**, et confrontée à `php/doc-en`
(branche `master`) ou à `php-src` — les `UPGRADING` 8.0 à 8.4 ont été lus, mais
seul `PHP-8.4` est une source autorisée par `source-map.yml` ; les citations
ajoutées viennent donc de `php/doc-en`.

**Déploiement précédent, lu en production** : lots 24 à 26 (PR #365,
`1df52c6`), run Pages 37904380899, success — `ok  second-pass  lot-24
propertyaccess: …`, `ok  second-pass  lot-25 runtime: …`, `ok  second-pass
lot-26 serializer: …`.

### Page 1 — *PHP API up to PHP 8.4 version*

`CRS-722pscs6e2m0` · STANDARD · **400 → 485 mots** sur 900.

- **Un exemple de hook trompeur.** `set => $this->first = explode(…)[0]` laisse
  croire à une propriété calculée. Selon *Property hooks* (doc-en), la valeur
  d'une forme courte `set =>` est écrite **dans la propriété elle-même**.
  Exécuté : `isVirtual()` faux, et `fullName` stocke `"Ann"`. L'exemple passe à
  la forme bloc, avec `$first` et `$last` déclarées : propriété virtuelle, rien
  de stocké (exécuté) ; la page explique le piège de la forme courte.
- **Un piège périmé en 8.4.** « `readonly` … écrite une fois, depuis la portée de
  déclaration » : *Properties* (doc-en) — « As of PHP 8.4.0, readonly properties
  are implicitly protected(set), so may be set from child classes ». Exécuté :
  une classe fille initialise la propriété `readonly` du parent ;
  `isProtectedSet()` vrai.
- **Un terme faux.** « propriétés `final const` » (8.1) : `UPGRADING` 8.1 —
  « the final modifier for class constants » ; corrigé en « constantes de
  classe `final` ».
- **Carte** `FLC-ef5rh3zk8shg` : « quelles sont les **quatre** nouveautés de
  langage de PHP 8.4 », alors que la page en liste cinq et que `UPGRADING` 8.4
  (*New Features > Core*) compte aussi `new` déréférençable. Recto et verso
  corrigés.

### Page 2 — *Object Oriented Programming*

`CRS-3424z948caan` · **431 → 449 mots**. Même affirmation périmée sur
`readonly` (« depuis la portée de déclaration »), dans le corps et les points
clés : corrigée, avec la même source et la même exécution.

### Page 3 — *Attributes*

`CRS-tqcp2kn9r3b5` · **360 → 377 mots**. La page énumérait les arguments admis
(littéral, constante, constante de classe, cas d'énumération, tableau) en
omettant **`new`**, admis depuis PHP 8.1 (*migration81 / new in Initializers*,
doc-en : « … and as attribute arguments »). Exécuté :
`#[Outer([new Inner('a')])]` s'instancie. L'appel de fonction reste refusé à
la compilation (exécuté : « Constant expression contains invalid operations »),
et répéter un attribut sans `IS_REPEATABLE` ne lève qu'au `newInstance()`,
comme la page le disait. Question `QST-trxpf8mvw13a` (LEARNING) : explication
complétée de `new` ; choix et version inchangés.

### Page 4 — *Interfaces*

`CRS-e4y7gtn5k4f0` · **524 → 563 mots**. La raison donnée au refus d'une
propriété `readonly` pour un `set` d'interface (« elle ne s'écrit qu'une fois,
depuis sa portée de déclaration ») est fausse. *Object Interfaces* (doc-en) :
« The interface declaration applies only to public read and write access ».
Exécuté sur `interface I { public string $name { get; set; } }` :

| Membre de la classe | Résultat |
|---|---|
| `public readonly string $name;` | « Set access level of C::$name must be omitted (as in class I) » |
| `public private(set) string $name;` | la même erreur |
| `public string $name;` | satisfait |
| `public string $name { get => $this->raw; set => $this->raw = $value; }` | satisfait — `isVirtual()` faux |
| `public string $name { set => trim($value); }` | satisfait |

**Question VALIDATION `QST-4bawjd57a598`** — « Which class member fails to
satisfy it? », à réponse unique : **deux** de ses choix échouaient, `readonly`
(la clé) et `public private(set)`, présenté comme satisfaisant. Passée en
version 2 : le choix `CHO-9jjg0nsbqdek` (`private(set)`) est remplacé par
`CHO-0nnnetzjjdtw` (`public string $name { set => trim($value); }`, frappé par
`bin/cert id:mint`), qui satisfait la déclaration ; l'explication du choix à
hooks ne parle plus de propriété virtuelle ; l'explication générale donne la
vraie raison et retrace la version 1. La clé reste `readonly`.

### Page 5 — *Anonymous functions and closures*

`CRS-e7m4xbcxegyc` · **395 → 428 mots**. « `bind()` et `bindTo()` … échouent
sur une closure `static` » : seulement quand on leur passe un objet. Exécuté :
`Closure::bind($static, new B())` rend `null` avec l'avertissement « Cannot
bind an instance to a static closure » ; avec `null` pour objet et une portée
de classe, la closure `static` est rendue et lit un membre privé de cette
classe. *Closure::bindTo* (doc-en) : « Static closures cannot have any bound
object …, but this method can nevertheless be used to change their class
scope ».

### Page 6 — *Abstract classes*

`CRS-0jtjh77tabt1` · **314 → 336 mots**.

- Le tableau disait qu'une interface n'a **pas de constructeur**. Exécuté : une
  interface déclare `__construct(int $x)`, une classe conforme s'instancie, une
  signature incompatible est une erreur fatale. *Object Interfaces* (doc-en) :
  « Although they are supported, including constructors in interfaces is
  strongly discouraged ». Cellule corrigée.
- « Une méthode abstraite … jamais `private` » : vrai dans une classe
  (exécuté : « cannot be declared private »), faux dans un trait depuis PHP 8.0
  (*Traits*, doc-en ; exécuté). Précisé.
- « La classe fille doit implémenter toutes les méthodes abstraites » : sauf à
  être elle-même abstraite. Précisé.
- **Question** `QST-e35gsy2e4vr3` (LEARNING) : « Which declaration causes a
  fatal error? », clé `abstract private` sans dire où la méthode est déclarée ;
  passée en version 2, l'énoncé précise « in an abstract class, not in a
  trait », l'explication cite le cas du trait.

### Page 7 — *Exception and error handling*

`CRS-yc9fry0vz4gh` · **372 → 396 mots**.

- Le schéma plaçait `ArgumentCountError` directement sous `Error`. Exécuté :
  `get_parent_class('ArgumentCountError')` rend `TypeError`. Schéma corrigé ;
  les autres parents du schéma sont confirmés par la même exécution.
- « `finally` … exécuté dans tous les cas » / « s'exécute toujours » : exécuté,
  un `exit()` dans le `try` arrête le script sans passer par le `finally`.
  Précisé dans le commentaire du code, les pièges et les points clés ; le
  `return` du `finally` qui écrase celui du `try` est confirmé.
- **Question** `QST-hdx34ew6s8ah` (LEARNING) : l'explication d'un distracteur
  disait « it always runs regardless » ; corrigée, clé et version inchangées.

### Page 8 — *Traits*

`CRS-bshgpd44ykba` · **353 → 365 mots**. « `$x instanceof Timestampable` ne
compile pas » est faux. Exécuté : `php -l` ne signale rien, l'expression rend
`false` sur une instance d'une classe qui utilise le trait, et `class_uses()`
le liste. Corrigé. Confirmés : conflit non arbitré avec `as` seul, erreur
fatale ; constantes et méthodes statiques dans un trait.

### Page 9 — *Enums*

`CRS-3sggmeyd01x6` · **383 → 406 mots**.

- « Une enum pure n'a pas de `->value`. Y accéder est une erreur » : exécuté,
  c'est un avertissement « Undefined property » et la valeur `null`, sans
  exception. Corrigé.
- « toute modification indirecte est une erreur fatale » : exécuté, modifier
  `->value` directement ou par référence lève une `Error` rattrapable
  (« Cannot modify readonly property », « Cannot indirectly modify readonly
  property »). Corrigé.
- Confirmés : `from()` et sa `ValueError`, `array_column(Suit::cases(),
  'value')`, l'alias par constante, la redéfinition de `from()` refusée.

**Holdout.** Deux questions `HOLDOUT` du lot 01 répondent aux motifs de
recherche de ces corrections ; leur texte n'a pas été affiché et elles ne sont
désignées ici par aucun identifiant. Signal pour le propriétaire : **au moins
une question holdout du lot 01 mérite sa revue.**

**Aiguilles de smoke test.** Deux par page, absentes de la version `master` des
pages et du fichier de cartes ; contrôle d'aiguilles anciennes : aucune
régression.

**Contrôles réellement exécutés le 2026-10-09** : `php bin/cert validate`
0 bloquant ; `php bin/cert coverage` 163 / 163, inchangé ; `php bin/cert build`
exit 0 ; 11 audits exit 0, FINDINGS 0 — dont `aud05_question_bank` et
`aud10_answer_length_bias` après les modifications de questions ; 34 blocs
`run:` parsent ; `composer gate-full` exit 0 — 299 tests, 17 641 assertions,
TOTAL VIOLATIONS: 0 ; `prove_framework_rules_fail.py` et
`prove_flashcard_coverage_fails.py` PROOF OK ; `aud10 --prove`,
`lot27 --prove` exit 0 ; empreinte SHA-256 de `content/` et `docs/` identique
avant / après les preuves.

### Incident — le smoke test de la PR #366 a échoué

La PR #366 (lot 01) a été fusionnée (`f4920c0`) et déployée : le job *Deploy*
est en succès, les pages servies portent les corrections, mais le job
*Production smoke test* a échoué deux fois (runs 37906049116 et 37906161874)
sur une seule aiguille — `Second-pass correction missing from
lot-01/abstract-classes: sp01abs:no-unless-abstract`. Les 17 autres aiguilles
du lot 01 et toutes les aiguilles plus anciennes étaient présentes.

**Cause.** Le source et le markdown généré portent bien `sauf à être`. Le HTML
construit porte `sauf \x00à être` : un octet NUL inséré avant le `à`. Une
aiguille `grep -F` qui traverse cet endroit échoue donc. Mesuré sur le build : **169 pages HTML** contiennent au total
241 octets NUL, toujours insérés juste avant un caractère multi-octets (`à`,
`é`, `—`), parfois deux de suite, y compris au milieu d'un mot
(`D\x00éveloppe`) ; aucun autre type de fichier n'en contient. La position
varie d'une page à l'autre : sur ce build, `ne doit rien à la` (lot 04) n'est
pas touchée. Toute aiguille qui contient un caractère accentué peut donc
échouer à un build ultérieur, si le contenu qui la précède change.
Le phénomène est identique avec `docusaurus build --no-minify`, et le module
JavaScript compilé de la page est propre : les NUL naissent au rendu serveur.
`@docusaurus/core/lib/client/renderToHtml.js` y emploie
`renderToPipeableStream` de react-dom 18.3.1 et renvoie à
`facebook/react#31134` et `facebook/docusaurus#9985` (non lus : `github.com`
n'est pas joignable d'ici).

**Effet visible.** Dans un nœud texte, le navigateur ignore le NUL. Dans un
attribut, il le remplace par « � » : 20 `aria-label` (« D�velopper la
catégorie »), 3 `id` de titres et 1 `href` de sommaire
(`lot-03/backward-compatibility-promise`, `#points-cl�és`) sont touchés.
Défaut préexistant du site, hors du périmètre de la seconde passe ; signalé au
propriétaire avec une proposition de correctif, non appliqué ici.

**Réparation du smoke test.** L'aiguille `sauf à être` est remplacée par
`elle-même abstraite`, issue de la même correction et absente de la page
avant elle ; elle est présente dans le HTML construit. Les 170 aiguilles de
`pages.yml` ont été vérifiées contre le build local : toutes présentes. Aucune
aiguille n'est retirée ni affaiblie.

## Bilan du lot 01

9 pages relues : **7 corrigées** (1, 2, 4, 6, 7, 8, 9), **2 précisées** (3, 5).
Une carte corrigée (`FLC-ef5rh3zk8shg`). Quatre questions modifiées : une
VALIDATION (`QST-4bawjd57a598`, version 2, un choix remplacé) et trois LEARNING
(`QST-e35gsy2e4vr3` en version 2 ; `QST-trxpf8mvw13a` et `QST-hdx34ew6s8ah`,
explications seules). Aucune question holdout lue ni modifiée.
