---
id: CRS-yw6c5hq2mbax
official_item: OIT-c34hn4px3czj
title: "Controller rendering"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-11-03"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/templates.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/templates.rst"
    anchor: "embedding-controllers"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/Extension/HttpKernelExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/HttpKernelExtension.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "render, render_*, fragment_uri, controller"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/Extension/HttpKernelRuntime.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/HttpKernelRuntime.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "HttpKernelRuntime::renderFragment()"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/Fragment/FragmentHandler.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Fragment/FragmentHandler.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "FragmentHandler::render(), deliver()"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/Fragment/InlineFragmentRenderer.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Fragment/InlineFragmentRenderer.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "InlineFragmentRenderer::render(), createSubRequest()"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "addFragmentsSection()"
    verified_at: "2026-11-03"
outcome_sections:
  OUT-hxf3hmdmh40t: "Avec ou sans route"
  OUT-yzay5m44wwmq: "Avec ou sans route"
  OUT-wnm1nq0vg8m5: "Ce que cela déclenche"
  OUT-686mezsja6g7: "L'URL des fragments"
---

## Objectif

Exécuter un controller depuis un template, et savoir ce que cela coûte et ce qui
peut échouer. Le `forward()` d'un controller vers un autre appartient au lot
Controllers.

## L'essentiel

`render()` exécute un controller depuis un template et insère sa Response —
utile pour un bloc qui a ses propres données sur toutes les pages, comme une
barre latérale. Chaque appel est une **sub-Request** complète : ce n'est pas
gratuit, et une erreur ne se comporte pas pareil en `dev` et en `prod`. Pour une
simple unité d'interface, la documentation 8.0 recommande plutôt les Twig
Components.

## Le schéma

```mermaid
---
title: Ce que fait render() d'un controller dans un template
---
flowchart TD
  accTitle: Le déroulement d'un render de fragment dans Symfony 8.0
  accDescr: render passe au FragmentHandler, qui fixe ignore_errors à la négation de kernel.debug si l'appel ne le précise pas, puis choisit la strategy, inline par défaut ; une strategy esi, ssi ou hinclude non activée lève une erreur. La sub-Request GET reprend cookies, session et variables serveur, sans If-Modified-Since ni If-None-Match, et passe dans le kernel en SUB_REQUEST. Si le controller lève une exception, l'event kernel.exception n'est dispatché que si ignore_errors est vrai ; puis alt est rendu s'il existe ; sinon l'exception remonte, ou le fragment est vide avec ignore_errors. Une Response non 2xx, redirect compris, lève toujours une RuntimeException. Sinon le contenu est inséré.
  R["render(…)"] --> H{"ignore_errors précisé ?"}
  H -->|"non"| D["ignore_errors = !kernel.debug"]
  H -->|"oui"| S
  D --> S{"strategy"}
  S -->|"inline, par défaut"| SUB["sub-Request GET : cookies, session ; sans If-None-Match"]
  S -->|"esi, ssi, hinclude non activés"| NOR(["erreur : renderer does not exist"])
  SUB --> K["kernel, SUB_REQUEST"]
  K --> EXC{"le controller lève ?"}
  EXC -->|"oui"| LOG["kernel.exception, seulement si ignore_errors"]
  LOG --> ALT{"alt ?"}
  ALT -->|"oui"| ALTR(["le repli est rendu"])
  ALT -->|"non"| IGN{"ignore_errors ?"}
  IGN -->|"oui"| EMPTY(["fragment vide"])
  IGN -->|"non"| THROW(["l'exception remonte"])
  EXC -->|"non"| OK2{"Response 2xx ?"}
  OK2 -->|"non, redirect compris"| RTE(["RuntimeException, toujours"])
  OK2 -->|"oui"| HTML(["contenu inséré"])
```
## Avec ou sans route

```html
{{ render(path('latest_articles', {max: 3})) }}
{{ render(controller('App\\Controller\\BlogController::recentArticles', {max: 3})) }}
```

- Un controller **routé** : `render(path(…))` ou `render(url(…))`.
- Un controller **sans route** : `render(controller(…))`.
  `controller(nom, attributes, query)` construit une `ControllerReference` et
  **n'exécute rien** ; c'est `render()` qui l'exécute. Affichée seule,
  `{{ controller(…) }}` fait échouer le rendu : la référence ne se convertit pas
  en string.
- Les attributes de `controller()` deviennent des attributes de la
  sub-Request, objects compris en `inline` ; le troisième argument devient la
  query string.

## Ce que cela déclenche

Chaque appel est une **sub-Request `GET`** traitée par le kernel
(`HttpKernelInterface::SUB_REQUEST`). Elle reprend les cookies, les variables
serveur et la session de la Request courante, sans `If-Modified-Since` ni
`If-None-Match`, et transmet `_format` et la locale. Intégrer beaucoup de
controllers pèse sur les performances.

## Les strategies

- `inline` par défaut ; l'option `strategy`, ou `render_esi()`, `render_ssi()`,
  `render_hinclude()`, en choisissent une autre.
- `render_esi()` sans `framework.esi` échoue :
  « The "esi" renderer does not exist. » ; avec ESI activé mais sans reverse proxy, le fragment est rendu en
  `inline`.
- `fragment_uri()` rend l'URI signée d'un fragment sans l'exécuter.

## L'URL des fragments

