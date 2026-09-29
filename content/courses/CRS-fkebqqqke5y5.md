---
id: CRS-fkebqqqke5y5
official_item: OIT-adhs2ny9hc5f
title: "Service decoration"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-29"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/service_decoration.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container/service_decoration.rst"
    branch: "8.0"
    symbol_or_lines: "decorates, .inner, decoration_priority, AsDecorator"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Attribute/AsDecorator.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Attribute/AsDecorator.php"
    symbol_or_lines: "AsDecorator::__construct(decorates, priority = 0, onInvalid = EXCEPTION_ON_INVALID_REFERENCE)"
    branch: "8.0"
    verified_at: "2026-09-29"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Compiler/DecoratorServicePass.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/DecoratorServicePass.php"
    symbol_or_lines: "DecoratorServicePass::process()"
    branch: "8.0"
    verified_at: "2026-09-29"
---

## Objectif

Envelopper un service existant sans le remplacer, et savoir comment atteindre
l'original. *Quand* décorer plutôt qu'employer une passe de compilation est
traité dans *Framework overloading* (lot Symfony Architecture).

## Le mécanisme

Décorer, c'est demander au conteneur de mettre son service à la place d'un autre,
en lui passant l'ancien :

```php
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator(decorates: Mailer::class)]
class LoggingMailer implements MailerInterface
{
    public function __construct(
        private MailerInterface $inner,
        private LoggerInterface $logger,
    ) {}

    public function send(Message $m): void
    {
        $this->logger->info('sending');
        $this->inner->send($m);
    }
}
```

```yaml
services:
    App\Mailer\LoggingMailer:
        decorates: App\Mailer\Mailer
        arguments: ['@.inner']
```

Tout ce qui demandait `App\Mailer\Mailer` reçoit désormais le décorateur.
**L'identifiant ne change pas** : les autres services ne savent rien.

`#[AsDecorator]` prend trois arguments : `decorates`, `priority` (0 par défaut)
et `onInvalid` — les équivalents de `decorates`, `decoration_priority` et
`decoration_on_invalid`.

## `.inner`

Le service décoré n'est pas supprimé : il est **renommé** en
`<id du décorateur>.inner`. Exécuté avec `symfony/dependency-injection` 8.0.15 :
après décoration de `App\P6\Mailer` par `App\P6\LoggingMailer`,
`debug:container App\P6\LoggingMailer.inner` montre la classe `Mailer`.
`@.inner` est le raccourci qui désigne ce service depuis la définition du
décorateur.

En autowiring, un argument du décorateur typé comme la cible est câblé sur
`.inner` **automatiquement** — d'où l'exemple sans `arguments`. Exécuté : le
consommateur reçoit `Logging(Mailer)`. `#[AutowireDecorated]` le demande
explicitement ; en configuration explicite, il faut écrire `@.inner`.
L'option `decoration_inner_name` change ce nom : exécuté, `foo.original`
désigne bien l'original.

## Empiler des décorateurs

Plusieurs services peuvent décorer la même cible. L'ordre est donné par
`decoration_priority`, entier valant `0` par défaut : **une priorité plus haute
est appliquée plus tôt**, donc enveloppe la cible en premier — et se retrouve
donc **au plus près d'elle**, pas à l'extérieur.

```yaml
    Bar:
        decorates: Foo
        decoration_priority: 5
    Baz:
        decorates: Foo
        decoration_priority: 1
```

Le conteneur produit `new Baz(new Bar(new Foo()))`. `Bar`, prioritaire, est
collé à `Foo` ; `Baz`, moins prioritaire, est la couche externe — donc la
première appelée à l'exécution.

Exécuté : priorités 5 et 1 donnent `Baz(Bar(Foo))`. À priorité égale, l'ordre de
déclaration départage : `bar` puis `baz` donnent `baz(bar(Foo))`, l'ordre
inverse `bar(baz(Foo))` — le premier déclaré est le plus interne.

## Si la cible n'existe pas

`decoration_on_invalid` décide. Exécuté, avec une cible `nope` absente :

| Valeur | Résultat |
|---|---|
| `exception` (défaut) | `ServiceNotFoundException` : « has a dependency on a non-existent service "nope" » |
| `ignore` | le décorateur est **retiré** : ni `bar` ni `nope` n'existent |
| `null` | `nope` existe et désigne le décorateur, avec `null` en guise d'original |

Utile pour un bundle qui décore un service optionnel ; avec `null`, le
décorateur doit accepter un argument nullable.

## Décorer ou remplacer

Remplacer, c'est redéfinir l'identifiant avec une autre classe : l'original
disparaît. Décorer, c'est l'envelopper : le comportement d'origine reste
accessible et composable. Un décorateur doit donc implémenter la même interface
que sa cible.

Exécuté, la conséquence : un consommateur typé avec la **classe** décorée,
`Mailer`, reçoit le décorateur et échoue — `TypeError`, « must be of type
App\P6\Mailer, App\P6\LoggingMailer given ». Typé avec l'interface, il reçoit
`Logging(Mailer)`.

## Pièges d'examen

**L'original n'est pas supprimé** : il devient `<décorateur>.inner`.

**La priorité la plus haute est la plus interne.** Elle est appliquée en
premier, donc enveloppe directement le service décoré ; c'est la plus basse qui
finit à l'extérieur.

**Le décorateur prend l'identifiant de la cible** ; rien ailleurs n'a besoin de
changer — à condition que les consommateurs typent l'interface.

**`ignore` retire le décorateur**, il ne décore pas « à vide ».

## Points clés

- `decorates` remplace la cible en la recevant en argument ; l'identifiant est conservé.
- L'original reste joignable sous `<décorateur>.inner`, câblé automatiquement en
  autowiring.
- `decoration_priority` empile ; la plus haute est appliquée en premier, donc
  la plus **interne** — le conteneur produit `Baz(Bar(Foo))` pour 1 puis 5.
- `#[AsDecorator]` fait la même chose depuis la classe.

## Sources officielles

- [How to Decorate Services](https://github.com/symfony/symfony-docs/blob/8.0/service_container/service_decoration.rst)
- [DependencyInjection 8.0, `Attribute\AsDecorator`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Attribute/AsDecorator.php)
- [DependencyInjection 8.0, `Compiler\DecoratorServicePass`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/DecoratorServicePass.php)
