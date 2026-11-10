---
id: CRS-3sggmeyd01x6
official_item: OIT-hqz5c2qse24w
title: "Enums"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-09"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/enumerations.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/language/enumerations.xml"
    symbol_or_lines: '"A Backed Enum may contain only Backed Cases. A Pure Enum may contain only Pure Cases"; from()/tryFrom() contrast; and Differences from objects'
    repository: "php/doc-en"
    branch: "master"
    verified_at: "2026-09-01"
outcome_sections:
  OUT-xccz85tdts39: "Pure ou adossée"
  OUT-6az3084jk5xb: "L'API"
  OUT-c6mz3z761kqd: "Ce qu'une enum peut faire"
---

## Objectif

Distinguer enum pure et enum adossée, choisir entre `from()` et `tryFrom()`, et
savoir ce qu'une enum ne peut pas faire.

## L'essentiel

Une enum **pure** n'a que des cas ; une enum **adossée** donne à chaque cas une
valeur `int` ou `string`. Toutes ont `cases()` et `->name` ; les adossées
ajoutent `->value`, `from()` — qui **lève** — et `tryFrom()` — qui rend
**`null`**. Une enum porte methods, constantes et interfaces, mais **aucun
état** : ni property d'instance, ni constructor, ni `new`, ni inheritance.

## Le schéma

```mermaid
---
title: Enum pure ou adossée
---
flowchart LR
  accTitle: Les deux sortes d'enum et ce qu'elles permettent
  accDescr: Une enum pure, enum Status { case Draft; }, implémente UnitEnum. Une enum adossée, enum Suit: string { case Hearts = 'H'; }, implémente UnitEnum et BackedEnum, avec int ou string seulement. Toutes ont Suit::cases(), qui rend les cas et non les valeurs, et $case->name en readonly. Les adossées ont $case->value en readonly, from('H') qui rend le cas ou lève ValueError, et tryFrom('X') qui rend null. Sont autorisés les methods, les methods statiques, les constants, par exemple l'alias const Default = self::Spades, les interfaces et les attributes ; sont interdits les properties d'instance, le constructor, extends, new et la redéfinition de from() ou tryFrom(). Les cas sont des singletons : Suit::from('H') === Suit::Hearts.
  E(["enum"]) --> PU["pure<br/>enum Status<br/>{ case Draft; }<br/>→ UnitEnum"]
  E --> BA["adossée<br/>enum Suit: string<br/>{ case Hearts = 'H'; }<br/>→ UnitEnum + BackedEnum<br/>int ou string seulement"]
  PU --> ALL["toutes<br/>Suit::cases() → les cas<br/>(pas les valeurs)<br/>$case->name (readonly)"]
  BA --> ALL
  BA --> BV["adossées<br/>$case->value (readonly)<br/>from('H') → cas,<br/>sinon \ValueError<br/>tryFrom('X') → null"]
  E --> OK["autorisé<br/>methods,<br/>methods statiques,<br/>constantes (alias :<br/>const Default = self::Spades),<br/>interfaces, attributes"]
  E --> NO["interdit<br/>properties d'instance,<br/>constructor, extends, new,<br/>redéfinir from()/tryFrom()"]
  E --> SG(["cas = singletons<br/>Suit::from('H')<br/>=== Suit::Hearts"])
```

## L'exemple

```php
enum Suit: string implements Colorful
{
    case Hearts = 'H';
    case Spades = 'S';

    const Default = self::Spades;          // alias

    public function color(): string
    {
        return match ($this) {
            self::Hearts => 'Red',
            self::Spades => 'Black',
        };
    }
}

$suit = Suit::tryFrom($input) ?? Suit::Default;
```

## Pure ou adossée

Une enum pure déclare des cas sans valeur. Une enum adossée (*backed*) donne à
chaque cas une valeur de type `int` ou `string` — aucun autre type —, unique et
constante. Une enum adossée implémente automatiquement `BackedEnum` ; toute
enum implémente `UnitEnum`.

