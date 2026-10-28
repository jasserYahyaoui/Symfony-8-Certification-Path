---
id: CRS-w6yfxm6ad4zy
official_item: OIT-wm3qdqemtap9
title: "Services autowiring"
content_level: DEEP
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-28"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/autowiring.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container/autowiring.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: "type-based resolution, aliases, named autowiring aliases, Target, registerAliasForArgument, Autowire, AutowireServiceClosure, AutowireCallable, AutowireMethodOf, Required, union and intersection types"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Compiler/AutowirePass.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/AutowirePass.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "getAutowiredReference() — a Target naming a service id already aimed at by a named alias of the type; getCombinedAlias() — union and intersection types"
    verified_at: "2026-10-28"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/Attribute/Target.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Attribute/Target.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "Target::getParsedName() — camelCase normalization"
    verified_at: "2026-10-28"
outcome_sections:
  OUT-7k7n0avtawev: "La règle, en une phrase : par le type"
  OUT-h9f0rmgswrcz: "Pourquoi une interface a besoin d'un alias"
  OUT-5sh0v9zbqw10: "Trois façons de départager"
  OUT-06wrfq0kv0v0: "Ce que `#[Target]` attend"
  OUT-6j9rnmzbnkzn: "Câbler ce qui n'est pas un service"
---

## Objectif

Comprendre par quoi le container remplit un argument, pourquoi deux
implémentations d'une même interface le bloquent, les trois façons de trancher,
et ce que l'autowiring ne peut pas deviner.

## Avant la règle : le problème à résoudre

Un `TwitterClient` a besoin d'un transformer de texte :

```php
class TwitterClient
{
    public function __construct(private TransformerInterface $transformer)
    {
    }
}
```

Sans autowiring, il faudrait écrire pour **chaque** service la liste de ses
arguments : `arguments: ['@App\Util\Rot13Transformer']`. Avec cent services,
cent déclarations, à tenir à jour à chaque nouveau constructor.

L'autowiring lit le constructor et remplit les arguments **seul**. Il ne devine
rien au hasard : il applique une règle simple, et quand elle ne suffit pas, il
s'arrête avec un message qui dit quoi faire. Ce cours est cette règle, et ses
trois sorties de secours.

## Le fil rouge : deux transformers

```php
namespace App\Util;

interface TransformerInterface
{
    public function transform(string $value): string;
}

class Rot13Transformer implements TransformerInterface { /* … */ }
class UppercaseTransformer implements TransformerInterface { /* … */ }
```

Tant que seul `Rot13Transformer` existe, tout marche. L'arrivée
d'`UppercaseTransformer` casse des services qui n'ont rien changé : c'est le
cœur du sujet.

## Le cycle complet en un schéma

```text
argument du constructor :  TransformerInterface $transformer
        │
        ▼
1. TYPE       un service (ou un alias) a-t-il pour identifier ce type exact ?
              ── oui → injecté          (le nom de l'argument n'intervient pas)
        │ non
        ▼
2. NOM        un alias nommé « TransformerInterface $transformer » existe-t-il ?
              (ou un #[Target('…')] sur l'argument)
              ── oui → injecté
        │ non
        ▼
3. REPLI      argument nullable avec default value → laissé tel quel (null)
              scalaire (string, int…) → erreur : « configure its value explicitly »
              interface à plusieurs implémentations → erreur : « maybe alias this interface »
        │
        ▼
   l'erreur n'existe que pour un service CONSERVÉ : un service privé jamais
   utilisé est retiré avant, sans rien lever
```

L'ordre réel du code mêle les étapes 1 et 2 (un alias nommé est consulté avant
l'alias par défaut) ; le schéma montre la logique à retenir.

## La règle, en une phrase : par le type

L'autowiring résout un argument **par son type**, en cherchant un service dont
l'identifier est ce type. La documentation 8.0 : il « looks for a service whose
id exactly matches the type-hint ».

```php
class Mailer
{
    public function __construct(private TransportInterface $transport) {}
}
```

Le container cherche un service d'identifier `App\Mail\TransportInterface`.
Comme les classes découvertes ont leur FQCN pour identifier, cela fonctionne
sans rien écrire.

**Le nom de l'argument n'intervient pas** — sauf dans les cas de départage
ci-dessous. Le renommer ne change rien ; changer son type change tout.

