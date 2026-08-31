<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * §12: "unsupported Symfony version references"; §21.3 version-contamination
 * audit.
 *
 * The exam is Symfony 8.0 only (§10), so a reference to a later minor or major
 * in a source URL is contamination, not a harmless detail.
 */
final class VersionContaminationRule implements Rule
{
    /** Doc paths for Symfony versions that postdate the examinable 8.0. */
    private const string UNSUPPORTED_DOC_PATH = '#/doc/(?:current|[89]\.[1-9]\d*|[1-9]\d+\.\d+)/#';

    public function id(): string
    {
        return 'VER-001';
    }

    public function description(): string
    {
        return 'No source references a Symfony version other than 8.0.';
    }

    public function check(ContentSet $content): array
    {
        $violations = [];

        foreach ($content->matrix->items as $item) {
            foreach ($item->officialSources as $source) {
                if (1 === preg_match(self::UNSUPPORTED_DOC_PATH, $source->url)) {
                    $violations[] = new Violation(
                        $this->id(),
                        Severity::Error,
                        'Source points outside Symfony 8.0: '.$source->url,
                        $item->id->value,
                    );
                }

                if (null !== $source->branch && '' !== $source->branch
                    && !\in_array($source->branch, ['8.0', 'refs/heads/8.0'], true)
                    && str_contains((string) $source->repository, 'symfony/symfony')) {
                    $violations[] = new Violation(
                        $this->id(),
                        Severity::Error,
                        \sprintf('Symfony source pinned to branch "%s" instead of 8.0.', $source->branch),
                        $item->id->value,
                    );
                }
            }
        }

        return $violations;
    }
}
