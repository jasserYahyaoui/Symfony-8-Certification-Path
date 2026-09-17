# Raffinement pédagogique — Lot 02 (HTTP)

Journal de reprise pour la mission ouverte le 2026-09-17 : approfondir les
**pages de cours** existantes — pièges d'examen, subtilités entre notions
voisines, comportements implicites, flashcards en nombre, accès aux QCM depuis
chaque page. Un lot à la fois, dans l'ordre numérique. Le lot 01 est hors
périmètre sur instruction explicite.

## Contrainte qui structure tout le travail

La règle `REV-001` plafonne le **corps** d'une page (hors front matter) :
MINIMAL 700 mots, STANDARD 900, DEEP 1200. Six des dix pages du lot 02 étaient
déjà à moins de 100 mots de leur plafond au début de la mission :

| Page | Niveau | Mots au 2026-09-17 | Plafond | Marge |
|---|---|---|---|---|
| Cookies | STANDARD | 897 | 900 | 3 |
| Caching | STANDARD | 885 | 900 | 15 |
| HTTP request | DEEP | 1124 | 1200 | 76 |
| HTTP Specification (RFC 9110) | MINIMAL | 615 | 700 | 85 |
| HTTP response | STANDARD | 729 | 900 | 171 |

Le brief demande 1500 à 3000 mots par page : c'est **arithmétiquement
incompatible** avec `REV-001`. La règle n'est pas assouplie pour autant — elle a
déjà été recalibrée une fois, par [ADR-0008](../adr/0008-revision-budget-recalibration.md),
et l'assouplir une seconde fois pour tenir un objectif de volume reviendrait à
faire du nombre de mots une mesure de progression, ce que le plan interdit.

**Le volume va donc là où la règle ne mord pas** : les flashcards et les
questions sont des entités distinctes, sans plafond. C'est aussi là que le
manque était le plus net — **11 flashcards pour les dix items du lot** au
départ (9 dans `lot-02-http.yml`, 2 dans `golden-slice.yml`), soit à peu près le
strict minimum que `FLC-002` exige. Le total est de **26** après cette page.
Dans le corps du cours, seules les formulations qui manquaient vraiment sont
ajoutées, en restant sous le plafond.

## Axe `level` sur les flashcards (ajouté le 2026-09-17)

Le brief demande des flashcards à quatre niveaux. L'axe est désormais porté par
la donnée, pas par la mise en page : `FlashcardLevel` = `RECALL`,
`UNDERSTANDING`, `APPLICATION`, `TRAP`.

- Le champ `level` est **optionnel** : les cartes écrites avant l'axe n'ont pas
  de niveau et sont rendues en tête, sans titre. Leur en attribuer un sans les
  relire aurait été une valeur inventée.
- Une valeur inconnue est une **erreur de chargement**, pas un `null` silencieux.
- `DocsGenerator` regroupe sous un `###` par niveau **non vide**, dans l'ordre
  de l'énumération.
- `tests/Unit/FlashcardLevelTest.php` couvre les quatre comportements. La
  couverture a été **prouvée non vacue** : en retirant l'émission du titre dans
  le générateur, le test échoue (`FlashcardLevelTest.php:80`) ; le fichier a été
  restauré à l'identique (SHA-256 vérifié).
- L'audit d'accessibilité n'auditait qu'une page dont le deck n'a pas de niveau,
  donc sans `###` : la page RFC 9110 a été ajoutée à sa liste, sinon la nouvelle
  structure passait le contrôle sans jamais être regardée.

## État par page (ordre de navigation)

