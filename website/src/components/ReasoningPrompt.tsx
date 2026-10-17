import React, {useEffect, useRef, useState} from 'react';

interface Props {
  /** Called with the reasoning written, or '' when the learner cannot explain. */
  onDone: (reasoning: string) => void;
}

/** Below this, a reason is a word, not an explanation. */
const MINIMUM = 15;

/**
 * Remediation P3.2: the reasoning is asked for before the correction appears.
 *
 * The answer is already recorded; what is written here is not scored and not
 * stored — it stays on the page, beside the expected explanation, so the
 * learner compares the two. Only the comparison they then make is recorded.
 */
export default function ReasoningPrompt({onDone}: Props): React.JSX.Element {
  const [text, setText] = useState('');
  const field = useRef<HTMLTextAreaElement>(null);

  useEffect(() => {
    field.current?.focus();
  }, []);

  return (
    <form
      className="certpath-reasoning"
      onSubmit={(event) => {
        event.preventDefault();
        if (text.trim().length >= MINIMUM) {
          onDone(text.trim());
        }
      }}>
      <label htmlFor="reasoning-input">
        <strong>Avant la correction : pourquoi cette réponse ?</strong>
      </label>
      <p className="certpath-note" id="reasoning-hint">
        Quelques mots suffisent ({MINIMUM} caractères au moins). Ce texte n'est ni noté ni
        enregistré : il reste affiché à côté de l'explication attendue.
      </p>
      <textarea
        id="reasoning-input"
        ref={field}
        rows={3}
        value={text}
        aria-describedby="reasoning-hint"
        onChange={(event) => setText(event.target.value)}
      />
      <div className="certpath-actions">
        <button type="submit" className="button button--primary" disabled={text.trim().length < MINIMUM}>
          Voir la correction
        </button>
        <button type="button" className="button button--secondary" onClick={() => onDone('')}>
          Je ne sais pas l'expliquer
        </button>
      </div>
    </form>
  );
}
