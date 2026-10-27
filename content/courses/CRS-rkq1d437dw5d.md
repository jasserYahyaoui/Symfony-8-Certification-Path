---
id: CRS-rkq1d437dw5d
official_item: OIT-ehf5zdnmdb1j
title: "Deprecations best practices"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-27"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/contributing/code/conventions.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/contributing/code/conventions.rst"
    anchor: "contributing-code-conventions-deprecations"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: 'Writing a CHANGELOG Entry; Deprecating Code — "Deprecations must only be introduced on the next minor version of the impacted component"; Removing Deprecated Code'
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/contributing/code/releases.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/contributing/code/releases.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: 'semantic versioning, time-based model; Backward Compatibility — "Both versions have the same new features, but they differ in the deprecated features"'
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/contributing/code/bc.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/contributing/code/bc.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: '"only major releases (such as 5.0, 6.0 etc.) are allowed to break backward compatibility"; "keep the old API working and deprecate it instead of removing it"'
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/setup/upgrade_major.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/setup/upgrade_major.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: 'Make your Code Deprecation Free — "first upgrade to the last minor version so that you can see all the deprecations"; "When you run your tests using PHPUnit, no deprecation notices are shown"'
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Contracts/Deprecation/function.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Contracts/Deprecation/function.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: 'trigger_deprecation(string $package, string $version, string $message, mixed ...$args) — "@param string $version The version of the package that introduced the deprecation"; @trigger_error("Since $package $version: "…, E_USER_DEPRECATED)'
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/CHANGELOG.md"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/CHANGELOG.md"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: 'section 7.4 « Deprecate method `Request::get()` … »; section 8.0 « Remove `Request::get()` … »'
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/UPGRADE-8.0.md"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/UPGRADE-8.0.md"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: '"Symfony 8.0 doesn''t include any deprecated features. To upgrade, make sure to resolve all deprecation notices."; HttpFoundation « Remove `Request::get()` … »'
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony/af500fe18118677a0a89a3d1907bd26d995dd1c6/src/Symfony/Component/HttpFoundation/Request.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "af500fe18118677a0a89a3d1907bd26d995dd1c6"
    symbol_or_lines: 'Request::get(), lines 750-754 — @deprecated since Symfony 7.4 and trigger_deprecation(''symfony/http-foundation'', ''7.4'', …); 7.4 parent of the 8.0 merge 6f841c0 (Kernel 7.4.16-DEV)'
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony/af500fe18118677a0a89a3d1907bd26d995dd1c6/UPGRADE-7.4.md"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "af500fe18118677a0a89a3d1907bd26d995dd1c6"
    symbol_or_lines: 'HttpFoundation, line 98 — « Deprecate method `Request::get()` … »'
    verified_at: "2026-10-27"
outcome_sections:
  OUT-yqb3s91g64f7: "Règle 1 : déprécier en mineure, supprimer en majeure"
  OUT-8dx7erg8d1eb: "Règle 2 : deux signaux, deux publics"
  OUT-crtr1pmq22t7: "`trigger_deprecation()` ligne par ligne"
  OUT-0j2bgvnbyj7p: "Règle 3 : la trace écrite, trois files dans une pull request"
---

## Objectif

Comprendre comment Symfony fait évoluer son API sans casser les applications :
dans quelle version une fonctionnalité peut être dépréciée, comment la
deprecation est signalée, où elle est documentée, et quand le code déprécié
disparaît.

## Avant la règle : le problème à résoudre

Avant de mémoriser la moindre règle, il faut comprendre le problème que Symfony
cherche à résoudre.

Des milliers d'applications appellent l'API publique de Symfony. Un jour,
l'équipe veut améliorer une partie de cette API : une method au nom trompeur, un
comportement trop permissif, une class mal découpée. Elle a alors trois options.

1. **Modifier ou supprimer tout de suite.** C'est simple pour Symfony, mais
   brutal pour toi : un banal `composer update` casserait ton application. Or
   Symfony suit le *Semantic Versioning* et promet la backward compatibility :
   seule une version **majeure** a le droit de casser du code existant.
2. **Ne jamais rien changer.** Le framework serait figé, et chaque erreur de
   conception serait éternelle.
3. **Déprécier.** On garde l'ancienne API en état de marche, on ajoute la
   nouvelle à côté, on **prévient** ceux qui utilisent encore l'ancienne, et on
   ne la retire qu'à la prochaine version majeure.

La documentation de Symfony décrit exactement cette troisième voie : une
deprecation arrive *quand une implémentation ne peut pas être changée à cause de
la backward compatibility, mais qu'on veut quand même proposer une meilleure
alternative*. La promesse de backward compatibility le résume dans une consigne :
pour remplacer quelque chose, garder l'ancienne API fonctionnelle et la
déprécier, au lieu de la supprimer.

Une deprecation, c'est donc une **transition** : un délai organisé entre
« l'ancienne façon de faire fonctionne encore » et « l'ancienne façon a
disparu ».

**Qui rencontre cette notion ?** Deux personnes, aux deux extrémités :

- celui qui **déprécie** — un contributeur de Symfony, ou l'auteur d'un bundle
  ou d'une bibliothèque. C'est le point de vue de cette page : les *best
  practices* de l'item sont les conventions de contribution de Symfony ;
- celui qui **reçoit** la deprecation — le développeur d'application. Ce côté
  est résumé plus bas, dans « Concrètement dans ton application », et détaillé
  dans l'item *Handling legacy deprecated code* (lot Automated Tests).

## Le fil rouge : `Request::get()`, de 7.4 à 8.0

Toute la page suit un seul exemple, et il est réel. Pendant des années, on
écrivait dans un controller :

```php
$id = $request->get('id');
```

