---
id: CRS-h8s87edcx8ae
official_item: OIT-gqpj4rbt0hc7
title: "The base AbstractController class"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-07"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php"
    symbol_or_lines: "class declaration, setContainer, getSubscribedServices, protected helpers"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/controller.rst"
    anchor: "the-base-controller-class-services"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
outcome_sections:
  OUT-5xtttnzd990y: "Elle est optionnelle"
  OUT-37kbkcyddm1r: "Les raccourcis sont `protected`"
  OUT-j0pg9ejmv4hg: "Un subscriber à des services, pas un container"
  OUT-mzj3xrwsw6dr: "Un subscriber à des services, pas un container"
---

## Objectif

Savoir ce que `AbstractController` apporte, ce qu'il n'apporte pas, et pourquoi
il n'est pas un container déguisé. La production de la Response — `render()`,
`json()`, `file()` — est traitée dans l'item *The response*.

## L'essentiel

`AbstractController`, de **FrameworkBundle**, est une class de base
**optionnelle** : un controller est n'importe quel callable. Elle apporte des
raccourcis, tous **`protected`**, et reçoit non pas le container mais un
**locator** de onze services, tous facultatifs. Sans un service, le raccourci
qui en dépend échoue à l'appel, pas à la compilation.

## Le schéma

```text
AbstractController (FrameworkBundle) implements ServiceSubscriberInterface
  public     setContainer()  #[Required], retourne ?ContainerInterface (le précédent)
             getSubscribedServices()  → 11 services, tous préfixés '?'
  protected  24 raccourcis : render, json, redirectToRoute, isGranted, addFlash…
  private    doRender, doRenderView
service absent → render() : LogicException · generateUrl() : ServiceNotFoundException · json() : json_encode
addLink() → attribute _links (listener → header Link) · sendEarlyHints() → 103 immédiat
```

## L'exemple

```php
final class ProductController extends AbstractController
{
    #[Route('/product/{id}')]
    public function show(int $id, ProductCatalog $catalog): Response   // service : type-hint
    {
        return $this->render('product/show.html.twig', ['product' => $catalog->get($id)]);
    }
}
```

## Elle est optionnelle

Un controller Symfony est **n'importe quel callable PHP**. Une class qui
n'étend pas `AbstractController` fonctionne pareil ; elle écrit simplement plus
de code. La class vient de **FrameworkBundle**, pas du component HttpKernel.

## Les raccourcis sont `protected`

Sur la branche `8.0`, la class compte **vingt-quatre** methods `protected`,
**deux** `public` et **deux** `private`. Les raccourcis ne s'appellent que
depuis la sous-classe, jamais de l'extérieur ni depuis un test qui traiterait
le controller comme un service. Les deux methods publiques sont les points
d'attache de l'infrastructure : `setContainer()` et `getSubscribedServices()`.

| Famille | Methods |
|---|---|
| URL et navigation | `generateUrl()`, `redirect()`, `redirectToRoute()`, `forward()` |
| Rendu | `render()`, `renderView()`, `renderBlock()`, `renderBlockView()`, `stream()` |
| Response | `json()`, `file()`, `sendEarlyHints()` |
| Erreurs | `createNotFoundException()`, `createAccessDeniedException()` |
| Security | `isGranted()`, `getAccessDecision()`, `denyAccessUnlessGranted()`, `getUser()`, `isCsrfTokenValid()` |
| Forms, divers | `createForm()`, `createFormBuilder()`, `addFlash()`, `getParameter()`, `addLink()` |

`addLink()` n'écrit rien dans la Response : elle remplit l'attribute de Request
`_links`, dont un listener fera le header `Link`. `sendEarlyHints()` envoie ses
headers **immédiatement**, en **103**.

## Un subscriber à des services, pas un container

`getSubscribedServices()` déclare **onze** entrées : `router`, `request_stack`,
`http_kernel`, `serializer`, `twig`, `form.factory`,
`security.authorization_checker`, `security.token_storage`,
`security.csrf.token_manager`, `parameter_bag` et
`web_link.http_header_serializer`. La class reçoit un container restreint à
celles-là, injecté par `setContainer()`, marquée `#[Required]`, qui retourne le
container **précédent**.

