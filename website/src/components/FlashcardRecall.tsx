import React, {useEffect, useRef, useState} from 'react';
import type {ReviewOutcome} from '@site/src/lib/review';
import {flashcardRecord, localDay, rateFlashcard, resetFlashcard, type FlashcardRecord} from '@site/src/lib/storage';

interface Props {
  /** The card id, e.g. `FLC-…`, written by the generator into the page. */
  card: string;
  /** The card's version: a hash of its front and back. */
  version: string;
  item: string;
}

const CHOICES: {outcome: ReviewOutcome; label: string; hint: string}[] = [
  {outcome: 'AGAIN', label: 'À revoir', hint: 'je ne savais pas'},
  {outcome: 'HARD', label: 'Difficile', hint: 'retrouvé avec effort'},
  {outcome: 'GOOD', label: 'Bien', hint: 'retrouvé'},
  {outcome: 'EASY', label: 'Facile', hint: 'immédiat'},
];

function formatDay(day: string): string {
  const [y, m, d] = day.split('-').map(Number);
  return new Date(y, m - 1, d).toLocaleDateString('fr-FR', {weekday: 'long', day: 'numeric', month: 'long'});
}

/**
 * The recall rating of one flashcard (remediation P2.3).
 *
 * Rendered inside the card's <details>, after the back: the learner rates what
 * they knew BEFORE reading it, and only that rating is stored — opening a card
 * is not knowing it. The schedule is docs/policy/review-algorithm.md, ported to
 * src/lib/review.ts and held equal to the PHP reference by verify-review.mjs;
 * it is a plain interval schedule, and no efficacy is claimed for it.
 */
export default function FlashcardRecall({card, version, item}: Props): React.JSX.Element {
  // null until read on the client: localStorage does not exist at build time.
  const [record, setRecord] = useState<FlashcardRecord | null | undefined>(undefined);
  const [justRated, setJustRated] = useState(false);
  const status = useRef<HTMLParagraphElement>(null);

  useEffect(() => {
    setRecord(flashcardRecord(card, version));
  }, [card, version]);

  useEffect(() => {
    if (justRated) {
      status.current?.focus();
    }
  }, [justRated]);

  const today = localDay(new Date());
  const groupId = `rappel-${card.toLowerCase()}`;

  function rate(outcome: ReviewOutcome): void {
    setRecord(rateFlashcard(card, version, item, outcome));
    setJustRated(true);
  }

  function reset(): void {
    resetFlashcard(card);
    setRecord(null);
    setJustRated(false);
  }

  return (
    <div className="certpath-recall" role="group" aria-labelledby={groupId}>
      <p id={groupId} className="certpath-recall__prompt">
        <strong>Avant de lire le verso, vous saviez ?</strong>
      </p>
      <div className="certpath-recall__choices">
        {CHOICES.map(({outcome, label, hint}) => (
          <button key={outcome} type="button" className="button button--sm button--outline button--primary" onClick={() => rate(outcome)}>
            {label} <span className="certpath-recall__hint">({hint})</span>
          </button>
        ))}
      </div>
      {record && (
        <p ref={status} tabIndex={-1} role="status" className="certpath-recall__status">
          {record.history.at(-1)?.outcome === 'AGAIN' ? 'À revoir avant la fin de la session, puis ' : 'Prochaine révision : '}
          {record.due_on <= today ? "aujourd'hui" : formatDay(record.due_on)} ({record.interval_days} j).{' '}
          <button type="button" className="button button--sm button--link" onClick={reset}>
            Oublier cette carte
          </button>
        </p>
      )}
      {record === null && justRated === false && (
        <p className="certpath-recall__status">Pas encore notée. Ouvrir la carte n'enregistre rien.</p>
      )}
    </div>
  );
}
