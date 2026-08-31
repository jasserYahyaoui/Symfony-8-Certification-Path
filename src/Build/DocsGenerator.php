<?php

declare(strict_types=1);

namespace CertPath\Build;

use CertPath\Coverage\CoverageCalculator;
use CertPath\Coverage\CoverageReport;
use CertPath\Domain\ContentLevel;
use CertPath\Domain\OfficialItem;
use CertPath\Support\Project;
use CertPath\Validation\ContentSet;

/**
 * Generates the Docusaurus content tree from the canonical YAML (ADR-0003).
 *
 * PHP owns the data and the rules; Docusaurus owns the presentation. Nothing
 * under `website/docs/` or `website/static/data/` is hand-written — the
 * canonical files remain the single source of truth (Master Plan §11).
 */
final readonly class DocsGenerator
{
    public function __construct(
        private Project $project,
        private PayloadBuilder $payloads = new PayloadBuilder(),
        private CoverageCalculator $coverage = new CoverageCalculator(),
    ) {
    }

    /**
     * @return list<string> generated paths, relative to `website/`
     */
    public function generate(ContentSet $content): array
    {
        $docsDir = $this->project->path('website/docs');
        $dataDir = $this->project->path('website/static/data');

        $this->reset($docsDir);
        $this->reset($dataDir);

        $written = [];

        // Pool isolation is enforced here, at build time: the Practice payload
        // is assembled from the learning pool alone, so a holdout question is
        // absent from the file the Practice page fetches (§7.3, §17).
        $practice = $this->payloads->practicePayload($content);
        PayloadBuilder::assertNoHoldoutLeak($practice, $content);

        $written[] = $this->writeJson($dataDir.'/practice.json', $practice);
        $written[] = $this->writeJson($dataDir.'/exam.json', $this->payloads->examPayload($content));

        $report = $this->coverage->calculate($content->matrix);

        $written[] = $this->writeJson($dataDir.'/coverage.json', [
            'generated_at' => gmdate('c'),
            'denominator_established' => $report->isDenominatorEstablished(),
            'total_official_items' => $report->totalOfficialItems,
            'exam_ready_items' => $report->examReadyItems,
            'percentage' => $report->percentage(),
            'by_lot' => $report->byLot,
            'by_topic' => $report->byTopic,
        ]);

        $written[] = $this->writeDoc($docsDir.'/index.md', $this->introPage($report));
        $written[] = $this->writeDoc($docsDir.'/syllabus/coverage.md', $this->coveragePage($report));
        $written[] = $this->writeDoc($docsDir.'/syllabus/exclusions.md', $this->exclusionsPage($content));

        foreach ($this->coursePages($content) as $path => $markdown) {
            $written[] = $this->writeDoc($docsDir.'/'.$path, $markdown);
        }

        sort($written);

        return $written;
    }

    private function introPage(CoverageReport $report): string
    {
        $state = $report->isDenominatorEstablished()
            ? \sprintf(
                '**%s %%** — %d des %d items officiels atomiques sont `EXAM_READY`.',
                $report->percentage(),
                $report->examReadyItems,
                $report->totalOfficialItems,
            )
            : "**Non établie.** Le syllabus officiel n'a pas encore pu être importé, "
              ."donc le dénominateur n'existe pas. Ce n'est délibérément pas rapporté "
              ."comme `0 %` : un dénominateur indéfini et un numérateur nul sont deux "
              .'affirmations différentes.';

        return $this->frontMatter('Introduction', 0, 'introduction')."
# Introduction

Ce site est un **système d'apprentissage et d'évaluation** ciblé sur la
certification Symfony 8.0. Ce n'est pas une encyclopédie Symfony : il ne
couvre que ce qui est examinable, au niveau minimum suffisant pour comprendre,
retenir et appliquer sous contrainte de temps.

## État de la couverture

{$state}

La couverture est calculée **uniquement** ainsi :

```text
items officiels atomiques EXAM_READY / total des items officiels atomiques * 100
```

Jamais à partir du nombre de lots, de pages, de chapitres, de fiches, de
questions ou de fichiers. Voir [la couverture détaillée](./syllabus/coverage.md).

## Comment utiliser ce site

| Ressource | Rôle |
|---|---|
| **Documentation** | Comprendre les concepts examinables |
| **Practice Mode** | Discriminer et valider, sans chronomètre |
| **Exam Mode** | Simuler l'examen dans ses conditions publiées |

Chaque ressource a une fonction pédagogique distincte. Deux ressources ne sont
jamais créées pour le même but.

## Format publié de l'examen

```text
75 questions
90 minutes
15 topics
English
Symfony 8.0 only
```

Ces contraintes sont publiques et étiquetées `OFFICIAL_FORMAT`. Aucune
pondération par sujet n'est inventée : toute répartition interne utilisée pour
l'entraînement est étiquetée `TRAINING_DISTRIBUTION`.

## Langue

Les explications principales sont en français ; les noms d'API, mots-clés,
classes, interfaces et clés de configuration restent en anglais. L'examen
officiel étant en anglais, le Practice Mode avancé introduit progressivement
des formulations anglaises, et la simulation au format officiel est
intégralement en anglais.

## Vos données

Votre progression est stockée localement dans ce navigateur. Aucun compte,
aucune donnée envoyée, aucun secret côté client. Vous pouvez l'exporter ou
l'effacer depuis la page **Ma progression**.
";
    }

    private function coveragePage(CoverageReport $report): string
    {
        $markdown = $this->frontMatter('Couverture officielle', 1, 'coverage')."
# Couverture officielle

> Page générée par `php bin/cert build`. Ne pas modifier à la main.

La couverture est définie ainsi, et uniquement ainsi :

```text
items officiels atomiques EXAM_READY / total des items officiels atomiques * 100
```

";

        if (!$report->isDenominatorEstablished()) {
            return $markdown."## Couverture : `UNDEFINED`

Le syllabus officiel n'a pas été importé, donc **le dénominateur n'existe pas**.

Ce n'est pas rapporté comme `0 %`. Un dénominateur indéfini et un numérateur
nul sont deux affirmations différentes, et les confondre donnerait un faux
signal de complétude.

### Pourquoi le syllabus n'est pas importé

Le syllabus doit être importé **verbatim** depuis
`certification.symfony.com`, seule autorité de périmètre. Ce domaine est
inaccessible depuis l'environnement de build, et contrairement aux autres
sources bloquées il n'a **aucun dépôt amont** de substitution.

Les descriptions de lots du plan d'exécution énumèrent les domaines en détail
et il serait techniquement facile d'en synthétiser un syllabus plausible.
Ce serait substituer un dénominateur non officiel au dénominateur officiel :
tous les pourcentages calculés ensuite mesureraient la mauvaise chose tout en
paraissant parfaitement crédibles. Le fichier reste donc vide.

### Conséquence

Aucun lot de contenu ne peut démarrer tant que ce point n'est pas résolu.
";
        }

        $markdown .= \sprintf(
            "## Couverture : **%s %%**\n\n%d des %d items officiels atomiques sont `EXAM_READY`.\n\n",
            $report->percentage(),
            $report->examReadyItems,
            $report->totalOfficialItems,
        );

        $markdown .= "## Par sujet officiel\n\n| Sujet officiel | EXAM_READY | Total | % |\n|---|---:|---:|---:|\n";
        foreach ($report->byTopic as $topic => $counts) {
            $markdown .= \sprintf(
                "| %s | %d | %d | %s |\n",
                $topic,
                $counts['ready'],
                $counts['total'],
                $this->pct($counts['ready'], $counts['total']),
            );
        }

        $markdown .= "\n## Par lot\n\n| Lot | EXAM_READY | Total | % |\n|---|---:|---:|---:|\n";
        foreach ($report->byLot as $lot => $counts) {
            $markdown .= \sprintf(
                "| %s | %d | %d | %s |\n",
                $lot,
                $counts['ready'],
                $counts['total'],
                $this->pct($counts['ready'], $counts['total']),
            );
        }

        $markdown .= \sprintf("\n## Pas encore EXAM_READY (%d)\n\n", \count($report->blockedItemIds));
        if ([] === $report->blockedItemIds) {
            $markdown .= "Aucun.\n";
        } else {
            foreach ($report->blockedItemIds as $id) {
                $markdown .= '- `'.$id."`\n";
            }
        }

        return $markdown;
    }

    private function exclusionsPage(ContentSet $content): string
    {
        $markdown = $this->frontMatter('Exclusions officielles', 2, 'exclusions')."
# Exclusions officielles

> Page générée depuis `docs/syllabus/exclusions.yml`. Ne pas modifier à la main.

Les sujets ci-dessous sont **hors périmètre**. Aucun point ne peut dépendre
d'eux. Un terme exclu ne peut apparaître que dans une explication clairement
étiquetée de l'exclusion elle-même — la règle CI `SCOPE-001` rejette toute
question notée qui en mentionne un sans l'étiquette `exclusion-note`.

## Termes surveillés automatiquement

";

        if ([] === $content->excludedTerms) {
            return $markdown."Aucun terme n'est configuré.\n";
        }

        foreach ($content->excludedTerms as $term) {
            $markdown .= '- `'.$term."`\n";
        }

        return $markdown."
## Pourquoi cette liste est appliquée par la CI

Détecter une contamination hors périmètre après coup coûte bien plus cher que
de l'empêcher : une question hors scope qui a servi à s'entraîner a déjà
consommé du temps de révision et faussé la mesure de maîtrise. La liste est
donc exécutable, pas seulement documentaire.
";
    }

    /**
     * One page per official item, grouped by lot.
     *
     * @return array<string, string>
     */
    private function coursePages(ContentSet $content): array
    {
        $pages = [];
        $byLot = [];

        foreach ($content->matrix->items as $item) {
            $byLot[$item->lot][] = $item;
        }

        ksort($byLot);

        foreach ($byLot as $lot => $items) {
            $slug = $this->slug($lot);
            $pages['courses/'.$slug.'/index.md'] = $this->lotIndexPage($lot, $items);

            foreach ($items as $item) {
                $pages['courses/'.$slug.'/'.$this->slug($item->officialItem).'.md']
                    = $this->itemPage($item);
            }
        }

        if ([] === $pages) {
            $pages['courses/index.md'] = $this->frontMatter('Cours', 3, 'courses')."
# Cours

Aucun cours n'existe encore. Les pages de cours sont générées à partir de la
matrice du syllabus, et celle-ci est vide tant que le syllabus officiel n'a pas
été importé — voir [l'état de la couverture](../syllabus/coverage.md).

Créer des cours avant l'import reviendrait à enseigner un programme deviné.
";
        }

        return $pages;
    }

    /**
     * @param list<OfficialItem> $items
     */
    private function lotIndexPage(string $lot, array $items): string
    {
        $markdown = $this->frontMatter($lot, 0, $this->slug($lot))."
# {$lot}

| Item officiel | Niveau | Statut |
|---|---|---|
";

        foreach ($items as $item) {
            $markdown .= \sprintf(
                "| [%s](./%s.md) | `%s` | `%s` |\n",
                $item->officialItem,
                $this->slug($item->officialItem),
                $item->contentLevel->value,
                $item->status->value,
            );
        }

        return $markdown;
    }

    private function itemPage(OfficialItem $item): string
    {
        $markdown = $this->frontMatter($item->officialItem, $item->officialItemOrder, $this->slug($item->officialItem))."
# {$item->officialItem}

> **Item officiel, formulation verbatim :**
> {$item->officialWording}

| | |
|---|---|
| Sujet officiel | {$item->officialTopic} |
| Niveau de contenu | `{$item->contentLevel->value}` |
| Classification | `{$item->classification->value}` |
| Statut | `{$item->status->value}` |
| Contraintes de version | {$item->versionConstraints} |

## Objectifs d'apprentissage

";

        foreach ($item->learningOutcomes as $outcome) {
            $markdown .= '- '.$outcome."\n";
        }

        $markdown .= "\n## Justification du niveau\n\n".$item->contentLevelJustification."\n";

        if (ContentLevel::Deep === $item->contentLevel) {
            $markdown .= "\n:::info Niveau DEEP\nCe niveau n'est jamais le défaut. Il est réservé aux concepts structurels ou fréquemment confondus.\n:::\n";
        }

        $markdown .= "\n## Limites de périmètre\n\n".$item->exclusionBoundaries."\n";

        if ([] !== $item->officialSources) {
            $markdown .= "\n## Sources officielles\n\n";
            foreach ($item->officialSources as $source) {
                $markdown .= '- <'.$source->url.'>';
                if (null !== $source->commitSha) {
                    $markdown .= ' — `'.substr($source->commitSha, 0, 12).'`';
                }
                $markdown .= "\n";
            }
        }

        return $markdown;
    }

    private function frontMatter(string $title, int $position, string $slug): string
    {
        return "---\ntitle: ".$this->escapeYaml($title)."\nsidebar_position: ".$position
            ."\nsidebar_label: ".$this->escapeYaml($title)."\n---\n";
    }

    private function escapeYaml(string $value): string
    {
        return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
    }

    private function slug(string $value): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        $lower = mb_strtolower(false !== $ascii ? $ascii : $value);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $lower) ?? $lower;

        return trim($slug, '-') ?: 'item';
    }

    private function pct(int $ready, int $total): string
    {
        return 0 === $total ? 'n/a' : \sprintf('%.1f', $ready / $total * 100);
    }

    private function writeDoc(string $path, string $markdown): string
    {
        $this->ensureDir(\dirname($path));
        file_put_contents($path, $markdown);

        return $this->relative($path);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function writeJson(string $path, array $data): string
    {
        $this->ensureDir(\dirname($path));
        file_put_contents(
            $path,
            json_encode($data, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE),
        );

        return $this->relative($path);
    }

    private function relative(string $path): string
    {
        $prefix = $this->project->path('website').'/';

        return str_starts_with($path, $prefix) ? substr($path, \strlen($prefix)) : $path;
    }

    private function reset(string $dir): void
    {
        if (is_dir($dir)) {
            $items = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );

            foreach ($items as $item) {
                if ($item instanceof \SplFileInfo) {
                    $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
                }
            }

            rmdir($dir);
        }

        $this->ensureDir($dir);
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0o775, true) && !is_dir($dir)) {
            throw new \RuntimeException(\sprintf('Cannot create directory "%s".', $dir));
        }
    }
}
