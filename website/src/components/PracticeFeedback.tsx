import React, {useEffect, useRef} from 'react';
import Link from '@docusaurus/Link';
import type {ItemIndexEntry, Question} from '@site/src/lib/types';
import {isCorrect} from '@site/src/lib/storage';
import RichText from './RichText';

/**
 * What the learner reads after submitting, in the order Lot 27 fixes (§4 of
 * docs/audit/lot-27-practice-mode/practice-ui-contract.md):
 *
 *   1 Result · 2 Correct answer · 3 Why this answer is correct
 *   4 Why the other answers are incorrect · 5 Key takeaway
 *   6 Review this concept · 7 Next question
 *
 * EVERY SENTENCE HERE COMES FROM THE CANONICAL DATA. This component contains no
 * technical fact about Symfony, and must not acquire one: an explanation
 * written in JSX is an explanation no rule validates, no audit reads and no
 * source anchors. Where a canonical field is empty the section is omitted
 * rather than filled with a sentence that says nothing.
 *
 * Nothing in this file renders before submission — the page mounts it only once
 * an answer is recorded, so there is no state in which a correct answer is in
 * the DOM unread by the eye but readable by a screen reader.
 */

interface Props {
  question: Question;
  chosen: string[];
  item?: ItemIndexEntry;
  onNext: () => void;
  isLast: boolean;
}

export default function PracticeFeedback({
  question,
  chosen,
  item,
  onNext,
  isLast,
}: Props): React.JSX.Element {
  const correct = isCorrect(question, chosen);
  const result = correct ? 'correct' : 'incorrect';
  const heading = useRef<HTMLParagraphElement>(null);

  // Move focus to the verdict so a keyboard or screen-reader user lands on the
  // correction instead of being left on a now-disabled radio group.
  useEffect(() => {
    heading.current?.focus();
  }, [question.id]);

  const correctChoices = question.choices.filter((c) => c.correct);
  const wrongChoices = question.choices.filter((c) => !c.correct);
  const takeaway = item?.learning_outcomes?.[0];

  return (
    <div className="certpath-feedback" data-result={result}>
      {/* 1 — Result. Announced, and never carried by colour alone. */}
      <p
        className="certpath-verdict"
        data-result={result}
        ref={heading}
        tabIndex={-1}
        role="status">
        {correct ? '✔ Réponse correcte' : '✘ Réponse incorrecte'}
      </p>

      {/* 2 — Correct answer, and what the learner actually picked. */}
      <section aria-labelledby={`fb-answer-${question.id}`}>
        <h3 id={`fb-answer-${question.id}`} className="certpath-feedback-heading">
          {correctChoices.length > 1 ? 'Bonnes réponses' : 'Bonne réponse'}
        </h3>
        <ul className="certpath-answer-list">
          {correctChoices.map((choice) => (
            <li key={choice.id} data-state={chosen.includes(choice.id) ? 'found' : 'missed'}>
              <RichText as="span" language={question.code_language}>
                {choice.text}
              </RichText>
              <span className="certpath-note">
                {chosen.includes(choice.id)
                  ? ' — sélectionnée'
                  : ' — non sélectionnée'}
              </span>
            </li>
          ))}
        </ul>
      </section>

      {/* 3 — Why it is correct: the question's canonical explanation. */}
      <section aria-labelledby={`fb-why-${question.id}`}>
        <h3 id={`fb-why-${question.id}`} className="certpath-feedback-heading">
          Pourquoi cette réponse est correcte
        </h3>
        <RichText className="certpath-explanation" language={question.code_language}>
          {question.explanation}
        </RichText>
      </section>

      {/* 4 — Why each other choice is wrong, from its own canonical text. */}
      {wrongChoices.some((c) => c.explanation) && (
        <section aria-labelledby={`fb-others-${question.id}`}>
          <h3 id={`fb-others-${question.id}`} className="certpath-feedback-heading">
            Pourquoi les autres réponses sont incorrectes
          </h3>
          {wrongChoices
            .filter((c) => c.explanation)
            .map((choice) => (
              <div
                className="certpath-distractor"
                key={choice.id}
                data-chosen={chosen.includes(choice.id) ? 'yes' : 'no'}>
                <p className="certpath-distractor-choice">
                  <RichText as="span" language={question.code_language}>
                    {choice.text}
                  </RichText>
                  {chosen.includes(choice.id) && (
                    <strong className="certpath-your-answer">
                      {' '}
                      — votre réponse
                    </strong>
                  )}
                </p>
                <RichText className="certpath-note" language={question.code_language}>
                  {choice.explanation as string}
                </RichText>
              </div>
            ))}
        </section>
      )}

      {/* 5 — Key takeaway: the item's own learning outcome, never a new fact. */}
      {takeaway && (
        <section aria-labelledby={`fb-key-${question.id}`}>
          <h3 id={`fb-key-${question.id}`} className="certpath-feedback-heading">
            À retenir
          </h3>
          <RichText className="certpath-takeaway">{takeaway}</RichText>
        </section>
      )}

      {/* 6 — Where to revise. */}
      <section aria-labelledby={`fb-review-${question.id}`}>
        <h3 id={`fb-review-${question.id}`} className="certpath-feedback-heading">
          Revoir cette notion
        </h3>
        <p className="certpath-note">
          {question.official_topic}
          {item ? ` · ${item.official_item}` : ''}
        </p>
        {item && (
          <p>
            <Link to={item.course_url}>Ouvrir le cours</Link>
          </p>
        )}
        {question.official_sources.length > 0 && (
          <p className="certpath-note">
            Source officielle :{' '}
            {question.official_sources.map((source, i) => (
              <React.Fragment key={source.url}>
                {i > 0 && ', '}
                <a href={source.url} target="_blank" rel="noopener noreferrer">
                  {source.url}
                </a>
              </React.Fragment>
            ))}
          </p>
        )}
      </section>

      {/* 7 — Next. */}
      <div className="certpath-actions">
        <button type="button" className="button button--primary" onClick={onNext}>
          {isLast ? 'Voir mon bilan' : 'Question suivante'}
        </button>
      </div>
    </div>
  );
}