| # | Page | Niveau | Mots / plafond | Flashcards | QCM (pool LEARNING) | Statut |
|---|---|---|---|---|---|---|
| 1 | HTTP Specification (RFC 9110) | MINIMAL | 692 / 700 | 16 | 10 | **RAFFINÉE** (2026-09-17) |
| 2 | Status codes | MINIMAL | 666 / 700 | 17 | 3 | **RAFFINÉE** (2026-09-17) |
| 3 | HTTP request | DEEP | 1183 / 1200 | 17 | 4 | **RAFFINÉE** (2026-09-17) |
| 4 | HTTP response | STANDARD | 823 / 900 | 17 | 4 | **RAFFINÉE** (2026-09-17) |
| 5 | HTTP methods | STANDARD | 750 / 900 | 17 | 3 | **RAFFINÉE** (2026-09-17) |
| 6 | Cookies | STANDARD | 900 / 900 | 17 | 5 | **RAFFINÉE** (2026-09-17) |
| 7 | Caching | STANDARD | 885 / 900 | 1 | 4 | à faire |
| 8 | Content negotiation | STANDARD | 473 / 900 | 1 | 3 | à faire |
| 9 | Language detection | MINIMAL | 274 / 700 | 1 | 2 | à faire |
| 10 | Symfony HttpClient component | STANDARD | 762 / 900 | 1 | 4 | à faire |

Chiffres réconciliés le 2026-09-17 depuis `syllabus-matrix.yml` et `content/**`
par lecture du `ContentSet` chargé, pas depuis un rapport antérieur.

## Page 1 — HTTP Specification (RFC 9110), 2026-09-17

**Fait**

- 15 flashcards ajoutées (`FLC-ne8ag8fv1beh` … `FLC-4p951zavvt5r`), réparties
  4 RECALL / 4 UNDERSTANDING / 3 APPLICATION / 4 TRAP ; la carte préexistante
  `FLC-jn7eqg7cdsqb` a reçu le niveau `TRAP`, après relecture.
- Chaque affirmation relevée dans `specs/rfc9110.html` le jour même : en-tête
  `Obsoletes:`/`Updates:`, §1.2 *History and Evolution*, §3.1 *Resources*,
  §3.2 *Representations*, §3.7 *Intermediaries*, §3.8 *Caches*, §5.1 *Field
  Names*, §5.3 *Field Order* (dont la note `Set-Cookie`), §6 *Message
  Abstraction*, §6.1 *Framing and Completeness*, §8.8.3 *ETag*, section 11
  *Normative References*.
- Deux citations de section ont été corrigées en cours d'écriture : la filiation
  2068 → 2616 → 7230-7235 est en **§1.2 *History and Evolution***, pas en §1.1
  *Purpose*, et le titre exact de §1.2 comporte « and Evolution ».
- Section `## Tips d'examen` ajoutée à la page (mnémonique 9110/9111/9112 ;
  `Obsoletes` contre `Updates` ; le réflexe de relecture devant un énoncé
  absolu). Corps : 615 → **692 mots**, sous le plafond de 700.

**Non fait, et pourquoi**

- Aucune question ajoutée : l'item en a déjà 10 en pool LEARNING, toutes écrites
  et revues les 2026-09-16/17, et la porte du cours vers ces questions existe.
  En ajouter pour le nombre serait du volume sans valeur (§1.4).
- Le corps du cours n'a pas été étendu au-delà des 8 mots de marge restants.

**Contrôles réellement exécutés le 2026-09-17**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | 24 règles, 1 avertissement `PED-003` préexistant, **0 bloquant** |
| `vendor/bin/phpunit` | **293 tests, 15 651 assertions, OK** (dont les 4 du nouveau test) |
| Preuve de non-vacuité du nouveau test | échec provoqué obtenu, fichier restauré (SHA-256 identique) |
| `composer gate-full` (relancé après le dernier changement de rendu) | **exit 0**, 29 pages auditées, `TOTAL VIOLATIONS: 0` |
| Audit a11y de la page RFC 9110 elle-même | `item page with levelled flashcards axe=0 structural=0` |
| Jeu d'audits de la CI (11 scripts) | **tous rc=0** |
| `prove_framework_rules_fail.py` | **PROOF OK — 10 cas** |
| `prove_flashcard_coverage_fails.py` | **PROOF OK** |
| `docs/revision/plan.json` + `study-calendar.md` | régénérés avec les paramètres de la CI ; diff = le seul item RFC 9110 (615 → 692 mots, 1 → 16 flashcards) et le budget du jour |

