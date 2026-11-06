---
id: CRS-tqcp2kn9r3b5
official_item: OIT-1kjqdnryaw0h
title: "Attributes"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-26"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/attributes.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/language/attributes.xml"
    symbol_or_lines: 'Attributes overview; Attribute syntax ("Arguments can only be literal values or constant expressions"); Reading Attributes with the Reflection API (getAttributes() returns ReflectionAttribute instances; newInstance() constructs)'
    repository: "php/doc-en"
    branch: "master"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Attribute/Route.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Attribute/Route.php"
    symbol_or_lines: 'class Route — the #[\\Attribute(...)] declaration with IS_REPEATABLE and its TARGET_CLASS|TARGET_METHOD flags, as a real attribute declaration'
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/php/doc-en/master/appendices/migration81/new-features.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/appendices/migration81/new-features.xml"
    repository: "php/doc-en"
    branch: "master"
    symbol_or_lines: 'new in Initializers — "It is now possible to use new ClassName() expressions as ... attribute arguments"'
    verified_at: "2026-10-09"
outcome_sections:
  OUT-0rkkxvd0e76f: "Déclarer"
  OUT-xnmzqxrwf753: "Poser"
  OUT-s8tzdy31hq6k: "Lire"
---

## Objectif

Déclarer un attribute, le poser correctement, et le lire par réflexion — le
mécanisme sur lequel repose une grande partie de la configuration Symfony.

## L'essentiel

Un attribute est une class marquée `#[Attribute]`, avec ses cibles et
éventuellement `IS_REPEATABLE`. On le pose avec des arguments qui sont des
**expressions constantes**. Et il ne fait **rien tout seul** : il faut le lire
par réflexion. `getAttributes()` rend des `ReflectionAttribute` ; c'est
`newInstance()` qui construit l'object — et qui lève les erreurs.

## Le schéma

```text
déclarer   #[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)] final class Route
cibles     TARGET_CLASS · FUNCTION · METHOD · PROPERTY · CLASS_CONSTANT · PARAMETER · ALL
poser      #[Route('/blog', name: 'blog_list')]   positionnel ou nommé
arguments  littéral · constante · constante de class · cas d'enum · array · new (8.1) — pas d'appel, pas de variable
lire       getAttributes(Route::class) → ReflectionAttribute[]   (IS_INSTANCEOF pour les sous-classes)
           getArguments() → ['/blog', 'name' => 'blog_list'] · newInstance() → l'object
erreurs    mauvaise cible, répétition interdite, constructor → levées à newInstance()
```

## L'exemple

```php
$reflection = new \ReflectionMethod($controller, 'list');

foreach ($reflection->getAttributes(Route::class) as $attribute) {
    $route = $attribute->newInstance();     // instancie ENFIN la classe Route
    echo $route->path;
}
```

## Déclarer

Un attribute est une class ordinaire marquée par `#[Attribute]`, dont l'argument
combine des cibles — `TARGET_CLASS`, `TARGET_FUNCTION`, `TARGET_METHOD`,
`TARGET_PROPERTY`, `TARGET_CLASS_CONSTANT`, `TARGET_PARAMETER`, `TARGET_ALL` —
et `IS_REPEATABLE`, qui autorise plusieurs occurrences au même endroit.

## Poser

Les arguments suivent les règles d'appel, positionnelles ou nommées. Ils sont
des **valeurs littérales ou des expressions constantes** : un littéral, une
constante, une constant de class, un cas d'enum, un array de ces éléments et,
depuis PHP 8.1, une instanciation `new`, ce qui permet d'imbriquer des
attributes : `#[Outer([new Inner('a')])]`. Ni appel de function ni variable.

## Lire

**Un attribute ne fait rien tout seul.** `getAttributes()` renvoie des
`ReflectionAttribute`, filtrés par nom de class exact — le drapeau
`ReflectionAttribute::IS_INSTANCEOF` inclut les sous-classes.
`getArguments()` rend les arguments bruts sans rien construire ;
`newInstance()` construit l'object, et c'est là que lèvent une cible non
autorisée, une répétition interdite ou un constructor qui refuse ses arguments.

## Pièges d'examen

**Déclarer un attribute ne l'exécute pas.** Sans code de réflexion qui le lit,
un attribute est inerte. En Symfony, c'est le framework qui lit.

**`getAttributes()` ne construit rien.** Tant que `newInstance()` n'est pas
appelé, la class d'attribute n'est pas instanciée et ses erreurs n'apparaissent
pas.

**Les arguments sont des expressions constantes.** `#[Route(path: getPath())]`
ne compile pas.

**Sans `IS_REPEATABLE`, répéter un attribute est une erreur** — levée par
`newInstance()` : `getAttributes()` rend les deux occurrences sans rien dire.

## Application

- Lister les routes d'une class sans instancier `Route` → `getArguments()`.
- Lire tous les attributes d'une famille → `getAttributes(Base::class, ReflectionAttribute::IS_INSTANCEOF)`.

## QCM d'entraînement

**Question 1.** Un attribute déclaré `TARGET_METHOD` est posé sur une class.
Quand l'erreur apparaît-elle ?

- **A.** À la compilation du file
- **B.** À `getAttributes()`
- **C.** À `newInstance()`
- **D.** Jamais : la cible est indicative

<details>
<summary>Correction</summary>

**Bonne réponse : C.** La réflexion rend l'attribute sans contrôle ; c'est sa
construction qui vérifie la cible et lève une `Error`.

</details>

**Question 2.** `Child extends Base`, et la class porte `#[Child]`. Sans
drapeau, `getAttributes(Base::class)` le trouve-t-il ?

- **A.** Oui, par inheritance
- **B.** Non
- **C.** Oui, si `Child` est final
- **D.** Une exception

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Le filter compare le nom de class exact ; il faut
`ReflectionAttribute::IS_INSTANCEOF` pour inclure les sous-classes.

</details>

**Question 3.** `#[Requires(Level::Admin)]`, où `Level` est une enum pure. Cet
argument est-il valide en PHP 8.4 ?

- **A.** Oui
- **B.** Non : un object n'est pas constant
- **C.** Seulement avec `new`
- **D.** Seulement pour une backed enum

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Un cas d'enum est une expression constante, adossée ou
non ; il est accepté comme argument d'attribute.

</details>

**Question 4.** Que rend `getArguments()` pour `#[Route('/blog', name: 'b')]` ?

- **A.** `['path' => '/blog', 'name' => 'b']`
- **B.** Un object `Route`
- **C.** `['/blog', 'b']`
- **D.** `['/blog', 'name' => 'b']`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Les arguments sont rendus tels qu'écrits : indexés par
position, ou par nom pour les named arguments, sans rien instancier.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- `#[Attribute(cibles | IS_REPEATABLE)]` sur une class ordinaire.
- Arguments = **expressions constantes** ; `new` admis depuis 8.1.
- Un attribute est **inerte** sans lecteur.
- `getAttributes()` → **`ReflectionAttribute`**, nom exact (sinon **`IS_INSTANCEOF`**).
- `getArguments()` : brut ; **`newInstance()`** : construit.
- Cible, répétition, constructor : erreurs à **`newInstance()`**.

Moyen mnémotechnique : **« Un attribute dort jusqu'à newInstance() »**.

## Sources officielles

- Manuel PHP — *Attributes*
- `Symfony\Component\Routing\Attribute\Route` (branche 8.0, `6f841c0`)

