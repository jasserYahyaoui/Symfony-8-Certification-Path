---
id: CRS-82d40ncj59k9
official_item: OIT-9xdjjsqcbn13
title: "Mime"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/mime.rst"
    anchor: "usage"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
---

## Objectif

Composer un message électronique et savoir quelle structure MIME il produit.

## Périmètre

Ce composant **fabrique** le message ; il ne l'envoie pas. Le transport,
l'envoi et les événements appartiennent à l'item *Mailer*. L'envoi asynchrone
appartient au composant Messenger (lot 11) et n'est pas réexpliqué ici.

## Deux API pour un même message

Le composant offre deux niveaux :

- `Email` — l'API haut niveau, chaînable, qui couvre les besoins courants ;
- `Message` — l'API bas niveau, pour contrôler chaque partie à la main.

```php
use Symfony\Component\Mime\Email;

$email = new Email()
    ->from('fabien@symfony.com')
    ->to('foo@example.com')
    ->cc('bar@example.com')
    ->bcc('baz@example.com')
    ->replyTo('fabien@symfony.com')
    ->priority(Email::PRIORITY_HIGH)
    ->subject('Important Notification')
    ->text('Lorem ipsum...')
    ->html('<h1>Lorem ipsum</h1> <p>...</p>');
```

La documentation déconseille explicitement l'API bas niveau pour un besoin
ordinaire : elle ajoute de la complexité sans gain réel.

## L'arborescence MIME

Un message qui porte du texte, du HTML, une image intégrée et un fichier joint
se structure ainsi — c'est l'arbre qui fonctionne sur le plus de clients :

```text
multipart/mixed
├── multipart/related
│   ├── multipart/alternative
│   │   ├── text/plain
│   │   └── text/html
│   └── image/png
└── application/pdf
```

Chaque conteneur a un rôle distinct :

- `multipart/alternative` — plusieurs versions **du même contenu**. La forme
  préférée se place **en dernier**.
- `multipart/related` — des parties qui forment **un tout**, typiquement des
  images affichées dans le corps.
- `multipart/mixed` — des contenus **de natures différentes**, typiquement une
  pièce jointe.

## Les adresses

`from()`, `to()` et les autres acceptent une chaîne ou un objet `Address`.
`Address::create('Fabien Potencier <fabien@example.com>')` analyse la forme
`Nom <adresse>`. `addTo()`, `addCc()` et `addBcc()` **ajoutent** là où `to()`
remplace.

Les caractères UTF-8 sont admis dans la partie locale — **sauf pour l'adresse
d'expéditeur**, afin d'éviter les problèmes de retour en cas de rebond.

## Sérialiser un message

`Email` et `Message` sont de simples objets de données : `serialize()` les
accepte. On reconstruit ensuite le message avec `RawMessage` :

```php
$message = new RawMessage(unserialize($serializedEmail));
```

## Les types MIME

`MimeTypes` traduit dans les deux sens :

```php
$mimeTypes->getExtensions('image/jpeg'); // ['jpeg', 'jpg', 'jpe']
$mimeTypes->getMimeTypes('js');
```

Les tableaux rendus sont **ordonnés par priorité** : le premier élément est le
préféré.

`guessMimeType()` **ne regarde pas le nom du fichier** : il inspecte le
contenu. L'opération est coûteuse ; l'extension PHP `fileinfo` l'accélère. Un
devineur maison implémente `MimeTypeGuesserInterface` et porte le tag
`mime.mime_type_guesser`.

## Pièges d'examen

**Dans `multipart/alternative`, la version préférée est la dernière.**

**`guessMimeType()` ignore le nom du fichier** — il lit le contenu.

**Les tableaux de `MimeTypes` sont classés par priorité, pas alphabétiquement.**

**L'UTF-8 dans la partie locale est refusé pour l'expéditeur seul.**

## Points clés

- `Email` haut niveau, `Message` bas niveau ; le second se réserve aux besoins
  rares.
- `alternative` = mêmes contenus, `related` = un tout, `mixed` = natures
  différentes.
- `Address::create()` analyse `Nom <adresse>` ; `addTo()` ajoute, `to()`
  remplace.
- `RawMessage` reconstruit un message sérialisé.
- `MimeTypes` rend des tableaux ordonnés par préférence ; la devinette lit le
  contenu.

## Sources officielles

- [`components/mime.rst`, branche 8.0](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/mime.rst)
