<?php

declare(strict_types=1);

namespace CertPath\Tests\Integration;

use CertPath\Build\PayloadBuilder;
use CertPath\Domain\Course;
use CertPath\Support\CourseUrl;
use CertPath\Support\Project;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule\OutcomeSectionRule;
use PHPUnit\Framework\TestCase;

/**
 * The link from a wrong answer to the course section that teaches it (owner's
 * request of 2026-10-14), on the real corpus: every outcome has a section, every
 * exported question resolves, and SEC-001 refuses each way the link can break.
 */
final class OutcomeSectionTest extends TestCase
{
    private static ?ContentSet $content = null;

    private function content(): ContentSet
    {
        return self::$content ??= Project::locate()->loadContentSet();
    }

    public function testTheCorpusMapsEveryOutcomeToASection(): void
    {
        self::assertSame([], (new OutcomeSectionRule())->check($this->content()));
    }

    public function testEveryExamQuestionLinksToASectionOfItsCourse(): void
    {
        $project = Project::locate();
        $payload = (new PayloadBuilder())->examSeriesPayload($this->content(), $project->loadMockBlueprint('4'));

        $links = 0;
        foreach ($payload['questions'] as $q) {
            $sections = $payload['items'][$q['official_item']]['outcome_sections'];
            self::assertNotSame([], $q['assesses_outcomes'], $q['id'].' assesses no outcome, so it has nowhere to link');
            foreach ($q['assesses_outcomes'] as $outcome) {
                self::assertArrayHasKey($outcome, $sections, $q['id'].' assesses an outcome with no section');
                self::assertStringStartsWith($payload['items'][$q['official_item']]['course_url'].'#s-', $sections[$outcome]['url']);
                ++$links;
            }
        }
        self::assertGreaterThan(600, $links);
    }

    public function testTheAnchorIgnoresInlineCodeAndAccents(): void
    {
        self::assertSame('s-atpath-ne-change-pas-la-valeur-fautive', CourseUrl::sectionAnchor('`atPath()` ne change pas la valeur fautive'));
        self::assertSame('s-regles', CourseUrl::sectionAnchor('Règles'));
    }

    public function testAHeadingInsideAFenceIsNotASection(): void
    {
        self::assertSame(['Vraie'], Course::headingsOf("## Vraie\n\n```markdown\n## Fausse\n```\n"));
    }

    public function testTheRuleRefusesAHeadingThatDoesNotExist(): void
    {
        self::assertMessage('is not a `## ` heading', $this->withFirstCourse(static fn (array $map): array => array_map(static fn (string $h): string => $h.' (renamed)', $map)));
    }

    public function testTheRuleRefusesAnOutcomeWithoutSection(): void
    {
        self::assertMessage('has no section', $this->withFirstCourse(static fn (array $map): array => \array_slice($map, 1, null, true)));
    }

    public function testTheRuleRefusesASummaryAsTarget(): void
    {
        self::assertMessage('frames the course', $this->withFirstCourse(static fn (array $map): array => array_map(static fn (): string => 'Points clés', $map)));
    }

    public function testTheRuleRefusesAnOutcomeOfAnotherItem(): void
    {
        self::assertMessage('is not an outcome of', $this->withFirstCourse(static fn (array $map): array => $map + ['OUT-zzzzzzzzzzzz' => array_values($map)[0]]));
    }

    /**
     * @param callable(array<string, string>): array<string, string> $change
     *
     * @return list<string>
     */
    private function withFirstCourse(callable $change): array
    {
        $content = $this->content();
        $courses = $content->courses;
        $c = $courses[0];
        $courses[0] = new Course(
            $c->id, $c->officialItemId, $c->title, $c->contentLevel, $c->body, $c->officialSources,
            $c->language, $c->verificationStatus, $c->reviewedAt, $change($c->outcomeSections),
        );
        $mutated = new ContentSet(
            $content->matrix, $content->questions, $courses, $content->flashcards, $content->excludedTerms,
            $content->contextualExclusions, $content->wordingFingerprints, $content->contentFiles,
            $content->projectDir, $content->comprehension,
        );

        return array_map(static fn ($v): string => $v->format(), (new OutcomeSectionRule())->check($mutated));
    }

    /**
     * @param list<string> $messages
     */
    private static function assertMessage(string $needle, array $messages): void
    {
        self::assertNotSame([], $messages, 'SEC-001 stayed silent');
        self::assertStringContainsString('[ERROR] SEC-001', $messages[0]);
        self::assertTrue((bool) array_filter($messages, static fn (string $m): bool => str_contains($m, $needle)), implode("\n", $messages));
    }
}
