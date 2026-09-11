import React, {useEffect, useMemo, useState} from 'react';
import {useLocation} from '@docusaurus/router';
import Link from '@docusaurus/Link';
import styles from './RevisionCalendar.module.css';
import {readRevision, writeRevision, type RevisionSettings} from '@site/src/lib/storage';
import {reschedule, type Rescheduled} from '@site/src/lib/reschedule';
import RevisionSettingsForm from './RevisionSettingsForm';
import {
  type CalendarPayload,
  type EventKind,
  type PlanDay,
  type PlanEvent,
  KIND_LABEL,
  addDays,
  addMonths,
  dayLabel,
  formatDuration,
  hourBand,
  isoOf,
  monthLabel,
  parseDay,
  sameMonth,
  shortDayName,
  startOfMonth,
  startOfWeek,
  toMinutes,
  weekdayIndex,
} from '@site/src/lib/calendar';

const COLOR: Record<EventKind, string> = {
  NEW: 'var(--certpath-ev-new)',
  REVIEW: 'var(--certpath-ev-review)',
  LAB: 'var(--certpath-ev-lab)',
  ASSESS: 'var(--certpath-ev-assess)',
  MOCK: 'var(--certpath-ev-mock)',
  CONSOLIDATION: 'var(--certpath-ev-consolidation)',
  EXAM: 'var(--certpath-exam)',
};

/**
 * 10 minutes per grid row.
 *
 * Not an aesthetic choice: most planned sessions are 10 to 16 minutes long, so
 * at a 15-minute slot they occupied a single 1.1rem row -- about 17 pixels for
 * two lines of text. The title spilled out of its coloured block onto the slot
 * behind it and rendered white on pale grey, which the audit caught in the week
 * view and could not catch in the month view.
 */
const SLOT = 10;

/**
 * Below this, a block is one row tall and shows its time only; the title would
 * not fit and would overflow. It stays available to screen readers through the
 * hidden label, and to everyone through the detail panel.
 */
const TITLE_MIN_MINUTES = 20;

type View = 'month' | 'week' | 'day';

interface Selected {
  date: string;
  event: PlanEvent;
}

const CANONICAL = (payload: CalendarPayload): RevisionSettings => ({
  start: payload.start ?? '',
  exam: payload.exam ?? '',
  maxNew: payload.params.max_new,
  weekday: payload.params.budget['0'],
  weekend: payload.params.budget['5'],
  weekdayStart: payload.params.day_start['0'],
  weekendStart: payload.params.day_start['5'],
});

/** A slot is identified by when it happens and what it is, not by an index:
 * replanning moves slots between days, and an index-based key would carry a
 * tick from one session to a different one. */
export function eventKey(date: string, event: PlanEvent): string {
  return `${date}|${event.start}|${event.title}`;
}

