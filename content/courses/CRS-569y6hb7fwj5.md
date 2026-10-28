---
id: CRS-569y6hb7fwj5
official_item: OIT-9x3strrjdng7
title: "Built-in validation constraints"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-28"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/reference/constraints/map.rst.inc"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/reference/constraints/map.rst.inc"
    branch: "8.0"
    symbol_or_lines: "liste des contraintes natives"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Constraints/NotBlankValidator.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/NotBlankValidator.php"
    branch: "8.0"
    symbol_or_lines: "NotBlankValidator::validate"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Constraints/LengthValidator.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/LengthValidator.php"
    branch: "8.0"
    symbol_or_lines: "validate(): returns on null only"
    verified_at: "2026-10-15"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Constraints/EmailValidator.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/EmailValidator.php"
    branch: "8.0"
    symbol_or_lines: "validate(): returns on null and on the empty string"
    verified_at: "2026-10-15"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Constraints/CollectionValidator.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/CollectionValidator.php"
    branch: "8.0"
    symbol_or_lines: "missingFieldsMessage, extraFieldsMessage, Required, Optional"
    verified_at: "2026-10-15"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Constraints/Choice.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/Choice.php"
    branch: "8.0"
    symbol_or_lines: "__construct(): first parameter $options, an array is refused"
    verified_at: "2026-10-15"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/CHANGELOG.md"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/CHANGELOG.md"
    branch: "8.0"
    symbol_or_lines: "8.0: Url requireTld defaults to true; options no longer configured implicitly"
    verified_at: "2026-10-15"
outcome_sections:
  OUT-zxecwdwyhgng: "Les familles"
  OUT-wkzdab0t8y8n: "Les distinctions qui décident"
  OUT-ca39zvmah4sj: "Les constraints de structure"
  OUT-8hwwtw34wdgt: "Les constraints de structure"
  OUT-kyt7yfamw0aq: "null et la string vide"
  OUT-bkrt4hz3td66: "Configurer une constraint en 8.0"
---

## Objectif

Reconnaître les familles de constraints fournies et trancher entre celles qui se
ressemblent. Le catalogue complet ne se mémorise pas : `debug:validator` et la
référence le donnent.

## Avant la règle : le problème à résoudre

Un formulaire de contact a quatre champs : un email obligatoire, un
commentaire facultatif mais toujours envoyé, un âge, et une liste de tags. Pour
chacun, il faut choisir **la bonne** constraint parmi plus de soixante — et
les noms se ressemblent : `NotBlank` ou `NotNull` ? `EqualTo` ou `IdenticalTo` ?
`All` ou `Collection` ?

Le piège n'est pas de connaître la liste, mais de savoir **ce que chaque
constraint laisse passer** : `null`, la string vide, `0`, une string d'espaces.
C'est là que se cachent les validations qui passent à tort.

## Le fil rouge : un formulaire de contact

```php
class Contact
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public ?string $email = null;

    #[Assert\NotNull]
    public ?string $comment = null;      // '' accepté, null refusé

    #[Assert\Positive]
    public ?int $age = null;             // null accepté : facultatif

    #[Assert\All([new Assert\Length(max: 20)])]
    public array $tags = [];
}
```

## Le cycle complet en un schéma

```text
la valeur est-elle null ?
   ── oui → seules NotNull / NotBlank (et IsNull, Blank) la jugent ; toutes les autres la laissent passer
   ── non ↓
la valeur est-elle '' ?
   ── NotBlank refuse ; Email, Regex, Url, Type('string') acceptent
   ── Length(min:…), Choice, Positive comptent '' comme une valeur, et peuvent refuser
   ── non ↓
la constraint organise-t-elle d'autres constraints ?
   ── All (chaque élément) · Collection (chaque key) · Sequentially (s'arrête à la 1re)
      AtLeastOneOf (une suffit) · When (si une expression est vraie) · Compound (un lot nommé)
   ── non → la constraint juge la valeur elle-même
```

## Les familles

La référence officielle (`map.rst.inc`, 8.0) range les constraints natives
ainsi :

| Famille | Exemples |
|---|---|
| Basiques | `NotBlank`, `NotNull`, `IsNull`, `Blank`, `IsTrue`, `IsFalse`, `Type` |
| Strings | `Email`, `Length`, `Regex`, `Url`, `Uuid`, `Ip`, `Json`, `PasswordStrength`, `WordCount` |
| Comparaison | `EqualTo`, `IdenticalTo`, `GreaterThan`, `Range`, `DivisibleBy`, `Unique` |
| Nombres | `Positive`, `PositiveOrZero`, `Negative`, `NegativeOrZero` |
| Date | `Date`, `DateTime`, `Time`, `Timezone`, `Week` |
| Choix | `Choice`, `Country`, `Language`, `Locale` |
| File | `File`, `Image`, `Video` |
| Financières et autres nombres | `Iban`, `Bic`, `CardScheme`, `Currency`, `Isbn`, `Luhn` |
| Autres | `Valid`, `All`, `Collection`, `Count`, `Callback`, `Sequentially`, `AtLeastOneOf`, `When`, `Compound`, `Cascade` |

