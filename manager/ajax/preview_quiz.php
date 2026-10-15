<?php
/**
 * Preview della pagina quiz per il manager (stesso stile di waiting_page / thank_you).
 * Interazione visiva consentita (selezione risposte, clic pulsante); nessun invio reale.
 */
require_once __DIR__ . '/../config/config.php';

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/manager'));
$projectRoot = preg_replace('#/manager(?:/.*)?$#', '', $scriptDir);
$BASE_HREF = rtrim((string) $projectRoot, '/') . '/public/';

// Preview live: session + brouillon AVANT qualsiasi output (header.php invia HTML)
$quiz_page_config = isset($content_config['pages']['quiz_page']) ? $content_config['pages']['quiz_page'] : (isset($content_defaults['pages']['quiz_page']) ? $content_defaults['pages']['quiz_page'] : []);
if (!empty($_GET['live'])) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!empty($_SESSION['quiz_preview_draft']) && is_array($_SESSION['quiz_preview_draft'])) {
        $quiz_page_config = $_SESSION['quiz_preview_draft'];
    }
}

$quiz_config = $contest_config['quiz'] ?? ['num_questions' => 4, 'mode' => 'standard', 'require_all_correct' => true];
$num_questions = max(1, min(20, (int)($quiz_config['num_questions'] ?? 4)));

