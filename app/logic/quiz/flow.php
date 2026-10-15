<?php
/**
 * Quiz flow: una sola pagina, più step.
 * Step 1: Verifica fidelity (se abilitata e non già OK in sessione).
 * Step 2: Scelta gioco Standard / Audio (se quiz.mode = choice).
 * Step 3: Domande quiz (A/B/C/D), invio risposte.
 * Step 4 (POST): Correzioni, salvataggio quiz_tentativi, Instant Win, redirect thank-you.
 *
 * Con quiz attivo da registrazione: ingresso senza ?tk= (dati in sessione fino al completamento quiz).
 * Dopo iscrizione con partecipazione già in DB: token in sessione (SUMMERCOOP_QUIZFLOW_TOKEN_SESSION_KEY), URL senza tk.
 * Con ?tk= : flusso legacy (link con token in query).
 */
include __DIR__ . '/../../inc/config.php';
include __DIR__ . '/../../inc/functions.php';
require_once __DIR__ . '/pending_registration.php';
require_once __DIR__ . '/quiz_completion_email.php';

// Base URL assoluta per redirect (quiz_flow e thank-you sono in public/)
$script_dir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/public'), '/');
$BASE_HREF = ($script_dir !== '' ? $script_dir : '/public') . '/';
$PUBLIC_BASE = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $script_dir;
$site_root_path = rtrim(dirname($script_dir), '/');
if ($site_root_path === '') {
    $site_root_path = '/';
}
$HOME_URL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim($site_root_path, '/') . '/';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$quiz_enabled = !empty($contest_config['quiz_enabled']);
if (!$quiz_enabled) {
    header('Location: ' . $BASE_HREF . 'registrazione');
    exit;
}

$token = isset($_GET['tk']) ? trim((string) $_GET['tk']) : '';
if ($token !== '') {
    unset($_SESSION[SUMMERCOOP_QUIZFLOW_TOKEN_SESSION_KEY]);
}

$pending_box = $_SESSION[GST_QUIZ_PENDING_SESSION_KEY] ?? null;
$use_pending_registration = ($token === '' && is_array($pending_box) && !empty($pending_box['payload']) && !gst_quiz_pending_is_expired($pending_box));

if (!$use_pending_registration && $token === '' && !empty($_SESSION[SUMMERCOOP_QUIZFLOW_TOKEN_SESSION_KEY]) && is_string($_SESSION[SUMMERCOOP_QUIZFLOW_TOKEN_SESSION_KEY])) {
    $token = trim((string) $_SESSION[SUMMERCOOP_QUIZFLOW_TOKEN_SESSION_KEY]);
}

$quiz_urls_include_tk = (isset($_GET['tk']) && trim((string) $_GET['tk']) !== '');

if ($token === '' && !$use_pending_registration) {
    if (is_array($pending_box)) {
        unset($_SESSION[GST_QUIZ_PENDING_SESSION_KEY]);
    }
    header('Location: ' . $HOME_URL);
    exit;
}

$c_pdo = conn_pdo();

$pending_payload = null;
if ($use_pending_registration) {
    gst_quiz_pending_touch();
    $pending_payload = $pending_box['payload'];
    $sess_id_base = substr(md5('PEND' . ($pending_box['secret'] ?? '')), 0, 12);
    $id_cliente = 0;
} else {
    $sess_id_base = substr(md5($token), 0, 12);
    $stmt_p = $c_pdo->prepare('SELECT p.id, p.id_cliente FROM partecipazioni p WHERE p.token = :tk LIMIT 1');
    $stmt_p->bindValue(':tk', $token, PDO::PARAM_STR);
    $stmt_p->execute();
    $participation = $stmt_p->fetch(PDO::FETCH_ASSOC);
    if (!$participation) {
        header('Location: ' . $HOME_URL);
        exit;
    }
    $id_cliente = (int) $participation['id_cliente'];
}

$quiz_config = $contest_config['quiz'] ?? ['num_questions' => 4, 'mode' => 'standard', 'require_all_correct' => true];
$num_questions = max(1, min(20, (int) ($quiz_config['num_questions'] ?? 4)));
$quiz_mode_config = $quiz_config['mode'] ?? 'standard';
$require_all_correct = !empty($quiz_config['require_all_correct']);

