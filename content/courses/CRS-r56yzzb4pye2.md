---
id: CRS-r56yzzb4pye2
official_item: OIT-dp7w7s85wxjg
title: "Global variables"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-11-03"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/templates.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/templates.rst"
    anchor: "the-app-global-variable"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bridge/Twig/AppVariable.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/AppVariable.php"
    symbol_or_lines: "getUser, getSession, getFlashes, getCurrent_route"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/TwigBundle/Resources/config/twig.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/TwigBundle/Resources/config/twig.php"
    symbol_or_lines: "twig.app_variable"
    repository: "symfony/symfony"
    branch: "8.0"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/templates.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/templates.rst"
    symbol_or_lines: "Global Variables"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    verified_at: "2026-11-03"
  - url: "https://raw.githubusercontent.com/twigphp/Twig/v3.22.0/doc/advanced.rst"
    readable_url: "https://github.com/twigphp/Twig/blob/v3.22.0/doc/advanced.rst"
    symbol_or_lines: "Globals"
    repository: "twigphp/Twig"
    branch: "v3.22.0"
    verified_at: "2026-11-03"
outcome_sections:
  OUT-r9kjw1fha5yx: "La variable `app`"
  OUT-5z9qxkyjy0yc: "La variable `app`"
  OUT-thnmr95cr5f4: "Déclarer ses propres globales"
  OUT-zn59vbr2pzhs: "Le piège"
---

## Objectif

Connaître les variables disponibles dans **tous** les templates sans qu'un
controller les passe, et savoir en déclarer.

## Avant la règle : le problème à résoudre

Le menu affiche le nom du user connecté, la page met en évidence la route
courante, le layout montre les flash messages. Passer ces valeurs depuis
chaque controller serait fastidieux et fragile. Twig et Symfony fournissent des
**variables globales**, visibles partout ; on peut aussi en déclarer.

Trois questions : **ce que Twig fournit**, **ce que Symfony ajoute** avec
`app`, et **comment déclarer les siennes** sans surprise. Tous les faits de
cette page ont été exécutés avec TwigBundle 8.0.15 et Twig 3.22.2.

## Le fil rouge : le layout du site

```twig
{# templates/base.html.twig #}
<nav>
    <a href="{{ path('home') }}" class="{{ app.current_route == 'home' ? 'active' }}">Accueil</a>
    {% if app.user %}Bonjour {{ app.user.userIdentifier }}{% endif %}
</nav>
{% for message in app.flashes('notice') %}
    <p class="notice">{{ message }}</p>
{% endfor %}
<footer>{{ site_name }} — {{ app.environment }}</footer>
```

```yaml
# config/packages/twig.yaml
twig:
    globals:
        site_name: 'Ma boutique'
```

Aucun controller ne passe ces variables : `app` vient de Symfony, `site_name`
de la configuration.

## Le cycle complet en un schéma

D'où vient une variable lue dans un template, rejoué :

```mermaid
---
title: D'où vient une variable lue dans un template
---
flowchart TD
  accTitle: L'origine d'une variable lue par un template Twig dans Symfony
  accDescr: Une variable passée au rendu par le controller est prioritaire. À défaut, une globale répond, déclarée par twig.globals, par une extension Twig via GlobalsInterface, ou par Twig lui-même pour _self, _context et _charset ; TwigBundle déclare la globale app, un object AppVariable. Une macro ne voit que ses arguments et les globales, pas les variables du template qui l'appelle.
  NAME["{{ nom }}"] --> CTX{"passée au rendu ?"}
  CTX -->|"oui"| C(["la valeur du contexte, même si une globale porte ce nom"])
  CTX -->|"non"| GLOB{"globale ?"}
  GLOB -->|"Twig"| T(["_self, _context, _charset"])
  GLOB -->|"TwigBundle"| A(["app : AppVariable"])
  GLOB -->|"twig.globals, GlobalsInterface"| G(["vos globales"])
  GLOB -->|"non"| U(["inconnue : null, ou erreur si strict_variables"])
```

