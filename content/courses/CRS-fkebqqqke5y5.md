---
id: CRS-fkebqqqke5y5
official_item: OIT-adhs2ny9hc5f
title: "Service decoration"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-28"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/service_decoration.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container/service_decoration.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: "decorates, .inner, decoration_inner_name, decoration_priority, decoration_on_invalid, #[AsDecorator], #[AutowireDecorated]"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Attribute/AsDecorator.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Attribute/AsDecorator.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "AsDecorator::__construct(decorates, priority = 0, onInvalid = EXCEPTION_ON_INVALID_REFERENCE)"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Compiler/DecoratorServicePass.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/DecoratorServicePass.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "DecoratorServicePass::process()"
    verified_at: "2026-10-28"
outcome_sections:
  OUT-tv6pkb930kss: "`.inner` : l'original n'est pas perdu"
  OUT-gxkp3y83mp10: "Le mécanisme : prendre la place, garder l'identifier"
  OUT-7mpy7kfscvv1: "Empiler des decorators : l'ordre contre-intuitif"
---

## Objectif

Envelopper un service existant sans le remplacer ni toucher à ses
consommateurs, atteindre l'original par `.inner`, empiler plusieurs decorators
dans le bon ordre et choisir le comportement quand la cible manque. *Quand*
décorer plutôt qu'employer un compiler pass relève de *Framework overloading*.

## Avant la règle : le problème à résoudre

Tu veux journaliser chaque email envoyé par l'application. Le service d'envoi,
`Mailer`, n'est pas à toi : il vient d'un bundle, ou il est utilisé par vingt
services. Trois solutions naïves, trois impasses :

- **modifier `Mailer`** : impossible s'il vient d'un bundle, et cela mélange deux
  responsabilités ;
- **le remplacer** par ta propre class : tu perds son comportement, que tu dois
  réécrire ;
- **changer les vingt consommateurs** pour qu'ils appellent ton service :
  interminable, et chaque nouveau consommateur doit y penser.

La **décoration** offre une quatrième voie : ton service prend la place de
`Mailer` dans le container, **reçoit l'original en argument**, et lui délègue
après avoir fait son travail. Les consommateurs ne voient rien.

## Le fil rouge : un `LoggingMailer`

```php
interface MailerInterface
{
    public function send(Message $message): void;
}

class Mailer implements MailerInterface { /* envoie vraiment */ }
```

Le decorator :

```php
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator(decorates: Mailer::class)]
class LoggingMailer implements MailerInterface
{
    public function __construct(
        private MailerInterface $inner,     // l'original
        private LoggerInterface $logger,
    ) {
    }

    public function send(Message $message): void
    {
        $this->logger->info('sending');
        $this->inner->send($message);       // délègue à l'original
    }
}
```

Toute la page suit ces deux classes : comment `LoggingMailer` prend la place de
`Mailer`, comment il atteint l'original, et ce qui se passe quand on en empile
plusieurs.

## Le cycle complet en un schéma

```text
AVANT                              APRÈS décoration
  id App\Mailer  ──► Mailer           id App\Mailer  ──► LoggingMailer
                                                            │ $inner
                                       id App\LoggingMailer.inner ──► Mailer (l'original, renommé)

LES CONSOMMATEURS                     demandent toujours « App\Mailer » (ou MailerInterface)
                                       → ils reçoivent LoggingMailer, qui délègue à Mailer

PLUSIEURS DECORATORS (priority 5 et 1)
  appliqués du plus prioritaire au moins prioritaire :
  Foo  →  Bar(Foo)  →  Baz(Bar(Foo))
          prio 5       prio 1          ← la plus BASSE finit à l'EXTÉRIEUR, appelée en premier
```

## Le mécanisme : prendre la place, garder l'identifier

Décorer, c'est demander au container de mettre un service **à la place** d'un
autre, en lui passant l'ancien. Deux écritures équivalentes :

```php
#[AsDecorator(decorates: Mailer::class)]
class LoggingMailer implements MailerInterface { /* … */ }
```

```yaml
services:
    App\Mailer\LoggingMailer:
        decorates: App\Mailer\Mailer
        arguments: ['@.inner']
```

Tout ce qui demandait `App\Mailer\Mailer` reçoit désormais le decorator.
**L'identifier ne change pas** : les autres services ne savent rien, et aucune
ligne n'est à modifier chez eux.

`#[AsDecorator]` prend trois arguments, lus dans le code 8.0 : `decorates`,
`priority` (0 par défaut) et `onInvalid` — les équivalents YAML de `decorates`,
`decoration_priority` et `decoration_on_invalid`.

### La condition : la même interface

