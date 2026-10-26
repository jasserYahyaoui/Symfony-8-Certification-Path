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
| touchées par la seule réécriture terminologique (PR suivante) | voir plus bas |

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
  passe ne corrige que des défauts établis. Consigné pour une extension.
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
langue, pas de fond ; ils sont corrigés dans la PR suivante, avec l'outil
lui-même (motifs `keep`), pour qu'ils ne reviennent pas.
