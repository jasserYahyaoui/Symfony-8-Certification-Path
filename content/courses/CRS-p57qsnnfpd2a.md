---
id: CRS-p57qsnnfpd2a
official_item: OIT-emymfwgesh99
title: "Generate 404 pages"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-08"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller/error_pages.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/controller/error_pages.rst"
    anchor: "overriding-the-default-error-templates"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/controller.rst"
    anchor: "managing-errors-and-404-pages"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/ErrorRenderer/TwigErrorRenderer.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/ErrorRenderer/TwigErrorRenderer.php"
    symbol_or_lines: "render, isDebug, findTemplate"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/ErrorHandler/Exception/FlattenException.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/ErrorHandler/Exception/FlattenException.php"
    symbol_or_lines: "createFromThrowable"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/Controller/ErrorController.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Controller/ErrorController.php"
    symbol_or_lines: "preview"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Exception/JsonException.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Exception/JsonException.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "final class JsonException extends UnexpectedValueException implements RequestExceptionInterface"
    verified_at: "2026-10-08"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Exception/SessionNotFoundException.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Exception/SessionNotFoundException.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "SessionNotFoundException extends LogicException implements RequestExceptionInterface"
    verified_at: "2026-10-08"
outcome_sections:
  OUT-qa34bn6sqmqj: "Produire le 404"
  OUT-kybyc8rb3vw0: "Personnaliser la page"
  OUT-jwxbjkcsf51k: "Personnaliser la page"
  OUT-ym6q5502fmyf: "Deux pièges"
  OUT-mfs1nh45d7sr: "Deux pièges"
---

## Objectif

Produire un 404 depuis un controller, savoir quel statut produit une exception,
et personnaliser la page affichée.

## L'essentiel

`createNotFoundException()` **construit** une `NotFoundHttpException` : sans
`throw`, rien ne se passe. Le statut d'une exception se décide en trois lignes —
celui d'une exception HTTP, **400** pour une `RequestExceptionInterface`, sinon
500. La page se personnalise par des templates Twig, qui reçoivent une
`FlattenException`, et ne s'affichent ni en debug ni avec la security.

## Le schéma

```text
throw $this->createNotFoundException('…')   ≡ new NotFoundHttpException($message, $previous) ; défaut 'Not Found'
statut (FlattenException::createFromThrowable)
   HttpExceptionInterface → son statut · RequestExceptionInterface → 400 · autre → 500
template (templates/bundles/TwigBundle/Exception/, production)
   error404.html.twig → error.html.twig → HtmlErrorRenderer (rendu intégré)
variables   exception (FlattenException) · status_code · status_text
debug       page d'exception à la place ; prévisualiser par /_error/404 (when@dev)
```

## L'exemple

```php
#[Route('/product/{id}')]
public function show(int $id): Response
{
    $product = $this->catalog->find($id);
    if (null === $product) {
        throw $this->createNotFoundException('The product does not exist');
    }
    // ...
}
```

## Produire le 404

Le mot important est `throw`. `createNotFoundException()` **construit et
retourne** l'exception ; elle ne la lève pas. Sans `throw`, le controller
continue et la page s'affiche normalement, en 200. Sans argument, le message
vaut `'Not Found'`.

## Quel statut pour quelle exception

`FlattenException::createFromThrowable()` décide, dans cet ordre :

| Exception levée | Statut |
|---|---|
| implémente `HttpExceptionInterface` | celui qu'elle porte |
| implémente `RequestExceptionInterface` | **400** |
| toute autre | **500** |

La documentation omet la deuxième ligne.
Cinq exceptions de HttpFoundation l'implémentent et donnent un 400 :
`BadRequestException`, `SuspiciousOperationException`,
`ConflictingHeadersException`, `SessionNotFoundException` et `JsonException`,
que lève `getPayload()` sur un body invalide.

## Personnaliser la page

On surcharge les templates de TwigBundle dans
`templates/bundles/TwigBundle/Exception/`. `TwigErrorRenderer` cherche
`error404.html.twig`, puis `error.html.twig`, puis passe la main au rendu HTML
intégré, `HtmlErrorRenderer`. Un file par statut n'est donc pas nécessaire. Ces
templates ne servent qu'au HTML.

Le template reçoit `status_code`, `status_text` et `exception`. La
documentation présente `exception` comme l'exception levée ; le code passe une
`FlattenException`, une class autonome qui n'étend pas `Exception`.
`exception.message` fonctionne, un test sur la class d'origine échoue. Ne jamais
afficher `exception.traceAsString` à un user final.

