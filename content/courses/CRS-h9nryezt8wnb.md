---
id: CRS-h9nryezt8wnb
official_item: OIT-tjn6kyvyc2h9
title: "Filesystem"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-26"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/filesystem.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/components/filesystem.rst"
    anchor: "filesystem-utilities"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Filesystem/Filesystem.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Filesystem/Filesystem.php"
    branch: "8.0"
    symbol_or_lines: "mkdir(); rename(); dumpFile(); readFile(); symlink(bool $copyOnWindows = false) — mirrors only when DIRECTORY_SEPARATOR is a backslash"
    verified_at: "2026-10-09"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Filesystem/Path.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Filesystem/Path.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "normalize(), isAbsolute() : « \\ » lu comme séparateur seulement si DIRECTORY_SEPARATOR vaut « \\ »"
    verified_at: "2026-10-16"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Filesystem/Filesystem.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Filesystem/Filesystem.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "tempnam($dir, $prefix, $suffix)"
    verified_at: "2026-10-16"
outcome_sections:
  OUT-0mnzvybywnv8: "Les opérations à connaître"
  OUT-p4ef3jhd70w3: "Écrire et lire"
  OUT-9zm5ea8e1m2y: "Les erreurs"
  OUT-85v8saxqy4sb: "Un environment de test portable"
---

## Objectif

Manipuler files et directories avec un comportement uniforme entre systèmes,
et surtout : **des erreurs qui lèvent une exception** au lieu de rendre `false`
silencieusement comme les functions natives de PHP.

## Prérequis

Les exceptions PHP.

## Deux classes, deux roles

| Class | Ce qu'elle fait |
|---|---|
| `Filesystem` | agit sur le disque — créer, copier, supprimer |
| `Path` | manipule des **strings** de path, sans toucher au disque |

La distinction est utile : `Path::normalize()` ne vérifie pas qu'un path
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
  systèmes POSIX — et **ignore un directory déjà existant** plutôt que
  d'échouer. Le mode réel subit le `umask` courant ;
- **`remove()` supprime files, directories et liens** indifféremment ;
- **`mkdir()` et `remove()` acceptent un array** ou tout `Traversable`.

`rename()` échoue si la cible existe, sauf troisième argument à `true`.

Exécuté sur Filesystem 8.0.15, `umask` à `022` :

| Appel | Résultat |
|---|---|
| `mkdir('a/b/c')` | trois niveaux créés, mode `0755` (0777 moins le `umask`) |
| `mkdir()` sur l'existant | aucune exception |
| `rename()` sur une cible existante | `IOException` |
| même appel avec `true` | la cible est remplacée |
| `dumpFile('x/y/f.txt', …)` | `x/y` créé au passage |

`symlink()` accepte un troisième argument qui **duplique le directory** au lieu
de créer le lien — sous Windows seulement : le code le nomme `$copyOnWindows`.
La documentation dit « si le filesystem ne gère pas les liens » ;
exécuté sous Linux avec `true`, c'est bien un lien qui est créé.

## Écrire et lire

Trois methods, trois garanties différentes :

```php
$filesystem->dumpFile('file.txt', 'Hello World');
$filesystem->appendToFile('logs.txt', 'ligne', true);
$contents = $filesystem->readFile('/chemin/file.txt');
```

**`dumpFile()` est atomique.** Elle écrit un file temporaire puis le déplace :
un lecteur simultané voit **soit l'ancien file complet, soit le nouveau
complet**, jamais un file à moitié écrit. Elle crée aussi le file et son
directory au besoin.

**`appendToFile()`** ajoute à la fin, crée file et directory s'ils manquent,
et accepte un troisième argument pour **verrouiller** pendant l'écriture.

**`readFile()` lève une exception** là où `file_get_contents()` rendrait
`false` — quand le path n'est pas lisible, et quand on lui passe un
directory. Exécuté sur un file absent et sur un directory : `IOException`
dans les deux cas.

## Les paths, sans toucher au disque

`Path` couvre la canonicalisation, la jointure, la conversion
absolu/relatif et la recherche de base commune. `Filesystem` expose de son côté
`isAbsolutePath()` et `makePathRelative()`, qui prend **deux paths absolus**
et rend le path relatif du second vers le premier.

## Les erreurs

Tout échec lève une exception implémentant `ExceptionInterface` ou
`IOExceptionInterface`. Cette dernière expose **`getPath()`**, qui dit sur quel
path l'opération a échoué.

```php
try {
    $filesystem->mkdir($dir);
} catch (IOExceptionInterface $e) {
    echo "Échec sur ".$e->getPath();
}
```

C'est le gain réel du component : une erreur ne peut pas passer inaperçue.

Une réserve, lue dans `Filesystem::readFile()` (8.0) et exécutée : sur un
**directory**, l'`IOException` est construite **sans path** — `getPath()`
rend `null`, le path ne figure que dans le message. Pour un file illisible,
le code passe le path à l'exception, et `getPath()` le rend.

## Un environment de test portable

Exécuté sous Linux avec Filesystem 8.0.15 :

| Appel | Résultat |
|---|---|
| `Path::canonicalize('/var/www/../log/./x')` | `/var/log/x` |
| `Path::join('a', '/b', 'c/')` | `a/b/c` |
| `Path::canonicalize('a\\b/../c')` | `c` : sous Unix, `\` est un caractère de nom |
| `isAbsolutePath('c:\\Windows')` | `false` |
| `tempnam($dir, 'test_', '.txt')` | un file vide, au nom unique |

Le code ne lit `\` et `C:` comme séparateur et racine que si
`DIRECTORY_SEPARATOR` vaut `\` : la documentation montre `true` pour
`c:\Windows`, ce que rend Windows — lu dans `Path::isAbsolute()`. Un test portable écrit donc ses paths avec
`/`, travaille sous `sys_get_temp_dir()` et nettoie par `remove()`.

## Pièges d'examen

**`mkdir()` est récursive et ignore l'existant** — elle n'échoue pas sur un
directory déjà là.

**`dumpFile()` est atomique**, `appendToFile()` ne l'est pas.

**`readFile()` lève sur un directory**, contrairement à `file_get_contents()`.

**`remove()` accepte un array** et traite files, directories et liens.

**`getPath()` est sur `IOExceptionInterface`** et nomme le path fautif — sauf
pour `readFile()` sur un directory, où il rend `null`.

**`Path` ne touche pas au disque.**

## Points clés

- `Filesystem` agit, `Path` calcule des strings.
- `mkdir()` récursive, mode 0777 par défaut, existant ignoré, `umask` appliqué.
- `dumpFile()` atomique ; `appendToFile()` avec lock optionnel ;
  `readFile()` lève au lieu de rendre `false`.
- Échec = exception `IOExceptionInterface`, avec `getPath()`.

## Sources officielles

- [The Filesystem Component](https://github.com/symfony/symfony-docs/blob/8.0/components/filesystem.rst)
- [`Filesystem`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Filesystem/Filesystem.php)
