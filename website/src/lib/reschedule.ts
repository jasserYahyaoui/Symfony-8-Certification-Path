/**
 * The scheduler, ported to the browser.
 *
 * `tools/revision/build_roadmap.py` remains the source of truth: it produces
 * the published plan, and CI regenerates it and fails on any diff. This port
 * exists so the candidate can change the exam date, the daily budget or the
 * start times and see the WHOLE plan reflow — spaced revisions, lot
 * assessments, mocks and all — instead of watching labels move over a fixed
 * grid.
 *
 * Two implementations of one algorithm is a divergence waiting to happen, so
 * the risk is answered rather than accepted:
 *
 *  - every constant comes from `params` in the published payload; none is
 *    retyped here;
 *  - `website/tools/verify-reschedule.mjs` runs this port over the canonical
 *    parameters and asserts the result is identical, day by day and slot by
 *    slot, to the plan Python wrote. CI runs it on every push.
 *
 * The day the two disagree, the build fails — which is the only way a second
 * implementation is safe to keep.
 */

import type {EventKind, PlanDay, PlanEvent} from './calendar';

export interface PlanItem {
  name: string;
  topic: string;
  lot: string;
  level: 'MINIMAL' | 'STANDARD' | 'DEEP';
  total: number;
  words: number;
  nq: number;
  nfc: number;
  transverse: boolean;
  href: string | null;
}

export interface PlanParams {
  max_new: number;
  budget: Record<string, number>;
  day_start: Record<string, string>;
  review: Record<string, Record<string, number>>;
  offsets: number[];
  offsets_plus: number[];
  assess_min: number;
  pause_after: number;
  pause_min: number;
  mocks: [string, string][];
}

export interface Settings {
  start: string;
  exam: string;
  maxNew: number;
  weekday: number;
  weekend: number;
  weekdayStart: string;
  weekendStart: string;
}

export interface Rescheduled {
  days: Record<string, PlanDay>;
  allItemsIn: string | null;
  lastDay: string | null;
  lostReviews: Record<string, number>;
  lostMinutes: number;
  error: string | null;
}

const DAY = 86400000;

function iso(t: number): string {
  return new Date(t).toISOString().slice(0, 10);
}

function parse(day: string): number {
  const [y, m, d] = day.split('-').map(Number);
  return Date.UTC(y, m - 1, d);
}

/** Monday-based, 0 = Monday, matching Python's `date.weekday()`. */
function weekday(t: number): number {
  return (new Date(t).getUTCDay() + 6) % 7;
}

function hhmm(minutes: number): string {
  const h = Math.floor(minutes / 60);
  const m = minutes % 60;
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
}

interface Draft {
  neu: {id: string; minutes: number; full: boolean}[];
  rev: {id: string; offset: number; minutes: number}[];
  lab: string[];
  assess: string[];
  mock: [string, string] | null;
  used: number;
  budget: number;
}

const KIND_ORDER: Record<EventKind, number> = {
  EXAM: 0,
  MOCK: 1,
  REVIEW: 2,
  NEW: 3,
  LAB: 4,
  ASSESS: 5,
  CONSOLIDATION: 6,
};

