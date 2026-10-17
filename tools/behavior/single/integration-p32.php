<?php

declare(strict_types=1);

/*
 * Remediation P3.2 (2026-10-17): questions whose situation crosses components,
 * so that the rule deciding the answer belongs to one and the trap to another
 * (docs/policy/first-exposure.md). One boolean per choice, in content order,
 * compared by run.php with the `correct` flags of the YAML.
 */

namespace CertBehavior\P32Integration;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\RequestPayloadValueResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Controller\ErrorController;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\EventListener\ErrorListener;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

final class OrderInput
{
    public function __construct(
        #[Assert\Positive]
        public int $quantity = 0,
    ) {
    }
}

/** The status a body gets through #[MapRequestPayload], in a kernel wired as the framework wires it. */
function payloadStatus(string $contentType, string $body, int $validationFailed = 422): int
{
    // As the framework service: the object normalizer receives property_info,
    // so types are checked during denormalization.
    $types = new PropertyInfoExtractor([], [new ReflectionExtractor()]);
    $serializer = new Serializer([new ObjectNormalizer(null, null, null, $types)], [new JsonEncoder()]);
    $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    $payload = new RequestPayloadValueResolver($serializer, $validator);

    $dispatcher = new EventDispatcher();
    $dispatcher->addSubscriber($payload);
    $controller = 422 === $validationFailed
        ? static fn (#[MapRequestPayload] OrderInput $input): Response => new Response((string) $input->quantity)
        : static fn (#[MapRequestPayload(validationFailedStatusCode: 409)] OrderInput $input): Response => new Response((string) $input->quantity);
    $resolver = new class($controller) implements ControllerResolverInterface {
        public function __construct(private \Closure $controller)
        {
        }

        public function getController(Request $request): callable|false
        {
            // The error sub-request carries ErrorController in `_controller`.
            return $request->attributes->get('_controller') ?? $this->controller;
        }
    };
    $kernel = new HttpKernel($dispatcher, $resolver, new RequestStack(), new ArgumentResolver(null, [$payload, ...ArgumentResolver::getDefaultArgumentValueResolvers()]));
    $dispatcher->addSubscriber(new ErrorListener(new ErrorController($kernel, null, new HtmlErrorRenderer(false)), null, false));

    return $kernel->handle(Request::create('/orders', 'POST', server: ['CONTENT_TYPE' => $contentType], content: $body))->getStatusCode();
}

return [
    // {"quantity": "three"} into int $quantity: 422 | 400 | 415 | 500
    'QST-xfq1w6p4khfq' => static function (): array {
        $status = payloadStatus('application/json', '{"quantity": "three"}');
        // The distractors' own cases, so their explanations are executed too.
        $malformed = payloadStatus('application/json', '{"quantity": ');
        $noDecoder = payloadStatus('text/plain', '{"quantity": 3}');
        if (400 !== $malformed || 415 !== $noDecoder || 200 !== payloadStatus('application/json', '{"quantity": 3}')) {
            throw new \LogicException(sprintf('Unexpected baseline: malformed %d, no decoder %d.', $malformed, $noDecoder));
        }
        // The course's claim: validationFailedStatusCode moves the 422, not the 400.
        if (409 !== payloadStatus('application/json', '{"quantity": "three"}', 409)
            || 400 !== payloadStatus('application/json', '{"quantity": ', 409)) {
            throw new \LogicException('validationFailedStatusCode does not behave as the course states.');
        }

        return [422 === $status, 400 === $status, 415 === $status, 500 === $status];
    },

    // a kernel.request listener at priority 64 reads _route: null | 'product_show' | '/product/7' | '7'
    'QST-ayq8xg4be7j8' => static function (): array {
        $routes = new RouteCollection();
        $routes->add('product_show', new Route('/product/{id}', ['_controller' => static fn (): Response => new Response('')]));
        $stack = new RequestStack();
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new RouterListener(new UrlMatcher($routes, new RequestContext()), $stack));
        $seen = [];
        foreach ([64, 16] as $priority) {
            $dispatcher->addListener(KernelEvents::REQUEST, static function (RequestEvent $event) use (&$seen, $priority): void {
                $seen[$priority] = $event->getRequest()->attributes->get('_route');
            }, $priority);
        }
        $resolver = new class implements ControllerResolverInterface {
            public function getController(Request $request): callable|false
            {
                return $request->attributes->get('_controller');
            }
        };
        (new HttpKernel($dispatcher, $resolver, $stack, new ArgumentResolver()))->handle(Request::create('/product/7'));
        // The explanation's claim: below 32, the same listener reads the name.
        if ('product_show' !== $seen[16]) {
            throw new \LogicException('A listener at 16 did not see the route.');
        }
        $route = $seen[64];

        return [null === $route, 'product_show' === $route, '/product/7' === $route, '7' === $route];
    },
];
