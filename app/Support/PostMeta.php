<?php

namespace App\Support;

/**
 * Phase 2 — small helpers for computing metadata that we expose through
 * the API. Kept in a dedicated class so the rules are unit-testable and
 * consistent across endpoints.
 */
class PostMeta
{
    public const WORDS_PER_MINUTE = 200;

    /**
     * Reading time in whole minutes. Minimum of 1.
     */
    public static function readingTime(?string $content): int
    {
        if ($content === null || trim($content) === '') {
            return 1;
        }

        $wordCount = self::wordCount($content);

        return max(1, (int) ceil($wordCount / self::WORDS_PER_MINUTE));
    }

    public static function wordCount(?string $content): int
    {
        if ($content === null || trim($content) === '') {
            return 0;
        }

        // strip_tags so a post like "<p>foo</p> <p>bar</p>" counts 2 words,
        // not 7 (3 html tags + 4 spaces).
        $plain = trim(strip_tags($content));
        if ($plain === '') {
            return 0;
        }

        return str_word_count($plain);
    }
}
