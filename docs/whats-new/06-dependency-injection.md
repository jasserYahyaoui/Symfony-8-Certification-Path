# Dependency Injection en 8.0

## Objectif

Ce que la 8.0 change dans le conteneur. Page d'enrichissement : hors périmètre
officiel, hors couverture.

## Deux attributs remplacés

*« Remove `#[TaggedIterator]` and `#[TaggedLocator]` attributes, replaced by
`#[AutowireLocator]` and `#[AutowireIterator]` »*.

Attention à l'appariement, qui n'est pas dans l'ordre d'écriture : c'est
`TaggedIterator` → `AutowireIterator`, et `TaggedLocator` → `AutowireLocator`.
La ligne du journal cite les remplaçants dans l'ordre inverse des remplacés, ce
qui suffit à faire répondre de travers.

## Le tag `!tagged` disparaît

*« Remove `!tagged` tag, use `!tagged_iterator` instead »*. Le raccourci
historique en YAML n'existe plus ; seule la forme explicite subsiste.

## Le XML s'en va aussi

*« Remove support for the XML configuration format »*, et avec lui
`ExtensionInterface::getXsdValidationBasePath()` et `getNamespace()`, retirées
**sans alternative** — le journal le dit ainsi.

Disparaît également le format PHP fluide pour la configuration sémantique : on
instancie les constructeurs en ligne, avec le tableau de configuration en
argument, et on les retourne.

## Un service mal déclaré devient une erreur

*« Registering a service without a class when its id is a non-existing FQCN
throws an error »*.

Un identifiant qui ressemble à un nom de classe pleinement qualifié mais ne
correspond à aucune classe existante n'est plus toléré. C'est typiquement la
faute de frappe dans un identifiant de service : elle était silencieuse, elle
casse maintenant la compilation du conteneur.

## Deux arguments ajoutés

`ContainerBuilder::findTaggedResourceIds()` reçoit `$throwOnAbstract`, et
`registerAliasForArgument()` reçoit `$target`.
`getAutoconfiguredAttributes()` est remplacée par
`getAttributeAutoconfigurators()`.

## Pièges d'examen

**L'appariement des attributs s'inverse** : `TaggedIterator` → `AutowireIterator`.

**`getXsdValidationBasePath()` n'a pas de remplaçant** — le journal précise
« without alternatives ».

**Un id de service qui imite un FQCN inexistant fait échouer la compilation**,
alors qu'il passait avant.

## Points clés

- `#[AutowireIterator]` et `#[AutowireLocator]` remplacent les deux attributs
  `Tagged*`.
- `!tagged` disparaît au profit de `!tagged_iterator`.
- Plus de format XML ; deux méthodes d'`ExtensionInterface` retirées sans
  alternative.
- Un service sans classe dont l'id est un FQCN inexistant lève une erreur.

## Sources officielles

- [DependencyInjection, journal 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/CHANGELOG.md)

## Flashcards

### Mémorisation

<details>
<summary>Quels attributs remplacent `#[TaggedIterator]` et `#[TaggedLocator]` ?</summary>

**`#[AutowireIterator]`** et **`#[AutowireLocator]`**, dans cet ordre.

Le journal cite les remplaçants dans l'ordre inverse des remplacés — d'où l'erreur d'appariement.

</details>

<details>
<summary>Que remplace le tag `!tagged` en 8.0 ?</summary>

**`!tagged_iterator`.** Le raccourci historique est retiré.

Seule la forme explicite subsiste.

</details>

### Compréhension

<details>
<summary>Pourquoi `getXsdValidationBasePath()` est-elle retirée *sans alternative* ?</summary>

Parce qu'elle ne servait qu'au format XML, lui-même retiré. Sans XML, il n'y a rien à valider par un schéma.

Le journal précise « without alternatives », ce qui est rare et donc informatif.

</details>

<details>
<summary>Pourquoi faire échouer un service sans classe dont l'id ressemble à un FQCN inexistant ?</summary>

Parce que c'est presque toujours une faute de frappe dans un identifiant. L'erreur était auparavant silencieuse, et le service n'était jamais celui qu'on croyait.

L'échec est déplacé à la compilation du conteneur.

</details>

### Application

<details>
<summary>Un service est déclaré sous l'id `App\Service\Maler` sans classe, et cette classe n'existe pas. Résultat en 8.0 ?</summary>

Une erreur à la compilation du conteneur.

En 7.x, la faute de frappe passait et produisait un service fantôme.

</details>

<details>
<summary>Un argument porte `#[TaggedLocator('app.handler')]`. Par quoi le remplacer ?</summary>

Par **`#[AutowireLocator]`** — locator vers locator.

L'erreur fréquente est de croiser avec `AutowireIterator`.

</details>

### Pièges

<details>
<summary>Piège : « `TaggedIterator` devient `AutowireLocator`. »</summary>

Non, l'appariement se conserve : `TaggedIterator` → `AutowireIterator`, `TaggedLocator` → `AutowireLocator`.

L'ordre de citation du journal induit précisément cette inversion.

</details>

<details>
<summary>Piège : « la configuration sémantique en PHP fluide reste possible. »</summary>

Non : le format fluide est retiré. On instancie les constructeurs en ligne avec le tableau de configuration en argument, et on les retourne.

C'est un changement d'écriture, pas seulement de style.

</details>