## Les trois globales de Twig lui-même

Twig 3.22 fournit toujours trois variables, avant même Symfony :

| Variable | Contenu |
|---|---|
| `_self` | le nom du template courant |
| `_context` | le contexte courant, c'est-à-dire toutes les variables visibles |
| `_charset` | le jeu de caractères courant |

Rejoué : `_self` vaut `glob/a.html.twig`, `_charset` `UTF-8`, et
`_context|keys` liste les variables passées **et** les globales — `app`,
`site` — ce qui permet de tout transmettre à une macro.

## La variable `app`

TwigBundle expose une variable `app` dans chaque template — un object
`AppVariable` du bridge Twig. C'est la voie normale d'accès à l'état de la
Request depuis un template.

| Property | Contenu |
|---|---|
| `app.user` | le user courant, ou `null` s'il n'est pas authentifié |
| `app.request` | l'object `Request` courant |
| `app.session` | la session, ou `null` si la Request n'en a pas |
| `app.flashes` | les flash messages ; `app.flashes('notice')` filtre par type |
| `app.environment` | `dev`, `prod`… |
| `app.debug` | le mode debug |
| `app.token` | le token de sécurité |
| `app.current_route` | le nom de la route courante |
| `app.current_route_parameters` | ses parameters |
| `app.locale` | la locale courante |
| `app.enabled_locales` | les locales activées |

Trois détails lus dans `AppVariable` (8.0) :

- `app.current_route` lit l'attribute `_route` de la Request courante, et
  `app.current_route_parameters` l'attribute `_route_params` — un array vide
  s'il manque ;
- `app.flashes` accepte aussi une liste de types et rend alors un array
  `type => messages` ; sans session compatible, il rend un array vide ;
- une property dont le service manque **lève une `RuntimeException`** :
  `app.user` et `app.token` sans le component Security installé, par exemple —
  « The "app.user" variable is not available. » Seul `app.flashes` retombe sur
  un array vide.

`app.session` peut valoir `null`. Et lire son contenu — `app.flashes`, par
exemple — démarre la session, avec les conséquences que cela a sur la
cachabilité de la page.

**Rejoué :**

| Situation | Résultat |
|---|---|
| Request avec `_route: product_show`, `_route_params: {id: 7}` | `app.current_route` = `product_show`, parameters `{"id":7}` |
| `app.flashes(['a', 'b'])` | `{"a":["A1"],"b":["B1"]}` |
| aucun Request — une command qui rend un email | `app.request` et `app.session` valent `null`, `app.flashes` rend `[]` |
| `{{ app.user }}` sans SecurityBundle | `Twig\Error\RuntimeError`, qui emballe la `RuntimeException` d'`AppVariable` |

La dernière ligne compte pour qui attrape l'erreur : `Twig\Error\RuntimeError`
n'est **pas** une `\RuntimeException`, et un `catch (\RuntimeException)` autour
du rendu la laisse passer.

## Déclarer ses propres globales

```yaml
# config/packages/twig.yaml
twig:
    globals:
        ga_tracking: 'UA-xxxxx-x'
        mailer: '@app.mailer'
```

La valeur devient `{{ ga_tracking }}` partout, sans qu'aucun controller ne la
passe ; une valeur qui commence par `@` désigne un service. Hors configuration,
une extension Twig peut en déclarer par `GlobalsInterface::getGlobals()`, et
Twig seul par `Environment::addGlobal()`.

La documentation de Twig le précise : les globales sont disponibles dans tous
les templates **et dans les macros**, qui ne voient pourtant pas les variables du
template qui les appelle. C'est adapté à une constant d'application ; ce n'est
pas un substitut au passage de variables, qui reste la voie normale pour les
données d'une page.

