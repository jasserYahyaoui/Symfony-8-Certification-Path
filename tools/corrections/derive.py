#!/usr/bin/env python3
"""What a commit changed in the canonical content, by id (remediation P3.3).

docs/corrections/changelog.yml says, for each pedagogical correction, which
courses, cards and questions carry it. Those lists are not written from
memory: this script reads the commit and its parent from git and reports every
entity whose canonical form changed — course sections by heading, cards,
questions with their pool and version, matrix items and outcomes.

    python3 tools/corrections/derive.py <commit> [<commit> ...]
    python3 tools/corrections/derive.py --check
    python3 tools/corrections/derive.py --prove
    python3 tools/corrections/derive.py --versions <base>

`--check` reads the changelog and fails if an entry names an id its commit did
not change (a correction claimed where none happened), a pull request or date
that is not its commit's, or if one of those commits changed what a question
asks or expects without raising its version. `--prove` injects one defect per
rule, in memory, and fails if any goes unreported. `--versions <base>` is the
version rule alone, over base..HEAD, for every push.

A HOLDOUT question is never named: its changes are counted, by pool, and its
content is compared without being printed. A commit that touched one says so
in the count, which is the only way the changelog learns of it.
"""
from __future__ import annotations

import json
import re
import subprocess
import sys
from pathlib import Path

import yaml

ROOT = Path(__file__).resolve().parents[2]
CHANGELOG = ROOT / 'docs/corrections/changelog.yml'
MATRIX = 'docs/syllabus/syllabus-matrix.yml'
PAGES = 'docs/whats-new'
FRONT = re.compile(r'\A---\n(.*?)\n---\n(.*)\Z', re.S)


def git(*args: str) -> str:
    return subprocess.run(['git', '-C', str(ROOT), *args], check=True, capture_output=True, text=True).stdout


def files_at(rev: str, prefix: str) -> list[str]:
    out = git('ls-tree', '-r', '--name-only', rev, '--', prefix)
    return [line for line in out.splitlines() if line]


_BATCH: subprocess.Popen | None = None


def show(rev: str, path: str) -> str | None:
    """One blob through a single `git cat-file --batch`, not a process per file."""
    global _BATCH
    if _BATCH is None:
        _BATCH = subprocess.Popen(['git', '-C', str(ROOT), 'cat-file', '--batch'],
                                  stdin=subprocess.PIPE, stdout=subprocess.PIPE)
    _BATCH.stdin.write(f'{rev}:{path}\n'.encode())
    _BATCH.stdin.flush()
    header = _BATCH.stdout.readline().decode().split()
    if len(header) < 3 or header[1] == 'missing':
        return None
    data = _BATCH.stdout.read(int(header[2]))
    _BATCH.stdout.read(1)
    return data.decode()


def sections(body: str) -> dict[str, str]:
    """Course body split by `## ` heading; text before the first one is ''."""
    out: dict[str, str] = {}
    current = ''
    buf: list[str] = []
    for line in body.splitlines():
        if line.startswith('## '):
            out[current] = '\n'.join(buf).strip()
            current, buf = line[3:].strip(), []
        else:
            buf.append(line)
    out[current] = '\n'.join(buf).strip()
    return out


def snapshot(rev: str, only: set[str]) -> dict[str, dict]:
    """id -> {'kind', 'data', ...} for the canonical entities of the files in `only`.

    Only the files the commit touched are read on either side: an entity in an
    untouched file is the same on both, and one that moved between files moved
    between two touched files, so it is seen on both sides.
    """
    entities: dict[str, dict] = {}
    for path in files_at(rev, 'content/courses'):
        if path not in only or not path.endswith('.md'):
            continue
        text = show(rev, path)
        m = FRONT.match(text or '')
        if not m:
            continue
        front = yaml.safe_load(m.group(1))
        entities[front['id']] = {
            'kind': 'course', 'item': front.get('official_item'), 'title': front.get('title'),
            'front': front, 'sections': sections(m.group(2)), 'path': path,
        }
    for prefix, key, kind in (('content/flashcards', 'flashcards', 'flashcard'),
                              ('content/questions', 'questions', 'question'),
                              ('content/comprehension', 'questions', 'comprehension')):
        for path in files_at(rev, prefix):
            if path not in only or not path.endswith('.yml'):
                continue
            doc = yaml.safe_load(show(rev, path) or '') or {}
            for raw in doc.get(key) or []:
                entities[raw['id']] = {
                    'kind': kind, 'item': raw.get('official_item'), 'data': raw,
                    'pool': raw.get('pool', 'COMPREHENSION' if kind == 'comprehension' else None),
                    'version': raw.get('version', 1),
                }
    for path in files_at(rev, PAGES):
        if path in only and path.endswith('.md'):
            entities[path] = {'kind': 'page', 'item': None, 'sections': sections(show(rev, path) or '')}
    matrix = yaml.safe_load(show(rev, MATRIX) or '') or {} if MATRIX in only else {}
    for raw in matrix.get('items') or []:
        entities[raw['id']] = {'kind': 'item', 'item': raw['id'], 'data': raw}
        for outcome in raw.get('learning_outcomes') or []:
            if isinstance(outcome, dict) and 'id' in outcome:
                entities[outcome['id']] = {'kind': 'outcome', 'item': raw['id'], 'data': outcome}
    return entities


