---
id: CRS-d235sja3h7y2
official_item: OIT-0wnxbapegqhv
title: "Violations builder"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-26"
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

## Les deux façons

`ExecutionContextInterface` en offre deux, et elles ne sont pas équivalentes :

```php
$context->addViolation('Message {{ v }}', ['{{ v }}' => $v]); // direct
$context->buildViolation('Message')->addViolation();        // par le builder
```

`addViolation()` ajoute la violation immédiatement, au path courant ; elle
accepte déjà les parameters du message en second argument. `buildViolation()`
retourne un **builder** que l'on configure avant de valider — c'est la
forme à connaître, parce que c'est la seule qui permet de choisir le field visé,
le code ou la valeur fautive. Elle accepte elle aussi les parameters en second
argument.

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

Les dix methods de `ConstraintViolationBuilderInterface` (8.0) :

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
le form l'affiche en tête plutôt que sur le field fautif. Le path se
compose : `atPath('address.city')` vise la property d'un object imbriqué.
**`setCode()`** donne au code appelant un identifier stable — `getCode()` sur
la violation — là où le message change avec la langue.

## `atPath()` ne change pas la valeur fautive

Exécuté depuis un `Callback` de class : la violation dirigée vers `email` par
`atPath('email')` porte le path `email`, mais sa valeur fautive reste
**l'object entier** — la valeur courante du contexte. Pour afficher la valeur du
field, il faut `setInvalidValue()`.

## Les emplacements du message

Le message porte des emplacements entre doubles accolades, remplis par
`setParameter()`. C'est ce qui permet de traduire le message sans y interpoler la
valeur à la main. La violation garde les deux formes : `getMessageTemplate()`
rend `'built {{ v }}'`, `getMessage()` le texte rempli.

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
  redéfinir, la constraint lève une `ConstraintDefinitionException`.

En 8.0, une option se déclare dans le constructor, qui appelle
`parent::__construct(null, $groups, $payload)`.

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

## Points clés

- `addViolation()` sur le contexte : direct, parameters admis.
  `buildViolation()` : configurable.
- Une chaîne d'appels `buildViolation()` non terminée par `addViolation()` **ne produit
  rien**.
- `atPath()` attribue l'erreur au bon field ; indispensable en portée class.
- `setParameter()` remplit `{{ … }}` ; `setCode()` donne un identifier stable ;
  `setInvalidValue()` corrige la valeur fautive.
- `Slug` est validée par `SlugValidator` ; un mauvais type de valeur se signale
  par `UnexpectedValueException`.

## Sources officielles

- [Créer une constraint sur mesure](https://github.com/symfony/symfony-docs/blob/8.0/validation/custom_constraint.rst), [`Constraint`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraint.php) et [`RecursiveContextualValidator`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Validator/RecursiveContextualValidator.php), branche 8.0
- [`ConstraintViolationBuilderInterface`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Violation/ConstraintViolationBuilderInterface.php)
- [`ExecutionContextInterface`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Context/ExecutionContextInterface.php)
