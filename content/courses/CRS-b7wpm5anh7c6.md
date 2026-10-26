---
id: CRS-b7wpm5anh7c6
official_item: OIT-tvc5rjv6qvse
title: "Configuration (including DotEnv and ExpressionLanguage components)"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-26"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/configuration.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/configuration.rst"
    anchor: "configuration-multiple-env-files"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Dotenv/Dotenv.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Dotenv/Dotenv.php"
    branch: "8.0"
    symbol_or_lines: "loadEnv(); bootEnv(); populate() — bool $overrideExistingVars = false"
    verified_at: "2026-10-08"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/ExpressionLanguage/ExpressionLanguage.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/ExpressionLanguage/ExpressionLanguage.php"
    branch: "8.0"
    symbol_or_lines: "lint(Expression|string $expression, array $names, int $flags = 0): void"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/configuration/env_var_processors.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/configuration/env_var_processors.rst"
    branch: "8.0"
    symbol_or_lines: "Environment Variable Processors"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/expression_language.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/components/expression_language.rst"
    branch: "8.0"
    symbol_or_lines: "Parsing and Linting Expressions"
    verified_at: "2026-10-02"
outcome_sections:
  OUT-tgt66re62pkz: "Deux mécanismes, deux moments"
  OUT-zbp5kh2xgz63: "La cascade `.env`"
  OUT-xrdyp9pn9ffe: "ExpressionLanguage"
---

## Objectif

Savoir où une valeur de configuration est écrite, qui l'emporte quand plusieurs
sources la définissent, et comment une expression peut la calculer.

## Prérequis

Les environments Symfony, et les parameters du container.

## Deux mécanismes, deux moments

C'est la distinction qui structure tout l'item :

| | Parameter | Variable d'environment |
|---|---|---|
| Écrit dans | `config/services.yaml`, key `parameters` | un file `.env`, ou le système |
| Référencé par | `%app.admin_email%` | `%env(DATABASE_URL)%` |
| Résolu | **à la compilation** du container | **à l'exécution** |

Un parameter est figé dans le container compilé ; une variable d'environment
ne l'est pas, et c'est précisément ce qui permet de déployer le même container
sur plusieurs machines.

## Les parameters

```yaml
parameters:
    app.admin_email: 'something@example.com'
    app.supported_locales: ['en', 'es', 'fr']
    app.some_constant: !php/const App\Entity\BlogPost::MAX_ITEMS
    app.some_enum: !php/enum App\Enum\PostState::Published
```

Le préfixe `app.` est une convention, non une obligation : il distingue les
parameters de l'application de ceux de Symfony.

Un `%` littéral se **double** : `%%s` produit `%s`. Sans cela, Symfony y verrait
un nom de parameter. Exécuté : `'a %%s b'` est lu `a %s b`.

## La cascade `.env`

Quatre files, du plus général au plus spécifique :

| File | Portée | Versionné |
|---|---|---|
| `.env` | default values, tous environments | **oui** |
| `.env.local` | cette machine, tous environments | non |
| `.env.<env>` | un environment, toutes machines | **oui** |
| `.env.<env>.local` | cette machine, un environment | non |

Deux règles décident du reste, et ce sont elles que l'examen interroge :

- **`.env.local` est ignoré dans l'environment `test`** — délibérément, pour
  que les tests donnent le même résultat pour tout le monde ;
- **une vraie variable d'environment l'emporte** sur les files `.env`,
  qui ajoutent seulement ce qui manque — sauf si Dotenv est appelé avec
  `overrideExistingVars: true` (exécuté). Condition documentée :
  `variables_order` doit contenir `E`, pour exposer `$_ENV`.

Entre les files eux-mêmes, **le dernier chargé gagne**, dans l'ordre
ci-dessus. Exécuté avec Dotenv 8.0.15, une même key définie dans plusieurs
files :

| Key définie dans | En `dev` | En `test` |
|---|---|---|
| `.env`, `.env.local`, `.env.dev` | `.env.dev` | `.env` |
| `.env`, `.env.local` | `.env.local` | `.env` |
| `.env`, `.env.dev.local` | `.env.dev.local` | `.env` |

Le piège est la première ligne : **`.env.dev` l'emporte sur `.env.local`**.

Le file `.env` est lu et analysé **à chaque Request**, en production comme
ailleurs. Il n'y a donc pas de cache à vider après l'avoir modifié.

