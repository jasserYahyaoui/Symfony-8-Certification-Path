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

## L'essentiel

Déprécier, c'est garder l'ancienne API **fonctionnelle** en annonçant son
remplacement. **Une mineure prévient, la majeure suivante supprime** ; entre
les deux, l'application migre.

## Le schéma

```text
7.4 mineure   déprécier : @deprecated + trigger_deprecation() + trois files → l'application corrige
8.0 majeure   supprimer : le code part, « Remove … » au CHANGELOG.md → un appel oublié = Error
```

## Le fil rouge : `Request::get()`, de 7.4 à 8.0

`Request::get()` cherchait une valeur dans les attributes, la query string
puis le body POST, sans dire d'où elle venait. Dépréciée en 7.4, supprimée en
8.0 ; le remplacement dit la provenance :

```php
$id    = $request->attributes->get('id');    // placeholder de route : /article/{id}
$page  = $request->query->get('page');       // query string : ?page=2
$title = $request->request->get('title');    // body d'un form envoyé en POST
```

## Règle 1 : déprécier en mineure, supprimer en majeure

- Deprecation : la **prochaine mineure** du component, bundle, bridge ou
  contrat ; **exceptionnellement** une version antérieure maintenue, si elle est
  **critique**.
- Suppression : la **prochaine majeure** seulement, environ tous les deux ans.
- 7.4 et 8.0 ont les mêmes nouveautés : **8.0 = 7.4 moins le code déprécié**.
- Une **nouvelle** class, interface, trait ou method ne naît jamais dépréciée.

## Règle 2 : deux signaux, deux publics

| | `@deprecated` | `trigger_deprecation()` |
|---|---|---|
| nature | annotation PHPDoc | appel de function |
| agit | à la lecture du code | à l'exécution, à chaque passage |
| ne voit pas | un appel caché dans une dependency | un code déprécié jamais exécuté |

L'annotation porte la version (**obligatoire**), le remplacement si possible,
et son **FQCN** s'il vit dans un autre namespace :

```php
/**
 * @deprecated since Symfony 7.4, use properties `->attributes`, `query` or `request` directly instead
 */
```

La function vient d'un package de contrats minuscule, sans le framework :

```bash
composer require symfony/deprecation-contracts
```

## `trigger_deprecation()` ligne par ligne

```php
trigger_deprecation(
    'symfony/http-foundation',   // 1. le nom Composer du package, pas le namespace
    '7.4',                       // 2. la version d'INTRODUCTION, jamais celle de suppression
    'Request::get() is deprecated, use properties ->attributes, query or request directly instead.', // 3. le message
);                               // 4. puis, facultatifs, les ...$args insérés au format printf()
```

- Elle appelle `@trigger_error(…, E_USER_DEPRECATED)`, message
  `Since symfony/http-foundation 7.4: …` : **silencée**, l'exécution continue ;
  seul un error handler la recueille.
- **Pour une method** : dans son body ; une notice à chaque appel.
- **Pour une class entière** : au niveau du file, **après les `use`**. Placé
  avant, le `::class` du remplacement est résolu dans le namespace courant et
  nomme une class qui n'existe pas. La notice part une fois, au chargement.

## Règle 3 : la trace écrite, trois files dans une pull request

| File | Pour `Request::get()` |
|---|---|
| `CHANGELOG.md` du component | section `7.4` : « Deprecate method `Request::get()`, … » |
| `UPGRADE-<mineure>.md` | `UPGRADE-7.4.md` : la deprecation |
| `UPGRADE-<majeure suivante>.md` | `UPGRADE-8.0.md` : « Remove `Request::get()`, … » |

Les trois, **obligatoires**, dans **la même pull request** : *Deprecate* dans la
mineure, *Remove* dans la majeure.

## Le `CHANGELOG.md` a ses propres règles

- titre `CHANGELOG`, une section par **version mineure** ;
- **aucune section de troisième niveau** (pas de `### Deprecations`) ;
- un message court, à l'impératif, **capitalisé**, **sans point final** ;
- les nouvelles entrées **en haut** ;
- les `CHANGELOG-*` de la racine de `symfony/symfony` sont **générés** : on
  édite celui du component.

```markdown
CHANGELOG
=========

5.3
---

 * Add `MagicConfig` that allows configuring things
```

## La suppression a sa propre trace

La pull request qui supprime ajoute les conséquences au `CHANGELOG.md` du
component : « Remove the `Deprecated` class, use `Replacement` instead ».
HttpFoundation porte ainsi *Deprecate* sous `7.4` et *Remove* sous `8.0` : une
fonctionnalité dépréciée puis supprimée laisse **quatre** entrées.

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

