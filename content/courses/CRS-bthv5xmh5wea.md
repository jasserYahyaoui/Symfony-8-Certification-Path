---
id: CRS-bthv5xmh5wea
official_item: OIT-rwavvsx2d7nq
title: "Group sequence"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-28"
format: pedagogical
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

## Avant la règle : le problème à résoudre

L'inscription vérifie deux choses : que le `username` est rempli, puis
que le password n'est pas dans une liste de passwords compromis — un
appel réseau, lent.

Si le nom est vide, appeler le service distant est inutile : la saisie est déjà
refusée. Et afficher en même temps « nom obligatoire » et « password
compromis » noie le user sous des erreurs.

Il faut pouvoir dire : « vérifie d'abord **ceci** ; **seulement si tout
passe**, vérifie **cela** ». C'est une **group sequence**.

## Le fil rouge : un `User` en deux étapes

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

Étape 1 : le group `User` (les constraints par défaut de la class). Étape 2 :
`Strict`, le contrôle coûteux.

## Le cycle complet en un schéma

```text
validate($user)             (ou le group Default)
     │
     ▼  Default = la SÉQUENCE déclarée sur la class
ÉTAPE 1   group « User »   ── une violation ? ──► STOP, on rend ces violations
     │ aucune
     ▼
ÉTAPE 2   group « Strict »  ── violations rendues
     
validate($user, null, ['Strict'])  → Strict SEUL, tout de suite, sans séquence
validate($user, null, ['User'])    → User SEUL, sans séquence

déclaration :  ['Default', …] → GroupDefinitionException (Default interdit)
               [… sans 'User'] → GroupDefinitionException (nom de class obligatoire)
provider    :  ['User', 'Premium', 'Api']     → étapes successives
               [['User', 'Premium'], 'Api']   → User et Premium dans la MÊME étape
```

## Le mécanisme

Une **group sequence** valide les groups **l'un après l'autre**, et
s'arrête au premier qui produit une violation. Les suivants ne sont pas exécutés.

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
même arrêt au premier group en échec : `u` vide ne laisse voir que sa
violation ; `u` rempli laisse passer à `Strict`.

## Le premier effet de bord : `Default` change de sens

Hors séquence, `Default` et le nom de la class désignent la même chose. **Dès
qu'une séquence est déclarée sur la class, ce n'est plus vrai** : `Default`
désigne désormais *la séquence elle-même*.

D'où la règle d'écriture : une séquence de class se déclare avec le **nom de la
class**, jamais avec `Default`.

### Ce que dit la documentation, ce que fait le code

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

| Appel | Ce qui est validé |
|---|---|
| `validate($user)` ou `['Default']` | la séquence : `User`, puis `Strict` si `User` passe |
| `['User']` | `User` seul |
| `['Strict']` | `Strict` seul, même si `User` échouerait |

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

## Concrètement dans ton application

- Un contrôle coûteux (service distant, appel à la base de données) : son propre group,
  placé **après** le group du nom de la class.
- Une séquence de class : `#[Assert\GroupSequence(['User', 'Strict'])]`, jamais
  `Default`.
- Un form : `validation_groups` peut recevoir une `GroupSequence`.
- Des règles qui dépendent de l'object (offre gratuite ou payante) :
  `GroupSequenceProviderInterface`.
- Un test qui valide `['Strict']` directement ne teste **pas** la séquence.

## À comprendre

- **Pourquoi s'arrêter au premier échec ?** Pour ne pas payer un contrôle
  coûteux, ni afficher des erreurs qui découlent d'une première.
- **Pourquoi `Default` est-il interdit dans la séquence ?** Parce que, sur une
  class qui déclare une séquence, `Default` **est** la séquence : il se
  contiendrait lui-même.
- **Pourquoi valider `Strict` contourne-t-il la séquence ?** Parce que la
  séquence remplace le group `Default`, pas les autres : demander un group, c'est
  demander ce group-là.
- **Pourquoi des sous-tableaux ?** Pour regrouper des contrôles de même niveau
  dans une étape, et ne séparer que ce qui doit vraiment attendre.

## À mémoriser

- Séquence = étapes ; la première étape en échec arrête tout.
- Sur la class, `Default` = la séquence ; déclarer avec le **nom de la class**.
- `Default` dans une séquence de class : `GroupDefinitionException` (pas de
  récursion) ; nom de class absent : `GroupDefinitionException`.
- `validate(…, ['Strict'])` ou `['User']` : pas de séquence.
- Provider : à plat = une étape par group ; sous-tableau = une étape commune.
- Moyen mnémotechnique : **« D'abord le simple, ensuite le cher »**.

## Pièges d'examen

- `Default` dans une séquence de class : **exception**, pas récursion —
  déclarer avec le **nom de la class**, obligatoire.
- Valider explicitement un group de la séquence, ou le nom de la class,
  **ignore** la séquence.
- Un array **imbriqué** réunit des groups dans une même étape ; à plat, chacun
  est sa propre étape.
- La séquence s'arrête à la **première étape** en échec, pas à la première
  constraint.
- Une `GroupSequence` passée à `validate()` accepte `Default` : le contrôle ne
  s'applique qu'à la séquence déclarée sur la class.

## Application : trois scénarios

**Scénario 1 — Le contrôle distant.** Le password est vérifié contre un
service externe, à éviter si le form est déjà invalide. *Solution.*
`#[Assert\GroupSequence(['User', 'Strict'])]`, et la constraint distante dans
`Strict`.