Chaque entrée est préfixée par `?` : **facultative**. Sans le service, le
raccourci lève à l'appel — une `LogicException` explicite pour le rendu ; sans
Serializer, `json()` ne lève pas : il se rabat sur `json_encode`. Sans router,
`generateUrl()` lève une `ServiceNotFoundException` du locator, pas une
`LogicException`. Pour tout autre service, la voie normale est le
**type-hint** d'un argument d'action.

## Pièges d'examen

**Étendre `AbstractController` est facultatif.** Un controller est n'importe quel
appelable PHP ; la class de base fait gagner des raccourcis, pas des droits.

**Ses raccourcis sont `protected`.** Ils ne s'appellent que depuis la
sous-classe : un test qui traiterait le controller comme un service ordinaire ne
peut pas les invoquer de l'extérieur. Les deux seules methods `public` sont
`setContainer()` et `getSubscribedServices()`.

**Ce n'est pas le container.** La class est un *subscriber* : onze services
déclarés, tous facultatifs, et rien d'autre n'est accessible.

**Elle vient de FrameworkBundle**, pas du component HttpKernel.

**`addLink()` n'écrit pas dans la Response.** Elle remplit l'attribute de Request
`_links` ; le header est produit plus tard par un listener.

## Application

- Un unit test veut appeler `render()` sur le controller → impossible,
  passer par un functional test.
- Le controller a besoin d'un service hors de la liste → type-hint d'argument.

## QCM d'entraînement

**Question 1.** Un controller hérite de la class de base de FrameworkBundle.
Combien d'entrées compte le locator restreint qu'il reçoit ?

- **A.** Vingt-quatre
- **B.** Trois, ceux de la security
- **C.** Onze
- **D.** Tout le container

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Onze entrées, toutes préfixées par `?` : la class reçoit
un locator restreint à celles-là, pas le container complet.

</details>

**Question 2.** Le container d'un controller ne fournit pas `router`. L'action
appelle `$this->generateUrl('home')`. Que se passe-t-il ?

- **A.** Une `LogicException` au message explicite
- **B.** Une `ServiceNotFoundException`
- **C.** Une URL relative, sans router
- **D.** `null`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Ce raccourci ne vérifie pas la présence du service : le
locator lève lui-même son exception, contrairement aux methods de rendu.

</details>

**Question 3.** Une action appelle `$this->addLink($request, $link)`, puis
inspecte les headers de la Response qu'elle va retourner. Qu'y trouve-t-elle ?

- **A.** Un header `Link`
- **B.** Un header `Link`, envoyé en 103
- **C.** Une exception : la Response n'existe pas encore
- **D.** Rien : le lien est dans `_links`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Le raccourci dépose le lien dans un attribute de la
Request ; c'est un listener qui écrira le header plus tard.

</details>

**Question 4.** Avec quel status code `sendEarlyHints()` émet-elle ses headers ?

- **A.** 103
- **B.** 100
- **C.** 200, avec la Response finale
- **D.** 302

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Les headers partent tout de suite, avec un statut
informationnel, avant que la Response finale ne soit envoyée.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- **Optionnelle**, fournie par **FrameworkBundle** ; un controller est un callable.
- **24 `protected`**, 2 `public` (`setContainer`, `getSubscribedServices`), 2 `private`.
- Un **locator** de **11** services, tous facultatifs (`?`).
- `setContainer()` : `#[Required]`, retourne le container **précédent**.
- Service absent : exception **à l'appel** ; `json()` retombe sur `json_encode`.
- `addLink()` → **`_links`** ; `sendEarlyHints()` → **103**.

Moyen mnémotechnique : **« Onze services, zéro container »**.

## Sources officielles

- [AbstractController, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php)
- [Controller, « The Base Controller Class & Services »](https://github.com/symfony/symfony-docs/blob/8.0/controller.rst)

