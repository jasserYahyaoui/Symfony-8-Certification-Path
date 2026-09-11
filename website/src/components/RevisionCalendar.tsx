import React, {useMemo, useState} from 'react';
import {useLocation} from '@docusaurus/router';
import styles from './RevisionCalendar.module.css';
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

export default function RevisionCalendar({payload}: {payload: CalendarPayload}): React.JSX.Element {
  const dates = useMemo(() => Object.keys(payload.days).sort(), [payload.days]);
  const first = dates.length > 0 ? parseDay(dates[0]) : new Date();

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

  const examIso = payload.exam;

  function dayFor(iso: string): PlanDay | undefined {
    return payload.days[iso];
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

  return (
    <div>
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
          onPick={(date, event) => setSelected({date, event})}
        />
      )}

      {view === 'day' && (
        <DayView
          cursor={cursor}
          dayFor={dayFor}
          examIso={examIso}
          onPick={(date, event) => setSelected({date, event})}
        />
      )}

      {selected && <Details selected={selected} lotName={payload.lot_name} onClose={() => setSelected(null)} />}
    </div>
  );
}

/* --------------------------------------------------------------------- mois */

function MonthView({
  cursor,
  dayFor,
  examIso,
  onPick,
  onOpenDay,
}: {
  cursor: Date;
  dayFor: (iso: string) => PlanDay | undefined;
  examIso: string | null;
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
                className={styles.chip}
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
  onPick,
}: {
  cursor: Date;
  dayFor: (iso: string) => PlanDay | undefined;
  examIso: string | null;
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
                className={styles.event}
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
  onPick,
}: {
  cursor: Date;
  dayFor: (iso: string) => PlanDay | undefined;
  examIso: string | null;
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
        {day.events.map((event, i) => (
          <li key={i} className={styles.dayRow}>
            <span className={styles.dayTime}>
              {event.start}–{event.end}
            </span>
            <div className={styles.dayCard} style={{borderLeftColor: COLOR[event.kind]}}>
              <span className={styles.tag} style={{background: COLOR[event.kind]}}>
                {KIND_LABEL[event.kind]}
              </span>
              <span className={styles.dayCardTitle}>{event.title}</span>
              <p className={styles.dayCardMeta}>
                {event.objective} — {formatDuration(event.minutes)}
              </p>
              <button type="button" className="button button--sm button--outline button--secondary" onClick={() => onPick(iso, event)}>
                Détail
              </button>
            </div>
          </li>
        ))}
      </ul>
    </>
  );
}

/* ------------------------------------------------------------------- détail */

function Details({
  selected,
  lotName,
  onClose,
}: {
  selected: Selected;
  lotName: Record<string, string>;
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

      <button type="button" className="button button--sm button--secondary" onClick={onClose}>
        Fermer le détail
      </button>
    </section>
  );
}
