# Lot 02 — inventaire exhaustif

Généré depuis les fichiers canoniques le 2026-09-15. Onze pages rendues :
les dix ci-dessous plus `index.md`, produit par `DocsGenerator` et non éditable.

| # | Page rendue | Item officiel | Niveau | Mots | Outcomes | Sources citées | Statut initial |
|---|---|---|---|---|---|---|---|
| 1 | `http-specification-rfc-9110` | HTTP Specification (RFC 9110) | MINI | 387 | 3 | 1 | À AUDITER |
| 2 | `status-codes` | Status codes | MINI | 376 | 3 | 2 | À AUDITER |
| 3 | `http-request` | HTTP request | STAN | 416 | 4 | 2 | À AUDITER |
| 4 | `http-response` | HTTP response | STAN | 390 | 3 | 1 | À AUDITER |
| 5 | `http-methods` | HTTP methods | STAN | 448 | 3 | 2 | À AUDITER |
| 6 | `cookies` | Cookies | STAN | 343 | 4 | 1 | À AUDITER |
| 7 | `caching` | Caching | STAN | 498 | 4 | 2 | À AUDITER |
| 8 | `content-negotiation` | Content negotiation | STAN | 359 | 3 | 2 | À AUDITER |
| 9 | `language-detection` | Language detection | MINI | 242 | 3 | 1 | À AUDITER |
| 10 | `symfony-httpclient-component` | Symfony HttpClient component | STAN | 450 | 4 | 2 | À AUDITER |

## Détail par page

### `http-specification-rfc-9110` — HTTP Specification (RFC 9110)

- **Canonique** : `CRS-depbnbc0g82x.md`
- **Niveau** : `MINIMAL` — budget REV-001 : 400 mots ; occupé : **387**
- **Sections** : Objectif · Ce que RFC 9110 remplace · Vocabulaire imposé · Pièges d'examen · Points clés · Sources officielles
- **Outcomes déclarés** :
  - Situer RFC 9110 parmi 9111, 9112, 9113 et 9114
  - Énoncer que la sémantique est indépendante de la version de transport
  - Distinguer ressource et représentation
- **Sources déjà citées** :
  - `https://raw.githubusercontent.com/httpwg/httpwg.github.io/master/specs/rfc9110.html` — ancre : sections 3 Terminology, 6 Message Abstra

### `status-codes` — Status codes

- **Canonique** : `CRS-984c2bv3wh09.md`
- **Niveau** : `MINIMAL` — budget REV-001 : 400 mots ; occupé : **376**
- **Sections** : Objectif · Les cinq classes · Les codes qui comptent · Pièges d'examen · Points clés · Sources officielles
- **Outcomes déclarés** :
  - Identifier la classe d'un code de statut à partir de son premier chiffre
  - Distinguer 401 et 403 dans un scénario d'accès
  - Distinguer les redirections préservant la méthode (307/308) des autres
- **Sources déjà citées** :
  - `https://raw.githubusercontent.com/httpwg/httpwg.github.io/master/specs/rfc9110.html` — ancre : section-15
  - `https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Response.php` — ancre : Response::HTTP_* constants, lines 28-160

### `http-request` — HTTP request

- **Canonique** : `CRS-222wq64mqxye.md`
- **Niveau** : `STANDARD` — budget REV-001 : 900 mots ; occupé : **416**
- **Sections** : Objectif · Les sacs · InputBag n'accepte que des scalaires · Corps brut et méthode · Client et proxys · Pièges d'examen · Points clés · Sources officielles
- **Outcomes déclarés** :
  - Choisir le sac approprié parmi les sept exposes par Request
  - Expliquer pourquoi InputBag refuse les valeurs non scalaires
  - Utiliser all() plutôt que get() pour une entrée multiple
  - Expliquer pourquoi getClientIp() exige des trusted proxies
- **Sources déjà citées** :
  - `https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Request.php` — ancre : public bag properties lines 94-130, getC
  - `https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/InputBag.php` — ancre : InputBag::get, BadRequestException on no

### `http-response` — HTTP response

- **Canonique** : `CRS-hdsq2qdz7qdv.md`
- **Niveau** : `STANDARD` — budget REV-001 : 900 mots ; occupé : **390**
- **Sections** : Objectif · La classe de base · Les sous-classes · `isRedirect()` contre `isRedirection()` · Autres méthodes de test · Pièges d'examen · Points clés · Sources officielles
- **Outcomes déclarés** :
  - Choisir la sous-classe de Response adaptée au cas
  - Distinguer isRedirect() de isRedirection() sur des statuts précis
  - Distinguer isOk() de isSuccessful()
- **Sources déjà citées** :
  - `https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Response.php` — ancre : isRedirect line 1254, isRedirection line

### `http-methods` — HTTP methods

