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
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Request.php"
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
// ['fr_FR', 'fr', 'en', '*'] — triées par préférence décroissante

$request->getPreferredLanguage(['en', 'de']);
// 'en' — le meilleur choix PARMI ceux que l'application propose
```

`getLanguages()` normalise la casse et le séparateur : `fr-FR` devient
`fr_FR`, la forme attendue par Symfony.

Quand aucune langue demandée ne correspond, `getPreferredLanguage()` ne renvoie
pas `null` : il renvoie **le premier locale de la liste fournie**, traité comme
langue par défaut.

## Comment Symfony normalise un tag de langue

`getLanguages()` ne se contente pas de remplacer `-` par `_`. Chaque valeur
passe par une décomposition en **trois composants** — langue, écriture, région —
puis par une recomposition :

```php
// fr-FR      -> fr_FR
// zh-hans    -> zh_Hans     (l'écriture prend une capitale initiale)
// fr-latn-fr -> fr_Latn_FR  (la région passe en majuscules)
```

La grammaire reconnue est `2 ou 3 lettres` pour la langue, puis **4 lettres**
optionnelles pour l'écriture, puis **2 lettres** optionnelles pour la région.
Une valeur qui n'entre pas dans ce moule — ou le `*` d'un `Accept-Language` —
n'est pas rejetée : elle ressort en minuscules, inchangée. La liste est enfin
dédoublonnée, donc `fr-FR,fr-fr;q=0.8` ne produit qu'une entrée.

## Comment `getPreferredLanguage()` choisit

Avec une liste de locales supportées, la méthode ne compare pas des chaînes
égales : elle construit pour chaque langue demandée **toutes ses combinaisons**,
de la plus précise à la plus générale, et retient le premier locale supporté qui
**commence par** l'une d'elles.

Pour `fr_Latn_FR`, les combinaisons sont, dans cet ordre :

```text
fr_Latn_FR  ->  fr_Latn  ->  fr_FR  ->  fr
```

Deux conséquences que l'examen peut viser :

- un client demandant `fr` obtient `fr_CA` si c'est le premier locale supporté
  de la liste — **l'ordre de votre liste départage**, pas une proximité
  linguistique ;
- la comparaison est un **préfixe**, pas une égalité : `fr` sélectionne le
  premier supporté commençant par `fr`.

## Tips d'examen

**Trois sorties, un seul nom de méthode.** `getPreferredLanguage()` rend la
première langue *du client* sans argument ; avec une liste, le premier locale
*correspondant* ; et si rien ne correspond, **le premier de la liste**. Jamais
`null` dès qu'on lui passe une liste.

**Le séparateur trahit la source.** `fr-FR` vient du réseau, `fr_FR` de
Symfony : voir un tiret dans du code applicatif signale une valeur non
normalisée.

**Négocier la langue impose `Vary: Accept-Language`.** Sans lui, le premier
visiteur fixe la langue servie à tous les suivants par le cache partagé.

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

## Aller lire la source

- [`Request`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Request.php) — `getLanguages()`, `getPreferredLanguage()`
  (branche 8.0, `6f841c0`)
