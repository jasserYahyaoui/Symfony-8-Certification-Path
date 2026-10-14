---
id: CRS-bthv5xmh5wea
official_item: OIT-rwavvsx2d7nq
title: "Group sequence"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-09-29"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/validation/sequence_provider.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/validation/sequence_provider.rst"
    symbol_or_lines: '"How to Sequentially Apply Validation Groups"; and the warning "you have to use the {ClassName} (e.g. User) group when specifying a group sequence. When using Default, you get an infinite recursion (as the Default group references the group sequence, which will contain the Default group which references the same group sequence, ...)"'
    branch: "8.0"
    verified_at: "2026-09-01"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/Validator/Mapping/ClassMetadata.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Mapping/ClassMetadata.php"
    repository: "symfony/symfony"
    branch: "8.0"
    symbol_or_lines: "ClassMetadata::setGroupSequence(), Default not allowed"
    verified_at: "2026-09-29"
outcome_sections:
  OUT-xyz5371z2twb: "Le mécanisme"
  OUT-b5t21ax5y3aq: "Le premier effet de bord : `Default` change de sens"
  OUT-w45gx8dhzkr1: "Le deuxième : valider un group de la séquence la contourne"
  OUT-kbx7xfhhm46j: "Le troisième : la séquence peut être dynamique"
---

## Objectif

Ordonner les validation groups pour qu'un group coûteux ne s'exécute que si
le précédent a réussi — et maîtriser les effets de bord que cet ordre introduit.

## Prérequis

Les validation groups, et la distinction entre `Default` et le group portant
le nom de la class.

## Le mécanisme

Une **group sequence** valide les groups **l'un après l'autre**, et
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

Le group `Strict` n'est évalué que si tout `User` est valide. Exécuté avec
`symfony/validator` 8.0.15 : `username` vide, seule sa violation apparaît ;
`username` rempli, la violation de `Strict` apparaît. L'intérêt est double : ne
pas noyer le user sous des erreurs dérivées, et ne pas payer une
vérification lente — un appel réseau, une Request — sur une donnée déjà mal
formée.

La même déclaration existe en PHP par `$metadata->setGroupSequence([...])`, et
dans un form par l'option `validation_groups`. Une séquence peut aussi
être passée **directement** à `validate()` :
`validate($user, null, new GroupSequence(['Default', 'Strict']))` — exécuté,
même arrêt au premier group en échec.

## Le premier effet de bord : `Default` change de sens

Hors séquence, `Default` et le nom de la class désignent la même chose. **Dès
qu'une séquence est déclarée sur la class, ce n'est plus vrai** : `Default`
désigne désormais *la séquence elle-même*.

D'où la règle d'écriture : une séquence de class se déclare avec le **nom de la
class**, jamais avec `Default`.

## Ce que dit la documentation, ce que fait le code

`sequence_provider.rst` (8.0) annonce qu'inclure `Default` produit une
**récursion infinie**. Le code refuse la séquence avant toute récursion :
`ClassMetadata::setGroupSequence()` contrôle son contenu. Exécuté :

| Séquence déclarée sur la class | Résultat |
|---|---|
| `['Default', 'Strict']` | `GroupDefinitionException` : « The group "Default" is not allowed in group sequences. » |
| `['Strict']`, sans le nom de la class | `GroupDefinitionException` : « The group "MissingClass" is missing in the group sequence. » |
| `['Seq', 'Strict']` | séquence valide |

Le nom de la class est donc **obligatoire** dans une séquence statique, et
`Default` **interdit**. La règle d'écriture reste la même ; seule la sanction
diffère de ce qu'annonce la page. Une séquence passée à `validate()`, elle, n'est
pas soumise à ce contrôle : `Default` y est accepté.

## Le deuxième : valider un group de la séquence la contourne

Appeler `validate($user, null, ['Strict'])` ne déroule **pas** la séquence : cela
valide `Strict` seul, immédiatement, sans passer par `User`. La séquence n'est
attachée qu'au group `Default`. Exécuté : `['Strict']` donne la seule violation
de `Strict`, même avec `username` vide.

Le group portant le nom de la class ne la déclenche pas non plus : valider
`['Seq']` n'applique que les constraints de ce group. Seul `Default` — ou
l'absence de group — déroule la séquence.

Autrement dit, une séquence protège la validation *par défaut*, pas chaque group
pris isolément.

## Le troisième : la séquence peut être dynamique

Quand l'ordre dépend de l'object — un compte gratuit et un compte payant n'ont pas
les mêmes règles — la class implémente `GroupSequenceProviderInterface` et porte
l'attribute `#[Assert\GroupSequenceProvider]` :

```php
public function getGroupSequence(): array|GroupSequence
{
    return ['User', 'Premium', 'Api'];      // à plat
    // return [['User', 'Premium'], 'Api']; // imbriqué
}
```

La forme de l'array change le comportement, et c'est le détail à retenir.
Exécuté avec une violation dans chacun des trois groups :

| Retour du provider | Violations |
|---|---|
| `['User', 'Premium', 'Api']` | celles de `User` seulement |
| `[['User', 'Premium'], 'Api']` | celles de `User` **et** de `Premium` |

- **à plat**, un échec dans `User` arrête tout : ni `Premium` ni `Api` ;
- **imbriqué**, les groups d'un même sous-tableau sont validés **ensemble** :
  `Premium` est validé et ses violations remontent, mais `Api` non.

Le sous-tableau est donc une étape ; la séquence est une suite d'étapes.

Un provider n'est pas contrôlé comme une séquence statique : exécuté, un retour
`['Premium', 'Api']`, sans le nom de la class, ne lève rien.

## Pièges d'examen

- `Default` dans une séquence de class : **exception**, pas récursion —
  déclarer avec le **nom de la class**, obligatoire.
- Valider explicitement un group de la séquence, ou le nom de la class,
  **ignore** la séquence.
- Un array **imbriqué** group des groups dans une même étape ; à plat, chacun
  est sa propre étape.
- La séquence s'arrête à la **première étape** en échec, pas à la première
  constraint.

## Points clés

- Les groups sont évalués dans l'ordre ; le premier échec arrête la suite.
- Sous séquence, `Default` désigne la séquence : déclarer avec le nom de class.
- `ClassMetadata` refuse `Default` et exige le nom de la class.
- Valider un group nommé de la séquence court-circuite l'ordre.
- `GroupSequenceProviderInterface` rend la séquence dépendante de l'object ;
  imbriquer un sous-tableau valide ses groups ensemble.

## Sources officielles

- [How to Sequentially Apply Validation Groups](https://github.com/symfony/symfony-docs/blob/8.0/validation/sequence_provider.rst)
- [Validator 8.0, `ClassMetadata::setGroupSequence()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Mapping/ClassMetadata.php)
