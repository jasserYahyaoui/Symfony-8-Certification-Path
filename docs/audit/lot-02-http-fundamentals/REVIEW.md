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

## Revue n° 2

En cours. Le lot **reste bloqué** jusqu'à un verdict explicite.
