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
    symbol_or_lines: "render, renderView, stream, json, file"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller.rst"
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

Un cas n'a pas besoin de ce troisième argument : si l'un des paramètres passés
au gabarit est un **formulaire invalide**, `render()` retourne d'elle-même un
**422**. Le comportement est documenté sur la méthode et vaut aussi pour
`renderBlock()`.

`renderBlock()` et `renderBlockView()` font la même chose pour un seul bloc du
gabarit. `stream()` retourne une `StreamedResponse` : le gabarit est envoyé au
fur et à mesure, ce qui évite de construire une page entière en mémoire.

## JSON

`json()` retourne une `JsonResponse`. Si le composant Serializer est disponible,
il est utilisé ; sinon la méthode retombe sur `json_encode`. Elle accepte le
statut, des en-têtes et un contexte de sérialisation.

## Servir un fichier

`file()` retourne une `BinaryFileResponse`. Par défaut la disposition est
`attachment` — le navigateur télécharge. Pour un affichage dans la page, il faut
passer `ResponseHeaderBag::DISPOSITION_INLINE`. Le deuxième argument renomme le
fichier vu par l'utilisateur sans toucher au fichier sur disque.

## Points clés

- Retourner une `Response`, sinon `kernel.view` doit s'en charger.
- `render()` → `Response`, `renderView()` → `string`.
- Le troisième argument de `render()` impose un statut ; un formulaire
  invalide dans les paramètres donne un 422 sans rien demander.
- `json()` utilise le Serializer s'il est installé.
- `file()` télécharge par défaut ; `DISPOSITION_INLINE` pour afficher.

## Sources officielles

- [AbstractController, branche 8.0](https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php)
- [Controller, « Returning JSON Response »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller.rst)