**Non exécuté** : aucun `git push`, aucune PR, aucun déploiement — l'autorisation
de pousser n'a pas été donnée. Le travail est **commité localement** sur la
branche `content/lot-02-01-rfc9110`. Aucun statut `DEPLOYED` n'est revendiqué.

## Page 2 — Status codes, 2026-09-17

**Fait**

- 15 flashcards ajoutées (`FLC-7jbsw0n0dkqt` … `FLC-d4q9k3gjvj4s`), réparties
  4 RECALL / 4 UNDERSTANDING / 4 APPLICATION / 3 TRAP ; les deux cartes
  préexistantes du deck `golden-slice` (401/403, 307/308) ont reçu leur niveau
  après relecture, et ne sont pas redoublées.
- Matière relevée le jour même dans `specs/rfc9110.html` (§15 préambule, §15.1,
  §15.2, §15.4.3, §15.4.4, §15.5.2, §15.5.6, §15.5.7, §15.5.14, §15.5.16,
  §15.5.19, §15.5.21) et dans `Response.php` de la branche 8.0 (`isCacheable`
  l. 545, `setStatusCode` l. 476, `isInvalid` l. 1164, `isEmpty` l. 1264,
  `prepare` l. 244, constantes l. 60-67, `statusTexts` l. 163-170).
- Trois écarts entre la RFC et Symfony sont désormais enseignés, chacun vérifié
  des deux côtés : la liste de `Response::isCacheable()` n'est pas la liste
  *heuristically cacheable* de §15.1 (302 en plus, 204/206/308/405/414/501 en
  moins) ; `HTTP_I_AM_A_TEAPOT` existe alors que §15.5.19 intitule 418
  « (Unused) » et le réserve ; les constantes 413 et 422 gardent leurs anciens
  noms quand `$statusTexts` a suivi les renommages de 9110.
- Section `## Tips d'examen` ajoutée. Corps : 535 → **666 mots** sur 700.

**Non fait, et pourquoi**

- Aucune question ajoutée : l'item a 4 questions pour 4 objectifs identifiés,
  soit une par objectif. Il n'est pas parmi les 8 items que `PED-003` signale.
- Aucun SHA n'est cité pour `Response.php` : le fichier a été relu sur la
  branche 8.0 le 2026-09-17 sans que le commit soit constaté, et un SHA
  reconstruit serait inventé.

**Contrôles réellement exécutés le 2026-09-17**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant** (le même avertissement `PED-003` préexistant) |
| `composer gate-full` | **exit 0** — 293 tests, 15 666 assertions ; `TOTAL VIOLATIONS: 0` |
| `aud02`, `aud04`, `aud05`, `aud07`, `aud08`, `aud09` | **rc=0** |
| `docs/revision/plan.json` + `study-calendar.md` | régénérés ; diff = le seul item Status codes |

## Page 3 — HTTP request, 2026-09-17

**Fait**

- 16 flashcards ajoutées (`FLC-c1n5fb6sdjzt` … `FLC-e3jfzd2kpmvf`), réparties
  4 RECALL / 4 UNDERSTANDING / 4 APPLICATION / 4 TRAP ; la carte préexistante
  `FLC-ke52b2c0c9vm` a reçu le niveau `APPLICATION`.
- Sources relues le jour même sur la branche 8.0 : `Request.php` (sacs publics
  l. 94-130, `getMethod` l. 1202-1239, `getRealMethod` l. 1246, `getPayload`
  l. 1539-1560, `getHost` l. 1132-1166, `setTrustedHosts` l. 642,
  `setAllowedHttpMethodOverride` l. 710), `InputBag.php` (`get` l. 37-50),
  `ParameterBag.php` (`all` l. 45-56).
