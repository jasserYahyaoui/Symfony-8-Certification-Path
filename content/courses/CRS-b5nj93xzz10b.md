---
id: CRS-b5nj93xzz10b
official_item: OIT-gxew257vwhm8
title: "Forms rendering with Twig"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/forms.rst"
    anchor: "rendering-forms"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Rendre un formulaire dans un gabarit, du plus court au plus contrôlé.

## Les fonctions

| Fonction | Ce qu'elle produit |
|---|---|
| `form(form)` | le formulaire entier, d'un bloc |
| `form_start(form)` | la balise `<form>` ouvrante |
| `form_end(form)` | la balise fermante, **et les champs non encore rendus** |
| `form_row(form.champ)` | libellé + widget + aide + erreurs, dans son conteneur |
| `form_label(form.champ)` | le libellé seul |
| `form_widget(form.champ)` | le champ de saisie seul |
| `form_errors(form.champ)` | les erreurs du champ |
| `form_help(form.champ)` | le texte d'aide |
| `form_rest(form)` | tous les champs non encore rendus |

## Le détail qui compte

`form_end()` rend **aussi** les champs qui n'ont pas été rendus explicitement.
C'est ce qui garantit que le jeton CSRF, qui est un champ caché, part avec le
formulaire même quand on a rendu les champs un par un.

Le passer à `form_end(form, {'render_rest': false})` désactive ce comportement —
et fait alors disparaître le jeton, avec les conséquences que cela suppose.

## Trois niveaux de contrôle

```html
{{ form(form) }}
```

```html
{{ form_start(form) }}
    {{ form_row(form.username) }}
    {{ form_row(form.email) }}
{{ form_end(form) }}
```

```html
{{ form_start(form) }}
    {{ form_label(form.username) }}
    {{ form_errors(form.username) }}
    {{ form_widget(form.username, {'attr': {'class': 'input-lg'}}) }}
{{ form_end(form) }}
```

## Les options de la balise

`form_start()` accepte l'action, la méthode et des attributs :

```html
{{ form_start(form, {'action': path('cible'), 'method': 'GET'}) }}
{{ form_start(form, {'attr': {'novalidate': 'novalidate'}}) }}
```

`novalidate` désactive la validation HTML5 du navigateur, ce qui est utile pour
observer la validation côté serveur.

## Points clés

- `form()` rend tout ; `form_row()` rend un champ complet ; `form_widget()` le
  seul champ de saisie.
- `form_end()` rend les champs restants, dont le jeton CSRF.
- `render_rest: false` supprime ce filet — rarement souhaitable.
- `form_start()` porte `action`, `method` et `attr`.

## Sources officielles

- [Forms, « Rendering Forms »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/forms.rst)
