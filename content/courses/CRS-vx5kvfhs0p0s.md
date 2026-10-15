---
id: CRS-vx5kvfhs0p0s
official_item: OIT-3r7rp470754w
title: "Debugging variables"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-24"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/templates.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/templates.rst"
    anchor: "debugging-variables"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/Extension/DumpExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/DumpExtension.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "DumpExtension::getFunctions(), dump()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/Node/DumpNode.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Node/DumpNode.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "DumpNode::compile()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/DebugBundle/Resources/config/services.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/DebugBundle/Resources/config/services.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "twig.extension.dump"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/Command/LintCommand.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Command/LintCommand.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "LintCommand::configure()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/src/Util/CallableArgumentsExtractor.php"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/src/Util/CallableArgumentsExtractor.php"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    symbol_or_lines: "CallableArgumentsExtractor::extractArguments()"
    verified_at: "2026-09-24"
outcome_sections:
  OUT-e93qx0gj8t90: "Deux formes, deux destinations"
  OUT-sbmhyzyq1vde: "Hors du développement"
  OUT-5qgcf97r7ze3: "Les commands"
---

## Objectif

Inspecter le contenu d'une variable dans un template, savoir où le résultat
apparaît, et ce qui se passe hors du développement.

## Deux formes, deux destinations

C'est la distinction qui compte :

| Écriture | Où va le résultat |
|---|---|
| `{{ dump(article) }}` | **dans la page**, visible à l'endroit de l'appel |
| `{% dump articles %}` | dans la **debug toolbar**, pas dans la page |

Le tag est donc la bonne quand on inspecte une variable dans une mise en page
qu'on ne veut pas casser. Le code 8.0 le montre : `DumpExtension::dump()` rend
du HTML inséré dans la page, alors que `DumpNode` compile le tag en un appel
à `VarDumper::dump()`, que DebugBundle collecte pour la barre.

Sans argument, `{{ dump() }}` comme `{% dump %}` sortent **tout le contexte** du
template, macros exclues.

## Étiqueter ce qu'on inspecte

- Le **tag** nomme lui-même ce qu'il affiche : `{% dump articles, user %}` se compile
  en `VarDumper::dump(["articles" => …, "user" => …])`.
- La **function** : la documentation montre
  `{{ dump(blog_posts: articles, user: app.user) }}`. Or `DumpExtension`
  déclare `dump(Environment $env, array $context)` sans argument variadique, et
  Twig 3.22 rejette alors les named arguments : **`SyntaxError: Unknown
  arguments`**, reproduit en exécutant Twig 3.22 avec une function déclarée à
  l'identique. Les arguments positionnels, eux, passent.

## Hors du développement

La function et le tag viennent de `DumpExtension`, que **DebugBundle**
enregistre (`services.php`). La documentation les annonce en `dev` et `test`
seulement.

- Bundle absent : la function n'existe pas, et la compilation du template échoue
  — `SyntaxError: Unknown "dump" function`. C'est l'« erreur PHP » que décrit la
  documentation pour `prod`.
- Extension chargée mais `kernel.debug` à `false` : `dump()` rend `null` et la
  tag ne fait rien — les deux testent `isDebug()`.

C'est délibéré : un `dump()` oublié divulguerait l'état interne de
l'application.

## Les commands

- `php bin/console debug:twig` — filters, functions, tests, globales et paths
  réellement disponibles ; `--filter=date` restreint la liste.
- `php bin/console lint:twig templates/` — vérifie la syntaxe **sans rendre** :
  une variable indéfinie n'y est pas détectée. `--show-deprecations` traite les
  deprecations comme des erreurs.

## Pièges d'examen

**La function et le tag n'écrivent pas au même endroit.**

**En production, la function n'existe pas** : erreur à la compilation, pas un
avertissement.

**Sans argument, c'est tout le contexte qui sort.**

**Le tag tag par nom de variable ; la function refuse les arguments
nommés** avec Twig 3.22, malgré l'exemple de la documentation.

## Points clés

- `{{ dump(x) }}` dans la page ; `{% dump x %}` vers la debug toolbar.
- Sans argument : tout le contexte.
- Fourni par DebugBundle ; absent → `Unknown "dump" function`.
- `debug:twig` inventorie, `lint:twig` valide la syntaxe sans rendre.

## Sources officielles

- [Symfony Templates, « The Dump Twig Utilities »](https://github.com/symfony/symfony-docs/blob/8.0/templates.rst)
- [Twig Bridge 8.0, `DumpExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/DumpExtension.php) et [`DumpNode`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Node/DumpNode.php)
- [DebugBundle 8.0, `services.php`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/DebugBundle/Resources/config/services.php)
- [Twig 3.22, `CallableArgumentsExtractor`](https://github.com/twigphp/Twig/blob/v3.22.0/src/Util/CallableArgumentsExtractor.php)
