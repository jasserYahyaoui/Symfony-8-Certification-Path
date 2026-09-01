---
id: CRS-w6yfxm6ad4zy
official_item: OIT-wm3qdqemtap9
title: "Services autowiring"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/autowiring.rst"
    branch: "8.0"
    symbol_or_lines: "type-based resolution, aliases, named autowiring aliases, Target, Autowire"
    verified_at: "2026-09-01"
---

## Objectif

Comprendre par quoi le conteneur remplit un argument, pourquoi deux
implémentations d'une même interface le bloquent, et les trois façons de
trancher. C'est le mécanisme qui explique pourquoi presque aucune configuration
n'est nécessaire — et pourquoi, quand il échoue, le message est déroutant.

## Prérequis

Le conteneur, ses définitions et ses alias.

## La règle, en une phrase

L'autowiring résout un argument **par son type**, en cherchant un service dont
l'identifiant est ce type.

```php
class Mailer
{
    public function __construct(private TransportInterface $transport) {}
}
```

Le conteneur cherche un service d'identifiant `App\Mail\TransportInterface`.
Comme les classes découvertes ont leur FQCN pour identifiant, cela fonctionne
sans rien écrire.

**Le nom de l'argument n'intervient pas** — sauf dans les cas de départage
ci-dessous. Le renommer ne change rien ; changer son type change tout.

## Pourquoi une interface a besoin d'un alias

Une interface n'est le nom d'aucun service : personne ne l'instancie. Type-hinter
`TransportInterface` échoue donc, à moins qu'un **alias** ne dise quelle
implémentation utiliser :

```yaml
services:
    App\Mail\TransportInterface: '@App\Mail\SmtpTransport'
```

Quand une interface n'a qu'**une seule** implémentation dans `src/`, Symfony crée
cet alias tout seul. Dès qu'il y en a deux, il ne devine plus, et le message est
« *Cannot autowire … : argument type-hinted with interface … but no such service
exists* » — la cause n'est pas l'absence de classe, mais l'absence de choix.

## Trois façons de départager

**1. L'alias par défaut** — une implémentation gagne pour tout le monde :

```yaml
    App\Util\TransformerInterface: '@App\Util\Rot13Transformer'
```

**2. L'alias d'autowiring nommé** — l'interface *plus le nom de l'argument* :

```yaml
    App\Util\TransformerInterface $shoutyTransformer: '@App\Util\UppercaseTransformer'
```

Tout argument typé `TransformerInterface` **et nommé** `$shoutyTransformer`
reçoit alors l'implémentation criarde ; les autres gardent celle par défaut.
C'est l'unique cas où le nom de l'argument compte.

**3. `#[Target]`** — le même choix, déclaré sur l'argument :

```php
public function __construct(
    #[Target('shoutyTransformer')] private TransformerInterface $transformer,
) {}
```

`#[Target]` prend le **nom employé dans l'alias nommé**, pas un identifiant de
service ni un alias ordinaire. Son avantage sur la solution 2 : le nom de
l'argument redevient libre, et une faute de frappe lève une exception au lieu de
retomber silencieusement sur l'implémentation par défaut.

## Câbler ce qui n'est pas un service

`#[Autowire]` couvre tout ce que le type ne peut pas exprimer :

```php
#[Autowire(service: 'monolog.logger.request')] LoggerInterface $logger,
#[Autowire('%kernel.project_dir%/data')]       string $dataDir,
#[Autowire(param: 'kernel.debug')]             bool $debug,
#[Autowire(env: 'bool:FEATURE_FLAG')]          bool $flag,
```

## Les limites

L'autowiring ne devine **que par le type**. Un argument scalaire — `string`,
`int` — n'a aucun service correspondant : il faut `bind`, `#[Autowire]` ou un
argument explicite. Un argument avec une valeur par défaut est laissé tel quel
si rien ne correspond.

`php bin/console debug:autowiring` liste les types câblables **et** les alias
nommés existants ; c'est la réponse à « pourquoi ça ne se câble pas ».

## Pièges d'examen

- L'autowiring passe par le **type**, pas par le nom — sauf alias nommé et `#[Target]`.
- Deux implémentations d'une interface **suppriment** l'alias automatique.
- `#[Target]` attend le nom de l'**alias nommé**, pas un identifiant de service.
- Un scalaire ne s'autowire jamais.
- L'autowiring est résolu **à la compilation** : l'erreur apparaît au build, pas à la requête.

## Points clés

- Un argument est rempli par son type ; le FQCN sert d'identifiant.
- Une interface a besoin d'un alias, créé seul si l'implémentation est unique.
- Départager : alias par défaut, alias nommé `Interface $argument`, ou `#[Target]`.
- `#[Autowire]` injecte service, paramètre, variable d'environnement ou expression.
- `debug:autowiring` est l'outil de diagnostic.

## Sources officielles

- [Defining Services Dependencies Automatically (Autowiring)](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/autowiring.rst)
