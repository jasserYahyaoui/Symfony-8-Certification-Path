---
id: CRS-b4g5s2asb9ye
official_item: OIT-83sac57rw0xn
title: "Tags"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-28"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/tags.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container/tags.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: "tagged_iterator, AutoconfigureTag, AsTaggedItem, AutowireIterator, AutowireLocator, priority, index_by"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Compiler/PriorityTaggedServiceTrait.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/PriorityTaggedServiceTrait.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "findAndSortTaggedServices() — keys only with an index attribute or for a locator"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Argument/TaggedIteratorArgument.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Argument/TaggedIteratorArgument.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "__construct() — getDefault<Attr>Name / getDefault<Attr>Priority derived from the index attribute"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Compiler/UnusedTagsPass.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Compiler/UnusedTagsPass.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "Tag %s was defined on service(s) %s, but was never used."
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "registerForAutoconfiguration(EventSubscriberInterface::class)"
    verified_at: "2026-10-28"
outcome_sections:
  OUT-deqvr7c2bvsf: "Ce qu'un tag fait : un marqueur, sans effet propre"
  OUT-enhmt34n32vh: "Recevoir les services tagués : un iterable lazy"
  OUT-e2wn6rtf32vb: "Ordonner et nommer : `priority` et `index`"
  OUT-ejv3jffbt4xd: "Poser les tags sans configuration"
---

## Objectif

Marquer des services pour qu'un autre les retrouve sans les connaître par leur
nom, les recevoir sous forme d'itérable lazy, les ordonner par priority, les
indexer par une key, et poser les tags automatiquement depuis une interface.

## Avant la règle : le problème à résoudre

Ton application envoie des notifications par SMS, par email, par Slack. Un
`NotificationChain` doit appeler tous les canaux. La version naïve :

```php
class NotificationChain
{
    public function __construct(
        private SmsHandler $sms,
        private EmailHandler $email,
        private SlackHandler $slack,
    ) {
    }
}
```

Chaque nouveau canal oblige à modifier `NotificationChain`. Et si le canal vient
d'un autre bundle, `NotificationChain` ne peut même pas le connaître. Il faudrait que
chaque canal puisse **se déclarer** comme « canal de notification », et que la
`NotificationChain` reçoive **tous ceux qui se sont déclarés**, quels qu'ils soient.

C'est exactement ce que fait un **tag**. C'est aussi le mécanisme qui permet au
framework de trouver tes listeners, tes voters, tes commands ou tes extensions
Twig : `kernel.event_listener`, `controller.argument_value_resolver`,
`form.type_extension`, `twig.extension` sont des tags.

## Le fil rouge : des handlers de notification

```php
interface HandlerInterface
{
    public function notify(string $message): void;
}

class SmsHandler implements HandlerInterface { /* … */ }
class EmailHandler implements HandlerInterface { /* … */ }
class FaxHandler implements HandlerInterface { /* … */ }
```

Et un `HandlerChain` qui doit recevoir **tous** les handlers, sans les nommer :

```php
class HandlerChain
{
    public function __construct(private iterable $handlers) {}

    public function notifyAll(string $message): void
    {
        foreach ($this->handlers as $handler) {
            $handler->notify($message);
        }
    }
}
```

## Le cycle complet en un schéma

```text
1. MARQUER     chaque handler porte le tag app.handler
               (YAML tags: […], ou #[AutoconfigureTag] sur l'interface)
        │
        ▼
2. LIRE        quelque chose collecte le tag :
               un argument #[AutowireIterator('app.handler')] / !tagged_iterator,
               un locator #[AutowireLocator], ou un compiler pass
               ── rien ne le lit ? aucun effet, aucune erreur (une ligne de log en debug)
        │
        ▼
3. ORDONNER    priority : la plus HAUTE passe en premier
   INDEXER     index : une key, seulement avec indexAttribute / index_by, ou dans un locator
        │
        ▼
4. EXÉCUTER    HandlerChain est construit : AUCUN handler ne l'est encore (lazy)
               foreach → chaque handler est construit au moment où on l'atteint
```

