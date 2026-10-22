#!/usr/bin/env python3
"""Files a reviewer can attach instead of browsing (remediation P3.3).

The first independent review (2026-10-22, docs/audit/independent-evaluation-
2026-10-22/) could open none of the links it was given, and so judged nothing.
A reviewer who cannot browse can still read files: this builds them.

    python3 tools/review/build_pack.py --out DIR
    python3 tools/review/build_pack.py --out DIR --upstream symfony=PATH --upstream symfony-docs=PATH
    python3 tools/review/build_pack.py --prove

`review-pack-<commit>.zip` holds, from HEAD as committed: the courses, the
LEARNING, VALIDATION and comprehension questions, the flashcards, the
syllabus, the remediation tracker, the journal of corrections, the reviewer's
brief, the executed facts of tools/behavior and tools/review/draw_sample.py,
which runs inside the pack. `symfony-8.0-reference-<commit>.zip` holds the
upstream clones given with --upstream: PHP sources and CHANGELOGs without
tests, and the documentation's .rst files, each at the commit its manifest
names.

The HOLDOUT pool is not in the project pack, and the pack proves it. Its
questions are not all in the holdout file: lots 01 to 13 and the golden slice
mix pools in one file. So the holdout file is left out, every other question
file is re-serialised without its HOLDOUT entries, every question file of the
pack is read again and none may hold one, and every file is searched for every
HOLDOUT id. A failure counts, never names. --prove injects each defect and
fails unless the build refuses it.
"""
from __future__ import annotations

import argparse
import hashlib
import io
import subprocess
import sys
import zipfile
from pathlib import Path

import yaml

ROOT = Path(__file__).resolve().parents[2]
HOLDOUT_FILE = 'content/questions/mock-04-holdout.yml'
INCLUDE = (
    'content/courses/',
    'content/questions/',
    'content/comprehension/',
    'content/flashcards/',
    'docs/syllabus/official-syllabus.md',
    'docs/syllabus/syllabus-matrix.yml',
    'docs/syllabus/exclusions.yml',
    'docs/syllabus/lots.yml',
    'docs/remediation/README.md',
    'docs/corrections/changelog.yml',
    'docs/review/',
    'docs/policy/first-exposure.md',
    'docs/facts/central-facts.yml',
    'tools/behavior/checks/',
    'tools/behavior/single/',
    'tools/behavior/questions/',
    'tools/behavior/composer.lock',
    'tools/review/draw_sample.py',
)


class Refused(Exception):
    """The pack would carry the HOLDOUT. The message never names a question."""


def git(*args: str) -> bytes:
    return subprocess.run(['git', '-C', str(ROOT), *args], check=True, capture_output=True).stdout


def bank_files(commit: str) -> list[str]:
    return [p for p in git('ls-tree', '-r', '--name-only', commit).decode().splitlines()
            if p.startswith('content/questions/') and p.endswith('.yml')]


def holdout_ids(commit: str) -> set[str]:
    """Every HOLDOUT id, wherever its question is filed."""
    ids: set[str] = set()
    for p in bank_files(commit):
        for q in (yaml.safe_load(git('show', f'{commit}:{p}')) or {}).get('questions') or []:
            if q.get('pool') == 'HOLDOUT':
                ids.add(q['id'])
    return ids


MATRIX = 'docs/syllabus/syllabus-matrix.yml'


def project_files(commit: str, holdout: set[str]) -> tuple[dict[str, bytes], int]:
    """The pack's files, and how many files lost HOLDOUT entries or ids."""
    paths = [p for p in git('ls-tree', '-r', '--name-only', commit).decode().splitlines()
             if p.startswith(INCLUDE) and p != HOLDOUT_FILE]
    files = {p: git('show', f'{commit}:{p}') for p in paths}
    rewritten = 0
    for p in paths:
        if p.startswith('content/questions/') and p.endswith('.yml'):
            data = yaml.safe_load(files[p]) or {}
            kept = [q for q in data.get('questions') or [] if q.get('pool') != 'HOLDOUT']
            if len(kept) != len(data.get('questions') or []):
                data['questions'] = kept
                files[p] = yaml.safe_dump(data, allow_unicode=True, sort_keys=False, width=110).encode()
                rewritten += 1
    # The matrix names each item's questions by id, the holdout's included.
    matrix = yaml.safe_load(files[MATRIX])
    for item in matrix['items']:
        for key in ('question_refs', 'exam_refs'):
            if item.get(key):
                item[key] = [i for i in item[key] if i not in holdout]
    files[MATRIX] = yaml.safe_dump(matrix, allow_unicode=True, sort_keys=False, width=110).encode()
    rewritten += 1
    return files, rewritten


def guard(files: dict[str, bytes], holdout: set[str]) -> dict[str, int]:
    """Every reason the pack might carry the holdout, checked. Counts only."""
    if not holdout:
        raise Refused('no HOLDOUT id was found to exclude: the guard would hold vacuously')
    if HOLDOUT_FILE in files:
        raise Refused('the HOLDOUT file is in the pack')
    questions = 0
    for path, body in files.items():
        if path.startswith('content/questions/') and path.endswith('.yml'):
            for q in (yaml.safe_load(body) or {}).get('questions') or []:
                questions += 1
                if q.get('pool') == 'HOLDOUT':
                    raise Refused(f'{path} holds a HOLDOUT question (id withheld)')
    hits = 0
    for body in files.values():
        text = body.decode('utf-8', errors='replace')
        hits += sum(1 for i in holdout if i in text)
    if hits:
        raise Refused(f'{hits} HOLDOUT id occurrence(s) in the pack (ids withheld)')
    return {'files': len(files), 'questions': questions, 'holdout_excluded': len(holdout)}


