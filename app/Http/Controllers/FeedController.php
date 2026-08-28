<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Response;

/**
 * Phase 3 — RSS / Atom feed.
 *
 *   GET /feed.xml  -> Atom 1.0 feed of the latest 30 published posts.
 *
 * Output is plain XML; we use a tiny hand-built template so we don't
 * have to pull in spatie/laravel-feed or similar. Validates with
 * `xmllint --noout`.
 */
class FeedController extends Controller
{
    public function index(): Response
    {
        $posts = Post::with('author')
            ->published()
            ->orderBy('published_at', 'desc')
            ->limit(30)
            ->get();

        $base = rtrim(config('app.url'), '/');

        $updated = $posts->first()?->updated_at?->toAtomString()
            ?? now()->toAtomString();

        $entries = $posts->map(function (Post $post) use ($base) {
            $url = $base.'/blog/'.$post->slug;
            $body = $this->summary($post);

            return "    <entry>\n"
                .'      <title>'.$this->xml($post->title)."</title>\n"
                ."      <id>{$url}</id>\n"
                ."      <link href=\"{$url}\"/>\n"
                .'      <updated>'.$post->updated_at->toAtomString()."</updated>\n"
                .'      <published>'.optional($post->published_at)->toAtomString()."</published>\n"
                .'      <author><name>'.$this->xml(optional($post->author)->name ?? 'Anonymous')."</name></author>\n"
                .'      <summary type="text">'.$this->xml($body)."</summary>\n"
                .'    </entry>';
        })->implode("\n");

        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <title>Jarir Blog</title>
  <subtitle>Insightful articles, news, and stories.</subtitle>
  <link href="{$base}/"/>
  <link href="{$base}/feed.xml" rel="self"/>
  <id>{$base}/</id>
  <updated>{$updated}</updated>
{$entries}
</feed>
XML;

        return response($xml, 200, [
            'Content-Type' => 'application/atom+xml; charset=utf-8',
        ]);
    }

    private function summary(Post $post): string
    {
        $text = $post->excerpt ?: $post->content;

        return mb_substr(strip_tags($text), 0, 280);
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