## L'API

Toutes les enums exposent `cases()`, qui rend les cas eux-mêmes, et `->name`,
en lecture seule. Les enums **adossées uniquement** ajoutent `->value`, en
lecture seule, `from()`, qui lève une `\ValueError` si aucun cas ne correspond,
et `tryFrom()`, qui rend alors `null`. Pour les valeurs :
`array_column(Suit::cases(), 'value')`.

## Ce qu'une enum peut faire

Methods, methods statiques, constantes, implémentation d'interfaces et
attributes. Une constant peut désigner un cas : c'est un alias. Ses cas sont
des singletons, comparables par `===`.

Elle ne peut pas porter d'**état** : pas de property d'instance, pas de
constructor, pas d'`extends`, pas de `new` — « Cannot instantiate enum ».
`->value` et `->name` sont en lecture seule : toute modification, directe ou
indirecte, lève une `Error`.

## Pièges d'examen

**`from()` lève, `tryFrom()` renvoie `null`.** Sur une entrée user,
`from()` produit une `\ValueError` non rattrapée. C'est la distinction la plus
testée.

**Redéfinir `from()` ou `tryFrom()` sur une enum adossée est une erreur
fatale.** Ces methods sont fournies par l'engine.

**`cases()` sur une enum adossée renvoie les cas, pas les valeurs.** Pour les
valeurs : `array_column(Suit::cases(), 'value')`.

**Une enum pure n'a pas de `->value`.** Y accéder ne lève rien : PHP émet un
avertissement « Undefined property » et rend `null` — exécuté.

## Application

- Une valeur venue d'un form → `tryFrom()` et une valeur de repli.
- Une valeur de votre propre base, censée toujours valide → `from()`, qui
  échoue bruyamment.

## QCM d'entraînement

**Question 1.** Que vaut `Suit::from('H') === Suit::Hearts` ?

- **A.** `true`
- **B.** `false` : deux instances distinctes
- **C.** Une `Error`
- **D.** `true` seulement avec `==`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Chaque cas est un singleton : `from()` rend l'object du
cas lui-même, identique à celui qu'on nomme directement.

</details>

**Question 2.** Que produit `new Suit()` ?

- **A.** Le premier cas, par défaut
- **B.** Une `Error`
- **C.** Un object sans cas
- **D.** Un cas `null`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Une enum ne s'instancie jamais : PHP refuse `new` avec
« Cannot instantiate enum », ses seules instances étant ses cas.

</details>

**Question 3.** Que fait PHP de `enum F: float { case A = 1.0; }` ?

- **A.** Il l'accepte
- **B.** Un warning de conversion
- **C.** Une erreur fatale
- **D.** Il l'accepte si les valeurs sont entières

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Le type d'adossement ne peut être que `int` ou `string` ;
tout autre type est refusé à la compilation de l'enum.

</details>

**Question 4.** Une enum adossée implémente-t-elle `UnitEnum` ?

- **A.** Non : seulement `BackedEnum`
- **B.** Non, aucune interface
- **C.** Seulement si elle le déclare elle-même
- **D.** Oui, en plus de `BackedEnum`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Toute enum implémente `UnitEnum` ; une enum adossée y
ajoute automatiquement `BackedEnum`, sans rien déclarer.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- Pure : des cas ; adossée : **`int` ou `string`**, unique et constante.
- `cases()` → les **cas** ; `name` et `value` en **lecture seule**.
- **`from()` lève** `\ValueError` ; **`tryFrom()` rend `null`**.
- Methods, constantes (alias), interfaces : **oui**.
- État, constructor, `extends`, **`new`** : **non**.
- `UnitEnum` pour toutes, **`BackedEnum`** en plus pour les adossées.

Moyen mnémotechnique : **« from fâche, tryFrom tolère »**.

## Sources officielles

- Manuel PHP — *Enumerations*

