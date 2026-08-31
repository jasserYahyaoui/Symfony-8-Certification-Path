import React from 'react';
import Layout from '@theme/Layout';
import Link from '@docusaurus/Link';

/**
 * Landing page. The published exam constraints below are OFFICIAL_FORMAT
 * (§7.4); nothing else about the exam's composition is claimed as official.
 */
export default function Home(): React.JSX.Element {
  return (
    <Layout
      title="Accueil"
      description="Système d'apprentissage et d'évaluation ciblé sur la certification Symfony 8.0.">
      <header className="hero hero--primary">
        <div className="container">
          <h1 className="hero__title">Symfony 8 Certification Path</h1>
          <p className="hero__subtitle">
            Le plus petit système fiable qui couvre entièrement le syllabus
            officiel de la certification Symfony 8.0.
          </p>
          <div className="certpath-actions">
            <Link className="button button--secondary button--lg" to="/docs/">
              Commencer
            </Link>
            <Link className="button button--outline button--secondary button--lg" to="/practice">
              Practice Mode
            </Link>
          </div>
        </div>
      </header>

      <main className="container margin-vert--xl">
        <div className="row">
          <div className="col col--4">
            <h2>Comprendre</h2>
            <p>
              Des explications ciblées sur ce qui est réellement examinable, avec
              les distinctions et les pièges qui font la différence le jour J.
              Pas une encyclopédie Symfony.
            </p>
            <Link to="/docs/">Documentation →</Link>
          </div>

          <div className="col col--4">
            <h2>S'entraîner</h2>
            <p>
              Practice Mode sans chronomètre : la réponse et l'explication
              n'apparaissent qu'après validation, avec filtrage par sujet et
              rejeu des points faibles.
            </p>
            <Link to="/practice">Practice Mode →</Link>
          </div>

          <div className="col col--4">
            <h2>Se tester</h2>
            <p>
              Exam Mode chronométré, au format publié de l'examen : 75 questions,
              90 minutes. Aucune correction avant la soumission finale.
            </p>
            <Link to="/exam">Exam Mode →</Link>
          </div>
        </div>

        <hr />

        <div className="row">
          <div className="col col--6">
            <h2>Format officiel publié</h2>
            <pre>
              <code>
                {'75 questions\n90 minutes\n15 topics\nEnglish\nSymfony 8.0 only'}
              </code>
            </pre>
            <p className="certpath-note">
              Ces contraintes sont publiques (<code>OFFICIAL_FORMAT</code>). Aucune
              pondération par sujet n'est inventée : toute répartition interne
              utilisée pour l'entraînement est étiquetée{' '}
              <code>TRAINING_DISTRIBUTION</code>.
            </p>
          </div>

          <div className="col col--6">
            <h2>Vos données</h2>
            <p>
              Votre progression reste dans le stockage local de ce navigateur.
              Aucun compte, aucune donnée envoyée, aucun secret côté client.
              Vous pouvez l'exporter ou l'effacer à tout moment.
            </p>
            <Link to="/progression">Gérer ma progression →</Link>
          </div>
        </div>
      </main>
    </Layout>
  );
}