`DivisibleBy` est une constraint de **comparaison**, pas de nombres. Une famille
propre à la base de données existe aussi : elle sort du périmètre.

## Les distinctions qui décident

**`NotBlank` contre `NotNull`.** `NotNull` ne refuse que `null`. `NotBlank`
teste `false === $value || (!$value && '0' != $value)` : il refuse `null`, la
string vide, `false` et l'array vide. Exécuté avec `symfony/validator`
8.0.15 :

| Valeur | `NotBlank` | `NotNull` |
|---|---|---|
| `null` | refusée | refusée |
| `''`, `false`, `[]` | refusées | acceptées |
| `'0'`, `0`, `0.0` | **acceptées** | acceptées |
| `' '` | **acceptée** | acceptée |

L'int `0` passe donc `NotBlank` : en PHP 8, `'0' != 0` est faux. Une string
d'espaces passe aussi, sauf avec `normalizer: 'trim'` (exécuté : une violation).
L'option `allowNull` fait accepter `null` à `NotBlank` (exécuté : 0 violation).

**`IsNull` et `Blank`** sont leurs symétriques : elles exigent une valeur
absente.

**`EqualTo` contre `IdenticalTo`** : `==` contre `===`. Exécuté : `'1'`
satisfait `EqualTo(1)`, pas `IdenticalTo(1)`.

**`Length` contre `Count`** : la première mesure une string, en caractères —
`'été'` fait 3 (exécuté : `Length(min: 4)` refuse, `Length(max: 3)` accepte) —,
la seconde une collection.

## null et la string vide

Une constraint de valeur **ignore `null`** : ce n'est pas son usage de dire si la
valeur est obligatoire. Exécuté sur `symfony/validator` 8.0.15, `Length`,
`Email`, `Regex`, `Choice`, `Positive`, `Count`, `Url` et `Type` ne signalent
rien sur `null`. Seules `NotNull` et `NotBlank` le refusent.

La string vide n'est pas `null`, et les constraints ne la traitent pas toutes
de la même façon :

| Constraint sur `''` | Résultat |
|---|---|
| `Email`, `Regex`, `Url` | acceptée : le validator rend la main |
| `Type('string')` | acceptée : `''` est une string |
| `Length(min: 3)` | violation : la longueur 0 est mesurée |
| `Choice(choices: ['a'])` | violation : `''` n'est pas un choix |
| `Positive` | violation |

Un field obligatoire porte donc `NotBlank` **en plus** de sa constraint de
format : `Email` seule laisse passer `null` et `''`.

## Configurer une constraint en 8.0

Les options se passent en **named arguments**. Le CHANGELOG 8.0 retire la
configuration implicite par array. Piège : le premier parameter de `Choice` est
encore `$options`. `#[Assert\Choice(['draft', 'published'])]` lève donc une
`InvalidArgumentException` à la lecture des metadata ; il faut
`choices: ['draft', 'published']`. Exécuté, `new Assert\Choice(['draft',
'published'])` : « Passing an array of options to configure the
"Symfony\Component\Validator\Constraints\Choice" constraint is no longer
supported. ».

Autre changement 8.0 : `requireTld` de `Url` vaut `true` par défaut. Exécuté,
`http://localhost` échoue avec `new Url()` et passe avec `requireTld: false`.

Une constraint mal configurée lève dès sa construction : `new Assert\Length()`
sans `min` ni `max`, `MissingOptionsException` (exécuté).

## Les constraints de structure

Elles ne testent pas une valeur, elles en organisent d'autres :

- `All` applique une constraint à **chaque élément** d'un array ;
- `Collection` associe une constraint à **chaque key** d'un array. Une key
  absente donne `This field is missing.` à `[key]`, une key en trop
  `This field was not expected.` — sauf `allowMissingFields` ou
  `allowExtraFields`. `Required` exige la key, pas une valeur non nulle ;
- `Sequentially` arrête à la **première** constraint violée : exécuté, `'ab'`
  contre `Length(min: 5)` puis `Email` donne une violation, deux sans
  `Sequentially` ;
