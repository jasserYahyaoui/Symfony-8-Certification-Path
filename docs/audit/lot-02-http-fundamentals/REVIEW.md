# Lot 02 — revues indépendantes

## Revue n° 1 — 2026-09-15, HEAD `771e105`

**Agent :** sous-agent indépendant, lecture seule, n'ayant participé ni à la
rédaction ni à l'examen. Méthode déclarée et vérifiable : lecture intégrale des
10 cours, 14 sources officielles récupérées, **clone *sparse* de
`symfony/symfony@8.0` + PHP 8.4.19 pour exécuter réellement les exemples**,
vérification des deux SHA cités et des 17 numéros de ligne, conversion des 12
liens `/blob/` en `raw` (12 × HTTP 200).

### Verdict : **LOT 02 NON VALIDÉ — 64 / 100**

| Poste | Barème | Note |
|---|---:|---:|
| Exactitude Symfony 8.0 | 25 | 17 |
| Couverture HTTP Fundamentals | 20 | 12 |
| Préparation à la certification | 20 | 12 |
| Qualité pédagogique | 15 | 11 |
| Exemples et cas pratiques | 10 | 5 |
| Sources et traçabilité | 5 | 4 |
| Cohérence et navigation | 5 | 3 |
| **Total** | **100** | **64** |

### Les cinq points bloquants, vérifiés par moi avant correction

| # | Anomalie | Source qui tranche | Statut |
|---|---|---|---|
| **P0-1** | `setMaxAge()` annoté « → caches privés ». Faux : un cache **partagé** utilise `max-age` dès que `s-maxage` est absent. Le cours se contredisait lui-même trois lignes plus bas (« `s-maxage` **prime sur** `max-age` pour eux ») | RFC 9111 §4.2.1 ; `symfony-docs@8.0/http_cache/expiration.rst` l. 34-38, qui écrit littéralement `setPublic(); setMaxAge(600);` pour les **caches partagés** | **CORRIGÉE** |
| **P1-1** | `setSharedMaxAge()` présenté comme le raccourci à préférer. La doc officielle recommande l'inverse | `expiration.rst` l. 47-54 : « `s-maxage` … prohibits a cache to use a stale response in `stale-if-error` scenarios. **That's why it's recommended to use both `public` and `max-age` directives** » | **CORRIGÉE** |
| **P1-2** | `303 See Other` absent du lot, alors qu'il figure dans le code cité par le cours *HTTP response* | RFC 9110 §15.4.4 | **CORRIGÉE** |
| **P1-3** | sortie de `getLanguages()` fausse pour l'en-tête montré : `*` survit à la normalisation | `Request.php` `getLanguages()` applique `formatLocale()` à **tous** les items, sans filtrer | **CORRIGÉE** |
| **P1-4** | le retrait de `Request::get()` en 8.0 n'était enseigné nulle part | `HttpFoundation/CHANGELOG.md`, section **8.0** : « **Remove `Request::get()`**, use properties `->attributes`, `query` or `request` directly instead » | **CORRIGÉE** |

Plus **P2-14** (renvoi interne faux : « la seconde ligne » désignait la
première), corrigé dans le même passage.

### Ce que le reviewer a confirmé comme juste

À porter au crédit du lot, vérifié par **exécution** et non par relecture :
`isRedirect()` au caractère près, `, true` compris · la correction du `P0`
précédent sur `getClientIp()` (les deux exemples rendent bien `203.0.113.7` et
`5.6.7.8`) · les défauts de `Cookie::create()` et `fromString()` · la garde
`isMethodCacheable()` de `isNotModified()` et son `elseif` · `setSharedMaxAge()`
appelant `setPublic()` · le `Cache-Control` par défaut `no-cache, private` ·
`Response` neuve en `HTTP/1.0` · `QUERY` et la divergence RFC/Symfony ·
`InputBag::get()` · le tri de `getAcceptableContentTypes()` · les signatures de
`HttpClientInterface` · **17/17 numéros de ligne exacts**, `6f841c0` étant le
HEAD courant de la branche 8.0, **12/12 liens à 200**.

### Ce que je n'ai pas fait, et pourquoi

Le reviewer signale 14 anomalies `P2` et 9 `P3` supplémentaires. Les
**bloquantes seules** ont été traitées dans cette itération : la mission
conditionne la validation à « aucun P0, aucun P1 », et traiter les 23 restantes
dans le même passage ferait perdre la traçabilité de ce qui a corrigé quoi.
Elles sont nommées dans le rapport et restent **ouvertes**, notamment :
`Partitioned`/CHIPS, `setCache()`, `getPreferredFormat()` qui honore `_format`
avant `Accept`, `415`, les ancres `#section-N` des RFC qui n'atterrissent pas,
et la prémisse manquante des exemples `getClientIp()` (`REMOTE_ADDR` doit
lui-même être de confiance).

