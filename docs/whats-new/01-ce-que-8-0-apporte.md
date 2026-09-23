# Ce que Symfony 8.0 apporte, et ce qu'il retire

## Pourquoi ce chapitre existe

Cette page n'est **pas** du périmètre officiel. Elle est classée `ENRICHMENT` :
elle ne compte pas dans la couverture et ne porte aucune question notée. Elle
répond à une question que le syllabus ne pose pas mais que l'examen suppose
résolue — *sur quelle version travaille-t-on, et qu'est-ce qui a disparu ?*

## La phrase qui décide de tout

Le fichier de migration officiel ouvre ainsi :

> Symfony 7.4 and Symfony 8.0 are released simultaneously at the end of
> November 2025. According to the Symfony release process, both versions have
> the same features, but Symfony 8.0 doesn't include any deprecated features.

Trois conséquences, et elles sont contre-intuitives.

**8.0 n'apporte aucune fonctionnalité que 7.4 n'a pas.** Les deux versions
sortent le même jour avec le même jeu de fonctionnalités. Chercher « les
nouveautés de la 8.0 » au sens de fonctions inédites, c'est chercher une liste
vide.

**Ce que 8.0 apporte, c'est une absence.** Elle ne contient pas le code
déprécié. La version majeure est donc définie par ce qu'elle **retire**, pas par
ce qu'elle ajoute.

**La migration se prépare en 7.4.** La note le dit : pour migrer, il faut
d'abord résoudre toutes les notices de dépréciation. On corrige sur 7.4, où le
code déprécié fonctionne encore en émettant un avertissement ; on passe ensuite
en 8.0, où il n'existe plus.

## PHP 8.4 au minimum

Le fichier porte cette exigence en encadré, juste après la note d'ouverture :

> Symfony v8 requires PHP v8.4 or higher

C'est une contrainte de plateforme, pas une recommandation. Un hébergement resté
en PHP 8.3 ne peut pas faire tourner Symfony 8, quel que soit le reste du code.

## Où sont vraiment les nouveautés

Les composants récents — `TypeInfo`, `JsonPath`, `JsonStreamer`, `ObjectMapper` —
existent bien dans la 8.0, mais leur journal de modifications les fait démarrer
en **7.1** et **7.3**. Ils sont nouveaux pour qui vient de la 6.x, pas apportés
par la 8.0.

Les sections `8.0` des journaux de composants, elles, contiennent des
suppressions **et** quelques ajouts d'arguments ou de méthodes. Ce sont eux que
les pages suivantes de ce chapitre détaillent, composant par composant.

## Pièges d'examen

**« La 8.0 apporte telle fonctionnalité »** est une affirmation presque toujours
fausse : la fonctionnalité existe aussi en 7.4. La 8.0 apporte une **absence**.

**« On migre de 7.4 vers 8.0 en corrigeant après coup »** : non. On corrige les
dépréciations **avant**, sur 7.4, parce qu'après le code n'existe plus pour
avertir.

**Confondre « nouveau composant » et « nouveau en 8.0 »** : les composants
récents datent de 7.1 à 7.3.

## Points clés

- 7.4 et 8.0 sortent ensemble avec les **mêmes fonctionnalités**.
- 8.0 se définit par le retrait du code déprécié.
- **PHP 8.4 minimum**, comme contrainte, pas comme conseil.
- La migration se prépare en résolvant les dépréciations **sur 7.4**.

## Sources officielles

- [UPGRADE FROM 7.4 to 8.0](https://github.com/symfony/symfony/blob/8.0/UPGRADE-8.0.md)

## Flashcards

### Mémorisation

<details>
<summary>Quelle version minimale de PHP Symfony 8 exige-t-il ?</summary>

**PHP 8.4.**

Le fichier de migration le porte en encadré, juste après la note d'ouverture. C'est une contrainte de plateforme, pas une recommandation.

</details>

<details>
<summary>Quel jeu de fonctionnalités la 8.0 a-t-elle par rapport à la 7.4 ?</summary>

**Le même.** Les deux versions sortent le même jour avec les mêmes fonctionnalités.

Ce que la 8.0 change, c'est qu'elle ne contient pas le code déprécié.

</details>

### Compréhension

<details>
<summary>Pourquoi faut-il résoudre les dépréciations **avant** de passer en 8.0 ?</summary>

Parce qu'en 7.4 le code déprécié fonctionne encore et **avertit** ; en 8.0 il n'existe plus, donc plus rien ne signale le problème.

On corrige là où l'outil parle encore, pas là où il s'est tu.

</details>

<details>
<summary>Pourquoi chercher « les nouveautés de la 8.0 » au sens de fonctions inédites donne-t-il une liste vide ?</summary>

Parce qu'une fonctionnalité présente en 8.0 est présente en 7.4 aussi. La version majeure se définit par ce qu'elle **retire**.

C'est le processus de publication de Symfony : la dernière mineure et la majeure suivante sortent ensemble.

</details>

### Application

<details>
<summary>Un hébergement est en PHP 8.3 et le code n'a plus aucune dépréciation. Peut-on passer en Symfony 8 ?</summary>

**Non.** PHP 8.4 est un minimum absolu, indépendant de l'état du code.

Les deux conditions sont cumulatives : plateforme *et* dépréciations.

</details>

<details>
<summary>On vous demande « quelle fonctionnalité la 8.0 a-t-elle apportée que la 7.4 n'a pas ? »</summary>

Aucune. La question repose sur une prémisse fausse.

Une bonne réponse consiste ici à corriger la prémisse, pas à chercher un exemple.

</details>

### Pièges

<details>
<summary>Piège : « `TypeInfo`, `JsonPath` et `JsonStreamer` sont les nouveaux composants de la 8.0. »</summary>

Non — leurs journaux les font démarrer en **7.1** et **7.3**. Ils sont nouveaux pour qui vient de la 6.x, pas apportés par la 8.0.

« Récent » et « apporté par la 8.0 » sont deux choses différentes.

</details>

<details>
<summary>Piège : « la 8.0 déprécie l'ancien code. »</summary>

Non : la 7.4 le déprécie, la 8.0 le **supprime**. Il n'y a plus d'avertissement à lire.

Confondre les deux étapes fait planifier la migration dans le mauvais ordre.

</details>