- Cinq comportements implicites que la page n'enseignait pas sont désormais
  couverts, chacun lu dans le code : `all($clé)` lève la **même** exception que
  `get()` mais dans la direction opposée, et rend `[]` sur une clé absente ; un
  **défaut** non scalaire donne une `\InvalidArgumentException` et non une
  `BadRequestException` ; `getPayload()` rend un **clone** du sac POST, et lève
  une `JsonException` sur un JSON valide qui ne décode pas vers un tableau ;
  un override vers GET/HEAD/CONNECT/TRACE est ignoré en silence, et une valeur
  non alphabétique lève une `SuspiciousOperationException` ; `getHost()` ne lève
  qu'**une fois** puis rend la chaîne vide.
- Section `## Tips d'examen` ajoutée. Corps : 1124 → **1183 mots** sur 1200.

**Non fait, et pourquoi**

- Aucune question ajoutée : les 5 objectifs identifiés sont couverts par
  6 questions, dont une holdout et une VALIDATION.

**Contrôles réellement exécutés le 2026-09-17**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant** |
| `composer gate-full` | **exit 0** — 293 tests, 15 682 assertions ; `TOTAL VIOLATIONS: 0` |
| `aud02`, `aud03 --offline`, `aud04`, `aud05`, `aud07`, `aud08`, `aud09`, `aud10` | **rc=0** |
| `prove_flashcard_coverage_fails.py` | **rc=0** |
| `docs/revision/plan.json` + `study-calendar.md` | régénérés |

**Défaut corrigé en cours de route** : quatre cartes portaient des séquences
sur-échappées (`\\Stringable`, `\"array\"`) issues du heredoc d'écriture, qui
seraient parties telles quelles sur la page. Relues après analyse YAML, pas à
l'œil.

## Page 4 — HTTP response, 2026-09-17

**Fait**

- 16 flashcards ajoutées (`FLC-keg9pm2d1bcq` … `FLC-rpe8vx2qy4gx`), réparties
  4 RECALL / 4 UNDERSTANDING / 4 APPLICATION / 4 TRAP ; la carte préexistante
  `FLC-c3jxj5v9jt5n` (isRedirect / isRedirection) a reçu le niveau `TRAP`.
- Sources relues le jour même sur la branche 8.0 : `Response.php` (`prepare`,
  `sendHeaders`), `JsonResponse.php` (`DEFAULT_ENCODING_OPTIONS` l. 30-34,
  `__construct` l. 39-50, `update` l. 167-187, `setEncodingOptions`),
  `RedirectResponse.php` (`__construct` l. 35-48, `setTargetUrl` l. 64-90),
  `StreamedResponse.php` (`sendContent` l. 113-128, `setContent` l. 132-143,
  `getContent` l. 145-148), `ResponseHeaderBag.php`.
- Cinq comportements que la page n'enseignait pas, chacun lu dans le code :
  `JsonResponse` échappe `<`, `>`, `'`, `&` et le guillemet droit, donc sa
  sortie n'est pas celle de `json_encode()` ; `new JsonResponse()` produit `{}`
  et non `null`, par une raison de sécurité que la classe cite (OWASP) ;
  `RedirectResponse` écrit un **corps HTML complet** avec un `meta refresh`, et
  retire `cache-control` sur un 301 non explicitement configuré ;
  `StreamedResponse::getContent()` renvoie **`false`** et son `setContent()`
  non nul lève ; `fromJsonString()` sur un tableau lève une `\TypeError`.
- Section `## Tips d'examen` ajoutée. Corps : 729 → **823 mots** sur 900.

**Contrôles réellement exécutés le 2026-09-17**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant** |
| `composer gate-full` | **exit 0** — 293 tests, 15 698 assertions ; `TOTAL VIOLATIONS: 0` |
| `aud02`, `aud03 --offline`, `aud04`, `aud05`, `aud07`, `aud08`, `aud09`, `aud10` | **rc=0** |
| Aiguilles de smoke test | 4 ajoutées, chacune absente de `master` et présente dans la page construite |

## Page 5 — HTTP methods, 2026-09-17

**Fait**

- 16 flashcards ajoutées (`FLC-2sq3a5cdq266` … `FLC-p3vmjc4wdfzy`), réparties
  4 RECALL / 4 UNDERSTANDING / 4 APPLICATION / 4 TRAP ; la carte préexistante
  `FLC-fwh5svxege2c` a reçu le niveau `RECALL`.
