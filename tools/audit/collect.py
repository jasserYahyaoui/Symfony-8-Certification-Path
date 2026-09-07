"""Shared collectors for the §14 audits.

Every audit reads the canonical files through this module so that two audits
can never disagree about what the corpus contains.
"""
import glob
import pathlib
import re

import yaml

ROOT = pathlib.Path(__file__).resolve().parents[2]


def _load(path):
    d = yaml.safe_load(path.read_text(encoding='utf-8'))
    return d


def questions():
    out = []
    for f in sorted((ROOT / 'content/questions').glob('*.yml')):
        d = _load(f)
        items = d['questions'] if isinstance(d, dict) and 'questions' in d else d
        for q in items:
            q['_file'] = f.name
            out.append(q)
    return out


def flashcards():
    out = []
    for f in sorted((ROOT / 'content/flashcards').glob('*.yml')):
        d = _load(f)
        items = d['flashcards'] if isinstance(d, dict) and 'flashcards' in d else d
        for c in items:
            c['_file'] = f.name
            out.append(c)
    return out


FRONT_MATTER = re.compile(r'\A---\n(.*?)\n---\n', re.S)


def courses():
    """Each course as (front matter dict, body text). Body excludes front matter."""
    out = []
    for f in sorted((ROOT / 'content/courses').glob('*.md')):
        text = f.read_text(encoding='utf-8')
        m = FRONT_MATTER.match(text)
        if not m:
            raise SystemExit(f'{f.name}: no YAML front matter')
        fm = yaml.safe_load(m.group(1))
        fm['_file'] = f.name
        out.append((fm, text[m.end():]))
    return out


def matrix():
    d = _load(ROOT / 'docs/syllabus/syllabus-matrix.yml')
    return d['items'] if isinstance(d, dict) and 'items' in d else d


def source_map():
    return _load(ROOT / 'docs/syllabus/source-map.yml')


def sources_of(record):
    """Every official_sources entry on a question, course front matter or card."""
    return record.get('official_sources') or []
