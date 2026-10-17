<?php

declare(strict_types=1);

namespace CertPath\Build;

use CertPath\Domain\Course;
use CertPath\Domain\Pool;
use CertPath\Support\CourseUrl;
use CertPath\Support\Project;
use CertPath\Validation\ContentSet;

/**
 * The « Journal des corrections » page (remediation P3.3).
 *
 * Rendered from docs/corrections/changelog.yml, newest first. Each entry says
 * what was taught, what replaced it, where to read it again — course sections
 * and flashcards by their anchors — and how many questions changed, by pool.
 * Question ids are never printed: the page is for the learner, and the ids of
 * exam-mode questions would only invite looking them up. The canonical file is
 * checked against git by tools/corrections/derive.py and against the current
 * content by CorrectionsChangelogTest; this class only renders it.
 */
final class CorrectionsPage
{
    public function __construct(private Project $project)
    {
    }

    private const string REPOSITORY = 'https://github.com/jasserYahyaoui/Symfony-8-Certification-Path';

    private const array KIND_LABEL = [
        'CORRECTION' => 'Correction',
        'SCOPE' => 'Périmètre élargi',
        'EXTENSION' => 'Ajout',
    ];

    private const array POOL_LABEL = [
        'LEARNING' => 'Practice',
        'VALIDATION' => 'exam mode',
        'COMPREHENSION' => 'contrôle de compréhension',
    ];

    private const array MONTHS = [
        1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
        'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
    ];

