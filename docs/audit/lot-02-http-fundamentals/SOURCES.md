# Lot 02 — sources officielles consultées

Session du **2026-09-15**. Toutes récupérées réellement (`curl`, code `200`)
depuis `raw.githubusercontent.com`, seule forme joignable depuis ce conteneur :
`github.com` répond `403` pour un dépôt amont sous la politique d'accès de la
session. Les liens publiés dans les cours utilisent la forme `/blob/`, qui
désigne le même objet.

| Source | Pages concernées | Ce qui y a été vérifié | Conclusion |
|---|---|---|---|
| [`components/http_foundation.rst`](https://github.com/symfony/symfony-docs/blob/8.0/components/http_foundation.rst) (1 137 l.) | HTTP request, HTTP response, Cookies | liste des sept propriétés publiques ; `createFromGlobals()` l. 37-41 ; `create()` l. 235 ; `getPayload()` renvoie un `InputBag` l. 212-216 | conforme — **sauf** la prose sur `request` (voir O-1) |
| [`Request.php`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Request.php) (2 218 l.) | HTTP request, HTTP methods, Content negotiation, Language detection | `public InputBag $request` l. 101 ; les 7 sacs l. 94-130 ; `isMethodSafe` l. 1444, `isMethodIdempotent` l. 1452, `isMethodCacheable` l. 1462 ; `getClientIps` l. 798, `getClientIp` l. 821 | le cours est exact ; `QUERY` et `PURGE` confirmés |
| [`Response.php`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Response.php) | HTTP response, Status codes, Caching | `isSuccessful` l. 1184, `isRedirection` l. 1194, `isOk` l. 1224, `isRedirect` l. 1254 avec `[201, 301, 302, 303, 307, 308]` ; `isNotModified` l. 1118 et sa garde ; `setSharedMaxAge` l. 841 appelant `setPublic()` | le cours est exact sur `isRedirect` ; **deux faits manquaient** (A-05, A-06) |
| [`Cookie.php`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Cookie.php) | Cookies | signature de `create()` l. 75 : `$path='/'`, `$httpOnly=true`, `$sameSite=SAMESITE_LAX`, `$secure=null`, `$expire=0` ; docblock de `$secure` : « or null to auto-enable this when the request is already using HTTPS » | **les défauts manquaient** (A-07) |
| [`http_cache.rst`](https://github.com/symfony/symfony-docs/blob/8.0/http_cache.rst) (389 l.) | Caching | modèles expiration / validation | conforme |
| [`http_client.rst`](https://github.com/symfony/symfony-docs/blob/8.0/http_client.rst) (2 387 l.) | Symfony HttpClient component | réponses paresseuses, sérialisation dans la boucle émettrice | conforme, les 2 questions `hard` sont enseignées |
| [`controller.rst`](https://github.com/symfony/symfony-docs/blob/8.0/controller.rst) (1 140 l.) | HTTP request | obtention de la `Request` dans un contrôleur | conforme |
| [RFC 9110](https://github.com/httpwg/httpwg.github.io/blob/master/specs/rfc9110.html) | RFC 9110, Status codes, HTTP methods, Caching, Content negotiation | §9.2 propriétés des méthodes, §15 codes, §8.8 validation, §12.5.5 `Vary` | conforme |

## Vérification de joignabilité

**143 liens `/blob/` présents dans `content/courses/`, 143 dont l'objet existe**,
établi en interrogeant leur équivalent `raw` (`200`). La joignabilité de la
forme `/blob/` elle-même n'est **pas** mesurée depuis ce conteneur : elle est
héritée par construction — même propriétaire, dépôt, référence, chemin.
