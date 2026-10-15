<?php

declare(strict_types=1);

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\Psr16Adapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Contracts\Cache\ItemInterface;

$tempDir = static fn (): string => sys_get_temp_dir().'/cert-behavior-'.bin2hex(random_bytes(4));
$removeDir = static function (string $dir): void {
    if (!is_dir($dir)) {
        return;
    }
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($dir);
};

/*
 * The F2 checks run on both implementations of get(): ArrayAdapter has its own,
 * every adapter extending AbstractAdapter (FilesystemAdapter here) goes through
 * Contracts' CacheTrait::doGet(). Until 2026-10-15 they ran on ArrayAdapter
 * only while the facts cite CacheTrait, and a mutation of CacheTrait left them
 * green (deep audit, docs/audit/deep-8.0/). $onEachGet runs $check on a fresh
 * pool of each kind and returns the first failure, prefixed by the adapter.
 */
$onEachGet = static function (callable $check) use ($tempDir, $removeDir): ?string {
    $dir = $tempDir();
    try {
        foreach (['ArrayAdapter' => static fn () => new ArrayAdapter(), 'FilesystemAdapter' => static fn () => new FilesystemAdapter('f2', 0, $dir)] as $name => $make) {
            if (null !== $failure = $check($make())) {
                return "$name: $failure";
            }
        }

        return null;
    } finally {
        $removeDir($dir);
    }
};

return [
    // F2: a present key does not guarantee the callback is skipped.
    'cache.callback-runs-on-hit-with-beta-inf' => static function () use ($onEachGet): ?string {
        return $onEachGet(static function ($cache): ?string {
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
        });
    },
    // F2: with no early expiration (beta=0) a fresh hit is served without the callback,
    // and beta=0 does not disable the cache itself.
    'cache.beta-zero-serves-hit' => static function () use ($onEachGet): ?string {
        return $onEachGet(static function ($cache): ?string {
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
        });
    },
    // F2: $save=false returns the value without storing it.
    'cache.save-false-not-stored' => static function () use ($onEachGet): ?string {
        return $onEachGet(static function ($cache): ?string {
            $value = $cache->get('k', static function (ItemInterface $item, bool &$save): string {
                $save = false;

                return 'transient';
        });
        if ('transient' !== $value) {
            return 'get() did not return the callback value';
        }

        return $cache->getItem('k')->isHit() ? 'the value was stored although $save was false' : null;
        });
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
    // P1.4: PSR-6 writes only on save(); a stored null is a hit.
    'cache.psr6-save-and-null' => static function (): ?string {
        $pool = new ArrayAdapter();
        $item = $pool->getItem('k');
        if ($item->isHit() || null !== $item->get()) {
            return 'a miss was not isHit() false with get() null';
        }
        $item->set('x');
        if ($pool->getItem('k')->isHit()) {
            return 'set() without save() stored the value';
        }
        $item->set(null);
        $pool->save($item);
        $again = $pool->getItem('k');
        if (!$again->isHit() || null !== $again->get()) {
            return 'a saved null was not a hit holding null';
        }
        $pool->deleteItem('k');

        return $pool->getItem('k')->isHit() ? 'deleteItem() left the item' : null;
    },
    // P1.4: saveDeferred() is invisible to another pool until commit(), and a
    // getItem() on the same key of the deferring pool commits it.
    'cache.psr6-deferred-commit' => static function () use ($tempDir, $removeDir): ?string {
        $dir = $tempDir();
        try {
            $pool = new FilesystemAdapter('ns', 0, $dir);
            $other = new FilesystemAdapter('ns', 0, $dir);
            $pool->saveDeferred($pool->getItem('a')->set('v'));
            if ($other->getItem('a')->isHit()) {
                return 'a deferred item was visible to another pool before commit';
            }
            if (!$pool->getItem('a')->isHit() || !$other->getItem('a')->isHit()) {
                return 'getItem() on the deferring pool did not commit the item';
            }
            $pool->saveDeferred($pool->getItem('b')->set('v'));
            $pool->commit();

            if (!$other->getItem('b')->isHit()) {
                return 'commit() did not persist the deferred item';
            }
            // ArrayAdapter's saveDeferred() is save(): no deferral at all.
            $array = new ArrayAdapter();
            $array->saveDeferred($array->getItem('c')->set('v'));
            $snapshot = $array->getValues();

            return isset($snapshot['c']) ? null : 'ArrayAdapter deferred an item instead of saving it';
        } finally {
            $removeDir($dir);
        }
    },
    // P1.4: PSR-16 returns the default on a miss; has() tells a stored null apart.
    'cache.psr16-default-and-has' => static function (): ?string {
        $cache = new Psr16Cache(new ArrayAdapter());
        if ('dflt' !== $cache->get('missing', 'dflt')) {
            return 'a miss did not return the default';
        }
        $cache->set('z', null);

        return null === $cache->get('z', 'dflt') && $cache->has('z')
            ? null
            : 'a stored null was not returned as null, or has() was false';
    },
    // P1.4: Psr16Cache turns a PSR-6 pool into PSR-16; Psr16Adapter turns PSR-16
    // back into a pool that also speaks the Cache Contracts.
    'cache.psr-bridges' => static function (): ?string {
        $psr16 = new Psr16Cache(new ArrayAdapter());
        $pool = new Psr16Adapter($psr16);
        if (!$psr16 instanceof Psr\SimpleCache\CacheInterface) {
            return 'Psr16Cache is not a PSR-16 cache';
        }
        if (!$pool instanceof Psr\Cache\CacheItemPoolInterface || !$pool instanceof Symfony\Contracts\Cache\CacheInterface) {
            return 'Psr16Adapter is not both a PSR-6 pool and a Contracts cache';
        }

        return 'computed' === $pool->get('k', static fn (): string => 'computed') && $psr16->has('k')
            ? null
            : 'a value computed through the Contracts did not reach the PSR-16 cache';
    },
];
