---
id: CRS-f74jcpnrrkhz
official_item: OIT-kcj9a5846b1s
title: "Framework interoperability and PSRs"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-26"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/composer.json"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/composer.json"
    symbol_or_lines: '"provide" key (psr/*-implementation entries)'
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/psr7.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/components/psr7.rst"
    anchor: "the-psr-7-bridge"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
outcome_sections:
  OUT-t5v0mavn0je1: "Les PSR implémentées"
  OUT-3we3dc646hxk: "Les PSR suivies"
  OUT-sqrkr5sjqqz1: "Le cas PSR-7"
  OUT-tga0d0zxwpvv: "Les PSR implémentées"
---

## Objectif

Savoir quelles recommandations du PHP-FIG Symfony **implémente**, lesquelles il
**suit**, et laquelle il ne supporte que par un bridge.

## L'essentiel

Symfony a trois rapports aux recommandations du PHP-FIG : il en **implémente**
huit — il fournit le code derrière l'interface —, il en **suit** deux, des
conventions, et il en **ponte** une, PSR-7, par un bridge. La source qui fait
foi est la key `provide` du `composer.json` de `symfony/symfony`.

## Le schéma

```text
implémente  PSR-3 log · 6 cache · 11 container · 13 link · 14 event dispatcher · 16 simple cache · 18 HTTP client · 20 clock
suit        PSR-4 autoload (src/ → App\) · PSR-12 style        ← absentes de provide
ponte       PSR-7 messages + PSR-17 fabriques : symfony/psr-http-message-bridge + une implémentation (nyholm/psr7)
provide     15 entrées = 8 psr/* + 5 contrats Symfony + 2 HTTPlug (php-http/*)
```

## L'exemple : une bibliothèque qui type contre une PSR

```php
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

final class Plugin
{
    public function __construct(
        private ContainerInterface $container,          // le container de Symfony convient
        private EventDispatcherInterface $dispatcher,   // le dispatcher aussi, sans adapter
    ) {}
}
```

## Les PSR implémentées

| PSR | Object | Component Symfony | Versions déclarées |
|---|---|---|---|
| **PSR-3** | Logger | intégration de logging | `1.0`, `2.0`, `3.0` |
| **PSR-6** | Cache | Cache | `2.0`, `3.0` — pas `1.0` |
| **PSR-11** | Container | DependencyInjection | `1.1`, `2.0` — pas `1.0` |
| **PSR-13** | Link | WebLink | `1.0`, `2.0` |
| **PSR-14** | Event Dispatcher | EventDispatcher | `1.0` |
| **PSR-16** | Simple Cache | Cache | `1.0`, `2.0`, `3.0` |
| **PSR-18** | HTTP Client | HttpClient | `1.0` |
| **PSR-20** | Clock | Clock | `1.0` |

Le nom du package virtuel donne l'indice : `psr/<nom>-implementation` dans
`provide` ⇒ implémentée ; absent ⇒ pas implémentée.

## Les deux autres familles de `provide`

- **Cinq contrats Symfony** — `symfony/cache-implementation`,
  `event-dispatcher`, `http-client`, `service`, `translation` : des
  abstractions publiées dans des packages minuscules. Une bibliothèque qui les
  exige fonctionne avec Symfony sans en dépendre.
- **Deux HTTPlug** — `php-http/client-implementation` et
  `php-http/async-client-implementation`, déclarés à `*` : l'abstraction client
  qui précède PSR-18. HttpClient satisfait les deux.

## Les PSR suivies