## Deux pièges

**En développement**, le mode debug fait sauter le template : la grande page
d'exception s'affiche. Pour prévisualiser, on importe en `when@dev` les routes
de FrameworkBundle sous le préfixe `/_error` : `/_error/404`, ou
`/_error/404.json` pour un autre format.

**La security n'est pas disponible sur une page 404.** À cause de l'ordre de
chargement du routage et de la security, le user y apparaît déconnecté.

## Pièges d'examen

**La method d'aide construit l'exception, elle ne la lève pas.** Sans `throw`,
le controller poursuit et la page s'affiche normalement.

**Toute exception non HTTP ne donne pas 500.** Celles qui implémentent
`RequestExceptionInterface` donnent 400.

**`exception` dans le template n'est pas l'exception levée.** C'est sa version
aplatie.

**Il n'y a pas besoin d'un template par statut.** Le rendu retombe sur le template
générique, puis sur le rendu intégré.

**La security n'est pas disponible sur une page 404.** Une page d'erreur ne peut
pas afficher le nom du user.

## Application

- Un produit absent → `throw $this->createNotFoundException()`, jamais un
  `return` d'une page vide.
- Une seule page d'erreur pour tous les statuts → `error.html.twig` suffit.

## QCM d'entraînement

**Question 1.** `$request->getPayload()` lit un body JSON mal formé, et
l'exception n'est pas attrapée. Quel statut reçoit le client ?

- **A.** 500 : ce n'est pas une exception HTTP
- **B.** 400
- **C.** 422
- **D.** 415

<details>
<summary>Correction</summary>

**Bonne réponse : B.** La `JsonException` de HttpFoundation implémente
`RequestExceptionInterface`, et l'aplatissement de l'exception traduit cette
interface en statut 400.

</details>

**Question 2.** L'app a `error404.html.twig` et `error.html.twig`. En
production, un controller laisse échapper une `\InvalidArgumentException`.
Quel template rend la page ?

- **A.** `error404.html.twig`
- **B.** Aucun : le rendu intégré prend la main
- **C.** `error.html.twig`
- **D.** `error500.html.twig`, créé à la volée

<details>
<summary>Correction</summary>

**Bonne réponse : C.** L'exception donne un 500 ; faute de template portant ce
code, le renderer retombe sur le template générique.

</details>

**Question 3.** En production, le projet n'a **aucun** template d'erreur.
Qu'affiche une exception non attrapée ?

- **A.** Le rendu HTML intégré
- **B.** Une page blanche
- **C.** Une erreur Twig : template introuvable
- **D.** La page d'exception du debug

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Quand ni le template du code ni le générique n'existe,
`TwigErrorRenderer` délègue au renderer HTML de secours.

</details>

**Question 4.** En développement, `error404.html.twig` existe. Un visiteur
demande une page absente. Que voit-il ?

- **A.** Le template `error404.html.twig`
- **B.** `error.html.twig`, en repli
- **C.** Une page blanche, faute de template de debug
- **D.** La page d'exception du mode debug

<details>
<summary>Correction</summary>

**Bonne réponse : D.** En debug, `TwigErrorRenderer` ne cherche pas de template
et passe directement au rendu de secours, qui affiche l'exception.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- **`throw`** devant `createNotFoundException()` ; défaut `'Not Found'`.
- Statut : HTTP → le sien ; **`RequestExceptionInterface` → 400** ; sinon 500.
- Templates : `error404.html.twig` → **`error.html.twig`** → rendu intégré.
- `exception` = une **`FlattenException`**, pas l'exception levée.
- Debug : page d'exception ; **`/_error/404`** pour prévisualiser.
- Pas de **security** sur une page 404.

Moyen mnémotechnique : **« Sans throw, pas de 404 »**.

## Sources officielles

- [How to Customize Error Pages](https://github.com/symfony/symfony-docs/blob/8.0/controller/error_pages.rst)
- [Controller, « Managing Errors and 404 Pages »](https://github.com/symfony/symfony-docs/blob/8.0/controller.rst)
- [`TwigErrorRenderer`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/ErrorRenderer/TwigErrorRenderer.php)
- [`FlattenException`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/ErrorHandler/Exception/FlattenException.php)
- [`ErrorController::preview()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Controller/ErrorController.php)

