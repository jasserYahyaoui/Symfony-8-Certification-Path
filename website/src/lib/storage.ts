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

const STORAGE_KEY = 'certpath.learner-state';
const STORAGE_VERSION = 2;

export interface Attempt {
  question_id: string;
  question_version: number;
  official_item: string;
  correct: boolean;
  chosen: string[];
  answered_at: string;
  mode: 'practice' | 'exam' | 'mock' | 'mock-1' | 'mock-2' | 'mock-3' | 'mock-5';
}

export interface ExamSession {
  // 'mock' joined 'exam' when Mock 4 shipped. The shape is unchanged, so a
  // history written before it stays readable and needs no migration.
  mode: 'exam' | 'mock' | 'mock-1' | 'mock-2' | 'mock-3' | 'mock-5';
  question_count: number;
  correct: number;
  unanswered: number;
  elapsed_seconds: number;
  timed_out: boolean;
  finished_at: string;
}

/**
 * Agenda state: what the candidate has ticked off, and the schedule they
 * replanned to. Both live in the same record as the rest of the learner
 * state, so the export and the reset on /progression already cover them —
 * a second storage key would be a second thing to forget to erase.
 */
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
  revision: RevisionState;
}

type Migration = (state: LearnerState) => LearnerState;

/** Add `3: (state) => ...` here when the shape changes. */
const MIGRATIONS: Record<number, Migration> = {
  // The agenda arrived after the question banks. A learner returning with a
  // v1 record keeps every attempt and session; only the new slice is added.
  2: (state) => ({...state, revision: emptyRevision()}),
};

function emptyRevision(): RevisionState {
  return {done: {}, settings: null};
}

function empty(): LearnerState {
  return {schema_version: STORAGE_VERSION, attempts: [], sessions: [], revision: emptyRevision()};
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

export function recordSession(session: ExamSession): void {
  const state = readState();
  state.sessions.push(session);
  writeState(state);
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
