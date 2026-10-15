"""Deep audit 8.0, step D. Flag French-prose « string(s) » whose syntax reads as a chain
(the governing verb: terminer, traverser, interrompre...). Needs spaCy and
fr_core_news_sm; review every flag by hand.

    python3 tools/audit/deep80/sense.py

Original note: Courses, French flashcards,
matrix French fields, glossary notes. Code fences and inline code removed first."""
import glob,re,yaml,spacy
import pathlib, os
os.chdir(pathlib.Path(__file__).resolve().parents[3])
nlp=spacy.load('fr_core_news_sm')
CHAIN={'terminer','traverser','exécuter','interrompre','arrêter','casser','parcourir','ordonner','fermer','continuer','compléter','réduire','retenir','insérer','déclarer','retirer','appeler','suivre','briser'}
STRINGY={'vide','littéral','littérale','brut','brute','JSON','majuscule','caractère','format','pointé','pointée','arbitraire','libre'}
def clean(t): return re.sub(r'`[^`\n]*`',' X ',re.sub(r'^```.*?^```',' ',t,flags=re.S|re.M))
src=[]
for f in glob.glob('content/courses/*.md'): src.append((f.split('/')[-1],clean(open(f).read().split('\n---\n',1)[-1])))
for f in glob.glob('content/flashcards/*.yml'):
    for c in yaml.safe_load(open(f))['flashcards']:
        if c.get('language')=='fr': src.append((c['id'],clean(' '.join(str(c.get(k,'')) for k in ('front','back','explanation','memorization_justification')))))
for it in yaml.safe_load(open('docs/syllabus/syllabus-matrix.yml'))['items']:
    src.append((it['id'],clean(' '.join([str(it.get('content_level_justification','')),str(it.get('minimum_evidence',''))]+[o['outcome'] for o in it['learning_outcomes']]))))
for e in yaml.safe_load(open('docs/syllabus/glossary.yml'))['entries']:
    if e.get('note'): src.append(('glossary:'+e['en'],clean(e['note'])))
n=0;flag=0
for where,t in src:
    if 'string' not in t: continue
    for tok in nlp(t):
        if tok.lower_ not in ('string','strings'): continue
        n+=1
        rel={tok.head.lemma_}|{c.lemma_ for c in tok.children}
        if (tok.head.lemma_ in CHAIN or any(c.lemma_ in CHAIN for c in tok.children)) and not rel&STRINGY:
            flag+=1; s=tok.sent.text.replace('\n',' ')
            print(f'{where}: [{tok.dep_}<-{tok.head.lemma_}] …{s[:170]}…')
print(f'# {n} occurrences read, {flag} flagged for review')
