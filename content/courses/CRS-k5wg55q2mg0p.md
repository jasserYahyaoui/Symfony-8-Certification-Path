---
id: CRS-k5wg55q2mg0p
official_item: OIT-bc3brs4a4mtt
title: "Client object"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/KernelBrowser.php"
    branch: "8.0"
    symbol_or_lines: "KernelBrowser"
    verified_at: "2026-09-02"
---

## Objectif

Piloter l'application comme un navigateur : requêtes, navigation, redirections,
authentification.

## Prérequis

Les tests fonctionnels avec `WebTestCase`.

## Ce qu'est le client

`static::createClient()` rend un `KernelBrowser`. Il **simule** un navigateur :
aucune socket n'est ouverte, aucun serveur web n'intervient. La requête est
construite en mémoire et passée au noyau. C'est pourquoi ces tests sont rapides
— et pourquoi ils n'exécutent aucun JavaScript.

Le client est disponible dans le conteneur sous le service **`test.client`**, en
environnement `test` ou partout où l'option `framework.test` est activée ; on
peut donc le remplacer entièrement.

## Faire une requête

```php
$crawler = $client->request('GET', '/post/hello-world');
```

La signature complète :

```php
request(
    string $method,
    string $uri,
    array $parameters = [],
    array $files = [],
    array $server = [],
    ?string $content = null,
    bool $changeHistory = true,
): Crawler
```

`request()` **retourne un `Crawler`**, pas une réponse. La réponse se lit par
`$client->getResponse()`.

Pour une requête AJAX, `xmlHttpRequest()` a les mêmes arguments et ajoute
l'en-tête `HTTP_X_REQUESTED_WITH` automatiquement.

## Naviguer

```php
$client->back();
$client->forward();
$client->reload();
$client->restart();   // vide les cookies et l'historique
```

`back()` et `forward()` **sautent les redirections** rencontrées, comme le fait
un vrai navigateur.

## Les redirections

Le client **ne suit pas** les redirections par défaut. Deux méthodes, à ne pas
confondre :

| Méthode | Effet |
|---|---|
| `followRedirect()` | suit **la** redirection en attente, une fois |
| `followRedirects()` | change le **mode** : toutes les suivantes seront suivies |
| `followRedirects(false)` | revient au comportement par défaut |

La différence est le singulier : l'une agit sur la réponse courante, l'autre
règle le client. `followRedirects()` doit être appelée **avant** la requête.

## Plusieurs requêtes dans un test

Après une requête, la suivante **redémarre le noyau** et reconstruit le
conteneur, pour isoler les requêtes. Conséquence pratique : le jeton de sécurité
est effacé entre deux requêtes.

`disableReboot()` réinitialise le noyau au lieu de le redémarrer — Symfony
appelle alors `reset()` sur les services marqués `kernel.reset`. Cela efface
également le jeton de sécurité ; pour le conserver, il faut retirer l'étiquette
`kernel.reset` du service concerné par une passe de compilation en environnement
de test.

## S'authentifier

```php
$client->loginUser($user);
```

Rejouer un formulaire de connexion à chaque test le rendrait lent.
`loginUser()` place directement l'utilisateur dans le pare-feu — `main` par
défaut, modifiable par le deuxième argument.

## Voir l'exception

```php
$client->catchExceptions(false);
```

Par défaut les exceptions sont interceptées et rendues en page d'erreur ; le
test échoue alors sur un code 500 sans dire pourquoi. Cette méthode les laisse
remonter jusqu'à PHPUnit.

## Pièges d'examen

**`request()` retourne un `Crawler`**, pas une `Response`.

**Le client ne suit pas les redirections par défaut.**

**`followRedirect()` ≠ `followRedirects()`** : une fois, contre un réglage.

**Le noyau redémarre entre deux requêtes** et le jeton de sécurité est perdu.

**Aucun JavaScript n'est exécuté** : c'est un client simulé, pas un navigateur.

## Points clés

- `createClient()` rend un `KernelBrowser`, disponible comme service `test.client`.
- `request()` rend un `Crawler` ; `getResponse()` rend la réponse.
- `back()`, `forward()`, `reload()`, `restart()` pour naviguer.
- `followRedirect()` une fois, `followRedirects()` en mode.
- `loginUser()` pour s'authentifier, `catchExceptions(false)` pour déboguer.

## Sources officielles

- [Testing](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/testing.rst)
- [`KernelBrowser`, branche 8.0](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/KernelBrowser.php)
