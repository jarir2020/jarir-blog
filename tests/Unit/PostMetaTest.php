<?php

namespace Tests\Unit;

use App\Support\PostMeta;
use PHPUnit\Framework\TestCase;

/**
 * Phase 2 — PostMeta unit tests.
 *
 * Pure logic, no Laravel boot. These are the rules the API server-side
 * reading-time and the search-related word count rely on.
 */
class PostMetaTest extends TestCase
{
    public function test_reading_time_is_at_least_one_minute(): void
    {
        $this->assertSame(1, PostMeta::readingTime(null));
        $this->assertSame(1, PostMeta::readingTime(''));
        $this->assertSame(1, PostMeta::readingTime('   '));
    }

    public function test_reading_time_short_content_is_one_minute(): void
    {
        // 50 words = 50/200 = 0.25 → ceil = 1.
        $content = str_repeat('word ', 50);
        $this->assertSame(1, PostMeta::readingTime($content));
    }

    public function test_reading_time_400_words_is_two_minutes(): void
    {
        $content = str_repeat('word ', 400);
        $this->assertSame(2, PostMeta::readingTime($content));
    }

    public function test_reading_time_2000_words_is_ten_minutes(): void
    {
        $content = str_repeat('word ', 2000);
        $this->assertSame(10, PostMeta::readingTime($content));
    }

    public function test_word_count_strips_html_tags(): void
    {
        // Use a space between the blocks so strip_tags doesn't glue words
        // together; the production code is not expected to fix that for us.
        $content = "<p>Hello world</p>\n<p>This is a test</p>";
        $this->assertSame(6, PostMeta::wordCount($content));
    }

    public function test_word_count_returns_zero_for_empty(): void
    {
        $this->assertSame(0, PostMeta::wordCount(null));
        $this->assertSame(0, PostMeta::wordCount(''));
        $this->assertSame(0, PostMeta::wordCount('   '));
    }

    public function test_word_count_returns_zero_for_only_html(): void
    {
        $this->assertSame(0, PostMeta::wordCount('<p></p><br><img src="x.jpg">'));
    }
}
