---
id: CRS-h9nryezt8wnb
official_item: OIT-tjn6kyvyc2h9
title: "Filesystem"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-26"
format: pedagogical
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

## L'essentiel

Le component Filesystem manipule files et directories avec un comportement
uniforme, et surtout **des erreurs qui lèvent une exception** au lieu de
rendre `false` en silence. Deux classes : `Filesystem` agit sur le disque,
`Path` calcule des strings de path sans y toucher.

## Le schéma

```text
Filesystem   mkdir (récursif, existant ignoré, umask) · copy · remove (array, fichiers + dossiers + liens)
             rename(…, true) pour écraser · symlink · mirror
écrire       dumpFile : atomique, crée les dossiers │ appendToFile : ajoute, lock optionnel
lire         readFile : IOException au lieu de false (fichier absent, dossier)
erreurs      IOExceptionInterface::getPath() — null pour readFile() sur un dossier
Path         canonicalize · join · makeRelative … sans toucher au disque ; "\" et "C:" seulement sous Windows
```

## L'exemple : publier un rapport

```php
$filesystem = new Filesystem();
try {
    $filesystem->mkdir('/var/reports', 0700);
    $filesystem->dumpFile('/var/reports/today.html', $html);   // un lecteur voit l'ancien ou le nouveau, entier
    $filesystem->appendToFile('/var/log/reports.log', "ok\n", true);
} catch (IOExceptionInterface $e) {
    echo 'Échec sur '.$e->getPath();
}
```

## Les opérations à connaître

| Appel | Résultat (umask 022) |
|---|---|
| `mkdir('a/b/c')` | trois niveaux, mode `0755` (0777 moins le `umask`) |
| `mkdir()` sur l'existant | aucune exception |
| `rename()` sur une cible existante | `IOException` ; avec `true`, la cible est remplacée |
| `remove(['lien', '/un/dossier', 'f.log'])` | files, directories et liens |

`mkdir()` et `remove()` acceptent un array ou tout `Traversable`.
`symlink()` a un troisième argument qui duplique le directory au lieu de
créer le lien — sous Windows seulement : le code le nomme `$copyOnWindows`.
Exécuté sous Linux avec `true`, c'est bien un lien qui est créé.

## Écrire et lire

- **`dumpFile()` est atomique** : file temporaire, puis déplacement. Elle
  crée le file et son directory au besoin.
- **`appendToFile()`** ajoute à la fin, crée file et directory s'ils manquent,
  et accepte un troisième argument pour **verrouiller** l'écriture.
- **`readFile()` lève une `IOException`** là où `file_get_contents()` rendrait
  `false` : file absent, ou directory.

## Les erreurs

Tout échec lève une exception `ExceptionInterface` ou `IOExceptionInterface`,
dont **`getPath()`** dit sur quel path l'opération a échoué. Exception
exécutée : `readFile()` sur un directory construit l'exception sans path —
`getPath()` rend `null`, le path n'est que dans le message.

## Un environment de test portable

| Appel (Linux) | Résultat |
|---|---|
| `Path::canonicalize('/var/www/../log/./x')` | `/var/log/x` |
| `Path::join('a', '/b', 'c/')` | `a/b/c` |
| `Path::canonicalize('a\\b/../c')` | `c` : sous Unix, `\` est un caractère de nom |
| `isAbsolutePath('c:\\Windows')` | `false` : `C:` n'est une racine que sous Windows |
| `makePathRelative('/var/lib/symfony/src/Symfony/', '/var/lib/symfony/src/Symfony/Component')` | `../` |
| `tempnam($dir, 'test_', '.txt')` | un file vide au nom unique, `test_….txt` |

Un test portable écrit ses paths avec `/`, travaille sous
`sys_get_temp_dir()` et nettoie par `remove()`.

## Pièges d'examen

**`mkdir()` est récursive et ignore l'existant** — elle n'échoue pas sur un
directory déjà là.

**`dumpFile()` est atomique**, `appendToFile()` ne l'est pas.

**`readFile()` lève sur un directory**, contrairement à `file_get_contents()`.

**`remove()` accepte un array** et traite files, directories et liens.

**`getPath()` est sur `IOExceptionInterface`** et nomme le path fautif — sauf
pour `readFile()` sur un directory, où il rend `null`.

**`Path` ne touche pas au disque.**

## Application

- Un file de configuration lu par d'autres process pendant qu'on l'écrit
  → `dumpFile()`.
- Un journal écrit par plusieurs workers → `appendToFile($f, $l, true)`.

## QCM d'entraînement

**Question 1.** Que rend `Path::join('a', '/b', 'c/')` ?

- **A.** `/b/c`, le path absolu l'emporte
- **B.** `a//b/c/`
- **C.** `a/b/c`
- **D.** Une exception : un segment est absolu

<details>
<summary>Correction</summary>

**Bonne réponse : C.** `join()` assemble les segments et normalise les
séparateurs, sans toucher au disque ni traiter `/b` comme une racine.

</details>

**Question 2.** `appendToFile('logs/2026/app.log', 'ligne')` est appelé, et
`logs/` n'existe pas. Que se passe-t-il ?

- **A.** Une `IOException` : le directory manque
- **B.** Les directories et le file sont créés
- **C.** Rien : la ligne est perdue
- **D.** Le file est créé à la racine du projet

<details>
<summary>Correction</summary>

**Bonne réponse : B.** `appendToFile()`, comme `dumpFile()`, crée le file et
ses directories manquants avant d'écrire.

</details>

**Question 3.** Que rend
`makePathRelative('/var/lib/symfony/src/Symfony/', '/var/lib/symfony/src/Symfony/Component')` ?

- **A.** `Component/`
- **B.** `/var/lib/symfony/src/Symfony/`
- **C.** `./`
- **D.** `../`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** La method rend le path relatif du second argument
vers le premier : depuis `Component`, on remonte d'un niveau.

</details>

**Question 4.** Un test appelle `$fs->tempnam($dir, 'test_', '.txt')`. Que
crée-t-il ?

- **A.** Un file vide, au nom unique
- **B.** Un directory temporaire
- **C.** Rien : la method rend seulement un nom
- **D.** Un file `test_.txt`, écrasé à chaque appel

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Le file existe aussitôt, vide, avec le préfixe et le
suffixe demandés : deux appels ne se marchent pas dessus.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- `Filesystem` **agit**, `Path` **calcule** sans toucher au disque.
- `mkdir()` **récursive**, existant ignoré, `umask` appliqué.
- `dumpFile()` **atomique** ; `appendToFile()` avec **lock** optionnel.
- `readFile()` **lève** au lieu de rendre `false`.
- `IOExceptionInterface::getPath()` — `null` pour un directory lu.
- `rename(…, true)` pour écraser ; `remove()` prend un array.

Moyen mnémotechnique : **« Agir sur le disque, calculer sur les strings »**.

## Sources officielles

- [The Filesystem Component](https://github.com/symfony/symfony-docs/blob/8.0/components/filesystem.rst)
- [`Filesystem`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Filesystem/Filesystem.php)

