"""Pre-check an annotation table against the ARC-001 constraints.

Written after ARC-001 rejected the same mistake in three consecutive lots (07,
12, 08): an archetype chosen from what a question *looks like*, against an
`exam_skill` or `cognitive_level` that says otherwise. The rule was right every
time. This script does not duplicate the rule into the build and does not
replace it — ARC-001 remains the only judge. It simply moves the discovery of
that class of mistake from CI to the workstation.

Usage:

    python3 tools/audit/check_annotation_map.py <file defining MAP>

where MAP is {question_id: (archetype, [outcome_ids])}. Exits non-zero if any
mapping would be rejected.

It checks the *constraints*, not the *aptness* of an archetype: it would accept
SCENARIO_CHOICE on a question that has no scenario.
"""
import sys, yaml, glob

REQ_DIAGNOSE = {'CODE_DIAGNOSIS', 'BEHAVIOR_DIAGNOSIS'}
FORBID_CODE  = {'BEHAVIOR_DIAGNOSIS', 'BEHAVIOR_PREDICTION'}
REQ_CODE     = {'CODE_OUTPUT', 'CODE_DIAGNOSIS'}

def check(mapping):
    qs = {q['id']: q for f in glob.glob('content/questions/*.yml')
          for q in yaml.safe_load(open(f))['questions']}
    bad = []
    for qid, (arch, _outs) in mapping.items():
        q = qs.get(qid)
        if q is None:
            bad.append(f'{qid}: not in the corpus'); continue
        skill, cog, lang = q['exam_skill'], q['cognitive_level'], q.get('code_language')
        if arch in REQ_DIAGNOSE and skill != 'DIAGNOSE':
            bad.append(f'{qid}: {arch} requires exam_skill DIAGNOSE, has {skill}')
        if arch in FORBID_CODE and lang is not None:
            bad.append(f'{qid}: {arch} forbids code, but code_language={lang}')
        if arch in REQ_CODE and lang is None:
            bad.append(f'{qid}: {arch} requires code_language, none declared')
        if arch == 'CONCEPT_DISTINCTION' and skill == 'RECOGNIZE':
            bad.append(f'{qid}: CONCEPT_DISTINCTION forbids exam_skill RECOGNIZE')
        if arch == 'DEFINITION_RECALL' and cog == 'APPLY':
            bad.append(f'{qid}: DEFINITION_RECALL forbids cognitive_level APPLY')
        if arch == 'VERSION_ATTRIBUTION' and not any(
                c.isdigit() for c in q['question']):
            bad.append(f'{qid}: VERSION_ATTRIBUTION names no version in the stem')
    return bad

if __name__ == '__main__':
    ns = {}
    exec(open(sys.argv[1]).read(), ns)
    problems = check(ns['MAP'])
    for p in problems:
        print('ARC-001 would reject:', p)
    print(f'{len(problems)} problem(s) in {len(ns["MAP"])} mapped questions')
    sys.exit(1 if problems else 0)
