<?php
include __DIR__ . '/../inc/config.php';
include __DIR__ . '/../inc/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Base per link/asset: deve puntare sempre alla cartella public/ (CSS, immagini, ecc.)
$script = $_SERVER['SCRIPT_NAME'] ?? '';
if (strpos($script, 'app/logic') !== false) {
    // Pagina aperta via app/logic/thank-you.php → base = public/
    $BASE_HREF = rtrim(preg_replace('#/app/logic.*#', '/public/', $script), '/') . '/';
} elseif (strpos(str_replace('\\', '/', $script), '/public/') !== false) {
    // Pagina aperta via public/index.php, public/thank-you.php o route pulita.
    $BASE_HREF = rtrim(dirname($script), '/\\') . '/';
} else {
    $BASE_HREF = '../../public/';
}

require_once __DIR__ . '/../../public/assets/inc/sanitize.inc.php';

$get_tk = isset($_GET['tk']) ? trim((string) $_GET['tk']) : '';
if ($get_tk !== '') {
    $stored = sanitize_sql_string($get_tk);
    if ($stored === false || $stored === '') {
        header('Location: ' . $BASE_HREF . 'registrazione');
        exit;
    }
    $_SESSION[SUMMERCOOP_THANKYOU_TOKEN_SESSION_KEY] = $stored;
    header('Location: ' . $BASE_HREF . 'grazie');
    exit;
}

$tk = '';
if (!empty($_SESSION[SUMMERCOOP_THANKYOU_TOKEN_SESSION_KEY]) && is_string($_SESSION[SUMMERCOOP_THANKYOU_TOKEN_SESSION_KEY])) {
    $raw_sess = $_SESSION[SUMMERCOOP_THANKYOU_TOKEN_SESSION_KEY];
    unset($_SESSION[SUMMERCOOP_THANKYOU_TOKEN_SESSION_KEY]);
    $tk = sanitize_sql_string(trim($raw_sess));
    if ($tk === false) {
        $tk = '';
    }
}

if ($tk === '') {
    header('Location: ' . $BASE_HREF . 'registrazione');
    exit();
}

$public_dir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/public/thank-you.php'), '/\\');
$site_root_path = rtrim(dirname($public_dir), '/\\');
if ($site_root_path === '') {
    $site_root_path = '/';
}
$home_url = rtrim($site_root_path, '/') . '/';

include __DIR__ . '/../../public/assets/inc/header.php';

