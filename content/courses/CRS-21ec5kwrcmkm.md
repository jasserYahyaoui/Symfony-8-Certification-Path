---
id: CRS-21ec5kwrcmkm
official_item: OIT-vk57zg2wpep7
title: "Backward compatibility promise"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/contributing/code/bc.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/contributing/code/bc.rst"
    anchor: "using-symfony-code"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    verified_at: "2026-09-01"
---

## Objectif

Savoir ce que la promesse de rétrocompatibilité garantit **à l'utilisateur de
Symfony**, et surtout ce qu'elle ne garantit pas.

## Le principe

La promesse suit le *semantic versioning* : seule une version **majeure** peut
casser la compatibilité. Une version mineure ajoute des fonctionnalités et peut
déprécier, jamais casser.

Le point important est que la promesse n'est pas globale : elle dépend de la
**manière** dont on utilise le code.

## Utiliser, étendre, implémenter

**Utiliser** est toujours couvert : typer un argument avec une classe ou une
interface Symfony, instancier, appeler une méthode publique, lire une propriété
publique. **Implémenter une interface** l'est aussi.

**Étendre une classe** est couvert pour l'essentiel — accéder à une propriété
protégée, appeler ou surcharger une méthode publique ou protégée. Le tableau
officiel répond **non** à quatre lignes, et elles ne se valent pas :

| Dans une classe que vous étendez… | Garanti ? |
|---|---|
| ajouter une **nouvelle propriété** | **non** |
| ajouter une **nouvelle méthode** | **non** |
| appeler une méthode privée par réflexion | **non** |
| accéder à une propriété privée par réflexion | **non** |

Les **deux premières** vous concernent en écrivant du code ordinaire : si vous
ajoutez `getFoo()` et que Symfony ajoute `getFoo()` avec une autre signature dans
une version mineure, la collision est inévitable. Le risque vous appartient.

Les **deux dernières** ne sont pas propres à l'héritage : le privé atteint par
réflexion sort de l'API publique, quelle que soit la classe.

## Les traits, et leur asymétrie avec les classes

Le document leur consacre une section entière, et son tableau ne répond
**jamais non** : le trait lui-même, ses propriétés et ses méthodes — publiques,
protégées **ou privées** — son usage pour implémenter une interface, une
méthode abstraite ou une classe abstraite, tout est garanti.

Le contraste avec les classes est net : le privé d'une **classe** n'est jamais
garanti, le privé d'un **trait** l'est toujours. La raison est structurelle —
un trait importé fait partie de *votre* classe. Seule exception : `@internal`.

## Ajouter un argument : toujours en dernier

Un argument ne s'ajoute à une méthode publique **que s'il est le dernier**. Le
procédé explique une bizarrerie qu'on croise dans le code du framework :

```php
public function say(string $text, /* bool $stripWhitespace = true */): void
{
    $stripWhitespace = 2 <= \func_num_args() ? func_get_arg(1) : false;
}
```

Argument **en commentaire** dans la signature, documenté en PHPDoc, lu par
`func_num_args()`. Le défaut retenu est celui qui **préserve** le comportement
actuel — ici `false`, quand l'argument commenté annonce `true` pour plus tard.

## Les trois exclusions

Sont **hors** de la promesse :

- ce qui porte `@internal` — classe, interface, trait, méthode, propriété — et
  tout ce qui vit dans un espace de noms `*\Tests\` ;
- les **fonctionnalités expérimentales**, marquées `@experimental`. Une
  fonctionnalité ne peut le rester **qu'une seule version mineure** — le noyau
  de l'équipe peut prolonger d'**une** de plus au cas par cas — et ne peut
  **jamais** être introduite dans une version **LTS**. Tant qu'elle l'est, le
  `CHANGELOG` doit expliquer chaque rupture et la façon de migrer ;
- les traductions internes de sécurité et de validation.

Une rupture est également tolérée lorsqu'elle est nécessaire pour corriger une
faille de sécurité.

## Le piège des arguments nommés

Les **noms de paramètres** ne sont couverts que pour les **constructeurs de
classes d'attribut**. Partout ailleurs, `$service->method(timeout: 5)` peut
casser en montant d'une mineure : le nom du paramètre peut changer.

## final et @final

Le mot-clé `final` interdit l'extension. L'annotation `@final` marque la même
intention **sans l'imposer techniquement** : le code fonctionne, mais l'étendre
sort de la promesse. `@final since Symfony x.y` signale une transition — la
classe n'est pas encore considérée finale.

## Pièges d'examen

**« Étendre est couvert » est vrai à quatre lignes près.** Ajouter une
**propriété** ou une **méthode** à une classe Symfony étendue n'est pas
garanti : Symfony peut introduire le même nom en mineure. Les deux autres
lignes visent le privé par réflexion, jamais garanti.

**Les arguments nommés ne sont pas couverts**, sauf pour les constructeurs de
classes d'attribut.

**`@final` n'interdit rien techniquement.** Le code qui étend une classe `@final`
fonctionne — il sort simplement de la promesse. Seul le mot-clé `final` empêche
l'extension.

**Privé d'un trait ≠ privé d'une classe.** Le premier est garanti, le second
jamais. Le trait devient votre code ; la classe reste celle de Symfony.

**Un argument ne s'ajoute qu'en dernière position.** Une signature Symfony qui
porte un argument en commentaire n'est pas un oubli : c'est le procédé
officiel.

## Tips d'examen

**Trois verbes, trois réponses.** Utiliser → oui. Implémenter → oui. Étendre →
oui, sauf ajouter une propriété ou une méthode.

**« Une seule mineure » est le chiffre de l'expérimental**, et « jamais en
LTS » sa seconde moitié.

## Points clés

- Utiliser et implémenter : couverts. Étendre : couvert **sauf** ajout de
  propriété ou de méthode.
- `@internal`, expérimental et traductions internes sont hors promesse.
- Arguments nommés garantis uniquement pour les constructeurs d'attributs.
- `@final` marque l'intention ; `final` l'impose.
- Traits : tout est garanti, **privé compris** ; seule exception, `@internal`.
- `@experimental` : une mineure seulement, jamais en LTS.
- Un argument ne s'ajoute qu'en **dernier**, par signature commentée et
  `func_num_args()`.

## Sources officielles

- [Our Backward Compatibility Promise](https://github.com/symfony/symfony-docs/blob/8.0/contributing/code/bc.rst)
