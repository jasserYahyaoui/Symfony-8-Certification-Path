---
id: CRS-mpwjc4g3vmj7
official_item: OIT-7sewqvmw6468
title: "Request handling"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/http_kernel.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/components/http_kernel.rst"
    anchor: "the-workflow-of-a-request"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/events.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/reference/events.rst"
    anchor: "kernel-events"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/runtime.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/components/runtime.rst"
    anchor: "usage"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/HttpKernel.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/HttpKernel.php"
    symbol_or_lines: "handle, handleRaw, filterResponse, finishRequest, handleThrowable"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-18"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/HttpKernelInterface.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/HttpKernelInterface.php"
    symbol_or_lines: "MAIN_REQUEST, SUB_REQUEST, handle"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-18"
---

## Objectif

Suivre le trajet complet d'une requête HTTP dans une application Symfony, depuis
le contrôleur frontal jusqu'à l'envoi de la réponse, et savoir **qui** fait quoi
à chaque étape.

## Prérequis

Le composant HttpFoundation et le vocabulaire requête / réponse.

## Le contrat

Tout part d'une seule signature, celle de `HttpKernelInterface` :

```php
public function handle(
    Request $request,
    int $type = self::MAIN_REQUEST,
    bool $catch = true
): Response;
```

Elle dit l'essentiel de l'architecture : une requête entre, une réponse sort,
toujours. Les deux autres arguments méritent d'être lus. `$type` vaut
`MAIN_REQUEST` (**1**) ou `SUB_REQUEST` (**2**) — le noyau traite les deux par le
même code, et c'est aux écouteurs de faire la différence quand elle compte. `$catch` décide si
une exception est convertie en réponse ou relancée telle quelle ; c'est ce qui
distingue le comportement de production du comportement de débogage.

## Le trajet

**1. Le contrôleur frontal.** `public/index.php` est le seul point d'entrée. Il
inclut `vendor/autoload_runtime.php` et retourne une fonction qui construit le
`Kernel`. C'est le composant **Runtime** qui prend le relais : c'est lui, et non
le fichier, qui crée la `Request` depuis les globales, appelle `handle()`, envoie
la réponse et déclenche `terminate()`.

**2. Le noyau démarre.** `Kernel::boot()` enregistre les bundles et construit —
ou recharge depuis `var/cache/` — le conteneur de services compilé.

**3. `kernel.request`.** Le premier événement, dispatché **avant** que le
contrôleur soit connu. C'est là que le routeur résout la route et dépose ses
paramètres dans `request->attributes`, dont la clé `_controller`. C'est là aussi
qu'un écouteur peut court-circuiter : s'il appelle `setResponse()`, la résolution
et l'exécution du contrôleur sont sautées — mais **pas la suite du cycle**.
`kernel.response`, puis `kernel.finish_request`, puis `kernel.terminate` après
l'envoi, ont lieu normalement. Une redirection de sécurité fonctionne
exactement ainsi.

**4. Résolution du contrôleur.** Le `ControllerResolver` lit `_controller` dans
les attributs et retourne un *callable* PHP. Le noyau ne sait rien d'autre : un
contrôleur est un appelable, pas nécessairement une méthode d'une classe.

**5. `kernel.controller`.** Le contrôleur est connu mais pas encore exécuté. Un
écouteur peut ici le **remplacer entièrement**.

**6. Résolution des arguments**, puis `kernel.controller_arguments`, juste avant
l'appel. Cet événement relit **deux** choses après le dispatch, le contrôleur
*et* les arguments : un écouteur peut donc encore remplacer le contrôleur ici,
pas seulement à l'étape 5. Le détail des résolveurs de valeur appartient au lot
Controllers.

**7. Le contrôleur est appelé.** Sa valeur de retour décide de la suite.

**8. `kernel.view` — conditionnel.** Il n'est dispatché **que si** le contrôleur
n'a pas retourné de `Response`. Son rôle est de transformer la valeur retournée
en réponse. Si le contrôleur a retourné une `Response`, cet événement n'a jamais
lieu.

**9. `kernel.response`.** Dispatché dans tous les cas, une fois la réponse
obtenue — par le contrôleur ou par un écouteur de `kernel.view`. C'est le dernier
endroit où la modifier : en-têtes, cookies, injection de la barre de débogage.

**10. `kernel.finish_request`.** Après `kernel.response`. Il sert à **restaurer
l'état global** de l'application — la locale, par exemple — ce qui n'a
d'importance que lorsqu'une requête en imbrique une autre.

**11. Envoi**, puis **12. `kernel.terminate`**, dispatché *après* que la réponse
a été envoyée au client. C'est le seul endroit où un traitement lent ne coûte
rien à l'utilisateur.

## La pile de requêtes, poussée et dépilée par `handle()`

