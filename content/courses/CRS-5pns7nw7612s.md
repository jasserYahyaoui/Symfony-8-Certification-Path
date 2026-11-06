---
id: CRS-5pns7nw7612s
official_item: OIT-tb3bnd6b0f01
title: "File upload"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
format: pedagogical
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
outcome_sections:
  OUT-np64n1673fc3: "Le récupérer"
  OUT-q06cyzmh9hjt: "Le récupérer"
  OUT-map3gyk0v2py: "Le déplacer"
  OUT-aw5esqj5yqqm: "Ce qui vient du client n'est pas fiable"
  OUT-38dvrzpkavx9: "Ce qui vient du client n'est pas fiable"
---

## Objectif

Récupérer un file téléversé, le déplacer, et savoir quelles informations ne
doivent jamais être crues.

## L'essentiel

Un file téléversé arrive en `UploadedFile`, par le sac `files` ou par
`#[MapUploadedFile]`. Il vit dans un file temporaire que PHP **supprime** en fin
de Request : seul `move()` le conserve. Et tout ce que le client **annonce** —
nom, extension, path, type MIME — est une entrée, pas un fait : le nom stocké
se génère, l'extension se devine du contenu.

## Le schéma

```text
obtenir     $request->files->get('brochure')  ·  #[MapUploadedFile] UploadedFile $picture
            key lue : option name, sinon nom de l'argument
absent      nullable / défaut → null ou défaut · array → [] · sinon HttpException 422
conserver   $file->move($dir, $name) → File ; lève une exception par code d'erreur PHP
client      getClientOriginalName · …Extension · …Path · getClientMimeType · guessClientExtension
contenu     getMimeType() · guessExtension()        → à utiliser
```

## L'exemple : un nom sûr

```php
public function upload(#[MapUploadedFile] UploadedFile $brochure, SluggerInterface $slugger): Response
{
    $safe = $slugger->slug(pathinfo($brochure->getClientOriginalName(), PATHINFO_FILENAME));
    $brochure->move($this->getParameter('brochures_directory'), $safe.'-'.uniqid().'.'.$brochure->guessExtension());
    // ...
}
```

## Le récupérer

Deux paths, un même résultat : `$request->files->get('brochure')`, ou un
argument portant `#[MapUploadedFile]`. On obtient un `UploadedFile`, sous-classe
de `File` qui enveloppe le file temporaire.

Le resolver lit `$request->files` sous l'option `name` de l'attribute, à défaut
sous le **nom de l'argument**. Quand rien n'est envoyé, un argument nullable ou
doté d'une default value reçoit `null` ou ce défaut, un argument `array` un
array vide, tout autre une `HttpException` **422**. Ce 422 est la default value
de `validationFailedStatusCode`, qui sert aussi quand les constraints de
l'attribute échouent ; sur un array, elles s'appliquent à chaque file.

## Le déplacer

`move($directory, $name)` retourne un `File` à la nouvelle place. Elle vérifie
d'abord `isValid()` — envoi sans erreur **et** file arrivé par HTTP —, sinon
elle lève une exception par code d'erreur : `IniSizeFileException` pour la
limite de `php.ini`, `FormSizeFileException` pour celle du form,
`PartialFileException`, `NoFileException`, etc.

## Ce qui vient du client n'est pas fiable

Le code marque ces methods comme non sûres : `getClientOriginalName()`,
`getClientOriginalExtension()`, `getClientOriginalPath()` — identique au nom
sauf envoi d'un dossier —, `getClientMimeType()`, et `guessClientExtension()`,
qui part du type annoncé bien que son nom ne commence pas par `getClient`.

Les methods fiables partent du contenu : `getMimeType()` et `guessExtension()`.

**`getSize()`** : la documentation la range parmi les valeurs non sûres. Le
code la mesure : elle vient de `\SplFileInfo` et rend la taille du file
temporaire reçu, pas une valeur déclarée. Retenir les deux.

## Pièges d'examen

**Rien de ce que le client annonce sur le file n'est fiable.** Nom, extension,
path, type MIME annoncé : tout vient du navigateur.

**`guessClientExtension()` n'est pas `guessExtension()`.** La première part du
type annoncé, la seconde du contenu.

**Sans déplacement, il ne reste rien.** Le file temporaire disparaît à la fin
de la Request.

**Un file manquant donne 422, pas 400.** Sauf argument nullable, avec défaut,
ou typé array.

**`move()` lève une exception typée** selon l'erreur de téléversement.

## Application

- Stocker un avatar → slug du nom d'origine, `uniqid()`, puis `guessExtension()`.
- Un file facultatif → `?UploadedFile $avatar = null`, sinon 422.

## QCM d'entraînement

**Question 1.** Un PNG est envoyé sous le nom `photo.exe`, avec le type annoncé
`image/jpeg`. Que rend `guessClientExtension()` ?

- **A.** `png`
- **B.** `exe`
- **C.** `null` : type non fiable
- **D.** `jpg`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** La method déduit l'extension du type MIME annoncé par le
client ; seule `guessExtension()` lit le contenu et rendrait `png`.

</details>

**Question 2.** Le file envoyé dépasse `upload_max_filesize`. Que fait
`move()` ?

- **A.** Elle lève `FormSizeFileException`
- **B.** Elle lève `IniSizeFileException`
- **C.** Elle lève `PartialFileException`
- **D.** Elle déplace un file tronqué

<details>
<summary>Correction</summary>

**Bonne réponse : B.** `isValid()` échoue, et l'exception levée dépend du code
d'erreur PHP : celui de la limite de `php.ini` donne celle-ci.

</details>

**Question 3.** L'argument `#[MapUploadedFile] array $photos` ne reçoit aucun
file. Quelle valeur prend-il ?

- **A.** `null`
- **B.** Une `HttpException` 422
- **C.** `[]`
- **D.** Une `TypeError`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Un argument typé `array` sans default value reçoit un
array vide ; le 422 est réservé aux autres arguments obligatoires.

</details>

**Question 4.** Selon le code de Symfony 8.0, que mesure
`$file->getSize()` ?

- **A.** La taille du file temporaire reçu
- **B.** La taille déclarée par le navigateur
- **C.** La limite `upload_max_filesize`
- **D.** Rien : la method n'existe pas sur `UploadedFile`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** La method est héritée de `\SplFileInfo` et lit le file
référencé ; la documentation la range pourtant parmi les valeurs non sûres.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- `$request->files` ou **`#[MapUploadedFile]`** (`name`, sinon nom d'argument).
- Absent : `null`, défaut, **`[]`** pour un array, sinon **422**.
- Sans **`move()`**, rien ne reste ; elle rend un `File`.
- `move()` lève une exception **par code d'erreur** (`IniSizeFileException`…).
- Non fiables : les `getClient…` **et `guessClientExtension()`**.
- Fiables : **`getMimeType()`**, **`guessExtension()`** ; `getSize()` mesurée par le code.

Moyen mnémotechnique : **« Client annonce, contenu prouve »**.

## Sources officielles

- [How to Upload Files](https://github.com/symfony/symfony-docs/blob/8.0/controller/upload_file.rst)
- [Controller, « Mapping Uploaded Files »](https://github.com/symfony/symfony-docs/blob/8.0/controller.rst)
- [`UploadedFile`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/File/UploadedFile.php)
- [`RequestPayloadValueResolver`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Controller/ArgumentResolver/RequestPayloadValueResolver.php)
- [`SplFileInfo::getSize`](https://github.com/php/doc-en/blob/master/reference/spl/splfileinfo/getsize.xml)

