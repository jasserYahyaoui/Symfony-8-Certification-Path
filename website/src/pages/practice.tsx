import React, {useEffect, useMemo, useState} from 'react';
import Layout from '@theme/Layout';
import Link from '@docusaurus/Link';
import {useLocation} from '@docusaurus/router';
import type {ItemIndexEntry, Question} from '@site/src/lib/types';
import {
  isCorrect,
  practiceAttempts,
  rateReasoning,
  readState,
  recordAttempt,
  recordPracticeSession,
  shuffle,
  weakQuestionIds,
} from '@site/src/lib/storage';
import usePayload from '@site/src/lib/usePayload';
import {lotOf, parallelReplay, unseenMixedSeries} from '@site/src/lib/exposure';
import QuestionCard from '@site/src/components/QuestionCard';
import PracticeFeedback from '@site/src/components/PracticeFeedback';
import ReasoningPrompt from '@site/src/components/ReasoningPrompt';
import PracticeResults from '@site/src/components/PracticeResults';
import EmptyBank from '@site/src/components/EmptyBank';

/** Enough entropy to separate two series, with no dependency to add. */
function newSessionId(): string {
  return `ps-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 8)}`;
}

type Series = '' | 'inedite' | 'parallele';

interface Filters {
  topic: string;
  difficulty: string;
  language: string;
  weakOnly: boolean;
}

/**
 * Remediation P3.1: a difficulty here is the author's estimate. No learner
 * data measures it yet (item statistics: MISSING), so the page never calls it
 * a measured or certified difficulty.
 */
const DIFFICULTY_LABEL: Record<string, string> = {easy: 'facile', medium: 'moyenne', hard: 'difficile'};

/** A per-viewer convenience: whether « écrire mon raisonnement » stays on. */
const EXPLAIN_KEY = 'certpath.explain-first';

/**
 * Practice Mode, Master Plan §9.1: un-timed, answer submitted before feedback,
 * explanation and sources shown afterwards, filtering by topic/domain/difficulty,
 * weakness replay, and strict separation from the holdout pool.
 */
