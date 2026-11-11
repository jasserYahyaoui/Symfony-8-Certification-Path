---
id: CRS-wzmrks85zyh1
official_item: OIT-ttwpe00f32q9
title: "Validation scopes"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-28"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/validation.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/validation.rst"
    anchor: "constraint-targets"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: "Constraint Targets: Properties (uninitialized typed property validated as null), Getters, Classes"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/validation/custom_constraint.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/validation/custom_constraint.rst"
    anchor: "class-constraint-validator"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: "Class Constraint Validator, getTargets()"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Mapping/Loader/AttributeLoader.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Mapping/Loader/AttributeLoader.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "AttributeLoader, get|is|has method constraints"
    verified_at: "2026-10-28"
outcome_sections:
  OUT-tgzkeywcr2c5: "Les trois cibles"
  OUT-wdhsjrrpmggk: "L'accessor"
  OUT-v2t6031e9x7s: "La class"
---

## Objectif

Savoir sur quoi une constraint peut être posée, et ce que chaque scope permet
que les autres ne permettent pas.

## L'essentiel

Une constraint se pose à trois endroits : une **property** (la valeur
stockée), un **accessor** (une valeur calculée par une method `get`, `is` ou
`has`) ou la **class** (l'object entier, seul endroit qui voit plusieurs
properties à la fois). La visibilité ne compte jamais.

## Le schéma

```mermaid
---
title: Ce qu'une constraint valide selon sa place
---
flowchart TD
  accTitle: Une constraint sur une property, une method ou la class
  accDescr: Posée sur une property, une constraint valide la valeur stockée, privée comprise, lue par réflexion ; non initialisée, elle vaut null. Posée sur une method get, is ou has, elle valide sa return value, et le path s'écrit sans préfixe : isPasswordSafe devient passwordSafe ; un autre nom lève MappingException, un argument requis ArgumentCountError. Posée sur la class, elle reçoit l'object entier, comme Callback, dont getTargets() rend CLASS_CONSTRAINT.
  C(["une constraint<br/>posée sur…"]) --> P["une PROPERTY<br/>→ la valeur stockée<br/>(privée comprise,<br/>lue par réflexion)<br/>non initialisée → null"]
  C --> M["une METHOD get/is/has<br/>→ sa return value<br/>path sans préfixe :<br/>isPasswordSafe<br/>→ passwordSafe<br/>autre nom<br/>→ MappingException<br/>argument requis<br/>→ ArgumentCountError"]
  C --> K["la CLASS<br/>→ l'object entier<br/>(Callback,<br/>getTargets()<br/>→ CLASS_CONSTRAINT)"]
```

## L'exemple : un `Author`

```php
class Author
{
    #[Assert\Length(min: 3)]                  // stocké : property
    private string $firstName;

    private string $password;

    #[Assert\IsTrue(message: 'Le mot de passe ne peut pas reprendre le prénom.')]
    private function isPasswordSafe(): bool   // calculé : accessor
    {
        return $this->firstName !== $this->password;
    }
}
```

## Les trois cibles

| Cible | Ce qu'elle valide | Quand la choisir |
|---|---|---|
| property | la valeur stockée | le cas courant |
| accessor | la valeur **retournée** par une method | la règle est calculée |
| class | l'object entier | la règle croise plusieurs properties |

## La property

La visibilité n'a aucune importance : le validator lit la property par
réflexion, `private` comprise. Une property **typée non initialisée** est
validée comme `null` : `NotBlank` la refuse, `Length` la laisse passer.

## L'accessor

| Method annotée | Résultat |
|---|---|
| `private function isPasswordSafe()` | violation au path `passwordSafe` |
| `public function getFullName()` | violation au path `fullName` |
| `private function checkPasswordSafety()` | `MappingException` : « Constraints can only be added on methods beginning with "get", "is" or "has". » |
| `public function isOk(int $x)` | `ArgumentCountError` **à la validation** |

- Le path est le nom **sans le préfixe**, première lettre en minuscule ; le
  préfixe ignore la casse, et une method statique est acceptée.
- En YAML, ces constraints vont sous `getters:`, avec le nom sans préfixe.
- Un mauvais nom échoue à la lecture des metadata ; un argument requis n'est
  pas vérifié au chargement : c'est l'appel qui casse.

## La class

