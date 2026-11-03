---
id: CRS-2bj91rj9kvj0
official_item: OIT-b9x8az2bx4t8
title: "TwigBundle"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-11-03"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/templates.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/templates.rst"
    anchor: "template-locations-and-namespaces"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/Configuration.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/Configuration.php"
    symbol_or_lines: "strict_variables, default_path, paths, globals, form_themes"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/TwigExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/TwigExtension.php"
    symbol_or_lines: "getBundleTemplatePaths, normalizeBundleName"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/Command/DebugCommand.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Command/DebugCommand.php"
    symbol_or_lines: "debug:twig"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/Command/LintCommand.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Command/LintCommand.php"
    symbol_or_lines: "lint:twig"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-11-03"
outcome_sections:
  OUT-xw5hbxewf5xf: "Trois packages, trois roles"
  OUT-fe5bp6xb3w5m: "Où vivent les templates"
  OUT-4hq67myssdj1: "Surcharger le template d'un bundle"
  OUT-eqrycamdmt25: "Les options qui changent le comportement"
  OUT-kd4ywvpax765: "Les commands"
---

## Objectif

Savoir ce que TwigBundle ajoute à Twig, où il cherche les templates, et quelles
options de configuration comptent. La distinction component / bridge / bundle
est traitée dans le lot Symfony Architecture.

## Avant la règle : le problème à résoudre

Twig seul sait compiler et rendre un template. Il ne sait pas où vivent les
templates d'une application Symfony, ni comment surcharger celui d'un bundle
installé, ni quelles options dépendent de l'environment. C'est TwigBundle qui
répond, depuis `config/packages/twig.yaml`.

Trois questions : **quel package fait quoi**, **où Twig cherche un template**,
et **quelles options changent le comportement**.

## Le fil rouge : surcharger le profil d'AcmeBlogBundle

Un bundle installé, `AcmeBlogBundle`, rend `@AcmeBlog/user/profile.html.twig`.
L'application veut ajouter un bandeau sans toucher au bundle :

```twig
{# templates/bundles/AcmeBlogBundle/user/profile.html.twig #}
{% extends '@!AcmeBlog/user/profile.html.twig' %}

{% block body %}
    <p class="banner">Bienvenue</p>
    {{ parent() }}
{% endblock %}
```

Rejoué avec TwigBundle 8.0.15 et Twig 3.22.2 : `@AcmeBlog/user/profile.html.twig`
rend désormais le bandeau suivi du contenu d'origine ;
`@!AcmeBlog/user/profile.html.twig` rend l'original seul.

## Le cycle complet en un schéma

Lu dans `TwigExtension` et relevé avec `debug:twig` :

```mermaid
---
title: Quel file sert un template de bundle
---
flowchart TD
  accTitle: Comment Twig résout un template de bundle surchargé ou non
  accDescr: Pour un nom en @AcmeBlog, Twig cherche d'abord dans templates/bundles/AcmeBlogBundle de l'application, puis dans le directory de templates du bundle ; le premier file trouvé gagne. Pour un nom en @!AcmeBlog, seul le directory du bundle est consulté. Une surcharge qui étend @!AcmeBlog atteint donc l'original ; une surcharge qui étendrait @AcmeBlog se retrouverait elle-même.
  NAME{"nom demandé"} -->|"@AcmeBlog/…"| APP{"templates/bundles/AcmeBlogBundle/… existe ?"}
  APP -->|"oui"| OVR(["la surcharge de l'application"])
  APP -->|"non"| BUNDLE(["le template du bundle"])
  NAME -->|"@!AcmeBlog/…"| BUNDLE
  OVR -.->|"extends '@!AcmeBlog/…'"| BUNDLE
  OVR -.->|"extends '@AcmeBlog/…'"| OVR
```

La dernière flèche est un piège : la surcharge s'étend **elle-même**.

## Trois packages, trois roles

| Package | Role |
|---|---|
| `twig/twig` | l'engine : la syntaxe, le compiler, les filters de base |
| `symfony/twig-bridge` | les extensions Twig qui exposent les components Symfony — `path()`, `trans`, le rendu de form |
| `symfony/twig-bundle` | l'intégration dans le framework : configuration, paths, services |

