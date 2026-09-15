import React from 'react';
import Link from '@docusaurus/Link';
import type {Attempt} from '@site/src/lib/storage';
import type {ItemIndexEntry, Question} from '@site/src/lib/types';
import RichText from './RichText';

/**
 * The end-of-series report (Lot 27).
 *
 * WHAT IT REFUSES TO SAY. There is no official Symfony pass mark in this
 * project's evidence, so none is printed: §19 forbids inventing one, and a
 * threshold a learner reads as official would be exactly that. The percentage
 * is reported as a percentage and labelled INTERNAL_TRAINING_FORMAT.
 *
 * Per-item wording is deliberately cautious — "Correct in this session" says
 * what happened, where "mastered" would claim something one question cannot
 * establish. An item answered right once is not an item learned.
 *
 * PER_QUESTION_TIMING_NOT_IMPLEMENTED: no duration is shown because none is
 * measured. A plausible-looking number would be a fabricated one.
 */

interface Props {
  questions: Question[];
  items: Record<string, ItemIndexEntry>;
  attempts: Attempt[];
  onRestart: () => void;
}

type Bucket = {total: number; correct: number; wrong: number; unanswered: number};

function empty(): Bucket {
  return {total: 0, correct: 0, wrong: 0, unanswered: 0};
}

function pct(part: number, whole: number): string {
  return whole === 0 ? 'n/a' : `${Math.round((part / whole) * 100)} %`;
}

