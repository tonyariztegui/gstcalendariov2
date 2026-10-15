<?php
// Includi configurazione e utilità prima di qualsiasi output
include __DIR__ . '/../app/inc/config.php';
include __DIR__ . '/../app/inc/functions.php';
include 'assets/inc/sanitize.inc.php';
include 'assets/mail/email_system.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!empty($_GET['preview'])) {
    if (!empty($contest_config['published'])) {
        header('Location: assistenza');
        exit;
    }
    $_SESSION['contest_preview'] = true;
}
$is_preview = !empty($_SESSION['contest_preview'] ?? null);
if (empty($contest_config['published']) && !$is_preview) {
    header('Location: benvenuto');
    exit;
}

$now = time();
$help_form_enabled = (bool) ($contest_config['help_form_enabled'] ?? true);
$help_page_config = [];
if (isset($content_config['pages']['help_page']) && is_array($content_config['pages']['help_page'])) {
    $help_page_config = $content_config['pages']['help_page'];
} elseif (isset($content_defaults['pages']['help_page']) && is_array($content_defaults['pages']['help_page'])) {
    $help_page_config = $content_defaults['pages']['help_page'];
}

// Gestione form assistenza (PRG) prima di qualsiasi output
if ($help_form_enabled && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $nome = trim($_POST['nome'] ?? '');
    $cognome = trim($_POST['cognome'] ?? '');
    $prefisso = trim($_POST['prefisso'] ?? '');
    $tel = trim($_POST['tel'] ?? '');
    $tel = preg_replace('/\D/', '', $tel); // solo numeri
    $email = trim($_POST['email'] ?? '');
    $messaggio = trim($_POST['messaggio'] ?? '');
    $privacy = isset($_POST['privacy']) ? 1 : 0;

    $errors = [];
    if (mb_strlen($nome) < 2) { $errors[] = "- Il nome è obbligatorio e deve contenere almeno 2 caratteri."; }
    if (mb_strlen($cognome) < 2) { $errors[] = "- Il cognome è obbligatorio e deve contenere almeno 2 caratteri."; }
    if (empty($email) || strpos($email, '@') === false) { $errors[] = "- L'email è obbligatoria e deve contenere il simbolo @."; }
    if (empty($prefisso)) { $errors[] = "- Prefisso dovrebbe contenere al massimo 3 caratteri."; }
    elseif (mb_strlen($prefisso) > 3) { $errors[] = "- Prefisso dovrebbe contenere al massimo 3 caratteri."; }
    if (empty($tel) || strlen($tel) !== 10 || !preg_match('/^[0-9]{10}$/', $tel)) { $errors[] = "- Il numero di cellulare è obbligatorio e deve contenere 10 cifre."; }
    if (empty($messaggio)) { $errors[] = "- Inserisci il tuo messaggio."; }
    elseif (mb_strlen($messaggio) > 5000) { $errors[] = "- Il messaggio non può superare i 5000 caratteri."; }
    if (!$privacy) { $errors[] = "- Devi accettare l'informativa privacy per continuare."; }

    if (!empty($errors)) {
        $_SESSION['help_error_list'] = '<ul class="list-unstyled mb-0"><li>' . implode('</li><li>', $errors) . '</li></ul>';
        header('Location: assistenza#help-errors');
        exit;
    }

    $sent = sendAssistanceEmail($nome, $cognome, $prefisso, $tel, $email, $messaggio);

    if ($sent) {
        $_SESSION['help_message'] = 'success';
        $_SESSION['help_message_text'] = 'Il tuo messaggio è stato inviato con successo.';
    } else {
        $_SESSION['help_message'] = 'error';
        $_SESSION['help_message_text'] = "Errore nell'invio del messaggio. Riprova più tardi.";
    }

    header('Location: assistenza');
    exit;
}