def answered(data: dict) -> tuple:
    """What an attempt answered: the prompt and the correct choices.

    A reworded distractor or explanation leaves an earlier attempt valid; a new
    prompt or a different key does not, and must come with a new version.
    """
    return (data.get('question'), data.get('answer_mode'),
            sorted((c['id'], c.get('text')) for c in data.get('choices') or [] if c.get('correct')))


def changes(commit: str, base: str | None = None) -> dict:
    """What `commit` changed against `base` (its parent by default)."""
    base = base or f'{commit}^'
    touched = set(git('diff', '--name-only', base, commit, '--', 'content', MATRIX, PAGES).splitlines())
    before, after = snapshot(base, touched), snapshot(commit, touched)
    report: dict = {'commit': git('rev-parse', '--short', commit).strip(), 'courses': {}, 'pages': {}, 'flashcards': {},
                    'questions': {}, 'items': {}, 'outcomes': {}, 'holdout_changed': 0,
                    'holdout_unversioned': 0}
    for eid in sorted(set(before) | set(after)):
        old, new = before.get(eid), after.get(eid)
        kind = (new or old)['kind']
        state = 'added' if old is None else 'removed' if new is None else None
        if kind in ('question', 'comprehension') and (new or old).get('pool') == 'HOLDOUT':
            if state or old['data'] != new['data']:
                report['holdout_changed'] += 1
            if not state and answered(old['data']) != answered(new['data']) and new['version'] <= old['version']:
                report['holdout_unversioned'] += 1
            continue
        if kind == 'page':
            if state:
                report['pages'][eid] = {'state': state}
                continue
            touched_sections = [h for h in new['sections'] if new['sections'][h] != old['sections'].get(h)]
            if touched_sections or set(old['sections']) != set(new['sections']):
                report['pages'][eid] = {'state': 'changed', 'sections': [h for h in touched_sections if h]}
        elif kind == 'course':
            if state:
                report['courses'][eid] = {'state': state, 'item': (new or old)['item']}
                continue
            touched = [h for h in new['sections'] if h and new['sections'][h] != old['sections'].get(h)]
            removed = [h for h in old['sections'] if h and h not in new['sections']]
            if touched or removed or old['front'] != new['front'] or old['sections'].get('') != new['sections'].get(''):
                report['courses'][eid] = {'state': 'changed', 'item': new['item'], 'sections': touched,
                                          'sections_removed': removed}
        elif kind == 'flashcard':
            if state or old['data'] != new['data']:
                report['flashcards'][eid] = {'state': state or 'changed', 'item': (new or old)['item']}
        elif kind in ('question', 'comprehension'):
            if state or old['data'] != new['data']:
                entry = {'state': state or 'changed', 'item': (new or old)['item'], 'pool': (new or old)['pool']}
                if not state:
                    entry['version'] = [old['version'], new['version']]
                    entry['answer_changed'] = answered(old['data']) != answered(new['data'])
                report['questions'][eid] = entry
        elif kind == 'item':
            if state or old['data'] != new['data']:
                report['items'][eid] = state or 'changed'
        elif kind == 'outcome':
            if state or old['data'] != new['data']:
                report['outcomes'][eid] = {'state': state or 'changed', 'item': (new or old)['item']}
    return report


