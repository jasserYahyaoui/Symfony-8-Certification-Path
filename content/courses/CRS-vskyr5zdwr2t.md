---
id: CRS-vskyr5zdwr2t
official_item: OIT-kkhb3wd341ex
title: "Validation groups"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-28"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/validation/groups.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/validation/groups.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: '"How to Apply only a Subset of all Your Validation Constraints (Validation Groups)"; "Constraints in the Default group of a class are the constraints that have either no explicit group configured or that are configured to a group equal to the class name or the string Default"; inheritance and the class-name group'
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Constraint.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraint.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "Constraint::addImplicitGroupName()"
    verified_at: "2026-10-28"
outcome_sections:
  OUT-yme605pqpcbw: "Les groups implicites"
  OUT-s73ykpqy5sch: "Valider avec un group"
  OUT-vnsvr71xs30c: "`Default` contre le nom de la class"
---

## Objectif

N'appliquer qu'une partie des constraints d'une class, et connaître les deux
groups que Symfony crée sans qu'on les déclare.

## Avant la règle : le problème à résoudre

La même class `User` sert à deux moments :

- à l'**inscription**, le mot de passe est obligatoire et doit faire au moins
  sept caractères ;
- à la **modification du profil**, l'utilisateur change sa ville ; le mot de
  passe n'est pas dans le formulaire.

Si toutes les constraints s'appliquaient toujours, la modification du profil
échouerait sur un mot de passe que personne n'a saisi. Il faut pouvoir dire :
« ici, ne vérifie que **ces** règles-là ».

Un **group** est une étiquette posée sur une constraint ; à la validation, on
choisit les étiquettes à appliquer.

## Le fil rouge : un `User` à deux usages

```php
class User
{
    #[Assert\Email(groups: ['registration'])]
    private string $email;

    #[Assert\NotBlank(groups: ['registration'])]
    #[Assert\Length(min: 7, groups: ['registration'])]
    private string $password;

    #[Assert\Length(min: 2)]
    private string $city;
}
```

`city` n'a pas de group explicite ; `email` et `password` sont étiquetés
`registration`.

## Le cycle complet en un schéma

```text
DÉCLARATION   constraint sans group      → groups ["Default", "User"]
              constraint groups: ['Default'] → ["Default", "User"]
              constraint groups: ['User']    → ["User"] seulement
              constraint groups: ['registration'] → ["registration"]
     │
     ▼
VALIDATION    validate($user)                      → group Default
              validate($user, null, ['registration']) → registration seul
              validate($user, null, ['Default', 'registration']) → les deux
     │
     ▼
CASCADE (#[Assert\Valid])
              Default      → l'object imbriqué est validé dans Default
              User         → l'object imbriqué est validé dans « User » (souvent rien)
              registration → l'object imbriqué est validé dans registration
```

## Les groups implicites

C'est ce qui se rate. Cette class définit **trois** groups, dont deux que
personne n'a écrits :

| Group | Contenu |
|---|---|
| `Default` | les constraints sans group explicite — ici `city` |
| `User` | **le nom de la class** ; les constraints de `User` dans `Default` |
| `registration` | les constraints explicitement étiquetées — ici `email` et `password` |

Exécuté avec `symfony/validator` 8.0.15 : `new Assert\NotBlank()` porte
d'abord `["Default"]` ; une fois rattachée à la class `User`
(`addImplicitGroupName('User')`), elle porte `["Default", "User"]`. Déclarée
`groups: ['Default']`, elle reçoit aussi `["Default", "User"]`.

**Un group ne se déclare nulle part** : il existe dès qu'une constraint le
nomme.

### Un écart entre la documentation et le code

`groups.rst` (8.0) range dans `Default` les constraints « sans group, ou
configurées avec le nom de la class ou la string `Default` ». Pour le nom de la
class, le code dit autre chose. Exécuté : une constraint déclarée
`groups: ['User']` a pour seuls groups `["User"]`, et **ne s'exécute pas**
quand on valide `Default` ; elle s'exécute avec le group `User`. La relation ne
vaut que dans un sens : ce qui est dans `Default` est dans `User`, pas
l'inverse.

## Valider avec un group

```php
$validator->validate($user);                             // Default
$validator->validate($user, null, ['registration']);     // registration seul
$validator->validate($user, null, ['Default', 'registration']);
```

Sans argument, **seul `Default` s'applique** — pas « toutes les constraints ».
Une constraint rangée dans un group personnalisé est donc invisible par défaut.
Le troisième argument accepte aussi une string seule (exécuté :
`validate($user, null, 'Default')` équivaut à `['Default']`).

