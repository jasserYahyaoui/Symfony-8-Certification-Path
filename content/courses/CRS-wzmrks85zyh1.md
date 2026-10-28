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

## Avant la règle : le problème à résoudre

Trois règles pour un même `Author` :

1. le prénom fait au moins trois caractères ;
2. le mot de passe ne reprend pas le prénom ;
3. la date de fin de contrat ne précède pas la date de début.

La première porte sur **une valeur stockée**. La deuxième sur **une valeur
calculée**, qui n'existe dans aucune property. La troisième **croise deux
properties**. Une constraint de property ne sait faire que la première : il
faut d'autres endroits où poser une constraint. Ce sont les **scopes**.

## Le fil rouge : un `Author`

```php
class Author
{
    #[Assert\Length(min: 3)]
    private string $firstName;

    private string $password;
    private \DateTimeImmutable $start;
    private \DateTimeImmutable $end;
}
```

## Le cycle complet en un schéma

```text
une constraint posée sur…
     │
     ├── une PROPERTY ─────────── la valeur stockée (privée comprise, lue par réflexion)
     │                            non initialisée → validée comme null
     │
     ├── une METHOD get…/is…/has… ── sa valeur de RETOUR, calculée à la validation
     │                            path = nom sans préfixe : isPasswordSafe → passwordSafe
     │                            autre nom → MappingException au chargement
     │                            argument requis → ArgumentCountError à la validation
     │
     └── la CLASS ─────────────── l'object entier : la seule qui voit tout
                                  (Callback, constraint dont getTargets() rend 'class')
```

## Les trois cibles

Une constraint s'applique à une **property**, à un **accessor**, ou à la
**class entière**.

| Cible | Ce qu'elle valide | Quand la choisir |
|---|---|---|
| property | la valeur stockée | le cas courant |
| accessor | la valeur **retournée** par une method | la règle est calculée |
| class | l'object entier | la règle croise plusieurs properties |

## La property

```php
#[Assert\NotBlank]
private string $name;
```

La visibilité n'a aucune importance : le validator lit la property par
réflexion, y compris `private`.

Une property **typée non initialisée** est validée comme `null`, prévient la
documentation 8.0. Exécuté avec `symfony/validator` 8.0.15 : `NotBlank` sur
`public string $name;` jamais affectée signale une violation de valeur
invalide `null` ; `Length(min: 3)` sur une autre property non initialisée ne
signale rien, puisqu'elle ignore `null`.

## L'accessor

C'est le scope qui se retient mal. Une constraint peut porter sur la return value d'une method dont le nom commence par **`get`**, **`is`** ou **`has`**.
La **visibilité est libre** — `private`, `protected` ou `public`.

```php
#[Assert\IsTrue(message: 'Le mot de passe ne peut pas reprendre le prénom.')]
private function isPasswordSafe(): bool
{
    return $this->firstName !== $this->password;
}
```

L'intérêt est de valider une règle qui n'existe dans aucune property : la valeur
est calculée au moment de la validation.

En YAML, la documentation 8.0 range ces constraints sous `getters:`, avec le nom
**sans préfixe** : `passwordSafe`.

Exécuté avec `symfony/validator` 8.0.15 :

| Method annotée | Résultat |
|---|---|
| `private function isPasswordSafe()` | violation au path `passwordSafe` |
| `public function getFullName()` | violation au path `fullName` |
| `private function checkPasswordSafety()` | **`MappingException`** : « Constraints can only be added on methods beginning with "get", "is" or "has". » |
| `public function isOk(int $x)` | **`ArgumentCountError`** à la validation |
| `public static function isStat()` | violation au path `stat` : accepté |
| `public function ISOK()` | violation au path `oK` : le préfixe ignore la casse |

Le path de la violation est le nom **sans le préfixe**, première lettre en
minuscule. Un mauvais nom n'est donc pas ignoré en silence : la lecture des
métadonnées échoue. L'absence d'argument, elle, n'est pas vérifiée au
chargement ; c'est l'appel qui casse.

## La class

