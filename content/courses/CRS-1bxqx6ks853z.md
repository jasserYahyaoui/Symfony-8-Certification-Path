---
id: CRS-1bxqx6ks853z
official_item: OIT-d416348gfhde
title: "Components and Bridges"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-07"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/composer.json"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/composer.json"
    symbol_or_lines: '"replace" and "provide" keys'
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/composer.json"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/composer.json"
    symbol_or_lines: '"description" and "require" keys'
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/PhpUnit/composer.json"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/PhpUnit/composer.json"
    symbol_or_lines: '"name", "type", "description" and "require" keys'
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-18"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/PsrHttpMessage/composer.json"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/PsrHttpMessage/composer.json"
    branch: "8.0"
    symbol_or_lines: "clés name, type et require"
    verified_at: "2026-10-07"
outcome_sections:
  OUT-21jyzfg4a961: "Le triangle"
  OUT-tvjm3k5gjnb4: "Le triangle"
  OUT-vzswg6fmf7fr: "Le bundle"
---

## Objectif

Distinguer trois notions que le vocabulaire Symfony sépare strictement :
**component**, **bridge** et **bundle**.

## L'essentiel

Trois mots, trois roles. Un **component** est une bibliothèque autonome,
utilisable hors du framework. Un **bridge** relie un component à une
bibliothèque tierce. Un **bundle** branche le tout dans le framework et le
rend configurable. Le `composer.json` du mono-dépôt `symfony/symfony` déclare
ce découpage — et le bridge PHPUnit en est l'exception.

## Le schéma

```text
component   symfony/console        autonome, repository propre en lecture seule, tout projet PHP
bridge      symfony/twig-bridge    component ↔ bibliothèque tierce (twig/twig) ; ne configure rien
bundle      TwigBundle             extension de configuration, services, commands : branche dans le framework
mono-dépôt  replace  : 65 packages remplacés (components, 3 bridges, 5 bundles) — pas installés en plus
            provide  : seulement des *-implementation (psr/log-implementation…)
```

## L'exemple : Twig sous trois noms

`twig/twig` est la bibliothèque tierce ; `symfony/twig-bridge` fournit les
extensions Twig qui exposent la génération d'URL, le rendu de form et la
translation ; `TwigBundle` enregistre les services et la configuration
`twig:`. Sans le bundle, le bridge seul n'enregistre aucun service.

## Le triangle

| | Autonome ? | Dépend d'un tiers ? | Configure le framework ? |
|---|---|---|---|
| Component | oui | non | non |
| Bridge | non | oui | non |
| Bundle | non | parfois | **oui** |

Le test d'un component : il s'installe dans un projet PHP quelconque.

## Le bundle

Un bundle fournit une extension de configuration, enregistre des services dans
le container, ajoute des commands ou des routes : `FrameworkBundle`,
`TwigBundle`, `SecurityBundle`. C'est la ligne qui le sépare d'un bridge.

## `replace` et `provide`, deux keys distinctes

`replace` liste **65 packages** : `symfony/symfony` les remplace, sans les
installer en plus. On y trouve les components, **trois** bridges —
`symfony/doctrine-bridge`, `symfony/monolog-bridge`, `symfony/twig-bridge` — et
**cinq** bundles : `framework-bundle`, `security-bundle`, `twig-bundle`,
`debug-bundle`, `web-profiler-bundle`. `provide` ne liste aucun package
Symfony, seulement des noms terminés par `-implementation`.

## Le bridge PhpUnit, qui dément la definition

`src/Symfony/Bridge/` en compte pourtant **cinq** : `PhpUnit` et
`PsrHttpMessage` sont absents de `replace` et publiés à part.
`PsrHttpMessage` relie HttpFoundation et PSR-7, conforme à la definition.
`symfony/phpunit-bridge`, de type `symfony-bridge`, n'exige que
`php >=8.1.0` : il ne dépend d'aucun component et d'aucune bibliothèque tierce.
Il sert à tester, d'où une constraint PHP plus basse que le `>=8.4` du bridge
Twig, et il s'installe à part, en `require-dev`.

