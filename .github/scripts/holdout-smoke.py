"""Prove holdout isolation against the deployed bytes (§7.3, ADR-0006).

Until 2026-09-03 the production smoke test only asserted that practice.json
*declared* `pool: LEARNING`. That proved the payload's own label and nothing
about what it shipped. This compares the question ids actually deployed against
the canonical banks in the checkout, and also looks for holdout choice ids, so
a leaked answer is caught even if the question id were renamed.

Since Mock 4 shipped, the holdout *is* deployed — in one payload, which exists
for it. So the check runs in both directions: none of the holdout in the two
learning payloads, and all of it in the mock payload. A mock short of a
question is a failure too, because the sitting would then be under the official
75 with nothing saying so.

Usage: holdout-smoke.py <practice.json> <exam.json> [mock-4.json]
"""

import glob
import json
import sys

import yaml

EXPECTED = {"practice": "LEARNING", "exam": "VALIDATION"}


def canonical_pools():
    questions = {}
    choices = {}
    for path in glob.glob("content/questions/*.yml"):
        with open(path, encoding="utf-8") as handle:
            for question in yaml.safe_load(handle).get("questions") or []:
                questions.setdefault(question["pool"], set()).add(question["id"])
                if question["pool"] == "HOLDOUT":
                    for choice in question.get("choices") or []:
                        choices.setdefault("HOLDOUT", set()).add(choice["id"])
    return questions, choices


def check_mock(path, holdout, problems):
    """The mock payload must carry the whole holdout and nothing else."""
    with open(path, encoding="utf-8") as handle:
        payload = json.load(handle)

    ids = {q["id"] for q in payload["questions"]}
    name = "mock-4"

    if payload.get("pool") != "HOLDOUT":
        problems.append(f"{name} declares pool {payload.get('pool')}, expected HOLDOUT")

    missing = sorted(holdout - ids)
    if missing:
        problems.append(f"{name} is missing {len(missing)} holdout question(s): {missing[:5]}")

    foreign = sorted(ids - holdout)
    if foreign:
        problems.append(f"{name} ships {len(foreign)} question(s) outside the holdout: {foreign[:5]}")

    declared = payload.get("question_count")
    if declared != len(ids):
        problems.append(f"{name} declares question_count {declared} but ships {len(ids)}")

    foreign_language = sorted(q["id"] for q in payload["questions"] if q.get("language") != "en")
    if foreign_language:
        problems.append(f"{name} ships {len(foreign_language)} question(s) not in English (§10): {foreign_language[:5]}")

    return ids


def main(argv):
    if len(argv) not in (3, 4):
        print("::error::usage: holdout-smoke.py <practice.json> <exam.json> [mock-4.json]")
        return 2

    pools, choices = canonical_pools()
    holdout = pools.get("HOLDOUT", set())
    holdout_choices = choices.get("HOLDOUT", set())
    if not holdout:
        print("::error::no HOLDOUT question found in content/questions — the check would pass vacuously")
        return 1

    problems = []
    for path, name in zip(argv[1:], ("practice", "exam")):
        want = EXPECTED[name]
        before = len(problems)
        with open(path, encoding="utf-8") as handle:
            raw = handle.read()
        payload = json.loads(raw)
        ids = {q["id"] for q in payload["questions"]}

        if payload.get("pool") != want:
            problems.append(f"{name} declares pool {payload.get('pool')}, expected {want}")

        foreign = sorted(ids - pools.get(want, set()))
        if foreign:
            problems.append(f"{name} ships {len(foreign)} question(s) outside the {want} pool: {foreign[:5]}")

        leaked = sorted(i for i in holdout if i in raw)
        if leaked:
            problems.append(f"{name} contains HOLDOUT question ids: {leaked[:5]}")

        answers = sorted(c for c in holdout_choices if c in raw)
        if answers:
            problems.append(f"{name} contains HOLDOUT choice ids: {answers[:5]}")

        # Only claim ok for a payload that actually passed: a green line
        # printed next to its own errors is how a failure gets skimmed past.
        if len(problems) == before:
            print(f"ok  {name:<8} {len(ids):>4} questions, all {want}, no holdout id or choice")
        else:
            print(f"FAIL {name:<8} {len(ids):>4} questions — see the errors below")

    if len(argv) == 4:
        before = len(problems)
        ids = check_mock(argv[3], holdout, problems)
        if len(problems) == before:
            print(f"ok  mock-4   {len(ids):>4} questions, the whole holdout and nothing else, all English")
        else:
            print(f"FAIL mock-4   {len(ids):>4} questions — see the errors below")

    for problem in problems:
        print("::error::" + problem)

    print(f"checked against {len(holdout)} holdout questions and {len(holdout_choices)} holdout choices")
    return 1 if problems else 0


if __name__ == "__main__":
    sys.exit(main(sys.argv))
