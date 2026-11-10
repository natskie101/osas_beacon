<?php

declare(strict_types=1);

use App\Auth\ArrayUser;
use App\Enums\Role;
use Illuminate\Support\Facades\Auth;

if (!function_exists('absolute_url')) {
    function absolute_url(string $path = ''): string
    {
        $configured = rtrim((string) config('app.url', ''), '/');

        if ($configured !== '') {
            return $configured . ($path === '' ? '' : '/' . ltrim($path, '/'));
        }

        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

        return ($secure ? 'https' : 'http') . '://' . $host . url($path);
    }
}

if (!function_exists('auth_user')) {
    function auth_user(): ?array
    {
        $user = Auth::user();

        return $user instanceof ArrayUser ? $user->toArray() : null;
    }
}

if (!function_exists('auth_role')) {
    function auth_role(): ?Role
    {
        $user = auth_user();

        return $user === null ? null : Role::tryFrom((string) $user['role']);
    }
}

if (!function_exists('initials')) {
    function initials(?string $first, ?string $last): string
    {
        $a = strtoupper(substr(trim((string) $first), 0, 1));
        $b = strtoupper(substr(trim((string) $last), 0, 1));

        return ($a . $b) ?: 'UC';
    }
}

if (!function_exists('format_bytes')) {
    function format_bytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1048576) {
            return number_format($bytes / 1024, 1) . ' KB';
        }

        return number_format($bytes / 1048576, 1) . ' MB';
    }
}

if (!function_exists('google_drive_video_embed_url')) {
    function google_drive_video_embed_url(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');

        if (!in_array($scheme, ['http', 'https'], true) || $host !== 'drive.google.com') {
            return null;
        }

        if (preg_match('~^/file/d/([A-Za-z0-9_-]+)(?:/(?:preview|view))?/?$~', $path, $matches) === 1) {
            return 'https://drive.google.com/file/d/' . $matches[1] . '/preview';
        }

        if (in_array($path, ['/open', '/uc'], true)) {
            parse_str((string) ($parts['query'] ?? ''), $query);
            $fileId = (string) ($query['id'] ?? '');

            if (preg_match('~^[A-Za-z0-9_-]+$~', $fileId) === 1) {
                return 'https://drive.google.com/file/d/' . $fileId . '/preview';
            }
        }

        return null;
    }
}

if (!function_exists('video_embed_url')) {
    function video_embed_url(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        $driveEmbedUrl = google_drive_video_embed_url($url);

        if ($driveEmbedUrl !== null) {
            return $driveEmbedUrl;
        }

        if (preg_match('~youtu\.be/([A-Za-z0-9_-]{6,})~', $url, $matches) === 1) {
            return 'https://www.youtube.com/embed/' . $matches[1] . '?rel=0';
        }

        if (preg_match('~youtube\.com/watch\?.*v=([A-Za-z0-9_-]{6,})~', $url, $matches) === 1) {
            return 'https://www.youtube.com/embed/' . $matches[1] . '?rel=0';
        }

        if (preg_match('~youtube\.com/embed/([A-Za-z0-9_-]{6,})~', $url, $matches) === 1) {
            return 'https://www.youtube.com/embed/' . $matches[1] . '?rel=0';
        }

        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $matches) === 1) {
            return 'https://player.vimeo.com/video/' . $matches[1] . '?rel=0';
        }

        // Fallback for other providers: only https URLs may be embedded, and
        // never any URL containing credentials or unusual characters.
        if (
            str_starts_with(strtolower($url), 'https://')
            && filter_var($url, FILTER_VALIDATE_URL) !== false
            && !str_contains($url, '@')
        ) {
            return $url;
        }

        return null;
    }
}

if (!function_exists('fmt_date')) {
    function fmt_date(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? $value : date('M j, Y', $timestamp);
    }
}

if (!function_exists('fmt_datetime')) {
    function fmt_datetime(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? $value : date('M j, Y g:i A', $timestamp);
    }
}

if (!function_exists('time_ago')) {
    function time_ago(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $timestamp = strtotime($value);

        if ($timestamp === false) {
            return $value;
        }

        $diff = time() - $timestamp;

        if ($diff <= 1) {
            return 'Just now';
        }

        if ($diff < 60) {
            return $diff . ' secs ago';
        }

        if ($diff < 3600) {
            $minutes = (int) round($diff / 60);

            return $minutes . ($minutes === 1 ? ' min ago' : ' mins ago');
        }

        if ($diff < 86400) {
            $hours = (int) round($diff / 3600);

            return $hours . ($hours === 1 ? ' hr ago' : ' hrs ago');
        }

        if ($diff < 172800) {
            return 'Yesterday';
        }

        $days = (int) round($diff / 86400);

        return $days . ' days ago';
    }
}

if (!function_exists('db_datetime')) {
    function db_datetime(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }
}

if (!function_exists('datetime_input')) {
    function datetime_input(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? '' : date('Y-m-d\TH:i', $timestamp);
    }
}

if (!function_exists('body_html')) {
    function body_html(?string $value): string
    {
        return App\Support\Html::clean($value);
    }
}

if (!function_exists('json_items')) {
    function json_items(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('status_class')) {
    function status_class(?string $status): string
    {
        $key = strtolower(str_replace([' ', '&'], ['-', ''], (string) $status));

        return match ($key) {
            'active', 'published', 'sent', 'resolved', 'completed', 'verified', 'returned', 'released', 'on-track' => 'badge--published',
            'pending', 'open', 'warning', 'medium', 'claim-pending', 'processing' => 'badge--pending',
            'scheduled', 'in-progress', 'info', 'under-review' => 'badge--scheduled',
            'draft', 'archived', 'closed', 'inactive', 'cancelled', 'rejected' => 'badge--draft',
            'high', 'urgent', 'critical', 'overdue', 'at-risk' => 'badge--high',
            'low', 'recommended' => 'badge--low',
            default => 'badge--brand',
        };
    }
}