Certaines constraints s'appliquent à l'object lui-même, `Callback` en tête. Une
constraint personnalisée y accède en retournant `Constraint::CLASS_CONSTRAINT`
depuis sa method `getTargets()` ; par défaut, elle retourne
`Constraint::PROPERTY_CONSTRAINT`, qui couvre properties **et** accessors.
Exécuté : `NotBlank` retourne `'property'`, `Callback` les deux cibles.

C'est la seule portée qui voit **toutes** les properties à la fois, donc la seule
qui puisse exprimer une règle croisée — « la date de fin suit la date de
début ». Exécuté avec un `#[Assert\Callback('check')]` de class qui rattache la
violation à `end` par `atPath('end')`.

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

`atPath()` rattache la violation à une property, pour qu'un form l'affiche sous
le bon champ ; sans lui, elle est rattachée à l'object (path vide).

## Concrètement dans ton application

- Format, longueur, présence d'une valeur : une constraint de **property**.
- Une valeur dérivée (« le mot de passe n'est pas le prénom », « le panier
  n'est pas vide ») : un **accessor** `is…` ou `has…` avec `IsTrue`.
- Une cohérence entre champs (dates, montants) : un `Callback` de **class**, et
  `atPath()` pour placer l'erreur.
- Un DTO aux properties typées sans default value : penser qu'une property non
  affectée sera validée comme `null`.

## À comprendre

- **Pourquoi un préfixe imposé ?** Pour que la method soit lue comme une
  valeur de l'object et nommée comme une property : `isPasswordSafe` devient
  `passwordSafe`.
- **Pourquoi une erreur et pas un oubli silencieux ?** Parce qu'une constraint
  ignorée laisserait passer des données invalides : mieux vaut casser au
  chargement.
- **Pourquoi la class pour une règle croisée ?** Parce qu'une constraint de
  property ne reçoit que **sa** valeur ; seule la class reçoit l'object.

## À mémoriser

- Trois cibles : property, accessor, class.
- Property : visibilité libre ; non initialisée → `null`.
- Accessor : `get` / `is` / `has` (casse indifférente), sans argument,
  visibilité libre, statique accepté ; path sans préfixe.
- Mauvais nom → `MappingException` ; argument requis → `ArgumentCountError`.
- Class : `Callback` ou `getTargets()` qui rend `Constraint::CLASS_CONSTRAINT` ;
  `atPath()` pour viser un champ.
- Moyen mnémotechnique : **« Stocké, Calculé, Croisé »** — property, accessor,
  class.

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

## Application : trois scénarios

**Scénario 1 — Le mot de passe et le prénom.** *Solution.* Une method privée
`isPasswordSafe(): bool` portant `#[Assert\IsTrue(message: …)]` ; la violation
apparaît au path `passwordSafe`.

**Scénario 2 — Les dates d'un contrat.** *Solution.* `#[Assert\Callback('check')]`
sur la class ; dans `check()`, `buildViolation(...)->atPath('end')`.

**Scénario 3 — L'accessor qui casse tout.** Une method
`verifyStock()` porte `#[Assert\IsTrue]`, et la validation lève une
`MappingException`. *Solution.* Renommer en `hasStock()` ou `isInStock()` : le
préfixe est obligatoire.

## QCM d'entraînement

Six questions sur les scopes. Engage-toi avant d'ouvrir la correction.

**Question 1.** `#[Assert\IsTrue]` est posé sur `private function hasValidStock(): bool`, qui rend `false`. Quel path porte la violation ?

- **A.** `hasValidStock`
- **B.** `validStock`
- **C.** `hasValidStock()`
- **D.** Aucun : une method privée n'est pas lue

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Le préfixe est retiré et la première lettre passe en
minuscule (exécuté : `isPasswordSafe` donne `passwordSafe`).

A et C gardent le préfixe. D est le piège : la visibilité est libre.

</details>

**Question 2.** `#[Assert\IsTrue]` est posé sur `public function verifyStock(): bool`. Que se passe-t-il ?

- **A.** La constraint est ignorée, la validation passe
- **B.** La constraint est appliquée, le path vaut `verifyStock`
- **C.** Une `MappingException` au chargement des metadata
- **D.** Une `ArgumentCountError` à la validation

<details>
<summary>Correction</summary>

**Bonne réponse : C.** « Constraints can only be added on methods beginning
with "get", "is" or "has". » (exécuté).

A est le piège : ce n'est pas un oubli silencieux. B invente une règle. D
concerne une method qui exige un argument.

</details>

**Question 3.** Une réservation exige `$checkout` après `$checkin`. Où poser la règle ?

- **A.** Une constraint sur la property `$checkout`, qui reçoit l'object
- **B.** Un accessor `getCheckout()` avec `GreaterThan`
- **C.** Une constraint `Callback` de class
- **D.** Une constraint sur chacune des deux properties

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Seul le scope class voit toutes les properties à la
fois ; `atPath('checkout')` place l'erreur sur le bon champ.

A est le piège : une constraint de property ne reçoit que sa valeur. B ne voit
que la valeur retournée. D ne compare rien.

</details>

**Question 4.** Un DTO déclare `#[Assert\Length(min: 3)] public string $code;`, jamais affectée. Que rend la validation pour `code` ?

- **A.** Aucune violation
- **B.** Une `Error` : property typée lue avant initialisation
- **C.** Une violation « too short », la longueur valant 0
- **D.** Une violation « should not be blank »

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Une property typée non initialisée est validée comme
`null` (documentation 8.0), et `Length` ignore `null` (exécuté).

B est le piège : l'erreur PHP n'a pas lieu, le Validator lit `null`. C confond
`null` et `''`. D décrit `NotBlank`, absente ici.

</details>

**Question 5.** `#[Assert\IsTrue]` est posé sur `public function isAvailable(\DateTimeInterface $at): bool`. Que se passe-t-il ?

- **A.** Une `MappingException` au chargement : un accessor ne prend pas d'argument
- **B.** Le Validator passe la date du jour comme argument
- **C.** La constraint est ignorée, faute d'argument disponible
- **D.** Une `ArgumentCountError` au moment de la validation

<details>
<summary>Correction</summary>

**Bonne réponse : D.** L'absence d'argument n'est pas vérifiée au chargement ;
c'est l'appel qui casse (exécuté avec `isOk(int $x)`).

A est le piège : le nom est valide, et le chargement ne contrôle pas la
signature. B et C inventent des comportements.

</details>

**Question 6.** Que retourne `getTargets()` d'une constraint personnalisée qui ne la redéfinit pas ?

- **A.** `Constraint::CLASS_CONSTRAINT`
- **B.** `Constraint::PROPERTY_CONSTRAINT`
- **C.** Un array des deux cibles
- **D.** `null`, la cible étant déduite de l'endroit où on la pose

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Cette valeur couvre les properties **et** les
accessors. Exécuté : `NotBlank` retourne `'property'`. Pour une constraint de
class, il faut redéfinir `getTargets()`.

A est l'inverse. C décrit `Callback`. D invente un comportement.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- Trois cibles : **property** (stocké), **accessor** (calculé), **class**
  (croisé).
- La visibilité ne compte jamais.
- Accessor : `get` / `is` / `has`, sans argument ; path **sans préfixe**.
- Mauvais nom : `MappingException` ; argument requis : `ArgumentCountError`.
- Property typée non initialisée : validée comme `null`.
- Règle entre deux champs : `Callback` de class + `atPath()`.

**En une phrase, pour tout reconstruire :**

```text
STOCKÉ → property | CALCULÉ → get/is/has (path sans préfixe) | CROISÉ → class (Callback, atPath)
```

## Sources officielles

- [Validation, « Constraint Targets »](https://github.com/symfony/symfony-docs/blob/8.0/validation.rst)
- [Custom Validation Constraint, « Class Constraint Validator »](https://github.com/symfony/symfony-docs/blob/8.0/validation/custom_constraint.rst)
- [Validator 8.0, `AttributeLoader`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Mapping/Loader/AttributeLoader.php)