Exécuté sur une class `User` avec une `Address` en cascade :

| Appel | Violations |
|---|---|
| `validate($user)` | `city`, `address.zip` |
| `validate($user, null, ['registration'])` | `email`, `address.street` |
| `validate($user, null, ['Default', 'registration'])` | `email`, `city`, `address.zip`, `address.street` |

Un nom de group inconnu ne lève rien : il ne sélectionne simplement aucune
constraint.

Dans un form, la même chose s'écrit avec l'option `validation_groups` du
type ; la documentation 8.0 renvoie à `form/validation_groups`.

## `Default` contre le nom de la class

Les deux sont identiques… sauf sur les objects **imbriqués**. Exécuté avec un
`User` dont la property `address` porte `#[Assert\Valid]` :

| Group demandé | Violations |
|---|---|
| `Default` | `city`, `address.zip` |
| `User` | `city`, sans `address.zip` |
| `registration` | `email`, `address.street` |

Valider dans `Default` descend dans les constraints `Default` de l'`Address` ;
valider dans `User` ne le fait pas, car le group transmis à l'`Address` reste
`User`. Un group nommé, lui, traverse la cascade : `address.street`, étiquetée
`registration`, est atteinte.

### Et avec l'héritage

La documentation 8.0 l'ajoute, et l'exécution le confirme avec
`User extends BaseUser`, chacun portant un `NotBlank` sans group :

| Group demandé | Violations |
|---|---|
| `Default` ou `User` | celles de `User` **et** de `BaseUser` |
| `BaseUser` | celles de `BaseUser` seulement |

La troisième différence apparaît avec une group sequence ; elle est traitée
dans l'item *Group sequence*.

## Concrètement dans ton application

- Un formulaire d'inscription : `validation_groups: ['Default',
  'registration']`, pour garder les règles communes.
- Un formulaire de profil : rien, ou `['Default']`.
- Une règle qui ne doit servir qu'à un moment précis : un group nommé, jamais le
  nom de la class.
- Une validation qui « ne voit plus rien » : vérifie le nom du group passé, une
  faute ne lève pas d'erreur.

## À comprendre

- **Pourquoi `Default` seul par défaut ?** Parce que les groups nommés sont,
  par construction, des règles **de situation** : les appliquer toujours
  annulerait leur intérêt.
- **Pourquoi un group « nom de la class » ?** Pour désigner « les règles par
  défaut de **cette** class » sans entraîner celles des objects imbriqués.
- **Pourquoi la cascade diffère-t-elle ?** Parce que le group demandé est
  transmis tel quel à l'object imbriqué : `Default` y a un sens, `User` presque
  jamais.

## À mémoriser

- Une constraint sans group : `Default` + nom de la class.
- Trois groups pour le fil rouge : `Default`, `User`, `registration`.
- `validate($o)` = `Default` seulement ; le troisième argument accepte un
  array ou une string.
- `groups: ['User']` : **pas** dans `Default` (contrairement à ce que laisse
  croire la documentation).
- Cascade : `Default` descend, `User` non, un group nommé oui.
- Héritage : le group de la sous-classe inclut le parent ; le group du parent,
  seulement le parent.
- Moyen mnémotechnique : **« Default par défaut, le reste sur demande »**.

## Pièges d'examen

**Sans group passé, seul `Default` est validé.** Une constraint étiquetée
`registration` ne s'exécute jamais si personne ne demande ce group.

**`Default` et le nom de la class ne sont pas interchangeables** dès qu'il y a
un object imbriqué.

**Une constraint étiquetée du seul nom de la class n'est pas dans `Default`**,
quoi qu'en dise la documentation.

**Un group ne se déclare nulle part** : il existe dès qu'une constraint le
nomme.

## Application : trois scénarios

**Scénario 1 — L'inscription.** Le mot de passe doit être vérifié à
l'inscription, et la ville toujours. *Solution.* `password` dans
`registration`, `city` sans group, et `validate($user, null, ['Default',
'registration'])` — ou `validation_groups` dans le form.

**Scénario 2 — La règle invisible.** Un `#[Assert\Email(groups: ['registration'])]`
ne signale jamais rien. *Solution.* L'appel `validate($user)` ne demande que
`Default` : ajoute `registration` au troisième argument.

**Scénario 3 — L'adresse oubliée.** On valide `User` avec le group `User`, et
le code postal vide de l'adresse cascadée passe. *Solution.* Valider `Default` :
le group `User` est transmis tel quel à l'`Address`, où aucune constraint ne le
porte.

## QCM d'entraînement

