<?php

/**
 * Normalise operational contest dates from content.json (today, +30 days, Y-m-d).
 */
if (!function_exists('resolve_contest_config_date')) {
    function resolve_contest_config_date(string $raw, string $resolvedStart = ''): string
    {
        $raw = trim($raw);
        $token = strtolower($raw);
        if ($raw === '' || $token === 'today' || $token === 'oggi') {
            return date('Y-m-d');
        }
        if ($resolvedStart !== '' && ($raw[0] === '+' || $raw[0] === '-')) {
            $computed = strtotime($resolvedStart . ' ' . $raw);

            return $computed ? date('Y-m-d', $computed) : $raw;
        }
        if ($token === 'start+30' || $token === 'start+30days' || $token === 'start+30 days') {
            $base = $resolvedStart !== '' ? $resolvedStart : date('Y-m-d');

            return date('Y-m-d', strtotime($base . ' +30 days'));
        }

        return $raw;
    }
}

/**
 * Effective display dates: display_* from config, or fallback to operational dates.
 *
 * @param array<string, mixed> $content_config
 * @param array{start_date: string, end_date: string} $operational_dates
 * @return array{start_date: string, end_date: string}
 */
if (!function_exists('resolve_contest_display_dates')) {
    function resolve_contest_display_dates(array $content_config, array $operational_dates): array
    {
        $isYmd = static function (string $d): bool {
            return $d !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) === 1;
        };
        $ds = trim((string) ($content_config['display_start_date'] ?? ''));
        $de = trim((string) ($content_config['display_end_date'] ?? ''));

        return [
            'start_date' => $isYmd($ds) ? $ds : (string) ($operational_dates['start_date'] ?? ''),
            'end_date' => $isYmd($de) ? $de : (string) ($operational_dates['end_date'] ?? ''),
        ];
    }
}

/**
 * Resolve operational + display dates from raw content (manager preview).
 *
 * @param array<string, mixed> $content
 * @param array<string, mixed> $defaults
 * @return array{operational: array{start_date: string, end_date: string}, display: array{start_date: string, end_date: string}}
 */
if (!function_exists('resolve_contest_dates_from_content')) {
    function resolve_contest_dates_from_content(array $content, array $defaults): array
    {
        $startRaw = trim((string) ($content['start_date'] ?? $defaults['start_date'] ?? ''));
        $endRaw = trim((string) ($content['end_date'] ?? $defaults['end_date'] ?? ''));
        $operational = [
            'start_date' => resolve_contest_config_date($startRaw),
            'end_date' => resolve_contest_config_date($endRaw, resolve_contest_config_date($startRaw)),
        ];
        $display = resolve_contest_display_dates($content, $operational);

        return ['operational' => $operational, 'display' => $display];
    }
}

/**
 * Formatted display dates for manager previews (waiting page, placeholders).
 *
 * @param array<string, mixed> $content
 * @param array<string, mixed> $defaults
 */
if (!function_exists('contest_manager_preview_formatted_dates')) {
    function contest_manager_preview_formatted_dates(array $content, array $defaults, string $dateFormat): array
    {
        $resolved = resolve_contest_dates_from_content($content, $defaults);
        $display = $resolved['display'];
        $format = static function (string $ymd) use ($dateFormat): string {
            if ($ymd === '') {
                return '';
            }
            if (function_exists('format_contest_date')) {
                return format_contest_date($ymd, $dateFormat);
            }
            $ts = strtotime($ymd);

            return $ts ? date($dateFormat, $ts) : $ymd;
        };

        return [
            'start' => $format($display['start_date']),
            'end' => $format($display['end_date']),
            'start_raw' => $display['start_date'],
            'end_raw' => $display['end_date'],
        ];
    }
}
