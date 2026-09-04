import React, {useCallback, useEffect, useRef, useState} from 'react';
import Layout from '@theme/Layout';
import type {ItemIndexEntry, Question, TrainingMockPayload} from '@site/src/lib/types';
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
 * A training mock — Mocks 1, 2 and 3 of Master Plan §10.
 *
 * Everything numeric arrives with the payload rather than being written here,
 * because §10 fixes no count and no duration for these mocks: the values are
 * this project's, derived from the bank in the blueprint, and labelled
 * INTERNAL_TRAINING_FORMAT. A page holding its own copy could drift from that.
 *
 * The payload carries the *eligible pool*; the sitting is drawn from it using
 * the recorded topic spread, so two consecutive sittings are not identical.
 */
export default function TrainingMock({
  file,
  heading,
  mode,
}: {
  file: string;
  heading: string;
  /** Which mock a recorded attempt belongs to; history is useless if two mocks share a label. */
  mode: 'mock-1' | 'mock-2' | 'mock-3';
}): React.JSX.Element {
  const state = usePayload<TrainingMockPayload>(file);
  const [phase, setPhase] = useState<Phase>({name: 'briefing'});
  const [index, setIndex] = useState(0);
  const [remaining, setRemaining] = useState(0);

  const answersRef = useRef<Map<string, string[]>>(new Map());
  const pool: Question[] = state.status === 'ready' ? state.payload.questions : [];
  const items: Record<string, ItemIndexEntry> = state.status === 'ready' ? state.payload.items : {};

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

  /**
   * Draws the sitting: the blueprint's quota per official topic, filled at
   * random from the eligible pool, then shuffled so the topics interleave.
   * One question per atomic item follows from the bank — every VALIDATION
   * question sits on its own item.
   */
  function draw(payload: TrainingMockPayload): Question[] {
    const byTopic = new Map<string, Question[]>();
    for (const question of payload.questions) {
      const bucket = byTopic.get(question.official_topic) ?? [];
      bucket.push(question);
      byTopic.set(question.official_topic, bucket);
    }

    const picked: Question[] = [];
    const seenItems = new Set<string>();

    for (const [topic, wanted] of Object.entries(payload.topic_spread)) {
      for (const question of shuffle(byTopic.get(topic) ?? [])) {
        if (picked.length >= payload.question_count) {
          break;
        }
        if (seenItems.has(question.official_item)) {
          continue;
        }
        seenItems.add(question.official_item);
        picked.push(question);
        if (picked.filter((q) => q.official_topic === topic).length >= wanted) {
          break;
        }
      }
    }

    return shuffle(picked);
  }

  function start(payload: TrainingMockPayload): void {
    answersRef.current = new Map();
    setIndex(0);
    setRemaining(payload.duration_minutes * 60);
    setPhase({name: 'running', questions: draw(payload), startedAt: Date.now()});
  }

  return (
    <Layout title={heading} description="Simulation d'entraînement interne, au format décidé par ce projet.">
      <main className="container margin-vert--lg">
        <h1>{heading}</h1>

        {state.status === 'loading' && <p role="status">Chargement…</p>}
        {state.status === 'error' && <p role="status">Chargement impossible : {state.message}</p>}
        {state.status === 'ready' && pool.length === 0 && <EmptyBank mode="exam" />}

        {state.status === 'ready' && pool.length > 0 && phase.name === 'briefing' && (
          <section aria-labelledby="briefing-heading">
            <h2 id="briefing-heading">Avant de commencer</h2>

            <p>{state.payload.purpose}</p>

            <p>
              {state.payload.question_count} questions tirées d'un vivier de{' '}
              {pool.length}, {state.payload.duration_minutes} minutes, en anglais.
              Deux sessions successives ne sont donc pas identiques.
            </p>

            <p className="certpath-note">
              Format interne (<code>{state.payload.format_label}</code>) et
              répartition interne (<code>{state.payload.distribution_label}</code>).{' '}
              {state.payload.not_official}
            </p>

            <p>
              Ces questions proviennent de la banque servie par l'Exam Mode :
              elles ne sont <strong>pas</strong> inédites. Seul Mock 4 l'est. Un
              score obtenu ici ne démontre donc aucune clause de readiness §22.
            </p>

            <p>
              Aucune correction, aucun indice et aucune réponse avant la
              soumission. À l'expiration du temps, les réponses déjà saisies sont
              conservées et soumises.
            </p>

            <div className="certpath-actions">
              <button
                type="button"
                className="button button--primary button--lg"
                onClick={() => start(state.payload)}>
                Démarrer
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
                  Soumettre la simulation
                </button>
              </div>
            )}
          </section>
        )}

        {phase.name === 'finished' && state.status === 'ready' && (
          <Results
            result={phase.result}
            scoringPolicy={state.payload.scoring_policy}
            onRestart={() => setPhase({name: 'briefing'})}
          />
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

/** §10: everything a mock must report once it has been submitted. */
function Results({
  result,
  scoringPolicy,
  onRestart,
}: {
  result: Result;
  scoringPolicy: string;
  onRestart: () => void;
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
          Nouvelle session
        </button>
      </div>
    </section>
  );
}
