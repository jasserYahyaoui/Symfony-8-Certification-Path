import type {Attempt, ComprehensionAttempt} from './storage';

/**
 * What the learner's own answers show, per official topic and per channel.
 *
 * Remediation F5 (2026-10-13). Coverage and Certification Readiness describe
 * the content; nothing on the site described the learner. This does, and it
 * keeps apart what must not be added together:
 *
 * - `practice`      — LEARNING bank, corrected after each answer;
 * - `comprehension` — the end-of-lot checks, corrected after each answer;
 * - `training`      — Exam Mode and Mocks 1, 2, 3, 5: VALIDATION questions the
 *                     learner may already have met, so not an independent test;
 * - `mock4`         — the holdout, the only bank kept out of every learning
 *                     payload; its first answer is the closest thing to a
 *                     measure on unseen questions this site can offer.
 *
 * For each, the FIRST answer to a question and the LATEST are counted
 * separately: a second try on a known question measures memory of that
 * question as much as the notion.
 *
 * No pass probability is computed. The official pass mark and topic weights
 * are not published, and a figure built on them would be invented.
 *
 * Imports nothing at runtime, so website/tools/verify-observed.mjs can run it.
 */

export type Channel = 'practice' | 'comprehension' | 'training' | 'mock4';

export const CHANNELS: readonly Channel[] = ['practice', 'comprehension', 'training', 'mock4'];

/** Fewer distinct questions than this in a cell, and nothing is concluded. */
export const MIN_QUESTIONS = 5;

export interface Cell {
  /** Distinct questions answered at least once. */
  questions: number;
  /** Of those, how many were right on the first answer ever given. */
  firstCorrect: number;
  /** Of those, how many are right on the latest answer given. */
  latestCorrect: number;
  /** Questions answered more than once. */
  repeated: number;
}

export type Observed = Map<string, Record<Channel, Cell>>;

export function channelOf(mode: Attempt['mode']): Channel {
  switch (mode) {
    case 'practice':
      return 'practice';
    case 'mock':
      return 'mock4';
    default:
      return 'training';
  }
}

function emptyCell(): Cell {
  return {questions: 0, firstCorrect: 0, latestCorrect: 0, repeated: 0};
}

function emptyRow(): Record<Channel, Cell> {
  return {practice: emptyCell(), comprehension: emptyCell(), training: emptyCell(), mock4: emptyCell()};
}

interface Answer {
  channel: Channel;
  question_id: string;
  official_item: string;
  correct: boolean;
  answered_at: string;
}

/**
 * @param topicOf official item id → official topic; an item it does not know
 *                is grouped under `unknownTopic` rather than dropped silently.
 */
export function observe(
  attempts: readonly Attempt[],
  comprehension: readonly ComprehensionAttempt[],
  topicOf: (item: string) => string | undefined,
  unknownTopic = 'Item inconnu',
): Observed {
  const answers: Answer[] = [
    ...attempts.map((a) => ({...a, channel: channelOf(a.mode)})),
    ...comprehension.map((a) => ({...a, channel: 'comprehension' as Channel})),
  ];
  // Stable on ties, so two answers stamped the same millisecond keep the
  // order they were recorded in.
  const ordered = answers
    .map((a, i) => ({a, i}))
    .sort((x, y) => x.a.answered_at.localeCompare(y.a.answered_at) || x.i - y.i)
    .map(({a}) => a);

  const perQuestion = new Map<string, {topic: string; channel: Channel; first: boolean; latest: boolean; count: number}>();
  for (const a of ordered) {
    const key = `${a.channel}|${a.question_id}`;
    const seen = perQuestion.get(key);
    if (seen) {
      seen.latest = a.correct;
      seen.count += 1;
    } else {
      perQuestion.set(key, {
        topic: topicOf(a.official_item) ?? unknownTopic,
        channel: a.channel,
        first: a.correct,
        latest: a.correct,
        count: 1,
      });
    }
  }

  const out: Observed = new Map();
  for (const q of perQuestion.values()) {
    const row = out.get(q.topic) ?? emptyRow();
    const cell = row[q.channel];
    cell.questions += 1;
    cell.firstCorrect += q.first ? 1 : 0;
    cell.latestCorrect += q.latest ? 1 : 0;
    cell.repeated += q.count > 1 ? 1 : 0;
    out.set(q.topic, row);
  }

  return out;
}

export function enough(cell: Cell): boolean {
  return cell.questions >= MIN_QUESTIONS;
}
