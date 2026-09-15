import React, {useMemo, useState} from 'react';
import Layout from '@theme/Layout';
import Link from '@docusaurus/Link';
import type {ItemIndexEntry, Question} from '@site/src/lib/types';
import {
  isCorrect,
  practiceAttempts,
  recordAttempt,
  recordPracticeSession,
  shuffle,
  weakQuestionIds,
} from '@site/src/lib/storage';
import usePayload from '@site/src/lib/usePayload';
import QuestionCard from '@site/src/components/QuestionCard';
import PracticeFeedback from '@site/src/components/PracticeFeedback';
import PracticeResults from '@site/src/components/PracticeResults';
import EmptyBank from '@site/src/components/EmptyBank';

/** Enough entropy to separate two series, with no dependency to add. */
function newSessionId(): string {
  return `ps-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 8)}`;
}

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
  // One id per series, so the report reads back exactly the attempts of THIS
  // run rather than every practice attempt ever recorded.
  const [sessionId, setSessionId] = useState(() => newSessionId());
  const [finished, setFinished] = useState(false);

  const all: Question[] = state.status === 'ready' ? state.payload.questions : [];
  const items: Record<string, ItemIndexEntry> =
    state.status === 'ready' ? (state.payload.items ?? {}) : {};

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
    startSeries();
  }

  function startSeries(): void {
    setIndex(0);
    setAnswered(null);
    setFinished(false);
    setSessionId(newSessionId());
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
      session_id: sessionId,
    });
    setAnswered(chosen);
  }

  function finish(): void {
    const answeredAttempts = practiceAttempts(sessionId);
    recordPracticeSession({
      mode: 'practice',
      session_id: sessionId,
      question_count: queue.length,
      answered: new Set(answeredAttempts.map((a) => a.question_id)).size,
      correct: answeredAttempts.filter((a) => a.correct).length,
      order: queue.map((q) => q.id),
      finished_at: new Date().toISOString(),
    });
    setFinished(true);
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

              {finished && (
                <PracticeResults
                  questions={queue}
                  items={items}
                  attempts={practiceAttempts(sessionId)}
                  onRestart={() => {
                    setRound((r) => r + 1);
                    startSeries();
                  }}
                />
              )}

              {!finished && queue.length > 0 && index >= queue.length && (
                <div>
                  <p role="status">Série terminée.</p>
                  <div className="certpath-actions">
                    <button
                      type="button"
                      className="button button--primary"
                      onClick={finish}>
                      Voir mon bilan
                    </button>
                  </div>
                </div>
              )}

              {!finished && question && (
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

                  {/* Mounted only once an answer exists: before submission the
                      correction is absent from the DOM entirely, not hidden. */}
                  {answered && (
                    <PracticeFeedback
                      question={question}
                      chosen={answered}
                      item={items[question.official_item]}
                      isLast={index + 1 >= queue.length}
                      onNext={() => {
                        if (index + 1 >= queue.length) {
                          finish();
                          return;
                        }
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
          <Link to="/docs">En savoir plus</Link>.
        </p>
      </main>
    </Layout>
  );
}