// In preview non bloccare l'accesso con le date del concorso
if (!$is_preview && $now < $date_debut) {
    header('Location: ' . assistenza_blocked_redirect_target());
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Assistenza - <?php echo htmlspecialchars($contest_config['name']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php echo get_favicon_tags(); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/minimal.css">
    <?php echo get_background_style_block(); ?>
    <?php echo function_exists('get_custom_fonts_style_block') ? get_custom_fonts_style_block(false) : ''; ?>
    <?php echo get_logo_desktop_size_style_block(); ?>
    <?php echo get_brand_layout_style_block('help'); ?>
    <?php echo get_theme_colors_block(); ?>
    <?php echo get_button_style_block(); ?>
    <?php echo get_form_container_style_block(); ?>
    <?php echo get_help_page_style_block(); ?>
    <?php echo get_footer_style_block(); ?>
</head>
<body class="help-page">
<div class="d-flex flex-column min-vh-100 body-background-wrapper">
<?php
// Messaggi flash
$flash_message = null;
if (isset($_SESSION['help_message'])) {
    $flash_message = [
        'type' => $_SESSION['help_message'],
        'text' => $_SESSION['help_message_text'] ?? '',
    ];
    unset($_SESSION['help_message'], $_SESSION['help_message_text']);
}

$help_errors_html = '';
if (isset($_SESSION['help_error_list'])) {
    $help_errors_html = $_SESSION['help_error_list'];
    unset($_SESSION['help_error_list']);
}
$logo_display_mode = strtolower(trim((string)($contest_config['logo_display_mode'] ?? '')));
if (!in_array($logo_display_mode, ['none', 'logo', 'header', 'both'], true)) {
    $logo_display_mode = (array_key_exists('logo_enabled', $contest_config) && !filter_var($contest_config['logo_enabled'], FILTER_VALIDATE_BOOLEAN)) ? 'none' : 'logo';
}
$show_logo = in_array($logo_display_mode, ['logo', 'both'], true);
$show_header = in_array($logo_display_mode, ['header', 'both'], true) && trim((string)($contest_config['header_image'] ?? '')) !== '';
$has_brand_visual = $show_logo || $show_header;
?>
    <div class="container-xxl py-4 flex-grow-1 header-main-background-wrapper brand-layout-page-active<?php echo $show_header ? ' brand-header-active' : ''; ?><?php echo ($show_header && !$show_logo) ? ' brand-header-only' : ''; ?>">
        <main class="w-100">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-11 col-xl-10 col-xxl-9 mx-auto logo-stack brand-layout-page--help">
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
                    <div class="card registration-card public-page-container--help <?php echo $has_brand_visual ? 'logo-attached-card ' : ''; ?>p-4 p-md-5 mx-auto mb-5 w-100">
                        <div class="concorso-annuncio help-faq-section">
                            <div class="accordion help-faq-accordion" id="faqAccordion">
                                <h1 class="fw-bold text-center mb-4">Domande Frequenti</h1>
                                <?php
                                $faq_items = $contest_config['faq'] ?? [];
                                $replacements = [
                                    '{{MIN_SPEND}}' => number_format($contest_config['min_spend'], 2, ',', '.'),
                                    '{{START_DATE}}' => htmlspecialchars($contest_config['start_date_formatted'] ?? $contest_config['start_date']),
                                    '{{END_DATE}}' => htmlspecialchars($contest_config['end_date_formatted'] ?? $contest_config['end_date']),
                                    '{{CONTEST_NAME}}' => htmlspecialchars($contest_config['name'])
                                ];
                                $idx = 1;
                                foreach ($faq_items as $item) {
                                    $question = htmlspecialchars($item['q'] ?? "Domanda {$idx}");
                                    $answer_raw = $item['a'] ?? '';
                                    $answer = str_replace(array_keys($replacements), array_values($replacements), $answer_raw);
                                    $answer_safe = nl2br(htmlspecialchars($answer));
                                    $collapse_id = 'collapse' . $idx;
                                    $heading_id = 'faq' . $idx;
                                ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="<?php echo $heading_id; ?>">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#<?php echo $collapse_id; ?>" aria-expanded="false" aria-controls="<?php echo $collapse_id; ?>">
                                            <?php echo $question; ?>
                                        </button>
                                    </h2>
                                    <div id="<?php echo $collapse_id; ?>" class="accordion-collapse collapse" aria-labelledby="<?php echo $heading_id; ?>" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            <?php echo $answer_safe; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                    $idx++;
                                }
                                ?>
                            </div>
                        </div>
                        <?php if ($help_form_enabled): ?>
                        <div class="text-center mb-4 mt-5">
                            <h1 class="fw-bold help-form-heading">Hai bisogno del nostro supporto?</h1>
                            <h2 class="fs-4 help-form-subtitle">Compila il modulo</h2>
                        </div>
                        <div class="help-form-wrapper">
                            <form method="post" name="register_user" id="help-form">
                                <?php if ($flash_message): ?>
                                    <?php
                                        $alert_class = $flash_message['type'] === 'success' ? 'alert-success' : 'alert-danger';
                                    ?>
                                    <div class="alert <?php echo $alert_class; ?> mb-4" role="alert">
                                        <?php echo htmlspecialchars($flash_message['text'], ENT_QUOTES); ?>
                                    </div>
                                <?php endif; ?>
                                <div id="help-errors" class="alert alert-danger mb-4" role="alert" <?php echo !empty($help_errors_html) ? '' : 'style="display:none"'; ?>><?php echo $help_errors_html; ?></div>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label for="nome" class="visually-hidden">Nome</label>
                                        <input type="text" placeholder="Nome" class="form-control form-req" id="nome" name="nome" data-error="Inserisci il tuo Nome">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="cognome" class="visually-hidden">Cognome</label>
                                        <input type="text" placeholder="Cognome" class="form-control form-req" id="cognome" name="cognome" data-error="Inserisci il tuo Cognome">
                                    </div>
                                    <div class="col-12 col-md-6 mb-3 mb-md-0">
                                        <label for="email" class="visually-hidden">E-mail</label>
                                        <input type="text" placeholder="E-mail" class="form-control form-req" id="email" name="email" data-error="Inserisci il tuo Indirizzo Email">
                                    </div>
                                    <div class="col-4 col-sm-3 col-md-2 mb-3 mb-md-0">
                                        <label for="prefisso" class="visually-hidden">Prefisso</label>
                                        <input type="text" placeholder="Prefisso" class="form-control form-req" id="prefisso" name="prefisso" data-error="Prefisso" maxlength="3" value="+39">
                                    </div>
                                    <div class="col-8 col-sm-9 col-md-4">
                                        <label for="telefono" class="visually-hidden">N. Telefonico</label>
                                        <input type="text" placeholder="N. Telefonico" class="form-control form-req" id="telefono" name="tel" data-error="Inserisci il tuo Telefono">
                                    </div>
                                    <div class="col-12">
                                        <label for="message" class="visually-hidden">Messaggio</label>
                                        <textarea placeholder="Inserisci il tuo messaggio" class="form-control form-req" id="message" name="messaggio" rows="4" maxlength="5000" data-error="Inserisci il tuo messaggio"></textarea>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check" id="privacy-container">
                                            <input class="form-check-input form-req form-checkbox-required" type="checkbox" name="privacy"
                                                id="privacy" value="1" data-error="Devi accettare la nostra normativa sulla Privacy" style="width: 20px; height: 20px;">
                                            <label class="form-check-label" for="privacy">
                                                Dichiaro di aver preso visione e di accettare
                                                <a href="<?php echo htmlspecialchars($contest_config['privacy_link']); ?>" target="_blank" rel="noopener noreferrer" aria-label="Informativa della privacy (si apre in una nuova finestra)">l'informativa della privacy</a> *
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-12 text-center mt-4">
                                        <button
                                            type="submit"
                                            class="btn btn-success btn-lg fw-bold"
                                            name="register"
                                            data-gst-track="invia"
                                        ><?php echo htmlspecialchars((string) ($contest_config['buttons']['help']['text'] ?? 'INVIA')); ?></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <?php else: ?>
                            <?php echo render_config_page_container_content($help_page_config, $contest_config, ['content_class' => 'help-page-custom-content']); ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
<?php include 'assets/inc/footer.php'; ?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha384-vtXRMe3mGCbOeY7l30aIg8H9p3GdeSe4IFlP6G8JMa7o7lXvnz3GFKzPxzJdPfGK" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" integrity="sha384-nLoOnA/BDh8A/jxqtckg4DumuCGOBYUnNJLZdQz/zfYNp3wcjGSoWTAzgko06G/2" crossorigin="anonymous"></script>
<script>
$(document).ready(function() {
    function showError(fieldId, message) {
        const field = $('#' + fieldId);
        if (fieldId === 'privacy') {
            field.addClass('is-invalid');
            field.removeClass('is-valid');
            const label = field.next('label');
            label.removeClass('is-invalid text-danger');
            label.css('color', 'var(--contest-text)');
            const container = field.closest('.form-check');
            let errorDiv = container.next('.error-message');
            if (errorDiv.length === 0) {
                container.after('<div class="error-message text-danger small mt-1" style="display: block; width: 100%; text-align: center;"></div>');
                errorDiv = container.next('.error-message');
            }
            errorDiv.text(message);
        } else {
            let errorDiv = field.siblings('.error-message');
            if (errorDiv.length === 0) {
                field.after('<div class="error-message text-danger small mt-1"></div>');
                errorDiv = field.siblings('.error-message');
            }
            errorDiv.text(message);
            field.addClass('is-invalid');
            field.removeClass('is-valid');
        }
    }
    function showSuccess(fieldId) {
        const field = $('#' + fieldId);
        if (fieldId === 'privacy') {
            field.removeClass('is-invalid');
            const container = field.closest('.form-check');
            container.next('.error-message').remove();
        } else {
            field.siblings('.error-message').remove();
            field.removeClass('is-invalid');
            field.addClass('is-valid');
        }
    }
    $('#nome').on('blur', function() {
        const value = $(this).val().trim();
        if (value.length === 0) {
            showError('nome', 'Il nome è obbligatorio');
        } else if (value.length < 2) {
            showError('nome', 'Il nome deve contenere almeno 2 caratteri');
        } else {
            showSuccess('nome');
        }
    });
    $('#cognome').on('blur', function() {
        const value = $(this).val().trim();
        if (value.length === 0) {
            showError('cognome', 'Il cognome è obbligatorio');
        } else if (value.length < 2) {
            showError('cognome', 'Il cognome deve contenere almeno 2 caratteri');
        } else {
            showSuccess('cognome');
        }
    });
    $('#email').on('blur', function() {
        const value = $(this).val().trim();
        if (value.length === 0) {
            showError('email', 'L\'email è obbligatoria');
        } else if (value.indexOf('@') === -1) {
            showError('email', 'L\'email deve contenere il simbolo @');
        } else {
            showSuccess('email');
        }
    });
    $('#prefisso').on('blur', function() {
        const value = $(this).val().trim();
        if (value.length === 0) {
            showError('prefisso', 'Il prefisso è obbligatorio');
        } else if (value.length > 3) {
            showError('prefisso', 'Prefisso dovrebbe contenere al massimo 3 caratteri');
        } else if (value.length > 0 && value.length <= 3) {
            showSuccess('prefisso');
        }
    });
    $('#telefono').on('input', function() {
        let value = $(this).val();
        const numericValue = value.replace(/\D/g, '');
        if (numericValue.length > 10) {
            $(this).val(numericValue.substring(0, 10));
        } else {
            $(this).val(numericValue);
        }
    });
    $('#telefono').on('blur', function() {
        const value = $(this).val().trim();
        const numericValue = value.replace(/\D/g, '');
        if (value.length === 0) {
            showError('telefono', 'Il numero di cellulare è obbligatorio');
        } else if (numericValue.length === 0) {
            showError('telefono', 'Il numero di telefono può contenere solo numeri');
        } else if (numericValue.length !== 10) {
            showError('telefono', 'Inserisci un numero di 10 cifre');
        } else {
            $(this).val(numericValue);
            showSuccess('telefono');
        }
    });
    $('#message').on('blur', function() {
        const value = $(this).val().trim();
        if (value.length === 0) {
            showError('message', 'Il messaggio è obbligatorio');
        } else if (value.length > 5000) {
            showError('message', 'Il messaggio non può superare i 5000 caratteri');
        } else {
            showSuccess('message');
        }
    });
    $('#privacy').on('change', function() {
        if ($(this).is(':checked')) {
            showSuccess('privacy');
        } else {
            showError('privacy', 'Devi accettare l\'informativa privacy');
        }
    });
    $('#help-form').on('submit', function(e) {
        let missing = [];
        const nome = $('#nome').val().trim();
        if (nome.length === 0) {
            showError('nome', 'Il nome è obbligatorio');
            missing.push('Il nome è obbligatorio e deve contenere almeno 2 caratteri.');
        } else if (nome.length < 2) {
            showError('nome', 'Il nome deve contenere almeno 2 caratteri');
            missing.push('Il nome è obbligatorio e deve contenere almeno 2 caratteri.');
        }
        const cognome = $('#cognome').val().trim();
        if (cognome.length === 0) {
            showError('cognome', 'Il cognome è obbligatorio');
            missing.push('Il cognome è obbligatorio e deve contenere almeno 2 caratteri.');
        } else if (cognome.length < 2) {
            showError('cognome', 'Il cognome deve contenere almeno 2 caratteri');
            missing.push('Il cognome è obbligatorio e deve contenere almeno 2 caratteri.');
        }
        const email = $('#email').val().trim();
        if (email.length === 0) {
            showError('email', 'L\'email è obbligatoria');
            missing.push('L\'email è obbligatoria.');
        } else if (email.indexOf('@') === -1) {
            showError('email', 'L\'email deve contenere il simbolo @');
            missing.push('L\'email deve contenere il simbolo @.');
        }
        const prefisso = $('#prefisso').val().trim();
        if (prefisso.length === 0) {
            showError('prefisso', 'Il prefisso è obbligatorio');
            missing.push('Prefisso dovrebbe contenere al massimo 3 caratteri.');
        } else if (prefisso.length > 3) {
            showError('prefisso', 'Prefisso dovrebbe contenere al massimo 3 caratteri');
            missing.push('Prefisso dovrebbe contenere al massimo 3 caratteri.');
        } else if (prefisso.length > 0 && prefisso.length <= 3) {
            showSuccess('prefisso');
        }
        const tel = $('#telefono').val().trim();
        const telNumeric = tel.replace(/\D/g, '');
        if (tel.length === 0) {
            showError('telefono', 'Il numero di cellulare è obbligatorio');
            missing.push('Il numero di cellulare è obbligatorio e deve contenere 10 cifre.');
        } else if (telNumeric.length === 0) {
            showError('telefono', 'Il numero di telefono può contenere solo numeri');
            missing.push('Il numero di cellulare è obbligatorio e deve contenere 10 cifre.');
        } else if (telNumeric.length !== 10) {
            showError('telefono', 'Inserisci un numero di 10 cifre');
            missing.push('Il numero di cellulare è obbligatorio e deve contenere 10 cifre.');
        }
        const messaggio = $('#message').val().trim();
        if (messaggio.length === 0) {
            showError('message', 'Il messaggio è obbligatorio');
            missing.push('Inserisci il tuo messaggio.');
        } else if (messaggio.length > 5000) {
            showError('message', 'Il messaggio non può superare i 5000 caratteri');
            missing.push('Il messaggio non può superare i 5000 caratteri.');
        }
        if (!$('#privacy').is(':checked')) {
            showError('privacy', 'Devi accettare l\'informativa privacy');
            missing.push('Devi accettare l\'informativa privacy per continuare.');
        }
        if (missing.length > 0) {
            e.preventDefault();
            const items = missing.map(m => '<li>' + m + '</li>').join('');
            $('#help-errors').html('<ul class="list-unstyled mb-0">' + items + '</ul>').show();
            $('html, body').animate({ scrollTop: $('#help-errors').offset().top - 100 }, 500);
        }
    });
});
</script>
</div>
</body>
</html>