- `AtLeastOneOf` réussit si **une** des constraints réussit ; si toutes
  échouent, il produit **une** violation qui les résume ;
- `When` n'applique une constraint que si une expression est vraie ;
- `Compound` regroupe un jeu de constraints réutilisable sous un seul nom ;
  toutes s'exécutent, comme une liste simple.

Exécuté : `Collection` sur `['a' => 1, 'x' => 2]`, avec les keys `a` et `b`
attendues, signale `[b]` « This field is missing. » et `[x]` « This field was
not expected. » ; `All([NotBlank])` sur `['a', '']` signale une violation.

| Besoin | Constraint |
|---|---|
| la même règle pour chaque élément | `All` |
| une règle par key d'un array | `Collection` |
| ne pas lancer une vérification coûteuse si la précédente échoue | `Sequentially` |
| accepter l'une ou l'autre forme | `AtLeastOneOf` |
| une règle seulement dans certains cas | `When` |

## Concrètement dans ton application

- Champ obligatoire : `NotBlank` ; champ toujours envoyé mais vide permis :
  `NotNull`.
- Champ facultatif avec format : la constraint de format seule (`Email`,
  `Positive`…), `null` passe.
- Liste homogène : `All` ; array structuré (payload JSON) : `Collection`.
- Vérification chère (appel distant) après un contrôle de format :
  `Sequentially`.
- Pour lister les constraints d'une class : `php bin/console debug:validator
  'App\Entity\Contact'`.

## À comprendre

- **Pourquoi les constraints ignorent-elles `null` ?** Pour séparer « la valeur
  est-elle présente ? » (`NotNull`, `NotBlank`) de « est-elle bien formée ? » :
  un champ facultatif reste ainsi validable.
- **Pourquoi `NotBlank` accepte-t-il `0` ?** Parce que `0` est une vraie
  valeur : un âge, une quantité. Seul le « vide » est refusé.
- **Pourquoi `Sequentially` ?** Parce que, sans lui, toutes les constraints
  s'exécutent et cumulent leurs messages ; avec lui, la première erreur suffit.
- **Pourquoi `choices:` nommé ?** Parce que 8.0 refuse la configuration par
  array, et que le premier parameter de `Choice` est encore l'ancien `$options`.

## À mémoriser

- `NotBlank` refuse `null`, `''`, `false`, `[]` ; accepte `0`, `'0'`, `0.0`,
  `' '` (sauf `normalizer: 'trim'`).
- `NotNull` ne refuse que `null`.
- `EqualTo` = `==`, `IdenticalTo` = `===`.
- `null` passe toute constraint de valeur ; `''` dépend de la constraint.
- `All` (éléments), `Collection` (keys), `Sequentially` (stop au premier
  échec), `AtLeastOneOf` (une suffit).
- 8.0 : named arguments, `Choice(choices: …)`, `Url` exige un TLD.
- Moyen mnémotechnique : **« Présent ? Bien formé ? Organisé ? »** — trois
  questions, trois sortes de constraints.

## Pièges d'examen

**`NotBlank` accepte `0`, `'0'` et `' '`.** Il refuse le vide au sens de PHP,
sauf ces valeurs. Pour une string dont le vide est légitime, c'est `NotNull`.

**`All` n'est pas `Collection`.** `All` traite les éléments uniformément ;
`Collection` décrit un array key par key.

**Sans `Sequentially`, toutes les constraints s'exécutent** et cumulent leurs
violations sur la même property.

**`DivisibleBy` est rangée en comparaison.**

**`Email` seule accepte `null` et `''`.** L'obligation vient de `NotBlank`.

**`Choice(['a', 'b'])` lève une exception en 8.0** : `choices:` est nommé.

## Application : trois scénarios

**Scénario 1 — L'email obligatoire.** Le formulaire accepte un email vide.
*Solution.* `Email` seule laisse passer `''` et `null` : ajoute `NotBlank`.

**Scénario 2 — Le commentaire.** Il doit toujours être envoyé, mais peut être
vide. *Solution.* `NotNull` : `''` passe, `null` non. `NotBlank` refuserait
`''`.

**Scénario 3 — Le payload d'API.** Un array doit contenir exactement les keys
`name` et `price`. *Solution.* `Collection` avec ces deux keys : une key
absente ou en trop produit sa violation à `[key]`.

## QCM d'entraînement

Six questions sur les constraints natives. Tranche avant d'ouvrir la
correction.

**Question 1.** Quelles valeurs passent `NotBlank` ?

- **A.** `0`, `'0'` et `' '`
- **B.** `0` seulement
- **C.** Aucune des valeurs « falsy »
- **D.** `'0'` et `' '`, mais pas l'int `0`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Exécuté : `0`, `'0'`, `0.0` et `' '` passent ; `null`,
`''`, `false` et `[]` sont refusés.

D est le piège : en PHP 8, `'0' != 0` est faux, donc l'int `0` passe aussi. B et
C ignorent les exceptions.

</details>

**Question 2.** Un champ `website` porte `#[Assert\Url]` seul, et l'utilisateur ne le remplit pas (`null`). Que se passe-t-il ?

