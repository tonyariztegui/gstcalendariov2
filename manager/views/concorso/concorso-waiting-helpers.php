<?php

declare(strict_types=1);

require_once __DIR__ . '/concorso-constants.php';

/**
 * @param array<int|string, mixed> $blocks
 */
function concorso_build_waiting_text_block_html(array $block): ?string
{
    if (($block['type'] ?? 'text') !== 'text' || !isset($block['content'])) {
        return null;
    }
    $content = trim((string) $block['content']);

    return $content !== '' ? $content : null;
}

/**
 * @param array<string, mixed> $block
 */
function concorso_build_waiting_image_block_html(array $block): ?string
{
    if (($block['type'] ?? '') !== 'image' || empty($block['src'])) {
        return null;
    }
    $src = htmlspecialchars((string) $block['src'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $alt = htmlspecialchars((string) ($block['alt'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $width = trim((string) ($block['width'] ?? ''));
    $style = $width !== ''
        ? ' style="max-width: ' . htmlspecialchars($width, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ';"'
        : '';

    return '<p><img src="' . $src . '" alt="' . $alt . '"' . $style . '></p>';
}

/**
 * @param array<string, mixed> $block
 */
function concorso_build_waiting_button_block_html(array $block): ?string
{
    if (($block['type'] ?? '') !== 'button' || empty($block['text'])) {
        return null;
    }
    $text = htmlspecialchars((string) $block['text'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $url = htmlspecialchars((string) ($block['url'] ?? '#'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $target = trim((string) ($block['target'] ?? ''));
    $style = $block['style'] ?? [];
    $style_type = is_array($style) ? ($style['type'] ?? 'primary') : 'primary';
    $btn_class = $style_type === 'secondary' ? 'btn btn-secondary' : 'btn btn-success';
    $target_attr = $target !== ''
        ? ' target="' . htmlspecialchars($target, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"'
        : '';
    $rel_attr = $target === '_blank' ? ' rel="noopener noreferrer"' : '';

    return '<p><a class="' . $btn_class . ' btn-lg fw-bold text-uppercase" href="' . $url . '"' . $target_attr . $rel_attr . '>' . $text . '</a></p>';
}

/**
 * @param array<int|string, mixed> $blocks
 */
function concorso_build_waiting_blocks_html(array $blocks): string
{
    $chunks = [];
    foreach ($blocks as $block) {
        if (!is_array($block)) {
            continue;
        }
        $html = concorso_build_waiting_text_block_html($block)
            ?? concorso_build_waiting_image_block_html($block)
            ?? concorso_build_waiting_button_block_html($block);
        if ($html !== null) {
            $chunks[] = $html;
        }
    }

    return trim(implode("\n", $chunks));
}

/**
 * @param array<int|string, mixed> $items
 * @return array<int, mixed>
 */
function concorso_sort_waiting_items_by_order(array $items): array
{
    usort($items, static function ($a, $b): int {
        $orderA = is_array($a) ? ($a['order'] ?? 0) : 0;
        $orderB = is_array($b) ? ($b['order'] ?? 0) : 0;

        return $orderA <=> $orderB;
    });

    return $items;
}

/**
 * @param array<string, mixed> $container
 */
function concorso_extract_waiting_container_html(array $container): string
{
    $blocks = $container['blocks'] ?? [];
    if (!is_array($blocks)) {
        return '';
    }

    return concorso_build_waiting_blocks_html(concorso_sort_waiting_items_by_order($blocks));
}

/**
 * @param mixed $waitingPage
 */
function concorso_extract_waiting_html($waitingPage): string
{
    if (!is_array($waitingPage)) {
        return '';
    }
    if (isset($waitingPage['html']) && is_string($waitingPage['html'])) {
        return $waitingPage['html'];
    }
    $containers = $waitingPage['containers'] ?? [];
    if (!is_array($containers)) {
        return '';
    }
    $chunks = [];
    foreach ($containers as $container) {
        if (!is_array($container)) {
            continue;
        }
        $chunk = concorso_extract_waiting_container_html($container);
        if ($chunk !== '') {
            $chunks[] = $chunk;
        }
    }

    return trim(implode("\n", $chunks));
}

function concorso_maybe_decode_waiting_html(string $html): string
{
    if ($html === '') {
        return $html;
    }
    if (strpos($html, '&lt;') !== false || strpos($html, '&gt;') !== false) {
        $html = htmlspecialchars_decode($html, ENT_QUOTES);
    }
    if (function_exists('contest_normalize_summernote_font_html')) {
        $html = contest_normalize_summernote_font_html($html);
    }

    return $html;
}

/**
 * @return list<string>
 */
function concorso_waiting_align_values(): array
{
    return ['left', 'center', 'right', 'justify'];
}

function concorso_normalize_waiting_align(string $align, string $fallback = 'center'): string
{
    $align = strtolower(trim($align));

    return in_array($align, concorso_waiting_align_values(), true) ? $align : $fallback;
}

/**
 * @param array<string, mixed> $ctx
 */
function concorso_resolve_waiting_page_html(string $page_key, array $ctx, bool $raw_has_containers, bool $raw_has_html): string
{
    $content_current = $ctx['content_current'];
    $content_defaults = $ctx['content_defaults'];
    $page_html_value = concorso_extract_waiting_html($content_current['pages'][$page_key] ?? []);
    if ($page_html_value === '' && !($raw_has_containers || $raw_has_html)) {
        $page_html_value = concorso_extract_waiting_html($content_defaults['pages'][$page_key] ?? []);
    }

    return concorso_maybe_decode_waiting_html($page_html_value);
}

/**
 * @param array<string, mixed> $ctx
 * @return array{source: array<int, mixed>, defined: bool}
 */
function concorso_resolve_waiting_containers_source(string $page_key, array $ctx, bool $raw_has_containers): array
{
    $content_current = $ctx['content_current'];
    $content_defaults = $ctx['content_defaults'];
    $page_config_current = $content_current['pages'][$page_key] ?? [];
    $page_config_defaults = $content_defaults['pages'][$page_key] ?? [];
    $page_containers_source = [];
    $page_containers_defined = false;

    if ($raw_has_containers && is_array($page_config_current) && array_key_exists('containers', $page_config_current)) {
        if (is_array($page_config_current['containers'])) {
            $page_containers_source = $page_config_current['containers'];
        }
        $page_containers_defined = true;
    } elseif (is_array($page_config_defaults) && array_key_exists('containers', $page_config_defaults)) {
        if (is_array($page_config_defaults['containers'])) {
            $page_containers_source = $page_config_defaults['containers'];
        }
        $page_containers_defined = true;
    }

    return [
        'source' => $page_containers_source,
        'defined' => $page_containers_defined,
    ];
}

/**
 * @param array<string, mixed> $container_background
 * @param array<string, mixed> $defaults
 * @return array<string, mixed>
 */
function concorso_normalize_waiting_container_background(array $container_background, array $defaults): array
{
    $background_mode = strtolower(trim((string) ($container_background['mode'] ?? $defaults['mode'])));
    if (!in_array($background_mode, ['transparent', 'color', 'image'], true)) {
        $background_mode = (string) $defaults['mode'];
    }
    $background_color = trim((string) ($container_background['color'] ?? $defaults['color']));
    $background_opacity = (int) ($container_background['opacity'] ?? $defaults['opacity']);
    $background_opacity = max(0, min(100, $background_opacity));
    if ($background_mode === 'transparent') {
        $background_mode = 'color';
        $background_opacity = 0;
    }
    $background_image = trim((string) ($container_background['image'] ?? $defaults['image']));

    return [
        'mode' => $background_mode,
        'color' => $background_color,
        'image' => $background_image,
        'opacity' => $background_opacity,
    ];
}

/**
 * @param array<string, mixed> $spacing
 * @param array<string, int> $defaults
 * @return array<string, int>
 */
function concorso_normalize_waiting_container_spacing(array $spacing, array $defaults): array
{
    if (!is_array($spacing)) {
        $spacing = [];
    }
    $spacing = array_merge($defaults, $spacing);
    foreach (['top', 'right', 'bottom', 'left'] as $side) {
        $spacing[$side] = max(0, (int) ($spacing[$side] ?? 0));
    }

    return $spacing;
}

/**
 * @param array<int, mixed> $container_blocks
 * @return array{content_type: string, image: array{src: string, alt: string, width: string}}
 */
function concorso_analyze_waiting_container_blocks(array $container_blocks): array
{
    $container_content_type = 'text';
    $container_image = [
        'src' => '',
        'alt' => '',
        'width' => '',
    ];
    $has_text_blocks = false;
    $first_image_block = null;

    foreach ($container_blocks as $block) {
        if (!is_array($block)) {
            continue;
        }
        $block_type = $block['type'] ?? 'text';
        if (in_array($block_type, ['text', 'button'], true)) {
            $has_text_blocks = true;
        } elseif ($block_type === 'image' && $first_image_block === null) {
            $first_image_block = $block;
        }
    }
    if (!$has_text_blocks && is_array($first_image_block)) {
        $container_content_type = 'image';
    }
    if (is_array($first_image_block)) {
        $container_image = [
            'src' => trim((string) ($first_image_block['src'] ?? '')),
            'alt' => trim((string) ($first_image_block['alt'] ?? '')),
            'width' => trim((string) ($first_image_block['width'] ?? '')),
        ];
    }

    return [
        'content_type' => $container_content_type,
        'image' => $container_image,
    ];
}

/**
 * @param array<string, mixed> $container
 * @param array<string, mixed> $ctx
 * @return array<string, mixed>
 */
function concorso_build_waiting_page_container(array $container, int $index, string $page_align_value, array $ctx): array
{
    $container_blocks = is_array($container['blocks'] ?? null) ? $container['blocks'] : [];
    $container_id = trim((string) ($container['id'] ?? ''));
    if ($container_id === '') {
        $container_id = 'container-' . ($index + 1);
    }
    $container_align = concorso_normalize_waiting_align(
        (string) ($container['style']['align'] ?? $page_align_value),
        $page_align_value
    );
    $container_background = is_array($container['background'] ?? null) ? $container['background'] : [];
    $defaults = $ctx['waiting_container_default_background'];
    $container_html = concorso_maybe_decode_waiting_html(concorso_extract_waiting_container_html($container));
    $container_style = is_array($container['style'] ?? null) ? $container['style'] : [];
    $container_margin = concorso_normalize_waiting_container_spacing(
        is_array($container_style['margin'] ?? null) ? $container_style['margin'] : [],
        $ctx['waiting_container_default_margin']
    );
    $container_radius = concorso_normalize_waiting_container_spacing(
        is_array($container_style['radius'] ?? null) ? $container_style['radius'] : [],
        $ctx['waiting_container_default_radius']
    );
    $block_analysis = concorso_analyze_waiting_container_blocks($container_blocks);

    return [
        'id' => $container_id,
        'align' => $container_align,
        'html' => $container_html,
        'content_type' => $block_analysis['content_type'],
        'image' => $block_analysis['image'],
        'background' => concorso_normalize_waiting_container_background($container_background, $defaults),
        'margin' => $container_margin,
        'radius' => $container_radius,
    ];
}

/**
 * @param array<string, mixed> $ctx
 * @return array{containers: list<array<string, mixed>>, defined: bool, has_source: bool}
 */
function concorso_build_waiting_page_containers(string $page_key, array $ctx, string $page_align_value, bool $raw_has_containers): array
{
    $containers_info = concorso_resolve_waiting_containers_source($page_key, $ctx, $raw_has_containers);
    $page_containers_source = $containers_info['source'];
    $page_containers_defined = $containers_info['defined'];
    $has_containers_source = is_array($page_containers_source) && !empty($page_containers_source);
    $page_containers = [];

    if (is_array($page_containers_source)) {
        $sorted_containers = concorso_sort_waiting_items_by_order($page_containers_source);
        foreach ($sorted_containers as $index => $container) {
            if (!is_array($container)) {
                continue;
            }
            $page_containers[] = concorso_build_waiting_page_container($container, (int) $index, $page_align_value, $ctx);
        }
    }

    return [
        'containers' => $page_containers,
        'defined' => $page_containers_defined,
        'has_source' => $has_containers_source,
    ];
}

/**
 * @param list<array<string, mixed>> $page_containers
 * @param array<string, mixed> $ctx
 * @return list<array<string, mixed>>
 */
function concorso_ensure_waiting_fallback_container(
    array $page_containers,
    string $page_html_value,
    string $page_align_value,
    array $ctx,
    bool $raw_has_containers,
    bool $page_containers_defined,
    bool $has_containers_source
): array {
    if (!empty($page_containers) || $raw_has_containers || ($page_containers_defined && $has_containers_source)) {
        return $page_containers;
    }

    $page_containers[] = [
        'id' => 'container-1',
        'align' => $page_align_value,
        'html' => $page_html_value,
        'content_type' => 'text',
        'image' => ['src' => '', 'alt' => '', 'width' => ''],
        'background' => $ctx['waiting_container_default_background'],
        'margin' => $ctx['waiting_container_default_margin'],
        'radius' => $ctx['waiting_container_default_radius'],
    ];

    return $page_containers;
}

function concorso_apply_waiting_preview_placeholders(string $html, array $ctx): string
{
    if (!function_exists('contest_replace_placeholders')) {
        return str_replace(
            [
                CONCORSO_PH_CONTEST_NAME,
                CONCORSO_PH_START_DATE,
                CONCORSO_PH_END_DATE,
                CONCORSO_PH_MIN_SPEND,
                CONCORSO_PH_REGOLAMENTO,
            ],
            [
                $ctx['waiting_preview_name'],
                $ctx['waiting_preview_start'],
                $ctx['waiting_preview_end'],
                $ctx['waiting_preview_min_spend'],
                $ctx['waiting_preview_rules_link'],
            ],
            $html
        );
    }

    return contest_replace_placeholders($html, [
        CONCORSO_PH_CONTEST_NAME => (string) ($ctx['waiting_preview_name'] ?? ''),
        CONCORSO_PH_START_DATE => (string) ($ctx['waiting_preview_start'] ?? ''),
        CONCORSO_PH_END_DATE => (string) ($ctx['waiting_preview_end'] ?? ''),
        CONCORSO_PH_MIN_SPEND => (string) ($ctx['waiting_preview_min_spend'] ?? ''),
        CONCORSO_PH_REGOLAMENTO => (string) ($ctx['waiting_preview_rules_link'] ?? ''),
        '{{PRIZE_WIN_AMOUNT}}' => (string) ($ctx['waiting_preview_prize_win_amount'] ?? ''),
        '{{PRIZE_TYPE}}' => (string) ($ctx['waiting_preview_prize_type'] ?? ''),
    ]);
}

/**
 * @param array<string, mixed> $container
 * @param array<string, mixed> $ctx
 * @return array<string, mixed>
 */
function concorso_build_waiting_preview_container(array $container, string $page_align_value, array $ctx): array
{
    $container_content_type = $container['content_type'] ?? 'text';
    $container_html = '';
    $container_image = $container['image'] ?? ['src' => '', 'alt' => '', 'width' => ''];
    $container_margin = concorso_normalize_waiting_container_spacing(
        is_array($container['margin'] ?? null) ? $container['margin'] : [],
        $ctx['waiting_container_default_margin']
    );
    $container_radius = concorso_normalize_waiting_container_spacing(
        is_array($container['radius'] ?? null) ? $container['radius'] : [],
        $ctx['waiting_container_default_radius']
    );

    if ($container_content_type !== 'image') {
        $html_src = (string) ($container['html'] ?? '');
        $container_html = concorso_apply_waiting_preview_placeholders($html_src, $ctx);
        $container_html = trim(strip_tags($container_html, $ctx['waiting_preview_allowed_tags']));
        if ($container_html === '') {
            $container_html = '<p class="text-muted mb-0">Nessun contenuto impostato.</p>';
        }
    }

    $container_align = concorso_normalize_waiting_align((string) ($container['align'] ?? $page_align_value), $page_align_value);

    return [
        'align' => $container_align,
        'html' => $container_html,
        'content_type' => $container_content_type,
        'image' => $container_image,
        'background' => $container['background'] ?? $ctx['waiting_container_default_background'],
        'margin' => $container_margin,
        'radius' => $container_radius,
    ];
}

/**
 * @param list<array<string, mixed>> $page_containers
 * @param array<string, mixed> $ctx
 * @return list<array<string, mixed>>
 */
function concorso_build_waiting_preview_containers(array $page_containers, string $page_align_value, array $ctx): array
{
    $preview_containers = [];
    foreach ($page_containers as $container) {
        $preview_containers[] = concorso_build_waiting_preview_container($container, $page_align_value, $ctx);
    }
    if (empty($preview_containers)) {
        $preview_containers[] = [
            'align' => $page_align_value,
            'html' => '<p class="text-muted mb-0">Nessun contenuto impostato.</p>',
            'content_type' => 'text',
            'image' => ['src' => '', 'alt' => '', 'width' => ''],
            'background' => $ctx['waiting_container_default_background'],
            'margin' => $ctx['waiting_container_default_margin'],
            'radius' => $ctx['waiting_container_default_radius'],
        ];
    }

    return $preview_containers;
}

/**
 * @param array<string, mixed> $ctx
 */
function concorso_resolve_waiting_thankyou_title(string $page_key, array $ctx): string
{
    if (strpos($page_key, 'thankyou_') !== 0) {
        return '';
    }
    $content_current = $ctx['content_current'];
    $content_defaults = $ctx['content_defaults'];
    $page_title = trim((string) ($content_current['pages'][$page_key]['title'] ?? ''));
    if ($page_title === '') {
        $page_title = trim((string) ($content_defaults['pages'][$page_key]['title'] ?? ''));
    }
    if ($page_title === '') {
        $page_title = CONCORSO_THANKYOU_TITLE;
    }

    return $page_title;
}

/**
 * @param array<string, mixed> $ctx
 * @return array{align_value: string, containers: list<array<string, mixed>>, preview_containers: list<array<string, mixed>>, title: string}
 */
function concorso_build_waiting_page_state(string $page_key, array $ctx): array
{
    $content_current = $ctx['content_current'];
    $content_defaults = $ctx['content_defaults'];
    $content_raw = $ctx['content_raw'];
    $page_config_raw = $content_raw['pages'][$page_key] ?? [];
    $raw_has_containers = is_array($page_config_raw) && array_key_exists('containers', $page_config_raw);
    $raw_has_html = is_array($page_config_raw) && array_key_exists('html', $page_config_raw);
    $page_html_value = concorso_resolve_waiting_page_html($page_key, $ctx, $raw_has_containers, $raw_has_html);
    $page_align_value = concorso_normalize_waiting_align(
        (string) ($content_current['pages'][$page_key]['align'] ?? ($content_defaults['pages'][$page_key]['align'] ?? 'center'))
    );
    $containers_result = concorso_build_waiting_page_containers($page_key, $ctx, $page_align_value, $raw_has_containers);
    $page_containers = concorso_ensure_waiting_fallback_container(
        $containers_result['containers'],
        $page_html_value,
        $page_align_value,
        $ctx,
        $raw_has_containers,
        $containers_result['defined'],
        $containers_result['has_source']
    );

    return [
        'align_value' => $page_align_value,
        'containers' => $page_containers,
        'preview_containers' => concorso_build_waiting_preview_containers($page_containers, $page_align_value, $ctx),
        'title' => concorso_resolve_waiting_thankyou_title($page_key, $ctx),
    ];
}
