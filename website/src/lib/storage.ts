/**
 * Learner state, Master Plan §13.
 *
 * Everything lives in this browser's localStorage: no account, no network
 * call, no secret. Reads run the migration chain, so a learner who returns
 * after a schema change keeps their history.
 *
 * Every access is guarded: localStorage throws outright in some privacy modes,
 * and a thrown storage error must never take the page down with it.
 */
import type {Question} from './types';
import {freshState, next, type ReviewOutcome} from './review';

const STORAGE_KEY = 'certpath.learner-state';
const STORAGE_VERSION = 5;

export type ReasoningRating = 'matched' | 'partial' | 'missed' | 'skipped';

export interface Attempt {
  question_id: string;
  question_version: number;
  official_item: string;
  correct: boolean;
  chosen: string[];
  answered_at: string;
  /**
   * The practice series this attempt belongs to (Lot 27). Optional on purpose:
   * every attempt recorded before Lot 27 has none, and back-filling one would
   * invent a series that never happened.
   */
  session_id?: string;
  /**
   * Remediation P3.2: in Practice Mode with « écrire mon raisonnement » on, the
   * learner compares the reasoning written before the correction with the
   * expected one. `missed` on a right answer is a right choice for a wrong
   * reason, which Mock 5 counts as a failure. Absent when nothing was asked.
   */
  reasoning?: ReasoningRating;
  // `blanc-N` is numbered mock exam N of /examens (1 to 6 fixed, 7 and up drawn).
  mode: 'practice' | 'exam' | 'mock' | 'mock-1' | 'mock-2' | 'mock-3' | 'mock-5' | `blanc-${number}`;
}

export interface ExamSession {
  // 'mock' joined 'exam' when Mock 4 shipped. The shape is unchanged, so a
  // history written before it stays readable and needs no migration.
  mode: 'exam' | 'mock' | 'mock-1' | 'mock-2' | 'mock-3' | 'mock-5' | `blanc-${number}`;
  question_count: number;
  correct: number;
  unanswered: number;
  elapsed_seconds: number;
  timed_out: boolean;
  finished_at: string;
}

/**
 * A finished Practice Mode series (Lot 27).
 *
 * Kept apart from ExamSession on purpose: an exam sitting is timed and can time
 * out, a practice series is neither, and reusing the exam shape would mean
 * recording `timed_out: false` about a mode in which timing out is not a thing
 * that can happen. `elapsed_seconds` is absent for the same reason —
 * PER_QUESTION_TIMING_NOT_IMPLEMENTED: no per-question timing is measured
 * today, and this project does not write a number it did not measure.
 */
export interface PracticeSession {
  mode: 'practice';
  /** Stable across a series so its attempts can be grouped afterwards. */
  session_id: string;
  question_count: number;
  answered: number;
  correct: number;
  /** Question ids in the order served, so a review replays the real series. */
  order: string[];
  /**
   * Remediation P3.2: a series of first exposures only — `inedite`, never
   * answered questions dealt lot by lot, or `parallele`, another question on
   * each weak point. Absent on an ordinary series.
   */
  series?: 'inedite' | 'parallele';
  finished_at: string;
}

/**
 * Agenda state: what the candidate has ticked off, and the schedule they
 * replanned to. Both live in the same record as the rest of the learner
 * state, so the export and the reset on /progression already cover them —
 * a second storage key would be a second thing to forget to erase.
 */
/**
 * One answer given in a comprehension check (ADR-0010, addendum 2026-10-13).
 *
 * Kept in its own slice, never in `attempts`: the check corrects after each
 * question, so it is learning, not evidence. `attempts` drives the weak-point
 * replay of Practice Mode and the selection of Mock 5; a comprehension answer
 * there would let a corrected-on-the-spot answer steer an evaluation.
 */
export interface ComprehensionAttempt {
  question_id: string;
  question_version: number;
  official_item: string;
  lot: string;
  correct: boolean;
  answered_at: string;
  /** Remediation P3.2: answered in a delayed check, days after the lot was read. */
  delayed?: boolean;
}

/**
 * One flashcard's schedule for this learner (remediation P2.3).
 *
 * Written only when the learner rates their recall after reading the back —
 * opening a card is not knowing it, so a reveal records nothing. `card_hash`
 * is the card's version: if the front or back changes, the stored schedule
 * belongs to a card that no longer exists, and the card starts afresh.
 */
export interface FlashcardRecord {
  card_hash: string;
  official_item: string;
  interval_days: number;
  ease: number;
  repetitions: number;
  lapses: number;
  reviewed_at: string;
  /** Local calendar day, YYYY-MM-DD. */
  due_on: string;
  history: {outcome: ReviewOutcome; at: string}[];
}

export interface RevisionState {
  /** Event key (`date|start|title`) → ISO timestamp it was marked done. */
  done: Record<string, string>;
  /** null = the published plan, untouched. */
  settings: RevisionSettings | null;
}

