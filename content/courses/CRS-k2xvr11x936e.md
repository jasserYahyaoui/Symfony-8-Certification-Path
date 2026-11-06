---
id: CRS-k2xvr11x936e
official_item: OIT-hzbednd04fd4
title: "Official best practices"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-26"
format: pedagogical
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

## L'essentiel

Le document *Symfony Best Practices* énonce **trente** recommandations en **dix**
sections, pour une application web classique : ce ne sont pas des règles du
framework, rien ne casse si on les ignore. L'examen vise celles que
l'intuition contredit — la constant plutôt que le parameter, l'URL en dur,
pas de bundle applicatif, les boutons dans le template.

## Le schéma

```text
dépend de la machine (URL de base, SMTP)   → variable d'environment
sensible                                   → secret
applicatif, identique partout              → parameter, préfixé app. (app.contents_dir)
change presque jamais (éléments par page)  → constante de class
code applicatif → src/, sans bundle   ·   controller → AbstractController   ·   1 firewall, hasher auto (bcrypt)
form → class PHP, boutons dans le template, constraints sur l'object   ·   tests → smoke test + URL en dur
```

## L'exemple : une valeur qui ne change presque jamais

```php
final class Post
{
    public const NUMBER_OF_ITEMS = 10;   // lisible partout, même dans un template
}
```

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

Variable d'environment pour ce qui dépend de la machine, secret pour le
sensible, parameter pour l'applicatif — et une **constante de class** pour ce
qui ne change presque jamais : elle est lisible partout, y compris dans un
template, là où un parameter exige le container ; le prix est qu'elle se
redéfinit mal dans un test. Les parameters applicatifs se préfixent par `app.`,
avec un ou deux mots descriptifs : `app.contents_dir` plutôt que `app.dir`.

## Les recommandations qui surprennent

- **Pas de bundle** pour organiser le code de l'application.
- **L'URL en dur dans un functional test**, pour qu'il échoue si l'URL publique
  change ; les deux seules de la section *Tests* disent aussi de *smoke tester*
  toutes les URL avec un provider de données.
- **Les boutons d'un form vont dans le template** : la même class sert à créer
  (« Ajouter ») et à éditer (« Enregistrer »).
- **`EntityValueResolver` est facultatif** : dès que la récupération se
  complique, écrire la query **dans le controller**.
- **Hasher `auto`** : un selector, dont le défaut actuel est **`bcrypt`**.

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

## Application

- L'adresse d'expédition des emails, identique sur chaque machine → un
  parameter `app.…`.
- Le password de la base, différent par machine et privé → un secret.

## QCM d'entraînement

**Question 1.** Que recommandent les best practices pour les translations ?

- **A.** Le format YAML, avec le texte source comme key
- **B.** Le format PHP, plus rapide à charger
- **C.** Le format XLIFF, avec des keys
- **D.** Le format JSON, lisible par le front

<details>
<summary>Correction</summary>

**Bonne réponse : C.** XLIFF est le format le mieux outillé ; des keys
évitent de toucher tous les catalogues quand le texte d'origine change.

</details>

**Question 2.** Combien de firewalls la configuration de sécurité devrait-elle
définir, selon le document ?

- **A.** Deux : un pour le site, un pour l'administration
- **B.** Un seul, en règle générale
- **C.** Un par zone protégée
- **D.** Aucun : `access_control` suffit

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Un seul firewall garde la configuration simple ; un
second ne se justifie que pour deux systèmes vraiment différents.

</details>

**Question 3.** Où poser les constraints de validation d'un form ?

- **A.** Sur l'object sous-jacent
- **B.** Dans la class de form, field par field
- **C.** Dans le controller, avant `isValid()`
- **D.** Dans le template, à côté du field

<details>
<summary>Correction</summary>

**Bonne réponse : A.** La validation porte sur les données : posée sur
l'object, elle vaut pour tout form et tout autre entry point.

</details>

**Question 4.** Comment nommer un template qui n'est qu'un fragment inclus
ailleurs ?

- **A.** Avec le suffixe `.fragment.html.twig`
- **B.** Dans un directory `fragments/`
- **C.** En `UpperCamelCase`, comme une class
- **D.** Avec un préfixe `_`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Le soulignement initial distingue un fragment d'un
template complet, le reste du nom suivant le `snake_case`.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- **Trente** recommandations, **dix** sections, aucune règle du framework.
- Environment / secret / parameter `app.` / **constante** pour le quasi-fixe.
- **Pas de bundle** applicatif ; services privés ; autowiring.
- `AbstractController` ; `EntityValueResolver` **si c'est commode**.
- Boutons dans le **template** ; constraints sur l'**object**.
- **Un seul** firewall ; hasher `auto`, aujourd'hui `bcrypt`.
- XLIFF et keys ; AssetMapper ; **URL en dur** en functional test.

Moyen mnémotechnique : **« Recommandé n'est pas obligatoire »**.

## Sources officielles

- [The Symfony Framework Best Practices](https://github.com/symfony/symfony-docs/blob/8.0/best_practices.rst)

