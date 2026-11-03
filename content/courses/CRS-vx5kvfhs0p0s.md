---
id: CRS-vx5kvfhs0p0s
official_item: OIT-3r7rp470754w
title: "Debugging variables"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-11-03"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/templates.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/templates.rst"
    anchor: "debugging-variables"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/Extension/DumpExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/DumpExtension.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "DumpExtension::getFunctions(), dump()"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/Node/DumpNode.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Node/DumpNode.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "DumpNode::compile()"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/DebugBundle/Resources/config/services.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/DebugBundle/Resources/config/services.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "twig.extension.dump"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/Command/LintCommand.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Command/LintCommand.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "LintCommand::configure()"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/src/Util/CallableArgumentsExtractor.php"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/src/Util/CallableArgumentsExtractor.php"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    symbol_or_lines: "CallableArgumentsExtractor::extractArguments()"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/Compiler/ExtensionPass.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/Compiler/ExtensionPass.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "twig.extension.debug tagged when kernel.debug and no twig.extension.dump"
    verified_at: "2026-11-03"
outcome_sections:
  OUT-e93qx0gj8t90: "Deux formes, deux destinations"
  OUT-sbmhyzyq1vde: "Hors du développement"
  OUT-5qgcf97r7ze3: "Les commands"
---

## Objectif

Inspecter le contenu d'une variable dans un template, savoir où le résultat
apparaît, et ce qui se passe hors du développement.

## L'essentiel

`dump` existe en **function** et en **tag**, qui n'écrivent pas au même endroit.
Les deux viennent de DebugBundle, actif en `dev` et `test` : un `dump()` oublié
en production fait échouer la compilation. Deux commands inspectent les
templates sans les rendre.

## Le schéma

```mermaid
---
title: Ce que devient dump selon DebugBundle et kernel.debug
---
flowchart TD
  accTitle: Le comportement de la function et du tag dump dans Symfony 8.0
  accDescr: Avec DebugBundle, la function et le tag viennent de DumpExtension. En debug, la function rend un dump HTML dans la page et le tag envoie le dump à VarDumper, que DebugBundle collecte pour la debug toolbar, sans toucher la page. Hors debug, les deux ne font rien. Sans DebugBundle et en debug, TwigBundle enregistre la function dump de Twig, qui affiche un var_dump dans la page, et le tag n'existe pas. Sans DebugBundle et hors debug, la function et le tag sont inconnus et la compilation du template échoue.
  D{"DebugBundle chargé ?"} -->|"oui"| DB{"kernel.debug ?"}
  DB -->|"oui"| ON["function : HTML dans la page ; tag : vers la toolbar"]
  DB -->|"non"| OFF(["function et tag ne font rien"])
  D -->|"non"| NB{"kernel.debug ?"}
  NB -->|"oui"| TW["function de Twig : var_dump dans la page ; tag inconnu"]
  NB -->|"non"| ERR(["Unknown function dump : la compilation échoue"])
```
## Deux formes, deux destinations

| Écriture | Où va le résultat |
|---|---|
| `{{ dump(article) }}` | **dans la page**, à l'endroit de l'appel |
| `{% dump articles %}` | dans la **debug toolbar**, la page reste intacte |

- Sans argument, `{{ dump() }}` comme `{% dump %}` sortent **tout le contexte**
  du template, macros exclues.
- Le tag nomme ce qu'il affiche : `{% dump articles, user %}`.
- La function n'accepte que des arguments **positionnels** : l'exemple de la
  documentation `{{ dump(blog_posts: articles, user: app.user) }}` lève
  `SyntaxError: Unknown arguments` avec Twig 3.22.

## Hors du développement

DebugBundle enregistre l'extension (`twig.extension.dump`). Selon le cas :

| DebugBundle | `kernel.debug` | `{{ dump(x) }}` | `{% dump x %}` |
|---|---|---|---|
| oui | oui | dump HTML dans la page | vers la toolbar |
| oui | non | rien | rien |
| non | oui | `var_dump` brut de Twig | tag inconnu |
| non | non | « Unknown function "dump" » | tag inconnu |

La production est la dernière ligne : un `dump()` oublié fait échouer la
**compilation** du template — l'« erreur PHP » que décrit la documentation.
C'est délibéré : un dump divulguerait l'état interne de l'application.

## Les commands

- `php bin/console debug:twig` — filters, functions, tests, globales et paths
  disponibles ; `--filter=date` restreint la liste.
- `php bin/console lint:twig templates/` — vérifie la syntaxe **sans rendre** :
  une variable indéfinie n'est pas détectée ; l'exit code vaut `1` en cas
  d'erreur. `--show-deprecations` traite les deprecations comme des erreurs.

