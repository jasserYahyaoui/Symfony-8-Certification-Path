---
id: CRS-kqq96z5y9r2f
official_item: OIT-egy2wn3z7gb7
title: "Configuration (YAML and PHP attributes)"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/routing.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/routing.rst"
    anchor: "creating-routes"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Attribute/Route.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Attribute/Route.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "Route::__construct"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Loader/YamlFileLoader.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Loader/YamlFileLoader.php"
    symbol_or_lines: "AVAILABLE_KEYS, validate, validateAlias, loadContent"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/RouteCollection.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/RouteCollection.php"
    symbol_or_lines: "add, all"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Routing/Loader/AttributeClassLoader.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Routing/Loader/AttributeClassLoader.php"
    symbol_or_lines: "priority"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-09-24"
outcome_sections:
  OUT-f2tn472mwm2p: "Les deux formes"
  OUT-jjd0fkj8bsy5: "Le piège : l'ordre d'évaluation"
  OUT-tkq7ea4zgfd1: "Le piège : l'ordre d'évaluation"
  OUT-zmrs1nv9vh0d: "Le namespace de l'attribute"
---

## Objectif

Déclarer une route en YAML et en attribute PHP, savoir choisir entre les deux,
et maîtriser la conséquence la plus piégeuse de ce choix : **l'ordre
d'évaluation**.

## L'essentiel

Une route se déclare en attribute `#[Route]` ou en YAML : les deux formes sont
équivalentes, mais **pas leurs options**. `priority` n'existe qu'en attribute ;
une key YAML inconnue fait **échouer** le chargement. Et la règle qui pilote
tout : les routes sont essayées **dans l'ordre**, et la **première** qui
correspond gagne — jamais la plus spécifique.

## Le schéma

```text
attribute   use Symfony\Component\Routing\Attribute\Route   (Annotation\Route : supprimé)
            16 arguments : path, name, requirements, options, defaults, host, methods, schemes,
            condition, priority, locale, format, utf8, stateless, env, alias
YAML        liste AVAILABLE_KEYS : pas de priority · env → block when@prod: · alias → entrée alias seul
            key inconnue, controller + defaults._controller, stateless + defaults._stateless → exception
ordre       première route qui correspond = gagnante
            priority : plus haute d'abord · égalité → ordre de déclaration · null → priority de la class, sinon 0
```

## L'exemple : les deux formes

```php
use Symfony\Component\Routing\Attribute\Route;

class BlogController
{
    #[Route('/blog/{page}', name: 'blog_list', requirements: ['page' => '[0-9]+'], methods: ['GET', 'HEAD'])]
    public function list(int $page = 1): Response {}
}
```

```yaml
blog_list:
    path: /blog/{page}
    controller: App\Controller\BlogController::list
    requirements:
        page: '[0-9]+'
    defaults:
        page: 1
    methods: [GET, HEAD]
```

## Les deux formes

Le nom de la route est la key YAML, ou l'argument `name:` de l'attribute. Le
controller est explicite en YAML ; en attribute, il est déduit de la method qui
porte l'attribute. `path` accepte un **array** indexé par locale : l'attribute
produit alors une route par locale, `blog_list.fr`, `blog_list.en`…

Les jeux d'options diffèrent. L'attribute accepte seize arguments ; le loader
YAML a sa propre liste, `AVAILABLE_KEYS` :

| Besoin | Attribute | YAML |
|---|---|---|
| réordonner | `priority` | **absent** — on déplace la route dans le file |
| restreindre à un environment | `env` | un block `when@prod:` au premier niveau |
| déclarer un alias | `alias` | une entrée qui ne porte **que** `alias` (et `deprecated`) |
| désigner le controller | déduit de la method | `controller` |

Une key hors liste lève une `InvalidArgumentException` qui énumère les keys
admises ; `controller` avec `defaults._controller`, ou `stateless` avec
`defaults._stateless`, lèvent aussi.

## Le namespace de l'attribute

`Symfony\Component\Routing\Attribute\Route` est le seul correct.
`Symfony\Component\Routing\Annotation\Route`, hérité des annotations, a été
supprimé : l'importer échoue en Symfony 8.0.

## Le piège : l'ordre d'évaluation

