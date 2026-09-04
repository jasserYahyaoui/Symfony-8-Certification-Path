import React, {useCallback, useEffect, useRef, useState} from 'react';
import Layout from '@theme/Layout';
import type {ItemIndexEntry, Question, TrainingMockPayload} from '@site/src/lib/types';
import {isCorrect, recordAttempt, recordSession, shuffle} from '@site/src/lib/storage';
import usePayload from '@site/src/lib/usePayload';
import QuestionCard from '@site/src/components/QuestionCard';
import EmptyBank from '@site/src/components/EmptyBank';
import {
  durationMinutes,
  selectWeaknessSitting,
  WEAKNESS_MINIMUM_ITEMS,
  type Selection,
} from '@site/src/lib/weakness';

const WARNING_SECONDS = 300;

interface Mock5Payload extends TrainingMockPayload {
  minimum_questions: number;
  maximum_questions: number;
  fallback: {name: string; trigger: string; behaviour: string; forbidden: string};
  weakness_evidence: string[];
}

type Phase =
  | {name: 'briefing'}
  | {name: 'fallback'}
  | {name: 'running'; selection: Selection; minutes: number; startedAt: number}
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
  weakItems: WeakItem[];
}

/**
 * Mock 5 — weakness-based (Master Plan §10).
 *
 * The only mock with no fixed selection: the payload is the candidate universe
 * and the sitting is drawn against this browser's own history. Two learners
 * never sit the same paper.
 *
 * When the evidence is too thin the page does not run a sitting and does not
 * pretend to. That is the point of INSUFFICIENT_EVIDENCE_FALLBACK — a mock
 * assembled from invented weaknesses would send the learner to revise the
 * wrong things while believing the tool had measured them.
 */