- Sources relues le jour même : RFC 9110 §9.1, §9.2.1, §9.2.2, §9.2.3, §9.3.2,
  §9.3.4, §9.3.5, §9.3.7, §9.3.8 ; `Request.php` de la branche 8.0
  (`isMethodSafe` l. 1444, `isMethodIdempotent` l. 1452, `isMethodCacheable`
  l. 1462, `createFromGlobals` l. 286-300).
- Quatre faits que la page n'enseignait pas, chacun cité dans le texte source :
  le **nom d'une méthode est sensible à la casse** (§9.1) quand les noms de
  champs ne le sont pas (§5.1) ; la sûreté « does not prevent an implementation
  from including behavior that is potentially harmful » (§9.2.1) ; DELETE porte
  sur **l'association URI → fonction**, « similar to the `rm` command in UNIX »
  (§9.3.5) ; **RFC 9110 ne définit pas PATCH** — ses trois seules occurrences
  renvoient à RFC 5789, ce qui explique son absence des listes de §9.2.
- `createFromGlobals()` analyse elle-même le corps pour **PUT, DELETE, PATCH et
  QUERY** via `request_parse_body()`, avec repli silencieux sur `$_POST`.
- Section `## Tips d'examen` ajoutée. Corps : 608 → **750 mots** sur 900.

**Contrôles réellement exécutés le 2026-09-17**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant** |
| `composer gate-full` | **exit 0** — 293 tests, 15 714 assertions ; `TOTAL VIOLATIONS: 0` |
| `aud02`, `aud03 --offline`, `aud04`, `aud05`, `aud07`, `aud08`, `aud09`, `aud10` | **rc=0** |
| Aiguilles de smoke test | 4 ajoutées, chacune absente de `master` et présente dans la page construite |

## Page 6 — Cookies, 2026-09-17 — RAFFINÉE MAIS NON POUSSÉE

**Fait**

- 16 flashcards ajoutées (`FLC-bgxzd03hz55x` … `FLC-2q4760k550k3`), 4 par
  niveau ; la carte préexistante `FLC-m9c0rxyaz2dj` a reçu le niveau `TRAP`.
- **Une erreur du cours corrigée.** Le tableau des attributs rangeait
  `Domain` et `Path` ensemble sous « Restreignent la portée d'envoi ». C'est
  faux pour `Domain` : le brouillon httpbis écrit que si le serveur omet
  l'attribut, « the user agent will return the cookie only to the origin
  server », et qu'avec `Domain=site.example` le cookie part aussi vers
  `www.site.example` et `www.corp.site.example`. `Domain` **élargit** ; c'est
  son absence qui restreint. La ligne du tableau et un point clé ont été
  réécrits, et une carte porte la citation.
- Autres faits lus dans `Cookie.php` et `ResponseHeaderBag.php` : `clearCookie()`
  a **ses propres défauts** (`secure = false`, `sameSite = null`), différents de
  ceux de `create()` ; une valeur vide sérialise `nom=deleted; …; Max-Age=0` ;
  `withExpires(-1)` produit un cookie de **session** et non un cookie expiré,
  parce que `expiresTimestamp()` ramène tout non-positif à `0` ; `getMaxAge()`
  ne descend jamais sous `0`, donc seul `isCleared()` distingue les deux états.
- Une carte que j'allais écrire a été abandonnée : elle redoublait
  `FLC-m9c0rxyaz2dj` (SameSite=none impose Secure). Son identifiant minté porte
  la carte `Domain` à la place.
- Corps : 897 → **900 mots sur 900**. La correction coûtait plus que les 3 mots
  de marge ; quatre formulations redondantes ont été resserrées pour la payer.
  **La page est exactement au plafond : toute addition future devra libérer des
  mots d'abord.**

**Contrôles réellement exécutés le 2026-09-17**

