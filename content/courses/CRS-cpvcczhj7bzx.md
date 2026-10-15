---
id: CRS-cpvcczhj7bzx
official_item: OIT-j6tqs8s8f54f
title: "Handling file upload"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-25"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller/upload_file.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/controller/upload_file.rst"
    anchor: "adding-the-file-field-to-the-form"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/Extension/Core/Type/FileType.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Core/Type/FileType.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "FileType::buildForm(), PRE_SUBMIT listener"
    verified_at: "2026-09-25"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Constraints/FileValidator.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/FileValidator.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "FileValidator::validate(), Mime requirement"
    verified_at: "2026-09-25"
outcome_sections:
  OUT-9qanndr176yx: "Le field"
  OUT-zd387nyydnm7: "Le field"
  OUT-2swcsdfcjqwc: "Récupérer le file"
---

## Objectif

Brancher un téléversement sur un form. `UploadedFile`, `move()` et le
caractère non fiable des métadonnées client sont traités dans le lot
Controllers.

## Le field

```php
->add('brochure', FileType::class, [
    'label' => 'Brochure (PDF)',
    'mapped' => false,
    'required' => false,
    'constraints' => [
        new Assert\File(maxSize: '1024k', extensions: ['pdf']),
    ],
])
```

**`mapped: false`** est l'astuce centrale. L'entité stocke en général un *nom de
file*, pas un file ; le field n'a donc pas de property correspondante.

Le laisser mappé ne lève même pas d'erreur. Exécuté avec `symfony/form` 8.0.15
et `HttpFoundationExtension`, sur `public string $brochure` : le form est
**valide** et la property reçoit le **path temporaire** du file,
`'/tmp/uph3ls52ved943c7dSVBX'` — PHP convertit l'object en chaîne. Une
corruption silencieuse, pas une exception.

**`required: false`** rend le téléversement facultatif — utile sur un form
de modification, où l'absence de file signifie « garder l'existant ».

**`constraints`** : un field non mappé ne profite pas des constraints déclarées
sur l'entité ; on les pose sur le field. Exécuté : un `b.pdf` passe, un `b.txt`
échoue avec le message d'extension. L'option `extensions` de `File` exige le
component **Mime** : sans lui, `FileValidator` lève une `LogicException`.

## Récupérer le file

Un field non mappé n'atterrit pas dans l'object. Il se lit sur le form :

```php
$file = $form->get('brochure')->getData();

if ($file) {
    // déplacer le fichier, puis stocker son nom sur l'entité
}
```

Le test est nécessaire : avec `required: false`, `getData()` retourne `null`
quand rien n'a été envoyé.

## Ce que `FileType` filter

Un listener `PRE_SUBMIT` de `FileType` ne garde que de vrais files
téléversés, reconnus par le handler de Request. Exécuté : une **string**
postée à la place du file devient `null`, sans erreur. Avec
`multiple: true`, la valeur est un **array** de files.

Le handler compte : `HttpFoundationRequestHandler`, celui du framework,
reconnaît un `UploadedFile` ; le handler natif attend l'array de
`$_FILES`.

## Pièges d'examen

**Un field de file n'est presque jamais mappé** — et le mapper par erreur
n'échoue pas : l'entité reçoit le path temporaire.

**Un field non mappé n'arrive pas dans l'object**, et vaut `null` quand rien n'a
été envoyé.

**Les constraints d'un field non mappé se déclarent sur le field.**

**`extensions` sans le component Mime** : `LogicException`.

## Points clés

- `FileType` ; `mapped: false`, `required: false`, `constraints`.
- Valeur lue par `$form->get('champ')->getData()`, `null` si rien n'est envoyé.
- Tout ce qui n'est pas un file téléversé devient `null` ; `multiple` rend
  un array.
- Mappé sur une string : path temporaire, sans erreur.

## Sources officielles

- [How to Upload Files](https://github.com/symfony/symfony-docs/blob/8.0/controller/upload_file.rst)
- [Form 8.0, `FileType`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Core/Type/FileType.php)
- [Validator 8.0, `FileValidator`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/FileValidator.php)
