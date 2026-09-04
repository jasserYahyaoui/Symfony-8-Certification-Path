import React, {useCallback, useEffect, useRef, useState} from 'react';
import Layout from '@theme/Layout';
import type {ItemIndexEntry, MockPayload, Question} from '@site/src/lib/types';
import {isCorrect, recordAttempt, recordSession, shuffle} from '@site/src/lib/storage';
import usePayload from '@site/src/lib/usePayload';
import QuestionCard from '@site/src/components/QuestionCard';
import EmptyBank from '@site/src/components/EmptyBank';

const WARNING_SECONDS = 300;

type Phase =
  | {name: 'briefing'}
  | {name: 'running'; questions: Question[]; startedAt: number}
  | {name: 'finished'; result: Result};

interface TopicResult {
  topic: string;
  asked: number;
  correct: number;
}

interface MissedItem {
  item: string;
  label: string;
  topic: string;
  outcomes: string[];
}

interface Result {
  total: number;
  correct: number;
  unanswered: number;
  elapsedSeconds: number;
  timedOut: boolean;
  byTopic: TopicResult[];
  missed: MissedItem[];
}

/**
 * Mock 4 — the official-format simulation (Master Plan §10).
 *
 * Unlike Exam Mode, nothing here is configurable. 75 questions and 90 minutes
 * are facts about the certification, not preferences, and they arrive with the
 * payload rather than being retyped here so the page cannot drift from the
 * blueprint. The bank is the holdout pool, reserved for exactly this and
 * served by no learning mode (ADR-0005, Option A).
 */
export default function Mock4(): React.JSX.Element {
  const state = usePayload<MockPayload>('mock-4.json');
  const [phase, setPhase] = useState<Phase>({name: 'briefing'});
  const [index, setIndex] = useState(0);
  const [remaining, setRemaining] = useState(0);

  const answersRef = useRef<Map<string, string[]>>(new Map());
  const pool: Question[] = state.status === 'ready' ? state.payload.questions : [];
  const items: Record<string, ItemIndexEntry> =
    state.status === 'ready' ? state.payload.items : {};

  const finish = useCallback(
    (questions: Question[], startedAt: number, timedOut: boolean) => {
      const answers = answersRef.current;
      let correct = 0;
      let unanswered = 0;

      const topics = new Map<string, TopicResult>();
      const missed = new Map<string, MissedItem>();

      for (const question of questions) {
        const chosen = answers.get(question.id) ?? [];
        if (chosen.length === 0) {
          unanswered += 1;
        }

        const ok = isCorrect(question, chosen);
        if (ok) {
          correct += 1;
        }

        const topic = topics.get(question.official_topic) ?? {
          topic: question.official_topic,
          asked: 0,
          correct: 0,
        };
        topic.asked += 1;
        topic.correct += ok ? 1 : 0;
        topics.set(question.official_topic, topic);

        if (!ok) {
          const entry = items[question.official_item];
          missed.set(question.official_item, {
            item: question.official_item,
            label: entry?.official_item ?? question.official_item,
            topic: question.official_topic,
            outcomes: entry?.learning_outcomes ?? [],
          });
        }

        recordAttempt({
          question_id: question.id,
          question_version: question.version,
          official_item: question.official_item,
          correct: ok,
          chosen,
          answered_at: new Date().toISOString(),
          mode: 'mock',
        });
      }

      const elapsedSeconds = Math.round((Date.now() - startedAt) / 1000);

      recordSession({
        mode: 'mock',
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
          byTopic: [...topics.values()].sort(
            (a, b) => a.correct / a.asked - b.correct / b.asked || a.topic.localeCompare(b.topic),
          ),
          missed: [...missed.values()].sort(
            (a, b) => a.topic.localeCompare(b.topic) || a.label.localeCompare(b.label),
          ),
        },
      });
    },
    [items],
  );

  // On expiry the sitting is submitted with whatever has been entered; the
  // timer never destroys work (§13).
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

  function start(minutes: number): void {
    answersRef.current = new Map();
    setIndex(0);
    setRemaining(minutes * 60);
    setPhase({name: 'running', questions: shuffle(pool), startedAt: Date.now()});
  }

  return (
    <Layout
      title="Mock 4"
      description="Simulation finale au format officiel : 75 questions, 90 minutes, en anglais.">
      <main className="container margin-vert--lg">
        <h1>Mock 4 — simulation finale</h1>

        {state.status === 'loading' && <p role="status">Chargement…</p>}
        {state.status === 'error' && (
          <p role="status">Chargement impossible : {state.message}</p>
        )}

        {state.status === 'ready' && pool.length === 0 && <EmptyBank mode="exam" />}

        {state.status === 'ready' && pool.length > 0 && phase.name === 'briefing' && (
          <section aria-labelledby="briefing-heading">
            <h2 id="briefing-heading">Avant de commencer</h2>

            <p>
              {state.payload.question_count} questions, {state.payload.duration_minutes}{' '}
              minutes, intégralement en anglais, sur Symfony {state.payload.symfony}.
              Rien n'est configurable : c'est le format de l'examen, pas une
              préférence.
            </p>

            <p>
              Les questions proviennent du <strong>holdout</strong> — une banque
              réservée à cette simulation et servie par aucun mode
              d'apprentissage. Elles ne sont donc pas déjà vues,{' '}
              <em>sauf</em> si vous avez lu le dépôt public : les réponses y sont
              lisibles, et cette simulation ne prétend pas le contraire.
            </p>

            <p>
              Aucune correction, aucun indice et aucune réponse ne sont affichés
              avant la soumission. À l'expiration du temps, les réponses déjà
              saisies sont conservées et soumises.
            </p>

            <p className="certpath-note">
              La répartition par sujet est une <code>TRAINING_DISTRIBUTION</code>{' '}
              interne, dérivée du nombre d'items officiels par sujet. Aucune
              pondération officielle n'est publiée et aucune n'est déduite ici.
            </p>

            <div className="certpath-actions">
              <button
                type="button"
                className="button button--primary button--lg"
                onClick={() => start(state.payload.duration_minutes)}>
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
              /* Announced at two thresholds only: a per-second live region
                 floods a screen reader for 90 minutes. */
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
          <Results result={phase.result} onRestart={() => setPhase({name: 'briefing'})} />
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
        <caption>Résultat global</caption>
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
        seules, sont sélectionnées. La politique officielle de notation n'est pas
        publiée, donc ce score n'est pas une prédiction de résultat.
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

      <h3>Ce qu'il reste à savoir faire</h3>
      {result.missed.length === 0 ? (
        <p>Aucune question manquée : aucun objectif à revoir.</p>
      ) : (
        <>
          <p>
            Chaque item manqué est listé avec les objectifs d'apprentissage qui
            lui sont attachés — c'est ce qu'il faut savoir faire, plutôt que le
            seul nom du sujet.
          </p>
          <ul>
            {result.missed.map((entry) => (
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
