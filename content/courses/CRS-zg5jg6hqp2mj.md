---
id: CRS-zg5jg6hqp2mj
official_item: OIT-se21g2xv6h4r
title: "Crawler object (CssSelector and DomCrawler components)"
content_level: STANDARD
language: fr
verification_status: VERIFIED
reviewed_at: "2026-10-02"
official_sources:
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/testing/dom_crawler.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/testing/dom_crawler.rst"
    anchor: "traversing"
    repository: "symfony/symfony-docs"
    branch: "8.0"
    verified_at: "2026-09-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DomCrawler/Crawler.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DomCrawler/Crawler.php"
    branch: "8.0"
    symbol_or_lines: "text(?string $default = null, bool $normalizeWhitespace = true); attr($attribute, $default)"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony/8.0/src/Symfony/Component/DomCrawler/Form.php"
    readable_url: "https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DomCrawler/Form.php"
    branch: "8.0"
    symbol_or_lines: "setNode() — button, submit input or form node"
    verified_at: "2026-10-02"
  - url: "https://raw.githubusercontent.com/symfony/symfony-docs/8.0/components/dom_crawler.rst"
    readable_url: "https://github.com/symfony/symfony-docs/blob/8.0/components/dom_crawler.rst"
    branch: "8.0"
    symbol_or_lines: "Forms — filter(...)->form()"
    verified_at: "2026-10-02"
---

## Objectif

Parcourir le document rendu, en extraire une valeur, et cliquer ou soumettre —
c'est le composant qui donne au test l'accès au HTML.

## Prérequis

L'objet client, et les sélecteurs CSS.

## Deux composants, un rôle chacun

**DomCrawler** parcourt le document et en extrait des nœuds. **CssSelector**
ne parcourt rien : il **traduit un sélecteur CSS en expression XPath**. Le
premier utilise le second.

C'est la distinction que l'examen teste. XPath est plus puissant mais illisible ;
CSS est plus simple et couvre presque tous les besoins. CssSelector permet
d'écrire `h1.title` là où il faudrait sinon une expression XPath complète.

## Sélectionner

```php
$crawler->filter('h1.title');        // sélecteur CSS
$crawler->filterXpath('//h1');       // expression XPath
```

`filter()` exige le composant CssSelector ; `filterXpath()` non. Lu dans
`Crawler` (8.0) : sans lui, `LogicException` « To filter with a CSS selector,
install the CssSelector component… Or use filterXpath instead. »

## Parcourir

Chaque méthode rend **un nouveau `Crawler`**, ce qui permet de les enchaîner :

```php
$crawler->filter('input[type=submit]')
    ->last()
    ->ancestors()
    ->first();
```

| Méthode | Ce qu'elle rend |
|---|---|
| `eq(1)` | le nœud à cet index — `0` est le premier |
| `first()`, `last()` | le premier, le dernier |
| `siblings()` | les frères, **le nœud courant exclu** |
| `nextAll()`, `previousAll()` | les frères après, avant |
| `ancestors()` | les ascendants, jusqu'à `<html>` |
| `children()` | les enfants directs |
| `reduce($fn)` | ne garde que les nœuds pour lesquels la fonction rend `true` |

`count($crawler)` donne le nombre de nœuds retenus.

## Extraire

```php
$crawler->text();                     // le texte du premier nœud
$crawler->text('Défaut');             // ce texte si le nœud n'existe pas
$crawler->text(null, false);          // sans normaliser les espaces
$crawler->attr('class');              // un attribut du premier nœud
$crawler->extract(['_text', 'href']); // un tableau, pour tous les nœuds
$crawler->each(fn ($node, $i) => $node->attr('href'));
```

Le point à retenir : **`text()` et `attr()` ne portent que sur le premier
nœud** ; `extract()` et `each()` portent sur tous. La clé `_text` d'`extract()`
désigne le texte plutôt qu'un attribut.

Exécuté avec DomCrawler 8.0.15 :

