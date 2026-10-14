---
id: CRS-thmkagkjcvh3
official_item: OIT-pfrzrr0qcmh3
title: "The cookies"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/ResponseHeaderBag.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/ResponseHeaderBag.php"
    symbol_or_lines: "setCookie, removeCookie, clearCookie"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
outcome_sections:
  OUT-kejhjexj129b: "Deux objects, deux sens"
  OUT-yfn908g4pdys: "Conséquence pratique"
---

## Objectif

Savoir de quel côté un cookie se lit et de quel côté il s'écrit dans un
controller. Les attributes de sécurité — `SameSite`, `Secure`, `HttpOnly` — et la
constraint de suppression sont traités dans le lot HTTP.

## Deux objects, deux sens

C'est l'asymétrie à retenir, et elle suit le protocole :

| Opération | Object | Path |
|---|---|---|
| **lire** un cookie | `Request` | `$request->cookies->get('nom')` |
| **écrire** un cookie | `Response` | `$response->headers->setCookie(...)` |
| **supprimer** un cookie chez le client | `Response` | `$response->headers->clearCookie('nom')` |
| **retirer** un cookie de la Response | `Response` | `$response->headers->removeCookie('nom')` |

Un cookie posé sur la Request ne part nulle part : la Request est ce que le
client a envoyé. Un cookie s'écrit sur la Response, parce qu'il voyage dans
le header `Set-Cookie`.

## Conséquence pratique

Un controller qui veut poser un cookie doit donc **avoir la Response en main** :
il la construit, y attache le cookie, puis la retourne.

```php
$response = $this->render('page.html.twig');
$response->headers->setCookie(Cookie::create('theme', 'dark'));

return $response;
```

Appeler la function PHP `setcookie()` fonctionne au niveau du protocole, mais
court-circuite l'object `Response` : le header est écrit hors du modèle de
Symfony, échappe aux listeners de `kernel.response`, et n'apparaît pas dans les
functional tests qui inspectent la Response. C'est la raison de passer par
`ResponseHeaderBag`.


## `clearCookie()` et `removeCookie()` font le contraire l'une de l'autre

Deux noms voisins, deux effets opposés — et les commentaires du code le disent
mot pour mot.

| Method | Ce qu'elle fait |
|---|---|
| `removeCookie()` | retire le cookie **du sac**, « mais ne le supprime pas dans le navigateur » |
| `clearCookie()` | « supprime un cookie dans le navigateur » |

Le mécanisme explique tout. `removeCookie()` fait un `unset` dans le tableau
interne : le cookie **n'est jamais envoyé**, il n'y a donc aucun `Set-Cookie`
pour lui. `clearCookie()`, elle, **appelle `setCookie()`** avec un cookie de
valeur nulle et de date d'expiration dans le passé : c'est bien un header
envoyé, dont le seul role est de faire oublier le précédent.

Autrement dit : pour annuler une écriture qu'on vient de faire dans le même
controller, `removeCookie()`. Pour effacer un cookie que le client possède déjà,
`clearCookie()`.

## Un cookie est indexé par domaine, path et nom

`setCookie()` ne range pas les cookies par nom seul, mais sur **trois niveaux** :
domaine, puis path, puis nom. Deux cookies portant le même nom sur des paths
différents coexistent donc sans s'écraser.

C'est pourquoi `removeCookie()` prend un path et un domaine en plus du nom :
sans eux, elle viserait la mauvaise entrée. Son path par défaut est `/`.

`getCookies()` rend la liste, à plat par défaut ; le format `array` conserve
l'arborescence. Un format inconnu lève une `InvalidArgumentException`.

## Pièges d'examen

**On lit un cookie sur la Request et on l'écrit sur la Response.** Poser un
cookie sur l'object Request ne l'envoie nulle part : la Request est ce que le
client a déjà envoyé.

**`removeCookie()` ne supprime rien chez le client.** Elle retire l'entrée du
sac, donc le cookie n'est pas envoyé du tout. Pour faire oublier un cookie déjà
posé, il faut en **envoyer** un — c'est `clearCookie()`.

**La fonction native de PHP fonctionne, et c'est le problème.** Elle écrit
le header hors du modèle de Symfony : le cookie échappe aux listeners de
`kernel.response` et n'apparaît pas dans un functional test qui inspecte la
Response.

**Écrire un cookie oblige donc à tenir la Response.** Un controller qui délègue
entièrement le rendu n'a pas d'endroit où l'attacher.

## Tips d'examen

**Pour choisir entre les deux suppressions** : le cookie existe-t-il déjà chez
le client ? Oui → `clearCookie()`. Non, je viens de le poser → `removeCookie()`.

## Points clés

- Lire sur la Request, écrire et supprimer sur la Response.
- `$response->headers` est un `ResponseHeaderBag` : `setCookie()`,
  `removeCookie()`, `clearCookie()`, `getCookies()`.
- `removeCookie()` annule l'envoi ; `clearCookie()` envoie une expiration passée.
- Les cookies sont indexés par domaine, path puis nom — d'où les arguments de
  `removeCookie()`, dont le path vaut `/` par défaut.
- `setcookie()` de PHP contourne l'object `Response` ; ne pas l'utiliser.

## Sources officielles

- [ResponseHeaderBag, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/ResponseHeaderBag.php)
