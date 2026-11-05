---
id: CRS-htvm3b1gas0d
official_item: OIT-92ctf3sy3ddk
title: "Dependency Injection component"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-27"
format: pedagogical
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/dependency_injection.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/components/dependency_injection.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: 'Basic Usage (register, addArgument, setParameter, Reference, addMethodCall); Getting Services That Don''t Exist — "EXCEPTION_ON_INVALID_REFERENCE: throws an exception at compile time (this is the default behavior)"; Avoiding your Code Becoming Dependent on the Container'
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container/injection_types.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container/injection_types.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: 'Types of Injection — Constructor Injection, Immutable-setter Injection, Setter Injection, Property Injection, with their advantages and disadvantages'
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/service_container.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/service_container.rst"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    commit_sha: "eea05cbfe063b9cf99afaf303b8cad76757f43bb"
    symbol_or_lines: 'Public Versus Private Services — "Every service defined is private by default"; "you should fetch services using dependency injection instead of using $container->get()"'
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DependencyInjection/ContainerBuilder.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/ContainerBuilder.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "ContainerBuilder::register(), compile(bool $resolveEnvPlaceholders = false), get()"
    verified_at: "2026-10-27"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/HttpKernel/Kernel.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Kernel.php"
    repository: "symfony/symfony"
    branch: "8.0"
    commit_sha: "6f841c00f41e5c037d40e1d739e2dc602c8f289d"
    symbol_or_lines: "Kernel::initializeContainer() — includes the cached container, checks its freshness only in debug; otherwise buildContainer(), compile(), dumpContainer() with PhpDumper"
    verified_at: "2026-10-27"
outcome_sections:
  OUT-svr2vz7ebkv7: "Injecter ou récupérer : la distinction de fond"
  OUT-6ah70qtk60fe: "Les quatre formes d'injection"
  OUT-dvs57dgffn57: "Le component seul, ligne par ligne"
---

## Objectif

Comprendre ce que la dependency injection résout, distinguer recevoir un service
et le demander au container, choisir la forme d'injection adaptée à une
dependency, et savoir utiliser le component seul jusqu'à la compilation. Le
container dans une application — definitions, visibilité, configuration — a sa
propre page, *Service container*.

## L'essentiel

Une class **reçoit** ses dependencies au lieu de les fabriquer ou de les
demander au container. Elle les reçoit par l'une des **quatre** formes
d'injection, selon que la dependency est obligatoire ou facultative. Le
component, autonome, décrit les services, puis les **compile** : un container
figé, d'où l'on ne fait qu'un `get()`, à l'entry point.

## Le schéma

```text
la class déclare   NewsletterManager::__construct(Mailer $mailer)
on décrit          register('mailer', …)->addArgument('%mailer.transport%') ; register('newsletter_manager', …)->addArgument(new Reference('mailer'))
on compile         compile() : passes, références vérifiées, privés retirés ou inlinés, parameters figés
on obtient         un seul get('newsletter_manager'), à l'entry point ; de l'injection partout ailleurs
```

## Le fil rouge : un `NewsletterManager` et son `Mailer`

```php
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

$container = new ContainerBuilder();
$container->setParameter('mailer.transport', 'sendmail');
$container->register('mailer', Mailer::class)
    ->addArgument('%mailer.transport%');                 // un parameter : une valeur
$container->register('newsletter_manager', NewsletterManager::class)
    ->addArgument(new Reference('mailer'))               // un service
    ->setPublic(true);                                   // privé par défaut
$container->compile();
$manager = $container->get('newsletter_manager');
```

`addArgument('mailer')` passerait la **string** `'mailer'` : seul
`new Reference('mailer')` désigne un service.

## Injecter ou récupérer : la distinction de fond

Une class qui reçoit `ContainerInterface` et appelle `get('mailer')` **ne fait
pas** de dependency injection : elle **récupère**. Le critère est ce qu'elle
reçoit — un object qui fournit n'importe quoi —, pas le moyen.

| | Injection | Container entier | Service locator |
|---|---|---|---|
| La signature dit ce qu'elle utilise | oui | **non** | une liste déclarée |
| Identifier faux détecté | à la compilation | à l'exécution | à l'exécution |
| Statut | la voie normale | à éviter | cas particulier légitime |

« As few times as possible at the entry point » : dans une application, cet
entry point est le kernel.

## Les quatre formes d'injection

