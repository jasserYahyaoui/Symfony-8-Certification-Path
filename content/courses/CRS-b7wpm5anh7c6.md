---
id: CRS-b7wpm5anh7c6
official_item: OIT-tvc5rjv6qvse
title: "Configuration (including DotEnv and ExpressionLanguage components)"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/configuration.rst"
    anchor: "configuration-multiple-env-files"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
---

## Objectif

Savoir où une valeur de configuration est écrite, qui l'emporte quand plusieurs
sources la définissent, et comment une expression peut la calculer.

## Prérequis

Les environnements Symfony, et les paramètres du conteneur.

## Deux mécanismes, deux moments

C'est la distinction qui structure tout l'item :

| | Paramètre | Variable d'environnement |
|---|---|---|
| Écrit dans | `config/services.yaml`, clé `parameters` | un fichier `.env`, ou le système |
| Référencé par | `%app.admin_email%` | `%env(DATABASE_URL)%` |
| Résolu | **à la compilation** du conteneur | **à l'exécution** |

Un paramètre est figé dans le conteneur compilé ; une variable d'environnement
ne l'est pas, et c'est précisément ce qui permet de déployer le même conteneur
sur plusieurs machines.

## Les paramètres

```yaml
parameters:
    app.admin_email: 'something@example.com'
    app.supported_locales: ['en', 'es', 'fr']
    app.some_constant: !php/const App\Entity\BlogPost::MAX_ITEMS
    app.some_enum: !php/enum App\Enum\PostState::Published
```

Le préfixe `app.` est une convention, non une obligation : il distingue les
paramètres de l'application de ceux de Symfony.

Un `%` littéral se **double** : `%%s` produit `%s`. Sans cela, Symfony y verrait
un nom de paramètre.

## La cascade `.env`

Quatre fichiers, du plus général au plus spécifique :

| Fichier | Portée | Versionné |
|---|---|---|
| `.env` | valeurs par défaut, tous environnements | **oui** |
| `.env.local` | cette machine, tous environnements | non |
| `.env.<env>` | un environnement, toutes machines | **oui** |
| `.env.<env>.local` | cette machine, un environnement | non |

Deux règles décident du reste, et ce sont elles que l'examen interroge :

- **`.env.local` est ignoré dans l'environnement `test`** — délibérément, pour
  que les tests donnent le même résultat pour tout le monde ;
- **une vraie variable d'environnement l'emporte toujours** sur tout ce que les
  fichiers `.env` définissent. Le fichier ne fait qu'ajouter ce qui manque.

Le fichier `.env` est lu et analysé **à chaque requête**, en production comme
ailleurs. Il n'y a donc pas de cache à vider après l'avoir modifié.

Sa syntaxe accepte des commentaires (`#`), l'interpolation (`${AUTRE_VAR}`), une
valeur de repli (`${VAR:-defaut}`), les guillemets simples pour un littéral et
les doubles pour interpoler.

## Les processeurs d'environnement

Une variable d'environnement est toujours une chaîne. Un processeur la convertit
au moment de la lecture :

```yaml
parameters:
    app.debug: '%env(bool:APP_DEBUG)%'
    app.port:  '%env(int:PORT)%'
    app.hosts: '%env(json:ALLOWED_HOSTS)%'
```

Ils se **chaînent**, et se lisent de droite à gauche :
`%env(json:base64:CONFIG)%` décode d'abord la base64, puis lit le JSON.

## ExpressionLanguage

Le composant compile et évalue des expressions d'une ligne, souvent booléennes.
Deux modes :

```php
$el = new ExpressionLanguage();
$el->evaluate('1 + 2');   // 3 — évalué, sans compilation
$el->compile('1 + 2');    // '(1 + 2)' — compilé en PHP, donc cachable
```

`parse()` rend l'arbre syntaxique ; `lint()` lève une `SyntaxError` si
l'expression est invalide, et accepte les drapeaux
`Parser::IGNORE_UNKNOWN_VARIABLES` et `Parser::IGNORE_UNKNOWN_FUNCTIONS`.

Dans le framework, ce composant est ce qui rend possible une condition écrite en
configuration plutôt qu'en PHP — une condition de route, une règle
d'autorisation.

## Pièges d'examen

**Un paramètre est résolu à la compilation, une variable d'environnement à
l'exécution.**

**La vraie variable d'environnement gagne** contre les fichiers `.env`.

**`.env.local` ne s'applique pas en `test`.**

**`.env` est relu à chaque requête** — aucun cache à vider.

**Les processeurs se chaînent de droite à gauche.**

**`%%` pour un `%` littéral.**

## Points clés

- Paramètre = compilation ; variable d'environnement = exécution.
- Cascade `.env` → `.env.local` → `.env.<env>` → `.env.<env>.local`, la vraie
  variable système au-dessus de tout.
- Processeurs typés, chaînables de droite à gauche.
- ExpressionLanguage : `evaluate()` sans compiler, `compile()` pour cacher.

## Sources officielles

- [Configuring Symfony](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/configuration.rst)
- [Environment Variable Processors](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/configuration/env_var_processors.rst)
- [The ExpressionLanguage Component](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/expression_language.rst)
