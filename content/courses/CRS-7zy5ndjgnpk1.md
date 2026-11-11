---
id: CRS-7zy5ndjgnpk1
official_item: OIT-bwfqarnn6s2f
title: "HTTP redirects"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-26"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/controller.rst"
    anchor: "controller-redirect"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php"
    symbol_or_lines: "redirect, redirectToRoute, generateUrl"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/RedirectResponse.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/RedirectResponse.php"
    symbol_or_lines: "__construct, setTargetUrl"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Response.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Response.php"
    symbol_or_lines: "isRedirect"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
outcome_sections:
  OUT-gzgd3nv5dqsb: "Les deux methods"
  OUT-v6qwe7g2fz54: "Le statut"
  OUT-2n6hy6wr111b: "Le danger de `redirect()`"
---

## Objectif

Rediriger le navigateur depuis un controller, et connaître le danger attaché à
l'une des deux methods.

## L'essentiel

Un controller redirige par `redirectToRoute()` (vers une route) ou
`redirect()` (vers une URL). Les deux rendent une `RedirectResponse` : le
navigateur repart, l'URL affichée change. Le statut est **302** par défaut,
seuls six codes sont admis, et `redirect()` ne vérifie jamais sa destination.

## Le schéma

```mermaid
---
title: De redirectToRoute à la RedirectResponse
---
flowchart TD
  accTitle: redirectToRoute, redirect et les statuts admis
  accDescr: redirectToRoute($route, $params = [], $status = 302) appelle redirect(generateUrl($route, $params), $status), et Location reçoit un path absolu, comme /accueil. Les parameters qui sont des variables du path vont dans le path, les autres dans la query string, et _fragment devient l'ancre. redirect($url, $status = 302) crée new RedirectResponse($url, $status), sans aucune validation de $url. Les statuts admis sont 201, 301, 302, 303, 307 et 308 ; le reste lève, 304 et 300 compris. Pour un 301, Cache-Control est retiré s'il n'est pas fourni, d'où un cache navigateur durable.
  RR["redirectToRoute($route,<br/>$params = [], $status = 302)"] -->|"redirect(generateUrl($route,<br/>$params), $status)<br/>Location : path absolu (/accueil)"| R["redirect($url, $status = 302)<br/>→ new RedirectResponse($url, $status)<br/>aucune validation de $url"]
  RR -.- PA["parameters<br/>variable du path → path<br/>autre → query string<br/>_fragment → #ancre"]
  R --> ST["statuts admis<br/>201, 301, 302, 303, 307, 308<br/>le reste lève<br/>(304 et 300 compris)"]
  ST --> P301["301 : Cache-Control retiré<br/>s'il n'est pas fourni<br/>cache navigateur durable"]
```

## L'exemple : Post / Redirect / Get

```php
public function save(Request $request): Response
{
    // … enregistrement
    return $this->redirectToRoute('blog_show', ['slug' => $post->getSlug(), '_fragment' => 'comments']);
}
```

## Les deux methods

`redirectToRoute()` est bâtie sur `redirect()` : elle génère l'URL avec
`generateUrl()`, sans type de référence, donc en **path absolu** — `Location`
porte `/accueil`, pas `https://exemple.com/accueil`. Pas de quatrième argument
pour changer cela : une URL complète se construit soi-même, puis passe à
`redirect()`.

## Le statut

Le troisième argument porte le code, **302 par défaut** :
`redirectToRoute('homepage', [], 301)`. Un 301 est mis en cache par le
navigateur, parfois durablement : sans `Cache-Control` fourni, le constructor
retire ce header. Le constructor vérifie le statut par `isRedirect()` : **201,
301, 302, 303, 307 et 308** seulement ; tout autre code — 304, 200 — lève une
`InvalidArgumentException`, comme une URL vide.

## Parameters utiles

- un parameter qui n'est pas une variable du path part en query string :
  `redirectToRoute('blog_show', $request->query->all())` conserve la query
  string d'origine ;
