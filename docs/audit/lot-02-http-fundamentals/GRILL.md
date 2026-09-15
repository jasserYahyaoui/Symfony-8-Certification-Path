# Lot 02 — challenge de type examen

## Grill Me : indisponible

Vérifié, pas supposé :

| Vérification | Résultat |
|---|---|
| `ListPlugins` | **aucun plugin activé** dans cette session |
| `SearchSkills` (`grill me`, `quiz`, `challenge`, `exam questions`, `certification`) | **0 résultat** |
| `SearchPlugins` (mêmes mots-clés) | 10 plugins du catalogue, **aucun Grill Me** |

**Grill Me n'a donc pas été utilisé, et rien ici ne prétend en provenir.** Repli
appliqué, conforme à la consigne : un **agent examinateur indépendant**, lancé en
sous-agent, en lecture seule, qui n'avait pas participé à la rédaction.

## Protocole

L'examinateur a reçu la liste des 10 cours canoniques et l'ordre de : lire
intégralement, rédiger **25 questions difficiles** de type certification,
classer chacune `COUVERT` / `PARTIEL` / `NON COUVERT` selon ce que le lot 02
permet de répondre, et vérifier chaque fait contre les sources officielles 8.0
récupérées par `curl`. Interdiction d'écrire dans le dépôt.

Durée réelle : ~9 minutes, 56 appels d'outils.

## Résultat brut, avant correction

| Classement | Nombre |
|---|---:|
| `COUVERT` | **10** |
| `PARTIEL` | **4** |
| `NON COUVERT` | **11** |

Soit **40 %** de couverture ferme, 56 % en acceptant les partielles.

Et surtout : **deux erreurs factuelles** dans les cours, dont une que j'avais
moi-même introduite quelques minutes plus tôt.

## Les deux erreurs

### E-1 — `getClientIp()` ne renvoie pas « la plus à gauche » · **P0** · corrigée

J'avais écrit, en gras, dans les *Pièges d'examen* : « Avec trusted proxies,
c'est la valeur **la plus à gauche** de `X-Forwarded-For` qui est renvoyée ».
**C'est faux.** `normalizeAndFilterClientIps()` (`Request.php` l. 2146-2183)
complète la chaîne avec `REMOTE_ADDR`, retire les proxys de confiance, puis
**renverse** : `return $clientIps ? array_reverse($clientIps) : [$firstTrustedIp];`
`getClientIps()[0]` est donc l'adresse non fiable **la plus proche du serveur**.
Le docblock de `getClientIps()` le dit : « the most trusted IP address is first
… The "real" client IP address is the last one ».

Trois aggravations :

1. **C'est une erreur de sécurité**, pas seulement d'examen : fonder une
   allow-list sur la lecture inverse revient à faire confiance à une valeur que
   le client contrôle.
2. **Mon exemple donnait le bon résultat par coïncidence.** Avec une seule
   adresse non fiable restante, `array_reverse()` est l'identité. La règle était
   fausse et illustrée par le seul cas où cela ne se voit pas.
3. Je l'avais mise **en gras dans les pièges** — l'endroit précis où le lecteur
   cesse de vérifier.

Vérifiée par moi-même dans la source avant correction, sans croire l'examinateur
sur parole.

### E-2 — RFC 9110 n'obsolète pas RFC 7234 · **P1** · corrigée

Le cours écrivait « remplace les anciennes RFC 7230 à 7235 ». L'intervalle
inclut 7234 (*Caching*), que 9110 ne remplace pas — et le tableau du cours
lui-même attribuait déjà le cache à 9111. En-têtes des deux RFC, relevés
directement :

- RFC 9110 — `Obsoletes: 2818, 7230, 7231, 7232, 7233, 7235, 7538, 7615, 7694`
- RFC 9111 — `Obsoletes: 7234`

## Lacunes traitées

| # | Sujet | Cours | Traitement |
|---|---|---|---|
| Q10 | le constructeur de `RedirectResponse` valide son statut (304 refusé, 201 accepté) | HTTP response | **ajouté** |
| Q18 | le `Cache-Control` par défaut est `no-cache, private`, pas `private` | Caching | **ajouté** |
| Q19 | `If-None-Match` a priorité ; la date n'est évaluée que s'il est absent | Caching | **ajouté** |
| Q22 | `getPreferredLanguage()` retombe sur `$locales[0]`, jamais `null` | Language detection | **ajouté** |
| Q23 | `getStatusCode()` **ne lève pas** — la phrase englobante était trompeuse | HttpClient | **corrigé** |
| Q8 | `X-HTTP-Method-Override` agit sans activation ; `_method` l'exige ; `getRealMethod()` | HTTP request | **ajouté** |
| Q11 | `new Response()` porte `HTTP/1.0` ; rôle de `prepare()` | HTTP response | **ajouté** |
| Q15 | les défauts de `fromString()` diffèrent de ceux de `create()` et ne sont pas sûrs | Cookies | **ajouté** |
| Q16 | `removeCookie()` n'efface rien chez le client | Cookies | **ajouté** |
| Q20 | `getAcceptableContentTypes()` trie sur `q` puis l'ordre d'écriture, garde les `q=0` | Content negotiation | **ajouté** |
| Q25 | `timeout` = inactivité ; `max_duration` = total (0 = illimité) ; `max_redirects = 20` | HttpClient | **ajouté** |
| Q1 | la RFC définit une sémantique de cache pour `GET`, `HEAD` **et `POST`** | HTTP methods | **ajouté** |

## Lacunes non traitées, et pourquoi

| # | Sujet | Décision |
|---|---|---|
| Q3 | sémantique de `303 See Other` | **budget**. *Status codes* est `MINIMAL`, plafond `REV-001` de 400 mots, occupé à 376. Le niveau se décide sur l'item, jamais pour faire entrer du contenu : promouvoir ici serait exactement ce que `CLAUDE.md` interdit |
| Q4 | `HTTP_PERMANENTLY_REDIRECT = 308` | **même budget**, même raison |

Ces deux-là restent **ouvertes et nommées** plutôt que d'être silencieusement
converties en promotion de niveau. Le reviewer indépendant en dispose.
