<?php

namespace Tests\Unit;

use App\Models\Widget;
use App\Support\SidebarResolver;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the YouTube URL parser and the video widget
 * resolution. Lives in tests/Unit/ so it doesn't need the
 * Laravel test base (no DB, no schema, no factory).
 */
class YouTubeUrlTest extends TestCase
{
    private SidebarResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new SidebarResolver();
    }

    /**
     * Use reflection to drive the private parseYouTubeUrls method.
     * Cheaper than building a real Widget model.
     */
    private function parse(string $raw): array
    {
        $r = new \ReflectionMethod(SidebarResolver::class, 'parseYouTubeUrls');
        $r->setAccessible(true);
        return $r->invoke($this->resolver, $raw);
    }

    public function test_empty_string_returns_empty_array(): void
    {
        $this->assertSame([], $this->parse(''));
    }

    public function test_blank_lines_are_skipped(): void
    {
        $this->assertSame([], $this->parse("\n\n  \n\n"));
    }

    public function test_watch_url_is_parsed(): void
    {
        $v = $this->parse('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
        $this->assertCount(1, $v);
        $this->assertSame('dQw4w9WgXcQ', $v[0]['id']);
        $this->assertSame('https://www.youtube.com/embed/dQw4w9WgXcQ', $v[0]['embed_url']);
        $this->assertSame('https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg', $v[0]['thumbnail_url']);
    }

    public function test_short_url_is_parsed(): void
    {
        $v = $this->parse('https://youtu.be/oHg5SJYRHA4');
        $this->assertSame('oHg5SJYRHA4', $v[0]['id']);
    }

    public function test_embed_url_is_parsed(): void
    {
        $v = $this->parse('https://www.youtube.com/embed/abcDEF12345');
        $this->assertSame('abcDEF12345', $v[0]['id']);
    }

    public function test_shorts_url_is_parsed(): void
    {
        $v = $this->parse('https://www.youtube.com/shorts/abcDEF12345');
        $this->assertSame('abcDEF12345', $v[0]['id']);
    }

    public function test_bare_id_is_parsed(): void
    {
        $v = $this->parse('dQw4w9WgXcQ');
        $this->assertSame('dQw4w9WgXcQ', $v[0]['id']);
    }

    public function test_invalid_id_is_skipped(): void
    {
        $v = $this->parse('not-a-youtube-url');
        $this->assertSame([], $v);
    }

    public function test_too_short_id_is_rejected(): void
    {
        // 10 chars is one short of the 11-char YouTube id format.
        $v = $this->parse('dQw4w9WgXc');
        $this->assertSame([], $v);
    }

    public function test_duplicate_ids_are_deduplicated(): void
    {
        $v = $this->parse("https://www.youtube.com/watch?v=dQw4w9WgXcQ\nhttps://youtu.be/dQw4w9WgXcQ");
        $this->assertCount(1, $v, 'Same id in different URL shapes should appear once.');
    }

    public function test_mixed_urls_each_parsed(): void
    {
        $v = $this->parse("https://www.youtube.com/watch?v=AAAAAAAAAAA\nhttps://youtu.be/BBBBBBBBBBB\nCCCCCCCCCCC");
        $this->assertCount(3, $v);
        $this->assertSame('AAAAAAAAAAA', $v[0]['id']);
        $this->assertSame('BBBBBBBBBBB', $v[1]['id']);
        $this->assertSame('CCCCCCCCCCC', $v[2]['id']);
    }
}