export function reschedule(
  items: Record<string, PlanItem>,
  order: string[],
  params: PlanParams,
  lotName: Record<string, string>,
  settings: Settings,
): Rescheduled {
  const budgetOf = (t: number): number => (weekday(t) >= 5 ? settings.weekend : settings.weekday);
  const startOf = (t: number): string =>
    weekday(t) >= 5 ? settings.weekendStart : settings.weekdayStart;

  const days = new Map<number, Draft>();

  const day = (t: number): Draft => {
    let d = days.get(t);
    if (!d) {
      d = {neu: [], rev: [], lab: [], assess: [], mock: null, used: 0, budget: budgetOf(t)};
      days.set(t, d);
    }
    return d;
  };

  const reviews = new Map<number, {id: string; offset: number; minutes: number}[]>();
  const queue = [...order];
  const remaining: Record<string, number> = {};
  const lotDone: Record<string, number> = {};

  let t = parse(settings.start);
  let introduced: string[] = [];
  let guard = 0;

  const pending = (): boolean => {
    for (const k of reviews.keys()) {
      if (k >= t) {
        return true;
      }
    }
    return false;
  };

  while (queue.length > 0 || pending()) {
    guard += 1;
    if (guard > 2000) {
      return {days: {}, allItemsIn: null, lastDay: null, lostReviews: {}, lostMinutes: 0,
        error: 'boucle non bornée'};
    }

    const D = day(t);
    const wd = weekday(t);

    for (const r of reviews.get(t) ?? []) {
      D.rev.push(r);
      D.used += r.minutes;
    }
    reviews.delete(t);

    if (wd === 5) {
      if (introduced.length > 0) {
        D.lab = [...introduced];
        D.used = Math.max(D.used, D.budget);
      }
      introduced = [];
    } else if (wd === 6) {
      D.used = Math.max(D.used, D.budget);
    } else {
      while (
        queue.length > 0 &&
        D.used < D.budget &&
        D.neu.filter((n) => n.full).length < settings.maxNew
      ) {
        const id = queue[0];
        const item = items[id];
        const left = remaining[id] ?? item.total;
        const free = D.budget - D.used;

        if (left <= free) {
          D.neu.push({id, minutes: left, full: left === item.total});
          D.used += left;
          delete remaining[id];
          queue.shift();
          introduced.push(id);

          const offsets = item.transverse ? params.offsets_plus : params.offsets;
          for (const off of offsets) {
            const at = t + off * DAY;
            const list = reviews.get(at) ?? [];
            list.push({id, offset: off, minutes: params.review[item.level][String(off)]});
            reviews.set(at, list);
          }

          if (queue.length === 0 || items[queue[0]].lot !== item.lot) {
            lotDone[item.lot] = t;
          }
        } else if (free >= 20) {
          D.neu.push({id, minutes: free, full: false});
          remaining[id] = left - free;
          D.used += free;
          if (!introduced.includes(id)) {
            introduced.push(id);
          }
          break;
        } else {
          break;
        }
      }
    }

    t += DAY;
  }

  const keys = (): number[] => [...days.keys()].sort((a, b) => a - b);
  let lastStudy = Math.max(...keys());
  const allItemsIn = Math.max(...Object.values(lotDone));
  const exam = parse(settings.exam);

  // Mocks: never before every lot is studied. With a fixed exam date the free
  // weekends can run short, so two share a weekend rather than eating into the
  // study period; Mock 4 stays alone and last.
  let m4 = exam - DAY;
  while (weekday(m4) !== 5) {
    m4 -= DAY;
  }

  const free: number[] = [];
  for (let d0 = allItemsIn + DAY; d0 < m4; d0 += DAY) {
    if (weekday(d0) >= 5) {
      free.push(d0);
    }
  }

  const need = params.mocks.length - 1;

  if (free.length < need) {
    return {
      days: {}, allItemsIn: iso(allItemsIn), lastDay: null, lostReviews: {}, lostMinutes: 0,
      error:
        `Pas de place : ${need} mocks à caser entre le ${iso(allItemsIn)} et le ${iso(m4)}, ` +
        `${free.length} jour(s) de week-end disponible(s). Avancez la fin des lots ` +
        `(plus d'items par jour) ou reculez la date d'examen.`,
    };
  }

  const slots = [...free.slice(free.length - need), m4];
  const mockDates: [string, number][] = [];

  params.mocks.forEach(([name, note], n) => {
    day(slots[n]).mock = [name, note];
    mockDates.push([name, slots[n]]);
  });

  // Two passes: in one, the next mock overwrote the previous one's debrief.
  for (const [name, at] of mockDates) {
    let cd = at + DAY;
    let g = 0;
    while (day(cd).mock !== null) {
      cd += DAY;
      g += 1;
      if (g > 60) {
        break;
      }
    }
    day(cd).mock = [`Correction ${name}`, 'analyse par item, plan de correction, re-révision ciblée'];
  }

  const capacity = (at: number): number => {
    const v = day(at);
    let taken =
      v.neu.reduce((s, n) => s + n.minutes, 0) +
      v.rev.reduce((s, r) => s + r.minutes, 0) +
      params.assess_min * v.assess.length;
    if (v.mock) {
      taken += v.mock[0].startsWith('Correction') ? 60 : 90;
    }
    return v.budget - taken;
  };

  // Assessments after the mocks, and deferred rather than overwritten.
  const lots = Object.entries(lotDone).sort((a, b) => a[1] - b[1] || a[0].localeCompare(b[0]));

  for (const [lot, done] of lots) {
    let s = done;
    while (weekday(s) !== 6) {
      s += DAY;
    }
    let g = 0;
    while (capacity(s) < params.assess_min) {
      s += DAY;
      g += 1;
      if (g > 400) {
        break;
      }
    }
    day(s).assess.push(lot);
  }

  // ------------------------------------------------------------- events
  const out: Record<string, PlanDay> = {};

  for (const at of keys()) {
    const v = day(at);
    const blocks: [EventKind, string, string | null, string, number, string[]][] = [];

    if (v.mock) {
      const [name, note] = v.mock;
      if (name.startsWith('Correction')) {
        blocks.push(['MOCK', name, null, note, 60, []]);
      } else {
        blocks.push(['MOCK', name, null, note, 90, []]);
      }
    }

    const byOffset = new Map<number, {id: string; minutes: number}[]>();
    for (const r of v.rev) {
      const list = byOffset.get(r.offset) ?? [];
      list.push({id: r.id, minutes: r.minutes});
      byOffset.set(r.offset, list);
    }

    for (const off of [...byOffset.keys()].sort((a, b) => a - b)) {
      const group = byOffset.get(off) as {id: string; minutes: number}[];
      const lotsHere = [...new Set(group.map((g) => items[g.id].lot))].sort();
      blocks.push([
        'REVIEW',
        `Révision espacée J+${off}`,
        lotsHere.length === 1 ? lotsHere[0] : null,
        group.map((g) => `${items[g.id].topic} : ${items[g.id].name}`).join(' · '),
        group.reduce((s, g) => s + g.minutes, 0),
        group.map((g) => g.id),
      ]);
    }

    for (const n of v.neu) {
      const item = items[n.id];
      const det: string[] = [];
      if (item.nq) {
        det.push(`${item.nq} questions`);
      }
      det.push(item.nfc ? `${item.nfc} flashcards` : 'aucune flashcard');
      blocks.push([
        'NEW',
        `${n.full ? 'Nouveau' : 'Nouveau (suite)'} — ${item.name}`,
        item.lot,
        `${item.topic} · ${item.level} · ${item.words} mots · ${det.join(', ')}`,
        n.minutes,
        [n.id],
      ]);
    }

    if (v.lab.length > 0) {
      blocks.push([
        'LAB',
        'Source tour et mise en pratique',
        null,
        `${v.lab.length} items de la semaine : ${v.lab.map((id) => items[id].name).join(' · ')}`,
        Math.max(0, v.budget - blocks.reduce((s, b) => s + b[4], 0)),
        [...v.lab],
      ]);
    }

    for (const lot of v.assess) {
      blocks.push([
        'ASSESS',
        `Assessment ${lot} — ${lotName[lot] ?? lot}`,
        lot,
        'contrôle de maîtrise, analyse des écarts, plan de correction',
        params.assess_min,
        [],
      ]);
    }

    if (weekday(at) === 6) {
      blocks.push([
        'CONSOLIDATION',
        'Consolidation et rattrapage',
        null,
        'reprendre les questions ratées de la semaine, rattraper ce qui a débordé',
        Math.max(0, v.budget - blocks.reduce((s, b) => s + b[4], 0)),
        [],
      ]);
    }

    blocks.sort((a, b) => KIND_ORDER[a[0]] - KIND_ORDER[b[0]]);

    let cursor = Number(startOf(at).slice(0, 2)) * 60;
    let sincePause = 0;
    const events: PlanEvent[] = [];

    for (const [kind, title, lot, objective, minutes, ids] of blocks) {
      if (minutes <= 0) {
        continue;
      }
      if (sincePause >= params.pause_after && kind !== 'EXAM') {
        cursor += params.pause_min;
        sincePause = 0;
      }
      events.push({
        start: hhmm(cursor),
        end: hhmm(cursor + minutes),
        minutes,
        kind,
        title,
        lot,
        objective,
        items: ids,
      });
      cursor += minutes;
      sincePause = kind === 'MOCK' ? 0 : sincePause + minutes;
    }

    out[iso(at)] = {
      events,
      milestone: null,
      used: events.reduce((s, e) => s + e.minutes, 0),
      budget: v.budget,
    };
  }

  // ------------------------------------------------------- truncation
  const lost: Record<string, number> = {};
  let lostMinutes = 0;

  for (const date of Object.keys(out)) {
    if (parse(date) < exam) {
      continue;
    }
    for (const e of out[date].events) {
      if (e.kind === 'REVIEW') {
        const off = e.title.replace('Révision espacée J+', '');
        lost[off] = (lost[off] ?? 0) + e.items.length;
        lostMinutes += e.minutes;
      }
    }
    if (parse(date) > exam) {
      delete out[date];
    }
  }

  const examIso = iso(exam);
  out[examIso] = {
    events: [],
    milestone: ['EXAMEN Symfony 8.0', "jour de l'épreuve — aucune révision n'est planifiée"],
    used: 0,
    budget: budgetOf(exam),
  };

  const kept = Object.keys(out).filter((d) => out[d].events.length > 0);
  lastStudy = kept.length > 0 ? parse(kept[kept.length - 1]) : exam;

  return {
    days: out,
    allItemsIn: iso(allItemsIn),
    lastDay: examIso,
    lostReviews: lost,
    lostMinutes,
    error: null,
  };
}

export function lastStudyDay(r: Rescheduled): string | null {
  const kept = Object.keys(r.days)
    .filter((d) => r.days[d].events.length > 0)
    .sort();
  return kept.length > 0 ? kept[kept.length - 1] : null;
}
