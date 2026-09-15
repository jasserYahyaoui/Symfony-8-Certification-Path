<?php

declare(strict_types=1);

namespace CertPath\Tests\Integration;

use CertPath\Build\PayloadBuilder;
use CertPath\Support\Project;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The simulations hub (/simulations).
 *
 * The page holds no sentence of its own: it renders this payload. So what is
 * checked here is that every sentence the page needs is present and comes from a
 * blueprint, that the one distinction the hub must not blur stays sharp — Mock
 * 4's count and duration are published, the other four are this project's
 * decision — and that the payload carries no question at all.
 *
 * That last one is the reason the assertion exists rather than the test alone.
 * Mock 4's bank is reserved and unseen (ADR-0005 Option A), and a page whose
 * whole subject is "this one is unseen" is the last place a leak would be
 * looked for.
 */
final class SimulationsPayloadTest extends TestCase
{
    /** @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: array<string, mixed>} */
    private function fixture(): array
    {
        $project = Project::locate();
        $mocks = $project->loadMocksBlueprint();
        $four = $project->loadMockBlueprint('4');

        self::assertNotSame([], $mocks, 'the mocks blueprint must exist');
        self::assertNotSame([], $four, 'the Mock 4 blueprint must exist');

        return [(new PayloadBuilder())->simulationsPayload($mocks, $four), $mocks, $four];
    }

    public function testEveryMockIsDescribedInTheOrderTheProjectRecommends(): void
    {
        [$payload] = $this->fixture();

        $ids = array_column($payload['mocks'], 'id');

        self::assertSame(['mock-1', 'mock-2', 'mock-3', 'mock-5', 'mock-4'], $ids, 'the hub must list every mock, ordered by the blueprint sequence');

        $sequences = array_column($payload['mocks'], 'sequence');
        $sorted = $sequences;
        sort($sorted);

        self::assertSame($sorted, $sequences);
    }

    public function testEverySentenceComesFromABlueprint(): void
    {
        [$payload, $mocks, $four] = $this->fixture();

        foreach ($payload['mocks'] as $entry) {
            $spec = 'mock-4' === $entry['id']
                ? $four
                : PayloadBuilder::mockSpec($mocks, $entry['id']);

            self::assertSame($spec['purpose'], $entry['purpose'], $entry['id'].': the role is not the blueprint\'s');
            self::assertSame($spec['when_to_use'], $entry['when_to_use'], $entry['id'].': the guidance is not the blueprint\'s');
            self::assertNotSame('', trim($entry['when_to_use']), $entry['id'].': no guidance, so the hub cannot say when to sit it');
        }

        self::assertSame($mocks['not_official'], $payload['not_official']);
    }

    /**
     * §10 fixes a count and a duration for Mock 4 only. Labelling all five the
     * same way would be false in one direction or the other, and the direction
     * that matters is the one that presents this project's decision as official.
     */
    public function testOnlyMockFourIsLabelledOfficial(): void
    {
        [$payload, , $four] = $this->fixture();

        foreach ($payload['mocks'] as $entry) {
            if ('mock-4' === $entry['id']) {
                self::assertSame('OFFICIAL_FORMAT', $entry['format_label']);
                self::assertSame((string) $four['official_constraints']['questions'], $entry['question_count']);
                self::assertSame((string) $four['official_constraints']['minutes'], $entry['duration_minutes']);
                self::assertFalse($entry['repeatable'], 'Mock 4 must not read like the four that can be replayed');

                continue;
            }

            self::assertSame('INTERNAL_TRAINING_FORMAT', $entry['format_label'], $entry['id']);
            self::assertTrue($entry['repeatable'], $entry['id']);
        }
    }

    public function testTheHubCarriesNoQuestion(): void
    {
        [$payload] = $this->fixture();

        PayloadBuilder::assertNoQuestionLeak($payload);

        $json = json_encode($payload, \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR);

        self::assertStringNotContainsString('QST-', $json, 'a question id reached the hub');
        self::assertStringNotContainsString('CHO-', $json, 'a choice id reached the hub');
    }

    /**
     * A guard that has only ever been silent is not a guard. Each mutation below
     * is one way the payload could start carrying content, and each must be
     * refused — including at the per-mock level, where a leak would hide.
     *
     * @param array<string, mixed> $defect
     */
    #[DataProvider('leaks')]
    public function testTheGuardRefusesEachLeak(array $defect, string $expected): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches($expected);

        PayloadBuilder::assertNoQuestionLeak($defect);
    }

    /** @return iterable<string, array{0: array<string, mixed>, 1: string}> */
    public static function leaks(): iterable
    {
        yield 'a question list at the top level' => [
            ['mocks' => [], 'questions' => [['id' => 'QST-x']]],
            '/carries "questions"/',
        ];

        yield 'an item index at the top level' => [
            ['mocks' => [], 'items' => ['ITM-x' => []]],
            '/carries "items"/',
        ];

        yield 'a sample inside one mock' => [
            ['mocks' => [['id' => 'mock-4', 'questions' => [['id' => 'QST-x']]]]],
            '/mock-4 carries "questions"/',
        ];

        yield 'an explanation inside one mock' => [
            ['mocks' => [['id' => 'mock-1', 'explanation' => 'because…']]],
            '/mock-1 carries "explanation"/',
        ];
    }
}
