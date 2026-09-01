---
id: CRS-fkebqqqke5y5
official_item: OIT-adhs2ny9hc5f
title: "Service decoration"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/service_decoration.rst"
    branch: "8.0"
    symbol_or_lines: "decorates, .inner, decoration_priority, AsDecorator"
    verified_at: "2026-09-01"
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
    public function __construct(private MailerInterface $inner) {}

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

## `.inner`

Le service décoré n'est pas supprimé : il est renommé et reste disponible sous
`.inner`. C'est le seul moyen d'atteindre l'original.

En autowiring, l'argument est câblé sur `.inner` **automatiquement** — d'où
l'exemple sans `arguments` ci-dessus. En configuration explicite, il faut
l'écrire. L'option `decoration_inner_name` change ce nom si besoin.

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

L'inversion est le piège : *appliqué en premier* signifie *construit en premier*,
c'est-à-dire *le plus à l'intérieur*.

## Si la cible n'existe pas

`decoration_on_invalid` décide : lever une exception (le défaut), ignorer le
décorateur, ou injecter `null`. Utile pour un bundle qui décore un service
optionnel.

## Décorer ou remplacer

Remplacer, c'est redéfinir l'identifiant avec une autre classe : l'original
disparaît. Décorer, c'est l'envelopper : le comportement d'origine reste
accessible et composable. Un décorateur doit donc implémenter la même interface
que sa cible.

## Pièges d'examen

**L'original n'est pas supprimé** : il devient `.inner`.

**La priorité la plus haute est la plus interne.** Elle est appliquée en
premier, donc enveloppe directement le service décoré ; c'est la plus basse qui
finit à l'extérieur.

**Le décorateur prend l'identifiant de la cible** ; rien ailleurs n'a besoin de
changer.

## Points clés

- `decorates` remplace la cible en la recevant en argument ; l'identifiant est conservé.
- L'original reste joignable sous `.inner`, câblé automatiquement en autowiring.
- `decoration_priority` empile ; la plus haute est appliquée en premier, donc
  la plus **interne** — le conteneur produit `Baz(Bar(Foo))` pour 1 puis 5.
- `#[AsDecorator]` fait la même chose depuis la classe.

## Sources officielles

- [How to Decorate Services](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/service_decoration.rst)