- **A.** Une violation « This value is not a valid URL. »
- **B.** Une violation « This value should not be blank. »
- **C.** Aucune violation : `Url` ignore `null`
- **D.** Une exception : `Url` exige une string

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Une constraint de valeur laisse passer `null` ; seule
`NotNull` ou `NotBlank` le refuse (exécuté).

A est le piège : le format n'est jugé que si une valeur existe. B décrit
`NotBlank`. D invente une exception.

</details>

**Question 3.** Pour `''`, laquelle de ces constraints signale une violation ?

- **A.** `Email`
- **B.** `Length(min: 3)`
- **C.** `Regex('/^[a-z]+$/')`
- **D.** `Type('string')`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** `Length` mesure la longueur 0 et la refuse (exécuté).

A et C rendent la main sur `''`. D accepte : `''` est bien une string.

</details>

**Question 4.** Sur Symfony 8.0, que fait `#[Assert\Choice(['draft', 'published'])]` ?

- **A.** Il fonctionne, comme dans les versions précédentes
- **B.** Il ignore les choix et accepte toute valeur
- **C.** Il prend `draft` comme seul choix
- **D.** Une `InvalidArgumentException`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Il faut `choices:`. Exécuté : « Passing an array of
options to configure the … Choice constraint is no longer supported. ».

A est le piège : la forme positionnelle a été retirée en 8.0. B et C inventent
des comportements.

</details>

**Question 5.** `'ab'` est validé contre `Sequentially([new Length(min: 5), new Email()])`. Combien de violations ?

- **A.** Deux, une par constraint
- **B.** Une : la séquence s'arrête au premier échec
- **C.** Aucune : `Sequentially` n'agit que sur un array
- **D.** Une, celle d'`Email`, la dernière de la liste

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Exécuté : 1 avec `Sequentially`, 2 sans.

A est le résultat sans `Sequentially`. C confond avec `All`. D se trompe de
constraint : c'est `Length` qui échoue en premier.

</details>

**Question 6.** Un array `['name' => 'x', 'color' => 'red']` est validé par `Collection` qui attend `name` et `price`. Que signale-t-il ?

- **A.** `[price]` absente, et `[color]` non attendue
- **B.** Seulement `[price]`, les keys en trop étant ignorées
- **C.** Une exception, car l'array ne correspond pas
- **D.** Rien : `Collection` ne vérifie que les valeurs présentes

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Exécuté avec `a` / `b` attendues et `x` en trop : « This
field is missing. » à `[b]`, « This field was not expected. » à `[x]`.

B est le piège : il faudrait `allowExtraFields`. C et D inventent des
comportements.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- `NotBlank` refuse `null`, `''`, `false`, `[]` ; il **accepte** `0`, `'0'`,
  `' '`.
- `NotNull` ne refuse que `null`.
- Toute constraint de valeur **laisse passer `null`** ; `Email` laisse aussi
  passer `''`.
- `EqualTo` (`==`) contre `IdenticalTo` (`===`) ; `Length` (caractères) contre
  `Count` (éléments).
- `All` = chaque élément ; `Collection` = chaque key ; `Sequentially` = stop au
  premier échec ; `AtLeastOneOf` = une suffit.
- 8.0 : `Choice(choices: …)` obligatoire ; `Url` exige un TLD par défaut.
- `DivisibleBy` est en comparaison.

**En une phrase, pour tout reconstruire :**

```text
PRÉSENT ? (NotNull, NotBlank) → BIEN FORMÉ ? (Email, Length…, null passe)
  → ORGANISÉ ? (All, Collection, Sequentially, AtLeastOneOf, When)
```

## Sources officielles

- [Référence des constraints](https://github.com/symfony/symfony-docs/blob/8.0/reference/constraints/map.rst.inc)
- [`LengthValidator`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/LengthValidator.php), [`EmailValidator`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/EmailValidator.php), [`CollectionValidator`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/CollectionValidator.php), [`Choice`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/Choice.php) et le [CHANGELOG du Validator](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/CHANGELOG.md), branche 8.0
- [`NotBlankValidator`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/NotBlankValidator.php)
