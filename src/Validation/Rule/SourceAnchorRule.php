<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Domain\ItemStatus;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * §12: "missing or irrelevant sources" and
 *      "source without version anchor where required".
 *
 * §2.3 also forbids `/current/` as a primary source.
 */
final class SourceAnchorRule implements Rule
{
    public function id(): string
    {
        return 'SRC-001';
    }

    public function description(): string
    {
        return 'Sources are present, version-anchored and never taken from /current/.';
    }

    public function check(ContentSet $content): array
    {
        $violations = [];

        foreach ($content->matrix->officialItems() as $item) {
            if (!$item->status->isAtLeast(ItemStatus::SourceVerified)) {
                continue;
            }

            if ([] === $item->officialSources) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    'Item claims SOURCE_VERIFIED but declares no official source.',
                    $item->id->value,
                );
                continue;
            }

            foreach ($item->officialSources as $source) {
                if (str_contains($source->url, '/current/')) {
                    $violations[] = new Violation(
                        $this->id(),
                        Severity::Error,
                        '/current/ is never a valid primary source (§2.3): '.$source->url,
                        $item->id->value,
                    );
                }

                if (!$source->isVersionAnchored()) {
                    $violations[] = new Violation(
                        $this->id(),
                        Severity::Error,
                        'Source lacks a version anchor (branch or commit sha): '.$source->url,
                        $item->id->value,
                    );
                }

                if (!$source->hasAnchor()) {
                    $violations[] = new Violation(
                        $this->id(),
                        Severity::Warning,
                        'Source has no section anchor or line reference; a homepage is not evidence (§2.4): '.$source->url,
                        $item->id->value,
                    );
                }
            }
        }

        return $violations;
    }
}
