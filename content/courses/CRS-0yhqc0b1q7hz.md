---
id: CRS-0yhqc0b1q7hz
official_item: OIT-dmkbj2x94rks
title: "CSRF protection"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-08"
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
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Security/Csrf/SameOriginCsrfTokenManager.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Security/Csrf/SameOriginCsrfTokenManager.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "isValidOrigin: Sec-Fetch-Site first, then Origin and Referer"
    verified_at: "2026-10-08"
outcome_sections:
  OUT-p1tsfr91bmqy: "Ce qui est automatique"
  OUT-nhv282ce36br: "Ce qui est automatique"
  OUT-9may3eay9wcj: "Les options du form"
  OUT-vtp031jk3dbg: "Les options du form"
  OUT-wc1yk5bm6nw6: "Tokens avec ou sans état"
---

## Objectif

Savoir ce que le component Form protège automatiquement, et comment le
configurer. La vérification manuelle par `isCsrfTokenValid()` dans un controller
appartient au lot Controllers.

## Ce qui est automatique

Quand la protection est activée, un form ajoute un **field caché** portant
un token, et vérifie ce token à la submission. Un form soumis sans token
valide n'est pas valide : `isValid()` retourne `false`, sans qu'aucun code ne
teste quoi que ce soit.

Exécuté avec `symfony/form` et `security-csrf` 8.0 : la vue du form `task`
porte les enfants `title` et `_token` ; soumis sans token, il est invalide avec
l'erreur « The CSRF token is invalid. Please try to resubmit the form. » ; avec
le token, il est valide.

`FormTypeCsrfExtension` ajoute ce field dans `finishView()`, sur le form
racine seulement. Il est rendu par `form_end()`, ce qui explique pourquoi
désactiver `render_rest` fait disparaître le token.

## Les options du form

Déclarées dans `configureOptions()` :

| Option | Role | Défaut |
|---|---|---|
| `csrf_protection` | active ou désactive la protection pour ce form | la configuration globale |
| `csrf_field_name` | le nom du field caché | `_token` |
| `csrf_token_id` | la string servant à générer le token | voir ci-dessous |
| `csrf_message` | le message d'erreur | « The CSRF token is invalid… » |

Sans `csrf_token_id`, le code prend le défaut configuré pour le type, sinon **le
nom du form**, sinon la class du type. Exécuté : le form `task` a
pour identifier `task`, et son token est refusé par un form `other`.

Un identifier **différent par form** améliore la security des tokens avec
état : un token valable pour un form ne l'est alors pas pour un autre.

La protection s'active globalement par `framework.csrf_protection` ; les
options de form se règlent sous `framework.form.csrf_protection`
(`token_id`, `field_name`).

## Tokens avec ou sans état

Les tokens **avec état** sont stockés en session : les utiliser démarre une
session. C'est le mode de FrameworkBundle quand rien n'est configuré, et celui
où l'identifier par form compte.

Les tokens **sans état** ne dépendent pas de la session : la page reste
cachable. On les déclare par identifier, avec
`framework.csrf_protection.stateless_token_ids`. Pour les valider, Symfony
vérifie les headers **`Origin`** et **`Referer`** : si l'un correspond à
l'origine de l'application, le token est accepté.

Le code 8.0 (`SameOriginCsrfTokenManager::isValidOrigin()`) ajoute une règle
que la documentation ne dit pas : le header `Sec-Fetch-Site`, s'il est présent,
décide seul. Exécuté : `Origin` correct mais `Sec-Fetch-Site: cross-site`,
refusé ; `Origin` étranger mais `same-origin`, accepté. Pour une question
d'examen, la règle documentée reste celle à citer.

La documentation 8.0 précise qu'ils sont **activés par défaut dans une
application Flex** : la configuration fournie déclare
`stateless_token_ids: ['submit', 'authenticate', 'logout']` et donne
`submit` comme identifier par défaut aux form types autoconfigurés.

## Hors form

Pour un `<form>` écrit à la main, le token se produit dans le template :

```html
<input type="hidden" name="token" value="{{ csrf_token('delete-item') }}">
```

L'argument est l'identifier de token — une string arbitraire, à faire
correspondre à la vérification côté controller.

## Pièges d'examen

**Le token part avec le tag de closure du form.** Désactiver
`render_rest` le fait disparaître.

**Un form sans token valide est invalide** ; c'est le component qui
refuse.

**L'identifier par défaut est le nom du form**, pas une valeur commune.

**Sans état ne veut pas dire sans contrôle** : ce sont les headers `Origin` et
`Referer` qui sont vérifiés.

## Points clés

- Field `_token` ajouté au form racine et vérifié à la submission.
- `csrf_protection`, `csrf_field_name`, `csrf_token_id`, `csrf_message`.
- Identifier par défaut : défaut du type, sinon nom du form.
- Avec état : session ; sans état : `Origin`/`Referer`, activé par défaut avec
  Flex pour `submit`.
- `csrf_token('id')` en Twig pour un form écrit à la main.

## Sources officielles

- [CSRF protection](https://github.com/symfony/symfony-docs/blob/8.0/security/csrf.rst)
- [Form 8.0, `FormTypeCsrfExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Csrf/Type/FormTypeCsrfExtension.php)
- [FrameworkBundle 8.0, `Configuration`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php)
