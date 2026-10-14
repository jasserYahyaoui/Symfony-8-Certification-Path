import React, {useCallback, useEffect, useRef, useState} from 'react';
import type {ItemIndexEntry, Question} from '@site/src/lib/types';
import {isCorrect, recordAttempt, recordSession, type ExamSession} from '@site/src/lib/storage';
import QuestionCard from '@site/src/components/QuestionCard';

const WARNING_SECONDS = 300;

type Phase =
  | {name: 'running'; questions: Question[]; startedAt: number}
  | {name: 'finished'; result: Result};

interface TopicResult {
  topic: string;
  asked: number;
  correct: number;
}

interface WeakItem {
  item: string;
  label: string;
  topic: string;
  outcomes: string[];
}

interface Result {
  total: number;
  correct: number;
  unanswered: number;
  incorrect: number;
  elapsedSeconds: number;
  timedOut: boolean;
  byTopic: TopicResult[];
  weakTopics: TopicResult[];
  weakItems: WeakItem[];
}

/**
 * One timed sitting and its report: the part Mocks 1, 2, 3 and the numbered
 * mock exams (/examens) share. The caller chooses the questions; this runs the
 * clock, records every answer under `mode`, and reports per §10. No correction
 * is shown before submission; at the end of the time the answers already given
 * are submitted.
 */
export default function ExamSitting({
  questions,
  durationMinutes,
  items,
  mode,
  scoringPolicy,
  onRestart,
  restartLabel = 'Nouvelle session',
  submitLabel = 'Soumettre la simulation',
}: {
  questions: Question[];
  durationMinutes: number;
  items: Record<string, ItemIndexEntry>;
  /** Which sitting a recorded attempt belongs to. */
  mode: ExamSession['mode'];
  scoringPolicy: string;
  onRestart: () => void;
  restartLabel?: string;
  submitLabel?: string;
}): React.JSX.Element {
  const [phase, setPhase] = useState<Phase>(() => ({name: 'running', questions, startedAt: Date.now()}));
  const [index, setIndex] = useState(0);
  const [remaining, setRemaining] = useState(durationMinutes * 60);
  const answersRef = useRef<Map<string, string[]>>(new Map());

  const finish = useCallback(
    (questions: Question[], startedAt: number, timedOut: boolean) => {
      const answers = answersRef.current;
      let correct = 0;
      let unanswered = 0;
      let incorrect = 0;

      const topics = new Map<string, TopicResult>();
      const weak = new Map<string, WeakItem>();

      for (const question of questions) {
        const chosen = answers.get(question.id) ?? [];
        if (chosen.length === 0) {
          unanswered += 1;
        }

        const ok = isCorrect(question, chosen);
        if (ok) {
          correct += 1;
        } else {
          incorrect += 1;
          const entry = items[question.official_item];
          weak.set(question.official_item, {
            item: question.official_item,
            label: entry?.official_item ?? question.official_item,
            topic: question.official_topic,
            outcomes: entry?.learning_outcomes ?? [],
          });
        }

        const topic = topics.get(question.official_topic) ?? {
          topic: question.official_topic,
          asked: 0,
          correct: 0,
        };
        topic.asked += 1;
        topic.correct += ok ? 1 : 0;
        topics.set(question.official_topic, topic);

        recordAttempt({
          question_id: question.id,
          question_version: question.version,
          official_item: question.official_item,
          correct: ok,
          chosen,
          answered_at: new Date().toISOString(),
          mode,
        });
      }

      const elapsedSeconds = Math.round((Date.now() - startedAt) / 1000);

      recordSession({
        mode,
        question_count: questions.length,
        correct,
        unanswered,
        elapsed_seconds: elapsedSeconds,
        timed_out: timedOut,
        finished_at: new Date().toISOString(),
      });

      const byTopic = [...topics.values()].sort(
        (a, b) => a.correct / a.asked - b.correct / b.asked || a.topic.localeCompare(b.topic),
      );

      setPhase({
        name: 'finished',
        result: {
          total: questions.length,
          correct,
          unanswered,
          incorrect,
          elapsedSeconds,
          timedOut,
          byTopic,
          weakTopics: byTopic.filter((t) => t.correct < t.asked),
          weakItems: [...weak.values()].sort(
            (a, b) => a.topic.localeCompare(b.topic) || a.label.localeCompare(b.label),
          ),
        },
      });
    },
    [items, mode],
  );

  useEffect(() => {
    if (phase.name !== 'running') {
      return undefined;
    }

    const ticker = window.setInterval(() => {
      setRemaining((current) => {
        if (current <= 1) {
          window.clearInterval(ticker);
          finish(phase.questions, phase.startedAt, true);
          return 0;
        }
        return current - 1;
      });
    }, 1000);

    return () => window.clearInterval(ticker);
  }, [phase, finish]);

  return (
    <>
      {phase.name === 'running' && (
          <section aria-labelledby="running-heading">
            <h2 id="running-heading">Simulation en cours</h2>

            <p
              className="certpath-timer"
              data-state={remaining <= WARNING_SECONDS ? 'warning' : 'normal'}
              role="timer"
              aria-live={remaining === WARNING_SECONDS || remaining === 60 ? 'assertive' : 'off'}>
              {formatTime(remaining)}
              {remaining <= WARNING_SECONDS && <span className="certpath-note"> — temps bientôt écoulé</span>}
            </p>

            {index < phase.questions.length ? (
              <>
                <QuestionCard
                  key={phase.questions[index].id}
                  question={phase.questions[index]}
                  index={index}
                  total={phase.questions.length}
                  submitLabel="Enregistrer et continuer"
                  initialSelection={answersRef.current.get(phase.questions[index].id) ?? []}
                  onSubmit={(chosen) => {
                    answersRef.current.set(phase.questions[index].id, chosen);
                    setIndex((i) => i + 1);
                  }}
                />
                <div className="certpath-actions">
                  <button
                    type="button"
                    className="button button--secondary"
                    onClick={() => finish(phase.questions, phase.startedAt, false)}>
                    Soumettre maintenant
                  </button>
                </div>
              </>
            ) : (
              <div className="certpath-actions">
                <button
                  type="button"
                  className="button button--primary button--lg"
                  onClick={() => finish(phase.questions, phase.startedAt, false)}>
                  {submitLabel}
                </button>
              </div>
            )}
          </section>
        )}


      {phase.name === 'finished' && (
        <Results result={phase.result} scoringPolicy={scoringPolicy} onRestart={onRestart} restartLabel={restartLabel} />
      )}
    </>
  );
}

