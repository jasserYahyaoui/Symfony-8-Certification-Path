import React, {useCallback, useEffect, useRef, useState} from 'react';
import Layout from '@theme/Layout';
import type {Question} from '@site/src/lib/types';
import {isCorrect, recordAttempt, recordSession, shuffle} from '@site/src/lib/storage';
import usePayload from '@site/src/lib/usePayload';
import QuestionCard from '@site/src/components/QuestionCard';
import EmptyBank from '@site/src/components/EmptyBank';

const OFFICIAL_QUESTION_COUNT = 75;
const OFFICIAL_DURATION_MINUTES = 90;
const WARNING_SECONDS = 300;

type Phase =
  | {name: 'setup'}
  | {name: 'running'; questions: Question[]; startedAt: number}
  | {name: 'finished'; result: Result};

interface Result {
  total: number;
  correct: number;
  unanswered: number;
  elapsedSeconds: number;
  timedOut: boolean;
  weakTopics: [string, number][];
}

/**
 * Exam Mode, Master Plan §9.2: hidden answers and hints, configurable time
 * limit, answer recording, declared scoring policy, time measurement, and
 * final analysis only after submission.
 */
export default function Exam(): React.JSX.Element {
  const state = usePayload('exam.json');
  const [phase, setPhase] = useState<Phase>({name: 'setup'});
  const [questionCount, setQuestionCount] = useState(OFFICIAL_QUESTION_COUNT);
  const [durationMinutes, setDurationMinutes] = useState(OFFICIAL_DURATION_MINUTES);
  const [index, setIndex] = useState(0);
  const [remaining, setRemaining] = useState(0);

  const answersRef = useRef<Map<string, string[]>>(new Map());
  const pool: Question[] = state.status === 'ready' ? state.payload.questions : [];

  const finish = useCallback(
    (questions: Question[], startedAt: number, timedOut: boolean) => {
      const answers = answersRef.current;
      let correct = 0;
      let unanswered = 0;
      const weak = new Map<string, number>();

      for (const question of questions) {
        const chosen = answers.get(question.id) ?? [];
        if (chosen.length === 0) {
          unanswered += 1;
        }

        const ok = isCorrect(question, chosen);
        if (ok) {
          correct += 1;
        } else {
          weak.set(question.official_topic, (weak.get(question.official_topic) ?? 0) + 1);
        }

        recordAttempt({
          question_id: question.id,
          question_version: question.version,
          official_item: question.official_item,
          correct: ok,
          chosen,
          answered_at: new Date().toISOString(),
          mode: 'exam',
        });
      }

      const elapsedSeconds = Math.round((Date.now() - startedAt) / 1000);

      recordSession({
        mode: 'exam',
        question_count: questions.length,
        correct,
        unanswered,
        elapsed_seconds: elapsedSeconds,
        timed_out: timedOut,
        finished_at: new Date().toISOString(),
      });

      setPhase({
        name: 'finished',
        result: {
          total: questions.length,
          correct,
          unanswered,
          elapsedSeconds,
          timedOut,
          weakTopics: [...weak.entries()].sort((a, b) => b[1] - a[1]),
        },
      });
    },
    [],
  );

  // The timer never destroys work: on expiry it submits whatever the learner
  // has already entered (§13, non-destructive timeout behaviour).
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

  function start(): void {
    const count = Math.min(Math.max(questionCount, 1), pool.length);
    answersRef.current = new Map();
    setIndex(0);
    setRemaining(durationMinutes * 60);
    setPhase({name: 'running', questions: shuffle(pool).slice(0, count), startedAt: Date.now()});
  }

  return (
    <Layout
      title="Exam Mode"
      description="Simulation chronométrée au format de la certification Symfony 8.">
      <main className="container margin-vert--lg">
        <h1>Exam Mode</h1>
        <p>
          Simulation chronométrée. Aucune correction, aucun indice et aucune
          réponse ne sont affichés avant la soumission finale. À l'expiration du
          temps, les réponses déjà saisies sont conservées et soumises : le
          chronomètre ne détruit jamais votre travail.
        </p>

        {state.status === 'loading' && <p role="status">Chargement…</p>}
        {state.status === 'error' && (
          <p role="status">Chargement impossible : {state.message}</p>
        )}

        {state.status === 'ready' && pool.length === 0 && <EmptyBank mode="exam" />}

        {state.status === 'ready' && pool.length > 0 && phase.name === 'setup' && (
          <section aria-labelledby="setup-heading">
            <h2 id="setup-heading">Configuration</h2>
            <div className="certpath-filters">
              <div className="certpath-field">
                <label htmlFor="exam-count">Nombre de questions</label>
                <input
                  type="number"
                  id="exam-count"
                  min={1}
                  max={pool.length}
                  value={questionCount}
                  onChange={(e) => setQuestionCount(Number(e.target.value))}
                />
              </div>
              <div className="certpath-field">
                <label htmlFor="exam-duration">Durée (minutes)</label>
                <input
                  type="number"
                  id="exam-duration"
                  min={1}
                  max={180}
                  value={durationMinutes}
                  onChange={(e) => setDurationMinutes(Number(e.target.value))}
                />
              </div>
            </div>

            <p className="certpath-note">
              Format officiel publié : {OFFICIAL_QUESTION_COUNT} questions,{' '}
              {OFFICIAL_DURATION_MINUTES} minutes, en anglais (
              <code>OFFICIAL_FORMAT</code>). Toute autre répartition utilisée ici
              est une <code>TRAINING_DISTRIBUTION</code> interne et n'est pas
              officielle.
            </p>

            <div className="certpath-actions">
              <button type="button" className="button button--primary button--lg" onClick={start}>
                Démarrer la simulation
              </button>
            </div>
          </section>
        )}

        {phase.name === 'running' && (
          <section aria-labelledby="running-heading">
            <h2 id="running-heading">Simulation en cours</h2>

            <p
              className="certpath-timer"
              data-state={remaining <= WARNING_SECONDS ? 'warning' : 'normal'}
              role="timer"
              /* Announce sparingly: a per-second live region floods a screen reader. */
              aria-live={remaining === WARNING_SECONDS || remaining === 60 ? 'assertive' : 'off'}>
              {formatTime(remaining)}
              {remaining <= WARNING_SECONDS && (
                <span className="certpath-note"> — temps bientôt écoulé</span>
              )}
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
              <>
                <p role="status">
                  Toutes les questions ont été parcourues. Vous pouvez soumettre.
                </p>
                <div className="certpath-actions">
                  <button
                    type="button"
                    className="button button--primary button--lg"
                    onClick={() => finish(phase.questions, phase.startedAt, false)}>
                    Soumettre la simulation
                  </button>
                </div>
              </>
            )}
          </section>
        )}

        {phase.name === 'finished' && (
          <Results result={phase.result} onRestart={() => setPhase({name: 'setup'})} />
        )}
      </main>
    </Layout>
  );
}

