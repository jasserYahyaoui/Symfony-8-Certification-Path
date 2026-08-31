import React, {useMemo, useState} from 'react';
import Layout from '@theme/Layout';
import Link from '@docusaurus/Link';
import type {Question} from '@site/src/lib/types';
import {isCorrect, recordAttempt, shuffle, weakQuestionIds} from '@site/src/lib/storage';
import usePayload from '@site/src/lib/usePayload';
import QuestionCard from '@site/src/components/QuestionCard';
import EmptyBank from '@site/src/components/EmptyBank';

interface Filters {
  topic: string;
  difficulty: string;
  language: string;
  weakOnly: boolean;
}

/**
 * Practice Mode, Master Plan §9.1: un-timed, answer submitted before feedback,
 * explanation and sources shown afterwards, filtering by topic/domain/difficulty,
 * weakness replay, and strict separation from the holdout pool.
 */
export default function Practice(): React.JSX.Element {
  const state = usePayload('practice.json');
  const [filters, setFilters] = useState<Filters>({
    topic: '',
    difficulty: '',
    language: '',
    weakOnly: false,
  });
  const [index, setIndex] = useState(0);
  const [answered, setAnswered] = useState<string[] | null>(null);
  const [round, setRound] = useState(0);

  const all: Question[] = state.status === 'ready' ? state.payload.questions : [];

  const queue = useMemo(() => {
    const weak = filters.weakOnly ? weakQuestionIds() : null;

    return shuffle(
      all.filter((q) => {
        if (filters.topic && q.official_topic !== filters.topic) return false;
        if (filters.difficulty && q.difficulty !== filters.difficulty) return false;
        if (filters.language && q.language !== filters.language) return false;
        if (weak && !weak.has(q.id)) return false;
        return true;
      }),
    );
    // `round` re-shuffles deliberately when the learner restarts a series.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [all, filters, round]);

  const topics = useMemo(
    () => [...new Set(all.map((q) => q.official_topic))].sort(),
    [all],
  );
  const difficulties = useMemo(
    () => [...new Set(all.map((q) => q.difficulty))].sort(),
    [all],
  );

  function updateFilter<K extends keyof Filters>(key: K, value: Filters[K]): void {
    setFilters((current) => ({...current, [key]: value}));
    setIndex(0);
    setAnswered(null);
  }

  function handleSubmit(question: Question, chosen: string[]): void {
    recordAttempt({
      question_id: question.id,
      question_version: question.version,
      official_item: question.official_item,
      correct: isCorrect(question, chosen),
      chosen,
      answered_at: new Date().toISOString(),
      mode: 'practice',
    });
    setAnswered(chosen);
  }

  const question = queue[index];

  return (
    <Layout
      title="Practice Mode"
      description="Entraînement libre aux questions de la certification Symfony 8.">
      <main className="container margin-vert--lg">
        <h1>Practice Mode</h1>
        <p>
          Entraînement sans limite de temps. La réponse et l'explication
          n'apparaissent qu'après validation. Les questions du pool{' '}
          <em>holdout</em> sont absentes de ce mode : elles ne figurent pas dans
          les données chargées par cette page.
        </p>

        {state.status === 'loading' && <p role="status">Chargement…</p>}

        {state.status === 'error' && (
          <p role="status">Impossible de charger les questions : {state.message}</p>
        )}

        {state.status === 'ready' && all.length === 0 && <EmptyBank mode="practice" />}

        {state.status === 'ready' && all.length > 0 && (
          <>
            <section aria-labelledby="filters-heading">
              <h2 id="filters-heading">Filtres</h2>
              <div className="certpath-filters">
                <div className="certpath-field">
                  <label htmlFor="filter-topic">Sujet officiel</label>
                  <select
                    id="filter-topic"
                    value={filters.topic}
                    onChange={(e) => updateFilter('topic', e.target.value)}>
                    <option value="">Tous</option>
                    {topics.map((t) => (
                      <option key={t} value={t}>
                        {t}
                      </option>
                    ))}
                  </select>
                </div>

                <div className="certpath-field">
                  <label htmlFor="filter-difficulty">Difficulté</label>
                  <select
                    id="filter-difficulty"
                    value={filters.difficulty}
                    onChange={(e) => updateFilter('difficulty', e.target.value)}>
                    <option value="">Toutes</option>
                    {difficulties.map((d) => (
                      <option key={d} value={d}>
                        {d}
                      </option>
                    ))}
                  </select>
                </div>

                <div className="certpath-field">
                  <label htmlFor="filter-language">Langue</label>
                  <select
                    id="filter-language"
                    value={filters.language}
                    onChange={(e) => updateFilter('language', e.target.value)}>
                    <option value="">Toutes</option>
                    <option value="fr">Français</option>
                    <option value="en">English</option>
                  </select>
                </div>

                <div className="certpath-field">
                  <label htmlFor="filter-weak">
                    <input
                      type="checkbox"
                      id="filter-weak"
                      checked={filters.weakOnly}
                      onChange={(e) => updateFilter('weakOnly', e.target.checked)}
                    />{' '}
                    Rejouer mes points faibles
                  </label>
                </div>
              </div>
            </section>

            <section aria-labelledby="quiz-heading">
              <h2 id="quiz-heading">Question</h2>

              {queue.length === 0 && (
                <p role="status">Aucune question ne correspond à ces filtres.</p>
              )}

              {queue.length > 0 && index >= queue.length && (
                <div>
                  <p role="status">Série terminée.</p>
                  <div className="certpath-actions">
                    <button
                      type="button"
                      className="button button--primary"
                      onClick={() => {
                        setIndex(0);
                        setAnswered(null);
                        setRound((r) => r + 1);
                      }}>
                      Recommencer
                    </button>
                  </div>
                </div>
              )}

              {question && (
                <>
                  <QuestionCard
                    key={`${question.id}-${round}`}
                    question={question}
                    index={index}
                    total={queue.length}
                    submitLabel="Valider ma réponse"
                    disabled={answered !== null}
                    onSubmit={(chosen) => handleSubmit(question, chosen)}
                  />

                  {answered && (
                    <Feedback
                      question={question}
                      chosen={answered}
                      onNext={() => {
                        setIndex((i) => i + 1);
                        setAnswered(null);
                      }}
                    />
                  )}
                </>
              )}
            </section>
          </>
        )}

        <p className="certpath-note">
          Votre progression reste dans ce navigateur.{' '}
          <Link to="/docs/">En savoir plus</Link>.
        </p>
      </main>
    </Layout>
  );
}

function Feedback({
  question,
  chosen,
  onNext,
}: {
  question: Question;
  chosen: string[];
  onNext: () => void;
}): React.JSX.Element {
  const correct = isCorrect(question, chosen);
  const result = correct ? 'correct' : 'incorrect';

  return (
    <div className="certpath-feedback" data-result={result} role="status">
      <p className="certpath-verdict" data-result={result}>
        {correct ? 'Réponse correcte' : 'Réponse incorrecte'}
      </p>

      <p>{question.explanation}</p>

      {question.choices
        .filter((c) => !c.correct && c.explanation)
        .map((c) => (
          <p className="certpath-note" key={c.id}>
            « {c.text} » — {c.explanation}
          </p>
        ))}

      {question.official_sources.length > 0 && (
        <p className="certpath-note">
          Sources :{' '}
          {question.official_sources.map((source, i) => (
            <React.Fragment key={source.url}>
              {i > 0 && ', '}
              <a href={source.url} target="_blank" rel="noopener noreferrer">
                {source.url}
              </a>
            </React.Fragment>
          ))}
        </p>
      )}

      <div className="certpath-actions">
        <button type="button" className="button button--primary" onClick={onNext}>
          Question suivante
        </button>
      </div>
    </div>
  );
}