Si l'identifier d'un service n'est pas son FQCN (`app.rot13.transformer`), le
type ne le trouve plus : un **alias** `App\Util\Rot13Transformer:
'@app.rot13.transformer'` le rend de nouveau autowirable. C'est ainsi que les
bundles exposent leurs services : `Psr\Log\LoggerInterface` est un alias vers
le service `logger`.

### Les types union et intersection

La documentation 8.0 l'annonce : l'autowiring sait traiter un type union ou
intersection. Relu dans `AutowirePass::getCombinedAlias()` : il faut un alias
pour le type complet, **ou que chaque type soit un alias vers le même service**.

Exécuté, une class `AB` qui implémente `AInterface` et `BInterface` (un alias
automatique chacune, tous deux vers `AB`) :

| Argument | Résultat |
|---|---|
| `AInterface\|BInterface $u` | `AB` injecté |
| `AInterface&BInterface $i` | `AB` injecté |
| `GreeterInterface\|Rot13Transformer $x` (deux services différents) | erreur : « has type "…GreeterInterface\|…Rot13Transformer" but this class was not found » |

## Pourquoi une interface a besoin d'un alias

Une interface n'est le nom d'aucun service : personne ne l'instancie. Type-hinter
`TransformerInterface` échoue donc, à moins qu'un **alias** ne dise quelle
implémentation utiliser :

```yaml
services:
    App\Util\TransformerInterface: '@App\Util\Rot13Transformer'
```

Quand une interface n'a qu'**une seule** implémentation parmi les classes
découvertes, Symfony crée cet alias tout seul. Dès qu'il y en a deux, il ne
devine plus. Exécuté avec FrameworkBundle 8.0.15, deux implémentations de
`TransformerInterface` et un service qui l'attend :

> Cannot autowire service "Aw\Service\Consumer": argument "$transformer" of
> method "__construct()" references interface "Aw\Util\TransformerInterface"
> but no such service exists. You should maybe alias this interface to one of
> these existing services: "Aw\Util\Rot13Transformer",
> "Aw\Util\UppercaseTransformer".

La cause n'est pas l'absence de class, mais l'absence de choix — et le message
propose lui-même les candidats.

