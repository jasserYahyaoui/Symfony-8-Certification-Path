export type AnswerMode = 'single' | 'multiple';

export interface Choice {
  id: string;
  text: string;
  correct: boolean;
  explanation: string | null;
}

export interface SourceRef {
  url: string;
  anchor: string | null;
}

export interface Question {
  id: string;
  version: number;
  official_topic: string;
  official_item: string;
  domain: string;
  subtopic: string | null;
  language: 'fr' | 'en';
  difficulty: string;
  cognitive_level: string;
  exam_skill: string;
  answer_mode: AnswerMode;
  required_answer_count: number;
  question: string;
  code_language: string | null;
  shuffle_choices: boolean;
  negative_wording: boolean;
  estimated_time_seconds: number;
  scoring_policy: string;
  choices: Choice[];
  explanation: string;
  official_sources: SourceRef[];
  tags: string[];
}

export interface Payload {
  generated_at: string;
  pool: 'LEARNING' | 'VALIDATION' | 'HOLDOUT';
  questions: Question[];
  /**
   * Present on practice.json since Lot 27 and on every mock payload. Exam Mode
   * does not carry it, so it stays optional rather than forcing a cast at the
   * one call site that has no index.
   */
  items?: Record<string, ItemIndexEntry>;
}

/** An atomic official item, with what the learner is meant to be able to do. */
export interface ItemIndexEntry {
  official_item: string;
  official_topic: string;
  learning_outcomes: string[];
  /**
   * Where to revise the concept. Derived by CourseUrl at build time from the
   * same helper that writes the page, so this never points at a route the
   * build did not produce.
   */
  course_url: string;
}

/**
 * Mock 4 (§10). The official constraints travel with the payload rather than
 * being retyped in the page: 75 and 90 are facts about the exam, and a page
 * holding its own copy is a page that can drift from the blueprint.
 */
export interface MockPayload extends Payload {
  mock: string;
  question_count: number;
  duration_minutes: number;
  language: string;
  symfony: string;
  distribution_label: string;
  items: Record<string, ItemIndexEntry>;
}

/**
 * A training mock (Mocks 1, 2 and 3 — §10). The payload carries the eligible
 * pool; `question_count` is the sitting drawn from it, and `topic_spread` says
 * how many per official topic. Both are INTERNAL_TRAINING_FORMAT: §10 fixes a
 * count and a duration for Mock 4 only.
 */
export interface TrainingMockPayload extends Payload {
  mock: string;
  purpose: string;
  question_count: number;
  duration_minutes: number;
  language: string;
  format_label: string;
  distribution_label: string;
  not_official: string;
  scoring_policy: string;
  topic_spread: Record<string, number>;
  items: Record<string, ItemIndexEntry>;
}
