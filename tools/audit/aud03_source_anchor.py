"""AUD-03 — Source and anchor audit (Master Plan §14, §2.2–§2.4; §22 clause 5).

Version contamination (AUD-02) asks whether a source points at the right
version. This audit asks the three questions that come before that:

  does every scored or taught record cite a source at all,
  does every citation carry an anchor a reader can follow,
  and does every cited URL actually resolve?

Reachability is checked live against the network unless --offline is passed,
in which case that check reports NOT VERIFIED rather than passing silently.
"""
import concurrent.futures
import datetime as dt
import sys
import urllib.request

sys.path.insert(0, __file__.rsplit('/', 1)[0])
import collect  # noqa: E402

OFFLINE = '--offline' in sys.argv

findings = []
counts = {}


def note(check, detail):
    findings.append((check, detail))


def bump(k, n=1):
    counts[k] = counts.get(k, 0) + n


records = []
for q in collect.questions():
    records.append(('question', q.get('id'), q['_file'], collect.sources_of(q)))
for fm, _ in collect.courses():
    records.append(('course', fm.get('id'), fm['_file'], collect.sources_of(fm)))
for c in collect.flashcards():
    records.append(('flashcard', c.get('id'), c['_file'], collect.sources_of(c)))

# The reference point must be the real current date. It was pinned to
# 2026-09-07 when this audit was written, which made ANCHOR-7 report every
# citation verified after that day as "in the future" - six false positives
# on the first content unit that followed, and a growing number after that.
# A check whose reference point is frozen stops measuring what it claims to.
TODAY = dt.date.today()
urls = set()

for kind, rid, fname, srcs in records:
    bump(f'{kind}s')
    if not srcs:
        note('ANCHOR-1 no source at all', f'{kind} {rid} ({fname})')
        continue
    bump(f'{kind} citations', len(srcs))
    for s in srcs:
        url = (s.get('url') or '').strip()
        if not url:
            note('ANCHOR-2 citation without a url', f'{kind} {rid} ({fname})')
            continue
        urls.add(url)
        if not url.startswith('https://'):
            note('ANCHOR-3 non-https source', f'{kind} {rid} ({fname}): {url}')
        # §2.4's contract, as SourceRef::hasAnchor() implements it: either
        # field satisfies it. Reading only symbol_or_lines reported every
        # `anchor:`-style citation as unanchored.
        anchor = ((s.get('symbol_or_lines') or '') or (s.get('anchor') or '')).strip()
        if not anchor:
            note('ANCHOR-4 citation without an anchor',
                 f'{kind} {rid} ({fname}): {url} — no symbol_or_lines, so the '
                 f'claim cannot be located in the source')
        v = s.get('verified_at')
        if not v:
            note('ANCHOR-5 citation without verified_at', f'{kind} {rid} ({fname}): {url}')
        else:
            try:
                when = v if isinstance(v, dt.date) else dt.date.fromisoformat(str(v))
            except ValueError:
                note('ANCHOR-6 unparseable verified_at', f'{kind} {rid} ({fname}): {v!r}')
            else:
                if when > TODAY:
                    note('ANCHOR-7 verified_at in the future',
                         f'{kind} {rid} ({fname}): {when}')

bump('distinct urls', len(urls))


def head(url):
    req = urllib.request.Request(url, method='GET')
    try:
        with urllib.request.urlopen(req, timeout=45) as r:
            return url, r.status, None
    except Exception as exc:  # noqa: BLE001 — any failure is a finding
        return url, None, str(exc)[:120]


if OFFLINE:
    print('reachability: NOT VERIFIED (--offline)')
else:
    with concurrent.futures.ThreadPoolExecutor(max_workers=8) as pool:
        for url, status, err in pool.map(head, sorted(urls)):
            if status == 200:
                bump('urls returning 200')
            else:
                note('ANCHOR-8 source does not resolve',
                     f'{url} → {status or "no response"} {err or ""}')

def report(title):
    print(title)
    print('=' * 60)
    for k in sorted(counts):
        print(f'  {k:44s} {counts[k]}')
    print()
    if not findings:
        print('FINDINGS: 0')
        return 0
    # Grouped by check, so a noisy check can never truncate a quiet one out of
    # the report. A dead source hidden behind 103 missing anchors is exactly
    # the failure this format exists to prevent.
    by_check = {}
    for check, detail in findings:
        by_check.setdefault(check, []).append(detail)
    print(f'FINDINGS: {len(findings)} across {len(by_check)} checks')
    for check in sorted(by_check):
        rows = by_check[check]
        print(f'\n  [{check}] {len(rows)}')
        for detail in rows[:12]:
            print(f'      {detail}')
        if len(rows) > 12:
            print(f'      … and {len(rows) - 12} more of this check')
    return 1


sys.exit(report('AUD-03 — source and anchor audit'))
