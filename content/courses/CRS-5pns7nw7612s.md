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
    anchor: "how-to-upload-files"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller.rst"
    anchor: "mapping-uploaded-files"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
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

L'attribut `#[MapUploadedFile]` résout le fichier d'après le **nom de
l'argument**. Si aucun fichier n'est envoyé, une `HttpException` est levée ;
pour rendre le fichier facultatif, il faut typer l'argument comme nullable
(`?UploadedFile`). L'attribut accepte aussi une liste de contraintes de
validation.

Dans les deux cas on obtient un `UploadedFile`, une sous-classe de `File` qui
enveloppe le fichier temporaire créé par PHP.

## Le déplacer

```php
$file->move($destinationDirectory, $newFilename);
```

Le fichier temporaire disparaît à la fin de la requête : sans `move()`, rien
n'est conservé.

## Ce qui vient du client n'est pas fiable

C'est le cœur de l'item. Ces méthodes retournent des données **fournies par le
navigateur**, qu'un utilisateur malveillant contrôle entièrement :

- `getClientOriginalName()` — le nom d'origine ;
- `getClientOriginalExtension()` — l'extension d'origine ;
- `getClientOriginalPath()` — le chemin d'origine ;
- `getSize()` — la taille annoncée.

La recommandation officielle est donc de **générer soi-même le nom** et de
déterminer l'extension par `guessExtension()`, qui la déduit du type MIME réel
du fichier et non de ce que le client affirme.

Le motif recommandé combine trois éléments : le nom d'origine passé au
`SluggerInterface` pour rester lisible, un identifiant unique pour éviter les
collisions, et `guessExtension()` pour l'extension.

```php
$safe = $slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
$name = $safe.'-'.uniqid().'.'.$file->guessExtension();
```

## Points clés

- `$request->files` ou `#[MapUploadedFile]`, qui résout par nom d'argument.
- Sans fichier, `#[MapUploadedFile]` lève une `HttpException` — sauf argument
  nullable.
- `move()` est obligatoire : le fichier temporaire est effacé après la requête.
- Tout ce qui commence par `getClient…` vient du navigateur et n'est pas fiable.
- `guessExtension()` déduit l'extension du type MIME.

## Sources officielles

- [How to Upload Files](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller/upload_file.rst)
- [Controller, « Mapping Uploaded Files »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller.rst)