export default function Practice(): React.JSX.Element {
  const state = usePayload('practice.json');
  // `?item=OIT-…` narrows the series to one atomic official item. It is how a
  // course page hands the learner its own questions: before 2026-09-17 the only
  // way in was this page's mixed stream of every LEARNING question in the bank,
  // so finishing a course led nowhere.
  const search = new URLSearchParams(useLocation().search);
  const requestedItem = search.get('item') ?? '';
  // Remediation P3.2 (docs/policy/first-exposure.md): two series of first
  // exposures only. `inedite` deals never-answered questions lot by lot;
  // `parallele` serves, for each weak point, another question on the same
  // outcome or item. An `?item=` narrowing wins: it names what to practise.
  const requestedSeries = search.get('serie');
  const series: Series = requestedItem === '' && (requestedSeries === 'inedite' || requestedSeries === 'parallele') ? requestedSeries : '';
  const [filters, setFilters] = useState<Filters>({
    topic: '',
    difficulty: '',
    language: '',
    weakOnly: false,
  });
  const [index, setIndex] = useState(0);
  const [answered, setAnswered] = useState<string[] | null>(null);
  // Remediation P3.2: the reasoning asked before the correction. null until
  // given for the current question; '' when the learner could not explain.
  const [explainFirst, setExplainFirst] = useState(false);
  const [reasoning, setReasoning] = useState<string | null>(null);

  useEffect(() => {
    try {
      setExplainFirst(window.localStorage.getItem(EXPLAIN_KEY) === '1');
    } catch {
      // Storage refused (private mode): the option simply starts off.
    }
  }, []);

  function toggleExplainFirst(on: boolean): void {
    setExplainFirst(on);
    try {
      window.localStorage.setItem(EXPLAIN_KEY, on ? '1' : '0');
    } catch {
      // Not remembered, still applied for this visit.
    }
  }
  const [round, setRound] = useState(0);
  // One id per series, so the report reads back exactly the attempts of THIS
  // run rather than every practice attempt ever recorded.
  const [sessionId, setSessionId] = useState(() => newSessionId());
  const [finished, setFinished] = useState(false);

  const all: Question[] = state.status === 'ready' ? state.payload.questions : [];
  const items: Record<string, ItemIndexEntry> =
    state.status === 'ready' ? (state.payload.items ?? {}) : {};

  // Computed once per series, like the queue: answering a question makes it
  // seen, and a series recomputed after each answer would change under the
  // learner's feet.
  const replay = useMemo(
    () => (series === 'parallele' && all.length > 0 ? parallelReplay(all, readState().attempts) : null),
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [all, series, round],
  );

  const queue = useMemo(() => {
    if (series === 'inedite') {
      return unseenMixedSeries(all, (q) => lotOf(items[q.official_item]?.course_url ?? ''), readState().attempts);
    }
    if (series === 'parallele') {
      return replay?.questions ?? [];
    }
    const weak = filters.weakOnly ? weakQuestionIds() : null;

    return shuffle(
      all.filter((q) => {
        if (requestedItem && q.official_item !== requestedItem) return false;
        if (filters.topic && q.official_topic !== filters.topic) return false;
        if (filters.difficulty && q.difficulty !== filters.difficulty) return false;
        if (filters.language && q.language !== filters.language) return false;
        if (weak && !weak.has(q.id)) return false;
        return true;
      }),
    );
    // `round` re-shuffles deliberately when the learner restarts a series.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [all, filters, requestedItem, round, series, replay]);

  const seriesLots = useMemo(
    () => new Set(queue.map((q) => lotOf(items[q.official_item]?.course_url ?? ''))).size,
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [queue],
  );

  // Moving between series by link keeps the page mounted: start afresh.
  useEffect(() => {
    startSeries();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [requestedItem, series]);

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
    setReasoning(null);
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
      ...(series !== '' ? {series} : {}),
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

        {requestedItem === '' && series === '' && (
          <p>
            Pour mesurer ce que vous savez sans le souvenir d'une correction :{' '}
            <Link to="/practice?serie=inedite">série inédite</Link> (dix questions
            jamais répondues, lots mêlés) ou{' '}
            <Link to="/practice?serie=parallele">forme parallèle de mes points faibles</Link>{' '}
            (une autre question sur chaque notion manquée).
          </p>
        )}

        {state.status === 'loading' && <p role="status">Chargement…</p>}

        {state.status === 'error' && (
          <p role="status">Impossible de charger les questions : {state.message}</p>
        )}

        {state.status === 'ready' && all.length === 0 && <EmptyBank mode="practice" />}

        {state.status === 'ready' && requestedItem !== '' && (
          /* The series is narrowed. Say so, name what to, and offer the way
             back: a filter the learner cannot see is a filter they will blame
             the bank for. `role="status"` because the narrowing is the result
             of their navigation, announced once on arrival. */
          <p role="status" className="certpath-scope">
            {items[requestedItem] ? (
              <>
                Série restreinte à l'item{' '}
                <strong>{items[requestedItem].official_item}</strong> —{' '}
                {queue.length} question{queue.length > 1 ? 's' : ''}.{' '}
                <Link to={items[requestedItem].course_url}>Revenir au cours</Link>
                {' · '}
                <Link to="/practice">Toutes les questions</Link>
              </>
            ) : (
              <>
                Aucun item ne porte l'identifiant <code>{requestedItem}</code>, donc
                aucune question ne correspond.{' '}
                <Link to="/practice">Voir toutes les questions</Link>
              </>
            )}
          </p>
        )}

        {state.status === 'ready' && all.length > 0 && series === 'inedite' && (
          /* Nothing here names a lot, an item or a topic of the series: the
             learner meets each question as at the exam, without being told
             which notion, or which trap, it is about. */
          <p role="status" className="certpath-scope">
            {queue.length > 0 ? (
              <>
                Série inédite — {queue.length} question{queue.length > 1 ? 's' : ''} que vous
                n'avez jamais répondue{queue.length > 1 ? 's' : ''} dans leur version actuelle,
                tirée{queue.length > 1 ? 's' : ''} de {seriesLots} lot{seriesLots > 1 ? 's' : ''},
                jamais deux fois de suite du même lot tant qu'un autre en offre une. Le sujet et
                le cours n'apparaissent qu'avec la correction.{' '}
              </>
            ) : (
              <>
                Vous avez répondu à toutes les questions de Practice dans leur version actuelle :
                aucune n'est inédite.{' '}
              </>
            )}
            <Link to="/practice">Toutes les questions</Link>
          </p>
        )}

        {state.status === 'ready' && all.length > 0 && series === 'parallele' && replay && (
          <p role="status" className="certpath-scope">
            {replay.weak === 0 ? (
              <>
                Aucun point faible : la dernière réponse donnée à chaque question est juste, ou
                aucune n'a encore été donnée.{' '}
              </>
            ) : (
              <>
                Forme parallèle — {replay.weak} point{replay.weak > 1 ? 's' : ''} faible
                {replay.weak > 1 ? 's' : ''} (question dont la dernière réponse est fausse) ;{' '}
                {queue.length} question{queue.length > 1 ? 's' : ''} jamais répondue
                {queue.length > 1 ? 's' : ''}, une par point faible, jamais celle manquée :{' '}
                {[...replay.stands_for.values()].filter((s) => s.match === 'outcome').length} sur
                le même résultat d'apprentissage,{' '}
                {[...replay.stands_for.values()].filter((s) => s.match === 'item').length} sur le
                même item faute de mieux.
                {replay.uncovered > 0 &&
                  ` ${replay.uncovered} point${replay.uncovered > 1 ? 's' : ''} faible${replay.uncovered > 1 ? 's' : ''} sans question inédite restante.`}{' '}
              </>
            )}
            <Link to="/practice">Toutes les questions</Link>
          </p>
        )}

        {state.status === 'ready' && all.length > 0 && (
          <>
            <section aria-labelledby="filters-heading">
              <h2 id="filters-heading">{series === '' ? 'Filtres' : 'Option'}</h2>
              <div className="certpath-filters">
                {series === '' && (
                  <>
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
                      <label htmlFor="filter-difficulty">Difficulté estimée</label>
                      <select
                        id="filter-difficulty"
                        value={filters.difficulty}
                        onChange={(e) => updateFilter('difficulty', e.target.value)}>
                        <option value="">Toutes</option>
                        {difficulties.map((d) => (
                          <option key={d} value={d}>
                            {DIFFICULTY_LABEL[d] ?? d}
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
                  </>
                )}

                <div className="certpath-field">
                  <label htmlFor="option-explain">
                    <input
                      type="checkbox"
                      id="option-explain"
                      checked={explainFirst}
                      onChange={(e) => toggleExplainFirst(e.target.checked)}
                    />{' '}
                    Écrire mon raisonnement avant la correction
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
                  firstExposure={series !== ''}
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
                  {answered && explainFirst && reasoning === null && (
                    <ReasoningPrompt
                      key={question.id}
                      onDone={(text) => {
                        setReasoning(text);
                        if (text === '') {
                          rateReasoning(sessionId, question.id, 'skipped');
                        }
                      }}
                    />
                  )}

                  {answered && (!explainFirst || reasoning !== null) && (
                    <PracticeFeedback
                      question={question}
                      chosen={answered}
                      item={items[question.official_item]}
                      isLast={index + 1 >= queue.length}
                      reasoning={explainFirst ? (reasoning ?? '') : undefined}
                      onRateReasoning={(rating) => rateReasoning(sessionId, question.id, rating)}
                      onNext={() => {
                        if (index + 1 >= queue.length) {
                          finish();
                          return;
                        }
                        setIndex((i) => i + 1);
                        setAnswered(null);
                        setReasoning(null);
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
