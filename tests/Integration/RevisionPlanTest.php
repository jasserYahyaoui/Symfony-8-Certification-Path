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

    /**
     * The generator used to schedule J+45 and J+60 revisions past the exam:
     * for a 15/12/2026 sitting it planned 25 days running to 09/01/2027. A
     * revision the candidate can never do is not a revision, and listing it
     * inflates the advertised workload by hours that cannot be worked. The
     * loss is recorded in `lost_reviews` instead, so dropping it stays a
     * measured cost rather than a silent one.
     */
    public function testNoWorkIsScheduledAfterTheExam(): void
    {
        $plan = $this->plan();

        self::assertNotNull($plan['exam'] ?? null, 'the plan carries no exam date');
        $exam = new \DateTimeImmutable($plan['exam']);

        foreach ($plan['days'] as $date => $day) {
            $when = new \DateTimeImmutable($date);

            self::assertLessThanOrEqual(
                $exam,
                $when,
                $date.' is planned after the exam of '.$plan['exam'],
            );

            if ($when == $exam) {
                self::assertSame([], $day['rev'], 'the exam day carries revisions');
                self::assertSame([], $day['new'], 'the exam day introduces new items');
            }
        }

        // The count must stay visible: a plan that silently dropped the
        // revisions instead of recording them would pass everything above.
        self::assertArrayHasKey('lost_reviews_by_offset', $plan);
        self::assertSame(
            count($plan['lost_reviews']),
            array_sum($plan['lost_reviews_by_offset']),
            'the per-offset breakdown does not add up to the recorded losses',
        );
    }

    /**
     * The agenda grid draws these events; nothing else validates them, and a
     * slot whose end precedes its start renders as a zero-height block the eye
     * reads as an empty day rather than as a bug.
     */
    /**
     * Three losses of the same shape, all found by reconciling the plan
     * against itself rather than by reading it: an assignment that overwrote
     * whatever the day already held.
     *
     * Eight of twenty-six lot assessments survived — one Sunday was due to
     * carry twelve. Two mock debriefs of five vanished, because the next
     * mock was placed on top of the previous one's correction. Nothing
     * reported any of it; the plan simply contained less than it promised.
     */
    public function testNothingScheduledIsSilentlyOverwritten(): void
    {
        $plan = $this->plan();

        $assessed = [];
        $mocks = [];
        $corrections = [];

        foreach ($plan['days'] as $date => $day) {
            foreach ($day['assess'] as $lot) {
                self::assertArrayNotHasKey($lot, $assessed, $lot.' is assessed twice');
                $assessed[$lot] = $date;
            }

            if (null === $day['mock']) {
                continue;
            }

            $name = $day['mock'][0];

            if (str_starts_with($name, 'Correction ')) {
                $corrections[substr($name, 11)] = $date;
            } elseif (!str_starts_with($name, 'EXAMEN')) {
                $mocks[$name] = $date;
            }
        }

        self::assertNotEmpty($plan['lot_done'], 'no lot is recorded as finished');
        self::assertSame(
            array_keys($plan['lot_done']),
            array_keys($assessed),
            'every finished lot must get exactly one assessment',
        );

        self::assertNotEmpty($mocks, 'the plan schedules no mock');

        foreach ($mocks as $name => $sat) {
            self::assertArrayHasKey($name, $corrections, $name.' has no debrief');
            self::assertGreaterThan(
                $sat,
                $corrections[$name],
                $name.' is debriefed on '.$corrections[$name].', before it is sat',
            );
        }
    }

    public function testEveryEventOccupiesACoherentSlot(): void
    {
        $kinds = ['NEW', 'REVIEW', 'LAB', 'ASSESS', 'MOCK', 'CONSOLIDATION', 'EXAM'];
        $seen = 0;

        foreach ($this->plan()['days'] as $date => $day) {
            $previousEnd = -1;

            foreach ($day['events'] as $event) {
                ++$seen;

                self::assertContains($event['kind'], $kinds, $date.' carries an unknown event kind');
                self::assertGreaterThan(0, $event['minutes'], $date.' carries an event of no duration');
                self::assertNotSame('', trim((string) $event['title']), $date.' carries an untitled event');

                $start = $this->minutes($event['start']);
                $end = $this->minutes($event['end']);

                self::assertSame(
                    $event['minutes'],
                    $end - $start,
                    $date.' has an event from '.$event['start'].' to '.$event['end']
                        .' declared as '.$event['minutes'].' minutes',
                );

                // Sessions never overlap: the grid would draw them on top of
                // each other, and a candidate cannot sit two at once.
                self::assertGreaterThanOrEqual(
                    $previousEnd,
                    $start,
                    $date.' starts an event at '.$event['start'].' before the previous one ends',
                );

                $previousEnd = $end;
            }

            $planned = array_sum(array_column($day['events'], 'minutes'));

            self::assertLessThanOrEqual(
                $day['budget'],
                $planned,
                $date.' schedules '.$planned.' minutes of events against a budget of '.$day['budget'],
            );

            // `used` carried two opposite distortions before this: it ignored
            // mocks entirely, and it counted a whole weekend budget whatever
            // the day actually held. One field, one meaning.
            self::assertSame(
                $day['used'],
                $planned,
                $date.' declares '.$day['used'].' minutes used but draws '.$planned,
            );
        }

        self::assertGreaterThan(300, $seen, 'the plan carries almost no events');
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
    private function minutes(string $hhmm): int
    {
        [$h, $m] = array_map('intval', explode(':', $hhmm));

        return $h * 60 + $m;
    }

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