- **Canonique** : `CRS-erfvgqsx1z2p.md`
- **Niveau** : `STANDARD` — budget REV-001 : 900 mots ; occupé : **448**
- **Sections** : Objectif · Les trois propriétés · Le tableau · Ce que le tableau enseigne · Côté Symfony · Pièges d'examen · Points clés · Sources officielles
- **Outcomes déclarés** :
  - Classer une méthode selon sûre, idempotente et cacheable
  - Expliquer pourquoi PUT et DELETE sont idempotents sans être sûrs
  - Distinguer l'effet sur l'état du code de statut renvoyé
- **Sources déjà citées** :
  - `https://raw.githubusercontent.com/httpwg/httpwg.github.io/master/specs/rfc9110.html` — ancre : section 9.2 Common Method Properties
  - `https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Request.php` — ancre : isMethodSafe line 1444, isMethodIdempote

### `cookies` — Cookies

- **Canonique** : `CRS-7fa2behkxmf9.md`
- **Niveau** : `STANDARD` — budget REV-001 : 900 mots ; occupé : **343**
- **Sections** : Objectif · Lire et écrire · Les attributs de sécurité · Les trois valeurs de SameSite · Pièges d'examen · Points clés · Sources officielles
- **Outcomes déclarés** :
  - Poser et supprimer un cookie via la réponse
  - Associer chaque attribut de sécurité à la menace qu'il atténue
  - Énoncer la contrainte imposée par SameSite=none
  - Expliquer pourquoi la suppression exige les mêmes path et domain
- **Sources déjà citées** :
  - `https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Cookie.php` — ancre : SAMESITE_NONE, SAMESITE_LAX, SAMESITE_ST

### `caching` — Caching

- **Canonique** : `CRS-e3j0d3a8ndrf.md`
- **Niveau** : `STANDARD` — budget REV-001 : 900 mots ; occupé : **498**
- **Sections** : Objectif · Deux modèles · Expiration · Validation · Vary · Trois directives voisines · Pièges d'examen · Points clés · Sources officielles
- **Outcomes déclarés** :
  - Distinguer expiration et validation
  - Distinguer max-age de s-maxage et leurs caches respectifs
  - Distinguer no-cache de no-store
  - Justifier la présence de Vary dès qu'une réponse est négociée
- **Sources déjà citées** :
  - `https://raw.githubusercontent.com/httpwg/httpwg.github.io/master/specs/rfc9110.html` — ancre : section 12.5.5 Vary, section 8.8 Validat
  - `https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Response.php` — ancre : setPublic 609, setMaxAge 793, setSharedM

### `content-negotiation` — Content negotiation

- **Canonique** : `CRS-dab7evz3fe29.md`
- **Niveau** : `STANDARD` — budget REV-001 : 900 mots ; occupé : **359**
- **Sections** : Objectif · Le principe · Les en-têtes de requête · Côté Symfony · Les en-têtes de réponse · Pièges d'examen · Points clés · Sources officielles
- **Outcomes déclarés** :
  - Interpréter les facteurs de qualité, y compris q=0
  - Déterminer la représentation choisie face à un en-tête Accept donne
  - Annoncer le choix par Content-Type, Content-Language et Vary
- **Sources déjà citées** :
  - `https://raw.githubusercontent.com/httpwg/httpwg.github.io/master/specs/rfc9110.html` — ancre : section 12 Content Negotiation, section 
  - `https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Request.php` — ancre : getAcceptableContentTypes line 1783

### `language-detection` — Language detection

- **Canonique** : `CRS-qa0x33akw264.md`
- **Niveau** : `MINIMAL` — budget REV-001 : 400 mots ; occupé : **242**
- **Sections** : Objectif · L'en-tête · Côté Symfony · Pièges d'examen · Points clés · Sources officielles
- **Outcomes déclarés** :
  - Lire les préférences linguistiques du client
  - Distinguer getLanguages() de getPreferredLanguage() avec liste supportée
  - Connaître la normalisation fr-FR vers fr_FR
- **Sources déjà citées** :
  - `https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Request.php` — ancre : Request::getLanguages line 1663, Request

### `symfony-httpclient-component` — Symfony HttpClient component

- **Canonique** : `CRS-ez2qmqfk902e.md`
- **Niveau** : `STANDARD` — budget REV-001 : 900 mots ; occupé : **450**
- **Sections** : Objectif · L'interface · Le point central : les réponses sont paresseuses · La réponse · Options utiles · Tests · Pièges d'examen · Points clés · Sources officielles
- **Outcomes déclarés** :
  - Émettre une requête sortante via HttpClientInterface
  - Identifier le moment où la réponse est réellement attendue
  - Obtenir du parallélisme en émettant avant de lire
  - Inspecter une réponse d'erreur sans exception
- **Sources déjà citées** :
  - `https://raw.githubusercontent.com/symfony/symfony-docs/8.0/http_client.rst` — ancre : section Processing Responses, asynchrono
  - `https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Contracts/HttpClient/HttpClientInterface.php` — ancre : request line 85, stream line 93, withOpt
