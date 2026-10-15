<?php
require_once __DIR__ . '/../app/inc/config.php';

$code = isset($public_error_code) ? (int) $public_error_code : (int) ($_GET['code'] ?? 404);
$allowed_codes = [403, 404, 405, 500];
if (!in_array($code, $allowed_codes, true)) {
    $code = 404;
}

$messages = [
    403 => [
        'title' => 'Accesso non consentito',
        'text' => 'Non hai i permessi per visualizzare questa pagina.',
    ],
    404 => [
        'title' => 'Pagina non trovata',
        'text' => 'La pagina richiesta non esiste o non è più disponibile.',
    ],
    405 => [
        'title' => 'Operazione non consentita',
        'text' => 'Questa operazione non puo essere eseguita.',
    ],
    500 => [
        'title' => 'Errore',
        'text' => 'Si e verificato un errore. Riprova piu tardi.',
    ],
];

http_response_code($code);
$error = $messages[$code];
$home_target = !empty($contest_config['published'])
    ? (!empty($contest_config['participation_page_enabled']) ? 'partecipazione' : 'registrazione')
    : 'benvenuto';
$_errorBtnColors = resolve_button_colors($contest_config);
$primary_button_bg = (string) ($_errorBtnColors['primary']['bg'] ?? ($contest_config['colors']['primary'] ?? '#0d6efd'));
unset($_errorBtnColors);

include __DIR__ . '/assets/inc/header.php';
?>
<style>
.public-error-code {
    font-size: clamp(3rem, 10vw, 6.5rem);
    line-height: 0.95;
    font-weight: 800;
    color: <?php echo htmlspecialchars($primary_button_bg, ENT_QUOTES, 'UTF-8'); ?>;
    letter-spacing: 0;
}
</style>
<div class="container-xxl py-4 flex-grow-1 header-main-background-wrapper">
    <main class="w-100">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-11 col-xl-10 col-xxl-9 mx-auto logo-stack">
                <div class="registration-card public-page-container--registration card p-4 p-md-5 mx-auto w-100 text-center">
                    <div class="mx-auto col-12 col-lg-10">
                        <div class="public-error-code mb-3"><?php echo (int) $code; ?></div>
                        <h1 class="h4 fw-bold mb-3"><?php echo htmlspecialchars($error['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
                        <p class="mb-4"><?php echo htmlspecialchars($error['text'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <div class="mx-auto px-2 mt-4 col-12 col-md-6 col-lg-5">
                            <a class="btn btn-success btn-lg fw-bold w-100" href="<?php echo htmlspecialchars($home_target, ENT_QUOTES, 'UTF-8'); ?>">Torna alla pagina principale</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/assets/inc/footer.php'; ?>
