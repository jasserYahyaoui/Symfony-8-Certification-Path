# Lots 22 à 26 — Miscellaneous, raffinés sous le cadre version 2

Vingt-deuxième à vingt-sixième lots raffinés, traités **en une seule unité**,
pour la raison donnée au §0 du rapport des lots 14 à 17 : treize lots d'un à
trois items chacun demandaient vingt-six *pull requests*. Chaque lot garde son
entrée de journal, ses preuves de production et sa ligne de tableau de bord ;
seule la *pull request* est partagée.

| Lot | Items |
|---|---|
| 22 | Mailer · Mime |
| 23 | Process |
| 24 | PropertyAccess |
| 25 | Runtime |
| 26 | Serializer |

C'est la **dernière unité** du chantier de raffinement : après elle, les
vingt-six lots de contenu ont été audités sous le cadre version 2.

## 1. Ce que le cadre a trouvé

**Neuf outcomes sur vingt-neuf** n'étaient évalués par aucune question :

| Lot | Item | Outcome sans aucune question |
|---|---|---|
| 22 | Mailer | choisir un transport parmi ceux fournis, et reconnaître celui qui est déconseillé |
| 22 | Mailer | distinguer acceptation par le transport et remise effective |
| 22 | Mime | distinguer l'API de haut niveau de l'API de bas niveau |
| 23 | Process | distinguer lancement bloquant et lancement non bloquant |
| 23 | Process | distinguer délai total et délai d'inactivité |
| 24 | PropertyAccess | `__get()` est pris en charge par défaut, `__call()` doit être activé |
| 25 | Runtime | sélectionner un *runtime* par variable d'environnement ou par `composer.json` |
| 26 | Serializer | plusieurs normaliseurs, un seul encodeur dans la chaîne |
| 26 | Serializer | ce que le normaliseur d'objets prend par défaut, et comment exclure un membre |

Les vingt autres l'étaient déjà. Le creux est homogène et reconnaissable : ces
six items portaient les **cas d'usage** — comment envoyer, comment lancer, comment
lire un chemin — et laissaient de côté les **choix** que l'examen fait porter sur
eux : quel transport, quel mode de lancement, quel délai, quel normaliseur.

Les neuf questions ajoutées comblent exactement ce creux. Les neuf faits ont été
vérifiés dans les sources amont avant rédaction : `mailer.rst`,
`components/mime.rst`, `components/process.rst`, `components/property_access.rst`,
`components/runtime.rst`, `serializer.rst`, tous à la branche `8.0`.

## 2. Une citation qui ne résolvait pas, héritée de l'unité précédente

`aud03_source_anchor.py` signale **une** source en échec sur les 178 distinctes
du corpus :

```
[ANCHOR-8 source does not resolve] 1
    https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/cache.rst
    → no response HTTP Error 404: Not Found
```

Elle vient de la question sur les quatre concepts du composant Cache, écrite dans
l'unité des lots 18 à 21, et le rapport de cette unité la cite encore sous ce
chemin. Le chemin correct à la branche `8.0` est **`cache.rst`**, à la racine :
la section citée — *Cache Pools, Adapters and Items*, avec les définitions
**Adapter** et **Provider** — y figure bien, et le contenu de la question est
exact. Seule l'URL était fausse.

Corrigée ici, avec `verified_at` porté au jour de la vérification. `aud03`
retombe à **0 finding**, 177 URL sur 177 en `200`.

**Pourquoi CI ne l'avait pas vue :** le *workflow* exécute
`aud03_source_anchor.py --offline`, parce que l'egress du *runner* n'est pas
garanti. Hors ligne, le script vérifie la forme de l'URL — branche ancrée, pas
de `/current/` — mais ne peut pas savoir si elle répond. Une URL bien formée
vers un fichier inexistant passe donc CI. C'est une limite réelle du contrôle,
pas un défaut de cette unité, et elle est écrite ici pour qu'elle soit connue :
**seule l'exécution en ligne, locale, prouve qu'une source existe.**

## 3. L'indice de longueur, et le dénominateur pour la troisième fois

| Lot | Avant | Après |
|---|---:|---:|
| 22 | 3/9 = **33,3 %** | 1/9 = **11,1 %** |
| 23 | 2/5 = **40,0 %** | 1/5 = **20,0 %** |
| 24 | 2/4 = **50,0 %** | 1/4 = **25,0 %** |
| 25 | 2/4 = **50,0 %** | 1/4 = **25,0 %** |
| 26 | 4/5 = **80,0 %** | 1/5 = **20,0 %** |

**Huit éditions**, toutes sur la bonne réponse, sept sur une question
préexistante et une sur une question écrite dans cette unité. Le geste est
toujours le même : la clause de justification quitte le choix et rejoint
l'explication, qui la porte déjà ou l'accueille.

L'édition la plus nette porte sur une bonne réponse de **167 caractères** face à
un distracteur le plus long de **77** — un écart que n'importe quel candidat
peut lire sans rien savoir du sujet. Celle-là n'a pas été faite pour la mesure :
elle corrigeait un vrai défaut.

