---
id: CRS-qa0x33akw264
official_item: OIT-fhjmv9w3wxsb
title: "Language detection"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Request.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "Request::getLanguages line 1663, Request::getPreferredLanguage line 1633"
    verified_at: "2026-09-01"
---

## Objectif

Lire la préférence linguistique du client depuis la requête HTTP.

> **Périmètre.** Cette page couvre le mécanisme HTTP. Le choix du locale par la
> route appartient à l'item *User's locale guessing* (Routing), et la traduction
> à *Internationalization and localization*.

## L'en-tête

Le client envoie ses préférences, pondérées par un facteur de qualité :

```http
Accept-Language: fr-FR,fr;q=0.9,en;q=0.8,*;q=0.5
```

Sans `q`, la valeur par défaut est `1.0`. Les valeurs vont de `0` à `1`, et
`q=0` signifie explicitement **refusé**.

## Côté Symfony

```php
$request->getLanguages();
// ['fr_FR', 'fr', 'en'] — triées par préférence décroissante

$request->getPreferredLanguage(['en', 'de']);
// 'en' — le meilleur choix PARMI ceux que l'application propose
```

`getLanguages()` normalise la casse et le séparateur : `fr-FR` devient
`fr_FR`, la forme attendue par Symfony.

## Pièges d'examen

**`getPreferredLanguage()` sans argument ne négocie rien.** Elle renvoie la
première langue demandée par le client, que l'application sache la servir ou
non. Passer la liste des locales supportées est ce qui rend l'appel utile.

**`Accept-Language` est une préférence, pas une instruction.** Un serveur peut
légitimement répondre dans une autre langue ; il l'indique alors par
`Content-Language`, et doit ajouter `Vary: Accept-Language` s'il fait varier la
réponse selon cet en-tête.

## Points clés

- `Accept-Language` porte des locales pondérées par `q` (défaut `1.0`).
- `getLanguages()` renvoie la liste triée, normalisée en `fr_FR`.
- `getPreferredLanguage($supported)` négocie ; sans argument, non.
- Varier selon la langue impose `Vary: Accept-Language`.

## Sources officielles

- `Symfony\Component\HttpFoundation\Request` (branche 8.0, `6f841c0`)
