"""Deep audit 8.0, step A: every 7.4 -> 8.0 change, from the pinned sources.

Reads UPGRADE-8.0.md and the « 8.0 » section of each component CHANGELOG at the
tag v8.0.15 (e8cac3037ed57dfd9fd03edc84186bc0497901e9), and writes
docs/audit/deep-8.0/entries.json: one row per entry, {component, source, text}.

    python3 tools/audit/deep80/fetch_entries.py
"""
import json
import pathlib
import re
import urllib.request

TAG = 'v8.0.15'
RAW = f'https://raw.githubusercontent.com/symfony/symfony/{TAG}/'
ROOT = pathlib.Path(__file__).resolve().parents[3]
COMPONENTS = ('AssetMapper BrowserKit Cache Config Console DependencyInjection DomCrawler ExpressionLanguage Form '
              'HtmlSanitizer HttpFoundation HttpClient HttpKernel Intl JsonStreamer Ldap Mailer Messenger Mime Notifier '
              'OptionsResolver PropertyAccess PropertyInfo Routing Serializer Translation String TypeInfo Uid Validator '
              'VarExporter VarDumper Webhook Workflow Yaml EventDispatcher Filesystem Finder Process Clock Lock RateLimiter '
              'Asset Stopwatch Runtime ErrorHandler Semaphore Scheduler ObjectMapper').split()
PATHS = {c: f'src/Symfony/Component/{c}/CHANGELOG.md' for c in COMPONENTS}
PATHS |= {b: f'src/Symfony/Bundle/{b}/CHANGELOG.md' for b in ('FrameworkBundle', 'SecurityBundle', 'TwigBundle', 'WebProfilerBundle')}
PATHS |= {f'{b}Bridge': f'src/Symfony/Bridge/{b}/CHANGELOG.md' for b in ('Twig', 'Doctrine', 'Monolog')}
PATHS |= {f'Security{c}': f'src/Symfony/Component/Security/{c}/CHANGELOG.md' for c in ('Core', 'Http', 'Csrf')}


def get(path):
    try:
        with urllib.request.urlopen(RAW + path, timeout=30) as r:
            return r.read().decode()
    except Exception:
        return None


entries = []
for name, path in sorted(PATHS.items()):
    text = get(path)
    m = text and re.search(r'^8\.0\n-+\n(.*?)(?=^\d+\.\d+\n-+\n|\Z)', text, re.M | re.S)
    for item in re.split(r'\n(?= ?\* )', m.group(1)) if m else []:
        if item.strip().startswith('*'):
            entries.append({'component': name, 'source': 'CHANGELOG', 'text': item.strip().split('\n')[0].lstrip('* ').strip()})
component = None
lines = get('UPGRADE-8.0.md').split('\n')
for i, line in enumerate(lines):
    if re.fullmatch(r'[A-Z][A-Za-z]+', line) and i + 1 < len(lines) and set(lines[i + 1]) == {'-'}:
        component = line
        continue
    m = re.match(r'^ \* (.*)', line)
    if m and component:
        entries.append({'component': component, 'source': 'UPGRADE', 'text': m.group(1)})
out = ROOT / 'docs/audit/deep-8.0/entries.json'
out.write_text(json.dumps(entries, ensure_ascii=False, indent=1) + '\n', encoding='utf-8')
print(f'{len(entries)} entries ({TAG}) -> {out.relative_to(ROOT)}')
