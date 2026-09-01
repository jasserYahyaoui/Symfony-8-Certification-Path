---
id: CRS-0yhqc0b1q7hz
official_item: OIT-dmkbj2x94rks
title: "CSRF protection"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security/csrf.rst"
    anchor: "csrf-protection-in-symfony-forms"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Savoir ce que le composant Form protège automatiquement, et comment le
configurer. La vérification manuelle par `isCsrfTokenValid()` dans un contrôleur
appartient au lot Controllers.

## Ce qui est automatique

Quand la protection est activée, un formulaire ajoute un **champ caché** portant
un jeton, et vérifie ce jeton à la soumission. Un formulaire soumis sans jeton
valide n'est pas valide : `isValid()` retourne `false`, sans qu'aucun code ne
teste quoi que ce soit.

Le champ caché est rendu par `form_end()`, ce qui explique pourquoi désactiver
`render_rest` fait disparaître le jeton.

## Les trois options du formulaire

Déclarées dans `configureOptions()` :

| Option | Rôle |
|---|---|
| `csrf_protection` | active ou désactive la protection pour ce formulaire |
| `csrf_field_name` | le nom du champ caché, `_token` par défaut |
| `csrf_token_id` | la chaîne servant à générer le jeton |

`csrf_token_id` mérite l'attention : utiliser une valeur **différente par
formulaire** améliore la sécurité, parce qu'un jeton valable pour un formulaire
ne l'est alors pas pour un autre.

La protection s'active globalement par `framework.csrf_protection`.

## Jetons avec ou sans état

Le mode par défaut est **avec état** : le jeton est stocké en session, donc la
session démarre. C'est ce qui rend `csrf_token_id` significatif.

Symfony 8 propose aussi des **jetons sans état**, générés côté client. Ils
évitent de démarrer une session pour un simple formulaire, ce qui préserve la
cachabilité de la page.

## Hors formulaire

Pour un `<form>` écrit à la main, le jeton se produit dans le gabarit :

```html
<input type="hidden" name="token" value="{{ csrf_token('delete-item') }}">
```

L'argument est l'identifiant de jeton — une chaîne arbitraire, à faire
correspondre à la vérification côté contrôleur.

## Points clés

- Protection automatique : champ caché posé et vérifié par le formulaire.
- `isValid()` échoue sur un jeton absent ou invalide.
- `csrf_protection`, `csrf_field_name` (`_token`), `csrf_token_id`.
- Un `csrf_token_id` différent par formulaire est recommandé.
- Le mode par défaut stocke le jeton en session ; le mode sans état l'évite.
- `csrf_token('id')` en Twig pour un formulaire écrit à la main.

## Sources officielles

- [CSRF protection](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security/csrf.rst)
