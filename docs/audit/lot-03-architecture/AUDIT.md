# Lot 03 — Symfony Architecture — audit adversarial du 2026-09-16

**Note : 77/100. 1 `P0`, 3 `P1`, 7 `P2`, 10 `P3`.**

Auditeur indépendant, n'ayant pas rédigé ce lot. Les 15 cours et les 62
questions non-holdout lus intégralement, chaque affirmation confrontée à une
source récupérée sur la branche 8.0. Les 7 questions `HOLDOUT` du lot n'ont pas
été ouvertes ; elles n'apparaissent dans le rapport que comme un nombre.

## Inventaire mesuré

15 items · 7 210 mots de corps · 69 questions = **50 `LEARNING` + 12
`VALIDATION` + 7 `HOLDOUT`** · 59 résultats d'apprentissage, **tous** couverts
par au moins une question non-holdout · `POOL-002` satisfaite sur les 12 items
`STANDARD`/`DEEP` · les 24 URL sources répondent **200** sur la branche 8.0.

## Les quatre anomalies bloquantes — corrigées le jour même

Chacune vérifiée par le Tech Lead contre la source avant correction, jamais sur
la seule foi du rapport.

### `P0-1` — trois répertoires attribués au mauvais mécanisme, l'erreur répétée trois fois

Le cours *Code organization* rangeait `templates/`, `translations/` et
`vendor/` sous la clé `extra` du `composer.json`, dans le tableau, dans les
pièges d'examen **et** dans les points clés. Son mnémotechnique final — « ce que
Composer doit connaître se déclare dans `composer.json` ; ce que seul le noyau
doit connaître se surcharge dans le `Kernel` » — était une **fausse dichotomie**
qui supprimait la troisième voie.

`override_dir_structure.rst`, que le cours cite lui-même, tranche :

| Répertoire | Ligne | Mécanisme réel |
|---|---|---|
| `templates/` | 214-240 | `twig.default_path` — configuration Twig |
| `translations/` | 241-273 | `framework.translator.default_path` |
| `vendor/` | 307-320 | clé **`config`**, pas `extra` |

Les seules clés `extra` documentées sont `bin-dir`, `config-dir`, `src-dir`,
`public-dir`. Le « … » du tableau laissait croire qu'il en existait d'autres.
Un candidat entraîné sur cette page répondait `extra.templates-dir`, une clé
qui n'existe pas.

**L'erreur était propagée** dans l'explication de `QST-ys2pndyx4es9`, corrigée
dans le même geste.

### `P1-1` — `\RuntimeException` donnée comme archétype du « sans statut »

Le cours *Exception handling* écrivait, dans l'encadré que le candidat
mémorise : « Seules celles qui implémentent `HttpExceptionInterface` portent un
statut ; une `\RuntimeException` n'en porte aucun. »

`HttpException.php` l. 19 : `class HttpException extends \RuntimeException
implements HttpExceptionInterface`. `NotFoundHttpException`,
`AccessDeniedHttpException` et `BadRequestHttpException` — que le cours cite
deux paragraphes plus haut — **sont** des `\RuntimeException` et portent
**toutes** un statut. La classe choisie comme contre-exemple est la classe mère
de l'exemple.

La règle énoncée était juste ; l'illustration la démentait. Remplacée, et le
piège est désormais nommé : **le critère est l'interface, jamais la classe mère.**

### `P1-2` — le déplacement du cache présenté comme ayant une seule voie

`override_dir_structure.rst` l. 139 et 182 documentent `APP_CACHE_DIR` et
`APP_LOG_DIR` à égalité avec `getCacheDir()` / `getLogDir()`. Le cours ne
connaissait que la voie PHP, et un distracteur de `QST-ys2pndyx4es9` proposait
`KERNEL_CACHE_DIR` — faux **par le seul nom**. Un candidat connaissant
`APP_CACHE_DIR` voyait deux réponses défendables : la question mesurait du bruit.
L'explication du distracteur nomme désormais la vraie variable.

### `P1-3` — un tableau annoncé complet, amputé de trois mécanismes sur huit

`bundles/override.rst` compte **huit** sections. Le cours *Framework
overloading* en traitait cinq et promettait le tableau complet. Manquaient :

- **Routage** (l. 52-63) — « Routing is never automatically imported in
  Symfony » : surcharger revient à ne pas importer. C'est l'affirmation la plus
  interrogeable du document.
- **Contrôleurs** (l. 64-71) — route de même chemin, chargée avant celle du bundle.
- **Mapping d'entité** (l. 82-89) — possible uniquement via *mapped superclass*.

Ajoutés. Le compte annoncé est désormais exact : neuf lignes pour huit sections,
décoration et passe de compilation relevant toutes deux de *Services &
Configuration*. Au passage, `P3-7` corrigé : une traduction se surcharge par le
**domaine**, pas par le nom de fichier (l. 149-151).

## Ce que l'audit a explicitement **ne pas** trouvé

- **Version périmée : rien.** Licence, calendrier de publication (mensuel /
  mai-novembre / majeure les années impaires, 8 + 8 mois contre LTS 3 + 4 ans),
  promesse de rétrocompatibilité, dépréciations, PSR — vérifiés un par un contre
  la branche 8.0. La liste des PSR du cours correspond **exactement** à la clé
  `provide` de `symfony/symfony` 8.0, ni plus ni moins.
- **Contradiction interne : deux seulement**, dont une déjà comptée en `P0-1`.
  *Request handling* et *Event dispatcher* se recouvrent largement et disent la
  même chose, confirmée par `HttpKernel.php` et `KernelEvents.php`.
- **Fuite de réponse `CRS-001` : aucune.**
- **Biais de longueur : aucun.** 13/62 des bonnes réponses strictement les plus
  longues, soit 21,0 %, sous la ligne de base de 25 %.
- **Ancres et plages de lignes** vérifiées une à une : justes, sauf les deux
  écarts retenus en `P3`.

## Ce qui reste ouvert et nommé

`P2` — source de `QST-anhjdyw3sw5z` pointant deux sections qui ne traitent pas
son sujet ; `QST-w6dwd7mt2gh4` citant un chemin de code que son propre énoncé
exclut ; justification des dépôts Flex contredite par `setup.rst` l. 217-220 ;
règles chiffrées de l'expérimental absentes (`bc.rst` l. 624-628) ; exception
« PHP mineure » absente (`releases.rst` l. 124-127) ; `assets/` et `migrations/`
absents de l'arborescence ; trois recommandations contre-intuitives absentes
d'*Official best practices*.

`P3` — `$_SERVER` contre `$_SESSION` entre un cours et sa propre question ;
« lines 1-21 » pour un `LICENSE` de 19 lignes ; un titre de section périmé ; une
source déclarée mais non affichée ; l'équivalence suggérée entre les 5 bridges
et les 3 entrées `replace` ; un accord de genre ; 55 questions anglaises pour 15
cours français, sans critère documenté.

**Aucune contre-revue n'a vu l'état corrigé.** Le lot n'est pas déclaré validé.

## Réserve de l'auditeur, retenue telle quelle

L'audit n'a pas exécuté `composer gate-full` ni les scripts de `tools/audit/`,
et n'affirme rien sur le contenu du holdout. Trois ou quatre affirmations
« pédagogiques » du lot ne sont vérifiables contre aucune source et n'ont été ni
validées ni comptées comme fautes.
