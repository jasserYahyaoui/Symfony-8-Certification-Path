#!/usr/bin/env python3
"""Prouve que FLC-002 échoue réellement, au lieu de se taire.

CLAUDE.md : « A rule that has only ever been silent is not passing. » Ce projet
a déjà trouvé cinq contrôles vacués. FLC-002 ne rapporte rien sur le corpus
actuel — exactement la forme qu'avaient ces cinq-là.

Trois défauts sont injectés tour à tour dans les VRAIS fichiers canoniques :

  1. un item perd sa dernière flashcard            -> lacune non exemptée
  2. un item qui a une carte reçoit une exemption  -> exemption périmée
  3. le registre nomme un item inexistant          -> exemption orpheline

Chacun doit produire un [ERROR] FLC-002. Chaque fichier est ensuite restauré et
son SHA-256 comparé à l'original : le script ne laisse aucune trace.

    python3 tools/audit/prove_flashcard_coverage_fails.py
"""
import hashlib
import pathlib
import re
import subprocess
import sys

ROOT = pathlib.Path(__file__).resolve().parents[2]
REGISTER = ROOT / 'docs/policy/flashcard-exemptions.yml'


def digest(p: pathlib.Path) -> str:
    return hashlib.sha256(p.read_bytes()).hexdigest()


def validate() -> str:
    r = subprocess.run(['php', 'bin/cert', 'validate'], cwd=ROOT,
                       capture_output=True, text=True)
    return r.stdout + r.stderr


def fires(out: str) -> bool:
    return any(l.startswith('[ERROR] FLC-002') for l in out.splitlines())


def main() -> int:
    cards = sorted((ROOT / 'content/flashcards').glob('*.yml'))
    targets = [REGISTER] + cards
    before = {p: (p.read_bytes(), digest(p)) for p in targets}

    baseline = validate()
    if fires(baseline):
        print('ABORT : FLC-002 fire déjà sur le corpus intact ; la preuve serait fausse.')
        return 2

    results = []
    try:
        # --- 1. lacune : retirer la dernière carte d'un item -----------------
        victim = ROOT / 'content/flashcards/lot-24-miscellaneous.yml'
        raw = victim.read_text(encoding='utf-8')
        m = re.search(r'^( *)- id: FLC-\w+\n', raw, re.M)
        assert m, 'aucune carte à retirer'
        cut = raw[:m.start()].rstrip('\n') + '\n'
        victim.write_text(cut, encoding='utf-8')
        results.append(('item sans carte ni exemption', fires(validate())))
        victim.write_bytes(before[victim][0])

        # --- 2. exemption périmée -------------------------------------------
        reg = REGISTER.read_text(encoding='utf-8')
        item = re.search(r'official_item: (OIT-\w+)',
                         (ROOT / 'content/flashcards/lot-01-php.yml').read_text(encoding='utf-8')).group(1)
        REGISTER.write_text(reg.replace(
            'exemptions: []',
            'exemptions:\n  - official_item: ' + item +
            '\n    reason: "injection de preuve"\n    assessed_by: "QUESTION"\n'
            '    decided_at: "2026-09-11"\n    decided_by: "prove_flashcard_coverage_fails.py"'),
            encoding='utf-8')
        results.append(('exemption périmée (l\'item a une carte)', fires(validate())))
        REGISTER.write_bytes(before[REGISTER][0])

        # --- 3. exemption orpheline -----------------------------------------
        REGISTER.write_text(reg.replace(
            'exemptions: []',
            'exemptions:\n  - official_item: OIT-000000000000\n'
            '    reason: "injection de preuve"\n    assessed_by: "QUESTION"\n'
            '    decided_at: "2026-09-11"\n    decided_by: "prove_flashcard_coverage_fails.py"'),
            encoding='utf-8')
        results.append(('exemption nommant un item inexistant', fires(validate())))
        REGISTER.write_bytes(before[REGISTER][0])
    finally:
        for p, (raw, _) in before.items():
            p.write_bytes(raw)

    restored = all(digest(p) == before[p][1] for p in targets)

    for label, fired in results:
        print(f"  {'FIRE ' if fired else 'MUET '} {label}")
    print(f"  restauration byte-identique (SHA-256) : {'OK' if restored else 'ÉCHEC'}")

    ok = restored and all(f for _, f in results) and len(results) == 3
    print('PROOF OK' if ok else 'PROOF FAILED')
    return 0 if ok else 1


if __name__ == '__main__':
    sys.exit(main())
