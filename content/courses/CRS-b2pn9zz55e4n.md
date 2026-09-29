---
id: CRS-b2pn9zz55e4n
official_item: OIT-v7zyhcm44m88
title: "Form events"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-29"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/form/events.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/form/events.rst"
    anchor: "form-events"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/Form.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Form.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "Form::submit(), add(), remove(), getData()"
    verified_at: "2026-09-25"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/Extension/Validator/EventListener/ValidationListener.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Validator/EventListener/ValidationListener.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "ValidationListener::validateForm(), isRoot()"
    verified_at: "2026-09-25"
---

## Objectif

Modifier un formulaire pendant son cycle de vie : ajouter un champ selon la
donnée, assainir une valeur brute, réagir après la soumission. C'est le
mécanisme qui rend un formulaire dynamique.

## Prérequis

Les trois couches de données du composant Form, et le dispatcher d'événements.

## Deux moments, cinq événements

Le cycle se divise en deux phases : la **mise en place** de la donnée initiale,
et la **soumission**.

### Mise en place

Les entrées numérotées sont les événements. Les lignes en retrait entre elles ne
sont pas des événements : ce sont les transformations que le composant effectue.

1. **`PRE_SET_DATA`** — la donnée du **modèle**, avant toute transformation. Les
   champs ne sont pas encore tous construits : **c'est ici qu'on en ajoute ou
   qu'on en retire** en fonction de la donnée initiale.

   *→ le composant calcule les trois représentations*

2. **`POST_SET_DATA`** — les trois représentations existent. Bon endroit pour
   décider en connaissant l'état complet, par exemple « l'objet est-il neuf ou
   existant ? ». On peut encore y ajouter ou retirer des champs, même si la
   documentation préfère `PRE_SET_DATA`.

### Soumission

3. **`PRE_SUBMIT`** — la donnée **brute de la requête**, non transformée :
   chaînes et tableaux, plus les objets `UploadedFile` d'un champ de fichier.
   C'est le moment pour assainir une valeur, ou pour ajouter des champs d'après
   ce que l'utilisateur a envoyé — le cas des listes dépendantes.

   *→ les enfants sont soumis, puis la vue est transformée en normalisée*

4. **`SUBMIT`** — la donnée **normalisée**. On peut encore changer les valeurs,
   mais les enfants ont **déjà été soumis** : la documentation dit qu'on ne peut
   plus ajouter ni retirer de champ.

   *→ le composant transforme la normalisée en modèle*

5. **`POST_SUBMIT`** — `$event->getData()` rend la donnée de **vue** ; la
   donnée du modèle se lit par `$event->getForm()->getData()`. La structure de
   *ce* formulaire est figée. **La validation s'exécute par un écouteur sur cet
   événement**, ce qui explique qu'un objet peuplé et validé soit disponible une
   fois la soumission terminée.

## Ce que dit le code sur le verrouillage

`Form::add()` et `Form::remove()` ne testent qu'une chose : le formulaire est-il
marqué soumis ? Or `Form::submit()` pose ce marqueur **après** l'événement
`SUBMIT` et **avant** `POST_SUBMIT`. Exécuté avec `symfony/form` 8.0.15, un
écouteur du formulaire racine qui ajoute un champ `extra` :

| Événement | `add()` | Le champ ajouté |
|---|---|---|
| `PRE_SET_DATA`, `POST_SET_DATA`, `PRE_SUBMIT` | accepté | soumis, donnée envoyée |
| `SUBMIT` | accepté, **sans erreur** | **jamais soumis**, garde sa donnée initiale |
| `POST_SUBMIT` | `AlreadySubmittedException` | — |

Le verrou de `SUBMIT` est donc fonctionnel, pas une exception : un champ ajouté
là est silencieusement ignoré par la soumission. L'exception n'arrive qu'à
`POST_SUBMIT`, avec « You cannot add children to a submitted form. ».

## Ce que porte chaque événement

Exécuté sur un champ texte muni d'un transformateur de modèle entre l'entier
`5` et la chaîne `'n:5'`, puis soumis avec `'n:7'` :