export default function PracticeResults({
  questions,
  items,
  attempts,
  onRestart,
}: Props): React.JSX.Element {
  // The last attempt per question wins — a learner who redid a question inside
  // the series is graded on what they last did.
  const byQuestion = new Map<string, Attempt>();
  for (const attempt of attempts) {
    byQuestion.set(attempt.question_id, attempt);
  }

  const total = questions.length;
  const answered = questions.filter((q) => byQuestion.has(q.id));
  const correct = answered.filter((q) => byQuestion.get(q.id)?.correct).length;
  const wrong = answered.length - correct;
  const unanswered = total - answered.length;

  const byTopic = new Map<string, Bucket>();
  const byItem = new Map<string, Bucket>();
  const missedOutcomes = new Map<string, Set<string>>();

  for (const question of questions) {
    const attempt = byQuestion.get(question.id);
    const state = !attempt ? 'unanswered' : attempt.correct ? 'correct' : 'wrong';

    for (const [map, key] of [
      [byTopic, question.official_topic],
      [byItem, question.official_item],
    ] as const) {
      const bucket = map.get(key) ?? empty();
      bucket.total += 1;
      bucket[state] += 1;
      map.set(key, bucket);
    }

    if (state === 'wrong') {
      const outcomes = items[question.official_item]?.learning_outcomes ?? [];
      for (const outcome of outcomes) {
        const set = missedOutcomes.get(outcome) ?? new Set<string>();
        set.add(question.official_item);
        missedOutcomes.set(outcome, set);
      }
    }
  }

  const toReview = questions.filter((q) => {
    const attempt = byQuestion.get(q.id);
    return !attempt || !attempt.correct;
  });

  return (
    <section aria-labelledby="results-heading" className="certpath-results">
      <h2 id="results-heading">Bilan de la série</h2>

      <p role="status">
        <strong>
          {correct} / {total} — {pct(correct, total)}
        </strong>{' '}
        en <em>Practice Mode</em>.
      </p>

      <ul>
        <li>Questions servies : {total}</li>
        <li>Correctes : {correct}</li>
        <li>Incorrectes : {wrong}</li>
        <li>Non répondues : {unanswered}</li>
      </ul>

      <p className="certpath-note">
        <strong>Ce score n'est pas un résultat officiel Symfony.</strong> Aucun
        seuil de réussite officiel n'est connu de ce projet et aucun n'est
        affiché ici ; ce chiffre est un indicateur d'entraînement interne
        (<code>INTERNAL_TRAINING_FORMAT</code>). La durée n'est pas affichée
        parce qu'elle n'est pas mesurée
        (<code>PER_QUESTION_TIMING_NOT_IMPLEMENTED</code>).
      </p>

      <h3>Par sujet officiel</h3>
      {/* A region that scrolls must be reachable by keyboard, or its off-screen
          columns are unreadable without a mouse (axe scrollable-region-focusable,
          found by the Lot 27 audit of this very state). */}
      <div className="certpath-table-scroll" tabIndex={0} role="region"
        aria-label="Résultats par sujet officiel">
        <table>
          <thead>
            <tr>
              <th scope="col">Sujet</th>
              <th scope="col">Questions</th>
              <th scope="col">Correctes</th>
              <th scope="col">Incorrectes</th>
              <th scope="col">Non répondues</th>
              <th scope="col">Réussite</th>
            </tr>
          </thead>
          <tbody>
            {[...byTopic.entries()]
              .sort((a, b) => a[0].localeCompare(b[0]))
              .map(([topic, b]) => (
                <tr key={topic}>
                  <th scope="row">{topic}</th>
                  <td>{b.total}</td>
                  <td>{b.correct}</td>
                  <td>{b.wrong}</td>
                  <td>{b.unanswered}</td>
                  <td>{pct(b.correct, b.total)}</td>
                </tr>
              ))}
          </tbody>
        </table>
      </div>

      <h3>Par item officiel atomique</h3>
      <div className="certpath-table-scroll" tabIndex={0} role="region"
        aria-label="Résultats par item officiel atomique">
        <table>
          <thead>
            <tr>
              <th scope="col">Item</th>
              <th scope="col">Résultat de la série</th>
              <th scope="col">Cours</th>
            </tr>
          </thead>
          <tbody>
            {[...byItem.entries()]
              .sort((a, b) =>
                (items[a[0]]?.official_item ?? a[0]).localeCompare(
                  items[b[0]]?.official_item ?? b[0],
                ),
              )
              .map(([itemId, b]) => {
                const entry = items[itemId];
                // Cautious by design: one right answer is evidence about one
                // question, not about an item.
                const verdict =
                  b.wrong > 0
                    ? 'Needs review'
                    : b.unanswered === b.total
                      ? 'Insufficient evidence'
                      : 'Correct in this session';

                return (
                  <tr key={itemId}>
                    <th scope="row">{entry?.official_item ?? itemId}</th>
                    <td data-verdict={verdict}>
                      {verdict} ({b.correct}/{b.total})
                    </td>
                    <td>
                      {entry ? (
                        <Link to={entry.course_url}>Ouvrir</Link>
                      ) : (
                        <span className="certpath-note">—</span>
                      )}
                    </td>
                  </tr>
                );
              })}
          </tbody>
        </table>
      </div>

      <h3>Learning outcomes à revoir</h3>
      {missedOutcomes.size === 0 ? (
        <p className="certpath-note">
          Aucune erreur dans cette série : aucun outcome n'est signalé. Cela ne
          vaut pas pour les outcomes que la série n'a pas rencontrés.
        </p>
      ) : (
        <ul>
          {[...missedOutcomes.entries()].map(([outcome, itemIds]) => (
            <li key={outcome}>
              <RichText as="span">{outcome}</RichText>
              <span className="certpath-note">
                {' '}
                — {[...itemIds].map((id) => items[id]?.official_item ?? id).join(', ')}
              </span>
            </li>
          ))}
        </ul>
      )}

      <h3>Revue des erreurs</h3>
      {toReview.length === 0 ? (
        <p className="certpath-note">Rien à revoir dans cette série.</p>
      ) : (
        toReview.map((question) => {
          const attempt = byQuestion.get(question.id);
          const entry = items[question.official_item];
          const correctChoices = question.choices.filter((c) => c.correct);
          const picked = question.choices.filter((c) =>
            (attempt?.chosen ?? []).includes(c.id),
          );

          return (
            <details className="certpath-review" key={question.id}>
              <summary>
                {entry?.official_item ?? question.official_topic} —{' '}
                {attempt ? 'réponse incorrecte' : 'non répondue'}
              </summary>

              <RichText className="certpath-prompt" language={question.code_language}>
                {question.question}
              </RichText>

              <p className="certpath-note">
                Votre réponse :{' '}
                {picked.length === 0
                  ? 'aucune'
                  : picked.map((c) => c.text).join(' · ')}
              </p>
              <p className="certpath-note">
                Bonne réponse : {correctChoices.map((c) => c.text).join(' · ')}
              </p>

              <RichText className="certpath-explanation" language={question.code_language}>
                {question.explanation}
              </RichText>

              {question.choices
                .filter((c) => !c.correct && c.explanation)
                .map((choice) => (
                  <RichText
                    className="certpath-note"
                    key={choice.id}
                    language={question.code_language}>
                    {`${choice.text} — ${choice.explanation}`}
                  </RichText>
                ))}

              {entry?.learning_outcomes?.[0] && (
                <RichText className="certpath-takeaway">
                  {entry.learning_outcomes[0]}
                </RichText>
              )}

              {entry && (
                <p>
                  <Link to={entry.course_url}>Ouvrir le cours</Link>
                </p>
              )}
            </details>
          );
        })
      )}

      <h3>Recommandations</h3>
      {toReview.length === 0 ? (
        <p className="certpath-note">
          Série sans erreur. La suite utile est une nouvelle série sur d'autres
          sujets, puis l'<Link to="/exam">Exam Mode</Link> pour travailler en
          conditions chronométrées.
        </p>
      ) : (
        <ol>
          <li>
            Revoir les cours des items signalés{' '}
            <em>Needs review</em> ci-dessus.
          </li>
          <li>
            Rejouer une série filtrée sur{' '}
            <strong>Rejouer mes points faibles</strong> : elle sert exactement
            les questions manquées.
          </li>
          <li>
            Passer en <Link to="/exam">Exam Mode</Link> une fois ces items
            repassés, pour les travailler en conditions chronométrées.
          </li>
        </ol>
      )}

      <div className="certpath-actions">
        <button type="button" className="button button--primary" onClick={onRestart}>
          Nouvelle série
        </button>
      </div>
    </section>
  );
}
