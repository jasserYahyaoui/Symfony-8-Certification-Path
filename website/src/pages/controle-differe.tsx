import React, {useEffect, useState} from 'react';
import Layout from '@theme/Layout';
import Link from '@docusaurus/Link';
import {useLocation} from '@docusaurus/router';
import ComprehensionCheck from '@site/src/components/ComprehensionCheck';
import {readState} from '@site/src/lib/storage';
import {FIRST_DELAY_DAYS, SERIES_SIZE, schedule, selectDelayed, type LotSchedule} from '@site/src/lib/delayed';
import usePayload from '@site/src/lib/usePayload';
import type {ComprehensionPayload} from '@site/src/lib/types';

function lotLabel(lot: string): string {
  return lot.replace(/^lot-/, 'Lot ');
}

function formatDay(iso: string): string {
  return new Date(iso).toLocaleDateString('fr-FR', {weekday: 'long', day: 'numeric', month: 'long'});
}

/**
 * The list: lots due for a delayed check, the next ones, and why the others
 * are absent. Read on the client only: the history lives in localStorage.
 */
function DueLots({payload}: {payload: ComprehensionPayload}): React.JSX.Element {
  const [lots, setLots] = useState<LotSchedule[] | null>(null);

  useEffect(() => {
    setLots(schedule(readState().comprehension, new Date()));
  }, []);

  if (lots === null) {
    return <p role="status">Lecture de votre historique…</p>;
  }
  if (lots.length === 0) {
    return (
      <p>
        Aucun contrôle de compréhension n'a encore été passé dans ce navigateur. Un contrôle différé
        se propose {FIRST_DELAY_DAYS} jours après le premier passage d'un lot.
      </p>
    );
  }

  const due = lots.filter((l) => l.due && payload.lots[l.lot]);
  const later = lots.filter((l) => !l.due && payload.lots[l.lot]);

  return (
    <>
      <h2>À faire maintenant</h2>
      {due.length === 0 ? (
        <p>Aucun contrôle différé n'est dû aujourd'hui.</p>
      ) : (
        <ul>
          {due.map((l) => (
            <li key={l.lot}>
              <Link to={`/controle-differe?lot=${l.lot}`}>
                {lotLabel(l.lot)} — contrôle différé ({SERIES_SIZE} questions)
              </Link>{' '}
              <span className="certpath-note">dernière activité le {formatDay(l.lastActivity)}</span>
            </li>
          ))}
        </ul>
      )}
      {later.length > 0 && (
        <>
          <h2>Prochains</h2>
          <ul>
            {later.map((l) => (
              <li key={l.lot}>
                {lotLabel(l.lot)} : le {formatDay(l.dueAt)}{' '}
                <span className="certpath-note">(intervalle de {l.intervalDays} jours)</span>
              </li>
            ))}
          </ul>
        </>
      )}
    </>
  );
}

/** A delayed check of one lot. The selection is drawn once, on arrival. */
function DelayedSitting({lot, payload}: {lot: string; payload: ComprehensionPayload}): React.JSX.Element {
  const [selection, setSelection] = useState<string[] | null>(null);

  useEffect(() => {
    const ids = payload.questions.filter((q) => q.lot === lot).map((q) => q.id);
    setSelection(selectDelayed(ids, readState().comprehension.filter((a) => a.lot === lot)));
  }, [lot, payload]);

  if (!payload.lots[lot]) {
    return (
      <p role="status">
        Aucun lot ne porte l'identifiant <code>{lot}</code>. <Link to="/controle-differe">Voir les contrôles différés</Link>
      </p>
    );
  }
  if (selection === null) {
    return <p role="status">Préparation du contrôle…</p>;
  }

  return (
    <>
      <p>
        {lotLabel(lot)} : {selection.length} questions du{' '}
        <Link to={payload.lots[lot].url}>contrôle de compréhension</Link>, d'abord celles manquées la
        dernière fois, puis celles jamais posées, puis les plus anciennes. Correction après chaque
        réponse. <Link to="/controle-differe">Retour à la liste</Link>
      </p>
      <ComprehensionCheck lot={lot} selection={selection} />
    </>
  );
}

/**
 * Delayed checks (remediation P3.2): the comprehension questions of a lot,
 * asked again days after it was read — see src/lib/delayed.ts for the
 * intervals and the selection.
 */
export default function DelayedChecks(): React.JSX.Element {
  const state = usePayload<ComprehensionPayload>('comprehension.json');
  const lot = new URLSearchParams(useLocation().search).get('lot') ?? '';

  return (
    <Layout title="Contrôles différés" description="Les contrôles de compréhension repris des jours après la lecture du lot.">
      <main className="container margin-vert--lg">
        <h1>Contrôles différés</h1>
        <p>
          Le contrôle de compréhension d'un lot se passe juste après la lecture : il mesure ce qui est
          compris, pas ce qui reste. Un contrôle différé reprend {SERIES_SIZE} de ses questions{' '}
          {FIRST_DELAY_DAYS} jours après votre dernière réponse sur le lot, puis 14, puis 28. Ces
          intervalles sont une règle simple, sans efficacité revendiquée.
        </p>
        {state.status === 'loading' && <p role="status">Chargement…</p>}
        {state.status === 'error' && <p role="status">Impossible de charger les questions : {state.message}</p>}
        {state.status === 'ready' &&
          (lot === '' ? <DueLots payload={state.payload} /> : <DelayedSitting lot={lot} payload={state.payload} />)}
      </main>
    </Layout>
  );
}
