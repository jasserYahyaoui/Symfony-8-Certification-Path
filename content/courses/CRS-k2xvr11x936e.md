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
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/best_practices.rst"
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

Un document officiel, versionné avec la documentation, qui énonce **exactement
trente** recommandations réparties en **dix** sections.

Ce sont des **recommandations pour une application web classique**, pas des
règles du framework : rien ne casse si on les ignore.

## Les trente, par section

| Section | Recommandations |
|---|---|
| Création du projet | binaire Symfony ; arborescence par défaut |
| Configuration | variables d'environnement pour l'infrastructure ; secrets pour le sensible ; paramètres pour l'applicatif ; noms courts et préfixés ; **constantes** pour ce qui change rarement |
| Logique métier | pas de bundle applicatif ; autowiring ; services privés ; configuration minimale de ses propres services ; attributs pour le mapping Doctrine |
| Contrôleurs | étendre `AbstractController` ; attributs pour routage, cache et sécurité ; injection de dépendances pour obtenir un service ; `EntityValueResolver` **si c'est commode** |
| Gabarits | `snake_case` pour noms et variables ; préfixe `_` pour les fragments |
| Formulaires | formulaires en classes PHP ; **boutons dans les gabarits** ; contraintes sur l'objet sous-jacent ; une seule action pour afficher et traiter |
| Internationalisation | format XLIFF ; **clés** plutôt que texte source |
| Sécurité | **un seul** pare-feu ; hacheur `auto` ; voteurs pour le fin |
| Ressources web | AssetMapper |
| Tests | *smoke tests* des URL ; URL **en dur** dans un test fonctionnel |

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
- **Coder l'URL en dur dans un test fonctionnel**, au lieu de la générer — pour
  que le test échoue si l'URL publique change. À ne pas confondre avec le
  *smoke test* des URL, qui les parcourt toutes via un fournisseur de données
  PHPUnit.
- **Les boutons d'un formulaire vont dans le gabarit**, ni dans la classe de
  formulaire, ni dans le contrôleur : la classe doit rester agnostique quant à
  l'endroit où elle sert. Le même formulaire affiche « Ajouter » à la création
  et « Enregistrer » à l'édition — et le style du bouton reste dans le gabarit.
- **`EntityValueResolver` est facultatif** : « si c'est commode ». Dès que la
  logique de récupération se complique, la recommandation est de faire la
  requête **dans le contrôleur**.
- **Hacheur `auto`**, qui choisit le meilleur disponible selon l'installation
  PHP. Son défaut actuel est **`bcrypt`**.

## Pièges d'examen

**Une valeur qui ne change presque jamais ne devient pas un paramètre.** La
recommandation officielle est d'en faire une **constante de classe** : elle est
lisible partout, y compris depuis un gabarit, là où un paramètre exige le
conteneur.

**L'URL se code en dur dans un test fonctionnel.** Ce n'est pas un raccourci
toléré, c'est la recommandation : le test doit échouer si l'URL publique change.

**Pas de bundle pour le code de l'application.** Un projet est une application ;
les bundles sont pour le code partagé entre projets.

**Le bouton n'appartient pas au formulaire.** Le mettre dans la classe est
précisément ce que le document déconseille, parce que la classe doit pouvoir
servir à la création comme à l'édition.

**`auto` n'est pas un algorithme.** C'est un sélecteur ; ce qu'il choisit
aujourd'hui est `bcrypt`.

## Tips d'examen

**Trente recommandations, dix sections.** Si un nombre est demandé, ce sont
ceux-là.

**Trois recommandations portent sur les URL de test**, et elles ne disent pas
la même chose : *smoke tester* toutes les URL avec un fournisseur de données,
et coder l'URL **en dur** dans le test fonctionnel.

## Points clés

- Environnement → infrastructure ; secret → sensible ; paramètre →
  application ; constante → ce qui ne change presque jamais.
- Préfixe `app.` pour les paramètres applicatifs.
- Pas de bundle pour le code applicatif ; services privés ; un seul pare-feu.
- URL en dur dans un test fonctionnel : c'est bien la recommandation.
- Trente recommandations, dix sections.
- Boutons de formulaire dans le **gabarit** ; `EntityValueResolver` facultatif ;
  hacheur `auto`, aujourd'hui `bcrypt`.

## Sources officielles

- [The Symfony Framework Best Practices](https://github.com/symfony/symfony-docs/blob/8.0/best_practices.rst)