`Request::get()` cherchait la valeur dans plusieurs endroits à la fois, dans
cet ordre : les attributes (placeholders de route), puis la query string, puis
le body d'un form POST. Pratique, mais ambigu : en lisant `get('id')`, on ne sait
pas d'où vient la valeur. Le docblock de la method le disait déjà : elle sert
surtout aux bibliothèques qui ont besoin de cette souplesse ; dans un
controller, mieux vaut lire directement la bonne property publique
(`attributes`, `query`, `request`).

Symfony 7.4 a donc **déprécié** `Request::get()`, et Symfony 8.0 l'a
**supprimée**. Le remplacement est explicite sur la provenance de chaque
valeur — exécuté avec HttpFoundation 8.0.15 :

```php
$id    = $request->attributes->get('id');    // placeholder de route : /article/{id}
$page  = $request->query->get('page');       // query string : ?page=2
$title = $request->request->get('title');    // body d'un form envoyé en POST
```

Garde cet exemple en tête : chaque règle de la page va s'y appliquer.

## Le cycle complet en un schéma

Avant le détail, voici le mécanisme entier, appliqué au fil rouge :

```text
Symfony 7.3   Request::get() fonctionne, sans aucun signal
     │
     ▼
Symfony 7.4   MINEURE : la deprecation est introduite
     │          ├─ @deprecated since Symfony 7.4, …          → pour qui lit le code
     │          ├─ trigger_deprecation('symfony/http-foundation', '7.4', …)
     │          │                                             → pour le code qui tourne
     │          └─ documentée : CHANGELOG.md du component (section 7.4),
     │             UPGRADE-7.4.md, et l'annonce de la suppression
     ▼
Ton appli     la method marche toujours, mais chaque appel émet une notice
     │          → tu remplaces get() par attributes / query / request
     ▼
Symfony 8.0   MAJEURE : la method est supprimée
                ├─ CHANGELOG.md du component (section 8.0) :
                │    « Remove `Request::get()`, use properties … »
                ├─ UPGRADE-8.0.md : la même entrée « Remove … »
                └─ $request->get('id') → Error: Call to undefined method …::get()
```

Lis-le de haut en bas : **une mineure prévient, la majeure suivante supprime**,
et entre les deux, c'est à l'application de migrer. Chaque étape du schéma
correspond à une section de la page :

| Étape du schéma | Section |
|---|---|
| quand déprécier, quand supprimer | Règle 1 |
| `@deprecated` et `trigger_deprecation()` | Règle 2, puis l'appel ligne par ligne |
| `CHANGELOG.md`, `UPGRADE-*.md` | Règle 3 et ses deux suites |
| la migration de l'application | Concrètement dans ton application |

## Règle 1 : déprécier en mineure, supprimer en majeure

C'est la règle de calendrier : elle dit **quand** on a le droit de déprécier, et
**quand** on a le droit de supprimer. Elle ne se comprend qu'avec le sens des
numéros de version, alors commençons par là.

### D'abord, comprendre les numéros de version

Symfony suit le *Semantic Versioning* : un numéro `MAJEURE.MINEURE.PATCH`, comme
`8.0.15`. Le document consacré aux releases fixe ce que chaque niveau peut
contenir :

| Niveau | Exemple | Rythme | Ce qu'il peut contenir |
|---|---|---|---|
| patch | `7.4.6` → `7.4.7` | environ chaque mois | des corrections de bugs seulement |
| mineure | `7.3` → `7.4` | tous les six mois, en mai et en novembre | des nouveautés et **de nouvelles deprecations**, mais aucun breaking change |
| majeure | `7.4` → `8.0` | tous les deux ans, en novembre des années impaires | des breaking changes |

### Le calendrier officiel

> Une deprecation ne peut être introduite que dans la **prochaine version
> mineure** du component concerné (ou du bundle, du bridge, du contrat). Elle
> peut **exceptionnellement** être introduite dans une version antérieure encore
> maintenue, si elle est **critique**.
>
> Du code déprécié ne peut être **supprimé que dans la prochaine version
> majeure** du component — donc une fois tous les deux ans (branche `6.0`,
> `7.0`, etc.).

### Pourquoi la mineure pour déprécier ?

Parce que déprécier **ne casse rien**. `Request::get()` déprécié en 7.4 renvoie
exactement la même valeur qu'en 7.3 ; il ajoute seulement un avertissement.
Une mineure a le droit d'apporter du nouveau sans breaking change : une
deprecation entre exactement dans ce cadre. Un patch, lui, ne contient que des
corrections de bugs — d'où le caractère *exceptionnel* (et réservé au critique)
d'une deprecation sur une version déjà publiée.

### Pourquoi attendre la majeure pour supprimer ?

Parce que supprimer **casse** : toute application qui appelle encore
`$request->get()` plante dès que la method disparaît. Et seule une majeure a le
droit de casser. Si Symfony supprimait en mineure, `composer update` de 7.3 vers
7.4 pourrait faire tomber une application en production : la promesse de
backward compatibility n'aurait plus de sens.

Le calendrier découle de là : la suppression attend la majeure suivante, donc
« environ tous les deux ans ».

### Le détail qui rend la montée de version possible

Symfony développe la dernière mineure d'une branche **et** la majeure suivante
en même temps : 7.4 et 8.0 ont été publiées ensemble. Le document de release
l'explique : les deux versions ont **les mêmes nouveautés** ; elles ne diffèrent
que par les deprecations — la 7.4 les contient toutes, la 8.0 les a toutes
retirées. `UPGRADE-8.0.md` l'écrit en tête : Symfony 8.0 n'inclut aucune
fonctionnalité dépréciée, donc pour monter, il faut avoir résolu toutes les
notices de deprecation.

Autrement dit : **8.0 = 7.4 moins ce qui était déprécié.** Une application qui
tourne sur 7.4 sans aucune deprecation passe en 8.0 sans surprise.

### Deux interdits, et leur logique

