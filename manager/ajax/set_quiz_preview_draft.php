<?php
/**
 * Riceve i dati del form "Blocco domanda (template)" via POST e li salva in sessione
 * per la preview live (preview_quiz.php?live=1).
 * L'intestazione usa i container già salvati in content.json.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$p = function ($key, $default = '') {
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
};
$pint = function ($key, $default = 0) {
    return isset($_POST[$key]) ? (int) $_POST[$key] : $default;
};

$content_path = dirname(__DIR__, 2) . '/app/config/content.json';
$defaults_path = dirname(__DIR__, 2) . '/app/config/content.defaults.json';
$existing_containers = [];
if (is_file($content_path) && is_readable($content_path)) {
    $content_data = json_decode(file_get_contents($content_path), true);
    if (is_array($content_data['pages']['quiz_page']['containers'] ?? null)) {
        $existing_containers = $content_data['pages']['quiz_page']['containers'];
    }
}
if (empty($existing_containers) && is_file($defaults_path) && is_readable($defaults_path)) {
    $defaults_data = json_decode(file_get_contents($defaults_path), true);
    if (is_array($defaults_data['pages']['quiz_page']['containers'] ?? null)) {
        $existing_containers = $defaults_data['pages']['quiz_page']['containers'];
    }
}

$qblock_mode = ($p('quiz_qblock_bg_mode') === 'image') ? 'image' : 'color';
$qblock_image = $p('quiz_qblock_bg_image');
if ($qblock_mode !== 'image') {
    $qblock_image = '';
}
$qblock_color = $p('quiz_qblock_bg_color', '#ffffff');
if ($qblock_color !== '' && $qblock_color[0] !== '#') {
    $qblock_color = '#' . $qblock_color;
}
$qblock_bg = [
    'mode' => $qblock_mode,
    'color' => $qblock_color,
    'image' => $qblock_image,
    'opacity' => max(0, min(100, $pint('quiz_qblock_bg_opacity', 100))),
];
$qblock_style = [
    'margin' => [
        'top' => $pint('quiz_qblock_margin_top', 0),
        'right' => $pint('quiz_qblock_margin_right', 0),
        'bottom' => $pint('quiz_qblock_margin_bottom', 12),
        'left' => $pint('quiz_qblock_margin_left', 0),
    ],
    'radius' => [
        'top' => $pint('quiz_qblock_radius_tl', 8),
        'right' => $pint('quiz_qblock_radius_tr', 8),
        'bottom' => $pint('quiz_qblock_radius_br', 8),
        'left' => $pint('quiz_qblock_radius_bl', 8),
    ],
    'padding' => [
        'top' => $pint('quiz_qblock_pad_top', 16),
        'right' => $pint('quiz_qblock_pad_right', 16),
        'bottom' => $pint('quiz_qblock_pad_bottom', 16),
        'left' => $pint('quiz_qblock_pad_left', 16),
    ],
];

$draft = [
    'containers' => $existing_containers,
    'question_block_style' => [
        'content' => $p('quiz_qblock_html'),
        'inner_blocks' => (function () use ($p, $pint) {
            $inner = [];
            $defaults = ['Domanda {{N}}.', '', 'Risposta', ''];
            for ($ib = 0; $ib < 4; $ib++) {
                $content = $p("quiz_inner_{$ib}_content");
                if ($content === '' && isset($defaults[$ib]) && $defaults[$ib] !== '') {
                    $content = $defaults[$ib];
                }
                $inner[] = [
                    'content' => $content,
                    'style' => [
                        'padding' => max(0, $pint("quiz_inner_{$ib}_pad", 4)),
                        'margin_bottom' => max(0, $pint("quiz_inner_{$ib}_mb", 4)),
                        'text_align' => in_array($p("quiz_inner_{$ib}_align"), ['left', 'center', 'right', 'justify'], true) ? $p("quiz_inner_{$ib}_align") : 'left',
                        'background_color' => $p("quiz_inner_{$ib}_bg"),
                        'radius' => max(0, $pint("quiz_inner_{$ib}_radius", 0)),
                    ],
                ];
            }
            return $inner;
        })(),
        'answer_input_style' => ($p('quiz_answer_input_style') === 'square') ? 'square' : 'radio',
        'answer_tick_color' => $p('quiz_answer_tick_color', '#333333'),
        'submit_button_text' => $p('quiz_submit_button_text', 'Invia risposte'),
        'submit_button_bg_color' => $p('quiz_submit_button_bg_color', '#0d6efd'),
        'submit_button_align' => in_array($p('quiz_submit_button_align'), ['left', 'center', 'right'], true) ? $p('quiz_submit_button_align') : 'center',
        'background' => $qblock_bg,
        'style' => $qblock_style,
    ],
];

$_SESSION['quiz_preview_draft'] = $draft;
echo json_encode(['ok' => true]);