Avec `controller()`, l'URL interne est `/_fragment` par défaut
(`framework.fragments.path`). `framework.fragments` est **désactivé par
défaut** : l'activer sert à exposer `/_fragment` aux Requests venues de
l'extérieur — ESI, hinclude —, avec une signature. En `inline`, la sub-Request
porte déjà son `_controller` : aucune activation n'est nécessaire.

## Quand le fragment échoue

- **Exception** dans le controller : `FragmentHandler` fixe `ignore_errors` à
  `!kernel.debug`. En `dev`, l'exception remonte ; en `prod`, elle passe par
  `kernel.exception` — pour le log — et le fragment est vide.
- `alt` désigne un fragment de repli, essayé **avant** de relancer : en `dev`, il
  masque l'erreur sans la loguer.
- **Response non 2xx**, redirect compris : `RuntimeException`, **quel que soit**
  `ignore_errors`. Un fragment ne redirige pas.

## Pièges d'examen

**Rendre un controller depuis un template déclenche une sub-Request**, pas un
appel de function.

**`controller()` seul n'exécute rien** : il construit une référence, et
l'afficher sans `render()` fait échouer le rendu.

**Une erreur de fragment ne se comporte pas pareil en `dev` et en `prod`.**

**Un redirect dans un fragment est une erreur.**

**`framework.fragments` n'est pas actif par défaut** ; il sert à exposer
`/_fragment` à l'extérieur, pas à faire fonctionner `inline`.

## Application

- La barre latérale est vide en production depuis une semaine → `ignore_errors`
  vaut `true` en `prod` : l'exception est dans les logs ; ajouter un `alt`.
- Un fragment redirige vers le login et la page entière tombe → afficher un
  message à la place ; le contrôle d'accès appartient à la page.

## QCM d'entraînement

**Question 1.** Un template écrit `{{ controller('App\\Controller\\Box::recent') }}`,
sans `render()`. Que se passe-t-il ?

- **A.** Rien ne s'affiche : la référence n'est pas exécutée
- **B.** Le rendu échoue : pas de conversion en string
- **C.** Le controller est exécuté et sa Response affichée
- **D.** L'URL `/_fragment` du controller s'affiche

<details>
<summary>Correction</summary>

**Bonne réponse : B.** `ControllerReference` n'a pas de `__toString()`. A est
l'idée reçue : rien n'est exécuté, mais l'affichage échoue.

</details>

**Question 2.** En `dev`, le controller d'un fragment lève une exception.
L'appel est `render(controller('…'), {alt: controller('…::fallback')})`. Que se
passe-t-il ?

- **A.** L'exception remonte, comme toujours en `dev`
- **B.** Le fragment est vide, et l'exception est loguée
- **C.** Le repli est rendu, sans dispatch de `kernel.exception`
- **D.** Le repli est rendu, et l'exception passe aussi par `kernel.exception`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** `kernel.exception` n'est dispatché que si `ignore_errors`
est vrai — `false` en `dev` — puis `alt` est essayé avant de relancer. D décrit
`prod`.

</details>

**Question 3.** `framework.esi` n'est pas activé. Que fait
`{{ render_esi(controller('…::recent')) }}` ?

- **A.** Il rend le fragment en `inline`, faute d'ESI
- **B.** Il écrit un tag `<esi:include>` dans la page
- **C.** Il échoue : le renderer `esi` n'existe pas
- **D.** Il ne rend rien

<details>
<summary>Correction</summary>

**Bonne réponse : C.** A est ce qui arrive **avec** ESI activé, sans reverse
proxy capable d'ESI.

</details>

**Question 4.** Le controller d'un fragment retourne un redirect ; l'appel porte
`ignore_errors: true`. Que se passe-t-il ?

- **A.** Le fragment est vide, sans erreur
- **B.** La page entière est redirigée
- **C.** Le fragment affiche le contenu de la page cible du redirect
- **D.** Une erreur « Status code is 302 » qui nomme la page

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Toute Response non 2xx est refusée, quel que soit
`ignore_errors` ; le message nomme l'URL de la page.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- **`render()`** exécute ; **`controller()`** désigne un controller sans route.
- **`{{ controller(…) }}`** seul : le rendu **échoue**.
- Chaque fragment = une **sub-Request `GET`** ; cookies et session repris.
- **`ignore_errors`** = `!kernel.debug` : `dev` relance, `prod` avale et logue.
- **`alt`** est essayé avant de relancer.
- **Non 2xx** → `RuntimeException`, toujours.
- **`render_esi()`** exige `framework.esi` ; **`/_fragment`** n'exige
  `framework.fragments` que pour l'extérieur.

Moyen mnémotechnique : **« render exécute, controller désigne, dev relance,
prod avale, redirect casse »**.

```text
render(path|url|controller) = sub-Request GET ; controller() seul échoue ; ignore_errors = !debug ;
alt avant de relancer ; non 2xx = RuntimeException ; esi à activer ; /_fragment pour l'extérieur
```

## Sources officielles

- [Symfony Templates, « Embedding Controllers »](https://github.com/symfony/symfony-docs/blob/8.0/templates.rst)
- [Twig Bridge 8.0, `HttpKernelExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/HttpKernelExtension.php) et [`HttpKernelRuntime`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/Extension/HttpKernelRuntime.php)
- [HttpKernel 8.0, `FragmentHandler`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Fragment/FragmentHandler.php) et [`InlineFragmentRenderer`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Fragment/InlineFragmentRenderer.php)
- [FrameworkBundle 8.0, `Configuration`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php)