$fields_enabled = $contest_config['fields_enabled'] ?? [];
$fidelity_enabled = !empty($fields_enabled['fidelity']);

// Chiavi sessione per il quiz (stabili per tutta la richiesta: pending secret o token legacy)
$sess_fidelity_ok = 'quiz_fidelity_ok_' . $sess_id_base;
$sess_quiz_mode = 'quiz_mode_' . $sess_id_base;
$sess_quiz_question_ids = 'quiz_question_ids_' . $sess_id_base;

// Dopo il form di iscrizione il fidelity è già validato: salta step 1 se presente nel payload
if ($use_pending_registration && $fidelity_enabled) {
    $pf = trim((string) ($pending_payload['fidelity'] ?? ''));
    if ($pf !== '') {
        $_SESSION[$sess_fidelity_ok] = true;
    }
}

$quiz_form_action = $PUBLIC_BASE . '/quiz' . ($quiz_urls_include_tk && $token !== '' ? ('?tk=' . urlencode($token)) : '');

// ----- POST: invio risposte quiz -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quiz_submit']) && isset($_POST['q']) && is_array($_POST['q'])) {
    if ($use_pending_registration) {
        $persist = gst_persist_pending_registration($c_pdo);
        if (!$persist['ok']) {
            unset($_SESSION[GST_QUIZ_PENDING_SESSION_KEY]);
            $_SESSION['registration_error'] = htmlspecialchars($persist['error'] ?? 'Registrazione non completata.', ENT_QUOTES, 'UTF-8');
            header('Location: ' . $BASE_HREF . 'registrazione');
            exit;
        }
        $token = (string) $persist['token'];
        $id_cliente = (int) $persist['id_cliente'];
        $use_pending_registration = false;
        $pending_payload = null;
    }

    $risposte = $_POST['q'];
    $quiz_mode_used = isset($_SESSION[$sess_quiz_mode]) ? $_SESSION[$sess_quiz_mode] : 'standard';

    // Carica domande (stesse usate per il quiz: stessi ID salvati in sessione)
    if ($quiz_mode_used === 'audio') {
        $tipo_sql = "tipo = 'audio'";
    } else {
        $tipo_sql = "tipo = 'text'";
    }
    $question_ids = isset($_SESSION[$sess_quiz_question_ids]) && is_array($_SESSION[$sess_quiz_question_ids])
        ? $_SESSION[$sess_quiz_question_ids]
        : [];
    if (count($question_ids) === $num_questions) {
        $placeholders = implode(',', array_fill(0, count($question_ids), '?'));
        $stmt_d = $c_pdo->prepare("
            SELECT id, risposta_corretta FROM quiz_domande
            WHERE attiva = 1 AND ({$tipo_sql}) AND id IN ({$placeholders})
        ");
        $stmt_d->execute(array_values($question_ids));
        $domande = $stmt_d->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Fallback: sessione scaduta o senza ID, carica per ordine
        $stmt_d = $c_pdo->prepare("
            SELECT id, risposta_corretta FROM quiz_domande
            WHERE attiva = 1 AND ({$tipo_sql})
            ORDER BY ordine ASC, id ASC
            LIMIT :limit
        ");
        $stmt_d->bindValue(':limit', $num_questions, PDO::PARAM_INT);
        $stmt_d->execute();
        $domande = $stmt_d->fetchAll(PDO::FETCH_ASSOC);
    }

    $corrette = 0;
    $risposte_json = [];
    foreach ($domande as $d) {
        $id_d = $d['id'];
        $risposte_json[$id_d] = isset($risposte[$id_d]) ? strtoupper(substr(trim((string) $risposte[$id_d]), 0, 1)) : '';
        if (in_array($risposte_json[$id_d], ['A', 'B', 'C', 'D'], true) && $risposte_json[$id_d] === $d['risposta_corretta']) {
            $corrette++;
        }
    }
    $totali = count($domande);
    $esito = $require_all_correct ? ($corrette === $totali && $totali > 0) : ($corrette > 0);

    // Inserisci o aggiorna quiz_tentativi (un tentativo per token)
    $stmt_ins = $c_pdo->prepare("
        INSERT INTO quiz_tentativi (id_cliente, token_partecipazione, quiz_mode, corrette, totali, esito, risposte_json)
        VALUES (:id_cliente, :token, :quiz_mode, :corrette, :totali, :esito, :risposte_json)
        ON DUPLICATE KEY UPDATE quiz_mode = VALUES(quiz_mode), corrette = VALUES(corrette), totali = VALUES(totali), esito = VALUES(esito), risposte_json = VALUES(risposte_json)
    ");
    $stmt_ins->execute([
        ':id_cliente' => $id_cliente,
        ':token' => $token,
        ':quiz_mode' => $quiz_mode_used,
        ':corrette' => $corrette,
        ':totali' => $totali,
        ':esito' => $esito ? 1 : 0,
        ':risposte_json' => json_encode($risposte_json),
    ]);

    // Risultato in base al modo concorso e all'esito quiz
    $instantwin_result = null;
    $_SESSION['quiz_passed'] = (bool) $esito;

    if ($contest_config['mode'] === 'instantwin') {
        // Instant Win: la formula si applica SOLO se il quiz è superato (risposte corrette)
        if ($esito) {
            $iwPerPdv = !empty($contest_config['instantwin_per_pdv']);
            $iwPerCedi = !empty($contest_config['instantwin_per_cedi']);
            if ($iwPerPdv) {
                require_once __DIR__ . '/../instantwin_pdv/instantwin_pdv.php';
            } elseif ($iwPerCedi) {
                require_once __DIR__ . '/../instantwin_cedi/instantwin_cedi.php';
            } else {
                require_once __DIR__ . '/../instantwin.php';
            }
            if ($iwPerPdv) {
                $instantwin_result = checkInstantWinPdv($token);
            } elseif ($iwPerCedi) {
                $instantwin_result = checkInstantWinCedi($token);
            } else {
                $instantwin_result = checkInstantWin($token);
            }
        }
        // Se quiz fallito: non si lancia l'Instant Win, l'utente ha perso direttamente
        $_SESSION['instantwin_result'] = $instantwin_result;
    } else {
        // Estrazione: eligibilité au tirage via quiz_tentativi.esito ; thank-you = partecipazione ou page quiz non superato (si require_all_correct)
        $_SESSION['instantwin_result'] = null;
    }

    quiz_flow_send_completion_email($c_pdo, $token, $instantwin_result, (bool) $esito);

    $_SESSION[SUMMERCOOP_THANKYOU_TOKEN_SESSION_KEY] = $token;
    unset($_SESSION[SUMMERCOOP_QUIZFLOW_TOKEN_SESSION_KEY]);
    header('Location: ' . $PUBLIC_BASE . '/grazie');
    exit;
}

// ----- POST: scelta gioco (Standard / Audio) -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quiz_game_choice'])) {
    $choice = isset($_POST['quiz_game']) ? trim((string) $_POST['quiz_game']) : '';
    if (in_array($choice, ['standard', 'audio'], true)) {
        $_SESSION[$sess_quiz_mode] = $choice;
        unset($_SESSION[$sess_quiz_question_ids]); // Nuova scelta = nuove domande random
    }
    $loc = $PUBLIC_BASE . '/quiz' . ($quiz_urls_include_tk && $token !== '' ? ('?tk=' . urlencode($token) . '&step=3') : '?step=3');
    header('Location: ' . $loc);
    exit;
}

// ----- POST: verifica fidelity -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quiz_fidelity_submit']) && $fidelity_enabled) {
    $fidelity_value = isset($_POST['fidelity']) ? trim((string) $_POST['fidelity']) : '';
    $fidelity_ok = false;
    if ($use_pending_registration && is_array($pending_payload)) {
        $expected = trim((string) ($pending_payload['fidelity'] ?? ''));
        if ($expected !== '' && hash_equals($expected, $fidelity_value)) {
            $fidelity_ok = true;
        }
    } elseif ($token !== '') {
        $stmt_u = $c_pdo->prepare('SELECT u.id_cliente FROM utenti u JOIN partecipazioni p ON p.id_cliente = u.id_cliente WHERE p.token = :tk LIMIT 1');
        $stmt_u->bindValue(':tk', $token, PDO::PARAM_STR);
        $stmt_u->execute();
        $user = $stmt_u->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $col = ($contest_config['fidelity']['type'] ?? 'numeric') === 'alphanumeric' ? 'fidelity' : 'fidelity';
            $stmt_f = $c_pdo->prepare("SELECT 1 FROM utenti WHERE id_cliente = :id AND {$col} = :val LIMIT 1");
            $stmt_f->execute([':id' => $user['id_cliente'], ':val' => $fidelity_value]);
            if ($stmt_f->fetch()) {
                $fidelity_ok = true;
            }
        }
    }
    if ($fidelity_ok) {
        $_SESSION[$sess_fidelity_ok] = true;
        $loc = $PUBLIC_BASE . '/quiz' . ($quiz_urls_include_tk && $token !== '' ? ('?tk=' . urlencode($token) . '&step=2') : '?step=2');
        header('Location: ' . $loc);
        exit;
    }
    $fidelity_error = 'Codice fidelity non valido.';
}

