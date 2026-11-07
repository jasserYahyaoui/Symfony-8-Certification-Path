---
lot: lot-20
verification_status: VERIFIED
reviewed_at: '2026-11-07'
---

## En une phrase

L'EventDispatcher permet à un code d'annoncer qu'il s'est passé quelque chose,
sans savoir qui va réagir : les listeners inscrits sont prévenus dans l'ordre
de leur priority, et reçoivent un object event qui transporte l'information.

## L'image de la vie réelle

Pense à une **alarme incendie dans un immeuble**. Celui qui appuie sur le
bouton ne connaît pas les voisins : il déclenche l'alarme, c'est
`dispatch()`. Les pompiers, le gardien, chaque étage réagissent selon ce qui
est prévu : ce sont les **listeners**. Les pompiers passent avant le gardien :
c'est la **priority**. Le message de l'alarme — « feu au 3ᵉ étage » —, c'est
l'**event**. Et le pompier peut dire « inutile de prévenir les autres, c'est
réglé » : c'est `stopPropagation()`.

## Notion par notion

### EventDispatcher

**C'est quoi ?** Le service qui tient la liste des listeners par nom d'event,
et les appelle quand l'event est distribué.

**Dans la vraie vie** Le boîtier de l'alarme : il sait qui prévenir pour quel
type d'alerte, et dans quel ordre.

**Pour l'examen** La priority la plus haute passe en premier ; à égalité,
l'ordre d'ajout décide. Sans nom, `dispatch()` utilise la class de l'event, et
rend le même object. Un listener se branche de l'extérieur, un subscriber
déclare lui-même ses events.

### Event

**C'est quoi ?** L'object qui circule entre celui qui déclenche et les
listeners. La class de base `Event` sait seulement arrêter la propagation.

**Dans la vraie vie** Le message de l'alarme : pour qu'il dise où est le feu,
il faut le rédiger ; l'alarme de base ne fait que sonner.

**Pour l'examen** Pour transporter une donnée, on écrit une sous-classe
d'`Event`, ou l'on prend `GenericEvent`. `stopPropagation()` saute les
listeners suivants, mais n'annule pas ce qui est déjà fait.

## Les erreurs de débutant

- Croire que la priority basse passe en premier.
- Penser que `stopPropagation()` annule l'action déjà déclenchée.
- Attendre de la class `Event` qu'elle transporte des données.

## La fiche du lot

- **`dispatch()`** annonce, les **listeners** réagissent.
- Priority **haute = en premier** ; égalité : ordre d'ajout.
- Sans nom, l'event porte **le nom de sa class**.
- **Listener** branché de l'extérieur, **subscriber** auto-déclaré.
- `Event` : **arrêter la propagation**, rien d'autre.
- **`stopPropagation()`** saute la suite, n'annule rien.
