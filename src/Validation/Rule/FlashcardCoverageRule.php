<?php

declare(strict_types=1);

namespace CertPath\Validation\Rule;

use CertPath\Validation\ContentSet;
use CertPath\Validation\Rule;
use CertPath\Validation\Severity;
use CertPath\Validation\Violation;
use Symfony\Component\Yaml\Yaml;

/**
 * PED-010: every official item carries a flashcard, or a written exemption.
 *
 * Before this rule, 37 of the 163 items had no flashcard. Twenty-nine of those
 * absences were editorial decisions justified in the header comment of their
 * lot's file; eight were justified nowhere at all. Nothing told the two apart,
 * because a comment is not data — so a real gap and a considered decision
 * looked identical to every gate in the project.
 *
 * The rule reads `docs/policy/flashcard-exemptions.yml` and fails both ways:
 * an item with no card and no entry is a gap, and an entry for an item that
 * now has a card is a stale claim. The second half matters as much as the
 * first: an exemption nobody revisits is how a register starts lying.
 *
 * It does NOT relax §6. A card for a fact already retained through application
 * is still pure revision cost, and exempting such an item is still the right
 * call — it just has to be written down where a script can read it.
 */
final class FlashcardCoverageRule implements Rule
{
    public const string REGISTER = 'docs/policy/flashcard-exemptions.yml';

    public function id(): string
    {
        return 'FLC-002';
    }

    public function description(): string
    {
        return 'Every official item has a flashcard, or a written and still-applicable exemption.';
    }

    /**
     * @return array<string, true> official item ids named by the register
     */
    private function exemptions(ContentSet $content): array
    {
        $path = $content->projectDir.'/'.self::REGISTER;

        if (!is_file($path)) {
            return [];
        }

        $document = Yaml::parseFile($path);
        $exempt = [];

        foreach ($document['exemptions'] ?? [] as $entry) {
            if (\is_array($entry) && isset($entry['official_item'])) {
                $exempt[(string) $entry['official_item']] = true;
            }
        }

        return $exempt;
    }

    public function check(ContentSet $content): array
    {
        $exemptions = $this->exemptions($content);
        $withCard = [];
        foreach ($content->flashcards as $card) {
            $withCard[$card->officialItemId] = true;
        }

        $violations = [];
        $items = [];

        foreach ($content->matrix->officialItems() as $item) {
            $id = $item->id->value;
            $items[$id] = true;
            $hasCard = isset($withCard[$id]);
            $exempt = isset($exemptions[$id]);

            if (!$hasCard && !$exempt) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    \sprintf(
                        'Item "%s" has no flashcard and no exemption in %s.',
                        $item->officialItem,
                        self::REGISTER,
                    ),
                    $id,
                );

                continue;
            }

            if ($hasCard && $exempt) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    \sprintf(
                        'Item "%s" is exempted in %s but now carries a flashcard; the exemption is stale.',
                        $item->officialItem,
                        self::REGISTER,
                    ),
                    $id,
                );
            }
        }

        foreach (array_keys($exemptions) as $id) {
            if (!isset($items[$id])) {
                $violations[] = new Violation(
                    $this->id(),
                    Severity::Error,
                    \sprintf('Exemption in %s names unknown official item "%s".', self::REGISTER, $id),
                    $id,
                );
            }
        }

        return $violations;
    }
}
