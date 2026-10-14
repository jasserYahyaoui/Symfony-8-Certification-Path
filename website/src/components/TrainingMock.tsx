import React, {useState} from 'react';
import Layout from '@theme/Layout';
import type {ItemIndexEntry, Question, TrainingMockPayload} from '@site/src/lib/types';
import {shuffle} from '@site/src/lib/storage';
import usePayload from '@site/src/lib/usePayload';
import ExamSitting from '@site/src/components/ExamSitting';
import EmptyBank from '@site/src/components/EmptyBank';

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
  const [sitting, setSitting] = useState<{questions: Question[]; key: number} | null>(null);
  const pool: Question[] = state.status === 'ready' ? state.payload.questions : [];
  const items: Record<string, ItemIndexEntry> = state.status === 'ready' ? state.payload.items : {};

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
    setSitting({questions: draw(payload), key: Date.now()});
  }

  return (
    <Layout title={heading} description="Simulation d'entraînement interne, au format décidé par ce projet.">
      <main className="container margin-vert--lg">
        <h1>{heading}</h1>

        {state.status === 'loading' && <p role="status">Chargement…</p>}
        {state.status === 'error' && <p role="status">Chargement impossible : {state.message}</p>}
        {state.status === 'ready' && pool.length === 0 && <EmptyBank mode="exam" />}

        {state.status === 'ready' && pool.length > 0 && sitting === null && (
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

        {sitting !== null && state.status === 'ready' && (
          <ExamSitting
            key={sitting.key}
            questions={sitting.questions}
            durationMinutes={state.payload.duration_minutes}
            items={items}
            mode={mode}
            scoringPolicy={state.payload.scoring_policy}
            onRestart={() => setSitting(null)}
          />
        )}
      </main>
    </Layout>
  );
}

