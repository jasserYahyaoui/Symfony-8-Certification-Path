# Lot 27 — rapport final

Practice Mode devait devenir un outil de révision : afficher le code comme du
code, ne rien dévoiler avant réponse, puis expliquer la bonne réponse, chaque
mauvaise, le point à retenir, où réviser, et rendre un bilan de série.

## Ce que le lot a réellement changé

**Une seule question de contenu a été touchée, et pour sa présentation.** Le
défaut n'était pas éditorial : aucun composant ne rendait le balisage de la
banque, donc une question affichait sa clôture ```` ```php ```` en toutes
lettres, une autre voyait ses attributs `#[Route]` aplatis en paragraphe, et
242 fragments inline sortaient avec leurs *backticks*.

| Unité | Objet | PR | Fusion | *Smoke test* production |
|---|---|---|---|---|
| A | Audit read-only | #134 | `35ffe92` | `104307401287` |
| B | Interface et données | #136 | `b16f1bd` | `104323853743` |
| C | Correction ciblée | #138 | `4a422bc` | `104337454417` |
| D | Vérification finale | ce document | — | — |

## Vérifications exécutées

`website/tools/verify-practice-ui.mjs` — **18 vérifications** dans un vrai
navigateur, dont **5 preuves** qu'un contrôle rejette son propre défaut :

| Vérifié | Résultat |
|---|---|
| Aucune correction avant soumission — ni bloc, ni explication, ni attribut | ok |
| Score juste dans les deux sens | ok |
| Les sept sections, dans l'ordre, avec le choix de l'apprenant rappelé | ok |
| Code multiligne en bloc, indentation préservée, aucune clôture littérale | ok |
| Question à **deux** blocs : deux blocs, prose conservée | ok |
| Question multiple : compte annoncé, excès signalé, tout-ou-rien respecté | ok |
| Bilan : score, verdict prudent, mentions obligatoires, lien cours vivant | ok |
| Série jouable **entièrement au clavier** | ok |
| Focus porté sur le verdict après soumission | ok |
| **Exam Mode** fonctionne encore avec le `QuestionCard` modifié | ok |
| Réponses survivant à un rechargement, `schema_version` 3 | ok |
| Historique **v2** encore lisible après migration | ok |
| `practice.json` : 505 questions, 0 identifiant du holdout | ok |

Accessibilité : **27 états**, 0 violation, dont les six états interactifs de
Practice audités **à 390 px**.

## Les défauts que les contrôles ont trouvés, et que je n'avais pas vus

Ils sont listés parce qu'ils sont la meilleure preuve que les contrôles
servaient à quelque chose.

1. **`scrollable-region-focusable`** sur les tableaux du bilan. Infima impose
   `table { display: block; overflow: auto }`, donc c'est la *table* qui
   défilait, pas le conteneur étiqueté. Corrigé en lui rendant `display: table`,
   ce qui répare aussi le calcul des largeurs de colonnes.
2. **`verify-agenda-ui.mjs` codait `schema_version === 2` en dur** et a échoué
   sur la migration. Il lit désormais `STORAGE_VERSION` dans la source.
3. **`TECH-5` d'`aud08` a attrapé ma propre prose** — « audited what a page »
   correspondait à son motif. C'est le commentaire qui a changé, pas la règle.
4. **Ma vérification « code en bloc » sélectionnait la première question
   clôturée**, donc en clôturer une seconde l'a fait basculer sur celle dont les
   extraits tiennent en une ligne. Elle teste maintenant le corps du bloc.
5. **Ma vérification clavier tabulait vers un radio précis.** `Tab` atteint le
   *groupe* ; on s'y déplace aux flèches. Elle ne passait que lorsque le
   mélange plaçait la cible en premier — une vérification qui dépend d'un
   mélange est une vérification qui ment. Corrigée, puis exécutée trois fois de
   suite pour le vérifier.
6. **Mon pilotage d'Exam Mode cliquait le premier bouton de la page**, qui est
   un bouton de la barre Docusaurus. Ciblé par son nom.

Quatre de ces six défauts étaient dans **mes propres contrôles**, pas dans le
produit. Un contrôle qui passe pour la mauvaise raison ne vaut pas mieux qu'un
contrôle absent.

## Non-régression

```text
practice.json → LEARNING uniquement      505 questions, 163 items indexés
exam.json     → VALIDATION uniquement    136
mock-1/2/3/5  → aucun HOLDOUT
mock-4        → les 75 HOLDOUT et rien d'autre
```

Couverture officielle : **100 % (163/163)**, inchangée. Aucun cours, aucune
matrice, aucun learning outcome, aucun niveau, aucune clé de réponse modifiés.
Les 21 questions françaises fonctionnent sans changement éditorial.

## Preuve en production, pas seulement en local

`.github/scripts/practice-smoke.py` contrôle le contrat de Practice Mode sur les
**octets servis par GitHub Pages** : index d'items présent, libellé et sujet non
vides, outcomes présents, `course_url` bien formée pour les 163, aucune question
orpheline. Six défauts injectés sont rejetés, vérifié avant câblage.

Trois URL de cours sont en outre **réellement interrogées** en production. Trois
sur 163 est un **échantillon**, et le script le dit : la structure des 163 est
contrôlée, et `onBrokenLinks: 'throw'` prouve que le build a écrit les pages.

## Ce que ce lot ne prouve pas

- **`TECHNICALLY_INCORRECT` vaut 0 parce qu'aucun contrôle automatique ne peut
  l'établir**, pas parce que l'absence d'erreur technique aurait été démontrée.
  **Aucune des 484 questions anglaises n'a été relue par un humain.**
- Le rendu est vérifié ; la **qualité pédagogique** des explications ne l'est
  pas. Un script voit qu'une explication existe, jamais qu'elle éclaire.
- `PER_QUESTION_TIMING_NOT_IMPLEMENTED` : aucune durée n'est mesurée, donc
  aucune n'est affichée. Le bilan ne dit rien du temps passé.
- Le score du bilan n'est **pas** un résultat officiel Symfony et aucun seuil
  officiel n'existe dans ce projet ; le chiffre est étiqueté
  `INTERNAL_TRAINING_FORMAT`.
- Les 21 questions françaises **n'ont pas été auditées**, seulement préservées.

## Statut

**`PASS_WITH_DEFERRED_FRENCH`** — les 484 questions anglaises passent la porte
pédagogique et bénéficient du nouveau rendu ; les françaises fonctionnent sans
régression et leur raffinement éditorial reste hors de ce lot, comme prévu.