**Aucune promotion de niveau n'a été faite.** `REV-001` a bloqué deux fois —
à 472 puis à 418 mots sur un plafond `MINIMAL` de 400 — et la réponse a été de
resserrer le texte, y compris en retirant 12 mots de moindre valeur pour faire
place à `303`. Le reviewer a lui-même vérifié que les cinq corrections tenaient
dans les budgets existants.

## Revue n° 2 — 2026-09-15, HEAD `20e1336`

**Agent :** second sous-agent indépendant, n'ayant participé ni à la rédaction,
ni à l'examen, ni à la revue n° 1, et à qui il était demandé de **ne pas lire
les rapports** avant d'avoir formé son jugement. 24 sources récupérées, **~40
comportements exécutés** en PHP 8.4.19 contre le composant 8.0 reconstitué.

### Verdict : **LOT 02 NON VALIDÉ — 82,5 / 100** — aucun `P0`, **un `P1`**

| Poste | Barème | n° 1 | n° 2 |
|---|---:|---:|---:|
| Exactitude Symfony 8.0 | 25 | 17 | **23** |
| Couverture HTTP Fundamentals | 20 | 12 | **16** |
| Préparation à la certification | 20 | 12 | **17** |
| Qualité pédagogique | 15 | 11 | **12** |
| Exemples et cas pratiques | 10 | 5 | **8** |
| Sources et traçabilité | 5 | 4 | **3** |
| Cohérence et navigation | 5 | 3 | **3,5** |
| **Total** | **100** | **64** | **82,5** |

Le reviewer confirme que **les cinq corrections de la revue n° 1 sont toutes
exactes** et qu'aucune erreur n'a été introduite par elles — ce qui n'allait pas
de soi, deux corrections antérieures de ce lot ayant déjà introduit un défaut.

### `P1-1` — la définition du message n'était pas celle de RFC 9110

Le cours écrivait : « **Message** : requête ou réponse, composé d'une **ligne de
départ**, de champs d'en-tête, et éventuellement d'un corps. »

RFC 9110 §6 *Message Abstraction*, relevé mot pour mot :

> « A message consists of the following: **control data** to describe and route
> the message, a **headers** lookup table…, a potentially unbounded stream of
> **content**, and a **trailers** lookup table… »

Trois aggravations : la « ligne de départ » est la forme **HTTP/1.1**, donc
RFC 9112 — ce que la même page reproche 22 lignes plus bas ; §6 est déclarée
**vérifiée** dans le front matter ; et les *trailers*, seul composant que le
lecteur ne devinera pas, manquaient. Sur une page intitulée « Vocabulaire
imposé », c'est la définition normative qui était fausse. **CORRIGÉE.**

### Corrections faites dans la même itération

| Anomalie | Traitement |
|---|---|
| `P1-1` définition du message | réécrite : données de contrôle, en-têtes, contenu, *trailers* |
| `P2-4` **trois ancres RFC mortes** (`#section-15`, `#section-9.2`, `#section-12`) | remplacées par les ancres réelles du document httpwg — `#status.codes`, `#method.properties`, `#content.negotiation`, vérifiées présentes |
| `P2-2` la recommandation `stale-if-error` reposait sur une page **ni citée ni liée** | `http_cache/expiration.rst` ajoutée en `official_sources` avec son passage exact, et liée |
| `P2-3` trois RFC citées sans URL | RFC 9111 liée ; 5861 et 8246 nommées avec leur section |
| `P2-1` `setMaxAge(3600)` annoté `max-age=3600` | l'en-tête réellement émis est `max-age=3600, private` |
| `P2-10` `getPreferredFormat()` | la précédence de `_format` sur `Accept` est enseignée |
| `P2-5` constantes de redirection | `HTTP_SEE_OTHER`, `HTTP_TEMPORARY_REDIRECT` et `HTTP_PERMANENTLY_REDIRECT` ajoutées, avec le piège de nommage |
| `P3` « 303 impose un `GET` » | corrigé en « `GET` (ou `HEAD`) », conforme à §15.4.4 |
| `P3` « une requête malformée est 4xx même si le serveur plante » | retiré — trompeur sur le code réellement renvoyé |

**Budget.** `REV-001` a bloqué **trois fois de plus** dans cette itération (428,
414, 405 mots sur un plafond `MINIMAL` de 400). Réponse à chaque fois : resserrer
la prose, y compris en supprimant la phrase P3 trompeuse. **Aucune promotion de
niveau.** Le reviewer avait lui-même vérifié que le correctif tenait dans les 9
mots de marge.

### Restent ouvertes

`Partitioned`/CHIPS et les préfixes `__Host-` ; `setTrustedHosts()` et
l'empoisonnement de `Host` (page à 865/900, 35 mots de marge) ; la restriction
8.0 de l'override de méthode ; le corps des `PUT`/`PATCH`/`DELETE`/`QUERY` dans
`createFromGlobals()` — atténué, la doc officielle dit encore `$_POST` ; `415` ;
`setCache()` ; la prémisse `REMOTE_ADDR` des exemples `getClientIp()` ; `reviewed_at`
non rafraîchi.

## Revue n° 3

En cours. Le lot **reste bloqué** jusqu'à un verdict explicite.