## Pièges d'examen

**La function et le tag n'écrivent pas au même endroit.**

**En production, la function n'existe pas** : erreur à la compilation, pas un
avertissement.

**Sans argument, c'est tout le contexte qui sort.**

**Le tag indexe par nom de variable ; la function refuse les named arguments** avec Twig 3.22, malgré l'exemple de la documentation.

## Application

- Un `{{ dump(cart) }}` dans un `<table>` déforme la page → `{% dump cart %}`,
  et lire la debug toolbar.
- Après deployment, « Unknown function "dump" » → un `dump()` est resté dans
  un template ; `lint:twig` lancé en `prod` l'aurait signalé.

## QCM d'entraînement

**Question 1.** Un projet n'a pas DebugBundle ; `kernel.debug` est actif. Que
fait `{{ dump(x) }}` ?

- **A.** Il échoue : « Unknown function "dump" »
- **B.** Il affiche un `var_dump` de `x`
- **C.** Il ne rend rien
- **D.** Il envoie `x` à la debug toolbar

<details>
<summary>Correction</summary>

**Bonne réponse : B.** En `debug`, sans le dump de DebugBundle, TwigBundle
active la function `dump()` de Twig, qui écrit un `var_dump`. A est le cas hors
`debug`.

</details>

**Question 2.** Une CI lance `lint:twig templates/` ; un template contient un
`{% if x %}` jamais fermé. Que reçoit la CI ?

- **A.** Le code `0`, avec un avertissement dans l'output
- **B.** Rien : le linter ne lit que les templates déjà rendus
- **C.** Le code `1` et « Unexpected end of template »
- **D.** Le code `0`, l'erreur n'apparaissant qu'au rendu

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Le linter compile chaque template : une erreur de syntaxe
le fait sortir avec le code `1`, que la CI lit comme un échec.

</details>

**Question 3.** Avec DebugBundle, que lève l'exemple de la documentation
`{{ dump(blog_posts: articles, user: app.user) }}` ?

- **A.** Rien : le dump étiqueté s'affiche
- **B.** Une `SyntaxError` : arguments inconnus
- **C.** « Unknown function "dump" », DebugBundle n'étant pas lu
- **D.** Une erreur au rendu seulement, pas à la compilation

<details>
<summary>Correction</summary>

**Bonne réponse : B.** La function de `DumpExtension` n'est pas variadique, et
Twig 3.22 refuse les named arguments dès la compilation.

</details>

**Question 4.** DebugBundle est chargé, `kernel.debug` est actif. Que contient
la page produite par `[{% dump x %}]` ?

- **A.** `[]`
- **B.** `[` suivi d'un bloc HTML `sf-dump`, puis `]`
- **C.** Un `var_dump` entre crochets
- **D.** Une erreur : le tag exige la toolbar

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Le tag confie le dump au collector de DebugBundle, que la
toolbar affiche ; la page reste intacte. B est ce que fait la function.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- **Function** : dans la page ; **tag** : vers la toolbar.
- Sans argument : **tout le contexte**.
- Function : **arguments positionnels** seulement ; tag : chaque variable
  nommée.
- DebugBundle hors `debug` : **rien**.
- Sans DebugBundle : `debug` → **`var_dump`** de Twig, tag inconnu ; sinon
  **Unknown function**.
- **`debug:twig --filter=…`** : ce que Twig connaît.
- **`lint:twig`** : syntaxe seulement, exit code `1` en cas d'erreur.

Moyen mnémotechnique : **« accolades dans la page, tag dans la barre, prod
sans bundle : erreur »**.

```text
{{ dump }} = page, {% dump %} = toolbar ; sans argument = tout le contexte ; function sans named arguments ;
DebugBundle + debug = actif, sans debug = muet ; sans bundle : debug = var_dump de Twig, prod = Unknown function ; lint = syntaxe
```

## Sources officielles

- [Symfony Templates, « The Dump Twig Utilities »](https://github.com/symfony/symfony-docs/blob/8.0/templates.rst)
- [Twig Bridge 8.0, `DumpExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/DumpExtension.php) et [`DumpNode`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Node/DumpNode.php)
- [DebugBundle 8.0, `services.php`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/DebugBundle/Resources/config/services.php)
- [TwigBundle 8.0, `ExtensionPass`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/TwigBundle/DependencyInjection/Compiler/ExtensionPass.php)
- [Twig 3.22, `CallableArgumentsExtractor`](https://github.com/twigphp/Twig/blob/v3.22.0/src/Util/CallableArgumentsExtractor.php)

