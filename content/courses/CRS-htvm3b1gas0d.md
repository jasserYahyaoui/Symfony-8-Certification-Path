---
id: CRS-htvm3b1gas0d
official_item: OIT-92ctf3sy3ddk
title: "Dependency Injection component"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-29"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container.rst"
    branch: "8.0"
    symbol_or_lines: "What is a Service Container"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/ContainerBuilder.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/ContainerBuilder.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "ContainerBuilder::register(), compile(), get()"
    verified_at: "2026-09-29"
---

## Objectif

Savoir ce que le composant résout, et distinguer l'injection de la récupération.
Le conteneur lui-même — définitions, visibilité, compilation — est traité dans
*Service container*.

## Le problème

Une classe qui construit ses propres collaborateurs les choisit à votre place :

```php
class Mailer
{
    public function __construct()
    {
        $this->transport = new SmtpTransport('smtp.acme.test');  // figé
    }
}
```

Impossible de la tester sans SMTP, impossible d'en changer sans la modifier.
**L'injection de dépendances** renverse la responsabilité : la classe déclare ce
dont elle a besoin, quelqu'un d'autre le fournit.

```php
class Mailer
{
    public function __construct(private TransportInterface $transport) {}
}
```

## Trois formes d'injection

| Forme | Écriture | Quand |
|---|---|---|
| **constructeur** | argument du constructeur | dépendance **obligatoire** — la voie normale |
| **mutateur** | `setLogger()`, souvent `#[Required]` | dépendance facultative, ou cycle à casser |
| **propriété** | propriété publique, `Definition::setProperty()` | déconseillée : casse l'encapsulation |

## Injection ou récupération

C'est la distinction de fond. **Injecter**, c'est recevoir ce dont on a besoin.
**Récupérer** (*service location*), c'est demander au conteneur de le donner :

```php
$mailer = $container->get('mailer');   // récupération
```

Une classe qui reçoit le conteneur entier peut demander n'importe quoi, donc
ses dépendances ne sont plus lisibles dans sa signature. C'est pourquoi le
conteneur n'est pas injecté dans les services applicatifs. Le cas légitime —
beaucoup de dépendances dont peu servent à chaque appel — a sa propre réponse,
le *service locator*, traité dans son item.

## Le composant

`symfony/dependency-injection` est **autonome**. Hors framework, on construit un
`ContainerBuilder`, on y déclare des définitions, puis on appelle `compile()` :

```php
$container = new ContainerBuilder();
$container->setParameter('smtp_host', 'smtp.acme.test');
$container->register('transport', SmtpTransport::class)->addArgument('%smtp_host%');
$container->register('mailer', Mailer::class)
    ->addArgument(new Reference('transport'))
    ->setPublic(true);
$container->compile();
```

La compilation exécute les passes de compilation, résout le conteneur et le
**fige**. Dans une application Symfony, elle a lieu une fois puis est mise en
cache.

## Ce que l'exécution montre

Exécuté avec `symfony/dependency-injection` 8.0.15 :

| Situation | Résultat |
|---|---|
| `register()` sans `setPublic()` | `isPublic()` vaut `false` : **privé par défaut** |
| `get('mailer')` **avant** `compile()` | fonctionne, et `%smtp_host%` est résolu |
| `get('mailer')` après `compile()`, service privé | `ServiceNotFoundException` : « removed or inlined when the container was compiled » |
| `has('transport')` après `compile()`, privé | `false` |
| `register()` après `compile()` | `BadMethodCallException` : « Adding definition to a compiled container is not allowed. » |
| `setParameter()` après `compile()` | `LogicException` : « Impossible to call set() on a frozen ParameterBag. » |
| deux `get('mailer')` | la même instance ; `setShared(false)` en donne deux |

Le message du conteneur compilé conseille lui-même la voie normale : « make it
public, or stop using the container directly and use dependency injection ».

## Pièges d'examen

**Injection ≠ récupération.** Recevoir le conteneur n'est pas de l'injection de
dépendances, même si le conteneur est injecté.

**Le constructeur est la voie normale**, le mutateur l'exception ; l'inverse
n'est pas vrai.

**Un service est privé par défaut** : après `compile()`, il n'est plus
récupérable par `get()` s'il n'a pas été rendu public.

**`compile()` fige** : plus de définition ni de paramètre ajouté ensuite.

## Points clés

- La classe déclare ses besoins ; quelqu'un d'autre les fournit.
- Constructeur pour l'obligatoire, mutateur pour le facultatif ou un cycle.
- Recevoir le conteneur entier n'est pas de l'injection : c'est de la
  récupération, et cela masque les dépendances.
- `ContainerBuilder` + `compile()` : composant autonome ; services privés par
  défaut, conteneur figé après compilation.

## Sources officielles

- [Service Container, « What is a Service Container »](https://github.com/symfony/symfony-docs/blob/8.0/service_container.rst)
- [DependencyInjection 8.0, `ContainerBuilder`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/ContainerBuilder.php)
