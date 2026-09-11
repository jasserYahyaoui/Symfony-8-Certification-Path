# PED-010 — Flashcard Gap Remediation

**Date** : 2026-09-11 · **Branche** : `remediate/ped-010-flashcards` · **Base** : `8d2dce1`

## 1. Ce qui a été remesuré, et non repris d'un rapport

| | Avant | Après |
| --- | ---: | ---: |
| Items officiels | 163 | 163 |
| Flashcards | 137 | **174** |
| Items avec ≥ 1 carte — **couverture brute** | 126 (77,3 %) | **163 (100 %)** |
| Exemptions justifiées | — | **0** |
| **Couverture applicable** | 126/163 | **163/163** |
| Flashcards orphelines | 0 | 0 |
| Ids dupliqués | 0 | 0 |
| Cartes sans source | 0 | 0 |
| Doublons exacts front + back | 0 | 0 |
| Fronts en quasi-doublon (`FLC-001`) | 0 | 0 |

Couverture brute et couverture applicable coïncident **parce qu'aucune exemption
n'a été retenue**, pas parce que la distinction aurait été escamotée. Le registre
existe et est vide ; `FLC-002` le lit.

## 2. Le constat qui a décidé de la méthode

Les 37 items sans carte n'étaient pas 37 lacunes.

| | Nombre |
| --- | ---: |
| Absences **justifiées par écrit** dans l'en-tête du fichier de leur lot | **29** |
| Absences justifiées **nulle part** | **8** |

Les huit : `Global variables` (lot 06), que l'en-tête du lot omettait sans le
dire, et les **sept items du lot 07**, dont le fichier ne portait aucun en-tête
justificatif.

**Rien ne distinguait les deux cas pour un outil.** Un commentaire n'est pas une
donnée : aucune porte du projet ne pouvait dire si une absence était une décision
ou un oubli. C'est le défaut que `FLC-002` corrige, autant que la lacune
elle-même.

## 3. Pourquoi les 29 exemptions tombent

Les justifications d'origine tenaient toutes au même raisonnement :

> « table consultée plutôt que mémorisée », « mécanisme raisonné à partir d'un
> critère », « fait que le cours porte déjà ».

Ce raisonnement est **juste en situation d'apprentissage**. Il ne tient pas à
l'examen, qui est à livre fermé. Sous cette contrainte, une table qu'on ne peut
pas consulter se réduit au **critère** qui la résume — et ce critère est
exactement une cible de rappel.

Les 37 cartes créées testent donc **le critère, jamais la table** :

| Item | Ce que la carte teste | Ce qu'elle ne teste pas |
| --- | --- | --- |
| Components and Bridges | le critère qui sépare bridge et bundle | la liste des paquets |
| HttpKernel / FrameworkBundle | où vit l'arbre `framework:` | la table de répartition |
| Special internal routing attributes | le seul des cinq absent des imports | les cinq paramètres |
| Built-in form types | que les cinq types de choix sont un seul socle | le catalogue |
| Filters and functions | le critère filtre/fonction | la liste des filtres |

### Les recouvrements signalés à l'origine ont été respectés

Trois en-têtes signalaient qu'une carte ferait doublon. Les cartes créées
évitent explicitement ces recouvrements :

| Item | Doublon signalé | Ce que la nouvelle carte teste à la place |
| --- | --- | --- |
| Abstract classes | la carte `self`/`static` du lot 01 | `abstract private` est une erreur fatale |
| HttpFoundation component | la carte InputBag du lot 02 | le **sens** de la dépendance |
| The cookies | la carte d'attributs de cookie du lot 02 | l'asymétrie requête/réponse |
| CSRF protection | la carte `form_end()` du lot 07 | la **nature** du refus, pas son rendu |

Et la carte *Content negotiation* ne porte **pas** sur « `q` vaut 1.0 par
défaut » — le réflexe que §6 nomme explicitement comme sur-production — mais sur
`q=0`, qui est un refus et non une préférence basse.

