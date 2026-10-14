---
id: CRS-0x4v9jaz4cnq
official_item: OIT-t0qx5z264tyf
title: "Profiler object (WebProfiler bundle)"
content_level: MINIMAL
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/testing/profiling.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/testing/profiling.rst"
    anchor: "enabling-the-profiler-in-tests"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/KernelBrowser.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/KernelBrowser.php"
    branch: "8.0"
    symbol_or_lines: "enableProfiler() — no-op without the profiler service; getProfile() — false or loadProfileFromResponse()"
    verified_at: "2026-10-02"
---

## Objectif

Lire les données du profiler depuis un test, pour vérifier une métrique que la
Response ne montre pas.

## Prérequis

L'object client.

## Deux réglages, pas un

En environment de test, le profiler est **activé mais ne collecte rien** :

```yaml
when@test:
    framework:
        profiler: { enabled: true, collect: false }
```

La collecte ralentirait tous les tests. On la déclenche donc au cas par cas :

```php
$client->enableProfiler();          // pour la requête suivante seulement
$crawler = $client->request('GET', '/lucky/number');
$profile = $client->getProfile();
```

Le point que l'examen teste : **`enableProfiler()` n'active pas le profiler**,
qui doit déjà l'être par configuration. Elle active seulement la **collecte**,
et seulement **pour la Request suivante** — une deuxième Request exige un
nouvel appel. Lu dans `KernelBrowser::enableProfiler()` : sans service
`profiler`, l'appel ne fait rien.

Exécuté avec FrameworkBundle 8.0.15, `GET /hello` sous trois configurations :

| Configuration | Request | `getProfile()` |
|---|---|---|
| aucune key `profiler` | après `enableProfiler()` | `false` |
| `enabled: true, collect: false` | sans `enableProfiler()` | `null` |
| idem | juste après `enableProfiler()` | un `Profile` |
| idem | la Request suivante | `null` |
| `enabled: true, collect: true` | chacune | un `Profile` |

`false` signifie « pas de profiler » ; `null`, « profiler présent, rien
collecté pour cette Response ». Lu dans `KernelBrowser::getProfile()`.

## Lire un collector

```php
$profile->getCollector('time')->getDuration();
$profile->getToken();
```

Exécuté : le profil liste notamment les collectors `request`, `command`,
`time`, `memory` ; `getCollector('request')->getRoute()` rend `hello`.

`getProfile()` rend `false` ou `null` si le profiler n'est pas disponible, d'où
l'idiome de la documentation :

```php
if ($profile = $client->getProfile()) {
    // …
}
```

`getToken()` sert à retrouver la Request dans le profiler web après coup, en
l'incluant dans le message d'échec de l'assertion.

## Le cadre d'usage

La documentation est nette : un functional test devrait **tester la Response**.
Le profiler sert aux cas où la métrique est l'object du test — un budget de
Requests, un temps passé dans le framework.

## Pièges d'examen

**`enableProfiler()` n'active pas le profiler** ; elle active la collecte.

**Elle ne vaut que pour la Request suivante.**

**`getProfile()` peut rendre `false` ou `null`** — le tester. `false` : pas
de profiler ; `null` : rien collecté.

**`collect: true` collecte à chaque Request**, sans `enableProfiler()`.

## Points clés

- En test : `enabled: true`, `collect: false`.
- `enableProfiler()` avant la Request, `getProfile()` après.
- `getCollector('…')` pour une métrique, `getToken()` pour retrouver la trace.

## Sources officielles

- [How to Use the Profiler in a Functional Test](https://github.com/symfony/symfony-docs/blob/8.0/testing/profiling.rst)
- [`KernelBrowser`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/KernelBrowser.php)
