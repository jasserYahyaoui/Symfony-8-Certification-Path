#!/usr/bin/env python3
"""Practice Mode's data contract, checked against the DEPLOYED payload (Lot 27).

Unit B gave `practice.json` an item index carrying the readable item label, the
learning outcomes and the course URL. Without them the feedback can tell a
learner they were wrong but neither what concept they missed nor where to revise
it — so the day that index silently stops being emitted, two of the seven
feedback sections disappear and every other gate stays green: the build passes,
the rules pass, the accessibility audit passes, and the page still renders.

That is why this runs against the bytes GitHub Pages actually serves rather than
against a local build. `PayloadBuilder` is already covered by PracticePayloadTest
in this checkout; what no test in the repository can see is whether the file on
the production host carries what the repository believes it carries.

Exit code 1 on any structural defect. With a second argument, also writes three
course URLs — first, middle, last — for the workflow to fetch: the structure
check proves all 163 are well formed and `onBrokenLinks: 'throw'` proves the
build wrote the pages, but neither proves the production host serves them.
Three of 163 is a SAMPLE and is reported as one.

    python3 .github/scripts/practice-smoke.py /tmp/practice.json [/tmp/sample.txt]
"""
import json
import re
import sys

COURSE_URL = re.compile(r'^/docs/courses/[a-z0-9-]+/[a-z0-9-]+$')
# Deliberately character-for-character the PHP SourceUrl::RAW pattern. The first
# version ended in `.+` where PHP has `[^?#]+`, so a citation carrying `?` or `#`
# was a warning in `composer gate` and a FAILURE in the production smoke: a green
# repository gate that still breaks the deploy.
RAW_FILE = re.compile(
    r'^https://raw\.githubusercontent\.com/[^/]+/[^/]+/(?!refs/)[^/?\#]+/[^?\#]+$')


def main(path: str, sample_out: str | None = None) -> int:
    with open(path, encoding='utf-8') as handle:
        payload = json.load(handle)

    problems = []

    if payload.get('pool') != 'LEARNING':
        problems.append(f"pool is {payload.get('pool')!r}, expected LEARNING")

    items = payload.get('items')
    if not isinstance(items, dict) or not items:
        print('FAIL  practice  the deployed payload carries no item index; the '
              'feedback would lose its key takeaway and its course link')
        return 1

    questions = payload.get('questions') or []
    if not questions:
        problems.append('the deployed payload carries no question')

    for item_id, entry in sorted(items.items()):
        if not str(entry.get('official_item', '')).strip():
            problems.append(f'{item_id}: empty item label')
        if not str(entry.get('official_topic', '')).strip():
            problems.append(f'{item_id}: empty topic')
        if not entry.get('learning_outcomes'):
            problems.append(f'{item_id}: no learning outcome, so no key takeaway')
        url = str(entry.get('course_url', ''))
        if not COURSE_URL.match(url):
            problems.append(f'{item_id}: course_url {url!r} is not a course route')

    # Citations carry two spellings of one object since the citation schema's
    # version 2: `url`, the raw file the claim was verified against, and
    # `readable_url`, the rendered page a learner opens. SRC-002 holds them
    # together in the repository; this holds the DEPLOYED payload to the same
    # contract, because the field is only useful if it survives the build.
    bad_readable = []
    for question in questions:
        for source in question.get('official_sources') or []:
            raw = str(source.get('url', ''))
            readable = str(source.get('readable_url', ''))
            if not readable:
                bad_readable.append(f"{question['id']}: citation without readable_url")
            elif RAW_FILE.match(raw) and not readable.startswith('https://github.com/'):
                bad_readable.append(
                    f"{question['id']}: readable_url is not a rendered page ({readable})")
    if bad_readable:
        problems.extend(bad_readable[:5])
        if len(bad_readable) > 5:
            problems.append(f'{len(bad_readable) - 5} further citation defect(s) not listed')

    # Every question must reach an indexed item, or its feedback is degraded.
    orphans = [q['id'] for q in questions if q.get('official_item') not in items]
    if orphans:
        problems.append(
            f'{len(orphans)} question(s) name an item the index does not carry, '
            f'first {orphans[0]}')

    # The whole point of Practice Mode is that the holdout is not in it. That is
    # proven pool-wide by holdout-smoke.py; this only refuses a payload that has
    # started labelling itself with a per-question pool, which would mean the
    # builder changed under us.
    labelled = [q['id'] for q in questions if 'pool' in q]
    if labelled:
        problems.append(
            f'{len(labelled)} question(s) carry a per-question `pool` field, '
            'which exportQuestion does not emit; the builder changed')

    for problem in problems:
        print(f'FAIL  practice  {problem}')

    if problems:
        return 1

    if sample_out:
        urls = [items[k]['course_url'] for k in sorted(items)]
        sample = [urls[0], urls[len(urls) // 2], urls[-1]]
        # The TRAILING newline is load-bearing, not cosmetic. `while read` drops
        # a final line that has none, so the first production run of this check
        # fetched two of its three URLs and still reported success — a check
        # silently doing less than it claims, which is worse than no check.
        with open(sample_out, 'w', encoding='utf-8') as handle:
            handle.write('\n'.join(sample) + '\n')
        print(f'ok  practice  {len(sample)} course URLs sampled for fetching')

    outcomes = sum(len(e['learning_outcomes']) for e in items.values())
    citations = sum(len(q.get('official_sources') or []) for q in questions)
    print(f'ok  practice  {len(questions)} questions, {len(items)} items indexed, '
          f'{outcomes} learning outcomes, every course_url well-formed')
    # Counted, not asserted. A citation whose url is not a raw GitHub file has no
    # rendered equivalent and legitimately carries the same URL twice; saying
    # "each carrying a rendered readable_url" over that set would be an unearned
    # PASS of exactly the kind §16 and §19 forbid.
    rendered = sum(
        1
        for question in questions
        for source in question.get('official_sources') or []
        if str(source.get('readable_url', '')).startswith('https://github.com/')
    )
    print(f'ok  practice  {citations} citations, {rendered} of them linking a rendered '
          f'GitHub page, {citations - rendered} with no derivable equivalent')
    return 0


if __name__ == '__main__':
    sys.exit(main(sys.argv[1], sys.argv[2] if len(sys.argv) > 2 else None))
