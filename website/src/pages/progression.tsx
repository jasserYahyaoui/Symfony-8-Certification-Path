import React, {useState} from 'react';
import Layout from '@theme/Layout';
import {clearState, readState} from '@site/src/lib/storage';

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

  function handleExport(): void {
    const state = readState();
    setExported(JSON.stringify(state, null, 2));
    setStatus(
      `Export généré : ${state.attempts.length} tentative(s), ${state.sessions.length} session(s). Copiez ce JSON pour le conserver.`,
    );
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
    <Layout title="Ma progression" description="Exporter ou effacer votre progression locale.">
      <main className="container margin-vert--lg">
        <h1>Ma progression</h1>

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