La règle officielle ajoute deux interdits :

- une **nouvelle** class (ou interface, ou trait) ne peut pas être introduite
  dépréciée, ni contenir des methods dépréciées ;
- une **nouvelle** method ne peut pas être introduite dépréciée.

La documentation n'en donne pas la raison, mais elle se reconstruit à partir de
ce qui précède : une deprecation est une transition pour **ceux qui utilisent
déjà** l'API. Une API qui vient de naître n'a encore personne à faire
migrer ; la publier déjà dépréciée reviendrait à annoncer sa suppression avant
même son premier usage.

**À retenir en une ligne : Mineure, je préviens. Majeure, je supprime.**

## Règle 2 : deux signaux, deux publics

Une deprecation se déclare **deux fois**, parce qu'elle s'adresse à deux
publics qui ne regardent pas le code au même moment.

### Le signal statique : `@deprecated`, pour qui lit

C'est une annotation PHPDoc, posée sur la class, la method, la property…
concernée. Elle ne s'exécute pas : c'est de la documentation, lue par le
développeur, son IDE et ses outils. Voici celle de `Request::get()` en 7.4 :

```php
/**
 * @deprecated since Symfony 7.4, use properties `->attributes`, `query` or `request` directly instead
 */
public function get(string $key, mixed $default = null): mixed
```

La convention fixe son contenu :

- la **version** dans laquelle la deprecation est introduite est **obligatoire** :
  `@deprecated since Symfony 5.1.` ;
- le **remplacement** s'indique **chaque fois que c'est possible** :
  `@deprecated since Symfony 5.1, use Replacement instead.` ;
- si le remplacement vit dans un **autre namespace** que la class dépréciée, son
  nom pleinement qualifié (FQCN) est **obligatoire** :
  `@deprecated since Symfony 5.1, use A\B\Replacement instead.`

*Contexte complémentaire, hors règle Symfony :* les IDE et les outils d'analyse
statique savent lire `@deprecated`, par exemple pour barrer un appel. C'est
utile, mais rien de tout cela ne se produit quand le code **tourne**.

### Le signal dynamique : `trigger_deprecation()`, pour le code qui tourne

C'est un **appel de function**, placé dans le code déprécié lui-même. Il émet un
avertissement **chaque fois que ce code s'exécute réellement**. Dans
`Request::get()` en 7.4, c'est la première instruction du body :

```php
public function get(string $key, mixed $default = null): mixed
{
    trigger_deprecation('symfony/http-foundation', '7.4', 'Request::get() is deprecated, use properties ->attributes, query or request directly instead.');

    // … puis l'ancien comportement, inchangé
}
```

Cette function n'est pas dans un component du framework : elle vient d'un
package de contrats minuscule, que la convention exige :

```bash
composer require symfony/deprecation-contracts
```

Pourquoi un package séparé ? Il ne contient qu'une function et sa convention
(son README : *A generic function and convention to trigger deprecation
notices*). Une bibliothèque qui veut signaler ses deprecations au format
Symfony l'installe donc **sans tirer le framework**. Chercher
`trigger_deprecation()` dans HttpFoundation ou dans FrameworkBundle, c'est ne pas
la trouver.

### Pourquoi Symfony exige les deux

Chacun couvre l'angle mort de l'autre :

| | `@deprecated` | `trigger_deprecation()` |
|---|---|---|
| nature | annotation PHPDoc | appel de function |
| quand agit-il ? | à la lecture du code, avant toute exécution | à l'exécution, à chaque passage dans le code déprécié |
| pour qui ? | le développeur, l'IDE, les outils | l'application, ses logs, la debug toolbar |
| ce qu'il ne voit pas | un appel caché dans une dependency, jamais lu | un code déprécié qui ne s'exécute jamais |
| fourni par | PHPDoc, rien à installer | le package de contrats, installé par la command ci-dessus |

Avec `@deprecated` seul, une application qui appelle la method sans en lire le
source ne serait jamais avertie. Avec `trigger_deprecation()` seul, le lecteur
du code ne verrait rien avant d'exécuter. Les deux ensemble couvrent le moment
de la lecture **et** celui de l'exécution.

**Astuce mémoire : un signal pour lire (`@deprecated`), un signal pour courir
(`trigger_deprecation()`).**

## `trigger_deprecation()` ligne par ligne

Reprenons l'appel du fil rouge, argument par argument :

```php
trigger_deprecation(
    'symfony/http-foundation',                    // 1. le package
    '7.4',                                        // 2. la version d'introduction
    'Request::get() is deprecated, use properties ->attributes, query or request directly instead.', // 3. le message
);
```

1. **`'symfony/http-foundation'` — le package.** C'est le **nom Composer** du
   package qui déprécie (`@param string $package The name of the Composer
   package that is triggering the deprecation`). Ni le namespace
   `Symfony\Component\HttpFoundation`, ni le nom court `HttpFoundation`.
   **Piège possible.**
2. **`'7.4'` — la version d'introduction.** C'est la version du package **qui a
   introduit la deprecation** (`@param string $version The version of the
   package that introduced the deprecation`). Ce n'est **pas** la version de
   suppression : celle-ci n'est jamais passée en argument, elle découle de la
   règle 1 (la majeure suivante, ici 8.0). **C'est le piège le plus classique.**
3. **Le message** — ce que lira le développeur. Il peut contenir des
   emplacements `%s`.
4. **`...$args`, facultatifs** — les valeurs insérées dans le message, au format
   de `printf()`. L'exemple de la documentation s'en sert pour nommer les deux
   class : `'The "%s" class is deprecated, use "%s" instead.', Deprecated::class, Replacement::class`.

### Ce que la function fait réellement

Son code, dans la branche 8.0, tient en une ligne :

```php
@trigger_error(($package || $version ? "Since $package $version: " : '').($args ? vsprintf($message, $args) : $message), \E_USER_DEPRECATED);
```

