"""Lot 27 Unit A — read-only audit of the English LEARNING bank (Practice Mode).

Reads canonical files and writes CSV under docs/audit/lot-27-practice-mode/.
It never writes to content/ or to the matrix: Unit A diagnoses, Unit C repairs.

WHY THE FIRST DRAFT OF THIS SCRIPT WAS WRONG, recorded so it is not repeated.

  VERSION_MISMATCH matched `\\b8\\.[1-9]\\b` and returned seven questions. All
  seven name PHP 8.1 to 8.4, which is IN scope — Symfony 8.0 requires PHP 8.4
  and lot 01 carries an item called "PHP API up to PHP 8.4 version". The check
  was scoped to Symfony versions, and version contamination is left to
  aud02_version_contamination.py, which is the project's authority on it and
  reports zero. A second, worse check beside a good one is not extra safety.

  CODE_NOT_STRUCTURED returned 129 questions, which read as 129 content
  defects. It was one defect: nothing in the UI renders code at all. Of the
  questions carrying code, two are genuinely multiline and the rest are short
  inline fragments that the brief says to KEEP inline. Reporting them as one
  class would have justified rewriting 129 questions that need no rewriting.

`--prove` injects one synthetic defect per classification and asserts the
classifier fires, because every check here currently returns zero for four of
its classes, which is the shape all five vacuous checks in this project had.
"""
import collections
import csv
import glob
import os
import re
import sys
import unicodedata

import yaml

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
OUT = os.path.join(ROOT, 'docs/audit/lot-27-practice-mode')

# A fragment the learner reads as code. Deliberately conservative: a missed
# fragment is displayed as prose, which is the defect this lot exists to fix.
CODE_TOKENS = [
    (r'\{%|\{\{|\{#', 'twig'),
    (r'#\[[A-Z]\w+', 'php-attribute'),
    (r'<\?php|->\w+\(|::\w+|\$[a-z_]\w*', 'php'),
    (r'^\s{2,}[a-z_]+:', 'yaml'),
    (r'<[a-z-]+\s+[a-z-]+=', 'xml'),
    (r'^(GET|POST|PUT|PATCH|DELETE|HEAD) /|^Cache-Control:', 'http'),
    (r'^\s*(php |bin/console|composer |symfony )', 'shell'),
]
FENCE = re.compile(r'```')
GENERIC = re.compile(r"^(this is correct|correct|true)\.?$", re.I)


def slug(value):
    ascii_ = unicodedata.normalize('NFKD', value).encode('ascii', 'ignore').decode()
    return re.sub(r'[^a-z0-9]+', '-', ascii_.lower()).strip('-') or 'item'


def code_signals(text):
    if not isinstance(text, str) or not text:
        return []
    return [kind for pattern, kind in CODE_TOKENS if re.search(pattern, text, re.M)]


def classify(question, items, courses):
    """@return (list[str] classifications, dict diagnostics)."""
    item = items.get(question.get('official_item'))
    choices = question.get('choices') or []
    correct = [c for c in choices if c.get('correct')]
    wrong = [c for c in choices if not c.get('correct')]
    explanation = (question.get('explanation') or '').strip()
    missing = [c['id'] for c in wrong if not (c.get('explanation') or '').strip()]

    surfaces = {'prompt': question.get('question') or ''}
    for choice in choices:
        surfaces[f'choice:{choice["id"]}'] = choice.get('text') or ''
        if choice.get('explanation'):
            surfaces[f'choice_expl:{choice["id"]}'] = choice['explanation']
    surfaces['explanation'] = explanation

    coded = {k: sorted(set(code_signals(v))) for k, v in surfaces.items()}
    coded = {k: v for k, v in coded.items() if v}
    # A block is multiline content or content the author already fenced. An
    # inline fragment stays inline: turning `$request->getLocale()` into a
    # block would break the sentence it belongs to.
    blocks = {k for k, v in surfaces.items() if '\n' in v.strip() or FENCE.search(v)}

    outcomes = question.get('assesses_outcomes') or []
    course_refs = (item.get('course_refs') or []) if item else []
    course_ok = bool(course_refs) and all(ref in courses for ref in course_refs)

    tags = []
    if not explanation or GENERIC.match(explanation) or len(explanation) < 40:
        tags.append('MISSING_CORRECT_EXPLANATION')
    if missing:
        tags.append('MISSING_DISTRACTOR_EXPLANATION')
    if len(correct) != int(question.get('required_answer_count', 1)):
        tags.append('AMBIGUOUS_CONTENT')
    if blocks:
        tags.append('CODE_BLOCK_REQUIRED')
    if coded and not blocks:
        tags.append('CODE_INLINE_PRESENT')
    if not item:
        tags.append('DATA_MODEL_GAP:item')
    if not outcomes:
        tags.append('DATA_MODEL_GAP:outcomes')
    if not course_ok:
        tags.append('DATA_MODEL_GAP:course')
    if not (question.get('official_sources') or []):
        tags.append('DATA_MODEL_GAP:source')
    if not tags:
        tags.append('READY_FOR_NEW_UI')

    return tags, {
        'item': item, 'correct': correct, 'wrong': wrong, 'missing': missing,
        'explanation': explanation, 'coded': coded, 'blocks': blocks,
        'outcomes': outcomes, 'course_refs': course_refs, 'course_ok': course_ok,
        'surfaces': surfaces,
    }


