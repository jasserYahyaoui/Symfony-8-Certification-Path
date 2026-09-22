---
id: CRS-h8s87edcx8ae
official_item: OIT-gqpj4rbt0hc7
title: "The base AbstractController class"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
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
il n'est pas un conteneur déguisé. La production de la réponse — `render()`,
`json()`, `file()` — est traitée dans l'item *The response*.

## Elle est optionnelle

Un contrôleur Symfony est **n'importe quel appelable PHP**. Il n'a pas besoin
d'étendre quoi que ce soit ; la documentation présente `AbstractController`
comme une classe de base *optionnelle*, dont on hérite pour gagner des
raccourcis. Une classe qui n'en hérite pas fonctionne exactement pareil, elle
écrit simplement plus de code.

Elle vient de **FrameworkBundle**, pas du composant HttpKernel.

## Les raccourcis sont `protected`

Toutes les méthodes utilitaires sont déclarées `protected`. Conséquence directe
et régulièrement interrogée : elles ne sont appelables que **depuis la
sous-classe**. On ne peut pas appeler `$controller->render(...)` depuis
l'extérieur, ni depuis un test unitaire qui traiterait le contrôleur comme un
service ordinaire.

La classe compte, sur la branche `8.0`, **vingt-quatre** méthodes `protected`,
**deux** `public` et **deux** `private`. Les deux méthodes publiques ne sont pas
des raccourcis : ce sont les deux points d'attache de l'infrastructure,
`setContainer()` et `getSubscribedServices()`.

| Famille | Méthodes |
|---|---|
| URL et navigation | `generateUrl()`, `redirect()`, `redirectToRoute()`, `forward()` |
| Rendu | `render()`, `renderView()`, `renderBlock()`, `renderBlockView()`, `stream()` |
| Réponse | `json()`, `file()`, `sendEarlyHints()` |
| Erreurs | `createNotFoundException()`, `createAccessDeniedException()` |
| Sécurité | `isGranted()`, `getAccessDecision()`, `denyAccessUnlessGranted()`, `getUser()`, `isCsrfTokenValid()` |
| Formulaires | `createForm()`, `createFormBuilder()` |
| Divers | `addFlash()`, `getParameter()`, `addLink()` |

## Un abonné à des services, pas un conteneur

`AbstractController` implémente `ServiceSubscriberInterface`. Elle ne reçoit
donc **pas** le conteneur complet : `getSubscribedServices()` déclare **onze**
entrées, et elle reçoit un conteneur restreint à celles-là.

| Clé | Pour |
|---|---|
| `router`, `request_stack`, `http_kernel` | URL, requête courante, sous-requêtes |
| `twig`, `form.factory`, `serializer` | rendu, formulaires, `json()` |
| `security.authorization_checker`, `security.token_storage`, `security.csrf.token_manager` | les trois services de sécurité, distincts |
| `parameter_bag` | `getParameter()` |
| `web_link.http_header_serializer` | `sendEarlyHints()` |

Chaque valeur est préfixée par `?` : le service est **facultatif**. C'est ce qui
permet d'utiliser la classe sans Twig ou sans le composant Security ; le
raccourci correspondant lève alors une exception au message explicite plutôt que
d'échouer à l'autowiring.

L'injection passe par `setContainer()`, marquée `#[Required]`, donc appelée
automatiquement. Détail de signature : elle **retourne le conteneur
précédent** — `?ContainerInterface` — elle ne retourne pas `void`.

Pour tout le reste, la voie normale reste le **type-hint** d'un argument
d'action : Symfony injecte le service correspondant.

## Deux raccourcis dont le nom trompe

`addLink()` n'écrit **rien dans la réponse**. Elle dépose un fournisseur de
liens dans l'attribut de requête `_links` ; c'est un écouteur qui en fera
l'en-tête `Link`. Sans le composant WebLink, elle lève une `LogicException`.

`sendEarlyHints()` envoie les en-têtes **immédiatement**, avec le statut
**103**, avant que la réponse finale ne parte. C'est le seul raccourci de la
classe qui écrit sur la sortie au moment où on l'appelle.

## Pièges d'examen

**Étendre `AbstractController` est facultatif.** Un contrôleur est n'importe quel
appelable PHP ; la classe de base fait gagner des raccourcis, pas des droits.

**Ses raccourcis sont `protected`.** Ils ne s'appellent que depuis la
sous-classe : un test qui traiterait le contrôleur comme un service ordinaire ne
peut pas les invoquer de l'extérieur. Les deux seules méthodes `public` sont
`setContainer()` et `getSubscribedServices()`.

**Ce n'est pas le conteneur.** La classe est un *abonné* : onze services
déclarés, tous facultatifs, et rien d'autre n'est accessible.

**Elle vient de FrameworkBundle**, pas du composant HttpKernel.

**`addLink()` n'écrit pas dans la réponse.** Elle remplit l'attribut de requête
`_links` ; l'en-tête est produit plus tard par un écouteur.

## Tips d'examen

**Le préfixe `?` est la clé de lecture de la liste.** Il ne dit pas « peut-être
présent dans le conteneur » au hasard : il dit que la classe fonctionne sans, et
que le raccourci concerné lèvera une exception explicite si on l'appelle quand
même.

**Pour situer un raccourci** : s'il produit une réponse, il est dans le lot 04 ;
s'il touche à la sécurité, il en délègue tout à l'un des trois services de
sécurité déclarés.

## Points clés

- Optionnelle ; un contrôleur est un appelable, rien de plus.
- Vient de FrameworkBundle.
- 24 méthodes `protected`, 2 `public` d'infrastructure, 2 `private`.
- `ServiceSubscriberInterface` + `getSubscribedServices()` : **onze** entrées,
  toutes facultatives par le préfixe `?`.
- `setContainer()` est `#[Required]` et retourne le conteneur **précédent**.
- `addLink()` passe par l'attribut `_links` ; `sendEarlyHints()` émet un **103**.

## Sources officielles

- [AbstractController, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php)
- [Controller, « The Base Controller Class & Services »](https://github.com/symfony/symfony-docs/blob/8.0/controller.rst)
