---
id: CRS-nfkzx2s1n3r3
official_item: OIT-mgvdw7cfpwyz
title: "Forms handling"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-28"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/forms.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/forms.rst"
    anchor: "processing-forms"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/Form.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Form.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "Form::isValid(), Form::submit()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/Extension/HttpFoundation/HttpFoundationRequestHandler.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/HttpFoundation/HttpFoundationRequestHandler.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "HttpFoundationRequestHandler::handleRequest()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Form/Extension/Validator/Constraints/FormValidator.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Validator/Constraints/FormValidator.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "FormValidator::validate()"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "AbstractController::doRender()"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/InputBag.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/InputBag.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "InputBag::get()"
    verified_at: "2026-09-24"
outcome_sections:
  OUT-y3ytkg8qf2rc: "Le pattern recommandé"
  OUT-68682qbtydht: "L'ordre des deux tests"
  OUT-zr9efpstp5ny: "L'object est déjà à jour"
  OUT-mj8bjkemt33a: "`submit()`, pour le contrôle fin"
  OUT-hkxn2p2xjmg7: "`clearMissing` et la validation"
---

## Objectif

Traiter une submission dans le bon ordre, savoir ce que `handleRequest()`
décide à ta place, et ce que `submit($data, false)` valide vraiment.

## Avant la règle : le problème à résoudre

Une page « Nouvelle tâche » doit faire deux choses avec la même URL :

- au premier passage (`GET`), **afficher** un form vide ;
- au second (`POST`), **lire** ce que le user a saisi, le vérifier, puis
  soit enregistrer et rediriger, soit réafficher le form avec ses erreurs.

Écrire deux actions dupliquerait la construction du form. Écrire une seule
action oblige à savoir **dans quel passage on est**. Et une API `PATCH`, qui ne
reçoit qu'une partie des fields, pose une troisième question : que deviennent,
et comment sont validés, les fields **absents** ?

## Le fil rouge : une `Task`

```php
class Task
{
    #[Assert\NotBlank]
    public ?string $title = null;

    public ?string $note = null;
}
```

```php
#[Route('/task/new', name: 'task_new')]
public function new(Request $request): Response
{
    $task = new Task();
    $form = $this->createForm(TaskType::class, $task);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // $task contient déjà les valeurs saisies
        return $this->redirectToRoute('task_success');
    }

    return $this->render('task/new.html.twig', ['form' => $form]);
}
```

## Le cycle complet en un schéma

```mermaid
---
title: Une action, deux passages
---
flowchart TD
  accTitle: Une seule action qui affiche et traite un form
  accDescr: handleRequest() ne soumet rien si la method de la Request diffère de l'option method du form, POST par défaut, ni si la Request ne porte pas de données sous le nom du form. Sinon il appelle submit(), avec clearMissing à false pour PATCH. Le controller teste isSubmitted() puis isValid(). Soumis et valide, il redirige. Sinon il rend le form, et render() passe la Response en 422 si le form est soumis et invalide. isValid() sur un form non soumis lève une LogicException.
  HR["handleRequest()"] --> M{"method = option method ?"}
  M -->|"non"| NS["non soumis"]
  M -->|"oui"| D{"données sous le nom du form ?"}
  D -->|"non"| NS
  D -->|"oui"| SUB["submit(), clearMissing false pour PATCH"]
  SUB --> V{"isSubmitted() et isValid() ?"}
  NS --> V
  V -->|"oui"| RED(["redirect"])
  V -->|"non"| R{"soumis et invalide ?"}
  R -->|"oui"| R422(["render() : 422"])
  R -->|"non"| R200(["render() : 200"])
```

## Le pattern recommandé

C'est la forme que donne la documentation (`forms.rst`, « Processing Forms ») :
**une seule action** crée le form, appelle `handleRequest()`, teste
`isSubmitted() && isValid()`, redirige en cas de succès, et rend le template
dans tous les autres cas.

