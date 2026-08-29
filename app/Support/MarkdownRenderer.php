<?php

namespace App\Support;

use League\CommonMark\CommonMarkConverter;

/**
 * Phase 9 — render page bodies (markdown) to safe HTML.
 *
 * We use `html_input => 'strip'` so even if an admin pastes a
 * `<script>` tag into the body, it's neutralised before the parser
 * sees it. Combined with `allow_unsafe_links => false`, this gives
 * us Markdown's expressiveness without giving admins an XSS
 * vector.
 *
 * The converter is built once per request via the singleton
 * registration in `AppServiceProvider::register()`.
 */
class MarkdownRenderer
{
    private CommonMarkConverter $converter;

    public function __construct()
    {
        $this->converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 100,
            'renderer' => [
                'soft_break' => "<br>\n",
            ],
        ]);
    }

    public function render(string $markdown): string
    {
        if (trim($markdown) === '') {
            return '';
        }
        return (string) $this->converter->convert($markdown);
    }
}
