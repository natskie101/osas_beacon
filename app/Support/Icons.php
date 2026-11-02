<?php

declare(strict_types=1);

namespace App\Support;

final class Icons
{
    /** @var array<string, string> */
    private const PATHS = [
        'dashboard' => '<rect x="3" y="3" width="7.5" height="7.5" rx="1.5"/><rect x="13.5" y="3" width="7.5" height="4.5" rx="1.5"/><rect x="13.5" y="10.5" width="7.5" height="10.5" rx="1.5"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="1.5"/>',
        'megaphone' => '<path d="M3 11v2a1 1 0 0 0 1 1h2l6 4V6L6 10H4a1 1 0 0 0-1 1Z"/><path d="M16 9a4 4 0 0 1 0 6"/><path d="M18.5 6.5a8 8 0 0 1 0 11"/>',
        'scale' => '<path d="M12 3v18"/><path d="M7 5h10"/><path d="M5 21h14"/><path d="M6 5 3 12h6L6 5Z"/><path d="M18 5l-3 7h6l-3-7Z"/>',
        'graduation' => '<path d="M2.5 8.5 12 4l9.5 4.5L12 13 2.5 8.5Z"/><path d="M6 10.8V16c0 1.7 2.7 3 6 3s6-1.3 6-3v-5.2"/><path d="M21 9v5"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/>',
        'activity' => '<path d="M3 20h18"/><path d="M6 20V11M11 20V6M16 20v-6M21 20V9"/>',
        'headset' => '<path d="M4 14v-2a8 8 0 0 1 16 0v2"/><path d="M4 14h3v5H5.5A1.5 1.5 0 0 1 4 17.5V14Z"/><path d="M20 14h-3v5h1.5a1.5 1.5 0 0 0 1.5-1.5V14Z"/><path d="M20 19v1a2 2 0 0 1-2 2h-3"/>',
        'bell' => '<path d="M18 15V10a6 6 0 1 0-12 0v5l-1.5 3h15L18 15Z"/><path d="M10 21h4"/>',
        'users' => '<circle cx="9" cy="8" r="3.4"/><path d="M3 20c0-3.3 2.7-5.5 6-5.5s6 2.2 6 5.5"/><path d="M16 5.2a3.4 3.4 0 0 1 0 6.6"/><path d="M18.5 20c0-2.2-.7-3.9-2-5"/>',
        'fileText' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h4"/>',
        'search' => '<circle cx="11" cy="11" r="6.5"/><path d="M16 16l4.5 4.5"/>',
        'filter' => '<path d="M3 5h18l-7 8v6l-4 2v-8L3 5Z"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'close' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'check' => '<path d="M4.5 12.5 9.5 17.5 19.5 6.5"/>',
        'checkCircle' => '<circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.8 2.8L16 9.5"/>',
        'eye' => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/>',
        'eyeOff' => '<path d="M3 3l18 18"/><path d="M10.6 6.1A9.9 9.9 0 0 1 12 6c6 0 9.5 6 9.5 6a17 17 0 0 1-2.9 3.5"/><path d="M6.4 8.3A16.4 16.4 0 0 0 2.5 12s3.5 6 9.5 6c1.3 0 2.5-.3 3.5-.7"/><path d="M9.9 10.1a3 3 0 0 0 4.1 4.2"/>',
        'alert' => '<path d="M12 4 2.8 20h18.4L12 4Z"/><path d="M12 10v4.5M12 17.4v.2"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7.5V12l3.5 2"/>',
        'download' => '<path d="M12 3v11"/><path d="M8 11l4 4 4-4"/><path d="M4 19h16"/>',
        'refresh' => '<path d="M20 11a8 8 0 0 0-13.5-4.6L4 9"/><path d="M4 5v4h4"/><path d="M4 13a8 8 0 0 0 13.5 4.6L20 15"/><path d="M20 19v-4h-4"/>',
        'chevronLeft' => '<path d="M14.5 5.5 8 12l6.5 6.5"/>',
        'chevronRight' => '<path d="M9.5 5.5 16 12l-6.5 6.5"/>',
        'arrowRight' => '<path d="M4 12h15"/><path d="M13 6l6 6-6 6"/>',
        'arrowUp' => '<path d="M12 20V5"/><path d="M6 11l6-6 6 6"/>',
        'arrowDown' => '<path d="M12 4v15"/><path d="M6 13l6 6 6-6"/>',
        'send' => '<path d="M4 12 20 4l-6.5 16-3-6.5L4 12Z"/>',
        'shield' => '<path d="M12 3l7 3v6c0 4.4-3 7.7-7 9-4-1.3-7-4.6-7-9V6l7-3Z"/><path d="M9 12l2 2 4-4"/>',
        'target' => '<circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="4.5"/><circle cx="12" cy="12" r="1"/>',
        'broadcast' => '<circle cx="12" cy="12" r="2.5"/><path d="M5.5 5.5a9 9 0 0 0 0 13M18.5 18.5a9 9 0 0 0 0-13"/><path d="M8.5 8.5a5 5 0 0 0 0 7M15.5 15.5a5 5 0 0 0 0-7"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3.5 7l8.5 6 8.5-6"/>',
        'lock' => '<rect x="5" y="10.5" width="14" height="10" rx="2"/><path d="M8.5 10.5V8a3.5 3.5 0 0 1 7 0v2.5"/>',
        'user' => '<circle cx="12" cy="8.5" r="3.6"/><path d="M4.5 20c0-3.6 3.4-6 7.5-6s7.5 2.4 7.5 6"/>',
        'upload' => '<path d="M12 20V9"/><path d="M8 12l4-4 4 4"/><path d="M4 4h16"/>',
        'edit' => '<path d="M4 20h4l10-10-4-4L4 16v4Z"/><path d="M14.5 5.5 18.5 9.5"/>',
        'trash' => '<path d="M4 7h16"/><path d="M9 7V4.8h6V7"/><path d="M6.5 7l1 13h9l1-13"/><path d="M10.5 11v5M13.5 11v5"/>',
        'external' => '<path d="M14 4h6v6"/><path d="M20 4l-8 8"/><path d="M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'logout' => '<path d="M15 4h3a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-3"/><path d="M10 8l-4 4 4 4"/><path d="M6 12h9"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5.5M12 7.8v.2"/>',
        'printer' => '<path d="M7 9V4h10v5"/><rect x="4" y="9" width="16" height="7" rx="1.5"/><path d="M7 14h10v6H7z"/>',
        'layers' => '<path d="M12 3 3 8l9 5 9-5-9-5Z"/><path d="M3 13.5 12 18.5l9-5"/>',
        'pin' => '<path d="M12 21s6-5.5 6-10a6 6 0 1 0-12 0c0 4.5 6 10 6 10Z"/><circle cx="12" cy="11" r="2.4"/>',
        'dot' => '<circle cx="12" cy="12" r="3.4"/>',
    ];