export interface RevisionSettings {
  start: string;
  exam: string;
  maxNew: number;
  weekday: number;
  weekend: number;
  weekdayStart: string;
  weekendStart: string;
}

export interface LearnerState {
  schema_version: number;
  attempts: Attempt[];
  sessions: ExamSession[];
  practice_sessions: PracticeSession[];
  comprehension: ComprehensionAttempt[];
  revision: RevisionState;
  /** Card id → its schedule. A card never rated has no entry. */
  flashcards: Record<string, FlashcardRecord>;
}

type Migration = (state: LearnerState) => LearnerState;

/** Add `3: (state) => ...` here when the shape changes. */
const MIGRATIONS: Record<number, Migration> = {
  // The agenda arrived after the question banks. A learner returning with a
  // v1 record keeps every attempt and session; only the new slice is added.
  2: (state) => ({...state, revision: emptyRevision()}),
  // Lot 27: Practice Mode gained an end-of-series report, which needs a record
  // of the series. Attempts, sessions and agenda are carried through
  // untouched — a learner who has been practising for weeks keeps everything
  // and simply has no practice series recorded before this version.
  3: (state) => ({...state, practice_sessions: []}),
  // Comprehension checks start recording answers, in a slice of their own.
  // Nothing earlier is touched; a learner simply has no history before v4.
  4: (state) => ({...state, comprehension: []}),
  // Flashcards start recording a recall rating and a due day per card.
  // Nothing earlier is touched; no card has a schedule before v5.
  5: (state) => ({...state, flashcards: {}}),
};

function emptyRevision(): RevisionState {
  return {done: {}, settings: null};
}

function empty(): LearnerState {
  return {
    schema_version: STORAGE_VERSION,
    attempts: [],
    sessions: [],
    practice_sessions: [],
    comprehension: [],
    revision: emptyRevision(),
    flashcards: {},
  };
}

function migrate(raw: unknown): LearnerState {
  if (!raw || typeof raw !== 'object') {
    return empty();
  }

  let state = raw as LearnerState;
  let version = typeof state.schema_version === 'number' ? state.schema_version : 0;

  while (version < STORAGE_VERSION) {
    const migration = MIGRATIONS[version + 1];
    if (!migration) {
      return empty();
    }
    state = migration(state);
    version += 1;
    state.schema_version = version;
  }

  const revision = state.revision ?? emptyRevision();

  return {
    schema_version: STORAGE_VERSION,
    attempts: Array.isArray(state.attempts) ? state.attempts : [],
    sessions: Array.isArray(state.sessions) ? state.sessions : [],
    practice_sessions: Array.isArray(state.practice_sessions) ? state.practice_sessions : [],
    comprehension: Array.isArray(state.comprehension) ? state.comprehension : [],
    flashcards: state.flashcards && typeof state.flashcards === 'object' && !Array.isArray(state.flashcards) ? state.flashcards : {},
    revision: {
      done: revision.done && typeof revision.done === 'object' ? revision.done : {},
      settings: revision.settings ?? null,
    },
  };
}

export function readRevision(): RevisionState {
  return readState().revision;
}

export function writeRevision(revision: RevisionState): boolean {
  const state = readState();
  state.revision = revision;
  return writeState(state);
}

export function readState(): LearnerState {
  if (typeof window === 'undefined') {
    return empty();
  }

  try {
    const raw = window.localStorage.getItem(STORAGE_KEY);
    return raw ? migrate(JSON.parse(raw)) : empty();
  } catch {
    return empty();
  }
}

export function writeState(state: LearnerState): boolean {
  if (typeof window === 'undefined') {
    return false;
  }

  try {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    return true;
  } catch {
    return false;
  }
}

export function clearState(): boolean {
  if (typeof window === 'undefined') {
    return false;
  }

  try {
    window.localStorage.removeItem(STORAGE_KEY);
    return true;
  } catch {
    return false;
  }
}

export function recordAttempt(attempt: Attempt): void {
  const state = readState();
  state.attempts.push(attempt);
  writeState(state);
}

/**
 * Records how the learner judged their own reasoning against the expected one
 * (remediation P3.2), on the attempt just made for this question in this
 * series. Nothing is written if that attempt cannot be found.
 */
export function rateReasoning(sessionId: string, questionId: string, rating: ReasoningRating): void {
  const state = readState();
  for (let i = state.attempts.length - 1; i >= 0; i -= 1) {
    const attempt = state.attempts[i];
    if (attempt.session_id === sessionId && attempt.question_id === questionId) {
      attempt.reasoning = rating;
      writeState(state);
      return;
    }
  }
}

export function recordSession(session: ExamSession): void {
  const state = readState();
  state.sessions.push(session);
  writeState(state);
}

export function recordPracticeSession(session: PracticeSession): void {
  const state = readState();
  state.practice_sessions.push(session);
  writeState(state);
}

export function recordComprehension(attempt: ComprehensionAttempt): void {
  const state = readState();
  state.comprehension.push(attempt);
  writeState(state);
}

