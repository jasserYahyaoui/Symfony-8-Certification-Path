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

## Revue n° 3 — 2026-09-15, HEAD `8cf5020`

**Agent :** troisième sous-agent indépendant, sans contact avec les deux
précédents, à qui il était demandé de ne pas lire les rapports avant d'avoir
clos son jugement. 30 sources récupérées, composant 8.0 reconstitué,
**~45 comportements exécutés** sous PHP 8.4.19.

### Verdict : **LOT 02 NON VALIDÉ — 76,5 / 100** — **deux `P0`**, aucun `P1`

| Poste | Barème | n° 1 | n° 2 | n° 3 |
|---|---:|---:|---:|---:|
| Exactitude Symfony 8.0 | 25 | 17 | 23 | **19** |
| Couverture HTTP Fundamentals | 20 | 12 | 16 | **16** |
| Préparation à la certification | 20 | 12 | 17 | **16** |
| Qualité pédagogique | 15 | 11 | 12 | **12** |
| Exemples et cas pratiques | 10 | 5 | 8 | **7** |
| Sources et traçabilité | 5 | 4 | 3 | **3** |
| Cohérence et navigation | 5 | 3 | 3,5 | **3,5** |
| **Total** | **100** | **64** | **82,5** | **76,5** |

**Les deux `P0` tombent tous les deux sur le cours *Caching*, et tous deux sont
des défauts que les itérations précédentes ont produits ou manqués.**

### `P0-1` — une annotation vraie isolément, fausse dans son bloc

Corrigeant le `P2-1` de la revue n° 2, j'avais écrit :

```php
$response->setPublic();
$response->setMaxAge(3600);         // émet « max-age=3600, private » → tous les caches
```

`ResponseHeaderBag::computeCacheControlValue()` n'ajoute `, private` que si ni
`public`, ni `private`, ni `s-maxage` n'est présent :

```php
if (isset($this->cacheControl['public']) || isset($this->cacheControl['private'])) {
    return $header;
}
```

`setPublic()` précédant l'appel, l'en-tête émis est `public, max-age=3600`.
J'avais recopié le comportement **isolé** de `setMaxAge()` — exact hors contexte
— dans une séquence où il ne s'applique pas. L'annotation était de surcroît
auto-contradictoire : `private` interdit le stockage par un cache partagé, donc
« private → tous les caches » ne peut pas être vrai. **CORRIGÉE** : les trois
lignes portent désormais l'en-tête cumulé réel, et le comportement isolé est
énoncé à part.

**C'est la quatrième correction de ce lot qui introduit un défaut.**

### `P0-2` — « l'ETag l'emporte toujours » est faux

Les revues n° 1 et n° 2 avaient **portré ce passage au crédit du lot**. La
revue n° 3 l'a exécuté :

```php
if (($ifNoneMatchEtags = $request->getETags()) && (null !== $etag = $this->getEtag())) {
```

La garde exige **deux** conditions : un `If-None-Match` dans la requête **et**
un ETag sur la réponse. Une réponse sans ETag, avec `Last-Modified`, face à une
requête portant `If-None-Match` **et** `If-Modified-Since` correspondant, rend
`true` et un 304 — la date a bien été évaluée. **CORRIGÉE**, et l'occasion
pédagogique manquée est reprise : RFC 9110 §13.1.3 impose d'ignorer
`If-Modified-Since` dès que `If-None-Match` est présent, **sans** condition sur
la réponse. Symfony s'en écarte.

### Aussi corrigés

Trois `P3` issus de mon propre commit précédent : doublon du bloc de sources RFC,
`anchor: "expiration"` qui n'existe pas dans `expiration.rst`, et « 303 complète
ce tableau 2×2 » alors que 303 n'appartient pas à ce 2×2.

### Ce que la revue n° 3 confirme comme juste

12/12 liens à 200 ; **les 3 ancres RFC corrigées à l'itération précédente
existent réellement** et pointent sur §15, §9.2 et §12 ; 17/17 numéros de ligne
exacts ; `REV-001` recalculé avec la formule du dépôt : **10/10 conformes** ;
`POOL-002` satisfait ; aucun renvoi inter-items mort ; et — vérification que
personne n'avait faite — **aucun fait testé par `lot-02-http.yml` n'est absent
des cours**.

### Restent ouvertes

`Partitioned`/CHIPS et les préfixes `__Host-` ; `setTrustedHosts()` ;
la restriction 8.0 de l'override ; `send()`/`sendHeaders()` ; SSRF côté
HttpClient ; `reviewed_at` non rafraîchi sur les 10 cours ; et un distracteur de
`lot-02-http.yml` qui affirme « max-age applies to private caches » — le piège
que le cours dénonce désormais.

