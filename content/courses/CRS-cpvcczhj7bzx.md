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
---

## Objectif

Brancher un téléversement sur un formulaire. `UploadedFile`, `move()` et le
caractère non fiable des métadonnées client sont traités dans le lot
Controllers.

## Le champ

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
fichier*, pas un fichier ; le champ n'a donc pas de propriété correspondante.

Le laisser mappé ne lève même pas d'erreur. Exécuté avec `symfony/form` 8.0.15
et `HttpFoundationExtension`, sur `public string $brochure` : le formulaire est
**valide** et la propriété reçoit le **chemin temporaire** du fichier,
`'/tmp/uph3ls52ved943c7dSVBX'` — PHP convertit l'objet en chaîne. Une
corruption silencieuse, pas une exception.

**`required: false`** rend le téléversement facultatif — utile sur un formulaire
de modification, où l'absence de fichier signifie « garder l'existant ».

**`constraints`** : un champ non mappé ne profite pas des contraintes déclarées
sur l'entité ; on les pose sur le champ. Exécuté : un `b.pdf` passe, un `b.txt`
échoue avec le message d'extension. L'option `extensions` de `File` exige le
composant **Mime** : sans lui, `FileValidator` lève une `LogicException`.

## Récupérer le fichier

Un champ non mappé n'atterrit pas dans l'objet. Il se lit sur le formulaire :

```php
$file = $form->get('brochure')->getData();

if ($file) {
    // déplacer le fichier, puis stocker son nom sur l'entité
}
```

Le test est nécessaire : avec `required: false`, `getData()` retourne `null`
quand rien n'a été envoyé.

## Ce que `FileType` filtre

Un écouteur `PRE_SUBMIT` de `FileType` ne garde que de vrais fichiers
téléversés, reconnus par le gestionnaire de requête. Exécuté : une **chaîne**
postée à la place du fichier devient `null`, sans erreur. Avec
`multiple: true`, la valeur est un **tableau** de fichiers.

Le gestionnaire compte : `HttpFoundationRequestHandler`, celui du framework,
reconnaît un `UploadedFile` ; le gestionnaire natif attend le tableau de
`$_FILES`.

## Pièges d'examen

**Un champ de fichier n'est presque jamais mappé** — et le mapper par erreur
n'échoue pas : l'entité reçoit le chemin temporaire.

**Un champ non mappé n'arrive pas dans l'objet**, et vaut `null` quand rien n'a
été envoyé.

**Les contraintes d'un champ non mappé se déclarent sur le champ.**

**`extensions` sans le composant Mime** : `LogicException`.

## Points clés

- `FileType` ; `mapped: false`, `required: false`, `constraints`.
- Valeur lue par `$form->get('champ')->getData()`, `null` si rien n'est envoyé.
- Tout ce qui n'est pas un fichier téléversé devient `null` ; `multiple` rend
  un tableau.
- Mappé sur une chaîne : chemin temporaire, sans erreur.

## Sources officielles

- [How to Upload Files](https://github.com/symfony/symfony-docs/blob/8.0/controller/upload_file.rst)
- [Form 8.0, `FileType`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Core/Type/FileType.php)
- [Validator 8.0, `FileValidator`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/FileValidator.php)
