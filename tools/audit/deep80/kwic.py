"""Concordance (KWIC) over the corpus: courses, what's-new pages, flashcards, and the
LEARNING, VALIDATION and COMPREHENSION questions. HOLDOUT questions are skipped by
their pool, never by file name, and nothing from them is printed.

    python3 tools/audit/deep80/kwic.py REGEX [width]
"""
import pathlib, os
os.chdir(pathlib.Path(__file__).resolve().parents[3])
import glob,re,sys,yaml
rx=re.compile(sys.argv[1]); W=int(sys.argv[2]) if len(sys.argv)>2 else 70
def show(where,t):
    for m in rx.finditer(t):
        s=t[max(0,m.start()-W):m.end()+W].replace('\n',' ')
        print(f'{where}: …{s}…')
for f in sorted(glob.glob('content/courses/*.md')): show(f.split('/')[-1],open(f).read().split('\n---\n',1)[-1])
for f in sorted(glob.glob('docs/whats-new/*.md')): show(f.split('/')[-1],open(f).read())
for f in sorted(glob.glob('content/flashcards/*.yml')):
    for c in yaml.safe_load(open(f))['flashcards']: show(c['id'],' '.join(str(c.get(k,'')) for k in ('front','back','explanation')))
for f in sorted(glob.glob('content/questions/*.yml')+glob.glob('content/comprehension/*.yml')):
    for q in yaml.safe_load(open(f)).get('questions',[]):
        if q.get('pool')=='HOLDOUT': continue
        show(q['pool'][:4]+':'+q['id'],q['question']+' || '+q.get('explanation','')+' || '+' | '.join(c['text']+' '+c.get('explanation','') for c in q['choices']))
