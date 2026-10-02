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

Lire les données du profileur depuis un test, pour vérifier une métrique que la
réponse ne montre pas.

## Prérequis

L'objet client.

## Deux réglages, pas un

En environnement de test, le profileur est **activé mais ne collecte rien** :

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

Le point que l'examen teste : **`enableProfiler()` n'active pas le profileur**,
qui doit déjà l'être par configuration. Elle active seulement la **collecte**,
et seulement **pour la requête suivante** — une deuxième requête exige un
nouvel appel. Lu dans `KernelBrowser::enableProfiler()` : sans service
`profiler`, l'appel ne fait rien.

Exécuté avec FrameworkBundle 8.0.15, `GET /hello` sous trois configurations :

| Configuration | Requête | `getProfile()` |
|---|---|---|
| aucune clé `profiler` | après `enableProfiler()` | `false` |
| `enabled: true, collect: false` | sans `enableProfiler()` | `null` |
| idem | juste après `enableProfiler()` | un `Profile` |
| idem | la requête suivante | `null` |
| `enabled: true, collect: true` | chacune | un `Profile` |

`false` signifie « pas de profileur » ; `null`, « profileur présent, rien
collecté pour cette réponse ». Lu dans `KernelBrowser::getProfile()`.

## Lire un collecteur

```php
$profile->getCollector('time')->getDuration();
$profile->getToken();
```

Exécuté : le profil liste notamment les collecteurs `request`, `command`,
`time`, `memory` ; `getCollector('request')->getRoute()` rend `hello`.

`getProfile()` rend `false` ou `null` si le profileur n'est pas disponible, d'où
l'idiome de la documentation :

```php
if ($profile = $client->getProfile()) {
    // …
}
```

`getToken()` sert à retrouver la requête dans le profileur web après coup, en
l'incluant dans le message d'échec de l'assertion.

## Le cadre d'usage

La documentation est nette : un test fonctionnel devrait **tester la réponse**.
Le profileur sert aux cas où la métrique est l'objet du test — un budget de
requêtes, un temps passé dans le framework.

## Pièges d'examen

**`enableProfiler()` n'active pas le profileur** ; elle active la collecte.

**Elle ne vaut que pour la requête suivante.**

**`getProfile()` peut rendre `false` ou `null`** — le tester. `false` : pas
de profileur ; `null` : rien collecté.

**`collect: true` collecte à chaque requête**, sans `enableProfiler()`.

## Points clés

- En test : `enabled: true`, `collect: false`.
- `enableProfiler()` avant la requête, `getProfile()` après.
- `getCollector('…')` pour une métrique, `getToken()` pour retrouver la trace.

## Sources officielles

- [How to Use the Profiler in a Functional Test](https://github.com/symfony/symfony-docs/blob/8.0/testing/profiling.rst)
- [`KernelBrowser`, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/KernelBrowser.php)