**Symfony essaie les routes dans l'ordre, et la première qui correspond gagne.**
En YAML, cet ordre est celui du file : on déplace la route. En attributes, il
dépend de la découverte des classes et des methods, qu'on ne contrôle pas :
d'où `priority`.

```php
#[Route('/blog/list', name: 'blog_list', priority: 2)]
public function list(): Response {}

#[Route('/blog/{slug}', name: 'blog_show')]   // priority 0
public function show(string $slug): Response {}
```

La priority la plus élevée passe en premier ; à égalité, l'ordre de
déclaration départage. L'argument vaut `null` par défaut : la route hérite alors
de la priority d'un `#[Route]` posé sur la class, à défaut de `0`.

## Pièges d'examen

**La première route qui correspond gagne, et les suivantes ne sont jamais
essayées.** Une route générale placée avant une route spécifique masque
définitivement la seconde.

**La priority est une option d'attribute, pas de YAML.** En YAML on déplace une
ligne ; écrire la key fait échouer le chargement.

**Les deux formats n'ont pas le même jeu d'options.** L'environment et l'alias
existent des deux côtés, mais sous deux formes différentes.

**L'ancien namespace d'annotation n'existe plus.** Un import hérité de
l'époque Doctrine échoue en Symfony 8.0 — l'attribute vit désormais dans
`Attribute`.

## Application

- `/blog/list` capté par `/blog/{slug}` en attributes → `priority: 2` sur la
  route spécifique.
- Une route réservée au développement en YAML → la placer sous `when@dev:`.

## QCM d'entraînement

**Question 1.** La class porte `#[Route(priority: 5)]`, et sa method
`#[Route('/x', name: 'x')]` sans priority. Quelle priority a la route `x` ?

- **A.** 0
- **B.** 5
- **C.** `null`
- **D.** Une exception : priority ambiguë

<details>
<summary>Correction</summary>

**Bonne réponse : B.** L'argument `priority` vaut `null` sur la method : la
route hérite donc de celle posée au niveau de la class.

</details>

**Question 2.** Une route YAML porte `controller: App\C::a` et
`defaults: { _controller: App\C::b }`. Que se passe-t-il au chargement ?

- **A.** La key `controller` l'emporte, `defaults` étant ignoré
- **B.** `_controller` l'emporte
- **C.** Une `InvalidArgumentException`
- **D.** Les deux sont appelés

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Le loader refuse de recevoir le controller par deux
voies ; il signale le conflit au lieu de trancher en silence.

</details>

**Question 3.** Deux routes ont la même priority, `2`. Laquelle est essayée
d'abord ?

- **A.** Celle déclarée en premier
- **B.** Celle au path le plus long
- **C.** Celle dont le nom vient en premier dans l'alphabet
- **D.** Aucun ordre garanti

<details>
<summary>Correction</summary>

**Bonne réponse : A.** La collection trie par priority décroissante et
conserve, à priority égale, l'ordre dans lequel les routes ont été ajoutées.

</details>

**Question 4.** Comment restreindre une route YAML à l'environment `prod` ?

- **A.** La key `env: prod` sur la route
- **B.** Un block `when@prod:` au premier niveau
- **C.** `priority: prod`
- **D.** Impossible : seuls les attributes acceptent un environment

<details>
<summary>Correction</summary>

**Bonne réponse : B.** La key `env` n'existe pas dans la liste du loader YAML ;
on regroupe les routes sous un block conditionnel par environment.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- Import : **`Routing\Attribute\Route`** ; `Annotation` est supprimé.
- **Première** route qui correspond gagne, jamais la plus spécifique.
- `priority` : **attribute seulement** ; plus haute d'abord, égalité → ordre.
- `priority` `null` → celle de la **class**, sinon 0.
- YAML : `when@prod:` pour l'environment, entrée **alias seul** pour un alias.
- Key YAML inconnue ou doublon de controller → **`InvalidArgumentException`**.

Moyen mnémotechnique : **« Premier arrivé, premier servi »**.

## Sources officielles

- `routing.rst` (symfony-docs, branche 8.0, `eea05cb`)
- `Symfony\Component\Routing\Attribute\Route` (symfony, branche 8.0, `6f841c0`)
- `YamlFileLoader`, `RouteCollection`, `AttributeClassLoader` (symfony, branche 8.0)