## Pièges d'examen

**Un bridge ne configure rien.** C'est la ligne qui sépare bridge et bundle :
le bridge relie un component à une bibliothèque tierce, le bundle branche le
tout dans le framework et apporte la configuration. Un bridge posé seul dans un
projet Symfony n'enregistre aucun service.

**Un component ne dépend pas du framework.** L'installer dans un projet PHP
quelconque est le test : si c'est impossible, ce n'est pas un component.

**`replace` n'est pas `require`.** Le `composer.json` du mono-dépôt déclare que
`symfony/symfony` *remplace* chaque package individuel ; il ne les installe pas
en plus.

## Application

- Une bibliothèque utile hors de tout framework → un component.
- Exposer une bibliothèque tierce dans des templates Twig → un bridge, puis un
  bundle pour la configuration.

## QCM d'entraînement

**Question 1.** Un projet installe `symfony/symfony`. Obtient-il
`symfony/phpunit-bridge` ?

- **A.** Oui, comme tous les packages du mono-dépôt
- **B.** Oui, mais seulement en `require-dev`
- **C.** Non : il est absent de `replace`
- **D.** Non : il est fusionné dans FrameworkBundle

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Le bridge PHPUnit n'est pas déclaré dans `replace` ; il
s'installe séparément, en général parmi les dependencies de développement.

</details>

**Question 2.** Combien de bridges la key `replace` du mono-dépôt liste-t-elle
sur la branche 8.0 ?

- **A.** Trois : Doctrine, Monolog et Twig
- **B.** Cinq, tout `src/Symfony/Bridge/`
- **C.** Un seul : le bridge Twig
- **D.** Aucun : `replace` ne liste que des components

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Le directory compte cinq bridges, mais `PhpUnit` et
`PsrHttpMessage` ne figurent pas dans `replace` et sont publiés à part.

</details>

**Question 3.** La key `replace` de `symfony/symfony` ne contient-elle que des
components ?

- **A.** Oui, par definition
- **B.** Non : aussi bridges et bundles
- **C.** Non : aussi des implémentations PSR
- **D.** Oui, plus `symfony/phpunit-bridge`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** `framework-bundle`, `security-bundle`, `twig-bundle`,
`debug-bundle` et `web-profiler-bundle` y figurent ; les PSR sont dans
`provide`.

</details>

**Question 4.** Quel bridge du mono-dépôt n'a besoin d'aucun component et
d'aucune bibliothèque tierce ?

- **A.** `symfony/twig-bridge`
- **B.** `symfony/psr-http-message-bridge`
- **C.** `symfony/doctrine-bridge`
- **D.** `symfony/phpunit-bridge`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Son `composer.json` n'exige que PHP : c'est un bridge
par son nom et son type déclaré, pas par ses dependencies.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- **Component** : autonome ; **bridge** : component ↔ tiers ; **bundle** :
  configure le framework.
- Un bridge seul **n'enregistre aucun service**.
- `replace` : **65** packages remplacés, pas installés en plus.
- Dans `replace` : **3 bridges**, **5 bundles** ; `provide` : des
  `-implementation`.
- `PhpUnit` et `PsrHttpMessage` : hors `replace`.
- `phpunit-bridge` : **PHP seulement**, `>=8.1.0`.

Moyen mnémotechnique : **« Seul, relié, configuré »**.

## Sources officielles

- [composer.json de symfony/symfony (branche 8.0)](https://github.com/symfony/symfony/blob/8.0/composer.json)
- [composer.json du bridge Twig](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/composer.json)
- [composer.json du bridge PHPUnit](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/PhpUnit/composer.json)
- [composer.json du bridge PsrHttpMessage](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/PsrHttpMessage/composer.json)

