# Lot 02 — journal des modifications

Aucun fichier de `website/docs/` n'a été touché : il est généré et gitignoré.
Toutes les modifications portent sur `content/courses/`.

| Fichier | Section | Problème initial | Criticité | Modification | Source | Validation |
|---|---|---|---|---|---|---|
| `CRS-e3j0d3a8ndrf.md` (Caching) | Validation | la garde de méthode de `isNotModified()` n'était pas enseignée | P1 | ajout du `if (!$request->isMethodCacheable()) return false;` et de son sens, + piège d'examen + point clé | `Response.php` l. 1118-1122 | `validate` 0 bloquant |
| `CRS-e3j0d3a8ndrf.md` (Caching) | Expiration | `setSharedMaxAge()` rend la réponse publique — non dit | P1 | ajout du paragraphe, du piège et du point clé, avec le contraste `setMaxAge()` | `Response.php` l. 841-847 | idem |
| `CRS-7fa2behkxmf9.md` (Cookies) | Lire et écrire | les défauts de `Cookie::create()` n'étaient nulle part | P1 | nouveau bloc « Ce que `Cookie::create()` pose sans qu'on le demande » : tableau des 5 défauts, dont `secure=null` auto-activé en HTTPS | `Cookie.php` l. 75 et docblock | idem |
| `CRS-222wq64mqxye.md` (HTTP request) | Client et proxys | l'adresse renvoyée par `getClientIp()` n'était pas précisée | P1 | ajout de l'ordre de `X-Forwarded-For`, exemple chiffré, mention de `getClientIps()` | `Request.php` l. 798-824 | idem |
| `CRS-222wq64mqxye.md` (HTTP request) | *(nouvelle)* D'où vient l'objet | `createFromGlobals()` / `create()` absents du corpus entier | P2 | nouvelle section, plus un piège les distinguant | `http_foundation.rst` l. 37-41, 235 | idem |
| `CRS-222wq64mqxye.md` (HTTP request) | front matter | la page primaire du composant n'était pas citée | P2 | 3ᵉ `official_source` ajoutée, ancre `accessing-request-data`, passage exact en `symbol_or_lines` | `http_foundation.rst` | `SRC-001`, `SRC-002` verts |
| `CRS-erfvgqsx1z2p.md` (HTTP methods) | Le tableau / Pièges | `QUERY` affiché 4 fois, jamais expliqué | P2 | paragraphe d'explication + piège « les cacheables sont trois, pas deux », relié à `isNotModified()` | `Request.php` l. 1444-1465 | idem |
| **les 10 cours** | Sources officielles | section en prose, **aucun lien cliquable** | P2 | listes réécrites en liens Markdown, tous en `/blob/`, avec les lignes exactes | sources ci-dessus | 143/143 liens dont l'objet existe |
| `docs/audit/.../README.md` | — | lien interne mort vers l'ADR 0003 | P3 | corrigé en `0003-docusaurus-presentation-layer.md` | — | `LNK-001` vert |

## Fichiers examinés et **non** modifiés

| Fichier | Pourquoi aucune modification |
|---|---|
| `CRS-depbnbc0g82x.md` (RFC 9110) | contenu exact ; les distinctions sûre/idempotente y sont déjà traitées finement. Seule la liste de sources est devenue cliquable |
| `CRS-984c2bv3wh09.md` (Status codes) | 401/403, 301/302/307/308, 204/200 déjà traités ; aucune question `hard` |
| `CRS-hdsq2qdz7qdv.md` (HTTP response) | le piège `isRedirect()` / `isRedirection()` est enseigné avec le code source exact, vérifié ligne à ligne |
| `CRS-dab7evz3fe29.md` (Content negotiation) | la question `hard` du lot (Vary manquant) est enseignée |
| `CRS-qa0x33akw264.md` (Language detection) | `MINIMAL`, aucune question `hard`, périmètre explicitement borné |
| `CRS-ez2qmqfk902e.md` (HttpClient) | les 2 questions `hard` sont enseignées explicitement |
| `website/docs/courses/lot-02/**` | **généré et gitignoré** — l'éditer est écrasé au prochain build (ADR-0003) |
| toutes les banques de questions | aucune réponse fausse trouvée ; les 4 faits manquants étaient dans les **cours**, pas dans les questions |

## Erreurs commises pendant ce travail, et consignées

- **Vérificateur de liens fautif.** Ma première extraction capturait le
  guillemet fermant du YAML, produisant **140 faux `404`**. Corrigée, elle donne
  143/143. Une anomalie inventée aurait été pire qu'une anomalie manquée.
- **Lien interne mort dans mon propre README**, attrapé par `LNK-001` du dépôt.