`handleRequest()` est ce qui rend cela possible : il regarde la Request, décide
si elle contient une submission pour **ce** form, et ne fait **rien** sinon. Au
premier passage, le form reste non soumis et le code tombe directement sur le
`render()`.

Le redirect après succès évite qu'un rafraîchissement du navigateur ne
renvoie le `POST`.

## Quand `handleRequest()` soumet-il ?

`HttpFoundationRequestHandler` (8.0) applique trois tests, dans cet ordre :

1. **La method.** Celle de la Request doit être égale à l'option `method` du
   form, `POST` par défaut. Sinon, il s'arrête.
2. **Le nom du form.** Pour `GET`, `HEAD` et `TRACE`, il lit la query string ;
   pour les autres methods, le body et les files. Si rien n'est rangé sous le nom du form, il
   s'arrête.
3. **La submission.** Il appelle `submit($data, 'PATCH' !== $method)`.

Exécuté avec Form 8.0.15 :

| Situation | `isSubmitted()` |
|---|---|
| form `POST`, Request `GET` portant ses fields | `false` |
| form `'method' => 'GET'`, Request `GET` avec `?task_form[title]=x` | `true` |
| form `POST`, Request `POST` sans key `task_form` | `false` |
| form `POST`, Request `PATCH` | `false` |
| form `'method' => 'PATCH'`, Request `PATCH` partielle | `true`, absents conservés |

Un form au nom vide (`createNamed('')`) lit toute la Request, mais n'est soumis
que si au moins un de ses fields y figure (exécuté).

`handleRequest()` attend une `Request` : `handleRequest(null)` lève une
`UnexpectedTypeException` avec l'extension HttpFoundation (exécuté).

## L'ordre des deux tests

`isSubmitted()` **avant** `isValid()` — ce n'est pas une question de style :
`Form::isValid()` **lève une `LogicException`** sur un form non soumis,
« Cannot check if an unsubmitted form is valid. Call Form::isSubmitted() and
ensure that it's true before calling Form::isValid(). » (exécuté).

À l'inverse, `isSubmitted()` vaut `true` même si les données sont invalides :
c'est `isValid()` qui compte les erreurs, **y compris celles des fields
enfants** (`getErrors(true)`). Exécuté : un `title` vide donne 0 erreur sur la
racine, 1 erreur en profondeur, et `isValid()` vaut `false`.

Un form soumis mais désactivé (`disabled`) est, lui, toujours valide.

## L'object est déjà à jour

Après la submission, l'object passé à `createForm()` **a été modifié en
place** : le form y a écrit chaque valeur par PropertyAccess. `getData()`
retourne ce même object — exécuté : `$form->getData() === $task` vaut `true`.

Tant que le form n'est pas soumis, `getData()` retourne les données initiales,
intactes.

Un form ne se soumet **qu'une fois** : un second `submit()` lève une
`AlreadySubmittedException`, « A form can only be submitted once. », et
`setData()` après submission en lève une aussi (exécuté).

## Le rendu qui suit

Rendre le form invalide est le comportement normal :
`AbstractController::render()` repère un form soumis et invalide parmi ses
parameters et passe la Response en **422** — seulement si son statut était
encore 200 (lu dans `doRender()`). Il convertit aussi le form en vue : pas
besoin d'appeler `createView()`.

`renderView()` convertit le form de la même manière, mais retourne une string :
il n'y a pas de Response, donc pas de 422.

## `submit()`, pour le contrôle fin

`handleRequest()` est recommandé. `submit()` sert quand on décide soi-même du
moment et de la donnée — une API JSON, par exemple :

```php
if ($request->isMethod('POST')) {
    $form->submit($request->getPayload()->all($form->getName()));
}
```

L'exemple de `forms.rst` (8.0) écrit `getPayload()->get($form->getName())`.
Pour un form composé, la valeur est un array, et `InputBag::get()` le
refuse : **`BadRequestException`**, « Input value "task_form" contains a
non-scalar value » (exécuté avec HttpFoundation 8.0.15). La forme correcte est
`all()`, comme ci-dessus.