def unversioned(derived: dict) -> list[str]:
    """Questions whose prompt or key changed without a new version."""
    out = [qid for qid, q in sorted(derived['questions'].items())
           if q.get('answer_changed') and q['version'][1] <= q['version'][0]]
    if derived.get('holdout_unversioned'):
        out.append(f'{derived["holdout_unversioned"]} HOLDOUT question(s) (ids withheld)')
    return out


def verify(log: dict, derived_for, holdout: set[str]) -> list[str]:
    """Every problem with the changelog; `derived_for(commit)` returns changes(commit)."""
    problems: list[str] = []
    seen: set[str] = set()
    for entry in log['entries']:
        commit = entry['commit']
        derived = derived_for(commit)
        where = f"{entry['id']} ({commit})"
        subject, day = git('log', '-1', '--format=%s%n%cs', commit).splitlines()
        if not subject.endswith(f"(#{entry['pull_request']})"):
            problems.append(f'{where}: the commit is not pull request #{entry["pull_request"]}: {subject}')
        if day != str(entry['date']):
            problems.append(f'{where}: committed on {day}, the entry says {entry["date"]}')
        if subprocess.run(['git', '-C', str(ROOT), 'merge-base', '--is-ancestor', commit, 'HEAD']).returncode != 0:
            problems.append(f'{where}: not an ancestor of HEAD')
        # Sections are not checked here: the entry names the headings as they
        # stand today, so its links work, and CorrectionsChangelogTest holds
        # them to the current course. A heading renamed since would fail one
        # check or the other whichever way it was written.
        for course in entry.get('courses') or []:
            if course['id'] not in derived['courses']:
                problems.append(f'{where}: {course["id"]} is not changed by this commit')
        for page in entry.get('pages') or []:
            if page['path'] not in derived['pages']:
                problems.append(f'{where}: {page["path"]} is not changed by this commit')
        for fid in entry.get('flashcards') or []:
            if fid not in derived['flashcards']:
                problems.append(f'{where}: {fid} is not changed by this commit')
        for qid in entry.get('questions') or []:
            if qid not in derived['questions']:
                shown = 'a HOLDOUT question (id withheld)' if qid in holdout else qid
                problems.append(f'{where}: {shown} is not changed by this commit')
        raised = {qid for qid in entry.get('questions') or []
                  if qid in derived['questions'] and derived['questions'][qid].get('version', [0, 0])[1]
                  > derived['questions'][qid].get('version', [0, 0])[0]}
        declared = set(entry.get('reversioned') or [])
        for qid in sorted(raised - declared):
            problems.append(f'{where}: {qid} got a new version in this commit; list it under reversioned')
        for qid in sorted(declared - raised):
            problems.append(f'{where}: {qid} is under reversioned, but this commit did not raise its version')
        if derived['holdout_changed'] and not entry.get('holdout_changed'):
            problems.append(f'{where}: the commit changed {derived["holdout_changed"]} HOLDOUT question(s); '
                            'the entry must say so with holdout_changed')
        if commit not in seen:
            seen.add(commit)
            for what in unversioned(derived):
                problems.append(f'{where}: {what} changed its prompt or correct answer without a new version')
    return problems


def holdout_ids() -> set[str]:
    return {q['id'] for path in sorted((ROOT / 'content/questions').glob('*.yml'))
            for q in (yaml.safe_load(path.read_text()) or {}).get('questions') or []
            if q.get('pool') == 'HOLDOUT'}


def check() -> int:
    log = yaml.safe_load(CHANGELOG.read_text())
    cache: dict[str, dict] = {}

    def derived_for(commit: str) -> dict:
        if commit not in cache:
            cache[commit] = changes(commit)
        return cache[commit]

    problems = verify(log, derived_for, holdout_ids())
    for line in problems:
        print(f'FAIL  {line}', file=sys.stderr)
    print(f'{len(log["entries"])} entries, {len(cache)} commits re-derived, {len(problems)} problem(s)')
    return 1 if problems else 0


def versions(base: str) -> int:
    """The regression guard run on every push: base..HEAD must not change what a
    question asks or expects while keeping its version, or a learner's earlier
    answers would keep counting for a question that is no longer the same."""
    found = unversioned(changes('HEAD', base))
    for what in found:
        print(f'FAIL  {what} changed its prompt or correct answer without a new version', file=sys.stderr)
    shown = base[:12] if re.fullmatch(r'[0-9a-f]{40}', base) else base
    print(f'{shown}..HEAD: {len(found)} question(s) changed without a new version')
    return 1 if found else 0


