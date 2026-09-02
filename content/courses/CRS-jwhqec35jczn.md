---
id: CRS-jwhqec35jczn
official_item: OIT-a1anzcv85my3
title: "Finder"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/finder.rst"
    anchor: "usage"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
---

## Objectif

Trouver des fichiers et des répertoires par critères chaînés, et connaître le
piège qui fait échouer une réutilisation d'objet.

## Prérequis

Les itérateurs PHP, et `SplFileInfo`.

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
de PHP pour ajouter les méthodes de chemin relatif — d'où `getRelativePathname()`,
absent de la classe native.

Toutes les méthodes de critère se chaînent : elles forment une interface fluide.

## L'objet est à état — le piège central

La documentation le signale par un avertissement, et c'est le point le plus
testable de l'item :

> Le `Finder` est **stateful**. Tout appel de méthode modifie **toutes** ses
> instances.

Concrètement, un second `->name()` sur le même objet **s'ajoute** au premier au
lieu de le remplacer. Pour plusieurs recherches partageant une configuration
commune, il faut donc **cloner** :

```php
$finder = new Finder();
$finder->files()->in('./templates');

foreach ((clone $finder)->name('partial_*') as $file) { }
foreach ((clone $finder)->name('plugin_*') as $file) { }
```

Sans le clonage, la seconde boucle chercherait `partial_*` **et** `plugin_*`.

## Où chercher

`in()` est **le seul critère obligatoire**.

```php
$finder->in([__DIR__, '/ailleurs']);
$finder->in('src/Symfony/*/*/Resources');
$finder->in(__DIR__)->exclude('ruby');
$finder->ignoreUnreadableDirs()->in(__DIR__);
```

Le joker `*` est accepté, chaque motif devant résoudre vers au moins un
répertoire. Les répertoires passés à `exclude()` sont **relatifs** à ceux donnés
à `in()`.

Le Finder reposant sur les itérateurs PHP, `in()` accepte aussi une URL avec un
protocole supporté — `ftp://`, `zlib://`.

## Quoi chercher

```php
$finder->files();        // fichiers seuls
$finder->directories();  // répertoires seuls
```

**Par défaut, le Finder rend les deux.** `files()` et `directories()` restreignent.

`followLinks()` suit les liens symboliques — mais **ne les résout pas** : un lien
vers un fichier apparaît comme lien, tandis qu'un lien vers un répertoire fait
entrer son contenu dans les résultats.

## Les fichiers de gestion de version

Les métadonnées des VCS — Git, Mercurial — sont **ignorées par défaut** ;
`ignoreVCS(false)` les réintègre.

`ignoreVCSIgnored(true)` applique en plus les règles des fichiers `.gitignore`
rencontrés. Un détail que la documentation prend soin de préciser : **Symfony
part du répertoire de recherche**, là où Git part de la racine du dépôt. Pour un
comportement identique à Git, il faut donc lancer la recherche depuis cette
racine.

## Restreindre et trier

```php
$finder->depth('== 0');            // enfants directs seulement
$finder->depth(['> 2', '< 5']);    // ou par chaînage

$finder->sortByName();
$finder->sortByName(true);         // ordre naturel : file2 avant file10
$finder->sortByType();             // répertoires d'abord, puis fichiers
```

Le parcours est **récursif par défaut** ; `depth()` le borne.

`sortByName()` utilise `strcmp()` — donc `file1`, `file10`, `file2`. Passer
`true` bascule sur l'ordre **naturel**.

## Récupérer les résultats

`Finder` implémente `IteratorAggregate` : au-delà du `foreach`,
`iterator_to_array()` et `iterator_count()` fonctionnent.

Une précaution quand `in()` a été appelé **plusieurs fois** : un itérateur
distinct est créé par emplacement, et les clés peuvent se répéter. Il faut alors
passer **`false`** en second argument d'`iterator_to_array()`, sans quoi des
résultats s'écrasent.

## Pièges d'examen

**Le `Finder` est à état** : deux `name()` s'additionnent. Cloner pour réutiliser.

**Sans `files()` ni `directories()`, les deux sont rendus.**

**`in()` est le seul critère obligatoire.**

**`followLinks()` suit sans résoudre.**

**`sortByName()` n'est pas naturel par défaut** — `file10` avant `file2`.

**Plusieurs `in()` : `iterator_to_array($finder, false)`.**

## Points clés

- `files()->in()->name()`, interface fluide, `SplFileInfo` enrichi.
- Objet à état : cloner pour plusieurs recherches.
- `in()` obligatoire ; `exclude()` relatif à `in()`.
- VCS ignorés par défaut ; `.gitignore` via `ignoreVCSIgnored()`.
- Récursif par défaut, borné par `depth()`.
- `iterator_to_array($finder, false)` après plusieurs `in()`.

## Sources officielles

- [The Finder Component](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/finder.rst)