$c_pdo = conn_pdo();
$stmt = $c_pdo->prepare("
    SELECT u.*, p.token, p.data_partecipazione, p.ip_address, p.has_won, p.prize_id, p.prize_amount, p.win_date
    FROM utenti u
    JOIN partecipazioni p ON u.id_cliente = p.id_cliente
    WHERE p.token = :token
");
$stmt->bindValue(':token', $tk, PDO::PARAM_STR);
$stmt->execute();
$c = $stmt->fetch(PDO::FETCH_OBJ);

if (!$c) {
    header('Location: ' . $BASE_HREF . 'registrazione');
    exit();
}

global $contest_mode;

$has_won = false;
$prize_value = 0;
$prize_id = 0;
$message = '';

// Carica la configurazione delle pagine
$pages_config = $content_config['pages'] ?? ($content_defaults['pages'] ?? []);
$thankyou_page_config = [];
$quiz_passed_snapshot = null;
$quiz_failed_applies = false;
$instantwin_used_quiz_failed_page = false;
$thankyou_page_container_class = 'public-page-container--thankyou-estrazione';
$thankyou_layout_page_key = 'thankyou_estrazione_page';

if ($contest_mode === 'instantwin') {
    $instantwin_result = $_SESSION['instantwin_result'] ?? null;
    // Lettura prima della unset: esito quiz nel flusso quiz → thank-you
    $quiz_passed_snapshot = array_key_exists('quiz_passed', $_SESSION) ? $_SESSION['quiz_passed'] : null;
    $sharedThankyou = !empty($contest_config['instantwin_shared_thankyou_page']);

    if ($instantwin_result && isset($instantwin_result['has_won']) && $instantwin_result['has_won'] === true) {
        $has_won = true;
        $prize_value = $instantwin_result['prize_value'] ?? 0;
        $prize_id = (int) ($instantwin_result['prize_id'] ?? 0);
    } elseif (isset($c->has_won) && $c->has_won == 1) {
        $has_won = true;
        $prize_value = $c->prize_amount ?? 0;
        $prize_id = (int) ($c->prize_id ?? 0);
    }

    if ($sharedThankyou) {
        // Una sola pagina thank-you (contenuto WIN); le email restano separate win/loss
        $quiz_cfg_ty = $contest_config['quiz'] ?? [];
        $quiz_failed_applies = !empty($contest_config['quiz_enabled'])
            && !empty($quiz_cfg_ty['require_all_correct'])
            && $quiz_passed_snapshot === false;
        if ($quiz_failed_applies) {
            $qf_primary = $pages_config['thankyou_instantwin_quiz_failed_page'] ?? [];
            $qf_html = isset($qf_primary['html']) ? trim((string) $qf_primary['html']) : '';
            $has_qf_content = !empty($qf_primary['containers']) || $qf_html !== '';
            if ($has_qf_content) {
                $thankyou_page_config = $qf_primary;
                $instantwin_used_quiz_failed_page = true;
                $thankyou_page_container_class = 'public-page-container--thankyou-quiz-failed';
                $thankyou_layout_page_key = 'thankyou_instantwin_quiz_failed_page';
            } else {
                $thankyou_page_config = $pages_config['thankyou_instantwin_win_page'] ?? [];
                $thankyou_page_container_class = 'public-page-container--thankyou-win';
                $thankyou_layout_page_key = 'thankyou_instantwin_win_page';
            }
        } else {
            $thankyou_page_config = $pages_config['thankyou_instantwin_win_page'] ?? [];
            $thankyou_page_container_class = 'public-page-container--thankyou-win';
            $thankyou_layout_page_key = 'thankyou_instantwin_win_page';
        }
    } elseif ($has_won) {
        // Utilizza la page thank-you WIN
        $thankyou_page_config = $pages_config['thankyou_instantwin_win_page'] ?? [];
        $thankyou_page_container_class = 'public-page-container--thankyou-win';
        $thankyou_layout_page_key = 'thankyou_instantwin_win_page';
    } else {
        $quiz_cfg_ty = $contest_config['quiz'] ?? [];
        $quiz_failed_applies = !empty($contest_config['quiz_enabled'])
            && !empty($quiz_cfg_ty['require_all_correct'])
            && $quiz_passed_snapshot === false;

        if ($quiz_failed_applies) {
            $qf_primary = $pages_config['thankyou_instantwin_quiz_failed_page'] ?? [];
            $qf_html = isset($qf_primary['html']) ? trim((string) $qf_primary['html']) : '';
            $has_qf_content = !empty($qf_primary['containers']) || $qf_html !== '';
            if ($has_qf_content) {
                $thankyou_page_config = $qf_primary;
                $instantwin_used_quiz_failed_page = true;
                $thankyou_page_container_class = 'public-page-container--thankyou-quiz-failed';
                $thankyou_layout_page_key = 'thankyou_instantwin_quiz_failed_page';
            } else {
                $thankyou_page_config = $pages_config['thankyou_instantwin_loss_page'] ?? [];
                $thankyou_page_container_class = 'public-page-container--thankyou-loss';
                $thankyou_layout_page_key = 'thankyou_instantwin_loss_page';
            }
        } else {
            // Thank-you LOSE (quiz superato ma IW perso, oppure quiz disattivato / navigazione senza sessione quiz)
            $thankyou_page_config = $pages_config['thankyou_instantwin_loss_page'] ?? [];
            $thankyou_page_container_class = 'public-page-container--thankyou-loss';
            $thankyou_layout_page_key = 'thankyou_instantwin_loss_page';
        }
    }

    if (isset($_SESSION['instantwin_result'])) {
        unset($_SESSION['instantwin_result']);
    }
} else {
    $quiz_passed_snapshot = array_key_exists('quiz_passed', $_SESSION) ? $_SESSION['quiz_passed'] : null;
    $quiz_cfg_ty = $contest_config['quiz'] ?? [];
    $quiz_failed_applies = !empty($contest_config['quiz_enabled'])
        && !empty($quiz_cfg_ty['require_all_correct'])
        && $quiz_passed_snapshot === false;

    if ($quiz_failed_applies) {
        $qf_primary = $pages_config['thankyou_estrazione_quiz_failed_page'] ?? [];
        $qf_html = isset($qf_primary['html']) ? trim((string) $qf_primary['html']) : '';
        $has_qf_content = !empty($qf_primary['containers']) || $qf_html !== '';
        if ($has_qf_content) {
            $thankyou_page_config = $qf_primary;
            $thankyou_page_container_class = 'public-page-container--thankyou-estrazione-quiz-failed';
            $thankyou_layout_page_key = 'thankyou_estrazione_quiz_failed_page';
        } else {
            $thankyou_page_config = $pages_config['thankyou_estrazione_page'] ?? [];
            $thankyou_page_container_class = 'public-page-container--thankyou-estrazione';
            $thankyou_layout_page_key = 'thankyou_estrazione_page';
        }
    } else {
        $thankyou_page_config = $pages_config['thankyou_estrazione_page'] ?? [];
        $thankyou_page_container_class = 'public-page-container--thankyou-estrazione';
        $thankyou_layout_page_key = 'thankyou_estrazione_page';
    }
}

// Quiz abilitato: in estrazione solo chi ha superato il quiz è eligibile al sorteggio (quiz_tentativi.esito)
$quiz_enabled = !empty($contest_config['quiz_enabled']);
$quiz_passed = isset($_SESSION['quiz_passed']) ? (bool) $_SESSION['quiz_passed'] : null;
if (isset($_SESSION['quiz_passed'])) {
    unset($_SESSION['quiz_passed']);
}
if (isset($_SESSION['gst_quiz_pending_registration'])) {
    unset($_SESSION['gst_quiz_pending_registration']);
}
foreach (array_keys($_SESSION) as $session_key) {
    if (strpos((string) $session_key, 'quiz_') === 0) {
        unset($_SESSION[$session_key]);
    }
}

// Rendi il contenuto usando la funzione render_thankyou_page_content
$prize_type = '';
if ($prize_id > 0) {
    $prize_type = config_resolve_prize_type_from_id($c_pdo, $prize_id);
}
if (!empty($thankyou_page_config)) {
    $message = render_thankyou_page_content(
        is_array($thankyou_page_config) ? $thankyou_page_config : [],
        $contest_config,
        $prize_value,
        $prize_type
    );
} else {
    // Fallback al vecchio sistema se la configurazione non esiste
    if ($contest_mode === 'instantwin') {
        if ($has_won) {
            $message = '
                <div class="alert alert-success text-center p-4 mb-4">
                    <h2 class="fs-3 fw-bold mb-3">COMPLIMENTI!</h2>
                    <h3 class="fs-4 fw-semibold mb-4">HAI VINTO!</h3>
                    <div class="fs-1 fw-bold mb-4" style="color: var(--contest-primary);">
                        &euro;' . number_format($prize_value, 2, ',', '.') . '
                    </div>
                    <p class="fs-5 mb-2">Riceverai tutte le informazioni per ritirare il tuo premio all\'indirizzo email indicato.</p>
                    <p class="mb-0">Conserva lo scontrino originale per eventuali verifiche.</p>
                </div>';
        } else {
            $message = '
                <div class="alert alert-danger text-center p-4 mb-4">
                    <h3 class="fs-3 fw-bold mb-3">NON HAI VINTO</h3>
                    <p class="fs-5 mb-2">Questa volta non hai vinto, ma puoi partecipare di nuovo con un nuovo scontrino!</p>
                    <p class="mb-0">Ogni scontrino valido può essere caricato una sola volta.</p>
                </div>';
        }
    } else {
        if ($quiz_failed_applies) {
            $message = '
            <div class="alert alert-warning text-center p-4 mb-4">
                <h3 class="fs-4 fw-bold mb-3">Quiz non superato</h3>
                <p class="fs-5 mb-2">Non hai risposto correttamente al quiz. Non sei eligibile per l\'estrazione finale.</p>
                <p class="mb-0">Puoi partecipare di nuovo con una nuova iscrizione e un nuovo scontrino.</p>
            </div>';
        } else {
            $thankyou_message = 'La tua partecipazione a "' . htmlspecialchars($contest_config['name']) . '" e stata registrata con successo.';
            $thankyou_subtitle = 'Riceverai comunicazioni all\'indirizzo email indicato. Conserva lo scontrino fino alla chiusura delle verifiche.';
            if ($quiz_enabled && $quiz_passed === true) {
                $thankyou_subtitle = 'Hai superato il quiz: sei in lista per l\'estrazione finale. Riceverai comunicazioni all\'indirizzo email indicato.';
            }
            $message = '
            <div class="text-center">
                <p class="fs-5 fw-semibold mb-3">' . $thankyou_message . '</p>
                <p class="mb-1">' . $thankyou_subtitle . '</p>
                <p class="mb-0">Per ogni dettaglio consulta il regolamento completo.</p>
            </div>';
        }
    }
}
?>

<?php
$logo_display_mode = strtolower(trim((string)($contest_config['logo_display_mode'] ?? '')));
if (!in_array($logo_display_mode, ['none', 'logo', 'header', 'both'], true)) {
    $logo_display_mode = (array_key_exists('logo_enabled', $contest_config) && !filter_var($contest_config['logo_enabled'], FILTER_VALIDATE_BOOLEAN)) ? 'none' : 'logo';
}
$show_logo = in_array($logo_display_mode, ['logo', 'both'], true);
$show_header = in_array($logo_display_mode, ['header', 'both'], true) && trim((string)($contest_config['header_image'] ?? '')) !== '';
$has_brand_visual = $show_logo || $show_header;
?>

<script>
window.addEventListener("pageshow", function (event) {
    if (event.persisted) {
        window.location.href = '<?php echo htmlspecialchars($home_url, ENT_QUOTES); ?>';
    }
});
</script>

<?php echo get_brand_layout_style_block($thankyou_layout_page_key); ?>

<div class="container-xxl py-4 flex-grow-1 thank-you-page header-main-background-wrapper brand-layout-page-active<?php echo $show_header ? ' brand-header-active' : ''; ?><?php echo ($show_header && !$show_logo) ? ' brand-header-only' : ''; ?>">
    <main class="row justify-content-center w-100 mx-0">
        <div class="col-12 col-lg-11 col-xl-10 col-xxl-9 px-0 px-md-3">
            <div class="logo-stack mx-auto brand-layout-page--<?php echo htmlspecialchars($thankyou_layout_page_key, ENT_QUOTES, 'UTF-8'); ?>">
                <?php if ($has_brand_visual): ?>
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
                        <img src="<?php echo $header_image; ?>" alt="Header <?php echo htmlspecialchars($contest_config['name']); ?>" class="img-fluid card-logo-img brand-header-img">
                    </picture>
                    <?php endif; ?>
                    <?php if ($show_logo): ?>
                    <picture>
                        <?php if (!empty($logo_mobile)): ?>
                            <source media="(max-width: 767.98px)" srcset="<?php echo $logo_mobile; ?>">
                        <?php endif; ?>
                        <img src="<?php echo $logo_main; ?>" alt="Logo <?php echo htmlspecialchars($contest_config['name']); ?>" class="img-fluid card-logo-img">
                    </picture>
                    <?php endif; ?>
                </header>
                <?php endif; ?>
                <div class="card registration-card <?php echo htmlspecialchars($thankyou_page_container_class, ENT_QUOTES, 'UTF-8'); ?> <?php echo $has_brand_visual ? 'logo-attached-card ' : ''; ?>mx-auto">
                    <div class="card-body text-center">
                        <?php echo $message; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../../public/assets/inc/footer.php'; ?>