$c_pdo = conn_pdo();
// Prime N domande attive per ordine di gestione (come elenco)
$stmt_q = $c_pdo->prepare("
    SELECT id, testo, tipo, audio_file, video_file, risposta_a, risposta_b, risposta_c, risposta_d
    FROM quiz_domande
    WHERE attiva = 1
    ORDER BY ordine ASC, id ASC
    LIMIT :limit
");
$stmt_q->bindValue(':limit', $num_questions, PDO::PARAM_INT);
$stmt_q->execute();
$questions = $stmt_q->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../public/assets/inc/sanitize.inc.php';
$quiz_preview_wizard = !empty($_GET['wizard']);
$brand_layout_page_key = 'quiz_page';
$logo_display_mode = strtolower(trim((string) ($contest_config['logo_display_mode'] ?? '')));
if (!in_array($logo_display_mode, ['none', 'logo', 'header', 'both'], true)) {
    $logo_display_mode = (array_key_exists('logo_enabled', $contest_config) && !filter_var($contest_config['logo_enabled'], FILTER_VALIDATE_BOOLEAN)) ? 'none' : 'logo';
}
$show_logo = in_array($logo_display_mode, ['logo', 'both'], true);
$show_header = in_array($logo_display_mode, ['header', 'both'], true) && trim((string) ($contest_config['header_image'] ?? '')) !== '';
$has_brand_visual = $show_logo || $show_header;

if (!$quiz_preview_wizard) {
    include __DIR__ . '/../../public/assets/inc/header.php';
}

$contest_name = htmlspecialchars($contest_config['name'] ?? 'Concorso', ENT_QUOTES, 'UTF-8');
if ($quiz_preview_wizard) {
    ?><!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <?php echo function_exists('get_form_container_style_block') ? get_form_container_style_block(true) : ''; ?>
    <?php echo function_exists('get_brand_layout_style_block') ? get_brand_layout_style_block('quiz_page') : ''; ?>
    <title>Anteprima quiz</title>
</head>
<body>
<?php
}
?>
<div class="container-xxl py-4 flex-grow-1 header-main-background-wrapper brand-layout-page-active<?php echo $show_header ? ' brand-header-active' : ''; ?><?php echo ($show_header && !$show_logo) ? ' brand-header-only' : ''; ?>">
    <main class="row justify-content-center w-100 mx-0">
        <div class="col-12 col-lg-11 col-xl-10 col-xxl-9 px-0 px-md-3">
            <div class="logo-stack mx-auto brand-layout-page--quiz_page">
                <?php if ($has_brand_visual && !$quiz_preview_wizard): ?>
                <header class="text-center card-logo-wrapper brand-visual-stack" role="banner">
                    <?php
                    $logo_main = htmlspecialchars($contest_config['logo'] ?? '', ENT_QUOTES);
                    $logo_mobile = htmlspecialchars($contest_config['logo_mobile'] ?? ($contest_config['logo'] ?? ''), ENT_QUOTES);
                    $header_image = htmlspecialchars($contest_config['header_image'] ?? '', ENT_QUOTES);
                    $header_image_mobile = htmlspecialchars($contest_config['header_image_mobile'] ?? '', ENT_QUOTES);
                    ?>
                    <?php if ($show_header): ?>
                    <picture>
                        <?php if ($header_image_mobile !== ''): ?>
                            <source media="(max-width: 767.98px)" srcset="<?php echo $header_image_mobile; ?>">
                        <?php endif; ?>
                        <img src="<?php echo $header_image; ?>" alt="Header <?php echo $contest_name; ?>" class="img-fluid card-logo-img brand-header-img">
                    </picture>
                    <?php endif; ?>
                    <?php if ($show_logo): ?>
                    <picture>
                        <?php if (!empty($logo_mobile)): ?>
                            <source media="(max-width: 767.98px)" srcset="<?php echo $logo_mobile; ?>">
                        <?php endif; ?>
                        <img src="<?php echo $logo_main; ?>" alt="Logo <?php echo $contest_name; ?>" class="img-fluid card-logo-img">
                    </picture>
                    <?php endif; ?>
                </header>
                <?php endif; ?>
            <div class="card registration-card public-page-container--quiz-page <?php echo ($has_brand_visual && !$quiz_preview_wizard) ? 'logo-attached-card ' : ''; ?>mx-auto">
                <div class="card-body py-4">
            <?php if (function_exists('render_quiz_page_header_containers') && !empty($quiz_page_config['containers'])): ?>
                <?php echo render_quiz_page_header_containers($contest_config, $quiz_page_config); ?>
            <?php else: ?>
                <h1 class="h4 mb-4">Quiz – <?php echo $contest_name; ?></h1>
            <?php endif; ?>

            <?php if (empty($questions)): ?>
                <p class="text-muted">Nessuna domanda attiva. Aggiungi domande dalla gestione Quiz – Domande (form e elenco sopra).</p>
            <?php else: ?>
                <form method="post" action="#" id="quiz-preview-form" onsubmit="return false;">
                    <?php
                    $question_block_style = function_exists('get_quiz_question_block_style') ? get_quiz_question_block_style($quiz_page_config) : 'margin-bottom: 12px; border-radius: 8px; padding: 16px; background-color: #fff;';
                    $question_block_content = (function_exists('get_quiz_question_block_content') ? get_quiz_question_block_content($quiz_page_config, $contest_config) : '');
                    $inner_0 = function_exists('get_quiz_question_inner_block_style') ? get_quiz_question_inner_block_style($quiz_page_config, 0) : '';
                    $inner_1 = function_exists('get_quiz_question_inner_block_style') ? get_quiz_question_inner_block_style($quiz_page_config, 1) : '';
                    $inner_2 = function_exists('get_quiz_question_inner_block_style') ? get_quiz_question_inner_block_style($quiz_page_config, 2) : '';
                    $inner_3 = function_exists('get_quiz_question_inner_block_style') ? get_quiz_question_inner_block_style($quiz_page_config, 3) : '';
                    $answer_style = function_exists('get_quiz_answer_input_style') ? get_quiz_answer_input_style($quiz_page_config) : 'radio';
                    $answer_class = ($answer_style === 'square') ? ' quiz-answer-square' : '';
                    $tick_color = function_exists('get_quiz_answer_tick_color') ? get_quiz_answer_tick_color($quiz_page_config) : '#6c757d';
                    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $tick_color)) {
                        $palette_colors = is_array($contest_config['colors'] ?? null) ? $contest_config['colors'] : [];
                        $tick_color = $palette_colors['secondary'] ?? '#6c757d';
                    }
                    $tick_hex = ltrim($tick_color, '#');
                    if (strlen($tick_hex) === 3) { $tick_hex = $tick_hex[0].$tick_hex[0].$tick_hex[1].$tick_hex[1].$tick_hex[2].$tick_hex[2]; }
                    $tick_fill_enc = '%23' . $tick_hex;
                    $btn_config = function_exists('get_quiz_submit_button_config') ? get_quiz_submit_button_config($quiz_page_config) : ['text' => 'Invia risposte', 'bg_color' => '#0d6efd', 'align' => 'center'];
                    echo '<style>.form-check-input:checked{background-color:' . htmlspecialchars($tick_color, ENT_QUOTES, 'UTF-8') . ';border-color:' . htmlspecialchars($tick_color, ENT_QUOTES, 'UTF-8') . '}';
                    if ($answer_style === 'square') {
                        echo '.quiz-answer-square .form-check-input{appearance:none;-webkit-appearance:none;width:1.1em;height:1.1em;border:2px solid ' . htmlspecialchars($tick_color, ENT_QUOTES, 'UTF-8') . ';border-radius:0;vertical-align:middle;}.quiz-answer-square .form-check-input:checked{background:center/70% no-repeat url("data:image/svg+xml,%3csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 8 8\'%3e%3cpath fill=\'' . $tick_fill_enc . '\' d=\'M6.564.75L3 4.314 1.436 2.75l-.682.682L3 5.679l4.246-4.246z\'/%3e%3c/svg%3e");border-color:' . htmlspecialchars($tick_color, ENT_QUOTES, 'UTF-8') . '}';
                    }
                    echo '</style>';
                    echo function_exists('get_quiz_options_list_style_block') ? get_quiz_options_list_style_block() : '';
                    ?>
                    <?php foreach ($questions as $i => $q): ?>
                        <?php
                        $num = $i + 1;
                        $blk0_content = function_exists('get_quiz_question_inner_block_content') ? get_quiz_question_inner_block_content($quiz_page_config, $contest_config, 0, $num) : '';
                        if ($blk0_content === '') { $blk0_content = 'Domanda ' . $num . '.'; }
                        $blk2_content = function_exists('get_quiz_question_inner_block_content') ? get_quiz_question_inner_block_content($quiz_page_config, $contest_config, 2, $num) : '';
                        if ($blk2_content === '') { $blk2_content = 'Risposta'; }
                        $audio_src = $q['audio_file'] ?? '';
                        if ($audio_src !== '' && strpos($audio_src, 'http') !== 0 && ($audio_src[0] ?? '') !== '/') { $audio_src = $BASE_HREF . ltrim($audio_src, '/'); }
                        $testo_domanda_html = '';
                        if (!empty($q['testo'])) { $testo_domanda_html .= '<span class="quiz-testo-domanda d-block mb-0" style="text-align:inherit">' . nl2br(htmlspecialchars($q['testo'], ENT_QUOTES, 'UTF-8')) . '</span>'; }
                        if ($q['tipo'] === 'audio' && !empty($q['audio_file'])) { $testo_domanda_html .= '<audio controls src="' . htmlspecialchars($audio_src, ENT_QUOTES, 'UTF-8') . '"></audio>'; }
                        $opzioni_html = function_exists('get_quiz_question_options_html') ? get_quiz_question_options_html($q, $answer_style, false) : '';
                        $use_template = ($question_block_content !== '' && (strpos($question_block_content, '{{DOMANDA_N}}') !== false || strpos($question_block_content, '{{TESTO_DOMANDA}}') !== false || strpos($question_block_content, '{{RISPOSTA}}') !== false || strpos($question_block_content, '{{OPZIONI}}') !== false));
                        ?>
                        <div style="<?php echo htmlspecialchars($question_block_style, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php if ($use_template): ?>
                                <?php
                                $block_html = str_replace(
                                    ['{{DOMANDA_N}}', '{{TESTO_DOMANDA}}', '{{RISPOSTA}}', '{{OPZIONI}}'],
                                    [$blk0_content, $testo_domanda_html, $blk2_content, $opzioni_html],
                                    $question_block_content
                                );
                                echo $block_html;
                                ?>
                        <?php else: ?>
                                <?php if ($question_block_content !== ''): ?><?php echo $question_block_content; ?><?php endif; ?>
                                <div style="<?php echo htmlspecialchars($inner_0, ENT_QUOTES, 'UTF-8'); ?>"><p class="font-weight-bold mb-0"><?php echo $blk0_content; ?></p></div>
                                <div style="<?php echo htmlspecialchars($inner_1, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $testo_domanda_html; ?></div>
                                <div style="<?php echo htmlspecialchars($inner_2, ENT_QUOTES, 'UTF-8'); ?>"><label class="d-block mb-0 font-weight-bold"><?php echo $blk2_content; ?></label></div>
                                <div style="<?php echo htmlspecialchars($inner_3, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $opzioni_html; ?></div>
                        <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <div class="mt-3" style="text-align: <?php echo htmlspecialchars($btn_config['align'], ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="button" id="quiz-preview-submit" class="btn" style="background-color: <?php echo htmlspecialchars($btn_config['bg_color'], ENT_QUOTES, 'UTF-8'); ?>; border-color: <?php echo htmlspecialchars($btn_config['bg_color'], ENT_QUOTES, 'UTF-8'); ?>; color: #fff;"><?php echo htmlspecialchars($btn_config['text'], ENT_QUOTES, 'UTF-8'); ?></button>
                        <p id="quiz-preview-submit-note" class="small text-muted mt-2 mb-0 d-none" role="status">Anteprima: nessun invio effettivo.</p>
                    </div>
                    <script>
                    (function () {
                        var btn = document.getElementById('quiz-preview-submit');
                        var note = document.getElementById('quiz-preview-submit-note');
                        if (btn && note) {
                            btn.addEventListener('click', function () {
                                note.classList.remove('d-none');
                            });
                        }
                    })();
                    </script>
                </form>
            <?php endif; ?>
                </div>
            </div>
            </div>
        </div>
    </main>
</div>
<?php
if ($quiz_preview_wizard) {
    echo '</body></html>';
} elseif (!$quiz_preview_wizard) {
    include __DIR__ . '/../../public/assets/inc/footer.php';
}