Son second argument, `$clearMissing`, vaut `true` par défaut :

| Appel | `title` stocké `'keep'`, submission `['note' => 'n']` |
|---|---|
| `submit($data)` | `title` devient `null` |
| `submit($data, false)` | `title` reste `'keep'` |

C'est le cas d'une Request `PATCH`, et `handleRequest()` fait ce choix de
lui-même pour un form dont l'option `method` vaut `PATCH`.

## `clearMissing` et la validation

**Ce que dit la documentation.** `forms.rst` (8.0) avertit qu'avec
`$clearMissing` à `false` « the validation will only apply to the submitted
fields », et sa section « Constraints At Field Level » précise : « only the
constraints from the submitted form fields will be evaluated ».

**Ce que fait le code 8.0.** `FormValidator` fait deux choses distinctes :

- il valide l'option `constraints` de chaque field **seulement si ce field a
  été soumis** ;
- sur le form racine, il valide **tout l'object** (`data_class`) avec ses
  constraints de class, sans regarder quels fields ont été soumis.

Exécuté avec Form 8.0.15 et `submit(['nick' => 'z'], false)` :

| Où est le `NotBlank` / `Email` de `email` | `email` stocké | Résultat |
|---|---|---|
| option `constraints` du field | `''` | **valide** : field non soumis, constraint sautée |
| attribute sur `User::$email` | `'a@b.c'` | valide |
| attribute sur `User::$email` | `'not-an-email'` | **invalide** : validé avec la valeur stockée |

La phrase de la documentation est donc exacte pour l'option `constraints`, pas
pour les constraints déclarées sur la class : celles-ci valident un field
absent **avec la valeur déjà présente** dans l'object.

Pour forcer la validation d'un field absent, la documentation ajoute la key
avec `null` avant de soumettre :

```php
$form->submit(array_merge(['email' => null], $request->getPayload()->all()), false);
```

Exécuté : c'est efficace — le field est soumis, donc validé — mais `null`
**remplace** la valeur stockée quand le client n'envoie pas `email`. Le field
devient de fait obligatoire dans chaque `PATCH`.

## Concrètement dans ton application

- Une page HTML qui crée ou modifie : le pattern à une action,
  `handleRequest()`, `isSubmitted() && isValid()`, redirect.
- Un form de recherche en `GET` : `'method' => 'GET'`, sinon il n'est jamais
  soumis.
- Une API JSON : `submit($request->getPayload()->all(...))`, ou le payload
  décodé.
- Une API `PATCH` : `submit($data, false)`, en sachant que les constraints de
  l'entité continuent de s'appliquer aux valeurs stockées.

## À comprendre

- **Pourquoi `handleRequest()` ne fait-il rien au premier passage ?** Parce
  que c'est ce qui permet à une seule action de servir l'affichage et le
  traitement.
- **Pourquoi `isValid()` lève-t-elle une exception plutôt que de rendre
  `false` ?** Parce qu'un form non soumis n'est ni valide ni invalide : la
  question n'a pas de sens, et l'exception signale l'oubli du garde.
- **Pourquoi les constraints de class s'appliquent-elles à un field absent ?**
  Parce que le validator valide l'**object**, pas le form : il ne sait pas
  quelles properties viennent de la submission.

## À mémoriser

- Une action : `handleRequest()`, puis `isSubmitted() && isValid()`, redirect.
- `handleRequest()` : method du form, puis nom du form, puis `submit()`.
- `isValid()` sur un form non soumis : `LogicException`.
- L'object passé à `createForm()` est modifié en place ; `getData()` le rend.
- `render()` : 422 si un form soumis est invalide ; pas `renderView()`.
- `submit($data, false)` garde les absents ; `handleRequest()` le fait pour
  `PATCH`.
- `false` saute l'option `constraints` des absents, **pas** les constraints de
  la class.
- Moyen mnémotechnique : **« Method, nom, submission ; soumis, puis valide »**.

## Pièges d'examen

**`isValid()` sur un form non soumis lève une exception.** Le garde
`isSubmitted() && isValid()` n'est pas facultatif.