**Rejoué, la priority :** une globale `title: GLOBAL`, et un rendu qui passe
`title: CONTEXT` — le template affiche `CONTEXT`. Une variable passée au rendu
masque la globale du même nom. Et une macro affiche la globale `site`, mais pas
la variable `local` du template appelant.

## Le piège

`app.flashes` **consomme** les messages, comme toute lecture du sac de flashs.
Les afficher deux fois dans une page en fait disparaître la moitié ; la lecture
non consommatrice est `app.session.flashbag.peekAll()`.

Rejoué : `app.flashes('notice')` rend `saved` la première fois, rien la seconde ;
après lecture, `peekAll()` rend un array vide.

## Concrètement dans ton application

- Route courante : `app.current_route`, pas `app.request.attributes.get('_route')`.
- User : tester `app.user` avant de lire ses properties.
- Flashes : les lire **une** fois, dans le layout ; `peekAll()` pour un panneau
  de debug.
- Un template rendu hors Request — email, PDF : ne pas compter sur
  `app.request`.
- Une constant d'application : `twig.globals` ; un service : `'@id'`.
- Un nom de globale qui ne heurte aucune variable de page.

## À comprendre

- **Pourquoi `app` plutôt que des variables séparées ?** Un seul object donne
  un accès lazy : la session n'est lue que si un template la demande.
- **Pourquoi le contexte masque-t-il la globale ?** Le controller sait mieux
  que la configuration ce qu'une page doit afficher.
- **Pourquoi une exception plutôt que `null` sans Security ?** `null` veut déjà
  dire « personne de connecté » ; l'absence du component est une autre
  situation, qu'il faut voir.

## À mémoriser

- Twig : **`_self`**, **`_context`**, **`_charset`** ; Symfony : **`app`**.
- `app` : **onze** properties — user, request, session, flashes, environment,
  debug, token, current_route, current_route_parameters, locale,
  enabled_locales.
- **`app.flashes`** consomme ; **`peekAll()`** ne consomme pas.
- Service absent : **exception** (`RuntimeError` dans le template), sauf
  `app.flashes`.
- **`twig.globals`**, `@` pour un service ; visibles **dans les macros**.
- Une variable passée au rendu **masque** une globale du même nom.
- Moyen mnémotechnique : **« Twig en souligné, Symfony dans app, le reste en
  globals »**.

## Pièges d'examen

**La session vue depuis un template peut être `null`** — et lire son contenu la
démarre, avec l'effet que cela a sur la cachabilité de la page.

**Le user courant vaut `null` quand personne n'est authentifié.** Lire
`app.user.email` lève alors une `RuntimeError` — « Impossible to access an
attribute ("email") on a null variable » — en mode strict, actif en `dev` par
défaut, et rend `null` sinon. Et `app.user` lève une exception si le component
Security n'est pas installé.

**`_self`, `_context`, `_charset` existent sans Symfony.** Ce sont les globales
de Twig ; `app` est celle de Symfony.

**La forme courte de la route courante existe.** Traverser les attributes de la
Request pour l'obtenir fonctionne, mais c'est le path long de la même chose.

## Application : trois scénarios

**Scénario 1 — Le titre qui ne change pas.** Une globale `title` est déclarée
pour le site ; un controller passe `title` pour une page, et c'est bien le sien
qui s'affiche — puis un autre controller oublie de le passer et affiche le titre
du site. *Solution.* C'est le comportement attendu : le contexte masque la
globale. Nommer la globale autrement, `site_title`, évite la confusion.

**Scénario 2 — L'email qui plante.** Une command rend un email qui affiche
`{{ app.request.host }}` : erreur, `app.request` vaut `null`. *Solution.* Hors
Request, passer le host en variable, ou le lire dans la configuration.

**Scénario 3 — Le handler d'erreur muet.** Un `catch (\RuntimeException)`
autour du rendu devait capter l'absence de Security ; l'erreur passe au travers.
*Solution.* Twig la remonte en `Twig\Error\RuntimeError` : attraper celle-ci,
ou `Twig\Error\Error`.

