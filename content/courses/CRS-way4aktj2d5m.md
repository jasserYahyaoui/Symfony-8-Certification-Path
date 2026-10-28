---
id: CRS-way4aktj2d5m
official_item: OIT-6wd8860brzfy
title: "Validator component"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-28"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Validator/ValidatorInterface.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Validator/ValidatorInterface.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "ValidatorInterface"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/validation.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/validation.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: "Using the Validator, Validation Callables, findByCodes(), getConstraint(), Debugging the Constraints"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/ValidatorBuilder.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/ValidatorBuilder.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "ValidatorBuilder::enableAttributeMapping(), default false"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/validator/resources.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/components/validator/resources.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: "Loading Resources, attribute mapping"
    verified_at: "2026-10-28"
outcome_sections:
  OUT-wsh6jsvs5s6f: "Les trois manières de valider"
  OUT-0enczxzp4pwq: "Ce qu'il retourne : une liste, jamais un bool"
  OUT-kgffdypsnscc: "Les trois manières de valider"
---

## Objectif

Savoir ce que le component valide, comment on l'appelle, et ce qu'il retourne.
Les constraints elles-mêmes appartiennent aux items *Built-in validation
constraints* et *Custom callback validators*.

## Avant la règle : le problème à résoudre

Une API reçoit l'inscription d'un utilisateur : un email, un nom, une adresse.
Avant d'enregistrer quoi que ce soit, il faut savoir si ces données respectent
des règles — email bien formé, nom d'au moins trois caractères, ville
renseignée — et, sinon, **lesquelles** sont violées, pour les afficher.

Écrire ces `if` à la main dans chaque controller les disperserait et les
dupliquerait. Le Validator sépare les deux moitiés :

- les **règles** (les constraints) sont déclarées une fois, sur la class ;
- la **vérification** est un seul appel, qui rend la liste de ce qui ne va pas.

## Le fil rouge : un `User` et son adresse

```php
use Symfony\Component\Validator\Constraints as Assert;

class Address
{
    #[Assert\NotBlank]
    public string $city = '';
}

class User
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email = 'x';

    #[Assert\Length(min: 3)]
    public string $name = 'ab';

    #[Assert\Valid]
    public Address $address;
}
```

Les trois properties sont invalides : `x` n'est pas un email, `ab` est trop
court, la ville est vide.

## Le cycle complet en un schéma

```text
ENTRÉE      un object (ses constraints sont lues sur la class)
            | une valeur + des constraints passées en argument
     │
     ▼
LECTURE     framework : constraints lues (attributes, YAML, XML)
            hors framework : createValidator() NE LIT PAS les attributes
            ── sans enableAttributeMapping(), l'object paraît valide
     │
     ▼
VÉRIFIER    chaque constraint des groups demandés (« Default » par défaut)
            ── donnée invalide → une VIOLATION (jamais d'exception)
            ── constraint mal configurée, scalaire sans constraint → EXCEPTION
     │
     ▼
RÉSULTAT    ConstraintViolationList : vide = valide ; tester count(), pas le bool
```

## Ce qu'il valide

Le Validator confronte une **valeur** à des **constraints**. Le cas courant est
un object dont les properties portent des constraints, mais l'entrée peut aussi
être une valeur nue :

```php
$violations = $validator->validate($email, new Assert\Email());
```

Dans une application Symfony, on injecte `ValidatorInterface` par autowiring —
le service `validator`, selon la documentation 8.0 ; le framework configure la
lecture des constraints.

### Hors framework : le piège des attributes

C'est un component **autonome**, mais `Validation::createValidator()` construit
un validator **qui ne lit pas les attributes** : `ValidatorBuilder` démarre avec
la lecture des attributes désactivée. Exécuté avec `symfony/validator` 8.0.15,
sur le `User` du fil rouge :

| Validator | Violations |
|---|---|
| `Validation::createValidator()` | **0** |
| `Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()` | 3 |

Aucune erreur ne signale l'oubli : l'object paraît simplement valide. Les
constraints passées en argument, elles, s'appliquent dans les deux cas (exécuté :
`validate('x', new Assert\Email())` rend une violation avec les deux).

## Les trois manières de valider

```php
$validator->validate($object);                                  // l'objet entier
$validator->validateProperty($object, 'email');                 // une propriété
$validator->validatePropertyValue(User::class, 'email', $value); // une valeur candidate
```