def load():
    matrix = yaml.safe_load(
        open(os.path.join(ROOT, 'docs/syllabus/syllabus-matrix.yml'), encoding='utf-8'))
    items = {i['id']: i for i in matrix['items']}
    courses = {os.path.basename(p)[:-3]
               for p in glob.glob(os.path.join(ROOT, 'content/courses/*.md'))}
    questions = []
    for path in sorted(glob.glob(os.path.join(ROOT, 'content/questions/*.yml'))):
        for question in yaml.safe_load(open(path, encoding='utf-8'))['questions']:
            question['_file'] = os.path.basename(path)
            questions.append(question)
    return items, courses, questions


def prove(items, courses):
    """Assert every classification can fire. Synthetic, in memory, no file read."""
    base = {
        'id': 'QST-probe', 'official_item': next(iter(items)), 'language': 'en',
        'pool': 'LEARNING', 'required_answer_count': 1,
        'question': 'A plain sentence with no technical content at all.',
        'explanation': 'A sufficiently long canonical explanation of why the answer holds.',
        'assesses_outcomes': [items[next(iter(items))]['learning_outcomes'][0]['id']],
        'official_sources': [{'url': 'https://example.invalid'}],
        'choices': [
            {'id': 'CHO-a', 'text': 'right', 'correct': True},
            {'id': 'CHO-b', 'text': 'wrong', 'correct': False, 'explanation': 'Because.'},
        ],
    }
    import copy
    cases = []

    def case(label, expect, mutate):
        q = copy.deepcopy(base)
        mutate(q)
        cases.append((label, expect, classify(q, items, courses)[0]))

    case('clean fixture', 'READY_FOR_NEW_UI', lambda q: None)
    case('empty explanation', 'MISSING_CORRECT_EXPLANATION',
         lambda q: q.__setitem__('explanation', ''))
    case('generic explanation', 'MISSING_CORRECT_EXPLANATION',
         lambda q: q.__setitem__('explanation', 'This is correct.'))
    case('distractor without explanation', 'MISSING_DISTRACTOR_EXPLANATION',
         lambda q: q['choices'][1].pop('explanation'))
    case('answer count inconsistent', 'AMBIGUOUS_CONTENT',
         lambda q: q.__setitem__('required_answer_count', 2))
    case('multiline code in the prompt', 'CODE_BLOCK_REQUIRED',
         lambda q: q.__setitem__('question', "Read this:\n$a->b();\nreturn $a;"))
    case('already fenced code', 'CODE_BLOCK_REQUIRED',
         lambda q: q.__setitem__('question', 'Read: ```php $a->b(); ```'))
    case('inline fragment only', 'CODE_INLINE_PRESENT',
         lambda q: q.__setitem__('question', 'What does $request->getLocale() return?'))
    case('unknown official item', 'DATA_MODEL_GAP:item',
         lambda q: q.__setitem__('official_item', 'OIT-doesnotexist0'))
    case('no outcome link', 'DATA_MODEL_GAP:outcomes',
         lambda q: q.__setitem__('assesses_outcomes', []))
    case('no official source', 'DATA_MODEL_GAP:source',
         lambda q: q.__setitem__('official_sources', []))

    failures = []
    for label, expect, got in cases:
        ok = expect in got
        print(f'  {"OK  " if ok else "FAIL"} {expect:<32} {label} -> {"|".join(got)}')
        if not ok:
            failures.append(label)

    # DATA_MODEL_GAP:course needs an item whose course_refs do not resolve,
    # which no real item has; prove it against a synthetic item instead.
    fake_items = dict(items)
    fake_items['OIT-nocourse000'] = {
        'id': 'OIT-nocourse000', 'lot': 'lot-01', 'official_item': 'Probe',
        'learning_outcomes': [{'id': 'OUT-probe0000000', 'outcome': 'x'}],
        'course_refs': ['CRS-doesnotexist'],
    }
    import copy as _copy
    q = _copy.deepcopy(base)
    q['official_item'] = 'OIT-nocourse000'
    q['assesses_outcomes'] = ['OUT-probe0000000']
    got = classify(q, fake_items, courses)[0]
    ok = 'DATA_MODEL_GAP:course' in got
    print(f'  {"OK  " if ok else "FAIL"} {"DATA_MODEL_GAP:course":<32} unresolvable course ref -> {"|".join(got)}')
    if not ok:
        failures.append('course ref')

    if failures:
        print(f'\nPROOF FAILED — {len(failures)} classification(s) stayed silent on their '
              'own defect; the audit is VACUOUS, not clean')
        return 1
    print(f'\nPROOF OK — {len(cases) + 1} cases, every classification fired on its own '
          'defect (synthetic fixtures, in memory; no canonical file was read for writing)')
    return 0


