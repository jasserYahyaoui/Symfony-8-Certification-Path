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
---

## Objectif

Savoir de quel côté un cookie se lit et de quel côté il s'écrit dans un
contrôleur. Les attributs de sécurité — `SameSite`, `Secure`, `HttpOnly` — et la
contrainte de suppression sont traités dans le lot HTTP.

## Deux objets, deux sens

C'est l'asymétrie à retenir, et elle suit le protocole :

| Opération | Objet | Chemin |
|---|---|---|
| **lire** un cookie | `Request` | `$request->cookies->get('nom')` |
| **écrire** un cookie | `Response` | `$response->headers->setCookie(...)` |
| **supprimer** un cookie chez le client | `Response` | `$response->headers->clearCookie('nom')` |
| **retirer** un cookie de la réponse | `Response` | `$response->headers->removeCookie('nom')` |

Un cookie posé sur la requête ne part nulle part : la requête est ce que le
client a envoyé. Un cookie s'écrit sur la réponse, parce qu'il voyage dans
l'en-tête `Set-Cookie`.

## Conséquence pratique

Un contrôleur qui veut poser un cookie doit donc **avoir la réponse en main** :
il la construit, y attache le cookie, puis la retourne.

```php
$response = $this->render('page.html.twig');
$response->headers->setCookie(Cookie::create('theme', 'dark'));

return $response;
```

Appeler la fonction PHP `setcookie()` fonctionne au niveau du protocole, mais
court-circuite l'objet `Response` : l'en-tête est écrit hors du modèle de
Symfony, échappe aux écouteurs de `kernel.response`, et n'apparaît pas dans les
tests fonctionnels qui inspectent la réponse. C'est la raison de passer par
`ResponseHeaderBag`.


## `clearCookie()` et `removeCookie()` font le contraire l'une de l'autre

Deux noms voisins, deux effets opposés — et les commentaires du code le disent
mot pour mot.

| Méthode | Ce qu'elle fait |
|---|---|
| `removeCookie()` | retire le cookie **du sac**, « mais ne le supprime pas dans le navigateur » |
| `clearCookie()` | « supprime un cookie dans le navigateur » |

Le mécanisme explique tout. `removeCookie()` fait un `unset` dans le tableau
interne : le cookie **n'est jamais envoyé**, il n'y a donc aucun `Set-Cookie`
pour lui. `clearCookie()`, elle, **appelle `setCookie()`** avec un cookie de
valeur nulle et de date d'expiration dans le passé : c'est bien un en-tête
envoyé, dont le seul rôle est de faire oublier le précédent.

Autrement dit : pour annuler une écriture qu'on vient de faire dans le même
contrôleur, `removeCookie()`. Pour effacer un cookie que le client possède déjà,
`clearCookie()`.

## Un cookie est indexé par domaine, chemin et nom

`setCookie()` ne range pas les cookies par nom seul, mais sur **trois niveaux** :
domaine, puis chemin, puis nom. Deux cookies portant le même nom sur des chemins
différents coexistent donc sans s'écraser.

C'est pourquoi `removeCookie()` prend un chemin et un domaine en plus du nom :
sans eux, elle viserait la mauvaise entrée. Son chemin par défaut est `/`.

`getCookies()` rend la liste, à plat par défaut ; le format `array` conserve
l'arborescence. Un format inconnu lève une `InvalidArgumentException`.

## Pièges d'examen

**On lit un cookie sur la requête et on l'écrit sur la réponse.** Poser un
cookie sur l'objet requête ne l'envoie nulle part : la requête est ce que le
client a déjà envoyé.

**`removeCookie()` ne supprime rien chez le client.** Elle retire l'entrée du
sac, donc le cookie n'est pas envoyé du tout. Pour faire oublier un cookie déjà
posé, il faut en **envoyer** un — c'est `clearCookie()`.

**La fonction native de PHP fonctionne, et c'est le problème.** Elle écrit
l'en-tête hors du modèle de Symfony : le cookie échappe aux écouteurs de
`kernel.response` et n'apparaît pas dans un test fonctionnel qui inspecte la
réponse.

**Écrire un cookie oblige donc à tenir la réponse.** Un contrôleur qui délègue
entièrement le rendu n'a pas d'endroit où l'attacher.

## Tips d'examen

**Pour choisir entre les deux suppressions** : le cookie existe-t-il déjà chez
le client ? Oui → `clearCookie()`. Non, je viens de le poser → `removeCookie()`.

## Points clés

- Lire sur la requête, écrire et supprimer sur la réponse.
- `$response->headers` est un `ResponseHeaderBag` : `setCookie()`,
  `removeCookie()`, `clearCookie()`, `getCookies()`.
- `removeCookie()` annule l'envoi ; `clearCookie()` envoie une expiration passée.
- Les cookies sont indexés par domaine, chemin puis nom — d'où les arguments de
  `removeCookie()`, dont le chemin vaut `/` par défaut.
- `setcookie()` de PHP contourne l'objet `Response` ; ne pas l'utiliser.

## Sources officielles

- [ResponseHeaderBag, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/ResponseHeaderBag.php)
