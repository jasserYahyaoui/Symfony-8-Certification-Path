---
id: CRS-htvm3b1gas0d
official_item: OIT-92ctf3sy3ddk
title: "Dependency Injection component"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container.rst"
    branch: "8.0"
    symbol_or_lines: "What is a Service Container"
    verified_at: "2026-09-01"
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
| **propriété** | propriété publique affectée | déconseillée : casse l'encapsulation |

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
$container->register('mailer', Mailer::class)->addArgument(new Reference('transport'));
$container->compile();
```

La compilation est le moment où le conteneur est résolu et figé. Dans une
application Symfony, elle a lieu une fois puis est mise en cache.

## Pièges d'examen

**Injection ≠ récupération.** Recevoir le conteneur n'est pas de l'injection de
dépendances, même si le conteneur est injecté.

**Le constructeur est la voie normale**, le mutateur l'exception ; l'inverse
n'est pas vrai.

**Le composant s'utilise seul**, sans le framework.

## Points clés

- La classe déclare ses besoins ; quelqu'un d'autre les fournit.
- Constructeur pour l'obligatoire, mutateur pour le facultatif ou un cycle.
- Recevoir le conteneur entier n'est pas de l'injection : c'est de la
  récupération, et cela masque les dépendances.
- `ContainerBuilder` + `compile()` : composant autonome.

## Sources officielles

- [Service Container, « What is a Service Container »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container.rst)
