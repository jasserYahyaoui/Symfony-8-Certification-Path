<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * §12: "dead internal links".
 *
 * Only repository-relative Markdown links are checked; external URLs are the
 * job of the source-verification policy, not of a link crawler.
 */
final class DeadInternalLinkRule implements Rule
{
    public function id(): string
    {
        return 'LNK-001';
    }

    public function description(): string
    {
        return 'Every relative Markdown link resolves to a file in the repository.';
    }

    public function check(ContentSet $content): array
    {
        $violations = [];

        foreach ($content->contentFiles as $relativePath) {
            $absolute = $content->projectDir.'/'.$relativePath;
            if (!is_file($absolute)) {
                continue;
            }

            $markdown = file_get_contents($absolute);
            if (false === $markdown) {
                continue;
            }

            preg_match_all('/\[[^\]]*\]\(([^)\s]+)\)/', $markdown, $matches);

            foreach ($matches[1] ?? [] as $target) {
                if ($this->isExternalOrAnchor($target)) {
                    continue;
                }

                $path = strtok($target, '#') ?: $target;
                $resolved = \dirname($absolute).'/'.$path;

                if (!file_exists($resolved)) {
                    $violations[] = new Violation(
                        $this->id(),
                        Severity::Error,
                        \sprintf('Dead internal link "%s".', $target),
                        $relativePath,
                    );
                }
            }
        }

        return $violations;
    }

    private function isExternalOrAnchor(string $target): bool
    {
        return str_starts_with($target, '#')
            || str_starts_with($target, 'http://')
            || str_starts_with($target, 'https://')
            || str_starts_with($target, 'mailto:');
    }
}
