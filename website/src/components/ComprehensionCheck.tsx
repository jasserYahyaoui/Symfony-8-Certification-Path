import React, {useEffect, useRef, useState} from 'react';
import Link from '@docusaurus/Link';
import type {ComprehensionPayload, ComprehensionQuestion, ItemIndexEntry} from '@site/src/lib/types';
import {isCorrect, missedComprehension, recordComprehension} from '@site/src/lib/storage';
import usePayload from '@site/src/lib/usePayload';
import QuestionCard from './QuestionCard';
import PracticeFeedback from './PracticeFeedback';

interface Props {
  /** The lot id, e.g. `lot-12`, written by the generator into the page. */
  lot: string;
}

/**
 * The comprehension check closing a lot (ADR-0010).
 *
 * The questions come from comprehension.json, which the build fills from the
 * comprehension banks alone, and are served in the author's order: they follow
 * the lot from its first notion to the synthesis that ties them together.
 * The correction follows each answer — the owner's choice — and reuses Practice
 * Mode's, so its seven sections and their accessibility are the audited ones.
 *
 * Answers are stored since 2026-10-13 (ADR-0010 addendum), in a slice of their
 * own — `comprehension`, never `attempts`. The check is learning, not
 * evidence: it feeds no weakness history, no readiness figure and no mock, and
 * an answer in `attempts` would make Mock 5 select from it. What the record
 * buys is a retake of the questions last missed in this lot.
 */
export default function ComprehensionCheck({lot}: Props): React.JSX.Element {
  const state = usePayload<ComprehensionPayload>('comprehension.json');
  const [index, setIndex] = useState(0);
  const [answered, setAnswered] = useState<string[] | null>(null);
  const [results, setResults] = useState<Record<string, boolean>>({});
  const [finished, setFinished] = useState(false);
  const [round, setRound] = useState(0);
  // null until read on the client: localStorage does not exist at build time.
  const [missedBefore, setMissedBefore] = useState<Set<string> | null>(null);
  const [onlyMissed, setOnlyMissed] = useState(false);
  const summary = useRef<HTMLHeadingElement>(null);

  useEffect(() => {
    setMissedBefore(missedComprehension(lot));
  }, [lot, round]);

  useEffect(() => {
    if (finished) {
      summary.current?.focus();
    }
  }, [finished]);

  if (state.status === 'loading') {
    return <p role="status">Chargement des questions…</p>;
  }

  if (state.status === 'error') {
    return <p role="status">Impossible de charger les questions : {state.message}</p>;
  }

  const payload = state.payload;
  const lotQuestions = payload.questions.filter((q) => q.lot === lot);
  const retake = lotQuestions.filter((q) => missedBefore?.has(q.id));
  const questions = onlyMissed && retake.length > 0 ? retake : lotQuestions;
  const items: Record<string, ItemIndexEntry> = payload.items ?? {};

  if (lotQuestions.length === 0) {
    return <p role="status">Aucune question de compréhension n'existe encore pour ce lot.</p>;
  }

  const question: ComprehensionQuestion | undefined = questions[index];
  const correctCount = Object.values(results).filter(Boolean).length;
  const answeredCount = Object.keys(results).length;

  function handleSubmit(current: ComprehensionQuestion, chosen: string[]): void {
    const correct = isCorrect(current, chosen);
    setResults((r) => ({...r, [current.id]: correct}));
    setAnswered(chosen);
    recordComprehension({
      question_id: current.id,
      question_version: current.version,
      official_item: current.official_item,
      lot,
      correct,
      answered_at: new Date().toISOString(),
    });
  }

  function next(): void {
    if (index + 1 >= questions.length) {
      setFinished(true);
      return;
    }
    setIndex((i) => i + 1);
    setAnswered(null);
  }

  function restart(missedOnly = false): void {
    setOnlyMissed(missedOnly);
    setIndex(0);
    setAnswered(null);
    setResults({});
    setFinished(false);
    setRound((r) => r + 1);
  }

  if (finished) {
    const missed = questions.filter((q) => results[q.id] === false);

    return (
      <section className="certpath-comprehension" aria-labelledby="comprehension-summary">
        <h2 id="comprehension-summary" ref={summary} tabIndex={-1}>
          Bilan : {correctCount} / {questions.length}
        </h2>
        {missed.length === 0 ? (
          <p>Toutes les réponses sont correctes : chaque objectif du lot est compris.</p>
        ) : (
          <>
            <p>
              {missed.length} question{missed.length > 1 ? 's' : ''} à revoir. Chaque
              lien mène au cours de la notion manquée.
            </p>
            <ol>
              {missed.map((q) => (
                <li key={q.id}>
                  Question {questions.indexOf(q) + 1}
                  {items[q.official_item] && (
                    <>
                      {' — '}
                      <Link to={items[q.official_item].course_url}>
                        {items[q.official_item].official_item}
                      </Link>
                    </>
                  )}
                </li>
              ))}
            </ol>
          </>
        )}
        <div className="certpath-actions">
          <button type="button" className="button button--primary" onClick={() => restart(false)}>
            Recommencer le contrôle
          </button>
          {missed.length > 0 && (
            <button type="button" className="button button--secondary" onClick={() => restart(true)}>
              Reprendre les {missed.length} question{missed.length > 1 ? 's' : ''} manquée{missed.length > 1 ? 's' : ''}
            </button>
          )}
        </div>
      </section>
    );
  }

  return (
    <section className="certpath-comprehension" aria-labelledby="comprehension-heading">
      <h2 id="comprehension-heading">Questions</h2>
      {index === 0 && answered === null && !onlyMissed && retake.length > 0 && (
        <>
          <p>
            Lors de vos passages précédents, {retake.length} question{retake.length > 1 ? 's' : ''} de
            ce lot {retake.length > 1 ? 'sont restées' : 'est restée'} manquée{retake.length > 1 ? 's' : ''}.
          </p>
          <div className="certpath-actions">
            <button type="button" className="button button--secondary" onClick={() => restart(true)}>
              Reprendre seulement celles-ci
            </button>
          </div>
        </>
      )}
      {onlyMissed && (
        <p className="certpath-note">
          Reprise : {questions.length} question{questions.length > 1 ? 's' : ''} manquée{questions.length > 1 ? 's' : ''} lors du passage précédent.
        </p>
      )}
      <p className="certpath-note" role="status">
        Score : {correctCount} / {answeredCount}
      </p>

      {question && (
        <>
          <QuestionCard
            key={`${question.id}-${round}`}
            question={question}
            index={index}
            total={questions.length}
            submitLabel="Valider ma réponse"
            disabled={answered !== null}
            onSubmit={(chosen) => handleSubmit(question, chosen)}
          />

          {/* Mounted only once an answer exists: before submission the
              correction is absent from the DOM entirely, not hidden. */}
          {answered && (
            <PracticeFeedback
              question={question}
              chosen={answered}
              item={items[question.official_item]}
              takeaways={question.assesses_outcomes
                .map((id) => payload.outcomes?.[id])
                .filter((text): text is string => Boolean(text))}
              relatedItems={question.related_items
                .map((id) => items[id])
                .filter((entry): entry is ItemIndexEntry => Boolean(entry))}
              isLast={index + 1 >= questions.length}
              onNext={next}
            />
          )}
        </>
      )}
    </section>
  );
}