| La dependency est… | Forme | Écriture avec le component |
|---|---|---|
| **obligatoire** | constructor | `->addArgument(new Reference('mailer'))` |
| facultative, service **immuable** | immutable-setter (`withMailer()` renvoie un clone) | `->addMethodCall('withMailer', [...], true)` |
| **facultative** | setter | `->addMethodCall('setMailer', [...])` |
| imposée par du code tiers | property publique — « mainly only disadvantages » | `->setProperty('mailer', ...)` |

- Le constructor garantit la présence et la stabilité ; le setter ne garantit
  ni l'appel ni la stabilité.
- `#[Required]` (`Symfony\Contracts\Service\Attribute`) n'agit **que** sur un
  service autowiré ; l'autowiring n'appelle pas un setter qui ne le porte pas.
  Un immutable-setter autowiré doit renvoyer `static`, sinon son résultat est
  jeté.
- Deux constructors qui se reçoivent mutuellement : `compile()` lève
  `ServiceCircularReferenceException`. Un **setter** d'un côté casse le cycle ;
  un immutable-setter, non.

## Le component seul, ligne par ligne

`symfony/dependency-injection` s'installe seul et fournit un container
**PSR-11**. Après `compile()` :

| Appel | Résultat |
|---|---|
| `get()` d'un service public | l'object ; deux `get()` donnent la **même** instance, sauf `setShared(false)` |
| `get()` ou `has()` d'un service **privé** | `ServiceNotFoundException` « …has been removed or inlined… » ; `false` |
| `register()` | `BadMethodCallException` : « Adding definition to a compiled container is not allowed. » |
| `setParameter()` | `LogicException` : « Impossible to call set() on a frozen ParameterBag. » |
| `getParameter()` | fonctionne : figé, pas illisible |

- Une référence vers un service jamais déclaré fait **échouer la
  compilation** (« …has a dependency on a non-existent service "mailer" ») :
  c'est `EXCEPTION_ON_INVALID_REFERENCE`, le défaut.
- `new Reference('mailer', ContainerInterface::IGNORE_ON_INVALID_REFERENCE)`
  dans un `addMethodCall()` : l'appel est **omis** si le service manque.
  `NULL_ON_INVALID_REFERENCE` passerait `null`.

## Pièges d'examen

**« Le container est injecté, donc c'est de la dependency injection. »** Faux.
Le critère est ce que la class reçoit, pas la façon dont elle le reçoit. Une
class qui reçoit le container et appelle `get()` fait de la récupération de
services, et ses dependencies réelles ont disparu de sa signature.

**« Il existe trois formes d'injection. »** La documentation 8.0 en décrit
quatre : constructor, immutable-setter, setter et property. Un énoncé qui
présente l'immutable-setter (une method `withX()` qui renvoie un clone) comme
une invention ou une mauvaise pratique se trompe.

**Constructor et setter inversés.** Le constructor est la voie normale, pour la
dependency **obligatoire** ; le setter est réservé à la dependency
**facultative** (ou à un cycle). Un choix qui met une dependency
indispensable dans un setter crée un object qui peut exister sans elle.

**`#[Required]` sans autowiring.** L'attribute seul ne fait rien : sur un
service non autowiré, le setter n'est jamais appelé et la property reste
`null`. À l'inverse, l'autowiring n'appelle pas un setter qui n'a pas
`#[Required]`.

**Un service privé n'est pas « non injectable ».** Privé interdit seulement le
`get()` après compilation ; le service reste injectable dans les autres. Et
c'est le défaut : un `register()` sans `setPublic(true)` donne un service
privé.

**`compile()` fige.** Après compilation, ni nouvelle definition ni nouveau
parameter : `register()` lève une `BadMethodCallException`, `setParameter()` une
`LogicException`. Lire un parameter reste possible.

**Une référence absente casse `compile()`, pas `get()`.** Par défaut, un
service qui référence un service inexistant fait échouer la compilation, même
si personne ne demande jamais ce service.

**Un cycle entre deux constructors aussi.** Deux services qui se reçoivent
mutuellement par leur constructor lèvent `ServiceCircularReferenceException`
dès `compile()`, pas au premier appel. Un setter d'un côté suffit à le casser.

**`addArgument('mailer')` n'injecte pas le service `mailer`.** C'est la string
`'mailer'` qui part dans le constructor. Pour un service, il faut
`new Reference('mailer')`.

## Application

- Un service a absolument besoin d'un `Serializer` → constructor, typé par une
  interface.
