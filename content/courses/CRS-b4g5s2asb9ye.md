---
id: CRS-b4g5s2asb9ye
official_item: OIT-83sac57rw0xn
title: "Tags"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/tags.rst"
    branch: "8.0"
    symbol_or_lines: "tagged_iterator, AutoconfigureTag, AsTaggedItem, AutowireIterator"
    verified_at: "2026-09-01"
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
instanciés qu'à l'itération. `exclude` en retire certains.

## Les poser sans configuration

`#[AutoconfigureTag]` sur une **interface** tague toutes les classes qui
l'implémentent :

```php
#[AutoconfigureTag('app.handler')]
interface HandlerInterface {}
```

C'est ce que fait le framework pour ses propres interfaces, et c'est pourquoi
implémenter `EventSubscriberInterface` suffit.

## Ordonner et nommer

Un tag porte des attributs, dont deux sont conventionnels :

- **`priority`** — l'ordre dans l'itérateur ; **la plus haute passe en premier** ;
- **`index`** — la clé sous laquelle le service apparaît, ce qui transforme
  l'itérateur en table de correspondance.

`#[AsTaggedItem]` les pose depuis la classe :

```php
#[AsTaggedItem(index: 'sms', priority: 10)]
class SmsHandler implements HandlerInterface {}
```

Sans `index`, une méthode statique `getDefaultIndexName()` sur la classe peut
fournir la clé ; `getDefaultPriority()` fait de même pour l'ordre.

## Les inspecter

```bash
php bin/console debug:container --tags
php bin/console debug:container --tag=app.handler
```

## Pièges d'examen

**Un tag n'a d'effet que si quelque chose le lit.** Taguer un service qu'aucune
passe ne collecte ne produit rien — et aucune erreur.

**`priority` haute = appelé en premier**, comme pour les écouteurs d'événements.

**L'itérateur est paresseux** : les services ne sont pas construits tant qu'on
n'itère pas.

## Points clés

- Un tag étiquette une définition ; un lecteur — passe ou itérateur — lui donne un sens.
- `!tagged_iterator` et `#[AutowireIterator]` injectent la collection, paresseuse.
- `#[AutoconfigureTag]` sur une interface tague ses implémentations.
- `priority` ordonne (haute d'abord), `index` donne une clé ; `#[AsTaggedItem]` pose les deux.

## Sources officielles

- [How to Work with Service Tags](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/tags.rst)
