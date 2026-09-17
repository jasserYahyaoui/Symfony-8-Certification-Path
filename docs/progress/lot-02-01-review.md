# 02-01 — revue indépendante (RFC 9110, `OIT-7801mj6w73ky`)

**Rôle** : INDEPENDENT_REVIEWER. Je n'ai ni rédigé ce cours ni écrit ces
questions.
**Date** : 2026-09-17 · **Branche** : `content/lot-02-01-rfc9110`

## Méthode réellement suivie

Pour chaque question : énoncé et propositions lus **sans la clé** (extraction
programmatique excluant `correct` et `explanation`), résolution personnelle,
comptage des bonnes réponses, vérification contre la source primaire, **puis
seulement** comparaison avec la clé.

Sources récupérées par `curl` une fois chacune, puis exploitées localement :

| Fichier | Usage |
|---|---|
| `specs/rfc9110.html` | en-tête du document, résumé, §1.4, §3.1, §5.1, §6, §6.1, §6.2, §12.5.5 |
| `specs/rfc9111.html` | champ `Obsoletes:`, §4.1 *Calculating Cache Keys with the Vary Header Field* |
| `specs/rfc9112.html` | champ `Obsoletes:`, §3 *Request Line*, §7 *Transfer Codings*, §7.1 *Chunked Transfer Coding* |
| `specs/rfc9113.html` | champ `Obsoletes:` seul, pour trancher « qui obsolète 7540 » |

Aucune question `pool: HOLDOUT` n'a été ouverte, ni
`content/questions/mock-04-holdout.yml`. Les neuf questions auditées sont toutes
`pool: LEARNING` (vérifié avant lecture).

## Ce que dit le document, vérifié et non récité

Ces constats servent de référence à toutes les sections qui suivent.

