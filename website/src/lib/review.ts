/**
 * The flashcard scheduler, in the browser (remediation P2.3).
 *
 * A port of `src/Review/ReviewScheduler.php`, specified in
 * docs/policy/review-algorithm.md: a plain, deterministic interval schedule
 * with no efficacy claim attached. Two implementations of one algorithm drift,
 * so `website/tools/verify-review.mjs` runs both over every sequence of up to
 * six outcomes, and a few long ones, and fails on the first difference.
 *
 * Pure: no clock, no randomness, no storage. The date arithmetic lives in the
 * caller, which knows what "today" is.
 */

export type ReviewOutcome = 'AGAIN' | 'HARD' | 'GOOD' | 'EASY';

export const OUTCOMES: readonly ReviewOutcome[] = ['AGAIN', 'HARD', 'GOOD', 'EASY'];

export interface ReviewState {
  intervalDays: number;
  ease: number;
  repetitions: number;
  lapses: number;
  dueInSession: boolean;
}

export const INITIAL_EASE = 2.5;
export const MIN_EASE = 1.3;
export const MAX_EASE = 3.0;
export const MAX_INTERVAL_DAYS = 180;
const FIRST_INTERVAL = 1;
const SECOND_INTERVAL = 3;

export function freshState(): ReviewState {
  return {intervalDays: 0, ease: INITIAL_EASE, repetitions: 0, lapses: 0, dueInSession: false};
}

/**
 * PHP's round(): half away from zero, after the same pre-rounding PHP applies
 * so that 2.675 rounds to 2.68 rather than to the 2.67 its binary value gives.
 */
function phpRound(value: number, precision = 0): number {
  const factor = 10 ** precision;
  const scaled = Number((value * factor).toPrecision(15));
  return (Math.sign(scaled) * Math.round(Math.abs(scaled))) / factor;
}

function clampEase(ease: number): number {
  return phpRound(Math.max(MIN_EASE, Math.min(MAX_EASE, ease)), 2);
}

export function next(state: ReviewState, outcome: ReviewOutcome): ReviewState {
  if (outcome === 'AGAIN') {
    // §6: "repeat in current session, then J1".
    return {
      intervalDays: FIRST_INTERVAL,
      ease: clampEase(state.ease - 0.2),
      repetitions: 0,
      lapses: state.lapses + 1,
      dueInSession: true,
    };
  }

  const [easeDelta, multiplier] =
    outcome === 'HARD' ? [-0.15, 1.2] : outcome === 'GOOD' ? [0, state.ease] : [0.15, state.ease * 1.3];
  const repetitions = state.repetitions + 1;
  const interval =
    repetitions === 1
      ? FIRST_INTERVAL
      : repetitions === 2
        ? SECOND_INTERVAL
        : Math.max(state.intervalDays + 1, phpRound(state.intervalDays * multiplier));

  return {
    intervalDays: Math.min(interval, MAX_INTERVAL_DAYS),
    ease: clampEase(state.ease + easeDelta),
    repetitions,
    lapses: state.lapses,
    dueInSession: false,
  };
}