Trois conséquences, chacune exécutée avec PHP 8.4.19 :

- le message envoyé devient
  `Since symfony/http-foundation 7.4: Request::get() is deprecated, …` — le
  deuxième argument se lit donc **« depuis 7.4 »**, jamais « jusqu'à » ;
- l'erreur est de type `E_USER_DEPRECATED` et **silencée** par l'operator `@` :
  même avec `display_errors` actif et `E_ALL`, rien ne s'affiche ;
- **l'exécution continue** : la method renvoie sa valeur comme avant. Un error
  handler, comme celui du component ErrorHandler, reçoit pourtant la notice et
  peut la journaliser — c'est ainsi qu'elle arrive dans le profiler.

### Où placer l'appel

- **Pour une method** : dans son body, avant l'ancien comportement, comme dans
  `Request::get()`. La notice part à **chaque appel**.
- **Pour une class entière** : au niveau du file, **après les déclarations
  `use`**, juste avant la class. C'est l'exemple officiel, tiré de Symfony 4.4 :

```php
namespace Symfony\Component\Routing\Loader\DependencyInjection;

use Symfony\Component\Routing\Loader\ContainerLoader;

trigger_deprecation('symfony/routing', '4.4', 'The "%s" class is deprecated, use "%s" instead.', ServiceRouterLoader::class, ContainerLoader::class);

/**
 * @deprecated since Symfony 4.4, use Symfony\Component\Routing\Loader\ContainerLoader instead.
 */
class ServiceRouterLoader extends ObjectRouteLoader
```

Remarque le `@deprecated` : `ContainerLoader` vit dans un autre namespace que
`ServiceRouterLoader`, d'où son FQCN obligatoire.

**Pourquoi après les `use` ?** La documentation donne la règle sans la
justifier ; l'exécution montre ce qu'elle évite. Placé **avant** le `use`,
`ContainerLoader::class` n'est pas encore importé : PHP le résout dans le
namespace courant, et le message annonce une class qui n'existe pas
(`…\DependencyInjection\ContainerLoader`). Placé **après**, il nomme la bonne.
Autre fait observé : au niveau du file, la notice part **une fois**, quand
l'autoloader charge la class — pas à chaque utilisation.

## Règle 3 : la trace écrite, trois files dans une pull request

Un signal dans le code ne suffit pas : il faut aussi que la deprecation soit
**documentée**, pour ceux qui préparent une montée de version sans exécuter
chaque ligne. Avant la liste officielle, comprends à quelle question chaque file
répond :

| File | La question à laquelle il répond | Pour `Request::get()` |
|---|---|---|
| `CHANGELOG.md` du component | qu'est-ce qui a changé dans ce component, version après version ? | section `7.4` : « Deprecate method `Request::get()`, … » |
| `UPGRADE-<mineure>.md` | que dois-je modifier en montant vers cette mineure ? | `UPGRADE-7.4.md`, rubrique HttpFoundation : la deprecation |
| `UPGRADE-<majeure suivante>.md` | qu'est-ce qui disparaîtra à la prochaine majeure ? | `UPGRADE-8.0.md` : « Remove `Request::get()`, … » |

### La liste officielle

Une deprecation doit être documentée à trois endroits, **dans la même pull
request** — la documentation précise que toutes ces tâches sont obligatoires :

1. le `CHANGELOG.md` du component concerné ;
2. le file `UPGRADE` de la **mineure visée** — `UPGRADE-4.4.md` dans l'exemple
   officiel — avec une entrée du type « Deprecate the `Deprecated` class, use
   `Replacement` instead » ;
3. le file `UPGRADE` de la **majeure suivante** — `UPGRADE-5.0.md` dans
   l'exemple — avec les **conséquences** : « Remove the `Deprecated` class, use
   `Replacement` instead ».

Remarque le changement de verbe : **Deprecate** dans le file de la mineure,
**Remove** dans celui de la majeure. Le premier dit « attention, ça va
partir » ; le second décrit ce que la majeure aura retiré.

Pourquoi tout dans la même pull request ? La règle ne le justifie pas ; sa
conséquence pratique est qu'il n'existe jamais de version où la deprecation est
dans le code sans être documentée.

## Le `CHANGELOG.md` a ses propres règles

Le `CHANGELOG.md` d'un component suit un format précis, vérifiable à l'œil :

- le titre principal est **toujours** `CHANGELOG` ;
- chaque entrée va dans une section de **version mineure** — `5.3` — comme
  élément de liste ;
- **aucune section de troisième niveau** n'est admise : pas de sous-rubrique
  « Deprecations » sous `7.4` ;
- le message suit les conventions de commit : court, **capitalisé**, **sans
  point final**, commençant par un verbe à l'impératif ;
- les nouvelles entrées s'ajoutent **en haut** de la liste.

L'exemple complet de la documentation :

```markdown
CHANGELOG
=========

5.3
---

 * Add `MagicConfig` that allows configuring things
```

Le file à modifier est celui **du component, du bridge ou du bundle modifié** —
pour notre fil rouge, `src/Symfony/Component/HttpFoundation/CHANGELOG.md`. Les
files `CHANGELOG-*` à la racine de `symfony/symfony` sont **générés
automatiquement** à la préparation des releases et ne doivent jamais être
modifiés à la main. Il suffit d'ouvrir `CHANGELOG-8.0.md` pour le comprendre :
c'est une liste de patchs (`8.0.15`, `8.0.16`…) et de leurs corrections, produite
par l'outillage de release.

**Astuce mémoire pour le message : I-M-P — Impératif, Majuscule, Pas de point.**
L'entrée réelle « Deprecate method `Request::get()`, use properties
`->attributes`, `query` or `request` directly instead » coche les trois.

## La suppression a sa propre trace