def prove() -> int:
    """Each rule of verify() fires on one injected defect (nothing is written)."""
    import copy
    log = yaml.safe_load(CHANGELOG.read_text())
    cache: dict[str, dict] = {}

    def real(commit: str) -> dict:
        if commit not in cache:
            cache[commit] = changes(commit)
        return cache[commit]

    holdout = holdout_ids()
    clean = verify(log, real, holdout)
    if clean:
        print('the changelog is not clean; fix it before proving the check', file=sys.stderr)
        return 1
    first = log['entries'][0]
    other_card = next(f for e in log['entries'] if e['commit'] != first['commit'] for f in e['flashcards'])
    injections = {
        'a card the commit did not change': (lambda e: e['flashcards'].append(other_card), None, other_card),
        'a course the commit did not change': (lambda e: e['courses'].append({'id': 'CRS-xxxxxxxxxxxx', 'sections': []}), None, 'CRS-xxxxxxxxxxxx'),
        'a question the commit did not change': (lambda e: e['questions'].append('QST-xxxxxxxxxxxx'), None, 'QST-xxxxxxxxxxxx'),
        'a page the commit did not change': (lambda e: e.setdefault('pages', []).append({'path': 'docs/whats-new/x.md', 'sections': []}), None, 'docs/whats-new/x.md'),
        'a wrong pull request': (lambda e: e.update(pull_request=e['pull_request'] + 1), None, 'is not pull request'),
        'a wrong date': (lambda e: e.update(date='2000-01-01'), None, 'the entry says 2000-01-01'),
        'a new version not declared': (lambda e: e.update(reversioned=[]), None, 'list it under reversioned'),
        'a new version claimed': (lambda e: e.update(reversioned=e['reversioned'] + [e['questions'][-1]]), None,
                                  'did not raise its version'),
        'a key changed without a new version': (None, 'unversioned', 'without a new version'),
        'a HOLDOUT question changed without a new version': (None, 'holdout', 'HOLDOUT question(s) (ids withheld)'),
        'a HOLDOUT change not declared': (None, 'holdout-changed', 'HOLDOUT question(s); the entry must say so'),
    }
    failed = 0
    # The detector itself, on a real commit: fdf4035 rewrote the key of
    # QST-xeygxgdd5586 (version 1 -> 2) and only reworded a distractor of
    # QST-kg5astsr8fz8 (version kept). The first is a change of what is
    # answered, the second is not.
    known = real('fdf4035')['questions']
    for qid, expected in (('QST-xeygxgdd5586', True), ('QST-kg5astsr8fz8', False)):
        hit = known[qid]['answer_changed'] is expected
        failed += 0 if hit else 1
        print(f'{"PROVED" if hit else "WRONG"}  detector: {qid} answer_changed is {known[qid]["answer_changed"]}')
    for name, (mutate_entry, mutate_derived, needle) in injections.items():
        broken = copy.deepcopy(log)
        if mutate_entry:
            mutate_entry(broken['entries'][0])

        def derived_for(commit: str, kind=mutate_derived) -> dict:
            d = copy.deepcopy(real(commit))
            if commit == first['commit'] and kind == 'unversioned':
                qid = next(iter(d['questions']))
                d['questions'][qid] = {**d['questions'][qid], 'answer_changed': True, 'version': [2, 2]}
            if commit == first['commit'] and kind == 'holdout':
                d['holdout_unversioned'] = 1
            if commit == first['commit'] and kind == 'holdout-changed':
                d['holdout_changed'] = 1
            return d

        problems = verify(broken, derived_for, holdout)
        hit = any(needle in p for p in problems)
        failed += 0 if hit else 1
        print(f'{"PROVED" if hit else "VACUOUS"}  {name}: {len(problems)} problem(s)')
    return 1 if failed else 0


def main(argv: list[str]) -> int:
    if argv == ['--check']:
        return check()
    if argv == ['--prove']:
        return prove()
    if len(argv) == 2 and argv[0] == '--versions':
        return versions(argv[1])
    if not argv:
        print(__doc__)
        return 2
    print(json.dumps([changes(c) for c in argv], ensure_ascii=False, indent=1))
    return 0


if __name__ == '__main__':
    sys.exit(main(sys.argv[1:]))
