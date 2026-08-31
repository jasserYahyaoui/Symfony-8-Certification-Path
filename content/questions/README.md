# Question bank

One YAML file per official topic, each declaring `schema_version: 1` and a
`questions:` list. Every field of Master Plan §7.1 is required; the loader
rejects a missing field rather than defaulting it.

Pools (§7.3):

- `LEARNING` — Practice Mode. Written to `build/data/practice.json`.
- `VALIDATION` — domain assessments.
- `HOLDOUT` — final mocks only. Never written to the Practice payload; the
  build asserts this and CI rule `POOL-001` guards the data.

Mint ids with `bin/cert id:mint Question 10` and `bin/cert id:mint Choice 40`.
Ids are minted, never derived from a slug or file name (§11).

The bank is empty until the official syllabus is imported: a question cannot
map to an official item that does not exist yet (§3.1).