La trace écrite ci-dessus accompagne la **deprecation**. Deux ans plus tard, la
pull request qui **supprime** le code doit, elle aussi, écrire : les
conséquences de la suppression vont dans le `CHANGELOG.md` du component, dans
**la même pull request** que la suppression. L'exemple officiel :

```markdown
5.0
---

 * Remove the `Deprecated` class, use `Replacement` instead
```

C'est exactement ce que porte aujourd'hui le `CHANGELOG.md` d'HttpFoundation,
section `8.0` : « Remove `Request::get()`, use properties `->attributes`,
`query` or `request` directly instead ». Le même file contient donc, à
quelques lignes d'écart, les deux moments du cycle : *Deprecate* sous `7.4`,
*Remove* sous `8.0`.

Au total, une fonctionnalité dépréciée puis supprimée laisse **quatre**
entrées : trois au moment de la deprecation, une quatrième — dans le
`CHANGELOG.md` — au moment de la suppression.

## Concrètement dans ton application

Tout ce qui précède est écrit du côté de celui qui déprécie. Voici ce que tu en
vois quand tu développes une application — en bref, le détail étant le sujet de
*Handling legacy deprecated code*.

- **`composer update` à l'intérieur d'une branche ne casse rien.** Une mineure
  peut apporter des deprecations, jamais de breaking change : en 7.4, ton
  `$request->get('id')` fonctionne toujours.
- **Les notices se voient dans le navigateur, en environment `dev`.** La
  documentation de montée de version le dit : elles apparaissent dans la web
  debug toolbar. Dans le code 8.0 du WebProfilerBundle, le panneau Logs du
  profiler a un onglet *Deprecations* avec leur nombre, y compris celles émises
  à la compilation du container.
- **Une notice n'apparaît que si le code déprécié s'exécute.** Puisque l'appel
  est dans le body de la method, une page jamais visitée ou une command jamais
  lancée ne signale rien. Parcourir l'application et faire tourner ses tests
  fait remonter les deprecations.
- **Une notice peut venir d'une dependency.** Si un bundle tiers appelle une
  API dépréciée, la documentation conseille de mettre ce bundle à jour : son
  auteur l'a souvent déjà corrigé.
- **L'ordre de montée découle de la règle 1** :

```text
7.2 ──composer update──► 7.4 (dernière mineure : toutes les deprecations sont visibles)
                          │
                          ├─ corriger chaque notice (get() → attributes / query / request)
                          ▼
                   contraintes "symfony/*" passées en 8.0.*  ──►  8.0
```

  La documentation insiste sur l'étape intermédiaire : monter d'abord à la
  **dernière** mineure, *pour voir toutes les deprecations* — y compris celles
  introduites après ta version actuelle. Corriger en 7.2 puis sauter en 8.0
  laisserait passer les deprecations ajoutées en 7.3 et 7.4.

- *Contexte complémentaire, hors item :* PHPUnit seul n'affiche aucune notice
  de deprecation, dit la documentation ; l'outil habituel pour les faire
  remonter dans les tests est le PHPUnit Bridge, hors périmètre d'examen.
- *Contexte complémentaire :* si tu publies toi-même un bundle, rien ne
  t'empêche de suivre la même convention : requérir le même package de
  contrats et appeler `trigger_deprecation()` avec **ton** nom de package
  Composer et **ta** version.

## À comprendre

Ce qui doit être clair avant toute mémorisation :

1. **Une deprecation est une transition, pas une suppression.** L'ancienne API
   continue de fonctionner, et c'est voulu : c'est la backward compatibility
   promise.
2. **La fenêtre vient du Semantic Versioning.** Déprécier ne casse rien, donc
   c'est permis en mineure ; supprimer casse, donc c'est réservé à la majeure.
3. **Deux signaux pour deux moments.** `@deprecated` sert à la lecture,
   `trigger_deprecation()` à l'exécution ; aucun des deux ne couvre l'autre.
4. **La trace écrite répond à trois questions** : qu'est-ce qui a changé
   (`CHANGELOG.md`), que dois-je modifier maintenant (`UPGRADE-<mineure>.md`),
   qu'est-ce qui disparaîtra (`UPGRADE-<majeure>.md`).
5. **La majeure ne fait que retirer.** La dernière mineure et la majeure
   suivante ont les mêmes nouveautés ; c'est ce qui rend l'ordre de montée
   « dernière mineure, puis majeure » si efficace.

## À mémoriser

Les informations exactes, telles que l'examen peut les demander :

- deprecation : dans la **prochaine mineure** du component (ou bundle, bridge,
  contrat) ; exceptionnellement sur une version antérieure maintenue, **si
  critique** ;
- suppression : uniquement dans la **prochaine majeure**, donc environ **tous
  les deux ans** ;
- interdits : une **nouvelle** class, interface ou trait ne naît pas dépréciée
  ni avec des methods dépréciées ; une **nouvelle** method ne naît pas
  dépréciée ;
- `@deprecated since Symfony X.Y, use Replacement instead.` — version
  obligatoire, remplacement si possible, **FQCN** si autre namespace ;
- `trigger_deprecation()` vient d'un **package de contrats** séparé, pas du
  framework (la command est rappelée sous cette liste) ;
- ses arguments : **package** Composer, **version d'introduction**, **message**,
  puis les valeurs `printf()` ;
- pour une class entière : l'appel se place **après les `use`** ;
- trace écrite : `CHANGELOG.md` du component + `UPGRADE` de la mineure + `UPGRADE`
  de la majeure suivante, **dans la même pull request** ;
- suppression : une entrée « Remove … » dans le `CHANGELOG.md`, **dans la même
  pull request** que la suppression ;
- `CHANGELOG.md` : titre `CHANGELOG`, sections de version mineure, pas de
  troisième niveau, impératif capitalisé sans point final, nouvelles entrées en
  haut ;
