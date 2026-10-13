<?php

declare(strict_types=1);

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

return [
    // F2: a present key does not guarantee the callback is skipped.
    'cache.callback-runs-on-hit-with-beta-inf' => static function (): ?string {
        $cache = new ArrayAdapter();
        $cache->get('k', static fn (): string => 'v1');
        if (!$cache->getItem('k')->isHit()) {
            return 'the key was not stored before the second call';
        }
        $ran = false;
        $hitInside = null;
        $value = $cache->get('k', static function (ItemInterface $item) use (&$ran, &$hitInside): string {
            $ran = true;
            $hitInside = $item->isHit();

            return 'v2';
        }, \INF);

        return match (true) {
            !$ran => 'with beta=INF the callback did not run on a present key',
            true !== $hitInside => 'isHit() was not true inside the recomputing callback',
            'v2' !== $value => "get() returned $value instead of the recomputed v2",
            'v2' !== $cache->getItem('k')->get() => 'the recomputed value was not saved',
            default => null,
        };
    },
    // F2: with no early expiration (beta=0) a fresh hit is served without the callback,
    // and beta=0 does not disable the cache itself.
    'cache.beta-zero-serves-hit' => static function (): ?string {
        $cache = new ArrayAdapter();
        $cache->get('k', static fn (): string => 'v1', 0.0);
        $ran = false;
        $value = $cache->get('k', static function () use (&$ran): string {
            $ran = true;

            return 'v2';
        }, 0.0);

        if ($ran || 'v1' !== $value) {
            return 'beta=0 did not serve the stored value';
        }
        // Default beta, no expiry: never elected early, whatever the draw.
        $cache->get('n', static fn (): string => 'v1');
        for ($i = 0; $i < 200; ++$i) {
            if ('v1' !== $cache->get('n', static fn (): string => 'v2')) {
                return 'an item without expiry was recomputed early with the default beta';
            }
        }

        return null;
    },
    // F2: $save=false returns the value without storing it.
    'cache.save-false-not-stored' => static function (): ?string {
        $cache = new ArrayAdapter();
        $value = $cache->get('k', static function (ItemInterface $item, bool &$save): string {
            $save = false;

            return 'transient';
        });
        if ('transient' !== $value) {
            return 'get() did not return the callback value';
        }

        return $cache->getItem('k')->isHit() ? 'the value was stored although $save was false' : null;
    },
    // F2 (pool isolation): two adapters on one directory are isolated by their
    // namespace, not by being two objects.
    'cache.pools-isolated-by-namespace' => static function (): ?string {
        $dir = sys_get_temp_dir().'/cert-behavior-'.bin2hex(random_bytes(4));
        try {
            $a = new FilesystemAdapter('', 0, $dir);
            $b = new FilesystemAdapter('', 0, $dir);
            $a->get('k', static fn (): string => 'from-a');
            if ('from-a' !== $b->get('k', static fn (): string => 'from-b')) {
                return 'two adapters with the same namespace and directory did not share the key';
            }
            $c = new FilesystemAdapter('pool_c', 0, $dir);
            if ('from-c' !== $c->get('k', static fn (): string => 'from-c')) {
                return 'a different namespace on the same directory saw the other pool\'s key';
            }

            return null;
        } finally {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($dir);
        }
    },
];
