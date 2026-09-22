---
id: CRS-a669jpap5zf2
official_item: OIT-2emwghgkkrdy
title: "The response"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php"
    symbol_or_lines: "render, renderView, stream, json, file"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/controller.rst"
    anchor: "returning-json-response"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Produire la réponse depuis un contrôleur, et choisir le bon raccourci. Le
catalogue des sous-classes de `Response` et les méthodes de test de statut
appartiennent au lot HTTP.

## La règle

Un contrôleur doit retourner un objet `Response`. S'il retourne autre chose,
l'événement `kernel.view` est dispatché pour convertir la valeur — sans écouteur
capable de le faire, la requête échoue. Retourner une `Response` est donc le
chemin normal, et tout le reste une convention à installer.

## Rendre un gabarit

Deux méthodes, une différence de type de retour qui se retient mal :

| Méthode | Retourne |
|---|---|
| `render()` | un objet `Response` |
| `renderView()` | une **chaîne** |

`render()` accepte un troisième argument : une `Response` déjà construite, dont
il remplit le contenu. C'est ainsi qu'on impose un statut autre que 200.

`renderBlock()` et `renderBlockView()` font la même chose pour un seul bloc du
gabarit. `stream()` retourne une `StreamedResponse` : le gabarit est envoyé au
fur et à mesure, ce qui évite de construire une page entière en mémoire.

Sans le bundle Twig, ces méthodes lèvent une `LogicException` qui **nomme la
méthode appelée** et le paquet à installer. Elles ne retournent pas une réponse
vide.

### Un formulaire passé au gabarit est converti pour vous

Avant le rendu, chaque paramètre qui est un `FormInterface` est remplacé par le
résultat de son `createView()`. On peut donc passer le formulaire lui-même ;
écrire `->createView()` à la main n'est pas une erreur, simplement redondant.

### Le 422 automatique tient à trois conditions, pas une

Le rendu sort en **422** sans qu'on l'ait demandé — mais seulement si les
**trois** conditions suivantes sont réunies :

1. la réponse en cours est encore en **200** ;
2. un paramètre est un `FormInterface` ;
3. ce formulaire est **soumis** *et* invalide.

Deux conséquences que « un formulaire invalide donne un 422 » laisse de côté.

**Un statut explicite l'emporte.** Si le troisième argument porte une `Response`
dont le statut n'est pas 200, la promotion n'a pas lieu du tout : le code ne
regarde les paramètres que tant que le statut vaut 200.

**Non soumis n'est pas invalide.** Un formulaire fraîchement construit, jamais
soumis, ne déclenche rien — la page d'affichage initiale sort donc bien en 200.

Le comportement vaut aussi pour `renderBlock()`, les deux passant par le même
code interne.

## JSON

`json()` retourne une `JsonResponse`. Si le composant Serializer est disponible,
il est utilisé ; sinon la méthode retombe sur `json_encode`. Elle accepte le
statut, des en-têtes et un contexte de sérialisation.

Le contexte n'est pas décoratif : il est fusionné par-dessus les options
d'encodage par défaut de `JsonResponse`, ce qui en fait le point d'entrée pour
les changer. Et sans Serializer, une donnée `null` est traitée à part — la
réponse porte la chaîne `null`, pas un corps vide.

## Servir un fichier

`file()` retourne une `BinaryFileResponse`. Par défaut la disposition est
`attachment` — le navigateur télécharge. Pour un affichage dans la page, il faut
passer `ResponseHeaderBag::DISPOSITION_INLINE`. Le deuxième argument renomme le
fichier vu par l'utilisateur sans toucher au fichier sur disque ; omis, c'est le
nom réel sur disque qui est annoncé.

L'en-tête de disposition est écrit **dans tous les cas**, y compris pour la
valeur par défaut : il n'y a pas de cas où le navigateur décide seul.

## Pièges d'examen

**Deux méthodes de rendu, deux types de retour.** L'une retourne une `Response`
prête à être retournée par le contrôleur, l'autre une simple chaîne. Retourner
la chaîne depuis un contrôleur déclenche `kernel.view`, et sans écouteur pour
la convertir, la requête échoue.

**Le 422 n'est pas inconditionnel.** Il faut un statut encore à 200, un
paramètre `FormInterface`, et un formulaire **soumis** *et* invalide. Un statut
explicite l'emporte ; un formulaire non soumis ne déclenche rien.

**Servir un fichier télécharge par défaut.** L'affichage dans la page demande de
changer explicitement la disposition.

**Sans Twig, le rendu lève.** La `LogicException` nomme la méthode et le paquet
manquant ; ce n'est pas une réponse vide.

## Tips d'examen

**Pour trancher une question sur le 422** : chercher le statut de départ avant
de chercher le formulaire. Si l'énoncé construit une `Response` avec un statut,
la question est réglée sans regarder les paramètres.

**Pour `render()` contre `renderView()`** : le suffixe `View` annonce ce qui est
rendu, la vue seule — donc une chaîne, pas une réponse.

## Points clés

- Retourner une `Response`, sinon `kernel.view` doit s'en charger.
- `render()` → `Response`, `renderView()` → `string`.
- Les paramètres `FormInterface` sont convertis en vue automatiquement.
- Le 422 demande **trois** conditions ; un statut explicite l'emporte, et un
  formulaire non soumis ne compte pas.
- `json()` utilise le Serializer s'il est installé ; le contexte porte les
  options d'encodage.
- `file()` télécharge par défaut ; `DISPOSITION_INLINE` pour afficher.

## Sources officielles

- [AbstractController, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php)
- [Controller, « Returning JSON Response »](https://github.com/symfony/symfony-docs/blob/8.0/controller.rst)
