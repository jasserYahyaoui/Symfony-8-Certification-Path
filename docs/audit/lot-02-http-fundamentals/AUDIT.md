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

**P0 ouverts : 0. P1 ouverts : 0.**

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