Les lots 24 et 25 illustrent en revanche le problème de dénominateur signalé par
les deux rapports précédents. Avec **quatre** questions, la suite des valeurs
possibles est 0 / 25 / 50 / 75 / 100 %. Le seuil étant strict (`taux > base`),
le lot passe à 25 % et échoue à 50 % : **une seule question biaisée est
tolérée, la deuxième fait échouer le lot**. Le pas de la mesure est ici plus
grand que l'effet mesuré.

**La règle n'a pas été touchée.** La demande de décision reste celle des deux
rapports précédents, et c'est sa troisième formulation consécutive :
`aud10` devrait-il rendre `NOT_APPLICABLE` sous un dénominateur minimum — une
dizaine de questions — plutôt que de conclure sur quatre ? Décision de
gouvernance (§15) ; elle n'est pas prise ici.

## 4. Ce qui a été ajouté

**Neuf questions**, toutes `LEARNING`, toutes en anglais, quatre `hard` et cinq
`medium`. Aucune question supprimée, aucune clé de réponse déplacée, aucun
énoncé modifié.

Recouvrement avec le holdout, mesuré sur les **bonnes réponses seules** et sans
lire le contenu du holdout : maximum **0,13** sur les neuf (le seuil qui avait
imposé une réécriture au lot 08 était 0,31). Aucune réécriture nécessaire.

## 5. État mesuré

| | Avant | Après |
|---|---:|---:|
| items | 6 | 6 |
| questions | 19 | **28** |
| dont LEARNING / VALIDATION / HOLDOUT | 12 / 6 / 1 | **21 / 6 / 1** |
| outcomes portant un id `OUT` | 0 | **29** |
| outcomes évalués | 20 | **29** |
| questions portant un `question_archetype` | 0 | **28** |
| archétypes distincts employés | 0 | **5** sur 11 |

Corpus, en cumulé et non en nouveau : **716 questions** (707 avant cette unité),
695 anglaises et 21 françaises, dont 270 `hard` (269 anglaises, 99,6 %).

Périmètre vérifié par script contre `53d73cd` : **6 items de matrice** touchés,
tous des lots 22 à 26 ; **9 questions ajoutées, 0 supprimée** ; 7 textes de choix
préexistants édités ; **0 clé de réponse déplacée, 0 énoncé modifié** ; 1 source
corrigée (§2).

Aucun des six cours ne dépasse le budget `REV-001` de son niveau : tous sont
`STANDARD` (budget 900 mots de corps), mesurés de 426 à 551 mots.

## 6. Portes

| Porte | Résultat |
|---|---|
| `php bin/cert validate` | PASS — exit 0, **0 bloquant** |
| idem sous sonde « lots 22-26 raffinés » | PASS — exit 0 |
| la sonde fait bien remonter `ARC-001` en `[ERROR]` | PROUVÉ — défaut injecté, règle déclenchée, fichier restauré (SHA-256 vérifié) |
| `check_annotation_map.py` | PASS — 0 problème sur 19, du premier coup |
| `vendor/bin/phpunit` | PASS — 245 tests, 13 964 assertions |
| `composer gate-full` | PASS — exit 0 |
| `npm --prefix website run a11y` | PASS — 22 pages, 0 violation (inclus dans `gate-full`) |
| `prove_framework_rules_fail.py` | PASS — 7 cas, restauration byte-identique |
| `aud01` … `aud11` | PASS — 0 finding chacun, `aud03` après la correction du §2 |
| `aud10` sur les cinq lots | PASS — voir §3 |
| Couverture officielle | 100 % (163/163 EXAM_READY) — inchangée |
| *Pull request* | voir §8 |
| Fusion | voir §8 |
| Déploiement + *smoke test* production | voir §8 |

## 7. Ce que cette unité change au tableau de bord

`docs/progress/certification-readiness.md`, régénéré : **`NOT_REFINED` passe de
6 à 0**. Les six derniers items qui échouaient à un critère automatique de leur
niveau le satisfont désormais.

Les **19** items restés `PARTIALLY_REFINED` sont ceux des lots 14 à 26 : ils
satisfont tous les critères automatiques, mais leur entrée de journal n'est pas
encore écrite, parce qu'elle ne s'écrit **qu'après fusion et vérification en
production**. C'est le comportement voulu, pas un reste : le journal enregistre
une livraison, pas une intention.

## 8. Statut de livraison

À l'écriture de ce rapport : **MISSING** pour la *pull request*, la fusion, le
déploiement et le *smoke test* production. Ces quatre lignes sont renseignées
dans l'entrée de journal, et l'entrée n'est écrite qu'une fois les quatre
réelles.

## 9. Limites

- **Aucun des six cours n'a été lu intégralement.** Les neuf questions viennent
  des sources amont listées au §1, pas des cours.
- Les neuf questions et les huit éditions **n'ont pas été relues par un humain**.
- La question du §3 reste **ouverte**, posée pour la troisième unité consécutive.
- La limite du §2 — `--offline` ne peut pas prouver qu'une source existe — est
  signalée, **pas corrigée** : rendre `aud03` en ligne obligatoire en CI
  dépendrait d'un egress que le *runner* ne garantit pas.
- Le **recouvrement holdout** du §4 est une mesure d'isolement fonctionnel sur
  les bonnes réponses ; ce n'est pas une garantie de confidentialité, le dépôt
  étant public.
