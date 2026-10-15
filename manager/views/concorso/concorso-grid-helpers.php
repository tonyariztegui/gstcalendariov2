<?php

declare(strict_types=1);

require_once __DIR__ . '/concorso-constants.php';

if (!function_exists('concorso_manager_preview_detect_root_rewrite')) {
    function concorso_manager_preview_detect_root_rewrite(string $baseDir): bool
    {
        static $cache = [];

        if (array_key_exists($baseDir, $cache)) {
            return $cache[$baseDir];
        }

        $result = false;
        $htaccess = $baseDir . '/.htaccess';
        if (is_file($htaccess)) {
            $content = @file_get_contents($htaccess);
            $publicRewrite = ltrim(CONCORSO_PATH_PUBLIC, '/') . '$1';
            if (is_string($content) && stripos($content, $publicRewrite) !== false) {
                $result = true;
            }
        }

        $cache[$baseDir] = $result;
        return $result;
    }
}

if (!function_exists('concorso_manager_preview_base_points_to_public')) {
    function concorso_manager_preview_base_points_to_public(string $base): bool
    {
        return $base !== ''
            && preg_match('~' . preg_quote(rtrim(CONCORSO_PATH_PUBLIC, '/'), '~') . '$~i', $base) === 1;
    }
}

if (!function_exists('concorso_manager_preview_build_base_url')) {
    function concorso_manager_preview_build_base_url(
        string $cleanPath,
        string $base,
        bool $basePointsToPublic,
        bool $rootRewriteToPublic
    ): string {
        if ($basePointsToPublic || $rootRewriteToPublic) {
            return $base . '/' . $cleanPath;
        }

        return $base . CONCORSO_PATH_PUBLIC . $cleanPath;
    }
}

if (!function_exists('concorso_manager_preview_build_relative_url')) {
    function concorso_manager_preview_build_relative_url(string $cleanPath, bool $rootRewriteToPublic): string
    {
        return $rootRewriteToPublic
            ? ('../' . $cleanPath)
            : ('..' . CONCORSO_PATH_PUBLIC . $cleanPath);
    }
}

if (!function_exists('concorso_manager_preview_url')) {
    function concorso_manager_preview_url(string $path, string $baseUrl, string $baseDir): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $base = rtrim($baseUrl, '/');
        $cleanPath = ltrim($path, '/');
        $pathExistsAtRoot = is_file($baseDir . DIRECTORY_SEPARATOR . $cleanPath);
        $pathExistsInPublic = is_file($baseDir . CONCORSO_PATH_PUBLIC . $cleanPath);
        $basePointsToPublic = concorso_manager_preview_base_points_to_public($base);
        $rootRewriteToPublic = concorso_manager_preview_detect_root_rewrite($baseDir);

        if ($pathExistsAtRoot) {
            return $base !== '' ? ($base . '/' . $cleanPath) : ('../' . $cleanPath);
        }

        if ($pathExistsInPublic) {
            if ($base !== '') {
                return concorso_manager_preview_build_base_url(
                    $cleanPath,
                    $base,
                    $basePointsToPublic,
                    $rootRewriteToPublic
                );
            }

            return concorso_manager_preview_build_relative_url($cleanPath, $rootRewriteToPublic);
        }

        if ($base !== '') {
            if ($basePointsToPublic) {
                return $base . '/' . $cleanPath;
            }

            return concorso_manager_preview_build_base_url($cleanPath, $base, false, $rootRewriteToPublic);
        }

        return concorso_manager_preview_build_relative_url($cleanPath, $rootRewriteToPublic);
    }
}

if (!function_exists('concorso_parse_position_order_standard_ref')) {
    /**
     * @param array<string, array<string, mixed>> $standard_fields_map
     * @param array<string, bool> $fields_enabled
     */
    function concorso_parse_position_order_standard_ref(
        string $field_ref,
        array $standard_fields_map,
        array $fields_enabled
    ): ?int {
        if ($field_ref === 'prov_pdv' || $field_ref === 'citta_pdv') {
            $pdvPos = null;
            foreach ($standard_fields_map as $field_info) {
                if (($field_info['name'] ?? '') === 'indirizzo_pdv') {
                    $pdvPos = (int) ($field_info['position'] ?? 0);
                    break;
                }
            }
            if ($pdvPos !== null && $pdvPos > 0) {
                return $field_ref === 'citta_pdv' ? $pdvPos + 1 : $pdvPos;
            }
            return $field_ref === 'citta_pdv' ? 10 : 9;
        }

        foreach ($standard_fields_map as $field_info) {
            if ($field_info['name'] !== $field_ref) {
                continue;
            }

            $field_name = $field_info['name'];
            $is_enabled = ($fields_enabled[$field_name] ?? true) === true;
            if ($is_enabled) {
                return (int) $field_info['position'];
            }
        }

        return null;
    }
}

