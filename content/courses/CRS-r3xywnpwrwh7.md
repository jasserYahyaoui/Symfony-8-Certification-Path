---
id: CRS-r3xywnpwrwh7
official_item: OIT-j2vjdxcer4ft
title: "Forms theming"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/form/form_themes.rst"
    anchor: "how-form-themes-work"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Changer le HTML produit par le rendu d'un formulaire, au bon niveau de
spécificité.

## Ce qu'est un thème

Un thème est un gabarit Twig qui définit les **blocs** utilisés pour rendre les
champs. Symfony en fournit plusieurs, dont `form_div_layout.html.twig`, le thème
par défaut.

## La chaîne de recherche

À chaque champ, Symfony calcule un nom de bloc et le cherche du **plus
spécifique au plus général**. Pour un champ `contact` de type `EmailType` dans un
formulaire nommé `user` :

```text
_user_contact_widget   →  absent ? on remonte
email_widget           →  absent ? on remonte
text_widget            →  trouvé dans form_div_layout.html.twig
```

C'est cette chaîne qui permet de personnaliser à n'importe quel niveau : un seul
champ d'un seul formulaire, tous les champs d'un type, ou tous les champs tout
court.

Le bloc le plus spécifique commence par un **souligné** et porte le nom du
formulaire puis celui du champ.

## Les parties d'un champ

Chaque champ se décompose, et chaque partie a son bloc :

| Suffixe | Partie |
|---|---|
| `_row` | le conteneur complet |
| `_label` | le libellé |
| `_widget` | le champ de saisie |
| `_help` | le texte d'aide |
| `_errors` | les erreurs |

## Où appliquer un thème

**Globalement**, par `twig.form_themes` :

```yaml
twig:
    form_themes: ['bootstrap_5_layout.html.twig']
```

L'ordre compte : la liste est parcourue **de la fin vers le début**, si bien que
le dernier thème listé est consulté en premier et le premier sert de dernier
recours.

**Pour un gabarit**, par la balise `{% form_theme %}` :

```html
{% form_theme form 'form/fields.html.twig' %}
```

**Dans le gabarit courant**, avec le mot-clé `_self` :

```html
{% form_theme form _self %}
{% block _user_email_widget %}
    <div class="email-field">{{ block('form_widget_simple') }}</div>
{% endblock %}
```

## Points clés

- Un thème est un gabarit de blocs ; le défaut est `form_div_layout.html.twig`.
- Recherche du plus spécifique au plus général :
  `_form_champ_partie` → `type_partie` → `text_partie`.
- Cinq parties : `_row`, `_label`, `_widget`, `_help`, `_errors`.
- `twig.form_themes` globalement, `{% form_theme %}` par gabarit, `_self` pour
  définir les blocs sur place.

## Sources officielles

- [How to Work with Form Themes](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/form/form_themes.rst)
