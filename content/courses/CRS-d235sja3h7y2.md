---
id: CRS-d235sja3h7y2
official_item: OIT-0wnxbapegqhv
title: "Violations builder"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Violation/ConstraintViolationBuilderInterface.php"
    branch: "8.0"
    symbol_or_lines: "ConstraintViolationBuilderInterface"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Context/ExecutionContextInterface.php"
    branch: "8.0"
    symbol_or_lines: "addViolation, buildViolation"
    verified_at: "2026-09-01"
---

## Objectif

Signaler une violation depuis un callback ou un validateur, et savoir laquelle
des deux méthodes du contexte utiliser.

## Les deux façons

`ExecutionContextInterface` en offre deux, et elles ne sont pas équivalentes :

```php
$context->addViolation('Message');                 // direct, rien à régler
$context->buildViolation('Message')->addViolation(); // par le constructeur
```

`addViolation()` ajoute la violation immédiatement, avec les valeurs par défaut.
`buildViolation()` retourne un **constructeur** que l'on configure avant de
valider — c'est la forme à connaître, parce que c'est la seule qui permet de
choisir le champ visé.

## Le constructeur n'écrit rien tout seul

C'est le piège central de l'item : `buildViolation()` **ne crée aucune
violation**. Elle prépare un objet ; tant que `addViolation()` n'est pas appelée
au bout de la chaîne, rien n'est enregistré et la validation passe.

```php
$context->buildViolation('La valeur {{ value }} est refusée.')
    ->setParameter('{{ value }}', $value)
    ->atPath('email')
    ->setCode(MaContrainte::VALEUR_REFUSEE)
    ->addViolation();          // ← sans cette ligne, rien ne se passe
```

## Ce que l'on peut régler

| Méthode | Effet |
|---|---|
| `atPath()` | attribue la violation à une autre propriété que celle en cours |
| `setParameter()` / `setParameters()` | remplit les emplacements `{{ … }}` du message |
| `setCode()` | pose un code machine, indépendant du texte |
| `setInvalidValue()` | la valeur affichée comme fautive |
| `setPlural()` | le nombre servant à choisir la forme plurielle |
| `setTranslationDomain()` / `disableTranslation()` | le traitement du message |
| `setCause()` | la cause technique, pour le débogage |

Deux méritent un mot. **`atPath()`** est ce qui rend une contrainte de classe
utilisable : sans elle, la violation est attachée à l'objet et le formulaire
l'affiche en tête plutôt que sur le champ fautif. **`setCode()`** donne au code
appelant un identifiant stable — `getCode()` sur la violation — là où le message
change avec la langue.

## Les emplacements du message

Le message porte des emplacements entre doubles accolades, remplis par
`setParameter()`. C'est ce qui permet de traduire le message sans y interpoler la
valeur à la main.

## Points clés

- `addViolation()` sur le contexte : direct. `buildViolation()` : configurable.
- Une chaîne `buildViolation()` non terminée par `addViolation()` **ne produit
  rien**.
- `atPath()` attribue l'erreur au bon champ ; indispensable en portée classe.
- `setParameter()` remplit `{{ … }}` ; `setCode()` donne un identifiant stable.

## Sources officielles

- [`ConstraintViolationBuilderInterface`, branche 8.0](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Violation/ConstraintViolationBuilderInterface.php)
- [`ExecutionContextInterface`, branche 8.0](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Context/ExecutionContextInterface.php)
