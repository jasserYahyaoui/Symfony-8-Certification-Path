import React from 'react';
import Layout from '@theme/Layout';
import Link from '@docusaurus/Link';
import type {SimulationEntry, SimulationsPayload} from '@site/src/lib/types';
import usePayload from '@site/src/lib/usePayload';

/**
 * The simulations hub: what each mock is for, and when to sit it.
 *
 * WHY IT EXISTS. The five mocks were published with no page saying what
 * distinguishes them. A learner arriving on the navbar dropdown had five names
 * and no reason to pick one — and the one that must be sat exactly once looked
 * exactly like the four that can be replayed.
 *
 * WHERE ITS SENTENCES COME FROM. All of them from `simulations.json`, built
 * from the two blueprints. This component states no count, no duration and no
 * role of its own: a page holding its own copy of "41 minutes" is a page that
 * will still say it after the bank makes it 44.
 *
 * WHAT IT DOES NOT SHOW. Any question, choice or answer. The payload carries
 * none (PayloadBuilder::assertNoQuestionLeak), so the page explaining that Mock
 * 4's bank is unseen cannot be the place it stops being.
 */
export default function Simulations(): React.JSX.Element {
  const state = usePayload<SimulationsPayload>('simulations.json');
  const mocks: SimulationEntry[] = state.status === 'ready' ? state.payload.mocks : [];

  return (
    <Layout
      title="Simulations"
      description="À quoi sert chaque mock, et quand le passer.">
      <header className="hero hero--primary">
        <div className="container">
          <h1 className="hero__title">Simulations</h1>
          <p className="hero__subtitle">
            Cinq mocks, cinq rôles différents. Voici lequel passer, et quand.
          </p>
        </div>
      </header>

      <main className="container margin-vert--xl">
        {state.status === 'loading' && <p role="status">Chargement…</p>}
        {state.status === 'error' && (
          <p role="status">Chargement impossible : {state.message}</p>
        )}

        {state.status === 'ready' && (
          <>
            <h2>Dans quel ordre</h2>
            <ol>
              {mocks.map((mock) => (
                <li key={mock.id}>
                  <Link to={mock.route}>{mock.name}</Link>
                  {mock.repeatable ? '' : ' — une seule fois, à la fin'}
                </li>
              ))}
            </ol>
            <p className="certpath-note">
              Cet ordre est une recommandation de ce projet, pas une règle
              officielle : rien n'empêche de passer un mock plus tôt, sauf le
              Mock 4, dont la banque ne peut être découverte qu'une fois.
            </p>

            <h2>En un coup d'œil</h2>
            {/* A region that scrolls must be reachable by keyboard, or its
                off-screen columns are unreadable without a mouse (§13). */}
            <div
              className="certpath-table-scroll"
              tabIndex={0}
              role="region"
              aria-label="Comparaison des cinq simulations">
              <table>
                <thead>
                  <tr>
                    <th scope="col">Mock</th>
                    <th scope="col">Rôle</th>
                    <th scope="col">Questions</th>
                    <th scope="col">Durée</th>
                    <th scope="col">Rejouable</th>
                  </tr>
                </thead>
                <tbody>
                  {mocks.map((mock) => (
                    <tr key={mock.id}>
                      <th scope="row">
                        <Link to={mock.route}>{mock.name}</Link>
                      </th>
                      <td>{mock.purpose}</td>
                      <td>{mock.question_count}</td>
                      <td>
                        {/* A duration expressed as a rule keeps its sentence;
                            one expressed as a number gets its unit here. */}
                        {/^\d+$/.test(mock.duration_minutes)
                          ? `${mock.duration_minutes} min`
                          : mock.duration_minutes}
                      </td>
                      <td>{mock.repeatable ? 'Oui' : 'Non'}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {mocks.map((mock) => (
              <section key={mock.id} aria-labelledby={`${mock.id}-heading`}>
                <h2 id={`${mock.id}-heading`}>{mock.name}</h2>

                <p>
                  <strong>À quoi il sert.</strong> {mock.purpose}
                </p>
                <p>
                  <strong>Quand le passer.</strong> {mock.when_to_use}
                </p>

                <ul>
                  <li>
                    Questions : {mock.question_count}
                    {mock.eligible_questions !== null && (
                      <>
                        {' '}
                        — tirées de {mock.eligible_questions} questions
                        éligibles, donc deux passages diffèrent
                      </>
                    )}
                  </li>
                  <li>
                    Durée :{' '}
                    {/^\d+$/.test(mock.duration_minutes)
                      ? `${mock.duration_minutes} minutes`
                      : mock.duration_minutes}
                  </li>
                  <li>Langue : {mock.language}</li>
                  <li>Banque : {mock.pool}</li>
                  <li>
                    Scoring : {mock.scoring_policy}
                  </li>
                  <li>
                    Format : <code>{mock.format_label}</code>
                  </li>
                </ul>

                <p>
                  <Link className="button button--primary" to={mock.route}>
                    Ouvrir {mock.name} →
                  </Link>
                </p>
              </section>
            ))}

            <hr />

            <h2>Ce que ces chiffres ne sont pas</h2>
            <p className="certpath-note">{state.payload.not_official}</p>
            <p className="certpath-note">
              Aucun seuil de réussite officiel n'est connu de ce projet et aucun
              n'est affiché : les scores rendus par les simulations sont des
              indicateurs d'entraînement interne
              (<code>INTERNAL_TRAINING_FORMAT</code>). Seules les contraintes
              marquées <code>OFFICIAL_FORMAT</code> — les 75 questions, les 90
              minutes et l'anglais du Mock 4 — sont publiées par la
              certification.
            </p>

            <h2>Avant les simulations</h2>
            <p>
              Une simulation mesure ; elle n'enseigne pas. Le travail se fait en{' '}
              <Link to="/practice">Practice Mode</Link> — sans chronomètre, avec
              la correction après chaque réponse — et en{' '}
              <Link to="/exam">Exam Mode</Link>, chronométré, pour l'endurance.
            </p>
          </>
        )}
      </main>
    </Layout>
  );
}
