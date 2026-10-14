---
id: CRS-7zy5ndjgnpk1
official_item: OIT-bwfqarnn6s2f
title: "HTTP redirects"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
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
---

## Objectif

Rediriger le navigateur depuis un controller, et connaître le danger attaché à
l'une des deux methods.

## Les deux methods

```php
return $this->redirectToRoute('homepage');        // vers une route
return $this->redirect('https://symfony.com/doc'); // vers une URL
```

Les deux retournent une `RedirectResponse`, donc une vraie Response HTTP : le
navigateur fait un second aller-retour et **l'URL affichée change**.

### L'une est bâtie sur l'autre

```php
protected function redirect(string $url, int $status = 302): RedirectResponse
{
    return new RedirectResponse($url, $status);
}

protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
{
    return $this->redirect($this->generateUrl($route, $parameters), $status);
}
```

`generateUrl()` est appelée **sans** type de référence, donc avec sa valeur par
défaut, le path absolu : le header `Location` porte `/accueil`, pas
`https://exemple.com/accueil`. `redirectToRoute()` n'a pas de quatrième
argument pour changer cela ; une URL complète se demande en construisant soi-même
l'URL puis en appelant `redirect()`.

## Le statut

Le troisième argument porte le code, **302 par défaut** :

```php
return $this->redirectToRoute('homepage', [], 301);
return $this->redirectToRoute('homepage', [], Response::HTTP_MOVED_PERMANENTLY);
```

Un 301 est mis en cache par le navigateur, parfois durablement. C'est la raison
de ne l'employer que pour un déplacement réellement permanent. Le constructeur y
contribue : sur un 301, si l'appelant n'a fourni aucun `Cache-Control`, il
**retire** ce header.

### Tous les 3xx ne sont pas acceptés

Le constructeur vérifie le statut par `isRedirect()`, qui n'admet que
**201, 301, 302, 303, 307 et 308**. Tout autre code lève une
`InvalidArgumentException` — y compris **304**, pourtant dans la plage 3xx, et
200. À l'inverse, 201 passe. Une URL vide lève aussi.

## Parameters utiles

- `$this->redirectToRoute('app_lucky_number', ['max' => 10])` — parameters de route ;
- un parameter qui n'est **pas** une variable du path part en chaîne de
  Request : `redirectToRoute('blog_show', $request->query->all())` conserve la
  query string d'origine ;
- la key spéciale `_fragment` ajoute une ancre à l'URL générée ;
- `$this->redirectToRoute($request->attributes->get('_route'))` redirige vers la
  route courante, ce qui sert le motif *Post / Redirect / Get*.

## Le danger de `redirect()`

`redirect()` **ne vérifie pas sa destination**. Passer une valeur venue de
le user y ouvre un redirect non validé : un attaquant fabrique un
lien vers votre domaine qui renvoie vers le sien. Toute URL redirigée doit être
validée, ou provenir d'une route.

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

## Points clés

- `redirectToRoute()` appelle `redirect()` avec un path absolu.
- Statut **302** par défaut ; troisième argument pour 301.
- Statuts admis : 201, 301, 302, 303, 307, 308 — le reste lève.
- Parameter hors path → query string ; `_fragment` → ancre.
- `redirect()` ne valide rien : jamais d'entrée user telle quelle.

## Sources officielles

- [Controller, section « Redirecting »](https://github.com/symfony/symfony-docs/blob/8.0/controller.rst)
- [`AbstractController`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php)
- [`RedirectResponse`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/RedirectResponse.php)
- [`Response::isRedirect()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Response.php)