La troisième mérite d'être connue : elle valide une valeur **contre les
constraints déclarées** pour cette property, **sans que l'object la porte**.
Elle accepte d'ailleurs un nom de class à la place d'une instance. Exécuté :
`ok@example.com` donne 0 violation, `bad` en donne 1.

Chaque method accepte en dernier argument les groups à appliquer. Un group
qu'aucune constraint ne porte ne lève rien : exécuté, `validate($user, null,
['nope'])` rend une liste **vide** pour un `User` pourtant invalide.

Exécuté : `validateProperty()` sur une property **inexistante** ne lève rien et
retourne une liste vide.

| Besoin | Method |
|---|---|
| tout l'object, constraints de classe comprises | `validate($object)` |
| une property d'un object existant | `validateProperty($object, 'email')` |
| une valeur **avant** de l'affecter | `validatePropertyValue(User::class, 'email', $candidat)` |
| une valeur isolée, sans class | `validate($valeur, new Assert\Email())` |

## Ce qu'il retourne : une liste, jamais un bool

Toujours une `ConstraintViolationListInterface` — **jamais un bool**. Une
validation réussie retourne une liste **vide**. La class concrète est
`ConstraintViolationList` : `Countable`, parcourable, accessible par indice
(`$violations[0]`), et convertible en chaîne pour le debug.

```php
if (0 !== count($violations)) {
    foreach ($violations as $violation) {
        $violation->getMessage();
        $violation->getPropertyPath();   // 'email', 'address.city'
        $violation->getInvalidValue();   // 'x'
        $violation->getCode();           // un UUID propre à la contrainte
        $violation->getConstraint();     // l'objet Email lui-même
    }
}
```

Exécuté sur le fil rouge : trois violations, de paths `email`, `address.city`
(l'object imbriqué par `#[Assert\Valid]`) et `name` ; le code d'`Email` est
`bd79c0ab-ddba-46cc-a703-a7a4b08de310`, la constante
`Assert\Email::INVALID_FORMAT_ERROR`.

Tester `if ($violations)` est faux : exécuté, une liste vide convertie en booléen
vaut `true`. Le test correct porte sur `count()`.

### Trier et afficher

- **Filtrer par code** : la documentation 8.0 montre `findByCodes()`. Exécuté,
  `findByCodes(Assert\Email::INVALID_FORMAT_ERROR)` garde 1 violation sur 3.
- **Afficher pour déboguer** : `(string) $violations` donne, exécuté,
  « Object(V\User).email: This value is not a valid email address. (code
  bd79c0ab-…) », une ligne par violation.
- **Dans Twig** : la liste se parcourt, `{{ error.message }}` pour chaque
  violation (documentation 8.0).

## Donnée invalide ou configuration invalide

Une **donnée** invalide ne lève rien, même quand son type ne convient pas.
Exécuté : un array soumis à `Email`, ou un object soumis à `Length`, produit la
violation « This value should be of type string. ».

Deux cas lèvent, en revanche :

- une **constraint** mal configurée, dès sa construction : `new
  Assert\Length()` sans `min` ni `max` lève `MissingOptionsException`, « Either
  option "min" or "max" must be given » ;
- une valeur **scalaire ou `null` sans constraint** : `validate('x')` lève une
  `RuntimeException`, « Cannot validate values of type "string" automatically.
  Please provide a constraint. » (`"null"` pour `null`). Un object ou un array
  sans constraint donne, lui, une liste vide.

| Situation | Résultat |
|---|---|
| donnée invalide, même mal typée | violation |
| constraint mal configurée | exception à sa construction |
| scalaire ou `null` sans constraint | `RuntimeException` |
| object ou array sans constraint | liste vide |

## Deux raccourcis de `Validation`

| Method | Valeur invalide | Valeur valide |
|---|---|---|
| `Validation::createIsValidCallable(...)` | la function rend `false` | `true` |
| `Validation::createCallable(...)` | la function **lève** `ValidationFailedException` | elle rend la valeur |

Exécuté avec `Email` sur `'x'` et sur `'a@b.co'`. Ils servent là où l'on attend
une function, par exemple `setAllowedValues()` d'OptionsResolver ou la
validation d'une réponse en console.

## Concrètement dans ton application

- Un controller ou un service : injecte `ValidatorInterface`, puis
  `count($violations)`.
- Un formulaire : le plus souvent, la validation passe par le formulaire, sans
  appel direct (documentation 8.0).
