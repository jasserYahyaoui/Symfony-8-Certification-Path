<?php

declare(strict_types=1);

namespace CertPath\Tests\Integration;

use CertPath\Build\PayloadBuilder;
use CertPath\Domain\Pool;
use CertPath\Support\CourseUrl;
use CertPath\Support\Project;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The data contract Practice Mode depends on (Lot 27).
 *
 * The Lot 27 audit found that `practice.json` carried no learning outcome, no
 * course link and no readable item label, so the page could tell a learner they
 * were wrong but neither what concept they had missed nor where to revise it.
 * These tests hold the repaired contract.
 *
 * Every assertion runs against the REAL canonical corpus, not a fixture: a
 * fixture would prove the builder can emit the shape, which was never in doubt,
 * and not that the 163 items actually resolve.
 */
#[CoversClass(PayloadBuilder::class)]
#[CoversClass(CourseUrl::class)]
final class PracticePayloadTest extends TestCase
{
    /** @return array<string, mixed> */
    private static function payload(): array
    {
        static $payload = null;

        return $payload ??= (new PayloadBuilder())->practicePayload(
            (new Project(\dirname(__DIR__, 2)))->loadContentSet(),
        );
    }

    public function testEveryPracticeQuestionResolvesToAnIndexedItem(): void
    {
        $payload = self::payload();

        self::assertNotEmpty($payload['items'], 'practice.json ships no item index');

        foreach ($payload['questions'] as $question) {
            self::assertArrayHasKey(
                $question['official_item'],
                $payload['items'],
                \sprintf(
                    'Question %s names item %s, which the index does not carry; the '
                    .'feedback would have no course link and no key takeaway.',
                    $question['id'],
                    $question['official_item'],
                ),
            );
        }
    }

    public function testEveryIndexedItemCarriesTheThreeFieldsTheFeedbackReads(): void
    {
        foreach (self::payload()['items'] as $id => $entry) {
            self::assertNotSame('', trim((string) $entry['official_item']), $id);
            self::assertNotSame('', trim((string) $entry['official_topic']), $id);
            self::assertNotSame('', trim((string) $entry['course_url']), $id);
            // The key takeaway is the first outcome; an item with none would
            // silently drop that section for every question it carries.
            self::assertNotEmpty($entry['learning_outcomes'], $id);
        }
    }

    /**
     * The link shipped to the learner has to reach a page the build produced.
     *
     * A dead "Ouvrir le cours" is worse than no link: it is a promise the
     * learner follows at the moment they are already stuck.
     */
    public function testEveryCourseUrlPointsAtAGeneratedPage(): void
    {
        $project = new Project(\dirname(__DIR__, 2));

        foreach (self::payload()['items'] as $id => $entry) {
            $relative = substr((string) $entry['course_url'], \strlen('/docs/'));
            $path = $project->path('website/docs/'.$relative.'.md');

            self::assertFileExists($path, \sprintf(
                'Item %s links to %s, which the docs build does not write.',
                $id,
                $entry['course_url'],
            ));
        }
    }

    public function testTheIndexIsBuiltFromTheSameHelperThatWritesThePage(): void
    {
        $content = (new Project(\dirname(__DIR__, 2)))->loadContentSet();

        foreach ($content->matrix->officialItems() as $item) {
            $entry = self::payload()['items'][$item->id->value] ?? null;
            if (null === $entry) {
                continue;
            }

            self::assertSame(CourseUrl::forItem($item), $entry['course_url']);
        }
    }

    public function testThePracticePayloadStaysLearningOnly(): void
    {
        $payload = self::payload();

        self::assertSame(Pool::Learning->value, $payload['pool']);
        self::assertNotEmpty($payload['questions']);
    }

    /**
     * The gate the Lot 27 brief states for an English LEARNING question.
     *
     * Not a duplicate of PED-002: that rule reads the canonical bank, this
     * reads the PAYLOAD. The audit's own finding is why both exist — the bank
     * was complete and the payload still could not feed the feedback, so a
     * green rule said nothing about what the learner would see.
     */
    public function testEveryEnglishQuestionCarriesWhatTheFeedbackNeeds(): void
    {
        self::assertSame([], self::problems(self::payload()));
    }

