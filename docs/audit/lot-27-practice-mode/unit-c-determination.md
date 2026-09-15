# Unité C — ce que l'audit a réellement déclenché

L'unité C ne s'exécute que « si l'unité A trouve des données absentes, fausses
ou insuffisantes ». Voici ce qu'il en est, re-dérivé et non rappelé.

## Les conditions de déclenchement, mesurées

Sur les **484 questions `LEARNING` anglaises**, relevé depuis
`english-feedback-audit.csv` :

| Condition | Questions |
|---|---:|
| Explication générale absente ou générique | **0** |
| Distracteur sans explication | **0** |
| Nombre de réponses incohérent avec la clé | **0** |
| Outcome non lié | **0** |
| `course_ref` non résolvable | **0** |
| Source officielle absente | **0** |
| Contenu techniquement faux | **0 établi** — voir les limites |
| Contamination de version | **0** (`aud02` fait autorité et rapporte 0) |

Aucun `BLOCKER` n'a émergé pendant l'unité B : aucune clé de réponse n'a été
contestée, aucune explication contredite par le rendu.

**Le contenu ne déclenche donc rien.** Ce qui reste, et qui déclenche l'unité C,
est d'une autre nature.

## Ce qui a déclenché l'unité C : une question, un défaut de présentation

`QST-cfhm8d3qscwq` (Routing, `golden-slice.yml`) déclarait `code_language: php`
et portait **du code multiligne non clôturé**. Après l'unité B, ses deux
attributs `#[Route]` s'affichaient donc en **texte courant**, avec leurs retours
à la ligne mais sans bloc — ce que la consigne « afficher tout vrai fragment
multiligne dans un bloc de code » interdit.

Le cas n'était pas trivial : la ligne mêlait **du code et de la prose**, les deux
attributs étant séparés par un connecteur `— and —`. Tout clôturer aurait mis du
non-PHP dans un bloc PHP. La question porte donc maintenant **deux blocs `php`
encadrant le connecteur resté en prose**.

**Ce qui n'a pas bougé**, vérifié par script contre `HEAD` plutôt qu'affirmé :

- un seul champ a changé, sur une seule question : `question` ;
- les deux fragments de code sont **identiques caractère pour caractère** —
  aucun reformatage, pas même la mise de l'attribut sur sa propre ligne, qui
  serait pourtant l'usage PHP ;
- la clé de réponse est inchangée (`CHO-9qgeyn3v01ns`) ;
- choix, explications, outcomes, item, niveau : inchangés ;
- aucune question française n'a été touchée.

## Un défaut de mon propre harnais, trouvé par l'échec

La vérification « le code est rendu en bloc » sélectionnait **la première
question clôturée** du payload. En clôturant une seconde question, l'unité C a
fait basculer cette sélection sur celle dont les extraits tiennent en une ligne,
et la vérification a échoué sur « le bloc a perdu ses retours à la ligne ».

**Le rendu n'était pas en cause ; la fixture l'était.** La sélection teste
maintenant le **corps** du bloc et exige qu'il soit réellement multiligne, ce
qui la rend indépendante de l'ordre du payload. Une vérification qui dépend de
ce qui trie en premier est une vérification qui cassera pour la mauvaise raison.

Une vérification a par ailleurs été ajoutée pour le cas neuf : une question à
**deux** blocs doit rendre deux blocs, conserver la prose entre eux, et
n'afficher aucune clôture littérale. C'est exactement le cas qu'un rendu à
clôture unique traite mal.

## Ce que cette unité ne conclut pas

- **`TECHNICALLY_INCORRECT` vaut 0 parce qu'aucun contrôle automatique ne peut
  l'établir**, pas parce que l'absence d'erreur technique aurait été démontrée.
  Aucune des 484 questions n'a été relue par un humain dans ce lot.
- Les 21 questions françaises ne sont pas auditées ; elles doivent seulement
  continuer à fonctionner.
- Le corpus de contenu est par ailleurs **inchangé depuis l'audit de l'unité A**
  (`git log 35ffe92..HEAD -- content/ docs/syllabus/` est vide avant cette
  unité), donc les classifications de l'unité A restent valides telles quelles.

## Statut

**`PASS` — déclenchée, et strictement bornée à une question.** Les autres
conditions restent à zéro, et zéro y est prouvé non muet par
`lot27_practice_audit.py --prove` (12 cas).
