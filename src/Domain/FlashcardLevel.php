<?php

declare(strict_types=1);

namespace CertPath\Domain;

/**
 * The cognitive job a flashcard does, Master Plan §6.
 *
 * §6 asks a card to carry genuine memorization value; it does not say that
 * every card does the same kind of work. A deck that only ever asks « what is
 * the value of X » drills recognition and leaves the learner unable to act on
 * it, which is the failure mode this axis exists to make visible: the level is
 * declared on the card, so a deck that is entirely RECALL can be seen to be
 * one rather than felt to be one.
 *
 * The field is OPTIONAL. Cards written before the axis existed carry no level
 * and are rendered ahead of the grouped ones — the alternative was to stamp a
 * level on 163 cards nobody re-read, which is the fabrication §19 forbids.
 */
enum FlashcardLevel: string
{
    /** A fact to hold verbatim: a list, a number, a name. */
    case Recall = 'RECALL';

    /** Why the fact is what it is, or what distinguishes it from its neighbour. */
    case Understanding = 'UNDERSTANDING';

    /** The fact used on a concrete case: given this input, what happens. */
    case Application = 'APPLICATION';

    /** The plausible wrong answer, named as such, and what defeats it. */
    case Trap = 'TRAP';

    public function label(): string
    {
        return match ($this) {
            self::Recall => 'Mémorisation',
            self::Understanding => 'Compréhension',
            self::Application => 'Application',
            // Not « Pièges d'examen » : a course page already carries a
            // section of that exact name, and two identical headings on one
            // page make its outline unreadable — to a screen reader first.
            self::Trap => 'Pièges',
        };
    }

    /**
     * Display order, independent of the order cards appear in their deck file.
     *
     * @return list<self>
     */
    public static function ordered(): array
    {
        return [self::Recall, self::Understanding, self::Application, self::Trap];
    }
}