| Contrôle | Résultat |
|---|---|
| `php bin/cert validate` | **0 bloquant** (à 902 mots, `REV-001` a bloqué ; réduit à 900) |
| `composer gate-full` | **exit 0** — 293 tests, 15 730 assertions ; `TOTAL VIOLATIONS: 0` |
| Aiguilles de smoke test | 4 ajoutées, vérifiées présentes dans la page construite et absentes de `master` |

### BLOQUANT — le plan de révision ne se régénère plus

`python3 tools/revision/build_roadmap.py --start 2026-10-01 --exam 2026-12-15
--max-new 4 --weekday 120 --weekend 180` sort en **erreur** :

```text
Pas de place : 4 mocks à caser entre 2026-11-30 et 2026-12-12, 2 jours de
week-end disponibles. Avancer la fin des lots (--max-new plus haut) ou reculer
la date d examen.
```

Mesuré, pas supposé : à `master` (pages 1 à 5) la même commande sort en `0` ;
avec les 16 cartes de la page 6, elle échoue. Les flashcards ajoutent du temps
de révision, la dernière introduction d'item recule, et la fenêtre des quatre
mocks se referme.

La CI exécute cette commande **avec ces paramètres exacts**, donc la page 6 ne
peut pas passer la CI en l'état. Les trois remèdes que l'outil ou le contexte
autorisent :

1. `--max-new 5` — vérifié : sort en `0`. Le plan introduit jusqu'à 5 items
   par jour au lieu de 4, à charge horaire quasi identique (88,2 h contre
   87,3 h).
2. Commencer le plan avant le 2026-10-01 — nous sommes le 2026-09-17, il y a
   donc onze jours disponibles en amont.
3. Réduire le nombre de flashcards — **écarté** : ce serait rétrécir la
   commande pour satisfaire l'outil de planification.

**Tranché le 2026-09-17 par le propriétaire : journées plus longues.** Les
budgets passent de 120/180 à **140/200 minutes**, le départ reste au
2026-10-01 et `--max-new` reste à 4. Vérifié après application : le générateur
sort en `0`, 76 jours planifiés, 436 sessions, 87,6 h hors mocks, les cinq mocks
placés et le dernier jour au 2026-12-15.

Trois fichiers portaient les anciens paramètres et ont été mis à jour ensemble,
faute de quoi la documentation aurait enseigné une commande ne produisant plus
le plan commité : `.github/workflows/ci.yml`, `docs/revision/study-roadmap.md`
et `docs/revision/exam-readiness.md`. Le remède retenu et les deux écartés sont
consignés dans `study-roadmap.md`, à côté de la commande.

## Déploiement des pages 1 à 3 — 2026-09-17

| Étape | Preuve |
|---|---|
| Push | `5ba2aba..a0fc246` sur `content/lot-02-01-rfc9110` |
| Pull Request | [#159](https://github.com/jasserYahyaoui/Symfony-8-Certification-Path/pull/159) |
| CI « Technical gate » | `success`, run `35247964411`, 16:39:50 → 16:44:27 UTC |
| Merge | commit `2dc3e57e8fab58692e2f2715de3de454d61e3db1` sur `master` |
| Déploiement Pages | run `35248663136`, job `Deploy` **success**, 16:48:17 → 16:48:22 UTC |
| Smoke test de production | job `105295839833` **success**, 16:48:34 → 16:48:44 UTC |

Ligne décisive du smoke test, recopiée du journal du run :

```text
ok  lot-02  the three refined pages carry their levelled flashcards and exam tips
```

Les huit aiguilles ont donc été trouvées **dans les octets servis par
`https://jasseryahyaoui.github.io/Symfony-8-Certification-Path`**, pas dans un
build local. Les trois pages raffinées servent 200, portent les titres de
niveau `Mémorisation` et `Pièges`, le corps d'une carte et le texte des tips.

Statut des pages 1 à 3 : **DEPLOYED**, au sens de §16 — merge, build, deploy et
smoke test de production, chacun avec sa sortie réelle.

## Prochaine action

Trancher le blocage du plan de révision ci-dessus, puis pousser la page 6 et
enchaîner sur la page 7 — **Caching** (STANDARD, 885 / 900 mots, 1 flashcard,
15 mots de marge).
