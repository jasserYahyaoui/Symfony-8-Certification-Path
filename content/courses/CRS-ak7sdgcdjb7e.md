---
id: CRS-ak7sdgcdjb7e
official_item: OIT-65bev6t7wbna
title: "The flash messages"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/session.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/session.rst"
    anchor: "flash-messages"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Session/Flash/FlashBag.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Session/Flash/FlashBag.php"
    symbol_or_lines: "add, set, get, all, peek, peekAll, has, clear"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-23"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php"
    symbol_or_lines: "addFlash"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-23"
---

## Objectif

Poser un message flash, savoir où il est stocké et quand il disparaît.

## Ce que c'est

Un message flash est un message de **session** conçu pour être utilisé
exactement une fois : il disparaît automatiquement dès qu'il est récupéré. C'est
le mécanisme du motif *Post / Redirect / Get* — on traite, on pose un message,
on redirige, et la page suivante l'affiche puis l'oublie.

Comme c'est de la session, poser un flash **démarre la session**.

## Poser

```php
$this->addFlash('notice', 'Vos modifications ont été enregistrées.');
```

`addFlash()` est un raccourci strictement équivalent à
`$request->getSession()->getFlashBag()->add()`. Le premier argument est un
*type* libre — `notice`, `warning`, `error` sont des usages courants, pas des
valeurs imposées.

Le raccourci **lève** plutôt que d'échouer en silence : une `LogicException`
nommant la configuration à changer si les sessions sont désactivées, une autre
si la session en place n'implémente pas `FlashBagAwareSessionInterface`.

### `add()` empile, `set()` écrase

Le sac expose les deux, et elles font le contraire l'une de l'autre :

```php
public function add(string $type, mixed $message): void
{
    $this->flashes[$type][] = $message;
}

public function set(string $type, string|array $messages): void
{
    $this->flashes[$type] = (array) $messages;
}
```

`add()` ajoute au tableau du type ; `set()` **remplace tout le type**. Un `set()`
posé après deux `add()` ne laisse que le dernier message. Le raccourci du
contrôleur n'expose que `add()`, ce qui rend `set()` d'autant plus facile à
manquer — et son effet d'autant plus surprenant.

## Lire, et le piège

Deux familles, une différence qui décide de tout :

| Méthode | Effet |
|---|---|
| `get('warning')` / `all()` | retourne les messages **et les consomme** |
| `peek('warning')` / `peekAll()` | retourne les messages **sans** les consommer |

Dans un gabarit Twig, `app.flashes` consomme ; `app.session.flashbag.peekAll()`
ne consomme pas. Afficher les flashs deux fois dans une même page avec
`app.flashes` en fait donc disparaître la moitié.

### Trois précisions que le tableau écrase

**`get()` ne vide qu'un type ; `all()` vide tout.** Lire les `notice` laisse les
`warning` intacts. Ce n'est pas la même portée.

**`clear()` est un alias de `all()`.** Son corps rend `all()`, donc elle vide
*et retourne* ce qu'elle a vidé. Ce n'est pas une purge silencieuse : la valeur
de retour porte les messages.

**Un type vide compte pour absent.** `has()` exige que la clé existe **et** que
son tableau ne soit pas vide. Un type présent mais vidé répond `false`, et
`get()` rend alors la valeur par défaut — `[]`, sauf si on en passe une autre.

## Pièges d'examen

**Lire un flash le consomme.** Afficher les messages deux fois dans la même page
par le chemin ordinaire en fait disparaître la moitié : la seconde lecture ne
trouve plus rien. La consultation sans consommation existe, c'est une autre
méthode.

**`set()` n'ajoute pas, elle remplace.** Deux `add()` puis un `set()` ne laissent
qu'un message. Seul `add()` empile.

**`clear()` retourne les messages qu'elle efface.** Ignorer sa valeur de retour
jette les messages.

**`get()` ne vide que son type.** Vider tout demande `all()`.

**Poser un flash démarre la session.** Le message flash *est* de la session ; il
n'y a pas de flash sans cookie de session.

**Le type du message est libre.** `notice`, `warning`, `error` sont des usages,
pas une énumération imposée par le framework.

## Tips d'examen

**Le préfixe `peek` est la clé de lecture.** Tout ce qui ne le porte pas
consomme — `get`, `all`, et `clear` qui est `all` sous un autre nom.

## Points clés

- Message de session, consommé à la première lecture.
- `addFlash()` ≡ `getSession()->getFlashBag()->add()`, et **lève** si la session
  manque.
- `add()` empile, `set()` **écrase** le type entier.
- `get()` et `all()` consomment ; `peek()` et `peekAll()` non.
- `clear()` est `all()` : elle vide **et retourne**.
- Un type présent mais vide compte pour absent.

## Sources officielles

- [Sessions, section « Flash Messages »](https://github.com/symfony/symfony-docs/blob/8.0/session.rst)
- [`FlashBag`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Session/Flash/FlashBag.php)
- [`AbstractController::addFlash()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php)
