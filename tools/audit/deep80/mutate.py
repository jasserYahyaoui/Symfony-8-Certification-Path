"""Deep audit 8.0, step C. Source-mutation proof: change the Symfony line carrying a behaviour, assert the house check
FAILS, restore, assert SHA-256 identical. Vendor files only; the repository is never touched.

A check that stays green when the Symfony line carrying its behaviour is broken
proves nothing about that line. Each row names the check, the vendor file, the
line and its mutation; the run prints PROVED or VACUOUS and the SHA-256 restore.

    python3 tools/audit/deep80/mutate.py
"""
import hashlib,subprocess,sys,pathlib
ROOT=pathlib.Path(__file__).resolve().parents[3]
V=ROOT/'tools/behavior/vendor/symfony'
M=[
 ('validator.url-requires-tld-by-default','validator/Constraints/Url.php','    public bool $requireTld = true;','    public bool $requireTld = false;'),
 ('validator.options-array-refused','validator/Constraints/Choice.php','        if (null !== $options) {','        if (false && null !== $options) {'),
 ('validator.custom-constraint-defaults','validator/Constraint.php',"        return static::class.'Validator';","        return static::class.'Checker';"),
 ('cache.callback-runs-on-hit-with-beta-inf','cache-contracts/CacheTrait.php','        $recompute = !$item->isHit() || \\INF === $beta;','        $recompute = !$item->isHit();'),
 ('cache.callback-runs-on-hit-with-beta-inf','cache/Adapter/ArrayAdapter.php','        if (\\INF === $beta || !$item->isHit()) {','        if (!$item->isHit()) {'),
 ('http-kernel.response-controller-skips-view','http-kernel/HttpKernel.php','        if (!$response instanceof Response) {','        if (true) {'),
 ('cache.save-false-not-stored','cache-contracts/CacheTrait.php','            if ($save) {','            if (true) {'),
 ('http-kernel.request-short-circuit','http-kernel/HttpKernel.php','        if ($event->hasResponse()) {\n            return $this->filterResponse($event->getResponse(), $request, $type);','        if (false) {\n            return $this->filterResponse($event->getResponse(), $request, $type);'),
 ('http-kernel.unconverted-view-throws','http-kernel/HttpKernel.php','                throw new ControllerDoesNotReturnResponseException($msg','                return new Response(\'\'); throw new ControllerDoesNotReturnResponseException($msg'),
 ('serializer.first-supporting-normalizer-wins','serializer/Serializer.php','            if ($cached || $normalizer->supportsNormalization($data, $format, $context)) {\n                return $normalizer;','            if ($cached || $normalizer->supportsNormalization($data, $format, $context)) {\n                $found ??= $normalizer; continue;'),
 ('cache.save-false-not-stored','cache/Adapter/ArrayAdapter.php','            if ($save) {','            if (true) {'),
 ('console.setcode-closure-typed-and-int','console/Command/InvokableCommand.php','            if (!$type instanceof \\ReflectionNamedType) {','            if (false) {'),
 # Three guards raise a TypeError for a non-int result; the check reads Symfony's message, so
 # removing Symfony's is_int() guard alone must make it fail.
 ('console.setcode-closure-typed-and-int','console/Command/InvokableCommand.php','        if (!\\is_int($statusCode)) {','        if (false) {'),
 ('config.required-node-has-no-default','config/Definition/Builder/NodeDefinition.php','        if ($this->required) {','        if (false) {'),
 ('config.required-node-has-no-default','config/Definition/Builder/NodeDefinition.php','        if ($this->default) {','        if (false) {'),
 # G6 is an absence: the mutation restores __toString() and must turn the check red.
 ('translation.translatable-not-stringable','translation/TranslatableMessage.php',('class TranslatableMessage implements TranslatableInterface','    public function getMessage(): string'),('class TranslatableMessage implements TranslatableInterface, \\Stringable','    public function __toString(): string { return $this->message; }\n\n    public function getMessage(): string')),
 ('validator.unexpected-value-vs-type','validator/Validator/RecursiveContextualValidator.php','            } catch (UnexpectedValueException $e) {','            } catch (\\LogicException $e) {'),
]
ok=True
for check,f,old,new in M:
    p=V/f; b=p.read_bytes(); h=hashlib.sha256(b).hexdigest()
    s=b.decode()
    for o,n in zip(*((old,new) if isinstance(old,tuple) else ((old,),(new,)))):
        assert s.count(o)==1,(f,o)
        s=s.replace(o,n)
    p.write_text(s)
    try:
        out=subprocess.run(['php','tools/behavior/run.php'],cwd=ROOT,capture_output=True,text=True).stdout
    finally:
        p.write_bytes(b)
    line=next((l for l in out.splitlines() if l.split(' ')[1:2]==[check]),'(check not reported)')
    same=hashlib.sha256(p.read_bytes()).hexdigest()==h
    fired=line.startswith('FAIL')
    ok&=fired and same
    print(('PROVED ' if fired else 'VACUOUS'),check,'|',line[:110],'| restore', 'identical' if same else 'DIFF')
print('ALL PROVED' if ok else 'NOT PROVED'); sys.exit(0 if ok else 1)
