<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * §12: "changed official wording without approved syllabus refresh".
 *
 * The verbatim wording of §3.1 is fingerprinted in `docs/syllabus/wording.lock`.
 * Editing an official item's wording is a syllabus refresh — a deliberate act
 * that must update the lock — not a routine content edit.
 */
final class OfficialWordingLockRule implements Rule
{
    public function id(): string
    {
        return 'SYL-002';
    }

    public function description(): string
    {
        return 'Official wording matches the approved syllabus fingerprint lock.';
    }

    public function check(ContentSet $content): array
    {
        if ([] === $content->wordingFingerprints) {
            return [];
        }

        $violations = [];

        foreach ($content->matrix->items as $item) {
            $expected = $content->wordingFingerprints[$item->id->value] ?? null;
            if (null === $expected) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    'Item is absent from the wording lock; run an approved syllabus refresh.',
                    $item->id->value,
                );
                continue;
            }

            if (!hash_equals($expected, self::fingerprint($item->officialWording))) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    'Official wording changed without an approved syllabus refresh.',
                    $item->id->value,
                );
            }
        }

        return $violations;
    }

    public static function fingerprint(string $wording): string
    {
        return hash('sha256', trim($wording));
    }
}
