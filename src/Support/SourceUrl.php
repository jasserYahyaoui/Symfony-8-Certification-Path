<?php

declare(strict_types=1);

namespace CertPath\Support;

/**
 * The one place a citation's readable URL is derived from its raw one.
 *
 * WHY TWO URLS EXIST. A citation has two jobs that pull in opposite
 * directions. It has to be *checkable* — fetched, byte for byte, at a pinned
 * ref — and it has to be *followable* by a learner, who wants a rendered page
 * with line numbers and a file tree, not a wall of reStructuredText. So the
 * canonical `url` stays on raw.githubusercontent.com, where it is verifiable
 * (177 of 177 citations answered 200 on 2026-09-15), and `readable_url` is the
 * github.com/blob form of the same object, which is what the site links to.
 *
 * WHY THE DUPLICATION IS SAFE. Two fields holding one fact drift the moment
 * somebody edits one of them. They cannot drift here: `readable_url` must be
 * exactly what this class derives from `url`, and rule SRC-002 refuses the
 * corpus when it is not. The stored field is a convenience for readers of the
 * YAML; the derivation below is the authority.
 *
 * WHAT IS NOT CLAIMED. That a blob URL is reachable from the build container.
 * github.com is gated by this session's repository-access policy and answers
 * 403 for an upstream repository, which is a fact about the sandbox and not
 * about the URL. Reachability is established on the raw form and inherited by
 * construction: same owner, same repository, same ref, same path, same object.
 */
final class SourceUrl
{
    /**
     * A raw file URL: host, owner, repository, ref, then the path.
     *
     * The ref is a single segment, and `refs/…` is excluded rather than left to
     * chance. GitHub also serves `raw/<owner>/<repo>/refs/heads/<branch>/<path>`,
     * whose blob equivalent drops the `refs/heads/` prefix — so a pattern that
     * merely takes the first segment turns it into `blob/refs/heads/8.0/…`, a
     * URL GitHub does not serve. The first version of this class did exactly
     * that while its comment claimed otherwise; the unit test for the form
     * caught it. No citation in this corpus uses it, and silently
     * mis-converting one is worse than leaving it alone, so it falls through
     * as "not a raw file URL".
     */
    private const string RAW = '#^https://raw\.githubusercontent\.com/([^/]+)/([^/]+)/(?!refs/)([^/?\#]+)/([^?\#]+)$#';

    /**
     * The github.com/blob form of a raw file URL.
     *
     * Anything that is not one is returned unchanged rather than mangled: a
     * citation this class does not understand keeps the URL its author wrote.
     */
    public static function readable(string $url): string
    {
        if (1 !== preg_match(self::RAW, $url, $m)) {
            return $url;
        }

        return \sprintf('https://github.com/%s/%s/blob/%s/%s', $m[1], $m[2], $m[3], $m[4]);
    }

    public static function isRawFileUrl(string $url): bool
    {
        return 1 === preg_match(self::RAW, $url);
    }
}
