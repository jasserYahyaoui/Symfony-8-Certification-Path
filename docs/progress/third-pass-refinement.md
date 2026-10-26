# Troisième passe — les 163 cours

Décidée par le propriétaire le 2026-10-24 (« Fait aussi raffiner les cours »,
puis « Go »), le jour où le préambule des pages est sorti du budget de révision
(ADR-0011). Elle relit les **163 cours**, lots 01 à 26, déjà relus par la
première passe et, pour les lots 02 à 16, par la seconde.

Méthode, celle de la [seconde passe](second-pass-refinement.md) : chaque
affirmation est confrontée au code 8.0 ou à la source admise, **par exécution
chaque fois que c'est possible** ; une page ne change que si un défaut est
établi ; une page relue sans défaut n'est pas modifiée. Aucun niveau n'est
promu ; `REV-001` reste un plafond.

Exécutions : PHP 8.4.19 ; Symfony 8.0.15 (`tools/behavior/vendor`) et une
application FrameworkBundle 8.0.15 hors dépôt, dont les sondes des passes
précédentes ont été **rejouées** (DependencyInjection, Security, Messenger,
Console, tests fonctionnels) avant d'en lire la sortie contre le texte ;
`symfony/symfony` @`6f841c0` et `symfony-docs` @`eea05cb` pour les lectures.
Les questions HOLDOUT n'ont pas été lues.

## Résultat

