/**
 * Delayed comprehension checks (remediation P3.2).
 *
 * A lot's comprehension check is taken right after reading it, when the
 * course is still in mind; that measures understanding, not what remains.
 * A delayed check asks a few of the lot's questions again later: 7 days after
 * the last answer given on the lot, then 14, then 28 once delayed checks have
 * been passed on it. The intervals are a plain doubling, like the flashcard
 * schedule (docs/policy/review-algorithm.md): no efficacy is claimed for them.
 *
 * Imports nothing at runtime, so website/tools/verify-delayed.mjs can run it.
 */
import type {ComprehensionAttempt} from './storage';

export const FIRST_DELAY_DAYS = 7;
export const SERIES_SIZE = 6;
const DAY = 86_400_000;

export interface LotSchedule {
  lot: string;
  /** The last answer given on the lot, delayed or not. */
  lastActivity: string;
  /** Days on which a delayed check of this lot was answered. */
  delayedSessions: number;
  intervalDays: number;
  /** When the next delayed check is due, as an ISO instant. */
  dueAt: string;
  due: boolean;
}

/** 7 days, then 14, then 28 — the third delayed check and later stay at 28. */
export function intervalDays(delayedSessions: number): number {
  return FIRST_DELAY_DAYS * 2 ** Math.min(delayedSessions, 2);
}

/** One schedule per lot with at least one answer, soonest due first. */
export function schedule(history: readonly ComprehensionAttempt[], now: Date): LotSchedule[] {
  const byLot = new Map<string, {last: number; days: Set<string>}>();
  for (const a of history) {
    const at = Date.parse(a.answered_at);
    if (!Number.isFinite(at)) {
      continue;
    }
    const entry = byLot.get(a.lot) ?? {last: 0, days: new Set<string>()};
    entry.last = Math.max(entry.last, at);
    if (a.delayed) {
      entry.days.add(a.answered_at.slice(0, 10));
    }
    byLot.set(a.lot, entry);
  }

  return [...byLot.entries()]
    .map(([lot, {last, days}]) => {
      const interval = intervalDays(days.size);
      const dueAt = last + interval * DAY;
      return {
        lot,
        lastActivity: new Date(last).toISOString(),
        delayedSessions: days.size,
        intervalDays: interval,
        dueAt: new Date(dueAt).toISOString(),
        due: now.getTime() >= dueAt,
      };
    })
    .sort((a, b) => a.dueAt.localeCompare(b.dueAt) || a.lot.localeCompare(b.lot));
}

/**
 * The questions a delayed check asks, at most SERIES_SIZE: first those whose
 * latest answer was wrong (oldest first), then those never answered (in the
 * author's order), then those last answered right, longest ago first.
 *
 * @param questionIds the lot's questions, in the author's order
 */
export function selectDelayed(
  questionIds: readonly string[],
  history: readonly ComprehensionAttempt[],
  size = SERIES_SIZE,
): string[] {
  const latest = new Map<string, {at: string; correct: boolean}>();
  for (const a of history) {
    const seen = latest.get(a.question_id);
    if (!seen || a.answered_at >= seen.at) {
      latest.set(a.question_id, {at: a.answered_at, correct: a.correct});
    }
  }
  const order = new Map(questionIds.map((id, i) => [id, i]));
  const byAge = (a: string, b: string): number =>
    latest.get(a)!.at.localeCompare(latest.get(b)!.at) || order.get(a)! - order.get(b)!;

  const wrong = questionIds.filter((id) => latest.get(id)?.correct === false).sort(byAge);
  const unseen = questionIds.filter((id) => !latest.has(id));
  const right = questionIds.filter((id) => latest.get(id)?.correct === true).sort(byAge);

  return [...wrong, ...unseen, ...right].slice(0, size);
}