- Un script ou une bibliothèque hors framework :
  `createValidatorBuilder()->enableAttributeMapping()->getValidator()`.
- Un traitement réservé à une erreur précise : `findByCodes()` avec la constante
  de la constraint.
- Pour voir les constraints d'une class : `php bin/console debug:validator
  'App\Entity\User'`, ou un dossier entier (`src/Entity`).

## À comprendre

- **Pourquoi une liste et pas un bool ?** Parce qu'il faut savoir **quoi**
  corriger : chaque violation porte un message, un path et un code.
- **Pourquoi pas d'exception pour une donnée invalide ?** Parce qu'une donnée
  invalide est un cas normal (un utilisateur se trompe) ; seule une erreur du
  développeur — constraint mal configurée, valeur sans règle — en est une.
- **Pourquoi `createValidator()` ignore-t-il les attributes ?** Parce que le
  component est autonome : le builder n'active que ce qu'on lui demande.
- **Pourquoi un group inconnu rend-il une liste vide ?** Parce que le Validator
  ne vérifie que les constraints des groups demandés ; aucune n'appartient à un
  group que personne n'a déclaré.

## À mémoriser

- Trois entrées : `validate()`, `validateProperty()`, `validatePropertyValue()`
  (cette dernière accepte un nom de class).
- Retour : `ConstraintViolationList`, **vide = valide** ; tester `count()`.
- Violation : `getMessage()`, `getPropertyPath()`, `getInvalidValue()`,
  `getCode()`, `getConstraint()`.
- Hors framework : `enableAttributeMapping()`, sinon 0 violation.
- Donnée invalide → violation ; constraint mal configurée ou scalaire sans
  constraint → exception.
- `createIsValidCallable()` rend un bool ; `createCallable()` lève.
- Moyen mnémotechnique : **« Règles sur la class, liste en retour, `count()`
  pour conclure »**.

## Pièges d'examen

**`validate()` ne lève rien pour une donnée invalide**, même mal typée ; une
constraint mal configurée ou un scalaire sans constraint, si.

**Une liste vide est un object.** Seul `count()` dit si la validation a réussi.

**`createValidator()` ignore les attributes** : il faut
`enableAttributeMapping()`.

**`validatePropertyValue()` n'a pas besoin de l'object peuplé** : elle teste une
valeur candidate contre les constraints de la property.

**Un group mal orthographié fait paraître l'object valide** : aucune constraint
n'y appartient, la liste rendue est vide, sans erreur.

## Application : trois scénarios

**Scénario 1 — Un endpoint d'inscription.** *Solution.* `$violations =
$validator->validate($user)` ; si `count($violations) > 0`, renvoyer pour chaque
violation son `getPropertyPath()` et son `getMessage()`.

**Scénario 2 — Vérifier un email avant de créer le `User`.** Le formulaire
d'inscription vérifie la saisie au fil de l'eau. *Solution.*
`validatePropertyValue(User::class, 'email', $saisie)` : les constraints de la
property, sans instance.

**Scénario 3 — Un script CLI qui valide des imports.** Tout paraît valide, même
des lignes visiblement fausses. *Solution.* Le validator vient de
`Validation::createValidator()`, qui ne lit pas les attributes : construis-le
avec `createValidatorBuilder()->enableAttributeMapping()`.

## QCM d'entraînement

Six questions sur le Validator. Arrête ta lettre avant d'ouvrir la correction.

**Question 1.** Un `Product` porte `#[Assert\NotBlank]` sur `$name`, laissé vide. Un script hors framework appelle `Validation::createValidator()->validate($product)`. Combien de violations ?

- **A.** Une, celle de `NotBlank`, comme dans le framework
- **B.** Aucune : ce validator ne lit pas les attributes
- **C.** Une exception : aucun loader de metadata n'est configuré
- **D.** Une, mais seulement en mode debug

<details>
<summary>Correction</summary>

**Bonne réponse : B.** `ValidatorBuilder` démarre avec la lecture des
attributes désactivée ; exécuté, 0 violation. Il faut
`createValidatorBuilder()->enableAttributeMapping()->getValidator()`.

A est le résultat dans le framework, pas ici. C invente une exception :
l'oubli est silencieux. D invente un mode.

</details>

**Question 2.** Que vaut `(bool) $validator->validate($validProduct)` pour un object qui respecte toutes ses constraints ?