| Appel | Résultat |
|---|---|
| `text()` sur `<h1>  Hello⏎   World  </h1>` | `"Hello World"` — espaces normalisés **par défaut** |
| `text(null, false)` | le texte brut, retours à la ligne compris |
| `text()` sur une sélection vide | `InvalidArgumentException` : « The current node list is empty. » |
| `text('Défaut')`, `attr('class', 'none')` sur une sélection vide | la valeur par défaut |
| `text()` sur trois `<li>` | le premier seulement |
| `siblings()` de `li.me` | les deux autres `<li>` |
| `ancestors()` de `li.me` | `ul`, `body`, `html` |

## Cliquer et soumettre

Deux niveaux coexistent. Le raccourci passe par le client :

```php
$client->clickLink('Cliquez ici');
$crawler = $client->submitForm('Ajouter', ['comment_form[content]' => '…']);
```

L'objet passe par le crawler, quand on a besoin du `Link` ou du `Form` :

```php
$link = $crawler->selectLink('Cliquez ici')->link();
$client->click($link);

$form = $crawler->selectButton('submit')->form();
$form['my_form[name]'] = 'Fabien';
$client->submit($form);
```

Le point contre-intuitif : **on sélectionne un bouton**, comme le recommande
`testing.rst`. Un formulaire peut en porter plusieurs, et c'est le bouton qui
détermine ce qui est envoyé. Le premier argument de `submitForm()` est le
texte, l'`id` ou le `name` d'un `<button>` ou d'un `<input type="submit">`.

Le code accepte pourtant aussi le `<form>` lui-même — la documentation du
composant le montre : `$crawler->filter('.form-vertical')->form()`. Exécuté,
un formulaire à deux boutons `Save` (`a=A`) et `Delete` (`b=B`) :

| Origine du `Form` | Valeurs envoyées |
|---|---|
| `selectButton('Delete')->form()` | `b=B`, `q=1` |
| `filter('#f')->form()` — le `<form>` | `q=1` seulement |
| `filter('input[name=q]')->form()` | `LogicException` « Unable to submit on a "input" tag. » |

Depuis le `<form>`, aucun bouton n'est envoyé, et les attributs `formaction` et
`formmethod` d'un bouton ne s'appliquent pas.

Sur les champs : `select()` pour une liste ou un bouton radio, `tick()` pour une
case, `upload()` pour un fichier.

## Pièges d'examen

**CssSelector ne parcourt pas le DOM** : il traduit CSS en XPath.

**`text()` et `attr()` ne lisent que le premier nœud.**

**`siblings()` exclut le nœud courant.**

**On sélectionne un bouton pour obtenir un formulaire** quand le bouton compte ;
le `<form>` seul marche aussi, mais n'envoie aucun bouton.

**`text()` normalise les espaces par défaut**, et lève une exception sur une
sélection vide sans valeur par défaut.

**Chaque méthode de parcours rend un nouveau `Crawler`** ; l'objet d'origine
n'est pas modifié.

## Points clés

- DomCrawler parcourt ; CssSelector traduit CSS en XPath.
- `filter()` en CSS, `filterXpath()` en XPath.
- Parcours chaînable, immuable ; `count()` pour le nombre de nœuds.
- `text()`/`attr()` sur le premier nœud, `extract()`/`each()` sur tous.
- `selectButton()->form()`, puis `$client->submit($form)`.

## Sources officielles

- [The DOM Crawler](https://github.com/symfony/symfony-docs/blob/8.0/testing/dom_crawler.rst)
- [The CssSelector Component](https://github.com/symfony/symfony-docs/blob/8.0/components/css_selector.rst)
- [The DomCrawler Component](https://github.com/symfony/symfony-docs/blob/8.0/components/dom_crawler.rst)
- [DomCrawler 8.0, `Form`](https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/DomCrawler/Form.php)
- [Testing](https://github.com/symfony/symfony-docs/blob/8.0/testing.rst)