**Une Request d'une autre method que celle du form est ignorée.**

**Après une submission valide, l'object est déjà à jour.**

**La submission manuelle vide les fields absents par défaut** ; `false` les
préserve.

**`false` ne dispense pas un field absent des constraints de la class** : il
est validé avec sa valeur stockée ; seule l'option `constraints` du field est
sautée.

**`getPayload()->get()` refuse un form composé** : la valeur est un array, il
faut `all()`.

## Application : trois scénarios

**Scénario 1 — Le form de recherche qui ne cherche jamais.** Un
`SearchType` est envoyé par un `<form method="get">`, et `isSubmitted()` reste
à `false`. *Solution.* L'option `method` du form vaut `POST` par défaut :
déclarer `'method' => 'GET'`.

**Scénario 2 — L'erreur 500 au premier affichage.** Le controller teste
`if ($form->isValid())`. *Solution.* `isValid()` lève une `LogicException` sur
un form non soumis : écrire `if ($form->isSubmitted() && $form->isValid())`.

**Scénario 3 — Le `PATCH` refusé.** Une API `PATCH` ne modifie que `nick`, et la
Response signale une erreur sur `email`, que le client n'a pas envoyé.
*Solution.* `email` porte un `#[Assert\Email]` sur l'entité, et la valeur stockée
est invalide : les constraints de la class valident l'object entier, même avec
`false`. C'est la donnée stockée qu'il faut corriger.

## QCM d'entraînement

Sept questions sur le traitement d'un form. Choisis avant d'ouvrir la
correction.

**Question 1.** Un form de recherche est créé avec `'method' => 'GET'`. Une Request `GET` arrive avec `?search[q]=php`, et `search` est le nom du form. Que donne `handleRequest()` ?

- **A.** Rien : un form n'est soumis que par une Request `POST`
- **B.** Il est soumis avec les valeurs de la query string
- **C.** Une exception : `GET` ne doit pas porter de données
- **D.** Le form est soumis, mais avec des valeurs vides

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Pour `GET`, `HEAD` et `TRACE`, le handler lit la query
string sous le nom du form (exécuté : `isSubmitted()` vaut `true`).

A est le piège : c'est vrai d'un form qui garde la method `POST` par défaut.
C et D inventent des comportements.

</details>

**Question 2.** Au premier affichage de la page, le controller exécute `if ($form->isValid() && $form->isSubmitted())`. Que se passe-t-il ?

- **A.** `isValid()` rend `false`, et la page s'affiche normalement
- **B.** `isValid()` rend `true` : il n'y a encore aucune erreur
- **C.** La page s'affiche avec les messages d'erreur des fields requis
- **D.** Une `LogicException` : le form n'est pas soumis

<details>
<summary>Correction</summary>

**Bonne réponse : D.** `isValid()` est appelée **en premier** et lève une
exception sur un form non soumis (exécuté).

A et B supposent qu'elle rend un bool. C confond avec un form soumis et
invalide.

</details>

**Question 3.** Après `handleRequest()` et une submission valide, le controller transmet `$task` à un service sans avoir appelé `getData()`. Que contient `$task` ?

- **A.** Les valeurs soumises : le form a écrit dans cet object
- **B.** Les valeurs initiales : seul `getData()` rend une copie à jour
- **C.** Les valeurs soumises, à condition que `data_class` soit déclarée
- **D.** Des properties à `null` jusqu'à l'appel de `getData()`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** L'object passé à `createForm()` est modifié en place ;
`getData()` le retourne lui-même (exécuté : `===` vaut `true`).

B est le piège : `getData()` ne fait pas de copie. C : `data_class` est devinée
de l'object passé. D invente un remplissage différé.

</details>

**Question 4.** `$author->city` vaut `'Lyon'`. Le form a deux fields, `city` et `bio`. On appelle `$form->submit(['bio' => 'texte'])`, sans second argument. Que vaut `$author->city` ?

