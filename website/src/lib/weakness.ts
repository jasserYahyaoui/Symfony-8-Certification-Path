/**
 * Mock 5's selection — Master Plan §10, "generated from demonstrated weak
 * outcomes".
 *
 * The rule that shapes everything here: a weakness must be *demonstrated*.
 * Nothing in this file may manufacture one. When the evidence is too thin the
 * caller is told so and no sitting is produced — a mock built from invented
 * weaknesses would be worse than no mock, because the learner would revise the
 * wrong things believing the tool had measured them.
 */
import type {Attempt} from './storage';
import {readState} from './storage';
import type {Question} from './types';

export interface WeaknessSignals {
  /** Atomic items with at least one recorded failure, strongest weakness first. */
  items: string[];
  weakTopics: string[];
  weakestSkill: string | null;
  englishFailureRatio: number | null;
}

export interface Selection {
  questions: Question[];
  signals: WeaknessSignals;
}

const MINIMUM_ITEMS = 10;
const MAXIMUM_QUESTIONS = 40;
const TIME_MARGIN = 1.15;

/**
 * A failure counts more when it is recent and when it has been repeated, and a
 * later success on the same item counts against it. Recency is a half-life of
 * 30 days rather than a cut-off, so an old failure fades instead of vanishing.
 */
function score(attempts: Attempt[], now: number): number {
  let value = 0;

  for (const attempt of attempts) {
    const ageDays = (now - Date.parse(attempt.answered_at)) / 86_400_000;
    const recency = Number.isFinite(ageDays) ? Math.pow(0.5, Math.max(ageDays, 0) / 30) : 0.5;
    value += attempt.correct ? -0.5 * recency : recency;
  }

  return value;
}

export function weaknessSignals(questionsById: Map<string, Question>, now = Date.now()): WeaknessSignals {
  const state = readState();

  const byItem = new Map<string, Attempt[]>();
  const bySkill = new Map<string, {asked: number; wrong: number}>();
  const byTopic = new Map<string, {asked: number; wrong: number}>();
  let englishAsked = 0;
  let englishWrong = 0;

  for (const attempt of state.attempts) {
    const bucket = byItem.get(attempt.official_item) ?? [];
    bucket.push(attempt);
    byItem.set(attempt.official_item, bucket);

    const question = questionsById.get(attempt.question_id);
    if (!question) {
      continue;
    }

    const skill = bySkill.get(question.exam_skill) ?? {asked: 0, wrong: 0};
    skill.asked += 1;
    skill.wrong += attempt.correct ? 0 : 1;
    bySkill.set(question.exam_skill, skill);

    const topic = byTopic.get(question.official_topic) ?? {asked: 0, wrong: 0};
    topic.asked += 1;
    topic.wrong += attempt.correct ? 0 : 1;
    byTopic.set(question.official_topic, topic);

    if (question.language === 'en') {
      englishAsked += 1;
      englishWrong += attempt.correct ? 0 : 1;
    }
  }

  const scored: Array<{item: string; value: number}> = [];
  for (const [item, attempts] of byItem) {
    const value = score(attempts, now);
    if (value > 0) {
      scored.push({item, value});
    }
  }
  scored.sort((a, b) => b.value - a.value);

  const weakestSkill =
    [...bySkill.entries()]
      .filter(([, s]) => s.wrong > 0)
      .sort((a, b) => b[1].wrong / b[1].asked - a[1].wrong / a[1].asked)[0]?.[0] ?? null;

  return {
    items: scored.map((entry) => entry.item),
    weakTopics: [...byTopic.entries()]
      .filter(([, t]) => t.wrong > 0)
      .sort((a, b) => b[1].wrong / b[1].asked - a[1].wrong / a[1].asked)
      .map(([topic]) => topic),
    weakestSkill,
    englishFailureRatio: englishAsked > 0 ? englishWrong / englishAsked : null,
  };
}

/**
 * Returns null when the evidence is too thin. That is the whole contract: the
 * caller must then show the fallback rather than a sitting, and the fallback
 * must not claim to be weakness-based.
 */
export function selectWeaknessSitting(
  pool: Question[],
  now = Date.now(),
): Selection | null {
  const byId = new Map(pool.map((q) => [q.id, q]));
  const signals = weaknessSignals(byId, now);

  if (signals.items.length < MINIMUM_ITEMS) {
    return null;
  }

  const byItem = new Map<string, Question[]>();
  for (const question of pool) {
    const bucket = byItem.get(question.official_item) ?? [];
    bucket.push(question);
    byItem.set(question.official_item, bucket);
  }

  const questions: Question[] = [];
  for (const item of signals.items) {
    if (questions.length >= MAXIMUM_QUESTIONS) {
      break;
    }

    // One question per weak item, and never one the learner has already been
    // asked: repeating a remembered question measures recall of the paper.
    const answered = new Set(readState().attempts.map((a) => a.question_id));
    const candidates = (byItem.get(item) ?? []).filter((q) => !answered.has(q.id));
    const chosen = candidates[Math.floor(Math.random() * candidates.length)];

    if (chosen) {
      questions.push(chosen);
    }
  }

  // The selection is bounded by the evidence, so it can come out shorter than
  // the minimum even when enough items are weak — every candidate may already
  // have been served. That is still not a reason to pad it.
  if (questions.length < MINIMUM_ITEMS) {
    return null;
  }

  return {questions, signals};
}

export function durationMinutes(questions: Question[]): number {
  const seconds = questions.reduce((total, q) => total + q.estimated_time_seconds, 0);

  return Math.ceil((seconds * TIME_MARGIN) / 60);
}

export const WEAKNESS_MINIMUM_ITEMS = MINIMUM_ITEMS;
export const WEAKNESS_MAXIMUM_QUESTIONS = MAXIMUM_QUESTIONS;