- la key spéciale `_fragment` ajoute une ancre ;
- `$request->attributes->get('_route')` redirige vers la route courante.

## Le danger de `redirect()`

`redirect()` ne vérifie pas sa destination : une valeur venue du user y ouvre
un redirect non validé, vers le domain d'un attaquant. Toute URL redirigée est
validée, ou vient d'une route.

## Pièges d'examen

**Le statut par défaut est 302, pas 301.** Le permanent se demande
explicitement, par le troisième argument — et un 301 mis en cache par le
navigateur est difficile à reprendre.

**304 n'est pas un statut de redirect valide.** Il est dans la plage 3xx,
mais la Response de redirect le refuse et lève.

**`redirectToRoute()` produit un path, pas une URL complète.** Le type de
référence n'est pas exposé.

**Rediriger vers une URL ne valide pas cette URL.** Une destination construite
depuis une entrée user ouvre un redirect non validé.

**Un redirect est une vraie Response HTTP.** Le navigateur repart, l'URL
affichée change, et le controller d'arrivée s'exécute dans une seconde Request —
ce n'est pas un appel interne.

## Application

- Un déplacement définitif de page → `redirectToRoute('…', [], 301)`.
- Un redirect après login vers « la page demandée » → valider la cible,
  jamais un `next` brut.

## QCM d'entraînement

**Question 1.** Que contient le header `Location` de
`$this->redirectToRoute('homepage')` si la route a le path `/accueil` ?

- **A.** `/accueil`
- **B.** `https://exemple.com/accueil`
- **C.** `homepage`, le nom de la route
- **D.** `//exemple.com/accueil`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** L'URL est générée avec le type de référence par défaut,
le path absolu ; rien dans la signature ne permet d'obtenir l'URL complète.

</details>

**Question 2.** La route `blog_show` a le path `/blog/{slug}`. Que produit
`redirectToRoute('blog_show', ['slug' => 'x', 'page' => 3, '_fragment' => 'c'])` ?

- **A.** `/blog/x/3#c`
- **B.** Une exception : `page` n'est pas déclaré
- **C.** `/blog/x?page=3&_fragment=c`
- **D.** `/blog/x?page=3#c`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Un parameter hors du path part en query string, et la
key spéciale `_fragment` devient l'ancre de l'URL.

</details>

**Question 3.** Que fait `$this->redirect('/a', 300)` ?

- **A.** Un redirect à choix multiples
- **B.** Une exception
- **C.** Un 302, le code étant corrigé
- **D.** Un 300, sans header `Location`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Une `InvalidArgumentException` : le constructor n'admet
que 201, 301, 302, 303, 307 et 308, et 300, pourtant 3xx, est refusé.

</details>

**Question 4.** Une `RedirectResponse` en 301 est créée sans `Cache-Control`.
Que devient ce header ?

- **A.** `no-cache, private`, comme toute Response
- **B.** `public, max-age=31536000`
- **C.** Il est retiré
- **D.** `no-store`, par security

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Sur un 301 sans `Cache-Control` fourni, le constructor
le retire : le navigateur peut alors garder le redirect longtemps.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- `redirectToRoute()` = `redirect(generateUrl())` : un **path absolu**.
- Statut **302** par défaut ; 301 par le **troisième argument**.
- Admis : **201, 301, 302, 303, 307, 308** ; le reste lève.
- Hors path → **query string** ; `_fragment` → **ancre**.
- 301 sans `Cache-Control` : header **retiré**, cache durable.
- `redirect()` **ne valide rien** : jamais d'entrée user brute.

Moyen mnémotechnique : **« 302 par défaut, 301 pour toujours »**.

## Sources officielles

- [Controller, section « Redirecting »](https://github.com/symfony/symfony-docs/blob/8.0/controller.rst)
- [`AbstractController`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php)
- [`RedirectResponse`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/RedirectResponse.php)
- [`Response::isRedirect()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Response.php)

