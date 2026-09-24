---
id: CRS-nfkzx2s1n3r3
official_item: OIT-mgvdw7cfpwyz
title: "Forms handling"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-24"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/forms.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/forms.rst"
    anchor: "processing-forms"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/Form.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Form.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "Form::isValid(), Form::submit()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/Extension/HttpFoundation/HttpFoundationRequestHandler.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/HttpFoundation/HttpFoundationRequestHandler.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "HttpFoundationRequestHandler::handleRequest()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "AbstractController::doRender()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/InputBag.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/InputBag.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "InputBag::get()"
    verified_at: "2026-09-24"
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

Le critère est d'abord la **méthode** : `HttpFoundationRequestHandler` compare
la méthode de la requête à l'option `method` du formulaire, `POST` par défaut,
et s'arrête si elles diffèrent. Exécuté : un formulaire `POST` reçoit une
requête `GET` portant ses champs → `isSubmitted()` vaut `false`.

La redirection après succès évite qu'un rafraîchissement du navigateur ne
renvoie le `POST`.

## L'ordre des deux tests

`isSubmitted()` **avant** `isValid()` — et ce n'est pas une question de style :
`Form::isValid()` **lève une `LogicException`** sur un formulaire non soumis,
« Cannot check if an unsubmitted form is valid ». Vérifié par exécution. Un
formulaire soumis mais désactivé (`disabled`) est, lui, toujours valide.

Après une soumission valide, l'objet passé à `createForm()` est **déjà mis à
jour** : `getData()` et la variable d'origine désignent la même chose.

## Le rendu qui suit

Rendre le formulaire invalide est le comportement normal :
`AbstractController::render()` repère un formulaire soumis et invalide parmi ses
paramètres et passe la réponse en **422** — seulement si son statut était
encore 200. Il convertit aussi le formulaire en vue : pas besoin d'appeler
`createView()`.

## `submit()`, pour le contrôle fin

`handleRequest()` est recommandé. `submit()` sert quand on décide soi-même du
moment et de la donnée :

```php
if ($request->isMethod('POST')) {
    $form->submit($request->getPayload()->all($form->getName()));
}
```

L'exemple de `forms.rst` (8.0) écrit `getPayload()->get($form->getName())`.
Pour un formulaire composé, la valeur est un tableau, et `InputBag::get()` le
refuse : **`BadRequestException`**, « Input value "form" contains a non-scalar
value » — exécuté avec HttpFoundation 8.0.15. La forme correcte est `all()`,
comme ci-dessus.

Son second argument, `$clearMissing`, vaut `true` par défaut : les champs absents
de la soumission sont mis à `null`. Le passer à `false` — le cas d'une requête
`PATCH` — laisse les champs absents intacts. `handleRequest()` fait ce choix de
lui-même : il appelle `submit($data, 'PATCH' !== $method)`.

**Le piège qui va avec :** avec `$clearMissing = false`, la validation ne
s'applique **qu'aux champs soumis**. Exécuté avec un `NotBlank` sur un champ
vide absent de la soumission : formulaire **valide** ; le même champ ajouté avec
`null` : **invalide**. C'est la parade documentée.

## Pièges d'examen

**`isValid()` sur un formulaire non soumis lève une exception.** Le garde
`isSubmitted() && isValid()` n'est pas facultatif.

**Une requête d'une autre méthode que celle du formulaire est ignorée.**

**Après une soumission valide, l'objet est déjà à jour.**

**La soumission manuelle vide les champs absents par défaut** ; `false` les
préserve, et ne valide alors que ce qui a été soumis.

## Points clés

- Une seule action rend et traite ; `handleRequest()` ne fait rien si la
  méthode diffère.
- `isSubmitted()` puis `isValid()` ; l'inverse lève une `LogicException`.
- L'objet d'origine est mis à jour ; `getData()` le retourne.
- `render()` : 422 pour un formulaire soumis invalide.
- `submit($data, $clearMissing = true)` ; `false` pour un `PATCH`, choisi
  automatiquement par `handleRequest()`.

## Sources officielles

- [Forms, « Processing Forms » et « Using the submit() Method »](https://github.com/symfony/symfony-docs/blob/8.0/forms.rst)
- [Form 8.0, `Form`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Form.php) et [`HttpFoundationRequestHandler`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/HttpFoundation/HttpFoundationRequestHandler.php)
- [FrameworkBundle 8.0, `AbstractController`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php)
