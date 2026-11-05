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

## L'essentiel

Le catalogue ne s'apprend pas : il se range en familles. Ce qui se joue à
l'examen, ce sont les distinctions — `NotBlank` contre `NotNull`, `null` contre
la string vide, `All` contre `Collection` — et la configuration 8.0 en
**named arguments**.

## Le schéma

```text
null ?        seules NotNull / NotBlank (et IsNull, Blank) jugent ; toutes les autres laissent passer
'' ?          NotBlank refuse ; Email, Regex, Url, Type('string') acceptent ; Length(min), Choice, Positive peuvent refuser
organiser ?   All (chaque élément) · Collection (chaque key) · Sequentially (1re erreur) · AtLeastOneOf · When · Compound
sinon         la constraint juge la valeur elle-même
```

## Le fil rouge : un form de contact

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

## Les familles

| Famille | Exemples |
|---|---|
| Basiques | `NotBlank`, `NotNull`, `IsNull`, `Blank`, `IsTrue`, `Type` |
| Strings | `Email`, `Length`, `Regex`, `Url`, `Uuid`, `Ip`, `Json`, `PasswordStrength` |
| Comparaison | `EqualTo`, `IdenticalTo`, `GreaterThan`, `Range`, `DivisibleBy`, `Unique` |
| Nombres | `Positive`, `PositiveOrZero`, `Negative`, `NegativeOrZero` |
| Date | `Date`, `DateTime`, `Time`, `Timezone`, `Week` |
| Choix | `Choice`, `Country`, `Language`, `Locale` |
| File | `File`, `Image`, `Video` |
| Financières | `Iban`, `Bic`, `CardScheme`, `Currency`, `Isbn`, `Luhn` |
| Autres | `Valid`, `All`, `Collection`, `Count`, `Callback`, `Sequentially`, `AtLeastOneOf`, `When`, `Compound` |

## Les distinctions qui décident

| Valeur | `NotBlank` | `NotNull` |
|---|---|---|
| `null` | refusée | refusée |
| `''`, `false`, `[]` | refusées | acceptées |
| `'0'`, `0`, `0.0`, `' '` | **acceptées** | acceptées |

- L'int `0` passe donc `NotBlank` ; `normalizer: 'trim'` fait refuser `' '` ;
  `allowNull` fait accepter `null`.
- `EqualTo` compare par `==`, `IdenticalTo` par `===` : `'1'` passe le
  premier, pas le second.
- `Length` compte des **caractères** (`'été'` en fait 3) ; `Count`, des
  éléments.

## null et la string vide

Une constraint de valeur **ignore `null`** : `Length`, `Email`, `Regex`,
`Choice`, `Positive`, `Count`, `Url`, `Type` ne signalent rien. Sur `''`,
`Email`, `Regex`, `Url` et `Type('string')` acceptent ; `Length(min: 3)`,
`Choice` et `Positive` refusent. Un field obligatoire porte donc `NotBlank`
**en plus** de son format.

## Configurer une constraint en 8.0

- Options en **named arguments** : `new Assert\Choice(['draft', 'published'])`
  lève « Passing an array of options to configure the "…\Choice" constraint
  is no longer supported. » — il faut `choices:`.
- `Url` exige un TLD par défaut : `http://localhost` échoue sans
  `requireTld: false`.
- `new Assert\Length()` sans `min` ni `max` : `MissingOptionsException`.

## Les constraints de structure

| Besoin | Constraint |
|---|---|
| la même règle pour chaque élément | `All` |
| une règle par key d'un array (`This field is missing.`, `This field was not expected.`) | `Collection` |
| ne pas lancer la vérification suivante si la précédente échoue | `Sequentially` |
| accepter l'une ou l'autre forme ; si toutes échouent, **une** violation qui les résume | `AtLeastOneOf` |
| une règle seulement si une expression est vraie | `When` |
| un jeu de constraints réutilisable sous un nom | `Compound` |

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

## Application

- Un commentaire peut être vide mais doit être envoyé → `NotNull`, pas
  `NotBlank`.
- Un contrôle distant coûteux ne doit pas tourner sur un format déjà faux →
  `Sequentially`.

## QCM d'entraînement

**Question 1.** Un field `website` porte `#[Assert\Url]` seul, et le user le
laisse à `null`. Que se passe-t-il ?

- **A.** Une violation « This value is not a valid URL. »
- **B.** Une violation « This value should not be blank. »
- **C.** Aucune violation
- **D.** Une exception : `Url` exige une string

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Une constraint de valeur laisse passer `null` ; seule
`NotNull` ou `NotBlank` rendrait le field obligatoire.

</details>

**Question 2.** La string `'1'` est validée contre `EqualTo(1)`, puis contre
`IdenticalTo(1)`. Que se passe-t-il ?

- **A.** Elle passe les deux
- **B.** Elle passe `EqualTo`, échoue à `IdenticalTo`
- **C.** Elle échoue aux deux : `'1'` n'est pas un int
- **D.** Elle échoue à `EqualTo`, passe `IdenticalTo`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** `EqualTo` compare par `==`, où `'1'` vaut `1` ;
`IdenticalTo` compare par `===`, où le type compte.

</details>

**Question 3.** `'été'` est validé contre `Length(min: 4)`. Résultat ?

- **A.** Acceptée : cinq octets en UTF-8
- **B.** Acceptée : `Length` ignore les accents
- **C.** Une exception : encoding inconnu
- **D.** Une violation : trois caractères

<details>
<summary>Correction</summary>

**Bonne réponse : D.** `Length` compte les caractères, pas les octets : trois,
sous le minimum de quatre.

</details>

**Question 4.** Une valeur échoue aux deux constraints d'un
`AtLeastOneOf([new Email(), new Regex('/^\+\d+$/')])`. Combien de violations ?

- **A.** Une, qui résume les deux échecs
- **B.** Deux, une par constraint
- **C.** Aucune : `AtLeastOneOf` ne signale rien
- **D.** Une exception : aucune alternative n'a réussi

<details>
<summary>Correction</summary>

**Bonne réponse : A.** `AtLeastOneOf` réussit si une constraint réussit ;
sinon il produit une seule violation qui liste les échecs.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- **`NotBlank`** refuse `null`, `''`, `false`, `[]` ; accepte `0`, `'0'`, `' '`.
- **`NotNull`** ne refuse que `null`.
- Les autres constraints **ignorent `null`** ; `''` dépend de la constraint.
- `EqualTo` = **`==`**, `IdenticalTo` = **`===`**.
- 8.0 : **named arguments** (`choices:`) ; `Url` exige un TLD.
- **`All`** : chaque élément ; **`Collection`** : chaque key.
- **`Sequentially`** s'arrête ; **`AtLeastOneOf`** : une suffit.

Moyen mnémotechnique : **« Présent ? Bien formé ? Organisé ? »**

## Sources officielles

- [Référence des constraints](https://github.com/symfony/symfony-docs/blob/8.0/reference/constraints/map.rst.inc)
- [`LengthValidator`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/LengthValidator.php), [`EmailValidator`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/EmailValidator.php), [`CollectionValidator`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/CollectionValidator.php), [`Choice`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/Choice.php) et le [CHANGELOG du Validator](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/CHANGELOG.md), branche 8.0
- [`NotBlankValidator`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/NotBlankValidator.php)