def main():
    items, courses, questions = load()

    if '--prove' in sys.argv:
        return prove(items, courses)

    learning = [q for q in questions if q.get('pool') == 'LEARNING']
    english = [q for q in learning if q.get('language') == 'en']
    french = [q for q in learning if q.get('language') != 'en']

    inventory, feedback, rendering = [], [], []
    tally = collections.Counter()

    for question in english:
        tags, d = classify(question, items, courses)
        tally.update(tags)
        item = d['item']
        course_url = ''
        if item:
            course_url = f"/docs/courses/{slug(item['lot'])}/{slug(item['official_item'])}"

        inventory.append({
            'question_id': question['id'], 'file': question['_file'],
            'lot': item['lot'] if item else '',
            'official_topic': question.get('official_topic', ''),
            'official_item_id': question.get('official_item', ''),
            'official_item': item['official_item'] if item else '',
            'difficulty': question.get('difficulty', ''),
            'answer_mode': question.get('answer_mode', ''),
            'required_answer_count': question.get('required_answer_count', ''),
            'correct_choices': len(d['correct']),
            'choices': len(question.get('choices') or []),
            'cognitive_level': question.get('cognitive_level', ''),
            'exam_skill': question.get('exam_skill', ''),
            'archetype': question.get('question_archetype', ''),
            'code_language': question.get('code_language') or '',
            'estimated_time_seconds': question.get('estimated_time_seconds', ''),
            'assesses_outcomes': '|'.join(d['outcomes']),
            'course_refs': '|'.join(d['course_refs']),
            'course_url': course_url,
            'sources': len(question.get('official_sources') or []),
            'classification': '|'.join(tags),
        })
        feedback.append({
            'question_id': question['id'],
            'explanation_chars': len(d['explanation']),
            'distractors': len(d['wrong']),
            'distractors_with_explanation': len(d['wrong']) - len(d['missing']),
            'distractors_missing': '|'.join(d['missing']),
            'correct_choice_ids': '|'.join(c['id'] for c in d['correct']),
            'answer_count_consistent':
                'yes' if len(d['correct']) == int(question.get('required_answer_count', 1)) else 'NO',
            'outcomes_linked': len(d['outcomes']),
            'course_resolvable': 'yes' if d['course_ok'] else 'NO',
            'classification': '|'.join(tags),
        })
        for surface, kinds in sorted(d['coded'].items()):
            rendering.append({
                'question_id': question['id'], 'surface': surface,
                'detected': '|'.join(kinds),
                'render_as': 'block' if surface in d['blocks'] else 'inline',
                'declared_code_language': question.get('code_language') or '',
                'already_fenced': 'yes' if FENCE.search(d['surfaces'][surface]) else 'no',
                'chars': len(d['surfaces'][surface]),
            })

    os.makedirs(OUT, exist_ok=True)
    for name, rows in (('learning-question-inventory.csv', inventory),
                       ('english-feedback-audit.csv', feedback),
                       ('code-rendering-inventory.csv', rendering)):
        with open(os.path.join(OUT, name), 'w', newline='', encoding='utf-8') as handle:
            writer = csv.DictWriter(handle, fieldnames=list(rows[0].keys()))
            writer.writeheader()
            writer.writerows(rows)

    print(f'  questions, all pools                         {len(questions)}')
    print(f'  LEARNING                                     {len(learning)}')
    print(f'    english (this unit)                        {len(english)}')
    print(f'    french  (no regression, not refined here)  {len(french)}')
    print()
    for tag, count in sorted(tally.items(), key=lambda kv: (-kv[1], kv[0])):
        print(f'  {tag:<32} {count}')
    print()
    print(f'  code surfaces, render as block               '
          f'{sum(1 for r in rendering if r["render_as"] == "block")}')
    print(f'  code surfaces, render inline                 '
          f'{sum(1 for r in rendering if r["render_as"] == "inline")}')
    return 0


sys.exit(main())