if (!function_exists('concorso_parse_position_order_custom_ref')) {
    /**
     * @param array<string, array<string, mixed>> $standard_fields_map
     * @param array<int, array<string, mixed>> $custom_fields
     * @param array<string, bool> $fields_enabled
     * @param array<int, string> $visited
     */
    function concorso_parse_position_order_custom_ref(
        string $field_ref,
        array $standard_fields_map,
        array $custom_fields,
        array $fields_enabled,
        array $visited
    ): ?int {
        foreach ($custom_fields as $customField) {
            if (!is_array($customField) || !isset($customField['id']) || $customField['id'] !== $field_ref) {
                continue;
            }

            if (!isset($customField['position_order'])) {
                return 5;
            }

            $posOrderValue = $customField['position_order'];
            if (is_numeric($posOrderValue)) {
                return (int) $posOrderValue;
            }

            $nextVisited = $visited;
            $nextVisited[] = $field_ref;
            $parsed = parse_position_order(
                $posOrderValue,
                $standard_fields_map,
                $custom_fields,
                $fields_enabled,
                $nextVisited
            );

            return (int) $parsed['position_order'];
        }

        return null;
    }
}

if (!function_exists('parse_position_order')) {
    /**
     * @param mixed $position_order_value
     * @param array<string, array<string, mixed>> $standard_fields_map
     * @param array<int, array<string, mixed>> $custom_fields
     * @param array<string, bool> $fields_enabled
     * @param array<int, string> $visited
     * @return array{position_order: int, target_field_ref: ?string}
     */
    function parse_position_order(
        $position_order_value,
        $standard_fields_map,
        $custom_fields,
        $fields_enabled = [],
        $visited = []
    ) {
        if (is_numeric($position_order_value)) {
            return [
                'position_order' => (int) $position_order_value,
                'target_field_ref' => null,
            ];
        }

        if (!preg_match('/^(before|after)_(s|c)_(.+)$/', (string) $position_order_value, $matches)) {
            return [
                'position_order' => 5,
                'target_field_ref' => null,
            ];
        }

        $placement = $matches[1];
        $field_type = $matches[2];
        $field_ref = $matches[3];

        if ($field_type === 'c' && in_array($field_ref, $visited, true)) {
            return [
                'position_order' => 5,
                'target_field_ref' => null,
            ];
        }

        $target_field_ref = $field_type . '_' . $field_ref;
        $ref_position_order = $field_type === 's'
            ? concorso_parse_position_order_standard_ref($field_ref, $standard_fields_map, $fields_enabled)
            : concorso_parse_position_order_custom_ref(
                $field_ref,
                $standard_fields_map,
                $custom_fields,
                $fields_enabled,
                $visited
            );

        if ($ref_position_order === null) {
            return [
                'position_order' => 5,
                'target_field_ref' => null,
            ];
        }

        return [
            'position_order' => $placement === 'before' ? $ref_position_order : $ref_position_order + 1,
            'target_field_ref' => $target_field_ref,
        ];
    }
}

if (!function_exists('concorso_organize_fields_collect_standard')) {
    /**
     * @param array<string, array<string, mixed>> $standard_fields_map
     * @param array<string, bool> $fields_enabled
     * @return array<int, array{original_letter: string, field_info: array<string, mixed>, assigned_letter: string}>
     */
    function concorso_organize_fields_collect_standard(array $standard_fields_map, array $fields_enabled): array
    {
        $enabled_standard_fields = [];
        foreach ($standard_fields_map as $letter => $field_info) {
            $field_name = $field_info['name'];
            $is_enabled = ($fields_enabled[$field_name] ?? true) === true;
            if ($is_enabled) {
                $enabled_standard_fields[] = [
                    'original_letter' => $letter,
                    'field_info' => $field_info,
                ];
            }
        }

        usort($enabled_standard_fields, static function (array $a, array $b): int {
            return $a['field_info']['position'] - $b['field_info']['position'];
        });

        $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T'];
        $letter_index = 0;
        foreach ($enabled_standard_fields as &$enabled_field) {
            $enabled_field['assigned_letter'] = $letters[$letter_index] ?? '?';
            $letter_index++;
        }
        unset($enabled_field);

        return $enabled_standard_fields;
    }
}

