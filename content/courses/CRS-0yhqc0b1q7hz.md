---
id: CRS-0yhqc0b1q7hz
official_item: OIT-dmkbj2x94rks
title: "CSRF protection"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-25"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/security/csrf.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/security/csrf.rst"
    anchor: "csrf-protection-in-symfony-forms"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/Extension/Csrf/Type/FormTypeCsrfExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Csrf/Type/FormTypeCsrfExtension.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "FormTypeCsrfExtension::buildForm(), finishView(), configureOptions()"
    verified_at: "2026-09-25"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "addCsrfSection(), addFormSection()"
    verified_at: "2026-09-25"
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

Exécuté avec `symfony/form` et `security-csrf` 8.0 : la vue du formulaire `task`
porte les enfants `title` et `_token` ; soumis sans jeton, il est invalide avec
l'erreur « The CSRF token is invalid. Please try to resubmit the form. » ; avec
le jeton, il est valide.

`FormTypeCsrfExtension` ajoute ce champ dans `finishView()`, sur le formulaire
racine seulement. Il est rendu par `form_end()`, ce qui explique pourquoi
désactiver `render_rest` fait disparaître le jeton.

## Les options du formulaire

Déclarées dans `configureOptions()` :

| Option | Rôle | Défaut |
|---|---|---|
| `csrf_protection` | active ou désactive la protection pour ce formulaire | la configuration globale |
| `csrf_field_name` | le nom du champ caché | `_token` |
| `csrf_token_id` | la chaîne servant à générer le jeton | voir ci-dessous |
| `csrf_message` | le message d'erreur | « The CSRF token is invalid… » |

Sans `csrf_token_id`, le code prend le défaut configuré pour le type, sinon **le
nom du formulaire**, sinon la classe du type. Exécuté : le formulaire `task` a
pour identifiant `task`, et son jeton est refusé par un formulaire `other`.

Un identifiant **différent par formulaire** améliore la sécurité des jetons avec
état : un jeton valable pour un formulaire ne l'est alors pas pour un autre.

La protection s'active globalement par `framework.csrf_protection` ; les
options de formulaire se règlent sous `framework.form.csrf_protection`
(`token_id`, `field_name`).

## Jetons avec ou sans état

Les jetons **avec état** sont stockés en session : les utiliser démarre une
session. C'est le mode de FrameworkBundle quand rien n'est configuré, et celui
où l'identifiant par formulaire compte.

Les jetons **sans état** ne dépendent pas de la session : la page reste
cachable. On les déclare par identifiant, avec
`framework.csrf_protection.stateless_token_ids`. Pour les valider, Symfony
vérifie les en-têtes **`Origin`** et **`Referer`** : si l'un correspond à
l'origine de l'application, le jeton est accepté.

La documentation 8.0 précise qu'ils sont **activés par défaut dans une
application Flex** : la configuration fournie déclare
`stateless_token_ids: ['submit', 'authenticate', 'logout']` et donne
`submit` comme identifiant par défaut aux types de formulaire autoconfigurés.

## Hors formulaire

Pour un `<form>` écrit à la main, le jeton se produit dans le gabarit :

```html
<input type="hidden" name="token" value="{{ csrf_token('delete-item') }}">
```

L'argument est l'identifiant de jeton — une chaîne arbitraire, à faire
correspondre à la vérification côté contrôleur.

## Pièges d'examen

**Le jeton part avec la balise de fermeture du formulaire.** Désactiver
`render_rest` le fait disparaître.

**Un formulaire sans jeton valide est invalide** ; c'est le composant qui
refuse.

**L'identifiant par défaut est le nom du formulaire**, pas une valeur commune.

**Sans état ne veut pas dire sans contrôle** : ce sont les en-têtes `Origin` et
`Referer` qui sont vérifiés.

## Points clés

- Champ `_token` ajouté au formulaire racine et vérifié à la soumission.
- `csrf_protection`, `csrf_field_name`, `csrf_token_id`, `csrf_message`.
- Identifiant par défaut : défaut du type, sinon nom du formulaire.
- Avec état : session ; sans état : `Origin`/`Referer`, activé par défaut avec
  Flex pour `submit`.
- `csrf_token('id')` en Twig pour un formulaire écrit à la main.

## Sources officielles

- [CSRF protection](https://github.com/symfony/symfony-docs/blob/8.0/security/csrf.rst)
- [Form 8.0, `FormTypeCsrfExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Csrf/Type/FormTypeCsrfExtension.php)
- [FrameworkBundle 8.0, `Configuration`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php)
