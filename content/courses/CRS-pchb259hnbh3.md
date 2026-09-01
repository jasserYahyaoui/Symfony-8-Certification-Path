---
id: CRS-pchb259hnbh3
official_item: OIT-rj9web4whgmz
title: "Form options (OptionsResolver component)"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/options_resolver.rst"
    anchor: "the-optionsresolver-component"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Déclarer et contraindre les options d'un type de formulaire. Le composant sert
partout où une classe accepte un tableau d'options, pas seulement dans les
formulaires.

## Le problème résolu

Un tableau d'options est ingérable : une clé mal orthographiée passe inaperçue,
un type inattendu casse plus loin, et rien ne dit ce qui est accepté.
`OptionsResolver` remplace le tableau par une **déclaration** contrôlée.

## Les méthodes

| Méthode | Effet |
|---|---|
| `setDefaults()` | déclare des options avec une valeur par défaut |
| `setRequired()` | l'option doit être fournie, sans défaut |
| `setDefined()` | l'option est **acceptée** mais sans défaut et non obligatoire |
| `setAllowedTypes()` | contraint le type : `'string'`, `'int\|null'`, `'DateTime[]'` |
| `setAllowedValues()` | contraint la valeur à une liste, ou à un test |
| `setNormalizer()` | transforme la valeur après validation |
| `setDeprecated()` | signale une option obsolète sans la retirer |

La distinction qui se rate est `setDefined()` contre `setDefaults()` : la
première déclare qu'une option **existe** sans lui donner de valeur ; la seconde
lui en donne une. Une option ni définie ni par défaut provoque une exception.

## Options paresseuses

Une valeur par défaut peut être une fermeture recevant les options déjà
résolues, ce qui permet à une option d'en dépendre d'une autre :

```php
$resolver->setDefault('label', function (Options $options) {
    return $options['required'] ? 'Obligatoire' : 'Facultatif';
});
```

L'ordre de déclaration n'a pas d'importance : le composant résout les
dépendances lui-même.

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

## Points clés

- `setDefaults` donne une valeur, `setDefined` autorise sans en donner,
  `setRequired` exige.
- `setAllowedTypes` et `setAllowedValues` contraignent ; `setNormalizer`
  transforme après coup.
- Une option inconnue lève une exception : c'est l'intérêt du composant.
- Un défaut paresseux, en fermeture, peut dépendre d'autres options.

## Sources officielles

- [The OptionsResolver Component](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/options_resolver.rst)
