---
id: CRS-b4g5s2asb9ye
official_item: OIT-83sac57rw0xn
title: "Tags"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-29"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/tags.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container/tags.rst"
    branch: "8.0"
    symbol_or_lines: "tagged_iterator, AutoconfigureTag, AsTaggedItem, AutowireIterator"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Compiler/PriorityTaggedServiceTrait.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/PriorityTaggedServiceTrait.php"
    symbol_or_lines: "findAndSortTaggedServices() — keys only with an index attribute or for a locator"
    branch: "8.0"
    verified_at: "2026-09-29"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Argument/TaggedIteratorArgument.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Argument/TaggedIteratorArgument.php"
    symbol_or_lines: "__construct() — getDefault<Attr>Name / getDefault<Attr>Priority derived from the index attribute"
    branch: "8.0"
    verified_at: "2026-09-29"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Compiler/UnusedTagsPass.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Compiler/UnusedTagsPass.php"
    symbol_or_lines: "Tag %s was defined on service(s) %s, but was never used."
    branch: "8.0"
    verified_at: "2026-09-29"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/FrameworkExtension.php"
    symbol_or_lines: "registerForAutoconfiguration(EventSubscriberInterface::class)"
    branch: "8.0"
    verified_at: "2026-09-29"
---

## Objectif

Marquer un service pour qu'un autre le trouve, sans que personne ne les
connaisse nommément.

## Ce qu'un tag fait

Un tag est une **étiquette sur une définition**. Il ne fait rien par lui-même :
quelque chose doit le lire — une passe de compilation, ou l'injection d'un
itérateur de services tagués.

```yaml
services:
    App\Handler\SmsHandler:
        tags: ['app.handler']
```

C'est le mécanisme d'extension du framework tout entier :
`kernel.event_listener`, `controller.argument_value_resolver`,
`form.type_extension`, `twig.extension` sont des tags.

Un tag que rien ne lit ne lève **aucune erreur**. En mode debug, FrameworkBundle
le signale seulement dans le journal de compilation (`UnusedTagsPass`) : exécuté,
« Tag "app.handlr" was defined on service(s) …, but was never used. Did you mean
"app.handler"? ». Le service doit avoir survécu à la compilation pour que ce
message apparaisse.

## Les recevoir

```php
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class HandlerChain
{
    public function __construct(
        #[AutowireIterator('app.handler')] private iterable $handlers,
    ) {}
}
```

```yaml
    App\Handler\HandlerChain:
        arguments: [!tagged_iterator app.handler]
```

L'argument reçoit un itérable **paresseux** : les services tagués ne sont
instanciés qu'à l'itération. Exécuté avec `symfony/dependency-injection`
8.0.15 : après construction de `HandlerChain`, aucun gestionnaire n'est
construit. L'option `exclude` en retire certains — exécuté,
`exclude: [FaxHandler::class]` rend les trois autres.

## Les poser sans configuration

`#[AutoconfigureTag]` sur une **interface** tague toutes les classes qui
l'implémentent. L'attribut est répétable.

```php
#[AutoconfigureTag('app.handler')]
interface HandlerInterface {}
```

Le framework obtient le même effet pour ses propres interfaces par un autre
moyen : `FrameworkExtension` appelle
`registerForAutoconfiguration(EventSubscriberInterface::class)`. Relu en 8.0.15 :
`EventSubscriberInterface` ne porte pas `#[AutoconfigureTag]`.

## Ordonner et nommer

Un tag porte des attributs, dont deux sont conventionnels :

- **`priority`** — l'ordre dans l'itérateur ; **la plus haute passe en premier** ;
- **`index`** — la clé sous laquelle le service apparaît.

`#[AsTaggedItem]` les pose depuis la classe :

```php
#[AsTaggedItem(index: 'sms', priority: 10)]
class SmsHandler implements HandlerInterface {}
```

La clé ne sert que si quelque chose la demande. Exécuté, avec `SmsHandler`
indexé `sms` :

| Injection | Clés obtenues |
|---|---|
| `#[AutowireIterator('app.handler')]` | `0, 1, 2, 3` — l'index est ignoré |
| `#[AutowireIterator('app.handler', indexAttribute: 'key')]` | `sms`, puis les noms de classe |
| `#[AutowireLocator('app.handler')]` | `sms`, puis les noms de classe |

Un itérateur simple reste une **liste** ; c'est `indexAttribute` — `index_by` en
YAML — ou un localisateur qui en fait une table de correspondance.

Une méthode statique peut fournir les valeurs par défaut. `getDefaultPriority()`
donne la priorité : exécuté, 5 place le service juste après `sms`. Pour la clé,
le nom de la méthode **dérive de l'attribut d'index** : avec `index_by: key`,
c'est `getDefaultKeyName()`, sauf si `default_index_method` en nomme une autre.
Exécuté : une méthode `getDefaultIndexName()` n'est appelée que si on la nomme.
La priorité suit la même règle : sous `index_by: key`, la méthode attendue
devient `getDefaultKeyPriority()` (`TaggedIteratorArgument`), et le
`getDefaultPriority()` du service est ignoré — exécuté, il perd sa place.

## Les inspecter

```bash
php bin/console debug:container --tags
php bin/console debug:container --tag=app.handler
```

## Pièges d'examen

**Un tag n'a d'effet que si quelque chose le lit.** Taguer un service qu'aucune
passe ne collecte ne produit rien — aucune erreur, au mieux une ligne de journal
en debug.

**`priority` haute = appelé en premier**, comme pour les écouteurs d'événements.

**L'itérateur est paresseux** : les services ne sont pas construits tant qu'on
n'itère pas.

**Un itérateur sans `index_by` a des clés entières**, même si les services
déclarent un `index`.

## Points clés

- Un tag étiquette une définition ; un lecteur — passe ou itérateur — lui donne un sens.
- `!tagged_iterator` et `#[AutowireIterator]` injectent la collection, paresseuse.
- `#[AutoconfigureTag]` sur une interface tague ses implémentations.
- `priority` ordonne (haute d'abord) ; `index` ne donne une clé que sous
  `index_by` ou dans un localisateur.

## Sources officielles

- [How to Work with Service Tags](https://github.com/symfony/symfony-docs/blob/8.0/service_container/tags.rst)
- [DependencyInjection 8.0, `PriorityTaggedServiceTrait`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/PriorityTaggedServiceTrait.php)
- [DependencyInjection 8.0, `Argument\TaggedIteratorArgument`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Argument/TaggedIteratorArgument.php)
- [FrameworkBundle 8.0, `UnusedTagsPass`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Compiler/UnusedTagsPass.php)