export default function RevisionCalendar({payload}: {payload: CalendarPayload}): React.JSX.Element {
  const [settings, setSettings] = useState<RevisionSettings | null>(null);
  const [done, setDone] = useState<Record<string, string>>({});
  const [storageBroken, setStorageBroken] = useState(false);
  const [loaded, setLoaded] = useState(false);

  // Read after mount, never during render: the pages are prerendered, and
  // localStorage does not exist while they are.
  useEffect(() => {
    const state = readRevision();
    setSettings(state.settings);
    setDone(state.done);
    setLoaded(true);
  }, []);

  const replanned: Rescheduled | null = useMemo(() => {
    if (!settings) {
      return null;
    }
    return reschedule(payload.items, payload.order, payload.params, payload.lot_name, settings);
  }, [payload, settings]);

  const active = replanned && !replanned.error ? replanned.days : payload.days;

  const dates = useMemo(() => Object.keys(active).sort(), [active]);
  const first = dates.length > 0 ? parseDay(dates[0]) : new Date();

  function persist(next: {settings?: RevisionSettings | null; done?: Record<string, string>}): void {
    const merged = {
      settings: next.settings !== undefined ? next.settings : settings,
      done: next.done !== undefined ? next.done : done,
    };
    setStorageBroken(!writeRevision(merged));
  }

  function toggle(date: string, event: PlanEvent): void {
    const key = eventKey(date, event);
    const next = {...done};
    if (next[key]) {
      delete next[key];
    } else {
      next[key] = new Date().toISOString();
    }
    setDone(next);
    persist({done: next});
  }

  const query = new URLSearchParams(useLocation().search);
  const wantedView = query.get('view');
  const wantedDate = query.get('date');

  const [view, setView] = useState<View>(
    wantedView === 'week' || wantedView === 'day' || wantedView === 'month' ? wantedView : 'month',
  );
  const [cursor, setCursor] = useState<Date>(
    wantedDate && /^\d{4}-\d{2}-\d{2}$/.test(wantedDate) ? parseDay(wantedDate) : first,
  );
  const [selected, setSelected] = useState<Selected | null>(null);

  const examIso = settings ? settings.exam : payload.exam;

  function dayFor(iso: string): PlanDay | undefined {
    return active[iso];
  }

  function move(step: number): void {
    if (view === 'month') {
      setCursor(addMonths(cursor, step));
    } else if (view === 'week') {
      setCursor(addDays(cursor, step * 7));
    } else {
      setCursor(addDays(cursor, step));
    }
  }

  const period =
    view === 'month'
      ? monthLabel(cursor)
      : view === 'week'
        ? `semaine du ${dayLabel(startOfWeek(cursor))}`
        : dayLabel(cursor);

  const canonical = CANONICAL(payload);
  const totalEvents = Object.values(active).reduce((n, d) => n + d.events.length, 0);
  const doneCount = Object.keys(active).reduce(
    (n, date) => n + active[date].events.filter((e) => done[eventKey(date, e)]).length,
    0,
  );

  return (
    <div>
      {loaded && (
        <RevisionSettingsForm
          value={settings ?? canonical}
          canonical={canonical}
          error={replanned?.error ?? null}
          onApply={(next) => {
            setSettings(next);
            persist({settings: next});
          }}
          onReset={() => {
            setSettings(null);
            persist({settings: null});
          }}
        />
      )}

      {storageBroken && (
        <div className="alert alert--warning" role="alert">
          Le stockage local de ce navigateur est indisponible : les cases cochées et vos réglages ne
          seront pas conservés d'une visite à l'autre.
        </div>
      )}

      {replanned && !replanned.error && (
        <p className={styles.replanNote}>
          <strong>Plan replanifié</strong> — tous les items étudiés au{' '}
          <strong>{replanned.allItemsIn}</strong>, examen le <strong>{settings?.exam}</strong>.
          {replanned.lostMinutes > 0 && (
            <>
              {' '}
              {Object.values(replanned.lostReviews).reduce((a, b) => a + b, 0)} révisions tombent
              après l'examen et ne sont pas planifiées.
            </>
          )}
        </p>
      )}

      <p className={styles.progress}>
        <strong>
          {doneCount} / {totalEvents}
        </strong>{' '}
        sessions cochées.{' '}
        {doneCount > 0 && (
          <button
            type="button"
            className="button button--sm button--outline button--secondary"
            onClick={() => {
              setDone({});
              persist({done: {}});
            }}>
            Tout décocher
          </button>
        )}
      </p>

      <div className={styles.toolbar}>
        <button type="button" className="button button--secondary button--sm" onClick={() => move(-1)}>
          ◀ Précédent
        </button>
        <button type="button" className="button button--secondary button--sm" onClick={() => setCursor(first)}>
          Début du plan
        </button>
        <button type="button" className="button button--secondary button--sm" onClick={() => move(1)}>
          Suivant ▶
        </button>

        <span className={styles.spacer} />

        <span className={styles.period} aria-live="polite">
          {period}
        </span>

        <div className={styles.viewGroup} role="group" aria-label="Choisir la vue du calendrier">
          {(['month', 'week', 'day'] as View[]).map((v) => (
            <button
              key={v}
              type="button"
              className={`button button--sm ${view === v ? 'button--primary' : 'button--outline button--secondary'}`}
              aria-pressed={view === v}
              onClick={() => setView(v)}>
              {v === 'month' ? 'Mois' : v === 'week' ? 'Semaine' : 'Jour'}
            </button>
          ))}
        </div>
      </div>

      <ul className={styles.legend}>
        {(Object.keys(KIND_LABEL) as EventKind[]).map((kind) => (
          <li key={kind} className={styles.legendItem}>
            <span className={styles.swatch} style={{background: COLOR[kind]}} aria-hidden="true" />
            {KIND_LABEL[kind]}
          </li>
        ))}
      </ul>

      {view === 'month' && (
        <MonthView
          cursor={cursor}
          dayFor={dayFor}
          examIso={examIso}
          done={done}
          onPick={(date, event) => setSelected({date, event})}
          onOpenDay={(date) => {
            setCursor(parseDay(date));
            setView('day');
          }}
        />
      )}

      {view === 'week' && (
        <WeekView
          cursor={cursor}
          dayFor={dayFor}
          examIso={examIso}
          done={done}
          onPick={(date, event) => setSelected({date, event})}
        />
      )}

      {view === 'day' && (
        <DayView
          cursor={cursor}
          dayFor={dayFor}
          examIso={examIso}
          items={payload.items}
          done={done}
          onToggle={toggle}
          onPick={(date, event) => setSelected({date, event})}
        />
      )}

      {selected && (
        <Details
          selected={selected}
          lotName={payload.lot_name}
          items={payload.items}
          done={Boolean(done[eventKey(selected.date, selected.event)])}
          onToggle={() => toggle(selected.date, selected.event)}
          onClose={() => setSelected(null)}
        />
      )}
    </div>
  );
}

