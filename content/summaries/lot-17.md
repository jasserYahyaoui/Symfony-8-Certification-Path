---
lot: lot-17
verification_status: VERIFIED
reviewed_at: '2026-11-07'
---

## En une phrase

Symfony peut placer devant l'application un reverse proxy écrit en PHP, qui
garde des Responses et les resservit sans réveiller l'application, selon les
headers de cache que le controller déclare.

## L'image de la vie réelle

Pense à une **boulangerie avec une vitrine**. Le boulanger cuit une fournée de
croissants et les met en vitrine : tant qu'ils sont frais, la vendeuse les
sert sans retourner au fournil. La vitrine, c'est le **reverse proxy** ;
la mention « frais jusqu'à 11 h », c'est l'**expiration** ; demander au
boulanger « ceux-là sont-ils encore bons ? », c'est la **validation**. Et la
vendeuse note sur chaque sac « vitrine » ou « fournil » : c'est le header
`X-Symfony-Cache`.

## Notion par notion

### HTTP Caching (reverse proxies, expiration, validation) Note: ESI (Edge Side Includes) is not included

**C'est quoi ?** Le reverse proxy de Symfony, activé par
`framework.http_cache`, l'attribute `#[Cache]` qui déclare la politique de
cache d'une action, et la trace `X-Symfony-Cache`.

**Dans la vraie vie** La vitrine de la boulangerie : seul le client qui demande
un produit absent de la vitrine fait déranger le boulanger.

**Pour l'examen** Seules les safe methods passent par le cache, et sa key est
l'URI. Une Request qui porte un `Cookie` ou un header `Authorization` rend la
Response privée par défaut. Si le controller fixe lui-même les headers, il
l'emporte sur `#[Cache]`.

## Les erreurs de débutant

- Croire qu'un `POST` peut être servi depuis le cache.
- Oublier qu'un cookie rend la Response privée.
- Attendre du reverse proxy qu'il invalide une page tout seul quand les
  données changent.

## La fiche du lot

- **`framework.http_cache: true`** : reverse proxy en PHP.
- **`#[Cache]`** déclare, le **controller** l'emporte.
- Cache : **safe methods** seulement, key = **l'URI**.
- `Cookie` ou `Authorization` → **`private`**.
- **`X-Symfony-Cache`** dit d'où vient la Response.
