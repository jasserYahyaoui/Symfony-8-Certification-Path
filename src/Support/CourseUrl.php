<?php

declare(strict_types=1);

namespace CertPath\Support;

use CertPath\Domain\OfficialItem;

/**
 * Where an item's course page is published, derived once.
 *
 * Three callers need this path: DocsGenerator writes the page, it links the
 * revision agenda to it, and since Lot 27 PayloadBuilder ships it to Practice
 * Mode so a learner who got a question wrong can reach the course. Deriving it
 * three times from three copies of the slug rule is how a link starts pointing
 * at a route the build never produced — the failure this class exists to make
 * impossible, not merely unlikely.
 *
 * The slug is part of the published URL contract: it is what bookmarks and
 * internal links are built on, so it must not move when a label is reworded.
 */
final class CourseUrl
{
    public static function forItem(OfficialItem $item): string
    {
        return '/docs/courses/'.self::slug($item->lot).'/'.self::slug($item->officialItem);
    }

    /** The path under `website/docs/`, without the leading `/docs/`. */
    public static function pagePath(OfficialItem $item): string
    {
        return 'courses/'.self::slug($item->lot).'/'.self::slug($item->officialItem);
    }

    public static function slug(string $value): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        $lower = mb_strtolower(false !== $ascii ? $ascii : $value);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $lower) ?? $lower;

        return trim($slug, '-') ?: 'item';
    }
}