## Revue n° 4 — 2026-09-15, HEAD `a6fd2c7`

**Agent :** quatrième sous-agent indépendant. Composant 8.0 cloné **et diffé
octet à octet** contre les fichiers `raw` (6/6 identiques), **~50 comportements
exécutés**, 25 sources, 11 liens et 5 ancres vérifiés, portes relancées par lui
au HEAD.

### Verdict : **LOT 02 NON VALIDÉ — 77 / 100** — **aucun `P0`**, 4 `P1`

| Poste | Barème | n° 1 | n° 2 | n° 3 | n° 4 |
|---|---:|---:|---:|---:|---:|
| Exactitude Symfony 8.0 | 25 | 17 | 23 | 19 | **21** |
| Couverture HTTP Fundamentals | 20 | 12 | 16 | 16 | **16** |
| Préparation à la certification | 20 | 12 | 17 | 16 | **13** |
| Qualité pédagogique | 15 | 11 | 12 | 12 | **13** |
| Exemples et cas pratiques | 10 | 5 | 8 | 7 | **8** |
| Sources et traçabilité | 5 | 4 | 3 | 3 | **3,5** |
| Cohérence | 5 | 3 | 3,5 | 3,5 | **2,5** |
| **Total** | **100** | **64** | **82,5** | **76,5** | **77** |

**Résultat central : les cours ne contiennent plus de `P0`.** Les quatre `P1`
étaient ailleurs — et deux d'entre eux dans un angle mort que quatre revues
avaient laissé.

| # | Où | Anomalie | Statut |
|---|---|---|---|
| `P1-1` | `lot-02-http.yml`, pool **HOLDOUT** | un distracteur enseignait « max-age applies to private caches » — le contre-sens que le cours dénonce (RFC 9111 §5.2.2.1 / §5.2.2.10) | **CORRIGÉE** |
| `P1-2` | `QST-z116xknpac5j` | l'explication publiait « `getClientIps()[0]` … original client left-most » — mécanisme faux (`array_reverse`), rouvrant la faille que le cours ferme | **CORRIGÉE** |
| `P1-3` | cours *HTTP request* | la restriction 8.0 de l'override (`GET`/`HEAD`/`CONNECT`/`TRACE` ignorés, `setAllowedHttpMethodOverride()`) — **ouverte depuis deux revues** | **CORRIGÉE** |
| `P1-4` | `VALIDATION.md` | fichier daté de `d084d78`, **quatre commits de cours en amont** : build et a11y y attestaient d'un arbre disparu pendant que `SESSION_STATE` annonçait « toutes les portes au vert » | **CORRIGÉE** |

Aucune clé de réponse n'a été modifiée : seules deux `explanation` ont été
réécrites. `aud06_holdout_integrity` a été relancé parce que `P1-1` touche un
payload `HOLDOUT` — il passe.

### L'angle mort, nommé

Quatre revues avaient vérifié qu'« aucun fait testé n'est absent des cours ».
**Personne n'avait vérifié la réciproque** : qu'aucune explication publiée ne
contredit un cours. Les deux derniers `P1` étaient exactement là.

## Revue n° 5 — **NON EXÉCUTÉE**

Lancée le 2026-09-15 sur le HEAD `afeea75`, avec pour consigne d'auditer la
banque de questions explication par explication et de trancher la question du
plafond de note atteignable sous contrainte `REV-001`.

**Elle s'est arrêtée avant de produire le moindre verdict**, sur une limite de
session de l'API (HTTP 429). Aucun score, aucune anomalie, aucune conclusion
n'en est issue, et **rien n'est reporté ici à sa place**.

Le lot **reste donc bloqué au dernier verdict rendu : celui de la revue n° 4,
`LOT 02 NON VALIDÉ`.**

## Question de fond, posée et non tranchée

Le poste « couverture » plafonne à **16/20** sur les quatre revues. Les notions
encore absentes — `Partitioned`/CHIPS, `setTrustedHosts()`, `send()`,
hiérarchie d'exceptions du client HTTP, `415` — ne peuvent entrer qu'en
retirant du texte : quatre cours sont à **moins de 15 mots** de leur plafond
`REV-001`, et promouvoir un niveau est interdit.

`REV-001` a bloqué **cinq fois** pendant cet audit. La réponse a toujours été de
resserrer la prose, jamais de changer de palier. Reste une question ouverte que
la revue n° 5 devait trancher : **95/100 est-il atteignable sous cette
contrainte, ou le plafond réel est-il structurellement inférieur ?** Elle
appelle une décision humaine, pas une itération de plus.

---

