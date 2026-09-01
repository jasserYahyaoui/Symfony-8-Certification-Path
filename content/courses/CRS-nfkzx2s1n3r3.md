---
id: CRS-nfkzx2s1n3r3
official_item: OIT-mgvdw7cfpwyz
title: "Forms handling"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/forms.rst"
    anchor: "processing-forms"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Traiter une soumission, dans le bon ordre, et connaître l'alternative à
`handleRequest()`.

## Le motif recommandé

Une **seule action** affiche et traite le formulaire :

```php
$form = $this->createForm(TaskType::class, $task);
$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
    // $form->getData() porte les valeurs soumises,
    // et $task a été mis à jour au passage
    return $this->redirectToRoute('task_success');
}

return $this->render('task/new.html.twig', ['form' => $form]);
```

`handleRequest()` regarde la requête, décide si le formulaire a été soumis, et
ne fait rien s'il ne l'a pas été. C'est ce qui permet au même code de servir les
deux cas.

## L'ordre des deux tests

`isSubmitted()` **avant** `isValid()`, et jamais l'inverse : un formulaire non
soumis n'a rien à valider. La conjonction se lit comme une garde, pas comme deux
questions indépendantes.

Après une soumission valide, l'objet passé à `createForm()` est **déjà mis à
jour** : `getData()` et la variable d'origine désignent la même chose.

## Le rendu qui suit

Rendre le formulaire invalide est le comportement normal : `render()` détecte un
formulaire invalide parmi ses paramètres et retourne un **422** de lui-même.

## `submit()`, pour le contrôle fin

`handleRequest()` est recommandé. `submit()` sert quand on décide soi-même du
moment et de la donnée :

```php
if ($request->isMethod('POST')) {
    $form->submit($request->getPayload()->get($form->getName()));
}
```

Son second argument, `$clearMissing`, vaut `true` par défaut : les champs absents
de la soumission sont mis à `null`. Le passer à `false` — le cas d'une requête
`PATCH` — laisse les champs absents intacts.

**Le piège qui va avec :** avec `$clearMissing = false`, la validation ne
s'applique **qu'aux champs soumis**. Pour forcer la validation d'un champ absent,
il faut l'ajouter explicitement à la donnée soumise, avec la valeur `null`.

## Points clés

- Une seule action rend et traite ; `handleRequest()` ne fait rien hors
  soumission.
- `isSubmitted()` puis `isValid()`, dans cet ordre.
- L'objet d'origine est mis à jour ; `getData()` le retourne.
- `submit($data, $clearMissing = true)` ; `false` pour un `PATCH`.
- Avec `$clearMissing = false`, seuls les champs soumis sont validés.

## Sources officielles

- [Forms, « Processing Forms » et « Using the submit() Method »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/forms.rst)
