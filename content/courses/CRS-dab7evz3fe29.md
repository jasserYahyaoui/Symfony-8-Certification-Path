---
id: CRS-dab7evz3fe29
official_item: OIT-mrvrtxm4v2m5
title: "Content negotiation"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/httpwg/httpwg.github.io/master/specs/rfc9110.html"
    branch: "master"
    symbol_or_lines: "section 12 Content Negotiation, section 12.5 Proactive Negotiation Fields"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Request.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "getAcceptableContentTypes line 1783"
    verified_at: "2026-09-01"
---

## Objectif

Choisir la représentation d'une ressource à partir des en-têtes `Accept*`, et
signaler ce choix correctement.

## Le principe

Une ressource a plusieurs **représentations** — JSON ou HTML, français ou
anglais, compressée ou non. Le client décrit ses préférences ; le serveur
choisit. C'est la *négociation proactive*, celle que l'examen vise.

## Les en-têtes de requête

| En-tête | Dimension négociée |
|---|---|
| `Accept` | Type de média |
| `Accept-Language` | Langue |
| `Accept-Encoding` | Compression |
| `Accept-Charset` | Jeu de caractères (déprécié en pratique) |

```http
Accept: text/html,application/xhtml+xml,application/json;q=0.9,*/*;q=0.8
```

Chaque valeur porte un facteur de qualité `q` entre `0` et `1`, valant `1.0`
par défaut. La spécificité départage à qualité égale : `text/html` l'emporte
sur `text/*`, qui l'emporte sur `*/*`.

## Côté Symfony

```php
$request->getAcceptableContentTypes();
// ['text/html', 'application/xhtml+xml', 'application/json', '*/*']
// déjà triés par préférence décroissante

$request->getPreferredFormat();     // 'html', 'json'…
$request->getRequestFormat();       // format demandé, 'html' par défaut
```

## Les en-têtes de réponse

Le serveur annonce son choix et déclare ce dont il a dépendu :

```http
Content-Type: application/json
Content-Language: fr
Vary: Accept, Accept-Language
```

`Vary` n'est pas facultatif dès qu'on négocie : sans lui, un cache partagé
servira la première représentation obtenue à tous les clients suivants.

Si aucune représentation ne convient, la réponse correcte est
`406 Not Acceptable` — mais servir une représentation par défaut reste
généralement préférable en pratique.

## Pièges d'examen

**`q=0` refuse explicitement.** `Accept: */*;q=0.8, image/png;q=0` signifie
« tout sauf du PNG ».

**Sans `q`, la valeur est `1.0`** — pas `0`, et pas une pondération implicite
par la position.

**Oublier `Vary` est le bug de cache classique** et n'a aucun symptôme
localement, où il n'y a pas de cache partagé.

**`Accept` est une préférence, pas une contrainte.** Un serveur peut répondre
autre chose ; il l'annonce par `Content-Type`.

## Points clés

- Une ressource, plusieurs représentations ; le client préfère, le serveur choisit.
- `q` par défaut `1.0` ; `q=0` refuse ; la spécificité départage.
- `getAcceptableContentTypes()` renvoie une liste déjà triée.
- Négocier impose `Vary` ; `406` si rien ne convient.

## Sources officielles

- RFC 9110 §12 — *Content Negotiation*
- `Symfony\Component\HttpFoundation\Request` (branche 8.0, `6f841c0`)
