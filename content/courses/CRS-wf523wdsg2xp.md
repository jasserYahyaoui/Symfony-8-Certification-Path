---
id: CRS-wf523wdsg2xp
official_item: OIT-xgrbftkj67ds
title: "Code debugging"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/var_dumper.rst"
    anchor: "components-var-dumper-dump"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
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

## Une dépendance de développement

`symfony/var-dumper` s'installe avec `--dev`, et DebugBundle également. C'est
voulu, et la conséquence est brutale : un `dump()` oublié dans du code déployé
appelle une fonction **qui n'existe pas** en production, ce que PHP 8 signale
par une `Error` fatale — pas une exception applicative rattrapable par une règle
métier, et pas un simple affichage indésirable.

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

`router:match` mérite d'être retenue : elle explique un 404 en montrant les
routes essayées et la raison de leur échec.

## Pièges d'examen

**`dd()` arrête l'exécution ; `dump()` non**, et `dump()` rend sa valeur.

**`dump()` va dans la barre de débogage** quand DebugBundle est installé, pas
dans la réponse.

**VarDumper est une dépendance `--dev`** : un `dump()` déployé provoque une
erreur fatale de fonction indéfinie.

**`debug:config` montre la configuration fusionnée**, pas le contenu d'un
fichier.

## Points clés

- `dump()` sur `var_dump()` ; `dd()` ajoute l'arrêt.
- DebugBundle envoie les dumps vers la barre ; `server:dump` vers un serveur.
- Installé en **dépendance de développement** (`composer require --dev`) : en
  production le composant est absent, et l'appel échoue parce que la fonction
  n'y existe pas.
- Les commandes `debug:*` et `router:match` révèlent ce que Symfony a compris.

## Sources officielles

- [The VarDumper Component](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/var_dumper.rst)
- [Console Commands](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/console.rst)