## QCM d'entraînement

Cinq questions sur les globales. Choisis avant d'ouvrir la correction.

**Question 1.** `twig.globals` déclare `title: 'Site'`. Un controller rend un
template avec `['title' => 'Page']`. Que sort `{{ title }}` ?

- **A.** `Site` : la configuration prime
- **B.** `Page`
- **C.** Une erreur : nom en conflit
- **D.** `SitePage`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Rejoué : une variable passée au rendu masque la globale
du même nom.

A inverse la priority. C et D sont faux.

</details>

**Question 2.** Que liste `{{ _context|keys|join(',') }}` dans un template rendu
avec `['title' => …]` et une globale `site` ?

- **A.** `title` seulement : les globales n'y sont pas
- **B.** `site` seulement
- **C.** Rien : `_context` est réservé aux macros
- **D.** `title`, `app` et `site`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Rejoué : `_context` contient les variables passées et les
globales, `app` comprise.

A et B en oublient une partie. C est faux.

</details>

**Question 3.** Une command console rend un email Twig. Que vaut
`app.request` ?

- **A.** `null`
- **B.** Une Request vide créée pour l'occasion
- **C.** Une `RuntimeError` : pas de Request
- **D.** La dernière Request traitée par le serveur

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Rejoué : sans Request sur la pile, `app.request` et
`app.session` valent `null`, et `app.flashes` rend `[]`.

B, C et D sont faux.

</details>

**Question 4.** Sans SecurityBundle, un controller entoure le rendu de
`{{ app.user }}` d'un `catch (\RuntimeException $e)`. Le catch s'exécute-t-il ?

- **A.** Oui : `AppVariable` lève une `RuntimeException`
- **B.** Oui, mais seulement en `prod`
- **C.** Non : `app.user` vaut `null`
- **D.** Non : Twig remonte une `RuntimeError`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Rejoué : `Twig\Error\RuntimeError`, qui n'étend pas
`\RuntimeException` ; la `RuntimeException` d'`AppVariable` est dans
`getPrevious()`.

A oublie l'emballage. B est faux. C confond avec un visiteur anonyme.

</details>

**Question 5.** Une macro est appelée depuis un template qui a une variable
`local` ; une globale `site` existe. Que voit la macro ?

- **A.** `local` et `site`
- **B.** `site` seulement
- **C.** `local` seulement
- **D.** Ni l'un ni l'autre

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Rejoué. La documentation de Twig : les globales sont
visibles dans les macros, pas les variables du template appelant.

A, C et D sont faux.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- Twig fournit **`_self`**, **`_context`**, **`_charset`**.
- Symfony fournit **`app`** : onze properties, dont **`current_route`**.
- **`app.flashes`** consomme les messages.
- Sans Request : **`app.request`** et **`app.session`** à `null`.
- Service absent : **exception**, en `RuntimeError` dans le template.
- **`twig.globals`** déclare ; **`@`** pour un service ; visibles dans les macros.
- Le **contexte** masque une **globale** de même nom.

**En une phrase, pour tout reconstruire :**

```text
contexte > globales (Twig : _self _context _charset ; TwigBundle : app ; twig.globals / GlobalsInterface) ;
app : user request session flashes(consomme) environment debug token current_route(+_parameters) locale enabled_locales
```

## Sources officielles

- [Symfony Templates, « The App Global Variable »](https://github.com/symfony/symfony-docs/blob/8.0/templates.rst)
- [Twig Bridge, `AppVariable`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bridge/Twig/AppVariable.php)
- [Twig 3.22, *Global Variables*](https://github.com/twigphp/Twig/blob/v3.22.0/doc/templates.rst) et [*Globals*](https://github.com/twigphp/Twig/blob/v3.22.0/doc/advanced.rst)