    /**
     * @param array{entries: list<array<string, mixed>>} $log
     */
    public function render(array $log, ContentSet $content, callable $mdx): string
    {
        $entries = $log['entries'];
        // Newest first; within a day, the file's own order reversed, so the
        // last delivery of the day comes first.
        $order = array_keys($entries);
        usort($order, static fn (int $a, int $b): int => [$entries[$b]['date'], $b] <=> [$entries[$a]['date'], $a]);

        $counts = array_fill_keys(array_keys(self::KIND_LABEL), 0);
        foreach ($entries as $entry) {
            ++$counts[$entry['kind']];
        }

        $md = "---\ntitle: \"Journal des corrections\"\nsidebar_position: 5\nsidebar_label: \"Journal des corrections\"\n---\n\n";
        $md .= "# Journal des corrections\n\n";
        $md .= "> Page générée depuis `docs/corrections/changelog.yml`. Ne pas modifier à la main.\n\n";
        $md .= "Ce que le parcours enseignait de faux ou d'incomplet, ce qui l'a remplacé, et où le relire. "
            ."La plus récente en premier. Chaque liste de cours, de flashcards et de questions est vérifiée "
            ."contre le commit qui l'a livrée (`tools/corrections/derive.py --check`, en CI).\n\n";
        $md .= "**Vos réponses suivent les corrections.** Quand une question est réécrite, sa version augmente : "
            ."une réponse donnée à une version antérieure ne compte plus dans [Ma progression](/progression) "
            ."ni dans la sélection du [Mock 5](/mock-5). Une flashcard dont le recto ou le verso change "
            ."repart de zéro dans votre planning.\n\n";
        $md .= "| Type | Ce que cela veut dire | Entrées |\n|---|---|---:|\n";
        $md .= '| '.self::KIND_LABEL['CORRECTION']." | ce qui était enseigné était faux ou trompeur | {$counts['CORRECTION']} |\n";
        $md .= '| '.self::KIND_LABEL['SCOPE']." | une restriction non sourcée sur ce qui est examinable a été levée | {$counts['SCOPE']} |\n";
        $md .= '| '.self::KIND_LABEL['EXTENSION']." | contenu ajouté ; rien de ce qui était enseigné n'était faux | {$counts['EXTENSION']} |\n\n";

        foreach ($order as $index) {
            $md .= $this->entry($entries[$index], $content, $mdx);
        }

        return $md;
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function entry(array $entry, ContentSet $content, callable $mdx): string
    {
        $md = '## '.$mdx($entry['title']).' {#'.strtolower($entry['id'])."}\n\n";
        $md .= '**'.self::KIND_LABEL[$entry['kind']].'** · '.$this->day($entry['date'])
            .' · [PR #'.$entry['pull_request'].']('.self::REPOSITORY.'/pull/'.$entry['pull_request'].")\n\n";

        if (isset($entry['was'])) {
            $label = 'CORRECTION' === $entry['kind'] ? 'Ancienne formulation, fausse' : 'Restriction levée';
            $md .= '**'.$label.'.** '.$mdx(trim($entry['was']))."\n\n";
            $md .= '**Ce qui est vrai.** '.$mdx(trim($entry['now']))."\n\n";
        } else {
            $md .= '**Ce qui est enseigné maintenant.** '.$mdx(trim($entry['now']))."\n\n";
        }

        $read = [];
        foreach ($entry['courses'] ?? [] as $ref) {
            $course = $this->course($ref['id'], $content);
            $item = $content->matrix->findById($course->officialItemId);
            foreach ($ref['sections'] as $heading) {
                $read[] = '['.$this->linkText($course->title.' › '.$heading).']('.CourseUrl::forSection($item, $heading).')';
            }
        }
        foreach ($entry['pages'] ?? [] as $page) {
            // The URL drops the number that orders the chapter, as
            // DocsGenerator::whatsNewPages() publishes it; the title is the H1.
            $slug = preg_replace('/^\d+-/', '', basename($page['path'], '.md'));
            $title = preg_match('/^#\s+(.+)$/m', (string) file_get_contents($this->project->path($page['path'])), $m)
                ? trim($m[1]) : $slug;
            $read[] = '['.$this->linkText($title).'](/docs/whats-new/'.$slug.') — '
                .implode(', ', array_map(static fn (string $h): string => '« '.$mdx($h).' »', $page['sections']));
        }
        if ([] !== $read) {
            $md .= "**Où le relire.**\n\n- ".implode("\n- ", $read)."\n\n";
        }

        $cards = [];
        foreach ($entry['flashcards'] ?? [] as $cardId) {
            foreach ($content->flashcards as $card) {
                if ($card->id->value === $cardId) {
                    $item = $content->matrix->findById($card->officialItemId);
                    $cards[] = '['.$this->linkText($card->front).']('.CourseUrl::forItem($item).'#'.CourseUrl::flashcardAnchor($cardId).')';
                }
            }
        }
        if ([] !== $cards) {
            $md .= '**Flashcards ('.\count($cards).").**\n\n- ".implode("\n- ", $cards)."\n\n";
        }

        $md .= $this->questions($entry, $content);

        if ([] !== ($entry['facts'] ?? [])) {
            $md .= '**Exécuté sur Symfony 8.0.** '.implode(', ', array_map(static fn (string $f): string => '`'.$f.'`', $entry['facts']))
                .' — `tools/behavior`, registre `docs/facts/central-facts.yml`.'."\n\n";
        }

        return $md;
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function questions(array $entry, ContentSet $content): string
    {
        $byPool = [];
        $lots = [];
        $all = [...$content->questions, ...$content->comprehension];
        foreach ($entry['questions'] ?? [] as $id) {
            foreach ($all as $question) {
                if ($question->id->value !== $id) {
                    continue;
                }
                // CorrectionsChangelogTest refuses a HOLDOUT id; this is the
                // second lock, at the point of publication.
                if (Pool::Holdout === $question->pool) {
                    throw new \LogicException('A HOLDOUT question is listed in docs/corrections/changelog.yml.');
                }
                $byPool[$question->pool->value] = ($byPool[$question->pool->value] ?? 0) + 1;
                if (Pool::Comprehension === $question->pool) {
                    $item = $content->matrix->findById($question->officialItemId);
                    $lots[$item->lot] = CourseUrl::forComprehension($item->lot);
                }
            }
        }
        if ([] === $byPool) {
            return '';
        }

        $parts = [];
        foreach (self::POOL_LABEL as $pool => $label) {
            if (isset($byPool[$pool])) {
                $parts[] = $byPool[$pool].' '.$label;
            }
        }
        $md = '**Questions.** '.implode(', ', $parts).'.';
        $reversioned = \count($entry['reversioned'] ?? []);
        if ($reversioned > 0) {
            $md .= ' Dont '.$reversioned.' réécrite'.($reversioned > 1 ? 's' : '').' dans une nouvelle version : '
                .'vos réponses à la version précédente ne comptent plus.';
        }
        if ([] !== $lots) {
            ksort($lots);
            $links = [];
            foreach ($lots as $lot => $url) {
                $links[] = '['.str_replace('lot-', 'lot ', $lot).']('.$url.')';
            }
            $md .= ' Contrôle de compréhension : '.implode(', ', $links).'.';
        }

        return $md."\n\n";
    }

    private function course(string $id, ContentSet $content): Course
    {
        foreach ($content->courses as $course) {
            if ($course->id->value === $id) {
                return $course;
            }
        }

        throw new \LogicException(\sprintf('docs/corrections/changelog.yml names %s, which no course defines.', $id));
    }

    /** Link text: escaped for MDX, brackets kept out of the link syntax. */
    private function linkText(string $text): string
    {
        $parts = preg_split('/(`[^`]*`)/', $text, -1, \PREG_SPLIT_DELIM_CAPTURE);
        $out = '';
        foreach ($parts as $part) {
            $out .= str_starts_with($part, '`')
                ? $part
                : str_replace(['\\', '[', ']', '<', '{', '}'], ['\\\\', '\\[', '\\]', '&lt;', '&#123;', '&#125;'], $part);
        }

        return $out;
    }

    private function day(string $date): string
    {
        [$y, $m, $d] = array_map('intval', explode('-', $date));

        return $d.' '.self::MONTHS[$m].' '.$y;
    }
}