`composer require symfony/twig-bundle` installe les trois : le bundle exige
`symfony/twig-bridge`, qui exige `twig/twig` (`^3.21|^4.0` en 8.0). C'est le
bundle qui rend Twig configurable depuis `config/packages/twig.yaml`.

## Où vivent les templates

Par défaut dans `templates/`, à la racine du projet — `twig.default_path` vaut
`%kernel.project_dir%/templates`. Rendre `product/index.html.twig` lit donc
`templates/product/index.html.twig`.

`twig.paths` **ajoute** des directories. La key est le path, la valeur un
namespace facultatif : un path sans namespace rejoint l'espace
principal, un path nommé se référence en `@Nom/...`.

Un bundle expose ses templates sous `@NomDuBundle/...` — pour `AcmeBlogBundle`,
`@AcmeBlog/user/profile.html.twig`, le suffixe `Bundle` étant retiré.

Rejoué, avec `paths: { '%kernel.project_dir%/extra': ~, '%kernel.project_dir%/extra/': Admin }` :
`main.html.twig` est trouvé dans `extra/` sans préfixe, et `@Admin/ns.html.twig`
par son namespace ; `templates/` reste servi.

## Surcharger le template d'un bundle

`TwigExtension` (8.0) enregistre, pour chaque bundle, deux emplacements dans cet
ordre :

1. `templates/bundles/AcmeBlogBundle/`, s'il existe — le nom complet, suffixe
   compris ;
2. le directory du bundle, `Resources/views/` ou `templates/`.

Le premier gagne : poser `templates/bundles/AcmeBlogBundle/user/profile.html.twig`
remplace le template du bundle sans toucher à son code. Le directory d'origine
reste accessible sous l'espace **`!AcmeBlog`**, ce qui permet à la surcharge
d'étendre l'original : `{% extends '@!AcmeBlog/user/profile.html.twig' %}`.

**Rejoué, l'oubli du point d'exclamation :** une surcharge qui étend
`@AcmeBlog/…` au lieu de `@!AcmeBlog/…` se désigne elle-même. Twig ne lève
aucune erreur explicite : le rendu tourne jusqu'à épuiser la mémoire — « Allowed
memory size … exhausted », erreur fatale PHP.

## Les options qui changent le comportement

| Option | Effet | Défaut (8.0) |
|---|---|---|
| `default_path` | déplace le directory des templates | `templates/` |
| `paths` | ajoute des directories, nommés ou non | aucun |
| `globals` | déclare des variables disponibles dans **tous** les templates | aucune |
| `form_themes` | choisit le theme de rendu des forms | `form_div_layout.html.twig` |
| `strict_variables` | fait échouer l'accès à une variable inexistante au lieu de retourner `null` | `%kernel.debug%` |
| `cache` | compile les templates en PHP dans le cache | activé |

`strict_variables` mérite d'être connue : elle décide si une faute de frappe
dans un nom de variable passe inaperçue ou lève une `Twig\Error\RuntimeError`.
Sa default value suit `kernel.debug` : **stricte en `dev`, permissive en
`prod`**. Une erreur visible en développement peut donc rendre une string vide
en production.

Rejoué, sans `strict_variables` configurée : avec `kernel.debug` vrai,
`{{ typo }}` lève « Variable "typo" does not exist » ; avec `kernel.debug` faux,
il rend une string vide. Dans les deux cas, `typo|default('fallback')`,
`typo is defined` et `user.name ?? 'anon'` passent sans erreur : ce sont les
formes prévues pour une variable facultative.

Dans `globals`, une valeur qui commence par `@` désigne un **service** ;
`@@` échappe le caractère et produit une string qui commence par `@`.
Rejoué : `greeter: '@App\…\Greeter'` donne l'object, dont `{{ greeter.hello }}`
appelle la method ; `raw_at: '@@not-a-service'` rend `@not-a-service`.

## Les commands

- `php bin/console debug:twig` — filters, functions, tests, globales et paths
  de templates ; avec un nom de template en argument, le file qui le fournit ;
  `--filter` restreint la liste ;
- `php bin/console lint:twig` — vérifie la syntaxe sans rendre ;
  `--show-deprecations` traite les deprecations comme des erreurs.

