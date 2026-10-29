---
id: CRS-d235sja3h7y2
official_item: OIT-0wnxbapegqhv
title: "Violations builder"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-28"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Violation/ConstraintViolationBuilderInterface.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Violation/ConstraintViolationBuilderInterface.php"
    branch: "8.0"
    symbol_or_lines: "ConstraintViolationBuilderInterface"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Context/ExecutionContextInterface.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Context/ExecutionContextInterface.php"
    branch: "8.0"
    symbol_or_lines: "addViolation, buildViolation"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/validation/custom_constraint.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/validation/custom_constraint.rst"
    branch: "8.0"
    symbol_or_lines: "Creating the Constraint Class; Creating the Validator itself; Class Constraint Validator"
    verified_at: "2026-10-15"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Constraint.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraint.php"
    branch: "8.0"
    symbol_or_lines: "validatedBy(): static::class.'Validator'; getTargets(): PROPERTY_CONSTRAINT"
    verified_at: "2026-10-15"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Validator/RecursiveContextualValidator.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Validator/RecursiveContextualValidator.php"
    branch: "8.0"
    symbol_or_lines: "validateInGroup(): catch UnexpectedValueException, build a Type violation"
    verified_at: "2026-10-15"
outcome_sections:
  OUT-x2rv3kwasf88: "Les deux façons"
  OUT-y8sak1s2672z: "Le builder n'écrit rien tout seul"
  OUT-eq4vjk7mmyxm: "Ce que l'on peut régler"
  OUT-nb6w1gbs0ndt: "Les emplacements du message"
  OUT-7m4ym18awd8g: "Une constraint sur mesure"
---

## Objectif

Signaler une violation depuis un callback ou un validator, et savoir laquelle
des deux methods du contexte utiliser.

## Avant la règle : le problème à résoudre

Un `Callback` de class vérifie qu'un email n'est pas déjà réservé. Quand la
règle échoue, il faut produire **une** violation, avec :

- un message traduisible, dans lequel l'email apparaît ;
- le **field** concerné (`email`), pour que le form l'affiche au bon endroit ;
- un **code** stable, pour que l'API renvoie une erreur exploitable sans
  analyser le texte.

Le contexte d'exécution fournit l'outil : un **builder** de violation, qu'on
configure puis qu'on enregistre. Et c'est précisément l'enregistrement qu'on
oublie.

## Le fil rouge : une adresse déjà réservée

```php
#[Assert\Callback('check')]
class Registration
{
    public string $email = '';

    public function check(ExecutionContextInterface $context): void
    {
        if ($this->isReserved($this->email)) {
            $context->buildViolation('L\'adresse {{ value }} est déjà prise.')
                ->setParameter('{{ value }}', $this->email)
                ->atPath('email')
                ->setCode('EMAIL_TAKEN')
                ->addViolation();
        }
    }
}
```

## Le cycle complet en un schéma

```text
$context->addViolation('msg', [params])   ── enregistre TOUT DE SUITE, au path courant

$context->buildViolation('msg')            ── crée un BUILDER, n'enregistre RIEN
        ->setParameter('{{ v }}', $v)        remplit le message
        ->atPath('email')                    déplace le PATH (pas la valeur fautive)
        ->setCode('EMAIL_TAKEN')             code stable
        ->setInvalidValue($this->email)      corrige la valeur fautive
        ->addViolation();                  ── ENREGISTRE  ← sans cette ligne : 0 violation
```

## Les deux façons

`ExecutionContextInterface` en offre deux, et elles ne sont pas équivalentes :

```php
$context->addViolation('Message {{ v }}', ['{{ v }}' => $v]); // direct
$context->buildViolation('Message')->addViolation();        // par le builder
```

`addViolation()` ajoute la violation immédiatement, au path courant ; elle
accepte déjà les parameters du message en second argument (exécuté : « direct
Y », path vide depuis un callback de class). `buildViolation()` retourne un
**builder** que l'on configure avant de valider — c'est la forme à connaître,
parce que c'est la seule qui permet de choisir le field visé, le code ou la
valeur fautive. Elle accepte elle aussi les parameters en second argument.

