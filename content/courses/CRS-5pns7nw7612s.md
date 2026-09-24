---
id: CRS-5pns7nw7612s
official_item: OIT-tb3bnd6b0f01
title: "File upload"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller/upload_file.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/controller/upload_file.rst"
    anchor: "how-to-upload-files"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/controller.rst"
    anchor: "mapping-uploaded-files"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/File/UploadedFile.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/File/UploadedFile.php"
    symbol_or_lines: "getClientMimeType, guessClientExtension, isValid, move"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/Controller/ArgumentResolver/RequestPayloadValueResolver.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Controller/ArgumentResolver/RequestPayloadValueResolver.php"
    symbol_or_lines: "mapUploadedFile"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/php/doc-en/master/reference/spl/splfileinfo/getsize.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/reference/spl/splfileinfo/getsize.xml"
    symbol_or_lines: "Returns the filesize in bytes for the file referenced."
    repository: "php/doc-en"
    branch: "master"
    verified_at: "2026-09-24"
---

## Objectif

Récupérer un fichier téléversé, le déplacer, et savoir quelles informations ne
doivent jamais être crues.

## Le récupérer

Deux chemins :

```php
$file = $request->files->get('brochure');            // sac files
```

```php
public function upload(#[MapUploadedFile] UploadedFile $picture): Response
```

Dans les deux cas on obtient un `UploadedFile`, une sous-classe de `File` qui
enveloppe le fichier temporaire créé par PHP.

### Ce que fait `#[MapUploadedFile]`

Le résolveur lit `$request->files` sous l'option `name` de l'attribut, et à
défaut sous le **nom de l'argument**. Quand rien n'est envoyé :

| Argument | Résultat |
|---|---|
| nullable, ou avec valeur par défaut | `null`, ou la valeur par défaut |
| typé `array` | un tableau vide |
| autre | une `HttpException` de statut **422** |

Le 422 est la valeur par défaut de l'option `validationFailedStatusCode`, qui sert
aussi quand les contraintes passées à l'attribut échouent. Sur un tableau de
fichiers, ces contraintes sont appliquées à chaque fichier.

## Le déplacer

```php
$file->move($destinationDirectory, $newFilename);
```

Le fichier temporaire disparaît à la fin de la requête : sans `move()`, rien
n'est conservé. `move()` retourne un `File` pointant vers la nouvelle place.

Elle vérifie d'abord `isValid()` — téléversement sans erreur **et** fichier
réellement arrivé par HTTP. Sinon elle lève une exception propre à chaque code
d'erreur de PHP : `IniSizeFileException` pour la limite de `php.ini`,
`FormSizeFileException` pour celle du formulaire, `NoFileException`,
`PartialFileException`, etc.

## Ce qui vient du client n'est pas fiable

C'est le cœur de l'item. Le code marque lui-même ces méthodes comme non sûres :

- `getClientOriginalName()` — le nom d'origine ;
- `getClientOriginalExtension()` — l'extension, tirée de ce nom ;
- `getClientOriginalPath()` — le chemin d'origine, identique au nom sauf envoi
  d'un dossier ;
- `getClientMimeType()` — le type MIME **annoncé** ; le fiable est `getMimeType()`,
  qui le devine d'après le contenu ;
- `guessClientExtension()` — une extension déduite du type MIME annoncé, donc
  tout aussi peu fiable, bien que son nom ne commence pas par `getClient`.

La recommandation officielle est donc de **générer soi-même le nom** et de
déterminer l'extension par `guessExtension()`, qui part du type MIME réel.

```php
$safe = $slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
$name = $safe.'-'.uniqid().'.'.$file->guessExtension();
```

### Et `getSize()` ?

La documentation la range parmi les valeurs non sûres, comme « la taille
d'origine ». **Le code dit autre chose** : ni `UploadedFile` ni `File` ne la
définissent. Elle vient de `\SplFileInfo`, qui retourne la taille du fichier
référencé — le fichier temporaire reçu par le serveur, pas une valeur déclarée
par le client. Pour l'examen, retenir les deux : la documentation la liste, le
code la mesure.

## Pièges d'examen

**Rien de ce que le client annonce sur le fichier n'est fiable.** Nom, extension,
chemin, type MIME annoncé : tout vient du navigateur.

**`guessClientExtension()` n'est pas `guessExtension()`.** La première part du
type annoncé, la seconde du contenu.

**Sans déplacement, il ne reste rien.** Le fichier temporaire disparaît à la fin
de la requête.

**Un fichier manquant donne 422, pas 400.** Sauf argument nullable, avec défaut,
ou typé tableau.

**`move()` lève une exception typée** selon l'erreur de téléversement.

## Points clés

- `$request->files` ou `#[MapUploadedFile]`, qui lit `name`, sinon le nom d'argument.
- Fichier absent : `null`, défaut, `[]` ou **422**.
- `move()` est obligatoire, retourne un `File`, lève une exception par code d'erreur.
- Non fiables : les quatre `getClient…` et `guessClientExtension()`.
- `guessExtension()` et `getMimeType()` partent du contenu.
- `getSize()` : listée par la documentation, mesurée par le code.

## Sources officielles

- [How to Upload Files](https://github.com/symfony/symfony-docs/blob/8.0/controller/upload_file.rst)
- [Controller, « Mapping Uploaded Files »](https://github.com/symfony/symfony-docs/blob/8.0/controller.rst)
- [`UploadedFile`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/File/UploadedFile.php)
- [`RequestPayloadValueResolver`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Controller/ArgumentResolver/RequestPayloadValueResolver.php)
- [`SplFileInfo::getSize`](https://github.com/php/doc-en/blob/master/reference/spl/splfileinfo/getsize.xml)