function formatTime(seconds: number): string {
  const safe = Math.max(seconds, 0);
  const mm = String(Math.floor(safe / 60)).padStart(2, '0');
  const ss = String(safe % 60).padStart(2, '0');
  return `${mm}:${ss}`;
}

function Results({
  result,
  onRestart,
}: {
  result: Result;
  onRestart: () => void;
}): React.JSX.Element {
  return (
    <section aria-labelledby="results-heading">
      <h2 id="results-heading">Résultat de la simulation</h2>

      {result.timedOut && (
        <p role="status">
          Le temps est écoulé. Vos réponses saisies ont été conservées et
          comptabilisées.
        </p>
      )}

      <table>
        <tbody>
          <tr>
            <th scope="row">Score</th>
            <td>
              {result.correct} / {result.total}
            </td>
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
        Politique de score interne (<code>INTERNAL_TRAINING_FORMAT</code>) : une
        question compte seulement si toutes ses bonnes réponses, et elles
        seules, sont sélectionnées. La politique officielle de notation n'est
        pas publiée.
      </p>

      {result.weakTopics.length > 0 && (
        <>
          <h3>Sujets à revoir</h3>
          <ul>
            {result.weakTopics.map(([topic, count]) => (
              <li key={topic}>
                {topic} — {count} erreur{count > 1 ? 's' : ''}
              </li>
            ))}
          </ul>
        </>
      )}

      <div className="certpath-actions">
        <button type="button" className="button button--primary" onClick={onRestart}>
          Nouvelle simulation
        </button>
      </div>
    </section>
  );
}