## Ce qu'un tag fait : un marqueur, sans effet propre

Un tag est un **marqueur posé sur une definition**. Il ne fait rien par lui-même :
quelque chose doit le lire — un compiler pass, ou l'injection d'un iterator de
services tagués.

```yaml
services:
    App\Handler\SmsHandler:
        tags: ['app.handler']

    # avec des attributes
    App\Handler\EmailHandler:
        tags:
            - { name: 'app.handler', priority: 10 }
```

Un tag que rien ne lit ne lève **aucune erreur**. En mode debug, FrameworkBundle
le signale seulement dans le journal de compilation (`UnusedTagsPass`). Exécuté :
« Tag "app.handlr" was defined on service(s) …, but was never used. Did you mean
"app.handler"? ». Le service doit avoir survécu à la compilation pour que ce
message apparaisse.

Conséquence pratique : une **faute de frappe** dans un nom de tag ne casse rien
visiblement. Le handler n'est simplement jamais appelé.

## Recevoir les services tagués : un iterable lazy

Deux écritures équivalentes :

```php
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class HandlerChain
{
    public function __construct(
        #[AutowireIterator('app.handler')] private iterable $handlers,
    ) {
    }
}
```

```yaml
    App\Handler\HandlerChain:
        arguments: [!tagged_iterator app.handler]
```

L'argument reçoit un itérable **lazy** : les services tagués ne sont
instanciés qu'à l'itération. Exécuté avec DependencyInjection 8.0.15 : après
construction de `HandlerChain`, aucun handler n'est construit. C'est un
generator réembobinable : chaque `foreach` reparcourt les services, construits
à la demande.

L'option `exclude` en retire certains : exécuté, `exclude: [FaxHandler::class]`
rend les autres handlers.

Variante : **`#[AutowireLocator('app.handler')]`** injecte un **locator**, un
petit container qui ne contient que ces services, interrogé par key
(`$locator->get('sms')`). Utile quand on n'en appelle qu'un, choisi à
l'exécution, plutôt que tous.

| Besoin | Injection |
|---|---|
| appeler **tous** les services tagués, dans l'ordre | `#[AutowireIterator]` / `!tagged_iterator` |
| en appeler **un**, choisi par sa key | `#[AutowireLocator]` / `!tagged_locator` |

## Ordonner et nommer : `priority` et `index`

Un tag porte des attributes, dont deux sont conventionnels :

- **`priority`** — l'ordre dans l'iterator ; **la plus haute passe en premier** ;
- **`index`** — la key sous laquelle le service apparaît.

`#[AsTaggedItem]` les pose depuis la class :

```php
#[AsTaggedItem(index: 'sms', priority: 10)]
class SmsHandler implements HandlerInterface {}
```

Exécuté : trois handlers de priorities 0, 10 et -5 sont parcourus dans l'ordre
10, 0, -5.

### La key ne sert que si on la demande

Exécuté, avec `SmsHandler` indexé `sms` :

| Injection | Keys obtenues |
|---|---|
| `#[AutowireIterator('app.handler')]` | `0, 1, 2, 3` : l'index est ignoré |
| `#[AutowireIterator('app.handler', indexAttribute: 'key')]` | `sms`, puis les noms de class |
| `#[AutowireLocator('app.handler')]` | `sms`, puis les noms de class |

Un iterator simple reste une **liste** ; c'est `indexAttribute` — `index_by` en
YAML — ou un locator qui en fait une table de correspondance.

### Les default values fournies par la class

