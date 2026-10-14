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

    /**
     * The lot's comprehension check (ADR-0010): the last page of the lot's own
     * sidebar category, beside the courses it tests.
     */
    public static function forComprehension(string $lot): string
    {
        return '/docs/'.self::comprehensionPath($lot);
    }

    public static function comprehensionPath(string $lot): string
    {
        return 'courses/'.self::slug($lot).'/controle-de-comprehension';
    }

    /**
     * The anchor of a course section. The generator writes it on the heading
     * as an explicit id, and the payloads link to it, so the two cannot
     * disagree the way a re-implementation of Docusaurus's slugger could. The
     * `s-` prefix keeps it clear of the ids Docusaurus derives for the
     * headings the generator adds around the course.
     */
    public static function sectionAnchor(string $heading): string
    {
        return 's-'.self::slug(str_replace('`', '', $heading));
    }

    public static function forSection(OfficialItem $item, string $heading): string
    {
        return self::forItem($item).'#'.self::sectionAnchor($heading);
    }

    public static function slug(string $value): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        $lower = mb_strtolower(false !== $ascii ? $ascii : $value);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $lower) ?? $lower;

        return trim($slug, '-') ?: 'item';
    }
}
