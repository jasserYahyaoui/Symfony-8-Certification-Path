---
id: CRS-bshgpd44ykba
official_item: OIT-n0zfkcr189af
title: "Traits"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-09"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/php/doc-en/master/language/oop5/traits.xml"
    readable_url: "https://github.com/php/doc-en/blob/master/language/oop5/traits.xml"
    repository: "php/doc-en"
    branch: "master"
    symbol_or_lines: "sections Precedence and Conflict Resolution"
    verified_at: "2026-09-01"
outcome_sections:
  OUT-xk68jha9m4nk: "L'ordre de précédence"
  OUT-w9w3hpwesby1: "Résolution de conflit"
  OUT-x8c9nce1wqh5: "Ce qu'un trait apporte"
---

## Objectif

Composer des traits, résoudre un conflit, et connaître l'ordre de précédence —
qui est contre-intuitif.

## L'essentiel

Un trait copie des membres dans une class, sans créer de type ni
d'inheritance. Sa précédence est contre-intuitive : la **class** gagne sur le
trait, qui gagne sur la **class parente**. Deux traits qui fournissent la même
method provoquent une **erreur fatale** tant que `insteadof` n'a pas tranché ;
`as` ne fait qu'ajouter un nom ou changer une visibilité.

## Le schéma

```text
contenu      properties, methods, methods statiques, methods abstraites, constantes (8.2)
             jamais instancié : new T → Error « Cannot instantiate trait »
précédence   class courante  >  trait  >  class parente
conflit      use Csv, Json { Csv::export insteadof Json; Json::export as exportJson; }
             insteadof → écarte · as → alias (l'original reste) · as protected → change la visibilité
static       une property statique de trait : une copie par class qui l'utilise
type         instanceof T → false · class_uses() le voit · pour typer : une interface
```

## L'exemple

```php
trait Timestampable
{
    private ?\DateTimeImmutable $updatedAt = null;

    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    abstract public function getId(): int;   // exigence sur la classe hôte
}

class Article
{
    use Timestampable;

    public function getId(): int { return 1; }
}
```

## Ce qu'un trait apporte

Un trait est une **unité de réutilisation horizontale** : du code partagé entre
classes sans relation d'inheritance. Il porte properties, methods statiques,
methods abstraites — que la class qui l'importe doit implémenter — et constantes. Il ne
s'instancie pas. Une property statique de trait est copiée : chaque class qui
l'utilise a la sienne.

## L'ordre de précédence

Une method définie dans la class **écrase** celle du trait ; le trait écrase
celle de la class parente. L'intuition « le trait, plus proche, gagne sur la
class » est fausse.

## Résolution de conflit

```php
class Reporter
{
    use Csv, Json {
        Csv::export insteadof Json;   // on choisit
        Json::export as exportJson;   // on garde l'autre sous un alias
    }
}
```

`insteadof` **écarte** une implémentation. `as` crée un **alias** sans rien
écarter, et peut changer la visibilité : `Csv::export as protected internalExport;`
ajoute un alias protégé, `hi as protected;` rend la method elle-même protégée.

## Pièges d'examen

**Le conflit n'est pas résolu automatiquement.** Sans `insteadof`, c'est une
erreur fatale — pas un choix silencieux du premier trait.

**`as` n'écarte rien.** Il ajoute un nom ; l'original reste. Utiliser `as` seul
face à un conflit ne le résout pas.

**Un trait n'est pas un type.** `$x instanceof Timestampable` compile, mais rend
`false` même si la class de `$x` utilise le trait — exécuté ; `class_uses()`,
lui, le voit. Pour typer, il faut une interface.

## Application

- Partager `touch()` entre entités sans parent commun → un trait.
- Typer les classes qui l'utilisent → une interface, implémentée à côté du
  trait.

## QCM d'entraînement

**Question 1.** Le trait `Counter` déclare `public static int $count = 0`. Les
classes `A` et `B` l'utilisent ; `A` incrémente deux fois, `B` une fois. Que
vaut `B::$count` ?

- **A.** 3 : la property est partagée
- **B.** 1
- **C.** Une erreur fatale
- **D.** 0 : le trait garde sa valeur

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Le trait est copié dans chaque class : `A` et `B` ont
chacune leur propre property statique, indépendante de l'autre.

</details>

**Question 2.** Le trait déclare `public int $n = 0;`, et la class qui
l'importe déclare elle aussi `public int $n = 1;`. Que se passe-t-il ?

- **A.** Une erreur fatale : definitions incompatibles
- **B.** La valeur de la class l'emporte, selon l'ordre de précédence habituel
- **C.** La valeur du trait l'emporte
- **D.** Un warning

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Pour une property, PHP n'applique pas la précédence des
methods : les deux déclarations doivent être identiques, sinon la composition échoue.

</details>

**Question 3.** Que produit `new Timestampable()` ?

- **A.** Un object vide
- **B.** `null`
- **C.** Une `Error`
- **D.** Un warning

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Un trait n'est pas une class instanciable : PHP lève
« Cannot instantiate trait » dès la tentative.

</details>

**Question 4.** La class `P` utilise le trait `U`, et `Q extends P` n'en
importe aucun. Que rend `class_uses(new Q())` ?

- **A.** `['U' => 'U']`
- **B.** `false`
- **C.** Une exception
- **D.** `[]`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** La function ne liste que les traits importés par la
class elle-même ; ceux de la class parente n'y figurent pas.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- Précédence : **class > trait > parent**.
- Conflit non arbitré : **erreur fatale**.
- **`insteadof`** écarte ; **`as`** ajoute un nom ou change une visibilité.
- Method abstraite du trait : à **implémenter** par la class host.
- Static de trait : **une copie** par class.
- **`instanceof` trait → `false`** ; `class_uses()` le voit ; typer par interface.

Moyen mnémotechnique : **« La class décide, le trait propose, le parent attend »**.

## Sources officielles

- Manuel PHP — *Traits*, sections *Precedence* et *Conflict Resolution*