export default function Mock5(): React.JSX.Element {
  const state = usePayload<Mock5Payload>('mock-5.json');
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
          mode: 'mock-5',
        });
      }

      const elapsedSeconds = Math.round((Date.now() - startedAt) / 1000);

      recordSession({
        mode: 'mock-5',
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
          incorrect,
          elapsedSeconds,
          timedOut,
          byTopic: [...topics.values()].sort(
            (a, b) => a.correct / a.asked - b.correct / b.asked || a.topic.localeCompare(b.topic),
          ),
          weakItems: [...weak.values()].sort(
            (a, b) => a.topic.localeCompare(b.topic) || a.label.localeCompare(b.label),
          ),
        },
      });
    },
    [items],
  );

  useEffect(() => {
    if (phase.name !== 'running') {
      return undefined;
    }

    const ticker = window.setInterval(() => {
      setRemaining((current) => {
        if (current <= 1) {
          window.clearInterval(ticker);
          finish(phase.selection.questions, phase.startedAt, true);
          return 0;
        }
        return current - 1;
      });
    }, 1000);

    return () => window.clearInterval(ticker);
  }, [phase, finish]);

  function start(): void {
    const selection = selectWeaknessSitting(pool);

    if (!selection) {
      setPhase({name: 'fallback'});
      return;
    }

    const minutes = durationMinutes(selection.questions);
    answersRef.current = new Map();
    setIndex(0);
    setRemaining(minutes * 60);
    setPhase({
      name: 'running',
      selection: {...selection, questions: shuffle(selection.questions)},
      minutes,
      startedAt: Date.now(),
    });
  }

  return (
    <Layout
      title="Mock 5 — Weakness-based"
      description="Simulation générée depuis vos faiblesses démontrées, dans ce navigateur.">
      <main className="container margin-vert--lg">
        <h1>Mock 5 — Weakness-based</h1>

        {state.status === 'loading' && <p role="status">Chargement…</p>}
        {state.status === 'error' && <p role="status">Chargement impossible : {state.message}</p>}
        {state.status === 'ready' && pool.length === 0 && <EmptyBank mode="exam" />}

        {state.status === 'ready' && pool.length > 0 && phase.name === 'briefing' && (
          <section aria-labelledby="briefing-heading">
            <h2 id="briefing-heading">Avant de commencer</h2>

            <p>{state.payload.purpose}</p>

            <p>
              Cette simulation n'a pas de sélection fixe : elle est construite à
              partir de vos erreurs enregistrées dans <strong>ce navigateur</strong>.
              Elle n'est donc identique pour personne d'autre, et elle change à
              mesure que vos réponses changent.
            </p>

            <p>
              Entre {state.payload.minimum_questions} et{' '}
              {state.payload.maximum_questions} questions, une par item faible, et
              jamais une question déjà posée. La durée est calculée au tirage.
            </p>

            <p className="certpath-note">
              Format interne (<code>{state.payload.format_label}</code>).{' '}
              {state.payload.not_official}
            </p>

            <div className="certpath-actions">
              <button type="button" className="button button--primary button--lg" onClick={start}>
                Générer la simulation
              </button>
            </div>
          </section>
        )}

        {phase.name === 'fallback' && state.status === 'ready' && (
          <section aria-labelledby="fallback-heading">
            <h2 id="fallback-heading">Pas assez de données — {state.payload.fallback.name}</h2>

            <p role="status">
              Aucune simulation n'est générée. Il faut au moins{' '}
              {WEAKNESS_MINIMUM_ITEMS} items officiels comportant une erreur
              enregistrée, et vos données locales n'en comptent pas encore assez.
            </p>

            <p>
              <strong>
                Ce qui suit n'est pas une simulation fondée sur vos faiblesses.
              </strong>{' '}
              Une sélection fabriquée à partir de faiblesses inventées vous
              enverrait réviser les mauvais sujets en croyant qu'ils ont été
              mesurés. Rien n'est donc proposé à la place.
            </p>

            <p>Pour rassembler des preuves, dans l'ordre :</p>
            <ol>
              <li>Practice Mode, qui enregistre chaque réponse.</li>
              <li>Exam Mode, chronométré.</li>
              <li>Mock 1, 2 ou 3, qui couvrent les 14 sujets officiels.</li>
            </ol>

            <p className="certpath-note">
              Vos données restent dans ce navigateur : aucun compte, aucun envoi.
              Les effacer remet ce compteur à zéro.
            </p>

            <div className="certpath-actions">
              <button
                type="button"
                className="button button--secondary"
                onClick={() => setPhase({name: 'briefing'})}>
                Revenir
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
            </p>

            {index < phase.selection.questions.length ? (
              <>
                <QuestionCard
                  key={phase.selection.questions[index].id}
                  question={phase.selection.questions[index]}
                  index={index}
                  total={phase.selection.questions.length}
                  submitLabel="Enregistrer et continuer"
                  initialSelection={answersRef.current.get(phase.selection.questions[index].id) ?? []}
                  onSubmit={(chosen) => {
                    answersRef.current.set(phase.selection.questions[index].id, chosen);
                    setIndex((i) => i + 1);
                  }}
                />
                <div className="certpath-actions">
                  <button
                    type="button"
                    className="button button--secondary"
                    onClick={() => finish(phase.selection.questions, phase.startedAt, false)}>
                    Soumettre maintenant
                  </button>
                </div>
              </>
            ) : (
              <div className="certpath-actions">
                <button
                  type="button"
                  className="button button--primary button--lg"
                  onClick={() => finish(phase.selection.questions, phase.startedAt, false)}>
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

      <p className="certpath-note">Politique de score interne : {scoringPolicy}.</p>

      <h3>Par sujet officiel</h3>
      <table>
        <caption>Réussite par sujet sur cette session</caption>
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
        <p>
          Toutes les questions de cette session sont réussies. Ces items étaient
          faibles : ils ne le sont plus dans cette session, ce qui n'est pas la
          même chose que la maîtrise — une seule bonne réponse ne la démontre pas.
        </p>
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

      <h3>Action ciblée</h3>
      <p>
        Refaites cette simulation après avoir révisé : la sélection est
        recalculée à chaque fois, donc les items redevenus solides en sortent et
        les autres restent.
      </p>

      <h3>Ce que ce résultat démontre — et ce qu'il ne démontre pas</h3>
      <ul>
        <li>Il démontre : une performance sur {result.total} items précédemment manqués.</li>
        <li>
          Il ne démontre pas : la maîtrise à partir d'une seule bonne réponse, ni
          aucune clause §22 — ces questions sont servies par Practice et Exam Mode.
        </li>
      </ul>

      <div className="certpath-actions">
        <button type="button" className="button button--primary" onClick={onRestart}>
          Nouvelle génération
        </button>
      </div>
    </section>
  );
}
