# Independent syllabus audit — pre-Lot 27

Diagnostic artifacts only. **Nothing here is canonical** and nothing in this
directory is read by `bin/cert`, the generator or CI.

| File | What it is |
|---|---|
| `pdf-raw.txt` | Text of all five PDF pages, extracted with pypdf 6.16.2 |
| `pdf-inventory.json` | The independent inventory, built from the PDF alone before the matrix was opened |
| `coverage-mapping.csv` | One row per official atomic item: PDF topic and wording, repo id, lot, level, refs, coverage status, version status, finding |

Source: `Symfony Certification.PDF`, sha256
`4ee8b9620c683f89c1e4d860d7a017fb1570698b8f9c56d0c716e8884a0b3bd1`, 5 pages,
468,963 bytes, supplied by the owner 2026-09-03.

## How the topic/item boundary was settled

The PDF states **"15 topics"** and renders **14** topic headings. The text
alone cannot say which lines are headings, so the question was settled by
layout metrics rather than by reading, and two candidate readings were tested:

| | Font size | Left edge |
|---|---|---|
| Topic heading | **17.0** | 19.2 (left column) / 382.0 (right) |
| Item | 16.0 | 51.2 / 414.0 |
| Sub-item | 16.0 | **83.2 / 446.0** |

Measured result: exactly **14** runs at size 17.0. `Object Oriented
Programming` sits at 16.0/51.2 — an **item** of topic *PHP*. `Components:`
also sits at 16.0/51.2, and is the only item-level line in the document with
children: its twelve components are the only lines at the deeper 83.2/446.0
indent.

This confirms the repository's existing model and the reading already recorded
in `docs/syllabus/official-syllabus.md`.

Three facts are kept together and none overrides another:

- **published constraint: 15 topics** — stated by the exam page, and binding
  for the blueprint and any per-topic mock distribution;
- **measured presentation: 14** first-level headings at font size 17.0;
- **item mapping: 163 / 163**, 0 missing, 0 unexpected.

The typography measures how the page is rendered, not what it states. That
`Components:` is the fifteenth topic remains an **interpretation** — it is the
only item-level line with children of its own, but it does not carry the 17.0
heading presentation, so the document neither names nor renders a fifteenth
heading. The discrepancy is in the source and is recorded rather than
reconciled (§2.5).
