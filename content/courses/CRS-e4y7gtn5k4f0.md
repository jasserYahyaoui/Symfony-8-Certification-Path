---
id: CRS-e4y7gtn5k4f0
official_item: OIT-vt0p9cacpkpd
title: "Interfaces"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-26"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/oop5/interfaces.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/language/oop5/interfaces.xml"
    symbol_or_lines: '"All methods declared in an interface must be public"; Constants — "It''s possible for interfaces to have constants. Interface constants work exactly like class constants"; Properties — "The interface declaration applies only to public read and write access" and "an interface property that is settable may not be readonly"'
    repository: "php/doc-en"
    branch: "master"
    verified_at: "2026-10-09"
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/oop5/properties.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/language/oop5/properties.xml"
    repository: "php/doc-en"
    branch: "master"
    symbol_or_lines: 'Readonly properties — "As of PHP 8.4.0, readonly properties are implicitly protected(set), so may be set from child classes"'
    verified_at: "2026-10-09"
outcome_sections:
  OUT-6tzwvmfwj408: "Ce qu'une interface contient"
  OUT-5vqwe44nsee5: "Ce qu'une interface contient"
  OUT-7vt306wrfkb7: "Les constants sont surchargeables — depuis PHP 8.1"
  OUT-ynfsf3qdn2mj: "Les properties d'interface — depuis PHP 8.4"
---

## Objectif

Reconnaître ce qu'une interface peut et ne peut pas déclarer.

## L'essentiel

Une interface déclare des methods implicitement **publiques et abstraites**, des
constantes et, depuis PHP 8.4, des **properties** en `get`, `set` ou les deux.
Deux réflexes anciens sont faux sur PHP 8.4 : une constant d'interface **se
surcharge** depuis 8.1, sauf `final` ; et une property `readonly` ne satisfait
**pas** une property d'interface `set`.

## Le schéma

```text
methods      implicitement public + abstract · pas de corps · pas de private/protected
constantes   publiques obligatoirement · surchargeables depuis 8.1 · final → non surchargeable
properties   8.4 : public string $name { get; } · { get; set; } — contrat d'accès public, pas d'état
             { get }      ← property publique, readonly, ou hook get virtuel
             { get; set } ← property publique ordinaire · PAS readonly · PAS public private(set)
héritage     class implements A, B · interface extends A, B
```

## L'exemple

```php
interface Cacheable
{
    const int DEFAULT_TTL = 3600;
    final const int VERSION = 2;

    public function getCacheKey(): string;
}

class Article implements Cacheable
{
    const int DEFAULT_TTL = 60;    // autorisé depuis 8.1
    // const int VERSION = 3;      // erreur fatale : la constante est final

    public function getCacheKey(): string { return 'article'; }
}
```

## Ce qu'une interface contient

- Des methods **implicitement `public` et abstraites**, sans corps ; une method
  `private` ou `protected` ne compile pas, et la class qui l'implémente doit la
  garder publique.
- Des constants, forcément publiques.
- Une class implémente **plusieurs** interfaces ; une interface **étend
  plusieurs** interfaces (`extends A, B`).

Elle ne contient aucune property **porteuse d'état**.

## Les constants sont surchargeables — depuis PHP 8.1

Avant PHP 8.1, une constant d'interface ne pouvait pas être redéfinie par la
class qui l'implémente. Depuis 8.1, elle le peut — et Symfony 8.0 exige PHP
8.4. C'est `final` sur la constant de l'interface qui interdit la surcharge,
pas l'interface elle-même.

## Les properties d'interface — depuis PHP 8.4

Une interface peut déclarer une property, en disant si elle est **lisible,
écrivable, ou les deux** :

```php
interface Named
{
    public string $name { get; }        // lisible
    public string $slug { get; set; }   // lisible et écrivable
}
```

La déclaration ne porte que sur l'accès public. Une property publique ordinaire
la satisfait, ou une property virtuelle qui implémente le hook correspondant.

**Une property `readonly` ne peut pas satisfaire un `set`** : elle est
implicitement `protected(set)` depuis PHP 8.4, et l'interface promet une
écriture **publique**. Pour la même raison, `public private(set)` ne la satisfait pas non plus. PHP le dit :
« Set access level of C::$name must be omitted (as in class I) ». `readonly`
convient en revanche pour un `get` seul.

## Pièges d'examen

**La règle d'avant 8.1 est le réflexe à corriger.** « Une constant d'interface
ne se surcharge pas » a été vrai pendant des années ; sur PHP 8.4 c'est faux,
sauf `final`.

**Déclarer une property n'est pas porter un état.** L'interface impose un
contrat d'accès ; c'est la class qui décide s'il y a une valeur stockée
derrière.

**`readonly` ne satisfait pas un `set`.** Le réflexe « readonly est une
property publique, donc ça passe » échoue précisément sur la moitié écriture.

## Application

- Imposer un identifier lisible sans dicter le stockage → `public string $id { get; }`.
- Bloquer la redéfinition d'une constant de contrat → `final const`.

## QCM d'entraînement

**Question 1.** L'interface déclare `public string $name { get; }`. La class
écrit `public string $name { get => 'virt'; }`, sans valeur stockée. Résultat ?

- **A.** Valide
- **B.** Erreur : il faut une property stockée
- **C.** Erreur : un hook ne satisfait pas une interface
- **D.** Valide seulement avec `readonly`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** L'interface impose un accès en lecture, pas un état ;
une property virtuelle munie du hook `get` remplit ce contrat.

</details>

**Question 2.** Pour le même `{ get; }`, la class déclare `$name` par
promotion de constructor, en `public` et `readonly`. Est-ce accepté ?

- **A.** Non : readonly vaut `protected(set)`
- **B.** Seulement sans promotion
- **C.** Oui
- **D.** Non : il faut un hook `get`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Seule la lecture publique est promise, et une property
`readonly` publique la fournit ; c'est un `set` qu'elle ne pourrait pas tenir.

</details>

**Question 3.** Une interface déclare `protected const X = 1;`. Que fait PHP ?

- **A.** Il l'accepte
- **B.** Il l'accepte, mais la cache aux classes
- **C.** Un warning
- **D.** Une erreur fatale

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Les constants d'interface sont forcément publiques ; PHP
refuse tout autre niveau d'accès dès la compilation.

</details>

**Question 4.** L'interface déclare `public function run(): void`, et la class
l'implémente en `protected function run(): void`. Que se passe-t-il ?

- **A.** C'est valide
- **B.** Une erreur fatale : la method doit être publique
- **C.** C'est valide, tant que la class qui l'implémente est déclarée abstraite
- **D.** Un warning de compatibilité

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Une implémentation ne peut pas réduire la visibilité
promise par l'interface ; PHP l'exige publique, comme dans le contrat.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- Methods **publiques et abstraites**, sans corps ; implémentation publique.
- Constantes **publiques**, surchargeables **depuis 8.1**, sauf **`final`**.
- `implements A, B` ; une interface **`extends A, B`**.
- PHP 8.4 : properties **`{ get; }`**, **`{ get; set; }`** — un contrat, pas un état.
- `readonly` satisfait **`get`**, jamais **`set`**.
- `public private(set)` ne satisfait pas `set` non plus.

Moyen mnémotechnique : **« L'interface promet, la class tient »**.

## Sources officielles

- Manuel PHP — *Object Interfaces*, sections *Constants* et *Properties*
- `php-src` branche `PHP-8.1`, file `UPGRADING` — modificateur `final` sur
  les constants de class (RFC `final_class_const`)