/* --------------------------------------------------------------------- mois */

function MonthView({
  cursor,
  dayFor,
  examIso,
  done,
  onPick,
  onOpenDay,
}: {
  cursor: Date;
  dayFor: (iso: string) => PlanDay | undefined;
  examIso: string | null;
  done: Record<string, string>;
  onPick: (date: string, event: PlanEvent) => void;
  onOpenDay: (date: string) => void;
}): React.JSX.Element {
  const monthStart = startOfMonth(cursor);
  const gridStart = startOfWeek(monthStart);

  const cells: Date[] = [];
  for (let i = 0; i < 42; i += 1) {
    cells.push(addDays(gridStart, i));
  }

  return (
    <div className={styles.monthGrid} role="list" aria-label={`Calendrier de ${monthLabel(cursor)}`}>
      {[0, 1, 2, 3, 4, 5, 6].map((i) => (
        <div key={i} className={styles.monthHead} aria-hidden="true">
          {shortDayName(i)}
        </div>
      ))}

      {cells.map((date) => {
        const iso = isoOf(date);
        const day = dayFor(iso);
        const out = !sameMonth(date, monthStart);
        const weekend = weekdayIndex(date) >= 5;
        const events = day?.events ?? [];
        const shown = events.slice(0, 3);

        return (
          <div
            key={iso}
            role="listitem"
            className={[
              styles.monthCell,
              out ? styles.monthCellOut : '',
              weekend && !out ? styles.monthCellWeekend : '',
            ].join(' ')}>
            <span className={styles.dayNumber}>
              {date.getUTCDate()}
              <span className={styles.visuallyHidden}> {dayLabel(date)}</span>
            </span>

            {iso === examIso && (
              <span className={styles.chip} style={{background: COLOR.EXAM}}>
                EXAMEN Symfony 8.0
              </span>
            )}

            {!out && !day && iso !== examIso && <span className={styles.restDay}>repos</span>}

            {shown.map((event, i) => (
              <button
                key={i}
                type="button"
                className={`${styles.chip} ${done[`${iso}|${event.start}|${event.title}`] ? styles.chipDone : ''}`}
                style={{background: COLOR[event.kind]}}
                onClick={() => onPick(iso, event)}>
                {event.start} {event.title}
              </button>
            ))}

            {events.length > shown.length && (
              <button type="button" className={styles.more} onClick={() => onOpenDay(iso)}>
                + {events.length - shown.length} autre(s)
                <span className={styles.visuallyHidden}> le {dayLabel(date)}</span>
              </button>
            )}
          </div>
        );
      })}
    </div>
  );
}

/* ------------------------------------------------------------------ semaine */

