---
id: CRS-k2xvr11x936e
official_item: OIT-hzbednd04fd4
title: "Official best practices"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/best_practices.rst"
    anchor: "the-symfony-framework-best-practices"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Reconnaître les recommandations officielles du document *Symfony Best
Practices*, et surtout celles que l'intuition contredit.

## Ce que le document est

Un document officiel, versionné avec la documentation, qui énonce une trentaine
de recommandations réparties en dix sections : création du projet,
configuration, logique métier, contrôleurs, gabarits, formulaires,
internationalisation, sécurité, ressources web, tests.

Ce sont des **recommandations pour une application web classique**, pas des
règles du framework : rien ne casse si on les ignore.

## Configuration : la règle des trois niveaux

C'est la partie la plus interrogée, parce qu'elle sépare trois choses que l'on
confond volontiers.

| Nature de la valeur | Où la mettre |
|---|---|
| dépend de l'endroit où tourne l'application (URL de base de données, identifiants SMTP) | **variable d'environnement** |
| est sensible | **secret** |
| relève de l'application, pas de l'infrastructure | **paramètre** |
| ne change presque jamais (nombre d'éléments par page) | **constante PHP** |

La quatrième ligne est la plus contre-intuitive : la recommandation officielle
est de **ne pas** faire un paramètre de configuration d'une option qui change
rarement, mais une constante de classe — parce qu'une constante est lisible
partout, y compris dans un gabarit, alors qu'un paramètre exige l'accès au
conteneur. Le prix à payer est qu'une constante est difficile à redéfinir dans
un test.

Les paramètres applicatifs se préfixent par `app.` pour éviter les collisions,
avec un ou deux mots descriptifs : `app.contents_dir` plutôt que `app.dir`.

## Les recommandations qui surprennent

- **Ne pas créer de bundle** pour organiser le code de l'application. Un projet
  = une application, sans bundle applicatif.
- **Rendre les services privés** autant que possible.
- **Un seul pare-feu** dans la configuration de sécurité.
- **Coder l'URL en dur dans un test fonctionnel**, au lieu de la générer — pour
  que le test échoue si l'URL publique change.
- **`snake_case` pour les noms de gabarits et de variables Twig**, et un
  **préfixe `_`** pour les fragments de gabarit.
- **Une seule action** pour afficher *et* traiter un formulaire.
- **Format XLIFF** pour les traductions, et des **clés** plutôt que le texte
  source comme identifiant de traduction.

## Points clés

- Environnement → infrastructure ; secret → sensible ; paramètre →
  application ; constante → ce qui ne change presque jamais.
- Préfixe `app.` pour les paramètres applicatifs.
- Pas de bundle pour le code applicatif ; services privés ; un seul pare-feu.
- URL en dur dans un test fonctionnel : c'est bien la recommandation.

## Sources officielles

- [The Symfony Framework Best Practices](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/best_practices.rst)
