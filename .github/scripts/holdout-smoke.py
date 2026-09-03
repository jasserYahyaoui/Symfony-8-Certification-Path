"""Prove holdout isolation against the deployed bytes (§7.3, ADR-0006).

Until 2026-09-03 the production smoke test only asserted that practice.json
*declared* `pool: LEARNING`. That proved the payload's own label and nothing
about what it shipped. This compares the question ids actually deployed against
the canonical banks in the checkout, and also looks for holdout choice ids, so
a leaked answer is caught even if the question id were renamed.

Usage: holdout-smoke.py <practice.json> <exam.json>
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


def main(argv):
    if len(argv) != 3:
        print("::error::usage: holdout-smoke.py <practice.json> <exam.json>")
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

    for problem in problems:
        print("::error::" + problem)

    print(f"checked against {len(holdout)} holdout questions and {len(holdout_choices)} holdout choices")
    return 1 if problems else 0


if __name__ == "__main__":
    sys.exit(main(sys.argv))
