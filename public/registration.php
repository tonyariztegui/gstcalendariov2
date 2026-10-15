<?php
if (!defined('REGISTRATION_LABEL_CODICE_PARTECIPAZIONE')) {
    define('REGISTRATION_LABEL_CODICE_PARTECIPAZIONE', 'Codice partecipazione');
}
if (!defined('REGISTRATION_ATTR_MIN')) {
    define('REGISTRATION_ATTR_MIN', ' min="');
}
if (!defined('REGISTRATION_ATTR_MAX')) {
    define('REGISTRATION_ATTR_MAX', ' max="');
}
if (!defined('REGISTRATION_ATTR_MAXLENGTH')) {
    define('REGISTRATION_ATTR_MAXLENGTH', ' maxlength="');
}
if (!defined('REGISTRATION_ATTR_ID_CUSTOM')) {
    define('REGISTRATION_ATTR_ID_CUSTOM', ' id="custom_');
}
if (!defined('REGISTRATION_ATTR_NAME_CUSTOM_FIELDS')) {
    define('REGISTRATION_ATTR_NAME_CUSTOM_FIELDS', ' name="custom_fields[');
}
// Avvia la sessione immediatamente - deve essere prima di qualsiasi output
header('Content-Type: text/html; charset=UTF-8');
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Se l'utente torna volontariamente su registration, invalida lo stato quiz corrente.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    if (isset($_SESSION['gst_quiz_pending_registration'])) {
        unset($_SESSION['gst_quiz_pending_registration']);
    }
    foreach (array_keys($_SESSION) as $session_key) {
        if (strpos((string) $session_key, 'quiz_') === 0) {
            unset($_SESSION[$session_key]);
        }
    }
}

// Includi la configurazione centralizzata
require_once __DIR__ . '/../app/inc/config.php';
require_once __DIR__ . '/../app/inc/functions.php';
$now = time();

// Non Publicato : ?preview=1
if (!empty($_GET['preview'])) {
    if (!empty($contest_config['published'])) {
        // Publicato = Sì : redirige vers la page réelle sans preview
        header('Location: registrazione');
        exit;
    }
    $_SESSION['contest_preview'] = true;
}
$is_preview = !empty($_SESSION['contest_preview'] ?? null);
// In produzione pubblicata non usare una vecchia sessione preview
if (!empty($contest_config['published']) && $is_preview) {
    unset($_SESSION['contest_preview']);
    $is_preview = false;
}
if (empty($contest_config['published']) && !$is_preview) {
    header('Location: benvenuto');
    exit;
}

// Dati form da ripristinare dopo errore validazione (servono anche allo script Select2 in <head>)
$form_data = $_SESSION['form_data'] ?? [];
if (!is_array($form_data)) {
    $form_data = [];
}
$field_errors = $_SESSION['field_errors'] ?? [];
if (!is_array($field_errors)) {
    $field_errors = [];
}

// Stile dei campi del form (label sopra vs placeholder dentro)
$form_field_style = strtolower((string) ($contest_config['form_field_style'] ?? 'label_top'));
if (!in_array($form_field_style, ['label_top', 'placeholder'], true)) {
    $form_field_style = 'label_top';
}

// Mappa dei placeholder per lo stile "placeholder dentro al campo".
// La chiave e' il name (o id) dell'input; il valore e' il placeholder visualizzato.
// Tutto verra' visualizzato in maiuscolo dal CSS (.form-control::placeholder).
// "*" = campo obbligatorio (come nelle label), anche sui select Select2.
$codicePhLabel = trim((string) ($contest_config['instantwin_codice_label'] ?? REGISTRATION_LABEL_CODICE_PARTECIPAZIONE));
$codicePhLabel = rtrim($codicePhLabel, " *\t");
if (!defined('SUMMERCOOP_IMPORTO_PH_MAP')) {
    define('SUMMERCOOP_IMPORTO_PH_MAP', 'Importo totale (es. 25,52 / 25 52)*');
    define('SUMMERCOOP_IMPORTO_PH_LABEL', 'es. 25,52');
    define('SUMMERCOOP_IMPORTO_PH_TITLE', 'Esempi: 25,52 oppure 25 52 oppure 25.45 (decimali inclusi)');
}
$placeholder_map = [
    'nome' => 'Nome*',
    'cognome' => 'Cognome*',
    'email' => 'E-mail*',
    'prefisso' => '+39*',
    'cellulare' => 'Telefono*',
    'fidelity' => 'Numero fidelity card*',
    'cap' => 'CAP*',
    'provincia' => 'Provincia',
    'citta' => 'Città',
    'indirizzo' => 'Indirizzo',
    'regione_pdv' => 'Regione PDV',
    'prov_pdv' => 'Provincia PDV*',
    'citta_pdv' => 'Città PDV*',
    'indirizzo_pdv' => 'Punto vendita di acquisto*',
    'importo' => SUMMERCOOP_IMPORTO_PH_MAP,
    'data_scontrino' => 'Data scontrino (GG/MM/AAAA)*',
    'codice_partecipazione' => $codicePhLabel . '*',
];
$placeholder_required_flags = $contest_config['fields_required'] ?? [];
if (is_array($placeholder_required_flags)) {
    foreach ($placeholder_map as $placeholder_key => $placeholder_value) {
        if (!array_key_exists($placeholder_key, $placeholder_required_flags)) {
            continue;
        }
        $placeholder_text = preg_replace('/\s*\*+\s*$/', '', (string) $placeholder_value);
        $is_required = filter_var($placeholder_required_flags[$placeholder_key], FILTER_VALIDATE_BOOLEAN);
        $placeholder_map[$placeholder_key] = $is_required ? $placeholder_text . '*' : $placeholder_text;
    }
}
// Hook per personalizzare la mappa (es. tramite content.json -> placeholder_overrides)
$placeholder_overrides = $contest_config['placeholder_overrides'] ?? [];
if (is_array($placeholder_overrides)) {
    foreach ($placeholder_overrides as $k => $v) {
        $k = (string) $k;
        $v = (string) $v;
        if ($k !== '' && $v !== '') {
            $placeholder_map[$k] = $v;
        }
    }
}

// Configurazione pulsante primario dal content.json
$primary_button = $contest_config['buttons']['primary'] ?? [];
$palette_colors = $contest_config['colors'] ?? [];
$palette_primary = $palette_colors['primary'] ?? '#0d6efd';
$primary_image = $primary_button['image'] ?? '';
$primary_hover_image = $primary_button['hover_image'] ?? '';
$primary_bg = $primary_button['bg'] ?? $palette_primary;
$primary_text_color = $primary_button['text_color'] ?? '#ffffff';
$primary_border = $primary_button['border'] ?? $primary_bg;
$primary_hover_bg = $primary_button['hover_bg'] ?? ($primary_button['image'] ? $primary_bg : '#0b5ed7');
$primary_hover_border = $primary_button['hover_border'] ?? $primary_hover_bg;
$max_file_size_bytes = (int) ($contest_config['max_file_size'] ?? (5 * 1024 * 1024));
$max_file_size_mb = max(1, (int) ceil($max_file_size_bytes / (1024 * 1024)));
$receipt_file_type_options = [
    'jpg' => [
        'label' => 'JPG',
        'extensions' => ['jpg'],
        'mimes' => ['image/jpeg', 'image/pjpeg'],
    ],
    'png' => [
        'label' => 'PNG',
        'extensions' => ['png'],
        'mimes' => ['image/png'],
    ],
    'pdf' => [
        'label' => 'PDF',
        'extensions' => ['pdf'],
        'mimes' => ['application/pdf'],
    ],
];
$receipt_type_keys = array_keys($receipt_file_type_options);
$receipt_allowed_types = $contest_config['receipt_allowed_extensions'] ?? $receipt_type_keys;
$receipt_allowed_types = array_values(array_intersect($receipt_type_keys, array_map(static function ($val) {
    return strtolower((string) $val);
}, (array) $receipt_allowed_types)));
if (empty($receipt_allowed_types)) {
    $receipt_allowed_types = $receipt_type_keys;
}
$receipt_allowed_extensions = [];
$receipt_allowed_mimes = [];
$receipt_allowed_labels = [];
foreach ($receipt_allowed_types as $typeKey) {
    if (!isset($receipt_file_type_options[$typeKey])) {
        continue;
    }
    $typeInfo = $receipt_file_type_options[$typeKey];
    $receipt_allowed_labels[] = $typeInfo['label'];
    foreach ($typeInfo['extensions'] as $ext) {
        $receipt_allowed_extensions[] = $ext;
    }
    foreach ($typeInfo['mimes'] as $mime) {
        $receipt_allowed_mimes[] = $mime;
    }
}
$receipt_allowed_extensions = array_values($receipt_allowed_extensions);
$receipt_allowed_mimes = array_values(array_unique($receipt_allowed_mimes));
$format_receipt_label = static function (array $items): string {
    $items = array_values(array_filter($items, static function ($val) {
        return trim((string) $val) !== '';
    }));
    $items = array_map(static function ($val) {
        return strtoupper((string) $val);
    }, $items);
    $count = count($items);
    if ($count === 0) {
        $result = '';
    } elseif ($count === 1) {
        $result = $items[0];
    } elseif ($count === 2) {
        $result = $items[0] . ' o ' . $items[1];
    } else {
        $last = array_pop($items);
        $result = implode(', ', $items) . ' o ' . $last;
    }
    return $result;
};
$receipt_allowed_ext_label = $format_receipt_label(array_map(static function ($ext) {
    return strtoupper((string) $ext);
}, $receipt_allowed_extensions));
$receipt_allowed_label = $receipt_allowed_ext_label !== '' ? $receipt_allowed_ext_label : $format_receipt_label($receipt_allowed_labels);
$receipt_accept_attr = array_map(static function ($ext) {
    return '.' . $ext;
}, $receipt_allowed_extensions);
if (empty($receipt_accept_attr)) {
    $receipt_accept_attr = ['.jpg', '.png', '.pdf'];
}
if ($receipt_allowed_label === '') {
    $receipt_allowed_label = $receipt_allowed_ext_label !== '' ? $receipt_allowed_ext_label : 'JPG, PNG o PDF';
}

// In preview non bloccare l'accesso con le date del concorso
if (!$is_preview && $now < $date_debut) {
    // Includi la pagina di attesa
    include_once 'waiting_page.php';
    exit;
}

// Se siamo dopo la data di fine del concorso, mostra la pagina di concorso terminato
if (!$is_preview && $now > $date_fin) {
    // Includi la pagina di concorso terminato
    include_once 'waiting_page_ended.php';
    exit;
}

