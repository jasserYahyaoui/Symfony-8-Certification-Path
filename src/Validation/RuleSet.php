<?php

declare(strict_types=1);

namespace CertPath\Validation;

use CertPath\Validation\Rule\AssessmentCoverageRule;
use CertPath\Validation\Rule\CognitiveLevelRule;
use CertPath\Validation\Rule\CourseIntegrityRule;
use CertPath\Validation\Rule\DeadInternalLinkRule;
use CertPath\Validation\Rule\DuplicateQuestionRule;
use CertPath\Validation\Rule\EnrichmentBudgetRule;
use CertPath\Validation\Rule\ExamReadyEvidenceRule;
use CertPath\Validation\Rule\FlashcardIntegrityRule;
use CertPath\Validation\Rule\HoldoutIsolationRule;
use CertPath\Validation\Rule\LearningOutcomeRule;
use CertPath\Validation\Rule\OfficialWordingLockRule;
use CertPath\Validation\Rule\OutOfScopeContaminationRule;
use CertPath\Validation\Rule\QuestionIntegrityRule;
use CertPath\Validation\Rule\ReferentialIntegrityRule;
use CertPath\Validation\Rule\SourceAnchorRule;
use CertPath\Validation\Rule\UniqueItemIdsRule;
use CertPath\Validation\Rule\ValidationPoolCoverageRule;
use CertPath\Validation\Rule\VersionContaminationRule;

/**
 * The mandatory CI rule set of Master Plan §12.
 *
 * §12 also states: "Never weaken tests merely to obtain a green build."
 * Removing a rule from this list is therefore a governance decision, not a
 * maintenance detail.
 */
final class RuleSet
{
    /**
     * @return list<Rule>
     */
    public static function mandatory(): array
    {
        return [
            new UniqueItemIdsRule(),
            new OfficialWordingLockRule(),
            new LearningOutcomeRule(),
            new AssessmentCoverageRule(),
            new SourceAnchorRule(),
            new QuestionIntegrityRule(),
            new CognitiveLevelRule(),
            new CourseIntegrityRule(),
            new FlashcardIntegrityRule(),
            new HoldoutIsolationRule(),
            new ValidationPoolCoverageRule(),
            new OutOfScopeContaminationRule(),
            new DuplicateQuestionRule(),
            new ReferentialIntegrityRule(),
            new VersionContaminationRule(),
            new EnrichmentBudgetRule(),
            new ExamReadyEvidenceRule(),
            new DeadInternalLinkRule(),
        ];
    }
}
