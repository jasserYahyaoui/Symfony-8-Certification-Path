/**
 * First exposure and parallel forms (remediation P3.2,
 * docs/policy/first-exposure.md).
 *
 * A first exposure is the first answer the learner gives to a question as it
 * stands — in any mode, since an exam series serves LEARNING questions too. A
 * rewritten question (a new version, P3.3) is a new question. A right answer
 * on a second exposure says as much about the memory of that question, and of
 * its correction, as about the notion.
 *
 * Two series are built here, both of first exposures only:
 *
 * - the « série inédite »: questions never answered, dealt lot by lot in turn,
 *   so two consecutive questions share a lot only when no other lot has one
 *   left. Nothing before the answer says which lot or item it comes from;
 * - the parallel replay of a weak point: instead of the missed question again,
 *   another question on the same learning outcome — or, when the bank has none,
 *   on the same atomic item — that the learner has never answered.
 *
 * Imports nothing at runtime, so website/tools/verify-exposure.mjs can run it.
 */

export const UNSEEN_SERIES_SIZE = 10;

export interface ExposableQuestion {
  id: string;
  version: number;
  official_item: string;
  assesses_outcomes?: string[];
}

export interface PriorAnswer {
  question_id: string;
  question_version: number;
  official_item: string;
  correct: boolean;
  answered_at: string;
}

/** The lot of a course URL: `/docs/courses/lot-08/…` → `lot-08`. */
export function lotOf(courseUrl: string): string {
  return courseUrl.split('/')[3] ?? '';
}

/** Question id → the highest version the learner has answered. */
export function answeredVersions(answers: readonly PriorAnswer[]): Map<string, number> {
  const seen = new Map<string, number>();
  for (const a of answers) {
    seen.set(a.question_id, Math.max(seen.get(a.question_id) ?? 0, a.question_version));
  }
  return seen;
}

/** Whether answering this question now would be a first exposure. */
export function isUnseen(question: ExposableQuestion, answered: Map<string, number>): boolean {
  return (answered.get(question.id) ?? 0) < question.version;
}

function shuffled<T>(items: readonly T[], random: () => number): T[] {
  const out = [...items];
  for (let i = out.length - 1; i > 0; i -= 1) {
    const j = Math.floor(random() * (i + 1));
    [out[i], out[j]] = [out[j], out[i]];
  }
  return out;
}

/**
 * The « série inédite »: at most `size` questions never answered, one lot
 * after another. The first round takes one question from each lot in a random
 * order, so a series of ten draws on ten lots whenever ten have one left.
 */
export function unseenMixedSeries<Q extends ExposableQuestion>(
  questions: readonly Q[],
  lotOfQuestion: (question: Q) => string,
  answers: readonly PriorAnswer[],
  size = UNSEEN_SERIES_SIZE,
  random: () => number = Math.random,
): Q[] {
  const answered = answeredVersions(answers);
  const byLot = new Map<string, Q[]>();
  for (const q of questions) {
    if (isUnseen(q, answered)) {
      const lot = lotOfQuestion(q);
      byLot.set(lot, [...(byLot.get(lot) ?? []), q]);
    }
  }

  const piles = shuffled([...byLot.keys()].sort(), random).map((lot) => shuffled(byLot.get(lot)!, random));
  const series: Q[] = [];
  while (series.length < size && piles.some((pile) => pile.length > 0)) {
    for (const pile of piles) {
      const next = series.length < size ? pile.pop() : undefined;
      if (next) {
        series.push(next);
      }
    }
  }
  return series;
}

export interface ParallelReplay<Q> {
  questions: Q[];
  /** Per question served: the missed question it stands for, and how close it is. */
  stands_for: Map<string, {missed: string; match: 'outcome' | 'item'}>;
  /** Weak points for which the bank has no unanswered question left. */
  uncovered: number;
  /** Weak points in all: questions whose latest answer was wrong. */
  weak: number;
}

/**
 * One unanswered question per weak point, never the missed one: a question
 * assessing one of the same learning outcomes when the bank has one, another
 * question on the same atomic item otherwise. A weak point is a question
 * whose latest answer was wrong, in any mode; one missed in Exam Mode is not
 * in this payload, so it can only be matched on its item.
 */
export function parallelReplay<Q extends ExposableQuestion>(
  questions: readonly Q[],
  answers: readonly PriorAnswer[],
  random: () => number = Math.random,
): ParallelReplay<Q> {
  const latest = new Map<string, PriorAnswer>();
  for (const a of answers) {
    const seen = latest.get(a.question_id);
    if (!seen || a.answered_at >= seen.answered_at) {
      latest.set(a.question_id, a);
    }
  }
  const weak = [...latest.values()]
    .filter((a) => !a.correct)
    .sort((a, b) => a.answered_at.localeCompare(b.answered_at) || a.question_id.localeCompare(b.question_id));

  const answered = answeredVersions(answers);
  const byId = new Map(questions.map((q) => [q.id, q]));
  const pool = shuffled(questions.filter((q) => isUnseen(q, answered)), random);
  const taken = new Set<string>();
  const served: Q[] = [];
  const standsFor = new Map<string, {missed: string; match: 'outcome' | 'item'}>();
  let uncovered = 0;

  for (const miss of weak) {
    const outcomes = new Set(byId.get(miss.question_id)?.assesses_outcomes ?? []);
    const free = pool.filter((q) => q.id !== miss.question_id && !taken.has(q.id));
    const byOutcome = free.find((q) => (q.assesses_outcomes ?? []).some((o) => outcomes.has(o)));
    const byItem = byOutcome ?? free.find((q) => q.official_item === miss.official_item);
    if (!byItem) {
      uncovered += 1;
      continue;
    }
    taken.add(byItem.id);
    served.push(byItem);
    standsFor.set(byItem.id, {missed: miss.question_id, match: byOutcome ? 'outcome' : 'item'});
  }

  return {questions: served, stands_for: standsFor, uncovered, weak: weak.length};
}
