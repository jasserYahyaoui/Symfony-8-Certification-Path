---
lot: lot-15
verification_status: VERIFIED
reviewed_at: '2026-11-07'
---

## En une phrase

Ce lot couvre la mise en production — les étapes que tout deployment
comporte — et les outils de développement qui observent chaque Request : les
data collectors, le profiler et la debug toolbar.

## L'image de la vie réelle

Pense à l'**ouverture d'un magasin**. Le matin, on livre la marchandise, on la
range, on met à jour les prix, on vide les cartons : c'est le
**deployment**. Pendant la période d'essai, des caméras et des compteurs notent
tout ce qui se passe : ce sont les **data collectors**. L'enregistrement est
rangé dans une archive avec un numéro (le **profiler**), et un petit écran près
de la caisse en affiche le résumé (la **debug toolbar**). Ces caméras sont
démontées avant l'ouverture au public.

## Notion par notion

### Deployment best practices

**C'est quoi ?** Les étapes d'une mise en production, quel que soit l'outil :
transférer le code, installer les dependencies, migrer les données, vider le
cache.

**Dans la vraie vie** Livrer, ranger, étiqueter, nettoyer : l'ordre de
l'ouverture du magasin, que la camionnette soit grande ou petite.

**Pour l'examen** Aucun outil n'est prescrit. Les dependencies s'installent
sans celles de développement, avec un autoloader optimisé, et le cache se vide
en environment `prod`. `composer dump-env prod` compile les variables dans
`.env.local.php`.

### Web Profiler, Web Debug Toolbar and Data collectors

**C'est quoi ?** Trois pièces : les data collectors ramassent l'information,
le profiler la stocke sous un token, la debug toolbar en affiche un résumé.

**Dans la vraie vie** Les caméras (collectors), l'archive numérotée (profiler)
et l'écran de résumé près de la caisse (toolbar).

**Pour l'examen** Le tout s'installe en dependency de développement, jamais en
production. La barre n'apparaît que sur une page HTML ; pour une Response JSON
ou un redirect, le profil existe quand même et se retrouve par le header
`X-Debug-Token-Link`.

## Les erreurs de débutant

- Installer le profiler en production.
- Croire qu'il n'y a pas de profil quand la barre n'apparaît pas.
- Vider le cache sans préciser l'environment `prod`.

## La fiche du lot

- Deployment : **code, dependencies, migrations, cache**.
- **Aucun outil** prescrit.
- `composer install` **sans dev**, autoloader **optimisé**.
- **Collectors** ramassent, **profiler** stocke, **toolbar** affiche.
- Profiler : **développement seulement**.
- Pas de barre ≠ pas de profil : **`X-Debug-Token-Link`**.
