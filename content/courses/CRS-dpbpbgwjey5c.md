---
id: CRS-dpbpbgwjey5c
official_item: OIT-yh6zx9shv9vs
title: "URLs generation"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/twig_reference.rst"
    anchor: "path"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Générer une URL depuis un gabarit. Le générateur lui-même et ses quatre types de
référence sont traités dans le lot Routing.

## Deux fonctions

```html
<a href="{{ path('blog_show', {slug: post.slug}) }}">Lire</a>
<a href="{{ url('blog_show', {slug: post.slug}) }}">Lien absolu</a>
```

| Fonction | Résultat |
|---|---|
| `path()` | un **chemin** absolu : `/blog/mon-article` |
| `url()` | une **URL** absolue : `https://example.com/blog/mon-article` |

C'est la seule différence entre les deux. Elles prennent les mêmes arguments :
le nom de la route, puis un tableau de paramètres.

## Un troisième argument

`{{ path('blog_show', {slug: 'x'}, true) }}` produit un chemin **relatif**. Il
est rarement utile et se retient surtout pour ne pas le confondre avec `url()`.

## Ce qui vaut aussi ici

Le comportement du générateur ne change pas parce qu'on l'appelle depuis un
gabarit : un paramètre absent de la route devient une chaîne de requête, une
route en `schemes: ['https']` peut faire retourner une URL absolue à `path()`,
et une route inconnue lève `RouteNotFoundException`.

Une URL ne s'écrit jamais à la main dans un gabarit : changer le chemin d'une
route mettrait alors les liens en défaut sans qu'aucun test ne le signale.

## Pièges d'examen

**Les deux fonctions ne diffèrent que par la forme du résultat** — chemin absolu
ou URL absolue — et prennent exactement les mêmes arguments. Il n'y a pas de
différence de comportement au-delà.

**La fonction de chemin peut retourner une URL absolue.** Si la route exige un
schéma différent de celui de la requête courante, il faut bien changer de
schéma : la sortie devient absolue sans qu'on l'ait demandé.

**Un paramètre absent de la route part en chaîne de requête**, ici comme
ailleurs — une faute de frappe ne produit aucune erreur.

## Points clés

- `path()` = chemin absolu, `url()` = URL absolue ; mêmes arguments.
- Troisième argument `true` : chemin relatif.
- Un paramètre hors route devient une chaîne de requête.
- Jamais d'URL écrite à la main.

## Sources officielles

- [Symfony Twig Reference, `path` et `url`](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/twig_reference.rst)
