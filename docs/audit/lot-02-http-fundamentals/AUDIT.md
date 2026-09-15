# Lot 02 — audit page par page

**Date :** 2026-09-15 · **Méthode :** lecture intégrale de chaque cours, puis
vérification de chaque affirmation forte contre la source officielle Symfony 8.0
réellement récupérée (`raw.githubusercontent.com` répond `200` ; `github.com`
répond `403` dans ce conteneur, politique d'accès de la session).

## Méthode : ce qui compte comme trou

Un trou n'est retenu que si les **trois** conditions tiennent :

1. une question `hard` du lot le teste ;
2. le cours ne l'énonce pas ;
3. une source officielle l'établit, citée avec son ancre ou sa ligne.

Deux raccourcis mécaniques ont été essayés **et rejetés** ailleurs dans ce
projet : le compte de mots par outcome (signale des cours suffisants) et le
recouvrement de termes réponse ↔ cours (invalide, les cours sont en français et
les questions en anglais). La méthode retenue est la lecture.

## Anomalies

| ID | Page | Description | Criticité | Source officielle | Statut |
|---|---|---|---|---|---|
| L02-A01 | HTTP request | ne citait pas `components/http_foundation.rst`, page primaire du composant | P2 | `components/http_foundation.rst` | **CORRIGÉE** |
| L02-A02 | HTTP request | `Request::createFromGlobals()` et `Request::create()` absents de **tout** le corpus, alors que ce sont les deux points d'entrée du composant | P2 | `http_foundation.rst` l. 37-41, 235 | **CORRIGÉE** |
| L02-A04 | HTTP methods | `QUERY` apparaît 4 fois dans le cours **sans être jamais expliqué** ; 2 questions en dépendent, dont une `hard` | P2 | `Request.php` l. 1444-1465 | **CORRIGÉE** |
| L02-A05 | Caching | `isNotModified()` s'ouvre sur une garde `isMethodCacheable()` — non enseigné, alors que `QST-vzjgas8c9fd1` (`hard`) teste exactement ce point | **P1** | `Response.php` l. 1118-1122 | **CORRIGÉE** |
| L02-A06 | Caching | `setSharedMaxAge()` appelle `setPublic()` lui-même — non enseigné, alors que `QST-m8byv8zcsa8m` (`hard`) teste ce point | **P1** | `Response.php` l. 841-847 | **CORRIGÉE** |
| L02-A07 | Cookies | les défauts de `Cookie::create()` ne sont nulle part, alors que `QST-kk9459fpx2v0` (`hard`) les interroge | **P1** | `Cookie.php` l. 75 | **CORRIGÉE** |
| L02-A08 | HTTP request | quelle adresse de `X-Forwarded-For` renvoie `getClientIp()` — non enseigné, alors que `QST-z116xknpac5j` (`hard`) le demande | **P1** | `Request.php` l. 798-824 | **CORRIGÉE** |
| L02-A09 | les 10 pages | la section « Sources officielles » ne contenait **aucun lien** : prose non cliquable. Les `official_sources` du front matter ne sont pas rendues par le générateur | P2 | — | **CORRIGÉE** |

### Anomalies levées par l'examinateur indépendant (voir `GRILL.md`)

| ID | Page | Description | Criticité | Source | Statut |
|---|---|---|---|---|---|
| L02-E01 | HTTP request | **`getClientIp()` ne renvoie pas « la plus à gauche »** — règle fausse, introduite par moi lors de la correction de L02-A08, mise en gras dans les pièges, et illustrée par le seul exemple où l'erreur ne se voit pas. Erreur de **sécurité** | **P0** | `Request.php` l. 2146-2183, `array_reverse()` | **CORRIGÉE** |
| L02-E02 | RFC 9110 | « remplace RFC 7230 à 7235 » inclut 7234, que 9110 n'obsolète pas — RFC 9111 le fait | **P1** | en-têtes `Obsoletes:` des deux RFC | **CORRIGÉE** |
| L02-E03 | HttpClient | « ces méthodes lèvent » englobait `getStatusCode()`, qui ne lève pas | **P1** | Contracts `ResponseInterface.php` l. 27-32 | **CORRIGÉE** |
| L02-E04 | Caching | le `Cache-Control` par défaut est `no-cache, private`, pas `private` | **P1** | `ResponseHeaderBag.php` l. 239-248 | **CORRIGÉE** |
| L02-E05 | Caching | `If-None-Match` a priorité sur `If-Modified-Since` — présentés comme symétriques | **P1** | `Response.php` l. 1144-1148 (`elseif`) | **CORRIGÉE** |
| L02-E06 | HTTP response | le constructeur de `RedirectResponse` valide son statut : 304 refusé, 201 accepté | P2 | `RedirectResponse.php` l. 41-43 | **CORRIGÉE** |
| L02-E07 | HTTP response | `new Response()` porte `HTTP/1.0` ; rôle de `prepare()` | P2 | `Response.php` l. 202-208 | **CORRIGÉE** |
| L02-E08 | Language detection | `getPreferredLanguage()` retombe sur `$locales[0]`, jamais `null` | P2 | `Request.php` `return $locales[0];` | **CORRIGÉE** |
| L02-E09 | HTTP request | `X-HTTP-Method-Override` agit sur tout `POST` sans activation ; `_method` l'exige ; `getRealMethod()` | P2 | `Request.php` l. 1202-1249 | **CORRIGÉE** |
| L02-E10 | Cookies | les défauts de `fromString()` diffèrent de `create()` et ne sont **pas** sûrs | P2 | `Cookie.php` l. 41-50 | **CORRIGÉE** |
| L02-E11 | Cookies | `removeCookie()` n'efface rien chez le client, contrairement à `clearCookie()` | P2 | `ResponseHeaderBag.php` l. 166-169 vs 220-222 | **CORRIGÉE** |
| L02-E12 | Content negotiation | `getAcceptableContentTypes()` trie sur `q` puis l'ordre d'écriture, garde les `q=0` ; la spécificité ne s'y applique pas | P2 | `AcceptHeader.php` l. 151-156 | **CORRIGÉE** |
| L02-E13 | HttpClient | `timeout` = inactivité, `max_duration` = total (0 = illimité), `max_redirects = 20` | P2 | `HttpClientInterface.php` l. 41, 55-56 | **CORRIGÉE** |
| L02-E14 | HTTP methods | RFC 9110 §9.2.3 définit une sémantique de cache pour `GET`, `HEAD` **et `POST`** — divergence avec `isMethodCacheable()` | P2 | RFC 9110 §9.2.3 | **CORRIGÉE** |
| L02-E15 | Status codes | `303 See Other` et `HTTP_PERMANENTLY_REDIRECT = 308` absents | P2 | RFC 9110 §15.4.4 ; `Response.php` l. 45-46 | **OUVERTE — budget** |

**L02-E15 reste ouverte à dessein.** *Status codes* est `MINIMAL` : plafond
`REV-001` de 400 mots, occupé à 376. Le niveau d'un item est un constat, jamais
une cible ; le promouvoir pour faire entrer du contenu est précisément ce que
`CLAUDE.md` interdit. L'anomalie est donc **nommée et laissée ouverte** plutôt
que dissimulée derrière une promotion.

**P0 ouverts : 0. P1 ouverts : 0. P2 ouverts : 1 (L02-E15).**

## Observations — ni défauts, ni à corriger

**O-1. La documentation amont est en retard sur le code.**
`components/http_foundation.rst` écrit que `request` est un `ParameterBag`
**ou** un `InputBag` « if the data is coming from `$_POST` ». Le code 8.0
déclare `public InputBag $request;` (`Request.php` l. 101) : propriété typée,
donc toujours un `InputBag`. Le cours suit le **code**, qui est la source la
plus forte, et il a raison. Conservé tel quel, et signalé ici parce qu'une
question d'examen rédigée d'après la prose de la doc serait trompeuse.

**O-2. Les 16 questions `hard` du lot ont été relues une à une** contre leur
cours. Douze étaient déjà enseignées ; quatre ne l'étaient pas, et sont les
anomalies P1 ci-dessus.

**O-3. `QUERY` est absent de `http_foundation.rst`.** Le seul témoin officiel de
son traitement par Symfony est le code (`isMethodSafe`, `isMethodIdempotent`,
`isMethodCacheable`). L'explication ajoutée s'en tient à ce que le code établit.

## Périmètre : notions écartées, et pourquoi

La mission énumère des sujets à examiner. Ceux-ci n'appartiennent pas au lot 02 :

| Notion | Où elle vit | Pourquoi pas ici |
|---|---|---|
| sessions | item *Sessions* | sujet distinct du syllabus |
| fichiers téléversés, `UploadedFile` | *Handling file upload* (Forms) | l'item porte le téléversement, pas la requête |
| reverse proxy Symfony, stratégies de cache | *HTTP Caching* (Miscellaneous) | le cours *Caching* le dit explicitement en tête |
| choix du locale par la route | *User's locale guessing* (Routing) | le cours *Language detection* le dit explicitement |
| `getRequestFormat()`, formats | *Content negotiation* | déjà traité par cette page |
| trusted hosts | aucun item du lot 02 | `setTrustedHosts()` n'est interrogé par aucune question du lot ; l'ajouter serait élargir le périmètre sans demande du syllabus (§1.4) |
