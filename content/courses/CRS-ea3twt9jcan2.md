---
id: CRS-ea3twt9jcan2
official_item: OIT-6cr9b8ea8g32
title: "The request"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/controller.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/controller.rst"
    anchor: "the-request-object-as-a-controller-argument"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/RequestStack.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/RequestStack.php"
    symbol_or_lines: "getCurrentRequest, getMainRequest, getParentRequest, getSession"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-22"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpFoundation/Request.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Request.php"
    symbol_or_lines: "getPayload"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-22"
---

## Objectif

Savoir comment un contrôleur obtient la requête et où atterrissent les
paramètres de route. Le modèle des sacs appartient au lot HTTP, le composant
lui-même au lot Symfony Architecture.

## L'obtenir

Il n'y a rien à faire : il suffit de **typer un argument** avec
`Symfony\Component\HttpFoundation\Request`. Symfony le remplit.

```php
public function index(Request $request): Response
```

Le nom de l'argument est libre ; c'est le type qui déclenche l'injection. Dans
un **service**, en revanche, la requête ne s'injecte pas : on injecte
`RequestStack` et on appelle `getCurrentRequest()`.

## Les paramètres de route

Un paramètre déclaré dans le chemin de la route est déposé dans
`$request->attributes`, puis passé au contrôleur **comme argument nommé** :

```php
#[Route('/lucky/number/{max}')]
public function number(int $max): Response
```

La correspondance se fait sur le **nom**, jamais sur la position. Réordonner les
arguments ne change rien ; les renommer casse tout.

Le sac `attributes` contient aussi les clés internes que le framework y place,
notamment `_route` et `_controller`.

### `RequestStack` porte une pile, pas une requête

Trois accesseurs, et ils diffèrent pendant une **sous-requête** :

| Méthode | Rend |
|---|---|
| `getCurrentRequest()` | la requête en cours — la **sous-requête** si on est dedans |
| `getMainRequest()` | celle qui est entrée par le serveur |
| `getParentRequest()` | `null` quand la courante **est** la principale |

`getSession()` est l'exception : elle ne rend pas `null`, elle **lève** une
`SessionNotFoundException`.

## Corps et chaîne de requête

`$request->query` porte la chaîne de requête. Pour le corps, `getPayload()` est
la méthode à connaître — mais son comportement n'est pas symétrique, et c'est
tout l'intérêt de la question.

Elle procède **dans cet ordre** :

| Situation | Ce qu'elle rend |
|---|---|
| `$request->request` n'est pas vide | un **clone** de ce sac — les données de formulaire l'emportent |
| sinon, corps brut vide | un `InputBag` **vide** |
| sinon | le corps décodé en JSON, dans un `InputBag` neuf |

Trois conséquences que la formulation « elle lit le corps quel que soit son
format » écrase :

**Le formulaire est prioritaire.** Le JSON n'est lu que si le sac des paramètres
de formulaire est vide ; ce n'est pas une union des deux.

**C'est un clone.** Modifier l'objet rendu ne modifie pas `$request->request`.

**Un JSON invalide lève.** Le décodage est fait avec l'option qui transforme
l'erreur en exception : un corps mal formé donne une `JsonException`, il ne
donne pas un sac vide. Et un JSON valide qui ne décode **pas en tableau** — un
nombre, une chaîne, un booléen seuls — lève également.

## Pièges d'examen

**Les paramètres de route s'apparient par leur nom, jamais par leur position.**
Réordonner les arguments du contrôleur ne casse rien ; en renommer un casse
tout.

**La requête ne s'injecte pas dans un service.** Typer un argument suffit dans
un contrôleur, mais un service est construit une fois pour toutes : il reçoit
`RequestStack`.

**`getPayload()` ne fusionne pas formulaire et JSON.** Si le sac des paramètres
de formulaire n'est pas vide, elle rend celui-là et ne regarde même pas le corps
brut.

**Un corps JSON invalide ne donne pas un sac vide** : il lève une exception. Le
sac vide est réservé au cas où le corps est **vide**.

**`getPayload()` rend un clone.** Écrire dans l'objet retourné ne change rien à
la requête.

## Tips d'examen

**Deux questions pour choisir où lire.** La donnée est-elle dans l'URL après le
point d'interrogation ? → `query`. Vient-elle du chemin de la route ? →
`attributes`, et donc d'un argument nommé. Vient-elle du corps ? →
`getPayload()`, en gardant en tête sa priorité au formulaire.

## Points clés

- Typer un argument `Request` suffit ; dans un service, injecter `RequestStack`.
- Les paramètres de route passent par `attributes` puis par le **nom** de
  l'argument.
- `_route` et `_controller` vivent dans `attributes`.
- `getPayload()` privilégie le formulaire, rend un **clone**, et **lève** sur un
  JSON invalide ou qui ne décode pas en tableau.

## Sources officielles

- [Controller, « The Request Object as a Controller Argument »](https://github.com/symfony/symfony-docs/blob/8.0/controller.rst)
- [`Request::getPayload()`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/Request.php)
- [`RequestStack`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpFoundation/RequestStack.php)
