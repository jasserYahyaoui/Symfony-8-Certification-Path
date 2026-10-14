import type {Attempt, ExamSession} from './storage';
import type {ExamSeriesPayload, Question} from './types';

/**
 * The menu logic of /examens, kept free of React and of storage so that
 * website/tools/verify-exams.mjs can run it on hand-built histories.
 */

export interface ExamStatus {
  number: number;
  /** Most recent finished session of this exam, if any. */
  last: ExamSession | null;
}

export function modeOf(number: number): ExamSession['mode'] {
  return `blanc-${number}`;
}

/** Status of each fixed exam, from the learner's finished sessions. */
export function statuses(payload: ExamSeriesPayload, sessions: readonly ExamSession[]): ExamStatus[] {
  return payload.exams.map(({number}) => {
    const mine = sessions.filter((s) => s.mode === modeOf(number));
    return {number, last: mine.length > 0 ? mine[mine.length - 1] : null};
  });
}

/**
 * The exam to sit next: the first fixed exam never finished, in order — they
 * rise in estimated difficulty — and once all six are done, a new draw, whose
 * number follows the highest one already sat.
 */
export function recommended(payload: ExamSeriesPayload, sessions: readonly ExamSession[]): number {
  const todo = statuses(payload, sessions).find((s) => s.last === null);
  if (todo) {
    return todo.number;
  }
  return nextDrawNumber(payload, sessions);
}

export function nextDrawNumber(payload: ExamSeriesPayload, sessions: readonly ExamSession[]): number {
  let highest = payload.exams.length;
  for (const s of sessions) {
    const m = /^blanc-(\d+)$/.exec(s.mode);
    if (m) {
      highest = Math.max(highest, Number(m[1]));
    }
  }
  return highest + 1;
}

/** The fixed questions of exam `number`, in the payload's order. */
export function fixedExam(payload: ExamSeriesPayload, number: number): Question[] {
  const exam = payload.exams.find((e) => e.number === number);
  if (!exam) {
    return [];
  }
  const byId = new Map(payload.questions.map((q) => [q.id, q]));
  return exam.question_ids.map((id) => byId.get(id)).filter((q): q is Question => q !== undefined);
}

/**
 * A drawn exam (7 and up): the same topic spread, one question per item, the
 * questions the learner has met least often first, ties broken by `random`.
 * Returns the questions and how many of them the learner has already met.
 */
export function drawExam(
  payload: ExamSeriesPayload,
  attempts: readonly Attempt[],
  random: () => number = Math.random,
): {questions: Question[]; alreadySeen: number} {
  const seen = new Map<string, number>();
  for (const a of attempts) {
    seen.set(a.question_id, (seen.get(a.question_id) ?? 0) + 1);
  }
  const keyed = payload.questions.map((q) => ({q, k: random()}));
  keyed.sort((a, b) => (seen.get(a.q.id) ?? 0) - (seen.get(b.q.id) ?? 0) || a.k - b.k);

  const picked: Question[] = [];
  const items = new Set<string>();
  for (const [topic, wanted] of Object.entries(payload.topic_spread)) {
    let n = 0;
    for (const {q} of keyed) {
      if (n >= wanted) break;
      if (q.official_topic !== topic || items.has(q.official_item)) continue;
      items.add(q.official_item);
      picked.push(q);
      n += 1;
    }
  }
  const order = picked.map((q) => ({q, k: random()})).sort((a, b) => a.k - b.k).map(({q}) => q);
  return {questions: order, alreadySeen: order.filter((q) => (seen.get(q.id) ?? 0) > 0).length};
}

/** Counts of estimated difficulty in a list of questions. */
export function difficultyMix(questions: readonly Question[]): Record<string, number> {
  const mix: Record<string, number> = {easy: 0, medium: 0, hard: 0};
  for (const q of questions) {
    mix[q.difficulty] = (mix[q.difficulty] ?? 0) + 1;
  }
  return mix;
}
