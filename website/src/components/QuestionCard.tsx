import React, {useMemo, useState} from 'react';
import type {Question} from '@site/src/lib/types';
import {shuffle} from '@site/src/lib/storage';

interface Props {
  question: Question;
  index: number;
  total: number;
  submitLabel: string;
  initialSelection?: string[];
  disabled?: boolean;
  onSubmit: (chosen: string[]) => void;
}

/**
 * One question, rendered as a real fieldset with real labels so it is fully
 * operable by keyboard and announced correctly by a screen reader (§13).
 */
export default function QuestionCard({
  question,
  index,
  total,
  submitLabel,
  initialSelection = [],
  disabled = false,
  onSubmit,
}: Props): React.JSX.Element {
  const [chosen, setChosen] = useState<string[]>(initialSelection);
  const multiple = question.answer_mode === 'multiple';

  // Shuffle once per question, not on every keystroke: re-ordering choices
  // while the learner is reading them would be disorienting.
  const choices = useMemo(
    () => (question.shuffle_choices ? shuffle(question.choices) : question.choices),
    [question],
  );

  function toggle(choiceId: string): void {
    setChosen((current) => {
      if (!multiple) {
        return [choiceId];
      }
      return current.includes(choiceId)
        ? current.filter((id) => id !== choiceId)
        : [...current, choiceId];
    });
  }

  function handleSubmit(event: React.FormEvent): void {
    event.preventDefault();
    if (chosen.length > 0) {
      onSubmit(chosen);
    }
  }

  return (
    <form onSubmit={handleSubmit}>
      <fieldset className="certpath-question" disabled={disabled}>
        <legend>
          Question {index + 1} sur {total}
        </legend>

        <p className="certpath-prompt">{question.question}</p>

        {question.negative_wording && (
          <p className="certpath-note">
            <strong>Attention :</strong> formulation négative.
          </p>
        )}

        <p className="certpath-note">
          {multiple
            ? `Réponses multiples — sélectionnez exactement ${question.required_answer_count} réponses.`
            : 'Réponse unique.'}
        </p>

        {choices.map((choice) => (
          <div className="certpath-choice" key={choice.id}>
            <input
              type={multiple ? 'checkbox' : 'radio'}
              name={`answer-${question.id}`}
              id={`choice-${choice.id}`}
              value={choice.id}
              checked={chosen.includes(choice.id)}
              onChange={() => toggle(choice.id)}
            />
            <label htmlFor={`choice-${choice.id}`}>{choice.text}</label>
          </div>
        ))}

        <div className="certpath-actions">
          <button
            type="submit"
            className="button button--primary"
            disabled={chosen.length === 0}>
            {submitLabel}
          </button>
        </div>
      </fieldset>
    </form>
  );
}