require_once __DIR__ . '/../app/logic/verify/qr_access.php';
qr_access_require();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione - <?php echo htmlspecialchars($contest_config['name']); ?></title>
    <?php echo get_favicon_tags(); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
    <?php
    $minimal_css_path = __DIR__ . '/assets/css/minimal.css';
    $custom_css_path = __DIR__ . '/assets/css/custom.css';
    $minimal_css_v = is_readable($minimal_css_path) ? filemtime($minimal_css_path) : time();
    $custom_css_v = is_readable($custom_css_path) ? filemtime($custom_css_path) : time();
    ?>
    <link rel="stylesheet" href="assets/css/minimal.css?v=<?php echo (int) $minimal_css_v; ?>">
    <link rel="stylesheet" href="assets/css/custom.css?v=<?php echo (int) $custom_css_v; ?>">
    <?php
    // I 3 colori dei campi del form sono validi in entrambe le modalita' (label_top e placeholder).
    $form_field_style_head = strtolower((string) ($contest_config['form_field_style'] ?? 'label_top'));
    if (!in_array($form_field_style_head, ['label_top', 'placeholder'], true)) {
        $form_field_style_head = 'label_top';
    }
    $formFieldResolved = resolve_form_field_colors($contest_config);
    $form_field_text_color = $formFieldResolved['text'];
    $form_field_border_color = $formFieldResolved['border'] !== ''
        ? $formFieldResolved['border']
        : 'var(--contest-border)';
    $form_field_bg_color = $formFieldResolved['bg'];
    $form_field_focus_color = $formFieldResolved['focus'];
    $form_field_select2_highlight_bg = $formFieldResolved['select2_highlight_bg'];
    $form_field_select2_highlight_text = $formFieldResolved['select2_highlight_text'];
    $form_field_calendar_popup_bg = $formFieldResolved['calendar_popup_bg'];
    $form_field_calendar_selected = $formFieldResolved['calendar_selected'];
    $form_field_calendar_hover_bg = $formFieldResolved['calendar_hover_bg'];
    $form_field_calendar_hover_text = $formFieldResolved['calendar_hover_text'];
    $form_field_checkbox_color = $formFieldResolved['checkbox'];
    $form_field_border_radius = max(0, min(40, (int) ($contest_config['form_field_border_radius'] ?? 6)));
    ?>
    <style>
    /* === COLORI DEI CAMPI DEL FORM (testo / bordo / sfondo) =================================
       Validi in entrambe le modalita' (Etichette sopra e Placeholder dentro). Configurabili
       dal manager > Concorso > Form & campi > Stile dei campi.
       =========================================================================================*/
    body {
        --form-field-text: <?php echo htmlspecialchars($form_field_text_color, ENT_QUOTES, 'UTF-8'); ?>;
        --form-field-border: <?php echo htmlspecialchars($form_field_border_color, ENT_QUOTES, 'UTF-8'); ?>;
        --form-field-bg: <?php echo htmlspecialchars($form_field_bg_color, ENT_QUOTES, 'UTF-8'); ?>;
        --form-field-focus: <?php echo htmlspecialchars($form_field_focus_color, ENT_QUOTES, 'UTF-8'); ?>;
        --form-field-radius: <?php echo (int) $form_field_border_radius; ?>px;
        --form-field-select2-highlight-bg: <?php echo htmlspecialchars($form_field_select2_highlight_bg, ENT_QUOTES, 'UTF-8'); ?>;
        --form-field-select2-highlight-text: <?php echo htmlspecialchars($form_field_select2_highlight_text, ENT_QUOTES, 'UTF-8'); ?>;
        --form-field-calendar-popup-bg: <?php echo htmlspecialchars($form_field_calendar_popup_bg, ENT_QUOTES, 'UTF-8'); ?>;
        --form-field-calendar-selected: <?php echo htmlspecialchars($form_field_calendar_selected, ENT_QUOTES, 'UTF-8'); ?>;
        --form-field-calendar-hover-bg: <?php echo htmlspecialchars($form_field_calendar_hover_bg, ENT_QUOTES, 'UTF-8'); ?>;
        --form-field-calendar-hover-text: <?php echo htmlspecialchars($form_field_calendar_hover_text, ENT_QUOTES, 'UTF-8'); ?>;
        --form-check-accent: <?php echo htmlspecialchars($form_field_checkbox_color, ENT_QUOTES, 'UTF-8'); ?>;
    }
    /* === FIX AUTOFILL (Chrome/Edge/Safari/Firefox) =============================================
       Quando il browser auto-completa un input, sovrascrive il colore del testo (e il fondo) con
       i suoi stili interni (-webkit-text-fill-color, ombra interna gialla, ecc.). Forziamo i nostri
       colori in modo che i valori auto-compilati abbiano lo stesso aspetto di quelli digitati. */
    #registration-form input:-webkit-autofill,
    #registration-form input:-webkit-autofill:hover,
    #registration-form input:-webkit-autofill:active,
    #registration-form select:-webkit-autofill,
    #registration-form textarea:-webkit-autofill {
        -webkit-text-fill-color: var(--form-field-text, #000000) !important;
        -webkit-box-shadow: 0 0 0 1000px var(--form-field-bg, #ffffff) inset !important;
        box-shadow: 0 0 0 1000px var(--form-field-bg, #ffffff) inset !important;
        caret-color: var(--form-field-text, #000000) !important;
        transition: background-color 5000s ease-in-out 0s;
    }
    #registration-form input:-webkit-autofill:focus,
    #registration-form input:-webkit-autofill:focus-visible,
    #registration-form select:-webkit-autofill:focus,
    #registration-form textarea:-webkit-autofill:focus {
        border-color: var(--form-field-focus) !important;
        -webkit-text-fill-color: var(--form-field-text, #000000) !important;
        -webkit-box-shadow: 0 0 0 1000px var(--form-field-bg, #ffffff) inset, 0 0 0 0.2rem color-mix(in srgb, var(--form-field-focus) 25%, transparent) !important;
        box-shadow: 0 0 0 1000px var(--form-field-bg, #ffffff) inset, 0 0 0 0.2rem color-mix(in srgb, var(--form-field-focus) 25%, transparent) !important;
        caret-color: var(--form-field-text, #000000) !important;
    }
    #registration-form .form-control:focus,
    #registration-form .form-control:focus-visible,
    #registration-form .form-select:focus,
    #registration-form .form-select:focus-visible,
    #registration-form .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: var(--form-field-focus) !important;
        box-shadow: 0 0 0 0.2rem color-mix(in srgb, var(--form-field-focus) 25%, transparent) !important;
    }
    /* Forziamo anche il -webkit-text-fill-color sui valori normalmente digitati,
       perche' su WebKit il color non e' sempre rispettato sugli input elements con valore. */
    #registration-form .form-control,
    #registration-form .form-select,
    #registration-form input[type="file"].form-control,
    #registration-form input[type="date"].form-control,
    #registration-form .flatpickr-input.form-control {
        -webkit-text-fill-color: var(--form-field-text, #000000);
        caret-color: var(--form-field-text, #000000);
        accent-color: var(--form-field-focus);
    }
    /* Stile pulsante: usa direttamente i colori del tema/manager */
    #submitBtn {
        background-image: none !important;
    }
    /* === MODALITA' CLASSICA (Etichette sopra) ===
       Applichiamo i 3 colori configurabili a tutti gli input/select/select2/file/date input.
       Cosi' anche in modalita' classica si possono personalizzare testo, bordo e sfondo. */
    body:not(.form-style-placeholder) #registration-form .form-control,
    body:not(.form-style-placeholder) #registration-form .form-select,
    body:not(.form-style-placeholder) #registration-form .select2-container--default .select2-selection--single,
    body:not(.form-style-placeholder) #registration-form input[type="file"].form-control,
    body:not(.form-style-placeholder) #registration-form input[type="date"].form-control,
    body:not(.form-style-placeholder) #registration-form .flatpickr-input.form-control {
        background-color: var(--form-field-bg, #ffffff) !important;
        border-color: var(--form-field-border, #dee2e6) !important;
        border-radius: var(--form-field-radius, 6px) !important;
    }
    body:not(.form-style-placeholder) #registration-form .form-control,
    body:not(.form-style-placeholder) #registration-form .form-select,
    body:not(.form-style-placeholder) #registration-form .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: var(--form-field-text, #000000) !important;
        -webkit-text-fill-color: var(--form-field-text, #000000) !important;
    }
    body:not(.form-style-placeholder) #registration-form .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: var(--form-field-placeholder, color-mix(in srgb, var(--form-field-text, #000000) 50%, #ffffff)) !important;
        -webkit-text-fill-color: var(--form-field-placeholder, color-mix(in srgb, var(--form-field-text, #000000) 50%, #ffffff)) !important;
    }
    body:not(.form-style-placeholder) #registration-form .form-control::placeholder,
    body:not(.form-style-placeholder) #registration-form .form-control::-webkit-input-placeholder,
    body:not(.form-style-placeholder) #registration-form .form-control::-moz-placeholder,
    body:not(.form-style-placeholder) #registration-form .form-control:-ms-input-placeholder {
        color: var(--form-field-placeholder, color-mix(in srgb, var(--form-field-text, #000000) 50%, #ffffff)) !important;
        opacity: 1 !important;
    }
    body:not(.form-style-placeholder) #registration-form input[type="date"]::-webkit-datetime-edit,
    body:not(.form-style-placeholder) #registration-form input[type="date"]::-webkit-datetime-edit-text,
    body:not(.form-style-placeholder) #registration-form input[type="date"]::-webkit-datetime-edit-day-field,
    body:not(.form-style-placeholder) #registration-form input[type="date"]::-webkit-datetime-edit-month-field,
    body:not(.form-style-placeholder) #registration-form input[type="date"]::-webkit-datetime-edit-year-field,
    body:not(.form-style-placeholder) #registration-form input[type="file"].form-control {
        color: var(--form-field-text, #000000) !important;
        -webkit-text-fill-color: var(--form-field-text, #000000) !important;
        background-color: var(--form-field-bg, #ffffff) !important;
        padding: 0 0.75rem 0 0 !important;
    }
    body:not(.form-style-placeholder) #registration-form input[type="file"]::file-selector-button,
    body:not(.form-style-placeholder) #registration-form input[type="file"]::-webkit-file-upload-button {
        color: var(--form-field-text, #000000) !important;
        -webkit-text-fill-color: var(--form-field-text, #000000) !important;
        background-color: color-mix(in srgb, var(--form-field-border, #dee2e6) 45%, var(--form-field-bg, #ffffff)) !important;
        border: 0 !important;
        border-inline-end: 1px solid var(--form-field-border, #dee2e6) !important;
        border-radius: 0 !important;
        box-sizing: border-box !important;
        height: 100% !important;
        margin: 0 !important;
        padding: 0.375rem 0.75rem !important;
    }
    /* Select disabled (es. CITTA' prima della provincia): mantiene gli stessi colori */
    body:not(.form-style-placeholder) #registration-form .form-control:disabled,
    body:not(.form-style-placeholder) #registration-form .form-select:disabled,
    body:not(.form-style-placeholder) #registration-form select[disabled],
    body:not(.form-style-placeholder) #registration-form .select2-container--default.select2-container--disabled .select2-selection--single {
        background-color: var(--form-field-bg, #ffffff) !important;
        border-color: var(--form-field-border, #dee2e6) !important;
        color: var(--form-field-text, #000000) !important;
        opacity: 1 !important;
    }
    /* Testo aiuto campi (fidelity / scontrino / codice) + footer sotto PARTECIPA */
    .logo-stack .registration-hint-text {
        color: var(--registration-hint-color, var(--contest-text, #ffffff)) !important;
        -webkit-text-fill-color: var(--registration-hint-color, var(--contest-text, #ffffff)) !important;
        font-size: 0.7rem !important;
        line-height: 1.4 !important;
        font-weight: 400;
        letter-spacing: normal;
        text-transform: none;
    }
    #registration-form .registration-hint-text + .error-message {
        margin-top: 0.35rem;
    }
    .logo-stack .registration-button-footer-text {
        color: var(--registration-button-footer-color, var(--contest-text, #ffffff)) !important;
        -webkit-text-fill-color: var(--registration-button-footer-color, var(--contest-text, #ffffff)) !important;
        font-size: 0.7rem !important;
        line-height: 1.4 !important;
        font-weight: 400;
        letter-spacing: normal;
        text-transform: none;
    }
    </style>
    <?php if ($form_field_style_head === 'placeholder'): ?>
    <style>
    /* === STILE "PLACEHOLDER DENTRO" - inline per evitare problemi di cache ===
       Le 3 CSS vars (--form-field-text/border/bg) sono gia' definite a livello body sopra.
       Qui aggiungiamo solo le regole specifiche della modalita' placeholder. */
    body.form-style-placeholder #registration-form label.form-label,
    body.form-style-placeholder #registration-form label[for="nome"],
    body.form-style-placeholder #registration-form label[for="cognome"],
    body.form-style-placeholder #registration-form label[for="email"],
    body.form-style-placeholder #registration-form label[for="prefisso"],
    body.form-style-placeholder #registration-form label[for="cellulare"],
    body.form-style-placeholder #registration-form label[for="fidelity"],
    body.form-style-placeholder #registration-form label[for="provincia"],
    body.form-style-placeholder #registration-form label[for="citta"],
    body.form-style-placeholder #registration-form label[for="indirizzo"],
    body.form-style-placeholder #registration-form label[for="prov_pdv"],
    body.form-style-placeholder #registration-form label[for="citta_pdv"],
    body.form-style-placeholder #registration-form label[for="indirizzo_pdv"],
    body.form-style-placeholder #registration-form label[for="importo"],
    body.form-style-placeholder #registration-form label[for="data_scontrino"],
    body.form-style-placeholder #registration-form label[for="codice_partecipazione"],
    body.form-style-placeholder #registration-form label[for^="custom_"] {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: hidden !important;
    }
    /* I checkbox mantengono la label visibile; Foto scontrino nasconde la label esterna */
    body.form-style-placeholder #registration-form .form-check label.form-check-label {
        display: inline-block !important;
        visibility: visible !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
    }
    body.form-style-placeholder #registration-form label[for="scontrino"] {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: hidden !important;
    }
    #registration-form .registration-file-input {
        position: relative;
        display: block;
        width: 100%;
        max-width: 100%;
    }
    /* Etichette sopra: texte natif browser (Nessun file / nom fichier), pas d'overlay */
    body:not(.form-style-placeholder) #registration-form .registration-file-status {
        display: none !important;
    }
    /* Placeholder dentro: masquer le texte natif, afficher le status custom */
    body.form-style-placeholder #registration-form .registration-file-input input[type="file"].form-control {
        color: transparent !important;
        -webkit-text-fill-color: transparent !important;
    }
    body.form-style-placeholder #registration-form .registration-file-status {
        display: block;
        position: absolute;
        left: 7.5rem;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: var(--form-field-font-size, 0.85rem);
        font-weight: 700;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        opacity: var(--form-placeholder-opacity, 0.72);
        color: var(--form-field-placeholder, color-mix(in srgb, var(--form-field-text, #000000) 55%, #ffffff));
        -webkit-text-fill-color: var(--form-field-placeholder, color-mix(in srgb, var(--form-field-text, #000000) 55%, #ffffff));
    }
    body.form-style-placeholder #registration-form .registration-file-status.is-filled {
        font-weight: 700;
        letter-spacing: 0.3px;
        text-transform: none;
        opacity: 1;
        color: var(--form-field-text, #000000);
        -webkit-text-fill-color: var(--form-field-text, #000000);
    }
    /* === BORDO E SFONDO DEI CAMPI =====================================================
       Usa --form-field-border e --form-field-bg impostate dal manager (Stile dei campi).
       Si applicano a tutti gli input elements / select elements / select2 / file / date input.
       =================================================================================== */
    body.form-style-placeholder #registration-form .form-control,
    body.form-style-placeholder #registration-form .form-select,
    body.form-style-placeholder #registration-form input[type="file"].form-control,
    body.form-style-placeholder #registration-form input[type="date"].form-control,
    body.form-style-placeholder #registration-form .flatpickr-input.form-control,
    body.form-style-placeholder #registration-form .select2-container--default .select2-selection--single {
        background-color: var(--form-field-bg, #ffffff) !important;
        border-color: var(--form-field-border, #dee2e6) !important;
        border-radius: var(--form-field-radius, 6px) !important;
    }
    /* Stile coerente per i select disabled (es. citta prima che provincia sia scelta).
       Manteniamo gli stessi colori bordo/sfondo (no grigio/alert nativo del browser) per
       uniformita' visiva con gli altri campi del form. */
    body.form-style-placeholder #registration-form .form-control:disabled,
    body.form-style-placeholder #registration-form .form-control[readonly],
    body.form-style-placeholder #registration-form .form-select:disabled,
    body.form-style-placeholder #registration-form select.form-select[disabled],
    body.form-style-placeholder #registration-form .form-select:disabled:focus,
    body.form-style-placeholder #registration-form select[disabled],
    body.form-style-placeholder #registration-form input[disabled] {
        background-color: var(--form-field-bg, #ffffff) !important;
        color: var(--form-field-text, #000000) !important;
        -webkit-text-fill-color: var(--form-field-text, #000000) !important;
        opacity: 1 !important;
        cursor: not-allowed;
        border-color: var(--form-field-border, #dee2e6) !important;
    }
    /* Citta disabilitata (vuota): stesso grigio placeholder, non testo pieno */
    body.form-style-placeholder #registration-form .form-select:disabled:has(option[value=""]:checked),
    body.form-style-placeholder #registration-form select.form-select[disabled]:has(option[value=""]:checked) {
        opacity: var(--form-placeholder-opacity) !important;
    }
    /* Anche i select2 quando wrappano un select disabled */
    body.form-style-placeholder #registration-form .select2-container--disabled .select2-selection--single,
    body.form-style-placeholder #registration-form .select2-container--default.select2-container--disabled .select2-selection--single {
        background-color: var(--form-field-bg, #ffffff) !important;
        opacity: 1 !important;
        cursor: not-allowed;
        border-color: var(--form-field-border, #dee2e6) !important;
    }
    body.form-style-placeholder #registration-form .select2-container--disabled .select2-selection--single .select2-selection__placeholder {
        opacity: var(--form-placeholder-opacity) !important;
        color: var(--form-field-text, #000000) !important;
    }
    /* I select nativi (provincia/citta) devono avere lo stesso aspetto degli altri input */
    body.form-style-placeholder #registration-form select.form-select {
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.3px;
        font-size: var(--form-field-font-size, 0.95rem);
    }
    /* Le option dentro al dropdown del select nativo devono pero' essere leggibili (no uppercase forzato) */
    body.form-style-placeholder #registration-form select.form-select option {
        text-transform: none;
        letter-spacing: normal;
        font-weight: 500;
        color: var(--form-field-text, #000000);
    }
    /* Date input: quando in placeholder mode e con type=text via JS, mantieni dimensione coerente */
    body.form-style-placeholder #registration-form input[data-was-date="1"] {
        font-family: inherit;
    }
    /* === COLORE DEL TESTO DEI CAMPI ===================================================
       Usa la variabile --form-field-text impostata dal manager (Stile dei campi ->
       Colore testo dei campi). Default = #000000 (nero), sempre leggibile su
       sfondo input bianco.
       =================================================================================== */
    /* Placeholder uniforme (provincia, citta, indirizzo, punto vendita): stesso colore e opacita'. */
    body.form-style-placeholder #registration-form {
        --form-placeholder-opacity: 0.72;
    }
    body.form-style-placeholder #registration-form .select2-container--default .select2-selection--single .select2-selection__rendered,
    body.form-style-placeholder #registration-form .select2-container--default.select2-container--disabled .select2-selection--single .select2-selection__rendered {
        font-family: inherit !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.3px !important;
        opacity: 1 !important;
        color: var(--form-field-text, #000000) !important;
        -webkit-text-fill-color: var(--form-field-text, #000000) !important;
        font-size: var(--form-field-font-size, 0.85rem) !important;
    }
    body.form-style-placeholder #registration-form .select2-container--default .select2-selection--single .select2-selection__placeholder,
    body.form-style-placeholder #registration-form .select2-container--default.select2-container--disabled .select2-selection--single .select2-selection__placeholder {
        font-family: inherit !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.3px !important;
        color: var(--form-field-placeholder, var(--form-field-text, #000000)) !important;
        -webkit-text-fill-color: var(--form-field-placeholder, var(--form-field-text, #000000)) !important;
        font-size: var(--form-field-font-size, 0.85rem) !important;
        opacity: var(--form-placeholder-opacity) !important;
    }
    body.form-style-placeholder #registration-form .form-control::placeholder,
    body.form-style-placeholder #registration-form .form-control::-webkit-input-placeholder,
    body.form-style-placeholder #registration-form .form-control::-moz-placeholder,
    body.form-style-placeholder #registration-form .form-control:-ms-input-placeholder {
        color: var(--form-field-placeholder, var(--form-field-text, #000000)) !important;
        -webkit-text-fill-color: var(--form-field-placeholder, var(--form-field-text, #000000)) !important;
        opacity: var(--form-placeholder-opacity) !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        font-size: var(--form-field-font-size, 0.85rem) !important;
        font-family: var(--form-field-font-family, inherit) !important;
    }
    /* Importo: placeholder piu' corto, senza maiuscole forzate, icona validazione nascosta (errore sotto) */
    body.form-style-placeholder #registration-form #importo::placeholder,
    body.form-style-placeholder #registration-form #importo::-webkit-input-placeholder,
    body.form-style-placeholder #registration-form #importo::-moz-placeholder {
        font-size: calc(var(--form-field-font-size, 0.85rem) * 0.8) !important;
        letter-spacing: 0 !important;
        text-transform: none !important;
    }
    body.form-style-placeholder #registration-form #importo.is-invalid {
        background-image: none !important;
        padding-right: 0.85rem !important;
    }
    /* Select nativi (prima del passaggio a Select2 o se disabilitati): prima option = placeholder */
    body.form-style-placeholder #registration-form select.form-select:has(option[value=""]:checked),
    body.form-style-placeholder #registration-form select.form-select:invalid {
        color: var(--form-field-text, #000000) !important;
        -webkit-text-fill-color: var(--form-field-text, #000000) !important;
        opacity: var(--form-placeholder-opacity) !important;
    }
    body.form-style-placeholder #registration-form select.form-select:not(:has(option[value=""]:checked)):not(:invalid) {
        color: var(--form-field-text, #000000) !important;
        -webkit-text-fill-color: var(--form-field-text, #000000) !important;
        opacity: 1 !important;
    }
    /* Fallback select nativo #citta (se Select2 non ancora attivo): fond blanc, pas de gris Bootstrap */
    body.form-style-placeholder #registration-form #citta.form-select,
    body.form-style-placeholder #registration-form #citta_pdv.form-select {
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        appearance: none !important;
        background-color: var(--form-field-bg, #ffffff) !important;
        background-image: none !important;
    }
    body.form-style-placeholder #registration-form .select2-container--default .select2-selection--single .select2-selection__arrow {
        position: absolute !important;
        top: 50% !important;
        right: 0.65rem !important;
        width: 20px !important;
        height: 20px !important;
        margin-top: 0 !important;
        transform: translateY(-50%) !important;
    }
    body.form-style-placeholder #registration-form .select2-container--default .select2-selection--single .select2-selection__arrow b {
        position: absolute !important;
        top: 50% !important;
        left: 50% !important;
        margin: 0 !important;
        transform: translate(-50%, -50%) !important;
    }
    body.form-style-placeholder #registration-form .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 1.4 !important;
        padding: 0 1.85rem 0 0 !important;
        margin: 0 !important;
    }
    body.form-style-placeholder #registration-form .select2-container--default .select2-selection--single .select2-selection__clear {
        position: absolute !important;
        top: 50% !important;
        right: 1.9rem !important;
        margin: 0 !important;
        line-height: 1 !important;
        transform: translateY(-50%) !important;
    }
    /* Il prefisso (+39) e' un VALORE dell'input. Stesso look degli altri campi. */
    body.form-style-placeholder #registration-form #prefisso {
        text-align: center;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        font-size: var(--form-field-font-size, 0.95rem);
        color: var(--form-field-text, #000000);
    }
    /* Tutti gli input/select - valore digitato e select nativi (anche disabled come citta) */
    body.form-style-placeholder #registration-form .form-control,
    body.form-style-placeholder #registration-form .form-select,
    body.form-style-placeholder #registration-form .form-control:disabled,
    body.form-style-placeholder #registration-form .form-select:disabled,
    body.form-style-placeholder #registration-form select[disabled] {
        font-weight: 700 !important;
        letter-spacing: 0.3px !important;
        color: var(--form-field-text, #000000) !important;
        -webkit-text-fill-color: var(--form-field-text, #000000) !important;
    }
    /* File input: fond distinct + même hauteur/largeur que les autres champs */
    body.form-style-placeholder #registration-form .registration-file-input {
        width: 100% !important;
        max-width: 100% !important;
    }
    body.form-style-placeholder #registration-form input[type="file"].form-control {
        display: block !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
        padding: 0 0.85rem 0 0 !important;
        min-height: 42px !important;
        height: 42px !important;
        font-size: var(--form-field-font-size, 0.95rem) !important;
        line-height: 1.4 !important;
        border-radius: var(--form-field-radius, 6px) !important;
    }
    body.form-style-placeholder #registration-form input[type="file"].form-control::file-selector-button,
    body.form-style-placeholder #registration-form input[type="file"].form-control::-webkit-file-upload-button {
        color: var(--form-field-text, #000000) !important;
        -webkit-text-fill-color: var(--form-field-text, #000000) !important;
        background-color: color-mix(in srgb, var(--form-field-border, #dee2e6) 45%, var(--form-field-bg, #ffffff)) !important;
        border: 0 !important;
        border-inline-end: 1px solid var(--form-field-border, #dee2e6) !important;
        border-radius: 0 !important;
        box-sizing: border-box !important;
        height: 42px !important;
        min-height: 42px !important;
        margin: 0 !important;
        padding: 0 0.85rem !important;
        line-height: 42px !important;
        font-weight: 700 !important;
        letter-spacing: 0.3px !important;
        text-transform: uppercase !important;
        font-size: var(--form-field-font-size, 0.85rem) !important;
    }
    /* Select2 dropdown (opzioni + ricerca): stessa dimensione dei campi */
    body.form-style-placeholder #registration-form .select2-results__option,
    body.form-style-placeholder #registration-form .select2-search--dropdown .select2-search__field {
        font-size: var(--form-field-font-size, 0.85rem) !important;
        font-family: var(--form-field-font-family, inherit) !important;
    }
    </style>
    <?php endif; ?>
    <?php echo get_background_style_block(); ?>
    <?php echo function_exists('get_custom_fonts_style_block') ? get_custom_fonts_style_block(false) : ''; ?>
    <?php echo get_logo_desktop_size_style_block(); ?>
    <?php echo get_brand_layout_style_block('registration'); ?>
    <?php echo get_theme_colors_block(); ?>
    <?php echo get_button_style_block(); ?>
    <?php echo get_form_container_style_block(); ?>
    <?php echo get_footer_style_block(); ?>

    <!-- Select2 CSS da CDN -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" integrity="sha384-OXVF05DQEe311p6ohU11NwlnX08FzMCsyoXzGOaL+83dKAb3qS17yZJxESl8YrJQ" crossorigin="anonymous" />
    <!-- Flatpickr CSS da CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css" integrity="sha384-RkASv+6KfBMW9eknReJIJ6b3UnjKOKC5bOUaNgIY778NFbQ8MtWq9Lr/khUgqtTt" crossorigin="anonymous" />
    <?php echo get_flatpickr_theme_style_block($contest_config); ?>
    <style>
    /* Select2 registration: centratura verticale (sovrascrive CDN Select2 caricato sopra) */
    #registration-form .select2-container--default .select2-selection--single {
        display: flex !important;
        align-items: center !important;
        min-height: calc(1.5em + 0.75rem + 2px) !important;
        height: auto !important;
        padding: 0.375rem 2rem 0.375rem 0.75rem !important;
        box-sizing: border-box !important;
    }
    body.form-style-placeholder #registration-form .select2-container--default .select2-selection--single {
        min-height: 42px !important;
        padding: 0.45rem 2rem 0.45rem 0.85rem !important;
    }
    #registration-form .select2-container--default .select2-selection--single .select2-selection__rendered,
    #registration-form .select2-container--default .select2-selection--single .select2-selection__placeholder {
        display: block !important;
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        line-height: 1.5 !important;
    }
    body.form-style-placeholder #registration-form .select2-container--default .select2-selection--single .select2-selection__rendered,
    body.form-style-placeholder #registration-form .select2-container--default .select2-selection--single .select2-selection__placeholder {
        line-height: 1.4 !important;
    }
    #registration-form .select2-container--default .select2-selection--single .select2-selection__arrow {
        position: absolute !important;
        top: 50% !important;
        right: 0.65rem !important;
        width: 20px !important;
        height: 20px !important;
        margin-top: 0 !important;
        transform: translateY(-50%) !important;
    }
    #registration-form .select2-container--default .select2-selection--single .select2-selection__arrow b {
        position: absolute !important;
        top: 50% !important;
        left: 50% !important;
        margin: 0 !important;
        transform: translate(-50%, -50%) !important;
    }
    #registration-form .select2-container--default .select2-selection--single .select2-selection__clear {
        top: 50% !important;
        transform: translateY(-50%) !important;
    }
    </style>

    <script src="assets/js/jquery-3.5.1.min.js"></script>
    <script>
    window.MIN_SPEND = <?php echo json_encode((float) ($contest_config['min_spend'] ?? 0)); ?>;
    window.MAX_FILE_SIZE_BYTES = <?php echo (int) $max_file_size_bytes; ?>;
    window.MAX_FILE_SIZE_MB = <?php echo (int) $max_file_size_mb; ?>;
    window.RECEIPT_ALLOWED_EXTENSIONS = <?php echo json_encode($receipt_allowed_extensions); ?>;
    window.RECEIPT_ALLOWED_MIMES = <?php echo json_encode($receipt_allowed_mimes); ?>;
    window.RECEIPT_ALLOWED_LABEL = <?php echo json_encode($receipt_allowed_label); ?>;
    window.FIELDS_ENABLED = <?php echo json_encode($contest_config['fields_enabled'] ?? []); ?>;
    window.FIELDS_REQUIRED = <?php echo json_encode($contest_config['fields_required'] ?? []); ?>;
    window.FORM_FIELD_STYLE = <?php echo json_encode($form_field_style); ?>;
    window.FORM_FIELD_PLACEHOLDERS = <?php echo json_encode($placeholder_map, JSON_UNESCAPED_UNICODE); ?>;
    window.FORM_FIELD_CALENDAR_THEME = <?php echo json_encode([
        'popupBg' => $form_field_calendar_popup_bg,
        'selected' => $form_field_calendar_selected,
        'hoverBg' => $form_field_calendar_hover_bg,
        'hoverText' => $form_field_calendar_hover_text,
        'text' => $form_field_text_color,
        'border' => $form_field_border_color !== '' && $form_field_border_color !== 'var(--contest-border)'
            ? $form_field_border_color
            : '#dee2e6',
    ], JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <script src="assets/js/registration.js?v=<?php echo (int) (is_readable(__DIR__ . '/assets/js/registration.js') ? filemtime(__DIR__ . '/assets/js/registration.js') : time()); ?>"></script>

    <!-- Select2 JS da CDN -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" integrity="sha384-d3UHjPdzJkZuk5H3qKYMLRyWLAQBJbby2yr2Q58hXXtAGF8RSNO9jpLDlKKPv5v3" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/it.js" crossorigin="anonymous"></script>
    <!-- Flatpickr JS da CDN (fallback: input type=date nativo) -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js" integrity="sha384-5JqMv4L/Xa0hfvtF06qboNdhvuYXUku9ZrhZh3bSk8VXF0A/RuSLHpLsSV9Zqhl6" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/it.js" integrity="sha384-XmcRqh+m1SV0CoktEURItyTPYrm018EIiFIZnaIFP1lRvHRna9DduiTvO+TQzcTJ" crossorigin="anonymous"></script>
    <script>
    // Override globale per select2: quando lo stile dei campi e' "placeholder dentro",
    // sovrascrive il placeholder hardcoded di ogni .select2() con quello custom della mappa.
    (function ($) {
        if (!$ || !$.fn || !$.fn.select2) return;
        var original = $.fn.select2;
        $.fn.select2 = function (options) {
            var args = Array.prototype.slice.call(arguments);
            try {
                if (window.FORM_FIELD_STYLE === 'placeholder' && options && typeof options === 'object') {
                    var id = this.attr ? (this.attr('id') || '') : '';
                    var name = this.attr ? (this.attr('name') || '') : '';
                    var map = window.FORM_FIELD_PLACEHOLDERS || {};
                    var custom = (id && Object.prototype.hasOwnProperty.call(map, id) && map[id])
                        || (name && Object.prototype.hasOwnProperty.call(map, name) && map[name])
                        || '';
                    var text = String(custom || options.placeholder || '').trim();
                    var isRequired = this.prop ? !!this.prop('required') : false;
                    if (isRequired && text && !/\*\s*$/.test(text)) {
                        text += '*';
                    }
                    if (text) {
                        args[0] = $.extend({}, options, { placeholder: text });
                    }
                }
            } catch (e) { /* fallback al comportamento originale */ }
            return original.apply(this, args);
        };
    })(window.jQuery);
    </script>
    <!-- Aggiunta SweetAlert2 CDN nel <head> -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" integrity="sha384-dCW5imOdApH6OwpFau8cZNKjqVbJYnCA5q+8YsMYP3XwXKsV6Jfz1u6MZLnXaBsS" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" integrity="sha384-nLoOnA/BDh8A/jxqtckg4DumuCGOBYUnNJLZdQz/zfYNp3wcjGSoWTAzgko06G/2" crossorigin="anonymous"></script>

     <script>
     const CONTEST_START_DATE = '<?php echo addslashes($contest_dates['start_date'] ?? ''); ?>';
     const CONTEST_END_DATE = '<?php echo addslashes($contest_dates['end_date'] ?? ''); ?>';
     const TODAY_DATE = '<?php echo addslashes(date('Y-m-d')); ?>';
     window.CONTEST_START_DATE = CONTEST_START_DATE;
     window.CONTEST_END_DATE = CONTEST_END_DATE;
     window.TODAY_DATE = TODAY_DATE;
     window.PDV_MODE = '<?php echo addslashes(strtolower(trim((string)($contest_config['pdv_config']['mode'] ?? 'combinato')))); ?>';

     function parseIsoDate(value) {
         if (!value || typeof value !== 'string') {
             return null;
         }
         const parts = value.split('-');
         if (parts.length !== 3) {
             return null;
         }
         const year = parseInt(parts[0], 10);
         const month = parseInt(parts[1], 10) - 1;
         const day = parseInt(parts[2], 10);
         if (Number.isNaN(year) || Number.isNaN(month) || Number.isNaN(day)) {
             return null;
         }
         const dateObj = new Date(year, month, day);
         if (Number.isNaN(dateObj.getTime())) {
             return null;
         }
         return dateObj;
     }

     function formatDateItalian(dateObj) {
         if (!(dateObj instanceof Date) || Number.isNaN(dateObj.getTime())) {
             return '';
         }
         const day = String(dateObj.getDate()).padStart(2, '0');
         const month = String(dateObj.getMonth() + 1).padStart(2, '0');
         return day + '/' + month + '/' + dateObj.getFullYear();
     }

     const contestStartDateObj = parseIsoDate(CONTEST_START_DATE);
     if (contestStartDateObj) {
         contestStartDateObj.setHours(0, 0, 0, 0);
     }
     const contestEndDateObj = parseIsoDate(CONTEST_END_DATE);
     if (contestEndDateObj) {
         contestEndDateObj.setHours(23, 59, 59, 999);
     }

     function ensureWrapper($el) {
         if (!$el.parent().hasClass('select2-wrapper')) {
             $el.wrap('<div class="select2-wrapper"></div>');
         }
         return $el.parent();
     }

     function initResidenceCittaSelect2(provincia, preselectedCitta) {
         const cittaSelect = $('#citta');
         if (!cittaSelect.length) {
             return;
         }
         const provinciaEnabled = !!(window.FIELDS_ENABLED && window.FIELDS_ENABLED.provincia);
         const hasProv = provinciaEnabled && !!(provincia && String(provincia).trim() !== '');
         const allCitiesMode = !provinciaEnabled;
         const map = window.FORM_FIELD_PLACEHOLDERS || {};
         const placeholderText = (map.citta && String(map.citta).trim() !== '')
             ? String(map.citta).trim()
             : (hasProv || allCitiesMode ? 'Seleziona città' : 'Città');

         if (cittaSelect.hasClass('select2-hidden-accessible')) {
             cittaSelect.select2('destroy');
         }

         cittaSelect.prop('disabled', provinciaEnabled && !hasProv);
         cittaSelect.empty();
         cittaSelect.append('<option value=""></option>');

         const opts = {
             placeholder: placeholderText,
             allowClear: hasProv || allCitiesMode,
             width: '100%',
             language: 'it',
             dropdownParent: ensureWrapper(cittaSelect)
         };

         if (hasProv || allCitiesMode) {
             opts.minimumInputLength = allCitiesMode ? 1 : 0;
             opts.ajax = {
                 url: 'ajax/get_cities.php',
                 dataType: 'json',
                 delay: 250,
                 data: function(params) {
                     const payload = {
                         action: 'cities',
                         q: params.term || ''
                     };
                     if (hasProv) {
                         payload.provincia = provincia;
                     }
                     return payload;
                 },
                 processResults: function(data) {
                     if (data.success && data.data) {
                         return {
                             results: data.data.map(function(citta) {
                                 return { id: citta, text: citta };
                             })
                         };
                     }
                     return { results: [] };
                 },
                 cache: true
             };
         }

         cittaSelect.select2(opts);

         if ((hasProv || allCitiesMode) && preselectedCitta && String(preselectedCitta).trim() !== '') {
             const city = String(preselectedCitta).trim();
             const opt = new Option(city, city, true, true);
             cittaSelect.append(opt).trigger('change');
         }
     }

     $(document).ready(function() {
        const pdvSelect = window.PDV_MODE === 'cascata' ? $('#indirizzo_pdv') : $('#indirizzo_pdv_combinato');
        const pdvWrapper = ensureWrapper(pdvSelect);

        if (window.PDV_MODE === 'cascata' && pdvSelect.length) {
            const regioneSelect = $('#regione_pdv');
            const provSelect = $('#prov_pdv');
            const cittaSelect = $('#citta_pdv');
            function hasPdvArea() {
                return !!(provSelect.val() || regioneSelect.val());
            }
            function resetCascataAfterAreaChange() {
                const hasArea = hasPdvArea();
                cittaSelect.val('').prop('disabled', !hasArea);
                pdvSelect.val('').prop('disabled', true);
                if (cittaSelect.data('select2')) {
                    cittaSelect.trigger('change.select2');
                }
                if (pdvSelect.data('select2')) {
                    pdvSelect.trigger('change');
                }
            }
            function setSelect2Value($select, value) {
                if (!$select.length || !value) return;
                const textValue = String(value);
                const exists = $select.find('option').filter(function() {
                    return $(this).val() === textValue;
                }).length > 0;
                if (!exists) {
                    $select.append(new Option(textValue, textValue, true, true));
                }
                $select.val(textValue).trigger('change.select2');
            }
            function syncCascataPdvData(data) {
                if (!data) return;
                setSelect2Value(provSelect, data.prov || '');
                setSelect2Value(cittaSelect, data.citta || '');
                setSelect2Value(regioneSelect, data.regione || '');
                cittaSelect.prop('disabled', !hasPdvArea());
                pdvSelect.prop('disabled', !hasPdvArea() || !cittaSelect.val());
            }
            if (regioneSelect.length && !regioneSelect.hasClass('d-none')) {
                regioneSelect.select2({
                    placeholder: 'Seleziona regione',
                    allowClear: true,
                    width: '100%',
                    language: 'it',
                    minimumInputLength: 0,
                    dropdownParent: ensureWrapper(regioneSelect),
                    ajax: {
                        url: 'cerca-pdv',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return { action: 'regione', q: params.term || '', selected: regioneSelect.val() || '' };
                        },
                        processResults: function(data) {
                            return { results: (data.results || []).map(function(item) {
                                return { id: item.id, text: item.text };
                            })};
                        },
                        cache: true
                    }
                });
                regioneSelect.on('select2:select select2:clear', function() {
                    if (regioneSelect.val()) {
                        provSelect.val('');
                        if (provSelect.data('select2')) provSelect.trigger('change.select2');
                    }
                    resetCascataAfterAreaChange();
                });
            }
            if (provSelect.length && !provSelect.hasClass('d-none')) {
                provSelect.select2({
                    placeholder: 'Seleziona provincia',
                    allowClear: true,
                    width: '100%',
                    language: 'it',
                    minimumInputLength: 0,
                    dropdownParent: ensureWrapper(provSelect),
                    ajax: {
                        url: 'cerca-pdv',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return { action: 'prov', q: params.term || '', selected: provSelect.val() || '' };
                        },
                        processResults: function(data) {
                            return { results: (data.results || []).map(function(item) {
                                return { id: item.id, text: item.text };
                            })};
                        },
                        cache: true
                    }
                });
                provSelect.on('select2:select select2:clear', function() {
                    if (provSelect.val()) {
                        regioneSelect.val('');
                        if (regioneSelect.data('select2')) regioneSelect.trigger('change.select2');
                    }
                    resetCascataAfterAreaChange();
                });
                if (!hasPdvArea() && cittaSelect.length) {
                    cittaSelect.prop('disabled', true);
                    if (cittaSelect.data('select2')) {
                        cittaSelect.trigger('change.select2');
                    }
                }
            }
            if (cittaSelect.length) {
                cittaSelect.select2({
                    placeholder: 'Seleziona città',
                    allowClear: true,
                    width: '100%',
                    language: 'it',
                    minimumInputLength: 0,
                    dropdownParent: ensureWrapper(cittaSelect),
                    ajax: {
                        url: 'cerca-pdv',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                action: 'citta',
                                regione: regioneSelect.val() || '',
                                prov: provSelect.val() || '',
                                q: params.term || '',
                                selected: cittaSelect.val() || ''
                            };
                        },
                        processResults: function(data) {
                            return { results: (data.results || []).map(function(item) {
                                return { id: item.id, text: item.text };
                            })};
                        },
                        cache: true
                    }
                });
                cittaSelect.on('select2:select select2:clear', function() {
                    pdvSelect.val('').prop('disabled', !hasPdvArea() || !cittaSelect.val());
                    if (pdvSelect.data('select2')) pdvSelect.trigger('change');
                });
            }
            if (pdvSelect.length) {
                pdvSelect.select2({
                    placeholder: 'Seleziona punto vendita',
                    allowClear: true,
                    width: '100%',
                    language: 'it',
                    minimumInputLength: 0,
                    dropdownParent: pdvWrapper,
                    ajax: {
                        url: 'cerca-pdv',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                action: 'indirizzo',
                                regione: regioneSelect.val() || '',
                                prov: provSelect.val() || '',
                                citta: cittaSelect.val() || '',
                                q: params.term || '',
                                selected: pdvSelect.val() || ''
                            };
                        },
                        processResults: function(data) {
                            return { results: (data.results || []).map(function(item) {
                                return {
                                    id: item.id,
                                    text: item.text,
                                    prov: item.prov,
                                    citta: item.citta,
                                    regione: item.regione
                                };
                            })};
                        },
                        cache: true
                    }
                });
                pdvSelect.on('select2:select', function(e) {
                    syncCascataPdvData(e.params.data);
                });
            }
            if (hasPdvArea()) {
                cittaSelect.prop('disabled', false);
                pdvSelect.prop('disabled', !cittaSelect.val());
            }
        } else if (pdvSelect.length) {
            function syncPdvHidden(data) {
                $('#prov_pdv_combinato').val(data && data.prov ? data.prov : '');
                $('#citta_pdv_combinato').val(data && data.citta ? data.citta : '');
            }
            pdvSelect.select2({
            placeholder: 'Seleziona punto vendita',
            allowClear: true,
            width: '100%',
            language: 'it',
            minimumInputLength: 0,
            dropdownParent: pdvWrapper,
            ajax: {
                url: 'cerca-pdv',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        action: 'pdv_combined',
                        q: params.term || '',
                        selected: pdvSelect.val() || ''
                    };
                },
                processResults: function(data) {
                    return {
                        results: (data.results || []).map(function(item) {
                            return {
                                id: item.id,
                                text: item.text,
                                prov: item.prov,
                                citta: item.citta,
                                indirizzo: item.indirizzo,
                                insegna: item.insegna,
                                ragsoc: item.ragsoc
                            };
                        })
                    };
                },
                cache: true
            }
        });

        pdvSelect.on('select2:select', function(e) {
            syncPdvHidden(e.params.data);
        });

        pdvSelect.on('select2:clear', function() {
            syncPdvHidden(null);
        });

            const preselectedOption = pdvSelect.find('option:selected');
            if (preselectedOption.length) {
                syncPdvHidden({
                    prov: preselectedOption.data('prov') || $('#prov_pdv_combinato').val(),
                    citta: preselectedOption.data('citta') || $('#citta_pdv_combinato').val()
                });
            }
        }

        const provinciaSelect = $('#provincia');
        const provinciaValue = <?php echo json_encode((string) ($form_data['provincia'] ?? ''), JSON_UNESCAPED_UNICODE); ?>;
        const provinciaEnabled = !!(window.FIELDS_ENABLED && window.FIELDS_ENABLED.provincia);
        const preselectedCitta = <?php echo json_encode((string) ($form_data['citta'] ?? ''), JSON_UNESCAPED_UNICODE); ?>;
        let suppressResidenceCittaReset = false;

        function restoreProvinciaSelection(value) {
            if (!provinciaSelect.length || !value) {
                return;
            }
            const safe = String(value);
            let $opt = provinciaSelect.find('option').filter(function() {
                return $(this).val() === safe;
            });
            if (!$opt.length) {
                $opt = $(new Option(safe, safe, true, true));
                provinciaSelect.append($opt);
            }
            provinciaSelect.val(safe).trigger('change.select2');
        }

        function bindProvinciaSelect2(extraOptions) {
            const base = {
                placeholder: 'Seleziona provincia',
                allowClear: true,
                width: '100%',
                language: 'it',
                dropdownParent: ensureWrapper(provinciaSelect),
                ajax: {
                    url: 'ajax/get_cities.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            action: 'province',
                            q: params.term || ''
                        };
                    },
                    processResults: function(data) {
                        if (data.success && data.data) {
                            const results = data.data.map(function(provincia) {
                                return {
                                    id: provincia,
                                    text: provincia
                                };
                            });
                            return { results: results };
                        }
                        return { results: [] };
                    },
                    cache: true
                }
            };
            provinciaSelect.select2($.extend(true, {}, base, extraOptions || {}));
        }

        if (provinciaSelect.length && provinciaValue) {
            $.ajax({
                url: 'ajax/get_cities.php',
                dataType: 'json',
                data: { action: 'province' },
                success: function(response) {
                    if (response.success && response.data) {
                        provinciaSelect.empty();
                        provinciaSelect.append('<option value="">Seleziona provincia</option>');
                        response.data.forEach(function(provincia) {
                            const selected = provincia === provinciaValue ? 'selected' : '';
                            provinciaSelect.append('<option value="' + provincia + '" ' + selected + '>' + provincia + '</option>');
                        });
                    }

                    bindProvinciaSelect2();
                    suppressResidenceCittaReset = true;
                    restoreProvinciaSelection(provinciaValue);
                    initResidenceCittaSelect2(provinciaValue, preselectedCitta);
                    suppressResidenceCittaReset = false;
                },
                error: function() {
                    bindProvinciaSelect2();
                    suppressResidenceCittaReset = true;
                    restoreProvinciaSelection(provinciaValue);
                    initResidenceCittaSelect2(provinciaValue, preselectedCitta);
                    suppressResidenceCittaReset = false;
                }
            });
        } else if (provinciaSelect.length) {
            bindProvinciaSelect2();
            initResidenceCittaSelect2(provinciaSelect.val(), preselectedCitta);
        } else if (!provinciaEnabled) {
            initResidenceCittaSelect2('', preselectedCitta);
        }

        $('#provincia').on('change', function() {
            if (suppressResidenceCittaReset) {
                return;
            }
            initResidenceCittaSelect2($(this).val());
        });


          setTimeout(function() {
              $('.select2-selection__rendered').removeAttr('title');
              $('.select2-results__option').removeAttr('title');
          }, 100);

              <?php if (!empty($field_errors)): ?>
              $(document).ready(function() {
                  <?php
                  $pdv_mode_for_errors = strtolower(trim((string) (($contest_config['pdv_config']['mode'] ?? 'combinato'))));
                  $pdv_mode_for_errors = in_array($pdv_mode_for_errors, ['combinato', 'cascata'], true) ? $pdv_mode_for_errors : 'combinato';
                  foreach ($field_errors as $field => $error):
                      $error_dom_id = $field;
                      if ($field === 'indirizzo_pdv' && $pdv_mode_for_errors !== 'cascata') {
                          $error_dom_id = 'indirizzo_pdv_combinato';
                      }
                  ?>
                  $('#<?php echo $error_dom_id; ?>-error').addClass('d-block').text('<?php echo addslashes($error); ?>').css('display', 'block');
                  $('#<?php echo $error_dom_id; ?>').addClass('is-invalid');
                  (function () {
                      var $f = $('#<?php echo $error_dom_id; ?>');
                      if ($f.length && $f.hasClass('select2-hidden-accessible')) {
                          $f.next('.select2-container').find('.select2-selection').addClass('is-invalid');
                      }
                  })();
                  <?php endforeach; ?>

                  setTimeout(function() {
                      var formElement = $('#registration-form');
                      if (formElement.length) {
                          var formOffset = formElement.offset().top;
                          var windowHeight = $(window).height();
                          var scrollPosition = formOffset - (windowHeight * 0.1);
                          $('html, body').animate({
                              scrollTop: scrollPosition
                          }, 500);
                      }
                  }, 100);
              });
              <?php endif; ?>


    });
    </script>

</head>
<?php $body_form_style_class = $form_field_style === 'placeholder' ? ' form-style-placeholder' : ''; ?>
<body class="<?php echo trim('form-field-style-' . htmlspecialchars($form_field_style, ENT_QUOTES, 'UTF-8') . $body_form_style_class); ?>">
<div class="d-flex flex-column min-vh-100 body-background-wrapper">
<?php
// form_data / field_errors già caricati in cima (ripristino dopo errore)
$fields_enabled = $contest_config['fields_enabled'] ?? [];
$fidelity_config = $contest_config['fidelity'] ?? ['type' => 'numeric', 'length' => 13];
$custom_fields = $contest_config['custom_fields'] ?? [];
if (!is_array($custom_fields)) {
    $custom_fields = [];
}
if (!function_exists('gst_migrate_custom_fields_position_orders')) {
    require_once __DIR__ . '/../app/inc/standard_fields_order.inc.php';
}
$custom_fields = gst_migrate_custom_fields_position_orders($custom_fields);
$contest_config['custom_fields'] = $custom_fields;
$custom_field_values = $form_data['custom_fields'] ?? [];
if (!is_array($custom_field_values)) {
    $custom_field_values = [];
}
$custom_field_errors = $field_errors['custom_fields'] ?? [];
if (!is_array($custom_field_errors)) {
    $custom_field_errors = [];
}

if (!function_exists('registrationAttrFragment')) {
    function registrationAttrFragment($prefix, $value) {
        return $value !== null && $value !== '' ? $prefix . htmlspecialchars((string) $value) . '"' : '';
    }
}

if (!function_exists('registrationGetCustomFieldMaxlengthAttr')) {
    function registrationGetCustomFieldMaxlengthAttr($maxLengthAttr, $customType) {
        if ($customType === 'text') {
            $textDefaultLimit = 5000;
            $textareaMaxLimit = 65535;
            if ($maxLengthAttr !== '' && (int) $maxLengthAttr > 0) {
                $configuredMaxLength = (int) $maxLengthAttr;
                $effectiveMaxLength = max($textDefaultLimit, min($configuredMaxLength, $textareaMaxLimit));
            } else {
                $effectiveMaxLength = $textDefaultLimit;
            }
            return registrationAttrFragment(REGISTRATION_ATTR_MAXLENGTH, $effectiveMaxLength);
        }
        return $maxLengthAttr !== '' ? registrationAttrFragment(REGISTRATION_ATTR_MAXLENGTH, $maxLengthAttr) : REGISTRATION_ATTR_MAXLENGTH . '5000"';
    }
}

if (!function_exists('registrationResolveCustomFieldType')) {
    function registrationResolveCustomFieldType($customField) {
        $typeMapping = [
            'text' => 'text',
            'textarea' => 'text',
            'number' => 'int',
            'email' => 'varchar',
            'date' => 'date',
            'int' => 'int',
            'varchar' => 'varchar',
            'boolean' => 'boolean',
            'bool' => 'boolean'
        ];
        $customType = strtolower(trim((string) ($customField['type'] ?? 'varchar')));
        $customType = $typeMapping[$customType] ?? 'varchar';
        if (!in_array($customType, ['int', 'varchar', 'text', 'date', 'boolean'], true)) {
            $customType = 'varchar';
        }
        return $customType;
    }
}

if (!function_exists('registrationResolveCustomFieldLengthAttrs')) {
    function registrationResolveCustomFieldLengthAttrs($customField, $customType) {
        $customExactLength = isset($customField['exact_length']) && $customField['exact_length'] !== null ? (int) $customField['exact_length'] : null;
        $customMinLength = isset($customField['min_length']) && $customField['min_length'] !== null ? (int) $customField['min_length'] : null;
        $customMaxLength = isset($customField['max_length']) && $customField['max_length'] !== null ? (int) $customField['max_length'] : null;
        if ($customExactLength !== null) {
            $maxLengthAttr = $customExactLength;
        } elseif ($customMaxLength !== null) {
            $maxLengthAttr = $customMaxLength;
        } else {
            $maxLengthAttr = '';
        }
        if ($customType === 'varchar' && $maxLengthAttr === '' && $customExactLength === null) {
            $maxLengthAttr = '255';
        }
        if ($customExactLength !== null) {
            $minLengthAttr = $customExactLength;
        } elseif ($customMinLength !== null) {
            $minLengthAttr = $customMinLength;
        } else {
            $minLengthAttr = '';
        }
        $dataExactLength = $customExactLength !== null ? ' data-exact-length="' . htmlspecialchars($customExactLength) . '"' : '';
        return [
            'customExactLength' => $customExactLength,
            'maxLengthAttr' => $maxLengthAttr,
            'minLengthAttr' => $minLengthAttr,
            'dataExactLength' => $dataExactLength,
        ];
    }
}

if (!function_exists('registrationApplyCustomFieldDateAttrs')) {
    function registrationApplyCustomFieldDateAttrs(array &$data, $customField) {
        $dateMin = isset($customField['date_min']) && trim($customField['date_min']) !== '' ? trim($customField['date_min']) : null;
        if (isset($customField['date_max_today']) && $customField['date_max_today']) {
            $dateMax = date('Y-m-d');
        } elseif (isset($customField['date_max']) && trim($customField['date_max']) !== '') {
            $dateMax = trim($customField['date_max']);
        } else {
            $dateMax = null;
        }
        $data['dateMinAttr'] = registrationAttrFragment(REGISTRATION_ATTR_MIN, $dateMin);
        $data['dateMaxAttr'] = registrationAttrFragment(REGISTRATION_ATTR_MAX, $dateMax);
        $data['htmlType'] = 'date';
    }
}

if (!function_exists('registrationApplyCustomFieldIntAttrs')) {
    function registrationApplyCustomFieldIntAttrs(array &$data, $customField) {
        $intMin = isset($customField['int_min']) && $customField['int_min'] !== '' && $customField['int_min'] !== null ? (int) $customField['int_min'] : null;
        $intMax = isset($customField['int_max']) && $customField['int_max'] !== '' && $customField['int_max'] !== null ? (int) $customField['int_max'] : null;
        $intExactDigits = isset($customField['int_exact_digits']) && $customField['int_exact_digits'] !== '' && $customField['int_exact_digits'] !== null ? (int) $customField['int_exact_digits'] : null;
        if ($intExactDigits !== null) {
            $intExactDigits = max(1, min(10, $intExactDigits));
        }
        $data['intDigitModeAttrs'] = '';
        // Mode codice numerico (CAP): N cifre esatte come stringa, non come INT32
        if ($intExactDigits !== null) {
            $n = (int) $intExactDigits;
            $data['htmlType'] = 'text';
            $data['intMinAttr'] = '';
            $data['intMaxAttr'] = '';
            $data['dataIntExactDigits'] = ' data-int-exact-digits="' . htmlspecialchars((string) $n) . '" data-custom-type="int"';
            $data['intDigitModeAttrs'] = ' inputmode="numeric" autocomplete="postal-code" spellcheck="false" pattern="[0-9]{' . $n . '}"'
                . registrationAttrFragment(REGISTRATION_ATTR_MAXLENGTH, (string) $n)
                . ' minlength="' . $n . '"';
            return;
        }
        $data['intMinAttr'] = registrationAttrFragment(REGISTRATION_ATTR_MIN, $intMin);
        $data['intMaxAttr'] = registrationAttrFragment(REGISTRATION_ATTR_MAX, $intMax);
        $data['dataIntExactDigits'] = ' data-custom-type="int"';
        if ($data['intMinAttr'] === '') {
            $data['intMinAttr'] = REGISTRATION_ATTR_MIN . '-2147483648"';
        }
        if ($data['intMaxAttr'] === '') {
            $data['intMaxAttr'] = REGISTRATION_ATTR_MAX . '2147483647"';
        }
        $data['htmlType'] = 'number';
        $data['intDigitModeAttrs'] = ' inputmode="numeric"';
    }
}

if (!function_exists('registrationApplyCustomFieldTypeAttrs')) {
    function registrationApplyCustomFieldTypeAttrs(array &$data, $customField) {
        $customType = $data['customType'];
        if ($customType === 'date') {
            registrationApplyCustomFieldDateAttrs($data, $customField);
        } elseif ($customType === 'int') {
            registrationApplyCustomFieldIntAttrs($data, $customField);
        } elseif ($customType === 'text') {
            $data['htmlType'] = 'textarea';
        } elseif ($customType === 'boolean') {
            $data['htmlType'] = 'checkbox';
        }
    }
}

if (!function_exists('registrationBuildCustomFieldRenderData')) {
    function registrationBuildCustomFieldRenderData($customField, $custom_field_values, $custom_field_errors) {
        if (!is_array($customField)) {
            return null;
        }

        $customId = preg_replace('/[^a-z0-9_-]/i', '', (string) ($customField['id'] ?? ''));
        $customLabel = trim((string) ($customField['label'] ?? ''));
        if ($customId === '' || $customLabel === '') {
            return null;
        }

        $customType = registrationResolveCustomFieldType($customField);
        $lengthAttrs = registrationResolveCustomFieldLengthAttrs($customField, $customType);
        $customRequired = !empty($customField['required']);

        $data = [
            'customId' => $customId,
            'customLabel' => $customLabel,
            'customType' => $customType,
            'customRequired' => $customRequired,
            'customValue' => $custom_field_values[$customId] ?? '',
            'customError' => $custom_field_errors[$customId] ?? '',
            'labelText' => $customLabel . ($customRequired ? ' *' : ''),
            'customExactLength' => $lengthAttrs['customExactLength'],
            'maxLengthAttr' => $lengthAttrs['maxLengthAttr'],
            'minLengthAttr' => $lengthAttrs['minLengthAttr'],
            'dataExactLength' => $lengthAttrs['dataExactLength'],
            'dataErrorMessage' => '',
            'dateMinAttr' => '',
            'dateMaxAttr' => '',
            'intMinAttr' => '',
            'intMaxAttr' => '',
            'dataIntExactDigits' => '',
            'intDigitModeAttrs' => '',
            'htmlType' => 'text'
        ];

        $customErrorMessage = isset($customField['error_message']) && trim($customField['error_message']) !== '' ? trim($customField['error_message']) : null;
        $data['dataErrorMessage'] = $customErrorMessage !== null ? ' data-error-message="' . htmlspecialchars($customErrorMessage) . '"' : '';

        registrationApplyCustomFieldTypeAttrs($data, $customField);

        return $data;
    }
}

if (!function_exists('registrationRenderCustomFieldInputHtml')) {
    function registrationRenderCustomFieldInputHtml($data) {
        $customType = $data['customType'];
        $maxLengthAttr = $data['maxLengthAttr'];
        $customExactLength = $data['customExactLength'];
        $intDigitModeAttrs = $data['intDigitModeAttrs'] ?? '';
        return '<input type="' . htmlspecialchars($data['htmlType']) . '" class="form-control ' . ($data['customError'] ? 'is-invalid' : '') . '"' . REGISTRATION_ATTR_ID_CUSTOM . htmlspecialchars($data['customId']) . '"' . REGISTRATION_ATTR_NAME_CUSTOM_FIELDS . htmlspecialchars($data['customId']) . ']" value="' . htmlspecialchars($data['customValue']) . '" data-field-label="' . htmlspecialchars($data['customLabel']) . '" ' . ($data['customRequired'] ? 'required' : '') . ($maxLengthAttr !== '' && $customType !== 'date' && $customType !== 'int' ? registrationAttrFragment(REGISTRATION_ATTR_MAXLENGTH, $maxLengthAttr) : '') . ($data['minLengthAttr'] !== '' && $customExactLength === null && $customType !== 'date' && $customType !== 'int' ? ' minlength="' . htmlspecialchars($data['minLengthAttr']) . '"' : '') . $data['dataExactLength'] . $data['dataErrorMessage'] . $data['dateMinAttr'] . $data['dateMaxAttr'] . $data['intMinAttr'] . $data['intMaxAttr'] . $data['dataIntExactDigits'] . $intDigitModeAttrs . '>';
    }
}

if (!function_exists('registrationRenderCustomFieldCheckboxHtml')) {
    function registrationRenderCustomFieldCheckboxHtml($data) {
        $checked = ($data['customValue'] === '1' || $data['customValue'] === 'on' || $data['customValue'] === true) ? 'checked' : '';
        return '<div class="form-check">'
            . '<input type="checkbox" class="form-check-input ' . ($data['customError'] ? 'is-invalid' : '') . '"' . REGISTRATION_ATTR_ID_CUSTOM . htmlspecialchars($data['customId']) . '"' . REGISTRATION_ATTR_NAME_CUSTOM_FIELDS . htmlspecialchars($data['customId']) . ']" value="1" ' . $checked . ' ' . ($data['customRequired'] ? 'required' : '') . '>'
            . '<label class="form-check-label" for="custom_' . htmlspecialchars($data['customId']) . '">'
            . htmlspecialchars($data['labelText'])
            . '</label></div>';
    }
}

if (!function_exists('registrationRenderCustomFieldTextareaHtml')) {
    function registrationRenderCustomFieldTextareaHtml($data, $includeFieldLabel = true) {
        $labelAttr = $includeFieldLabel ? ' data-field-label="' . htmlspecialchars($data['customLabel']) . '"' : '';
        return '<label for="custom_' . htmlspecialchars($data['customId']) . '" class="form-label">' . htmlspecialchars($data['labelText']) . '</label>'
            . '<textarea class="form-control ' . ($data['customError'] ? 'is-invalid' : '') . '"' . REGISTRATION_ATTR_ID_CUSTOM . htmlspecialchars($data['customId']) . '"' . REGISTRATION_ATTR_NAME_CUSTOM_FIELDS . htmlspecialchars($data['customId']) . ']" rows="3"' . $labelAttr . ' ' . ($data['customRequired'] ? 'required' : '') . registrationGetCustomFieldMaxlengthAttr($data['maxLengthAttr'], $data['customType']) . $data['dataExactLength'] . $data['dataErrorMessage'] . '>' . htmlspecialchars($data['customValue']) . '</textarea>';
    }
}

if (!function_exists('registrationRenderCustomFieldDefaultHtml')) {
    function registrationRenderCustomFieldDefaultHtml($data) {
        return '<label for="custom_' . htmlspecialchars($data['customId']) . '" class="form-label">' . htmlspecialchars($data['labelText']) . '</label>'
            . registrationRenderCustomFieldInputHtml($data);
    }
}

if (!function_exists('registrationRenderCustomFieldInnerHtml')) {
    function registrationRenderCustomFieldInnerHtml($data, $includeTextareaFieldLabel = true) {
        if ($data['htmlType'] === 'checkbox') {
            return registrationRenderCustomFieldCheckboxHtml($data);
        }
        if ($data['htmlType'] === 'textarea') {
            return registrationRenderCustomFieldTextareaHtml($data, $includeTextareaFieldLabel);
        }
        return registrationRenderCustomFieldDefaultHtml($data);
    }
}

if (!function_exists('registrationRenderCustomFieldErrorHtml')) {
    function registrationRenderCustomFieldErrorHtml($data) {
        return '<div class="error-message ' . ($data['customError'] ? 'd-block' : '') . '">'
            . ($data['customError'] ? htmlspecialchars($data['customError']) : '')
            . '</div>';
    }
}

// Funzione per generare l'HTML di un campo personalizzato
function renderCustomField($customField, $custom_field_values, $custom_field_errors, $position = null) {
    $data = registrationBuildCustomFieldRenderData($customField, $custom_field_values, $custom_field_errors);
    if ($data === null) {
        return '';
    }

    $dataPositionAttr = $position !== null ? ' data-position="' . htmlspecialchars($position) . '"' : '';
    return '<div class="row registration-form-row registration-form-row--inputs"' . $dataPositionAttr . '>'
        . '<div class="col-12">'
        . registrationRenderCustomFieldInnerHtml($data, true)
        . registrationRenderCustomFieldErrorHtml($data)
        . '</div></div>';
}

// ============================================
// SISTEMA DI POSIZIONI CON LETTERE E GRIGLIA
// ============================================
// 1. MAPPING DEI CAMPI STANDARD CON LE LORO LETTERE E POSIZIONI
$standard_fields_map = [
    'A' => ['name' => 'nome', 'label' => 'Nome', 'position' => 1, 'size' => 288],
    'B' => ['name' => 'cognome', 'label' => 'Cognome', 'position' => 2, 'size' => 288],
    'C' => ['name' => 'email', 'label' => 'Email', 'position' => 3, 'size' => 288],
    'D' => ['name' => 'prefisso_cellulare', 'label' => 'Prefisso/Cellulare', 'position' => 4, 'size' => 288],
    'E' => ['name' => 'cap', 'label' => 'CAP', 'position' => 5, 'size' => 288],
    'E2' => ['name' => 'provincia', 'label' => 'Provincia', 'position' => 5, 'size' => 288],
    'F' => ['name' => 'citta', 'label' => 'Città', 'position' => 6, 'size' => 288],
    'G' => ['name' => 'indirizzo', 'label' => 'Indirizzo', 'position' => 7, 'size' => 600],
    'H' => ['name' => 'fidelity', 'label' => 'Codice fidelity', 'position' => 8, 'size' => 288],
    'I' => ['name' => 'indirizzo_pdv', 'label' => 'Punto Vendita', 'position' => 9, 'size' => 600],
    'J' => ['name' => 'importo', 'label' => 'Importo scontrino', 'position' => 10, 'size' => 288],
    'K' => ['name' => 'data_scontrino', 'label' => 'Data scontrino', 'position' => 11, 'size' => 288],
    'L' => ['name' => 'scontrino', 'label' => 'Foto scontrino', 'position' => 12, 'size' => 600],
    'M' => ['name' => 'codice_partecipazione', 'label' => REGISTRATION_LABEL_CODICE_PARTECIPAZIONE, 'position' => 13, 'size' => 600],
    'N' => ['name' => 'privacy', 'label' => 'Privacy', 'position' => 14, 'size' => 600],
    'O' => ['name' => 'marketing', 'label' => 'Marketing', 'position' => 15, 'size' => 600],
    'P' => ['name' => 'profilazione', 'label' => 'Profilazione', 'position' => 16, 'size' => 600],
];

if (!function_exists('gst_apply_standard_fields_order')) {
    require_once __DIR__ . '/../app/inc/standard_fields_order.inc.php';
}
$standard_fields_map = gst_apply_standard_fields_order(
    $standard_fields_map,
    $contest_config['standard_fields_order'] ?? null
);

// 2. FUNZIONE PER CALCOLARE LA DIMENSIONE DI UN CAMPO PERSONALIZZATO
function getFieldSize($customField) {
    $fieldWidth = isset($customField['field_width']) ? trim((string)$customField['field_width']) : 'large';
    return ($fieldWidth === 'small') ? 288 : 600;
}

// 3. FUNZIONE PER CALCOLARE IL NUMERO DI POSIZIONI OCCUPATE
function getPositionsCount($size) {
    return ($size === 288) ? 1 : 2;
}

    // 3.5. FUNZIONE PER PARSARE IL VALORE DI POSITION_ORDER (può essere numerico o nel formato "after_s_email")
if (!function_exists('registrationDefaultPositionOrderResult')) {
    function registrationDefaultPositionOrderResult() {
        return [
            'position_order' => 5,
            'target_field_ref' => null
        ];
    }
}

if (!function_exists('registrationFindStandardReferencePosition')) {
    function registrationFindStandardReferencePosition($field_ref, $standard_fields_map, $fields_enabled) {
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
            if (($field_info['name'] ?? null) !== $field_ref) {
                continue;
            }
            $field_name = $field_info['name'];
            if (($fields_enabled[$field_name] ?? true) === true) {
                return $field_info['position'];
            }
            break;
        }
        return null;
    }
}

if (!function_exists('registrationFindCustomReferencePosition')) {
    function registrationFindCustomReferencePosition($field_ref, $standard_fields_map, $custom_fields, $fields_enabled, $visited) {
        $result = null;
        foreach ($custom_fields as $customField) {
            if (!is_array($customField) || !isset($customField['id']) || $customField['id'] !== $field_ref) {
                continue;
            }
            if (!isset($customField['position_order'])) {
                $result = 5;
                break;
            }
            $posOrderValue = $customField['position_order'];
            if (is_numeric($posOrderValue)) {
                $result = (int) $posOrderValue;
                break;
            }
            $nextVisited = $visited;
            $nextVisited[] = $field_ref;
            $parsed = parsePositionOrder($posOrderValue, $standard_fields_map, $custom_fields, $fields_enabled, $nextVisited);
            $result = $parsed['position_order'];
            break;
        }
        return $result;
    }
}

if (!function_exists('parsePositionOrder')) {
    function parsePositionOrder($position_order_value, $standard_fields_map, $custom_fields, $fields_enabled = [], $visited = []) {
        $result = registrationDefaultPositionOrderResult();
        if (is_numeric($position_order_value)) {
            $result = [
                'position_order' => (int) $position_order_value,
                'target_field_ref' => null
            ];
        } elseif (preg_match('/^(before|after)_([sc])_(.+)$/', $position_order_value, $matches)) {
            $placement = $matches[1];
            $field_type = $matches[2];
            $field_ref = $matches[3];
            if (!($field_type === 'c' && in_array($field_ref, $visited, true))) {
                $ref_position_order = $field_type === 's'
                    ? registrationFindStandardReferencePosition($field_ref, $standard_fields_map, $fields_enabled)
                    : registrationFindCustomReferencePosition($field_ref, $standard_fields_map, $custom_fields, $fields_enabled, $visited);
                if ($ref_position_order !== null) {
                    $result = [
                        'position_order' => $placement === 'before' ? $ref_position_order : $ref_position_order + 1,
                        'target_field_ref' => $field_type . '_' . $field_ref
                    ];
                }
            }
        }
        return $result;
    }
}

// 4. FUNZIONE PER ORGANIZZARE TUTTI I CAMPI NELLA GRIGLIA (escludendo i campi disabilitati)
if (!function_exists('registrationGetGridLetter')) {
    function registrationGetGridLetter($index, $letters) {
        if ($index < count($letters)) {
            return $letters[$index];
        }
        $double_index = $index - count($letters);
        $first_index = (int) ($double_index / count($letters));
        $second_index = $double_index % count($letters);
        return ($letters[$first_index] ?? '') . ($letters[$second_index] ?? '');
    }
}

if (!function_exists('registrationCollectEnabledStandardFields')) {
    function registrationCollectEnabledStandardFields($standard_fields_map, $fields_enabled) {
        $enabled_standard_fields = [];
        foreach ($standard_fields_map as $letter => $field_info) {
            $field_name = $field_info['name'];
            if (($fields_enabled[$field_name] ?? true) !== true) {
                continue;
            }
            $enabled_standard_fields[] = [
                'original_letter' => $letter,
                'field_info' => $field_info
            ];
        }
        usort($enabled_standard_fields, function ($a, $b) {
            return $a['field_info']['position'] - $b['field_info']['position'];
        });
        $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T'];
        foreach ($enabled_standard_fields as $index => &$enabled_field) {
            $enabled_field['assigned_letter'] = $letters[$index] ?? '?';
        }
        unset($enabled_field);
        return $enabled_standard_fields;
    }
}

if (!function_exists('registrationAppendStandardGridFields')) {
    function registrationAppendStandardGridFields(array &$all_fields, $enabled_standard_fields) {
        foreach ($enabled_standard_fields as $enabled_field) {
            $all_fields[] = [
                'type' => 'standard',
                'letter' => $enabled_field['assigned_letter'],
                'field' => $enabled_field['field_info'],
                'position_order' => $enabled_field['field_info']['position'],
                'size' => $enabled_field['field_info']['size'],
                'positions_count' => getPositionsCount($enabled_field['field_info']['size'])
            ];
        }
    }
}

if (!function_exists('registrationAppendCustomGridFields')) {
    function registrationAppendCustomGridFields(array &$all_fields, $enabled_standard_fields, $standard_fields_map, $custom_fields, $fields_enabled) {
        $all_letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        $custom_letter_index = count($enabled_standard_fields);
        $custom_index = 0;
        foreach ($custom_fields as $customField) {
            if (!is_array($customField)) {
                continue;
            }
            $position_order_value = $customField['position_order'] ?? 5;
            $parsed = parsePositionOrder($position_order_value, $standard_fields_map, $custom_fields, $fields_enabled);
            $target_field_ref = $parsed['target_field_ref'] ?? ($customField['target_field_ref'] ?? null);
            $size = getFieldSize($customField);
            $customFieldWithRef = $customField;
            if ($target_field_ref !== null) {
                $customFieldWithRef['target_field_ref'] = $target_field_ref;
            }
            $all_fields[] = [
                'type' => 'custom',
                'letter' => registrationGetGridLetter($custom_letter_index, $all_letters),
                'field' => $customFieldWithRef,
                'position_order' => $parsed['position_order'],
                'size' => $size,
                'positions_count' => getPositionsCount($size),
                'custom_index' => $custom_index,
                'target_field_ref' => $target_field_ref
            ];
            $custom_index++;
            $custom_letter_index++;
        }
    }
}

if (!function_exists('registrationCompareGridFields')) {
    function registrationCompareGridFields($a, $b) {
        $result = $a['position_order'] <=> $b['position_order'];
        if ($result === 0) {
            if ($a['type'] === 'custom' && $b['type'] === 'standard') {
                $result = -1;
            } elseif ($a['type'] === 'standard' && $b['type'] === 'custom') {
                $result = 1;
            } elseif ($a['type'] === 'custom' && $b['type'] === 'custom') {
                $result = ($a['custom_index'] ?? 999) <=> ($b['custom_index'] ?? 999);
            }
        }
        return $result;
    }
}

if (!function_exists('registrationBuildAllGridFields')) {
    function registrationBuildAllGridFields($enabled_standard_fields, $standard_fields_map, $custom_fields, $fields_enabled) {
        $all_fields = [];
        registrationAppendStandardGridFields($all_fields, $enabled_standard_fields);
        registrationAppendCustomGridFields($all_fields, $enabled_standard_fields, $standard_fields_map, $custom_fields, $fields_enabled);
        usort($all_fields, 'registrationCompareGridFields');
        return $all_fields;
    }
}

if (!function_exists('registrationFindTargetRefEndPosition')) {
    function registrationFindTargetRefEndPosition($target_field_ref, $grid) {
        $found = null;
        if ($target_field_ref !== null && preg_match('/^([sc])_(.+)$/', $target_field_ref, $ref_matches)) {
            $ref_type = $ref_matches[1];
            $ref_name = $ref_matches[2];
            foreach ($grid as $pos => $grid_item) {
                $isStandardMatch = $ref_type === 's' && $grid_item['type'] === 'standard' && ($grid_item['field']['name'] ?? null) === $ref_name;
                $isCustomMatch = $ref_type === 'c' && $grid_item['type'] === 'custom' && (($grid_item['field']['id'] ?? null) === $ref_name);
                if ($isStandardMatch || $isCustomMatch) {
                    $found = $pos + ($grid_item['positions_count'] - 1);
                    break;
                }
            }
        }
        return $found;
    }
}

if (!function_exists('registrationResolveTargetEndByOrder')) {
    function registrationResolveTargetEndByOrder($item, $grid) {
        $target_end_position = 0;
        $target_position_order = $item['position_order'] - 1;
        foreach ($grid as $pos => $grid_item) {
            $grid_pos_order = $grid_item['type'] === 'standard'
                ? ($grid_item['field']['position'] ?? null)
                : ($grid_item['position_order'] ?? null);
            if ($grid_pos_order !== null && $grid_pos_order == $target_position_order) {
                $target_end_position = max($target_end_position, $pos + ($grid_item['positions_count'] - 1));
            }
        }
        return $target_end_position;
    }
}

if (!function_exists('registrationResolveTargetEndPosition')) {
    function registrationResolveTargetEndPosition($item, $grid) {
        $refEnd = registrationFindTargetRefEndPosition($item['target_field_ref'] ?? null, $grid);
        if ($refEnd !== null) {
            return $refEnd;
        }
        return registrationResolveTargetEndByOrder($item, $grid);
    }
}

if (!function_exists('registrationFindAvailableGridPosition')) {
    function registrationFindAvailableGridPosition($start_position, $positions_count, $occupied) {
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

if (!function_exists('registrationResolveItemStartPosition')) {
    function registrationResolveItemStartPosition($item, $grid) {
        if ($item['type'] === 'standard') {
            return $item['position_order'];
        }
        $target_end_position = registrationResolveTargetEndPosition($item, $grid);
        if ($target_end_position > 0) {
            return $target_end_position + 1;
        }
        return $item['position_order'];
    }
}

function organizeFieldsInGrid($standard_fields_map, $custom_fields, $fields_enabled = []) {
    $grid = [];
    $occupied = [];
    $enabled_standard_fields = registrationCollectEnabledStandardFields($standard_fields_map, $fields_enabled);
    $all_fields = registrationBuildAllGridFields($enabled_standard_fields, $standard_fields_map, $custom_fields, $fields_enabled);

    foreach ($all_fields as $item) {
        $start_position = registrationResolveItemStartPosition($item, $grid);
        $found_position = registrationFindAvailableGridPosition($start_position, $item['positions_count'], $occupied);
        if ($found_position === null) {
            continue;
        }

        $grid[$found_position] = [
            'type' => $item['type'],
            'letter' => $item['letter'],
            'field' => $item['field'],
            'size' => $item['size'],
            'positions_count' => $item['positions_count']
        ];
        if ($item['type'] === 'custom') {
            $grid[$found_position]['position_order'] = $item['position_order'];
        }
        for ($i = 0; $i < $item['positions_count']; $i++) {
            $occupied[$found_position + $i] = true;
        }
    }

    ksort($grid);
    return $grid;
}

// 5. CALCOLARE LA GRIGLIA FINALE (escludendo i campi disabilitati)
$standard_fields_map = apply_fidelity_field_size_to_standard_map($standard_fields_map, $fidelity_config);
$fields_grid = organizeFieldsInGrid($standard_fields_map, $custom_fields, $fields_enabled);

// 6. FUNZIONE PER RECUPERARE I CAMPI PERSONALIZZATI IN UNA POSIZIONE DATA
function getCustomFieldsAtPosition($position, $fields_grid) {
    $result = [];
    foreach ($fields_grid as $pos => $item) {
        if ($pos == $position && $item['type'] === 'custom') {
            $result[] = $item['field'];
        }
    }
    return $result;
}

// 6b. FUNZIONE PER TROVARE LA POSIZIONE REALE DI UN CAMPO STANDARD NELLA GRIGLIA
function findStandardFieldPosition($field_name, $fields_grid) {
    foreach ($fields_grid as $pos => $item) {
        if ($item['type'] === 'standard' && isset($item['field']['name']) && $item['field']['name'] === $field_name) {
            return $pos;
        }
    }
    return null;
}

// 7. FUNZIONE PER RENDERE I CAMPI SECONDO LA GRIGLIA (per sostituire il vecchio sistema)
function renderFieldsFromGrid($start_position, $end_position, $fields_grid, $custom_field_values, $custom_field_errors) {
    $output = '';
    for ($pos = $start_position; $pos <= $end_position; $pos++) {
        if (isset($fields_grid[$pos]) && $fields_grid[$pos]['type'] === 'custom') {
            $customField = $fields_grid[$pos]['field'];
            $fieldSize = getFieldSize($customField);
            if ($fieldSize === 600) {
                // Campo largo: utilizzare renderCustomField (riga completa)
                $output .= renderCustomField($customField, $custom_field_values, $custom_field_errors, $pos);
            } else {
                // Campo piccolo: utilizzare renderCustomFieldContent (col-md-6)
                $output .= renderCustomFieldContent($customField, $custom_field_values, $custom_field_errors, false, $pos);
            }
        }
    }
    return $output;
}
if (!function_exists('registrationRenderFieldNome')) {
    function registrationRenderFieldNome($ctx) {
    $field_data = $ctx['field_data'];
    $form_data = $ctx['form_data'];
    $field_errors = $ctx['field_errors'];
    $fields_enabled = $ctx['fields_enabled'];
    $fidelity_config = $ctx['fidelity_config'];
    $contest_dates = $ctx['contest_dates'];
    $contest_config = $ctx['contest_config'];
    $field_required = $ctx['field_required'];
    $required_mark = $ctx['required_mark'];
    $required_attr = $ctx['required_attr'];
    $receipt_allowed_label = $ctx['receipt_allowed_label'];
    $receipt_accept_attr = $ctx['receipt_accept_attr'];
    ob_start();
$required = $field_required('nome');
            ?>
                    <div class="col-12 col-md-6">
                        <label for="nome" class="form-label">Nome<?php echo $required_mark($required); ?></label>
                        <input type="text" class="form-control <?php echo isset($field_errors['nome']) ? 'is-invalid' : ''; ?>" id="nome" name="nome" value="<?php echo htmlspecialchars($form_data['nome'] ?? ''); ?>"<?php echo $required_attr($required); ?>>
                        <div class="error-message <?php echo isset($field_errors['nome']) ? 'd-block' : ''; ?>" id="nome-error"><?php echo isset($field_errors['nome']) ? htmlspecialchars($field_errors['nome']) : ''; ?></div>
                    </div>
                    <?php
    return ob_get_clean();
    }
}

if (!function_exists('registrationRenderFieldCognome')) {
    function registrationRenderFieldCognome($ctx) {
    $field_data = $ctx['field_data'];
    $form_data = $ctx['form_data'];
    $field_errors = $ctx['field_errors'];
    $fields_enabled = $ctx['fields_enabled'];
    $fidelity_config = $ctx['fidelity_config'];
    $contest_dates = $ctx['contest_dates'];
    $contest_config = $ctx['contest_config'];
    $field_required = $ctx['field_required'];
    $required_mark = $ctx['required_mark'];
    $required_attr = $ctx['required_attr'];
    $receipt_allowed_label = $ctx['receipt_allowed_label'];
    $receipt_accept_attr = $ctx['receipt_accept_attr'];
    ob_start();
$required = $field_required('cognome');
                    ?>
                    <div class="col-12 col-md-6">
                        <label for="cognome" class="form-label">Cognome<?php echo $required_mark($required); ?></label>
                        <input type="text" class="form-control <?php echo isset($field_errors['cognome']) ? 'is-invalid' : ''; ?>" id="cognome" name="cognome" value="<?php echo htmlspecialchars($form_data['cognome'] ?? ''); ?>"<?php echo $required_attr($required); ?>>
                        <div class="error-message <?php echo isset($field_errors['cognome']) ? 'd-block' : ''; ?>" id="cognome-error"><?php echo isset($field_errors['cognome']) ? htmlspecialchars($field_errors['cognome']) : ''; ?></div>
                    </div>
                <?php
    return ob_get_clean();
    }
}

if (!function_exists('registrationRenderFieldEmail')) {
    function registrationRenderFieldEmail($ctx) {
    $field_data = $ctx['field_data'];
    $form_data = $ctx['form_data'];
    $field_errors = $ctx['field_errors'];
    $fields_enabled = $ctx['fields_enabled'];
    $fidelity_config = $ctx['fidelity_config'];
    $contest_dates = $ctx['contest_dates'];
    $contest_config = $ctx['contest_config'];
    $field_required = $ctx['field_required'];
    $required_mark = $ctx['required_mark'];
    $required_attr = $ctx['required_attr'];
    $receipt_allowed_label = $ctx['receipt_allowed_label'];
    $receipt_accept_attr = $ctx['receipt_accept_attr'];
    ob_start();
$required = $field_required('email');
                    ?>
                    <div class="col-12 col-md-6">
                        <label for="email" class="form-label">Email<?php echo $required_mark($required); ?></label>
                        <input type="email" class="form-control <?php echo isset($field_errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>"<?php echo $required_attr($required); ?>>
                        <div class="error-message <?php echo isset($field_errors['email']) ? 'd-block' : ''; ?>" id="email-error"><?php echo isset($field_errors['email']) ? htmlspecialchars($field_errors['email']) : ''; ?></div>
                    </div>
                    <?php
    return ob_get_clean();
    }
}

if (!function_exists('registrationResolvePrefissoCellulareEnabled')) {
    function registrationResolvePrefissoCellulareEnabled(array $fields_enabled): bool {
        $prefisso_cellulare_enabled = $fields_enabled['prefisso_cellulare'] ?? null;
        if ($prefisso_cellulare_enabled === null) {
            return ($fields_enabled['prefisso'] ?? true) === true ||
                ($fields_enabled['cellulare'] ?? true) === true;
        }
        return (bool) $prefisso_cellulare_enabled;
    }
}

if (!function_exists('registrationResolveCellulareDigits')) {
    /**
     * @return array{0: int, 1: int}
     */
    function registrationResolveCellulareDigits(array $contest_config): array {
        $cellulare_min = max(6, min(12, (int)($contest_config['cellulare_digits_min'] ?? 6)));
        $cellulare_max = max(6, min(12, (int)($contest_config['cellulare_digits_max'] ?? 12)));
        if ($cellulare_min > $cellulare_max) {
            $cellulare_max = $cellulare_min;
        }
        return [$cellulare_min, $cellulare_max];
    }
}

if (!function_exists('registrationRenderFieldPrefissoCellulare')) {
    function registrationRenderFieldPrefissoCellulare($ctx) {
    $form_data = $ctx['form_data'];
    $field_errors = $ctx['field_errors'];
    $fields_enabled = $ctx['fields_enabled'];
    $contest_config = $ctx['contest_config'];
    $field_required = $ctx['field_required'];
    $required_mark = $ctx['required_mark'];
    $required_attr = $ctx['required_attr'];
    ob_start();
            if (registrationResolvePrefissoCellulareEnabled($fields_enabled)):
                    $prefissoRequired = $field_required('prefisso');
                    $cellulareRequired = $field_required('cellulare');
                    [$cellulare_min, $cellulare_max] = registrationResolveCellulareDigits($contest_config);
                    ?>
                    <div class="col-4 col-sm-3 col-md-2">
                        <label for="prefisso" style="width:120%" class="form-label">Prefisso<?php echo $required_mark($prefissoRequired); ?></label>
                        <input type="text" class="form-control <?php echo isset($field_errors['prefisso']) ? 'is-invalid' : ''; ?>" id="prefisso" name="prefisso" pattern="^\+\d{1,3}$" maxlength="4" value="<?php echo htmlspecialchars($form_data['prefisso'] ?? '+39'); ?>" placeholder="+39"<?php echo $required_attr($prefissoRequired); ?>>
                        <div class="error-message <?php echo isset($field_errors['prefisso']) ? 'd-block' : ''; ?>" id="prefisso-error"><?php echo isset($field_errors['prefisso']) ? htmlspecialchars($field_errors['prefisso']) : ''; ?></div>
                    </div>
                    <div class="col-8 col-sm-9 col-md-4">
                        <label for="cellulare" class="form-label">Cellulare<?php echo $required_mark($cellulareRequired); ?></label>
                        <input type="text" class="form-control <?php echo isset($field_errors['cellulare']) ? 'is-invalid' : ''; ?>" id="cellulare" name="cellulare" pattern="^\d{<?php echo $cellulare_min; ?>,<?php echo $cellulare_max; ?>}$" maxlength="12" data-cellulare-min="<?php echo $cellulare_min; ?>" data-cellulare-max="<?php echo $cellulare_max; ?>" value="<?php echo htmlspecialchars($form_data['cellulare'] ?? ''); ?>"<?php echo $required_attr($cellulareRequired); ?>>
                        <div class="error-message <?php echo isset($field_errors['cellulare']) ? 'd-block' : ''; ?>" id="cellulare-error"><?php echo isset($field_errors['cellulare']) ? htmlspecialchars($field_errors['cellulare']) : ''; ?></div>
                </div>
                <?php
            endif;
    return ob_get_clean();
    }
}

if (!function_exists('registrationIsFidelityFieldEnabled')) {
    function registrationIsFidelityFieldEnabled(array $fields_enabled): bool {
        return ($fields_enabled['fidelity'] ?? true) === true;
    }
}

if (!function_exists('registrationBuildFidelityAriaDescribedby')) {
    function registrationBuildFidelityAriaDescribedby(string $fidelity_help_text, array $field_errors): string {
        $fidelity_aria = [];
        if ($fidelity_help_text !== '') {
            $fidelity_aria[] = 'fidelity-help';
        }
        if (isset($field_errors['fidelity'])) {
            $fidelity_aria[] = 'fidelity-error';
        }
        return $fidelity_aria !== [] ? ' aria-describedby="' . implode(' ', $fidelity_aria) . '"' : '';
    }
}

if (!function_exists('registrationRenderFieldFidelity')) {
    function registrationRenderFieldFidelity($ctx) {
    $field_data = $ctx['field_data'];
    $form_data = $ctx['form_data'];
    $field_errors = $ctx['field_errors'];
    $fields_enabled = $ctx['fields_enabled'];
    $fidelity_config = $ctx['fidelity_config'];
    $field_required = $ctx['field_required'];
    $required_mark = $ctx['required_mark'];
    $required_attr = $ctx['required_attr'];
    ob_start();
if (registrationIsFidelityFieldEnabled($fields_enabled)):
                $required = $field_required('fidelity');
                $fidelity_col_class = (($field_data['size'] ?? 288) === 600) ? 'col-12' : 'col-12 col-md-6';
                $fidelity_help_text = trim((string) ($fidelity_config['help_text'] ?? ''));
                $fidelity_help_color = trim((string) ($fidelity_config['help_text_color'] ?? ''));
            ?>
                    <div class="<?php echo $fidelity_col_class; ?>">
                        <label for="fidelity" class="form-label">Codice fidelity<?php echo $required_mark($required); ?></label>
                        <input
                            type="text"
                            class="form-control <?php echo isset($field_errors['fidelity']) ? 'is-invalid' : ''; ?>"
                            id="fidelity"
                            name="fidelity"
                            value="<?php echo htmlspecialchars($form_data['fidelity'] ?? ''); ?>"
                            data-fidelity-type="<?php echo htmlspecialchars(strtolower($fidelity_config['type'] ?? 'numeric')); ?>"
                            data-fidelity-length="<?php echo (int) ($fidelity_config['length'] ?? 13); ?>"
                            inputmode="<?php echo (isset($fidelity_config['type']) && strtolower($fidelity_config['type']) === 'numeric') ? 'numeric' : 'text'; ?>"
                            <?php echo $required_attr($required); ?>
                            <?php echo registrationBuildFidelityAriaDescribedby($fidelity_help_text, $field_errors); ?>
                        >
                        <?php if ($fidelity_help_text !== ''): ?>
                        <?php echo render_registration_hint_text_html($fidelity_help_text, 'start', 'fidelity-help', 'mt-2', $fidelity_help_color); ?>
                        <?php endif; ?>
                        <div class="error-message <?php echo isset($field_errors['fidelity']) ? 'd-block' : ''; ?>" id="fidelity-error"><?php echo isset($field_errors['fidelity']) ? htmlspecialchars($field_errors['fidelity']) : ''; ?></div>
                    </div>
                    <?php
            endif;
    return ob_get_clean();
    }
}

if (!function_exists('registrationRenderFieldCap')) {
    function registrationRenderFieldCap($ctx) {
        $form_data = $ctx['form_data'];
        $field_errors = $ctx['field_errors'];
        $field_required = $ctx['field_required'] ?? null;
        $isRequired = is_callable($field_required) ? (bool) $field_required('cap') : !empty($ctx['fields_enabled']['cap']);
        $requiredMark = $isRequired ? ' *' : '';
        $requiredAttr = $isRequired ? ' required' : '';
        $capValue = preg_replace('/\D+/', '', (string) ($form_data['cap'] ?? ''));
        if (strlen($capValue) > 5) {
            $capValue = substr($capValue, 0, 5);
        }
        $hasError = isset($field_errors['cap']);
        ob_start();
        ?>
                    <div class="col-12 col-md-6">
                        <label for="cap" class="form-label">CAP<?php echo $requiredMark; ?></label>
                        <input type="text" class="form-control <?php echo $hasError ? 'is-invalid' : ''; ?>" id="cap" name="cap"
                               value="<?php echo htmlspecialchars($capValue, ENT_QUOTES, 'UTF-8'); ?>"
                               inputmode="numeric" autocomplete="postal-code" spellcheck="false"
                               maxlength="5" minlength="5" pattern="[0-9]{5}"
                               data-cap-exact-digits="5"
                               placeholder="Es. 20121"<?php echo $requiredAttr; ?>>
                        <div class="error-message <?php echo $hasError ? 'd-block' : ''; ?>" id="cap-error"><?php echo $hasError ? htmlspecialchars($field_errors['cap']) : ''; ?></div>
                    </div>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('registrationRenderFieldProvincia')) {
    function registrationRenderFieldProvincia($ctx) {
    $field_data = $ctx['field_data'];
    $form_data = $ctx['form_data'];
    $field_errors = $ctx['field_errors'];
    $fields_enabled = $ctx['fields_enabled'];
    $fidelity_config = $ctx['fidelity_config'];
    $contest_dates = $ctx['contest_dates'];
    $contest_config = $ctx['contest_config'];
    $field_required = $ctx['field_required'];
    $required_mark = $ctx['required_mark'];
    $required_attr = $ctx['required_attr'];
    $receipt_allowed_label = $ctx['receipt_allowed_label'];
    $receipt_accept_attr = $ctx['receipt_accept_attr'];
    $provinciaRequired = $field_required('provincia');
    $hasProvinciaError = isset($field_errors['provincia']);
    ob_start();
?>
                    <div class="col-12 col-md-6">
                        <label for="provincia" class="form-label">Provincia<?php echo $required_mark($provinciaRequired); ?></label>
                        <select name="provincia" id="provincia" class="form-select<?php echo $hasProvinciaError ? ' is-invalid' : ''; ?>"<?php echo $required_attr($provinciaRequired); ?>>
                            <option value="">Seleziona provincia</option>
                            <?php
                            if (!empty($form_data['provincia'])) {
                                echo '<option value="' . htmlspecialchars($form_data['provincia'], ENT_QUOTES, 'UTF-8') . '" selected>' .
                                     htmlspecialchars($form_data['provincia'], ENT_QUOTES, 'UTF-8') . '</option>';
                            }
                            ?>
                        </select>
                        <div class="error-message <?php echo $hasProvinciaError ? 'd-block' : ''; ?>" id="provincia-error"><?php echo $hasProvinciaError ? htmlspecialchars($field_errors['provincia']) : ''; ?></div>
                    </div>
            <?php
    return ob_get_clean();
    }
}

if (!function_exists('registrationRenderFieldCitta')) {
    function registrationRenderFieldCitta($ctx) {
    $field_data = $ctx['field_data'];
    $form_data = $ctx['form_data'];
    $field_errors = $ctx['field_errors'];
    $fields_enabled = $ctx['fields_enabled'];
    $fidelity_config = $ctx['fidelity_config'];
    $contest_dates = $ctx['contest_dates'];
    $contest_config = $ctx['contest_config'];
    $field_required = $ctx['field_required'];
    $required_mark = $ctx['required_mark'];
    $required_attr = $ctx['required_attr'];
    $receipt_allowed_label = $ctx['receipt_allowed_label'];
    $receipt_accept_attr = $ctx['receipt_accept_attr'];
    $provinciaEnabled = !empty($fields_enabled['provincia']);
    $cittaRequired = $field_required('citta');
    $disabledAttr = $provinciaEnabled ? ' disabled' : '';
    $emptyLabel = $provinciaEnabled ? 'Seleziona prima la provincia' : 'Seleziona città';
    $canShowPreselected = !empty($form_data['citta']) && (!$provinciaEnabled || !empty($form_data['provincia']));
    $hasCittaError = isset($field_errors['citta']);
    ob_start();
?>
                    <div class="col-12 col-md-6">
                                        <label for="citta" class="form-label">Città<?php echo $required_mark($cittaRequired); ?></label>
                        <select name="citta" id="citta" class="form-select<?php echo $hasCittaError ? ' is-invalid' : ''; ?>"<?php echo $disabledAttr; ?><?php echo $required_attr($cittaRequired); ?>>
                            <option value=""><?php echo htmlspecialchars($emptyLabel, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php
                            if ($canShowPreselected) {
                                echo '<option value="' . htmlspecialchars($form_data['citta'], ENT_QUOTES, 'UTF-8') . '" selected>' .
                                     htmlspecialchars($form_data['citta'], ENT_QUOTES, 'UTF-8') . '</option>';
                            }
                            ?>
                        </select>
                        <div class="error-message <?php echo $hasCittaError ? 'd-block' : ''; ?>" id="citta-error"><?php echo $hasCittaError ? htmlspecialchars($field_errors['citta']) : ''; ?></div>
                </div>
                <?php
    return ob_get_clean();
    }
}

if (!function_exists('registrationRenderFieldIndirizzo')) {
    function registrationRenderFieldIndirizzo($ctx) {
    $field_data = $ctx['field_data'];
    $form_data = $ctx['form_data'];
    $field_errors = $ctx['field_errors'];
    $fields_enabled = $ctx['fields_enabled'];
    $fidelity_config = $ctx['fidelity_config'];
    $contest_dates = $ctx['contest_dates'];
    $contest_config = $ctx['contest_config'];
    $field_required = $ctx['field_required'];
    $required_mark = $ctx['required_mark'];
    $required_attr = $ctx['required_attr'];
    $receipt_allowed_label = $ctx['receipt_allowed_label'];
    $receipt_accept_attr = $ctx['receipt_accept_attr'];
    $indirizzoRequired = $field_required('indirizzo');
    $hasIndirizzoError = isset($field_errors['indirizzo']);
    ob_start();
?>
                    <div class="col-12">
                        <label for="indirizzo" class="form-label">Indirizzo<?php echo $required_mark($indirizzoRequired); ?></label>
                        <input type="text" class="form-control<?php echo $hasIndirizzoError ? ' is-invalid' : ''; ?>" id="indirizzo" name="indirizzo" value="<?php echo htmlspecialchars($form_data['indirizzo'] ?? ''); ?>" placeholder="Via, numero civico..."<?php echo $required_attr($indirizzoRequired); ?>>
                        <div class="error-message <?php echo $hasIndirizzoError ? 'd-block' : ''; ?>" id="indirizzo-error"><?php echo $hasIndirizzoError ? htmlspecialchars($field_errors['indirizzo']) : ''; ?></div>
                </div>
                <?php
    return ob_get_clean();
    }
}

if (!function_exists('registrationLookupSelectedPdv')) {
    function registrationLookupSelectedPdv($form_data) {
        $selected_pdv = [];
        if (empty($form_data['indirizzo_pdv'])) {
            return $selected_pdv;
        }
        try {
            $c_pdo = conn_pdo();
            $stmt = $c_pdo->prepare("SELECT id, prov, citta, cap, indirizzo, ragsoc, insegna, regione FROM punti_vendita WHERE id = :id AND active = 1 LIMIT 1");
            $stmt->bindValue(':id', (int) $form_data['indirizzo_pdv'], PDO::PARAM_INT);
            $stmt->execute();
            $selected_pdv = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log('registration PDV lookup: ' . $e->getMessage());
        }
        return $selected_pdv;
    }
}

if (!function_exists('registrationBuildPdvViewModel')) {
    function registrationBuildPdvViewModel(array $ctx) {
        $form_data = $ctx['form_data'];
        $contest_config = $ctx['contest_config'];
        $field_required = $ctx['field_required'];
        $pdv_config = $contest_config['pdv_config'] ?? [];
        $pdv_mode = strtolower(trim((string) ($pdv_config['mode'] ?? 'combinato')));
        $pdv_mode = in_array($pdv_mode, ['combinato', 'cascata'], true) ? $pdv_mode : 'combinato';
        $selected_pdv = registrationLookupSelectedPdv($form_data);
        $form_regione = $selected_pdv['regione'] ?? '';
        $form_prov = $form_data['prov_pdv'] ?? ($selected_pdv['prov'] ?? '');
        $form_citta = $form_data['citta_pdv'] ?? ($selected_pdv['citta'] ?? '');
        $pdv_cascata_geo_mode = strtolower(trim((string) ($pdv_config['cascata']['geographic_mode'] ?? 'provincia')));
        $pdv_cascata_geo_mode = in_array($pdv_cascata_geo_mode, ['provincia', 'regione'], true) ? $pdv_cascata_geo_mode : 'provincia';
        $pdv_show_regione = $pdv_cascata_geo_mode === 'regione';
        $pdv_show_provincia = $pdv_cascata_geo_mode === 'provincia';
        return [
            'pdv_config' => $pdv_config,
            'pdv_mode' => $pdv_mode,
            'selected_pdv' => $selected_pdv,
            'form_regione' => $form_regione,
            'form_prov' => $form_prov,
            'form_citta' => $form_citta,
            'pdv_cascata_geo_mode' => $pdv_cascata_geo_mode,
            'pdv_show_regione' => $pdv_show_regione,
            'pdv_show_provincia' => $pdv_show_provincia,
            'pdv_area_selected' => ($pdv_show_regione && $form_regione !== '') || ($pdv_show_provincia && $form_prov !== ''),
            'pdv_geo_col_class' => 'col-12 col-md-6',
            'provPdvRequired' => $field_required('prov_pdv'),
            'cittaPdvRequired' => $field_required('citta_pdv'),
            'indirizzoPdvRequired' => $field_required('indirizzo_pdv'),
            'form_data' => $form_data,
            'field_errors' => $ctx['field_errors'],
            'required_mark' => $ctx['required_mark'],
            'required_attr' => $ctx['required_attr'],
        ];
    }
}

if (!function_exists('registrationBuildCascataPdvDisplayText')) {
    function registrationBuildCascataPdvDisplayText($selected_pdv, $pdv_config) {
        $store_name = $selected_pdv['ragsoc'] ?: $selected_pdv['insegna'];
        $cascata_display = strtolower(trim((string) ($pdv_config['cascata']['punto_vendita_display'] ?? 'insegna_indirizzo')));
        if ($cascata_display === 'insegna_indirizzo' && trim($selected_pdv['indirizzo'] ?? '') !== '') {
            return ($store_name ?: 'PDV') . ' - ' . trim($selected_pdv['indirizzo']);
        }
        if ($cascata_display === 'indirizzo_insegna' && !empty($store_name)) {
            return trim($selected_pdv['indirizzo']) . ' - ' . $store_name;
        }
        return trim($selected_pdv['indirizzo']) ?: ($store_name ?: 'PDV');
    }
}

if (!function_exists('registrationResolveCombinatoPdvLabelPart')) {
    function registrationResolveCombinatoPdvLabelPart($k, $selected_pdv) {
        if ($k === 'ragsoc') {
            return $selected_pdv['ragsoc'] ?: $selected_pdv['insegna'];
        }
        if ($k === 'insegna') {
            return $selected_pdv['insegna'] ?: $selected_pdv['ragsoc'];
        }
        if ($k === 'indirizzo') {
            return $selected_pdv['indirizzo'] ?? '';
        }
        if ($k === 'citta') {
            return $selected_pdv['citta'] ?? '';
        }
        if ($k === 'prov') {
            return $selected_pdv['prov'] ?? '';
        }
        if ($k === 'regione') {
            return $selected_pdv['regione'] ?? '';
        }
        if ($k === 'cap') {
            return $selected_pdv['cap'] ?? '';
        }
        return '';
    }
}

if (!function_exists('registrationBuildCombinatoPdvDisplayText')) {
    function registrationBuildCombinatoPdvDisplayText($selected_pdv, $pdv_config) {
        $label_fields = $pdv_config['combinato']['label_fields'] ?? ['ragsoc', 'indirizzo', 'citta', 'prov'];
        $separator = $pdv_config['combinato']['separator'] ?? ' - ';
        $label_parts = [];
        foreach ($label_fields as $k) {
            $v = registrationResolveCombinatoPdvLabelPart($k, $selected_pdv);
            if (trim((string) $v) !== '') {
                $label_parts[] = trim($v);
            }
        }
        $display_text = implode($separator, $label_parts);
        if ($display_text === '') {
            $display_text = ($selected_pdv['ragsoc'] ?: $selected_pdv['insegna']) ?: $selected_pdv['indirizzo'] ?: 'PDV';
        }
        return $display_text;
    }
}

if (!function_exists('registrationResolveCascataCittaPlaceholder')) {
    function registrationResolveCascataCittaPlaceholder(bool $pdv_area_selected, string $pdv_cascata_geo_mode): string {
        if ($pdv_area_selected) {
            return 'Seleziona città';
        }
        return $pdv_cascata_geo_mode === 'regione' ? 'Seleziona prima la regione' : 'Seleziona prima la provincia';
    }
}

if (!function_exists('registrationRenderPdvCascataRegioneFieldHtml')) {
    function registrationRenderPdvCascataRegioneFieldHtml(array $vm): string {
        ob_start();
        $show = !empty($vm['pdv_show_regione']);
        $pdv_geo_col_class = $vm['pdv_geo_col_class'];
        $form_regione = $vm['form_regione'];
        $selectClass = $show ? 'form-select' : 'd-none';
        $hiddenAttrs = $show ? '' : ' aria-hidden="true" tabindex="-1"';
        if ($show): ?>
                            <div class="<?php echo $pdv_geo_col_class; ?>">
                                <label for="regione_pdv" class="form-label">Regione PDV</label>
        <?php endif; ?>
                                <select id="regione_pdv" class="<?php echo $selectClass; ?>"<?php echo $hiddenAttrs; ?>>
                                    <?php if ($show): ?>
                                    <option value="">Seleziona regione</option>
                                    <?php endif; ?>
                                    <?php if ($form_regione !== ''): ?>
                                    <option value="<?php echo htmlspecialchars($form_regione, ENT_QUOTES, 'UTF-8'); ?>" selected><?php echo htmlspecialchars($form_regione, ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endif; ?>
                                </select>
        <?php if ($show): ?>
                            </div>
        <?php endif;
        return ob_get_clean();
    }
}

if (!function_exists('registrationRenderPdvCascataProvinciaFieldHtml')) {
    function registrationRenderPdvCascataProvinciaFieldHtml(array $vm): string {
        ob_start();
        $show = !empty($vm['pdv_show_provincia']);
        $pdv_geo_col_class = $vm['pdv_geo_col_class'];
        $form_prov = $vm['form_prov'];
        $field_errors = $vm['field_errors'];
        $required_mark = $vm['required_mark'];
        $required_attr = $vm['required_attr'];
        $provPdvRequired = $vm['provPdvRequired'];
        $invalidClass = isset($field_errors['prov_pdv']) ? ' is-invalid' : '';
        $selectClass = $show ? 'form-select' . $invalidClass : 'd-none';
        $hiddenAttrs = $show ? '' : ' aria-hidden="true" tabindex="-1"';
        $requiredHtml = $show ? $required_attr($provPdvRequired) : '';
        if ($show): ?>
                            <div class="<?php echo $pdv_geo_col_class; ?>">
                                <label for="prov_pdv" class="form-label">Provincia PDV<?php echo $required_mark($provPdvRequired); ?></label>
        <?php endif; ?>
                                <select name="prov_pdv" id="prov_pdv" class="<?php echo $selectClass; ?>"<?php echo $hiddenAttrs; ?><?php echo $requiredHtml; ?>>
                                    <?php if ($show): ?>
                                    <option value="">Seleziona provincia</option>
                                    <?php endif; ?>
                                    <?php if ($form_prov !== ''): ?>
                                    <option value="<?php echo htmlspecialchars($form_prov, ENT_QUOTES, 'UTF-8'); ?>" selected><?php echo htmlspecialchars($form_prov, ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endif; ?>
                                </select>
                                <div class="error-message <?php echo isset($field_errors['prov_pdv']) ? 'd-block' : ''; ?>" id="prov_pdv-error"><?php echo isset($field_errors['prov_pdv']) ? htmlspecialchars($field_errors['prov_pdv']) : ''; ?></div>
        <?php if ($show): ?>
                            </div>
        <?php endif;
        return ob_get_clean();
    }
}

if (!function_exists('registrationRenderPdvCascataCittaFieldHtml')) {
    function registrationRenderPdvCascataCittaFieldHtml(array $vm): string {
        ob_start();
        $pdv_geo_col_class = $vm['pdv_geo_col_class'];
        $form_citta = $vm['form_citta'];
        $pdv_area_selected = $vm['pdv_area_selected'];
        $field_errors = $vm['field_errors'];
        $required_mark = $vm['required_mark'];
        $required_attr = $vm['required_attr'];
        $cittaPdvRequired = $vm['cittaPdvRequired'];
        $cittaPlaceholder = registrationResolveCascataCittaPlaceholder($pdv_area_selected, (string) $vm['pdv_cascata_geo_mode']);
        ?>
                            <div class="<?php echo $pdv_geo_col_class; ?>">
                                <label for="citta_pdv" class="form-label">Città PDV<?php echo $required_mark($cittaPdvRequired); ?></label>
                                <select name="citta_pdv" id="citta_pdv" class="form-select <?php echo isset($field_errors['citta_pdv']) ? 'is-invalid' : ''; ?>" <?php echo !$pdv_area_selected ? 'disabled' : ''; ?><?php echo $required_attr($cittaPdvRequired); ?>>
                                    <option value=""><?php echo $cittaPlaceholder; ?></option>
                                    <?php if ($form_citta !== '' && $pdv_area_selected): ?>
                                    <option value="<?php echo htmlspecialchars($form_citta, ENT_QUOTES, 'UTF-8'); ?>" selected><?php echo htmlspecialchars($form_citta, ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endif; ?>
                                </select>
                                <div class="error-message <?php echo isset($field_errors['citta_pdv']) ? 'd-block' : ''; ?>" id="citta_pdv-error"><?php echo isset($field_errors['citta_pdv']) ? htmlspecialchars($field_errors['citta_pdv']) : ''; ?></div>
                            </div>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('registrationRenderPdvCascataGeoRowHtml')) {
    function registrationRenderPdvCascataGeoRowHtml(array $vm): string {
        return '<div class="row registration-form-row registration-form-row--inputs">'
            . registrationRenderPdvCascataRegioneFieldHtml($vm)
            . registrationRenderPdvCascataProvinciaFieldHtml($vm)
            . registrationRenderPdvCascataCittaFieldHtml($vm)
            . '</div>';
    }
}

if (!function_exists('registrationRenderPdvCascataPuntoVenditaRowHtml')) {
    function registrationRenderPdvCascataPuntoVenditaRowHtml(array $vm): string {
        ob_start();
        $form_citta = $vm['form_citta'];
        $pdv_area_selected = $vm['pdv_area_selected'];
        $field_errors = $vm['field_errors'];
        $form_data = $vm['form_data'];
        $selected_pdv = $vm['selected_pdv'];
        $pdv_config = $vm['pdv_config'];
        $required_mark = $vm['required_mark'];
        $required_attr = $vm['required_attr'];
        $indirizzoPdvRequired = $vm['indirizzoPdvRequired'];
        ?>
                        <div class="row registration-form-row registration-form-row--inputs">
                            <div class="col-12">
                                <label for="indirizzo_pdv" class="form-label">Punto Vendita<?php echo $required_mark($indirizzoPdvRequired); ?></label>
                                <select name="indirizzo_pdv" id="indirizzo_pdv" class="form-select <?php echo isset($field_errors['indirizzo_pdv']) ? 'is-invalid' : ''; ?>" <?php echo (!$pdv_area_selected || $form_citta === '') ? 'disabled' : ''; ?><?php echo $required_attr($indirizzoPdvRequired); ?>>
                                    <option value=""><?php echo (!$pdv_area_selected || $form_citta === '') ? 'Seleziona prima area e città' : 'Seleziona punto vendita'; ?></option>
                                    <?php
                                    if (!empty($form_data['indirizzo_pdv']) && $selected_pdv) {
                                        $display_text = registrationBuildCascataPdvDisplayText($selected_pdv, $pdv_config);
                                        echo '<option value="' . htmlspecialchars($selected_pdv['id'], ENT_QUOTES, 'UTF-8') . '" data-prov="' . htmlspecialchars($selected_pdv['prov'], ENT_QUOTES, 'UTF-8') . '" data-citta="' . htmlspecialchars($selected_pdv['citta'], ENT_QUOTES, 'UTF-8') . '" data-regione="' . htmlspecialchars($selected_pdv['regione'] ?? '', ENT_QUOTES, 'UTF-8') . '" selected>' . htmlspecialchars($display_text, ENT_QUOTES, 'UTF-8') . '</option>';
                                    }
                                    ?>
                                </select>
                                <div class="error-message <?php echo isset($field_errors['indirizzo_pdv']) ? 'd-block' : ''; ?>" id="indirizzo_pdv-error"><?php echo isset($field_errors['indirizzo_pdv']) ? htmlspecialchars($field_errors['indirizzo_pdv']) : ''; ?></div>
                            </div>
                        </div>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('registrationRenderPdvCascataHtml')) {
    function registrationRenderPdvCascataHtml(array $vm) {
        return '<div class="pdv-cascata-block">'
            . registrationRenderPdvCascataGeoRowHtml($vm)
            . registrationRenderPdvCascataPuntoVenditaRowHtml($vm)
            . '</div>';
    }
}

if (!function_exists('registrationRenderPdvCombinatoHtml')) {
    function registrationRenderPdvCombinatoHtml(array $vm) {
        ob_start();
        $form_prov = $vm['form_prov'];
        $form_citta = $vm['form_citta'];
        $field_errors = $vm['field_errors'];
        $form_data = $vm['form_data'];
        $selected_pdv = $vm['selected_pdv'];
        $pdv_config = $vm['pdv_config'];
        $required_mark = $vm['required_mark'];
        $required_attr = $vm['required_attr'];
        $indirizzoPdvRequired = $vm['indirizzoPdvRequired'];
        ?>
                    <div class="col-12 pdv-combinato-block">
                        <label for="indirizzo_pdv_combinato" class="form-label">Punto Vendita<?php echo $required_mark($indirizzoPdvRequired); ?></label>
                        <select name="indirizzo_pdv" id="indirizzo_pdv_combinato" class="form-select <?php echo isset($field_errors['indirizzo_pdv']) ? 'is-invalid' : ''; ?>"<?php echo $required_attr($indirizzoPdvRequired); ?>>
                            <option value="">Seleziona punto vendita</option>
                            <?php
                            if (!empty($form_data['indirizzo_pdv']) && $selected_pdv) {
                                $display_text = registrationBuildCombinatoPdvDisplayText($selected_pdv, $pdv_config);
                                echo '<option value="' . htmlspecialchars($selected_pdv['id'], ENT_QUOTES, 'UTF-8') . '" data-prov="' . htmlspecialchars($selected_pdv['prov'], ENT_QUOTES, 'UTF-8') . '" data-citta="' . htmlspecialchars($selected_pdv['citta'], ENT_QUOTES, 'UTF-8') . '" data-indirizzo="' . htmlspecialchars($selected_pdv['indirizzo'], ENT_QUOTES, 'UTF-8') . '" data-ragsoc="' . htmlspecialchars($selected_pdv['ragsoc'] ?? '', ENT_QUOTES, 'UTF-8') . '" data-insegna="' . htmlspecialchars($selected_pdv['insegna'] ?? '', ENT_QUOTES, 'UTF-8') . '" data-cap="' . htmlspecialchars($selected_pdv['cap'] ?? '', ENT_QUOTES, 'UTF-8') . '" selected>' . htmlspecialchars($display_text, ENT_QUOTES, 'UTF-8') . '</option>';
                            }
                            ?>
                        </select>
                        <label for="prov_pdv_combinato" class="visually-hidden">Provincia PDV</label>
                        <input type="hidden" name="prov_pdv" id="prov_pdv_combinato" value="<?php echo htmlspecialchars($form_prov, ENT_QUOTES, 'UTF-8'); ?>">
                        <label for="citta_pdv_combinato" class="visually-hidden">Città PDV</label>
                        <input type="hidden" name="citta_pdv" id="citta_pdv_combinato" value="<?php echo htmlspecialchars($form_citta, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="error-message <?php echo isset($field_errors['indirizzo_pdv']) ? 'd-block' : ''; ?>" id="indirizzo_pdv_combinato-error"><?php echo isset($field_errors['indirizzo_pdv']) ? htmlspecialchars($field_errors['indirizzo_pdv']) : ''; ?></div>
                        <div class="error-message" id="prov_pdv_combinato-error"></div>
                        <div class="error-message" id="citta_pdv_combinato-error"></div>
                    </div>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('registrationRenderFieldIndirizzoPdv')) {
    function registrationRenderFieldIndirizzoPdv($ctx) {
        $vm = registrationBuildPdvViewModel($ctx);
        if ($vm['pdv_mode'] === 'cascata') {
            return registrationRenderPdvCascataHtml($vm);
        }
        return registrationRenderPdvCombinatoHtml($vm);
    }
}

if (!function_exists('registrationRenderFieldImporto')) {
    function registrationRenderFieldImporto($ctx) {
    $field_data = $ctx['field_data'];
    $form_data = $ctx['form_data'];
    $field_errors = $ctx['field_errors'];
    $fields_enabled = $ctx['fields_enabled'];
    $fidelity_config = $ctx['fidelity_config'];
    $contest_dates = $ctx['contest_dates'];
    $contest_config = $ctx['contest_config'];
    $field_required = $ctx['field_required'];
    $required_mark = $ctx['required_mark'];
    $required_attr = $ctx['required_attr'];
    $receipt_allowed_label = $ctx['receipt_allowed_label'];
    $receipt_accept_attr = $ctx['receipt_accept_attr'];
    ob_start();
if (($fields_enabled['importo'] ?? true) === true):
                $required = $field_required('importo');
                ?>
                     <div class="col-12 col-md-6">
                         <label for="importo" class="form-label">Importo scontrino<?php echo $required_mark($required); ?></label>
                        <input type="text" class="form-control <?php echo isset($field_errors['importo']) ? 'is-invalid' : ''; ?>" id="importo" name="importo" inputmode="decimal" pattern="^\d+(?:[.,\s]\d{1,2})?$" value="<?php echo htmlspecialchars($form_data['importo'] ?? ''); ?>" placeholder="<?php echo htmlspecialchars(SUMMERCOOP_IMPORTO_PH_LABEL); ?>" title="<?php echo htmlspecialchars(SUMMERCOOP_IMPORTO_PH_TITLE); ?>"<?php echo $required_attr($required); ?>>
                         <div class="error-message <?php echo isset($field_errors['importo']) ? 'd-block' : ''; ?>" id="importo-error"><?php echo isset($field_errors['importo']) ? htmlspecialchars($field_errors['importo']) : ''; ?></div>
                     </div>
            <?php
            endif;
    return ob_get_clean();
    }
}

if (!function_exists('registrationRenderFieldDataScontrino')) {
    function registrationRenderFieldDataScontrino($ctx) {
    $field_data = $ctx['field_data'];
    $form_data = $ctx['form_data'];
    $field_errors = $ctx['field_errors'];
    $fields_enabled = $ctx['fields_enabled'];
    $fidelity_config = $ctx['fidelity_config'];
    $contest_dates = $ctx['contest_dates'];
    $contest_config = $ctx['contest_config'];
    $field_required = $ctx['field_required'];
    $required_mark = $ctx['required_mark'];
    $required_attr = $ctx['required_attr'];
    $receipt_allowed_label = $ctx['receipt_allowed_label'];
    $receipt_accept_attr = $ctx['receipt_accept_attr'];
    ob_start();
if (($fields_enabled['data_scontrino'] ?? true) === true):
                $required = $field_required('data_scontrino');
            ?>
                     <div class="col-12 col-md-6">
                        <label for="data_scontrino" class="form-label">Data scontrino<?php echo $required_mark($required); ?></label>
                        <?php
                            $receiptMinDate = trim((string) ($contest_dates['start_date'] ?? ''));
                            $receiptMaxDate = trim((string) ($contest_dates['end_date'] ?? ''));
                            $todayYmd = date('Y-m-d');
                            if ($receiptMaxDate === '' || $receiptMaxDate > $todayYmd) {
                                $receiptMaxDate = $todayYmd;
                            }
                            if ($receiptMinDate !== '' && $receiptMaxDate !== '' && $receiptMinDate > $receiptMaxDate) {
                                $receiptMinDate = $receiptMaxDate;
                            }
                            $receiptDateValue = trim((string) ($form_data['data_scontrino'] ?? ''));
                            if ($receiptDateValue !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $receiptDateValue)) {
                                $receiptDateValue = '';
                            }
                        ?>
                        <input type="text"
                               class="form-control <?php echo isset($field_errors['data_scontrino']) ? 'is-invalid' : ''; ?>"
                               id="data_scontrino"
                               name="data_scontrino"
                               inputmode="numeric"
                               autocomplete="off"
                               placeholder="GG/MM/AAAA"
                               data-min="<?php echo htmlspecialchars($receiptMinDate, ENT_QUOTES, 'UTF-8'); ?>"
                               data-max="<?php echo htmlspecialchars($receiptMaxDate, ENT_QUOTES, 'UTF-8'); ?>"
                               min="<?php echo htmlspecialchars($receiptMinDate, ENT_QUOTES, 'UTF-8'); ?>"
                               max="<?php echo htmlspecialchars($receiptMaxDate, ENT_QUOTES, 'UTF-8'); ?>"
                               value="<?php echo htmlspecialchars($receiptDateValue); ?>"<?php echo $required_attr($required); ?>>
                         <div class="error-message <?php echo isset($field_errors['data_scontrino']) ? 'd-block' : ''; ?>" id="data_scontrino-error"><?php echo isset($field_errors['data_scontrino']) ? htmlspecialchars($field_errors['data_scontrino']) : ''; ?></div>
                     </div>
                <?php
            endif;
    return ob_get_clean();
    }
}

if (!function_exists('registrationResolveCodicePartecipazioneFieldMeta')) {
    /**
     * @return array{enabled: bool, label: string, exact_length: int|string}|null
     */
    function registrationResolveCodicePartecipazioneFieldMeta(array $fields_enabled, array $contest_config): ?array {
        $codiceEnabled = !empty($fields_enabled['codice_partecipazione']);
        $contestMode = strtolower((string)($contest_config['mode'] ?? ''));
        if (!$codiceEnabled || ($contestMode !== 'instantwin' && $contestMode !== 'estrazione')) {
            return null;
        }
        $label = trim((string)($contest_config['instantwin_codice_label'] ?? ''));
        if ($label === '') {
            $label = REGISTRATION_LABEL_CODICE_PARTECIPAZIONE;
        }
        require_once __DIR__ . '/../app/logic/codice_partecipazione_validation.php';
        $c_pdo_len = null;
        try {
            $c_pdo_len = conn_pdo();
        } catch (Throwable $e) {
            $c_pdo_len = null;
        }
        $codiceExactLength = registration_resolve_codice_partecipazione_length(
            $c_pdo_len instanceof PDO ? $c_pdo_len : null,
            $contest_config
        );
        return [
            'enabled' => true,
            'label' => $label,
            'exact_length' => $codiceExactLength,
        ];
    }
}

if (!function_exists('registrationRenderFieldCodicePartecipazione')) {
    function registrationRenderFieldCodicePartecipazione($ctx) {
    $field_data = $ctx['field_data'];
    $form_data = $ctx['form_data'];
    $field_errors = $ctx['field_errors'];
    $fields_enabled = $ctx['fields_enabled'];
    $contest_config = $ctx['contest_config'];
    $field_required = $ctx['field_required'];
    $required_mark = $ctx['required_mark'];
    $required_attr = $ctx['required_attr'];
    ob_start();
            $codiceMeta = registrationResolveCodicePartecipazioneFieldMeta(
                is_array($fields_enabled) ? $fields_enabled : [],
                is_array($contest_config) ? $contest_config : []
            );
            if ($codiceMeta !== null):
                $label = $codiceMeta['label'];
                $codiceExactLength = $codiceMeta['exact_length'];
                $required = $field_required('codice_partecipazione');
                $codice_help_text = trim((string) ($contest_config['codice_partecipazione_help_text'] ?? ''));
                $codice_help_color = trim((string) ($contest_config['codice_partecipazione_help_text_color'] ?? ''));
                $codice_aria = [];
                if ($codice_help_text !== '') {
                    $codice_aria[] = 'codice-partecipazione-help';
                }
                if (isset($field_errors['codice_partecipazione'])) {
                    $codice_aria[] = 'codice_partecipazione-error';
                }
                $codice_aria_attr = $codice_aria !== [] ? ' aria-describedby="' . htmlspecialchars(implode(' ', $codice_aria), ENT_QUOTES, 'UTF-8') . '"' : '';
            ?>
                    <?php $codice_col_class = (($field_data['size'] ?? 600) === 600) ? 'col-12' : 'col-12 col-md-6'; ?>
                    <div class="<?php echo $codice_col_class; ?>">
                        <label for="codice_partecipazione" class="form-label"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?><?php echo $required_mark($required); ?></label>
                        <input type="text"
                               class="form-control <?php echo isset($field_errors['codice_partecipazione']) ? 'is-invalid' : ''; ?>"
                               id="codice_partecipazione"
                               name="codice_partecipazione"
                               value="<?php echo htmlspecialchars($form_data['codice_partecipazione'] ?? ''); ?>"
                               autocomplete="off"
                               <?php echo $required_attr($required); ?>
                               maxlength="255"
                               data-codice-expected-length="<?php echo htmlspecialchars((string) $codiceExactLength, ENT_QUOTES, 'UTF-8'); ?>"
                               data-field-label="<?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>"
                               <?php echo $codice_aria_attr; ?>>
                        <?php if ($codice_help_text !== ''): ?>
                        <?php echo render_registration_hint_text_html($codice_help_text, 'start', 'codice-partecipazione-help', 'mt-2', $codice_help_color); ?>
                        <?php endif; ?>
                        <div class="error-message <?php echo isset($field_errors['codice_partecipazione']) ? 'd-block' : ''; ?>" id="codice_partecipazione-error"><?php echo isset($field_errors['codice_partecipazione']) ? htmlspecialchars($field_errors['codice_partecipazione']) : ''; ?></div>
                    </div>
                    <?php
            endif;
    return ob_get_clean();
    }
}

if (!function_exists('registrationRenderFieldScontrino')) {
    function registrationRenderFieldScontrino($ctx) {
    $field_data = $ctx['field_data'];
    $form_data = $ctx['form_data'];
    $field_errors = $ctx['field_errors'];
    $fields_enabled = $ctx['fields_enabled'];
    $fidelity_config = $ctx['fidelity_config'];
    $contest_dates = $ctx['contest_dates'];
    $contest_config = $ctx['contest_config'];
    $field_required = $ctx['field_required'];
    $required_mark = $ctx['required_mark'];
    $required_attr = $ctx['required_attr'];
    $receipt_allowed_label = $ctx['receipt_allowed_label'];
    $receipt_accept_attr = $ctx['receipt_accept_attr'];
    ob_start();
if (($fields_enabled['scontrino'] ?? true) === true):
                $required = $field_required('scontrino');
                $max_size_bytes = (int) ($contest_config['max_file_size'] ?? (5 * 1024 * 1024));
                $max_size_mb = max(1, (int) ceil($max_size_bytes / (1024 * 1024)));
                $scontrinoLabelText = 'Foto scontrino (' . $receipt_allowed_label . ', max ' . (string) $max_size_mb . ' MB)';
                $scontrinoEmptyText = htmlspecialchars($scontrinoLabelText . ($required ? ' *' : ''), ENT_QUOTES, 'UTF-8');
                $scontrino_help_text = trim((string) ($contest_config['scontrino_help_text'] ?? ''));
                $scontrino_help_color = trim((string) ($contest_config['scontrino_help_text_color'] ?? ''));
                $scontrino_aria = [];
                if ($scontrino_help_text !== '') {
                    $scontrino_aria[] = 'scontrino-help';
                }
                if (isset($field_errors['scontrino'])) {
                    $scontrino_aria[] = 'scontrino-error';
                }
                $scontrino_aria_attr = $scontrino_aria !== [] ? ' aria-describedby="' . htmlspecialchars(implode(' ', $scontrino_aria), ENT_QUOTES, 'UTF-8') . '"' : '';
                ?>
                     <div class="col-12">
                        <label for="scontrino" class="form-label"><?php echo htmlspecialchars($scontrinoLabelText, ENT_QUOTES, 'UTF-8'); ?><?php echo $required_mark($required); ?></label>
                        <div class="registration-file-input">
                            <input type="file" class="form-control <?php echo isset($field_errors['scontrino']) ? 'is-invalid' : ''; ?>" id="scontrino" name="scontrino" accept="<?php echo htmlspecialchars(implode(',', $receipt_accept_attr), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($form_data['scontrino'] ?? ''); ?>"<?php echo $required_attr($required); ?><?php echo $scontrino_aria_attr; ?>>
                            <span class="registration-file-status" aria-hidden="true" data-empty-text="<?php echo $scontrinoEmptyText; ?>"><?php echo $scontrinoEmptyText; ?></span>
                        </div>
                        <?php if ($scontrino_help_text !== ''): ?>
                        <?php echo render_registration_hint_text_html($scontrino_help_text, 'start', 'scontrino-help', 'mt-2', $scontrino_help_color); ?>
                        <?php endif; ?>
                        <div class="error-message <?php echo isset($field_errors['scontrino']) ? 'd-block' : ''; ?>" id="scontrino-error"><?php echo isset($field_errors['scontrino']) ? htmlspecialchars($field_errors['scontrino']) : ''; ?></div>
                     </div>
                <?php
            endif;
    return ob_get_clean();
    }
}

if (!function_exists('registrationRenderFieldPrivacy')) {
    function registrationRenderFieldPrivacy($ctx) {
    $field_data = $ctx['field_data'];
    $form_data = $ctx['form_data'];
    $field_errors = $ctx['field_errors'];
    $fields_enabled = $ctx['fields_enabled'];
    $fidelity_config = $ctx['fidelity_config'];
    $contest_dates = $ctx['contest_dates'];
    $contest_config = $ctx['contest_config'];
    $field_required = $ctx['field_required'];
    $required_mark = $ctx['required_mark'];
    $required_attr = $ctx['required_attr'];
    $receipt_allowed_label = $ctx['receipt_allowed_label'];
    $receipt_accept_attr = $ctx['receipt_accept_attr'];
    ob_start();
$required = $field_required('privacy');
                $privacy_label = $contest_config['consensi_texts']['privacy'] ?? 'Dichiaro di aver preso visione e di accettare {{PRIVACY_LINK}} *';
                if (!$required) {
                    $privacy_label = preg_replace('/\s*\*+\s*$/', '', $privacy_label);
                }
                $privacy_link_html = '<a href="' . htmlspecialchars($contest_config['privacy_link'] ?? '#', ENT_QUOTES, 'UTF-8') . '" id="linkInformativa" target="_blank" rel="noopener noreferrer">l\'informativa della privacy</a>';
                $privacy_label = str_replace('{{PRIVACY_LINK}}', $privacy_link_html, $privacy_label);
                ?>
                <div class="d-flex flex-column align-items-center">
                    <div class="form-check mb-2 checkbox-align">
                        <input class="form-check-input <?php echo isset($field_errors['privacy']) ? 'is-invalid' : ''; ?>" type="checkbox" id="privacy" name="privacy" value="1"<?php echo $required_attr($required); ?>>
                        <label class="form-check-label" for="privacy">
                            <?php echo $privacy_label; ?>
                        </label>
                    </div>
                    <div class="error-message <?php echo isset($field_errors['privacy']) ? 'd-block' : ''; ?>" id="privacy-error"><?php echo isset($field_errors['privacy']) ? htmlspecialchars($field_errors['privacy']) : ''; ?></div>
                </div>
                <?php
    return ob_get_clean();
    }
}

if (!function_exists('registrationRenderFieldMarketing')) {
    function registrationRenderFieldMarketing($ctx) {
    $field_data = $ctx['field_data'];
    $form_data = $ctx['form_data'];
    $field_errors = $ctx['field_errors'];
    $fields_enabled = $ctx['fields_enabled'];
    $fidelity_config = $ctx['fidelity_config'];
    $contest_dates = $ctx['contest_dates'];
    $contest_config = $ctx['contest_config'];
    $field_required = $ctx['field_required'];
    $required_mark = $ctx['required_mark'];
    $required_attr = $ctx['required_attr'];
    $receipt_allowed_label = $ctx['receipt_allowed_label'];
    $receipt_accept_attr = $ctx['receipt_accept_attr'];
    ob_start();
$marketing_label = $contest_config['consensi_texts']['marketing'] ?? 'Autorizzo il trattamento dei dati per finalità di marketing (i.e. attività promozionale, commerciale e pubblicitaria)';
                ?>
                <div class="d-flex flex-column align-items-center">
                    <div class="form-check mb-2 checkbox-align">
                        <input class="form-check-input" type="checkbox" id="marketing" name="marketing" value="1" <?php echo ($form_data['marketing'] ?? 0) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="marketing">
                            <?php echo htmlspecialchars($marketing_label, ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                    </div>
                </div>
                <?php
    return ob_get_clean();
    }
}

if (!function_exists('registrationRenderFieldProfilazione')) {
    function registrationRenderFieldProfilazione($ctx) {
    $field_data = $ctx['field_data'];
    $form_data = $ctx['form_data'];
    $field_errors = $ctx['field_errors'];
    $fields_enabled = $ctx['fields_enabled'];
    $fidelity_config = $ctx['fidelity_config'];
    $contest_dates = $ctx['contest_dates'];
    $contest_config = $ctx['contest_config'];
    $field_required = $ctx['field_required'];
    $required_mark = $ctx['required_mark'];
    $required_attr = $ctx['required_attr'];
    $receipt_allowed_label = $ctx['receipt_allowed_label'];
    $receipt_accept_attr = $ctx['receipt_accept_attr'];
    ob_start();
$profilazione_label = $contest_config['consensi_texts']['profilazione'] ?? 'Autorizzo il trattamento dei dati per finalità di profilazione (per ricevere comunicazioni personalizzate in base ai miei gusti e interessi)';
                ?>
                <div class="d-flex flex-column align-items-center">
                    <div class="form-check mb-4 checkbox-align">
                        <input class="form-check-input" type="checkbox" id="profilazione" name="profilazione" value="1" <?php echo ($form_data['profilazione'] ?? 0) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="profilazione">
                            <?php echo htmlspecialchars($profilazione_label, ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                    </div>
                </div>
                <?php
    return ob_get_clean();
    }
}

if (!function_exists('registrationPrepareStandardFieldCtx')) {
    function registrationPrepareStandardFieldCtx(array $ctx) {
        $fields_enabled = $ctx['fields_enabled'] ?? [];
        $contest_config = $ctx['contest_config'] ?? null;
        $ctx['field_required'] = static function (string $key) use ($fields_enabled, $contest_config): bool {
            if (($fields_enabled[$key] ?? true) !== true) {
                return false;
            }
            $required = is_array($contest_config ?? null) ? ($contest_config['fields_required'] ?? []) : [];
            if (!is_array($required) || !array_key_exists($key, $required)) {
                return ($fields_enabled[$key] ?? true) === true;
            }
            return filter_var($required[$key], FILTER_VALIDATE_BOOLEAN);
        };
        $ctx['required_mark'] = static function (bool $required): string {
            return $required ? ' *' : '';
        };
        $ctx['required_attr'] = static function (bool $required): string {
            return $required ? ' required' : '';
        };
        $ctx['receipt_allowed_label'] = $GLOBALS['receipt_allowed_label'] ?? 'JPG, PNG o PDF';
        $ctx['receipt_accept_attr'] = $GLOBALS['receipt_accept_attr'] ?? ['.jpg', '.png', '.pdf'];
        return $ctx;
    }
}

if (!function_exists('registrationRenderStandardFieldSwitch')) {
    function registrationRenderStandardFieldSwitch(array $ctx) {
        $ctx = registrationPrepareStandardFieldCtx($ctx);
        $field_name = $ctx['field_name'] ?? '';
        switch ($field_name) {
            case 'nome':
                return registrationRenderFieldNome($ctx);
            case 'cognome':
                return registrationRenderFieldCognome($ctx);
            case 'email':
                return registrationRenderFieldEmail($ctx);
            case 'prefisso_cellulare':
                return registrationRenderFieldPrefissoCellulare($ctx);
            case 'fidelity':
                return registrationRenderFieldFidelity($ctx);
            case 'cap':
                return registrationRenderFieldCap($ctx);
            case 'provincia':
                return registrationRenderFieldProvincia($ctx);
            case 'citta':
                return registrationRenderFieldCitta($ctx);
            case 'indirizzo':
                return registrationRenderFieldIndirizzo($ctx);
            case 'indirizzo_pdv':
                return registrationRenderFieldIndirizzoPdv($ctx);
            case 'importo':
                return registrationRenderFieldImporto($ctx);
            case 'data_scontrino':
                return registrationRenderFieldDataScontrino($ctx);
            case 'codice_partecipazione':
                return registrationRenderFieldCodicePartecipazione($ctx);
            case 'scontrino':
                return registrationRenderFieldScontrino($ctx);
            case 'privacy':
                return registrationRenderFieldPrivacy($ctx);
            case 'marketing':
                return registrationRenderFieldMarketing($ctx);
            case 'profilazione':
                return registrationRenderFieldProfilazione($ctx);
            default:
                return '';
        }
    }
}

// 8. FUNZIONE PER RENDERE UN CAMPO STANDARD (HTML)
function renderStandardField(array $ctx) {
    return registrationRenderStandardFieldSwitch($ctx);
}
// 9. FUNZIONE PER RENDERE I CAMPI IN MODO SEQUENZIALE
if (!function_exists('registrationFindFidelityIndirizzoPositions')) {
    function registrationFindFidelityIndirizzoPositions($fields_grid) {
        $fidelity_real_pos = null;
        $indirizzo_real_pos = null;
        foreach ($fields_grid as $pos => $item) {
            if ($item['type'] !== 'standard') {
                continue;
            }
            if ($item['field']['name'] === 'fidelity' && $fidelity_real_pos === null) {
                $fidelity_real_pos = $pos;
            }
            if ($item['field']['name'] === 'indirizzo' && $indirizzo_real_pos === null) {
                $indirizzo_real_pos = $pos;
            }
        }
        return [$fidelity_real_pos, $indirizzo_real_pos];
    }
}

if (!function_exists('registrationFindSectionLastPos')) {
    function registrationFindSectionLastPos($fields_grid, $anchor_pos, $minOrder, $maxOrder) {
        if ($anchor_pos === null) {
            return null;
        }
        $section_last_pos = $anchor_pos + ($fields_grid[$anchor_pos]['positions_count'] - 1);
        foreach ($fields_grid as $pos => $item) {
            if ($item['type'] !== 'custom' || !isset($item['position_order']) || $pos <= $anchor_pos) {
                continue;
            }
            $order = (int) $item['position_order'];
            if ($minOrder !== null && $order < $minOrder) {
                continue;
            }
            if ($maxOrder !== null && $order > $maxOrder) {
                continue;
            }
            $item_end_pos = $pos + ($item['positions_count'] - 1);
            if ($item_end_pos > $section_last_pos) {
                $section_last_pos = $item_end_pos;
            }
        }
        return $section_last_pos;
    }
}

if (!function_exists('registrationFindPdvAndScontrinoEndPositions')) {
    function registrationFindPdvAndScontrinoEndPositions($fields_grid) {
        $pdv_end_pos = null;
        $last_before_codice = null;
        foreach ($fields_grid as $pos => $item) {
            if ($item['type'] !== 'standard' || !isset($item['field']['name'])) {
                continue;
            }
            $name = $item['field']['name'];
            if ($name === 'indirizzo_pdv') {
                $pdv_end_pos = $pos + ($item['positions_count'] - 1);
            }
            if (in_array($name, ['importo', 'data_scontrino', 'scontrino'], true)) {
                $last_before_codice = $pos + ($item['positions_count'] - 1);
            }
        }
        return [$pdv_end_pos, $last_before_codice];
    }
}

if (!function_exists('registrationFindNamedStandardPosition')) {
    function registrationFindNamedStandardPosition($fields_grid, $fieldName) {
        foreach ($fields_grid as $pos => $item) {
            if ($item['type'] === 'standard' && isset($item['field']['name']) && $item['field']['name'] === $fieldName) {
                return $pos;
            }
        }
        return null;
    }
}

if (!function_exists('registrationFindFirstConsensoPosition')) {
    function registrationFindFirstConsensoPosition($fields_grid) {
        foreach ($fields_grid as $pos => $item) {
            if ($item['type'] === 'standard' && isset($item['field']['name']) && in_array($item['field']['name'], ['privacy', 'marketing', 'profilazione'], true)) {
                return $pos;
            }
        }
        return null;
    }
}

if (!function_exists('registrationBuildSectionSpacerPositions')) {
    function registrationBuildSectionSpacerPositions($section1_last_pos, $section2_last_pos, $pdv_end_pos, $scontrino_end_pos) {
        $section_spacer_positions = [];
        if ($section1_last_pos !== null) {
            $section_spacer_positions[] = $section1_last_pos;
        }
        if ($section2_last_pos !== null) {
            $section_spacer_positions[] = $section2_last_pos;
        }
        if ($pdv_end_pos !== null) {
            $section_spacer_positions[] = $pdv_end_pos;
        }
        if ($scontrino_end_pos !== null) {
            $section_spacer_positions[] = $scontrino_end_pos;
        }
        return $section_spacer_positions;
    }
}

if (!function_exists('registrationFlushCurrentRow')) {
    function registrationFlushCurrentRow(array &$current_row_fields, &$current_row_positions, &$output) {
        if (empty($current_row_fields)) {
            return;
        }
        $output .= '<div class="row registration-form-row registration-form-row--inputs">';
        foreach ($current_row_fields as $field_data) {
            $output .= $field_data['html'];
        }
        $output .= '</div>';
        $current_row_fields = [];
        $current_row_positions = 0;
    }
}

if (!function_exists('registrationSectionSpacerHtml')) {
    function registrationSectionSpacerHtml() {
        return '<div class="row registration-form-section-gap"><div class="col-12 col-md-8 col-lg-6 mx-auto"><div class="section-spacer" aria-hidden="true"></div></div></div>';
    }
}

if (!function_exists('registrationShouldShowSpacerBefore')) {
    function registrationShouldShowSpacerBefore($position, $fidelity_position, $first_pdv_position, $first_consenso_position) {
        if ($fidelity_position !== null && $position === $fidelity_position) {
            return true;
        }
        if ($fidelity_position === null && $first_pdv_position !== null && $position === $first_pdv_position) {
            return true;
        }
        if ($first_consenso_position !== null && $position === $first_consenso_position) {
            return true;
        }
        return false;
    }
}

if (!function_exists('registrationShouldShowSpacerAfterPrevious')) {
    function registrationShouldShowSpacerAfterPrevious($previous_position, $fields_grid, $section_spacer_positions, $section1_last_pos, $show_spacer_before_current) {
        if ($previous_position === null || $show_spacer_before_current) {
            return false;
        }
        $previous_item = $fields_grid[$previous_position];
        $previous_end_pos = $previous_position + ($previous_item['positions_count'] - 1);
        return in_array($previous_end_pos, $section_spacer_positions, true) && $previous_end_pos !== $section1_last_pos;
    }
}

if (!function_exists('registrationRenderStandardFieldFromGridItem')) {
    function registrationRenderStandardFieldFromGridItem($grid_item, array $ctx) {
        return renderStandardField([
            'field_name' => $grid_item['field']['name'],
            'field_data' => $grid_item['field'],
            'form_data' => $ctx['form_data'],
            'field_errors' => $ctx['field_errors'],
            'fields_enabled' => $ctx['fields_enabled'],
            'fidelity_config' => $ctx['fidelity_config'],
            'contest_dates' => $ctx['contest_dates'],
            'contest_config' => $ctx['contest_config'],
        ]);
    }
}

if (!function_exists('registrationAppendLargeGridItem')) {
    function registrationAppendLargeGridItem($grid_item, $position, array $ctx, array &$current_row_fields, &$current_row_positions, &$output) {
        registrationFlushCurrentRow($current_row_fields, $current_row_positions, $output);
        if ($grid_item['type'] === 'custom') {
            $output .= renderCustomField($grid_item['field'], $ctx['custom_field_values'], $ctx['custom_field_errors'], $position);
            return;
        }
        $field_html = registrationRenderStandardFieldFromGridItem($grid_item, $ctx);
        if ($field_html === '') {
            return;
        }
        $pdv_mode = strtolower(trim((string)($ctx['contest_config']['pdv_config']['mode'] ?? 'combinato')));
        $is_pdv_cascata_block = ($grid_item['field']['name'] ?? '') === 'indirizzo_pdv' && $pdv_mode === 'cascata';
        if ($is_pdv_cascata_block) {
            $output .= $field_html;
        } else {
            $is_consenso = in_array(($grid_item['field']['name'] ?? ''), ['privacy', 'marketing', 'profilazione'], true);
            $row_class = $is_consenso
                ? 'row registration-form-row'
                : 'row registration-form-row registration-form-row--inputs';
            $output .= '<div class="' . $row_class . '">' . $field_html . '</div>';
        }
    }
}

if (!function_exists('registrationAppendSmallGridItem')) {
    function registrationAppendSmallGridItem($grid_item, $position, array $ctx, array &$current_row_fields, &$current_row_positions, &$output) {
        if ($current_row_positions >= 2) {
            registrationFlushCurrentRow($current_row_fields, $current_row_positions, $output);
        }
        if ($grid_item['type'] === 'custom') {
            $field_html = renderCustomFieldContent($grid_item['field'], $ctx['custom_field_values'], $ctx['custom_field_errors'], false, $position);
        } else {
            $field_html = registrationRenderStandardFieldFromGridItem($grid_item, $ctx);
        }
        if ($field_html !== '') {
            $current_row_fields[] = ['html' => $field_html];
            $current_row_positions++;
        }
    }
}

if (!function_exists('registrationRenderFormFieldsSequentialImpl')) {
    function registrationRenderFormFieldsSequentialImpl(array $ctx) {
        $fields_grid = $ctx['fields_grid'];
        list($fidelity_real_pos, $indirizzo_real_pos) = registrationFindFidelityIndirizzoPositions($fields_grid);
        // Sezione 1 = Dati personali (fino a indirizzo); sezione 2 = Fidelity
        $dati_section_anchor = $indirizzo_real_pos;
        if ($dati_section_anchor === null) {
            foreach (['citta', 'provincia', 'cap', 'prefisso_cellulare', 'email', 'cognome', 'nome'] as $anchorName) {
                $dati_section_anchor = registrationFindNamedStandardPosition($fields_grid, $anchorName);
                if ($dati_section_anchor !== null) {
                    break;
                }
            }
        }
        $fidelity_order = null;
        if ($fidelity_real_pos !== null) {
            $fidItem = $fields_grid[$fidelity_real_pos] ?? null;
            if (is_array($fidItem)) {
                $fidelity_order = isset($fidItem['position_order'])
                    ? (int) $fidItem['position_order']
                    : (int) ($fidItem['field']['position'] ?? $fidelity_real_pos);
            }
        }
        $section1_last_pos = registrationFindSectionLastPos($fields_grid, $dati_section_anchor, null, $fidelity_order);
        $section2_last_pos = registrationFindSectionLastPos(
            $fields_grid,
            $fidelity_real_pos,
            $fidelity_order,
            $fidelity_order !== null ? $fidelity_order + 1 : null
        );
        list($pdv_end_pos, $scontrino_end_pos) = registrationFindPdvAndScontrinoEndPositions($fields_grid);
        $first_consenso_position = registrationFindFirstConsensoPosition($fields_grid);
        $section_spacer_positions = registrationBuildSectionSpacerPositions($section1_last_pos, $section2_last_pos, $pdv_end_pos, $scontrino_end_pos);
        $first_pdv_position = registrationFindNamedStandardPosition($fields_grid, 'indirizzo_pdv');

        $current_row_fields = [];
        $current_row_positions = 0;
        $previous_position = null;
        $output = '';

        foreach ($fields_grid as $position => $grid_item) {
            $is_large = $grid_item['size'] === 600;
            $show_spacer_before_current = registrationShouldShowSpacerBefore($position, $fidelity_real_pos, $first_pdv_position, $first_consenso_position);
            $show_spacer_after_previous = registrationShouldShowSpacerAfterPrevious($previous_position, $fields_grid, $section_spacer_positions, $section1_last_pos, $show_spacer_before_current);

            if ($show_spacer_before_current) {
                registrationFlushCurrentRow($current_row_fields, $current_row_positions, $output);
                $output .= registrationSectionSpacerHtml();
            }
            if ($show_spacer_after_previous) {
                registrationFlushCurrentRow($current_row_fields, $current_row_positions, $output);
                $output .= registrationSectionSpacerHtml();
            }

            if ($is_large) {
                registrationAppendLargeGridItem($grid_item, $position, $ctx, $current_row_fields, $current_row_positions, $output);
            } else {
                registrationAppendSmallGridItem($grid_item, $position, $ctx, $current_row_fields, $current_row_positions, $output);
            }

            $previous_position = $position;
        }

        registrationFlushCurrentRow($current_row_fields, $current_row_positions, $output);
        return $output;
    }
}

function renderFormFieldsSequential(array $ctx) {
    return registrationRenderFormFieldsSequentialImpl($ctx);
}

// 7. FUNZIONE PER RENDERE UN CAMPO PERSONALIZZATO (solo contenuto, senza riga)
if (!function_exists('registrationRenderCustomFieldContentImpl')) {
    function registrationRenderCustomFieldContentImpl($customField, $custom_field_values, $custom_field_errors, $addPadding = false, $position = null) {
        $data = registrationBuildCustomFieldRenderData($customField, $custom_field_values, $custom_field_errors);
        if ($data === null) {
            return '';
        }

        $paddingClass = $addPadding ? ' custom-field-next-to-email' : '';
        $dataPositionAttr = $position !== null ? ' data-position="' . htmlspecialchars($position) . '"' : '';
        return '<div class="col-12 col-md-6' . $paddingClass . '"' . $dataPositionAttr . '>'
            . registrationRenderCustomFieldInnerHtml($data, false)
            . registrationRenderCustomFieldErrorHtml($data)
            . '</div>';
    }
}

function renderCustomFieldContent($customField, $custom_field_values, $custom_field_errors, $addPadding = false, $position = null) {
    return registrationRenderCustomFieldContentImpl($customField, $custom_field_values, $custom_field_errors, $addPadding, $position);
}
unset($_SESSION['form_data']); // Pulire dopo l'utilizzo
unset($_SESSION['field_errors']); // Pulire dopo l'utilizzo
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
    <h1 class="visually-hidden">Registrazione a <?php echo htmlspecialchars($contest_config['name']); ?></h1>
    <div class="row justify-content-center">
        <div class="col-12 col-lg-11 col-xl-10 col-xxl-9 mx-auto logo-stack brand-layout-page--registration">
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
    <div class="registration-card public-page-container--registration <?php echo $has_brand_visual ? 'logo-attached-card ' : ''; ?>card p-4 p-md-5 mx-auto w-100">
        <?php
        $pages_config = $content_config['pages'] ?? ($content_defaults['pages'] ?? []);
        $form_page = $pages_config['form_page'] ?? [];
        echo render_waiting_page_content(is_array($form_page) ? $form_page : [], $contest_config);
        ?>
                <div class="mx-auto col-12 col-lg-10">
               <?php if (isset($_SESSION['registration_error'])): ?>
               <div class="alert alert-danger mb-4" id="error-messages">
                   <?php echo $_SESSION['registration_error']; ?>
               </div>
               <?php
               unset($_SESSION['registration_error']);
               endif;
               ?>
               <script>
               document.addEventListener('DOMContentLoaded', function () {
                   var err = document.getElementById('error-messages');
                   if (err) {
                       err.scrollIntoView({ behavior: 'smooth', block: 'center' });
                   }
               });
               </script>

            <form method="post" action="invia-registrazione" id="registration-form" enctype="multipart/form-data" novalidate>
                <?php
                // Rendere tutti i campi in modo sequenziale
                echo renderFormFieldsSequential([
                    'fields_grid' => $fields_grid,
                    'custom_field_values' => $custom_field_values,
                    'custom_field_errors' => $custom_field_errors,
                    'form_data' => $form_data,
                    'field_errors' => $field_errors,
                    'fields_enabled' => $fields_enabled,
                    'fidelity_config' => $fidelity_config,
                    'contest_dates' => $contest_dates,
                    'contest_config' => $contest_config,
                ]);
                ?>
                <div class="mx-auto px-2 mt-4 col-12 col-md-6 col-lg-5">
                    <button
                        type="submit"
                        class="btn btn-success btn-lg fw-bold w-100"
                        id="submitBtn"
                        data-gst-track="invia"
                    ><?php echo htmlspecialchars($contest_config['buttons']['primary']['text'] ?? ($contest_config['buttons']['primary_text'] ?? 'PARTECIPA')); ?></button>
                </div>
                <?php echo render_registration_page_button_footer_html(is_array($form_page) ? $form_page : [], $contest_config); ?>
            </form>
            <div class="text-center mt-4"></div>
        </div>
    </div>
        </div>
    </div>
    </main>
    </div>
<?php include_once 'assets/inc/footer.php'; ?>
</div>

<script>
// Configurazione centralizzata dei links
const CONFIG_LINKS = {
    privacy: '<?php echo htmlspecialchars($contest_config['privacy_link'], ENT_QUOTES, 'UTF-8'); ?>',
    regolamento: '<?php echo htmlspecialchars($contest_config['regolamento_link'], ENT_QUOTES, 'UTF-8'); ?>'
};
window.FIELDS_ENABLED = <?php echo json_encode($contest_config['fields_enabled'] ?? []); ?>;
window.FIELDS_REQUIRED = <?php echo json_encode($contest_config['fields_required'] ?? []); ?>;
window.CELLULARE_DIGITS_MIN = <?php echo json_encode(max(6, min(12, (int)($contest_config['cellulare_digits_min'] ?? 6)))); ?>;
window.CELLULARE_DIGITS_MAX = <?php echo json_encode(max($contest_config['cellulare_digits_min'] ?? 6, min(12, (int)($contest_config['cellulare_digits_max'] ?? 12)))); ?>;
const FIELDS_ENABLED = window.FIELDS_ENABLED;
const FIELDS_REQUIRED = window.FIELDS_REQUIRED;

(function () {
    function syncRegistrationFileStatus(input) {
        if (!input) return;
        var wrap = input.closest('.registration-file-input');
        if (!wrap) return;
        var status = wrap.querySelector('.registration-file-status');
        if (!status) return;
        var emptyText = status.getAttribute('data-empty-text') || '';
        if (input.files && input.files.length > 0 && input.files[0] && input.files[0].name) {
            status.textContent = input.files[0].name;
            status.classList.add('is-filled');
        } else {
            status.textContent = emptyText;
            status.classList.remove('is-filled');
        }
    }
    document.addEventListener('change', function (e) {
        var t = e.target;
        if (t && t.matches && t.matches('#registration-form input[type="file"]')) {
            syncRegistrationFileStatus(t);
        }
    });
    document.addEventListener('DOMContentLoaded', function () {
        var fileInputs = document.querySelectorAll('#registration-form .registration-file-input input[type="file"]');
        fileInputs.forEach(syncRegistrationFileStatus);
    });
})();
</script>

<?php if ($form_field_style === 'placeholder'): ?>
<script>
// Stile "Placeholder dentro al campo": applica i placeholder personalizzati dalla
// PHP map (window.FORM_FIELD_PLACEHOLDERS) e, come fallback, usa il testo della
// label corrispondente (stesso testo della label, incluso " *" se obbligatorio).
// Le label restano nel DOM ma vengono nascoste via CSS per gli screen reader.
(function () {
    function cleanLabel(text) {
        if (typeof text !== 'string') return '';
        return text.replace(/\s+/g, ' ').trim();
    }

    function applyPlaceholderStyle() {
        var form = document.getElementById('registration-form');
        if (!form) return;

        // Backup: nascondi via inline style le label (tranne checkbox)
        // cosi' anche se il CSS non fosse stato ancora applicato (cache, race-condition)
        // le label spariscono comunque dal layout. Foto scontrino: info nel controllo file.
        var labels = form.querySelectorAll('label');
        labels.forEach(function (lbl) {
            if (lbl.classList.contains('form-check-label')) return;
            lbl.style.display = 'none';
            lbl.setAttribute('aria-hidden', 'true');
        });

        var customMap = window.FORM_FIELD_PLACEHOLDERS || {};

        function lookupCustom(name, id) {
            if (name && Object.prototype.hasOwnProperty.call(customMap, name)) {
                return String(customMap[name]);
            }
            if (id && Object.prototype.hasOwnProperty.call(customMap, id)) {
                return String(customMap[id]);
            }
            // Supporto per fields con name del tipo "custom_fields[my_id]"
            if (name) {
                var m = name.match(/^custom_fields\[(.+)\]$/);
                if (m && Object.prototype.hasOwnProperty.call(customMap, 'custom_' + m[1])) {
                    return String(customMap['custom_' + m[1]]);
                }
                if (m && Object.prototype.hasOwnProperty.call(customMap, m[1])) {
                    return String(customMap[m[1]]);
                }
            }
            return null;
        }

        function placeholderForField(target, fallbackLabelText) {
            var name = target.getAttribute('name') || '';
            var id = target.id || '';
            var custom = lookupCustom(name, id);
            if (custom !== null && custom.trim() !== '') {
                return custom.trim();
            }
            var fallback = cleanLabel(fallbackLabelText);
            if (fallback && target.hasAttribute('required') && !/\*\s*$/.test(fallback)) {
                fallback = fallback + '*';
            }
            return fallback;
        }

        // Itera su tutti i campi (anche quelli senza una <label for> dedicata)
        var fields = form.querySelectorAll('input, select, textarea');
        fields.forEach(function (target) {
            var tag = (target.tagName || '').toLowerCase();
            var type = (target.getAttribute('type') || '').toLowerCase();

            // Saltare i checkbox/radio, file e date: l'etichetta resta visibile; le date
            // sono gestite da Flatpickr (placeholder sull'altInput in onReady).
            if (type === 'checkbox' || type === 'radio' || type === 'file' || type === 'hidden' || type === 'submit' || type === 'button' || type === 'date') {
                return;
            }

            // Cerca la label associata (per "for" o se dichiara l'input al suo interno)
            var labelText = '';
            if (target.id) {
                var matched = form.querySelector('label[for="' + (window.CSS && CSS.escape ? CSS.escape(target.id) : target.id) + '"]');
                if (matched) {
                    labelText = matched.textContent || '';
                }
            }

            var placeholderText = placeholderForField(target, labelText);
            if (!placeholderText) return;

            if (tag === 'input' || tag === 'textarea') {
                target.setAttribute('placeholder', placeholderText);
                target.setAttribute('aria-label', placeholderText);
            } else if (tag === 'select') {
                target.setAttribute('aria-label', placeholderText);
                var firstOpt = target.options && target.options.length ? target.options[0] : null;
                if (firstOpt && (!firstOpt.value || firstOpt.value === '')) {
                    firstOpt.text = placeholderText;
                }
                if (window.jQuery && jQuery(target).hasClass('select2-hidden-accessible') && !jQuery(target).val()) {
                    var rendered = jQuery(target).next('.select2-container').find('.select2-selection__rendered');
                    if (rendered.length) {
                        rendered.text(placeholderText);
                    }
                }
            }
        });

        // Aggiorna anche i placeholder gia' renderizzati in select2, se presenti.
        if (window.jQuery && jQuery.fn && jQuery.fn.select2) {
            jQuery(form).find('select').each(function () {
                if (jQuery(this).hasClass('select2-hidden-accessible')) {
                    jQuery(this).trigger('change.select2');
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyPlaceholderStyle);
    } else {
        applyPlaceholderStyle();
    }
})();
</script>
<?php endif; ?>

<script>
(function () {
    function initRegistrationDatePickers() {
        if (typeof window.flatpickr !== 'function') {
            return;
        }

        var form = document.getElementById('registration-form');
        if (!form) {
            return;
        }

        var isPlaceholder = document.body.classList.contains('form-style-placeholder');
        var placeholders = window.FORM_FIELD_PLACEHOLDERS || {};
        var calendarTheme = window.FORM_FIELD_CALENDAR_THEME || {};
        var italianLocale = (window.flatpickr.l10ns && window.flatpickr.l10ns.it) ? window.flatpickr.l10ns.it : 'it';

        function applyFlatpickrCalendarTheme(instance) {
            if (!instance || !instance.calendarContainer || !calendarTheme.popupBg) {
                return;
            }
            var cal = instance.calendarContainer;
            var popupBg = calendarTheme.popupBg;
            var targets = cal.querySelectorAll('.flatpickr-months, .flatpickr-innerContainer, .flatpickr-rContainer, .flatpickr-weekdays, .flatpickr-days, .dayContainer');
            cal.style.setProperty('background', popupBg, 'important');
            cal.style.setProperty('border-color', calendarTheme.border || '#dee2e6', 'important');
            cal.style.setProperty('color', calendarTheme.text || '#212529', 'important');
            targets.forEach(function (el) {
                el.style.setProperty('background', popupBg, 'important');
            });
        }

        form.querySelectorAll('input[type="date"], input#data_scontrino, input[data-flatpickr="1"]').forEach(function (input) {
            if (input._flatpickr) {
                return;
            }

            var min = input.getAttribute('data-min') || input.getAttribute('min') || '';
            var max = input.getAttribute('data-max') || input.getAttribute('max') || '';
            var today = (window.TODAY_DATE || '').trim();
            if (today && (!max || max > today)) {
                max = today;
            }
            var id = input.id || '';
            var placeholderText = (id && placeholders[id]) ? String(placeholders[id]) : '';
            var invalidClass = input.classList.contains('is-invalid') ? ' is-invalid' : '';

            var opts = {
                locale: italianLocale,
                dateFormat: 'Y-m-d',
                allowInput: true,
                disableMobile: true,
                monthSelectorType: 'static',
                minDate: min || undefined,
                maxDate: max || undefined,
                onReady: function (selectedDates, dateStr, instance) {
                    applyFlatpickrCalendarTheme(instance);
                    var visible = instance.altInput || instance.input;
                    if (!visible) {
                        return;
                    }
                    if (isPlaceholder && placeholderText) {
                        visible.setAttribute('placeholder', placeholderText);
                        visible.setAttribute('aria-label', placeholderText);
                    }
                    visible.classList.add('form-control');
                    if (invalidClass) {
                        visible.classList.add('is-invalid');
                    }
                },
                onOpen: function (selectedDates, dateStr, instance) {
                    applyFlatpickrCalendarTheme(instance);
                },
                onChange: function () {
                    if (input.id) {
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                },
                onClose: function () {
                    if (input.id) {
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            };

            if (isPlaceholder || input.id === 'data_scontrino') {
                opts.altInput = true;
                opts.altFormat = 'd/m/Y';
                opts.altInputClass = 'form-control' + invalidClass;
            }

            window.flatpickr(input, opts);
            input.setAttribute('data-flatpickr-init', '1');
        });
    }

    function bootDatePickers() {
        if (document.body.classList.contains('form-style-placeholder')) {
            // applyPlaceholderStyle (se attivo) gira nello stesso tick DOMContentLoaded.
            window.setTimeout(initRegistrationDatePickers, 0);
            return;
        }
        initRegistrationDatePickers();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootDatePickers);
    } else {
        bootDatePickers();
    }
})();
</script>

<script>
// Rimuovere gli attributi title automatici di Select2
document.addEventListener('DOMContentLoaded', function() {
    // Rimuovi title dagli elementi Select2 quando vengono creati
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        // Rimuovi title da tutti gli elementi Select2
                        const select2Elements = node.querySelectorAll ?
                            node.querySelectorAll('.select2-container, .select2-results__option, .select2-selection__rendered') : [];
                        select2Elements.forEach(function(el) {
                            el.removeAttribute('title');
                        });

                        if (node.classList && (node.classList.contains('select2-container') ||
                            node.classList.contains('select2-results__option') ||
                            node.classList.contains('select2-selection__rendered'))) {
                            node.removeAttribute('title');
                        }
                    }
                });
            }
        });
    });

    // Inizia l'osservazione
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    // Rimuovi anche gli attributi title esistenti
    setTimeout(function() {
        const select2Elements = document.querySelectorAll('.select2-container, .select2-results__option, .select2-selection__rendered');
        select2Elements.forEach(function(el) {
            el.removeAttribute('title');
        });
    }, 1000);
});
</script>
<script>
(function() {
    function shouldReload(event) {
        if (event && event.persisted) {
            return true;
        }
        if (window.performance && typeof window.performance.getEntriesByType === 'function') {
            const entries = window.performance.getEntriesByType('navigation');
            if (entries && entries.length > 0) {
                return entries[0].type === 'back_forward';
            }
        }
        return false;
    }

    window.addEventListener('pageshow', function(event) {
        if (shouldReload(event)) {
            window.location.reload();
        }
    });
})();
</script>
</body>
</html>
