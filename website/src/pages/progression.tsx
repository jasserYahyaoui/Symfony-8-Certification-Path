import React, {useEffect, useState} from 'react';
import Layout from '@theme/Layout';
import Link from '@docusaurus/Link';
import {clearState, importState, localDay, readState, type FlashcardRecord} from '@site/src/lib/storage';
import {CHANNELS, MIN_QUESTIONS, enough, observe, superseded, type Cell, type Channel, type Observed} from '@site/src/lib/observed';
import usePayload from '@site/src/lib/usePayload';
import type {Payload} from '@site/src/lib/types';

const CHANNEL_LABEL: Record<Channel, string> = {
  practice: 'Practice',
  comprehension: 'Compréhension',
  training: 'Exam et Mocks 1, 2, 3, 5',
  mock4: 'Mock 4',
};

function cellText(cell: Cell): string {
  if (cell.questions === 0) {
    return '—';
  }
  if (!enough(cell)) {
    return `données insuffisantes (${cell.questions} question${cell.questions > 1 ? 's' : ''})`;
  }
  const latest = cell.repeated > 0 ? ` · dernière réponse ${cell.latestCorrect}/${cell.questions}` : '';
  return `1re réponse ${cell.firstCorrect}/${cell.questions}${latest}`;
}

/**
 * What the learner's own answers show (remediation F5). Read on the client
 * only: localStorage does not exist when Docusaurus renders the page.
 */
interface QuestionVersions {
  questions: {id: string; version: number}[];
}

