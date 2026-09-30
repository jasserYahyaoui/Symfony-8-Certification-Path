---
id: CRS-w6yfxm6ad4zy
official_item: OIT-wm3qdqemtap9
title: "Services autowiring"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-30"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/autowiring.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container/autowiring.rst"
    branch: "8.0"
    symbol_or_lines: "type-based resolution, aliases, named autowiring aliases, Target, Autowire"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Compiler/AutowirePass.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/AutowirePass.php"
    symbol_or_lines: "getAutowiredReference() — a Target naming a service id already aimed at by a named alias of the type"
    branch: "8.0"
    verified_at: "2026-09-30"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Attribute/Target.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Attribute/Target.php"
    symbol_or_lines: "Target::getParsedName() — camelCase normalization"
    branch: "8.0"
    verified_at: "2026-09-30"
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

Quand une interface n'a qu'**une seule** implémentation parmi les classes
découvertes, Symfony crée cet alias tout seul. Dès qu'il y en a deux, il ne
devine plus. Exécuté avec FrameworkBundle 8.0.15, deux implémentations de
`TransformerInterface` :

> Cannot autowire service "App\P11\Consumer": argument "$transformer" of method
> "__construct()" references interface "App\P11\TransformerInterface" but no
> such service exists. You should maybe alias this interface to one of these
> existing services: "App\P11\Rot13Transformer", "App\P11\UppercaseTransformer".

La cause n'est pas l'absence de classe, mais l'absence de choix — et le message
propose lui-même les candidats.

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

`#[Target]` prend le **nom employé dans l'alias nommé**. Son avantage sur la
solution 2 : le nom de l'argument redevient libre, et une faute de frappe lève
une exception au lieu de retomber silencieusement sur l'implémentation par
défaut.

## Ce que l'exécution montre

Les deux alias ci-dessus déclarés, un consommateur à quatre arguments :

| Argument | Reçoit |
|---|---|
| `TransformerInterface $transformer` | `rot13` — l'alias par défaut |
| `TransformerInterface $shoutyTransformer` | `upper` — l'alias nommé |
| `TransformerInterface $shoutyTransfomer` (faute de frappe) | `rot13`, **sans erreur** |
| `#[Target('shoutyTransfomer')]` (même faute) | « has "#[Target('shoutyTransfomer')]" but no such target exists. Did you mean to target "shoutyTransformer" instead? » |
| `#[Target('shouty.transformer')]` | `upper` — le nom est normalisé en camelCase |

`debug:autowiring Transformer` liste les deux : `App\P11\TransformerInterface`
et `App\P11\TransformerInterface $shoutyTransformer`.

## Ce que dit la documentation, ce que fait le code

`autowiring.rst` (8.0) avertit que `#[Target]` « **does not** accept service ids
or service aliases ». Exécuté, c'est plus nuancé :

| `#[Target(…)]` | Résultat |
|---|---|
| `'App\P11\UppercaseTransformer'`, cible d'un alias nommé du même type | injecté |
| `'App\P11\Rot13Transformer'`, cible du seul alias par défaut | « no such target exists » |

`AutowirePass::getAutowiredReference()` accepte un identifiant de service
**lorsqu'un alias nommé de ce type le vise déjà**. La règle d'écriture reste celle
de la documentation — viser le nom de l'alias nommé —, mais l'affirmation absolue
est fausse.

## Câbler ce qui n'est pas un service

`#[Autowire]` couvre tout ce que le type ne peut pas exprimer :

```php
#[Autowire(service: 'monolog.logger.request')] LoggerInterface $logger,
#[Autowire('%kernel.project_dir%/data')]       string $dataDir,
#[Autowire(param: 'kernel.debug')]             bool $debug,
#[Autowire(env: 'bool:FEATURE_FLAG')]          bool $flag,
```

## Les limites

L'autowiring ne devine **que par le type**. Un argument scalaire n'a aucun
service correspondant — exécuté : « argument "$dir" of method "__construct()"
is type-hinted "string", you should configure its value explicitly. » Il faut
`bind`, `#[Autowire]` ou un argument explicite.

Un argument nullable avec une valeur par défaut est laissé tel quel si rien ne
correspond : exécuté, un `?App\Missing\Nope $opt = null` reçoit `null`.

L'erreur apparaît à la compilation — **pour les services conservés**. Exécuté :
la même classe au `string` non résolu, privée et jamais utilisée, est retirée et
ne lève rien.

## Pièges d'examen

- L'autowiring passe par le **type**, pas par le nom — sauf alias nommé et `#[Target]`.
- Deux implémentations d'une interface **suppriment** l'alias automatique.
- Une faute dans le nom d'argument retombe **en silence** sur l'alias par
  défaut ; la même faute dans `#[Target]` lève une exception.
- `#[Target]` vise le nom de l'**alias nommé** ; un identifiant ne passe que s'il
  est déjà la cible d'un alias nommé du même type.
- Un scalaire ne s'autowire jamais.

## Points clés

- Un argument est rempli par son type ; le FQCN sert d'identifiant.
- Une interface a besoin d'un alias, créé seul si l'implémentation est unique.
- Départager : alias par défaut, alias nommé `Interface $argument`, ou `#[Target]`.
- `#[Autowire]` injecte service, paramètre, variable d'environnement ou expression.
- `debug:autowiring` est l'outil de diagnostic.

## Sources officielles

- [Defining Services Dependencies Automatically (Autowiring)](https://github.com/symfony/symfony-docs/blob/8.0/service_container/autowiring.rst)
- [DependencyInjection 8.0, `Compiler\AutowirePass`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/AutowirePass.php)
- [DependencyInjection 8.0, `Attribute\Target`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Attribute/Target.php)