`Callback` s'applique à l'object lui-même. Une constraint maison y accède en
rendant `Constraint::CLASS_CONSTRAINT` depuis `getTargets()` ; par défaut, elle
rend `Constraint::PROPERTY_CONSTRAINT`, qui couvre properties **et**
accessors.

C'est la seule portée qui exprime une règle croisée :

```php
#[Assert\Callback('check')]
class Contract
{
    public function check(ExecutionContextInterface $context): void
    {
        if ($this->end < $this->start) {
            $context->buildViolation('La fin précède le début.')
                ->atPath('end')
                ->addViolation();
        }
    }
}
```

Sans `atPath()`, la violation est rattachée à l'object (path vide).

## Pièges d'examen

**Un accessor doit s'appeler `get…`, `is…` ou `has…`** ; sinon, une
`MappingException`, pas un oubli silencieux.

**La visibilité ne bloque rien**, ni sur une property ni sur un accessor.

**Le path d'une violation d'accessor perd le préfixe** : `isPasswordSafe`
donne `passwordSafe`.

**Une règle qui croise deux properties n'est pas une constraint de property** :
il lui faut le scope class.

**Une property typée jamais affectée est validée comme `null`** : `NotBlank` la
refuse, mais `Length` ou `Email` la laissent passer.

## Application

- « Le panier ne dépasse pas le plafond du client » → un accessor
  `isWithinLimit()` avec `IsTrue`.
- `verifyStock()` porte `#[Assert\IsTrue]` et la validation lève une
  `MappingException` → renommer en `hasStock()`.

## QCM d'entraînement

**Question 1.** `#[Assert\IsTrue]` est posé sur
`private function hasValidStock(): bool`, qui rend `false`. Quel path porte la
violation ?

- **A.** `hasValidStock`
- **B.** `hasValidStock()`
- **C.** Aucun : une method privée n'est pas lue
- **D.** `validStock`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Le préfixe `has` est retiré et la première lettre passe
en minuscule ; la visibilité privée ne gêne pas.

</details>

**Question 2.** `#[Assert\IsTrue]` est posé sur
`public function isAvailable(\DateTimeInterface $at): bool`. Que se passe-t-il ?

- **A.** Une `MappingException` au chargement des metadata
- **B.** Une `ArgumentCountError` à la validation
- **C.** Le validator passe la date du jour en argument
- **D.** La constraint est ignorée sans erreur

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Le nom est accepté au chargement ; c'est l'appel sans
argument, au moment de valider, que PHP refuse.

</details>

**Question 3.** Un DTO déclare `#[Assert\Length(min: 3)] public string $code;`,
jamais affectée. Que rend la validation pour `code` ?

- **A.** Une `Error` : property typée lue avant initialisation
- **B.** Une violation « too short », la longueur valant 0
- **C.** Aucune violation
- **D.** Une violation « should not be blank »

<details>
<summary>Correction</summary>

**Bonne réponse : C.** La property non initialisée est validée comme `null`,
et `Length` laisse passer `null`.

</details>

**Question 4.** Une constraint maison ne redéfinit pas `getTargets()`. Où
peut-on la poser ?

- **A.** Sur une property ou un accessor
- **B.** Sur une property seulement, jamais sur une method
- **C.** Partout, la class comprise
- **D.** Sur la class seulement

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Le défaut, `PROPERTY_CONSTRAINT`, couvre properties et
accessors ; posée sur une class, elle lève une `ConstraintDefinitionException`.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- Trois cibles : **property** (stocké), **accessor** (calculé), **class**
  (croisé).
- La visibilité ne compte jamais.
- Accessor : `get` / `is` / `has`, sans argument ; path **sans préfixe**.
- Mauvais nom : `MappingException` ; argument requis : `ArgumentCountError`.
- Property typée non initialisée : validée comme `null`.
- Règle entre deux fields : `Callback` de class + `atPath()`.

Moyen mnémotechnique : **« Stocké, Calculé, Croisé »** — property, accessor,
class.

## Sources officielles

- [Validation, « Constraint Targets »](https://github.com/symfony/symfony-docs/blob/8.0/validation.rst)
- [Custom Validation Constraint, « Class Constraint Validator »](https://github.com/symfony/symfony-docs/blob/8.0/validation/custom_constraint.rst)
- [Validator 8.0, `AttributeLoader`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Mapping/Loader/AttributeLoader.php)

