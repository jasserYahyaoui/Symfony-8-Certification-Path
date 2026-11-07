---
lot: lot-14
verification_status: VERIFIED
reviewed_at: '2026-11-07'
---

## En une phrase

Ce lot couvre la vie quotidienne d'une application : où se règlent ses
parameters (configuration, files `.env`), ce qui se passe quand une erreur
survient, et les outils pour comprendre ce que fait le code.

## L'image de la vie réelle

Pense à une **maison connectée**. Le **thermostat** se règle une fois pour
toutes à l'installation, ou se lit en direct sur un capteur : c'est la
différence entre un parameter et une variable d'environment. Quand un fusible
saute, le boîtier électrique affiche un code et la lumière de secours
s'allume : c'est la **gestion des erreurs**, avec une page d'erreur pour le
visiteur. Et le technicien vient avec son multimètre pour voir ce qui passe
dans les fils : c'est le **debug**, avec `dump()`.

## Notion par notion

### Configuration (including DotEnv and ExpressionLanguage components)

**C'est quoi ?** Les réglages de l'application : des parameters figés à la
compilation, des variables d'environment lues à l'exécution, chargées depuis
les files `.env`, et des expressions d'une ligne avec ExpressionLanguage.

**Dans la vraie vie** La température réglée à l'installation (le parameter) et
celle lue sur le capteur à chaque instant (la variable d'environment).

**Pour l'examen** Les files `.env` se chargent en cascade et le dernier chargé
gagne ; une vraie variable du serveur passe avant eux. En test, `.env.local`
est ignoré. `.env.local.php` remplace tous les files `.env`.

### Error handling

**C'est quoi ?** Toute erreur devient une exception, traitée par une seule
mécanique qui choisit un status code et une page d'erreur.

**Dans la vraie vie** Le boîtier électrique : le fusible qui saute est noté, et
le visiteur voit l'éclairage de secours, pas les fils.

**Pour l'examen** Le statut vient de la configuration, d'un attribute ou d'une
interface ; sinon c'est 500, et le message de l'exception n'est jamais lu pour
le décider. En `dev`, la page d'exception ; en `prod`, une page d'erreur
personnalisable par `error404.html.twig` ou `error.html.twig`.

### Code debugging

**C'est quoi ?** Les outils pour voir ce que fait le code : `dump()`, `dd()`,
et des commands qui montrent la configuration retenue.

**Dans la vraie vie** Le multimètre du technicien : il mesure sans couper le
courant ; `dd()`, c'est couper le courant pour regarder.

**Pour l'examen** `dump()` affiche une valeur et la rend, `dd()` affiche puis
arrête. `debug:config` montre la configuration fusionnée,
`config:dump-reference` toutes les keys possibles, `router:match -v` explique
un 404.

## Les erreurs de débutant

- Croire que `.env.local` est lu pendant les tests.
- Espérer qu'un message d'exception change le status code.
- Laisser un `dump()` en production : la Response est abîmée sans erreur
  visible.

## La fiche du lot

- **Parameter** = compilation, **`%env()%`** = exécution.
- `.env` en cascade : **le dernier chargé gagne**.
- Une **vraie variable** passe avant les `.env`.
- Statut : configuration, attribute, interface, sinon **500**.
- **`dump()`** rend sa valeur, **`dd()`** arrête.
- **`debug:config`** : la configuration fusionnée.