| Événement | `$event->getData()` | `$event->getForm()->getData()` |
|---|---|---|
| `PRE_SET_DATA` | `5` | interdit : `RuntimeException` |
| `POST_SET_DATA` | `5` | `5` |
| `PRE_SUBMIT` | `'n:7'` | `5` |
| `SUBMIT` | `'n:7'` | `5` |
| `POST_SUBMIT` | `'n:7'` | `7` |

Dans un écouteur `PRE_SET_DATA`, appeler `getData()` sur le formulaire lève
une `RuntimeException` : « A cycle was detected. Listeners to the PRE_SET_DATA
event must not call getData() if the form data has not already been set. » On
lit la donnée sur l'événement.

## Le tableau de décision

| Besoin | Événement |
|---|---|
| modifier la donnée initiale | `PRE_SET_DATA` |
| adapter la structure à la donnée initiale | `PRE_SET_DATA`, ou `POST_SET_DATA` |
| assainir la donnée brute soumise | `PRE_SUBMIT` |
| ajouter des champs d'après la valeur soumise | `PRE_SUBMIT` sur le **parent**, ou `POST_SUBMIT` sur l'**enfant** |
| modifier la donnée normalisée | `SUBMIT` |
| réagir après coup, journaliser | `POST_SUBMIT` |

La question qui tranche entre `PRE_SET_DATA` et `PRE_SUBMIT` est celle que pose
la documentation : réagit-on à la donnée **initiale**, qui vient de l'objet, ou à
la donnée **soumise**, qui vient de la requête ?

## Les formulaires imbriqués

Un champ dépendant d'un autre ne peut pas s'ajouter depuis son propre
`POST_SUBMIT` au formulaire lui-même : sa structure est figée. On l'ajoute au
**formulaire parent**, depuis l'événement de l'enfant. C'est ce qui rend le motif
des listes dépendantes contre-intuitif à écrire.

La validation, elle, ne part que du formulaire **racine** : `ValidationListener`
écoute le `POST_SUBMIT` de chaque formulaire, mais n'agit que si
`isRoot()` est vrai, et valide alors tout l'arbre. Exécuté : dans le `POST_SUBMIT` d'un enfant, aucune
erreur de validation n'existe encore ; dans un écouteur `POST_SUBMIT` de la
racine, de même priorité mais enregistré après, l'erreur `NotBlank` est déjà là.

## Comment s'abonner

Sur le constructeur, pour un cas local :

```php
$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
    $form = $event->getForm();
    $data = $event->getData();
});
```

Ou par une classe implémentant `EventSubscriberInterface`, réutilisable et
injectable.

Les écouteurs se déclarent pendant `buildForm()` : une fois le formulaire
construit, son dispatcher est un `ImmutableEventDispatcher`.

## Pièges d'examen

- `PRE_SUBMIT` reçoit la donnée **brute** : chaînes, tableaux, fichiers
  téléversés — pas d'objet du modèle.
- **À `SUBMIT`, `add()` ne lève rien**, mais le champ ajouté n'est jamais
  soumis ; l'exception n'arrive qu'à `POST_SUBMIT`.
- **`POST_SUBMIT` porte la donnée de vue** ; le modèle est sur le formulaire.
- Dans `PRE_SET_DATA`, **`$form->getData()` lève une exception** : lire
  `$event->getData()`.
- La validation s'exécute sur le `POST_SUBMIT` de la **racine**, pas avant.
- Un champ dépendant s'ajoute au **parent**.

## Points clés

- Cinq événements, deux phases, un ordre fixe.
- Structure modifiable à `PRE_SET_DATA`, `POST_SET_DATA` et `PRE_SUBMIT`.
- `SUBMIT` : champ ajouté ignoré ; `POST_SUBMIT` : `AlreadySubmittedException`.
- Données : modèle, modèle, requête, normalisée, vue.
- Le choix se fait sur l'origine de la donnée : objet ou requête.

## Sources officielles

- [Form Events](https://github.com/symfony/symfony-docs/blob/8.0/form/events.rst)
- [Form 8.0, `Form`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Form.php) : `submit()`, `add()`, `remove()`, `getData()`
- [Form 8.0, `ValidationListener`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Validator/EventListener/ValidationListener.php)
