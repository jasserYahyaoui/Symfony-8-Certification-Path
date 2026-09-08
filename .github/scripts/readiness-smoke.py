"""Assert that the DEPLOYED readiness figure is the one the repository computes.

A 200 on `data/readiness.json` proves the file was published. It does not prove
the number in it. That distinction stopped being academic the day ADR-0007
raised the refinement bar: Certification Readiness moved from 5.5% to 0%, and
nothing in the deployment pipeline would have noticed if the site had gone on
serving the old figure from a stale artifact.

The chain this closes:

    canonical data  →  docs/progress/certification-readiness.md   (CI fails on any diff)
                    →  website/static/data/readiness.json         (built from the same calculator)
                    →  the deployed payload                       (this script)

Reads the dashboard rather than re-running the calculator so that the check
needs nothing but Python and the checkout, and so that it compares two
independently produced artifacts instead of a value with itself.

Usage: readiness-smoke.py <deployed readiness.json> <certification-readiness.md>
"""
import json
import pathlib
import re
import sys

payload_path, dashboard_path = pathlib.Path(sys.argv[1]), pathlib.Path(sys.argv[2])

try:
    payload = json.loads(payload_path.read_text(encoding='utf-8'))
except (OSError, json.JSONDecodeError) as e:
    print(f'::error::deployed readiness.json is not readable JSON: {e}')
    sys.exit(1)

dashboard = dashboard_path.read_text(encoding='utf-8')

# | **Certification Readiness** | **0%** — 0 of 163 are refined to the point ...
figure = re.search(
    r'\*\*Certification Readiness\*\*\s*\|\s*\*\*([\d.]+)%\*\*\s*—\s*(\d+) of (\d+)',
    dashboard,
)
# **0 of 27 lots refined.**
lots = re.search(r'\*\*(\d+) of (\d+) lots refined\.\*\*', dashboard)

if figure is None or lots is None:
    print('::error::could not read the readiness figure out of the dashboard; '
          'the renderer changed and this check no longer reads what it claims to')
    sys.exit(1)

expected = {
    'percentage': float(figure.group(1)),
    'ready_items': int(figure.group(2)),
    'total_official_items': int(figure.group(3)),
    'lots_refined': int(lots.group(1)),
    'lots_total': int(lots.group(2)),
}

failed = False
for key, want in expected.items():
    got = payload.get(key)
    if got is None:
        print(f'::error::deployed readiness.json has no `{key}`')
        failed = True
    elif float(got) != float(want):
        print(f'::error::deployed `{key}` is {got}; the repository says {want}')
        failed = True

if failed:
    sys.exit(1)

print(
    'ok  readiness  deployed {p}% ({r}/{t}), {lr} of {lt} lots refined — '
    'matches the repository dashboard'.format(
        p=expected['percentage'], r=expected['ready_items'], t=expected['total_official_items'],
        lr=expected['lots_refined'], lt=expected['lots_total'],
    )
)
