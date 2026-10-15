<?php
/**
 * Anteprima delle pagine thank-you (Instant Win - Vincita / Non vincita, Estrazione - Partecipazione).
 * Accessibile solo da manager per utenti pm_admin.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['user_name']) || $_SESSION['user_name'] !== 'pm_admin') {
    header('HTTP/1.1 403 Forbidden');
    exit('Accesso non autorizzato.');
}

$type = isset($_GET['type']) ? trim($_GET['type']) : '';

if (!in_array($type, ['instantwin_win', 'instantwin_loss', 'instantwin_quiz_failed', 'estrazione', 'estrazione_quiz_failed'], true)) {
    header('HTTP/1.1 400 Bad Request');
    exit('Parametro type non valido.');
}

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/manager'));
$projectRoot = preg_replace('#/manager(?:/.*)?$#', '', $scriptDir);
$BASE_HREF = rtrim((string) $projectRoot, '/') . '/public/';
require_once __DIR__ . '/../../app/inc/config.php';
require_once __DIR__ . '/../../app/inc/functions.php';

// Blocca l'anteprima se il concorso è pubblicato (Publicato = Sì)
$is_published = $content_config['published'] ?? ($content_defaults['published'] ?? false);
if ($is_published) {
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $manager_path = dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $manager_path = rtrim(str_replace('\\', '/', $manager_path), '/') . '/';
    header('Location: ' . $scheme . '://' . $host . $manager_path);
    exit;
}

$pages_config = $content_config['pages'] ?? ($content_defaults['pages'] ?? []);
$thankyou_page_container_class = 'public-page-container--thankyou-estrazione';
$brand_layout_page_key = 'thankyou_estrazione_page';
$prize_type = '';

if ($type === 'instantwin_win') {
    $thankyou_page_config = $pages_config['thankyou_instantwin_win_page'] ?? [];
    $thankyou_page_container_class = 'public-page-container--thankyou-win';
    $brand_layout_page_key = 'thankyou_instantwin_win_page';
    $prize_value = 10.00; // Valore di esempio per anteprima
    try {
        $previewPdo = conn_pdo();
        $stmtPreviewPrize = $previewPdo->prepare('SELECT valore, tipo_premio FROM premi WHERE IS_ACTIVE = 1 ORDER BY ID ASC LIMIT 1');
        if ($stmtPreviewPrize && $stmtPreviewPrize->execute()) {
            $previewPrizeRow = $stmtPreviewPrize->fetch(PDO::FETCH_ASSOC);
            if (is_array($previewPrizeRow)) {
                if (($previewPrizeRow['valore'] ?? '') !== '') {
                    $prize_value = (float) $previewPrizeRow['valore'];
                }
                $prize_type = trim((string) ($previewPrizeRow['tipo_premio'] ?? ''));
                if ($prize_type === '') {
                    $prize_type = 'Premio';
                }
            }
        }
    } catch (Throwable $e) {
        $prize_type = 'Premio';
    }
} elseif ($type === 'instantwin_loss') {
    $thankyou_page_config = $pages_config['thankyou_instantwin_loss_page'] ?? [];
    $thankyou_page_container_class = 'public-page-container--thankyou-loss';
    $brand_layout_page_key = 'thankyou_instantwin_loss_page';
    $prize_value = 0;
} elseif ($type === 'instantwin_quiz_failed') {
    $thankyou_page_config = $pages_config['thankyou_instantwin_quiz_failed_page'] ?? [];
    $thankyou_page_container_class = 'public-page-container--thankyou-quiz-failed';
    $brand_layout_page_key = 'thankyou_instantwin_quiz_failed_page';
    $prize_value = 0;
} elseif ($type === 'estrazione_quiz_failed') {
    $thankyou_page_config = $pages_config['thankyou_estrazione_quiz_failed_page'] ?? [];
    $thankyou_page_container_class = 'public-page-container--thankyou-estrazione-quiz-failed';
    $brand_layout_page_key = 'thankyou_estrazione_quiz_failed_page';
    $prize_value = 0;
} else {
    // type === 'estrazione'
    $thankyou_page_config = $pages_config['thankyou_estrazione_page'] ?? [];
    $thankyou_page_container_class = 'public-page-container--thankyou-estrazione';
    $brand_layout_page_key = 'thankyou_estrazione_page';
    $prize_value = 0;
}

include __DIR__ . '/../../public/assets/inc/header.php';

$message = '';
if (!empty($thankyou_page_config)) {
    $message = render_thankyou_page_content(
        is_array($thankyou_page_config) ? $thankyou_page_config : [],
        $contest_config,
        $prize_value,
        $prize_type
    );
} else {
    if ($type === 'instantwin_win') {
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
    } elseif ($type === 'instantwin_loss') {
        $message = '
            <div class="alert alert-danger text-center p-4 mb-4">
                <h3 class="fs-3 fw-bold mb-3">NON HAI VINTO</h3>
                <p class="fs-5 mb-2">Questa volta non hai vinto, ma puoi partecipare di nuovo con un nuovo scontrino!</p>
                <p class="mb-0">Ogni scontrino valido può essere caricato una sola volta.</p>
            </div>';
    } elseif ($type === 'instantwin_quiz_failed') {
        $message = '
            <div class="alert alert-warning text-center p-4 mb-4">
                <h3 class="fs-3 fw-bold mb-3">RISPOSTE NON CORRETTE</h3>
                <p class="fs-5 mb-2">Non hai risposto correttamente a tutte le domande del quiz.</p>
                <p class="mb-0">Anteprima: configura la pagina nel manager.</p>
            </div>';
    } elseif ($type === 'estrazione_quiz_failed') {
        $message = '
            <div class="alert alert-warning text-center p-4 mb-4">
                <h3 class="fs-4 fw-bold mb-3">Quiz non superato</h3>
                <p class="fs-5 mb-2">Non hai risposto correttamente al quiz. Non sei eligibile per l\'estrazione finale.</p>
                <p class="mb-0">Anteprima: configura la pagina nel manager.</p>
            </div>';
    } else {
        $message = '
            <div class="text-center">
                <h2 class="fs-3 fw-bold mb-3">GRAZIE PER AVER PARTECIPATO!</h2>
                <p class="fs-5 mb-2">La tua partecipazione è stata registrata con successo.</p>
                <p class="mb-0">Riceverai comunicazioni all\'indirizzo email indicato. Conserva lo scontrino fino alla chiusura delle verifiche.</p>
            </div>';
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
<div class="container-xxl py-4 flex-grow-1 thank-you-page header-main-background-wrapper brand-layout-page-active<?php echo $show_header ? ' brand-header-active' : ''; ?><?php echo ($show_header && !$show_logo) ? ' brand-header-only' : ''; ?>">
    <main class="row justify-content-center w-100 mx-0">
        <div class="col-12 col-lg-11 col-xl-10 col-xxl-9 px-0 px-md-3">
            <div class="logo-stack mx-auto brand-layout-page--<?php echo htmlspecialchars($brand_layout_page_key, ENT_QUOTES, 'UTF-8'); ?>">
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
                        <img src="<?php echo $header_image; ?>" alt="Header <?php echo htmlspecialchars($contest_config['name'] ?? 'Concorso'); ?>" class="img-fluid card-logo-img brand-header-img">
                    </picture>
                    <?php endif; ?>
                    <?php if ($show_logo): ?>
                    <picture>
                        <?php if (!empty($logo_mobile)): ?>
                            <source media="(max-width: 767.98px)" srcset="<?php echo $logo_mobile; ?>">
                        <?php endif; ?>
                        <img src="<?php echo $logo_main; ?>" alt="Logo <?php echo htmlspecialchars($contest_config['name'] ?? 'Concorso'); ?>" class="img-fluid card-logo-img">
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