Une method statique peut fournir les default values. `getDefaultPriority()`
donne la priority : exécuté, 5 place le service juste après `sms`. Pour la key,
le nom de la method **dérive de l'attribute d'index** : avec `index_by: key`,
c'est `getDefaultKeyName()`, sauf si `default_index_method` en nomme une autre.
Exécuté : une method `getDefaultIndexName()` n'est appelée que si on la nomme.
La priority suit la même règle : sous `index_by: key`, la method attendue
devient `getDefaultKeyPriority()` (`TaggedIteratorArgument`), et le
`getDefaultPriority()` du service est ignoré — exécuté, il perd sa place.

## Poser les tags sans configuration

`#[AutoconfigureTag]` sur une **interface** tague toutes les classes qui
l'implémentent. L'attribute est répétable.

```php
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.handler')]
interface HandlerInterface {}
```

Avec `autoconfigure: true` (le défaut d'un projet), toute nouvelle class qui
implémente `HandlerInterface` devient un handler, sans une ligne de YAML.

Le framework obtient le même effet pour ses propres interfaces par un autre
moyen : `FrameworkExtension` appelle
`registerForAutoconfiguration(EventSubscriberInterface::class)`. Relu en 8.0 :
`EventSubscriberInterface` ne porte pas `#[AutoconfigureTag]`.

## Concrètement dans ton application

- Un ensemble extensible de services (canaux, exporteurs, règles) : une
  **interface** avec `#[AutoconfigureTag('app.xxx')]`, et un consommateur avec
  `#[AutowireIterator('app.xxx')]`. Ajouter un service, c'est créer une class.
- Ordre d'exécution : `priority` (la plus haute d'abord), avec `#[AsTaggedItem]`.
- Un seul service choisi par un nom : `#[AutowireLocator]` et `index`.
- Un handler jamais appelé : cherche une faute de frappe dans le tag, ou
  `autoconfigure` désactivé. En debug, le journal de compilation le dit.
- Pour inspecter :

```bash
php bin/console debug:container --tags
php bin/console debug:container --tag=app.handler
```

## À comprendre

- **Pourquoi un tag ne fait-il rien seul ?** Parce que c'est un simple marqueur, pas
  un comportement : le sens vient de celui qui la lit (un compiler pass du
  framework, ou ton iterator).
- **Pourquoi lazy ?** Pour que construire `HandlerChain` ne construise pas tous les
  handlers : on ne paie que ceux qu'on atteint.
- **Pourquoi la key ne sert-elle pas toujours ?** Parce qu'un iterator est une
  liste ordonnée ; la key n'a de sens que pour accéder à un élément précis.
- **Pourquoi `#[AutoconfigureTag]` sur l'interface ?** Pour que l'usage soit
  déclaré une fois, là où il est défini, et non dans chaque class.

## À mémoriser

- Tag = marqueur sur une definition ; **sans lecteur, aucun effet, aucune
  erreur** (log `UnusedTagsPass` en debug).
- Recevoir : `#[AutowireIterator('tag')]` / `!tagged_iterator tag` (tous, lazy) ;
  `#[AutowireLocator('tag')]` / `!tagged_locator` (un, par key).
- `exclude` retire des services de l'iterator.
- **`priority` haute = premier.** `index` : key seulement avec
  `indexAttribute` / `index_by`, ou dans un locator.
- `#[AsTaggedItem(index:, priority:)]` sur la class ; `#[AutoconfigureTag]` sur
  l'interface (répétable).
- Sous `index_by: key` : `getDefaultKeyName()` et `getDefaultKeyPriority()`.
- Moyen mnémotechnique : **« Marquer, Lire, Trier »** — sans le L, rien ne se
  passe.

## Pièges d'examen

**Un tag n'a d'effet que si quelque chose le lit.** Taguer un service qu'aucune
passe ne collecte ne produit rien — aucune erreur, au mieux une ligne de journal
en debug.

**`priority` haute = appelé en premier**, comme pour les event listeners.

**L'iterator est lazy** : les services ne sont pas construits tant qu'on
n'itère pas.

**Un iterator sans `index_by` a des keys entières**, même si les services
déclarent un `index`.