Puisque le decorator est injecté **partout à la place** de la cible, il doit
être utilisable partout où elle l'était : il implémente la **même interface**.
Exécuté sur FrameworkBundle 8.0.15, la conséquence pour les consommateurs :

| Le consommateur est typé par… | Ce qu'il reçoit |
|---|---|
| l'interface `MailerInterface` | `Logging(Mailer)` : tout fonctionne |
| la **class** décorée `Mailer` | `TypeError` : « must be of type App\Mailer, App\LoggingMailer given » |

C'est une raison de plus de typer les dependencies par interface : la
décoration reste alors invisible.

### Décorer ou remplacer

| | Remplacer | Décorer |
|---|---|---|
| Écriture | redéfinir l'id avec une autre class | `decorates:` ou `#[AsDecorator]` |
| L'original | disparaît | reste accessible par `.inner` |
| Comportement d'origine | à réécrire | délégué, composable |

## `.inner` : l'original n'est pas perdu

Le service décoré n'est pas supprimé : il est **renommé** en
`<id du décorateur>.inner`. Exécuté sur FrameworkBundle 8.0.15 :
`debug:container App\LoggingMailer.inner` montre un service de class
`App\Mailer`. `@.inner` est le raccourci qui désigne ce service depuis la
definition du decorator.

Trois façons de le recevoir :

- **en autowiring**, un argument du decorator typé comme la cible est câblé sur
  `.inner` **automatiquement** — d'où l'exemple du fil rouge sans `arguments`
  (exécuté : le consommateur reçoit `Logging(Mailer)`) ;
- **`#[AutowireDecorated]`** sur l'argument le demande explicitement ;
- **en configuration explicite**, `'@.inner'`.

L'option `decoration_inner_name` change le nom : exécuté, `foo.original`
désigne bien l'original.

## Empiler des decorators : l'ordre contre-intuitif

Plusieurs services peuvent décorer la même cible. L'ordre est donné par
`decoration_priority`, entier valant `0` par défaut. C'est le point qui se rate
systématiquement :

> **une priority plus haute est appliquée plus tôt**, donc enveloppe la cible en
> premier, donc se retrouve **au plus près d'elle**, pas à l'extérieur.

```yaml
    Bar:
        decorates: Foo
        decoration_priority: 5
    Baz:
        decorates: Foo
        decoration_priority: 1
```

Exécuté avec DependencyInjection 8.0.15 : le container produit
`Baz(Bar(Foo))`. Lis-le de l'intérieur vers l'extérieur :

1. `Bar` (priority 5) est appliqué d'abord : `Bar(Foo)` ;
2. `Baz` (priority 1) est appliqué ensuite, sur le résultat : `Baz(Bar(Foo))`.

À l'exécution, c'est donc `Baz`, la couche **externe**, qui est appelé en
premier, puis `Bar`, puis `Foo`.

À priority égale, l'ordre de déclaration départage : exécuté, `bar` puis `baz`
donnent `baz(bar(Foo))`, l'ordre inverse `bar(baz(Foo))`. **Le premier déclaré
est le plus interne.**

## Si la cible n'existe pas