if (!function_exists('concorso_organize_fields_assign_custom_letter')) {
    /**
     * @param array<int, string> $all_letters
     */
    function concorso_organize_fields_assign_custom_letter(int $custom_letter_index, array $all_letters): string
    {
        if ($custom_letter_index < count($all_letters)) {
            return $all_letters[$custom_letter_index];
        }

        $double_index = $custom_letter_index - count($all_letters);
        $first_index = (int) ($double_index / count($all_letters));
        $second_index = $double_index % count($all_letters);

        return $all_letters[$first_index] . $all_letters[$second_index];
    }
}

if (!function_exists('concorso_organize_fields_build_all_fields')) {
    /**
     * @param array<int, array{original_letter: string, field_info: array<string, mixed>, assigned_letter: string}> $enabled_standard_fields
     * @param array<string, array<string, mixed>> $standard_fields_map
     * @param array<int, array<string, mixed>> $custom_fields
     * @param array<string, bool> $fields_enabled
     * @return array<int, array<string, mixed>>
     */
    function concorso_organize_fields_build_all_fields(
        array $enabled_standard_fields,
        array $standard_fields_map,
        array $custom_fields,
        array $fields_enabled
    ): array {
        $all_fields = [];

        foreach ($enabled_standard_fields as $enabled_field) {
            $all_fields[] = [
                'type' => 'standard',
                'letter' => $enabled_field['assigned_letter'],
                'field' => $enabled_field['field_info'],
                'position_order' => $enabled_field['field_info']['position'],
                'size' => $enabled_field['field_info']['size'],
                'positions_count' => get_positions_count($enabled_field['field_info']['size']),
            ];
        }

        $custom_letter_index = count($enabled_standard_fields);
        $all_letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        $custom_index = 0;

        foreach ($custom_fields as $customField) {
            if (!is_array($customField)) {
                continue;
            }

            $position_order_value = $customField['position_order'] ?? 5;
            $parsed = parse_position_order($position_order_value, $standard_fields_map, $custom_fields, $fields_enabled);
            $position_order = $parsed['position_order'];
            $target_field_ref = $parsed['target_field_ref'] ?? ($customField['target_field_ref'] ?? null);
            $size = get_field_size($customField);
            $customFieldWithRef = $customField;
            if ($target_field_ref !== null) {
                $customFieldWithRef['target_field_ref'] = $target_field_ref;
            }

            $all_fields[] = [
                'type' => 'custom',
                'letter' => concorso_organize_fields_assign_custom_letter($custom_letter_index, $all_letters),
                'field' => $customFieldWithRef,
                'position_order' => $position_order,
                'size' => $size,
                'positions_count' => get_positions_count($size),
                'custom_index' => $custom_index,
                'target_field_ref' => $target_field_ref,
            ];
            $custom_index++;
            $custom_letter_index++;
        }

        return $all_fields;
    }
}

if (!function_exists('concorso_organize_fields_compare')) {
    /**
     * @param array<string, mixed> $a
     * @param array<string, mixed> $b
     */
    function concorso_organize_fields_compare(array $a, array $b): int
    {
        if ($a['position_order'] != $b['position_order']) {
            return $a['position_order'] - $b['position_order'];
        }
        if ($a['type'] === 'custom' && $b['type'] === 'standard') {
            return -1;
        }
        if ($a['type'] === 'standard' && $b['type'] === 'custom') {
            return 1;
        }
        if ($a['type'] === 'custom' && $b['type'] === 'custom') {
            $a_index = isset($a['custom_index']) ? $a['custom_index'] : 999;
            $b_index = isset($b['custom_index']) ? $b['custom_index'] : 999;

            return $a_index - $b_index;
        }

        return 0;
    }
}

if (!function_exists('concorso_organize_fields_sort')) {
    /**
     * @param array<int, array<string, mixed>> $all_fields
     */
    function concorso_organize_fields_sort(array &$all_fields): void
    {
        usort($all_fields, 'concorso_organize_fields_compare');
    }
}

if (!function_exists('concorso_organize_fields_target_end_by_ref')) {
    /**
     * @param array<int, array<string, mixed>> $grid
     */
    function concorso_organize_fields_target_end_by_ref(?string $target_field_ref, array $grid): int
    {
        if ($target_field_ref === null || !preg_match('/^(s|c)_(.+)$/', $target_field_ref, $ref_matches)) {
            return 0;
        }

        $ref_type = $ref_matches[1];
        $ref_name = $ref_matches[2];

        foreach ($grid as $pos => $grid_item) {
            if ($ref_type === 's' && $grid_item['type'] === 'standard') {
                if ($grid_item['field']['name'] === $ref_name) {
                    return $pos + ($grid_item['positions_count'] - 1);
                }
            } elseif ($ref_type === 'c' && $grid_item['type'] === 'custom') {
                if (isset($grid_item['field']['id']) && $grid_item['field']['id'] === $ref_name) {
                    return $pos + ($grid_item['positions_count'] - 1);
                }
            }
        }

        return 0;
    }
}