**Une faute de frappe dans un nom de tag ne lève aucune erreur.** Le service
n'est simplement jamais collecté ; seul le journal de compilation, en debug, le
signale.

## Application : trois scénarios

**Scénario 1 — Ajouter un canal sans toucher `HandlerChain`.** *Solution.*
`#[AutoconfigureTag('app.handler')]` sur `HandlerInterface`,
`#[AutowireIterator('app.handler')]` dans `HandlerChain`. Un `PushHandler
implements HandlerInterface` est appelé dès qu'il existe.

**Scénario 2 — L'ordre compte.** Le SMS doit partir avant l'email.
*Solution.* `#[AsTaggedItem(priority: 10)]` sur `SmsHandler`, priority plus basse
(ou 0) sur `EmailHandler` : la plus haute passe en premier.

**Scénario 3 — Choisir un canal par son nom.** Le client choisit « sms » ou
« email ». *Solution.* `#[AsTaggedItem(index: 'sms')]` sur chaque handler et
`#[AutowireLocator('app.handler')]` dans le service : `$locator->get('sms')`.
Avec un simple `#[AutowireIterator]`, les keys seraient `0, 1, 2`.

## QCM d'entraînement

Six questions sur les tags. Engage-toi sur une lettre avant d'ouvrir la
correction.

**Question 1.** Un `ExportService` est tagué `app.exportr` (avec une faute de frappe), alors que le consommateur attend `app.exporter`. Que se passe-t-il ?

- **A.** La compilation échoue : « unknown tag "app.exportr" »
- **B.** L'exporteur est appelé quand même, le tag le plus proche étant retenu
- **C.** Aucune erreur ; l'exporteur n'est simplement jamais collecté
- **D.** Le service est retiré du container avec un avertissement visible à l'écran

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Un tag n'a d'effet que si quelque chose le lit ; un nom
que rien ne lit ne lève rien. En debug, `UnusedTagsPass` écrit « Tag … was
defined on service(s) …, but was never used. Did you mean … ? » dans le journal
de compilation (exécuté).

A invente une validation des noms de tags. B invente une correction
automatique : la suggestion n'existe que dans le message de log. D invente un
avertissement visible.

</details>

**Question 2.** Trois services portent le tag `app.rule` avec les priorities `0`, `20` et `-10`. Dans quel ordre un `#[AutowireIterator('app.rule')]` les parcourt-il ?

- **A.** `20`, `0`, `-10`
- **B.** `-10`, `0`, `20`
- **C.** Dans l'ordre de déclaration des services, la priority ne jouant que pour les listeners
- **D.** Dans l'ordre alphabétique de leurs noms de class

<details>
<summary>Correction</summary>

**Bonne réponse : A.** La priority la plus haute passe en premier (exécuté :
10, 0, -5 parcourus dans cet ordre).

B est le piège : il inverse la règle. C invente une restriction aux listeners :
la même convention vaut pour tous les iterators tagués. D invente un tri par
nom.

</details>

**Question 3.** Un `ReportBuilder` reçoit `#[AutowireIterator('app.section')] iterable $sections`, et cinq services portent ce tag. Juste après la construction de `ReportBuilder`, combien de sections ont été construites ?

- **A.** Cinq, toutes construites pour être injectées
- **B.** Une seule, la première par priority
- **C.** Aucune : chaque section n'est construite qu'à l'itération
- **D.** Cela dépend de `shared` : cinq si elles sont partagées, aucune sinon

<details>
<summary>Correction</summary>

**Bonne réponse : C.** L'iterator est un generator réembobinable ; exécuté,
aucun service tagué n'est construit avant le `foreach`.

A est l'intuition fausse. B invente une construction partielle. D mélange le
partage d'instances et le moment de construction.

</details>

**Question 4.** `SmsHandler` porte `#[AsTaggedItem(index: 'sms')]`. `HandlerChain` reçoit `#[AutowireIterator('app.handler')] iterable $handlers`. Quelles keys obtient-elle en itérant ?

