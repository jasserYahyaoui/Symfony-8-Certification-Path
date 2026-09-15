# Citations : l'URL vérifiée et l'URL lisible

**Date :** 2026-09-15 · **Portée :** les 1 138 citations du corpus canonique

## Ce qui a changé

Chaque citation porte désormais **deux orthographes du même objet** :

| Champ | Rôle | Hôte |
|---|---|---|
| `url` | ce que le projet **récupère** pour vérifier la revendication | `raw.githubusercontent.com` |
| `readable_url` | ce que l'apprenant **ouvre** | `github.com/<owner>/<repo>/blob/<ref>/<path>` |

La dérivation vit dans `src/Support/SourceUrl.php`, en un seul endroit. Elle
préserve propriétaire, dépôt, référence et chemin à l'identique, et rend
inchangée toute URL qui n'est pas un fichier raw — une page de documentation,
une URL déjà en `blob/`, ou la forme `refs/heads/`, dont l'équivalent rendu
s'écrit autrement.

`SourceRef` dérive le champ quand le document ne le porte pas, **dans le
constructeur** : toute citation du système a donc un lien ouvrable, qu'elle
vienne du YAML, d'une fixture ou d'un test.

## Ce qui empêche les deux champs de diverger

Deux champs pour un seul fait, c'est une invitation permanente à la dérive :
quelqu'un repointe l'un et pas l'autre, et l'apprenant lit un fichier différent
de celui contre lequel la revendication a été vérifiée, tous les contrôles au
vert.

**Règle `SRC-002`** (mandatoire, §12) : `readable_url` doit être *exactement*
`SourceUrl::readable(url)`. Pas « une URL github.com », pas « une URL qui parle
du même fichier » — la dérivation, caractère par caractère.

Elle ne reste pas silencieuse par construction :
`tools/audit/prove_framework_rules_fail.py` injecte deux défauts réalistes dans
un fichier canonique réel et exige `[ERROR] SRC-002` pour chacun, puis restaure
tout **byte-identiquement sous SHA-256** :

1. `blob/PHP-8.3/` au lieu de `blob/master/` — plausible à la lecture, et
   envoie vers une autre version de la page ;
2. `readable_url` laissé sur l'hôte raw — l'erreur de copier-coller, qui passe
   toute autre porte et envoie l'apprenant vers des octets non rendus.

## Accessibilité : ce qui est mesuré, ce qui ne l'est pas

**Mesuré.** Les **177 URLs distinctes** citées par le corpus ont été
réellement interrogées depuis le conteneur de build le 2026-09-15 :
**177/177 en `200`**, sur 1 138 citations et 6 dépôts amont
(`symfony/symfony-docs` 91, `symfony/symfony` 57, `php/doc-en` 14,
`twigphp/Twig` 11, `httpwg/httpwg.github.io` 2, `php/php-src` 2).

**Non mesuré, et dit comme tel.** La joignabilité des URLs `blob`. `github.com`
est filtré par la politique d'accès aux dépôts de cette session et répond `403`
pour un dépôt amont — c'est un fait sur le bac à sable, pas sur l'URL. La
joignabilité est établie sur la forme raw et **héritée par construction** :
même propriétaire, même dépôt, même référence, même chemin, même objet.
C'est une déduction, pas une mesure, et elle est écrite ici comme telle.

Une vérification en ligne du format rendu demanderait un job CI, le runner
GitHub n'ayant pas cette restriction. Elle n'a pas été ajoutée.

## Ce que le format raw n'a pas perdu

`raw.githubusercontent.com` reste le champ canonique parce qu'il est le seul
**vérifiable** depuis cet environnement, et parce que `AUD-02` extrait de lui le
couple (dépôt, référence) de chaque citation. Une conversion en bloc l'aurait
rendu muet — `RAW.search()` n'aurait plus rien matché, `CONTAM-1`, `CONTAM-2` et
`CONTAM-8` auraient été sautés pour **toutes** les sources, et l'audit de
contamination de version aurait affiché zéro *finding* en ne regardant plus
rien. Après le changement, `AUD-02` lit toujours ses 1 138 sources et zéro
« non-raw source ».