- les `CHANGELOG-*` de la racine de `symfony/symfony` sont **générés**, jamais
  édités à la main.

Et la command qui installe `trigger_deprecation()`, à connaître telle quelle :

```bash
composer require symfony/deprecation-contracts
```

## Pièges d'examen

**Le deuxième argument de `trigger_deprecation()` est la version
d'introduction, pas celle de suppression.** `'7.4'` signifie « déprécié depuis
7.4 » ; la suppression en 8.0 n'est écrite nulle part dans l'appel.

**Le premier argument est un nom de package Composer.** `'symfony/http-foundation'`,
pas `'HttpFoundation'` ni un namespace.

**`trigger_deprecation()` n'appartient pas au framework.** Elle vient d'un
package de contrats dédié, installé à part — précisément pour qu'une
bibliothèque puisse l'appeler sans dépendre de Symfony.

**`@deprecated` seul ne prévient personne à l'exécution.** C'est de la
documentation : le signal qui atteint une application en marche est
`trigger_deprecation()`.

**Rien ne naît déprécié.** Une nouvelle class ne peut pas être introduite
dépréciée, ni contenir des methods dépréciées ; une nouvelle method non plus.

**Déprécier et supprimer ne se font pas dans la même version.** Deprecation en
mineure, suppression en majeure suivante — et la trace écrite va aux trois
endroits (`CHANGELOG.md`, les deux `UPGRADE-*.md`) dans la même pull request.

**La suppression écrit à son tour.** Une **quatrième** entrée de `CHANGELOG.md`
décrit les conséquences, dans la pull request qui supprime.

**Les `CHANGELOG-*` de la racine ne se modifient pas à la main.** Ils sont
générés à la préparation des versions. Celui qu'on édite est celui du
**component**.

## Application : quatre scénarios

Savoir réciter les règles ne suffit pas : l'examen les présente souvent sous
forme de code à relire ou de situation à trancher. Voici quatre situations,
avec le raisonnement attendu.

### Scénario 1 — relire une pull request

*Exemple fictif.* Une pull request déprécie `Foo::bar()` au profit de
`Foo::baz()` dans un component imaginaire, `symfony/foo`, pendant le
développement de la mineure `7.4`. Elle contient :

```php
/**
 * @deprecated use baz() instead
 */
public function bar(): string
{
    trigger_deprecation('Foo', '8.0', 'bar() is deprecated, use baz() instead.');

    return $this->baz();
}
```

et une seule modification de documentation, dans `CHANGELOG.md` :

```markdown
7.4
---

### Deprecations

 * deprecated `Foo::bar()`.
```

Voici ce qu'un relecteur doit signaler, règle par règle :

1. `@deprecated` sans **version** : il faut `@deprecated since Symfony 7.4, use baz() instead.`
2. Premier argument `'Foo'` : il faut le nom Composer, `'symfony/foo'`.
3. Deuxième argument `'8.0'` : c'est la version de **suppression** ; il faut la
   version d'introduction, `'7.4'`.
4. `### Deprecations` : section de **troisième niveau**, interdite.
5. `deprecated \`Foo::bar()\`.` : ni impératif (`Deprecate`), ni majuscule, et un
   point final.
6. Il manque les deux files `UPGRADE` : `UPGRADE-7.4.md` et `UPGRADE-8.0.md`,
   dans la même pull request.

### Scénario 2 — une application en 7.2 qui doit passer en 8.0

L'application appelle `$request->get('id')` dans plusieurs controllers.

- En 7.2 et 7.3 : aucun signal, la method n'est pas encore dépréciée.
- `composer update` jusqu'en 7.4 : rien ne casse ; la debug toolbar compte
  maintenant des deprecations, et le panneau Logs du profiler montre leurs
  messages, dont « … Since symfony/http-foundation 7.4: Request::get() is
  deprecated… ».
- Tu remplaces chaque appel par `attributes`, `query` ou `request`, selon la
  provenance réelle de la valeur, jusqu'à ce que plus aucune notice ne
  s'affiche en parcourant l'application et ses tests.
- Tu demandes la version `8.0.*` pour les packages `symfony/*` : la montée se fait sans
  surprise, puisque 8.0 ne fait que retirer ce que tu n'utilises plus.

Si tu avais sauté directement de 7.2 à 8.0, le premier appel restant aurait
levé `Error: Call to undefined method Symfony\Component\HttpFoundation\Request::get()`.

### Scénario 3 — la pull request de suppression

En développant 8.0, un contributeur supprime `Request::get()`. La règle ne le
laisse pas s'arrêter au code : la même pull request doit ajouter au
`CHANGELOG.md` d'HttpFoundation, dans la section `8.0` et en haut de la liste,
les conséquences de la suppression. C'est l'entrée qu'on lit aujourd'hui dans
ce file : « Remove `Request::get()`, use properties `->attributes`, `query` or
`request` directly instead ».

### Scénario 4 — déprécier une class entière

Une class est remplacée par une autre, dans un autre namespace. Il faut :
`trigger_deprecation()` au niveau du file, **après** les `use` (sinon le
`::class` du remplacement est mal résolu) ; `@deprecated since Symfony X.Y, use
Le\FQCN\DuRemplacement instead.` sur la class ; puis les trois entrées de
documentation.

## QCM d'entraînement

Prends le temps de répondre avant d'ouvrir la correction : c'est l'effort de
rappel qui fixe la notion.

**Question 1.** Dans `trigger_deprecation('symfony/http-foundation', '7.4', 'Request::get() is deprecated, …')`, que désigne `'7.4'` ?

- **A.** La version dans laquelle `Request::get()` sera supprimée
- **B.** La version du package qui a introduit la deprecation
- **C.** La version minimale de PHP exigée par le package
- **D.** La version de Symfony installée dans l'application

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Le docblock de la function le dit mot pour mot : *the
version of the package that introduced the deprecation*. Le message émis
commence d'ailleurs par « Since symfony/http-foundation 7.4: ».