function formatTime(seconds: number): string {
  const safe = Math.max(seconds, 0);
  const mm = String(Math.floor(safe / 60)).padStart(2, '0');
  const ss = String(safe % 60).padStart(2, '0');
  return `${mm}:${ss}`;
}

/** §10: everything a mock must report once it has been submitted. */
function Results({
  result,
  scoringPolicy,
  onRestart,
  restartLabel,
}: {
  result: Result;
  scoringPolicy: string;
  onRestart: () => void;
  restartLabel: string;
}): React.JSX.Element {
  return (
    <section aria-labelledby="results-heading">
      <h2 id="results-heading">Résultat</h2>

      {result.timedOut && (
        <p role="status">Le temps est écoulé. Vos réponses saisies ont été conservées et comptabilisées.</p>
      )}

      <table>
        <caption>Résultat global</caption>
        <tbody>
          <tr>
            <th scope="row">Score</th>
            <td>
              {result.correct} / {result.total}
            </td>
          </tr>
          <tr>
            <th scope="row">Incorrectes</th>
            <td>{result.incorrect}</td>
          </tr>
          <tr>
            <th scope="row">Sans réponse</th>
            <td>{result.unanswered}</td>
          </tr>
          <tr>
            <th scope="row">Temps utilisé</th>
            <td>
              {Math.floor(result.elapsedSeconds / 60)} min {result.elapsedSeconds % 60} s
            </td>
          </tr>
        </tbody>
      </table>

      <p className="certpath-note">
        Politique de score interne : {scoringPolicy}. La politique officielle de
        notation n'est pas publiée, donc ce score n'est pas une prédiction de
        résultat.
      </p>

      <h3>Par sujet officiel</h3>
      <table>
        <caption>Réussite par sujet, du plus faible au plus fort</caption>
        <thead>
          <tr>
            <th scope="col">Sujet</th>
            <th scope="col">Réussies</th>
            <th scope="col">Posées</th>
          </tr>
        </thead>
        <tbody>
          {result.byTopic.map((row) => (
            <tr key={row.topic}>
              <th scope="row">{row.topic}</th>
              <td>{row.correct}</td>
              <td>{row.asked}</td>
            </tr>
          ))}
        </tbody>
      </table>

      <h3>Objectifs d'apprentissage à revoir</h3>
      {result.weakItems.length === 0 ? (
        <p>Aucune question manquée : aucun objectif à revoir dans cette session.</p>
      ) : (
        <ul>
          {result.weakItems.map((entry) => (
            <li key={entry.item}>
              <strong>{entry.label}</strong> <em>({entry.topic})</em>
              {entry.outcomes.length > 0 && (
                <ul>
                  {entry.outcomes.map((outcome) => (
                    <li key={outcome}>{outcome}</li>
                  ))}
                </ul>
              )}
            </li>
          ))}
        </ul>
      )}

      <h3>Actions ciblées</h3>
      {result.weakItems.length === 0 ? (
        <p>
          Rien à cibler à partir de cette session. Une session sans erreur ne
          démontre pas la maîtrise : refaites-en une autre, le tirage sera
          différent.
        </p>
      ) : (
        <ol>
          {result.weakTopics.slice(0, 3).map((row) => (
            <li key={row.topic}>
              Reprendre <strong>{row.topic}</strong> en Practice Mode ({row.asked - row.correct} erreur
              {row.asked - row.correct > 1 ? 's' : ''} sur {row.asked}).
            </li>
          ))}
          <li>
            Relire les items listés ci-dessus, puis refaire cette simulation : le
            tirage change à chaque session.
          </li>
        </ol>
      )}

      <h3>Ce que ce résultat démontre — et ce qu'il ne démontre pas</h3>
      <ul>
        <li>
          Il démontre : une performance chronométrée en anglais sur{' '}
          {result.total} questions, à cette date.
        </li>
        <li>
          Il ne démontre pas : la maîtrise d'un item à partir d'une seule bonne
          réponse, ni aucune clause §22 — ces questions sont servies par l'Exam
          Mode et ne sont donc pas inédites.
        </li>
      </ul>

      <div className="certpath-actions">
        <button type="button" className="button button--primary" onClick={onRestart}>
          {restartLabel}
        </button>
      </div>
    </section>
  );
}
