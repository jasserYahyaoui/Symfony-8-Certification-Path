---
id: CRS-p1694d5f7r8c
official_item: OIT-79f4n087b66c
title: "Service container"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/alias_private.rst"
    branch: "8.0"
    symbol_or_lines: "public and private services, aliases"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container.rst"
    branch: "8.0"
    verified_at: "2026-09-01"
---

## Objectif

Comprendre ce que le conteneur contient, pourquoi il est compilé, et ce que
« privé » veut dire ici. La déclaration des services est traitée dans *Services
registration*.

## Une définition, pas un objet

Le conteneur ne contient pas des objets : il contient des **définitions** — la
classe, les arguments, les appels de méthode, les tags. L'objet n'est construit
qu'au premier `get()`, puis réutilisé. Un service est donc, par défaut, un
**singleton** dans le conteneur : deux injections du même service donnent la
même instance.

Chaque définition a un **identifiant**. Par convention, c'est le nom pleinement
qualifié de la classe — `App\Mailer\Mailer` — ce qui rend l'autowiring possible.

## Public ou privé

**Privé est le défaut**, et c'est la décision structurante.

| | Privé | Public |
|---|---|---|
| Injectable dans un autre service | oui | oui |
| Récupérable par `$container->get('id')` | **non** | oui |
| Peut être supprimé s'il n'est utilisé nulle part | **oui** | non |

Un service privé n'est pas caché : il est simplement **inaccessible depuis
l'extérieur du conteneur**. L'intérêt est double — le compilateur peut l'inliner
ou le retirer s'il n'est référencé nulle part, et rien ne peut le récupérer à la
volée, donc ses usages sont tous visibles dans la configuration.

Dans un test fonctionnel, `static::getContainer()` donne accès à un conteneur
spécial qui expose aussi les services privés.

## L'alias

Un **alias** est un second identifiant pointant vers une définition :

```yaml
services:
    Psr\Log\LoggerInterface: '@monolog.logger'
```

C'est le mécanisme qui rend une **interface** autowirable : l'argument est typé
avec l'interface, l'alias dit quelle implémentation utiliser.

## La compilation

Au premier chargement, le conteneur est **compilé** en une classe PHP écrite
dans `var/cache/`. Les paramètres sont résolus, les passes de compilation
s'exécutent, les services inutilisés disparaissent.

Conséquence pratique : le conteneur d'exécution ne relit aucune configuration.
Modifier `services.yaml` sans vider le cache n'a aucun effet en production ; en
développement, le conteneur est reconstruit quand ses sources changent.

## Inspecter

```bash
php bin/console debug:container            # tous les services
php bin/console debug:container --show-private
php bin/console debug:container Mailer     # recherche partielle
php bin/console debug:autowiring Logger    # ce qui est autowirable
```

## Pièges d'examen

**Un service est privé par défaut** et ne se récupère donc pas par `get()`.

**Privé ne veut pas dire « non injectable »** : cela ne concerne que l'accès
depuis l'extérieur.

**Le conteneur compilé est du PHP en cache** : la configuration n'est pas relue
à l'exécution.

## Points clés

- Le conteneur porte des définitions ; l'instance est créée à la demande, une fois.
- Privé par défaut : injectable, non récupérable, supprimable si inutilisé.
- Un alias donne un second identifiant — c'est ce qui rend une interface autowirable.
- Compilation en PHP dans `var/cache/` ; `debug:container` pour inspecter.

## Sources officielles

- [How to Make Service Arguments/References Optional, « Public and Private Services »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/alias_private.rst)
- [Service Container](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container.rst)