PSR-4 (autoloading) fait correspondre `src/` au namespace `App\` ; PSR-12
fonde les standards de codage. On ne « fournit » pas une convention : elles
ne figurent pas dans `provide`.

## Le cas PSR-7

PSR-7 (messages HTTP) et PSR-17 (fabriques) ne sont **pas** implémentées :
HttpFoundation a son propre modèle, antérieur à PSR-7 et mutable là où PSR-7
est immuable. Le bridge `symfony/psr-http-message-bridge` convertit dans les
deux sens, mais ne fournit pas d'implémentation : il en faut une, comme
`nyholm/psr7`.

## Pièges d'examen

**PSR-7 n'est pas implémentée.** C'est la seule de la liste qui demande un
bridge — `symfony/psr-http-message-bridge` — **plus** une implémentation tierce,
que le bridge ne fournit pas. Pour toutes les autres, Symfony *est*
l'implémentation.

**Implémentée et suivie ne sont pas la même chose.** PSR-4 et PSR-12 se
respectent, elles ne s'implémentent pas : elles ne figurent pas dans la key
`provide`.

**Le container *est* un `Psr\Container\ContainerInterface`.** Il n'y a pas
d'adapter à écrire pour une bibliothèque qui type contre PSR-11 ou PSR-14.

**`provide` ne contient pas que des PSR.** Sur quinze entrées, sept ne sont pas
des `psr/*` : cinq contrats Symfony et deux HTTPlug.

**PSR-6 est déclarée à partir de `2.0`.** Toutes les PSR implémentées ne
couvrent pas les mêmes versions majeures : trois pour PSR-3 et PSR-16, une
seule pour PSR-20, PSR-14 et PSR-18.

## Application

- Une bibliothèque qui attend un `Psr\Log\LoggerInterface` → le logger de
  l'application convient tel quel.
- Un middleware PSR-15 à réutiliser → le bridge PSR-7 et une implémentation.

## QCM d'entraînement

**Question 1.** Une bibliothèque exige `psr/container-implementation` en
`1.0`. `symfony/symfony` 8.0 la satisfait-il ?

- **A.** Oui : PSR-11 est implémentée
- **B.** Non : `1.1` ou `2.0` seulement
- **C.** Oui, par le bridge PSR-11
- **D.** Non : le container n'est pas PSR-11

<details>
<summary>Correction</summary>

**Bonne réponse : B.** `provide` déclare `1.1|2.0` pour le container : PSR-11
est bien implémentée, mais pas dans sa première version majeure.

</details>

**Question 2.** Quel component Symfony implémente PSR-13, *Link* ?

- **A.** WebLink
- **B.** Routing, qui produit les URLs
- **C.** HttpFoundation, via les headers
- **D.** Aucun : PSR-13 passe par un bridge

<details>
<summary>Correction</summary>

**Bonne réponse : A.** PSR-13 décrit des liens hypermédia ; WebLink les
fournit, et `provide` déclare `psr/link-implementation` en `1.0|2.0`.

</details>

**Question 3.** Une bibliothèque exige `php-http/client-implementation`.
Symfony la satisfait-il ?

- **A.** Non : seule PSR-18 est fournie
- **B.** Seulement avec un bridge HTTPlug
- **C.** Oui, en version `1.0` seulement
- **D.** Oui, sans constraint de version

<details>
<summary>Correction</summary>

**Bonne réponse : D.** L'entrée HTTPlug de `provide` est déclarée à `*` ;
HttpClient satisfait cette abstraction comme il satisfait PSR-18.

</details>

**Question 4.** Combien d'entrées compte la key `provide`, et combien sont des
`psr/*` ?

- **A.** Huit, toutes des PSR
- **B.** Dix, dont huit PSR et deux conventions
- **C.** Quinze, dont huit `psr/*`
- **D.** Quinze, dont dix `psr/*`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Huit PSR, cinq contrats Symfony et deux entrées
HTTPlug : `provide` n'est pas seulement la liste des PSR implémentées.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- Implémentées : **PSR-3, 6, 11, 13, 14, 16, 18, 20**.
- Suivies : **PSR-4** et **PSR-12**, absentes de `provide`.
- **PSR-7 / PSR-17** : bridge + implémentation tierce.
- Container = **PSR-11**, dispatcher = **PSR-14**, sans adapter.
- `provide` : **15** entrées — 8 `psr/*`, 5 contrats Symfony, 2 HTTPlug.
- Versions : PSR-6 dès `2.0`, PSR-11 dès `1.1`.

Moyen mnémotechnique : **« Implémenter, suivre, ponter »**.

## Sources officielles

- [composer.json de symfony/symfony, key `provide`](https://github.com/symfony/symfony/blob/8.0/composer.json)
- [The PSR-7 Bridge](https://github.com/symfony/symfony-docs/blob/8.0/components/psr7.rst)