Le choix peut aussi se faire depuis la class : `#[AsAlias]` sur une
implémentation en fait l'alias de son interface. Exécuté : avec
`SmsSender` (porteur de `#[AsAlias]`) et `MailSender`, deux implémentations de
`SenderInterface` découvertes par `resource`, le consommateur reçoit
`SmsSender`. L'attribute est lu à la **découverte** : avec les mêmes classes
déclarées une à une par `set()`, autoconfiguration active, il n'a eu aucun effet
(exécuté, même erreur qu'avec deux implémentations sans alias).

## Trois façons de départager

**1. L'alias par défaut** — une implémentation gagne pour tout le monde :

```yaml
    App\Util\TransformerInterface: '@App\Util\Rot13Transformer'
```

**2. L'alias d'autowiring nommé** — l'interface *plus le nom de l'argument* :

```yaml
    App\Util\TransformerInterface $shoutyTransformer: '@App\Util\UppercaseTransformer'
```

Tout argument typé `TransformerInterface` **et nommé** `$shoutyTransformer`
reçoit alors l'implémentation criarde ; les autres gardent celle par défaut.
C'est l'unique cas où le nom de l'argument compte.

**3. `#[Target]`** — le même choix, déclaré sur l'argument :

```php
public function __construct(
    #[Target('shoutyTransformer')] private TransformerInterface $transformer,
) {}
```

`#[Target]` prend le **nom employé dans l'alias nommé**. Son avantage sur la
solution 2 : le nom de l'argument redevient libre, et une faute de frappe lève
une exception au lieu de retomber silencieusement sur l'implémentation par
défaut.

Depuis une passe ou une extension, `registerAliasForArgument()` crée l'alias
nommé. La documentation 8.0 montre un quatrième argument, un nom de `Target`
distinct du nom d'argument :

```php
$container->registerAliasForArgument(
    'app.uppercase_transformer',
    TransformerInterface::class,
    'shoutyTransformer',  // le nom d'argument
    'shouty',             // le nom pour #[Target]
);
```

Exécuté, avec `'loudTransformer'` et `'loud'` : `#[Target('loud')]` reçoit
l'implémentation criarde, quel que soit le nom de l'argument.

### Ce que l'exécution montre

Les deux alias déclarés, un consommateur à plusieurs arguments (exécuté) :

| Argument | Reçoit |
|---|---|
| `TransformerInterface $transformer` | `rot13` — l'alias par défaut |
| `TransformerInterface $shoutyTransformer` | `upper` — l'alias nommé |
| `TransformerInterface $shoutyTransfomer` (faute de frappe) | `rot13`, **sans erreur** |
| `#[Target('shoutyTransfomer')]` (même faute) | erreur : « has "#[Target('shoutyTransfomer')]" but no such target exists. Did you mean to target one of "shoutyTransformer", "loud" instead? » |
| `#[Target('shouty.transformer')]` | `upper` — le nom est normalisé en camelCase |

`debug:autowiring Transformer` liste l'alias par défaut et chaque alias nommé,
sous la forme `App\Util\TransformerInterface $shoutyTransformer`.

## Ce que `#[Target]` attend

`autowiring.rst` (8.0) avertit que `#[Target]` « **does not** accept service ids
or service aliases ». Exécuté, c'est plus nuancé :

| `#[Target(…)]` | Résultat |
|---|---|
| `'Aw\Util\UppercaseTransformer'`, cible d'un alias nommé du même type | injecté |
| `'Aw\Util\Rot13Transformer'`, cible du seul alias par défaut | « no such target exists » |

`AutowirePass::getAutowiredReference()` accepte un identifier de service
**lorsqu'un alias nommé de ce type le vise déjà**. La règle d'écriture reste celle
de la documentation — viser le nom de l'alias nommé —, mais l'affirmation absolue
est fausse.

## Câbler ce qui n'est pas un service

L'autowiring ne devine **que par le type**. Un argument scalaire n'a aucun
service correspondant — exécuté : « argument "$dir" of method "__construct()"
is type-hinted "string", you should configure its value explicitly. » Il faut
`bind`, un argument explicite, ou `#[Autowire]`, qui couvre tout ce que le type
ne peut pas exprimer :

```php
#[Autowire(service: 'monolog.logger.request')] LoggerInterface $logger,
#[Autowire('%kernel.project_dir%/data')]       string $dataDir,
#[Autowire(param: 'kernel.debug')]             bool $debug,
#[Autowire(env: 'bool:FEATURE_FLAG')]          bool $flag,
```

`#[Autowire(expression: '…')]` injecte enfin le résultat d'une expression
(documentation 8.0).

Un argument nullable avec une default value est laissé tel quel si rien ne
correspond : exécuté, un `?App\Missing\Nope $opt = null` reçoit `null`.

L'erreur apparaît à la compilation — **pour les services conservés**. Exécuté :
la même class au `string` non résolu, privée et jamais utilisée, est retirée et
ne lève rien ; dès qu'un service conservé la reçoit, la compilation échoue.

### Recevoir une closure plutôt qu'un service

La documentation 8.0 décrit trois attributes qui injectent une **closure** :

| Attribute | La closure… |
|---|---|
| `#[AutowireServiceClosure('id')]` | rend le service quand on l'appelle |
| `#[AutowireCallable(service: 'id', method: 'format')]` | appelle `format()` du service avec ses arguments |
| `#[AutowireMethodOf('id')]` | appelle la method qui porte le **nom de l'argument** |

Exécuté : juste après la construction du consommateur, le service visé par
`#[AutowireServiceClosure]` n'est pas encore construit ; appeler la closure le
rend.

### Les setters et les properties

Avec l'autowiring, le container appelle aussi toute method marquée
`#[Required]`, et remplit une property publique typée marquée `#[Required]`
(exécuté : les deux reçoivent le service).

## Concrètement dans ton application

- Une interface, une implémentation : rien à écrire.
- Une deuxième implémentation arrive : déclare l'alias par défaut tout de suite,
  ou mets `#[AsAlias]` sur celle qui gagne.
- Un consommateur veut l'autre : `#[Target('nomDeLAlias')]` plutôt qu'un nom
  d'argument magique.
- Un scalaire : `#[Autowire(param: …)]`, `#[Autowire(env: …)]` ou `bind`.
- Un bundle public : la documentation 8.0 recommande d'y **configurer
  explicitement** ses services, sans compter sur l'autowiring de l'application
  qui l'installe.
- Pour comprendre une erreur : `php bin/console debug:autowiring <mot>`.

## À comprendre

- **Pourquoi par le type ?** Parce que le type dit **ce dont** l'argument a
  besoin, le nom seulement comment on l'appelle.
- **Pourquoi la deuxième implémentation casse-t-elle tout ?** Parce que l'alias
  automatique n'existait que parce qu'il n'y avait pas de choix à faire.
- **Pourquoi `#[Target]` plutôt que l'alias nommé ?** Parce qu'une faute dans un
  nom d'argument tombe en silence sur le défaut, alors qu'une faute dans
  `#[Target]` s'arrête à la compilation.
- **Pourquoi aucun coût ?** Parce que le container est compilé : la
  documentation 8.0 précise qu'il n'y a « *no* performance penalty », sinon des
  reconstructions plus fréquentes en `dev`.

## À mémoriser

- Résolution par le **type** = l'identifier d'un service ou d'un alias.
- Une implémentation : alias automatique ; **deux : plus d'alias**.
- Départager : alias par défaut, alias nommé `Interface $argument`,
  `#[Target('nom')]` (normalisé en camelCase), ou `#[AsAlias]` sur la class.
- Union / intersection : chaque type doit mener au **même** service.
- Scalaire : jamais autowiré → `#[Autowire]`, `bind`, argument explicite.
- Nullable avec défaut : laissé à `null`.
- Erreurs seulement pour les services **conservés**.
- Moyen mnémotechnique : **« Type, puis Nom, puis rien »** — et `#[Target]`
  pour ne pas dépendre du nom.

## Pièges d'examen

- L'autowiring passe par le **type**, pas par le nom — sauf alias nommé et `#[Target]`.
- Deux implémentations d'une interface **suppriment** l'alias automatique.
- Une faute dans le nom d'argument retombe **en silence** sur l'alias par
  défaut ; la même faute dans `#[Target]` lève une exception.
- `#[Target]` vise le nom de l'**alias nommé** ; un identifier ne passe que s'il
  est déjà la cible d'un alias nommé du même type.
- Un scalaire ne s'autowire jamais.
- Un type union ne s'autowire que si **chacun** de ses types est un alias vers le
  **même** service : deux services différents, et la compilation échoue.
- `#[AsAlias]` est lu à la **découverte** des classes (`resource`) : sur un
  service déclaré un par un, il ne départage rien.

## Application : trois scénarios

**Scénario 1 — La deuxième implémentation.** Tu ajoutes un
`UppercaseTransformer`, et un service qui n'a pas bougé ne compile plus.
*Solution.* Le message propose les deux candidats : déclare
`App\Util\TransformerInterface: '@App\Util\Rot13Transformer'`, ou ajoute
`#[AsAlias]` sur `Rot13Transformer`.

**Scénario 2 — Un consommateur veut l'autre.** `MastodonClient` veut la version
criarde, sans renommer son argument. *Solution.* L'alias nommé
`App\Util\TransformerInterface $shoutyTransformer`, et
`#[Target('shoutyTransformer')]` sur l'argument de `MastodonClient`.

**Scénario 3 — Un directory de données.** `ReportWriter` attend `string
$dataDir`. *Solution.* `#[Autowire('%kernel.project_dir%/var/data')]` sur
l'argument : l'autowiring seul lèverait « configure its value explicitly ».

## QCM d'entraînement

Six questions sur l'autowiring. Tranche avant de lire la correction.

**Question 1.** Un `ReportExporter` prend `ExporterInterface $exporter`. Une deuxième class implémente `ExporterInterface`, sans autre changement. Que se passe-t-il à la compilation ?

- **A.** La dernière class découverte devient l'alias de l'interface
- **B.** Un échec qui propose les deux candidats
- **C.** L'alias reste sur la première implémentation, dans l'ordre alphabétique
- **D.** Le container injecte un iterable qui contient les deux implémentations

<details>
<summary>Correction</summary>

**Bonne réponse : B.** L'alias automatique n'existe que pour une implémentation
unique. Exécuté : « … but no such service exists. You should maybe alias this
interface to one of these existing services: … ».

A et C inventent une règle de choix. D confond avec un iterator tagué.

</details>

**Question 2.** L'alias nommé `ExporterInterface $csvExporter` existe. Un argument s'appelle `$csvExpoter` (faute de frappe). Que reçoit-il ?

- **A.** L'alias par défaut, sans aucune erreur
- **B.** L'implémentation CSV : le nom le plus proche est retenu
- **C.** Une erreur de compilation qui suggère « csvExporter »
- **D.** `null`, car aucun alias ne porte ce nom exact

<details>
<summary>Correction</summary>

**Bonne réponse : A.** Un nom d'argument inconnu ne lève rien : seul le type
compte, et l'alias par défaut répond (exécuté : `rot13` pour
`$shoutyTransfomer`).

