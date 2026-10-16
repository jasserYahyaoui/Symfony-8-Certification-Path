<?php

declare(strict_types=1);

/*
 * Remediation P2.7 (Filesystem, Finder): a portable test environment — paths
 * written with "/", a temporary directory, a deterministic order. Executed on
 * Filesystem and Finder 8.0 under Linux: Path reads "\" and "C:" as a
 * separator and a root only when DIRECTORY_SEPARATOR is "\", so these checks
 * pin the Unix behaviour and fail on Windows rather than pass vacuously. Each
 * is proved by a mutation of the vendor line carrying it
 * (tools/audit/deep80/mutate.py).
 */

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

return [
    // Under Unix, "\" is a filename character and "c:\Windows" is relative; "/" paths canonicalize and join everywhere.
    'filesystem.portable-paths' => static function (): ?string {
        if ('/' !== \DIRECTORY_SEPARATOR) {
            return 'this check pins the Unix behaviour and must run where DIRECTORY_SEPARATOR is "/"';
        }
        $got = [
            Path::canonicalize('/var/www/../log/./x'),
            Path::join('a', '/b', 'c/'),
            Path::canonicalize('a\\b/../c'),
            (new Filesystem())->isAbsolutePath('c:\\Windows'),
            Path::isAbsolute('/tmp'),
            Path::makeRelative('/var/www/project/src', '/var/www'),
        ];
        $want = ['/var/log/x', 'a/b/c', 'c', false, true, 'project/src'];

        return $want === $got ? null : 'got '.json_encode($got);
    },

    // tempnam() creates an empty file with a unique name, prefix and suffix kept; remove() deletes the tree.
    'filesystem.tempnam-and-cleanup' => static function (): ?string {
        $fs = new Filesystem();
        $dir = sys_get_temp_dir().'/cert-fs-'.bin2hex(random_bytes(6));
        $fs->mkdir($dir.'/nested/deeper');
        $a = $fs->tempnam($dir, 'test_', '.txt');
        $b = $fs->tempnam($dir, 'test_', '.txt');
        $shape = [is_file($a), filesize($a), $a !== $b, str_starts_with(basename($a), 'test_'), str_ends_with($a, '.txt')];
        $fs->remove($dir);

        return match (true) {
            [true, 0, true, true, true] !== $shape => 'tempnam gave '.json_encode($shape),
            file_exists($dir) => 'remove() left the directory',
            default => null,
        };
    },

    // Finder order: sortByName() compares strings (f10 before f2), sortByName(true) naturally, reverseSorting() inverts.
    'finder.sort-for-a-deterministic-order' => static function (): ?string {
        $fs = new Filesystem();
        $dir = sys_get_temp_dir().'/cert-finder-'.bin2hex(random_bytes(6));
        $fs->mkdir($dir);
        foreach ([3, 10, 1, 2] as $n) {
            $fs->touch("$dir/f$n.txt");
        }
        $names = static fn (Finder $f): array => array_map(static fn ($file): string => $file->getFilename(), iterator_to_array($f, false));
        $base = static fn (): Finder => Finder::create()->files()->in($dir);
        $got = [
            $names($base()->sortByName()),
            $names($base()->sortByName(true)),
            $names($base()->sortByName(true)->reverseSorting()),
        ];
        $unsorted = $names($base());
        sort($unsorted);
        $fs->remove($dir);
        $want = [
            ['f1.txt', 'f10.txt', 'f2.txt', 'f3.txt'],
            ['f1.txt', 'f2.txt', 'f3.txt', 'f10.txt'],
            ['f10.txt', 'f3.txt', 'f2.txt', 'f1.txt'],
        ];

        return match (true) {
            $want !== $got => 'got '.json_encode($got),
            ['f1.txt', 'f10.txt', 'f2.txt', 'f3.txt'] !== $unsorted => 'the unsorted set differs: '.json_encode($unsorted),
            default => null,
        };
    },
];
