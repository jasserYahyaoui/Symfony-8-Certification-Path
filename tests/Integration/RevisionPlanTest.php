<?php

declare(strict_types=1);

namespace CertPath\Tests\Integration;

use CertPath\Support\Project;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * The candidate roadmap is generated, published, and therefore testable.
 *
 * These assertions pin the properties a reader would otherwise have to take on
 * trust: that the published pages come from the canonical documents, that the
 * plan never schedules a mock before every item is studied, and that nothing
 * of the HOLDOUT pool reaches any of it.
 */
#[CoversNothing]
final class RevisionPlanTest extends TestCase
{
    private const array DOCUMENTS = [
        'study-roadmap.md',
        'study-calendar.md',
        'mastery-checkpoints.md',
        'exam-readiness.md',
    ];

    private function root(): string
    {
        return Project::locate()->path('');
    }

    public function testTheFourCanonicalDocumentsExist(): void
    {
        foreach (self::DOCUMENTS as $name) {
            self::assertFileExists(
                $this->root().'/docs/revision/'.$name,
                $name.' is the canonical source of a published page',
            );
        }
    }

    /**
     * ADR-0003: website/docs is generated. The published pages must therefore
     * carry the body of their canonical document, not a hand-kept copy that
     * drifts.
     */
    public function testEachPublishedPageCarriesItsCanonicalBody(): void
    {
        $pairs = [
            'study-roadmap.md' => 'roadmap.md',
            'study-calendar.md' => 'calendar.md',
            'mastery-checkpoints.md' => 'checkpoints.md',
            'exam-readiness.md' => 'readiness.md',
        ];

        foreach ($pairs as $source => $page) {
            $published = $this->root().'/website/docs/revision/'.$page;

            if (!is_file($published)) {
                self::markTestSkipped('Run `php bin/cert build` before this test.');
            }

            $canonical = (string) file_get_contents($this->root().'/docs/revision/'.$source);
            $rendered = (string) file_get_contents($published);

            // The first heading of the canonical document must survive the
            // copy; front matter and MDX escaping are the only allowed edits.
            preg_match('/^#\s+(.+)$/m', $canonical, $m);
            self::assertNotEmpty($m, $source.' has no level-one heading');
            self::assertStringContainsString(
                trim($m[1]),
                $rendered,
                $page.' does not carry the heading of '.$source,
            );
        }
    }

    /**
     * Master Plan §10 reserves the mocks for a corpus already studied. The
     * generator enforces it; this pins the enforcement so a change to the
     * scheduling cannot quietly move a mock into the study period.
     */
    public function testNoMockIsScheduledBeforeEveryItemIsStudied(): void
    {
        $plan = $this->plan();
        $allIn = new \DateTimeImmutable($plan['all_items_in']);

        self::assertNotEmpty($plan['mock_dates'], 'the plan schedules no mock at all');

        $dates = [];
        foreach ($plan['mock_dates'] as [$name, $date]) {
            $day = new \DateTimeImmutable($date);
            self::assertGreaterThan(
                $allIn,
                $day,
                $name.' is scheduled on '.$date.', before every item is studied ('.$plan['all_items_in'].')',
            );
            $dates[$name] = $day;
        }

        self::assertSame('Mock 4', array_key_last($dates), 'Mock 4 must be the last mock');
        self::assertSame(max($dates), $dates['Mock 4'], 'Mock 4 must be sat last');
    }

    public function testNoDayExceedsItsOwnBudget(): void
    {
        foreach ($this->plan()['days'] as $date => $day) {
            self::assertLessThanOrEqual(
                $day['budget'],
                $day['used'],
                $date.' plans '.$day['used'].' minutes against a budget of '.$day['budget'],
            );
        }
    }

    public function testEveryOfficialItemIsIntroducedExactlyOnce(): void
    {
        $plan = $this->plan();
        $introduced = [];

        foreach ($plan['days'] as $day) {
            foreach ($day['new'] as [$id, , $full]) {
                if ($full) {
                    $introduced[] = $id;
                }
            }
        }

        self::assertCount(\count($plan['items']), $introduced, 'every item is introduced once');
        self::assertSame(\count($introduced), \count(array_unique($introduced)), 'no item is introduced twice');
    }

    /**
     * ADR-0005/0006: the holdout reaches exactly one payload. A study plan that
     * named a holdout question, or an id from it, would spend the one unseen
     * measurement the project keeps.
     */
    public function testNoRevisionDocumentNamesHoldoutContent(): void
    {
        $content = Project::locate()->loadContentSet();
        $forbidden = [];

        foreach ($content->questions as $question) {
            if ('HOLDOUT' !== $question->pool->value) {
                continue;
            }

            $forbidden[] = $question->id->value;

            foreach ($question->choices as $choice) {
                $forbidden[] = $choice->id->value;
            }
        }

        self::assertNotEmpty($forbidden, 'the holdout pool is empty; this test would be vacuous');

        foreach (self::DOCUMENTS as $name) {
            $body = (string) file_get_contents($this->root().'/docs/revision/'.$name);

            foreach ($forbidden as $id) {
                self::assertStringNotContainsString($id, $body, $name.' names holdout id '.$id);
            }
        }
    }

    /** @return array{days: array<string, array{new: list<array{0: string, 1: int, 2: bool}>, used: int, budget: int}>, items: list<array<string, mixed>>, mock_dates: list<array{0: string, 1: string}>, all_items_in: string} */
    private function plan(): array
    {
        $path = $this->root().'/docs/revision/plan.json';

        if (!is_file($path)) {
            self::markTestSkipped('Run `python3 tools/revision/build_roadmap.py` before this test.');
        }

        /** @var array{days: array<string, array{new: list<array{0: string, 1: int, 2: bool}>, used: int, budget: int}>, items: list<array<string, mixed>>, mock_dates: list<array{0: string, 1: string}>, all_items_in: string} $plan */
        $plan = json_decode((string) file_get_contents($path), true, 512, \JSON_THROW_ON_ERROR);

        return $plan;
    }
}