- **A.** `false`, puisque la validation n'a rien trouvé
- **B.** `null` converti, donc `false`
- **C.** `true` : une liste vide reste un object
- **D.** Une erreur, car la liste ne se convertit pas en booléen

<details>
<summary>Correction</summary>

**Bonne réponse : C.** `validate()` rend toujours une
`ConstraintViolationList` ; un object vaut `true` en booléen (exécuté). Le bon
test est `count($violations)`.

A est le piège : l'intuition « vide = faux » vaut pour un array, pas pour un
object. B invente un retour `null`. D invente une erreur.

</details>

**Question 3.** Le formulaire d'inscription veut vérifier la saisie `nope` contre les constraints de `Customer::$email`, avant de créer le moindre `Customer`. Quelle method ?

- **A.** `validatePropertyValue(Customer::class, 'email', 'nope')`
- **B.** `validateProperty(Customer::class, 'email')`
- **C.** `validate('nope')`, sans constraint
- **D.** `validate(new Customer(), null, ['email'])` sur un object vide

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Elle accepte un nom de class et teste une valeur
candidate contre les constraints de la property (exécuté : `bad` donne une
violation).

B valide la valeur **portée** par un object, et n'a pas de valeur ici. C lève
une `RuntimeException`. D passe `email` comme nom de **group**.

</details>

**Question 4.** Que fait `$validator->validate(['a', 'b'], new Assert\Email())` ?

- **A.** Il lève une `UnexpectedTypeException`
- **B.** Il valide chaque élément de l'array comme un email
- **C.** Il rend une liste vide : un array est ignoré par `Email`
- **D.** Une violation : la valeur doit être une string

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Une donnée invalide, même mal typée, produit une
violation : « This value should be of type string. » (exécuté).

A est le piège : le type inattendu ne lève pas ici. B confond avec `All`. C
invente un passage silencieux.

</details>

**Question 5.** Un `User` est invalide dans le group `Default`. Le code appelle `$validator->validate($user, null, ['Registraton'])`, avec une faute de frappe. Que se passe-t-il ?

- **A.** Une exception : le group n'existe pas
- **B.** Une liste vide, sans erreur
- **C.** Le group `Default` est utilisé à la place
- **D.** Les constraints de tous les groups sont vérifiées

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Aucune constraint n'appartient à ce group : seules
les constraints des groups demandés sont vérifiées ; exécuté avec `['nope']`, la liste est vide pour un object invalide.

A invente un contrôle des noms de groups. C et D inventent un repli.

</details>

**Question 6.** Une option d'OptionsResolver doit refuser une adresse invalide, avec un `false` et non une exception. Quel raccourci de `Validation` ?

- **A.** `Validation::createCallable(new Assert\Email())`
- **B.** `Validation::createValidator()` avec une constraint `Email`
- **C.** `Validation::createIsValidCallable(new Assert\Email())`
- **D.** `Validation::createValidatorBuilder()->getValidator()`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** La function rend `false` pour une valeur invalide
(exécuté sur `'x'`).

A est le piège : `createCallable()` lève `ValidationFailedException`. B et D
construisent un validator, pas une function.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- Le Validator confronte une **valeur** à des **constraints** ; il rend
  **toujours** une `ConstraintViolationList`.
- **Vide = valide** ; tester `count()`, jamais `if ($violations)`.
- Trois entrées : `validate()`, `validateProperty()`,
  `validatePropertyValue()` (nom de class accepté).
- Hors framework, `createValidator()` **ignore les attributes** :
  `enableAttributeMapping()`.
- Donnée invalide → **violation** ; constraint mal configurée ou scalaire sans
  constraint → **exception**.
- Un group inconnu → liste **vide**, sans erreur.
- `createIsValidCallable()` rend un bool ; `createCallable()` lève.

**En une phrase, pour tout reconstruire :**

```text
RÈGLES sur la class (lues ? enableAttributeMapping hors framework)
  → validate*() pour les groups demandés → LISTE de violations → count() pour conclure
```

## Sources officielles

- [ValidatorInterface, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Validator/ValidatorInterface.php)
- [Validation, « Using the Validator »](https://github.com/symfony/symfony-docs/blob/8.0/validation.rst)
- [Loading Resources](https://github.com/symfony/symfony-docs/blob/8.0/components/validator/resources.rst) et [Validator 8.0, `ValidatorBuilder`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/ValidatorBuilder.php)
