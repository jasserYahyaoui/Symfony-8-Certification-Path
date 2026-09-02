---
id: CRS-h9nryezt8wnb
official_item: OIT-tjn6kyvyc2h9
title: "Filesystem"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/filesystem.rst"
    anchor: "filesystem-utilities"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
---

## Objectif

Manipuler fichiers et répertoires avec un comportement uniforme entre systèmes,
et surtout : **des erreurs qui lèvent une exception** au lieu de rendre `false`
silencieusement comme les fonctions natives de PHP.

## Prérequis

Les exceptions PHP.

## Deux classes, deux rôles

| Classe | Ce qu'elle fait |
|---|---|
| `Filesystem` | agit sur le disque — créer, copier, supprimer |
| `Path` | manipule des **chaînes** de chemin, sans toucher au disque |

La distinction est utile : `Path::normalize()` ne vérifie pas qu'un chemin
existe, elle en réécrit la forme.

## Les opérations à connaître

```php
$filesystem = new Filesystem();

$filesystem->mkdir('/tmp/photos', 0700);
$filesystem->exists('/tmp/photos');
$filesystem->copy('source.txt', 'cible.txt');
$filesystem->remove(['lien', '/un/repertoire', 'activity.log']);
$filesystem->rename('/tmp/a.ogg', '/stock/b.ogg', true);
$filesystem->symlink('/source', '/destination');
$filesystem->mirror('/source', '/cible');
```

Trois comportements méritent d'être retenus :

- **`mkdir()` crée récursivement**, avec le mode `0777` par défaut sur les
  systèmes POSIX — et **ignore un répertoire déjà existant** plutôt que
  d'échouer. Le mode réel subit le `umask` courant ;
- **`remove()` supprime fichiers, répertoires et liens** indifféremment ;
- **`mkdir()` et `remove()` acceptent un tableau** ou tout `Traversable`.

`rename()` échoue si la cible existe, sauf troisième argument à `true`.
`symlink()` accepte un troisième argument qui **duplique le répertoire** quand
le système ne gère pas les liens symboliques.

## Écrire et lire

Trois méthodes, trois garanties différentes :

```php
$filesystem->dumpFile('file.txt', 'Hello World');
$filesystem->appendToFile('logs.txt', 'ligne', true);
$contents = $filesystem->readFile('/chemin/file.txt');
```

**`dumpFile()` est atomique.** Elle écrit un fichier temporaire puis le déplace :
un lecteur simultané voit **soit l'ancien fichier complet, soit le nouveau
complet**, jamais un fichier à moitié écrit. Elle crée aussi le fichier et son
répertoire au besoin.

**`appendToFile()`** ajoute à la fin, crée fichier et répertoire s'ils manquent,
et accepte un troisième argument pour **verrouiller** pendant l'écriture.

**`readFile()` lève une exception** là où `file_get_contents()` rendrait
`false` — quand le chemin n'est pas lisible, et quand on lui passe un
répertoire.

## Les chemins, sans toucher au disque

`Path` couvre la canonicalisation, la jointure, la conversion
absolu/relatif et la recherche de base commune. `Filesystem` expose de son côté
`isAbsolutePath()` et `makePathRelative()`, qui prend **deux chemins absolus**
et rend le chemin relatif du second vers le premier.

## Les erreurs

Tout échec lève une exception implémentant `ExceptionInterface` ou
`IOExceptionInterface`. Cette dernière expose **`getPath()`**, qui dit sur quel
chemin l'opération a échoué.

```php
try {
    $filesystem->mkdir($dir);
} catch (IOExceptionInterface $e) {
    echo "Échec sur ".$e->getPath();
}
```

C'est le gain réel du composant : une erreur ne peut pas passer inaperçue.

## Pièges d'examen

**`mkdir()` est récursive et ignore l'existant** — elle n'échoue pas sur un
répertoire déjà là.

**`dumpFile()` est atomique**, `appendToFile()` ne l'est pas.

**`readFile()` lève sur un répertoire**, contrairement à `file_get_contents()`.

**`remove()` accepte un tableau** et traite fichiers, répertoires et liens.

**`getPath()` est sur `IOExceptionInterface`** et nomme le chemin fautif.

**`Path` ne touche pas au disque.**

## Points clés

- `Filesystem` agit, `Path` calcule des chaînes.
- `mkdir()` récursive, mode 0777 par défaut, existant ignoré, `umask` appliqué.
- `dumpFile()` atomique ; `appendToFile()` avec verrou optionnel ;
  `readFile()` lève au lieu de rendre `false`.
- Échec = exception `IOExceptionInterface`, avec `getPath()`.

## Sources officielles

- [The Filesystem Component](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/filesystem.rst)