## Le builder n'écrit rien tout seul

C'est le piège central de l'item : `buildViolation()` **ne crée aucune
violation**. Elle prépare un object ; tant que `addViolation()` n'est pas appelée
au bout de la chaîne d'appels, rien n'est enregistré et la validation passe. Exécuté avec
`symfony/validator` 8.0.15 : une chaîne d'appels `buildViolation()->setParameter()` non
terminée ne produit aucune violation.

```php
$context->buildViolation('La valeur {{ value }} est refusée.')
    ->setParameter('{{ value }}', $value)
    ->atPath('email')
    ->setCode(MaContrainte::VALEUR_REFUSEE)
    ->addViolation();          // ← sans cette ligne, rien ne se passe
```

## Ce que l'on peut régler

Les dix methods de `ConstraintViolationBuilderInterface` (8.0, relues par
réflexion) :

| Method | Effet |
|---|---|
| `atPath()` | attribue la violation à une autre property que celle en cours |
| `setParameter()` / `setParameters()` | remplit les emplacements `{{ … }}` du message |
| `setCode()` | pose un code machine, indépendant du texte |
| `setInvalidValue()` | la valeur affichée comme fautive |
| `setPlural()` | le nombre servant à choisir la forme plurielle |
| `setTranslationDomain()` / `disableTranslation()` | le traitement du message |
| `setCause()` | la cause technique, pour le debug |
| `addViolation()` | enregistre la violation, en fin de chaîne d'appels |

Deux méritent un mot. **`atPath()`** est ce qui rend une constraint de class
utilisable : sans elle, la violation est attachée à l'object — path vide — et
le form l'affiche en tête plutôt que sur le field fautif. Exécuté avec le
component Form 8.0.15 : sans `atPath()`, l'erreur est sur le form racine (1
erreur racine, 0 sur `email`) ; avec `atPath('email')`, elle est sur le field.
Le path se compose : `atPath('address.city')` vise la property d'un object
imbriqué (exécuté). **`setCode()`** donne au code appelant un identifier stable
— `getCode()` sur la violation — là où le message change avec la langue.

### `atPath()` ne change pas la valeur fautive

Exécuté depuis un `Callback` de class : la violation dirigée vers `email` par
`atPath('email')` porte le path `email`, mais sa valeur fautive reste
**l'object entier** — la valeur courante du contexte. Pour afficher la valeur du
field, il faut `setInvalidValue()`.

## Les emplacements du message

Le message porte des emplacements entre doubles accolades, remplis par
`setParameter()`. C'est ce qui permet de traduire le message sans y interpoler la
valeur à la main. La violation garde les deux formes : `getMessageTemplate()`
rend `'built {{ v }}'`, `getMessage()` le texte rempli (exécuté : `built Z`).

## Une constraint sur mesure

Le builder sert d'abord dans un `ConstraintValidator`. Une constraint maison,
ce sont deux class :

```php
#[\Attribute]
class Slug extends Constraint
{
    public string $message = '"{{ value }}" is not a slug.';
}

class SlugValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Slug) {
            throw new UnexpectedTypeException($constraint, Slug::class);
        }
        if (null === $value || '' === $value) {
            return;
        }
        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }
        if (!preg_match('/^[a-z0-9-]+$/', $value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
```

Exécuté sur 8.0.15 :

- `validatedBy()` rend par défaut le nom de la class suivi de `Validator` ;
- le validator **reçoit `null`** : le `return` est son travail, pour laisser
  `NotNull` ou `NotBlank` décider ;
- `UnexpectedValueException` devient une violation —
  `This value should be of type string.` —, alors qu'`UnexpectedTypeException`
  remonte : c'est une erreur de code ;
- `getTargets()` vaut `PROPERTY_CONSTRAINT` : posée sur une class sans le
  redéfinir, la constraint lève une `ConstraintDefinitionException` — « The
  constraint "…\Slug" cannot be put on classes. ».