B invente une correction. C est le piège : c'est le comportement de `#[Target]`,
pas d'un nom d'argument. D confond avec un argument nullable.

</details>

**Question 3.** Un argument est déclaré `#[Target('csv.exporter')] ExporterInterface $exporter`, et l'alias nommé est `ExporterInterface $csvExporter`. Que reçoit-il ?

- **A.** Une erreur : le nom doit être écrit exactement comme l'argument
- **B.** L'alias par défaut, car `#[Target]` n'accepte que des noms en camelCase
- **C.** L'implémentation de l'alias nommé `csvExporter`
- **D.** Le service dont l'identifier est littéralement `csv.exporter`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** `Target::getParsedName()` normalise le nom en camelCase :
`csv.exporter` devient `csvExporter` (exécuté avec `shouty.transformer`).

A et B ignorent la normalization. D est le piège : `#[Target]` vise un alias
nommé, pas un identifier libre.

</details>

**Question 4.** Un service attend `NormalizerInterface|SerializerInterface $s`. Les deux types sont des alias vers **deux services différents**. Que se passe-t-il ?

- **A.** Le premier type de l'union est retenu, ici `NormalizerInterface`
- **B.** Le container choisit le service qui implémente le plus d'interfaces
- **C.** L'argument reçoit `null`, puisque l'union reste ambiguë
- **D.** La compilation échoue : l'union ne mène pas à un seul service

