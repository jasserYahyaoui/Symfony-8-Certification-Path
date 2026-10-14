import React, {useEffect, useState} from 'react';
import Layout from '@theme/Layout';
import Link from '@docusaurus/Link';
import type {ExamSeriesPayload, Question} from '@site/src/lib/types';
import {readState, shuffle, type ExamSession} from '@site/src/lib/storage';
import usePayload from '@site/src/lib/usePayload';
import ExamSitting from '@site/src/components/ExamSitting';
import {difficultyMix, drawExam, fixedExam, modeOf, nextDrawNumber, recommended, statuses} from '@site/src/lib/examSeries';

interface Sitting {
  number: number;
  questions: Question[];
  alreadySeen: number;
  key: number;
}

/**
 * The numbered mock exams (owner's request, 2026-10-14): official format,
 * picked from a menu that recommends which one to sit next.
 *
 * Exams 1 to 6 are fixed at build time and share no question; they rise in
 * estimated difficulty, which is the order recommended. From 7 on, each exam is
 * drawn from the same pool with the same topic spread, the questions met least
 * often first, and the page says how many were already met.
 *
 * Everything numeric comes from exams.json. History is read on the client only:
 * localStorage does not exist when Docusaurus renders the page.
 */
export default function Examens(): React.JSX.Element {
  const state = usePayload<ExamSeriesPayload>('exams.json');
  const [sessions, setSessions] = useState<ExamSession[] | null>(null);
  const [sitting, setSitting] = useState<Sitting | null>(null);

  useEffect(() => {
    if (sitting === null) {
      setSessions(readState().sessions);
    }
  }, [sitting]);

  function startFixed(payload: ExamSeriesPayload, number: number): void {
    setSitting({number, questions: shuffle(fixedExam(payload, number)), alreadySeen: 0, key: Date.now()});
  }

  function startDraw(payload: ExamSeriesPayload): void {
    const {questions, alreadySeen} = drawExam(payload, readState().attempts);
    setSitting({number: nextDrawNumber(payload, readState().sessions), questions, alreadySeen, key: Date.now()});
  }

  const payload = state.status === 'ready' ? state.payload : null;
  const title = sitting ? `Examen blanc ${sitting.number}` : 'Examens blancs';

  return (
    <Layout title={title} description="Examens blancs au format de l'examen Symfony 8.0, à choisir dans un menu.">
      <main className="container margin-vert--lg">
        <h1>{title}</h1>

        {state.status === 'loading' && <p role="status">Chargement…</p>}
        {state.status === 'error' && <p role="status">Chargement impossible : {state.message}</p>}

        {payload && sitting && (
          <>
            {sitting.alreadySeen > 0 && (
              <p className="certpath-note" role="status">
                Tirage : {sitting.alreadySeen} question{sitting.alreadySeen > 1 ? 's' : ''} sur{' '}
                {sitting.questions.length} {sitting.alreadySeen > 1 ? 'ont' : 'a'} déjà été vue
                {sitting.alreadySeen > 1 ? 's' : ''}. Le score mesure aussi votre mémoire de ces questions.
              </p>
            )}
            <ExamSitting
              key={sitting.key}
              questions={sitting.questions}
              durationMinutes={payload.duration_minutes}
              items={payload.items}
              mode={modeOf(sitting.number)}
              scoringPolicy={payload.scoring_policy}
              submitLabel="Soumettre l'examen"
              restartLabel="Retour au menu des examens"
              onRestart={() => setSitting(null)}
            />
          </>
        )}

        {payload && !sitting && (
          <Menu
            payload={payload}
            sessions={sessions}
            onFixed={(n) => startFixed(payload, n)}
            onDraw={() => startDraw(payload)}
          />
        )}
      </main>
    </Layout>
  );
}

