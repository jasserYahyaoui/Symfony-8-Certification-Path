---
id: CRS-pchb259hnbh3
official_item: OIT-rj9web4whgmz
title: "Form options (OptionsResolver component)"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-29"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/options_resolver.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/components/options_resolver.rst"
    anchor: "the-optionsresolver-component"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/OptionsResolver/OptionsResolver.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/OptionsResolver/OptionsResolver.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "OptionsResolver::setDefault(), setOptions(), resolve()"
    verified_at: "2026-09-29"
---

## Objectif

Déclarer et contraindre les options d'un type de formulaire. Le composant sert
partout où une classe accepte un tableau d'options, pas seulement dans les
formulaires.

## Le problème résolu

Un tableau d'options est ingérable : une clé mal orthographiée passe inaperçue,
un type inattendu casse plus loin, et rien ne dit ce qui est accepté.
`OptionsResolver` remplace le tableau par une **déclaration** contrôlée.

Exécuté avec `symfony/options-resolver` 8.0.8 : une option inconnue lève
`UndefinedOptionsException`, dont le message liste les options définies —
« The option "b" does not exist. Defined options are: "a", "c". ».

## Les méthodes

| Méthode | Effet |
|---|---|
| `setDefaults()` | déclare des options avec une valeur par défaut |
| `setRequired()` | l'option doit être fournie, sauf si elle a un défaut |
| `setDefined()` | l'option est **acceptée** mais sans défaut et non obligatoire |
| `setAllowedTypes()` | contraint le type : `'string'`, `'int\|null'`, `'DateTime[]'` |
| `setAllowedValues()` | contraint la valeur à une liste, ou à un test |
| `setNormalizer()` | transforme la valeur après validation |
| `setDeprecated()` | signale une option obsolète sans la retirer |
| `setOptions()` | déclare des options **imbriquées** |

La distinction qui se rate est `setDefined()` contre `setDefaults()` : la
première déclare qu'une option **existe** sans lui donner de valeur ; la seconde
lui en donne une. Exécuté : une option seulement définie et non fournie est
**absente** du tableau résolu.

## Requise et défaut se combinent

Une option peut être requise **et** avoir un défaut. Exécuté : `setRequired('a')`
puis `setDefault('a', 1)` résout `['a' => 1]` ; `isRequired('a')` reste vrai,
`isMissing('a')` devient faux. C'est le cas documenté d'une sous-classe qui
fournit la valeur d'une option que son parent exige. Sans défaut ni valeur :
`MissingOptionsException`, « The required option "a" is missing. ».

## Options paresseuses

Une valeur par défaut peut être une fermeture recevant les options déjà
résolues, ce qui permet à une option de dépendre d'une autre :

```php
$resolver->setDefault('label', function (Options $options) {
    return $options['required'] ? 'Obligatoire' : 'Facultatif';
});
```

Le paramètre doit être typé **`Options`**. La documentation le signale, et
l'exécution le confirme : une fermeture non typée devient **elle-même** la
valeur par défaut — l'option résolue est un objet `Closure`.

L'ordre de déclaration n'a pas d'importance : exécuté, une option paresseuse
déclarée avant celle dont elle dépend se résout correctement. Un second
paramètre reçoit la valeur par défaut précédente.

## Validation, puis normalisation

La validation passe **avant** le normalisateur, qui ne peut donc pas réparer une
valeur refusée. Exécuté : avec `setAllowedTypes('n', 'int')` et un normalisateur
qui convertit en entier, `'5'` est refusée par `InvalidOptionsException` ; avec
`setAllowedValues('m', ['GET', 'POST'])` et un normalisateur `strtoupper()`,
`'post'` est refusée aussi.

Pour `'DateTime[]'`, chaque élément est contrôlé : un tableau contenant une
chaîne est refusé.

## Options imbriquées

En 8.0, elles se déclarent par `setOptions()` :

```php
$resolver->setOptions('db', function (OptionsResolver $db): void {
    $db->setDefaults(['host' => 'localhost', 'port' => 3306]);
});
```

Exécuté : `['db' => ['port' => 5432]]` donne `host` et `port` complétés, et une
clé inconnue lève « The option "db[nope]" does not exist. ». La même fermeture
passée à `setDefault()` n'est **pas** interprétée : l'option vaut l'objet
`Closure`.

## Dans un type de formulaire

```php
public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefaults(['data_class' => Task::class]);
    $resolver->setRequired('category');
    $resolver->setAllowedTypes('category', 'string');
}
```

Les options ainsi déclarées deviennent celles que `->add()` accepte pour ce type,
et arrivent dans `$options` de `buildForm()`.

## Pièges d'examen

**Déclarer qu'une option existe n'est pas lui donner une valeur par défaut** :
une option seulement définie est absente du résultat.

**Une option requise peut avoir un défaut** : elle reste requise, mais n'est plus
manquante.

**Une option paresseuse exige le type `Options`** ; sans lui, la fermeture est la
valeur.

**Le normalisateur s'exécute après la validation** : il ne rattrape pas une
valeur refusée.

**Les options imbriquées passent par `setOptions()`**, pas par `setDefault()`.

## Points clés

- `setDefaults` donne une valeur, `setDefined` autorise sans en donner,
  `setRequired` exige sauf défaut.
- `setAllowedTypes` et `setAllowedValues` contrôlent, puis `setNormalizer`
  transforme.
- Une option inconnue lève une exception qui liste les options définies.
- Défaut paresseux : fermeture typée `Options`.
- Imbrication : `setOptions()`.

## Sources officielles

- [The OptionsResolver Component](https://github.com/symfony/symfony-docs/blob/8.0/components/options_resolver.rst)
- [OptionsResolver 8.0, `OptionsResolver`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/OptionsResolver/OptionsResolver.php)