- **Champ `Obsoletes:` de l'en-tête** : `2818, 7230, 7231, 7232, 7233, 7235,
  7538, 7615, 7694`. **7230 y figure sans réserve**, en toutes lettres.
- **Champ `Updates:` de l'en-tête** : `3864`. 3864 est donc **mis à jour**, pas
  obsolété.
- **Résumé (abstract)** : « This document updates RFC 3864 and obsoletes RFCs
  2818, 7231, 7232, 7233, 7235, 7538, 7615, 7694, **and portions of 7230**. »
  Le résumé et l'en-tête ne disent donc **pas la même chose** de 7230.
- **§1.4, Table 1** : neuf lignes ; **seule** la ligne `[RFC7230]` porte `[*]`,
  dont la note est « This document only obsoletes the portions of RFC 7230 that
  are independent of the HTTP/1.1 messaging syntax and connection management;
  the remaining bits of RFC 7230 are obsoleted by "HTTP/1.1" ».
- **RFC 9111** : `Obsoletes: 7234`. **RFC 9112** : `Obsoletes: 7230`.
  **RFC 9113** : `Obsoletes: 7540, 8740`.
- **`chunked`** : spécifié en **RFC 9112 §7.1**, sous §7 *Transfer Codings*.
  RFC 9110 ne fait que s'y référer (§6.4, renvoi à « Section 6 of [HTTP/1.1] »).
- **RFC 9112 §3** *Request Line* : `request-line = method SP request-target SP
  HTTP-version`.
- **RFC 9110 §6.2** *Control Data* : « In HTTP/1.1 and earlier, control data is
  sent as the first line of a message. **In HTTP/2 and HTTP/3, control data is
  sent as pseudo-header fields** with a reserved name prefix ». HTTP/2 et HTTP/3
  n'ont donc **aucune ligne de requête**.
- **RFC 9110 §12.5.5** *Vary* : « The "Vary" header field in a response
  **describes what parts of a request message** [...] **might have influenced**
  the origin server's process for selecting the content of this response. »
  Deux fonctions énoncées : élargir la clé de cache (« Vary expands the cache
  key required to match a new request to the stored cache entry ») et signaler à
  l'agent utilisateur qu'il y a eu négociation. Le champ **décrit** la sélection,
  il ne l'**opère** pas.
- **RFC 9110 §6.1** est intitulée *Framing and Completeness* et pose des
  exigences sur le cadrage, tout en renvoyant sa **syntaxe** à chaque version :
  « Each major version of HTTP defines its own framing mechanism. »

## Synthèse

| Question | Statut | Ma réponse | Clé | Accord |
|---|---|---|---|---|
| QST-mfj1a2ba5h07 | `APPROVED` | CHO-nqcsknvst6bz | idem | oui |
| QST-0vv6vvfv4han | `APPROVED_WITH_MINOR_FIXES` | CHO-6a4wjdv74ytt | idem | oui |
| QST-tcvpfdg7b3j7 | `APPROVED` | CHO-j259jqym8tmk | idem | oui |
| QST-s5dq5r2mg18c | `APPROVED` | CHO-zr724z8pgd4k | idem | oui |
| QST-c8phy0j4j0fq | `APPROVED_WITH_MINOR_FIXES` | CHO-p46de72r8451 | idem | oui |
| QST-43wvxedwe7wz | `FIX_REQUIRED` | CHO-pgjrzas4as8t | idem | oui |
| Cours `CRS-depbnbc0g82x` | `APPROVED_WITH_MINOR_FIXES` | — | — | — |

**Les six clés sont exactes.** Je n'ai trouvé aucune réponse fausse, aucune
question à double solution, aucun doublon, aucun hors-sujet. Les six ont
`required_answer_count: 1` et **exactement une** proposition `correct: true`
(compté, pas supposé). Aucun défaut relevé ci-dessous ne porte sur une clé : ils
portent sur une citation, deux formulations et deux manques du cours.

---

## QST-mfj1a2ba5h07 — `APPROVED`

**Ma réponse, avant la clé** : CHO-nqcsknvst6bz, « Only portions of it, the rest
passing to RFC 9112 ». Une seule bonne réponse.

**Preuve** : résumé de RFC 9110, « and portions of 7230 » ; §1.4 Table 1, note
`[*]` renvoyant le reste à RFC 9112, dont l'en-tête porte `Obsoletes: 7230`.

**Point remarquable, à ne pas « corriger »** : l'énoncé dit « **in its own
abstract** ». Ce cadrage n'est pas un ornement, il est **nécessaire** : le champ
`Obsoletes:` de l'en-tête, lui, liste 7230 sans réserve. Posée sans cette
restriction, la question aurait deux lectures défendables. Elle est donc juste
*parce que* l'énoncé est précis. Toute reformulation qui supprimerait « in its
own abstract » rendrait la question ambiguë.

Les trois explications de distracteurs sont exactes, y compris « 7234 is absent
from 9110's list. RFC 9111 carries "Obsoletes 7234" itself » (vérifié dans
l'en-tête de RFC 9111). Difficulté `medium`, `UNDERSTAND` / `DISTINGUISH` /
`CONCEPT_DISTINCTION` : cohérent, la discrimination porte sur *partiel vs
entier*. Citation `Abstract, and section 1.4 Specifications Obsoleted by This
Document` : titre de section exact.

## QST-0vv6vvfv4han — `APPROVED_WITH_MINOR_FIXES`

**Ma réponse, avant la clé** : CHO-6a4wjdv74ytt, « RFC 2818, HTTP Over TLS ».
Une seule bonne réponse.

**Preuve** : champ `Obsoletes:` de l'en-tête (2818 présent) ; champ `Updates:`
(3864, donc **pas** obsolété) ; en-tête de RFC 9113 (`Obsoletes: 7540, 8740`) ;
absence totale de 2616 du champ `Obsoletes:` de 9110.

Les trois explications de distracteurs sont exactes et chacune est vérifiée
séparément. Celle de 3864 — « 9110 UPDATES 3864; updating and obsoleting are two
different things » — est le meilleur distracteur des six questions.

**Défaut mineur 1 — énoncé imprécis.** « Besides the 723x series, RFC 9110's
obsoletes list carries **one** older RFC ». Déduction faite des 723x
(7230–7235), la liste en porte **quatre** : 2818, 7538, 7615 et 7694. Seul
l'adjectif « older » discrimine, et aucun distracteur n'offre 7538 / 7615 /
7694 : la question reste **répondable et non ambiguë** telle quelle. Elle est
seulement plus lâche qu'elle n'en a l'air.

*Correction exacte proposée* (énoncé) :
> `Besides the 723x series, RFC 9110's obsoletes list carries one RFC predating
> that series, which candidates rarely expect. Which one?`

**Défaut mineur 2 — preuve insuffisante côté cours** : voir la section *Cours*,
manque 2. Le cours ne mentionne nulle part `Updates: 3864` ; un apprenant qui
n'aurait lu que ce cours n'a **aucun moyen** d'écarter le distracteur 3864
autrement qu'au flair. La correction tient en une phrase, et elle est à porter
au cours, pas à la question.

## QST-tcvpfdg7b3j7 — `APPROVED`

**Ma réponse, avant la clé** : CHO-j259jqym8tmk, « In RFC 9112, as one framing
of the control data ». Une seule bonne réponse.

**Preuve** : RFC 9112 §3 *Request Line* ; RFC 9110 §6.2 *Control Data* pour la
raison (« In HTTP/1.1 and earlier, control data is sent as the first line »).

Le distracteur CHO-869t5r16v4wb (« Identically in 9112, 9113 and 9114 ») est
réfuté mot pour mot par §6.2 : HTTP/2 et HTTP/3 emploient des pseudo-en-têtes et
n'ont pas de ligne de requête. Son explication le dit exactement. Citation
`section 6 Message Abstraction, and section 6.2 Control Data` : les deux titres
sont exacts. Métadonnées cohérentes.

## QST-s5dq5r2mg18c — `APPROVED`

**Ma réponse, avant la clé** : CHO-zr724z8pgd4k, « The 723x series replaced 2616
first ». Une seule bonne réponse.

**Preuve** : RFC 2616 a été obsolétée par RFC 7230–7235 (2014) ; le champ
`Obsoletes:` de RFC 9110 ne contient **pas** 2616 — vérifié caractère par
caractère sur l'en-tête.

C'est ce dernier point qui rend le distracteur CHO-08fp24ytwt2g (« Nothing is
wrong; 2616 appears in 9110's obsoletes field ») réellement faux et non
seulement discutable : le piège est légitime. Les quatre explications sont
exactes. Cohérent avec le passage « Pièges d'examen » du cours.

## QST-c8phy0j4j0fq — `APPROVED_WITH_MINOR_FIXES`

**Ma réponse, avant la clé** : CHO-p46de72r8451, « RFC 9112, with the rest of
HTTP/1.1 framing ». Une seule bonne réponse.

**Preuve** : RFC 9112 §7 *Transfer Codings*, §7.1 *Chunked Transfer Coding*. La
citation `section 7 Transfer Codings` sur `rfc9112.html` est **exacte** (titre et
numéro vérifiés dans la table des matières et dans le corps).

**Défaut mineur — une explication contredit la précision du cours.**
L'explication de CHO-bc9422sam6fw affirme :

> « 9110 deliberately specifies **no framing at all**; that is what makes it
> version-independent. »

C'est trop fort. RFC 9110 **§6.1 s'intitule *Framing and Completeness*** et pose
des exigences de cadrage (quand un message est complet, tolérance du cadrage
implicite en HTTP/1.1) ; ce qu'elle ne définit pas, c'est une **syntaxe** de
cadrage — « Each major version of HTTP defines its own framing mechanism ». Le
cours, lui, est correctement nuancé : « RFC 9110 ne décrit aucune **syntaxe** de
trame. » L'explication est donc **moins exacte que le cours qu'elle accompagne**,
et un apprenant attentif qui ouvre §6.1 y verra une contradiction.

*Correction exacte proposée* (explication de CHO-bc9422sam6fw) :
> `9110 defines no framing syntax: §6.1 states that each major version of HTTP
> defines its own framing mechanism. That is what makes it version-independent.`

## QST-43wvxedwe7wz — `FIX_REQUIRED`

**Ma réponse, avant la clé** : CHO-pgjrzas4as8t, « A cache stores
representations, not resources ». Une seule bonne réponse.

**Preuve** : RFC 9110 §12.5.5 — Vary « expands the cache key required to match a
new request to the stored cache entry » ; RFC 9111 §4.1 — le cache « MUST NOT use
that stored response without revalidation unless all the presented request header
fields nominated by that Vary field value match those fields in the original
request ». L'entrée stockée est une représentation ; la clé est l'URI, qui
identifie la ressource ; d'où la nécessité d'élargir la clé.

Les trois distracteurs sont faux et bien choisis, en particulier
CHO-93g0rnd1567d (« Vary is the field by which the server picks the
representation »), qui est exactement la confusion que §12.5.5 dissipe : le champ
**décrit** ce qui a influencé la sélection, il ne la réalise pas. La question est
non ambiguë et n'est **pas** un doublon (voir plus bas).

**Défaut bloquant — la citation désigne les mauvaises sections.** Le champ
`official_sources[0].symbol_or_lines` porte :

> `section 3.2 Resources, section 3.3 Representations, section 12.5.5 Vary`

Or, dans RFC 9110 : **§3.1 = *Resources***, **§3.2 = *Representations***,
**§3.3 = *Connections, Clients, and Servers***. Deux des trois renvois sont
décalés d'un rang, et §3.3 envoie l'apprenant sur un sujet sans rapport. Seul
`§12.5.5 Vary` est juste. C'est la preuve d'une affirmation officielle : elle
doit pointer le texte qui a servi à la vérifier.

*Correction exacte* (`content/questions/lot-02-http.yml`, QST-43wvxedwe7wz) :

```yaml
    symbol_or_lines: "section 3.1 Resources, section 3.2 Representations, section 12.5.5 Vary"
