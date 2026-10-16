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
 ('form.collection-extra-entries-refused','form/Extension/Core/EventListener/ResizeFormListener.php','        // Add all additional rows\n        if ($this->allowAdd) {','        // Add all additional rows\n        if (true) {'),
 ('form.collection-delete-needs-allow-delete','form/Extension/Core/EventListener/ResizeFormListener.php','        // Remove all empty rows\n        if ($this->allowDelete) {','        // Remove all empty rows\n        if (true) {'),
 ('form.collection-prototype','form/Extension/Core/Type/CollectionType.php',"        if ($options['allow_add'] && $options['prototype']) {","        if ($options['prototype']) {"),
 ('form.by-reference-object-collection','form/Extension/Core/DataAccessor/PropertyPathAccessor.php','        if (!\\is_object($data) || !$form->getConfig()->getByReference() || $value !== $getValue()) {','        if (true) {'),
 ('form.property-path-and-inherit-data','form/Form.php','        if ($this->inheritData = $config->getInheritData()) {','        if ($this->inheritData = false) {'),
 ('validator.unexpected-value-vs-type','validator/Validator/RecursiveContextualValidator.php','            } catch (UnexpectedValueException $e) {','            } catch (\\LogicException $e) {'),
 ('http-client.unchecked-status-throws-on-destruct','http-client/Response/TransportResponseTrait.php',"        if ($this->initializer && null === $this->info['error'] && !$this->didTimeout) {",'        if (false) {'),
 ('http-client.retry-count-and-last-response','http-client/RetryableHttpClient.php','        private int $maxRetries = 3,','        private int $maxRetries = 2,'),
 ('http-client.retry-by-status-and-method','http-client/Retry/GenericRetryStrategy.php','        500 => self::IDEMPOTENT_METHODS,','        500,'),
 ('http-client.retry-delay-and-retry-after','http-client/Retry/GenericRetryStrategy.php',"        $delay = $this->delayMs * $this->multiplier ** $context->getInfo('retry_count');",'        $delay = $this->delayMs;'),
 ('http-client.retry-delay-and-retry-after','http-client/RetryableHttpClient.php','                return (int) ($after * 1000);','                return null;'),
 ('http-client.stream-error-chunk','http-client/Response/TransportResponseTrait.php',"                        $chunk->getContent();\n                        throw new \\LogicException('A transport exception should have been thrown.');",'                        $chunk->didThrow(true);'),
 ('http-client.stream-status-at-first-chunk','http-client/Response/TransportResponseTrait.php','                                $response->getHeaders(true);','                                $response->getHeaders(false);'),
 ('http-client.stream-timeout-chunk','http-client/Chunk/ErrorChunk.php','    public function isTimeout(): bool\n    {\n        $this->didThrow = true;','    public function isTimeout(): bool\n    {\n        return false;'),
 ('console.signal-exits-zero-by-default','console/Event/ConsoleSignalEvent.php','        private int|false $exitCode = 0,','        private int|false $exitCode = 130,'),
 ('console.signalable-command-return','console/Application.php','                            $exitCode = $command->handleSignal($signal, $exitCode);','                            $command->handleSignal($signal, $exitCode);'),
 ('console.alarm-event','console/Application.php','                            $this->dispatcher->dispatch($alarmEvent);','                            // the alarm event is not dispatched'),
 ('console.command-tester-dispatches-nothing','console/Application.php','        if (null === $this->dispatcher) {\n            try {\n                return $command->run($input, $output);','        if (true) {\n            try {\n                return $command->run($input, $output);'),
 ('console.interact-before-validation','console/Command/Command.php','        if ($input->isInteractive()) {\n            $this->interact($input, $output);','        if (true) {\n            $this->interact($input, $output);'),
 ('console.tester-stderr-separately','console/Helper/QuestionHelper.php','        if ($output instanceof ConsoleOutputInterface) {\n            $output = $output->getErrorOutput();','        if (false) {\n            $output = $output->getErrorOutput();'),
 ('profiler.collector-lifecycle','framework-bundle/DataCollector/AbstractDataCollector.php','        return static::class;',"        return 'collector';"),
 ('profiler.collector-lifecycle','http-kernel/Profiler/Profiler.php','            $profile->addCollector(clone $collector);','            $profile->addCollector($collector);'),
 ('profiler.collector-lifecycle','http-kernel/Profiler/Profiler.php','                $collector->lateCollect();','                // late collect skipped'),
 ('profiler.serialized-reset-disabled','http-kernel/DataCollector/DataCollector.php','        $this->data = [];','        // data kept'),
 ('profiler.serialized-reset-disabled','http-kernel/Profiler/Profiler.php','        if (false === $this->enabled) {','        if (false) {'),
 ('profiler.collector-in-functional-test','framework-bundle/KernelBrowser.php','        if ($this->profiler) {\n            $this->profiler = false;','        if ($this->profiler) {\n            // still enabled for the next request'),
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