## Revue n° 5 — 2026-09-16 — **LOT 02 NON VALIDÉ, 72/100**

Première revue à voir le lot après les trois vagues de corrections, les cinq
notions et leurs questions. Sous-agent indépendant, n'ayant participé ni à la
rédaction ni aux revues 1 à 4. Holdout non ouvert, vérifié.

| Poste | Note |
|---|---|
| Exactitude technique | 14 / 25 |
| Couverture du périmètre | 17 / 20 |
| Pertinence certification | 17 / 20 |
| Qualité pédagogique | 11 / 15 |
| Exemples de code | 8 / 10 |
| Sources | 3 / 5 |
| Cohérence interne | 2 / 5 |
| **Total** | **72 / 100** |

### Les trois anomalies bloquantes, toutes vérifiées par moi contre la source

**`P0-1` — la casse des préfixes de cookie. Écrite par moi le jour même.**
J'avais cité les lignes 842-893 du brouillon httpbis, qui est le chapitre des
exigences **serveur**, pour décrire ce que fait le **navigateur**. Le document
dit l'inverse à l'intention des agents : « UAs **MUST** match the prefix string
**case-insensitively** » (l. 1247), repris par les étapes 20 et 21 du storage
model (l. 1817-1831). Pire, le brouillon consacre 35 lignes à expliquer que la
correspondance sensible à la casse **est la faille** : un `__SeCuRe-SID` posé
par un tiers se ferait passer pour un cookie ordinaire. J'enseignais donc la
vulnérabilité comme si c'était la règle. Le chapitre que j'avais lu contient
pourtant, en tête, le renvoi explicite vers l'autre : « The user agent
requirements … are detailed in `{{ua-name-prefixes}}` ». Je ne l'ai pas suivi.

**`P1-1` — une règle de départage inexistante.** `QST-055ctb1t92na` enseignait
que la spécificité tranche entre deux motifs à qualité égale, et que « order of
appearance never enters the computation ». `AcceptHeader::sort()` fait
exactement le contraire : `getQuality() <=> … ?: getIndex() <=> …`. La question
contredisait le cours du même item, qui énonce déjà la bonne règle.

**`P1-2` — le libellé récompensait le piège que le cours démonte.**
`QST-z116xknpac5j` donnait pour bonne réponse « The left-most address, which is
the original client », alors que le cours consacre un paragraphe entier — et un
point clé — à dire que `getClientIp()` ne renvoie **pas** la plus à gauche.
J'avais corrigé l'`explanation` lors d'une revue précédente et laissé le
libellé, qui est pourtant ce que l'apprenant mémorise.

**`P2-1` — `content_level` du cours contre la matrice.** Ma promotion en `DEEP`
(ADR-0008) avait modifié la matrice sans le front matter du cours.

### Ce que la revue a explicitement **ne pas** trouvé

Toutes les plages de lignes des dix sections « Aller lire la source » et des
`official_sources` des 41 questions non-holdout vérifiées une à une : deux
fausses sur plusieurs dizaines. Extraits de code fidèles au caractère près.
Aucune fuite `CRS-001`. Aucun biais de longueur. Les 39 outcomes du lot tous
évalués hors holdout. `POOL-002` satisfaite. Aucun dépassement `REV-001`.

### Suites données le jour même

`P0-1`, `P1-1`, `P1-2` et `P2-1` corrigés, chacun vérifié contre la source
récupérée. Le cours *Content negotiation* corrigé aussi sur la formulation de
spécificité, défaut connexe signalé par la revue.

La recommandation hors barème est suivie : `Course::contentLevel` n'était
consommé par **aucune** règle. `CRS-002` le compare désormais à celui de son
item. Elle n'est pas muette — elle a trouvé **une seconde divergence** à sa
première exécution, dans le lot 01 (*Interfaces*, `MINIMAL` contre `STANDARD`),
que cette revue n'avait pas auditée. Son échec est prouvé par
`prove_framework_rules_fail.py`, qui compte désormais dix cas.

**Les `P2` et `P3` restants sont ouverts et nommés** : `WWW-Authenticate` sur
401, `getContentTypeFormat()`, collision `BinaryFileResponse`/`StreamedResponse`,
options de délai rangées sous `## Tests`, `PED-003` sur *Language detection*,
plage `lines 28-160` fausse, ancre `section-15` inexistante, ordre `ksort()` des
directives, exemple `getClientIp()` sous-spécifié, qualificatif « côté Symfony »
perdu, « types acceptés » avec `q=0`, et l'ambiguïté de `QST-6mzvc5xvgyqe`.

**Aucune sixième revue n'a vu l'état corrigé.** Le lot reste `NON VALIDÉ`.

