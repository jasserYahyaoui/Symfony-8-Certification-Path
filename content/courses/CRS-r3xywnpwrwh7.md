---
id: CRS-r3xywnpwrwh7
official_item: OIT-j2vjdxcer4ft
title: "Forms theming"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-25"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/form/form_themes.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/form/form_themes.rst"
    anchor: "how-form-themes-work"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/Resources/views/Form/form_div_layout.html.twig"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Resources/views/Form/form_div_layout.html.twig"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "block email_widget, form_widget_simple"
    verified_at: "2026-09-25"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/FormRenderer.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/FormRenderer.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "FormRenderer::searchAndRenderBlock()"
    verified_at: "2026-09-25"
outcome_sections:
  OUT-tdmyt1j1t4xx: "La chaîne de recherche"
  OUT-zphxh7z24zht: "La chaîne de recherche"
  OUT-ak7tm95dph6x: "Les parties d'un field"
  OUT-n3gb1pbjv5sn: "Où appliquer un theme"
  OUT-yz2mbwg9ee93: "Trois exemples documentés qui échouent"
---

## Objectif

Changer le HTML produit par le rendu d'un form, au bon niveau de
spécificité.

## Ce qu'est un theme

Un theme est un template Twig qui définit les **blocks** utilisés pour rendre les
fields. Symfony en fournit plusieurs, dont `form_div_layout.html.twig`, le theme
par défaut.

## La chaîne de recherche

Chaque field porte la liste `block_prefixes` de sa lignée de types, plus un
préfixe unique. Pour un field `contact` de type `EmailType` dans un form
nommé `user`, exécuté avec `symfony/form` 8.0.15 :

```text
["form", "text", "email", "_user_contact"]
```

Le rendu cherche du **plus spécifique au plus général**, dans tous les themes
à chaque niveau avant de descendre :

```text
_user_contact_widget   →  absent ? on descend
email_widget           →  trouvé dans form_div_layout.html.twig
text_widget            →  (jamais atteint ici)
form_widget
```

Le theme par défaut **définit `email_widget`**, et **aucun** theme livré ne
définit `text_widget`. Conséquence vérifiée par rendu réel : un `text_widget`
personnalisé change les fields `TextType`, mais **pas** les fields `EmailType`,
dont le block plus spécifique est trouvé d'abord. Chaque field remonte ainsi sa
propre lignée jusqu'à `form_`.

Le block le plus spécifique commence par un **souligné** et porte le nom du
form puis celui du field.

## Les parties d'un field

| Suffixe | Partie |
|---|---|
| `_row` | le container complet |
| `_label` | le libellé |
| `_widget` | le field de saisie |
| `_help` | le texte d'aide |
| `_errors` | les erreurs |

## Où appliquer un theme

**Globalement**, par `twig.form_themes`. La liste est parcourue **de la fin vers
le début** : le dernier theme listé est consulté en premier. Exécuté avec deux
themes définissant le même block : c'est toujours le dernier qui gagne.

**Pour un template**, par le tag `{% form_theme form 'form/fields.html.twig' %}`.

**Dans le template courant**, avec `_self` — à une condition que la documentation
signale : le template doit **étendre** un autre template. Rendu seul, ses blocks
sont affichés comme du contenu ordinaire ; exécuté, le block apparaît tel quel en
tête de page et le field garde son rendu par défaut.

## Trois exemples documentés qui échouent

La page `form_themes.rst` (8.0) s'appuie sur un block `text_widget` que le code
ne fournit pas. Rendu réel avec twig-bridge 8.0.15 :

| Exemple documenté | Résultat |
|---|---|
| `email_widget` qui enveloppe `{{ form_widget(form) }}` | le field devient `<input type="text">` : le type `email` est perdu |
| `{% use 'form_div_layout.html.twig' %}` puis `text_widget` avec `parent()` | `RuntimeError` : aucun trait ne définit `text_widget` |
| `use … with text_widget as base_text_widget` | `RuntimeError` : block non défini dans le trait |

Le premier s'explique par la string : l'appel imbriqué descend à `text_widget`,
absent, puis à `form_widget`, dont `form_widget_simple` prend `text` par défaut.
Pour envelopper un field e-mail sans perdre son type, on importe le theme par
défaut avec `{% use 'form_div_layout.html.twig' %}` et on appelle `parent()`
dans `email_widget`, qui, lui, existe : exécuté, l'output garde
`type="email"`.

## Pièges d'examen

**La liste des themes est parcourue de la fin vers le début.**

**`text_widget` n'existe dans aucun theme livré** ; `email_widget`, si.

**Un field remonte sa propre lignée** : un block de type parent ne s'applique
pas quand un block plus spécifique existe.

**`_self` exige un template qui en étend un autre.**

**Redéfinir `_row` quand on voulait `_widget` efface libellé, aide et erreurs.**

## Points clés

- Theme = template de blocks ; défaut `form_div_layout.html.twig`.
- Recherche : préfixe unique, type, types parents, `form_`.
- Cinq parties : `_row`, `_label`, `_widget`, `_help`, `_errors`.
- `twig.form_themes` : le dernier gagne ; `{% form_theme %}` ; `_self` avec
  `extends`.

## Sources officielles

- [How to Work with Form Themes](https://github.com/symfony/symfony-docs/blob/8.0/form/form_themes.rst)
- [Twig Bridge 8.0, `form_div_layout.html.twig`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Resources/views/Form/form_div_layout.html.twig)
- [Form 8.0, `FormRenderer`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/FormRenderer.php)