- Un `Tracker` facultatif doit être ignoré s'il n'existe pas → setter et
  `IGNORE_ON_INVALID_REFERENCE`.

## QCM d'entraînement

**Question 1.** Combien de formes d'injection la documentation Symfony 8.0
décrit-elle, page *Types of Injection* ?

- **A.** Deux : par constructor et par setter
- **B.** Trois : par constructor, par setter et par property
- **C.** Quatre : par constructor, immutable-setter, setter et property
- **D.** Cinq : par constructor, setter, property, attribute et container, chacun avec sa propre page

<details>
<summary>Correction</summary>

**Bonne réponse : C.** B oublie l'immutable-setter, une method qui renvoie un
clone portant la dependency ; un attribute n'est pas une forme d'injection.

</details>

**Question 2.** Que se passe-t-il ?

```php
$c = new ContainerBuilder();
$c->register('cart', Cart::class)->addArgument(new Reference('pricing'));
$c->register('pricing', Pricing::class)->addArgument(new Reference('cart'));
$c->compile();
```

- **A.** `compile()` réussit, puis le premier `get('cart')` provoque une loop infinie
- **B.** `compile()` lève une `ServiceCircularReferenceException`
- **C.** `compile()` réussit et le container injecte `null` dans un constructor
- **D.** `compile()` réussit ; l'erreur n'apparaît que si un service est public

<details>
<summary>Correction</summary>

**Bonne réponse : B.** Le cycle est détecté dès la compilation ; faire passer
l'un des deux côtés par un setter le casserait.

</details>

**Question 3.** Dans une definition du component, quelle écriture passe le
**service** `mailer` au constructor ?

- **A.** `->addArgument('mailer')`
- **B.** `->addArgument('%mailer%')`
- **C.** `->addArgument(\App\Service\Mailer::class)`
- **D.** `->addArgument(new Reference('mailer'))`

<details>
<summary>Correction</summary>

**Bonne réponse : D.** A et C passent des strings, et B désigne un parameter :
absent, il fait échouer `compile()` (« non-existent parameter »).

</details>

**Question 4.** `setTracker()` doit être **omis** si aucun service `tracker`
n'est déclaré. Quelle écriture ?

- **A.** `->addMethodCall('setTracker', [new Reference('tracker', ContainerInterface::IGNORE_ON_INVALID_REFERENCE)])`
- **B.** `->addMethodCall('setTracker', [new Reference('tracker')])`, car un setter est toujours facultatif
- **C.** `->addArgument(new Reference('tracker', ContainerInterface::NULL_ON_INVALID_REFERENCE))`, qui retire l'argument du constructor
- **D.** `->setProperty('tracker', new Reference('tracker'))`

<details>
<summary>Correction</summary>

**Bonne réponse : A.** B garde le comportement par défaut, qui fait échouer
`compile()` ; C passerait `null` au constructor au lieu de l'omettre.

</details>

## Fiche express

**Si tu dois retenir seulement 7 choses :**

- **Recevoir le container n'est pas de l'injection** : un seul `get()`, à
  l'entry point.
- **Quatre formes** : constructor, immutable-setter, setter, property.
- **Obligatoire → constructor. Facultative → setter.** Un cycle se casse par un
  setter.
- **`#[Required]`** n'agit que sur un service autowiré.
- **`Reference`** = un service ; `%x%` = un parameter ; sinon une string.
- Service **privé par défaut** ; `compile()` **fige** (ni `register()` ni
  `setParameter()`).
- Référence absente ou cycle : **`compile()`** échoue, pas `get()`.

Moyen mnémotechnique : **« C-I-S-P : Constructor, Immuable, Setter, Public »**,
de la plus sûre à la moins sûre.

```bash
composer require symfony/dependency-injection
```

## Sources officielles

- [The DependencyInjection Component](https://github.com/symfony/symfony-docs/blob/8.0/components/dependency_injection.rst)
- [Types of Injection](https://github.com/symfony/symfony-docs/blob/8.0/service_container/injection_types.rst)
- [Service Container, « Public Versus Private Services »](https://github.com/symfony/symfony-docs/blob/8.0/service_container.rst)
- [DependencyInjection 8.0, `ContainerBuilder`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DependencyInjection/ContainerBuilder.php)
- [HttpKernel 8.0, `Kernel::initializeContainer()`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/HttpKernel/Kernel.php)

