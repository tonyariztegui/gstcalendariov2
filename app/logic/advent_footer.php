<?php
/** Adapter for the shared Concorso footer. Storage remains in advent.json. */
function advent_footer_map(): array
{
    return [
        'footer_style' => ['mode'=>'mode','background'=>'background','image'=>'background_image','opacity'=>'opacity','image_position_vertical'=>'image_position_vertical','image_position_horizontal'=>'image_position_horizontal','image_size'=>'image_size','image_size_custom'=>'image_size_custom','image_repeat'=>'image_repeat','text_primary'=>'text_color','text_secondary'=>'text_secondary','link'=>'link_color','link_hover'=>'link_hover','border_color'=>'border_color','border_opacity'=>'border_opacity','border_width'=>'border_width'],
        'footer_layout' => ['show_logo'=>'show_logo','use_contest_logo'=>'use_main_logo','logo_image'=>'logo','logo_height_px'=>'logo_height','font_family'=>'font_family','font_size'=>'font_size'],
        'footer_layout_text' => ['contest_title'=>'title','intro_line'=>'text','legal_html_1'=>'legal','legal_html_2'=>'legal_2','link_regolamento_label'=>'regulation_label','link_privacy_label'=>'privacy_label','link_cookie_policy_label'=>'cookie_label','link_assistenza_label'=>'assistance_label'],
        'blocks' => ['contest_title'=>'title','contest_dates'=>'dates','prize_total'=>'prize_total','links_regolamento'=>'links_regulation','links_privacy'=>'links_privacy','links_cookie_policy'=>'links_cookie','links_assistenza'=>'links_assistance','promoter'=>'promoter','delegate'=>'delegate','legal_block_1'=>'legal_1','legal_block_2'=>'legal_2'],
    ];
}

function advent_footer_from_post(array $input): array
{
    if (!isset($input['footer_style_mode'])) { return $input; }
    foreach (advent_footer_map() as $group => $fields) {
        foreach ($fields as $shared => $local) {
            if ($group === 'blocks') { $input['footer_blocks'][$local] = !empty($input['footer_layout_block_' . $shared]); }
            else { $input['footer_' . $local] = $input[$group . '_' . $shared] ?? ''; }
        }
    }
    $input['footer_layout'] = ($input['footer_layout_template'] ?? '') === 'three_columns' ? 'columns' : 'centered';
    $input['footer_custom_text_colors'] = !empty($input['style_customization_footer_text_colors']);
    $input['footer_border_enabled'] = !empty($input['footer_style_border_enabled']);
    $input['footer_border_sides'] = $input['footer_style_border_sides'] ?? [];
    return $input;
}

function advent_footer_config(array $calendar, string $base): array
{
    $c = array_replace(advent_defaults(), $calendar);
    $out = ['footer_style'=>[], 'footer_layout'=>['blocks'=>[], 'texts'=>[]]];
    foreach (advent_footer_map() as $group => $fields) {
        foreach ($fields as $shared => $local) {
            if ($group === 'blocks') { $out['footer_layout']['blocks'][$shared] = !empty($c['footer_blocks'][$local]); }
            elseif ($group === 'footer_layout_text') { $out['footer_layout']['texts'][$shared] = $c['footer_' . $local]; }
            else { $out[$group][$shared] = $c['footer_' . $local]; }
        }
    }
    $out['footer_layout']['template'] = $c['footer_layout'] === 'columns' ? 'three_columns' : 'centered_compact';
    foreach (['legal_html_1','legal_html_2'] as $key) { $out['footer_layout']['texts'][$key] = advent_footer_safe_html($out['footer_layout']['texts'][$key]); }
    $out['footer_style']['border_width'] = $c['footer_border_enabled'] ? $c['footer_border_width'] : 0;
    $out['footer_style']['border_sides'] = $c['footer_border_enabled'] ? $c['footer_border_sides'] : [];
    $out['footer_style']['image'] = advent_asset_url($c['footer_background_image'], $base);
    $out['footer_layout']['logo_image'] = advent_asset_url($c['footer_logo'], $base);
    $out['style_customization'] = ['footer_text_colors'=>$c['footer_custom_text_colors']];
    $out['colors'] = ['primary'=>$c['accent'],'secondary'=>$c['accent'],'tertiary'=>$c['accent'],'text'=>'#233c34'];
    $out['name'] = $c['title'];
    $out['logo'] = advent_asset_url($c['logo'], $base);
    foreach (['start_date','end_date'] as $key) {
        $out[$key] = $c[$key];
        $out[$key . '_formatted'] = advent_date($c[$key])?->format('d/m/Y') ?? '';
    }
    foreach (['promoter','delegate'] as $key) { $out[$key]=$c['footer_'.$key]; $out[$key.'_link']=$c['footer_'.$key.'_url']; }
    $out['regolamento_link']=$c['footer_regulation_url'];
    $out['privacy_link']=$c['footer_privacy_url'];
    $out['cookie_policy_link']=$c['footer_cookie_url'];
    $out['assistenza_link']=$c['footer_assistance_url'];
    $out['prize_total']=$c['footer_prize_total'] ?? 0;
    $out['min_spend']=0;
    $out['cookie_consent']=['enabled'=>false];
    $out['footer_cookie_link_enabled']=true;
    return $out;
}