Six questions sur les groups. Choisis ta lettre avant d'ouvrir la correction.

**Question 1.** Une class `Order` porte une constraint sans group sur `$total` et une constraint `groups: ['checkout']` sur `$card`. Combien de groups existent ?

- **A.** Un seul, `checkout` : `Default` n'existe que si on le déclare
- **B.** Deux : `Default` et `checkout`
- **C.** Trois : `Default`, `Order` et `checkout`
- **D.** Quatre, en comptant le group vide

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Le nom de la class est un group implicite, qui contient
les constraints `Default` de cette class.

B est le piège : il oublie le group du nom de la class. A et D inventent des
règles.

</details>

**Question 2.** `validate($order)` est appelé sans groups. Quelles constraints s'exécutent ?

- **A.** Toutes celles de la class, quels que soient leurs groups
- **B.** Celles du group `Default` seulement
- **C.** Celles du group `Order` et des groups nommés
- **D.** Aucune : il faut toujours passer au moins un group

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Sans argument, seul `Default` s'applique ; la constraint
`checkout` reste muette.

A est l'intuition fausse. C et D inventent des règles.

</details>

**Question 3.** Dans la class `Order`, une constraint est déclarée `groups: ['Order']`. Elle s'exécute avec…

- **A.** le group `Order`, mais pas avec `Default`
- **B.** le group `Default`, car le nom de la class équivaut à `Default`
- **C.** n'importe quel group, puisqu'elle porte le nom de la class
- **D.** aucun group : un group ne peut pas porter le nom de la class

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Exécuté : ses seuls groups sont `["Order"]`. Ce qui est
dans `Default` est dans `Order`, pas l'inverse.

B est le piège : c'est ce que laisse croire la documentation. C et D inventent
des règles.

</details>

**Question 4.** `Order::$customer` porte `#[Assert\Valid]`, et `Customer::$email` un `NotBlank` sans group, vide. Quel appel signale `customer.email` ?

- **A.** `validate($order, null, ['Order'])`
- **B.** `validate($order, null, ['checkout'])`
- **C.** Aucun : la cascade ignore les groups
- **D.** `validate($order, null, ['Default'])`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** `Default` est transmis à `Customer`, dont le `NotBlank`
est dans `Default` (exécuté sur `address.zip`).

A est le piège : le group `Order` est transmis tel quel, et `Customer` n'a rien
dans ce group. B ne sélectionne que les constraints `checkout`. C est faux : le
group est transmis.

</details>

**Question 5.** `Admin extends BaseUser`. Chacune porte un `NotBlank` sans group. Que valide `validate($admin, null, ['BaseUser'])` ?

- **A.** Les constraints d'`Admin` et de `BaseUser`
- **B.** Les constraints de `BaseUser` seulement
- **C.** Rien : `BaseUser` n'est pas la class de l'object
- **D.** Les constraints d'`Admin` seulement

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Le group du parent ne contient que ses constraints par
défaut (documentation 8.0, exécuté). Avec `Admin` ou `Default`, les deux
classes sont validées.

A décrit le group `Admin`. C et D inventent des règles.

</details>

**Question 6.** Un appel `validate($user, null, ['registraton'])`, avec une faute de frappe, rend une liste vide pour un object invalide. Pourquoi ?

- **A.** Le Validator corrige le nom et ne trouve rien à signaler
- **B.** Le group inconnu fait basculer sur `Default`, ici valide
- **C.** Une exception est levée, puis interceptée par le Validator
- **D.** Aucune constraint ne porte ce group : rien n'est vérifié

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Un group existe dès qu'une constraint le nomme ; un nom
que personne n'utilise ne sélectionne rien, sans erreur.

A, B et C inventent des comportements.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- Une constraint sans group est dans `Default` **et** dans le group du nom de
  la class.
- `validate($o)` n'applique **que `Default`**.
- `groups: ['User']` n'est **pas** dans `Default`.
- Cascade : `Default` descend dans l'object imbriqué, le nom de la class non,
  un group nommé oui.
- Héritage : le group de la sous-classe couvre le parent ; celui du parent,
  le parent seul.
- Un group inconnu ou mal écrit ne lève rien : il ne sélectionne rien.

**En une phrase, pour tout reconstruire :**

```text
ÉTIQUETER (sans group = Default + Classe) → DEMANDER (rien = Default)
  → TRANSMETTRE le même group aux objects imbriqués
```

## Sources officielles

- [Validation Groups](https://github.com/symfony/symfony-docs/blob/8.0/validation/groups.rst)
- [Validator 8.0, `Constraint::addImplicitGroupName()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraint.php)