function Menu({
  payload,
  sessions,
  onFixed,
  onDraw,
}: {
  payload: ExamSeriesPayload;
  sessions: ExamSession[] | null;
  onFixed: (n: number) => void;
  onDraw: () => void;
}): React.JSX.Element {
  const history = sessions ?? [];
  const next = recommended(payload, history);
  const drawNumber = nextDrawNumber(payload, history);
  const rows = statuses(payload, history);

  return (
    <>
      <p>
        <strong>
          {payload.question_count} questions, {payload.duration_minutes} minutes, en anglais
        </strong>
        , tous les sujets officiels dans chaque examen. Aucune correction avant la soumission ; à la
        fin du temps, les réponses déjà saisies sont soumises.
      </p>

      {sessions !== null && (
        <p role="status">
          <strong>Recommandé maintenant : Examen blanc {next}.</strong>{' '}
          {next <= payload.exams.length
            ? 'Les examens 1 à 6 montent en difficulté estimée : passez-les dans l’ordre.'
            : 'Les six examens fixes sont faits : place aux tirages.'}
        </p>
      )}

      <h2>Examens 1 à {payload.exams.length} — aucune question en commun</h2>
      <div className="certpath-table-scroll" tabIndex={0} role="region" aria-label="Examens blancs fixes">
        <table>
          <caption>Les six examens fixes, du plus facile au plus difficile (difficulté estimée)</caption>
          <thead>
            <tr>
              <th scope="col">Examen</th>
              <th scope="col">Difficulté estimée</th>
              <th scope="col">Réponses multiples</th>
              <th scope="col">Dernier résultat</th>
              <th scope="col">Action</th>
            </tr>
          </thead>
          <tbody>
            {rows.map(({number, last}) => {
              const mix = difficultyMix(fixedExam(payload, number));
              const multiple = payload.exams.find((exam) => exam.number === number)?.multiple_answer_count ?? 0;
              return (
                <tr key={number}>
                  <th scope="row">
                    Examen blanc {number}
                    {number === next && sessions !== null && <> — <strong>recommandé</strong></>}
                  </th>
                  <td>
                    {mix.easy} faciles, {mix.medium} moyennes, {mix.hard} difficiles
                  </td>
                  <td>
                    {multiple} sur {payload.question_count}
                  </td>
                  <td>
                    {last
                      ? `${last.correct} / ${last.question_count} le ${new Date(last.finished_at).toLocaleDateString('fr-FR')}`
                      : 'pas encore passé'}
                  </td>
                  <td>
                    <button
                      type="button"
                      className={`button button--sm ${number === next ? 'button--primary' : 'button--secondary'}`}
                      onClick={() => onFixed(number)}>
                      {last ? 'Repasser' : 'Passer'} l'examen {number}
                    </button>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>

      <h2>Examens {payload.exams.length + 1} et suivants — tirages</h2>
      <p>
        Chaque tirage respecte la même répartition par sujet et prend d'abord les questions que vous
        avez le moins vues. Au-delà de six examens, la banque ne suffit plus à éviter les répétitions :
        le nombre de questions déjà vues est affiché au début.
      </p>
      <div className="certpath-actions">
        <button
          type="button"
          className={`button ${next === drawNumber ? 'button--primary' : 'button--secondary'}`}
          onClick={onDraw}>
          Tirer l'examen blanc {drawNumber}
        </button>
      </div>

      <h2>À savoir</h2>
      <ul>
        <li>{payload.format_note}</li>
        <li>{payload.answer_modes_note}</li>
        <li>
          Après la soumission, chaque question est corrigée : bonnes réponses, explication et, pour
          une erreur, le lien vers la section du cours qui l'enseigne.
        </li>
        <li>
          Répartition par sujet (<code>{payload.distribution_label}</code>) : {payload.distribution_note}
        </li>
        <li>{payload.not_unseen}</li>
        <li>
          Le score suit une politique interne ({payload.scoring_policy}) : la notation officielle n'est
          pas publiée, ce n'est donc pas une prédiction de résultat.
        </li>
      </ul>

      <p>
        Autres simulations : <Link to="/simulations">Mocks 1 à 5</Link>. Le{' '}
        <Link to="/mock-4">Mock 4</Link> reste le dernier, une seule fois.
      </p>
    </>
  );
}
