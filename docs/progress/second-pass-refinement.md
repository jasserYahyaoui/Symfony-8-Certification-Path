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
