# L'avertissement `PED-003` sur huit items, lu question par question

La *pull request* #128 a présenté cet avertissement comme une décision à
prendre — « ajouter une question à chacun, ou assumer l'écart ? ». **Cette
formulation était trompeuse et vient de moi.** Elle laissait entendre qu'un
outcome restait non évalué dans ces huit items. Lecture faite : aucun ne l'est.

## Ce que la règle mesure, et ce qu'elle ne mesure pas

`PED-003` porte deux contrôles distincts.

Le **contrôle liant**, en `ERROR` : *chaque outcome déclaré d'un item raffiné
est évalué par au moins une question qui le nomme*. Il s'exécute désormais en
erreur sur **les vingt-six lots**, tous raffinés, et il passe. Zéro outcome sans
question.

L'**avertissement agrégé** compte les items portant moins de questions que
d'outcomes. Le commentaire de la règle dit lui-même ce qu'il vaut :

> One question can assess two outcomes, so `questions < outcomes` does not
> prove an outcome is unassessed.

Il existait pour que la règle ne soit pas muette — donc non testée — sur un
corpus où aucun outcome ne portait encore d'identifiant. Les 603 outcomes en
portent un aujourd'hui, et le contrôle dont il était l'approximation s'exécute
partout. **L'avertissement n'est pas un défaut : c'est un compteur.**

## Les huit items, lus

Les onze questions à double lien ont été lues en entier — énoncé, quatre choix,
explication — et jugées sur une seule question : *la bonne réponse exige-t-elle
les deux faits, ou l'un des deux est-il seulement supposé dans l'énoncé ?*

| Lot | Item | Jugement |
|---|---|---|
| 10 | Access Control Rules | **Les deux exigés.** Répondre « la règle stricte est inatteignable » demande de savoir que seule la première règle s'applique *et* que l'ordre en découle. |
| 24 | PropertyAccess | **Les deux exigés.** La bonne réponse énonce les défauts opposés *et* leur renversement ; les distracteurs jouent sur les deux. |
| 25 | Runtime | **Les deux exigés.** Le scénario *est* l'effet de bord ; la réponse est la ré-inclusion du script. |
| 06 | Assets management | **Porté.** L'énoncé montre la forme de référence, la réponse donne la justification. |
| 07 | Handling file upload | **Porté ailleurs.** La déclaration non mappée est l'objet même de la question sœur. |
| 02 | Language detection | **Porté ailleurs.** La lecture des préférences du client est l'objet de la question sur `getPreferredLanguage()` avec la liste supportée. |
| 05 | Domain name matching | **Supposé, jamais testé** — voir ci-dessous. |
| 05 | HTTP methods matching | **Supposé, jamais testé** — voir ci-dessous. |

## Les deux seuls cas discutables, et pourquoi rien n'est ajouté

Dans les deux items du lot 05, un outcome n'est jamais *l'objet* du test :

- contraindre une route à un nom d'hôte — les deux questions écrivent
  `host: 'm.example.com'` dans l'énoncé et interrogent autre chose (le
  comportement sans l'option, puis le paramétrage du sous-domaine) ;
- restreindre une route à certaines méthodes — même forme : la question porte
  sur le défaut, et `methods: ['POST', 'PUT']` n'apparaît que comme distracteur.

Une question dédiée y testerait **la syntaxe d'une option que le candidat ne
peut pas ne pas avoir vue** en lisant les questions existantes. Le test
d'admission de §1.2 et la porte de valeur nette de §1.4 posent la même question :
cela améliore-t-il de façon démontrable la probabilité de répondre juste à une
question de périmètre officiel ? **La réponse n'est pas oui de façon
démontrable, donc on s'arrête** (§1.3).

Ajouter deux questions aurait fait baisser le compteur de 8 à 6. C'est
exactement la raison inadmissible : *« Never promote an item because a
percentage looks low. »*

## Ce qui n'a pas été fait, délibérément

- **La règle n'a pas été touchée**, ni dans sa sévérité ni dans son périmètre.
- **L'avertissement n'a pas été retiré.** Il reste juste, et un compteur visible
  vaut mieux qu'un compteur supprimé parce qu'il gênait la lecture.
- **Aucune question n'a été ajoutée ni réécrite.** Aucun lien
  `assesses_outcomes` n'a été modifié : les liens faibles décrits ci-dessus sont
  exacts — la question nomme bien un outcome qu'elle touche — et les resserrer
  ferait baisser le même compteur par une autre voie.

## Limites

- Ce jugement est **une lecture humaine, la mienne, non relue**. Aucun script ne
  décide si une bonne réponse exige un fait ou le suppose.
- Il porte sur les **onze questions à double lien de ces huit items**, pas sur
  les 716 du corpus. Le même examen sur l'ensemble n'a pas été fait.