En production, `composer dump-env prod` ou la command `dotenv:dump` écrivent
les valeurs finales dans `.env.local.php` ; **s'il existe, les files `.env`
ne sont plus analysés**. Exécuté avec `Dotenv::bootEnv()` : une key absente du
`.env.local.php` n'est plus définie.

Sa syntaxe accepte des commentaires (`#`), l'interpolation (`${AUTRE_VAR}`), une
valeur de repli (`${VAR:-defaut}`), les guillemets simples pour un littéral et
les doubles pour interpoler — exécuté : avec `E=x`, `'${E}'` reste littéral et
`"${E}"` donne `x`.

## Les processeurs d'environment

Une variable d'environment est toujours une string. Un processeur la convertit
au moment de la lecture :

```yaml
parameters:
    app.debug: '%env(bool:APP_DEBUG)%'
    app.port:  '%env(int:PORT)%'
    app.hosts: '%env(json:ALLOWED_HOSTS)%'
```

Ils se **chaînent**, et se lisent de droite à gauche :
`%env(json:base64:CONFIG)%` décode d'abord la base64, puis lit le JSON.

Exécuté sur un container compilé (DependencyInjection 8.0.15) :

| Référence | Valeur |
|---|---|
| `%env(bool:APP_DEBUG)%`, avec `0` | `false` |
| `%env(RAW)%`, sans processeur | la string brute |
| `%env(json:base64:CONFIG)%` | l'array décodé |
| `%env(base64:json:CONFIG)%` — ordre inversé | `RuntimeException` : *Invalid JSON in env var "CONFIG"* |

## ExpressionLanguage

Le component compile et évalue des expressions d'une ligne, souvent booléennes.
Deux modes :

```php
$el = new ExpressionLanguage();
$el->evaluate('1 + 2');   // 3 — évalué, sans compilation
$el->compile('1 + 2');    // '(1 + 2)' — compilé en PHP, donc cachable
```

`parse()` rend un `ParsedExpression` ; `lint()` ne rend rien et lève une
`SyntaxError` si l'expression est invalide. Exécuté sur ExpressionLanguage
8.0.15 :

| Appel | Résultat |
|---|---|
| `lint('foo + 1', [])` | `SyntaxError` : *Variable "foo" is not valid* |
| même appel, `Parser::IGNORE_UNKNOWN_VARIABLES` | aucune erreur |
| `lint('bar()', [], …VARIABLES)` | `SyntaxError` : *The function "bar" does not exist* |
| même appel, `Parser::IGNORE_UNKNOWN_FUNCTIONS` | aucune erreur |
| `lint('1 +', null)` | `TypeError` : en 8.0, `$names` est un `array` |

Dans le framework, ce component est ce qui rend possible une condition écrite en
configuration plutôt qu'en PHP — une condition de route, une règle
d'authorization.

## Pièges d'examen

**Un parameter est résolu à la compilation, une variable d'environment à
l'exécution.**

**La vraie variable d'environment gagne** contre les files `.env`, sauf
avec `overrideExistingVars: true`.

**`.env.local` ne s'applique pas en `test`.**

**`.env.<env>` l'emporte sur `.env.local`** : le dernier file chargé gagne.

**Avec un `.env.local.php`, les files `.env` ne sont plus lus.**

**`.env` est relu à chaque Request** — aucun cache à vider.

**Les processeurs se chaînent de droite à gauche.**

**`%%` pour un `%` littéral.**

## Points clés

- Parameter = compilation ; variable d'environment = exécution.
- Cascade `.env` → `.env.local` → `.env.<env>` → `.env.<env>.local`, le dernier
  chargé gagnant ; la vraie variable système au-dessus, par défaut.
- Processeurs typés, chaînables de droite à gauche.
- ExpressionLanguage : `evaluate()` sans compiler, `compile()` pour cacher.

## Sources officielles

- [Configuring Symfony](https://github.com/symfony/symfony-docs/blob/8.0/configuration.rst)
- [Environment Variable Processors](https://github.com/symfony/symfony-docs/blob/8.0/configuration/env_var_processors.rst)
- [The ExpressionLanguage Component](https://github.com/symfony/symfony-docs/blob/8.0/components/expression_language.rst)
- [`Dotenv`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Dotenv/Dotenv.php)
- [`ExpressionLanguage`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/ExpressionLanguage/ExpressionLanguage.php)
