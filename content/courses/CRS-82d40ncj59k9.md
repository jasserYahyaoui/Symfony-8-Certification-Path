---
id: CRS-82d40ncj59k9
official_item: OIT-9xdjjsqcbn13
title: "Mime"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-03"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/mime.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/components/mime.rst"
    anchor: "usage"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Mime/MimeTypes.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Mime/MimeTypes.php"
    branch: "8.0"
    symbol_or_lines: "getExtensions(); getMimeTypes()"
    verified_at: "2026-10-03"
outcome_sections:
  OUT-svzhszy009bw: "Deux API pour un même message"
  OUT-8mvg51813h97: "L'arborescence MIME"
  OUT-0rk3gn6523qv: "Les types MIME"
  OUT-1c0gmbpg2341: "Les adresses"
---

## Objectif

Composer un message électronique et savoir quelle structure MIME il produit.

## L'essentiel

Le component Mime **fabrique** un message électronique ; il ne l'envoie pas.
L'API `Email`, chaînable, couvre les besoins courants ; `Message`, bas niveau,
se réserve aux cas rares. À l'examen : la structure MIME produite, les
adresses, et `MimeTypes`, dont la devinette lit le contenu du file.

## Le schéma

```text
multipart/mixed                    contenus de natures différentes (pièce jointe)
├── multipart/related              un tout : le HTML et ses images intégrées
│   ├── multipart/alternative      deux versions du MÊME contenu ; la préférée EN DERNIER
│   │   ├── text/plain
│   │   └── text/html
│   └── image/png
└── application/pdf
```

## L'exemple : une notification

```php
use Symfony\Component\Mime\Email;

$email = new Email()
    ->from('fabien@symfony.com')
    ->to('foo@example.com')
    ->addCc('bar@example.com')
    ->priority(Email::PRIORITY_HIGH)
    ->subject('Important Notification')
    ->text('Lorem ipsum...')
    ->html('<h1>Lorem ipsum</h1>')
    ->attachFromPath('/path/report.pdf');
```

## Deux API pour un même message

`Email` est l'API haut niveau ; `Message` contrôle chaque partie à la main,
et la documentation la déconseille pour un besoin ordinaire. `Email` et
`Message` se sérialisent ; `new RawMessage(unserialize($data))` reconstruit un
message envoyable.

## L'arborescence MIME

L'arbre ci-dessus est exactement celui qu'`Email` produit pour texte, HTML,
image intégrée et PDF joint. `alternative` réunit des versions du même
contenu, `related` des parties qui forment un tout, `mixed` des contenus de
natures différentes.

## Les adresses

- Une string ou un object `Address` ;
  `Address::create('Fabien Potencier <fabien@example.com>')` analyse la forme
  `Nom <adresse>`.
- `to()`, `cc()`, `bcc()` remplacent ; `addTo()`, `addCc()`, `addBcc()`
  ajoutent.
- L'UTF-8 est admis dans la partie locale, **sauf pour le sender** :
  `Envelope` refuse `jösé@…` comme sender, l'accepte comme destinataire.

## Les types MIME

| Appel | Code 8.0, exécuté |
|---|---|
| `getExtensions('image/jpeg')` | `jpg`, `jpeg`, `jpe`, `jfif` |
| `getMimeTypes('js')` | `text/javascript` d'abord |

Les arrays sont classés **par priority**, le premier étant le préféré — la
documentation 8.0 en montre un ordre que le code ne rend plus.
`guessMimeType()` **lit le contenu**, pas le nom : un PNG nommé `.txt` est
`image/png`. L'extension PHP `fileinfo` l'accélère ; un devineur maison
implémente `MimeTypeGuesserInterface`, tag `mime.mime_type_guesser`.

## Pièges d'examen

**Dans `multipart/alternative`, la version préférée est la dernière.**

**`guessMimeType()` ignore le nom du file** — il lit le contenu.

**Les arrays de `MimeTypes` sont classés par priority, pas alphabétiquement**
— et la priority réelle du code diffère de l'exemple de la documentation.

**L'UTF-8 dans la partie locale est refusé pour le sender seul.**

## Application

- Un email lisible partout, en texte et en HTML → `text()` et `html()` :
  `Email` construit le `multipart/alternative`.
- Un message mis en queue → `serialize()`, puis `RawMessage` au
  retour.

## QCM d'entraînement

**Question 1.** Que rend en premier `$mimeTypes->getMimeTypes('js')` sur
Mime 8.0 ?

- **A.** `application/javascript`, comme dans la documentation
- **B.** `text/javascript`
- **C.** `application/x-javascript`
- **D.** Un array classé par ordre alphabétique

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Les types sont classés par priority, le préféré en
premier ; l'exemple de la documentation 8.0 ne correspond plus au code.

</details>

**Question 2.** Quelle extension PHP rend `guessMimeType()` plus rapide ?

- **A.** `intl`
- **B.** `mbstring`, pour les noms
- **C.** `fileinfo`
- **D.** `iconv`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** La devinette inspecte le contenu du file, ce qui
coûte ; l'extension `fileinfo` fournit cette inspection plus vite.

</details>

**Question 3.** `jösé@example.com` est ajouté comme **destinataire** d'un
message dont le sender est en ASCII. Que fait `Envelope` ?

- **A.** Il refuse l'adresse : l'UTF-8 est interdit
- **B.** Il l'accepte
- **C.** Il la convertit en punycode
- **D.** Il retire les accents

<details>
<summary>Correction</summary>

**Bonne réponse : B.** La partie locale UTF-8 n'est refusée que pour le
sender, à cause des rebonds ; un destinataire peut la porter.

</details>

**Question 4.** Une `Email` porte `text()` et `html()`, sans attachment ni
image. Quel container réunit les deux versions ?

- **A.** `multipart/mixed`, natures mêlées
- **B.** `multipart/related`
- **C.** `multipart/digest`, un recueil
- **D.** `multipart/alternative`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Deux rendus du même contenu forment une alternative ;
le client affiche le dernier qu'il sait lire, d'où le HTML en dernier.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- Mime **fabrique**, Mailer **envoie**.
- **`Email`** haut niveau ; `Message` pour les besoins rares.
- `alternative` = même contenu, préféré **en dernier** ; `related` = un tout ;
  `mixed` = natures différentes.
- `to()` remplace, **`addTo()`** ajoute ; UTF-8 refusé pour le **sender**.
- `MimeTypes` : arrays **par priority** ; `jpg` et `text/javascript` d'abord.
- `guessMimeType()` lit le **contenu** ; `fileinfo` accélère.

Moyen mnémotechnique : **« Même contenu, un tout, des natures »**.

## Sources officielles

- [`components/mime.rst`, branche 8.0](https://github.com/symfony/symfony-docs/blob/8.0/components/mime.rst)
- [`MimeTypes`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Mime/MimeTypes.php)