    /**
     * A check that has only ever passed has not been shown to be able to fail.
     *
     * Each case mutates a COPY of the real payload and asserts `problems()` —
     * the very function the test above trusts — rejects it. The checks are not
     * restated here: a second copy of the rule could pass while the real one
     * stayed blind, which is the failure mode this exists to exclude.
     *
     * @return iterable<string, array{callable(array<string, mixed>): array<string, mixed>, string}>
     */
    public static function defects(): iterable
    {
        yield 'question naming an item the index does not carry' => [
            static function (array $p): array {
                $p['questions'][0]['official_item'] = 'OIT-doesnotexist0';

                return $p;
            },
            'unknown item',
        ];

        yield 'item whose course url reaches no page' => [
            static function (array $p): array {
                $id = array_key_first($p['items']);
                $p['items'][$id]['course_url'] = '/docs/courses/lot-99/does-not-exist';

                return $p;
            },
            'course page',
        ];

        yield 'item with no learning outcome' => [
            static function (array $p): array {
                $id = array_key_first($p['items']);
                $p['items'][$id]['learning_outcomes'] = [];

                return $p;
            },
            'learning outcome',
        ];

        yield 'english question with an empty explanation' => [
            static function (array $p): array {
                $p['questions'][self::firstEnglish($p)]['explanation'] = '   ';

                return $p;
            },
            'explanation',
        ];

        yield 'answer count contradicting the key' => [
            static function (array $p): array {
                $i = self::firstEnglish($p);
                $p['questions'][$i]['required_answer_count'] += 1;

                return $p;
            },
            'expected answers',
        ];

        yield 'distractor stripped of its explanation' => [
            static function (array $p): array {
                $i = self::firstEnglish($p);
                foreach ($p['questions'][$i]['choices'] as $j => $choice) {
                    if (!$choice['correct']) {
                        $p['questions'][$i]['choices'][$j]['explanation'] = '';

                        break;
                    }
                }

                return $p;
            },
            'no explanation',
        ];

        yield 'a holdout question written into the practice payload' => [
            static function (array $p): array {
                $p['questions'][0]['pool'] = 'HOLDOUT';

                return $p;
            },
            'HOLDOUT',
        ];
    }

    #[DataProvider('defects')]
    public function testEachCheckRejectsItsOwnDefect(callable $mutate, string $expected): void
    {
        $problems = self::problems($mutate(self::payload()));

        self::assertNotEmpty(
            $problems,
            'the check stayed silent on its own defect; it is VACUOUS, not passing',
        );
        self::assertStringContainsString(
            $expected,
            implode(' | ', $problems),
        );
    }

    private static function firstEnglish(array $payload): int
    {
        foreach ($payload['questions'] as $i => $question) {
            if ('en' === $question['language']) {
                return $i;
            }
        }

        throw new \RuntimeException('no English question in the practice payload');
    }

    /**
     * Every rule the Practice feedback depends on, in one place.
     *
     * @param array<string, mixed> $payload
     *
     * @return list<string>
     */
    private static function problems(array $payload): array
    {
        $project = new Project(\dirname(__DIR__, 2));
        $problems = [];

        foreach ($payload['items'] as $id => $entry) {
            if ('' === trim((string) $entry['official_item'])) {
                $problems[] = $id.': empty item label';
            }
            if ([] === ($entry['learning_outcomes'] ?? [])) {
                $problems[] = $id.': no learning outcome, so no key takeaway';
            }
            $url = (string) $entry['course_url'];
            if ('' === $url) {
                $problems[] = $id.': no course url';

                continue;
            }
            $path = $project->path('website/docs/'.substr($url, \strlen('/docs/')).'.md');
            if (!is_file($path)) {
                $problems[] = $id.': course page missing for '.$url;
            }
        }

        foreach ($payload['questions'] as $question) {
            $id = (string) $question['id'];

            if (Pool::Holdout->value === ($question['pool'] ?? null)) {
                $problems[] = $id.': HOLDOUT question in the practice payload';
            }
            if (!isset($payload['items'][$question['official_item']])) {
                $problems[] = $id.': unknown item '.$question['official_item'];
            }
            if ('en' !== $question['language']) {
                continue;
            }
            if ('' === trim((string) $question['explanation'])) {
                $problems[] = $id.': empty explanation';
            }

            $correct = array_filter($question['choices'], static fn (array $c): bool => $c['correct']);
            if (\count($correct) !== $question['required_answer_count']) {
                $problems[] = \sprintf('%s: %d expected answers but %d marked correct',
                    $id, $question['required_answer_count'], \count($correct));
            }
            foreach ($question['choices'] as $choice) {
                if (!$choice['correct'] && '' === trim((string) ($choice['explanation'] ?? ''))) {
                    $problems[] = $id.': distractor '.$choice['id'].' has no explanation';
                }
            }
        }

        return $problems;
    }
}