$step = isset($_GET['step']) ? (int) $_GET['step'] : 1;

// Step 1: Fidelity
if ($fidelity_enabled && !isset($_SESSION[$sess_fidelity_ok])) {
    $step = 1;
}
// Step 2: Scelta gioco (solo se mode = choice e fidelity OK)
elseif ($quiz_mode_config === 'choice' && (!isset($_SESSION[$sess_quiz_mode]) || $step === 2)) {
    $step = 2;
} else {
    if (!isset($_SESSION[$sess_quiz_mode])) {
        $_SESSION[$sess_quiz_mode] = ($quiz_mode_config === 'audio') ? 'audio' : 'standard';
    }
    $step = 3;
}

// Verifica se tentativo già fatto (evita rifare quiz) — solo con token partecipazione in DB
if (!$use_pending_registration && $token !== '' && $step === 3) {
    $stmt_t = $c_pdo->prepare('SELECT 1 FROM quiz_tentativi WHERE token_partecipazione = :tk LIMIT 1');
    $stmt_t->bindValue(':tk', $token, PDO::PARAM_STR);
    $stmt_t->execute();
    if ($stmt_t->fetch()) {
        header('Location: ' . $HOME_URL);
        exit;
    }
}

$quiz_mode_used = $_SESSION[$sess_quiz_mode] ?? 'standard';
if ($quiz_mode_used === 'audio') {
    $tipo_sql_display = "tipo = 'audio'";
} else {
    $tipo_sql_display = "tipo = 'text'";
}

