---
id: CRS-k2xvr11x936e
official_item: OIT-hzbednd04fd4
title: "Official best practices"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-26"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/best_practices.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/best_practices.rst"
    anchor: "the-symfony-framework-best-practices"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
outcome_sections:
  OUT-bxcjgv614zqk: "Configuration : la règle des trois niveaux"
  OUT-2snt9b8p7pfa: "Configuration : la règle des trois niveaux"
  OUT-0b0m78g19vrs: "Les recommandations qui surprennent"
  OUT-2t4534jgce75: "Les recommandations qui surprennent"
---

## Objectif

Reconnaître les recommandations officielles du document *Symfony Best
Practices*, et surtout celles que l'intuition contredit.

## Ce que le document est

Un document officiel, versionné avec la documentation, qui énonce **exactement
trente** recommandations réparties en **dix** sections.

Ce sont des **recommandations pour une application web classique**, pas des
règles du framework : rien ne casse si on les ignore.

## Les trente, par section

| Section | Recommandations |
|---|---|
| Création du projet | binaire Symfony ; arborescence par défaut |
| Configuration | variables d'environment pour l'infrastructure ; secrets pour le sensible ; parameters pour l'applicatif ; noms courts et préfixés ; **constantes** pour ce qui change rarement |
| Logique métier | pas de bundle applicatif ; autowiring ; services privés ; configuration minimale de ses propres services ; attributes pour le mapping Doctrine |
| Controllers | étendre `AbstractController` ; attributes pour routage, cache et security ; dependency injection pour obtenir un service ; `EntityValueResolver` **si c'est commode** |
| Templates | `snake_case` pour noms et variables ; préfixe `_` pour les fragments |
| Forms | forms en classes PHP ; **boutons dans les templates** ; constraints sur l'object sous-jacent ; une seule action pour afficher et traiter |
| Internationalisation | format XLIFF ; **keys** plutôt que texte source |
| Security | **un seul** firewall ; hasher `auto` ; voters pour le contrôle fin |
| Ressources web | AssetMapper |
| Tests | *smoke tests* des URL ; URL **en dur** dans un functional test |

## Configuration : la règle des trois niveaux

C'est la partie la plus interrogée, parce qu'elle sépare trois choses que l'on
confond volontiers.

| Nature de la valeur | Où la mettre |
|---|---|
| dépend de l'endroit où tourne l'application (URL de base de données, identifiers SMTP) | **variable d'environment** |
| est sensible | **secret** |
| relève de l'application, pas de l'infrastructure | **parameter** |
| ne change presque jamais (nombre d'éléments par page) | **constante PHP** |

La quatrième ligne est la plus contre-intuitive : la recommandation officielle
est de **ne pas** faire un parameter de configuration d'une option qui change
rarement, mais une constant de class — parce qu'une constant est lisible
partout, y compris dans un template, alors qu'un parameter exige l'accès au
container. Le prix à payer est qu'une constant est difficile à redéfinir dans
un test.

Les parameters applicatifs se préfixent par `app.` pour éviter les collisions,
avec un ou deux mots descriptifs : `app.contents_dir` plutôt que `app.dir`.

## Les recommandations qui surprennent

- **Ne pas créer de bundle** pour organiser le code de l'application. Un projet
  = une application, sans bundle applicatif.
- **Coder l'URL en dur dans un functional test**, au lieu de la générer — pour
  que le test échoue si l'URL publique change. À ne pas confondre avec le
  *smoke test* des URL, qui les parcourt toutes via un provider de données
  PHPUnit.
- **Les boutons d'un form vont dans le template**, ni dans la class de
  form, ni dans le controller : la class doit rester agnostique quant à
  l'endroit où elle sert. Le même form affiche « Ajouter » à la création
  et « Enregistrer » à l'édition — et le style du bouton reste dans le template.
- **`EntityValueResolver` est facultatif** : « si c'est commode ». Dès que la
  logique de récupération se complique, la recommandation est d'écrire la
  query **dans le controller**.
- **Hasher `auto`**, qui choisit le meilleur disponible selon l'installation
  PHP. Son défaut actuel est **`bcrypt`**.

## Pièges d'examen

**Une valeur qui ne change presque jamais ne devient pas un parameter.** La
recommandation officielle est d'en faire une **constante de class** : elle est
lisible partout, y compris depuis un template, là où un parameter exige le
container.

**L'URL se code en dur dans un functional test.** Ce n'est pas un raccourci
toléré, c'est la recommandation : le test doit échouer si l'URL publique change.

**Pas de bundle pour le code de l'application.** Un projet est une application ;
les bundles sont pour le code partagé entre projets.

**Le bouton n'appartient pas au form.** Le mettre dans la class est
précisément ce que le document déconseille, parce que la class doit pouvoir
servir à la création comme à l'édition.

**`auto` n'est pas un algorithme.** C'est un selector ; ce qu'il choisit
aujourd'hui est `bcrypt`.

## Tips d'examen

**Trente recommandations, dix sections.** Si un nombre est demandé, ce sont
ceux-là.

**Deux recommandations portent sur les URL de test** — les deux seules de la
section *Tests* —, et elles ne disent pas la même chose : *smoke tester* toutes les URL avec un provider de données,
et coder l'URL **en dur** dans le functional test.

## Points clés

- Environment → infrastructure ; secret → sensible ; parameter →
  application ; constante → ce qui ne change presque jamais.
- Préfixe `app.` pour les parameters applicatifs.
- Pas de bundle pour le code applicatif ; services privés ; un seul firewall.
- URL en dur dans un functional test : c'est bien la recommandation.
- Trente recommandations, dix sections.
- Boutons de form dans le **template** ; `EntityValueResolver` facultatif ;
  hasher `auto`, aujourd'hui `bcrypt`.

## Sources officielles

- [The Symfony Framework Best Practices](https://github.com/symfony/symfony-docs/blob/8.0/best_practices.rst)