/** The local calendar day of `date`, YYYY-MM-DD — what "due today" means to the learner. */
export function localDay(date: Date): string {
  const pad = (n: number): string => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

function addDays(day: string, days: number): string {
  const [y, m, d] = day.split('-').map(Number);
  return localDay(new Date(y, m - 1, d + days));
}

/** The stored schedule of a card, or null if never rated or rated on another version of it. */
export function flashcardRecord(cardId: string, cardHash: string): FlashcardRecord | null {
  const record = readState().flashcards[cardId];
  return record && record.card_hash === cardHash ? record : null;
}

/**
 * Applies one recall rating with the scheduler of docs/policy/review-algorithm.md
 * and stores the result. A record for an older version of the card is
 * discarded: its schedule was earned on a different question.
 */
export function rateFlashcard(cardId: string, cardHash: string, officialItem: string, outcome: ReviewOutcome, now: Date = new Date()): FlashcardRecord {
  const state = readState();
  const previous = state.flashcards[cardId];
  const current = previous && previous.card_hash === cardHash ? previous : null;
  const scheduled = next(
    current
      ? {intervalDays: current.interval_days, ease: current.ease, repetitions: current.repetitions, lapses: current.lapses, dueInSession: false}
      : freshState(),
    outcome,
  );
  const record: FlashcardRecord = {
    card_hash: cardHash,
    official_item: officialItem,
    interval_days: scheduled.intervalDays,
    ease: scheduled.ease,
    repetitions: scheduled.repetitions,
    lapses: scheduled.lapses,
    reviewed_at: now.toISOString(),
    due_on: addDays(localDay(now), scheduled.intervalDays),
    history: [...(current?.history ?? []), {outcome, at: now.toISOString()}],
  };
  state.flashcards[cardId] = record;
  writeState(state);
  return record;
}

/** Forgets one card's schedule: the next rating starts it afresh. */
export function resetFlashcard(cardId: string): void {
  const state = readState();
  delete state.flashcards[cardId];
  writeState(state);
}

/**
 * Replaces the stored state with an exported one, after the same migration a
 * read applies. Refuses anything that is not an export of this site, and an
 * export from a newer schema, which this version cannot read without losing
 * what it does not know.
 */
export function importState(json: string): {ok: true} | {ok: false; reason: string} {
  let raw: unknown;
  try {
    raw = JSON.parse(json);
  } catch {
    return {ok: false, reason: "Ce texte n'est pas du JSON valide."};
  }
  if (!raw || typeof raw !== 'object' || typeof (raw as {schema_version?: unknown}).schema_version !== 'number') {
    return {ok: false, reason: "Ce JSON n'a pas de schema_version : ce n'est pas un export de ce site."};
  }
  if ((raw as {schema_version: number}).schema_version > STORAGE_VERSION) {
    return {ok: false, reason: `Cet export vient d'un schéma plus récent (${(raw as {schema_version: number}).schema_version}) que celui de cette page (${STORAGE_VERSION}).`};
  }
  return writeState(migrate(raw)) ? {ok: true} : {ok: false, reason: "Le stockage local refuse l'écriture (navigation privée ?)."};
}

/**
 * The comprehension questions of a lot whose most recent answer was wrong.
 * The latest answer wins: a notion missed then understood is no longer owed.
 */
export function missedComprehension(lot: string): Set<string> {
  const latest = new Map<string, boolean>();
  for (const attempt of readState().comprehension) {
    if (attempt.lot === lot) {
      latest.set(attempt.question_id, attempt.correct);
    }
  }
  return new Set([...latest].filter(([, correct]) => !correct).map(([id]) => id));
}

/**
 * The attempts belonging to one practice series, in the order served.
 *
 * Read back rather than held in React state so that a reload during a series
 * still produces a report — the attempts were written as they were answered.
 * The last attempt per question wins: a learner who replays a question inside
 * one series is graded on what they last did, not on their first guess.
 */
export function practiceAttempts(sessionId: string): Attempt[] {
  return readState().attempts.filter((a) => a.session_id === sessionId);
}

/** Question ids answered incorrectly at least once — drives weakness replay. */
export function weakQuestionIds(): Set<string> {
  const weak = new Set<string>();
  for (const attempt of readState().attempts) {
    if (!attempt.correct) {
      weak.add(attempt.question_id);
    }
  }
  return weak;
}

export function isCorrect(question: Question, chosen: string[]): boolean {
  const expected = question.choices.filter((c) => c.correct).map((c) => c.id);
  if (expected.length !== chosen.length) {
    return false;
  }
  const chosenSet = new Set(chosen);
  return expected.every((id) => chosenSet.has(id));
}

export function shuffle<T>(items: readonly T[]): T[] {
  const out = [...items];
  for (let i = out.length - 1; i > 0; i -= 1) {
    const j = Math.floor(Math.random() * (i + 1));
    [out[i], out[j]] = [out[j], out[i]];
  }
  return out;
}
