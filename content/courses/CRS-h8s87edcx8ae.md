---
id: CRS-h8s87edcx8ae
official_item: OIT-gqpj4rbt0hc7
title: "The base AbstractController class"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-07"
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
---

## Objectif

Savoir ce que `AbstractController` apporte, ce qu'il n'apporte pas, et pourquoi
il n'est pas un container déguisé. La production de la Response — `render()`,
`json()`, `file()` — est traitée dans l'item *The response*.

## Elle est optionnelle

Un controller Symfony est **n'importe quel callable PHP**. Il n'a pas besoin
d'étendre quoi que ce soit ; la documentation présente `AbstractController`
comme une class de base *optionnelle*, dont on hérite pour gagner des
raccourcis. Une class qui n'en hérite pas fonctionne exactement pareil, elle
écrit simplement plus de code.

Elle vient de **FrameworkBundle**, pas du composant HttpKernel.

## Les raccourcis sont `protected`

Toutes les methods utilitaires sont déclarées `protected`. Conséquence directe
et régulièrement interrogée : elles ne sont appelables que **depuis la
sous-classe**. On ne peut pas appeler `$controller->render(...)` depuis
l'extérieur, ni depuis un unit test qui traiterait le controller comme un
service ordinaire.

La class compte, sur la branche `8.0`, **vingt-quatre** methods `protected`,
**deux** `public` et **deux** `private`. Les deux methods publiques ne sont pas
des raccourcis : ce sont les deux points d'attache de l'infrastructure,
`setContainer()` et `getSubscribedServices()`.

| Famille | Methods |
|---|---|
| URL et navigation | `generateUrl()`, `redirect()`, `redirectToRoute()`, `forward()` |
| Rendu | `render()`, `renderView()`, `renderBlock()`, `renderBlockView()`, `stream()` |
| Response | `json()`, `file()`, `sendEarlyHints()` |
| Erreurs | `createNotFoundException()`, `createAccessDeniedException()` |
| Security | `isGranted()`, `getAccessDecision()`, `denyAccessUnlessGranted()`, `getUser()`, `isCsrfTokenValid()` |
| Forms | `createForm()`, `createFormBuilder()` |
| Divers | `addFlash()`, `getParameter()`, `addLink()` |

## Un abonné à des services, pas un container

`AbstractController` implémente `ServiceSubscriberInterface`. Elle ne reçoit
donc **pas** le container complet : `getSubscribedServices()` déclare **onze**
entrées, et elle reçoit un container restreint à celles-là.

| Key | Pour |
|---|---|
| `router`, `request_stack`, `http_kernel` | URL, Request courante, sub-Requests |
| `twig`, `form.factory`, `serializer` | rendu, forms, `json()` |
| `security.authorization_checker`, `security.token_storage`, `security.csrf.token_manager` | les trois services de sécurité, distincts |
| `parameter_bag` | `getParameter()` |
| `web_link.http_header_serializer` | `sendEarlyHints()` |

Chaque valeur est préfixée par `?` : le service est **facultatif**. C'est ce qui
permet d'utiliser la class sans Twig ou sans le composant Security ; le
raccourci correspondant lève alors une exception au message explicite plutôt que
d'échouer à l'autowiring.

L'injection passe par `setContainer()`, marquée `#[Required]`, donc appelée
automatiquement. Détail de signature : elle **retourne le container
précédent** — `?ContainerInterface` — elle ne retourne pas `void`.

Pour tout le reste, la voie normale reste le **type-hint** d'un argument
d'action : Symfony injecte le service correspondant.

## Deux raccourcis dont le nom trompe

`addLink()` n'écrit **rien dans la Response**. Elle dépose un provider de
liens dans l'attribute de Request `_links` ; c'est un listener qui en fera
le header `Link`. Sans le composant WebLink, elle lève une `LogicException`.

`sendEarlyHints()` envoie les headers **immédiatement**, avec le statut
**103**, avant que la Response finale ne parte. C'est le seul raccourci de la
class qui écrit sur l'output au moment où on l'appelle.

## Pièges d'examen

**Étendre `AbstractController` est facultatif.** Un controller est n'importe quel
appelable PHP ; la class de base fait gagner des raccourcis, pas des droits.

**Ses raccourcis sont `protected`.** Ils ne s'appellent que depuis la
sous-classe : un test qui traiterait le controller comme un service ordinaire ne
peut pas les invoquer de l'extérieur. Les deux seules methods `public` sont
`setContainer()` et `getSubscribedServices()`.

**Ce n'est pas le container.** La class est un *abonné* : onze services
déclarés, tous facultatifs, et rien d'autre n'est accessible.

**Elle vient de FrameworkBundle**, pas du composant HttpKernel.

**`addLink()` n'écrit pas dans la Response.** Elle remplit l'attribute de Request
`_links` ; le header est produit plus tard par un listener.

## Tips d'examen

**Le préfixe `?` est la key de lecture de la liste.** Il ne dit pas « peut-être
présent dans le container » au hasard : il dit que la class fonctionne sans, et
que le raccourci concerné lèvera une exception si on l'appelle quand même —
sauf `json()` : sans Serializer, il se rabat sur `JsonResponse` et
`json_encode()`, exécuté. Sans router, `generateUrl()` lève une
`ServiceNotFoundException` du localisateur, pas une `LogicException`.

**Pour situer un raccourci** : s'il produit une Response, il est dans le lot 04 ;
s'il touche à la security, il en délègue tout à l'un des trois services de
security déclarés.

## Points clés

- Optionnelle ; un controller est un callable, rien de plus.
- Vient de FrameworkBundle.
- 24 methods `protected`, 2 `public` d'infrastructure, 2 `private`.
- `ServiceSubscriberInterface` + `getSubscribedServices()` : **onze** entrées,
  toutes facultatives par le préfixe `?`.
- `setContainer()` est `#[Required]` et retourne le container **précédent**.
- `addLink()` passe par l'attribute `_links` ; `sendEarlyHints()` émet un **103**.

## Sources officielles

- [AbstractController, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php)
- [Controller, « The Base Controller Class & Services »](https://github.com/symfony/symfony-docs/blob/8.0/controller.rst)