```

**Nuance non bloquante** : l'explication de CHO-93g0rnd1567d dit « Vary reports,
afterwards, which ones it **used** », là où §12.5.5 écrit « **might have**
influenced ». La RFC couvre délibérément le cas où le serveur nomme un champ
qu'il n'a finalement pas exploité. Simplification pédagogique acceptable ; je ne
demande pas de changement.

---

## Cours `CRS-depbnbc0g82x.md` — `APPROVED_WITH_MINOR_FIXES`

**Aucune affirmation fausse.** J'ai repris chaque assertion contre le document :

| Affirmation du cours | Vérification |
|---|---|
| Liste du résumé, citée entre guillemets | **verbatim exact**, y compris « des parties de 7230 » |
| « La Table 1 du §1.4 marque cette ligne d'un astérisque, **seule de son tableau** » | exact : `[*]` sur `[RFC7230]` et sur aucune autre des neuf lignes |
| « 7234 n'y figure pas du tout [...] reprise par RFC 9111 » | exact : absente du champ `Obsoletes:` de 9110 ; `Obsoletes: 7234` dans l'en-tête de 9111 |
| « 2818 y figure [...] *HTTP Over TLS* » | exact, et §4.2.2 / annexe B.1 confirment la reprise |
| Tableau 9110 / 9111 / 9112 / 9113 / 9114 | exact |
| « Message : données de contrôle, champs d'en-tête, contenu, champs de fin » | §6, formulation exacte |
| « la "ligne de départ" est la forme HTTP/1.1, donc RFC 9112 » | §6.2, exact |
| « Ressource : la cible identifiée par un URI » | §3.1, « The target of an HTTP request is called a resource » |
| « nom insensible à la casse » | §5.1, « Field names are case-insensitive » |
| « RFC 9110 ne décrit aucune **syntaxe** de trame » | exact, et correctement nuancé (cf. §6.1) |
| « 2616 a d'abord été éclatée en 7230-7235 » | exact |

**Manque 1 — le cours ne prévient pas du désaccord entre le résumé et
l'en-tête.** Le cours écrit « Son propre résumé énonce la liste » puis donne la
liste du résumé : c'est honnête et correctement attribué. Mais il ne dit nulle
part que le **champ `Obsoletes:` de l'en-tête liste 7230 sans réserve**.
L'apprenant qui ouvre la source — ce que le cours l'invite à faire — croira le
cours en défaut. C'est aussi ce qui oblige QST-mfj1a2ba5h07 à se restreindre au
résumé. Une phrase suffit, à placer après le paragraphe « 7230 n'est obsolétée
qu'en partie » :

> L'en-tête du document, lui, écrit simplement `Obsoletes: 2818, 7230, 7231,
> …` : c'est le résumé et la Table 1 qui portent la réserve, pas le champ.

**Manque 2 — `Updates: 3864` est absent du cours.** L'en-tête porte
`Updates: 3864`, et *obsolète* n'est pas *met à jour*. QST-0vv6vvfv4han fait
reposer son distracteur le plus fort sur cette distinction, que le cours
n'enseigne pas : la preuve est insuffisante pour cette question. À ajouter au
même endroit :

> Le même en-tête porte `Updates: 3864` : 3864 est **mise à jour**, pas
> obsolétée — les deux champs sont distincts.

**Manque 3 — citation de tête devenue partiellement caduque.** Le front matter
déclare encore :

```yaml
    symbol_or_lines: "sections 3 Terminology, 6 Message Abstraction, 9 Methods, 15 Status Codes"
