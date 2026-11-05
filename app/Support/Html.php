<?php

declare(strict_types=1);

namespace App\Support;

final class Html
{
    /**
     * Clean rich text from announcements, regulations and orientation content.
     * The original portal delivered these bodies as trusted HTML; the rewrite
     * still removes scripting, event handlers and javascript: URLs.
     */
    public static function clean(?string $html): string
    {
        $html = (string) ($html ?? '');

        if ($html === '') {
            return '';
        }

        $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><h3><h4><h5><blockquote><a><span><hr><table><thead><tbody><tr><th><td>';
        $html = strip_tags($html, $allowed);
        $html = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;
        $html = preg_replace('/\sstyle\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;
        $html = preg_replace('/(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2/i', '$1="#"', $html) ?? $html;
        $html = preg_replace('/(href|src)\s*=\s*(["\'])\s*data:text\/html[^"\']*\2/i', '$1="#"', $html) ?? $html;

        return trim($html);
    }

    public static function summary(?string $html, int $limit = 180): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($html ?? ''))) ?? '');

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $limit)) . '…';
    }
}