**Scénario 2 — L'exception au démarrage.** Une class déclare
`#[Assert\GroupSequence(['Default', 'Strict'])]` et chaque validation lève
`GroupDefinitionException`. *Solution.* Remplacer `Default` par le nom de la
class.

**Scénario 3 — Les comptes payants.** Les règles `Premium` ne s'appliquent
qu'aux clients payants, et doivent être signalées en même temps que les règles de base.
*Solution.* Un provider qui rend `[['User', 'Premium'], 'Api']` pour un client payant,
`['User', 'Api']` sinon.

## QCM d'entraînement

Six questions sur les group sequences. Fixe ta lettre avant d'ouvrir la
correction.

**Question 1.** `Order` déclare `#[Assert\GroupSequence(['Order', 'Payment'])]`. `$reference` (sans group) est vide, et une constraint `Payment` échouerait aussi. Que rend `validate($order)` ?

- **A.** Les deux violations, dans l'ordre de la séquence
- **B.** Seulement celle de `$reference`
- **C.** Seulement celle de `Payment`, l'étape la plus récente
- **D.** Aucune : `Default` n'est pas dans la séquence

<details>
<summary>Correction</summary>

**Bonne réponse : B.** La première étape (`Order`) échoue, la séquence s'arrête
(exécuté avec `username`).

A est le piège : c'est le comportement sans séquence. C inverse l'ordre. D
oublie que `Default` **est** la séquence sur cette class.

</details>

**Question 2.** Sur `Invoice`, un développeur écrit la séquence de class `['Default', 'Payment']`, pensant y faire passer d'abord les règles par défaut. Que produit la première validation d'une `Invoice` ?

- **A.** Une récursion infinie, comme l'annonce la documentation
- **B.** La séquence fonctionne, `Default` valant le nom de la class
- **C.** `Default` est ignoré, seul `Strict` est validé
- **D.** Une `GroupDefinitionException` qui refuse `Default`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Exécuté : « The group "Default" is not allowed in group
sequences. ».

A est le piège : c'est ce qu'annonce `sequence_provider.rst`, mais
`ClassMetadata::setGroupSequence()` refuse la séquence avant toute récursion. B
et C inventent des comportements.

</details>

**Question 3.** La class `Order` déclare `['Order', 'Payment']`, et le code appelle `validate($order, null, ['Payment'])`, `$reference` étant vide. Que rend l'appel ?

- **A.** La violation de `Payment` seule
- **B.** La violation de `$reference` seule, la séquence commençant par `Order`
- **C.** Les deux violations
- **D.** Aucune : `Payment` ne peut être validé qu'à travers la séquence

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Demander un group le valide seul, immédiatement ; la
séquence n'est attachée qu'à `Default` (exécuté avec `Strict`).

B est le piège : la séquence ne se déroule pas ici. C et D inventent des
comportements.

</details>

**Question 4.** Un provider rend `[['User', 'Premium'], 'Api']`, et chacun des trois groups contient une violation. Quelles violations remontent ?

- **A.** Celles de `User` seulement
- **B.** Celles des trois groups
- **C.** Celles de `User` et de `Premium`
- **D.** Celles d'`Api` seulement, la dernière étape

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Le sous-tableau forme une seule étape : `User` et
`Premium` sont validés ensemble, puis la séquence s'arrête avant `Api`
(exécuté).

A est le résultat **à plat**. B ignore l'arrêt. D inverse la logique.

</details>

**Question 5.** Une class déclare `#[Assert\GroupSequence(['Strict'])]`, sans son propre nom. Que se passe-t-il ?

- **A.** Une `GroupDefinitionException` : le nom de la class manque
- **B.** Seul `Strict` est validé, à chaque validation par défaut
- **C.** Le nom de la class est ajouté automatiquement en tête
- **D.** La séquence est ignorée, et `Default` est validé normalement

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Exécuté : « The group "MissingClass" is missing in the
group sequence. ». Une séquence statique **doit** contenir le nom de la class.

B, C et D inventent des comportements ; seul un **provider** échappe à ce
contrôle.

</details>

**Question 6.** Un test appelle `validate($user, null, new GroupSequence(['Default', 'Strict']))` sur une class **sans** séquence déclarée. Que se passe-t-il ?

- **A.** `GroupDefinitionException`, comme sur la class
- **B.** `Default`, puis `Strict` si `Default` passe
- **C.** `Default` est ignoré, et `Strict` validé seul
- **D.** Une récursion infinie

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Une séquence passée à `validate()` n'est pas contrôlée
comme une séquence de class ; exécuté, elle s'arrête au premier group en échec.

A est le piège : le refus de `Default` ne vise que la séquence déclarée sur la
class. C et D inventent des comportements.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- Une séquence valide des **étapes** dans l'ordre et s'arrête à la **première
  en échec**.
- Sur la class, `Default` **est** la séquence : déclarer avec le **nom de la
  class**.
- `Default` dans une séquence de class → `GroupDefinitionException` ; nom de la
  class absent → `GroupDefinitionException`.
- Demander `['Strict']` ou `['User']` **contourne** la séquence.
- Provider : à plat, une étape par group ; **sous-tableau = une étape**.
- Une `GroupSequence` passée à `validate()` accepte `Default`.

**En une phrase, pour tout reconstruire :**

```text
Default = la séquence → étape 1 (nom de la class) → si OK, étape 2 (Strict) → …
  ; demander un group précis = ce group seul, sans séquence
```

## Sources officielles

- [How to Sequentially Apply Validation Groups](https://github.com/symfony/symfony-docs/blob/8.0/validation/sequence_provider.rst)
- [Validator 8.0, `ClassMetadata::setGroupSequence()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Mapping/ClassMetadata.php)
