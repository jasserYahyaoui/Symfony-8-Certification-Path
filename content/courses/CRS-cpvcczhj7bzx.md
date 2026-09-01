---
id: CRS-cpvcczhj7bzx
official_item: OIT-j6tqs8s8f54f
title: "Handling file upload"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller/upload_file.rst"
    anchor: "adding-the-file-field-to-the-form"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
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
])
```

Deux options portent tout le sujet.

**`mapped: false`** est l'astuce centrale. L'entité stocke en général un *nom de
fichier*, pas un fichier ; le champ n'a donc pas de propriété correspondante. Le
déclarer non mappé empêche le formulaire de vouloir écrire un `UploadedFile`
dans une propriété qui attend une chaîne.

**`required: false`** rend le téléversement facultatif — utile sur un formulaire
de modification, où l'absence de fichier signifie « garder l'existant ».

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

## Points clés

- `FileType` pour le widget ; `mapped: false` parce que l'entité stocke un nom,
  pas un fichier.
- `required: false` pour un téléversement facultatif.
- La valeur se lit par `$form->get('champ')->getData()`, et peut être `null`.

## Sources officielles

- [How to Upload Files, ajout du champ au formulaire](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller/upload_file.rst)