## 4. Provenance des faits

Chaque carte teste **une seule idée**, tirée de la section *Pièges d'examen* du
cours de son item — contenu `VERIFIED`, écrit et vérifié lors d'une campagne
antérieure — et cite **la source déjà ancrée de cet item**, sur une branche
versionnée. Aucune source `/current/`. Aucun fait postérieur à Symfony 8.0.
Aucune dépendance hors syllabus.

Terminologie technique en anglais, explication en français (§5).

## 5. La règle qui empêche la lacune de revenir

`FLC-002` — `src/Validation/Rule/FlashcardCoverageRule.php`, dans
`RuleSet::mandatory()`. Elle lit `docs/policy/flashcard-exemptions.yml` et
échoue dans **trois** directions :

1. un item sans carte et sans entrée au registre — une lacune ;
2. une entrée pour un item qui a depuis reçu une carte — une **exemption
   périmée** ;
3. une entrée nommant un item inexistant.

La deuxième compte autant que la première : une exemption que personne ne
revisite, c'est ainsi qu'un registre se met à mentir.

**La règle n'assouplit pas §6.** Une carte pour un fait déjà retenu par
l'application reste un coût de révision pur, et exempter un tel item reste le
bon choix — cela doit simplement être écrit là où un script peut le lire.

### Elle ne se contente pas d'être silencieuse

`tools/audit/prove_flashcard_coverage_fails.py` injecte les trois défauts dans
les **vrais** fichiers canoniques, vérifie qu'un `[ERROR] FLC-002` sort à chaque
fois, puis restaure chaque fichier et compare son SHA-256 à l'original.

```text
FIRE  item sans carte ni exemption
FIRE  exemption périmée (l'item a une carte)
FIRE  exemption nommant un item inexistant
restauration byte-identique (SHA-256) : OK
PROOF OK
```

Le script s'interrompt si `FLC-002` fire déjà sur le corpus intact : une preuve
qui part d'un corpus fautif ne prouve rien. Il tourne en CI à chaque push, à
côté de `prove_framework_rules_fail.py`.

## 6. Isolation du holdout

Vérifié par script : **aucun front de flashcard ne reprend l'énoncé d'une
question du pool HOLDOUT** (0 sur 174), et aucun ne reprend l'énoncé d'une
question `LEARNING` ou `VALIDATION` (0 sur 174). Aucun contenu de Mock 4 n'a été
lu, cité ni dérivé pour écrire ces cartes : la source de chaque fait est la
section *Pièges* du cours, jamais une question.

## 7. Portes

| Porte | Résultat |
| --- | --- |
| `php bin/cert validate` | PASS — exit 0, 22 règles, 0 erreur |
| `vendor/bin/phpunit` | PASS — 236 tests, 8 923 assertions |
| `composer gate-full` | PASS — exit 0 |
| `npm --prefix website run a11y` | PASS — 0 violation |
| Couverture officielle | 100 % (163/163) — inchangée |
| 10 audits `tools/audit/` | PASS — 0 finding chacun |
| `prove_framework_rules_fail.py` | PASS |
| `prove_flashcard_coverage_fails.py` | **PASS — 3 défauts, 3 tirs, restauration vérifiée** |

## 8. Limites

- Les 37 cartes n'ont pas été relues par un humain. Leurs faits proviennent de
  contenu `VERIFIED` et citent une source ancrée, mais leur **formulation** n'a
  pas été éprouvée sur un candidat.
- La détection de doublon reste **syntaxique** (`FLC-001` normalise le front).
  Deux cartes formulées différemment sur la même connaissance passeraient. Les
  recouvrements connus ont été traités à la main, item par item, section 3 — ce
  qui n'est pas une garantie automatique.
- Symfony n'est pas installé dans cet environnement : aucun comportement n'a été
  **exécuté**. Tout est lu dans des sources ancrées sur `8.0`.