```

Or la branche a **retiré** du cours tout le développement §9.2 (sûre /
idempotente), désormais évalué sous l'item *HTTP methods*, et les codes de statut
n'y apparaissent plus que dans une cellule de tableau. La citation annonce donc
deux sections que le cours n'enseigne plus, et **omet** §1.4, sur laquelle repose
désormais sa section la plus longue. Par ailleurs §3 s'intitule *Terminology
**and Core Concepts***.

*Correction exacte proposée* :

```yaml
    symbol_or_lines: "section 1.4 Specifications Obsoleted by This Document, section 3 Terminology and Core Concepts, section 5.1 Field Names, section 6 Message Abstraction"
```

**Sur `CRS-001`** : le cours énonce « Le codage `chunked` ou la ligne de requête
relèvent de RFC 9112 », ce qui donne la réponse de QST-c8phy0j4j0fq et de
QST-tcvpfdg7b3j7. Ces deux questions portent sur **le même item** que le cours :
c'est le cours qui enseigne son propre item, pas une fuite. Rien à corriger. À
noter tout de même que ces deux questions tirent toute leur assise d'**une seule
phrase** du cours.

## Doublons — aucun

- **QST-c8phy0j4j0fq vs QST-ktj89z3z3d5c** : toutes deux « quelle RFC fait X »
  sur la famille 911x, mais le fait visé diffère (cadrage `chunked` → 9112 vs
  cache → 9111) et les clés sont différentes. Pas un doublon.
- **QST-43wvxedwe7wz vs QST-s1btydv64pas** : surface narrative proche (une URI,
  JSON et HTML, français et anglais). La proposition testée diffère pourtant :
  `s1btydv64pas` teste la **distinction ressource / représentation** elle-même
  (`OUT-y8177jvv3m21`) ; `43wvxedwe7wz` teste **pourquoi un cache partagé exige
  `Vary`** (`OUT-5qj9w53751fy`), c'est-à-dire l'élargissement de la clé de cache.
  La seconde est la suite de la première — l'explication de `s1btydv64pas` se
  termine d'ailleurs sur « that separation is exactly what makes content
  negotiation and Vary meaningful ». **Pas un doublon**, mais la parenté de
  formulation mérite d'être connue si l'une des deux est un jour réécrite.
- **QST-tcvpfdg7b3j7 vs QST-tdqxjjpwv51c** : sujets distincts (où est spécifiée
  la ligne de requête vs invariance sémantique des codes de statut).

## Hors-sujet, difficulté, métadonnées

- **Hors-sujet** : aucun. Les six portent sur le positionnement de RFC 9110 et
  son vocabulaire, ce qui est l'item.
- **`assesses_outcomes`** : les six pointent des `OUT` déclarés au matrice pour
  `OIT-7801mj6w73ky` (`OUT-b4zep1k4shd7` ×3, `OUT-rvm0f266br2d` ×2,
  `OUT-5qj9w53751fy` ×1). Aucun outcome inventé.
- **`question_archetype` / `exam_skill`** : cohérents sur les six. Les deux
  `RECOGNIZE` portent `DEFINITION_RECALL`, les trois `DISTINGUISH` portent
  `CONCEPT_DISTINCTION`, et la seule mise en situation porte `SCENARIO_CHOICE`.
- **Difficulté** : `hard` sur QST-0vv6vvfv4han (rappel de 2818 + piège
  obsolete/update) et sur QST-43wvxedwe7wz (raisonnement en deux temps) :
  justifié. Les quatre `medium` le sont aussi.
- **`negative_wording: false`** partout : conforme à l'usage du dépôt, où le
  drapeau n'est levé que sur les énoncés d'exclusion (une seule occurrence dans
  tout le corpus).
- **Biais de longueur** : sur les six, la bonne réponse n'est la plus longue
  **aucune fois** ; deux fois elle est la plus courte.
- **Langue** : les six sont `language: en`, alors que le cours est `language:
  fr` et que `QST-tdqxjjpwv51c`, sur le même item, est en français. Le mélange
  préexiste à cette branche et relève d'une décision de lot, pas de cette revue.

## Portes exécutées pour cette revue (lecture seule)

| Commande | Résultat réel |
|---|---|
| `php bin/cert validate` | 24 règles, 727 questions, **0 bloquant** ; 1 avertissement `PED-003` portant sur 8 items, dont `OIT-7801mj6w73ky` ne fait pas partie (6 outcomes, 9 questions) |
| `tools/audit/aud02_version_contamination.py` | `FINDINGS: 0` — la nouvelle citation `rfc9112.html` est couverte par `SRC-RFC9110` (même dépôt, même ref `httpwg/httpwg.github.io@master`) |
| `tools/audit/aud05_question_bank.py` | `FINDINGS: 0` |
| `tools/audit/aud10_answer_length_bias.py` | `FINDINGS: 0` — lot-02 à **16,0 %** (8/50), P = 0,9547 |
| `tools/audit/aud11_length_cue_triage.py` | `FINDINGS: 0` — lot-02 à 7,4 % (4/54) |

`POOL-002` ne s'applique pas ici : l'item est `content_level: MINIMAL`.

## Ce que cette revue ne couvre pas

Elle ne juge que le cours et les neuf questions nommés. Elle ne dit rien des
autres items du lot 02, ni de la pertinence du découpage des outcomes, ni des
questions HOLDOUT — non ouvertes, par construction.

## Prochaine action

Appliquer les trois corrections, dans cet ordre de priorité :

1. **bloquant** — `symbol_or_lines` de QST-43wvxedwe7wz (§3.2/§3.3 → §3.1/§3.2) ;
2. mineur — explication de CHO-bc9422sam6fw (QST-c8phy0j4j0fq) ;
3. mineur — les deux phrases manquantes du cours (`Obsoletes:` de l'en-tête,
   `Updates: 3864`), le `symbol_or_lines` du front matter, et l'énoncé de
   QST-0vv6vvfv4han.

Puis `php bin/cert validate`, `php bin/cert coverage`, et l'ensemble des audits
avant le push — la correction 3 touche une citation de cours, ce que
`composer gate-full` ne contrôle pas.

Les six clés étant exactes, **aucune réécriture de question n'est nécessaire** :
les corrections portent sur une citation, une explication, un énoncé et le cours.
