<?php

declare(strict_types=1);

namespace CertPath\Tests\Unit;

use CertPath\Domain\Choice;
use CertPath\Domain\SyllabusMatrix;
use CertPath\Support\Id;
use CertPath\Tests\Support\ItemFactory;
use CertPath\Tests\Support\QuestionFactory;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule\OutOfScopeContaminationRule;
use PHPUnit\Framework\TestCase;

/**
 * SYL-1 regression suite.
 *
 * The official syllabus excludes, under Messenger: "third-party transports
 * (Doctrine, Redis, Amazon SQS, etc.) and their usage/configuration is not
 * included". AUD-01 found no entry for it in exclusions.yml, so SCOPE-001
 * could not see a scored question turning on one.
 *
 * The obvious fix — adding doctrine/redis/sqs to the flat `match_terms` list —
 * is wrong, and the negative cases below are why. Redis is an in-scope Cache
 * adapter; Doctrine is named by the syllabus as its own exclusion example; the
 * transport concept is an examinable Messenger item. A term list would reject
 * all three. The exclusion is contextual and is matched as such.
 */
final class MessengerTransportExclusionRuleTest extends TestCase
{
    /** Mirrors the entry in docs/syllabus/exclusions.yml. */
    private const CONTEXTUAL = [[
        'id' => 'EXC-MESSENGER-THIRD-PARTY-TRANSPORTS',
        'official_topic' => 'Messenger',
        'transport_terms' => ['doctrine', 'redis', 'amazon sqs', 'sqs', 'amqp', 'rabbitmq', 'beanstalkd', 'kafka'],
    ]];

    private static function ask(string $topic, string $question, string $explanation = '', array $choiceTexts = ['a', 'b'], array $tags = []): ContentSet
    {
        $item = ItemFactory::make();
        $choices = [];
        foreach ($choiceTexts as $i => $text) {
            $choices[] = new Choice(Id::mint(\CertPath\Support\EntityType::Choice), $text, 0 === $i, null);
        }

        return new ContentSet(
            matrix: new SyllabusMatrix([$item]),
            questions: [QuestionFactory::make([
                'officialItemId' => $item->id->value,
                'officialTopic' => $topic,
                'question' => $question,
                'explanation' => $explanation,
                'choices' => $choices,
                'tags' => $tags,
            ])],
            contextualExclusions: self::CONTEXTUAL,
        );
    }

    /**
     * @return list<string>
     */
    private static function scopeViolations(ContentSet $content): array
    {
        $out = [];
        foreach ((new OutOfScopeContaminationRule())->check($content) as $v) {
            $out[] = $v->message;
        }

        return $out;
    }

    // --- POSITIVE: these must be detected -----------------------------------

    public function testAScoredMessengerQuestionOnTheDoctrineTransportIsRejected(): void
    {
        $content = self::ask('Messenger', 'How do you configure the Doctrine transport DSN?');

        self::assertNotEmpty(self::scopeViolations($content));
    }

    public function testAScoredMessengerQuestionOnTheRedisTransportIsRejected(): void
    {
        $content = self::ask('Messenger', 'Which Redis transport option controls the consumer group?');

        self::assertNotEmpty(self::scopeViolations($content));
    }

    public function testAScoredMessengerQuestionOnAmazonSqsIsRejected(): void
    {
        $content = self::ask('Messenger', 'What is the visibility timeout of the Amazon SQS transport?');

        self::assertNotEmpty(self::scopeViolations($content));
    }

    public function testAnotherIdentifiableThirdPartyTransportIsRejected(): void
    {
        $content = self::ask('Messenger', 'Which DSN scheme selects the RabbitMQ transport?');

        self::assertNotEmpty(self::scopeViolations($content));
    }

    /** Operational usage counts, not only configuration. */
    public function testTransportUsageInAnExplanationIsRejected(): void
    {
        $content = self::ask('Messenger', 'Why did the worker stop consuming?', 'Because the AMQP transport lost its channel.');

        self::assertNotEmpty(self::scopeViolations($content));
    }

    /** A distractor is content the learner reads, so it is in the haystack. */
    public function testATransportNamedOnlyInADistractorIsRejected(): void
    {
        $content = self::ask('Messenger', 'Which transport is built in?', '', ['sync://', 'the Beanstalkd transport']);

        self::assertNotEmpty(self::scopeViolations($content));
    }

    // --- NEGATIVE: these must NOT be rejected -------------------------------

    /** Redis is a legitimate in-scope Cache adapter. */
    public function testRedisOutsideMessengerIsNotRejected(): void
    {
        $content = self::ask('Miscellaneous', 'Which cache adapter survives a restart?', 'A Redis adapter persists; an array adapter does not.');

        self::assertSame([], self::scopeViolations($content));
    }

    /** The syllabus names Doctrine as its own exclusion example. */
    public function testDoctrineNamedOnlyAsAnOfficialOutOfScopeExampleIsNotRejected(): void
    {
        $content = self::ask(
            'Messenger',
            'Which of these is out of scope for the exam?',
            'The syllabus excludes third-party transports such as Doctrine.',
            ['a', 'b'],
            ['exclusion-note'],
        );

        self::assertSame([], self::scopeViolations($content));
    }

    /** The transport concept is an examinable Messenger item. */
    public function testGenericMessengerTransportConceptsAreNotRejected(): void
    {
        $content = self::ask(
            'Messenger',
            'What happens to a message routed to no transport?',
            'It is handled immediately, synchronously. sync:// and in-memory:// are the framework\'s own transports.',
        );

        self::assertSame([], self::scopeViolations($content));
    }

    /** Messenger itself is in scope and must never be excluded. */
    public function testMessengerItselfIsNotExcluded(): void
    {
        $content = self::ask('Messenger', 'How many times does the middleware chain run for an async message?', 'Twice: on dispatch and on receipt.');

        self::assertSame([], self::scopeViolations($content));
    }

    /** Word boundaries: a term must not match inside a longer word. */
    public function testATransportTermInsideALongerWordIsNotRejected(): void
    {
        $content = self::ask('Messenger', 'What does the redistribution of retries do?', 'Redistributing retries across queues is not a transport concern.');

        self::assertSame([], self::scopeViolations($content));
    }

    /** The contextual exclusion must not leak into other topics. */
    public function testAnAmqpMentionOutsideMessengerIsNotRejected(): void
    {
        $content = self::ask('Symfony Architecture', 'Which component dispatches kernel events?', 'The EventDispatcher. AMQP is unrelated here.');

        self::assertSame([], self::scopeViolations($content));
    }

    /** With no contextual exclusion loaded, nothing changes for anyone. */
    public function testTheRuleIsInertWhenNoContextualExclusionIsConfigured(): void
    {
        $item = ItemFactory::make();
        $content = new ContentSet(
            matrix: new SyllabusMatrix([$item]),
            questions: [QuestionFactory::make([
                'officialItemId' => $item->id->value,
                'officialTopic' => 'Messenger',
                'question' => 'How do you configure the Doctrine transport?',
            ])],
        );

        self::assertSame([], self::scopeViolations($content));
    }
}
