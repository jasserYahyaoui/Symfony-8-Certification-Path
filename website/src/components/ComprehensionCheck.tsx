import React, {useEffect, useRef, useState} from 'react';
import Link from '@docusaurus/Link';
import type {ComprehensionPayload, ComprehensionQuestion, ItemIndexEntry} from '@site/src/lib/types';
import {isCorrect} from '@site/src/lib/storage';
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
 * Nothing is stored. The check is learning, not evidence: it feeds no weakness
 * history, no readiness figure and no mock, and recording it in the same
 * storage as Practice Mode would make Mock 5 select from it.
 */
export default function ComprehensionCheck({lot}: Props): React.JSX.Element {
  const state = usePayload<ComprehensionPayload>('comprehension.json');
  const [index, setIndex] = useState(0);
  const [answered, setAnswered] = useState<string[] | null>(null);
  const [results, setResults] = useState<Record<string, boolean>>({});
  const [finished, setFinished] = useState(false);
  const [round, setRound] = useState(0);
  const summary = useRef<HTMLHeadingElement>(null);

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
  const questions = payload.questions.filter((q) => q.lot === lot);
  const items: Record<string, ItemIndexEntry> = payload.items ?? {};

  if (questions.length === 0) {
    return <p role="status">Aucune question de compréhension n'existe encore pour ce lot.</p>;
  }

  const question: ComprehensionQuestion | undefined = questions[index];
  const correctCount = Object.values(results).filter(Boolean).length;
  const answeredCount = Object.keys(results).length;

  function handleSubmit(current: ComprehensionQuestion, chosen: string[]): void {
    setResults((r) => ({...r, [current.id]: isCorrect(current, chosen)}));
    setAnswered(chosen);
  }

  function next(): void {
    if (index + 1 >= questions.length) {
      setFinished(true);
      return;
    }
    setIndex((i) => i + 1);
    setAnswered(null);
  }

  function restart(): void {
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
          <button type="button" className="button button--primary" onClick={restart}>
            Recommencer le contrôle
          </button>
        </div>
      </section>
    );
  }

  return (
    <section className="certpath-comprehension" aria-labelledby="comprehension-heading">
      <h2 id="comprehension-heading">Questions</h2>
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
