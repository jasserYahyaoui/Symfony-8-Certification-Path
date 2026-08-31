import React from 'react';
import Link from '@docusaurus/Link';

/**
 * Shown while the question bank is empty. The bank cannot be filled before the
 * official syllabus is imported: a question must map to an official item, and
 * no official item exists yet (Master Plan §3.1).
 */
export default function EmptyBank({mode}: {mode: 'practice' | 'exam'}): React.JSX.Element {
  return (
    <div className="certpath-empty" role="status">
      <p>
        <strong>Aucune question disponible pour le moment.</strong>
      </p>
      <p>
        {mode === 'practice'
          ? 'La banque de questions sera constituée à partir du Lot 0.5 (Golden Slice).'
          : 'Le pool holdout des simulations sera constitué au Lot 27.'}
      </p>
      <p className="certpath-note">
        Une question doit être rattachée à un item officiel du syllabus, et le
        syllabus officiel n'a pas encore pu être importé.{' '}
        <Link to="/docs/syllabus/coverage">Voir l'état de la couverture</Link>.
      </p>
    </div>
  );
}