- **A.** `'Lyon'` : un field absent n'est jamais modifié par `submit()`
- **B.** `''` : la string vide du `TextType`
- **C.** `null` : `$clearMissing` vaut `true` par défaut
- **D.** Une exception : `city` manque à la submission

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Par défaut, les fields absents sont soumis avec `null`
(exécuté : `title` passe de `'keep'` à `null`).

A est le piège : c'est le comportement avec `false`. B et D inventent des
comportements.

</details>

**Question 5.** `User::$email` porte `#[Assert\Email]` et l'object stocke `'pas-un-email'`. Une API appelle `submit(['nick' => 'z'], false)`. Le form est-il valide ?

- **A.** Oui : avec `false`, un field absent n'est jamais validé
- **B.** Non : la constraint de class valide la valeur stockée
- **C.** Oui : seules les constraints de `nick` sont exécutées
- **D.** Non : `email` doit figurer dans chaque submission

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Le form racine valide tout l'object ; exécuté, la
violation est rattachée au field `email`, qui n'a pas été soumis.

A et C généralisent l'avertissement de la documentation, exact seulement pour
l'option `constraints` d'un field. D invente une obligation.

</details>

**Question 6.** Pour soumettre à la main un `TaskType` nommé `task_form` depuis une Request `POST`, quel appel rend les valeurs saisies sous la forme attendue par `submit()` ?

- **A.** `$request->getPayload()->all('task_form')`
- **B.** `$request->getPayload()->get('task_form')`
- **C.** `json_decode($request->getContent(), true)`
- **D.** `$request->query->all('task_form')`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** La valeur est un array : `all()` la rend.

B est le piège : `InputBag::get()` refuse une valeur non scalaire et lève une
`BadRequestException` (exécuté). C : le body d'un form HTML n'est pas du JSON,
`json_decode()` rend `null`. D lit la query string, vide pour ce `POST`.

</details>

**Question 7.** Le field `email` déclare `'constraints' => [new NotBlank()]` dans `buildForm()`, et l'object n'a aucune constraint. L'object stocke `''`. On appelle `submit(['nick' => 'z'], false)`. Le form est-il valide ?

- **A.** Non : `NotBlank` échoue sur la string vide
- **B.** Non : un field avec des constraints doit être soumis
- **C.** Une exception : `email` est requis
- **D.** Oui : l'option d'un field non soumis est ignorée

<details>
<summary>Correction</summary>

**Bonne réponse : D.** `FormValidator` ne valide les fields enfants que s'ils
ont été soumis (exécuté : valide).

A serait vrai si la constraint était sur la class, ou si `email` était soumis.
B et C inventent une obligation.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- **Une action** : `handleRequest()`, `isSubmitted() && isValid()`, redirect,
  sinon `render()`.
- `handleRequest()` : **method** du form (`POST` par défaut), puis **nom** du
  form, puis `submit()`.
- `isValid()` sur un form non soumis : **`LogicException`**.
- L'object passé à `createForm()` est **modifié en place**.
- `render()` passe en **422** pour un form soumis invalide.
- `submit($data)` met les absents à `null` ; **`false`** les garde, et
  `handleRequest()` le choisit pour `PATCH`.
- Avec `false`, l'option `constraints` d'un absent est sautée ; les
  **constraints de class** valident quand même sa valeur stockée.

**En une phrase, pour tout reconstruire :**

```text
handleRequest() : bonne method ? données sous le nom ? → submit($data, PATCH ? false : true)
  → isSubmitted() && isValid() → redirect | render() (422 si invalide)
```

## Sources officielles

- [Forms, « Processing Forms », « Using the submit() Method », « Constraints At Field Level »](https://github.com/symfony/symfony-docs/blob/8.0/forms.rst)
- [Form 8.0, `Form`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Form.php), [`HttpFoundationRequestHandler`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/HttpFoundation/HttpFoundationRequestHandler.php) et [`FormValidator`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/Extension/Validator/Constraints/FormValidator.php)
- [FrameworkBundle 8.0, `AbstractController`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.php)
