<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Domain\ItemStatus;
use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;

/**
 * §3.5 and §16: EXAM_READY is a claim about evidence, never an intention.
 *
 * This is the rule that stops the coverage percentage from being inflated by
 * flipping a boolean.
 */
final class ExamReadyEvidenceRule implements Rule
{
    public function id(): string
    {
        return 'RDY-001';
    }

    public function description(): string
    {
        return 'Every EXAM_READY claim is backed by status, sources, content, assessment and a verification date.';
    }

    public function check(ContentSet $content): array
    {
        $violations = [];

        foreach ($content->matrix->items as $item) {
            $claimsReady = $item->examReady || $item->status->isExamReady();
            if (!$claimsReady) {
                continue;
            }

            $subject = $item->id->value;

            if ($item->examReady !== $item->status->isExamReady()) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    \sprintf(
                        'exam_ready=%s contradicts status=%s.',
                        $item->examReady ? 'true' : 'false',
                        $item->status->value,
                    ),
                    $subject,
                );
            }

            if (!$item->verificationStatus->mayBeScored()) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    \sprintf('EXAM_READY claimed with verification_status=%s.', $item->verificationStatus->value),
                    $subject,
                );
            }

            if ([] === $item->officialSources) {
                $violations[] = new Violation($this->id(), Severity::Error, 'EXAM_READY without an official source.', $subject);
            }

            if ([] === $item->courseRefs) {
                $violations[] = new Violation($this->id(), Severity::Error, 'EXAM_READY without teaching content.', $subject);
            }

            if (!$item->hasAssessment()) {
                $violations[] = new Violation($this->id(), Severity::Error, 'EXAM_READY without an assessment.', $subject);
            }

            if (null === $item->lastVerifiedAt || '' === trim($item->lastVerifiedAt)) {
                $violations[] = new Violation($this->id(), Severity::Error, 'EXAM_READY without a verification date.', $subject);
            }

            if ('' === trim((string) $item->minimumEvidence)) {
                $violations[] = new Violation($this->id(), Severity::Error, 'EXAM_READY without declared minimum evidence.', $subject);
            }

            if (!$item->status->isAtLeast(ItemStatus::Tested)) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    'EXAM_READY claimed before the item reached TESTED.',
                    $subject,
                );
            }
        }

        return $violations;
    }
}
