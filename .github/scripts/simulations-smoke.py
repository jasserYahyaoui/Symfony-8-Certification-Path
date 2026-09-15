#!/usr/bin/env python3
"""The simulations hub, checked against the DEPLOYED payload and page.

The hub exists to answer one question — which mock to sit, and when — and it
holds none of its own text: every sentence comes from `simulations.json`. So a
payload that silently loses `when_to_use` leaves a page that still renders, still
passes the build, still passes the accessibility audit, and no longer answers the
question it was built for.

It also checks the direction that costs more if it is ever wrong: this payload
must carry NO question, choice or answer. Mock 4's bank is reserved and unseen
(ADR-0005 Option A), and a page explaining that must not be where it stops being
true. PayloadBuilder::assertNoQuestionLeak asserts it at build time; this asserts
it against the bytes GitHub Pages serves.

WHAT IT DELIBERATELY DOES NOT DO is look at the page's HTML. The hub is
client-rendered, so the served HTML carries the shell and « Chargement… »;
searching it for a mock name would match the NAVBAR and report success about a
body that never rendered. That the body renders is proven in a browser by
website/tools/verify-simulations-ui.mjs, which runs in CI.

    python3 .github/scripts/simulations-smoke.py /tmp/simulations.json
"""
import json
import sys

EXPECTED = {'mock-1', 'mock-2', 'mock-3', 'mock-4', 'mock-5'}
FORBIDDEN = ('questions', 'choices', 'question', 'explanation', 'items')
REQUIRED = ('route', 'name', 'purpose', 'when_to_use', 'question_count',
            'duration_minutes', 'language', 'pool', 'format_label')


def main(path: str) -> int:
    with open(path, encoding='utf-8') as handle:
        payload = json.load(handle)

    problems = []

    for key in FORBIDDEN:
        if key in payload:
            problems.append(f'the payload carries {key!r}; the hub describes '
                            'sittings and must never sample one')

    mocks = payload.get('mocks') or []
    ids = {m.get('id') for m in mocks}
    if ids != EXPECTED:
        problems.append(f'mocks are {sorted(i for i in ids if i)}, expected '
                        f'{sorted(EXPECTED)}')

    for mock in mocks:
        mock_id = mock.get('id', '?')
        for key in FORBIDDEN:
            if key in mock:
                problems.append(f'{mock_id}: carries {key!r}')
        for key in REQUIRED:
            if not str(mock.get(key, '')).strip():
                problems.append(f'{mock_id}: {key} is empty, so the hub cannot '
                                'say what this mock is for')
        if 'repeatable' not in mock:
            problems.append(f'{mock_id}: no repeatable flag, so Mock 4 reads '
                            'like the four that can be replayed')

    # The one distinction the hub must not blur: 75 and 90 are published
    # constraints, every other count and duration is this project's decision.
    four = next((m for m in mocks if m.get('id') == 'mock-4'), {})
    if four.get('format_label') != 'OFFICIAL_FORMAT':
        problems.append('mock-4 is not labelled OFFICIAL_FORMAT')
    if four.get('repeatable') is not False:
        problems.append('mock-4 is not marked as a single sitting')
    for mock in mocks:
        if mock.get('id') != 'mock-4' and mock.get('format_label') != 'INTERNAL_TRAINING_FORMAT':
            problems.append(f"{mock.get('id')}: not labelled "
                            'INTERNAL_TRAINING_FORMAT, which would present this '
                            "project's own figure as an official one")

    if not str(payload.get('not_official', '')).strip():
        problems.append('the payload carries no not_official statement')

    for problem in problems:
        print(f'FAIL  simulations  {problem}')

    if problems:
        return 1

    print(f'ok  simulations  {len(mocks)} mocks described, each with a role and '
          'a when-to-use, no question in the payload')
    return 0


if __name__ == '__main__':
    sys.exit(main(sys.argv[1]))
