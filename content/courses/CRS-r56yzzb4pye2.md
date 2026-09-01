---
id: CRS-r56yzzb4pye2
official_item: OIT-dp7w7s85wxjg
title: "Global variables"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/templates.rst"
    anchor: "the-app-global-variable"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Connaître les variables disponibles dans **tous** les gabarits sans qu'un
contrôleur les passe, et savoir en déclarer.

## La variable `app`

TwigBundle expose une variable `app` dans chaque gabarit. C'est la voie normale
d'accès à l'état de la requête depuis un gabarit.

| Propriété | Contenu |
|---|---|
| `app.user` | l'utilisateur courant, ou `null` s'il n'est pas authentifié |
| `app.request` | l'objet `Request` courant |
| `app.session` | la session, ou `null` s'il n'y en a pas |
| `app.flashes` | les messages flash ; `app.flashes('notice')` filtre par type |
| `app.environment` | `dev`, `prod`… |
| `app.debug` | le mode debug |
| `app.token` | le jeton de sécurité |
| `app.current_route` | le nom de la route courante |
| `app.current_route_parameters` | ses paramètres |
| `app.locale` | la locale courante |
| `app.enabled_locales` | les locales activées |

Deux détails utiles. `app.current_route` équivaut à
`app.request.attributes.get('_route')` — la forme courte évite de traverser la
requête. Et `app.session` peut valoir `null` : lire la session depuis un gabarit
la démarre, avec les conséquences que cela a sur la cachabilité.

## Déclarer ses propres globales

```yaml
# config/packages/twig.yaml
twig:
    globals:
        ga_tracking: 'UA-xxxxx-x'
```

La valeur devient `{{ ga_tracking }}` partout, sans qu'aucun contrôleur ne la
passe. C'est adapté à une constante d'application ; ce n'est pas un substitut au
passage de variables, qui reste la voie normale pour les données d'une page.

## Le piège

`app.flashes` **consomme** les messages, comme toute lecture du sac de flashs.
Les afficher deux fois dans une page en fait disparaître la moitié ; la lecture
non consommatrice est `app.session.flashbag.peekAll()`.

## Points clés

- `app` est fournie partout par TwigBundle ; onze propriétés à connaître.
- `app.current_route` est le raccourci de `app.request.attributes.get('_route')`.
- `twig.globals` déclare une variable disponible dans tous les gabarits.
- `app.flashes` consomme les messages.

## Sources officielles

- [Symfony Templates, « The App Global Variable »](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/templates.rst)
