<?php

declare(strict_types=1);

namespace App\Support;

final class Html
{
    /** @var list<string> */
    private const ALLOWED_TAGS = [
        'p', 'div', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li',
        'h1', 'h2', 'h3', 'h4', 'h5', 'blockquote', 'a', 'img', 'span',
        'hr', 'table', 'thead', 'tbody', 'tr', 'th', 'td',
    ];

    /** @var array<string, list<string>> */
    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'title'],
        'img' => ['src', 'alt', 'title'],
        'th' => ['colspan', 'rowspan'],
        'td' => ['colspan', 'rowspan'],
    ];

    private const VOID_TAGS = ['br', 'hr', 'img'];

    /**
     * Clean rich text from announcements, regulations and orientation content.
     *
     * Strategy: strip_tags with a tag allowlist, then rebuild every remaining
     * tag from scratch — dropping all attributes except a small per-tag
     * whitelist, and validating href/src URLs strictly. Event handlers, style
     * attributes and dangerous URL schemes therefore cannot survive, including
     * obfuscated variants (entities, control characters, mixed case).
     */
    public static function clean(?string $html): string
    {
        $html = (string) ($html ?? '');

        if ($html === '') {
            return '';
        }

        $allowed = '<' . implode('><', self::ALLOWED_TAGS) . '>';
        $html = strip_tags($html, $allowed);

        // Rebuild each allowed tag with only whitelisted attributes.
        $html = preg_replace_callback(
            '~<([a-zA-Z][a-zA-Z0-9]*)((?:"[^"]*"|\'[^\']*\'|[^"\'><])*)>~',
            self::rebuildTag(...),
            $html,
        ) ?? $html;

        // Escape any stray "<" that is not the start of a well-formed tag so a
        // browser cannot recover markup from malformed leftovers.
        $lessThanEntity = '&' . 'lt;';
        $html = preg_replace('~<(?![a-zA-Z/])~', $lessThanEntity, $html) ?? $html;

        return trim($html);
    }

    /** @param array{0: string, 1: string, 2: string} $match */
    private static function rebuildTag(array $match): string
    {
        $tag = strtolower($match[1]);
        $attributeString = $match[2];

        if (!in_array($tag, self::ALLOWED_TAGS, true)) {
            return '';
        }

        $allowedAttributes = self::ALLOWED_ATTRIBUTES[$tag] ?? [];
        $kept = [];

        if ($attributeString !== '') {
            preg_match_all(
                '~([a-zA-Z_:][-a-zA-Z0-9_:.]*)\s*(?:=\s*("|\')(.*?)\2|=\s*([^\s"\'>]+))?~s',
                $attributeString,
                $attributes,
                PREG_SET_ORDER,
            );

            foreach ($attributes as $attribute) {
                $name = strtolower((string) $attribute[1]);
                $value = (string) ($attribute[3] ?? $attribute[4] ?? '');

                if (!in_array($name, $allowedAttributes, true)) {
                    continue;
                }

                if (in_array($name, ['href', 'src'], true)) {
                    $value = self::safeUrl($value, $name);

                    if ($value === '') {
                        continue;
                    }
                }

                if (in_array($name, ['colspan', 'rowspan'], true) && !ctype_digit($value)) {
                    continue;
                }

                $kept[] = $name . '="' . htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
            }
        }

        $slash = in_array($tag, self::VOID_TAGS, true) ? '/' : '';

        return '<' . $tag . ($kept === [] ? '' : ' ' . implode(' ', $kept)) . $slash . '>';
    }

    /**
     * Accept only http(s), mailto, anchor and site-relative URLs. Entity
     * decoding happens first so encoded schemes (jav&#x0A;ascript:) are seen
     * for what they are, and any raw control character or space rejects the
     * URL outright — real URLs percent-encode those.
     */
    private static function safeUrl(string $value, string $attribute): string
    {
        $value = html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if ($value === '' || preg_match('~[\x00-\x20\x7f]~', $value) === 1) {
            return '';
        }

        if (str_starts_with($value, '#')) {
            return $value;
        }

        if (str_starts_with($value, '/') && !str_starts_with($value, '//')) {
            return $value;
        }

        if (preg_match('~^([a-zA-Z][a-zA-Z0-9+.-]*):~', $value, $scheme) !== 1) {
            return $value; // scheme-less relative URL
        }

        $scheme = strtolower((string) $scheme[1]);

        if ($scheme === 'mailto') {
            return $attribute === 'href' ? $value : '';
        }

        return in_array($scheme, ['http', 'https'], true) ? $value : '';
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