    public static function render(string $name, int $size = 16, string $class = ''): string
    {
        $inner = self::PATHS[$name] ?? self::PATHS['dot'];
        $classAttribute = $class !== '' ? ' class="' . e($class) . '"' : '';

        return '<svg viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="currentColor" '
            . 'stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"' . $classAttribute . '>'
            . $inner . '</svg>';
    }

    public static function beacon(int $size = 40, float $strokeWidth = 2.2): string
    {
        return '<svg viewBox="0 0 64 64" width="' . $size . '" height="' . $size . '" class="beacon-mark" '
            . 'fill="none" stroke="currentColor" stroke-width="' . $strokeWidth . '" '
            . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            . '<path d="M32 4v5"/><path d="M25.5 14h13"/><path d="M27 14l1-5h8l1 5"/>'
            . '<path d="M27 14h10l4 26H23l4-26Z"/><path d="M21.5 40h21"/><path d="M18.5 46h27"/>'
            . '<path d="M32 16v24"/><path d="M24 25h16M25 34h14"/>'
            . '<path d="M20 12 12 6M44 12l8-6"/><path d="M19 20H9M45 20h10"/>'
            . '<path d="M17 54c4-2.6 7.6-2.6 11 0s7.6 2.6 11 0" stroke-opacity=".7"/>'
            . '</svg>';
    }
}