`handle()` ne se contente pas d'orchestrer les événements. Il **empile** la
requête dans le `RequestStack` dès l'entrée, et la **dépile** dans un `finally`
— donc quoi qu'il arrive, exception comprise. C'est tout le mécanisme derrière
`getCurrentRequest()` : la requête courante est simplement celle du sommet, et
une sous-requête empilée par-dessus la principale rend la première accessible
par `getParentRequest()`.

Une exception : si la réponse est une `StreamedResponse`, sa fonction de rendu
est **réenveloppée** pour réempiler la requête le temps du flux, puis la
dépiler. Sans cela, `getCurrentRequest()` rendrait `null` à l'intérieur du
`callback`, puisqu'il s'exécute après la sortie de `handle()`.

## La branche que le trajet nominal ne montre pas

Le trajet ci-dessus décrit le cas où rien n'échoue. Dès qu'une exception est
levée, `handleRaw()` est interrompu et `$catch` décide :

- **`$catch` vaut `true`** — `kernel.exception` est dispatché. Si un écouteur
  pose une réponse, le cycle **reprend** au filtre de réponse : `kernel.response`
  puis `kernel.finish_request` ont lieu normalement. Si aucun ne le fait,
  `kernel.finish_request` est dispatché **quand même**, puis l'exception est
  relancée.
- **`$catch` vaut `false`** — `kernel.exception` n'a pas lieu du tout, mais
  `kernel.finish_request` est dispatché avant que l'exception ne remonte.

Autrement dit `kernel.finish_request` a lieu sur **les trois** chemins : succès,
exception rattrapée, exception relancée. C'est la seule garantie de ce type dans
tout le cycle, et c'est ce qui rend l'événement fiable pour restaurer un état
global. La manière d'écrire un écouteur d'exception appartient à la page
suivante.

## Trois façons d'échouer, trois exceptions

| Ce qui manque | Ce qui est levé |
|---|---|
| Le résolveur ne trouve aucun contrôleur | `NotFoundHttpException` — « the route is wrongly configured » |
| Le contrôleur ne rend pas une `Response`, et aucun écouteur de `kernel.view` n'en pose une | `ControllerDoesNotReturnResponseException` |
| La requête elle-même est malformée (`RequestExceptionInterface`) | convertie en `BadRequestHttpException` avant la branche d'exception |

La deuxième porte un message que tout le monde a déjà lu : « Did you forget to
add a return statement somewhere in your controller? » Elle n'est levée
**qu'après** `kernel.view` — l'événement a bien eu lieu, il n'a simplement rien
donné.

## Pièges d'examen

- `kernel.view` est **conditionnel**, `kernel.response` ne l'est pas.
- `kernel.terminate` est après l'envoi, pas avant.
- `kernel.finish_request` vient après `kernel.response`, pas avant.
- `kernel.request` précède la résolution du contrôleur : à ce moment,
  `_controller` peut être encore absent.
- Un court-circuit sur `kernel.request` saute le contrôleur, **pas**
  `kernel.response`, `kernel.finish_request` ni `kernel.terminate`.
- Un seul contrôleur frontal, `public/index.php` — quel que soit l'URL demandée.
- `kernel.finish_request` a lieu **même quand l'exception remonte**, et même
  quand `$catch` vaut `false` et que `kernel.exception` n'est pas dispatché.
- `kernel.controller_arguments` peut remplacer le contrôleur, pas seulement ses
  arguments.
- `MAIN_REQUEST` vaut **1** et `SUB_REQUEST` **2** — pas `0` et `1`.

## Tips d'examen

**Deux questions suffisent à placer un événement.** Le contrôleur est-il connu ?
(non → `kernel.request`). La réponse existe-t-elle ? (oui → `kernel.response`
puis `kernel.finish_request`, et `kernel.terminate` après l'envoi).

**`finally` est le mot-clé du cycle.** Dépilement de la requête et
`kernel.finish_request` ont lieu sur tous les chemins ; tout le reste est
conditionnel.

## Points clés

- `handle(Request, $type, $catch): Response` est le contrat unique.
- L'ordre : request → *(résolution)* → controller → controller_arguments →
  *(appel)* → view → response → finish_request → *(envoi)* → terminate.
- Tout le travail est fait par des écouteurs ; `handle()` ne fait qu'orchestrer.
- `handle()` empile la requête à l'entrée et la dépile dans un `finally` : c'est
  ce qui fait fonctionner `getCurrentRequest()` et `getParentRequest()`.
- Sur exception : `kernel.exception` si `$catch`, et `kernel.finish_request`
  dans tous les cas.

## Sources officielles

- [Composant HttpKernel, « The Request-Response Lifecycle »](https://github.com/symfony/symfony-docs/blob/8.0/components/http_kernel.rst)
- [Built-in Symfony Events, « Kernel Events »](https://github.com/symfony/symfony-docs/blob/8.0/reference/events.rst)
- [Composant Runtime, section « Usage »](https://github.com/symfony/symfony-docs/blob/8.0/components/runtime.rst)
- [`HttpKernel` (le code du cycle)](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/HttpKernel.php)
- [`HttpKernelInterface`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/HttpKernelInterface.php)
