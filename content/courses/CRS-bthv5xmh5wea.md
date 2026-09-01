---
id: CRS-bthv5xmh5wea
official_item: OIT-rwavvsx2d7nq
title: "Group sequence"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-01"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/validation/sequence_provider.rst"
    branch: "8.0"
    verified_at: "2026-09-01"
---

## Objectif

Ordonner les groupes de validation pour qu'un groupe coûteux ne s'exécute que si
le précédent a réussi — et maîtriser les trois effets de bord que cet ordre
introduit.

## Prérequis

Les groupes de validation, et la distinction entre `Default` et le groupe portant
le nom de la classe.

## Le mécanisme

Une **séquence de groupes** valide les groupes **l'un après l'autre**, et
s'arrête au premier qui produit une violation. Les suivants ne sont pas exécutés.

```php
#[Assert\GroupSequence(['User', 'Strict'])]
class User
{
    #[Assert\NotBlank]
    private string $username;

    #[Assert\IsTrue(groups: ['Strict'])]
    public function isPasswordSafe(): bool { /* ... */ }
}
```

Le groupe `Strict` n'est évalué que si tout `User` est valide. L'intérêt est
double : ne pas noyer l'utilisateur sous des erreurs dérivées, et ne pas payer
une vérification lente — un appel réseau, une requête — sur une donnée déjà
mal formée.

La même déclaration existe en PHP par `$metadata->setGroupSequence([...])`, et
dans un formulaire par l'option `validation_groups`.

## Le premier effet de bord : `Default` change de sens

Hors séquence, `Default` et le nom de la classe désignent la même chose. **Dès
qu'une séquence existe, ce n'est plus vrai** : `Default` désigne désormais *la
séquence elle-même*.

D'où la règle d'écriture : une séquence se déclare avec le **nom de la classe**,
jamais avec `Default`. Écrire `GroupSequence(['Default', 'Strict'])` produit une
**récursion infinie** — `Default` référence la séquence, qui contient `Default`,
qui référence la séquence.

## Le deuxième : valider un groupe de la séquence la contourne

Appeler `validate($user, null, ['Strict'])` ne déroule **pas** la séquence : cela
valide `Strict` seul, immédiatement, sans passer par `User`. La séquence n'est
attachée qu'au groupe `Default`.

Autrement dit, une séquence protège la validation *par défaut*, pas chaque groupe
pris isolément.

## Le troisième : la séquence peut être dynamique

Quand l'ordre dépend de l'objet — un compte gratuit et un compte payant n'ont pas
les mêmes règles — la classe implémente `GroupSequenceProviderInterface` et porte
l'attribut `#[Assert\GroupSequenceProvider]` :

```php
public function getGroupSequence(): array|GroupSequence
{
    return ['User', 'Premium', 'Api'];      // à plat
    // return [['User', 'Premium'], 'Api']; // imbriqué
}
```

La forme du tableau change le comportement, et c'est le détail à retenir :

- **à plat**, un échec dans `User` arrête tout : ni `Premium` ni `Api` ;
- **imbriqué**, les groupes d'un même sous-tableau sont validés **ensemble** :
  si `User` échoue, `Premium` est quand même validé et ses violations
  remontent, mais `Api` non.

Le sous-tableau est donc une étape ; la séquence est une suite d'étapes.

## Pièges d'examen

- Déclarer la séquence avec `Default` = récursion infinie ; utiliser le **nom de
  la classe**.
- Valider explicitement un groupe de la séquence **ignore** la séquence.
- Un tableau **imbriqué** groupe des groupes dans une même étape ; à plat, chacun
  est sa propre étape.
- La séquence s'arrête à la **première étape** en échec, pas à la première
  contrainte.

## Points clés

- Les groupes sont évalués dans l'ordre ; le premier échec arrête la suite.
- Sous séquence, `Default` désigne la séquence : déclarer avec le nom de classe.
- Valider un groupe nommé de la séquence court-circuite l'ordre.
- `GroupSequenceProviderInterface` rend la séquence dépendante de l'objet ;
  imbriquer un sous-tableau valide ses groupes ensemble.

## Sources officielles

- [How to Sequentially Apply Validation Groups](https://raw.githubusercontent.com/symfony/symfony-docs/8.0/validation/sequence_provider.rst)
