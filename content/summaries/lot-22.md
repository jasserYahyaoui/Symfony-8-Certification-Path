---
lot: lot-22
verification_status: VERIFIED
reviewed_at: '2026-11-07'
---

## En une phrase

Mime fabrique un email — sender, destinataires, texte, HTML, attachments — et Mailer l'envoie par un transport, tout de suite ou plus tard par
Messenger.

## L'image de la vie réelle

Pense à **l'envoi d'une lettre recommandée**. Tu écris la lettre, tu prépares
le pli, tu joins les documents : c'est **Mime**, qui fabrique le message.
Puis tu la confies à La Poste ou à un coursier : c'est le **transport** de
**Mailer**. Le récépissé du guichet prouve que La Poste a pris la lettre, pas
que le destinataire l'a lue.

## Notion par notion

### Mailer

**C'est quoi ?** Le component qui envoie un email par un transport décrit par
un DSN : `smtp`, `sendmail`…

**Dans la vraie vie** Le guichet de La Poste : il prend ta lettre et te donne un
récépissé.

**Pour l'examen** `MailerInterface::send()` ne rend rien, car l'envoi peut être
différé par Messenger. Un succès veut seulement dire que le transport a
accepté le message. L'envoi n'est asynchrone que si le message d'email est
routé vers un transport Messenger.

### Mime

**C'est quoi ?** Le component qui fabrique le message électronique, avec la
class `Email` et son API chaînable.

**Dans la vraie vie** Écrire la lettre et préparer le pli : le contenu, les
adresses, les documents joints.

**Pour l'examen** Mime fabrique, Mailer envoie. `to()` remplace les
destinataires, `addTo()` en ajoute. `MimeTypes` devine le type d'un file en
lisant son contenu.

## Les erreurs de débutant

- Attendre une return value de `MailerInterface::send()`.
- Croire qu'un envoi réussi veut dire « email lu ».
- Appeler `to()` deux fois en croyant ajouter un destinataire.

## La fiche du lot

- **Mime** fabrique, **Mailer** envoie.
- Transport = un **DSN** : `smtp`, `sendmail`.
- `MailerInterface::send()` ne rend **rien**.
- Succès = **accepté par le transport**.
- **`to()`** remplace, **`addTo()`** ajoute.