`decoration_on_invalid` (ou `onInvalid` dans l'attribute) décide. Exécuté, avec
une cible `nope` absente :

| Valeur | Résultat |
|---|---|
| `exception` (défaut) | `ServiceNotFoundException` : « has a dependency on a non-existent service "nope" » |
| `ignore` | le decorator est **retiré** : ni `bar` ni `nope` n'existent |
| `null` | `nope` existe et désigne le decorator, avec `null` en guise d'original |

Utile pour un bundle qui décore un service **optionnel** ; avec `null`, le
decorator doit accepter un argument nullable et vérifier sa présence.

## Concrètement dans ton application

- Ajouter du log, du cache, des métriques, un contrôle autour d'un service
  existant : **décore-le**, avec `#[AsDecorator]` sur ta class.
- Type ta class par l'**interface** de la cible, et ton argument `$inner` aussi :
  l'autowiring le câble sur `.inner`.
- Plusieurs couches ? Pose des `priority` explicites et vérifie l'ordre avec
  `php bin/console debug:container <id>` et ses `.inner`.
- Un bundle décore un service qui peut ne pas exister ? `onInvalid` à
  `ContainerInterface::IGNORE_ON_INVALID_REFERENCE`.

## À comprendre

- **Pourquoi l'identifier est-il gardé ?** Parce que c'est lui que demandent les
  consommateurs : le garder, c'est rendre la décoration invisible.
- **Pourquoi renommer l'original plutôt que le supprimer ?** Parce que le
  decorator doit lui déléguer : l'original doit rester un service.
- **Pourquoi la plus haute priority est-elle interne ?** Parce que les
  decorators sont appliqués un par un, du plus prioritaire au moins prioritaire,
  chacun autour du résultat précédent : le premier appliqué se retrouve collé à
  la cible.
- **Pourquoi la même interface ?** Parce que le decorator est injecté là où la
  cible l'était.

## À mémoriser

- `decorates:` / `#[AsDecorator(decorates:, priority: 0, onInvalid:)]`.
- L'identifier reste celui de la cible ; l'original devient **`<décorateur>.inner`**
  (`decoration_inner_name` pour le renommer).
- `.inner` : automatique en autowiring (argument typé comme la cible),
  `#[AutowireDecorated]`, ou `'@.inner'`.
- **Priority haute = couche interne** ; priority basse = couche externe, appelée
  en premier. À égalité, le premier déclaré est interne.
- `on_invalid` : `exception` (défaut), `ignore` (decorator retiré), `null`
  (décore avec `null`).
- Moyen mnémotechnique : **« Haute priority, au cœur ; basse priority, à la
  porte »**.

## Pièges d'examen

**L'original n'est pas supprimé** : il devient `<décorateur>.inner`.

**La priority la plus haute est la plus interne.** Elle est appliquée en
premier, donc enveloppe directement le service décoré ; c'est la plus basse qui
finit à l'extérieur.

**Le decorator prend l'identifier de la cible** ; rien ailleurs n'a besoin de
changer — à condition que les consommateurs typent l'interface.

**`ignore` retire le decorator**, il ne décore pas « à vide ».

**Un consommateur typé par la class décorée casse.** Il reçoit le decorator, qui
n'est pas de cette class : `TypeError`. Seul le typage par interface rend la
décoration invisible.

**À priority égale, le premier déclaré est le plus interne**, pas le dernier.

## Application : trois scénarios

**Scénario 1 — Mesurer le temps d'un client HTTP.** Tu veux chronométrer chaque
appel du client HTTP, utilisé partout. *Solution.* Une class `TimedHttpClient
implements HttpClientInterface`, `#[AsDecorator(decorates: 'http_client')]`, qui
reçoit l'original en `$inner`, mesure, et délègue. Aucun consommateur ne change.

**Scénario 2 — Deux couches.** Un cache et un log autour du même service. Le log
doit voir **chaque** appel, même ceux servis par le cache. *Solution.* Le log
doit être la couche **externe** : la priority la **plus basse**. Le cache, plus
interne : priority plus haute.

**Scénario 3 — Le `TypeError` après décoration.** Depuis l'ajout d'un decorator,
un service plante avec « must be of type App\Mailer, App\LoggingMailer given ».
*Diagnostic.* Il est typé par la class concrète. *Correction.* Le typer par
`MailerInterface`.

## QCM d'entraînement

Six questions sur la décoration. Engage-toi avant de déplier la correction.

**Question 1.** `AuditCache` décore le service `app.cache`. Que reçoit un service qui demandait `app.cache` ?

- **A.** L'original, car la décoration ne concerne que les nouveaux consommateurs
- **B.** `AuditCache`, sous le même identifier `app.cache`
- **C.** Une erreur, tant que le consommateur ne demande pas `AuditCache` par son id
- **D.** L'original, sous l'identifier `app.cache.inner`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Le decorator prend la place de la cible sous son
identifier : tous les consommateurs reçoivent le decorator, sans rien changer.

A invente une distinction entre anciens et nouveaux consommateurs. C suppose
qu'il faudrait changer l'id demandé : c'est justement ce que la décoration
évite. D est le piège : `.inner` désigne l'original **pour le decorator**, et
son nom est `<id du décorateur>.inner`, pas celui de la cible.

</details>

**Question 2.** Trois decorators visent `Repo` : `Metrics` (priority 10), `Cache` (priority 5) et `Log` (priority 0). Quel object le container construit-il ?

- **A.** `Metrics(Cache(Log(Repo)))`
- **B.** `Log(Cache(Metrics(Repo)))`
- **C.** `Cache(Metrics(Log(Repo)))`, l'ordre suivant la déclaration
- **D.** Une erreur : une même cible ne peut avoir qu'un decorator

<details>
<summary>Correction</summary>

**Bonne réponse : B.** La plus haute priority est appliquée en premier et finit
au plus près de la cible : `Metrics(Repo)`, puis `Cache(…)`, puis `Log(…)`.
Exécuté avec deux couches : priorities 5 et 1 donnent `Baz(Bar(Foo))`.

A est le piège : il place la plus haute priority à l'extérieur, comme si elle
était « la plus importante donc la première appelée ». C invente un ordre de
déclaration qui primerait sur la priority. D est faux : on empile autant de
decorators qu'on veut.

</details>

**Question 3.** Dans la pile précédente, quel decorator est appelé en premier quand un consommateur utilise `Repo` ?

- **A.** `Metrics`, la plus haute priority
- **B.** `Repo`, l'original
- **C.** Celui déclaré en premier dans le file
- **D.** `Log`, la couche externe

<details>
<summary>Correction</summary>

**Bonne réponse : D.** Le consommateur reçoit l'object externe, `Log(…)`, qui
délègue à `Cache`, qui délègue à `Metrics`, qui délègue à `Repo`.

A est le piège symétrique : la plus haute priority est la couche la plus
interne, donc appelée en dernier avant l'original. B oublie que tout l'intérêt
est d'intercepter avant l'original. C confond avec le départage à priority
égale.

</details>

**Question 4.** `#[AsDecorator(decorates: 'payment.gateway')]` est posé sur une class, et aucun service `payment.gateway` n'existe dans cette application. Que se passe-t-il par défaut ?

- **A.** La compilation échoue : « has a dependency on a non-existent service »
- **B.** Le decorator est retiré en silence
- **C.** Le decorator est enregistré seul, avec `null` en guise d'original
- **D.** Le decorator devient lui-même le service `payment.gateway`, sans original

<details>
<summary>Correction</summary>

**Bonne réponse : A.** `onInvalid` vaut `EXCEPTION_ON_INVALID_REFERENCE` par
défaut (lu dans `AsDecorator`) ; exécuté, la compilation lève
`ServiceNotFoundException`.

B décrit `ignore`, C et D décrivent `null` : ce sont des choix explicites, pas le
défaut.

</details>

**Question 5.** Un `TracingMailer` décore `App\Mailer` en autowiring. Son constructor prend `MailerInterface $inner`, et aucun argument n'est configuré. Que reçoit `$inner` ?

- **A.** Le `TracingMailer` lui-même, d'où une récursion infinie
- **B.** L'original `App\Mailer`, câblé automatiquement sur `.inner`
- **C.** `null`, faute d'un `'@.inner'` explicite
- **D.** Une erreur d'autowiring : deux services implémentent maintenant `MailerInterface`

<details>
<summary>Correction</summary>

**Bonne réponse : B.** En autowiring, un argument du decorator typé comme la
cible est câblé sur `.inner` automatiquement (exécuté : le consommateur reçoit
`Logging(Mailer)`).

A est la crainte naturelle, mais le container l'évite. C oublie le câblage
automatique : `'@.inner'` n'est nécessaire qu'en configuration explicite. D
oublie que l'alias de l'interface pointe vers l'identifier décoré.

</details>

**Question 6.** Deux decorators de priority 0, `First` puis `Second`, sont déclarés dans cet ordre sur la même cible `Core`. Que produit le container ?

- **A.** `First(Second(Core))`
- **B.** Une erreur : deux decorators de même priority sont ambigus
- **C.** `Second(First(Core))`
- **D.** `First(Core)` seulement, le second étant ignoré

<details>
<summary>Correction</summary>

**Bonne réponse : C.** À priority égale, l'ordre de déclaration départage, et le
premier déclaré est le plus interne (exécuté : `bar` puis `baz` donnent
`baz(bar(Foo))`).

A est le piège : il place le premier déclaré à l'extérieur. B invente une
ambiguïté. D invente une limite.

</details>

## Fiche express

**Si tu dois retenir seulement 6 choses :**

- Décorer = **prendre la place** de la cible sous **son identifier** et recevoir
  l'original : `#[AsDecorator(decorates: …)]` ou `decorates:`.
- L'original devient **`<décorateur>.inner`** ; en autowiring, l'argument typé
  comme la cible le reçoit tout seul.
- Le decorator implémente la **même interface** ; un consommateur typé par la
  class décorée prend une `TypeError`.
- **Haute priority, au cœur ; basse priority, à la porte** : `5` puis `1` donnent
  `Baz(Bar(Foo))`, et `Baz` est appelé en premier.
- À priority égale, le **premier déclaré est interne**.
- Cible absente : `exception` (défaut), `ignore` (decorator retiré), `null`.

**En une phrase, pour tout reconstruire :**

```text
DECORATOR → prend l'ID de la cible → reçoit l'original en .inner → délègue
  pile : priority haute appliquée d'abord = couche interne ; basse = externe, appelée en premier
  cible absente : exception | ignore (retiré) | null
```

## Sources officielles

- [How to Decorate Services](https://github.com/symfony/symfony-docs/blob/8.0/service_container/service_decoration.rst)
- [DependencyInjection 8.0, `Attribute\AsDecorator`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Attribute/AsDecorator.php)
- [DependencyInjection 8.0, `Compiler\DecoratorServicePass`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/DecoratorServicePass.php)
