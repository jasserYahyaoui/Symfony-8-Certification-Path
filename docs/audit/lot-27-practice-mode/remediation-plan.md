# Plan de remédiation — Practice Mode (Lot 27)

Établi depuis l'audit de l'Unité A, exécuté en lecture seule. **Aucune question
n'a été modifiée pour produire ce plan.**

## Le résultat qui change le plan

L'audit classe les 484 questions anglaises `LEARNING` ainsi :

| Classification | Questions |
|---|---:|
| `READY_FOR_NEW_UI` | **362** |
| `CODE_INLINE_PRESENT` | 120 |
| `CODE_BLOCK_REQUIRED` | 2 |
| `MISSING_CORRECT_EXPLANATION` | **0** |
| `MISSING_DISTRACTOR_EXPLANATION` | **0** |
| `AMBIGUOUS_CONTENT` | **0** |
| `TECHNICALLY_INCORRECT` | **0** |
| `DATA_MODEL_GAP` (item · outcomes · course · source) | **0** |
| `OUT_OF_SCOPE_DEPENDENCY` | **0** |
| `VERSION_MISMATCH` | **0** |

**Ces zéros ne sont pas un silence.** `python3 tools/audit/lot27_practice_audit.py
--prove` injecte un défaut synthétique par classification et vérifie qu'elle se
déclenche : **12 cas, tous OK**, en mémoire, sans lecture en écriture d'aucun
fichier canonique. Quatre classes ne renvoient rien sur le corpus réel — la
forme exacte qu'avaient les cinq contrôles vides déjà trouvés dans ce projet —
et c'est pour cela que la preuve existe.

Les zéros s'expliquent : `PED-002` impose déjà une explication par distracteur,
`PED-003` impose le lien outcome, `SRC-001` la source, `LNK-001` la référence de
cours. L'audit les re-mesure indépendamment et confirme.

### Deux erreurs de ma première passe, corrigées et enregistrées

- **`VERSION_MISMATCH` annonçait 7.** Les sept nommaient **PHP** 8.1 à 8.4, qui
  est dans le périmètre — Symfony 8.0 exige PHP 8.4. Détecteur faux, et de
  surcroît redondant avec `aud02_version_contamination.py`, qui fait autorité et
  rapporte 0. Retiré plutôt que corrigé à moitié.
- **`CODE_NOT_STRUCTURED` annonçait 129**, ce qui se lisait comme 129 défauts de
  contenu. C'est **un** défaut : rien ne rend le code. Deux surfaces appellent un
  bloc, 242 des fragments inline que la consigne demande de garder inline.
  Publier 129 aurait justifié de réécrire 129 questions qui n'en ont pas besoin.

## Conséquence directe sur le découpage

**L'Unité C est presque vide.** Aucune donnée anglaise n'est absente, fausse ou
insuffisante au sens de l'audit. La consigne de l'Unité C — « uniquement si
l'Unité A trouve des données absentes ou incorrectes » — n'est déclenchée par
rien. Elle reste ouverte pour un `BLOCKER` qui apparaîtrait pendant l'Unité B,
et se conclura sinon en `NOT_APPLICABLE` justifié par ce document.

Tout le travail réel est dans **l'Unité B** : un rendu et quatre champs.

## Unité B — ce qu'il faut construire

### B1. Transport des données manquantes (générateur, pas JSX)

Quatre manques, tous résolus par du code déjà écrit :

1. appeler `PayloadBuilder::itemIndex()` pour `practice.json` — il produit déjà
   `official_item` (libellé), `official_topic` et `learning_outcomes` pour les
   payloads de mock ;
2. y ajouter l'URL du cours, dérivée comme dans `DocsGenerator` :
   `/docs/courses/<slug(lot)>/<slug(official_item)>` ;
3. étendre le type TypeScript `Payload` en conséquence ;
4. couvrir le nouveau champ par un test et par le *smoke test* de production.

Aucun fait technique ne descend dans un composant React.

### B2. Rendu du code

Un composant unique, appliqué aux **quatre** surfaces — énoncé, texte de choix,
explication générale, explication de distracteur.

- bloc si la surface est multiligne ou déjà clôturée par l'auteur ;
- `<code>` inline sinon ;
- `code_language` utilisé quand il existe (12 questions) ;
- indentation et retours à la ligne préservés ;
- `{}`, `{{ }}`, `{% %}`, `{# #}`, `<`, `>`, `&`, `#`, `:` rendus littéralement —
  le contenu est du texte React, jamais du MDX, donc le risque JSX ne se pose
  pas tant qu'on n'injecte pas de HTML ;
- défilement horizontal sur mobile, sur le bloc seul, jamais sur la page ;
- **la valeur technique du code n'est pas modifiée.**

### B3. Feedback après soumission

Les sept sections du §4 du contrat, dans l'ordre, dont deux sections neuves
(*key takeaway*, *review this concept*) rendues possibles par B1.

Aujourd'hui `Feedback` affiche les explications de **tous** les distracteurs sans
marquer celui que l'apprenant a choisi : l'Unité B doit rappeler le choix fait.

### B4. Résultat de session

`ExamSession` exclut `practice` : il faut un enregistrement de session Practice,
donc une **migration de schéma** (`STORAGE_VERSION` 2 → 3) qui laisse lisible
l'historique existant. Analyses par topic, par atomic item et par learning
outcome, revue des erreurs, recommandations — toutes dérivées des tentatives
réelles, avec les formulations prudentes imposées.

`PER_QUESTION_TIMING_NOT_IMPLEMENTED` : la durée par question n'est pas mesurée
et ne sera pas simulée.

### B5. Tests

Les contrôles doivent échouer sur défaut injecté, pas seulement passer :
explication absente, explication de distracteur absente, `course_ref` fausse,
nombre de réponses incohérent, code multiligne aplati, YAML désindenté, Twig
cassé, bonne réponse visible avant soumission, mauvais score, `HOLDOUT` dans
Practice, mauvais résultat par topic ou par item.

## Non-régression exigée à chaque unité

```text
practice.json → LEARNING uniquement          (505, vérifié ce jour)
exam.json     → VALIDATION uniquement        (136)
mock-1/2/3/5  → aucun HOLDOUT
mock-4        → les 75 HOLDOUT et rien d'autre
```

## Ce que cette unité ne conclut pas

- Les 21 questions françaises ne sont **pas** auditées ici. Elles doivent
  continuer à fonctionner ; leur raffinement éditorial n'est pas dans ce lot.
- L'audit juge la **présence et la forme** des données. Il ne juge pas si une
  explication est pédagogiquement bonne, ni si une clé de réponse est correcte —
  aucun script ne le peut, et aucune n'a été relue par un humain dans cette
  unité.
- `TECHNICALLY_INCORRECT` et `OUT_OF_SCOPE_DEPENDENCY` renvoient 0 parce
  qu'**aucun contrôle automatique ne peut les établir**. La classe existe pour
  qu'un `BLOCKER` trouvé à la lecture ait où se ranger, pas parce qu'un script
  aurait prouvé leur absence.
