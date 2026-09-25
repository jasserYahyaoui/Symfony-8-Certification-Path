---
id: CRS-b5nj93xzz10b
official_item: OIT-gxew257vwhm8
title: "Forms rendering with Twig"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-25"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/forms.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/forms.rst"
    anchor: "rendering-forms"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/Resources/views/Form/form_div_layout.html.twig"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Resources/views/Form/form_div_layout.html.twig"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "blocks form, form_start, form_end, form_row"
    verified_at: "2026-09-25"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/FormRenderer.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/FormRenderer.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "FormRenderer::searchAndRenderBlock()"
    verified_at: "2026-09-25"
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

Rendu réel, exécuté avec `symfony/twig-bridge` 8.0.15 et Twig 3.22, thème par
défaut, pour un champ `task` avec une aide :

```html
<!-- form_row(form.task) -->
<div><label for="task_form_task" class="required">Task</label><input type="text"
 id="task_form_task" name="task_form[task]" required="required"
 aria-describedby="task_form_task_help" /><div id="task_form_task_help"
 class="help-text">Aide</div></div>

<!-- form_widget(form.task) -->
<input type="text" id="task_form_task" name="task_form[task]" required="required" />
```

## Le détail qui compte

`form_end()` rend **aussi** les champs qui n'ont pas été rendus explicitement :
son bloc appelle `form_rest(form)` sauf si `render_rest` vaut `false`. C'est ce
qui garantit que le jeton CSRF, qui est un champ caché, part avec le formulaire
même quand on a rendu les champs un par un.

Exécuté : un champ caché non rendu explicitement apparaît avec `form_end(form)`
et disparaît avec `form_end(form, {'render_rest': false})`. Pour le jeton, cela
veut dire une soumission refusée.

## Un champ ne se rend qu'une fois

`FormRenderer` retient les champs déjà rendus. Appeler `form_row(form.task)` puis
`form_widget(form.task)` lève une **`BadMethodCallException`** : « Field "task"
has already been rendered, save the result of previous render call to a variable
and output that instead ». Pour afficher deux fois un rendu, on le stocke dans
une variable.

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

## Action, méthode, attributs

`form_start()` accepte l'action, la méthode et des attributs :

```html
{{ form_start(form, {'action': path('cible'), 'method': 'GET'}) }}
{{ form_start(form, {'attr': {'novalidate': 'novalidate'}}) }}
```

`form()` accepte les mêmes variables : son bloc appelle `form_start(form)`, qui
les reçoit. Exécuté : `form(form, {'action': '/target', 'method': 'GET'})`
produit `<form name="task_form" method="get" action="/target">`.

`novalidate` désactive la validation HTML5 du navigateur, ce qui est utile pour
observer la validation côté serveur.

## Pièges d'examen

**La fermeture du formulaire rend aussi ce qui n'a pas été rendu**, jeton CSRF
compris.

**Un champ déjà rendu ne se rend pas une seconde fois** : exception.

**`form()` règle aussi action et méthode**, par le `form_start()` de son bloc.

**Les fonctions de rendu prennent le champ, pas son nom.**

## Points clés

- `form()` rend tout ; `form_row()` un champ complet ; `form_widget()` la seule
  saisie.
- `form_end()` appelle `form_rest()` sauf `render_rest: false`.
- Un champ rendu deux fois : `BadMethodCallException`.
- `form_start()` et `form()` portent `action`, `method` et `attr`.

## Sources officielles

- [Forms, « Rendering Forms »](https://github.com/symfony/symfony-docs/blob/8.0/forms.rst)
- [Twig Bridge 8.0, `form_div_layout.html.twig`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Resources/views/Form/form_div_layout.html.twig)
- [Form 8.0, `FormRenderer`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/FormRenderer.php)