def manifest(title: str, origin: list[str], files: dict[str, bytes], notes: list[str]) -> bytes:
    lines = [f'# {title}', '', *origin, '', *notes, '', '| sha256 | bytes | path |', '|---|---:|---|']
    lines += [f'| `{hashlib.sha256(b).hexdigest()}` | {len(b)} | {p} |' for p, b in sorted(files.items())]
    return ('\n'.join(lines) + '\n').encode()


def write_zip(path: Path, files: dict[str, bytes]) -> None:
    with zipfile.ZipFile(path, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as z:
        for p in sorted(files):
            info = zipfile.ZipInfo(p, date_time=(2026, 1, 1, 0, 0, 0))
            info.compress_type = zipfile.ZIP_DEFLATED
            z.writestr(info, files[p])


def upstream_files(name: str, root: Path) -> tuple[dict[str, bytes], str]:
    sha = subprocess.run(['git', '-C', str(root), 'log', '-1', '--format=%H %cs'], check=True,
                         capture_output=True, text=True).stdout.strip()
    out: dict[str, bytes] = {}
    for f in sorted(root.rglob('*')):
        rel = f.relative_to(root)
        if not f.is_file() or '.git' in rel.parts or 'Tests' in rel.parts or 'Fixtures' in rel.parts:
            continue
        if f.suffix in ('.php', '.rst') or f.name == 'CHANGELOG.md':
            out[f'{name}/{rel.as_posix()}'] = f.read_bytes()
    return out, sha


def build(out: Path, upstream: list[str]) -> int:
    commit = git('rev-parse', 'HEAD').decode().strip()
    holdout = holdout_ids(commit)
    files, rewritten = project_files(commit, holdout)
    counts = guard(files, holdout)
    files['MANIFEST.md'] = manifest(
        f'Review pack — commit {commit[:7]}',
        [f'Repository: jasserYahyaoui/Symfony-8-Certification-Path, commit `{commit}`.',
         'Built by tools/review/build_pack.py from the commit, not the working tree.'],
        files,
        [f'HOLDOUT: `{HOLDOUT_FILE}` left out; {rewritten} other files re-serialised without their HOLDOUT '
         f'entries or ids — the question files of lots 01 to 13, the golden slice, and `{MATRIX}` '
         f'(`question_refs`, `exam_refs`); their sha256 below is of that copy, not of the commit; '
         f'{counts["questions"]} questions read, none HOLDOUT; '
         f'the {counts["holdout_excluded"]} HOLDOUT ids searched in every file, none found.',
         'Run the sample here: `python3 tools/review/draw_sample.py --seed N`.'],
    )
    out.mkdir(parents=True, exist_ok=True)
    pack = out / f'review-pack-{commit[:7]}.zip'
    write_zip(pack, files)
    print(f'{pack}  {pack.stat().st_size} bytes, {counts["files"]} files, '
          f'{counts["questions"]} questions, HOLDOUT absent ({counts["holdout_excluded"]} ids searched)')

    if upstream:
        ref: dict[str, bytes] = {}
        origin = []
        for spec in upstream:
            name, _, path = spec.partition('=')
            got, sha = upstream_files(name, Path(path))
            ref.update(got)
            origin.append(f'- `{name}` at `{sha}` ({len(got)} files: PHP sources and CHANGELOGs without tests, or .rst)')
        ref['MANIFEST.md'] = manifest('Symfony 8.0 reference', origin, ref,
                                      ['Upstream branch 8.0, shallow clones; the commit is the reference, not the branch.'])
        name = f'symfony-8.0-reference-{commit[:7]}.zip'
        write_zip(out / name, ref)
        print(f'{out / name}  {(out / name).stat().st_size} bytes, {len(ref) - 1} files')
    return 0


def prove() -> int:
    commit = git('rev-parse', 'HEAD').decode().strip()
    holdout = holdout_ids(commit)
    clean, _ = project_files(commit, holdout)
    one = sorted(holdout)[0]
    defects = {
        'the HOLDOUT file': {**clean, HOLDOUT_FILE: git('show', f'{commit}:{HOLDOUT_FILE}')},
        'a HOLDOUT question in a bank file': {**clean, 'content/questions/extra.yml':
                                              yaml.safe_dump({'questions': [{'id': one, 'pool': 'HOLDOUT'}]}).encode()},
        'a HOLDOUT id quoted in a course': {**clean, 'content/courses/extra.md': f'See {one}.'.encode()},
        'no HOLDOUT id to exclude': None,
    }
    failed = 0
    for name, files in defects.items():
        try:
            if files is None:
                guard(clean, set())
            else:
                guard(files, holdout)
            print(f'FAILED  {name}: the pack was built')
            failed += 1
        except Refused as e:
            # The refusal must not print what it guards.
            if one in str(e):
                print(f'FAILED  {name}: refused, but the message names the question')
                failed += 1
            else:
                print(f'PROVED  {name}: refused ({e})')
    try:
        guard(clean, holdout)
        print('PROVED  the real pack passes the guard')
    except Refused as e:
        print(f'FAILED  the real pack is refused: {e}')
        failed += 1
    return 1 if failed else 0


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__.split('\n\n')[0])
    ap.add_argument('--out', type=Path)
    ap.add_argument('--upstream', action='append', default=[], help='name=path of a git clone')
    ap.add_argument('--prove', action='store_true')
    args = ap.parse_args()
    if args.prove:
        return prove()
    if args.out is None:
        ap.error('--out is required')
    return build(args.out, args.upstream)


if __name__ == '__main__':
    sys.exit(main())