A est le piège : la version de suppression n'est jamais passée en argument,
elle découle de la règle (la majeure suivante, 8.0). C n'a aucun rapport avec
la function. D est faux car l'argument est écrit en dur dans le code : en
7.4.16, la notice dit toujours « 7.4 ».

</details>

**Question 2.** Une class `Symfony\Component\Routing\Loader\DependencyInjection\ServiceRouterLoader` est dépréciée en 4.4 au profit de `Symfony\Component\Routing\Loader\ContainerLoader`. Quel commentaire PHPDoc respecte entièrement les conventions ?

- **A.** `@deprecated`
- **B.** `@deprecated since Symfony 4.4.`
- **C.** `@deprecated since Symfony 4.4, use ContainerLoader instead.`
- **D.** `@deprecated since Symfony 4.4, use Symfony\Component\Routing\Loader\ContainerLoader instead.`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** La version est obligatoire, le remplacement s'indique
quand il existe, et comme `ContainerLoader` est dans un autre namespace
(`…\Loader` et non `…\Loader\DependencyInjection`), son FQCN est obligatoire.

A n'a pas de version. B est une forme admise en général, mais ici un
remplacement existe et doit être cité. C cite le remplacement par son nom
court alors qu'il vit dans un autre namespace : c'est la nuance qui distingue C
de D.

</details>

**Question 3.** Une application tourne en 7.2 et doit passer en 8.0. Quelle démarche suit la documentation ?

- **A.** Passer directement en 8.0, puis corriger les erreurs qui apparaissent
- **B.** Monter en 7.4, corriger toutes les deprecations signalées, puis passer en 8.0
- **C.** Corriger les deprecations visibles en 7.2, puis passer directement en 8.0
- **D.** Passer en 8.0 sans rien corriger : la majeure convertit d'elle-même les appels dépréciés

<details>
<summary>Correction</summary>

**Bonne réponse : B.** La documentation demande de monter d'abord à la
dernière mineure *pour voir toutes les deprecations*, puis de les corriger : la
majeure n'étant que la dernière mineure moins le code déprécié, la montée se
fait alors sans surprise.

C est le piège : en 7.2, les deprecations introduites en 7.3 et 7.4
n'existent pas encore, elles passeraient donc inaperçues. A fait découvrir
les suppressions par des erreurs. D invente une conversion qui n'existe pas.

</details>

**Question 4.** Une bibliothèque qui ne dépend d'aucun component Symfony veut émettre des deprecations au format Symfony. Que doit-elle faire ?

- **A.** Requérir FrameworkBundle, qui définit `trigger_deprecation()`
- **B.** Requérir le component ErrorHandler, qui fournit `trigger_deprecation()`
- **C.** Requérir le package de contrats dédié, qui ne contient que cette function et sa convention
- **D.** Rien : `trigger_deprecation()` est une function native de PHP, disponible sans aucun package

<details>
<summary>Correction</summary>

**Bonne réponse : C.** La convention l'exige, et c'est la raison d'être de ce
package minuscule : fournir la function sans le framework. La command :

```bash
composer require symfony/deprecation-contracts
```

D confond avec `trigger_error()`, qui est native ; `trigger_deprecation()` est
définie par le package et chargée par l'autoloader de Composer. B confond avec
le component qui *recueille* les notices grâce à son error handler. A tirerait
tout le framework pour une seule function, qui n'y est d'ailleurs pas définie.

</details>

**Question 5.** En 7.4, un script appelle `$request->get('id')` sans error handler, avec `display_errors` actif et `error_reporting(E_ALL)`. Que se passe-t-il ?

- **A.** Une ligne « Deprecated: … » s'affiche, puis la valeur est renvoyée
- **B.** Rien ne s'affiche et la valeur est renvoyée normalement
- **C.** Une exception interrompt le script
- **D.** La method renvoie `null` pour forcer la migration

<details>
<summary>Correction</summary>

**Bonne réponse : B.** `trigger_deprecation()` appelle `trigger_error()`
précédé de l'operator `@` : la notice est silencée, et seul un error handler
installé la recueille. L'ancien comportement s'exécute ensuite, inchangé.

A serait vrai pour un `trigger_error(…, E_USER_DEPRECATED)` *sans* `@`. C et D
contrediraient le principe même d'une deprecation : le code continue de
fonctionner jusqu'à la majeure.

</details>

**Question 6.** Tu dépréciés une method d'HttpFoundation pour la mineure 7.4. Où écris-tu l'entrée de changelog ?

- **A.** Dans `CHANGELOG-7.4.md`, à la racine de `symfony/symfony`
- **B.** Dans le `CHANGELOG.md` d'HttpFoundation, section `7.4`, en haut de la liste
- **C.** Dans le `CHANGELOG.md` d'HttpFoundation, sous une nouvelle section `### Deprecations` de `7.4`
- **D.** Seulement dans `UPGRADE-7.4.md`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Le changelog à modifier est celui du component, l'entrée
va dans la section de la mineure, et les nouvelles entrées s'ajoutent en haut.

A vise un file généré, jamais édité à la main. C ajoute une section de
troisième niveau, interdite. D est incomplet : l'`UPGRADE-7.4.md` est
nécessaire, mais en plus du `CHANGELOG.md` et de l'`UPGRADE-8.0.md`, pas à leur
place.

</details>

**Question 7.** Parmi ces affirmations sur la documentation d'une deprecation, lesquelles sont exactes ? (plusieurs bonnes réponses)

- **A.** Les trois mises à jour se font dans la même pull request que la deprecation
- **B.** L'entrée du file `UPGRADE` de la majeure suivante décrit la suppression (« Remove … »)
- **C.** La pull request qui supprime le code n'a plus rien à documenter
- **D.** L'entrée de deprecation va aussi dans le `CHANGELOG-*` de la racine

