import React from 'react';
import Layout from '@theme/Layout';
import Link from '@docusaurus/Link';
import usePayload from '@site/src/lib/usePayload';
import RevisionCalendar from '@site/src/components/RevisionCalendar';
import {type CalendarPayload, formatDuration} from '@site/src/lib/calendar';

/**
 * The revision plan as an agenda grid.
 *
 * The Markdown calendar under /docs/revision/calendar stays: it is the
 * printable, linkable record, and it is what the production smoke test reads.
 * This page renders the same generated plan — same file, same minutes — as
 * time slots, because a learner deciding what to do on Tuesday evening should
 * not have to scroll ninety days of prose to find out.
 */
export default function CalendarPage(): React.JSX.Element {
  const state = usePayload<CalendarPayload>('revision-calendar.json');

  return (
    <Layout
      title="Agenda de révision"
      description="Le plan de révision Symfony 8.0 sous forme d'agenda : vue mensuelle, hebdomadaire et quotidienne.">
      <main className="container margin-vert--lg">
        <h1>Agenda de révision</h1>

        {state.status === 'loading' && <p>Chargement de l'agenda…</p>}

        {state.status === 'error' && (
          <div className="alert alert--danger" role="alert">
            <p>
              L'agenda n'a pas pu être chargé ({state.message}). Le calendrier jour par jour reste
              lisible dans <Link to="/docs/revision/calendar">la version rédigée</Link>.
            </p>
          </div>
        )}

        {state.status === 'ready' && (
          <>
            <Summary payload={state.payload} />
            <RevisionCalendar payload={state.payload} />
            <Caveat payload={state.payload} />
          </>
        )}
      </main>
    </Layout>
  );
}

function Summary({payload}: {payload: CalendarPayload}): React.JSX.Element {
  const total = Object.values(payload.days).reduce((sum, day) => sum + day.used, 0);
  const sessions = Object.values(payload.days).reduce((sum, day) => sum + day.events.length, 0);

  return (
    <p>
      Du <strong>{payload.start}</strong> au <strong>{payload.exam}</strong>, jour de l'examen :{' '}
      <strong>{sessions} sessions</strong> réparties sur{' '}
      <strong>{Object.keys(payload.days).length} journées</strong>, soit{' '}
      <strong>{formatDuration(total)}</strong> de vos disponibilités effectivement occupées —
      samedis de source tour et dimanches de consolidation compris. Le travail sur le contenu seul
      en représente 108,3 h ; <Link to="/docs/revision/roadmap">la roadmap</Link> détaille les deux
      totaux et pourquoi il faut les deux. Tous les items sont étudiés au{' '}
      <strong>{payload.all_items_in}</strong> ; les mocks viennent après, jamais avant.
    </p>
  );
}

/**
 * The one thing about this page that is not measured, said on the page rather
 * than in a commit message. The plan carries durations; clock times are a
 * display convention, and a grid that shows 18:00 without saying so would read
 * as a commitment the repository never made.
 */
function Caveat({payload}: {payload: CalendarPayload}): React.JSX.Element {
  const lost = Object.entries(payload.lost_reviews_by_offset);

  return (
    <section className="margin-top--lg">
      <h2>Ce que cet agenda affirme, et ce qu'il ne dit pas</h2>

      <p>
        <strong>Les durées sont mesurées</strong> — mots de corps de cours, nombre de questions,
        nombre de flashcards — à travers un modèle d'effort dont les hypothèses sont écrites dans{' '}
        <Link to="/docs/revision/roadmap">la roadmap</Link>. Elles sont à recalibrer après votre
        première semaine réelle.
      </p>

      <p>
        <strong>Les heures d'horloge sont une convention d'affichage, pas une donnée du plan.</strong>{' '}
        Rien dans le dépôt ne sait à quelle heure vous ouvrez un cours. Les créneaux partent de{' '}
        <strong>18:00</strong> en semaine et de <strong>09:00</strong> le week-end, dérivés des
        disponibilités que vous avez déclarées. Décalez-les : seule la durée compte, et l'ordre à
        l'intérieur d'une journée.
      </p>

      <p>
        <strong>L'heure de l'examen n'est pas connue de ce dépôt</strong>, donc le 15 décembre porte
        un jalon et non un créneau. Elle ne sera pas inventée.
      </p>

      {lost.length > 0 && (
        <p>
          L'agenda s'arrête à l'examen. Les{' '}
          <strong>
            {lost.reduce((sum, [, n]) => sum + n, 0)} révisions ({formatDuration(payload.lost_reviews_minutes)})
          </strong>{' '}
          que le modèle plaçait après l'épreuve —{' '}
          {lost.map(([off, n], i) => (
            <React.Fragment key={off}>
              {i > 0 ? ', ' : ''}
              {n} J+{off}
            </React.Fragment>
          ))}{' '}
          — ne sont pas faisables et ne sont donc pas affichées. Elles sont comptées plutôt
          qu'effacées : c'est le coût de la date choisie.
        </p>
      )}

      <p>
        Cette page est <strong>en lecture seule</strong> : déplacer un bloc à la souris ne
        l'enregistrerait nulle part, car aucune persistance de progression n'existe encore. Un bouton
        qui fait semblant est pire qu'un bouton absent. Pour décaler durablement une session,
        régénérez le plan — la commande est dans{' '}
        <Link to="/docs/revision/readiness">PRÊT-CANDIDAT</Link>.
      </p>
    </section>
  );
}
