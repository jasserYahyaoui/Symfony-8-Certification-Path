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
    anchor: "the-workflow-of-a-request"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/events.rst"
    anchor: "kernel-events"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/runtime.rst"
    anchor: "usage"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
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
`MAIN_REQUEST` ou `SUB_REQUEST` — le noyau traite les deux par le même code, et
c'est aux écouteurs de faire la différence quand elle compte. `$catch` décide si
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
l'appel. Le détail des résolveurs de valeur appartient au lot Controllers.

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

## Pièges d'examen

- `kernel.view` est **conditionnel**, `kernel.response` ne l'est pas.
- `kernel.terminate` est après l'envoi, pas avant.
- `kernel.finish_request` vient après `kernel.response`, pas avant.
- `kernel.request` précède la résolution du contrôleur : à ce moment,
  `_controller` peut être encore absent.
- Un court-circuit sur `kernel.request` saute le contrôleur, **pas**
  `kernel.response`, `kernel.finish_request` ni `kernel.terminate`.
- Un seul contrôleur frontal, `public/index.php` — quel que soit l'URL demandée.

## Points clés

- `handle(Request, $type, $catch): Response` est le contrat unique.
- L'ordre : request → *(résolution)* → controller → controller_arguments →
  *(appel)* → view → response → finish_request → *(envoi)* → terminate.
- Tout le travail est fait par des écouteurs ; `handle()` ne fait qu'orchestrer.

## Sources officielles

- [Composant HttpKernel, « The Request-Response Lifecycle »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/http_kernel.rst)
- [Built-in Symfony Events, « Kernel Events »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/events.rst)
- [Composant Runtime, section « Usage »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/runtime.rst)
