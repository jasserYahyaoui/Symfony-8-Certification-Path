# HttpFoundation en 8.0

## Objectif

Savoir ce que la 8.0 retire et ajoute dans le composant qui porte la requête et
la réponse. Page d'enrichissement : hors périmètre officiel, hors couverture.

## Ce qui disparaît

| Retiré | À la place |
|---|---|
| `Request::get()` | les sacs directement : `attributes`, `query`, `request` |
| l'argument `$format` à `null` sur `Request::setFormat()` | un format explicite |
| sept options de session sur `NativeSessionStorage` | plus d'équivalent |

`Request::get()` est la suppression la plus visible. Elle cherchait la valeur
successivement dans plusieurs sacs, ce qui rendait impossible de savoir d'où la
donnée venait. Le journal est explicite : *« use properties `->attributes`,
`query` or `request` directly instead »*.

Les sept options de session retirées sont celles qui configuraient les
mécanismes hérités de PHP : contrôle du référent, identifiant en URL, longueur
et alphabet de l'identifiant, hôtes et balises de propagation.

## Ce qui change de comportement

**La surcharge de méthode HTTP ne vaut plus pour `GET`, `HEAD`, `CONNECT` et
`TRACE`.** Le mécanisme qui laisse un formulaire déclarer `PUT` ou `DELETE`
existe toujours, mais il ne peut plus transformer une requête en l'une de ces
quatre méthodes.

**`sendHeaders()` après envoi déclenche un avertissement PHP.** Le journal
recommande une `StreamedResponse` plutôt que de forcer l'envoi.

## Ce qui s'ajoute

Trois arguments, tous sur des signatures existantes :

- `$subtypeFallback` sur `Request::getFormat()` ;
- **`$partitioned` sur `ResponseHeaderBag::clearCookie()`** ;
- `$expiration` sur `UriSigner::sign()`.

Et `IpUtils::anonymize()` reçoit `$v4Bytes` et `$v6Bytes` : le nombre d'octets
anonymisés devient réglable au lieu d'être fixe.

## Pièges d'examen

**`Request::get()` n'existe plus.** Une proposition qui l'emploie est fausse en
8.0, même si le code correspondant tournait en 7.x.

**La surcharge de méthode n'est pas supprimée**, elle est restreinte à quatre
méthodes exclues. Répondre « supprimée » est aussi faux que « inchangée ».

**Les ajouts sont des arguments, pas des méthodes.** `clearCookie()` n'est pas
nouvelle ; elle reçoit un argument de plus.

## Points clés

- `Request::get()` est retirée : lire le sac voulu directement.
- Surcharge de méthode HTTP interdite pour `GET`, `HEAD`, `CONNECT`, `TRACE`.
- `clearCookie()` gagne `$partitioned`, `getFormat()` gagne `$subtypeFallback`.
- Sept options de session héritées de PHP disparaissent.

## Sources officielles

- [HttpFoundation, journal 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/CHANGELOG.md)

## Flashcards

### Mémorisation

<details>
<summary>Quelle méthode de `Request` la 8.0 retire-t-elle ?</summary>

**`Request::get()`.** Le journal renvoie vers les sacs directement : `attributes`, `query`, `request`.

Elle cherchait dans plusieurs sacs successivement, ce qui rendait l'origine de la donnée indéterminable.

</details>

<details>
<summary>Quel argument la 8.0 ajoute-t-elle à `ResponseHeaderBag::clearCookie()` ?</summary>

**`$partitioned`.**

C'est un ajout d'argument sur une méthode existante, pas une méthode nouvelle.

</details>

### Compréhension

<details>
<summary>Pourquoi retirer `Request::get()` plutôt que la corriger ?</summary>

Parce que son défaut était son principe : chercher dans plusieurs sacs rend impossible de savoir d'où vient la valeur. Nommer le sac est la correction.

Une méthode dont le contrat est l'ambiguïté ne se corrige pas, elle se retire.

</details>

<details>
<summary>Pourquoi la surcharge de méthode HTTP est-elle interdite pour `GET`, `HEAD`, `CONNECT` et `TRACE` ?</summary>

Ce sont des méthodes sans effet de bord attendu ou réservées au diagnostic : laisser un formulaire les fabriquer ouvrait un écart entre ce que le serveur croit recevoir et ce qui a été émis.

Le mécanisme subsiste pour les autres méthodes.

</details>

### Application

<details>
<summary>Du code en 7.x appelle `$request->get('id')`. Que devient-il en 8.0 ?</summary>

Il échoue : la méthode n'existe plus. Il faut choisir le sac — `attributes` pour un paramètre de route, `query` pour la chaîne de requête, `request` pour un formulaire.

Le choix du sac est justement ce que l'ancienne méthode dispensait de faire.

</details>

<details>
<summary>Une réponse est envoyée puis le code rappelle `sendHeaders()`. Que se passe-t-il en 8.0 ?</summary>

Un **avertissement PHP** est déclenché. Le journal oriente vers une `StreamedResponse`.

Le besoin réel derrière ce double envoi est presque toujours du flux.

</details>

### Pièges

<details>
<summary>Piège : « la surcharge de méthode HTTP est supprimée en 8.0. »</summary>

Non : elle est **restreinte**. Elle ne peut plus produire `GET`, `HEAD`, `CONNECT` ni `TRACE` ; elle fonctionne pour les autres.

« Supprimée » et « inchangée » sont fausses toutes les deux.

</details>

<details>
<summary>Piège : « `clearCookie()` est une nouvelle méthode de la 8.0. »</summary>

Non : elle existait déjà et reçoit un argument de plus, `$partitioned`.

Les entrées « Add argument » d'un journal se lisent vite comme « Add method ».

</details>
