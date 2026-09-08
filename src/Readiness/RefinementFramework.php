<?php

declare(strict_types=1);

namespace CertPath\Readiness;

/**
 * What "refined" means, versioned.
 *
 * Lot 01 was audited and merged under a definition of refinement that did not
 * yet include question archetypes, the outcome-to-question link or a revision
 * budget (ADR-0007). That audit happened and its findings stand; what changed
 * is the bar.
 *
 * Recording the framework version each lot was refined under is the only
 * honest way to raise a bar:
 *
 *   - the lot's audit evidence is not deleted, because it is real;
 *   - the lot is not credited with structures it does not have;
 *   - the new rules do not fail the build over content whose refinement pass
 *     predates them, which would only invite the rules to be relaxed.
 *
 * A lot refined under an older version is re-refined, not re-labelled. The
 * Certification Readiness figure falls when this constant is raised, and that
 * is the intended behaviour — a metric that only ever rises measures effort,
 * not readiness.
 */
final class RefinementFramework
{
    /**
     * 1 — course, questions, declared modes, anchored sources, expert audit.
     * 2 — adds identified learning outcomes, the outcome-to-question link,
     *     question archetypes and the revision budget.
     */
    public const int CURRENT = 2;

    /** The version assumed for a log entry that predates the field. */
    public const int DEFAULT_FOR_UNVERSIONED_ENTRY = 1;
}
