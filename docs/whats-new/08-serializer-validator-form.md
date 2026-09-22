# Serializer, Validator et Form en 8.0

## Objectif

Trois composants dont les suppressions se ressemblent. Page d'enrichissement :
hors périmètre officiel, hors couverture.

## Serializer : une interface fusionne, une signature s'élargit

`NameConverterInterface::normalize()` et `denormalize()` ne prennent plus un
seul argument. Elles en reçoivent trois de plus, tous optionnels : la classe, le
format et le contexte.

C'est la conséquence d'une autre ligne du même journal :
`AdvancedNameConverterInterface` est **retirée**, et le journal renvoie vers
`NameConverterInterface`. L'interface « avancée » portait justement ces
arguments supplémentaires ; en les remontant dans l'interface de base, la
distinction n'avait plus de raison d'être.

Disparaissent aussi la gestion du caractère d'échappement CSV — la constante et
la méthode de contexte associée — et une méthode dont le nom portait une faute
de frappe, au profit de l'orthographe correcte.

## Le XML recule dans les trois composants

| Composant | Ce qui disparaît |
|---|---|
| Validator | la configuration **implicite** des options de contrainte en XML |
| Form | le fichier `validation.xml`, remplacé par des attributs sur la classe `Form` |
| Serializer | les alias de classes de l'espace de noms `Annotation` |

Le mouvement est le même que dans Routing et DependencyInjection : la
configuration passe aux attributs, et les formats hérités sont retirés plutôt
que dépréciés une fois de plus.

Serializer suit aussi Routing sur un point précis : les accesseurs des classes
d'attributs sont retirés **au profit de propriétés publiques**.

## Form : un défaut qui change de valeur

*« Change default value of `default_protocol` option in `UrlType` from `'http'`
to `null` »*.

C'est la seule modification de ce chapitre qui change un **comportement par
défaut** sans rien retirer. Une URL saisie sans protocole n'est plus complétée
d'office : le champ la laisse telle quelle. Un formulaire qui comptait
là-dessus se met à produire des valeurs différentes, sans erreur et sans
avertissement.

`ResizeFormListener::preSetData()` est par ailleurs remplacée par
`postSetData()`.

## Pièges d'examen

**`AdvancedNameConverterInterface` n'existe plus** : c'est l'interface de base
qui porte désormais les arguments supplémentaires.

**`default_protocol` vaut `null`, pas `'http'`.** Le changement est silencieux —
aucune exception, seulement une valeur différente en sortie.

**Le XML n'est pas déprécié une fois de plus, il est retiré** — dans Routing,
DependencyInjection, Validator et Form.

## Points clés

- `NameConverterInterface` reçoit classe, format et contexte ;
  `AdvancedNameConverterInterface` est retirée.
- L'échappement CSV disparaît du Serializer.
- `UrlType::default_protocol` passe de `'http'` à `null`.
- Le format XML recule partout, au profit des attributs.

## Sources officielles

- [Serializer, journal 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Serializer/CHANGELOG.md)
- [Form, journal 8.0](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Form/CHANGELOG.md)

## Flashcards

### Mémorisation

<details>
<summary>Combien d'arguments `NameConverterInterface::normalize()` prend-elle en 8.0 ?</summary>

**Quatre** : le nom de propriété, puis la classe, le format et le contexte, tous optionnels.

Ces trois arguments venaient de `AdvancedNameConverterInterface`, désormais retirée.

</details>

<details>
<summary>Quelle est la nouvelle valeur par défaut de `default_protocol` sur `UrlType` ?</summary>

**`null`**, au lieu de `'http'`.

Une URL saisie sans protocole n'est plus complétée d'office.

</details>

### Compréhension

<details>
<summary>Pourquoi `AdvancedNameConverterInterface` disparaît-elle ?</summary>

Parce que ses arguments supplémentaires sont remontés dans l'interface de base : la distinction entre convertisseur « simple » et « avancé » n'avait plus d'objet.

Une interface qui n'ajoute plus rien n'a pas de raison d'exister.

</details>

<details>
<summary>Pourquoi le changement de `default_protocol` est-il plus dangereux qu'une suppression ?</summary>

Parce qu'il ne lève rien. Le formulaire continue de fonctionner et produit simplement des valeurs différentes.

Une suppression casse au démarrage ; un défaut modifié casse en production, plus tard.

</details>

### Application

<details>
<summary>Un convertisseur de noms maison implémente `normalize(string $propertyName): string`. Que se passe-t-il en 8.0 ?</summary>

La signature ne correspond plus à l'interface : il faut ajouter les trois arguments optionnels.

C'est le cas classique d'un ajout d'arguments sur une interface publique.

</details>

<details>
<summary>Un utilisateur saisit `example.com` dans un `UrlType` sans configuration explicite. Que contient la donnée en 8.0 ?</summary>

`example.com`, tel quel — plus de préfixe `http://` ajouté automatiquement.

Pour retrouver l'ancien comportement, il faut poser `default_protocol` explicitement.

</details>

### Pièges

<details>
<summary>Piège : « le format XML de validation est retiré en 8.0. »</summary>

Nuance : c'est la configuration **implicite** des options de contrainte en XML qui est retirée, pas nécessairement tout le format.

Les journaux distinguent des retraits partiels de retraits complets ; Routing et DI retirent le format entier.

</details>

<details>
<summary>Piège : « `default_protocol` vaut toujours `http` si on ne configure rien. »</summary>

Non : le défaut est `null` en 8.0. C'est le seul changement de ce chapitre qui modifie un comportement sans rien retirer.

Aucune exception, aucun avertissement : seulement une valeur différente.

</details>