Rejoué, `debug:twig @AcmeBlog/user/profile.html.twig` affiche trois sections :
« Matched File » (`templates/bundles/AcmeBlogBundle/user/profile.html.twig`),
« Overridden Files » (le file du bundle), et « Configured Paths », où
`@AcmeBlog` liste le directory de surcharge **avant** celui du bundle.
`lint:twig` sur un `{% if %}` sans condition sort avec le code **1** ; c'est ce
code qu'une CI lit.

## Concrètement dans ton application

- Templates dans `templates/` ; un directory de plus : `twig.paths`, de
  préférence nommé.
- Changer un template de bundle : le copier sous `templates/bundles/NomBundle/`,
  et étendre `@!Nom/…`.
- Une variable facultative : `|default`, `is defined` ou `??`, jamais un pari
  sur le mode `prod`.
- Une globale qui dépend d'un service : `'@id_du_service'`.
- `lint:twig` en CI, `debug:twig <nom>` pour savoir qui sert un template.

## À comprendre

- **Pourquoi trois packages ?** Twig vit sans Symfony ; le bridge relie Twig aux
  components ; le bundle relie le tout au framework et à sa configuration.
- **Pourquoi `@!` ?** La surcharge a pris le nom `@AcmeBlog` ; il faut un autre
  nom pour atteindre ce qu'elle cache.
- **Pourquoi `strict_variables` suit-elle `kernel.debug` ?** En développement,
  une faute doit se voir ; en production, une page qui s'affiche vaut mieux
  qu'une erreur 500 — d'où l'intérêt de ne pas en dépendre.

## À mémoriser

- **Engine**, **bridge**, **bundle** ; le bundle tire les deux autres et
  configure.
- **`default_path`** remplace, **`paths`** ajoute.
- **`@AcmeBlog`** pour le namespace, **`templates/bundles/AcmeBlogBundle/`**
  pour la surcharge, **`@!AcmeBlog`** pour l'original.
- **`strict_variables`** = `%kernel.debug%`.
- **`globals`** : `@` = service, `@@` = un `@` littéral.
- **`debug:twig <nom>`** : Matched File et Overridden Files ; **`lint:twig`** :
  exit 1 sur erreur.
- Moyen mnémotechnique : **« la surcharge d'abord, le bang pour l'original »**.

## Pièges d'examen

**Trois packages, un seul à installer.** L'engine, le bridge et le bundle sont
distincts : c'est le bundle qui tire les deux autres, et c'est lui seul qui rend
Twig configurable.

**Le nom d'espace d'un bundle perd son suffixe ; le directory de surcharge le
garde.** `@AcmeBlog/...` d'un côté, `templates/bundles/AcmeBlogBundle/` de
l'autre.

**`strict_variables` n'a pas la même valeur partout.** Par défaut elle vaut
`kernel.debug`.

**Le directory par défaut se change, mais on peut aussi en ajouter.** Ce sont
deux options différentes : l'une remplace, l'autre complète.

## Application : trois scénarios

**Scénario 1 — La page qui ne finit jamais.** Après une surcharge de template
de bundle, la page plante sur « Allowed memory size exhausted ». *Solution.* La
surcharge étend `@AcmeBlog/…` : elle s'étend elle-même. Écrire `@!AcmeBlog/…`.

**Scénario 2 — Le bandeau vide en production.** Un template affiche
`{{ promo.title }}` ; en dev, une erreur quand il n'y a pas de promo, en prod une
zone vide. *Solution.* `{{ promo.title ?? '' }}` ou un test `is defined` :
le template ne dépend plus de `strict_variables`.

**Scénario 3 — Quel file est servi ?** Un template de bundle ne change pas
malgré une surcharge. *Solution.* `debug:twig @AcmeBlog/…` : si « Matched File »
pointe encore vers le bundle, le directory de surcharge est mal nommé — souvent
`templates/bundles/AcmeBlog/`, sans le suffixe.

## QCM d'entraînement

Cinq questions sur TwigBundle. Choisis avant d'ouvrir la correction.

**Question 1.** Une surcharge,
`templates/bundles/AcmeBlogBundle/user/profile.html.twig`, commence par
`{% extends '@AcmeBlog/user/profile.html.twig' %}`. Que se passe-t-il au rendu ?