if (!function_exists('concorso_organize_fields_target_end_by_order')) {
    /**
     * @param array<int, array<string, mixed>> $grid
     */
    function concorso_organize_fields_target_end_by_order(int $target_position_order, array $grid): int
    {
        $target_end_position = 0;

        foreach ($grid as $pos => $grid_item) {
            $grid_pos_order = null;
            if ($grid_item['type'] === 'standard') {
                $grid_pos_order = $grid_item['field']['position'];
            } elseif ($grid_item['type'] === 'custom' && isset($grid_item['position_order'])) {
                $grid_pos_order = $grid_item['position_order'];
            }

            if ($grid_pos_order !== null && $grid_pos_order == $target_position_order) {
                $end_pos = $pos + ($grid_item['positions_count'] - 1);
                if ($end_pos > $target_end_position) {
                    $target_end_position = $end_pos;
                }
            }
        }

        return $target_end_position;
    }
}

if (!function_exists('concorso_organize_fields_resolve_start_position')) {
    /**
     * @param array<string, mixed> $item
     * @param array<int, array<string, mixed>> $grid
     */
    function concorso_organize_fields_resolve_start_position(array $item, array $grid): int
    {
        if ($item['type'] === 'standard') {
            return (int) $item['position_order'];
        }

        $target_position_order = (int) $item['position_order'] - 1;
        $target_field_ref = $item['target_field_ref'] ?? ($item['field']['target_field_ref'] ?? null);
        $target_end_position = concorso_organize_fields_target_end_by_ref($target_field_ref, $grid);

        if ($target_end_position === 0) {
            $target_end_position = concorso_organize_fields_target_end_by_order($target_position_order, $grid);
        }

        if ($target_end_position > 0) {
            return $target_end_position + 1;
        }

        return (int) $item['position_order'];
    }
}

if (!function_exists('concorso_organize_fields_find_slot')) {
    /**
     * @param array<int, bool> $occupied
     */
    function concorso_organize_fields_find_slot(int $start_position, int $positions_count, array $occupied): ?int
    {
        for ($pos = $start_position; $pos <= 100; $pos++) {
            $available = true;
            for ($i = 0; $i < $positions_count; $i++) {
                if (isset($occupied[$pos + $i])) {
                    $available = false;
                    break;
                }
            }
            if ($available) {
                return $pos;
            }
        }

        return null;
    }
}

if (!function_exists('concorso_organize_fields_place_item')) {
    /**
     * @param array<int, array<string, mixed>> $grid
     * @param array<int, bool> $occupied
     * @param array<string, mixed> $item
     */
    function concorso_organize_fields_place_item(array &$grid, array &$occupied, array $item, int $found_position): void
    {
        $grid[$found_position] = [
            'type' => $item['type'],
            'letter' => $item['letter'],
            'field' => $item['field'],
            'size' => $item['size'],
            'positions_count' => $item['positions_count'],
        ];

        if ($item['type'] === 'custom') {
            $grid[$found_position]['position_order'] = $item['position_order'];
        }

        for ($i = 0; $i < $item['positions_count']; $i++) {
            $occupied[$found_position + $i] = true;
        }
    }
}

if (!function_exists('organize_fields_in_grid')) {
    /**
     * @param array<string, array<string, mixed>> $standard_fields_map
     * @param array<int, array<string, mixed>> $custom_fields
     * @param array<string, bool> $fields_enabled
     * @return array<int, array<string, mixed>>
     */
    function organize_fields_in_grid($standard_fields_map, $custom_fields, $fields_enabled = [])
    {
        $grid = [];
        $occupied = [];

        $enabled_standard_fields = concorso_organize_fields_collect_standard($standard_fields_map, $fields_enabled);
        $all_fields = concorso_organize_fields_build_all_fields(
            $enabled_standard_fields,
            $standard_fields_map,
            $custom_fields,
            $fields_enabled
        );
        concorso_organize_fields_sort($all_fields);

        foreach ($all_fields as $item) {
            $start_position = concorso_organize_fields_resolve_start_position($item, $grid);
            $found_position = concorso_organize_fields_find_slot(
                $start_position,
                (int) $item['positions_count'],
                $occupied
            );

            if ($found_position !== null) {
                concorso_organize_fields_place_item($grid, $occupied, $item, $found_position);
            }
        }

        ksort($grid);

        return $grid;
    }
}