- **A.** `sms`, puis les noms de class des autres handlers
- **B.** `0`, `1`, `2`… : l'index est ignoré
- **C.** Les ids des services, quels que soient les attributes
- **D.** Une erreur, car un `index` impose un locator

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Exécuté : un iterator simple reste une liste, même si les
services déclarent un `index`. Il faut `indexAttribute: 'key'` (ou `index_by`
en YAML), ou un `#[AutowireLocator]`, pour obtenir `sms`.

A est le piège : c'est le résultat avec `indexAttribute` ou un locator, pas avec
l'iterator simple. C invente un comportement. D invente une obligation.

</details>

**Question 5.** Toute future class qui implémente `ExporterInterface` doit être collectée, sans configuration YAML. Où poser quoi ?

- **A.** `#[AsTaggedItem('app.exporter')]` sur chaque class d'export
- **B.** `#[Autowire('app.exporter')]` sur le constructor du consommateur
- **C.** `tags: ['app.exporter']` sous `_defaults`, pour tous les services du file
- **D.** `#[AutoconfigureTag('app.exporter')]` sur `ExporterInterface`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Sur une interface, l'attribute tague toutes les classes
qui l'implémentent (avec `autoconfigure` actif), et il est répétable.

A demande de répéter sur chaque class, et `#[AsTaggedItem]` règle `index` et
`priority`, pas le nom du tag. B injecte une valeur, il ne tague rien. C
taguerait **tous** les services du file, exporteurs ou non.

</details>

**Question 6.** L'application doit appeler **un seul** handler, choisi à l'exécution par un code (`sms`, `email`). Quelle injection convient ?

- **A.** `#[AutowireLocator('app.handler')]`, puis `$locator->get($code)`
- **B.** `#[AutowireIterator('app.handler')]`, puis un `foreach` qui compare chaque key au code
- **C.** Le container entier, puis `$container->get('app.handler.'.$code)`
- **D.** Un argument par handler, et un `match` sur le code

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Un locator ne contient que les services du tag, indexés
par leur `index`, et ne construit que celui qu'on demande.

B est le piège : sans `indexAttribute`, les keys sont `0, 1, 2…`, et parcourir
la liste construit chaque handler atteint. C revient à la récupération sur le
container entier, avec des services privés inaccessibles. D est la version
naïve que les tags évitent.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- Un tag est un **marqueur** ; **sans lecteur, rien** ne se passe, sans erreur
  (log `UnusedTagsPass` en debug).
- `#[AutowireIterator]` / `!tagged_iterator` : **tous**, **lazy** (rien n'est
  construit avant l'itération) ; `exclude` en retire.
- `#[AutowireLocator]` / `!tagged_locator` : **un**, par key.
- **`priority` haute = premier.**
- `index` ne donne une key qu'avec `indexAttribute` / `index_by` ou dans un
  locator ; sinon `0, 1, 2…`.
- `#[AutoconfigureTag]` sur l'**interface** ; `#[AsTaggedItem(index:, priority:)]`
  sur la class.

**En une phrase, pour tout reconstruire :**

```text
MARQUER (tag, #[AutoconfigureTag] sur l'interface) → LIRE (iterator lazy | locator | pass)
  → TRIER (priority haute d'abord) → INDEXER (index, seulement si demandé)
```

## Sources officielles

- [How to Work with Service Tags](https://github.com/symfony/symfony-docs/blob/8.0/service_container/tags.rst)
- [DependencyInjection 8.0, `PriorityTaggedServiceTrait`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/PriorityTaggedServiceTrait.php)
- [DependencyInjection 8.0, `Argument\TaggedIteratorArgument`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Argument/TaggedIteratorArgument.php)
- [FrameworkBundle 8.0, `UnusedTagsPass`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Compiler/UnusedTagsPass.php)
- [FrameworkBundle 8.0, `FrameworkExtension`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php)