function WeekView({
  cursor,
  dayFor,
  examIso,
  done,
  onPick,
}: {
  cursor: Date;
  dayFor: (iso: string) => PlanDay | undefined;
  examIso: string | null;
  done: Record<string, string>;
  onPick: (date: string, event: PlanEvent) => void;
}): React.JSX.Element {
  const monday = startOfWeek(cursor);
  const week = [0, 1, 2, 3, 4, 5, 6].map((i) => addDays(monday, i));
  const days = week.map((d) => dayFor(isoOf(d))).filter((d): d is PlanDay => Boolean(d));
  const [fromHour, toHour] = hourBand(days);
  const rows = ((toHour - fromHour) * 60) / SLOT;

  return (
    <div className={styles.weekWrap}>
      <div
        className={styles.weekGrid}
        style={{
          gridTemplateColumns: '3.5rem repeat(7, minmax(0, 1fr))',
          gridTemplateRows: `auto repeat(${rows}, 1.5rem)`,
        }}>
        <div className={styles.weekHead} aria-hidden="true" />
        {week.map((date) => (
          <div
            key={isoOf(date)}
            className={`${styles.weekHead} ${weekdayIndex(date) >= 5 ? styles.weekHeadWeekend : ''}`}>
            {shortDayName(weekdayIndex(date))} {date.getUTCDate()}
          </div>
        ))}

        {Array.from({length: rows}).map((_, r) => {
          const minute = fromHour * 60 + r * SLOT;
          const onHour = minute % 60 === 0;
          return (
            <React.Fragment key={r}>
              <div
                className={`${styles.hourLabel} ${onHour ? styles.slotHour : ''}`}
                style={{gridColumn: 1, gridRow: r + 2}}>
                {onHour ? `${String(minute / 60).padStart(2, '0')}:00` : ''}
              </div>
              {week.map((date, c) => (
                <div
                  key={`${r}-${c}`}
                  className={[
                    styles.slot,
                    weekdayIndex(date) >= 5 ? styles.slotWeekend : '',
                    onHour ? styles.slotHour : '',
                  ].join(' ')}
                  style={{gridColumn: c + 2, gridRow: r + 2}}
                />
              ))}
            </React.Fragment>
          );
        })}

        {week.map((date, c) => {
          const iso = isoOf(date);
          const day = dayFor(iso);

          return (day?.events ?? []).map((event, i) => {
            const top = (toMinutes(event.start) - fromHour * 60) / SLOT;
            const span = Math.max(1, Math.round(event.minutes / SLOT));

            return (
              <button
                key={`${iso}-${i}`}
                type="button"
                className={`${styles.event} ${done[`${iso}|${event.start}|${event.title}`] ? styles.eventDone : ''}`}
                style={{
                  gridColumn: c + 2,
                  gridRow: `${Math.round(top) + 2} / span ${span}`,
                  background: COLOR[event.kind],
                }}
                onClick={() => onPick(iso, event)}>
                <span className={styles.eventTime}>
                  {event.start}–{event.end}
                </span>
                {event.minutes >= TITLE_MIN_MINUTES && (
                  <span className={styles.eventTitle}>{event.title}</span>
                )}
                <span className={styles.visuallyHidden}>
                  {' '}
                  {event.title}, {dayLabel(date)}, {KIND_LABEL[event.kind]},{' '}
                  {formatDuration(event.minutes)}
                </span>
              </button>
            );
          });
        })}
      </div>

      {week.some((d) => isoOf(d) === examIso) && (
        <p className={styles.milestone}>EXAMEN Symfony 8.0 — {dayLabel(parseDay(examIso as string))}</p>
      )}
    </div>
  );
}

/* --------------------------------------------------------------------- jour */

