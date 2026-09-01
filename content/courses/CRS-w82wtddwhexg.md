---
id: CRS-w82wtddwhexg
official_item: OIT-ns36thqnh2jk
title: "Auto escaping"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/filters/escape.rst"
    anchor: "escaping-strategies"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/autoescape.rst"
    anchor: "autoescape"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    commit_sha: "5079583d7313b0f0866ca32108036afcc072127d"
    verified_at: "2026-09-01"
---

## Objectif

Savoir ce que Twig échappe par défaut, avec quelle stratégie, et comment
choisir la bonne selon l'endroit du document.

## Le défaut

L'échappement automatique est **actif** dans une application Symfony, avec la
stratégie `html`. Toute variable affichée par `{{ }}` est donc échappée sans
rien demander. C'est ce qui rend une injection HTML difficile par accident.

L'échappement a lieu **à l'affichage**, pas à l'affectation : `{% set x = y %}`
ne transforme rien.

## Les cinq stratégies

Le contexte du document décide, et le mauvais choix laisse une faille ouverte :

| Stratégie | Contexte visé |
|---|---|
| `html` | corps HTML, ou valeur d'attribut **entre guillemets** |
| `js` | chaîne JavaScript ou JSON, encodée par séquences d'échappement |
| `css` | tout ce qui est inséré dans du CSS |
| `url` | un **sous-composant** d'URI ou un paramètre, jamais une URI entière |
| `html_attr` | nom d'attribut, ou valeur d'attribut **sans guillemets** |

La distinction `html` / `html_attr` est celle qui se rate : dès que la valeur
n'est pas entre guillemets — `data-x={{ v }}` — `html` ne suffit plus.

Pour la stratégie `html`, Twig utilise la fonction PHP `htmlspecialchars`.

## Choisir explicitement

```html
{{ user.name|e }}          {# équivaut à |e('html') #}
{{ user.name|e('js') }}
{{ user.name|escape('url') }}
```

`e` est l'alias de `escape`.

## Désactiver, avec parcimonie

`{{ trusted|raw }}` affiche sans échapper. Le nom du filtre est un
avertissement : la valeur doit être sûre *par construction*, pas parce qu'elle
semble sûre.

La balise `{% autoescape %}` change la stratégie sur un bloc, et
`{% autoescape false %}` la coupe :

```html
{% autoescape 'js' %}
    {{ value }}
{% endautoescape %}
```

## Points clés

- Échappement actif par défaut, stratégie `html`, appliqué à l'affichage.
- Cinq stratégies : `html`, `js`, `css`, `url`, `html_attr`.
- `html_attr` pour un attribut sans guillemets ; `url` pour un sous-composant.
- `e` est l'alias de `escape` ; `raw` désactive pour une valeur.
- `{% autoescape %}` change ou coupe la stratégie sur un bloc.

## Sources officielles

- [Twig 3.22, filtre `escape`](https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/filters/escape.rst)
- [Twig 3.22, balise `autoescape`](https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/tags/autoescape.rst)