## Application

- Une pull request appelle `trigger_deprecation('Foo', '8.0', …)` → le nom
  Composer (`'symfony/foo'`) et la version d'introduction (`'7.4'`).
- Une application en 7.2 vise 8.0 → monter à 7.4, la dernière mineure, pour
  voir toutes les notices, les corriger, puis passer en 8.0.

## QCM d'entraînement

**Question 1.** Dans `trigger_deprecation('symfony/http-foundation', '7.4',
'Request::get() is deprecated, …')`, que désigne `'7.4'` ?

- **A.** La version dans laquelle `Request::get()` sera supprimée
- **B.** La version du package qui a introduit la deprecation
- **C.** La version minimale de PHP exigée par le package
- **D.** La version de Symfony installée dans l'application

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Le message émis commence par « Since
symfony/http-foundation 7.4: » ; la version de suppression n'est jamais passée
en argument.

</details>

**Question 2.** `App\Legacy\OldMailer` est dépréciée en 4.4 au profit de
`App\Mail\Mailer`. Quel commentaire respecte la convention ?

- **A.** `@deprecated since Symfony 4.4, use App\Legacy\Mailer instead.`
- **B.** `@deprecated since Symfony 4.4.`
- **C.** `@deprecated since Symfony 4.4, use Mailer instead.`
- **D.** `@deprecated since Symfony 4.4, use App\Mail\Mailer instead.`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Le remplacement vit dans un autre namespace : son FQCN
est obligatoire ; A nomme une class du mauvais namespace, C le nom court.

</details>

**Question 3.** En 7.4, un script appelle `$request->get('id')` sans error
handler, avec `display_errors` actif et `error_reporting(E_ALL)`. Que se
passe-t-il ?

- **A.** Une ligne « Deprecated: … » s'affiche, puis la valeur est renvoyée
- **B.** Une exception interrompt le script
- **C.** Rien ne s'affiche, la valeur est renvoyée
- **D.** La method renvoie `null` pour forcer la migration

<details>
<summary>Correction</summary>

**Bonne réponse : C.** L'operator `@` silence la notice, que seul un error
handler recueille ; A serait vrai pour un `trigger_error()` sans `@`.

</details>

**Question 4.** Une deprecation peut-elle être introduite dans une version
antérieure, déjà publiée et encore maintenue ?

- **A.** Oui, exceptionnellement, si elle est critique
- **B.** Jamais : seulement dans la prochaine mineure du component
- **C.** Oui, librement, dans n'importe quel patch
- **D.** Seulement dans la version LTS

<details>
<summary>Correction</summary>

**Bonne réponse : A.** La règle normale est la prochaine mineure, avec cette
seule exception ; B oublie l'exception, C en fait la règle.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- **Mineure : je préviens. Majeure : je supprime.** Exception : critique, sur
  une version antérieure maintenue.
- **Rien ne naît déprécié.**
- **`@deprecated`** pour lire (version, remplacement, FQCN) ;
  **`trigger_deprecation()`** pour l'exécution, silencée.
- Arguments : package Composer, **version d'introduction**, message ; pour une
  class, **après les `use`**.
- **Trois files, une pull request** ; la suppression en ajoute une **quatrième**.
- `CHANGELOG.md` : pas de troisième niveau, **I-M-P** (impératif, majuscule,
  pas de point), en haut ; racine générée.

Moyen mnémotechnique : **« Mineure, je préviens. Majeure, je supprime. »**

## Sources officielles

- [Code Conventions, « Writing a CHANGELOG Entry », « Deprecating Code », « Removing Deprecated Code »](https://github.com/symfony/symfony-docs/blob/8.0/contributing/code/conventions.rst)
- [The Release Process](https://github.com/symfony/symfony-docs/blob/8.0/contributing/code/releases.rst)
- [Our Backward Compatibility Promise](https://github.com/symfony/symfony-docs/blob/8.0/contributing/code/bc.rst)
- [Upgrading a Major Version](https://github.com/symfony/symfony-docs/blob/8.0/setup/upgrade_major.rst)
- [`trigger_deprecation()`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Contracts/Deprecation/function.php)
- [`CHANGELOG.md` d'HttpFoundation, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/CHANGELOG.md)
- [`UPGRADE-8.0.md`](https://github.com/symfony/symfony/blob/8.0/UPGRADE-8.0.md)