function ObservedTable(): React.JSX.Element {
  const index = usePayload<Payload>('practice.json');
  // Remediation P3.3: the questions rewritten at least once, with their
  // current version. An answer to an earlier version is not counted.
  const versions = usePayload<QuestionVersions>('question-versions.json');
  const [observed, setObserved] = useState<Observed | null>(null);
  const [outdated, setOutdated] = useState(0);

  useEffect(() => {
    if (index.status !== 'ready' || versions.status !== 'ready') {
      return;
    }
    const items = index.payload.items ?? {};
    const rewritten = new Map(versions.payload.questions.map((q) => [q.id, q.version]));
    const state = readState();
    setObserved(observe(state.attempts, state.comprehension, (id) => items[id]?.official_topic, rewritten));
    setOutdated(superseded(state.attempts, state.comprehension, rewritten));
  }, [index.status, versions.status]);

  if (index.status === 'error') {
    return <p role="status">Index des sujets indisponible : {index.message}</p>;
  }
  if (versions.status === 'error') {
    return <p role="status">Versions des questions indisponibles : {versions.message}</p>;
  }
  if (observed === null) {
    return <p role="status">Lecture de votre historique…</p>;
  }

  const note = outdated > 0 && (
    <p>
      {outdated} réponse(s) portent sur une version antérieure d'une question réécrite depuis : elles ne sont plus
      comptées. Ce qui a changé : <Link to="/docs/corrections">Journal des corrections</Link>.
    </p>
  );

  if (observed.size === 0) {
    return (
      <>
        {note}
        <p>Aucune réponse enregistrée dans ce navigateur : rien ne peut encore être observé.</p>
      </>
    );
  }

  const topics = [...observed.keys()].sort((a, b) => a.localeCompare(b, 'fr'));

  return (
    <>
      {note}
      <div className="certpath-table-scroll" tabIndex={0} role="region" aria-label="Vos réponses par sujet officiel">
        <table>
          <caption>Vos réponses par sujet officiel, séparées par mode</caption>
          <thead>
            <tr>
              <th scope="col">Sujet officiel</th>
              {CHANNELS.map((c) => (
                <th scope="col" key={c}>
                  {CHANNEL_LABEL[c]}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {topics.map((topic) => (
              <tr key={topic}>
                <th scope="row">{topic}</th>
                {CHANNELS.map((c) => (
                  <td key={c}>{cellText(observed.get(topic)![c])}</td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </>
  );
}

interface FlashcardIndex {
  cards: {id: string; version: string; item: string; url: string; front: string}[];
}

/**
 * The flashcards rated on the course pages, and the ones due (remediation
 * P2.3). Read from flashcards.json, which carries each card's front and page
 * but never its back, so the list does not answer the card it links to.
 */
function DueFlashcards(): React.JSX.Element {
  const index = usePayload<FlashcardIndex>('flashcards.json');
  const [records, setRecords] = useState<Record<string, FlashcardRecord> | null>(null);

  useEffect(() => {
    setRecords(readState().flashcards);
  }, []);

  if (index.status === 'loading' || records === null) {
    return <p role="status">Chargement des flashcards…</p>;
  }
  if (index.status === 'error') {
    return <p role="status">Impossible de charger l'index des flashcards : {index.message}</p>;
  }

  const today = localDay(new Date());
  const rated = index.payload.cards.filter((c) => records[c.id]?.card_hash === c.version);
  const changed = index.payload.cards.filter((c) => records[c.id] && records[c.id].card_hash !== c.version);
  const due = rated.filter((c) => records[c.id].due_on <= today).sort((a, b) => records[a.id].due_on.localeCompare(records[b.id].due_on));

  return (
    <>
      <p>
        {rated.length} carte(s) notée(s) sur {index.payload.cards.length}, dont <strong>{due.length} à revoir aujourd'hui</strong>.
        {changed.length > 0 && ` ${changed.length} carte(s) ont changé depuis votre notation : elles repartent de zéro.`}
      </p>
      {due.length > 0 && (
        <ul>
          {due.map((c) => (
            <li key={c.id}>
              <Link to={c.url}>{c.front}</Link>
            </li>
          ))}
        </ul>
      )}
      <p className="certpath-note">
        Une carte n'est planifiée que si vous notez votre rappel après l'avoir
        ouverte. Le calendrier suit un algorithme d'intervalles simple et
        déterministe (<code>docs/policy/review-algorithm.md</code>) : aucune
        efficacité n'est revendiquée.
      </p>
    </>
  );
}

/**
 * Privacy controls, Master Plan §13: explicit reset and versioned JSON export.
 *
 * The export is rendered as selectable text rather than a download link: a
 * page-initiated download is blocked in some embedded viewers, and a dead
 * button is worse than a textarea the learner can copy.
 */
export default function Progression(): React.JSX.Element {
  const [exported, setExported] = useState<string | null>(null);
  const [status, setStatus] = useState('');
  const [toImport, setToImport] = useState('');

  function handleExport(): void {
    const state = readState();
    setExported(JSON.stringify(state, null, 2));
    setStatus(
      `Export généré : ${state.attempts.length} tentative(s), ${state.sessions.length} session(s). Copiez ce JSON pour le conserver.`,
    );
  }

  function handleImport(): void {
    if (!window.confirm('Remplacer votre progression locale par celle de cet export ?')) {
      return;
    }
    const result = importState(toImport);
    setStatus(result.ok === false ? `Import refusé : ${result.reason}` : 'Progression importée. Rechargez la page pour la voir partout.');
  }

  function handleReset(): void {
    if (!window.confirm('Effacer définitivement votre progression locale ?')) {
      return;
    }
    const cleared = clearState();
    setExported(null);
    setStatus(
      cleared
        ? 'Progression effacée.'
        : 'Effacement impossible : le stockage local est indisponible dans ce navigateur.',
    );
  }

  return (
    <Layout title="Ma progression" description="Vos résultats, vos flashcards à revoir, et l'export, l'import ou l'effacement de votre progression locale.">
      <main className="container margin-vert--lg">
        <h1>Ma progression</h1>

        <h2>Ce que vos réponses montrent</h2>
        <p>
          Ce tableau décrit <strong>vos</strong> réponses. La couverture du
          syllabus et la Certification Readiness décrivent le <strong>contenu</strong> du
          site : elles peuvent valoir 100 % sans que vous ayez ouvert un cours.
        </p>
        <ul>
          <li>
            <strong>Practice</strong> et <strong>Compréhension</strong> corrigent
            après chaque réponse : c'est de l'apprentissage, pas une mesure.
          </li>
          <li>
            <strong>Exam et Mocks 1, 2, 3, 5</strong> puisent dans la banque de
            validation : vous avez pu y revoir les mêmes questions.
          </li>
          <li>
            <strong>Mock 4</strong> est la seule banque tenue hors de tout
            entraînement. Sa première réponse est ce qui se rapproche le plus
            d'une mesure sur des questions inédites.
          </li>
        </ul>
        <p className="certpath-note">
          La « 1re réponse » compte la première fois que vous avez répondu à
          chaque question ; une nouvelle tentative mesure aussi votre souvenir de
          la question. Sous {MIN_QUESTIONS} questions distinctes, rien n'est
          conclu. Aucune probabilité de réussite n'est calculée : le seuil
          officiel et la pondération par sujet ne sont pas publiés.
        </p>
        <ObservedTable />

        <h2>Flashcards à revoir</h2>
        <DueFlashcards />

        <h2>Exporter, importer ou effacer</h2>

        <p>
          Toute votre progression est conservée dans le stockage local de ce
          navigateur, sous la clé <code>certpath.learner-state</code>. Elle n'est
          jamais envoyée sur un serveur, et elle ne suit pas d'un appareil à
          l'autre.
        </p>

        <div className="certpath-actions">
          <button type="button" className="button button--primary" onClick={handleExport}>
            Exporter ma progression (JSON)
          </button>
          <button type="button" className="button button--secondary" onClick={handleReset}>
            Effacer ma progression
          </button>
        </div>

        <p role="status">{status}</p>

        {exported && (
          <div className="certpath-field">
            <label htmlFor="export-output">Export JSON</label>
            <textarea
              id="export-output"
              rows={16}
              readOnly
              value={exported}
              style={{width: '100%', fontFamily: 'var(--ifm-font-family-monospace)'}}
            />
          </div>
        )}

        <div className="certpath-field">
          <label htmlFor="import-input">Importer un export JSON</label>
          <textarea
            id="import-input"
            rows={6}
            value={toImport}
            onChange={(e) => setToImport(e.target.value)}
            style={{width: '100%', fontFamily: 'var(--ifm-font-family-monospace)'}}
          />
          <button type="button" className="button button--secondary margin-top--sm" onClick={handleImport} disabled={toImport.trim() === ''}>
            Importer
          </button>
        </div>

        <h2>Migrations</h2>
        <p className="certpath-note">
          L'export porte un <code>schema_version</code>. À chaque lecture, la
          chaîne de migrations est appliquée, de sorte qu'un changement de
          schéma ne fasse pas perdre l'historique déjà accumulé.
        </p>
      </main>
    </Layout>
  );
}