function advent_footer_button_html(array $calendar, string $base = ''): string
{
    $label = trim((string) ($calendar['footer_button_label'] ?? ''));
    $url = trim((string) ($calendar['footer_button_url'] ?? ''));
    if (str_starts_with($url, '/public/')) { $url = rtrim($base, '/') . $url; }
    if ($label === '' || $url === '' || !preg_match('~^(?:https?://[^\s<>"\x27]+|/(?!/)[^\s<>"\x27]+)$~D', $url)) { return ''; }
    $defaults = advent_defaults();
    $colors = [];
    foreach (['footer_button_background', 'footer_button_text_color', 'footer_button_hover_background'] as $key) {
        $value = (string) ($calendar[$key] ?? $defaults[$key]);
        $colors[$key] = preg_match('/^#[0-9a-fA-F]{6}$/D', $value) ? $value : $defaults[$key];
    }
    $size = in_array($calendar['footer_button_size'] ?? '', ['small', 'medium', 'large'], true) ? $calendar['footer_button_size'] : 'medium';
    $width = ($calendar['footer_button_width'] ?? '') === 'full' ? 'full' : 'auto';
    $radius = max(0, min(32, (int) ($calendar['footer_button_radius'] ?? 8)));
    $style = '--advent-button-bg:' . $colors['footer_button_background']
        . ';--advent-button-text:' . $colors['footer_button_text_color']
        . ';--advent-button-hover:' . $colors['footer_button_hover_background']
        . ';--advent-button-radius:' . $radius . 'px';
    $newTab = !empty($calendar['footer_button_new_tab']) ? ' target="_blank" rel="noopener noreferrer"' : '';
    return '<div class="advent-footer-action"><a class="advent-footer-button advent-footer-button--' . $size
        . ' advent-footer-button--' . $width . '" href="' . advent_escape($url) . '" style="'
        . advent_escape($style) . '"' . $newTab . '>' . advent_escape($label) . '</a></div>';
}

function advent_footer_safe_html(string $html): string
{
    if ($html === '') return '';
    $doc = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"><div>' . $html . '</div>', LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);
    libxml_clear_errors(); libxml_use_internal_errors($previous);
    $allowed = ['div','p','br','span','strong','b','em','i','u','s','ul','ol','li','a','font'];
    foreach (iterator_to_array($doc->getElementsByTagName('*')) as $node) {
        if (!in_array($node->tagName, $allowed, true)) { $node->parentNode?->removeChild($node); continue; }
        foreach (iterator_to_array($node->attributes) as $attribute) {
            $name = $attribute->name; $value = $attribute->value;
            $keep = $name === 'href' && preg_match('~^(https?://|mailto:|tel:|/(?!/)|#)~i', $value);
            if (in_array($name, ['color','face','size'], true) && $node->tagName === 'font') $keep = true;
            if ($name === 'style') {
                $rules = [];
                foreach (explode(';', $value) as $rule) {
                    if (preg_match('/^\s*(color|background-color|font-family|font-size|font-weight|font-style|text-align|text-decoration)\s*:\s*([a-zA-Z0-9#(),.%\s\x27"-]+)$/D', $rule)) $rules[] = $rule;
                }
                $node->setAttribute('style', implode(';', $rules)); $keep = true;
            }
            if (!$keep) $node->removeAttribute($name);
        }
    }
    $root = $doc->getElementsByTagName('div')->item(0);
    $result = '';
    if ($root) foreach ($root->childNodes as $node) $result .= $doc->saveHTML($node);
    return $result;
}