function DayView({
  cursor,
  dayFor,
  examIso,
  items,
  done,
  onToggle,
  onPick,
}: {
  cursor: Date;
  dayFor: (iso: string) => PlanDay | undefined;
  examIso: string | null;
  items: CalendarPayload['items'];
  done: Record<string, string>;
  onToggle: (date: string, event: PlanEvent) => void;
  onPick: (date: string, event: PlanEvent) => void;
}): React.JSX.Element {
  const iso = isoOf(cursor);
  const day = dayFor(iso);

  if (iso === examIso) {
    return <p className={styles.milestone}>EXAMEN Symfony 8.0 — {dayLabel(cursor)}</p>;
  }

  if (!day || day.events.length === 0) {
    return (
      <p>
        <strong>{dayLabel(cursor)}</strong> — journée de repos : aucune session n'est planifiée.
      </p>
    );
  }

  return (
    <>
      <p>
        <strong>{dayLabel(cursor)}</strong> — {formatDuration(day.used)} planifiées sur un budget de{' '}
        {formatDuration(day.budget)}.
      </p>
      <ul className={styles.dayList}>
        {day.events.map((event, i) => {
          const key = `${iso}|${event.start}|${event.title}`;
          const ticked = Boolean(done[key]);

          return (
            <li key={i} className={styles.dayRow}>
              <span className={styles.dayTime}>
                {event.start}–{event.end}
              </span>
              <div
                className={`${styles.dayCard} ${ticked ? styles.dayCardDone : ''}`}
                style={{borderLeftColor: COLOR[event.kind]}}>
                <label className={styles.tick}>
                  <input type="checkbox" checked={ticked} onChange={() => onToggle(iso, event)} />
                  <span className={styles.tag} style={{background: COLOR[event.kind]}}>
                    {KIND_LABEL[event.kind]}
                  </span>
                  <span className={styles.dayCardTitle}>{event.title}</span>
                </label>
                <p className={styles.dayCardMeta}>
                  {event.objective} — {formatDuration(event.minutes)}
                </p>
                <CourseLinks ids={event.items} items={items} />
                <button
                  type="button"
                  className="button button--sm button--outline button--secondary"
                  onClick={() => onPick(iso, event)}>
                  Détail
                </button>
              </div>
            </li>
          );
        })}
      </ul>
    </>
  );
}

/**
 * The courses a slot covers, as links.
 *
 * The href comes from `DocsGenerator`, which builds it from the same item and
 * the same slug helper that wrote the page — so a link here cannot point at a
 * route the build did not produce. An item without one is rendered as plain
 * text rather than as a link that 404s.
 */
function CourseLinks({
  ids,
  items,
  heading = false,
}: {
  ids: string[];
  items: CalendarPayload['items'];
  heading?: boolean;
}): React.JSX.Element | null {
  const known = ids.map((id) => items[id]).filter(Boolean);

  if (known.length === 0) {
    return null;
  }

  return (
    <p className={styles.links}>
      {heading && <strong>Cours à ouvrir : </strong>}
      {known.map((item, i) => (
        <React.Fragment key={i}>
          {i > 0 ? ' · ' : ''}
          {item.href ? (
            <Link to={item.href}>{item.name}</Link>
          ) : (
            <span>{item.name}</span>
          )}
        </React.Fragment>
      ))}
    </p>
  );
}

/* ------------------------------------------------------------------- détail */

function Details({
  selected,
  lotName,
  items,
  done,
  onToggle,
  onClose,
}: {
  selected: Selected;
  lotName: Record<string, string>;
  items: CalendarPayload['items'];
  done: boolean;
  onToggle: () => void;
  onClose: () => void;
}): React.JSX.Element {
  const {date, event} = selected;

  return (
    <section className={styles.panel} style={{borderLeftColor: COLOR[event.kind]}} aria-label="Détail de la session">
      <h2>{event.title}</h2>

      <dl className={styles.panelGrid}>
        <div>
          <dt>Date</dt>
          <dd>{dayLabel(parseDay(date))}</dd>
        </div>
        <div>
          <dt>Début</dt>
          <dd>{event.start}</dd>
        </div>
        <div>
          <dt>Fin</dt>
          <dd>{event.end}</dd>
        </div>
        <div>
          <dt>Durée</dt>
          <dd>{formatDuration(event.minutes)}</dd>
        </div>
        <div>
          <dt>Type</dt>
          <dd>{KIND_LABEL[event.kind]}</dd>
        </div>
        <div>
          <dt>Lot</dt>
          <dd>{event.lot ? `${event.lot} — ${lotName[event.lot] ?? ''}` : 'transverse'}</dd>
        </div>
      </dl>

      <p>
        <strong>Objectif de la session :</strong> {event.objective}
      </p>

      <CourseLinks ids={event.items} items={items} heading />

      <p>
        <label className={styles.tick}>
          <input type="checkbox" checked={done} onChange={onToggle} />
          <strong>Session faite et terminée</strong>
        </label>
      </p>

      <button type="button" className="button button--sm button--secondary" onClick={onClose}>
        Fermer le détail
      </button>
    </section>
  );
}