- **A.** Le template d'origine est étendu normalement
- **B.** Une `LoaderError` : template introuvable
- **C.** Le rendu tourne sans fin jusqu'à épuiser la mémoire
- **D.** Twig ignore la surcharge et rend l'original

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Rejoué avec Twig 3.22.2 : `@AcmeBlog` désigne d'abord la
surcharge, qui s'étend donc elle-même ; « Allowed memory size … exhausted ».

A décrit `@!AcmeBlog`. B et D sont faux.

</details>

**Question 2.** `kernel.debug` vaut `true`, `strict_variables` n'est pas
configurée. Que rend `{{ typo|default('fallback') }}` si `typo` n'existe pas ?

- **A.** Une `RuntimeError` : la variable n'existe pas
- **B.** Une string vide
- **C.** `null`, affiché comme `null`
- **D.** `fallback`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Rejoué : le filter `default` accepte une variable
inexistante, même en mode strict. Sans le filter, la même variable lève une
`RuntimeError`.

A vaut pour `{{ typo }}` seul. B vaut pour `{{ typo }}` en `prod`. C est faux.

</details>

**Question 3.** `twig.globals` contient `raw_at: '@@not-a-service'`. Que rend
`{{ raw_at }}` ?

- **A.** Une erreur : service introuvable
- **B.** `@not-a-service`
- **C.** `@@not-a-service`
- **D.** Une string vide

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Rejoué : `@@` échappe l'arobase. Avec un seul `@`, la
valeur désignerait un service.

A suppose un seul `@`. C et D sont faux.

</details>

**Question 4.** `twig.paths` contient
`'%kernel.project_dir%/extra/': Admin`. Comment rendre le file
`extra/ns.html.twig` ?

- **A.** `'@Admin/ns.html.twig'`
- **B.** `'ns.html.twig'` : `paths` rejoint l'espace principal
- **C.** `'Admin/ns.html.twig'`
- **D.** `'@!Admin/ns.html.twig'`, obligatoire pour un path ajouté

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Rejoué. Un path nommé se référence par `@Nom/…` ; seul un
path sans namespace rejoint l'espace principal.

B vaut pour un path sans namespace. C oublie l'arobase. D est faux : Symfony ne
crée l'espace `!` que pour les bundles.

</details>

**Question 5.** `debug:twig @AcmeBlog/user/profile.html.twig` liste le file du
bundle sous « Overridden Files ». Qu'est-ce que cela signifie ?

- **A.** Le file du bundle sera rendu
- **B.** Le file du bundle est en erreur de syntaxe
- **C.** Un file de l'application le masque
- **D.** Le bundle n'est pas enregistré

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Rejoué : « Matched File » pointe vers
`templates/bundles/AcmeBlogBundle/…`, et le file du bundle passe dans
« Overridden Files ».

A est l'inverse. B et D sont faux.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- **Engine, bridge, bundle** : le bundle tire les deux autres et configure.
- **`default_path`** remplace `templates/` ; **`paths`** ajoute.
- Surcharge : **`templates/bundles/AcmeBlogBundle/`** ; original : **`@!AcmeBlog`**.
- Étendre **`@AcmeBlog`** depuis la surcharge : elle s'étend elle-même.
- **`strict_variables`** = `%kernel.debug%` ; `default`, `is defined`, `??`
  passent toujours.
- **`globals`** : `@` service, `@@` littéral.
- **`debug:twig <nom>`** dit qui sert ; **`lint:twig`** sort en 1 sur erreur.

**En une phrase, pour tout reconstruire :**

```text
twig/twig ← twig-bridge ← twig-bundle (configure) ; templates/ (default_path) + paths (@Nom) ;
@AcmeBlog = templates/bundles/AcmeBlogBundle/ puis le bundle ; @!AcmeBlog = le bundle ; strict_variables = kernel.debug
```

## Sources officielles

- [Templates, emplacements et namespaces](https://github.com/symfony/symfony-docs/blob/8.0/templates.rst)
- [TwigBundle, `Configuration`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/Configuration.php) et [`TwigExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/TwigExtension.php)
- [`debug:twig`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Command/DebugCommand.php) et [`lint:twig`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Command/LintCommand.php)