<details>
<summary>Correction</summary>

**Bonne réponse : D.** `getCombinedAlias()` exige un alias pour le type complet,
ou que chaque type de l'union soit un alias vers le **même** service. Exécuté
avec deux services différents : « … but this class was not found ».

A et B inventent une règle de choix. C confond avec un argument nullable.

</details>

**Question 5.** Un constructor prend `int $maxRetries`, sans default value, et rien n'est configuré. Le service est utilisé par un controller. Que se passe-t-il ?

- **A.** `$maxRetries` reçoit `0`, la valeur neutre du type
- **B.** La compilation échoue : aucun service n'est un `int`
- **C.** Le container cherche un parameter nommé `max_retries`
- **D.** Le service est construit plus tard, quand la valeur est connue

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Aucun service n'est un scalaire. Exécuté avec `string
$dir` : « is type-hinted "string", you should configure its value explicitly ».
`#[Autowire(param: …)]`, `bind` ou un argument explicite règlent le cas.

A invente une valeur. C invente une correspondance de noms (c'est le travail de
`bind`, à déclarer). D invente un report.

</details>

**Question 6.** Deux classes implémentent `SenderInterface`, toutes deux découvertes par `resource`. Laquelle des écritures suivantes, posée sur `SmsSender`, la rend injectée partout où `SenderInterface` est demandée ?

- **A.** `#[Target('SmsSender')]`
- **B.** `#[AsTaggedItem('default')]`
- **C.** `#[AsAlias]`
- **D.** `#[Autoconfigure(public: true)]`

<details>
<summary>Correction</summary>

**Bonne réponse : C.** Sur une class découverte, `#[AsAlias]` en fait l'alias
de son interface ; exécuté, le consommateur reçoit `SmsSender`.

A se pose sur un argument, pas sur une class. B règle un index de tag. D rend
le service public, sans créer d'alias.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- L'autowiring cherche un service dont l'identifier est le **type** ; le nom de
  l'argument n'intervient pas.
- Une interface, une implémentation : alias **automatique** ; deux : **plus
  d'alias**, erreur avec la liste des candidats.
- Départager : alias par défaut, alias nommé `Interface $nom`, `#[Target]`,
  `#[AsAlias]` sur la class découverte.
- Faute dans un nom d'argument : **silence** ; faute dans `#[Target]` :
  **exception**.
- `#[Target]` vise l'**alias nommé**, normalisé en camelCase.
- Union / intersection : un alias pour le tout, ou le **même** service pour
  chaque type.
- Scalaire : **jamais** autowiré → `#[Autowire]`, `bind` ; nullable avec défaut :
  `null`.

**En une phrase, pour tout reconstruire :**

```text
TYPE (identifier exact) → NOM (alias nommé | #[Target]) → REPLI (null si nullable, sinon erreur)
  … et les erreurs n'existent que pour les services conservés
```

## Sources officielles

- [Defining Services Dependencies Automatically (Autowiring)](https://github.com/symfony/symfony-docs/blob/8.0/service_container/autowiring.rst)
- [DependencyInjection 8.0, `Compiler\AutowirePass`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Compiler/AutowirePass.php)
- [DependencyInjection 8.0, `Attribute\Target`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/Attribute/Target.php)
