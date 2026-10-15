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
outcome_sections:
  OUT-4wrmsr0a1xvb: "La règle"
  OUT-5ram2kxkc3de: "Rendre un template"
  OUT-k5603xv7233j: "Rendre un template"
  OUT-fy6864mta4ff: "Servir un file"
  OUT-d9trsb0jwxp7: "Servir un file"
---

## Objectif

Produire la Response depuis un controller, et choisir le bon raccourci. Le
catalogue des sous-classes de `Response` et les methods de test de statut
appartiennent au lot HTTP.

## La règle

Un controller doit retourner un object `Response`. S'il retourne autre chose,
l'event `kernel.view` est dispatché pour convertir la valeur — sans listener
capable de le faire, la Request échoue. Retourner une `Response` est donc le
path normal, et tout le reste une convention à installer.

## Rendre un template

Deux methods, une différence de return type qui se retient mal :

| Method | Retourne |
|---|---|
| `render()` | un object `Response` |
| `renderView()` | une **string** |

`render()` accepte un troisième argument : une `Response` déjà construite, dont
il remplit le contenu. C'est ainsi qu'on impose un statut autre que 200.

`renderBlock()` et `renderBlockView()` font la même chose pour un seul block du
template. `stream()` retourne une `StreamedResponse` : le template est envoyé au
fur et à mesure, ce qui évite de construire une page entière en mémoire.

Sans le bundle Twig, ces methods lèvent une `LogicException` qui **nomme la
method appelée** et le package à installer. Elles ne retournent pas une Response
vide.

### Un form passé au template est converti pour vous

Avant le rendu, chaque parameter qui est un `FormInterface` est remplacé par le
résultat de son `createView()`. On peut donc passer le form lui-même ;
écrire `->createView()` à la main n'est pas une erreur, simplement redondant.

### Le 422 automatique tient à trois conditions, pas une

Le rendu sort en **422** sans qu'on l'ait demandé — mais seulement si les
**trois** conditions suivantes sont réunies :

1. la Response en cours est encore en **200** ;
2. un parameter est un `FormInterface` ;
3. ce form est **soumis** *et* invalide.

Deux conséquences que « un form invalide donne un 422 » laisse de côté.

**Un statut explicite l'emporte.** Si le troisième argument porte une `Response`
dont le statut n'est pas 200, la promotion n'a pas lieu du tout : le code ne
regarde les parameters que tant que le statut vaut 200.

**Non soumis n'est pas invalide.** Un form fraîchement construit, jamais
soumis, ne déclenche rien — la page d'affichage initiale sort donc bien en 200.

Le comportement vaut aussi pour `renderBlock()`, les deux passant par le même
code interne.

## JSON

`json()` retourne une `JsonResponse`. Si le component Serializer est disponible,
il est utilisé ; sinon la method retombe sur `json_encode`. Elle accepte le
statut, des headers et un contexte de serialization.

Le contexte n'est pas décoratif : il est fusionné par-dessus les options
d'encoding par défaut de `JsonResponse`, ce qui en fait l'entry point pour
les changer. Et sans Serializer, une donnée `null` est traitée à part — la
Response porte la string `null`, pas un body vide.

## Servir un file

`file()` retourne une `BinaryFileResponse`. Par défaut la disposition est
`attachment` — le navigateur télécharge. Pour un affichage dans la page, il faut
passer `ResponseHeaderBag::DISPOSITION_INLINE`. Le deuxième argument renomme le
file vu par le user sans toucher au file sur disque ; omis, c'est le
nom réel sur disque qui est annoncé.

Le header de disposition est écrit **dans tous les cas**, y compris pour la
default value : il n'y a pas de cas où le navigateur décide seul.

## Pièges d'examen

**Deux methods de rendu, deux return types.** L'une retourne une `Response`
prête à être retournée par le controller, l'autre une simple string. Retourner
la string depuis un controller déclenche `kernel.view`, et sans listener pour
la convertir, la Request échoue.

**Le 422 n'est pas inconditionnel.** Il faut un statut encore à 200, un
parameter `FormInterface`, et un form **soumis** *et* invalide. Un statut
explicite l'emporte ; un form non soumis ne déclenche rien.

**Servir un file télécharge par défaut.** L'affichage dans la page demande de
changer explicitement la disposition.

**Sans Twig, le rendu lève.** La `LogicException` nomme la method et le package
manquant ; ce n'est pas une Response vide.

## Tips d'examen

**Pour trancher une question sur le 422** : chercher le statut de départ avant
de chercher le form. Si l'énoncé construit une `Response` avec un statut,
la question est réglée sans regarder les parameters.

**Pour `render()` contre `renderView()`** : le suffixe `View` annonce ce qui est
rendu, la vue seule — donc une string, pas une Response.

## Points clés

- Retourner une `Response`, sinon `kernel.view` doit s'en charger.
- `render()` → `Response`, `renderView()` → `string`.
- Les parameters `FormInterface` sont convertis en vue automatiquement.
- Le 422 demande **trois** conditions ; un statut explicite l'emporte, et un
  form non soumis ne compte pas.
- `json()` utilise le Serializer s'il est installé ; le contexte porte les
  options d'encoding.
- `file()` télécharge par défaut ; `DISPOSITION_INLINE` pour afficher.

## Sources officielles

- [AbstractController, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php)
- [Controller, « Returning JSON Response »](https://github.com/symfony/symfony-docs/blob/8.0/controller.rst)
