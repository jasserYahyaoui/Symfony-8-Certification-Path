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
    symbol_or_lines: "class declaration, setContainer, getSubscribedServices, protected helpers"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller.rst"
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

Par famille :

| Famille | Méthodes |
|---|---|
| URL et navigation | `generateUrl()`, `redirect()`, `redirectToRoute()`, `forward()` |
| Rendu | `render()`, `renderView()`, `renderBlock()`, `renderBlockView()`, `stream()` |
| Réponse | `json()`, `file()`, `sendEarlyHints()` |
| Erreurs | `createNotFoundException()`, `createAccessDeniedException()` |
| Sécurité | `isGranted()`, `denyAccessUnlessGranted()`, `getUser()`, `isCsrfTokenValid()` |
| Formulaires | `createForm()`, `createFormBuilder()` |
| Divers | `addFlash()`, `getParameter()`, `addLink()` |

## Un abonné à des services, pas un conteneur

`AbstractController` implémente `ServiceSubscriberInterface`. Elle ne reçoit
donc **pas** le conteneur complet : elle déclare dans `getSubscribedServices()`
la liste exacte de ce dont elle a besoin — `router`, `request_stack`,
`http_kernel`, `serializer`, `twig`, `form.factory`, `parameter_bag`, les
services de sécurité — et reçoit un conteneur restreint à cette liste.

Chaque entrée est préfixée par `?` : le service est **facultatif**. C'est ce qui
permet d'utiliser la classe dans une application sans Twig ou sans le composant
Security ; l'appel du raccourci correspondant échoue alors avec un message
explicite plutôt que par une erreur d'autowiring.

L'injection passe par `setContainer()`, marquée `#[Required]`, donc appelée
automatiquement à l'instanciation du service.

Pour tout le reste, la voie normale reste le **type-hint** d'un argument
d'action : Symfony injecte le service correspondant.

## Points clés

- Optionnelle ; un contrôleur est un appelable, rien de plus.
- Vient de FrameworkBundle.
- Tous les raccourcis sont `protected`.
- `ServiceSubscriberInterface` + `getSubscribedServices()` : conteneur restreint,
  services déclarés facultatifs par le préfixe `?`.
- `setContainer()` est `#[Required]`.

## Sources officielles

- [AbstractController, branche 8.0](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php)
- [Controller, « The Base Controller Class & Services »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller.rst)