// Carica domande per step 3: 4 (o num_questions) domande random dalla db
$question_ids_display = isset($_SESSION[$sess_quiz_question_ids]) && is_array($_SESSION[$sess_quiz_question_ids])
    ? $_SESSION[$sess_quiz_question_ids]
    : [];
if (count($question_ids_display) !== $num_questions) {
    $stmt_ids = $c_pdo->prepare("
        SELECT id FROM quiz_domande
        WHERE attiva = 1 AND ({$tipo_sql_display})
        ORDER BY RAND()
        LIMIT :limit
    ");
    $stmt_ids->bindValue(':limit', $num_questions, PDO::PARAM_INT);
    $stmt_ids->execute();
    $question_ids_display = array_column($stmt_ids->fetchAll(PDO::FETCH_ASSOC), 'id');
    $_SESSION[$sess_quiz_question_ids] = $question_ids_display;
}
$questions = [];
if (!empty($question_ids_display)) {
    $placeholders = implode(',', array_fill(0, count($question_ids_display), '?'));
    $order_sql = 'ORDER BY FIELD(id, ' . implode(',', array_map('intval', $question_ids_display)) . ')';
    $stmt_q = $c_pdo->prepare("
        SELECT id, testo, tipo, audio_file, video_file, risposta_a, risposta_b, risposta_c, risposta_d
        FROM quiz_domande
        WHERE attiva = 1 AND ({$tipo_sql_display}) AND id IN ({$placeholders})
        {$order_sql}
    ");
    $stmt_q->execute(array_values($question_ids_display));
    $questions = $stmt_q->fetchAll(PDO::FETCH_ASSOC);
}

if ($step === 3 && empty($questions)) {
    if ($use_pending_registration) {
        $persist = gst_persist_pending_registration($c_pdo);
        if (!$persist['ok']) {
            unset($_SESSION[GST_QUIZ_PENDING_SESSION_KEY]);
            $_SESSION['registration_error'] = htmlspecialchars($persist['error'] ?? 'Registrazione non completata.', ENT_QUOTES, 'UTF-8');
            header('Location: ' . $BASE_HREF . 'registrazione');
            exit;
        }
        $token = (string) $persist['token'];
        $id_cliente = (int) $persist['id_cliente'];
        $use_pending_registration = false;
    }
    // Nessuna domanda: vai a thank-you
    $instantwin_result = null;
    if ($contest_config['mode'] === 'instantwin') {
        $iwPerPdv = !empty($contest_config['instantwin_per_pdv']);
        if ($iwPerPdv) {
            require_once __DIR__ . '/../instantwin_pdv/instantwin_pdv.php';
        } elseif (!empty($contest_config['instantwin_per_cedi'])) {
            require_once __DIR__ . '/../instantwin_cedi/instantwin_cedi.php';
        } else {
            require_once __DIR__ . '/../instantwin.php';
        }
        $instantwin_result = $iwPerPdv ? checkInstantWinPdv($token) : (!empty($contest_config['instantwin_per_cedi']) ? checkInstantWinCedi($token) : checkInstantWin($token));
        $_SESSION['instantwin_result'] = $instantwin_result;
    } else {
        $_SESSION['instantwin_result'] = null;
    }

    quiz_flow_send_completion_email($c_pdo, $token, $instantwin_result, true);

    $_SESSION[SUMMERCOOP_THANKYOU_TOKEN_SESSION_KEY] = $token;
    unset($_SESSION[SUMMERCOOP_QUIZFLOW_TOKEN_SESSION_KEY]);
    header('Location: ' . $PUBLIC_BASE . '/grazie');
    exit;
}

include __DIR__ . '/../../../public/assets/inc/header.php';
include __DIR__ . '/../../../public/assets/inc/sanitize.inc.php';

$contest_name = htmlspecialchars($contest_config['name'] ?? 'Concorso', ENT_QUOTES, 'UTF-8');
$quiz_page_config = $content_config['pages']['quiz_page'] ?? $content_defaults['pages']['quiz_page'] ?? [];
?>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <?php if ($step !== 3): ?>
            <h1 class="h4 mb-4">Quiz – <?php echo $contest_name; ?></h1>
            <?php endif; ?>

            <?php if ($step === 1): ?>
                <p>Inserisci il codice fidelity per continuare.</p>
                <?php if (!empty($fidelity_error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($fidelity_error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <form method="post" action="<?php echo htmlspecialchars($quiz_form_action, ENT_QUOTES); ?>">
                    <input type="hidden" name="quiz_fidelity_submit" value="1">
                    <div class="form-group">
                        <label for="fidelity">Codice fidelity</label>
                        <input type="text" id="fidelity" name="fidelity" class="form-control" required autocomplete="off">
                    </div>
                    <button type="submit" class="btn btn-primary">Continua</button>
                </form>

            <?php elseif ($step === 2): ?>
                <div class="card registration-card public-page-container--quiz-choice-page mx-auto">
                    <div class="card-body py-4">
                <?php
                global $content_config, $content_defaults;
                $quiz_choice_page_config = is_array($content_config['pages']['quiz_choice_page'] ?? null)
                    ? $content_config['pages']['quiz_choice_page']
                    : (is_array($content_defaults['pages']['quiz_choice_page'] ?? null) ? $content_defaults['pages']['quiz_choice_page'] : []);
                $choice_labels = is_array($quiz_choice_page_config['choice_labels'] ?? null)
                    ? $quiz_choice_page_config['choice_labels']
                    : ['standard' => 'Testo (domande scritte)', 'audio' => 'Audio (ascolto, poi risposte A/B/C/D)', 'submit' => 'Avvia quiz'];
                $label_standard = htmlspecialchars((string) ($choice_labels['standard'] ?? 'Testo (domande scritte)'), ENT_QUOTES, 'UTF-8');
                $label_audio = htmlspecialchars((string) ($choice_labels['audio'] ?? 'Audio (ascolto, poi risposte A/B/C/D)'), ENT_QUOTES, 'UTF-8');
                $label_submit = htmlspecialchars((string) ($choice_labels['submit'] ?? 'Avvia quiz'), ENT_QUOTES, 'UTF-8');
                if (function_exists('render_waiting_page_content') && !empty($quiz_choice_page_config['containers'])) {
                    echo render_waiting_page_content($quiz_choice_page_config, $contest_config);
                } else {
                    echo '<p>Scegli tra <strong>testo</strong> e <strong>audio</strong>:</p>';
                }
                ?>
                <form method="post" action="<?php echo htmlspecialchars($quiz_form_action, ENT_QUOTES); ?>">
                    <input type="hidden" name="quiz_game_choice" value="1">
                    <div class="form-group">
                        <div class="custom-control custom-radio">
                            <input type="radio" id="game_standard" name="quiz_game" value="standard" class="custom-control-input" checked>
                            <label class="custom-control-label" for="game_standard"><?php echo $label_standard; ?></label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" id="game_audio" name="quiz_game" value="audio" class="custom-control-input">
                            <label class="custom-control-label" for="game_audio"><?php echo $label_audio; ?></label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><?php echo $label_submit; ?></button>
                </form>
                    </div>
                </div>

            <?php else: ?>
                <div class="card registration-card public-page-container--quiz-page mx-auto">
                    <div class="card-body py-4">
                <?php if (function_exists('render_quiz_page_header_containers') && !empty($quiz_page_config['containers'])): ?>
                    <?php echo render_quiz_page_header_containers($contest_config, $quiz_page_config); ?>
                <?php else: ?>
                    <h1 class="h4 mb-4">Quiz – <?php echo $contest_name; ?></h1>
                    <p>Rispondi alle domande e invia. Risposte: A, B, C o D.</p>
                <?php endif; ?>
                <form method="post" action="<?php echo htmlspecialchars($quiz_form_action, ENT_QUOTES); ?>">
                    <input type="hidden" name="quiz_submit" value="1">
                    <?php
                    $question_block_style = function_exists('get_quiz_question_block_style') ? get_quiz_question_block_style($quiz_page_config) : 'margin-bottom: 12px; border-radius: 8px; padding: 16px; background-color: #fff;';
                    $question_block_content = function_exists('get_quiz_question_block_content') ? get_quiz_question_block_content($quiz_page_config, $contest_config) : '';
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
                    ?>
                    <style>.form-check-input:checked{background-color:<?php echo htmlspecialchars($tick_color, ENT_QUOTES, 'UTF-8'); ?>;border-color:<?php echo htmlspecialchars($tick_color, ENT_QUOTES, 'UTF-8'); ?>}<?php if ($answer_style === 'square'): ?>.quiz-answer-square .form-check-input{appearance:none;-webkit-appearance:none;width:1.1em;height:1.1em;border:2px solid <?php echo htmlspecialchars($tick_color, ENT_QUOTES, 'UTF-8'); ?>;border-radius:0;vertical-align:middle;}.quiz-answer-square .form-check-input:checked{background:center/70% no-repeat url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='<?php echo $tick_fill_enc; ?>' d='M6.564.75L3 4.314 1.436 2.75l-.682.682L3 5.679l4.246-4.246z'/%3e%3c/svg%3e");border-color:<?php echo htmlspecialchars($tick_color, ENT_QUOTES, 'UTF-8'); ?>}<?php endif; ?></style>
                    <?php echo function_exists('get_quiz_options_list_style_block') ? get_quiz_options_list_style_block() : ''; ?>
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
                        <button type="submit" class="btn" style="background-color: <?php echo htmlspecialchars($btn_config['bg_color'], ENT_QUOTES, 'UTF-8'); ?>; border-color: <?php echo htmlspecialchars($btn_config['bg_color'], ENT_QUOTES, 'UTF-8'); ?>; color: #fff;"><?php echo htmlspecialchars($btn_config['text'], ENT_QUOTES, 'UTF-8'); ?></button>
                    </div>
                </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
echo '<script>window.addEventListener("pageshow",function(e){if(e.persisted){window.location.href=' . json_encode($HOME_URL) . ';}});</script>';
include __DIR__ . '/../../../public/assets/inc/footer.php';
