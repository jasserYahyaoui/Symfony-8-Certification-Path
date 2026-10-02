---
id: CRS-wf523wdsg2xp
official_item: OIT-xgrbftkj67ds
title: "Code debugging"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/var_dumper.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/components/var_dumper.rst"
    anchor: "components-var-dumper-dump"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/ErrorHandler/composer.json"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/ErrorHandler/composer.json"
    branch: "8.0"
    symbol_or_lines: "\"symfony/var-dumper\": \"^7.4|^8.0\" under require"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/composer.json"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/composer.json"
    branch: "8.0"
    symbol_or_lines: "\"symfony/error-handler\": \"^7.4|^8.0\" under require"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Command/RouterMatchCommand.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Command/RouterMatchCommand.php"
    branch: "8.0"
    symbol_or_lines: "RouterMatchCommand"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Bundle/FrameworkBundle/Command/ConfigDebugCommand.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/Command/ConfigDebugCommand.php"
    branch: "8.0"
    symbol_or_lines: "ConfigDebugCommand"
    verified_at: "2026-10-02"
---

## Objectif

Inspecter une valeur, et interroger l'application sur ce qu'elle a réellement
compris de sa configuration.

## Périmètre

Le **profileur web, la barre de débogage et les collecteurs de données** font
l'objet d'un item distinct ; cette page ne les traite pas. Elle porte sur
VarDumper et sur les commandes d'inspection.

## Prérequis

Les commandes intégrées de la console.

## `dump()` plutôt que `var_dump()`

VarDumper installe une fonction globale `dump()` qui apporte, sur `var_dump()` :

- une vue adaptée au type de l'objet, plutôt qu'un déversement brut ;
- une sortie HTML ou colorée en terminal selon le contexte ;
- la détection des **références** : un même objet rencontré deux fois n'est pas
  réaffiché intégralement.

```php
dump($someVar);

// dump() rend la valeur reçue : on peut inspecter sans casser la chaîne
dump($someObject)->someMethod();
```

`dd()` — *dump and die* — affiche puis **arrête** l'exécution.

Dans une application Symfony, DebugBundle redirige `dump()` vers la barre de
débogage plutôt que vers la sortie, pour ne pas corrompre la vue en envoyant du
HTML au milieu d'une réponse. Si la barre ne peut pas s'afficher — `dd()`,
`die()`, une erreur fatale — le dump repart sur la sortie normale.

## Le serveur de dump

Mélanger la sortie de débogage à celle de l'application devient vite illisible.
Le serveur de dump collecte les dumps ailleurs :

```bash
php bin/console server:dump
php bin/console server:dump --format=html > dump.html
```

Une fois lancé, `dump()` ne s'affiche plus dans la réponse : les données lui
sont envoyées. C'est la réponse au cas d'une API JSON, où tout octet imprimé
casse la réponse.

## Un `dump()` oublié en production

La documentation installe `symfony/var-dumper` et DebugBundle avec `--dev`. On
en déduit souvent qu'un `dump()` oublié appelle une fonction absente en
production. **C'est faux dans une application Symfony 8.0.** Lu dans les
`composer.json` de la branche 8.0 : FrameworkBundle exige
`symfony/error-handler`, qui exige `symfony/var-dumper` — en dépendance
ordinaire, pas `--dev`. Le composant est donc installé même avec
`composer install --no-dev`.

Seul DebugBundle est vraiment absent. Exécuté sur un serveur web PHP, noyau en
`prod`, sans DebugBundle :

| Appel | Résultat |
|---|---|
| `function_exists('dump')` | `true` |
| route qui appelle `dump()` | 200 ; le dump HTML part **avant** le contenu |
| en-têtes de cette réponse | ceux de PHP : `Cache-Control` de Symfony a disparu |

Le dump est écrit sur la sortie avant que Symfony n'envoie sa réponse : PHP
envoie alors ses propres en-têtes, et ceux de la réponse sont perdus. Pas
d'erreur fatale, donc, mais une réponse corrompue — fatale pour une API JSON.

## Interroger l'application

Le second outil de débogage n'inspecte pas une valeur mais la **configuration
comprise** — question qu'aucune relecture de fichier ne tranche, puisque la
configuration résulte d'une fusion :

| Commande | Ce qu'elle répond |
|---|---|
| `debug:container` | quels services existent, sous quel identifiant |
| `debug:autowiring` | quel type peut être injecté, et par quoi |
| `debug:router` | quelles routes existent, dans quel ordre |
| `router:match /chemin` | **quelle** route répond à cette URL, et pourquoi |
| `debug:event-dispatcher` | quels écouteurs, dans quel ordre de priorité |
| `debug:config` | la configuration d'une extension, après fusion |
| `debug:twig` | les fonctions, filtres et chemins connus de Twig |

`router:match` mérite d'être retenue : elle explique un 404. Exécuté sur
FrameworkBundle 8.0.15, sur un chemin inconnu :

| Appel | Sortie |
|---|---|
| `router:match /e/rtx` | *None of the routes match the path "/e/rtx"* |
| même appel avec `-v` | chaque route essayée, et la raison : *Path "/public" does not match* |

`debug:config framework exceptions` affiche aussi les clés jamais écrites, avec
leur valeur par défaut (`log_level: null`) : c'est la configuration **après
fusion**.

## Pièges d'examen

**`dd()` arrête l'exécution ; `dump()` non**, et `dump()` rend sa valeur.

**`dump()` va dans la barre de débogage** quand DebugBundle est installé, pas
dans la réponse.

**Un `dump()` déployé ne provoque pas d'erreur** : VarDumper est requis par
ErrorHandler. Il corrompt la réponse — contenu et en-têtes.

**`debug:config` montre la configuration fusionnée**, pas le contenu d'un
fichier.

## Points clés

- `dump()` sur `var_dump()` ; `dd()` ajoute l'arrêt.
- DebugBundle envoie les dumps vers la barre ; `server:dump` vers un serveur.
- La documentation l'installe en `--dev`, mais FrameworkBundle le tire via
  ErrorHandler : en production, `dump()` existe et écrit dans la réponse.
- Les commandes `debug:*` et `router:match` révèlent ce que Symfony a compris.

## Sources officielles

- [The VarDumper Component](https://github.com/symfony/symfony-docs/blob/8.0/components/var_dumper.rst)
- [Console Commands](https://github.com/symfony/symfony-docs/blob/8.0/console.rst)
- [`composer.json` d'ErrorHandler, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/ErrorHandler/composer.json)
- [`composer.json` de FrameworkBundle, branche 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Bundle/FrameworkBundle/composer.json)