<details>
<summary>Correction</summary>

**Bonnes réponses : A et B.** Les trois tâches sont obligatoires et faites
dans la même pull request ; le file de la majeure suivante décrit les
conséquences, avec le verbe *Remove*.

C est faux : la suppression ajoute sa propre entrée au `CHANGELOG.md` du
component, dans sa pull request. D est faux : les `CHANGELOG-*` de la racine
sont générés.

</details>

**Question 8.** Ce file est chargé par l'autoloader. Qu'annonce la notice ?

```php
namespace Acme\Loader\DependencyInjection;

trigger_deprecation('acme/routing', '4.4', 'The "%s" class is deprecated, use "%s" instead.', ServiceRouterLoader::class, ContainerLoader::class);

use Acme\Loader\ContainerLoader;

class ServiceRouterLoader {}
```

- **A.** `use "Acme\Loader\ContainerLoader" instead`
- **B.** `use "Acme\Loader\DependencyInjection\ContainerLoader" instead`
- **C.** Une erreur fatale : un `use` ne peut pas suivre un appel de function
- **D.** Aucune notice : un appel au niveau du file ne s'exécute jamais

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Au moment de l'appel, `ContainerLoader` n'est pas
encore importé : PHP le résout dans le namespace courant. Exécuté avec PHP
8.4.19, le message nomme `Acme\Loader\DependencyInjection\ContainerLoader`, une
class qui n'existe pas. C'est ce qu'évite la règle « après les `use` ».

A est ce qu'on obtiendrait avec l'appel placé après le `use`. C est faux : le
file s'exécute sans erreur. D est faux : le code au niveau du file s'exécute
quand l'autoloader charge la class — une fois.

</details>

**Question 9.** Lequel de ces changements est admis dans une version mineure de Symfony ?

- **A.** Supprimer une method dépréciée depuis la mineure précédente
- **B.** Ajouter une nouvelle class marquée `@deprecated` dès sa création, pour préparer une future API
- **C.** Déprécier une method existante, en la gardant fonctionnelle et en indiquant son remplacement
- **D.** Renommer une method publique sans garder l'ancien nom

<details>
<summary>Correction</summary>

**Bonne réponse : C.** C'est exactement la voie que la backward compatibility
promise prescrit pour remplacer une API : la garder fonctionnelle et la
déprécier.

A et D cassent du code existant : réservé à une majeure. B viole l'interdit
« rien ne naît déprécié ».

</details>

**Question 10.** Une deprecation peut-elle être introduite dans une version antérieure, déjà publiée et encore maintenue ?

- **A.** Jamais : uniquement dans la prochaine mineure
- **B.** Oui, exceptionnellement, si elle est critique
- **C.** Oui, librement, dans n'importe quel patch
- **D.** Seulement dans la version LTS

<details>
<summary>Correction</summary>

**Bonne réponse : B.** La règle normale est la prochaine mineure, mais la
convention prévoit une exception : sur une version antérieure encore
maintenue, *si elle est critique*.

A oublie l'exception — c'est la nuance que cherche ce genre de question. C
transforme l'exception en règle. D invente une condition que la convention ne
pose pas.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- Déprécier, c'est garder l'ancienne API **fonctionnelle** en annonçant son
  remplacement : c'est la backward compatibility en action.
- **Mineure : je préviens. Majeure : je supprime.** Exception : une deprecation
  critique sur une version antérieure maintenue.
- Rien ne naît déprécié : ni une nouvelle class, ni une nouvelle method.
- **Deux signaux** : `@deprecated` (version obligatoire, remplacement, FQCN si
  autre namespace) pour lire ; `trigger_deprecation()` pour l'exécution.
- `trigger_deprecation()` vient d'un **package de contrats** séparé (command
  ci-dessous) ; ses arguments : package Composer, **version d'introduction**,
  message ; pour une class entière, **après les `use`**.
- **Trois files, une pull request** : `CHANGELOG.md` du component,
  `UPGRADE-<mineure>.md` (Deprecate…), `UPGRADE-<majeure>.md` (Remove…) ; la
  suppression ajoute une **quatrième** entrée au `CHANGELOG.md`.
- `CHANGELOG.md` : sections de mineure, pas de troisième niveau, **I-M-P**
  (impératif, majuscule, pas de point), nouvelles entrées en haut ; les
  `CHANGELOG-*` de la racine sont générés.

La command à connaître :

```bash
composer require symfony/deprecation-contracts
```

**En une phrase, pour tout reconstruire :**

```text
MINEURE → DÉPRÉCIER → @deprecated + trigger_deprecation()
        → CHANGELOG.md + UPGRADE mineure + UPGRADE majeure (même pull request)
        → l'application corrige sur la dernière mineure
        → MAJEURE SUIVANTE → SUPPRIMER (+ « Remove … » dans le CHANGELOG.md)
```

## Sources officielles

- [Code Conventions, « Writing a CHANGELOG Entry », « Deprecating Code », « Removing Deprecated Code »](https://github.com/symfony/symfony-docs/blob/8.0/contributing/code/conventions.rst)
- [The Release Process](https://github.com/symfony/symfony-docs/blob/8.0/contributing/code/releases.rst)
- [Our Backward Compatibility Promise](https://github.com/symfony/symfony-docs/blob/8.0/contributing/code/bc.rst)
- [Upgrading a Major Version](https://github.com/symfony/symfony-docs/blob/8.0/setup/upgrade_major.rst)
- [`trigger_deprecation()`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Contracts/Deprecation/function.php)
- [`CHANGELOG.md` d'HttpFoundation, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/CHANGELOG.md)
- [`UPGRADE-8.0.md`](https://github.com/symfony/symfony/blob/8.0/UPGRADE-8.0.md)
