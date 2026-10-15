---
id: CRS-jwhqec35jczn
official_item: OIT-a1anzcv85my3
title: "Finder"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/finder.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/components/finder.rst"
    anchor: "usage"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Finder/Finder.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Finder/Finder.php"
    branch: "8.0"
    symbol_or_lines: "getIterator(): LogicException without in() or append()"
    verified_at: "2026-10-02"
outcome_sections:
  OUT-9rc3vz43p7t0: "L'usage de base"
  OUT-0gd4a3m5v66c: "L'object est à état — le piège central"
  OUT-xntxjbxkapfz: "Quoi chercher"
  OUT-e7krb61ejhf9: "Récupérer les résultats"
---

## Objectif

Trouver des files et des directories par critères chaînés, et connaître le
piège qui fait échouer une réutilisation d'object.

## Prérequis

Les iterators PHP, et `SplFileInfo`.

## L'usage de base

```php
$finder = new Finder();
$finder->files()->in(__DIR__)->name('*.php');

if ($finder->hasResults()) {
    foreach ($finder as $file) {
        $file->getRealPath();
        $file->getRelativePathname();
    }
}
```

Chaque `$file` est un `Symfony\Component\Finder\SplFileInfo`, qui **étend** celui
de PHP pour ajouter les methods de path relatif — d'où `getRelativePathname()`,
absent de la class native.

Toutes les methods de critère se chaînent : elles forment une interface fluide.

## L'object est à état — le piège central

La documentation le signale par un avertissement, et c'est le point le plus
testable de l'item :

> Le `Finder` est **stateful**. Tout appel de method modifie **toutes** ses
> instances.

Concrètement, un second `->name()` sur le même object **s'ajoute** au premier au
lieu de le remplacer. Pour plusieurs recherches partageant une configuration
commune, il faut donc **cloner** :

```php
$finder = new Finder();
$finder->files()->in('./templates');

foreach ((clone $finder)->name('partial_*') as $file) { }
foreach ((clone $finder)->name('plugin_*') as $file) { }
```

Sans le clonage, la seconde loop chercherait `partial_*` **et** `plugin_*`.
Exécuté sur Finder 8.0.14 : un file après le premier `name()`, deux après le
second, sur le même object.

## Où chercher

`in()` est **le seul critère obligatoire**.

```php
$finder->in([__DIR__, '/ailleurs']);
$finder->in('src/Symfony/*/*/Resources');
$finder->in(__DIR__)->exclude('ruby');
$finder->ignoreUnreadableDirs()->in(__DIR__);
```

Le joker `*` est accepté, chaque pattern devant résoudre vers au moins un
directory. Les directories passés à `exclude()` sont **relatifs** à ceux donnés
à `in()`.

Le Finder reposant sur les iterators PHP, `in()` accepte aussi une URL avec un
protocole supporté — `ftp://`, `zlib://`.

## Quoi chercher

```php
$finder->files();        // fichiers seuls
$finder->directories();  // répertoires seuls
```

**Par défaut, le Finder rend les deux.** `files()` et `directories()` restreignent.
Exécuté : 5 résultats sans filter, 4 avec `files()`, 1 avec `directories()`.
Sans `in()` ni `append()`, l'itération lève une `LogicException`.

Les liens symboliques ne sont pas suivis par défaut ; `followLinks()` les suit.

## Les files de gestion de version

Les métadonnées des VCS — Git, Mercurial — sont **ignorées par défaut** ;
`ignoreVCS(false)` les réintègre.

`ignoreVCSIgnored(true)` applique en plus les règles des files `.gitignore`
rencontrés. Un détail que la documentation prend soin de préciser : **Symfony
part du directory de recherche**, là où Git part de la racine du repository. Pour un
comportement identique à Git, il faut donc lancer la recherche depuis cette
racine.

## Restreindre et trier

```php
$finder->depth('== 0');            // enfants directs seulement
$finder->depth(['> 2', '< 5']);    // ou par chaînage

$finder->sortByName();             // true pour l'ordre naturel
$finder->sortByType();             // répertoires d'abord, puis fichiers
```

Le parcours est **récursif par défaut** ; `depth()` le borne.

## Récupérer les résultats

`Finder` implémente `IteratorAggregate` : au-delà du `foreach`,
`iterator_to_array()` et `iterator_count()` fonctionnent.

Une précaution quand `in()` a été appelé **plusieurs fois** : un iterator
distinct est créé par emplacement, et les keys peuvent se répéter. Il faut alors
passer **`false`** en second argument d'`iterator_to_array()`, sans quoi des
résultats s'écrasent.

La key est le path du file. Exécuté : deux `a.txt` dans deux directories
distincts donnent deux keys, sans collision ; deux `in()` qui se recouvrent
(`f1` et `f1/sub`) atteignent le même file deux fois — `iterator_count()`
rend 5, `iterator_to_array()` 4, avec `false` 5.

## Pièges d'examen

**Le `Finder` est à état** : deux `name()` s'additionnent. Cloner pour réutiliser.

**Sans `files()` ni `directories()`, les deux sont rendus.**

**`in()` est le seul critère obligatoire.**

**Plusieurs `in()` : `iterator_to_array($finder, false)`.**

## Points clés

- `files()->in()->name()`, interface fluide, `SplFileInfo` enrichi.
- Object à état : cloner pour plusieurs recherches.
- `in()` obligatoire ; `exclude()` relatif à `in()`.
- VCS ignorés par défaut ; `.gitignore` via `ignoreVCSIgnored()`.
- Récursif par défaut, borné par `depth()`.
- `iterator_to_array($finder, false)` après plusieurs `in()`.

## Sources officielles

- [The Finder Component](https://github.com/symfony/symfony-docs/blob/8.0/components/finder.rst)
- [`Finder`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Finder/Finder.php)
