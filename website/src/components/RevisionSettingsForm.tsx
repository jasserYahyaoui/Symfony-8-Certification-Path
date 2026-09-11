import React, {useState} from 'react';
import styles from './RevisionCalendar.module.css';
import type {RevisionSettings} from '@site/src/lib/storage';

/**
 * The replanning controls.
 *
 * Changing any field re-runs the scheduler over the whole plan: the spaced
 * revisions move with the items that generated them, the lot assessments land
 * on the first day with room, and the mocks re-anchor to the new exam date.
 * Nothing here shifts labels over a frozen grid.
 */
export default function RevisionSettingsForm({
  value,
  canonical,
  error,
  onApply,
  onReset,
}: {
  value: RevisionSettings;
  canonical: RevisionSettings;
  error: string | null;
  onApply: (next: RevisionSettings) => void;
  onReset: () => void;
}): React.JSX.Element {
  const [draft, setDraft] = useState<RevisionSettings>(value);

  function set<K extends keyof RevisionSettings>(key: K, v: RevisionSettings[K]): void {
    setDraft({...draft, [key]: v});
  }

  const changed = JSON.stringify(draft) !== JSON.stringify(canonical);

  return (
    <form
      className={styles.settings}
      onSubmit={(e) => {
        e.preventDefault();
        onApply(draft);
      }}>
      <h2>Replanifier</h2>

      <p>
        Modifier une de ces valeurs <strong>recalcule tout le plan</strong> : les révisions espacées
        suivent les items qui les engendrent, les assessments se reposent sur le premier jour qui a
        la place, et les mocks se recalent sur la nouvelle date d'examen.
      </p>

      <div className={styles.settingsGrid}>
        <label>
          Date de début
          <input type="date" value={draft.start} onChange={(e) => set('start', e.target.value)} required />
        </label>
        <label>
          Date d'examen
          <input type="date" value={draft.exam} onChange={(e) => set('exam', e.target.value)} required />
        </label>
        <label>
          Nouveaux items par jour
          <input
            type="number"
            min={1}
            max={12}
            value={draft.maxNew}
            onChange={(e) => set('maxNew', Number(e.target.value))}
            required
          />
        </label>
        <label>
          Budget en semaine (min)
          <input
            type="number"
            min={30}
            max={600}
            step={10}
            value={draft.weekday}
            onChange={(e) => set('weekday', Number(e.target.value))}
            required
          />
        </label>
        <label>
          Budget le week-end (min)
          <input
            type="number"
            min={30}
            max={600}
            step={10}
            value={draft.weekend}
            onChange={(e) => set('weekend', Number(e.target.value))}
            required
          />
        </label>
        <label>
          Heure de début en semaine
          <input
            type="time"
            step={3600}
            value={draft.weekdayStart}
            onChange={(e) => set('weekdayStart', e.target.value)}
            required
          />
        </label>
        <label>
          Heure de début le week-end
          <input
            type="time"
            step={3600}
            value={draft.weekendStart}
            onChange={(e) => set('weekendStart', e.target.value)}
            required
          />
        </label>
      </div>

      {error && (
        <div className="alert alert--danger margin-top--sm" role="alert">
          <strong>Ce plan est infaisable, donc il n'est pas affiché.</strong> {error}
        </div>
      )}

      <p className={styles.settingsActions}>
        <button type="submit" className="button button--primary button--sm">
          Replanifier
        </button>{' '}
        <button
          type="button"
          className="button button--secondary button--sm"
          onClick={() => {
            setDraft(canonical);
            onReset();
          }}
          disabled={!changed && JSON.stringify(value) === JSON.stringify(canonical)}>
          Revenir au plan publié
        </button>
      </p>

      <p>
        <small>
          Les heures de début sont une <strong>convention d'affichage</strong> : le plan ne connaît
          que des durées. Les changer déplace les créneaux dans la journée, jamais la charge. Vos
          réglages restent dans ce navigateur et sont inclus dans l'export de{' '}
          <a href="../progression">Ma progression</a>.
        </small>
      </p>
    </form>
  );
}