En 8.0, une option se déclare dans le constructor, qui appelle
`parent::__construct(null, $groups, $payload)`.

## Concrètement dans ton application

- Un `Callback` de class : toujours `buildViolation()`, `atPath()` vers le
  field, puis `addViolation()`.
- Une API : `setCode()` avec une constant, et le client lit `getCode()`.
- Un message traduit : des `{{ placeholders }}` et `setParameter()`, jamais de
  concaténation.
- Une constraint maison : `return` sur `null` et `''`,
  `UnexpectedValueException` pour un mauvais type, `UnexpectedTypeException`
  pour une mauvaise constraint.

## À comprendre

- **Pourquoi un builder ?** Parce qu'une violation a beaucoup de réglages
  optionnels : une interface fluide évite une signature à dix arguments.
- **Pourquoi `addViolation()` à la fin ?** Parce que le builder ne sait pas
  quand vous avez fini de le configurer : c'est cet appel qui le dit.
- **Pourquoi deux exceptions différentes ?** `UnexpectedValueException`
  signale une **donnée** inattendue (une violation pour le user) ;
  `UnexpectedTypeException`, une **erreur de programmation** (la constraint n'est
  pas la bonne).

## À mémoriser

- `addViolation()` sur le contexte : direct, au path courant.
- `buildViolation()` : configurable, mais **rien sans `addViolation()`**.
- `atPath()` : le field ; sans lui, erreur à la racine du form.
- `atPath()` ne change pas la valeur fautive : `setInvalidValue()`.
- `setParameter()` remplit `{{ … }}` ; `getMessageTemplate()` garde le modèle.
- Constraint maison : `validatedBy()` = class + `Validator` ; le validator
  reçoit `null` et doit l'ignorer.
- Moyen mnémotechnique : **« Construire, viser, coder, ajouter »**.

## Pièges d'examen

**`buildViolation()` seule n'enregistre rien.** Sans `addViolation()` au bout de
la chaîne d'appels, la validation passe alors que la règle est violée.

**`addViolation()` existe des deux côtés** : sur le contexte, elle enregistre
directement ; sur le builder, elle termine la chaîne d'appels. Ce ne sont pas les
mêmes methods.

**Sans `atPath()`, une violation de portée class reste attachée à l'object** et
le form l'affiche en tête, pas sur le field.

**`atPath()` déplace le path, pas la valeur fautive.**

**Un validator maison doit ignorer `null` lui-même.**

## Application : trois scénarios

**Scénario 1 — La validation qui ne bloque rien.** Le callback détecte l'email
pris et appelle `buildViolation('…')->atPath('email')`. Le form est accepté.
*Solution.* Terminer la chaîne d'appels par `->addViolation()`.

**Scénario 2 — L'erreur en haut du form.** Le `Callback` de class signale une
date de fin invalide, mais l'erreur s'affiche au-dessus du form. *Solution.*
`->atPath('end')` avant `->addViolation()`.

**Scénario 3 — Une erreur exploitable par l'API.** Le client doit distinguer
« email pris » de « email invalide » sans lire le texte. *Solution.*
`->setCode(Registration::EMAIL_TAKEN)` ; le client lit `getCode()`.

## QCM d'entraînement

Six questions sur le builder de violations. Choisis avant d'ouvrir la
correction.

**Question 1.** Un callback appelle `$context->buildViolation('Mauvais')->atPath('email');` et rien d'autre. Combien de violations ?

- **A.** Une, au path `email`
- **B.** Une, au path vide
- **C.** Aucune
- **D.** Une exception : le builder n'a pas été terminé

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Le builder n'enregistre rien sans `addViolation()`
(exécuté : 0 violation).

A est le piège : c'est le résultat **avec** `addViolation()`. B et D inventent
des comportements.

</details>

**Question 2.** Dans un `Callback` de class, `buildViolation('Mauvais')->atPath('email')->addViolation()`. Quelle est la valeur fautive de la violation ?

