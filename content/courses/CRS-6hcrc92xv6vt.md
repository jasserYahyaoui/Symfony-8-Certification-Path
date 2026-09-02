---
id: CRS-6hcrc92xv6vt
official_item: OIT-qpjnpc56hjj0
title: "Clock"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/clock.rst"
    anchor: "clock_usage"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
---

## Objectif

Rendre testable du code qui dépend de l'heure. C'est la raison d'être du
composant, énoncée telle quelle par la documentation : **découpler l'application
de l'horloge système** pour pouvoir figer le temps.

Le problème qu'il résout est concret. Une méthode qui appelle
`new \DateTimeImmutable()` à l'intérieur d'elle-même ne peut pas être testée
pour hier, pour dans un mois, ou pour le 29 février : son résultat dépend du
moment où la suite de tests s'exécute.

## Prérequis

L'injection de dépendances, et `DateTimeImmutable`.

## Trois horloges, trois usages

Le composant fournit une `ClockInterface` et trois implémentations :

| Implémentation | Ce qu'elle fait | Quand |
|---|---|---|
| `NativeClock` | l'heure système — **équivalent à `new \DateTimeImmutable()`** | production |
| `MockClock` | une heure figée, que l'on déplace soi-même | tests |
| `MonotonicClock` | s'appuie sur `hrtime()`, haute résolution et **monotone** | mesurer une durée |

`MonotonicClock` n'est pas une horloge « plus précise » à tout faire : elle sert
de **chronomètre**. Son intérêt est de croître régulièrement sans subir les
ajustements de l'horloge système, qui peuvent faire reculer l'heure au milieu
d'une mesure.

## Injecter l'horloge

```php
use Symfony\Component\Clock\ClockInterface;

class ExpirationChecker
{
    public function __construct(private ClockInterface $clock) {}

    public function isExpired(\DateTimeInterface $validUntil): bool
    {
        return $this->clock->now() > $validUntil;
    }
}
```

Le remplacement à retenir : **`$this->clock->now()` à la place de
`new \DateTimeImmutable()`**. Rien d'autre ne change dans la logique.

Une variante existe pour éviter le paramètre de constructeur : le trait
**`ClockAwareTrait`**. Grâce à l'autoconfiguration, le conteneur appelle son
`setClock()`, et le service utilise ensuite **`$this->now()`**.

## `MockClock` : le temps ne passe pas tout seul

C'est le point qui décide le plus de réponses.

```php
$clock = new MockClock('2022-11-16 15:20:00');

$clock->sleep(600);                       // il est maintenant 15:30:00
$clock->modify('2022-11-16 15:00:00');    // et maintenant 15:00:00
```

`MockClock` est instanciée à une heure et **n'avance pas d'elle-même**. Elle
reste figée jusqu'à un appel explicite :

- **`sleep()`** déplace l'heure **instantanément** — `sleep(600)` ne fait pas
  attendre le test dix minutes, il fait comme si ;
- **`modify()`** accepte tous les formats de `DateTimeImmutable::modify()`.

## L'horloge globale

```php
use Symfony\Component\Clock\Clock;
use function Symfony\Component\Clock\now;

Clock::set(new MockClock());
$clock = Clock::get();

$now = now();
$later = now('+3 hours');
```

`Clock` accepte n'importe quelle implémentation compatible **PSR-20** comme
horloge globale. La fonction `now()` rend l'heure courante et accepte un
**modificateur** optionnel, dans les formats acceptés par le constructeur de
`DateTime`.

## `DatePoint`

Une enveloppe fine autour de `DateTimeImmutable`, utilisable partout où un
`DateTimeImmutable` ou un `DateTimeInterface` est attendu.

Sa particularité : **elle prend son heure de `Clock`**. Toute modification de
l'horloge globale se répercute donc sur un `DatePoint` créé ensuite — ce qui la
rend utilisable comme valeur par défaut sans casser la testabilité :

```php
private \DateTimeImmutable $createdAt = new DatePoint();
```

## Tester

Le trait **`ClockSensitiveTrait`** fige l'heure et restaure l'horloge globale
après chaque test. Sa méthode `mockTime()` accepte :

- une **chaîne** — une date (`'1996-07-01'`) ou un intervalle (`'+2 days'`) ;
- un **`DateTimeImmutable`**, pour se placer à cette date ;
- un **booléen**, pour figer ou restaurer l'horloge globale.

```php
$clock = static::mockTime(new \DateTimeImmutable('2022-03-02'));
$service->setClock($clock);
```

Combiné à `ClockAwareTrait`, cela donne un test dont le résultat ne dépend plus
du jour où on le lance.

## Pièges d'examen

**`MockClock` n'avance jamais seule.** Sans `sleep()` ni `modify()`, deux appels
à `now()` rendent la même heure.

**`sleep()` ne suspend pas l'exécution** dans une `MockClock` : elle déplace
l'heure simulée instantanément.

**`NativeClock` est l'équivalent de `new \DateTimeImmutable()`** — l'intérêt
n'est pas ce qu'elle rend, c'est qu'on puisse la remplacer.

**`MonotonicClock` sert à mesurer une durée**, pas à connaître la date.

**Deux traits, deux rôles** : `ClockAwareTrait` dans le service,
`ClockSensitiveTrait` dans le test.

**`DatePoint` lit `Clock`** : ce n'est pas un `DateTimeImmutable` ordinaire.

## Points clés

- Le composant existe pour découpler du temps système et rendre testable.
- `NativeClock` en production, `MockClock` en test, `MonotonicClock` pour chronométrer.
- `$this->clock->now()` remplace `new \DateTimeImmutable()`.
- `MockClock` est figée ; `sleep()` et `modify()` la déplacent.
- `ClockAwareTrait` côté service, `ClockSensitiveTrait::mockTime()` côté test.
- `DatePoint` enveloppe `DateTimeImmutable` et tire son heure de `Clock`.

## Sources officielles

- [The Clock Component](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/clock.rst)
