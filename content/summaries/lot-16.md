---
lot: lot-16
verification_status: VERIFIED
reviewed_at: '2026-11-07'
---

## En une phrase

Traduire une application, c'est remplacer chaque texte par une key, puis
chercher la key dans un catalogue choisi selon la langue du user, avec
une cascade de repli quand une translation manque.

## L'image de la vie réelle

Pense à un **musée** qui prête des audioguides en plusieurs langues. Chaque
œuvre a un numéro : c'est la **key**. Pour chaque langue, une liste dit ce
qu'il faut raconter pour chaque numéro : c'est le **catalogue**. Si la version
en espagnol d'Argentine manque, le guide passe à l'espagnol général, puis à la
langue de secours du musée : c'est la **cascade de repli**. Et si aucun texte
n'existe, le guide annonce simplement le numéro.

## Notion par notion

### Internationalization and localization (Note: Intl component utilities to access ICU data are not included)

**C'est quoi ?** Traduire les textes avec `trans()`, dans des catalogues dont
le nom de file indique le domain, la locale et le format.

**Dans la vraie vie** L'audioguide : on tape le numéro de l'œuvre, il lit le
texte dans ta langue, ou dans une langue proche s'il n'a pas la tienne.

**Pour l'examen** Une key introuvable est rendue telle quelle, sans erreur.
`%name%` est le placeholder classique, `{name}` celui d'ICU MessageFormat,
activé par le suffixe `+intl-icu` dans le nom du file. Quand deux catalogues
fournissent la même key, c'est le dernier chargé qui gagne.

## Les erreurs de débutant

- S'attendre à une erreur pour une key non traduite.
- Écrire `{name}` dans un catalogue sans le suffixe `+intl-icu`.
- Oublier `cache:clear` après avoir ajouté un nouveau catalogue.

## La fiche du lot

- File : **domain.locale.format**, domain par défaut `messages`.
- Key absente : **rendue telle quelle**.
- **`%name%`** classique, **`{name}`** ICU avec **`+intl-icu`**.
- Repli : locale, locale parente, langue, **`fallbacks`**.
- Deux catalogues pour une key : **le dernier chargé gagne**.