- **A.** La valeur de la property `email`
- **B.** L'object entier
- **C.** `null`, faute de `setInvalidValue()`
- **D.** La string `'email'`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** `atPath()` déplace le path, pas la valeur fautive, qui
reste la valeur courante du contexte (exécuté : l'object validé).

A est le piège : il faudrait `setInvalidValue()`. C et D inventent des valeurs.

</details>

**Question 3.** Le message est `'Le code {{ code }} est inconnu.'`. Quelle method du builder remplit l'emplacement ?

- **A.** `setCode()`
- **B.** `atPath()`
- **C.** `setInvalidValue()`
- **D.** `setParameter()`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** `setParameter('{{ code }}', …)` remplit l'emplacement ;
`getMessageTemplate()` garde le modèle (exécuté).

A est le piège : `setCode()` pose un code machine, sans rapport avec le texte.
B et C règlent le path et la valeur fautive.

</details>

**Question 4.** Un `Callback` de class ajoute une violation **sans** `atPath()`. Où un form Symfony l'affiche-t-il ?

- **A.** Sur le form racine, pas sur un field
- **B.** Sous le premier field du form
- **C.** Sous chaque field, la violation étant copiée
- **D.** Nulle part : une violation sans path est ignorée

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Exécuté avec le component Form : 1 erreur sur le form
racine, 0 sur le field ; avec `atPath('email')`, l'erreur passe sur le field.

B, C et D inventent des comportements.

</details>

**Question 5.** Un `SlugValidator` reçoit un array alors qu'il attend une string. Quelle exception transforme ce cas en violation pour le user ?

- **A.** `UnexpectedTypeException`
- **B.** `UnexpectedValueException`
- **C.** `ConstraintDefinitionException`
- **D.** `InvalidArgumentException`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Exécuté : elle devient « This value should be of type
string. ».

A est le piège : elle signale une mauvaise **constraint**, et remonte comme une
erreur. C et D ne sont pas converties en violation.

</details>

**Question 6.** La constraint `Slug` ne redéfinit ni `validatedBy()` ni `getTargets()`. Elle est posée sur une **class**. Que se passe-t-il ?

- **A.** Elle valide l'object entier avec `SlugValidator`
- **B.** Elle est ignorée en silence
- **C.** Elle est appliquée à chaque property de la class
- **D.** Une `ConstraintDefinitionException`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Elle ne vise que les properties : `getTargets()` vaut
`PROPERTY_CONSTRAINT` par défaut ;
exécuté : « The constraint "…\Slug" cannot be put on classes. ».

A est le piège : il faudrait redéfinir `getTargets()` pour rendre
`CLASS_CONSTRAINT`. B et C inventent des comportements.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- `addViolation()` du **contexte** : immédiat ; `buildViolation()` : un builder.
- Le builder **n'enregistre rien** sans `->addViolation()` à la fin.
- `atPath()` : le field visé ; sans lui, l'erreur est à la **racine** du form.
- `atPath()` ne change pas la **valeur fautive** : `setInvalidValue()`.
- `setParameter()` remplit `{{ … }}` ; `setCode()` donne un code stable.
- Constraint maison : `SlugValidator` par défaut, `return` sur `null`,
  `UnexpectedValueException` → violation.

**En une phrase, pour tout reconstruire :**

```text
buildViolation(msg) → setParameter / atPath / setCode / setInvalidValue → addViolation()
  ; oublier la dernière flèche = aucune violation
```

## Sources officielles

- [Créer une constraint sur mesure](https://github.com/symfony/symfony-docs/blob/8.0/validation/custom_constraint.rst), [`Constraint`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraint.php) et [`RecursiveContextualValidator`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Validator/RecursiveContextualValidator.php), branche 8.0
- [`ConstraintViolationBuilderInterface`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Violation/ConstraintViolationBuilderInterface.php)
- [`ExecutionContextInterface`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Context/ExecutionContextInterface.php)
