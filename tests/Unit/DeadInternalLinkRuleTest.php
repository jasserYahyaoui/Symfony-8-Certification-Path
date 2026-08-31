<?php

declare(strict_types=1);

namespace CertPath\Tests\Unit;

use CertPath\Domain\SyllabusMatrix;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule\DeadInternalLinkRule;
use CertPath\Validation\Severity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DeadInternalLinkRule::class)]
final class DeadInternalLinkRuleTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/certpath-links-'.bin2hex(random_bytes(6));
        mkdir($this->dir.'/docs', 0o775, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir.'/docs/*') ?: [] as $file) {
            unlink($file);
        }
        @rmdir($this->dir.'/docs');
        @rmdir($this->dir);
    }

    public function testBrokenRelativeLinkIsReported(): void
    {
        file_put_contents($this->dir.'/docs/a.md', 'See [the plan](missing.md).');

        $violations = (new DeadInternalLinkRule())->check($this->content(['docs/a.md']));

        self::assertCount(1, $violations);
        self::assertSame('LNK-001', $violations[0]->ruleId);
        self::assertSame(Severity::Error, $violations[0]->severity);
        self::assertStringContainsString('missing.md', $violations[0]->message);
    }

    public function testResolvingLinkPasses(): void
    {
        file_put_contents($this->dir.'/docs/a.md', 'See [b](b.md) and [b anchored](b.md#section).');
        file_put_contents($this->dir.'/docs/b.md', '# B');

        self::assertSame([], (new DeadInternalLinkRule())->check($this->content(['docs/a.md'])));
    }

    public function testExternalLinksAndBareAnchorsAreIgnored(): void
    {
        file_put_contents(
            $this->dir.'/docs/a.md',
            'See [docs](https://symfony.com/doc/8.0/index.html), [top](#top) and [mail](mailto:a@b.test).',
        );

        self::assertSame([], (new DeadInternalLinkRule())->check($this->content(['docs/a.md'])));
    }

    /**
     * @param list<string> $files
     */
    private function content(array $files): ContentSet
    {
        return new ContentSet(
            matrix: new SyllabusMatrix([]),
            contentFiles: $files,
            projectDir: $this->dir,
        );
    }
}