| | Pages |
|---|---|
| relues | 163 |
| exactes sur le fond | 145 |
| corrigées sur le fond (cette PR) | 18 |
| touchées par la seule réécriture terminologique (#435) | 65 |

Sur le fond, **21 défauts** dans 18 pages : 7 affirmations fausses ou
généralisées (D), 5 imprécisions (P), 4 énumérations fausses (L), 4 défauts de
rédaction (R), 1 coquille. Quatre cartes suivent.

## Les corrections

| # | Page | Défaut | Preuve |
|---|---|---|---|
| D1 | *Abstract classes* | « Visibilité : `public` ou `protected` » pour une abstract class — vrai de ses methods **abstraites** seulement ; une method concrète peut être `private` | exécuté, PHP 8.4.19 |
| D2 | *Object Oriented Programming* | exemple `private readonly` commenté « Cannot modify readonly property » : depuis l'extérieur, l'erreur est d'accès ; exemple passé en `public readonly` | exécuté : « Cannot modify readonly property Money::$amount » |
| D3 | *Attributes* | « répéter un attribute est une erreur au moment de la lecture par réflexion » : `getAttributes()` rend les deux, l'erreur vient de `newInstance()` | exécuté : « Attribute "R" must not be repeated » |
| D4 | *Language detection* | « une valeur qui n'entre pas dans ce moule ressort inchangée » : les sous-étiquettes après la région sont **supprimées**, `i-` retiré | exécuté : `de-DE-1996` → `de_DE`, `es-419` → `es`, `i-cherokee` → `cherokee` ; fait central |
| D5 | *Naming conventions* (lot 03) | « `InputArgumentMode::IsArray` … cohabitent dans le même code » : l'enum n'existe pas en 8.0 — c'est l'exemple des standards | `enum_exists()` faux, `git grep` vide ; fait central ; carte `FLC-sgkfqtmc07qt` |
| D6 | *CSRF protection* | piège « l'identifier par défaut est le nom du form, pas une valeur commune » — contredit la page : Flex donne `submit` à tous les types autoconfigurés | `csrf.rst` l.425-463, `FormTypeCsrfExtension` |
| D7 | *Custom callback validators* | « `Callback` est une constraint de class » : `getTargets()` rend class **et** property ; sur une property, le callback reçoit la valeur | exécuté ; fait central ; carte `FLC-53pz9zp44prb` |
| P2 | *Restrict URL parameters* | `\p{Lu}` présentée sans l'option `utf8` (le piège la donnait, pas la section) | `RouteCompiler` l.182-183 |
| P3 | *Built-in validation constraints* | `Type('string')` rangée sous « le validator rend la main » sur `''` : il l'accepte parce que c'est une string | `TypeValidator` l.66 ; `Type('int')` sur `''` : violation |
| P4 | *Service container* | « une référence vers un service inexistant échoue dès la compilation » donné comme intérêt du **privé** : c'est l'injection qui l'apporte | `service_container.rst` l.1044-1047 |
| P5 | *Retries and failures* | un tirage de jitter (1096, 2109, 4298 ms) présenté comme un calcul | `random_int` ±10 % ; ré-exécuté : 1042, 1878, 4325 |
| P6 | *Options and arguments* | mode `VALUE_NEGATABLE` seul pour `bool $x = true` : c'est `VALUE_NONE` + `VALUE_NEGATABLE` | `Attribute\Option` l.109-111 |
| L1 | *Object Oriented Programming* | « le binding statique tardive » → *late static binding* ; carte `FLC-xcnrvq0mddck` | — |
| L2 | *Twig syntax* | « Trois pièges » suivis de quatre | — |
| L3 | *Compiler passes* | « Trois exécutions » suivies de quatre lignes | — |
| L4 | *Cache* | « en propose deux » suivi de trois (Contracts, PSR-6, PSR-16) ; titre « Trois API, une recommandée » | — |
| R1 | *Symfony Flex* | deux sections sur les packs ; la courte, redite par la longue, retirée | — |
| R2 | *Compiler passes* | « Le noyau-passe tourne à -10000 » → « La passe du kernel » | — |
| R3 | *Security configuration* | « Seules ces deux familles sont jamais masquées » se lit « ne sont jamais » | — |
| R4 | *Options and arguments* | « il devient négociable » (= *negatable*) ; carte `FLC-g71n3msgyw1m` | — |
| — | *HTTP response* | coquille « s'arrete » dans un commentaire de code | — |

Sept paragraphes « Pièges d'examen » ont été reformulés ; leurs verdicts sont
redonnés dans `docs/audit/pitfall-flashcards/reviewed.yml`, chacun en relisant
la carte contre le nouveau texte.

**Gardes.** Trois faits centraux (`http-foundation.language-tail-dropped`,
`console.input-argument-mode-is-only-an-example`,
`validator.callback-targets-class-and-property`), chacun avec un check exécuté
par `tools/behavior/run.php` et des motifs interdits qui reconnaissent l'ancien
texte — prouvé : chaque motif trouve la version `master` et ne trouve pas la
nouvelle. Quatre aiguilles de smoke test (`third-pass`), absentes de `master`.

## Observé, non changé

- *Abstract classes* ne dit rien des **properties abstraites** de PHP 8.4
  (`abstract public string $n { get; }`). C'est un manque, pas une erreur : la
  passe ne corrige que des défauts établis. Consigné pour une extension —
  **faite après la passe** : une section « Les properties abstraites — depuis
  PHP 8.4 », une ligne du tableau interface / abstract class, un piège, trois
  cartes et trois questions de compréhension ; chaque règle exécutée avec PHP
  8.4.19, messages cités tels qu'ils sortent.
- La carte « où est cherchée la method d'un Callback déclaré par un simple
  nom » a été précisée (la valeur validée, l'object sur une class) pour rester
  d'accord avec D7.

## La terminologie

La relecture a aussi relevé des **réécritures abusives** de l'outil
terminologique — un mot français pris pour le terme technique qu'il traduit
ailleurs : « réponse » (à une question) devenu `Response`, « chaîne » (d'appels,
de hashers) devenu `string`, « tableau » (la table ci-dessus) devenu `array`,
« entier » (tout entier) devenu `int`, « commande » (un achat) devenu
`command`, « boucle » (verbe) devenu `loop`, des accords restés au féminin
(« la tag »), des élisions cassées (« L'listener »). Ce sont des défauts de
langue, pas de fond ; ils sont corrigés dans #435, avec l'outil
lui-même (motifs `keep`), pour qu'ils ne reviennent pas.

### Ce que la PR terminologique corrige

**71 cours** (65 que la terminologie seule touche, 6 déjà corrigés sur le
fond) et **14 fichiers de cartes**, chaque occurrence relue dans son contexte et
réécrite à la main — jamais par remplacement global. Par famille :

| Famille | Exemple avant | Après |
|---|---|---|
| « réponse » à une question | « Trois verbes, trois Responses » | « trois verdicts » |
| « réponse » d'un user en console | « accepte toute Response commençant par `y` » | « toute saisie » |
| « tableau » (la table de la page) | « ## L'array de décision » | « ## La table de décision » |
| « chaîne » d'appels, de hashers | « `auto` construit une **string** » | « une **chaîne de hashers** » |
| « tout entier », « en bloc », « en boucle » | « le framework tout int » | « tout entier » |
| « commande » (un achat) | « accède alors à la command par `getOrder()` » | « à l'`Order` » |
| verbe pris pour un nom | « Seul `q` class », « qui la factory » | « ordonne », « qui le génère » |
| « fermeture » du navigateur, du form | « la closure du navigateur » | « la fermeture du navigateur » |
| « sortie » d'un process | « terminé avant l'output » | « avant l'arrêt » |
| « système de fichiers », « agent utilisateur » | « système de files », « l'agent user » | `filesystem`, « le user agent » |
| accords restés au féminin | « une seconde tag », « aucune redirect » | « un second tag », « aucun redirect » |
| élisions cassées | « L'**listener** », « index de array » | « Le **listener** », « index d'array » |
| « requête » Doctrine ou CQRS | « faire la Request dans le controller » | « écrire la query » |

Huit titres changent ; les cinq que `outcome_sections` désigne sont mis à jour
dans la même modification (`SEC-001`). Treize paragraphes « Pièges d'examen »
reformulés reçoivent un nouveau verdict dans le registre, chacun relu contre sa
carte. Aucune question n'est touchée.

**L'outil.** `aucun`/`aucune` sont désormais des déterminants (l'accord suit le
genre du terme anglais) ; une forme de plusieurs mots est reconnue à travers un
retour à la ligne ; la fenêtre des motifs `keep` est lue espaces normalisés, si
bien qu'un motif écrit avec des espaces protège une expression coupée en deux
lignes — un premier essai de la correction, sans cela, réécrivait en « caching »
un « mise en cache » coupé par un retour à la ligne. Motifs `keep` ajoutés :
« en boucle », « en bloc », « d'un bloc », « tout entier », « en entier »,
« fermeture du navigateur », « redirection de flux ». Deux entrées :
« système de fichiers » → `filesystem` (avant « fichier », pour gagner), et
« voteur » → `voter`. Réécrit par l'outil corrigé : 18 fichiers, chaque
changement relu.

## Livraison

| Étape | PR | Commit | Production |
|---|---|---|---|
| Fond : 21 défauts dans 18 cours | #434 | `0bf0ba6` | smoke 121470795931 **success, lu** : 313 ok, 0 FAIL, les quatre lignes « third-pass » |
| Terminologie, et journal de #434 (13 entrées `CVR-…`, `finding: third-pass`) | #435 | `dd3ea45` | smoke 121496667051 **success, lu** : 313 ok, 0 FAIL, journal servi |
| Journal de #435 (`CVR-sg2brrtfhfk7` : les 16 cours et 6 cartes où la réécriture changeait le sens) | #436 | `cdb00a6` | smoke 121517190085 **success, lu** : 313 ok, 0 FAIL, journal servi |
| Extension consignée : properties abstraites de PHP 8.4 (*Abstract classes*) | PR suivante | — | — |
