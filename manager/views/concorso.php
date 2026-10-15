<?php

// Assicurati che la sessione sia attiva prima di qualsiasi output
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Verifica che solo pm_admin possa accedere a questa pagina
if (empty($_SESSION['user_name']) || $_SESSION['user_name'] !== 'pm_admin') {
    include "_header.php";
    include "res/inc/header.php";
    include "access_denied.php";
    include "_footer.php";
    exit;
}

include "res/inc/utenze.php";
include_once __DIR__ . '/../res/inc/sanitize.inc.php';
include_once __DIR__ . '/concorso/page-container-editor.php';
include_once __DIR__ . '/concorso/page-container-style-editor.php';
require_once __DIR__ . '/concorso/concorso-constants.php';
require_once __DIR__ . '/concorso/concorso-grid-helpers.php';
require_once __DIR__ . '/concorso/concorso-waiting-helpers.php';
require_once __DIR__ . '/../../app/inc/sms_mailup.inc.php';

if (!function_exists('manager_safe_html')) {
    function manager_safe_html(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('manager_preview_url')) {
    function manager_preview_url(string $path, string $baseUrl, string $baseDir): string
    {
        return concorso_manager_preview_url($path, $baseUrl, $baseDir);
    }
}

if (!function_exists('manager_ini_size_to_bytes')) {
    function manager_ini_size_to_bytes(string $size): int
    {
        $size = trim($size);
        if ($size === '') {
            return 0;
        }
        $unit = strtolower($size[strlen($size) - 1]);
        $value = (float) $size;
        switch ($unit) {
            case 'g':
                $value *= 1024;
                // no break
            case 'm':
                $value *= 1024;
                // no break
            case 'k':
                $value *= 1024;
                break;
            default:
                break;
        }
        return (int) round($value);
    }
}

// ============================================
// FUNZIONI UTILITARIE PER IL SISTEMA DI POSIZIONI
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
    'M' => ['name' => 'codice_partecipazione', 'label' => 'Codice partecipazione', 'position' => 13, 'size' => 600],
    'N' => ['name' => 'privacy', 'label' => 'Privacy', 'position' => 14, 'size' => 600],
    'O' => ['name' => 'marketing', 'label' => 'Marketing', 'position' => 15, 'size' => 600],
    'P' => ['name' => 'profilazione', 'label' => 'Profilazione', 'position' => 16, 'size' => 600],
];

// 2. FUNZIONE PER CALCOLARE LA DIMENSIONE DI UN CAMPO PERSONALIZZATO
function get_field_size($customField) {
    $fieldWidth = isset($customField['field_width']) ? trim((string)$customField['field_width']) : 'large';
    return ($fieldWidth === 'small') ? 288 : 600;
}

// 3. FUNZIONE PER CALCOLARE IL NUMERO DI POSIZIONI OCCUPATE
function get_positions_count($size) {
    return ($size === 288) ? 1 : 2;
}

// 4. FUNZIONE PER DETERMINARE LA SEZIONE DI UN CAMPO IN BASE AL SUO NOME
// Sezioni: 1=Dati personali, 2=Fidelity, 3=Punto vendita, 4=Scontrino, 5=Codice partecipazione, 6=Consensi
function get_field_section($field_name) {
    $section1_fields = ['nome', 'cognome', 'email', 'prefisso_cellulare', 'cellulare', 'cap', 'provincia', 'citta', 'indirizzo'];
    $section2_fields = ['fidelity'];
    $section3_fields = ['indirizzo_pdv', 'prov_pdv', 'citta_pdv'];
    $section4_fields = ['importo', 'data_scontrino', 'scontrino'];
    $section5_fields = ['codice_partecipazione'];
    $section6_fields = ['privacy', 'marketing', 'profilazione'];

    $field_name_lower = strtolower($field_name);

    if (in_array($field_name_lower, $section1_fields)) {
        return 1;
    } elseif (in_array($field_name_lower, $section2_fields)) {
        return 2;
    } elseif (in_array($field_name_lower, $section3_fields)) {
        return 3;
    } elseif (in_array($field_name_lower, $section4_fields)) {
        return 4;
    } elseif (in_array($field_name_lower, $section5_fields)) {
        return 5;
    } elseif (in_array($field_name_lower, $section6_fields)) {
        return 6;
    }

    return 1;
}

// parse_position_order / organize_fields_in_grid → concorso-grid-helpers.php


$baseDir = dirname(__DIR__, 2);
$defaultsPath = $baseDir . '/app/config/content.defaults.json';
$contentPath = $baseDir . '/app/config/content.json';

$content_defaults = load_json_config($defaultsPath, []);
$content_current = load_json_config($contentPath, $content_defaults);
$content_raw = load_json_config($contentPath, []);
require_once $baseDir . '/app/inc/functions.php';

$standard_fields_order_current = gst_standard_fields_normalize_order($content_current['standard_fields_order'] ?? null);
$standard_fields_map = gst_apply_standard_fields_order($standard_fields_map, $standard_fields_order_current);
if (!empty($content_current['custom_fields']) && is_array($content_current['custom_fields'])) {
    $content_current['custom_fields'] = gst_migrate_custom_fields_position_orders($content_current['custom_fields']);
}
$iwPrizeDistributionCurrent = contest_normalize_instantwin_prize_distribution(is_array($content_current) ? $content_current : []);
$content_current['instantwin_prize_distribution'] = $iwPrizeDistributionCurrent;
$content_current['instantwin_per_pdv'] = ($iwPrizeDistributionCurrent === 'pdv');
$content_current['instantwin_per_cedi'] = ($iwPrizeDistributionCurrent === 'cedi');
require_once $baseDir . '/app/inc/contest_date_helpers.php';
$preview_base_url = rtrim((string)($content_current['base_url'] ?? $content_defaults['base_url'] ?? ''), '/');
$maybe_decode_waiting_html = 'concorso_maybe_decode_waiting_html';
$waiting_container_default_background = [
    'mode' => 'color',
    'color' => CONCORSO_COLOR_WHITE,
    'image' => '',
    'opacity' => 100,
];
$waiting_container_default_margin = [
    'top' => 0,
    'right' => 0,
    'bottom' => 16,
    'left' => 0,
];
$waiting_container_default_radius = [
    'top' => 8,
    'right' => 8,
    'bottom' => 8,
    'left' => 8,
];
$waiting_container_default_padding = [
    'top' => 20,
    'right' => 20,
    'bottom' => 20,
    'left' => 20,
];
$public_page_container_styles = manager_resolve_public_page_container_styles($content_current, $content_defaults);
$waiting_preview_allowed_tags = '<p><h1><h2><h3><h4><h5><h6><strong><em><u><b><i><br><a><ul><ol><li><span><div><img><font>';
$waiting_preview_name = $content_current['name'] ?? ($content_defaults['name'] ?? 'Concorso');
$waiting_preview_date_format = $content_current['date_format'] ?? ($content_defaults['date_format'] ?? CONCORSO_DATE_FORMAT);
$_waiting_preview_dates = contest_manager_preview_formatted_dates($content_current, $content_defaults, $waiting_preview_date_format);
$waiting_preview_start = $_waiting_preview_dates['start'];
$waiting_preview_end = $_waiting_preview_dates['end'];
$waiting_preview_start_raw = $_waiting_preview_dates['start_raw'];
$waiting_preview_end_raw = $_waiting_preview_dates['end_raw'];
unset($_waiting_preview_dates);
$waiting_preview_min_spend = number_format((float) ($content_current['min_spend'] ?? ($content_defaults['min_spend'] ?? 0)), 2, ',', '.');
$waiting_preview_rules_link = $content_current['rules_link'] ?? ($content_defaults['rules_link'] ?? '#');
$thankyou_messages_defaults = $content_defaults['thankyou_messages'] ?? [];
$thankyou_messages_current = $content_current['thankyou_messages'] ?? $thankyou_messages_defaults;
$resolve_thankyou_message = static function ($value, $fallback): string {
    $value = trim((string) $value);
    if ($value !== '') {
        return $value;
    }
    return (string) $fallback;
};
$thankyou_instantwin_win_html = $resolve_thankyou_message(
    $thankyou_messages_current['instantwin_win_html'] ?? '',
    $thankyou_messages_defaults['instantwin_win_html'] ?? ''
);
$thankyou_instantwin_loss_html = $resolve_thankyou_message(
    $thankyou_messages_current['instantwin_loss_html'] ?? '',
    $thankyou_messages_defaults['instantwin_loss_html'] ?? ''
);
$thankyou_estrazione_html = $resolve_thankyou_message(
    $thankyou_messages_current['estrazione_html'] ?? '',
    $thankyou_messages_defaults['estrazione_html'] ?? ''
);
$email_templates_defaults = $content_defaults['email_templates'] ?? [];
$email_templates_current = $content_current['email_templates'] ?? $email_templates_defaults;
$get_email_template_value = static function (array $current, array $defaults, string $key, string $field): string {
    if (isset($current[$key]) && is_array($current[$key]) && array_key_exists($field, $current[$key])) {
        return (string) $current[$key][$field];
    }
    if (isset($defaults[$key]) && is_array($defaults[$key]) && array_key_exists($field, $defaults[$key])) {
        return (string) $defaults[$key][$field];
    }
    return '';
};
$email_partecipazione_subject = $get_email_template_value($email_templates_current, $email_templates_defaults, 'partecipazione', 'subject');
$email_partecipazione_body = $get_email_template_value($email_templates_current, $email_templates_defaults, 'partecipazione', 'body');
$email_instantwin_confirm_subject = $get_email_template_value($email_templates_current, $email_templates_defaults, 'instantwin_confirm', 'subject');
$email_instantwin_confirm_body = $get_email_template_value($email_templates_current, $email_templates_defaults, 'instantwin_confirm', 'body');
$email_instantwin_loss_subject = $get_email_template_value($email_templates_current, $email_templates_defaults, 'instantwin_loss', 'subject');
$email_instantwin_loss_body = $get_email_template_value($email_templates_current, $email_templates_defaults, 'instantwin_loss', 'body');
$email_instantwin_prize_subject = $get_email_template_value($email_templates_current, $email_templates_defaults, 'instantwin_prize', 'subject');
$email_instantwin_prize_body = $get_email_template_value($email_templates_current, $email_templates_defaults, 'instantwin_prize', 'body');
$email_instantwin_cancel_subject = $get_email_template_value($email_templates_current, $email_templates_defaults, 'instantwin_cancel', 'subject');
$email_instantwin_cancel_body = $get_email_template_value($email_templates_current, $email_templates_defaults, 'instantwin_cancel', 'body');
$get_email_sending_flag = static function (array $current, array $defaults, string $key): bool {
    if (isset($current[$key])) {
        return (bool) $current[$key];
    }
    if (isset($defaults[$key])) {
        return (bool) $defaults[$key];
    }
    return false;
};
$email_sending_partecipazione = $get_email_sending_flag($content_current['email_sending'] ?? [], $content_defaults['email_sending'] ?? [], 'partecipazione');
$email_sending_instantwin_confirm = $get_email_sending_flag($content_current['email_sending'] ?? [], $content_defaults['email_sending'] ?? [], 'instantwin_confirm');
$email_sending_instantwin_loss = $get_email_sending_flag($content_current['email_sending'] ?? [], $content_defaults['email_sending'] ?? [], 'instantwin_loss');
$email_sending_instantwin_cancel = $get_email_sending_flag($content_current['email_sending'] ?? [], $content_defaults['email_sending'] ?? [], 'instantwin_cancel');
$email_sending_instantwin_prize = $get_email_sending_flag($content_current['email_sending'] ?? [], $content_defaults['email_sending'] ?? [], 'instantwin_prize');
$sms_enabled = (bool) (($content_current['sms']['enabled'] ?? ($content_defaults['sms']['enabled'] ?? false)));
$sms_provider = (string) (($content_current['sms']['provider'] ?? ($content_defaults['sms']['provider'] ?? 'mailup')));
$sms_campaign_code = trim((string) (($content_current['sms']['campaign_code'] ?? ($content_defaults['sms']['campaign_code'] ?? ''))));
if ($sms_provider === '') {
    $sms_provider = 'mailup';
}
require_once __DIR__ . '/../../app/logic/mailup_sms_env.php';
require_once __DIR__ . '/../../app/logic/smtp_env.php';
$content_current = smtp_env_maybe_migrate_json($content_current, $contentPath);
smtp_env_migrate_active_into_general_if_needed();
$sms_env_fields = mailup_sms_env_schema();
$smsEnvBundle = mailup_sms_env_values_for_form();
$sms_env_values = $smsEnvBundle['values'];
$sms_env_set = $smsEnvBundle['set'];
$smtpProfilesUi = smtp_env_profiles_bundle_for_ui();
$smtp_active_profile = (string) ($smtpProfilesUi['active_profile'] ?? 'general');
$smtpProfileForm = smtp_env_read_profile_values($smtp_active_profile);
$smtp_env_values = $smtpProfileForm['values'];
$smtp_env_values['SMTP_PASSWORD'] = '';
$smtp_env_set = $smtpProfileForm['set'];
$smtp_profiles_json = json_encode(
    [
        'active_profile' => $smtp_active_profile,
        'labels' => $smtpProfilesUi['labels'] ?? [],
        'profiles' => $smtpProfilesUi['profiles'] ?? [],
    ],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
if (!is_string($smtp_profiles_json)) {
    $smtp_profiles_json = '{"active_profile":"general","labels":{},"profiles":{}}';
}
$sms_sending_partecipazione = $get_email_sending_flag($content_current['sms_sending'] ?? [], $content_defaults['sms_sending'] ?? [], 'partecipazione');
$sms_sending_instantwin_confirm = $get_email_sending_flag($content_current['sms_sending'] ?? [], $content_defaults['sms_sending'] ?? [], 'instantwin_confirm');
$sms_sending_instantwin_loss = $get_email_sending_flag($content_current['sms_sending'] ?? [], $content_defaults['sms_sending'] ?? [], 'instantwin_loss');
$sms_sending_instantwin_cancel = $get_email_sending_flag($content_current['sms_sending'] ?? [], $content_defaults['sms_sending'] ?? [], 'instantwin_cancel');
$sms_sending_instantwin_prize = $get_email_sending_flag($content_current['sms_sending'] ?? [], $content_defaults['sms_sending'] ?? [], 'instantwin_prize');
$get_sms_template_value = static function (array $current, array $defaults, string $type): string {
    $raw = '';
    if (isset($current[$type]) && is_string($current[$type])) {
        $raw = $current[$type];
    } elseif (isset($defaults[$type]) && is_string($defaults[$type])) {
        $raw = $defaults[$type];
    }
    return gst_sms_sanitize_template_text($raw, null);
};
$sms_templates_defaults = $content_defaults['sms_templates'] ?? [];
$sms_templates_current = $content_current['sms_templates'] ?? $sms_templates_defaults;
if (!is_array($sms_templates_defaults)) {
    $sms_templates_defaults = [];
}
if (!is_array($sms_templates_current)) {
    $sms_templates_current = $sms_templates_defaults;
}
$sms_template_partecipazione = $get_sms_template_value($sms_templates_current, $sms_templates_defaults, 'partecipazione');
$sms_template_instantwin_confirm = $get_sms_template_value($sms_templates_current, $sms_templates_defaults, 'instantwin_confirm');
$sms_template_instantwin_loss = $get_sms_template_value($sms_templates_current, $sms_templates_defaults, 'instantwin_loss');
$sms_template_instantwin_cancel = $get_sms_template_value($sms_templates_current, $sms_templates_defaults, 'instantwin_cancel');
$sms_template_instantwin_prize = $get_sms_template_value($sms_templates_current, $sms_templates_defaults, 'instantwin_prize');
$email_bcc_defaults = $content_defaults['email_bcc']['instantwin_prize'] ?? [];
$email_bcc_current = $content_current['email_bcc']['instantwin_prize'] ?? $email_bcc_defaults;
$email_bcc_instantwin_prize_enabled = (bool) ($email_bcc_current['enabled'] ?? ($email_bcc_defaults['enabled'] ?? false));
$email_bcc_instantwin_prize_use_pdv_email = (bool) ($email_bcc_current['use_pdv_email'] ?? ($email_bcc_defaults['use_pdv_email'] ?? true));
$email_bcc_instantwin_prize_global = trim((string) ($email_bcc_current['global'] ?? ($email_bcc_defaults['global'] ?? '')));
$email_bcc_instantwin_prize_by_insegna = $email_bcc_current['by_insegna'] ?? ($email_bcc_defaults['by_insegna'] ?? []);
if (!is_array($email_bcc_instantwin_prize_by_insegna)) {
    $email_bcc_instantwin_prize_by_insegna = [];
}
$email_bcc_instantwin_prize_by_insegna_lines = [];
foreach ($email_bcc_instantwin_prize_by_insegna as $insegnaKey => $emailsValue) {
    $insegnaLabel = trim((string) $insegnaKey);
    if ($insegnaLabel === '') {
        continue;
    }
    $emailsText = trim((string) $emailsValue);
    if ($emailsText === '') {
        continue;
    }
    $email_bcc_instantwin_prize_by_insegna_lines[] = $insegnaLabel . '=' . $emailsText;
}
$email_bcc_instantwin_prize_by_insegna_text = implode("\n", $email_bcc_instantwin_prize_by_insegna_lines);
$email_bcc_insegna_suggestions = [];
$email_voucher_block_style_defaults = $content_defaults['email_voucher_block_style'] ?? ['border_color' => CONCORSO_COLOR_SUCCESS, 'bg_color' => '#f0f0f0', 'text_color' => CONCORSO_COLOR_SUCCESS];
$email_voucher_block_style_current = $content_current['email_voucher_block_style'] ?? $email_voucher_block_style_defaults;
$email_voucher_block_border_color = $email_voucher_block_style_current['border_color'] ?? $email_voucher_block_style_defaults['border_color'];
$email_voucher_block_bg_color = $email_voucher_block_style_current['bg_color'] ?? $email_voucher_block_style_defaults['bg_color'];
$email_voucher_block_text_color = $email_voucher_block_style_current['text_color'] ?? $email_voucher_block_style_defaults['text_color'];
$email_voucher_code_display = strtolower(trim((string) ($content_current['email_voucher_code_display'] ?? ($content_defaults['email_voucher_code_display'] ?? 'text'))));
if (!in_array($email_voucher_code_display, ['text', 'barcode', 'both'], true)) {
    $email_voucher_code_display = 'text';
}
$contestMode = strtolower($content_current['contest_mode'] ?? ($content_defaults['contest_mode'] ?? 'estrazione'));
$contestMode = in_array($contestMode, ['estrazione', 'instantwin'], true) ? $contestMode : 'estrazione';
$premi_for_email_images = [];
$prize_email_templates_current = is_array($content_current['prize_email_templates'] ?? null)
    ? $content_current['prize_email_templates']
    : (is_array($content_defaults['prize_email_templates'] ?? null) ? $content_defaults['prize_email_templates'] : []);
if ($contestMode === 'instantwin') {
    $pdo_premi_email = (isset($connection) && $connection instanceof PDO) ? $connection : (function_exists('conn_pdo') ? conn_pdo() : null);
    if ($pdo_premi_email instanceof PDO) {
        try {
            $premi_for_email_images = $pdo_premi_email->query(
                'SELECT id, valore, tipo_premio FROM premi ORDER BY valore DESC, id ASC'
            )->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            $premi_for_email_images = [];
        }
    }
}
if (!function_exists('prize_email_template_key')) {
    require_once __DIR__ . CONCORSO_PRIZE_IMAGE_GENERATOR;
}
$auto_prize_with_receipt = (bool)($content_current['instantwin_auto_prize_with_receipt'] ?? ($content_defaults['instantwin_auto_prize_with_receipt'] ?? false));
$scontrino_enabled_config = (bool)($content_current['fields_enabled']['scontrino'] ?? ($content_defaults['fields_enabled']['scontrino'] ?? true));
$auto_prize_can_show = ($contestMode === 'instantwin') && $scontrino_enabled_config;
$instantwin_prize_flow = 'auto_immediate';
if ($auto_prize_can_show) {
    $instantwin_prize_flow = ($auto_prize_with_receipt || !$email_sending_instantwin_confirm)
        ? 'auto_immediate'
        : 'manager_confirm';
}
$instantwin_prize_flow_warning = $auto_prize_can_show
    && !$auto_prize_with_receipt
    && !$email_sending_instantwin_confirm
    && $email_sending_instantwin_prize;
$preview_prize_from_db = null;
$preview_prize_type_from_db = null;
$preview_pdo = null;
if (isset($connection) && $connection instanceof PDO) {
    $preview_pdo = $connection;
} elseif (function_exists('conn_pdo')) {
    try {
        $preview_pdo = conn_pdo();
    } catch (Exception $e) {
        $preview_pdo = null;
    }
}
if ($preview_pdo instanceof PDO) {
    try {
        $stmt = $preview_pdo->prepare('SELECT valore, tipo_premio FROM premi WHERE IS_ACTIVE = 1 ORDER BY ID ASC LIMIT 1');
        if ($stmt && $stmt->execute()) {
            $previewPrizeRow = $stmt->fetch(PDO::FETCH_ASSOC);
            if (is_array($previewPrizeRow)) {
                $preview_prize_from_db = $previewPrizeRow['valore'] ?? null;
                $preview_prize_type_from_db = $previewPrizeRow['tipo_premio'] ?? null;
            }
        }
    } catch (Exception $e) {
        $preview_prize_from_db = null;
    }
    try {
        $stmtInsegne = $preview_pdo->query("SELECT DISTINCT insegna FROM punti_vendita WHERE insegna IS NOT NULL AND TRIM(insegna) <> '' ORDER BY insegna ASC");
        if ($stmtInsegne) {
            foreach ($stmtInsegne->fetchAll(PDO::FETCH_COLUMN) as $insegnaValue) {
                $insegnaLabel = trim((string) $insegnaValue);
                if ($insegnaLabel !== '') {
                    $email_bcc_insegna_suggestions[] = $insegnaLabel;
                }
            }
        }
    } catch (Exception $e) {
        $email_bcc_insegna_suggestions = [];
    }
}
if ($preview_prize_from_db !== null && $preview_prize_from_db !== '') {
    $thankyou_preview_prize_amount = number_format((float) $preview_prize_from_db, 2, ',', '.');
} else {
    $thankyou_preview_prize_amount = number_format((float) ($content_current['prize_total'] ?? ($content_defaults['prize_total'] ?? 0)), 2, ',', '.');
}
$thankyou_preview_prize_win_amount = $thankyou_preview_prize_amount;
$thankyou_preview_prize_type = trim((string) ($preview_prize_type_from_db ?? ''));
if ($thankyou_preview_prize_type === '') {
    $thankyou_preview_prize_type = 'Premio';
}
$email_preview_name = 'Mario Rossi';
$email_preview_prize_amount = $thankyou_preview_prize_win_amount;
$email_preview_voucher = 'ABC123';
$contest_mode_preview = strtolower(trim((string) ($content_current['contest_mode'] ?? ($content_defaults['contest_mode'] ?? 'estrazione'))));
if (!in_array($contest_mode_preview, ['estrazione', 'instantwin'], true)) {
    $contest_mode_preview = 'estrazione';
}
$apply_thankyou_preview_placeholders = static function (string $value) use (
    $waiting_preview_name,
    $waiting_preview_start,
    $waiting_preview_end,
    $waiting_preview_min_spend,
    $waiting_preview_rules_link,
    $thankyou_preview_prize_amount,
    $thankyou_preview_prize_win_amount,
    $thankyou_preview_prize_type
): string {
    $placeholders = [
        CONCORSO_PH_CONTEST_NAME => $waiting_preview_name,
        CONCORSO_PH_START_DATE => $waiting_preview_start,
        CONCORSO_PH_END_DATE => $waiting_preview_end,
        CONCORSO_PH_MIN_SPEND => $waiting_preview_min_spend,
        CONCORSO_PH_REGOLAMENTO => $waiting_preview_rules_link,
        '{{PRIZE_AMOUNT}}' => $thankyou_preview_prize_amount,
        '{{PRIZE_WIN_AMOUNT}}' => $thankyou_preview_prize_win_amount,
        '{{PRIZE_TYPE}}' => $thankyou_preview_prize_type,
    ];
    return contest_replace_placeholders($value, $placeholders);
};

$fallback_thankyou_preview_message = '<p class="fs-5 fw-semibold mb-3">La tua partecipazione a "' . $waiting_preview_name . '" e stata registrata con successo.</p>'
    . '<p class="mb-1">Riceverai comunicazioni all\'indirizzo email indicato.</p>'
    . '<p class="mb-0">Per ogni dettaglio consulta il regolamento completo.</p>';
$thankyou_preview_message_win_source = $thankyou_instantwin_win_html;
if ($thankyou_preview_message_win_source === '') {
    $thankyou_preview_message_win_source = $fallback_thankyou_preview_message;
}
$thankyou_preview_message_win = $apply_thankyou_preview_placeholders($thankyou_preview_message_win_source);

$thankyou_preview_message_loss_source = $thankyou_instantwin_loss_html;
if ($thankyou_preview_message_loss_source === '') {
    $thankyou_preview_message_loss_source = $fallback_thankyou_preview_message;
}
$thankyou_preview_message_loss = $apply_thankyou_preview_placeholders($thankyou_preview_message_loss_source);

$thankyou_preview_message_estrazione_source = $thankyou_estrazione_html;
if ($thankyou_preview_message_estrazione_source === '') {
    $thankyou_preview_message_estrazione_source = $fallback_thankyou_preview_message;
}
$thankyou_preview_message_estrazione = $apply_thankyou_preview_placeholders($thankyou_preview_message_estrazione_source);

$thankyou_preview_message = $contest_mode_preview === 'instantwin'
    ? $thankyou_preview_message_loss
    : $thankyou_preview_message_estrazione;
$thankyou_preview_actions = '<a class="btn btn-primary btn-lg text-uppercase fw-semibold" href="' . $waiting_preview_rules_link . '" target="_blank" rel="noopener noreferrer">REGOLAMENTO</a>'
    . '<a class="btn btn-outline-primary btn-lg text-uppercase fw-semibold" href="#">PARTECIPA DI NUOVO</a>';
$_waitingBtnPreview = resolve_button_colors($content_current);
$waiting_preview_primary = (string) ($_waitingBtnPreview['primary']['bg'] ?? CONCORSO_COLOR_PRIMARY);
$waiting_preview_primary_hover = (string) ($_waitingBtnPreview['primary']['hover_bg'] ?? $waiting_preview_primary);
$waiting_preview_primary_text = (string) ($_waitingBtnPreview['primary']['text_color'] ?? CONCORSO_COLOR_WHITE);
$waiting_preview_secondary = (string) ($_waitingBtnPreview['secondary']['bg'] ?? CONCORSO_COLOR_SECONDARY);
$waiting_preview_secondary_hover = (string) ($_waitingBtnPreview['secondary']['hover_bg'] ?? $waiting_preview_secondary);
$waiting_preview_secondary_text = (string) ($_waitingBtnPreview['secondary']['text_color'] ?? CONCORSO_COLOR_WHITE);
unset($_waitingBtnPreview);
$waiting_preview_text = $content_current['colors']['text'] ?? ($content_defaults['colors']['text'] ?? CONCORSO_COLOR_TEXT);
$waiting_preview_ctx = [
    'content_current' => $content_current,
    'content_defaults' => $content_defaults,
    'content_raw' => $content_raw,
    'waiting_container_default_background' => $waiting_container_default_background,
    'waiting_container_default_margin' => $waiting_container_default_margin,
    'waiting_container_default_radius' => $waiting_container_default_radius,
    'waiting_preview_allowed_tags' => $waiting_preview_allowed_tags,
    'waiting_preview_name' => $waiting_preview_name,
    'waiting_preview_start' => $waiting_preview_start,
    'waiting_preview_end' => $waiting_preview_end,
    'waiting_preview_min_spend' => $waiting_preview_min_spend,
    'waiting_preview_rules_link' => $waiting_preview_rules_link,
];
$build_waiting_page_state = static function (string $page_key) use ($waiting_preview_ctx): array {
    return concorso_build_waiting_page_state($page_key, $waiting_preview_ctx);
};

$waiting_page_state = $build_waiting_page_state('waiting_page');
$waiting_page_align_value = $waiting_page_state['align_value'];
$waiting_page_containers = $waiting_page_state['containers'];
$waiting_preview_containers = $waiting_page_state['preview_containers'];

$participation_page_state = $build_waiting_page_state('participation_page');
$participation_page_align_value = $participation_page_state['align_value'];
$participation_page_containers = $participation_page_state['containers'];
$participation_page_preview_containers = $participation_page_state['preview_containers'];

$waiting_page_ended_state = $build_waiting_page_state('waiting_page_ended');
$waiting_page_ended_align_value = $waiting_page_ended_state['align_value'];
$waiting_page_ended_containers = $waiting_page_ended_state['containers'];
$waiting_preview_ended_containers = $waiting_page_ended_state['preview_containers'];

$form_page_state = $build_waiting_page_state('form_page');
$form_page_align_value = $form_page_state['align_value'];
$form_page_containers = $form_page_state['containers'];
$form_preview_containers = $form_page_state['preview_containers'];

$help_page_state = $build_waiting_page_state('help_page');
$help_page_align_value = $help_page_state['align_value'];
$help_page_containers = $help_page_state['containers'];
$help_page_preview_containers = $help_page_state['preview_containers'];

$thankyou_instantwin_win_state = $build_waiting_page_state('thankyou_instantwin_win_page');
$thankyou_instantwin_win_align_value = $thankyou_instantwin_win_state['align_value'];
$thankyou_instantwin_win_containers = $thankyou_instantwin_win_state['containers'];
$thankyou_instantwin_win_preview_containers = $thankyou_instantwin_win_state['preview_containers'];
$thankyou_instantwin_win_title = $thankyou_instantwin_win_state['title'] ?? CONCORSO_THANKYOU_TITLE;

$thankyou_instantwin_loss_state = $build_waiting_page_state('thankyou_instantwin_loss_page');
$thankyou_instantwin_loss_align_value = $thankyou_instantwin_loss_state['align_value'];
$thankyou_instantwin_loss_containers = $thankyou_instantwin_loss_state['containers'];
$thankyou_instantwin_loss_preview_containers = $thankyou_instantwin_loss_state['preview_containers'];
$thankyou_instantwin_loss_title = $thankyou_instantwin_loss_state['title'] ?? CONCORSO_THANKYOU_TITLE;

$thankyou_instantwin_quiz_failed_state = $build_waiting_page_state('thankyou_instantwin_quiz_failed_page');
$thankyou_instantwin_quiz_failed_align_value = $thankyou_instantwin_quiz_failed_state['align_value'];
$thankyou_instantwin_quiz_failed_containers = $thankyou_instantwin_quiz_failed_state['containers'];
$thankyou_instantwin_quiz_failed_preview_containers = $thankyou_instantwin_quiz_failed_state['preview_containers'];
$thankyou_instantwin_quiz_failed_title = $thankyou_instantwin_quiz_failed_state['title'] ?? CONCORSO_THANKYOU_TITLE;

$thankyou_estrazione_state = $build_waiting_page_state('thankyou_estrazione_page');
$thankyou_estrazione_align_value = $thankyou_estrazione_state['align_value'];
$thankyou_estrazione_containers = $thankyou_estrazione_state['containers'];
$thankyou_estrazione_preview_containers = $thankyou_estrazione_state['preview_containers'];
$thankyou_estrazione_title = $thankyou_estrazione_state['title'] ?? CONCORSO_THANKYOU_TITLE;

$thankyou_estrazione_quiz_failed_state = $build_waiting_page_state('thankyou_estrazione_quiz_failed_page');
$thankyou_estrazione_quiz_failed_align_value = $thankyou_estrazione_quiz_failed_state['align_value'];
$thankyou_estrazione_quiz_failed_containers = $thankyou_estrazione_quiz_failed_state['containers'];
$thankyou_estrazione_quiz_failed_preview_containers = $thankyou_estrazione_quiz_failed_state['preview_containers'];
$thankyou_estrazione_quiz_failed_title = $thankyou_estrazione_quiz_failed_state['title'] ?? CONCORSO_THANKYOU_TITLE;

$quiz_page_state = $build_waiting_page_state('quiz_page');
$quiz_page_align_value = $quiz_page_state['align_value'];
$quiz_page_containers = $quiz_page_state['containers'];
$quiz_page_preview_containers = $quiz_page_state['preview_containers'];
$quiz_qblock = is_array($content_current['pages']['quiz_page']['question_block_style'] ?? null)
    ? $content_current['pages']['quiz_page']['question_block_style']
    : (is_array($content_defaults['pages']['quiz_page']['question_block_style'] ?? null)
        ? $content_defaults['pages']['quiz_page']['question_block_style']
        : []);

$quiz_choice_page_state = $build_waiting_page_state('quiz_choice_page');
$quiz_choice_page_align_value = $quiz_choice_page_state['align_value'];
$quiz_choice_page_containers = $quiz_choice_page_state['containers'];
$quiz_choice_page_preview_containers = $quiz_choice_page_state['preview_containers'];
$quiz_choice_labels = is_array($content_current['pages']['quiz_choice_page']['choice_labels'] ?? null)
    ? $content_current['pages']['quiz_choice_page']['choice_labels']
    : (is_array($content_defaults['pages']['quiz_choice_page']['choice_labels'] ?? null)
        ? $content_defaults['pages']['quiz_choice_page']['choice_labels']
        : ['standard' => 'Testo (domande scritte)', 'audio' => 'Audio (ascolto, poi risposte A/B/C/D)', 'submit' => 'Avvia quiz']);

if (empty($thankyou_instantwin_win_containers) && !empty($thankyou_instantwin_win_html)) {
    $thankyou_instantwin_win_html_with_title = '<h1 class="fs-1 fw-bold text-uppercase mb-3">' . CONCORSO_THANKYOU_TITLE . '</h1>' . $thankyou_instantwin_win_html;
    $thankyou_instantwin_win_containers = [[
        'id' => 'container-1',
        'align' => $thankyou_instantwin_win_align_value,
        'html' => $thankyou_instantwin_win_html_with_title,
        'content_type' => 'text',
        'image' => ['src' => '', 'alt' => '', 'width' => ''],
        'background' => $waiting_container_default_background,
        'margin' => $waiting_container_default_margin,
        'radius' => $waiting_container_default_radius,
    ]];
    $thankyou_instantwin_win_preview_html = contest_replace_placeholders($thankyou_instantwin_win_html, [
        CONCORSO_PH_CONTEST_NAME => $waiting_preview_name,
        CONCORSO_PH_START_DATE => $waiting_preview_start,
        CONCORSO_PH_END_DATE => $waiting_preview_end,
        CONCORSO_PH_MIN_SPEND => $waiting_preview_min_spend,
        CONCORSO_PH_REGOLAMENTO => $waiting_preview_rules_link,
        '{{PRIZE_WIN_AMOUNT}}' => '€25,00',
        '{{PRIZE_TYPE}}' => $thankyou_preview_prize_type,
    ]);
    $thankyou_instantwin_win_preview_containers = [[
        'align' => $thankyou_instantwin_win_align_value,
        'html' => '<h1 class="fs-1 fw-bold text-uppercase mb-3">' . CONCORSO_THANKYOU_TITLE . '</h1>' . $thankyou_instantwin_win_preview_html,
        'content_type' => 'text',
        'image' => ['src' => '', 'alt' => '', 'width' => ''],
        'background' => $waiting_container_default_background,
        'margin' => $waiting_container_default_margin,
        'radius' => $waiting_container_default_radius,
    ]];
}

if (empty($thankyou_instantwin_loss_containers) && !empty($thankyou_instantwin_loss_html)) {
    $thankyou_instantwin_loss_html_with_title = '<h1 class="fs-1 fw-bold text-uppercase mb-3">' . CONCORSO_THANKYOU_TITLE . '</h1>' . $thankyou_instantwin_loss_html;
    $thankyou_instantwin_loss_containers = [[
        'id' => 'container-1',
        'align' => $thankyou_instantwin_loss_align_value,
        'html' => $thankyou_instantwin_loss_html_with_title,
        'content_type' => 'text',
        'image' => ['src' => '', 'alt' => '', 'width' => ''],
        'background' => $waiting_container_default_background,
        'margin' => $waiting_container_default_margin,
        'radius' => $waiting_container_default_radius,
    ]];
    $thankyou_instantwin_loss_preview_html = contest_replace_placeholders($thankyou_instantwin_loss_html, [
        CONCORSO_PH_CONTEST_NAME => $waiting_preview_name,
        CONCORSO_PH_START_DATE => $waiting_preview_start,
        CONCORSO_PH_END_DATE => $waiting_preview_end,
        CONCORSO_PH_MIN_SPEND => $waiting_preview_min_spend,
        CONCORSO_PH_REGOLAMENTO => $waiting_preview_rules_link,
    ]);
    $thankyou_instantwin_loss_preview_containers = [[
        'align' => $thankyou_instantwin_loss_align_value,
        'html' => '<h1 class="fs-1 fw-bold text-uppercase mb-3">' . CONCORSO_THANKYOU_TITLE . '</h1>' . $thankyou_instantwin_loss_preview_html,
        'content_type' => 'text',
        'image' => ['src' => '', 'alt' => '', 'width' => ''],
        'background' => $waiting_container_default_background,
        'margin' => $waiting_container_default_margin,
        'radius' => $waiting_container_default_radius,
    ]];
}

if (empty($thankyou_estrazione_containers) && !empty($thankyou_estrazione_html)) {
    $thankyou_estrazione_html_with_title = '<h1 class="fs-1 fw-bold text-uppercase mb-3">' . CONCORSO_THANKYOU_TITLE . '</h1>' . $thankyou_estrazione_html;
    $thankyou_estrazione_containers = [[
        'id' => 'container-1',
        'align' => $thankyou_estrazione_align_value,
        'html' => $thankyou_estrazione_html_with_title,
        'content_type' => 'text',
        'image' => ['src' => '', 'alt' => '', 'width' => ''],
        'background' => $waiting_container_default_background,
        'margin' => $waiting_container_default_margin,
        'radius' => $waiting_container_default_radius,
    ]];
    $thankyou_estrazione_preview_html = contest_replace_placeholders($thankyou_estrazione_html, [
        CONCORSO_PH_CONTEST_NAME => $waiting_preview_name,
        CONCORSO_PH_START_DATE => $waiting_preview_start,
        CONCORSO_PH_END_DATE => $waiting_preview_end,
        CONCORSO_PH_MIN_SPEND => $waiting_preview_min_spend,
        CONCORSO_PH_REGOLAMENTO => $waiting_preview_rules_link,
    ]);
    $thankyou_estrazione_preview_containers = [[
        'align' => $thankyou_estrazione_align_value,
        'html' => '<h1 class="fs-1 fw-bold text-uppercase mb-3">' . CONCORSO_THANKYOU_TITLE . '</h1>' . $thankyou_estrazione_preview_html,
        'content_type' => 'text',
        'image' => ['src' => '', 'alt' => '', 'width' => ''],
        'background' => $waiting_container_default_background,
        'margin' => $waiting_container_default_margin,
        'radius' => $waiting_container_default_radius,
    ]];
}

// Flash messages: se arrivano via GET (?msg / ?error), salvarli in sessione e ripulire l'URL
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['msg']) || isset($_GET['error']))) {
    $msgs = [];
    $errs = [];
    if (isset($_GET['msg'])) {
        if (is_array($_GET['msg'])) {
            foreach ($_GET['msg'] as $m) {
                $m = is_string($m) ? urldecode($m) : '';
                if ($m !== '') {
                    $msgs[] = $m;
                }
            }
        } else {
            $m = is_string($_GET['msg']) ? urldecode($_GET['msg']) : '';
            if ($m !== '') {
                $msgs[] = $m;
            }
        }
    }
    if (isset($_GET['error'])) {
        if (is_array($_GET['error'])) {
            foreach ($_GET['error'] as $e) {
                $e = is_string($e) ? urldecode($e) : '';
                if ($e !== '') {
                    $errs[] = $e;
                }
            }
        } else {
            $e = is_string($_GET['error']) ? urldecode($_GET['error']) : '';
            if ($e !== '') {
                $errs[] = $e;
            }
        }
    }

    if (!empty($msgs)) {
        $_SESSION['concorso_messages'] = array_merge($_SESSION['concorso_messages'] ?? [], $msgs);
    }
    if (!empty($errs)) {
        $_SESSION['concorso_errors'] = array_merge($_SESSION['concorso_errors'] ?? [], $errs);
    }

    // Redirect senza msg/error per evitare che gli alert si ripetano al refresh
    $clean = $_GET;
    unset($clean['msg'], $clean['error']);
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: $_SERVER['REQUEST_URI'];
    $qs = !empty($clean) ? ('?' . http_build_query($clean)) : '';
    header(CONCORSO_LOCATION_HEADER . $path . $qs);
    exit;
}

$messages = $_SESSION['concorso_messages'] ?? [];
$errors = $_SESSION['concorso_errors'] ?? [];
unset($_SESSION['concorso_messages'], $_SESSION['concorso_errors']);

$hasManagerUploadSizeError = false;
$requestExceededPostMax = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES)) {
    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    $postMaxSize = (string) ini_get('post_max_size');
    $postMaxBytes = manager_ini_size_to_bytes($postMaxSize);
    if ($contentLength > 0 && $postMaxBytes > 0 && $contentLength > $postMaxBytes) {
        $requestExceededPostMax = true;
        $hasManagerUploadSizeError = true;
        $errors[] = 'Il caricamento supera il limite massimo totale consentito. Riduci il peso delle immagini e riprova.';
    }
}
$uploadDir = $baseDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;
$uploadBaseUrl = CONCORSO_UPLOADS_CONFIG;

// Media di configurazione concorso (manager) - organizzati per sezione in public/assets/img/*
$logoUploadDir = $baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'logo' . DIRECTORY_SEPARATOR;
$logoBasePath = 'assets/img/logo/';
$headerUploadDir = $baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'header' . DIRECTORY_SEPARATOR;
$headerBasePath = 'assets/img/header/';
$faviconUploadDir = $baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'favicon' . DIRECTORY_SEPARATOR;
$faviconBasePath = 'assets/img/favicon/';
$emailUploadDir = $baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'email' . DIRECTORY_SEPARATOR;
$emailBasePath = 'assets/img/email/';
$backgroundUploadDir = $baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'backgrounds' . DIRECTORY_SEPARATOR;
$backgroundBasePath = 'assets/img/backgrounds/';
$footerUploadDir = $baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'footer' . DIRECTORY_SEPARATOR;
$footerBasePath = 'assets/img/footer/';
$customFontUploadDir = $baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR;
$customFontBasePath = 'assets/fonts/custom/';
$waitingPageUploadDir = $baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'waiting_page' . DIRECTORY_SEPARATOR;
$waitingPageBasePath = 'assets/img/waiting_page/';
$thankyouUploadDir = $baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'thank-you' . DIRECTORY_SEPARATOR;
$thankyouBasePath = 'assets/img/thank-you/';
$quizPageUploadDir = $baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'quiz_page' . DIRECTORY_SEPARATOR;
$quizPageBasePath = 'assets/img/quiz_page/';
$registrationUploadDir = $baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'registration' . DIRECTORY_SEPARATOR;
$registrationBasePath = 'assets/img/registration/';
$pdfUploadDir = $baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'pdf' . DIRECTORY_SEPARATOR;
$pdfBasePath = CONCORSO_ASSETS_PDF;
$configAssetUploadMaxMb = 2;
$configAssetUploadMaxBytes = $configAssetUploadMaxMb * 1024 * 1024;
$configAssetUploadMaxLabel = $configAssetUploadMaxMb . ' MB';

$contestMode = strtolower($content_current['contest_mode'] ?? ($content_defaults['contest_mode'] ?? 'estrazione'));
$contestMode = in_array($contestMode, ['estrazione', 'instantwin'], true) ? $contestMode : 'estrazione';
$maxUploadOptionsMb = range(4, 8);
$maxUploadMinMb = min($maxUploadOptionsMb);
$maxUploadMaxMb = max($maxUploadOptionsMb);
$maxFileSizeBytesCurrent = (int) ($content_current['max_file_size'] ?? ($content_defaults['max_file_size'] ?? 5 * 1024 * 1024));
$maxFileSizeMbSelected = (int) round($maxFileSizeBytesCurrent / (1024 * 1024));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['max_file_size_mb'])) {
    $maxFileSizeMbSelected = (int) $_POST['max_file_size_mb'];
}
$maxFileSizeMbSelected = max($maxUploadMinMb, min($maxUploadMaxMb, $maxFileSizeMbSelected));
$receiptFileTypeOptions = [
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
$receiptAllowedTypesDefault = array_keys($receiptFileTypeOptions);
$normalizeReceiptTypes = static function ($value) use ($receiptAllowedTypesDefault): array {
    $items = array_map('strtolower', (array) $value);
    $items = array_values(array_unique($items));
    return array_values(array_intersect($receiptAllowedTypesDefault, $items));
};
$receiptAllowedTypesSelected = $normalizeReceiptTypes(
    $content_current['receipt_allowed_extensions'] ?? ($content_defaults['receipt_allowed_extensions'] ?? $receiptAllowedTypesDefault)
);
if (empty($receiptAllowedTypesSelected)) {
    $receiptAllowedTypesSelected = $receiptAllowedTypesDefault;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedReceiptTypes = $_POST['receipt_allowed_extensions'] ?? null;
    $postedScontrinoEnabled = !empty($_POST['fields_enabled']['scontrino']);
    if (is_array($postedReceiptTypes)) {
        $receiptAllowedTypesSelected = $normalizeReceiptTypes($postedReceiptTypes);
    } elseif (!$postedScontrinoEnabled) {
        $receiptAllowedTypesSelected = $receiptAllowedTypesSelected ?: $receiptAllowedTypesDefault;
    } else {
        $receiptAllowedTypesSelected = [];
    }
}

// Gestione Dati - Trattamento import CSV (prima del formulario principale)
$errors_gestione = [];
$success_gestione = [];
$step_gestione = $_POST['step'] ?? null;
$is_gestione_dati_request = false;

// Considerare gestione-dati SOLO se step è 'preview'/'import' (o gestione_dati_only) E non è un salvataggio config.
// Se save_config=1 (Salva configurazione), non eseguire mai la validazione CSV così non compaiono "Seleziona tabella" / "Carica CSV".
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['save_config']) && (
    (isset($step_gestione) && in_array($step_gestione, ['preview', 'import'], true))
    || !empty($_POST['gestione_dati_only'])
)) {
    $is_gestione_dati_request = true;
}

if ($is_gestione_dati_request && isset($step_gestione) && in_array($step_gestione, ['preview', 'import'], true)) {
    require_once __DIR__ . '/../res/inc/gestione_dati_context.inc.php';
    $allowedTables = gestione_dati_allowed_tables($content_current, $content_defaults ?? []);

    function normalizeDelimiter_gestione(string $delimiter): string {
        return match ($delimiter) {
            'semicolon' => ';',
            'comma' => ',',
            'tab' => "\t",
            default => ';',
        };
    }
    
    function safeTableName_gestione(string $table): ?string {
        $table = trim($table);
        if ($table === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return null;
        }
        return $table;
    }
    
    function getTableColumns_gestione(PDO $pdo, string $table): array {
        $cols = [];
        $stmt = $pdo->query('DESCRIBE `' . $table . '`');
        foreach ($stmt as $row) {
            if (!empty($row['Field'])) {
                $cols[] = (string)$row['Field'];
            }
        }
        return $cols;
    }
    
    // Passo preview
    if ($step_gestione === 'preview') {
        $selectedTable = (string)($_POST['table'] ?? '');
        $delimiterUi = (string)($_POST['delimiter'] ?? 'semicolon');
        $hasHeader = !empty($_POST['has_header']);
        
        $safeTable = safeTableName_gestione($selectedTable);
        if (!$safeTable || !in_array($safeTable, $allowedTables, true)) {
            $errors_gestione[] = 'Seleziona una tabella valida.';
        }
        
        if (empty($_FILES['csv_file']) || ($_FILES['csv_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $errors_gestione[] = 'Carica un file CSV valido.';
        }
        
        if (!$errors_gestione && $connection) {
            $tmpName = (string)$_FILES['csv_file']['tmp_name'];
            $originalName = (string)$_FILES['csv_file']['name'];
            $upload_dir = __DIR__ . CONCORSO_CSV_UPLOAD_DIR;
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            // Rimuovere il file precedente dalla sessione prima di crearne uno nuovo
            if (isset($_SESSION['csv_import_gestione']['path']) && file_exists((string)$_SESSION['csv_import_gestione']['path'])) {
                @unlink((string)$_SESSION['csv_import_gestione']['path']);
            }
            $dest = $upload_dir . 'gestione_' . bin2hex(random_bytes(8)) . '.csv';
            
            if (!move_uploaded_file($tmpName, $dest)) {
                $errors_gestione[] = 'Impossibile salvare il file caricato.';
            } else {
                $_SESSION['csv_import_gestione'] = [
                    'path' => $dest,
                    'name' => $originalName,
                    'table' => $safeTable,
                    'delimiter' => $delimiterUi,
                    'has_header' => $hasHeader ? 1 : 0,
                ];
            }
        }
    }
    
    // Passo import
    if ($step_gestione === 'import' && $connection) {
        gestione_dati_raise_import_limits();
        $sess = $_SESSION['csv_import_gestione'] ?? null;
        if (!is_array($sess) || empty($sess['path']) || empty($sess['table'])) {
            $errors_gestione[] = 'Sessione mancante: rifai la preview e poi importa.';
        } else {
            $safeTable = safeTableName_gestione((string)$sess['table']);
            if (!$safeTable || !in_array($safeTable, $allowedTables, true)) {
                $errors_gestione[] = 'Tabella non valida nella sessione.';
            }
            if (empty($sess['path']) || !file_exists((string)$sess['path'])) {
                $errors_gestione[] = 'File CSV non trovato. Rifai la preview.';
            }
        }
        
        $mapping = $_POST['mapping'] ?? [];
        if (!$errors_gestione && (!is_array($mapping) || count($mapping) === 0)) {
            $errors_gestione[] = 'Mapping non valido.';
        }
        
        if (!$errors_gestione) {
            $csvPath = (string)$sess['path'];
            $delimiter = normalizeDelimiter_gestione((string)($sess['delimiter'] ?? 'semicolon'));
            $hasHeader = !empty($sess['has_header']);
            $tableColumns = getTableColumns_gestione($connection, $safeTable);
            
            $mapped = [];
            foreach ($mapping as $csvIndex => $colName) {
                $csvIndexInt = (int)$csvIndex;
                $colName = (string)$colName;
                if ($colName === '' || !in_array($colName, $tableColumns, true)) {
                    continue;
                }
                $mapped[$csvIndexInt] = $colName;
            }
            
            if (!$mapped) {
                $errors_gestione[] = 'Seleziona almeno una colonna da importare.';
            } else {
                $columns = array_values(array_unique(array_values($mapped)));
                
                // Détecter les colonnes de type date/datetime/timestamp pour convertir les valeurs vides en NULL
                $dateColumns = [];
                try {
                    $stmtDesc = $connection->query("DESCRIBE `{$safeTable}`");
                    $tableInfo = $stmtDesc->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($tableInfo as $colInfo) {
                        $colType = strtolower($colInfo['Type'] ?? '');
                        $colName = $colInfo['Field'] ?? '';
                        if (in_array($colName, $columns, true) && (
                            strpos($colType, 'date') !== false || 
                            strpos($colType, 'time') !== false ||
                            strpos($colType, 'timestamp') !== false
                        )) {
                            $dateColumns[] = $colName;
                        }
                    }
                } catch (Throwable $e) {
                    // En cas d'erreur, continuer sans détection automatique
                }
                
                // Préparer les vérifications de clés étrangères si ces colonnes sont mappées
                $hasIdCliente = in_array('id_cliente', $columns, true);
                $hasIdCodicePartecipazione = in_array('id_codice_partecipazione', $columns, true);
                $stmtCheckCliente = null;
                $stmtCheckCodicePartecipazione = null;
                if ($hasIdCliente) {
                    $stmtCheckCliente = $connection->prepare('SELECT id_cliente FROM utenti WHERE id_cliente = ? LIMIT 1');
                }
                if ($hasIdCodicePartecipazione) {
                    $stmtCheckCodicePartecipazione = $connection->prepare('SELECT id FROM codici_partecipazione WHERE id = ? LIMIT 1');
                }
                
                // Validazione codici_premio: conteggio totale e per prize_id (deve coincidere con premi.quantita_totale)
                $codiciPremioValidationOk = true;
                if ($safeTable === 'codici_premio') {
                    $prizeIdCsvIndex = null;
                    foreach ($mapped as $idx => $col) {
                        if ($col === 'prize_id') {
                            $prizeIdCsvIndex = $idx;
                            break;
                        }
                    }
                    if ($prizeIdCsvIndex !== null) {
                        $csvCountsByPrize = [];
                        $h = fopen($csvPath, 'r');
                        if ($h) {
                            $ln = 0;
                            while (($r = fgetcsv($h, 0, $delimiter, '"', '\\')) !== false) {
                                $ln++;
                                if ($ln === 1 && $hasHeader) {
                                    continue;
                                }
                                $pid = isset($r[$prizeIdCsvIndex]) ? trim((string)$r[$prizeIdCsvIndex]) : '';
                                $pid = ($pid !== '' && is_numeric($pid)) ? (int)$pid : 0;
                                if ($pid > 0) {
                                    $csvCountsByPrize[$pid] = ($csvCountsByPrize[$pid] ?? 0) + 1;
                                }
                            }
                            fclose($h);
                        }
                        $premiRows = $connection->query("SELECT id, quantita_totale FROM premi WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
                        if (empty($premiRows)) {
                            $errors_gestione[] = "Nessun premio attivo configurato. Configura prima i Premi nel concorso (Dati concorso > Tipi di premi).";
                            $codiciPremioValidationOk = false;
                        }
                        $doTruncate = !empty($_POST['truncate']);
                        foreach ($premiRows ?: [] as $pr) {
                            $pid = (int)$pr['id'];
                            $qt = (int)($pr['quantita_totale'] ?? 0);
                            $csvCount = (int)($csvCountsByPrize[$pid] ?? 0);
                            if ($doTruncate) {
                                if ($csvCount !== $qt) {
                                    $errors_gestione[] = "Premio ID {$pid}: il CSV contiene {$csvCount} codici, ma ne servono {$qt} (quantità configurata in Premi).";
                                    $codiciPremioValidationOk = false;
                                }
                            } else {
                                $stmtExist = $connection->prepare('SELECT COUNT(*) FROM codici_premio WHERE prize_id = ?');
                                $stmtExist->execute([$pid]);
                                $existingCount = (int)$stmtExist->fetchColumn();
                                $afterImport = $existingCount + $csvCount;
                                if ($afterImport !== $qt) {
                                    $errors_gestione[] = "Premio ID {$pid}: dopo l'import avresti {$afterImport} codici (esistenti: {$existingCount}, CSV: {$csvCount}), ma ne servono {$qt}.";
                                    $codiciPremioValidationOk = false;
                                }
                            }
                        }
                        $expectedTotal = array_sum(array_column($premiRows, 'quantita_totale'));
                        $csvTotalWithPrize = array_sum($csvCountsByPrize);
                        if ($doTruncate && $expectedTotal > 0 && $csvTotalWithPrize !== (int)$expectedTotal) {
                            $errors_gestione[] = "Totale: il CSV contiene {$csvTotalWithPrize} codici con prize_id valido, ma la somma delle quantità nei Premi è " . (int)$expectedTotal . ".";
                            $codiciPremioValidationOk = false;
                        }
                    } else {
                        $errors_gestione[] = "Per codici_premio è obbligatorio mappare la colonna prize_id per verificare la coerenza con i Premi.";
                        $codiciPremioValidationOk = false;
                    }
                } else {
                    $codiciPremioValidationOk = true; // non codici_premio, nessun blocco
                }
                
                if ($safeTable === 'codici_premio' && !$codiciPremioValidationOk) {
                    $errors_gestione[] = "Import codici premio bloccato: verifica le quantità per prize_id e riprova.";
                }
                
                // Validazione codici_partecipazione: lunghezza campo codice deve coincidere con Lunghezza codice configurata
                $codiciPartecipazioneValidationOk = true;
                if ($safeTable === 'codici_partecipazione') {
                    $codiceCsvIndex = null;
                    foreach ($mapped as $idx => $col) {
                        if ($col === 'codice') {
                            $codiceCsvIndex = $idx;
                            break;
                        }
                    }
                    if ($codiceCsvIndex !== null) {
                        $expectedLen = 0;
                        $useAutoLen = !empty($content_current['instantwin_codice_length_auto'] ?? ($content_defaults['instantwin_codice_length_auto'] ?? false));
                        if ($useAutoLen && $connection) {
                            try {
                                $stmtLen = $connection->query("SELECT CHAR_LENGTH(codice) AS len FROM codici_partecipazione WHERE isActive = 1 AND codice IS NOT NULL AND codice <> '' LIMIT 1");
                                $rowLen = $stmtLen ? $stmtLen->fetch(PDO::FETCH_ASSOC) : null;
                                $expectedLen = (int) ($rowLen['len'] ?? 0);
                            } catch (Throwable $e) {
                                $expectedLen = 0;
                            }
                        }
                        if ($expectedLen < 1) {
                            $expectedLen = (int) ($content_current['instantwin_codice_length'] ?? ($content_defaults['instantwin_codice_length'] ?? 12));
                        }
                        if ($expectedLen < 1) {
                            $expectedLen = 12;
                        } elseif ($expectedLen > 255) {
                            $expectedLen = 255;
                        }
                        $h = fopen($csvPath, 'r');
                        if ($h) {
                            $ln = 0;
                            $lenErrors = [];
                            while (($r = fgetcsv($h, 0, $delimiter, '"', '\\')) !== false) {
                                $ln++;
                                if ($ln === 1 && $hasHeader) {
                                    continue;
                                }
                                $codiceVal = isset($r[$codiceCsvIndex]) ? trim((string)$r[$codiceCsvIndex]) : '';
                                if ($codiceVal !== '') {
                                    $actualLen = mb_strlen($codiceVal, 'UTF-8');
                                    if ($actualLen !== $expectedLen) {
                                        $lenErrors[] = "Riga {$ln}: il codice ha {$actualLen} caratteri, ma deve averne esattamente {$expectedLen}.";
                                        $codiciPartecipazioneValidationOk = false;
                                    }
                                }
                            }
                            fclose($h);
                            foreach (array_slice($lenErrors, 0, 20) as $err) {
                                $errors_gestione[] = $err;
                            }
                            if (count($lenErrors) > 20) {
                                $errors_gestione[] = "... e altri " . (count($lenErrors) - 20) . " codici con lunghezza errata.";
                            }
                        }
                    } else {
                        $errors_gestione[] = "Per codici_partecipazione è obbligatorio mappare la colonna codice per verificare la lunghezza.";
                        $codiciPartecipazioneValidationOk = false;
                    }
                }
                if ($safeTable === 'codici_partecipazione' && !$codiciPartecipazioneValidationOk) {
                    $errors_gestione[] = "Import codici partecipazione bloccato: verifica che ogni codice abbia la lunghezza configurata (Lunghezza codice in Campi form) e riprova.";
                }
                
                $inserted = 0;
                $failed = 0;
                $failRows = [];
                
                $skipImportForValidation = ($safeTable === 'codici_premio' && !$codiciPremioValidationOk)
                    || ($safeTable === 'codici_partecipazione' && !$codiciPartecipazioneValidationOk);
                
                if (!$skipImportForValidation) {
                $connection->beginTransaction();
                $fkChecksDisabled = false;
                try {
                    // Désactiver temporairement les vérifications de clés étrangères pendant tout l'import
                    // (comme le fait phpMyAdmin) pour permettre l'import même si certaines références n'existent pas encore
                    $connection->exec(CONCORSO_SQL_FK_CHECKS_OFF);
                    $fkChecksDisabled = true;
                    
                    if (!empty($_POST['truncate'])) {
                        $connection->exec('TRUNCATE TABLE `' . $safeTable . '`');
                    }
                    
                    $handle = fopen($csvPath, 'r');
                    if (!$handle) {
                        throw new RuntimeException('Impossibile aprire il CSV per l\'import.');
                    }

                    $dateColumnsSet = array_fill_keys($dateColumns, true);
                    $batchSize = 500;
                    $batchRows = [];
                    $batchLines = [];
                    
                    $line = 0;
                    while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
                        $line++;
                        if ($line === 1 && $hasHeader) {
                            continue;
                        }
                        
                        $payload = [];
                        foreach ($mapped as $csvIndex => $colName) {
                            $value = isset($row[$csvIndex]) ? normalize_csv_import_cell_value(trim((string)$row[$csvIndex])) : null;
                            
                            // Convertir les dates vides en NULL (évite 0000-00-00 00:00:00)
                            if (isset($dateColumnsSet[$colName])) {
                                if ($value === null || $value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
                                    $value = null;
                                } else {
                                    // Normaliser les espaces multiples en un seul espace
                                    $value = preg_replace(CONCORSO_REGEX_WHITESPACE, ' ', trim($value));
                                    
                                    // Essayer différents formats de date
                                    $dateTest = null;
                                    $dateFormats = [
                                        'Y-m-d H:i:s',      // 2026-01-19 11:42:00
                                        'Y-m-d H:i',        // 2026-01-19 11:42
                                        'Y-m-d',            // 2026-01-19
                                        'd/m/Y H:i:s',      // 19/01/2026 11:42:00
                                        'd/m/Y H:i',        // 19/01/2026 11:42
                                        CONCORSO_DATE_FORMAT,            // 19/01/2026
                                        'd-m-Y H:i:s',      // 19-01-2026 11:42:00
                                        'd-m-Y H:i',        // 19-01-2026 11:42
                                        'd-m-Y',            // 19-01-2026
                                    ];
                                    
                                    foreach ($dateFormats as $format) {
                                        $dateTest = DateTime::createFromFormat($format, $value);
                                        if ($dateTest !== false) {
                                            // Convertir en format MySQL standard
                                            $value = $dateTest->format('Y-m-d H:i:s');
                                            break;
                                        }
                                    }
                                    
                                    if (!$dateTest) {
                                        // Format invalide, mettre à NULL
                                        $value = null;
                                    }
                                }
                            }
                            
                            // Valider id_cliente : si la valeur existe mais n'est pas dans utenti, mettre à NULL
                            if ($colName === 'id_cliente' && $value !== null && $value !== '') {
                                $idClienteInt = (int)$value;
                                if ($idClienteInt > 0) {
                                    $stmtCheckCliente->execute([$idClienteInt]);
                                    if (!$stmtCheckCliente->fetch()) {
                                        // id_cliente non trouvé dans utenti, mettre à NULL
                                        $value = null;
                                    } else {
                                        $value = $idClienteInt;
                                    }
                                } else {
                                    $value = null;
                                }
                            }
                            
                            // Valider id_codice_partecipazione : si la valeur existe mais n'est pas dans codici_partecipazione, mettre à NULL
                            if ($colName === 'id_codice_partecipazione' && $value !== null && $value !== '') {
                                $idCodiceInt = (int)$value;
                                if ($idCodiceInt > 0) {
                                    $stmtCheckCodicePartecipazione->execute([$idCodiceInt]);
                                    if (!$stmtCheckCodicePartecipazione->fetch()) {
                                        // id_codice_partecipazione non trouvé dans codici_partecipazione, mettre à NULL
                                        $value = null;
                                    } else {
                                        $value = $idCodiceInt;
                                    }
                                } else {
                                    $value = null;
                                }
                            }
                            
                            $payload[$colName] = $value;
                        }

                        $batchRows[] = $payload;
                        $batchLines[] = $line;
                        if (count($batchRows) >= $batchSize) {
                            gestione_dati_flush_insert_batch(
                                $connection,
                                $safeTable,
                                $columns,
                                $batchRows,
                                $batchLines,
                                $inserted,
                                $failed,
                                $failRows
                            );
                            $batchRows = [];
                            $batchLines = [];
                        }
                    }
                    fclose($handle);

                    if ($batchRows !== []) {
                        gestione_dati_flush_insert_batch(
                            $connection,
                            $safeTable,
                            $columns,
                            $batchRows,
                            $batchLines,
                            $inserted,
                            $failed,
                            $failRows
                        );
                    }
                    
                    // Réactiver les vérifications de clés étrangères avant le commit
                    $connection->exec(CONCORSO_SQL_FK_CHECKS_ON);
                    $fkChecksDisabled = false;
                    
                    if ($connection->inTransaction()) {
                        $connection->commit();
                    }
                    gestione_dati_reset_auto_increment_if_empty($connection, $safeTable);
                    $success_gestione[] = "Import completato. Inseriti: {$inserted}. Errori: {$failed}.";
                    if ($failed > 0) {
                        $errors_gestione[] = "Alcune righe hanno fallito l'inserimento (prime 20 sotto).";
                        $errors_gestione = array_merge($errors_gestione, $failRows);
                    }
                } catch (Throwable $e) {
                    if ($connection->inTransaction()) {
                        $connection->rollBack();
                    }
                    // Réactiver les vérifications de clés étrangères si elles étaient désactivées
                    if ($fkChecksDisabled) {
                        try {
                            $connection->exec(CONCORSO_SQL_FK_CHECKS_ON);
                        } catch (Throwable $fkEx) {
                            // Ignorer l'erreur si la connexion est fermée
                        }
                    }
                    $errors_gestione[] = 'Import fallito: ' . $e->getMessage();
                } finally {
                    if (file_exists($csvPath)) {
                        @unlink($csvPath);
                    }
                    unset($_SESSION['csv_import_gestione']);
                }
                } else {
                    if (file_exists($csvPath)) {
                        @unlink($csvPath);
                    }
                    unset($_SESSION['csv_import_gestione']);
                }
            }
        }
    }
    
    // Redirect per evitare il popup di reinvio modulo (PRG pattern)
    // Sauvegarder les messages dans la session avant la redirection
    if (!empty($success_gestione) || !empty($errors_gestione)) {
        $_SESSION['gestione_dati_success'] = $success_gestione;
        $_SESSION['gestione_dati_errors'] = $errors_gestione;
    }
    // Rediriger vers la même page en restant sur Gestione Dati (step 9) pour afficher directement le mapping
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($step_gestione) && in_array($step_gestione, ['preview', 'import'], true)) {
        $redirectUrl = $_SERVER['REQUEST_URI'];
        $sep = (strpos($redirectUrl, '?') !== false) ? '&' : '?';
        $redirectUrl .= $sep . 'concorso_step=9';
        header(CONCORSO_LOCATION_HEADER . $redirectUrl);
        exit;
    }
}

// Reset content.json e immagini quando si invia reset_config=1
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_config']) && $_POST['reset_config'] === '1') {
    $errors_reset = [];
    $messages_reset = [];
    
    // 1. Eliminare le colonne dei campi personalizzati dalla tabella partecipazioni
    $custom_fields_to_drop = $content_current['custom_fields'] ?? [];
    if (is_array($custom_fields_to_drop) && !empty($custom_fields_to_drop)) {
        require_once __DIR__ . CONCORSO_APP_FUNCTIONS;
        $pdo_reset = function_exists('conn_pdo') ? conn_pdo() : null;
        if ($pdo_reset) {
            $dropped_count = 0;
            foreach ($custom_fields_to_drop as $field) {
                if (!is_array($field) || !isset($field['id'])) {
                    continue;
                }
                $field_id = (string) ($field['id'] ?? '');
                $column_name = preg_replace(CONCORSO_REGEX_ALNUM_UNDERSCORE, '_', strtolower($field_id));
                if ($column_name === '') {
                    continue;
                }
                try {
                    $check = $pdo_reset->query("SHOW COLUMNS FROM partecipazioni LIKE '{$column_name}'");
                    if ($check && $check->rowCount() > 0) {
                        $pdo_reset->exec("ALTER TABLE partecipazioni DROP COLUMN `{$column_name}`");
                        $dropped_count++;
                    }
                } catch (Throwable $e) {
                    error_log("Reset config: impossibile eliminare colonna {$column_name}: " . $e->getMessage());
                }
                $old_column = 'custom_' . $column_name;
                try {
                    $check_old = $pdo_reset->query("SHOW COLUMNS FROM partecipazioni LIKE '{$old_column}'");
                    if ($check_old && $check_old->rowCount() > 0) {
                        $pdo_reset->exec("ALTER TABLE partecipazioni DROP COLUMN `{$old_column}`");
                        $dropped_count++;
                    }
                } catch (Throwable $e) {
                    // Ignorare colonne legacy
                }
            }
            if ($dropped_count > 0) {
                $messages_reset[] = $dropped_count . ' campo/i personalizzato/i eliminato/i dalla tabella partecipazioni.';
            }
        }
    }
    
    // 2. Eliminare immagini premio email (template + generate) prima di content.json
    if (!function_exists('prize_image_cleanup_all_uploads')) {
        require_once __DIR__ . CONCORSO_PRIZE_IMAGE_GENERATOR;
    }
    $premiCleanup = prize_image_cleanup_all_uploads();
    $premiDeletedTotal = (int) ($premiCleanup['templates'] ?? 0) + (int) ($premiCleanup['generated'] ?? 0);
    if ($premiDeletedTotal > 0) {
        $messages_reset[] = $premiDeletedTotal . ' file immagine premio eliminati (uploads/premi/).';
    }
    
    // 3. Eliminare content.json (se esiste)
    if (file_exists($contentPath)) {
        if (!@unlink($contentPath)) {
            $errors_reset[] = 'Impossibile eliminare content.json.';
        } else {
            $messages_reset[] = 'content.json eliminato con successo.';
        }
    } else {
        $messages_reset[] = 'content.json non esisteva già.';
    }
    
    // 4. Eliminare tutte le immagini in public/assets/img tranne default_img
    $imgDir = $baseDir . '/public/assets/img';
    if (is_dir($imgDir)) {
        $deleted_count = 0;
        $items = scandir($imgDir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === 'default_img') {
                continue;
            }
            
            $itemPath = $imgDir . DIRECTORY_SEPARATOR . $item;
            if (is_file($itemPath)) {
                if (@unlink($itemPath)) {
                    $deleted_count++;
                }
            } elseif (is_dir($itemPath)) {
                // Eliminare ricorsivamente le directory (tranne default_img)
                $deleteDir = function($dir) use (&$deleteDir, &$deleted_count) {
                    if (!is_dir($dir)) { return; }
                    $files = array_diff(scandir($dir), ['.', '..']);
                    foreach ($files as $file) {
                        $filePath = $dir . DIRECTORY_SEPARATOR . $file;
                        if (is_dir($filePath)) {
                            $deleteDir($filePath);
                        } else {
                            if (@unlink($filePath)) {
                                $deleted_count++;
                            }
                        }
                    }
                    @rmdir($dir);
                };
                $deleteDir($itemPath);
            }
        }
        if ($deleted_count > 0) {
            $messages_reset[] = "$deleted_count immagine/i eliminate da public/assets/img.";
        } else {
            $messages_reset[] = 'Nessuna immagine da eliminare in public/assets/img.';
        }
    }
    
    // 5. Svuotare la cartella uploads/csv (file temporanei Gestione Dati)
    $upload_dir_csv = __DIR__ . CONCORSO_CSV_UPLOAD_DIR;
    if (is_dir($upload_dir_csv)) {
        $csv_deleted = 0;
        foreach (glob($upload_dir_csv . 'gestione_*.csv') ?: [] as $file) {
            if (@unlink($file)) {
                $csv_deleted++;
            }
        }
        unset($_SESSION['csv_import_gestione']);
        if ($csv_deleted > 0) {
            $messages_reset[] = $csv_deleted . ' file CSV temporaneo/i eliminati.';
        }
    }

    // 5b. Eliminare i font personalizzati caricati (public/assets/fonts/custom), tenere .gitkeep
    $customFontsDir = $baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR . 'custom';
    if (is_dir($customFontsDir)) {
        $fonts_deleted = 0;
        foreach (scandir($customFontsDir) ?: [] as $fontItem) {
            if ($fontItem === '.' || $fontItem === '..' || $fontItem === '.gitkeep') {
                continue;
            }
            $fontPath = $customFontsDir . DIRECTORY_SEPARATOR . $fontItem;
            if (is_file($fontPath) && @unlink($fontPath)) {
                $fonts_deleted++;
            }
        }
        if ($fonts_deleted > 0) {
            $messages_reset[] = $fonts_deleted . ' font personalizzato/i eliminati da public/assets/fonts/custom.';
        } else {
            $messages_reset[] = 'Nessun font personalizzato da eliminare.';
        }
    }

    // Helper: elimina file in una cartella (e sotto-cartelle), con eccezioni per nome
    $resetClearDirContents = static function (string $dir, array $keepNames = ['.gitkeep', '.htaccess']) use (&$resetClearDirContents): int {
        $deleted = 0;
        if (!is_dir($dir)) {
            return 0;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..' || in_array($item, $keepNames, true)) {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_file($path)) {
                if (@unlink($path)) {
                    $deleted++;
                }
                continue;
            }
            if (is_dir($path)) {
                $deleted += $resetClearDirContents($path, $keepNames);
                // Rimuovere sottocartelle vuote (non i keepNames a root)
                $remaining = array_diff(scandir($path) ?: [], ['.', '..']);
                if ($remaining === []) {
                    @rmdir($path);
                }
            }
        }

        return $deleted;
    };

    // 5c. Eliminare PDF caricati (public/assets/pdf), tenere default_pdf
    $pdfDir = $baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'pdf';
    if (is_dir($pdfDir)) {
        $pdf_deleted = 0;
        foreach (scandir($pdfDir) ?: [] as $pdfItem) {
            if ($pdfItem === '.' || $pdfItem === '..' || $pdfItem === 'default_pdf' || $pdfItem === '.gitkeep') {
                continue;
            }
            $pdfPath = $pdfDir . DIRECTORY_SEPARATOR . $pdfItem;
            if (is_file($pdfPath)) {
                if (@unlink($pdfPath)) {
                    $pdf_deleted++;
                }
            } elseif (is_dir($pdfPath)) {
                $pdf_deleted += $resetClearDirContents($pdfPath);
                $remaining = array_diff(scandir($pdfPath) ?: [], ['.', '..']);
                if ($remaining === []) {
                    @rmdir($pdfPath);
                }
            }
        }
        if ($pdf_deleted > 0) {
            $messages_reset[] = $pdf_deleted . ' file PDF eliminati da public/assets/pdf.';
        } else {
            $messages_reset[] = 'Nessun PDF da eliminare in public/assets/pdf.';
        }
    }

    // 5d. Svuotare gli scontrini caricati (public/uploads/scontrini), tenere .htaccess/.gitkeep
    $scontriniDir = $baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'scontrini';
    if (is_dir($scontriniDir)) {
        $scontrini_deleted = $resetClearDirContents($scontriniDir);
        if ($scontrini_deleted > 0) {
            $messages_reset[] = $scontrini_deleted . ' file scontrino/i eliminati da public/uploads/scontrini.';
        } else {
            $messages_reset[] = 'Nessuno scontrino da eliminare.';
        }
    }
    
    // Reset Sicurezza (blacklist, journal, impostazioni predefinite)
    require_once __DIR__ . '/../../app/logic/contest_security.php';
    $pdo_sec_reset = isset($connection) && $connection instanceof PDO ? $connection : (function_exists('conn_pdo') ? conn_pdo() : null);
    if ($pdo_sec_reset instanceof PDO) {
        $secReset = contest_security_reset_to_defaults($pdo_sec_reset);
        if (!empty($secReset['ok'])) {
            $messages_reset[] = (string) ($secReset['message'] ?? 'Sicurezza ripristinata.');
        } else {
            $errors_reset[] = (string) ($secReset['message'] ?? 'Errore ripristino Sicurezza.');
        }
    } else {
        $errors_reset[] = 'Impossibile ripristinare Sicurezza: connessione database non disponibile.';
    }
    
    // 6. Svuotare tabelle dati se richiesto (reset_tables[])
    if (isset($_POST['reset_tables']) && is_array($_POST['reset_tables'])) {
        $allowedResetTables = ['premi', 'codici_premio', 'codici_partecipazione', 'punti_vendita', 'quiz_domande'];
        $resetTables = array_intersect($_POST['reset_tables'], $allowedResetTables);
        if (!empty($resetTables)) {
            $pdo_reset = isset($connection) && $connection instanceof PDO ? $connection : (function_exists('conn_pdo') ? conn_pdo() : null);
            if ($pdo_reset instanceof PDO) {
                try {
                    $pdo_reset->beginTransaction();
                    if (in_array('codici_premio', $resetTables, true)) {
                        $pdo_reset->exec('UPDATE codici_premio SET id_codice_partecipazione = NULL, id_partecipazione = NULL WHERE id_codice_partecipazione IS NOT NULL OR id_partecipazione IS NOT NULL');
                    }
                    if (in_array('codici_partecipazione', $resetTables, true)) {
                        if (in_array('codici_premio', $resetTables, true)) {
                            $pdo_reset->exec('UPDATE codici_premio SET id_codice_partecipazione = NULL WHERE id_codice_partecipazione IS NOT NULL');
                        }
                        $n = $pdo_reset->exec('DELETE FROM codici_partecipazione');
                        $messages_reset[] = "Codici partecipazione: {$n} righe eliminate.";
                    }
                    if (in_array('codici_premio', $resetTables, true)) {
                        $n = $pdo_reset->exec('DELETE FROM codici_premio');
                        $messages_reset[] = "Codici premio: {$n} righe eliminate.";
                    }
                    if (in_array('premi', $resetTables, true)) {
                        $pdo_reset->exec('UPDATE partecipazioni SET prize_id = NULL WHERE prize_id IS NOT NULL');
                        $n = $pdo_reset->exec('DELETE FROM premi');
                        $messages_reset[] = "Premi: {$n} righe eliminate.";
                    }
                    if (in_array('punti_vendita', $resetTables, true)) {
                        $n = $pdo_reset->exec('DELETE FROM punti_vendita');
                        $messages_reset[] = "Punti vendita (PDV): {$n} righe eliminate.";
                    }
                    if (in_array('quiz_domande', $resetTables, true)) {
                        $n = $pdo_reset->exec('DELETE FROM quiz_domande');
                        $messages_reset[] = "Quiz domande: {$n} righe eliminate.";
                    }
                    $pdo_reset->commit();
                    // TRUNCATE dopo il commit: resetta AUTO_INCREMENT a 1 per le prossime inserzioni (DDL = implicit commit, da fare fuori transazione)
                    $truncateTables = ['punti_vendita', 'codici_partecipazione', 'codici_premio', 'premi', 'quiz_domande'];
                    $toTruncate = array_intersect($resetTables, $truncateTables);
                    if (!empty($toTruncate)) {
                        try {
                            $pdo_reset->exec(CONCORSO_SQL_FK_CHECKS_OFF);
                            foreach ($toTruncate as $t) {
                                $pdo_reset->exec("TRUNCATE TABLE `{$t}`");
                            }
                        } catch (Exception $e) {
                            error_log("Reset TRUNCATE: " . $e->getMessage());
                        } finally {
                            $pdo_reset->exec(CONCORSO_SQL_FK_CHECKS_ON);
                        }
                    }
                } catch (Exception $e) {
                    if ($pdo_reset->inTransaction()) {
                        $pdo_reset->rollBack();
                    }
                    $errors_reset[] = 'Errore reset tabelle dati: ' . $e->getMessage();
                    error_log('Reset tabelle dati fallito: ' . $e->getMessage());
                }
            }
        }
    }
    
    // Flash messages in sessione + redirect PRG (URL pulita)
    if (!empty($messages_reset)) {
        $_SESSION['concorso_messages'] = array_merge($_SESSION['concorso_messages'] ?? [], $messages_reset);
    }
    if (!empty($errors_reset)) {
        $_SESSION['concorso_errors'] = array_merge($_SESSION['concorso_errors'] ?? [], $errors_reset);
    }

    // Redirect alla stessa pagina senza aggiungere query msg/error (evita alert ripetuti al refresh)
    $redirectUrl = $_SERVER['REQUEST_URI'];
    $path = parse_url($redirectUrl, PHP_URL_PATH) ?: $redirectUrl;
    $query = parse_url($redirectUrl, PHP_URL_QUERY);
    $params = [];
    if (is_string($query) && $query !== '') {
        parse_str($query, $params);
        unset($params['msg'], $params['error']);
    }
    $qs = !empty($params) ? ('?' . http_build_query($params)) : '';
    header(CONCORSO_LOCATION_HEADER . $path . $qs);
    exit;
}

// Salvare content.json quando si invia il form di configurazione (save_config=1) O quando non è una richiesta gestione-dati
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$requestExceededPostMax && (isset($_POST['save_config']) || !$is_gestione_dati_request)) {
    require_once __DIR__ . '/concorso/concorso-config-post-helpers.php';
    require_once dirname(__DIR__, 2) . '/app/logic/concorso_content_save.php';
    $creatingNewContentJson = !is_file($contentPath);
    // Sanitize helpers
    $postText = static function (string $key, string $fallback = ''): string {
        return trim((string) ($_POST[$key] ?? $fallback));
    };
    $postFloat = static function (string $key, float $fallback = 0.0): float {
        $raw = trim((string) ($_POST[$key] ?? ''));
        if ($raw === '') {
            return $fallback;
        }
        $normalized = str_replace('.', '', $raw);
        $normalized = str_replace(',', '.', $normalized);
        return $normalized === '' ? $fallback : (float) $normalized;
    };
    $postInt = static function (string $key, int $fallback = 0): int {
        $raw = trim((string) ($_POST[$key] ?? ''));
        return $raw === '' ? $fallback : (int) $raw;
    };

    $bgMode = strtolower($postText('background_mode', $content_current['background_mode'] ?? 'image'));
    $bgMode = in_array($bgMode, ['color', 'image'], true) ? $bgMode : ($content_defaults['background_mode'] ?? 'image');
    
    $bgScope = strtolower($postText('background_scope', $content_current['background_scope'] ?? 'full_body'));
    $bgScope = in_array($bgScope, ['full_body', 'header_main'], true) ? $bgScope : ($content_defaults['background_scope'] ?? 'full_body');

    $contestMode = strtolower($postText('contest_mode', $content_current['contest_mode'] ?? ($content_defaults['contest_mode'] ?? 'estrazione')));
    $contestMode = in_array($contestMode, ['estrazione', 'instantwin'], true) ? $contestMode : ($content_defaults['contest_mode'] ?? 'estrazione');

    $formFieldStyle = strtolower($postText('form_field_style', $content_current['form_field_style'] ?? ($content_defaults['form_field_style'] ?? 'label_top')));
    $formFieldStyle = in_array($formFieldStyle, ['label_top', 'placeholder'], true) ? $formFieldStyle : ($content_defaults['form_field_style'] ?? 'label_top');

    $formFieldBorderRadius = max(0, min(40, (int) ($_POST['form_field_border_radius'] ?? ($content_current['form_field_border_radius'] ?? ($content_defaults['form_field_border_radius'] ?? 6)))));

    $formFieldTextColorRaw = trim((string) ($_POST['form_field_text_color'] ?? ''));
    if ($formFieldTextColorRaw === '' || !preg_match(CONCORSO_REGEX_HEX6, $formFieldTextColorRaw)) {
        $formFieldTextColorRaw = (string) ($content_current['form_field_text_color'] ?? ($content_defaults['form_field_text_color'] ?? CONCORSO_COLOR_BLACK));
        if (!preg_match(CONCORSO_REGEX_HEX6, $formFieldTextColorRaw)) {
            $formFieldTextColorRaw = CONCORSO_COLOR_BLACK;
        }
    }
    $formFieldTextColor = strtolower($formFieldTextColorRaw);

    $formFieldPlaceholderColorRaw = trim((string) ($_POST['form_field_placeholder_color'] ?? ''));
    if ($formFieldPlaceholderColorRaw !== '' && !preg_match(CONCORSO_REGEX_HEX6, $formFieldPlaceholderColorRaw)) {
        $formFieldPlaceholderColorRaw = (string) ($content_current['form_field_placeholder_color'] ?? ($content_defaults['form_field_placeholder_color'] ?? ''));
    }
    $formFieldPlaceholderColor = ($formFieldPlaceholderColorRaw !== '' && preg_match(CONCORSO_REGEX_HEX6, $formFieldPlaceholderColorRaw))
        ? strtolower($formFieldPlaceholderColorRaw)
        : '';

    $formFieldBorderColorRaw = trim((string) ($_POST['form_field_border_color'] ?? ''));
    if ($formFieldBorderColorRaw === '' || !preg_match(CONCORSO_REGEX_HEX6, $formFieldBorderColorRaw)) {
        $formFieldBorderColorRaw = (string) ($content_current['form_field_border_color'] ?? ($content_defaults['form_field_border_color'] ?? '#dee2e6'));
        if (!preg_match(CONCORSO_REGEX_HEX6, $formFieldBorderColorRaw)) {
            $formFieldBorderColorRaw = '#dee2e6';
        }
    }
    $formFieldBorderColor = strtolower($formFieldBorderColorRaw);

    $formFieldBgColorRaw = trim((string) ($_POST['form_field_bg_color'] ?? ''));
    if ($formFieldBgColorRaw === '' || !preg_match(CONCORSO_REGEX_HEX6, $formFieldBgColorRaw)) {
        $formFieldBgColorRaw = (string) ($content_current['form_field_bg_color'] ?? ($content_defaults['form_field_bg_color'] ?? CONCORSO_COLOR_WHITE));
        if (!preg_match(CONCORSO_REGEX_HEX6, $formFieldBgColorRaw)) {
            $formFieldBgColorRaw = CONCORSO_COLOR_WHITE;
        }
    }
    $formFieldBgColor = strtolower($formFieldBgColorRaw);

    $formFieldFocusColorRaw = trim((string) ($_POST['form_field_focus_color'] ?? ''));
    if ($formFieldFocusColorRaw !== '' && !preg_match(CONCORSO_REGEX_HEX6, $formFieldFocusColorRaw)) {
        $formFieldFocusColorRaw = (string) ($content_current['form_field_focus_color'] ?? ($content_defaults['form_field_focus_color'] ?? ''));
    }
    $formFieldFocusColor = ($formFieldFocusColorRaw !== '' && preg_match(CONCORSO_REGEX_HEX6, $formFieldFocusColorRaw))
        ? strtolower($formFieldFocusColorRaw)
        : '';

    $formFieldCheckboxColorRaw = trim((string) ($_POST['form_field_checkbox_color'] ?? ''));
    if ($formFieldCheckboxColorRaw !== '' && !preg_match(CONCORSO_REGEX_HEX6, $formFieldCheckboxColorRaw)) {
        $formFieldCheckboxColorRaw = (string) ($content_current['form_field_checkbox_color'] ?? ($content_defaults['form_field_checkbox_color'] ?? ''));
    }
    $formFieldCheckboxColor = ($formFieldCheckboxColorRaw !== '' && preg_match(CONCORSO_REGEX_HEX6, $formFieldCheckboxColorRaw))
        ? strtolower($formFieldCheckboxColorRaw)
        : '';

    $formFieldConsentTextColorRaw = trim((string) ($_POST['form_field_consent_text_color'] ?? ''));
    if ($formFieldConsentTextColorRaw === '' || !preg_match(CONCORSO_REGEX_HEX6, $formFieldConsentTextColorRaw)) {
        $formFieldConsentTextColorRaw = (string) ($content_current['form_field_consent_text_color'] ?? ($content_defaults['form_field_consent_text_color'] ?? CONCORSO_COLOR_BLACK));
        if (!preg_match(CONCORSO_REGEX_HEX6, $formFieldConsentTextColorRaw)) {
            $formFieldConsentTextColorRaw = CONCORSO_COLOR_BLACK;
        }
    }
    $formFieldConsentTextColor = strtolower($formFieldConsentTextColorRaw);

    $formFieldConsentLinkColorRaw = trim((string) ($_POST['form_field_consent_link_color'] ?? ''));
    if ($formFieldConsentLinkColorRaw !== '' && !preg_match(CONCORSO_REGEX_HEX6, $formFieldConsentLinkColorRaw)) {
        $formFieldConsentLinkColorRaw = (string) ($content_current['form_field_consent_link_color'] ?? ($content_defaults['form_field_consent_link_color'] ?? ''));
    }
    $formFieldConsentLinkColor = ($formFieldConsentLinkColorRaw !== '' && preg_match(CONCORSO_REGEX_HEX6, $formFieldConsentLinkColorRaw))
        ? strtolower($formFieldConsentLinkColorRaw)
        : '';

    $formFieldConsentLinkHoverColorRaw = trim((string) ($_POST['form_field_consent_link_hover_color'] ?? ''));
    if ($formFieldConsentLinkHoverColorRaw !== '' && !preg_match(CONCORSO_REGEX_HEX6, $formFieldConsentLinkHoverColorRaw)) {
        $formFieldConsentLinkHoverColorRaw = (string) ($content_current['form_field_consent_link_hover_color'] ?? ($content_defaults['form_field_consent_link_hover_color'] ?? ''));
    }
    $formFieldConsentLinkHoverColor = ($formFieldConsentLinkHoverColorRaw !== '' && preg_match(CONCORSO_REGEX_HEX6, $formFieldConsentLinkHoverColorRaw))
        ? strtolower($formFieldConsentLinkHoverColorRaw)
        : '';

    $formFieldSelect2HighlightBgRaw = trim((string) ($_POST['form_field_select2_highlight_bg'] ?? ''));
    if ($formFieldSelect2HighlightBgRaw !== '' && !preg_match(CONCORSO_REGEX_HEX6, $formFieldSelect2HighlightBgRaw)) {
        $formFieldSelect2HighlightBgRaw = (string) ($content_current['form_field_select2_highlight_bg'] ?? ($content_current['form_field_select2_color'] ?? ($content_defaults['form_field_select2_highlight_bg'] ?? '')));
    }
    $formFieldSelect2HighlightBg = ($formFieldSelect2HighlightBgRaw !== '' && preg_match(CONCORSO_REGEX_HEX6, $formFieldSelect2HighlightBgRaw))
        ? strtolower($formFieldSelect2HighlightBgRaw)
        : '';

    $formFieldSelect2HighlightTextRaw = trim((string) ($_POST['form_field_select2_highlight_text'] ?? ''));
    if ($formFieldSelect2HighlightTextRaw !== '' && !preg_match(CONCORSO_REGEX_HEX6, $formFieldSelect2HighlightTextRaw)) {
        $formFieldSelect2HighlightTextRaw = (string) ($content_current['form_field_select2_highlight_text'] ?? ($content_defaults['form_field_select2_highlight_text'] ?? ''));
    }
    $formFieldSelect2HighlightText = ($formFieldSelect2HighlightTextRaw !== '' && preg_match(CONCORSO_REGEX_HEX6, $formFieldSelect2HighlightTextRaw))
        ? strtolower($formFieldSelect2HighlightTextRaw)
        : '';

    $formFieldCalendarPopupBgRaw = trim((string) ($_POST['form_field_calendar_popup_bg'] ?? ''));
    if ($formFieldCalendarPopupBgRaw !== '' && !preg_match(CONCORSO_REGEX_HEX6, $formFieldCalendarPopupBgRaw)) {
        $formFieldCalendarPopupBgRaw = (string) ($content_current['form_field_calendar_popup_bg'] ?? ($content_defaults['form_field_calendar_popup_bg'] ?? ''));
    }
    $formFieldCalendarPopupBg = ($formFieldCalendarPopupBgRaw !== '' && preg_match(CONCORSO_REGEX_HEX6, $formFieldCalendarPopupBgRaw))
        ? strtolower($formFieldCalendarPopupBgRaw)
        : '';

    $formFieldCalendarSelectedColorRaw = trim((string) ($_POST['form_field_calendar_selected_color'] ?? ''));
    if ($formFieldCalendarSelectedColorRaw !== '' && !preg_match(CONCORSO_REGEX_HEX6, $formFieldCalendarSelectedColorRaw)) {
        $formFieldCalendarSelectedColorRaw = (string) ($content_current['form_field_calendar_selected_color'] ?? ($content_defaults['form_field_calendar_selected_color'] ?? ''));
    }
    $formFieldCalendarSelectedColor = ($formFieldCalendarSelectedColorRaw !== '' && preg_match(CONCORSO_REGEX_HEX6, $formFieldCalendarSelectedColorRaw))
        ? strtolower($formFieldCalendarSelectedColorRaw)
        : '';

    $formFieldCalendarHoverBgRaw = trim((string) ($_POST['form_field_calendar_hover_bg'] ?? ''));
    if ($formFieldCalendarHoverBgRaw !== '' && !preg_match(CONCORSO_REGEX_HEX6, $formFieldCalendarHoverBgRaw)) {
        $formFieldCalendarHoverBgRaw = (string) ($content_current['form_field_calendar_hover_bg'] ?? ($content_defaults['form_field_calendar_hover_bg'] ?? ''));
    }
    $formFieldCalendarHoverBg = ($formFieldCalendarHoverBgRaw !== '' && preg_match(CONCORSO_REGEX_HEX6, $formFieldCalendarHoverBgRaw))
        ? strtolower($formFieldCalendarHoverBgRaw)
        : '';

    $formFieldCalendarHoverTextRaw = trim((string) ($_POST['form_field_calendar_hover_text'] ?? ''));
    if ($formFieldCalendarHoverTextRaw !== '' && !preg_match(CONCORSO_REGEX_HEX6, $formFieldCalendarHoverTextRaw)) {
        $formFieldCalendarHoverTextRaw = (string) ($content_current['form_field_calendar_hover_text'] ?? ($content_defaults['form_field_calendar_hover_text'] ?? ''));
    }
    $formFieldCalendarHoverText = ($formFieldCalendarHoverTextRaw !== '' && preg_match(CONCORSO_REGEX_HEX6, $formFieldCalendarHoverTextRaw))
        ? strtolower($formFieldCalendarHoverTextRaw)
        : '';

    $formFieldBgOpacity = max(0, min(100, (int) ($_POST['form_field_bg_opacity'] ?? ($content_current['form_field_bg_opacity'] ?? ($content_defaults['form_field_bg_opacity'] ?? 100)))));
    $formFieldFocusOpacity = max(0, min(100, (int) ($_POST['form_field_focus_opacity'] ?? ($content_current['form_field_focus_opacity'] ?? ($content_defaults['form_field_focus_opacity'] ?? 100)))));
    $formFieldConsentLinkHoverOpacity = max(0, min(100, (int) ($_POST['form_field_consent_link_hover_opacity'] ?? ($content_current['form_field_consent_link_hover_opacity'] ?? ($content_defaults['form_field_consent_link_hover_opacity'] ?? 100)))));
    $formFieldErrorModeRaw = strtolower(trim((string) ($_POST['form_field_error_mode'] ?? ($content_current['form_field_error_mode'] ?? ($content_defaults['form_field_error_mode'] ?? 'classic')))));
    $formFieldErrorMode = in_array($formFieldErrorModeRaw, ['classic', 'on_color_background'], true) ? $formFieldErrorModeRaw : 'classic';

    $fidelityType = strtolower($postText('fidelity_type', $content_current['fidelity']['type'] ?? 'numeric'));
    $fidelityType = in_array($fidelityType, ['numeric', 'alphanumeric'], true) ? $fidelityType : ($content_defaults['fidelity']['type'] ?? 'numeric');
    $fidelityLength = max(1, $postInt('fidelity_length', (int)($content_current['fidelity']['length'] ?? 13)));
    $fidelityUniqueParticipation = !empty($_POST['fidelity_unique_participation']);
    $fidelityFieldWidth = strtolower(trim($postText('fidelity_field_width', $content_current['fidelity']['field_width'] ?? 'large')));
    $fidelityFieldWidth = in_array($fidelityFieldWidth, ['small', 'large'], true) ? $fidelityFieldWidth : 'large';
    $fidelityHelpText = trim($postText('fidelity_help_text', $content_current['fidelity']['help_text'] ?? ''));
    if (strlen($fidelityHelpText) > 500) {
        $fidelityHelpText = substr($fidelityHelpText, 0, 500);
    }
    $fidelityHelpTextColorRaw = trim((string) ($_POST['fidelity_help_text_color'] ?? ($content_current['fidelity']['help_text_color'] ?? '')));
    $fidelityHelpTextColor = preg_match(CONCORSO_REGEX_HEX6, $fidelityHelpTextColorRaw) ? strtolower($fidelityHelpTextColorRaw) : '';

    $scontrinoHelpText = trim($postText('scontrino_help_text', $content_current['scontrino_help_text'] ?? ''));
    if (strlen($scontrinoHelpText) > 500) {
        $scontrinoHelpText = substr($scontrinoHelpText, 0, 500);
    }
    $scontrinoHelpTextColorRaw = trim((string) ($_POST['scontrino_help_text_color'] ?? ($content_current['scontrino_help_text_color'] ?? '')));
    $scontrinoHelpTextColor = preg_match(CONCORSO_REGEX_HEX6, $scontrinoHelpTextColorRaw) ? strtolower($scontrinoHelpTextColorRaw) : '';

    $codicePartecipazioneHelpText = trim($postText('codice_partecipazione_help_text', $content_current['codice_partecipazione_help_text'] ?? ''));
    if (strlen($codicePartecipazioneHelpText) > 500) {
        $codicePartecipazioneHelpText = substr($codicePartecipazioneHelpText, 0, 500);
    }
    $codicePartecipazioneHelpTextColorRaw = trim((string) ($_POST['codice_partecipazione_help_text_color'] ?? ($content_current['codice_partecipazione_help_text_color'] ?? '')));
    $codicePartecipazioneHelpTextColor = preg_match(CONCORSO_REGEX_HEX6, $codicePartecipazioneHelpTextColorRaw) ? strtolower($codicePartecipazioneHelpTextColorRaw) : '';

    $cellulareExactRaw = trim((string) ($_POST['cellulare_digits_exact'] ?? ''));
    $cellulareMinRaw = trim((string) ($_POST['cellulare_digits_min'] ?? ''));
    $cellulareMaxRaw = trim((string) ($_POST['cellulare_digits_max'] ?? ''));
    $cellulareExact = $cellulareExactRaw !== '' ? max(6, min(12, (int) $cellulareExactRaw)) : null;
    $hasCellulareRange = $cellulareMinRaw !== '' || $cellulareMaxRaw !== '';
    if ($cellulareExact !== null && $hasCellulareRange) {
        $errors[] = 'Numero di cifre (cellulare): compila solo "Numero di cifre esatto" oppure "Valore minimo/massimo", non entrambi. Usa la croce per svuotare un campo.';
    }
    if ($hasCellulareRange && $cellulareExact === null) {
        if ($cellulareMinRaw !== '' && $cellulareMaxRaw === '') {
            $errors[] = 'Numero di cifre (cellulare): compila anche il valore massimo.';
        } elseif ($cellulareMinRaw === '' && $cellulareMaxRaw !== '') {
            $errors[] = 'Numero di cifre (cellulare): compila anche il valore minimo.';
        }
    }
    $cellulareDigitsMin = null;
    $cellulareDigitsMax = null;
    if ($cellulareExact !== null && !$hasCellulareRange) {
        $cellulareDigitsMin = $cellulareDigitsMax = $cellulareExact;
    } elseif ($hasCellulareRange && $cellulareExact === null && $cellulareMinRaw !== '' && $cellulareMaxRaw !== '') {
        $cellulareDigitsMin = max(6, min(12, (int) $cellulareMinRaw));
        $cellulareDigitsMax = max(6, min(12, (int) $cellulareMaxRaw));
        if ($cellulareDigitsMin > $cellulareDigitsMax) {
            $cellulareDigitsMax = $cellulareDigitsMin;
        }
    }

    // Build fields_enabled from defaults + known keys so no field (e.g. codice_partecipazione) is ever missing
    $defaults_fields = $content_defaults['fields_enabled'] ?? [];
    $known_field_keys = [
        'nome', 'cognome', 'email', 'prefisso', 'cellulare', 'fidelity', 'prov_pdv', 'citta_pdv', 'indirizzo_pdv',
        'importo', 'data_scontrino', 'scontrino', 'codice_partecipazione', 'cap', 'provincia', 'citta', 'indirizzo',
        'privacy', 'marketing', 'profilazione',
    ];
    $all_field_keys = array_unique(array_merge(array_keys($defaults_fields), $known_field_keys));
    $fields_enabled = [];
    $fields_required = [];
    foreach ($all_field_keys as $fieldKey) {
        $rawValue = $_POST['fields_enabled'][$fieldKey] ?? null;
        $fields_enabled[$fieldKey] = !empty($rawValue);

        $defaultRequired = $content_current['fields_required'][$fieldKey]
            ?? ($content_defaults['fields_required'][$fieldKey] ?? true);
        $rawRequiredValue = $_POST['fields_required'][$fieldKey] ?? null;
        $fields_required[$fieldKey] = $fields_enabled[$fieldKey] && (
            isset($_POST['fields_required']) ? !empty($rawRequiredValue) : (bool) $defaultRequired
        );
    }
    // Provincia e CAP esclusivi: se entrambi attivi, priorità a CAP
    if (!empty($fields_enabled['cap']) && !empty($fields_enabled['provincia'])) {
        $fields_enabled['provincia'] = false;
        $fields_required['provincia'] = false;
    }
    if (empty($fields_enabled['cap'])) {
        $fields_required['cap'] = false;
    }
    if (empty($fields_enabled['provincia'])) {
        $fields_required['provincia'] = false;
    }
    // Ordine campi standard (consensi esclusi, sempre in coda)
    // Merge: ordine POST (campi abilitati) + chiavi precedenti non postate (disabilitate)
    $postedOrder = $_POST['standard_fields_order'] ?? [];
    if (!is_array($postedOrder)) {
        $postedOrder = [];
    }
    $previousOrder = gst_standard_fields_normalize_order($content_current['standard_fields_order'] ?? null);
    $postedNormalized = gst_standard_fields_normalize_order($postedOrder);
    // Se l'utente ha postato solo un sottoinsieme, appendi le chiavi mancanti (disabilitate) dall'ordine precedente
    $mergedOrder = $postedNormalized;
    foreach ($previousOrder as $prevKey) {
        if (!in_array($prevKey, $mergedOrder, true)) {
            $mergedOrder[] = $prevKey;
        }
    }
    $standard_fields_order_saved = gst_standard_fields_normalize_order($mergedOrder);
    $standard_fields_map = gst_apply_standard_fields_order($standard_fields_map, $standard_fields_order_saved);
    $pdv_group_enabled = !empty($_POST['fields_enabled_pdv_group']);
    $pdv_group_required = $pdv_group_enabled && !empty($_POST['fields_required_pdv_group']);
    if (array_key_exists('prov_pdv', $fields_enabled)) {
        $fields_enabled['prov_pdv'] = $pdv_group_enabled;
        $fields_required['prov_pdv'] = $pdv_group_required;
    }
    if (array_key_exists('citta_pdv', $fields_enabled)) {
        $fields_enabled['citta_pdv'] = $pdv_group_enabled;
        $fields_required['citta_pdv'] = $pdv_group_required;
    }
    if (array_key_exists('indirizzo_pdv', $fields_enabled)) {
        $fields_enabled['indirizzo_pdv'] = $pdv_group_enabled;
        $fields_required['indirizzo_pdv'] = $pdv_group_required;
    }

    $custom_fields = [];
    $custom_field_renames = [];
    $custom_fields_provided = isset($_POST['custom_fields_present']);
    if ($custom_fields_provided && isset($_POST['custom_fields']) && is_array($_POST['custom_fields'])) {
        $allowedCustomTypes = ['int', 'varchar', 'text', 'date', 'boolean'];
        $current_custom_fields = $content_current['custom_fields'] ?? [];
        if (!is_array($current_custom_fields)) {
            $current_custom_fields = [];
        }
        $current_custom_ids = [];
        foreach ($current_custom_fields as $field) {
            if (is_array($field) && isset($field['id'])) {
                $current_custom_ids[strtolower((string) $field['id'])] = true;
            }
        }
        $existing_db_columns = [];
        try {
            require_once __DIR__ . CONCORSO_APP_FUNCTIONS;
            $pdo_check = conn_pdo();
            if ($pdo_check instanceof PDO) {
                $stmt = $pdo_check->query("SHOW COLUMNS FROM partecipazioni");
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
                    if (isset($col['Field'])) {
                        $existing_db_columns[strtolower((string) $col['Field'])] = true;
                    }
                }
            }
        } catch (Exception $e) {
            $errors[] = "Impossibile verificare l'unicita dei campi personalizzati nel database.";
        }
        $label_registry = [];
        $id_registry = [];
        foreach ($_POST['custom_fields'] as $fieldData) {
            if (!is_array($fieldData)) {
                continue;
            }
            $label = trim((string) ($fieldData['label'] ?? ''));
            if ($label === '') {
                $errors[] = 'Il nome del campo personalizzato è obbligatorio.';
                continue;
            }
            if (mb_strlen($label) > 30) {
                $errors[] = "Il nome del campo personalizzato non può superare 30 caratteri: \"{$label}\".";
                continue;
            }
            $label_id = strtolower($label);
            $label_id = preg_replace('/[^a-z0-9\s_-]/', '', $label_id);
            $label_id = preg_replace(CONCORSO_REGEX_WHITESPACE, '_', $label_id);
            $label_id = preg_replace('/[_-]+/', '_', $label_id);
            $label_id = trim($label_id, '_-');
            $label_key = strtolower(preg_replace(CONCORSO_REGEX_WHITESPACE, ' ', $label));
            $label_key_trim = trim($label_key);
            $standard_field_labels = [
                'nome' => ['nome'],
                'cognome' => ['cognome'],
                'email' => ['email'],
                'prefisso' => ['prefisso'],
                'cellulare' => ['cellulare'],
                'fidelity' => ['codice fidelity', 'fidelity'],
                'cap' => ['cap'],
                'provincia' => ['provincia'],
                'citta' => ['citta', 'città'],
                'indirizzo' => ['indirizzo'],
                'prov_pdv' => ['provincia pdv'],
                'citta_pdv' => ['citta pdv', 'città pdv'],
                'indirizzo_pdv' => ['indirizzo pdv', 'punto vendita'],
                'importo' => ['importo', 'importo scontrino'],
                'data_scontrino' => ['data scontrino'],
                'scontrino' => ['scontrino', 'foto scontrino'],
                'codice_partecipazione' => ['codice partecipazione'],
                'privacy' => ['privacy'],
                'marketing' => ['marketing'],
                'profilazione' => ['profilazione'],
            ];
            foreach ($standard_field_labels as $field_key => $std_labels) {
                foreach ($std_labels as $std_label) {
                    if ($label_key_trim === $std_label) {
                        $is_enabled = !empty($fields_enabled[$field_key]);
                        $msg = $is_enabled
                            ? "Nome già utilizzato: \"{$label}\" (è un campo standard attivo nel form)."
                            : "Nome già utilizzato: \"{$label}\" (riservato ai campi standard; evita conflitti se li riattivi dopo).";
                        $errors[] = $msg;
                        continue 3;
                    }
                }
            }
            if (isset($label_registry[$label_key])) {
                $errors[] = "Nome già utilizzato: \"{$label}\".";
                continue;
            }
            $label_registry[$label_key] = true;

            $old_id = trim((string) ($fieldData['id'] ?? ''));
            if ($old_id !== '') {
                $old_id = preg_replace('/[^a-z0-9_-]/i', '', $old_id);
                $old_id = strtolower($old_id);
            }
            $is_existing = $old_id !== '' && isset($current_custom_ids[$old_id]);
            $new_id = ($label_id !== '') ? $label_id : ('custom_' . uniqid());
            if ($is_existing && $new_id === $old_id) {
                $id = $old_id;
            } elseif ($is_existing && $new_id !== $old_id) {
                $id = $new_id;
                $custom_field_renames[$old_id] = $new_id;
            } else {
                $is_auto_id = $old_id === '' || preg_match('/^custom_[a-f0-9]+$/i', $old_id) || preg_match('/^custom_\d+_\d+$/', $old_id);
                $id = ($is_auto_id && $label_id !== '') ? $label_id : (($old_id !== '') ? $old_id : $new_id);
                if ($id === '') {
                    $id = $new_id;
                }
            }
            $id_key = strtolower($id);
            if (isset($id_registry[$id_key])) {
                $errors[] = "Nome già utilizzato: \"{$label}\".";
                continue;
            }
            $id_registry[$id_key] = true;
            $column_name = preg_replace(CONCORSO_REGEX_ALNUM_UNDERSCORE, '_', $id_key);
            $old_column_name = $is_existing && isset($custom_field_renames[$old_id]) ? preg_replace(CONCORSO_REGEX_ALNUM_UNDERSCORE, '_', strtolower($old_id)) : null;
            if ($old_column_name !== null) {
                if (isset($existing_db_columns[$column_name]) && $column_name !== $old_column_name) {
                    $errors[] = "Nome già utilizzato: \"{$label}\" (esiste già nel database).";
                    continue;
                }
            } elseif (!isset($current_custom_ids[$id_key]) && isset($existing_db_columns[$column_name])) {
                $errors[] = "Nome già utilizzato: \"{$label}\" (esiste già nel database).";
                continue;
            }
            $type = strtolower(trim((string) ($fieldData['type'] ?? 'varchar')));
            $type = in_array($type, $allowedCustomTypes, true) ? $type : 'varchar';
            $required = !empty($fieldData['required']);
            
            $maxLength = isset($fieldData['max_length']) && $fieldData['max_length'] !== '' ? max(0, (int)$fieldData['max_length']) : null;

            if ($type === 'text') {
                if ($maxLength !== null) {
                    if ($maxLength < 5000) {
                        $maxLength = 5000;
                    } elseif ($maxLength > 65535) {
                        $maxLength = 65535;
                    }
                }
            }
            $minLength = isset($fieldData['min_length']) && $fieldData['min_length'] !== '' ? max(0, (int)$fieldData['min_length']) : null;
            $exactLength = isset($fieldData['exact_length']) && $fieldData['exact_length'] !== '' ? max(0, (int)$fieldData['exact_length']) : null;
            $errorMessage = isset($fieldData['error_message']) ? trim((string)$fieldData['error_message']) : '';

            // Funzione di conversione position_after -> position_order (per compatibilità)
            $positionAfterToOrder = gst_standard_fields_position_after_to_order($standard_fields_map);
            
            $positionOrder = null;
            $targetFieldRef = null;
            
            if (array_key_exists('position_order', $fieldData)) {
                $postValue = $fieldData['position_order'];
                $postValueStr = trim((string) gst_migrate_numeric_position_order($postValue));
                
                if ($postValueStr !== '' && $postValueStr !== '0') {
                    $parsed = parse_position_order($postValueStr, $standard_fields_map, $custom_fields, $fields_enabled);
                    $positionOrder = $parsed['position_order'];
                    $targetFieldRef = $parsed['target_field_ref'];
                    
                    if ($positionOrder < 1 || $positionOrder > 20) {
                        error_log("WARNING - Invalid position_order value: {$positionOrder} for field {$label}, using default");
                        $positionOrder = ($type === 'boolean') ? 14 : 6;
                        $targetFieldRef = null;
                    }
                } else {
                    error_log("WARNING - Empty or zero position_order for field {$label}, using default");
                    $positionOrder = ($type === 'boolean') ? 14 : 6;
                    $targetFieldRef = null;
                }
            } else {
                $defaultPosition = ($type === 'boolean') ? 'privacy' : 'fidelity';
                $positionAfter = isset($fieldData['position_after']) ? trim((string)$fieldData['position_after']) : $defaultPosition;

                if ($type === 'boolean' && !in_array($positionAfter, ['privacy', 'marketing', 'profilazione'], true)) {
                    $positionAfter = 'privacy';
                }
                
                $allowedPositions = ['nome', 'cognome', 'email', 'prefisso_cellulare', 'cellulare', 'fidelity', 'cap', 'provincia', 'citta', 'indirizzo', 'indirizzo_pdv', 'importo', 'data_scontrino', 'scontrino', 'codice_partecipazione', 'privacy', 'marketing', 'profilazione'];
                if (!in_array($positionAfter, $allowedPositions, true)) {
                    $positionAfter = ($type === 'boolean') ? 'privacy' : 'fidelity';
                }
                
                $positionOrder = $positionAfterToOrder[$positionAfter] ?? ($type === 'boolean' ? 14 : 8);
                $targetFieldRef = 's_' . $positionAfter;
            }
            
            $debugValue = isset($fieldData['position_order']) ? var_export($fieldData['position_order'], true) : 'NOT SET';
            $debugType = isset($fieldData['position_order']) ? gettype($fieldData['position_order']) : 'NOT SET';
            error_log("SAVE FIELD - Label: {$label}, ID: {$id}, POST position_order EXISTS: " . (array_key_exists('position_order', $fieldData) ? 'YES' : 'NO') . ", Type: {$debugType}, Value: {$debugValue}, Final positionOrder: {$positionOrder}, targetFieldRef: " . ($targetFieldRef ?? 'null'));
            
            $dateMin = null;
            $dateMax = null;
            $dateMaxToday = false;
            if ($type === 'date') {
                $dateMin = isset($fieldData['date_min']) && trim($fieldData['date_min']) !== '' ? trim($fieldData['date_min']) : null;
                $dateMax = isset($fieldData['date_max']) && trim($fieldData['date_max']) !== '' ? trim($fieldData['date_max']) : null;
                $dateMaxToday = !empty($fieldData['date_max_today']);
            }
            
            $intMin = null;
            $intMax = null;
            $intExactDigits = null;
            if ($type === 'int') {
                $intMin = isset($fieldData['int_min']) && trim($fieldData['int_min']) !== '' ? (int)$fieldData['int_min'] : null;
                $intMax = isset($fieldData['int_max']) && trim($fieldData['int_max']) !== '' ? (int)$fieldData['int_max'] : null;
                $intExactDigits = isset($fieldData['int_exact_digits']) && trim($fieldData['int_exact_digits']) !== '' ? (int)$fieldData['int_exact_digits'] : null;

                if ($intMin !== null) {
                    $intMin = max(-2147483648, min(2147483647, $intMin));
                }
                if ($intMax !== null) {
                    $intMax = max(-2147483648, min(2147483647, $intMax));
                }
                if ($intExactDigits !== null) {
                    $intExactDigits = max(1, min(10, $intExactDigits));
                    // Cifre esatte (CAP/codici): min/max non si applicano
                    $intMin = null;
                    $intMax = null;
                }
            }

            $fieldWidth = isset($fieldData['field_width']) ? trim((string)$fieldData['field_width']) : 'large';
            if (!in_array($fieldWidth, ['small', 'large'], true)) {
                $fieldWidth = 'large';
            }

            $storedPositionOrder = isset($fieldData['position_order'])
                ? trim((string) gst_migrate_numeric_position_order($fieldData['position_order']))
                : (string) $positionOrder;
            if ($storedPositionOrder === '' || $storedPositionOrder === '0') {
                $storedPositionOrder = is_string($positionOrder) ? $positionOrder : (string) (int) $positionOrder;
            }

            $fieldConfig = [
                'id' => $id,
                'label' => $label,
                'type' => $type,
                'required' => $required,
                'position_order' => $storedPositionOrder,
                'field_width' => $fieldWidth,
            ];
            
            if ($targetFieldRef !== null) {
                $fieldConfig['target_field_ref'] = $targetFieldRef;
            }
            
            if (isset($fieldConfig['position_after'])) {
                unset($fieldConfig['position_after']);
            }
            if (isset($fieldData['position_after'])) {
                // Non includerlo in fieldConfig
            }
            
            // Aggiungi validazioni solo se impostate
            if ($exactLength !== null) {
                $fieldConfig['exact_length'] = $exactLength;
            }
            if ($minLength !== null) {
                $fieldConfig['min_length'] = $minLength;
            }
            if ($maxLength !== null) {
                $fieldConfig['max_length'] = $maxLength;
            }
            if ($errorMessage !== '') {
                $fieldConfig['error_message'] = $errorMessage;
            }
            
            if ($type === 'date') {
                if ($dateMin !== null) {
                    $fieldConfig['date_min'] = $dateMin;
                }
                if ($dateMax !== null && !$dateMaxToday) {
                    $fieldConfig['date_max'] = $dateMax;
                }
                if ($dateMaxToday) {
                    $fieldConfig['date_max_today'] = true;
                }
            }
            
            if ($type === 'int') {
                if ($intMin !== null) {
                    $fieldConfig['int_min'] = $intMin;
                }
                if ($intMax !== null) {
                    $fieldConfig['int_max'] = $intMax;
                }
                if ($intExactDigits !== null) {
                    $fieldConfig['int_exact_digits'] = $intExactDigits;
                }
            }

            $custom_fields[] = $fieldConfig;
        }
    }

    $colors = [];
    foreach (['primary', 'secondary', 'tertiary', 'text'] as $key) {
        $colors[$key] = $postText('colors_' . $key, $content_current['colors'][$key] ?? ($content_defaults['colors'][$key] ?? ''));
    }

    $normalizeButtonHex = static function (string $key, string $fallback): string {
        $raw = trim((string) ($_POST[$key] ?? ''));
        if (strtolower($raw) === 'transparent') {
            return 'transparent';
        }
        if ($raw === '' || !preg_match(CONCORSO_REGEX_HEX6, $raw)) {
            return strtolower($fallback);
        }
        return strtolower($raw);
    };
    $normalizeButtonSelect = static function (string $key, array $allowed, string $fallback): string {
        $raw = strtolower(trim((string) ($_POST[$key] ?? $fallback)));
        return in_array($raw, $allowed, true) ? $raw : $fallback;
    };
    $buttonConfig = [];
    foreach (['primary', 'secondary'] as $buttonKey) {
        $currentButton = is_array($content_current['buttons'][$buttonKey] ?? null) ? $content_current['buttons'][$buttonKey] : [];
        $defaultButton = is_array($content_defaults['buttons'][$buttonKey] ?? null) ? $content_defaults['buttons'][$buttonKey] : [];
        $fallbackBg = $buttonKey === 'primary'
            ? ($colors['primary'] ?? ($content_defaults['colors']['primary'] ?? CONCORSO_COLOR_PRIMARY))
            : ($colors['secondary'] ?? ($content_defaults['colors']['secondary'] ?? CONCORSO_COLOR_SECONDARY));
        $fallbackHover = $buttonKey === 'primary' ? '#0b5ed7' : '#5c636a';
        $fallbackText = $buttonKey === 'primary' ? 'PARTECIPA' : 'SECONDARIO';
        $buttonBg = $normalizeButtonHex(
            "buttons_{$buttonKey}_bg",
            (string) ($currentButton['bg'] ?? ($defaultButton['bg'] ?? $fallbackBg))
        );
        $buttonHoverBg = $normalizeButtonHex(
            "buttons_{$buttonKey}_hover_bg",
            (string) ($currentButton['hover_bg'] ?? ($defaultButton['hover_bg'] ?? $fallbackHover))
        );
        $rawMode = strtolower(trim((string) ($_POST["buttons_{$buttonKey}_mode"] ?? ($currentButton['mode'] ?? ($defaultButton['mode'] ?? 'color')))));
        $buttonUseGradient = !empty($_POST["buttons_{$buttonKey}_use_gradient"]);
        if ($rawMode === 'gradient') {
            $buttonMode = 'color';
            $buttonUseGradient = true;
        } else {
            $buttonMode = in_array($rawMode, ['color', 'image'], true) ? $rawMode : 'color';
        }
        $buttonImageSize = $normalizeButtonSelect(
            "buttons_{$buttonKey}_image_size",
            ['cover', 'contain', 'auto', 'custom'],
            (string) ($currentButton['image_size'] ?? ($defaultButton['image_size'] ?? 'cover'))
        );
        $buttonImagePosV = $normalizeButtonSelect(
            "buttons_{$buttonKey}_image_position_vertical",
            ['top', 'center', 'bottom'],
            (string) ($currentButton['image_position_vertical'] ?? ($defaultButton['image_position_vertical'] ?? 'center'))
        );
        $buttonImagePosH = $normalizeButtonSelect(
            "buttons_{$buttonKey}_image_position_horizontal",
            ['left', 'center', 'right'],
            (string) ($currentButton['image_position_horizontal'] ?? ($defaultButton['image_position_horizontal'] ?? 'center'))
        );
        $buttonGradientDir = $normalizeButtonSelect(
            "buttons_{$buttonKey}_gradient_direction",
            ['vertical', 'horizontal'],
            (string) ($currentButton['gradient_direction'] ?? ($defaultButton['gradient_direction'] ?? 'vertical'))
        );
        $buttonGradientStyle = $normalizeButtonSelect(
            "buttons_{$buttonKey}_gradient_style",
            ['linear', 'center'],
            (string) ($currentButton['gradient_style'] ?? ($defaultButton['gradient_style'] ?? 'center'))
        );
        $buttonGradientFrom = $normalizeButtonHex(
            "buttons_{$buttonKey}_gradient_from",
            (string) ($currentButton['gradient_from'] ?? ($defaultButton['gradient_from'] ?? $buttonBg))
        );
        $buttonGradientTo = $normalizeButtonHex(
            "buttons_{$buttonKey}_gradient_to",
            (string) ($currentButton['gradient_to'] ?? ($defaultButton['gradient_to'] ?? $buttonHoverBg))
        );
        $buttonGradientHoverFrom = $normalizeButtonHex(
            "buttons_{$buttonKey}_gradient_hover_from",
            (string) ($currentButton['gradient_hover_from'] ?? ($defaultButton['gradient_hover_from'] ?? $buttonGradientFrom))
        );
        $buttonGradientHoverTo = $normalizeButtonHex(
            "buttons_{$buttonKey}_gradient_hover_to",
            (string) ($currentButton['gradient_hover_to'] ?? ($defaultButton['gradient_hover_to'] ?? $buttonGradientTo))
        );
        $buttonBorder = $normalizeButtonHex(
            "buttons_{$buttonKey}_border",
            (string) ($currentButton['border'] ?? ($defaultButton['border'] ?? $buttonBg))
        );
        $buttonHoverBorder = $normalizeButtonHex(
            "buttons_{$buttonKey}_hover_border",
            (string) ($currentButton['hover_border'] ?? ($defaultButton['hover_border'] ?? $buttonHoverBg))
        );
        if ($buttonMode === 'image') {
            $buttonBg = 'transparent';
            $buttonBorder = 'transparent';
            $buttonHoverBg = 'transparent';
            $buttonHoverBorder = 'transparent';
            $buttonUseGradient = false;
        }
        if ($buttonMode === 'color' && $buttonUseGradient) {
            $buttonBg = $buttonGradientFrom;
            $buttonHoverBg = $buttonGradientHoverFrom;
        }
        $buttonConfig[$buttonKey] = [
            'text' => $postText("buttons_{$buttonKey}_text", (string) ($currentButton['text'] ?? ($defaultButton['text'] ?? $fallbackText))),
            'mode' => $buttonMode,
            'bg' => $buttonBg,
            'text_color' => $normalizeButtonHex("buttons_{$buttonKey}_text_color", (string) ($currentButton['text_color'] ?? ($defaultButton['text_color'] ?? CONCORSO_COLOR_WHITE))),
            'border' => $buttonBorder,
            'hover_bg' => $buttonHoverBg,
            'hover_border' => $buttonHoverBorder,
            'radius' => max(0, min(80, $postInt("buttons_{$buttonKey}_radius", (int) ($currentButton['radius'] ?? ($defaultButton['radius'] ?? 8))))),
            'font' => contest_resolve_font_family(
                $postText("buttons_{$buttonKey}_font", (string) ($currentButton['font'] ?? ($defaultButton['font'] ?? ''))),
                $content_current
            ),
            'font_size' => contest_resolve_font_size(
                $postText("buttons_{$buttonKey}_font_size", (string) ($currentButton['font_size'] ?? ($defaultButton['font_size'] ?? '')))
            ),
            'use_gradient' => $buttonUseGradient,
            'gradient_style' => $buttonGradientStyle,
            'gradient_direction' => $buttonGradientDir,
            'gradient_from' => $buttonGradientFrom,
            'gradient_to' => $buttonGradientTo,
            'gradient_hover_from' => $buttonGradientHoverFrom,
            'gradient_hover_to' => $buttonGradientHoverTo,
            'image' => trim((string) ($_POST["buttons_{$buttonKey}_image"] ?? ($currentButton['image'] ?? ($defaultButton['image'] ?? '')))),
            'hover_image' => trim((string) ($_POST["buttons_{$buttonKey}_hover_image"] ?? ($currentButton['hover_image'] ?? ($defaultButton['hover_image'] ?? '')))),
            'image_size' => $buttonImageSize,
            'image_size_custom' => $postText("buttons_{$buttonKey}_image_size_custom", (string) ($currentButton['image_size_custom'] ?? ($defaultButton['image_size_custom'] ?? ''))),
            'image_position_vertical' => $buttonImagePosV,
            'image_position_horizontal' => $buttonImagePosH,
            'hide_text_on_image' => !empty($_POST["buttons_{$buttonKey}_hide_text_on_image"]),
        ];
    }
    $buttonHelpText = $postText(
        'buttons_help_text',
        (string) ($content_current['buttons']['help']['text'] ?? ($content_defaults['buttons']['help']['text'] ?? 'INVIA'))
    );
    $buttonQuizSubmitText = $postText(
        'buttons_quiz_submit_text',
        (string) ($content_current['buttons']['quiz_submit_text'] ?? ($content_defaults['buttons']['quiz_submit_text'] ?? 'INVIA RISPOSTE'))
    );

    $backgrounds = [];
    foreach (['default', 'mobile', 'tablet', 'laptop', 'desktop', 'ultrawide'] as $bk) {
        $backgrounds[$bk] = $postText('backgrounds_' . $bk, $content_current['backgrounds'][$bk] ?? ($content_defaults['backgrounds'][$bk] ?? ''));
    }
    // Se il default viene svuotato, ripristina il default shipped (evita background vuoto in produzione).
    if (trim((string) ($backgrounds['default'] ?? '')) === '') {
        $backgrounds['default'] = (string) ($content_defaults['backgrounds']['default'] ?? '');
    }

    $newConfig = $content_defaults;
    $newConfig = concorso_apply_keys_preserved_from_current($newConfig, is_array($content_current) ? $content_current : []);
    $newConfig['name'] = $postText('name', $content_current['name'] ?? '');
    $newConfig['start_date'] = $postText('start_date', $content_current['start_date'] ?? '');
    $newConfig['end_date'] = $postText('end_date', $content_current['end_date'] ?? '');
    $newConfig['display_start_date'] = trim($postText('display_start_date', $content_current['display_start_date'] ?? ''));
    $newConfig['display_end_date'] = trim($postText('display_end_date', $content_current['display_end_date'] ?? ''));
    $dateFormatSelected = $postText('date_format', $content_current['date_format'] ?? CONCORSO_DATE_FORMAT);
    $newConfig['date_format'] = ($dateFormatSelected === 'custom') ? $postText('date_format_custom', $content_current['date_format'] ?? CONCORSO_DATE_FORMAT) : $dateFormatSelected;
    $newConfig['contest_mode'] = $contestMode;
    $newConfig['form_field_style'] = $formFieldStyle;
    $newConfig['form_field_font'] = trim($postText('form_field_font', $content_current['form_field_font'] ?? ''));
    $newConfig['form_field_font_size'] = contest_resolve_font_size($postText('form_field_font_size', $content_current['form_field_font_size'] ?? ''));
    $newConfig['form_field_border_radius'] = $formFieldBorderRadius;
    $newConfig['form_field_text_color'] = $formFieldTextColor;
    $newConfig['form_field_placeholder_color'] = $formFieldPlaceholderColor;
    $newConfig['form_field_border_color'] = $formFieldBorderColor;
    $newConfig['form_field_bg_color'] = $formFieldBgColor;
    $newConfig['form_field_focus_color'] = $formFieldFocusColor;
    $newConfig['form_field_checkbox_color'] = $formFieldCheckboxColor;
    $newConfig['form_field_consent_text_color'] = $formFieldConsentTextColor;
    $newConfig['form_field_consent_link_color'] = $formFieldConsentLinkColor;
    $newConfig['form_field_consent_link_hover_color'] = $formFieldConsentLinkHoverColor;
    $newConfig['form_field_select2_highlight_bg'] = $formFieldSelect2HighlightBg;
    $newConfig['form_field_select2_highlight_text'] = $formFieldSelect2HighlightText;
    $newConfig['form_field_calendar_popup_bg'] = $formFieldCalendarPopupBg;
    $newConfig['form_field_calendar_selected_color'] = $formFieldCalendarSelectedColor;
    $newConfig['form_field_calendar_hover_bg'] = $formFieldCalendarHoverBg;
    $newConfig['form_field_calendar_hover_text'] = $formFieldCalendarHoverText;
    $newConfig['form_field_bg_opacity'] = $formFieldBgOpacity;
    $newConfig['form_field_focus_opacity'] = $formFieldFocusOpacity;
    $newConfig['form_field_consent_link_hover_opacity'] = $formFieldConsentLinkHoverOpacity;
    $newConfig['form_field_error_mode'] = $formFieldErrorMode;
    $postedPrizeFlow = strtolower(trim((string) ($_POST['instantwin_prize_flow'] ?? '')));
    $scontrinoEnabledForIw = ($contestMode === 'instantwin') && !empty($fields_enabled['scontrino']);
    if ($scontrinoEnabledForIw) {
        if ($postedPrizeFlow === 'manager_confirm') {
            $newConfig['instantwin_auto_prize_with_receipt'] = false;
        } else {
            $newConfig['instantwin_auto_prize_with_receipt'] = true;
        }
    } else {
        $newConfig['instantwin_auto_prize_with_receipt'] = !empty($_POST['instantwin_auto_prize_with_receipt']);
    }
    $iwPrizeDistributionPost = strtolower($postText('instantwin_prize_distribution', $content_current['instantwin_prize_distribution'] ?? 'default'));
    if (!in_array($iwPrizeDistributionPost, ['default', 'pdv', 'cedi'], true)) {
        $iwPrizeDistributionPost = 'default';
    }
    if ($contestMode !== 'instantwin') {
        $iwPrizeDistributionPost = 'default';
    }
    $newConfig['instantwin_prize_distribution'] = $iwPrizeDistributionPost;
    $newConfig['instantwin_per_pdv'] = ($contestMode === 'instantwin') && ($iwPrizeDistributionPost === 'pdv');
    $newConfig['instantwin_per_cedi'] = ($contestMode === 'instantwin') && ($iwPrizeDistributionPost === 'cedi');
    if ($newConfig['instantwin_per_pdv']) {
        $pdvCount = 0;
        try {
            require_once __DIR__ . CONCORSO_APP_FUNCTIONS;
            $pdo_chk = function_exists('conn_pdo') ? conn_pdo() : null;
            if ($pdo_chk instanceof PDO) {
                $cnt = $pdo_chk->query("SELECT COUNT(DISTINCT `codice`) FROM `punti_vendita` WHERE `active` = 1 AND `codice` IS NOT NULL AND TRIM(`codice`) <> ''")->fetchColumn();
                $pdvCount = (int) $cnt;
            }
        } catch (Throwable $e) {
            $errors[] = 'Impossibile verificare i punti vendita: ' . $e->getMessage();
            $newConfig['instantwin_per_pdv'] = false;
            $newConfig['instantwin_prize_distribution'] = 'default';
        }
        if ($pdvCount < 2) {
            $errors[] = 'Instant Win per PDV: sono necessari almeno 2 punti vendita attivi con codice nella tabella punti_vendita. Importa o attiva i PDV, oppure disattiva questa opzione.';
            $newConfig['instantwin_per_pdv'] = false;
            $newConfig['instantwin_prize_distribution'] = 'default';
        }
    }
    $postedCodiceLabel = trim((string) ($_POST['instantwin_codice_label'] ?? ''));
    if ($postedCodiceLabel === '') {
        $postedCodiceLabel = (string) ($content_current['instantwin_codice_label'] ?? ($content_defaults['instantwin_codice_label'] ?? 'Codice partecipazione'));
    }
    $newConfig['instantwin_codice_label'] = $postedCodiceLabel;
    $newConfig['instantwin_codice_length_auto'] = ($contestMode === 'instantwin') && !empty($_POST['instantwin_codice_length_auto']);
    require_once __DIR__ . '/../../app/logic/codice_partecipazione_validation.php';
    $postedCodicePartMode = strtolower(trim((string) ($_POST['codice_partecipazione_mode'] ?? '')));
    if ($postedCodicePartMode === '' || !in_array($postedCodicePartMode, ['pool', 'direct_win', 'combinazione'], true)) {
        $mergedCodiceCfg = array_merge(
            is_array($content_defaults) ? $content_defaults : [],
            is_array($content_current) ? $content_current : []
        );
        $postedCodicePartMode = codice_partecipazione_resolve_mode($mergedCodiceCfg);
    }
    if ($contestMode !== 'instantwin' && $postedCodicePartMode === 'direct_win') {
        $postedCodicePartMode = 'pool';
    }
    $newConfig['codice_partecipazione_mode'] = $postedCodicePartMode;
    $newConfig['instantwin_codice_mode'] = $postedCodicePartMode === 'direct_win' ? 'direct_win' : 'instantwin';
    $newConfig['codice_partecipazione_strict_validation'] = ($postedCodicePartMode === 'combinazione') && !empty($_POST['codice_partecipazione_strict_validation']);
    if (!empty($fields_enabled['codice_partecipazione']) && codice_partecipazione_mode_requires_pool($postedCodicePartMode)) {
        $cp_pool_count = 0;
        $pdo_cp_check = isset($connection) && $connection instanceof PDO ? $connection : (function_exists('conn_pdo') ? conn_pdo() : null);
        if ($pdo_cp_check instanceof PDO) {
            try {
                $cp_pool_count = (int) $pdo_cp_check->query(
                    'SELECT COUNT(*) FROM codici_partecipazione WHERE codice IS NOT NULL AND codice <> \'\''
                )->fetchColumn();
            } catch (Throwable $e) {
                $cp_pool_count = 0;
            }
        }
        if ($cp_pool_count === 0) {
            $messages[] = 'Attenzione: la modalità codice richiede il pool DB ma la tabella codici_partecipazione è vuota. Importa i codici in Gestione Dati prima di aprire le iscrizioni.';
        }
    }
    $postedCodiceLengthRaw = trim((string) ($_POST['instantwin_codice_length'] ?? ''));
    $postedCodiceLength = null;
    if ($postedCodiceLengthRaw !== '') {
        $postedCodiceLength = (int) $postedCodiceLengthRaw;
        if ($postedCodiceLength < 1) {
            $postedCodiceLength = 1;
        } elseif ($postedCodiceLength > 255) {
            $postedCodiceLength = 255;
        }
    } else {
        $postedCodiceLength = isset($content_current['instantwin_codice_length'])
            ? (int) $content_current['instantwin_codice_length']
            : (int) ($content_defaults['instantwin_codice_length'] ?? 12);
    }
    $newConfig['instantwin_codice_length'] = $postedCodiceLength;
    $newConfig['quiz_enabled'] = !empty($_POST['quiz_enabled']);
    $newConfig['instantwin_shared_thankyou_page'] = ($contestMode === 'instantwin') && !empty($_POST['instantwin_shared_thankyou_page']);
    $qrAccessPost = is_array($_POST['qr_access'] ?? null) ? $_POST['qr_access'] : [];
    $qrAccessDefaultsSave = is_array($content_defaults['qr_access'] ?? null) ? $content_defaults['qr_access'] : [];
    $qrErrorsListPost = is_array($qrAccessPost['errors_list'] ?? null) ? $qrAccessPost['errors_list'] : [];
    $qrErrorsMerged = [];
    require_once __DIR__ . '/../../app/inc/qr_config.php';
    foreach ($qrErrorsListPost as $qrErrorRow) {
        if (!is_array($qrErrorRow)) {
            continue;
        }
        $qrErrKey = qr_config_map_error_reason((string) ($qrErrorRow['key'] ?? ''));
        if (!in_array($qrErrKey, qr_config_system_error_keys(), true)) {
            continue;
        }
        $qrErrTitle = trim((string) ($qrErrorRow['title'] ?? ''));
        $qrErrText = trim((string) ($qrErrorRow['text'] ?? ''));
        if ($qrErrTitle === '' && $qrErrText === '') {
            continue;
        }
        $qrErrorsMerged[$qrErrKey] = [
            'title' => $qrErrTitle,
            'text' => $qrErrText,
        ];
    }
    $qrReceiptModePost = strtolower(trim((string) ($qrAccessPost['receipt_date_mode'] ?? 'contest_dates')));
    if ($qrReceiptModePost === 'whitelist') {
        $qrReceiptModePost = 'contest_dates';
    }
    if (!in_array($qrReceiptModePost, ['none', 'min', 'contest_dates'], true)) {
        $qrReceiptModePost = 'contest_dates';
    }
    $signatureAlgorithms = [];
    if (!empty($qrAccessPost['signature_sha256'])) {
        $signatureAlgorithms[] = 'sha256';
    }
    if (!empty($qrAccessPost['signature_sha1'])) {
        $signatureAlgorithms[] = 'sha1';
    }
    if ($signatureAlgorithms === []) {
        $signatureAlgorithms = ['sha256', 'sha1'];
    }
    $newConfig['qr_access'] = [
        'enabled' => !empty($qrAccessPost['enabled']),
        'verify_signature' => !empty($qrAccessPost['verify_signature']),
        'signature_algorithms' => $signatureAlgorithms,
        'public_key_sp' => trim((string) ($qrAccessPost['public_key_sp'] ?? '')),
        'public_key_mc' => trim((string) ($qrAccessPost['public_key_mc'] ?? '')),
        'contest_code' => trim((string) ($qrAccessPost['contest_code'] ?? '')),
        'verify_contest_code_sp' => !empty($qrAccessPost['verify_contest_code_sp']),
        'verify_contest_code_mc' => !empty($qrAccessPost['verify_contest_code_mc']),
        'max_uses_per_code' => max(1, (int) ($qrAccessPost['max_uses_per_code'] ?? 1)),
        'use_payload_giocate' => !empty($qrAccessPost['use_payload_giocate']),
        'rate_limit_seconds' => max(0, (int) ($qrAccessPost['rate_limit_seconds'] ?? 10)),
        'receipt_date_mode' => $qrReceiptModePost,
        'receipt_date_min' => preg_replace('/\D/', '', (string) ($qrAccessPost['receipt_date_min'] ?? '')),
        'errors' => $qrErrorsMerged,
    ];
    $quizNumQPost = isset($_POST['quiz_num_questions']) ? (int) $_POST['quiz_num_questions'] : (int) ($content_current['quiz']['num_questions'] ?? ($content_defaults['quiz']['num_questions'] ?? 4));
    $quizNumQPost = max(1, min(20, $quizNumQPost));
    $quizModePost = strtolower(trim((string) ($_POST['quiz_mode'] ?? ($content_current['quiz']['mode'] ?? ($content_defaults['quiz']['mode'] ?? 'standard')))));
    if (!in_array($quizModePost, ['choice', 'audio', 'standard'], true)) {
        $quizModePost = 'standard';
    }
    $quizPreserve = array_merge($content_defaults['quiz'] ?? [], $content_current['quiz'] ?? []);
    $newConfig['quiz'] = array_merge($quizPreserve, [
        'num_questions' => $quizNumQPost,
        'mode' => $quizModePost,
        'require_all_correct' => array_key_exists('quiz_require_all_correct', $_POST)
            ? !empty($_POST['quiz_require_all_correct'])
            : !empty($quizPreserve['require_all_correct']),
        'instantwin_separate_thankyou_quiz_failed' => !empty($quizPreserve['instantwin_separate_thankyou_quiz_failed']),
    ]);
    $newConfig['min_spend'] = $postFloat('min_spend', (float) ($content_current['min_spend'] ?? $content_defaults['min_spend'] ?? 0));
    $prizeTotalRaw = trim((string) ($_POST['prize_total'] ?? ''));
    $prizeTotalRaw = str_replace(' ', '', $prizeTotalRaw);
    $prizeTotalFallback = (float) ($content_current['prize_total'] ?? $content_defaults['prize_total'] ?? 0);
    if ($prizeTotalRaw === '') {
        $newConfig['prize_total'] = $prizeTotalFallback;
    } else {
        if (strpos($prizeTotalRaw, ',') !== false) {
            $parts = explode(',', $prizeTotalRaw, 2);
            $intPart = str_replace('.', '', trim($parts[0]));
            $decPart = preg_replace('/\D/', '', $parts[1] ?? '');
            $prizeTotalNormalized = $intPart . '.' . $decPart;
        } else {
            $lastDot = strrpos($prizeTotalRaw, '.');
            if ($lastDot !== false && preg_match('/^\d{1,2}$/', substr($prizeTotalRaw, $lastDot + 1))) {
                $prizeTotalNormalized = str_replace('.', '', substr($prizeTotalRaw, 0, $lastDot)) . '.' . substr($prizeTotalRaw, $lastDot + 1);
            } else {
                $prizeTotalNormalized = str_replace('.', '', $prizeTotalRaw);
            }
        }
        if (!is_numeric($prizeTotalNormalized)) {
            $errors[] = 'Il montepremi deve essere un numero valido (es. 1000 o 1000,50). Inserisci solo cifre e eventuale virgola o punto decimale.';
            $newConfig['prize_total'] = $prizeTotalFallback;
        } else {
            $prizeTotalVal = (float) $prizeTotalNormalized;
            if ($prizeTotalVal < 0) {
                $errors[] = 'Il montepremi non può essere negativo.';
                $newConfig['prize_total'] = $prizeTotalFallback;
            } elseif ($prizeTotalVal > 1000000) {
                $errors[] = 'Il montepremi non può superare 1.000.000.';
                $newConfig['prize_total'] = $prizeTotalFallback;
            } else {
                $newConfig['prize_total'] = $prizeTotalVal;
            }
        }
    }
    $newConfig['base_url'] = '';
    // Premi (solo Instant Win): matrice Ce.Di. oppure righe legacy
    $premiMatrixForSave = null;
    $customWeekPlanForSave = null;
    $cediSchemaConfig = is_array($content_current) ? $content_current : [];
    if ($iwPrizeDistributionPost === 'cedi') {
        require_once __DIR__ . '/../../app/logic/instantwin_cedi/cedi_core.php';
        $cediSchemaErrors = [];
        $cediDefsList = [];
        if (isset($_POST['cedi_definitions']) && is_array($_POST['cedi_definitions'])) {
            $cediDefsList = instantwin_cedi_parse_definitions_post($_POST['cedi_definitions'], $cediSchemaErrors);
        }
        $cediValsList = [];
        $cediPrizeDefsList = [];
        if (isset($_POST['cedi_prize_definitions']) && is_array($_POST['cedi_prize_definitions'])) {
            $cediPrizeDefsList = instantwin_cedi_parse_prize_definitions_post($_POST['cedi_prize_definitions'], $cediSchemaErrors);
            foreach ($cediPrizeDefsList as $def) {
                $cediValsList[] = (float) $def['value'];
            }
        } elseif (isset($_POST['cedi_prize_values']) && is_array($_POST['cedi_prize_values'])) {
            $cediValsList = instantwin_cedi_parse_prize_values_post($_POST['cedi_prize_values'], $cediSchemaErrors);
            foreach ($cediValsList as $v) {
                $cediPrizeDefsList[] = ['value' => $v, 'tipo' => ''];
            }
        }
        foreach ($cediSchemaErrors as $schemaMsg) {
            $errors[] = $schemaMsg;
        }
        if (!empty($cediSchemaErrors)) {
            $has_premi_validation_error = true;
        }
        $newConfig['cedi_definitions'] = $cediDefsList;
        $newConfig['cedi_prize_values'] = $cediValsList;
        $newConfig['cedi_prize_definitions'] = $cediPrizeDefsList;
        $cediSchemaConfig = array_merge($cediSchemaConfig, [
            'cedi_definitions' => $cediDefsList,
            'cedi_prize_values' => $cediValsList,
            'cedi_prize_definitions' => $cediPrizeDefsList,
        ]);
        if ($cediDefsList === []) {
            $errors[] = 'Inserire almeno un Ce.Di. (codice e nome).';
            $has_premi_validation_error = true;
        }
        if ($cediValsList === []) {
            $errors[] = 'Inserire almeno un importo premio (€).';
            $has_premi_validation_error = true;
        }
    } else {
        $newConfig = concorso_preserve_cedi_schema_from_current(
            $newConfig,
            is_array($content_current) ? $content_current : [],
            is_array($content_defaults) ? $content_defaults : []
        );
    }
    if ($iwPrizeDistributionPost === 'cedi' && isset($_POST['premi_matrix']) && is_array($_POST['premi_matrix'])) {
        if (!function_exists('instantwin_cedi_parse_matrix_post')) {
            require_once __DIR__ . '/../../app/logic/instantwin_cedi/cedi_core.php';
        }
        $matrixErrors = [];
        $premiMatrixForSave = instantwin_cedi_parse_matrix_post($_POST['premi_matrix'], $matrixErrors, $cediSchemaConfig);
        if ($premiMatrixForSave === null) {
            foreach ($matrixErrors as $msg) {
                $errors[] = $msg;
            }
            $has_premi_validation_error = true;
        } else {
            $matrixSum = 0;
            foreach ($premiMatrixForSave as $row) {
                $matrixSum += array_sum($row);
            }
            if ($matrixSum <= 0) {
                $errors[] = 'La matrice premi per Ce.Di. deve contenere almeno un coupon.';
                $has_premi_validation_error = true;
            } else {
                $new_premi = instantwin_cedi_matrix_to_premi_config($premiMatrixForSave, $cediSchemaConfig);
            }
        }
        $newConfig['premi_matrix_weeks_custom'] = !empty($_POST['premi_matrix_weeks_custom']);
        if ($premiMatrixForSave !== null && !empty($newConfig['premi_matrix_weeks_custom'])) {
            $weekCountEarly = 6;
            try {
                $weekCountEarly = instantwin_cedi_contest_week_count(
                    $newConfig['start_date'] ?? ($content_current['start_date'] ?? null),
                    $newConfig['end_date'] ?? ($content_current['end_date'] ?? null)
                );
            } catch (Throwable $e) {
                $weekCountEarly = 6;
            }
            $weekPlanErrorsEarly = [];
            if (!isset($_POST['premi_matrix_weeks']) || !is_array($_POST['premi_matrix_weeks']) || $_POST['premi_matrix_weeks'] === []) {
                $weekPlanErrorsEarly[] = 'Ripartizione settimanale personalizzata mancante: compilare il passo 4.';
            } else {
                $customWeekPlanForSave = instantwin_cedi_parse_week_plan_post(
                    $_POST['premi_matrix_weeks'],
                    $weekCountEarly,
                    $weekPlanErrorsEarly,
                    $cediSchemaConfig
                );
                if ($customWeekPlanForSave !== null) {
                    instantwin_cedi_validate_week_plan_against_matrix(
                        $customWeekPlanForSave,
                        $premiMatrixForSave,
                        $weekCountEarly,
                        $weekPlanErrorsEarly,
                        $cediSchemaConfig
                    );
                    if (!empty($weekPlanErrorsEarly)) {
                        $customWeekPlanForSave = null;
                    }
                }
            }
            foreach ($weekPlanErrorsEarly as $msg) {
                $errors[] = $msg;
            }
            if (!empty($weekPlanErrorsEarly)) {
                $has_premi_validation_error = true;
            }
        }
    }
    $premiPost = isset($_POST['premi']) && is_array($_POST['premi']) ? $_POST['premi'] : [];
    $new_premi = $new_premi ?? [];
    $has_premi_validation_error = $has_premi_validation_error ?? false;
    $premiIdx = 0;
    if ($premiMatrixForSave === null) {
    foreach ($premiPost as $p) {
        $premiIdx++;
        $valoreRaw = isset($p['valore']) ? trim((string)$p['valore']) : '';
        $valore = str_replace([',', ' '], ['.', ''], $valoreRaw);
        $tipo = isset($p['tipo_premio']) ? trim((string)$p['tipo_premio']) : '';
        $qt = isset($p['quantita_totale']) ? (int)$p['quantita_totale'] : 0;
        $active = isset($p['is_active']) && ($p['is_active'] === '1' || $p['is_active'] === 1 || $p['is_active'] === true);
        if ($valore === '' && $qt <= 0 && $tipo === '') {
            continue; // riga completamente vuota, ignorata
        }
        // Valore vuoto → 0 (premi senza importo monetario)
        if ($valore === '') {
            $valore = '0';
        }
        if (!is_numeric($valore)) {
            $errors[] = "Tipi di premi (riga {$premiIdx}): il valore deve essere un numero (solo cifre, eventuale virgola o punto decimale).";
            $has_premi_validation_error = true;
            continue;
        }
        if ($qt <= 0) {
            $errors[] = "Tipi di premi (riga {$premiIdx}): la quantità deve essere almeno 1.";
            $has_premi_validation_error = true;
            continue;
        }
        $valoreFloat = (float)$valore;
        if ($valoreFloat < 0) {
            $errors[] = "Tipi di premi (riga {$premiIdx}): il valore non può essere negativo.";
            $has_premi_validation_error = true;
            continue;
        }
        if ($valoreFloat > 1000000) {
            $errors[] = "Tipi di premi (riga {$premiIdx}): il valore non può superare 1.000.000.";
            $has_premi_validation_error = true;
            continue;
        }
        if ($qt > 1000000) {
            $errors[] = "Tipi di premi (riga {$premiIdx}): la quantità non può superare 1.000.000.";
            $has_premi_validation_error = true;
            continue;
        }
        $tipoFinal = $tipo !== '' ? $tipo : 'Premio';
        if (mb_strlen($tipoFinal) < 2) {
            $errors[] = "Tipi di premi (riga {$premiIdx}): il tipo premio deve avere almeno 2 caratteri.";
            $has_premi_validation_error = true;
            continue;
        }
        if (mb_strlen($tipoFinal) > 255) {
            $errors[] = "Tipi di premi (riga {$premiIdx}): il tipo premio non può superare 255 caratteri.";
            $has_premi_validation_error = true;
            continue;
        }
        $new_premi[] = ['valore' => $valoreFloat, 'tipo_premio' => $tipoFinal, 'quantita_totale' => $qt, 'is_active' => $active];
    }
    }
    $newConfig['premi'] = $new_premi;
    if ($premiMatrixForSave !== null) {
        $newConfig['premi_matrix'] = $premiMatrixForSave;
    } else {
        $newConfig = concorso_preserve_premi_matrix_from_current(
            $newConfig,
            is_array($content_current) ? $content_current : []
        );
    }
    $newConfig['published'] = (isset($_POST['published']) && $_POST['published'] === '1');
    $maxFileSizeDefaultMb = max($maxUploadMinMb, min($maxUploadMaxMb, (int) round($maxFileSizeBytesCurrent / (1024 * 1024))));
    $maxFileSizeMb = $postInt('max_file_size_mb', $maxFileSizeDefaultMb);
    $newConfig['max_file_size'] = $maxFileSizeMb * 1024 * 1024;
    $newConfig['receipt_allowed_extensions'] = $receiptAllowedTypesSelected;
    $newConfig['promoter'] = $postText('promoter', $content_current['promoter'] ?? '');
    $newConfig['promoter_link'] = $postText('promoter_link', $content_current['promoter_link'] ?? ($content_defaults['promoter_link'] ?? ''));
    $newConfig['delegate'] = $postText('delegate', $content_current['delegate'] ?? '');
    $newConfig['delegate_link'] = $postText('delegate_link', $content_current['delegate_link'] ?? ($content_defaults['delegate_link'] ?? ''));
    $newConfig['helpdesk_email'] = $postText('helpdesk_email', $content_current['helpdesk_email'] ?? ($content_defaults['helpdesk_email'] ?? ''));
    // SMTP: solo .env (mai POST / JSON). Se il transport è già in .env, svuota i residui JSON.
    if (function_exists('smtp_env_has_transport') && smtp_env_has_transport()) {
        foreach (smtp_env_blank_json_smtp() as $smtpJsonKey => $smtpJsonVal) {
            $newConfig[$smtpJsonKey] = $smtpJsonVal;
        }
    } else {
        $newConfig['smtp_host'] = (string) ($content_current['smtp_host'] ?? ($content_defaults['smtp_host'] ?? ''));
        $newConfig['smtp_port'] = (int) ($content_current['smtp_port'] ?? ($content_defaults['smtp_port'] ?? 587));
        $newConfig['smtp_username'] = (string) ($content_current['smtp_username'] ?? ($content_defaults['smtp_username'] ?? ''));
        $newConfig['smtp_password'] = (string) ($content_current['smtp_password'] ?? ($content_defaults['smtp_password'] ?? ''));
        $newConfig['smtp_from_email'] = (string) ($content_current['smtp_from_email'] ?? ($content_defaults['smtp_from_email'] ?? ''));
        $newConfig['smtp_from_name'] = (string) ($content_current['smtp_from_name'] ?? ($content_defaults['smtp_from_name'] ?? ''));
        $newConfig['smtp_reply_to'] = '';
    }
    $newConfig['privacy_link'] = $postText('privacy_link', $content_current['privacy_link'] ?? '');
    $newConfig['rules_link'] = $postText('rules_link', $content_current['rules_link'] ?? '');
    $newConfig['cookie_policy_link'] = $postText('cookie_policy_link', $content_current['cookie_policy_link'] ?? ($content_defaults['cookie_policy_link'] ?? ''));

    require_once dirname(__DIR__, 2) . '/app/inc/cookie_consent.php';
    $ccDefaults = is_array($content_defaults['cookie_consent'] ?? null) ? $content_defaults['cookie_consent'] : [];
    $ccEnabled = isset($_POST['cookie_consent_enabled']) && $_POST['cookie_consent_enabled'] === '1';
    if (!$ccEnabled) {
        // UI minimaliste: i dettagli non sono in POST — conserva la config esistente, spegni solo enabled
        $preserved = cookie_consent_normalize($content_current['cookie_consent'] ?? [], $ccDefaults);
        $preserved['enabled'] = false;
        $newConfig['cookie_consent'] = $preserved;
    } else {
    $ccPagesPost = [];
    foreach (cookie_consent_default_page_keys() as $pageKey => $_label) {
        $ccPagesPost[$pageKey] = [
            'banner' => isset($_POST['cookie_consent_page_banner'][$pageKey]),
            'tracking' => isset($_POST['cookie_consent_page_tracking'][$pageKey]),
        ];
    }
    $ccTech = [];
    $techNames = isset($_POST['cookie_consent_tech_name']) && is_array($_POST['cookie_consent_tech_name']) ? $_POST['cookie_consent_tech_name'] : [];
    $techPurpose = isset($_POST['cookie_consent_tech_purpose']) && is_array($_POST['cookie_consent_tech_purpose']) ? $_POST['cookie_consent_tech_purpose'] : [];
    $techDuration = isset($_POST['cookie_consent_tech_duration']) && is_array($_POST['cookie_consent_tech_duration']) ? $_POST['cookie_consent_tech_duration'] : [];
    $techProvider = isset($_POST['cookie_consent_tech_provider']) && is_array($_POST['cookie_consent_tech_provider']) ? $_POST['cookie_consent_tech_provider'] : [];
    foreach ($techNames as $i => $name) {
        $row = cookie_consent_normalize_cookie_row([
            'name' => $name,
            'purpose' => $techPurpose[$i] ?? '',
            'duration' => $techDuration[$i] ?? '',
            'provider' => $techProvider[$i] ?? '',
        ]);
        if ($row !== null) {
            $ccTech[] = $row;
        }
    }
    $ccMkt = [];
    // Marketing cookie names are not enumerated here: they depend on GTM/Meta tags.
    // Keep empty; the client documents them in Privacy / Cookie Policy.
    $styleKeys = [
        'bg_color', 'text_color', 'link_color', 'border_color',
        'button_bg', 'button_text', 'button_secondary_bg', 'button_secondary_text',
    ];
    $ccStylePost = [];
    foreach ($styleKeys as $sk) {
        $ccStylePost[$sk] = $postText('cookie_consent_style_' . $sk, $ccDefaults['style'][$sk] ?? '#333333');
    }
    $newConfig['cookie_consent'] = cookie_consent_normalize([
        'enabled' => true,
        'sync_registration_marketing' => isset($_POST['cookie_consent_sync_marketing']) && $_POST['cookie_consent_sync_marketing'] === '1',
        'consent_cookie_name' => (static function () use ($ccTech, $ccDefaults): string {
            foreach ($ccTech as $techRow) {
                if (!is_array($techRow)) {
                    continue;
                }
                $kind = cookie_consent_core_technical_kind($techRow);
                if ($kind === 'consent') {
                    return (string) ($techRow['name'] ?? '');
                }
            }

            return (string) ($ccDefaults['consent_cookie_name'] ?? 'gst_cookie_consent');
        })(),
        'banner' => [
            'text' => $postText('cookie_consent_text', $ccDefaults['banner']['text'] ?? ''),
            'accept_label' => $postText('cookie_consent_accept_label', $ccDefaults['banner']['accept_label'] ?? ''),
            'preferences_label' => $postText('cookie_consent_preferences_label', $ccDefaults['banner']['preferences_label'] ?? ''),
            'save_label' => $postText('cookie_consent_save_label', $ccDefaults['banner']['save_label'] ?? ''),
            'necessary_title' => $postText('cookie_consent_necessary_title', $ccDefaults['banner']['necessary_title'] ?? ''),
            'necessary_desc' => $postText('cookie_consent_necessary_desc', $ccDefaults['banner']['necessary_desc'] ?? ''),
            'marketing_title' => $postText('cookie_consent_marketing_title', $ccDefaults['banner']['marketing_title'] ?? ''),
            'marketing_desc' => $postText('cookie_consent_marketing_desc', $ccDefaults['banner']['marketing_desc'] ?? ''),
            'policy_link_label' => $postText('cookie_consent_policy_label', $ccDefaults['banner']['policy_link_label'] ?? ''),
        ],
        'style' => $ccStylePost,
        'tracking' => [
            'gtm_container_id' => $postText('cookie_consent_gtm', ''),
            'meta_pixel_id' => $postText('cookie_consent_meta', ''),
        ],
        'cookies' => [
            'technical' => $ccTech,
            'marketing' => $ccMkt,
        ],
        'pages' => $ccPagesPost,
    ], $ccDefaults);
    // Keep technical list in sync: PHPSESSID + cookie preferenze always present.
    $resolvedConsentName = cookie_consent_resolve_storage_name($newConfig['cookie_consent']);
    $newConfig['cookie_consent']['consent_cookie_name'] = $resolvedConsentName;
    $newConfig['cookie_consent']['cookies']['technical'] = cookie_consent_ensure_core_technical(
        is_array($newConfig['cookie_consent']['cookies']['technical'] ?? null)
            ? $newConfig['cookie_consent']['cookies']['technical']
            : [],
        $resolvedConsentName
    );
    }

    $logoDisplayMode = strtolower(trim($postText('logo_display_mode', $content_current['logo_display_mode'] ?? ($content_defaults['logo_display_mode'] ?? 'logo'))));
    $newConfig['logo_display_mode'] = in_array($logoDisplayMode, ['none', 'logo', 'header', 'both'], true) ? $logoDisplayMode : 'logo';
    $newConfig['logo_enabled'] = in_array($newConfig['logo_display_mode'], ['logo', 'both'], true);
    $newConfig['logo'] = $postText('logo', $content_current['logo'] ?? '');
    $newConfig['logo_mobile'] = $postText('logo_mobile', $content_current['logo_mobile'] ?? ($content_current['logo'] ?? ''));
    $newConfig['header_image'] = $postText('header_image', $content_current['header_image'] ?? ($content_defaults['header_image'] ?? ''));
    $newConfig['header_image_mobile'] = $postText('header_image_mobile', $content_current['header_image_mobile'] ?? ($content_defaults['header_image_mobile'] ?? ''));
    $logoDesktopSize = strtolower(trim($postText('logo_desktop_size', $content_current['logo_desktop_size'] ?? ($content_defaults['logo_desktop_size'] ?? 'medium'))));
    $newConfig['logo_desktop_size'] = in_array($logoDesktopSize, ['compact', 'medium', 'large', 'custom'], true)
        ? $logoDesktopSize
        : ($content_defaults['logo_desktop_size'] ?? 'medium');
    $logoDesktopWidthPct = $postInt('logo_desktop_width_percent', (int) ($content_current['logo_desktop_width_percent'] ?? ($content_defaults['logo_desktop_width_percent'] ?? 85)));
    $newConfig['logo_desktop_width_percent'] = max(40, min(100, $logoDesktopWidthPct));
    $logoDesktopMaxHeightPx = $postInt('logo_desktop_max_height_px', (int) ($content_current['logo_desktop_max_height_px'] ?? ($content_defaults['logo_desktop_max_height_px'] ?? 0)));
    $newConfig['logo_desktop_max_height_px'] = max(0, min(500, $logoDesktopMaxHeightPx));
    $logoTopSpacingDefaults = normalize_logo_top_spacing_config($content_defaults['logo_top_spacing'] ?? []);
    $logoTopSpacingCurrent = normalize_logo_top_spacing_config($content_current['logo_top_spacing'] ?? [], $logoTopSpacingDefaults);
    $newConfig['logo_top_spacing'] = [
        'mobile_px' => brand_layout_clamp_logo_top_spacing($postInt('logo_top_spacing_mobile_px', (int) ($logoTopSpacingCurrent['mobile_px'] ?? 0))),
        'tablet_px' => brand_layout_clamp_logo_top_spacing($postInt('logo_top_spacing_tablet_px', (int) ($logoTopSpacingCurrent['tablet_px'] ?? 0))),
        'desktop_px' => brand_layout_clamp_logo_top_spacing($postInt('logo_top_spacing_desktop_px', (int) ($logoTopSpacingCurrent['desktop_px'] ?? 0))),
    ];
    $headerLogoGapDefaults = normalize_brand_header_logo_gap_config($content_defaults['brand_header_logo_gap'] ?? []);
    $headerLogoGapCurrent = normalize_brand_header_logo_gap_config($content_current['brand_header_logo_gap'] ?? [], $headerLogoGapDefaults);
    $newConfig['brand_header_logo_gap'] = [
        'mobile_px' => brand_layout_clamp_header_logo_gap($postInt('brand_header_logo_gap_mobile_px', (int) ($headerLogoGapCurrent['mobile_px'] ?? 0))),
        'tablet_px' => brand_layout_clamp_header_logo_gap($postInt('brand_header_logo_gap_tablet_px', (int) ($headerLogoGapCurrent['tablet_px'] ?? 0))),
        'desktop_px' => brand_layout_clamp_header_logo_gap($postInt('brand_header_logo_gap_desktop_px', (int) ($headerLogoGapCurrent['desktop_px'] ?? 0))),
    ];
    $headerTopSpacingDefaults = normalize_brand_header_top_spacing_config($content_defaults['brand_header_top_spacing'] ?? []);
    $headerTopSpacingCurrent = normalize_brand_header_top_spacing_config($content_current['brand_header_top_spacing'] ?? [], $headerTopSpacingDefaults);
    $newConfig['brand_header_top_spacing'] = [
        'mobile_px' => brand_layout_clamp_header_top_spacing($postInt('brand_header_top_spacing_mobile_px', (int) ($headerTopSpacingCurrent['mobile_px'] ?? 0))),
        'tablet_px' => brand_layout_clamp_header_top_spacing($postInt('brand_header_top_spacing_tablet_px', (int) ($headerTopSpacingCurrent['tablet_px'] ?? 0))),
        'desktop_px' => brand_layout_clamp_header_top_spacing($postInt('brand_header_top_spacing_desktop_px', (int) ($headerTopSpacingCurrent['desktop_px'] ?? 0))),
    ];
    $brandPageLayoutCurrent = normalize_brand_layout_collection(
        $content_current['brand_page_layout'] ?? [],
        is_array($content_defaults['brand_page_layout'] ?? null) ? $content_defaults['brand_page_layout'] : []
    );
    $newConfig['brand_page_layout'] = [];
    foreach (brand_layout_page_definitions() as $pageKey => $pageLabel) {
        $rawPagePost = $_POST['brand_page_layout'][$pageKey] ?? [];
        $rawPagePost = is_array($rawPagePost) ? $rawPagePost : [];
        $preset = strtolower(trim((string) ($rawPagePost['preset'] ?? ($brandPageLayoutCurrent[$pageKey]['preset'] ?? 'attached'))));
        $customizePerSize = !empty($rawPagePost['customize_per_size']);
        $mobileOverlap = brand_layout_clamp_overlap((int) ($rawPagePost['mobile']['overlap_px'] ?? ($brandPageLayoutCurrent[$pageKey]['mobile']['overlap_px'] ?? 0)), 'mobile');
        $tabletOverlap = $customizePerSize
            ? brand_layout_clamp_overlap((int) ($rawPagePost['tablet']['overlap_px'] ?? ($brandPageLayoutCurrent[$pageKey]['tablet']['overlap_px'] ?? $mobileOverlap)), 'tablet')
            : $mobileOverlap;
        $desktopOverlap = $customizePerSize
            ? brand_layout_clamp_overlap((int) ($rawPagePost['desktop']['overlap_px'] ?? ($brandPageLayoutCurrent[$pageKey]['desktop']['overlap_px'] ?? $mobileOverlap)), 'desktop')
            : $mobileOverlap;
        $newConfig['brand_page_layout'][$pageKey] = normalize_brand_layout_item([
            'preset' => $preset,
            'customize_per_size' => $customizePerSize,
            'mobile' => ['overlap_px' => $mobileOverlap],
            'tablet' => ['overlap_px' => $tabletOverlap],
            'desktop' => ['overlap_px' => $desktopOverlap],
        ]);
    }
    $newConfig['favicon'] = $postText('favicon', $content_current['favicon'] ?? '');
    $newConfig['favicon_mobile'] = $postText('favicon_mobile', $content_current['favicon_mobile'] ?? ($content_current['favicon'] ?? ''));
    $newConfig['mail_banner'] = $postText('mail_banner', $content_current['mail_banner'] ?? ($content_defaults['mail_banner'] ?? ''));
    // Se l'utente "cancella" un asset di branding, ripristina il default invece di salvare stringa vuota.
    $logoFallback = trim((string) ($content_defaults['logo'] ?? ''));
    if ($newConfig['logo'] === '' && $logoFallback !== '') {
        $newConfig['logo'] = $logoFallback;
    }
    $logoMobileFallback = trim((string) ($content_defaults['logo_mobile'] ?? $newConfig['logo']));
    if ($newConfig['logo_mobile'] === '' && $logoMobileFallback !== '') {
        $newConfig['logo_mobile'] = $logoMobileFallback;
    }
    $faviconFallback = trim((string) ($content_defaults['favicon'] ?? ''));
    if ($newConfig['favicon'] === '' && $faviconFallback !== '') {
        $newConfig['favicon'] = $faviconFallback;
    }
    $faviconMobileFallback = trim((string) ($content_defaults['favicon_mobile'] ?? $newConfig['favicon']));
    if ($newConfig['favicon_mobile'] === '' && $faviconMobileFallback !== '') {
        $newConfig['favicon_mobile'] = $faviconMobileFallback;
    }
    $mailBannerFallback = trim((string) ($content_defaults['mail_banner'] ?? 'assets/img/default_img/banner.png'));
    if ($newConfig['mail_banner'] === '' && $mailBannerFallback !== '') {
        $newConfig['mail_banner'] = $mailBannerFallback;
    }
    $newConfig['background_mode'] = $bgMode;
    $newConfig['background_scope'] = $bgScope;
    $newConfig['background_color'] = $postText('background_color', $content_current['background_color'] ?? ($content_defaults['background_color'] ?? CONCORSO_COLOR_WHITE));
    $newConfig['backgrounds'] = $backgrounds;
    
    $newConfig['background_style'] = [
        'position_vertical' => $postText('background_style_position_vertical', $content_current['background_style']['position_vertical'] ?? ($content_defaults['background_style']['position_vertical'] ?? 'center')),
        'position_horizontal' => $postText('background_style_position_horizontal', $content_current['background_style']['position_horizontal'] ?? ($content_defaults['background_style']['position_horizontal'] ?? 'center')),
        'size' => $postText('background_style_size', $content_current['background_style']['size'] ?? ($content_defaults['background_style']['size'] ?? 'cover')),
        'size_custom' => $postText('background_style_size_custom', $content_current['background_style']['size_custom'] ?? ($content_defaults['background_style']['size_custom'] ?? '')),
        'repeat' => $postText('background_style_repeat', $content_current['background_style']['repeat'] ?? ($content_defaults['background_style']['repeat'] ?? 'no-repeat')),
        'attachment' => $postText('background_style_attachment', $content_current['background_style']['attachment'] ?? ($content_defaults['background_style']['attachment'] ?? 'fixed')),
        'customize_per_size' => isset($_POST['background_style_customize_per_size']) && $_POST['background_style_customize_per_size'] === '1',
    ];
    
    foreach (['mobile', 'tablet', 'laptop', 'desktop', 'ultrawide'] as $sizeKey) {
        $newConfig['background_style'][$sizeKey] = [
            'position_vertical' => $postText('background_style_' . $sizeKey . '_position_vertical', $content_current['background_style'][$sizeKey]['position_vertical'] ?? ($content_defaults['background_style'][$sizeKey]['position_vertical'] ?? 'center')),
            'position_horizontal' => $postText('background_style_' . $sizeKey . '_position_horizontal', $content_current['background_style'][$sizeKey]['position_horizontal'] ?? ($content_defaults['background_style'][$sizeKey]['position_horizontal'] ?? 'center')),
            'size' => $postText('background_style_' . $sizeKey . '_size', $content_current['background_style'][$sizeKey]['size'] ?? ($content_defaults['background_style'][$sizeKey]['size'] ?? 'cover')),
            'size_custom' => $postText('background_style_' . $sizeKey . '_size_custom', $content_current['background_style'][$sizeKey]['size_custom'] ?? ($content_defaults['background_style'][$sizeKey]['size_custom'] ?? '')),
            'repeat' => $postText('background_style_' . $sizeKey . '_repeat', $content_current['background_style'][$sizeKey]['repeat'] ?? ($content_defaults['background_style'][$sizeKey]['repeat'] ?? 'no-repeat')),
            'attachment' => $postText('background_style_' . $sizeKey . '_attachment', $content_current['background_style'][$sizeKey]['attachment'] ?? ($content_defaults['background_style'][$sizeKey]['attachment'] ?? 'fixed')),
        ];
    }
    $newConfig['colors'] = $colors;
    $fontsWorking = normalize_fonts_config($content_current['fonts'] ?? ($content_defaults['fonts'] ?? []));
    $removeFontIds = [];
    foreach ((array) ($_POST['remove_font_ids'] ?? []) as $removeFontId) {
        $removeFontId = trim((string) $removeFontId);
        if ($removeFontId !== '') {
            $removeFontIds[$removeFontId] = true;
        }
    }
    $keptFontItems = [];
    foreach ($fontsWorking['items'] as $fontItem) {
        $fontId = (string) ($fontItem['id'] ?? '');
        if ($fontId !== '' && isset($removeFontIds[$fontId])) {
            $relFile = (string) ($fontItem['file'] ?? '');
            if ($relFile !== '' && str_starts_with(str_replace('\\', '/', $relFile), 'assets/fonts/custom/')) {
                $absFile = $baseDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relFile);
                if (is_file($absFile)) {
                    @unlink($absFile);
                }
            }
            continue;
        }
        $keptFontItems[] = $fontItem;
    }
    $newConfig['fonts'] = normalize_fonts_config(['items' => $keptFontItems]);
    $styleCustomizationDefaults = is_array($content_defaults['style_customization'] ?? null)
        ? $content_defaults['style_customization']
        : [];
    $styleCustomizationCurrent = is_array($content_current['style_customization'] ?? null)
        ? $content_current['style_customization']
        : [];
    $buttonsColorsCustom = isset($_POST['style_customization_buttons_colors']) && $_POST['style_customization_buttons_colors'] === '1';
    $footerTextColorsCustom = isset($_POST['style_customization_footer_text_colors']) && $_POST['style_customization_footer_text_colors'] === '1';
    $helpPageColorsCustom = isset($_POST['style_customization_help_page_colors']) && $_POST['style_customization_help_page_colors'] === '1';
    $formFieldColorsCustom = isset($_POST['style_customization_form_field_colors']) && $_POST['style_customization_form_field_colors'] === '1';
    $cookieBannerColorsCustom = isset($_POST['style_customization_cookie_banner_colors']) && $_POST['style_customization_cookie_banner_colors'] === '1';
    // Se cookie banner disattivato, i dettagli non sono in POST: conserva il flag colori esistente
    if (!(isset($_POST['cookie_consent_enabled']) && $_POST['cookie_consent_enabled'] === '1')) {
        $cookieBannerColorsCustom = !empty($styleCustomizationCurrent['cookie_banner_colors']);
    }
    $hasExplicitStyleCustomization = array_key_exists('style_customization', $content_current);
    if (!$buttonsColorsCustom && !$hasExplicitStyleCustomization) {
        $migrationProbe = array_merge($content_current, [
            'colors' => $colors,
            'buttons' => array_merge(
                is_array($content_current['buttons'] ?? null) ? $content_current['buttons'] : [],
                $buttonConfig
            ),
        ]);
        if (contest_buttons_stored_colors_are_custom($migrationProbe, $content_defaults)) {
            $buttonsColorsCustom = true;
        }
    }
    if (!$helpPageColorsCustom && !$hasExplicitStyleCustomization) {
        $helpMigrationProbe = array_merge($content_current, ['help_page_style' => $content_current['help_page_style'] ?? []]);
        if (contest_help_page_stored_colors_are_custom($helpMigrationProbe)) {
            $helpPageColorsCustom = true;
        }
    }
    if (!$formFieldColorsCustom && !$hasExplicitStyleCustomization) {
        if (contest_form_fields_stored_colors_are_custom($content_current)) {
            $formFieldColorsCustom = true;
        }
    }
    $newConfig['style_customization'] = array_merge(
        $styleCustomizationDefaults,
        $styleCustomizationCurrent,
        [
            'buttons_colors' => $buttonsColorsCustom,
            'footer_text_colors' => $footerTextColorsCustom,
            'help_page_colors' => $helpPageColorsCustom,
            'form_field_colors' => $formFieldColorsCustom,
            'cookie_banner_colors' => $cookieBannerColorsCustom,
        ]
    );
    $newConfig['buttons'] = array_merge(
        $content_defaults['buttons'] ?? [],
        $content_current['buttons'] ?? [],
        $buttonConfig,
        [
            'help' => ['text' => $buttonHelpText],
            'quiz_submit_text' => $buttonQuizSubmitText,
        ]
    );
    // Typografia bottoni solo in primary/secondary (Bottoni), non per pagina.
    unset($newConfig['buttons']['pages']);
    $newConfig['fidelity'] = [
        'type' => $fidelityType,
        'length' => $fidelityLength,
        'unique_participation' => $fidelityUniqueParticipation,
        'field_width' => $fidelityFieldWidth,
        'help_text' => $fidelityHelpText,
        'help_text_color' => $fidelityHelpTextColor,
    ];
    $newConfig['scontrino_help_text'] = $scontrinoHelpText;
    $newConfig['scontrino_help_text_color'] = $scontrinoHelpTextColor;
    $newConfig['codice_partecipazione_help_text'] = $codicePartecipazioneHelpText;
    $newConfig['codice_partecipazione_help_text_color'] = $codicePartecipazioneHelpTextColor;
    if ($cellulareDigitsMin !== null && $cellulareDigitsMax !== null) {
        $newConfig['cellulare_digits_min'] = $cellulareDigitsMin;
        $newConfig['cellulare_digits_max'] = $cellulareDigitsMax;
    } else {
        // Vuoto = 10 cifre (sovrascrive eventuali valori 6-12 residui)
        $newConfig['cellulare_digits_min'] = 10;
        $newConfig['cellulare_digits_max'] = 10;
    }
    $newConfig['fields_enabled'] = $fields_enabled;
    $newConfig['fields_required'] = $fields_required;
    $newConfig['standard_fields_order'] = $standard_fields_order_saved ?? gst_standard_fields_normalize_order($content_current['standard_fields_order'] ?? null);
    // PDV config: mode combinato/cascata + opzioni (solo se PDV abilitato, altrimenti mantieni current)
    $pdvConfigPost = $_POST['pdv_config'] ?? [];
    $pdvConfigCurrent = $content_current['pdv_config'] ?? ($content_defaults['pdv_config'] ?? []);
    $pdvConfigMode = isset($pdvConfigPost['mode']) ? strtolower(trim((string) $pdvConfigPost['mode'])) : ($pdvConfigCurrent['mode'] ?? 'combinato');
    $pdvConfigMode = in_array($pdvConfigMode, ['combinato', 'cascata'], true) ? $pdvConfigMode : 'combinato';
    $pdvCombinatoLabelStr = isset($pdvConfigPost['combinato']['label_fields_str']) ? trim((string) $pdvConfigPost['combinato']['label_fields_str']) : '';
    if ($pdvCombinatoLabelStr === '' && !empty($pdvConfigCurrent['combinato']['label_fields'])) {
        $pdvCombinatoLabelStr = implode(', ', $pdvConfigCurrent['combinato']['label_fields']);
    }
    $pdvLabelFieldsAllowed = ['ragsoc', 'insegna', 'indirizzo', 'citta', 'prov', 'regione', 'cap'];
    $pdvCombinatoLabelFields = ['ragsoc', 'indirizzo', 'citta', 'prov'];
    if ($pdvCombinatoLabelStr !== '') {
        $parts = array_map('trim', explode(',', $pdvCombinatoLabelStr));
        $pdvCombinatoLabelFields = [];
        foreach ($parts as $p) {
            $p = strtolower($p);
            if (in_array($p, $pdvLabelFieldsAllowed, true) && !in_array($p, $pdvCombinatoLabelFields, true)) {
                $pdvCombinatoLabelFields[] = $p;
            }
        }
        if (empty($pdvCombinatoLabelFields)) {
            $pdvCombinatoLabelFields = ['ragsoc', 'indirizzo', 'citta', 'prov'];
        }
    }
    // IMPORTANT: do NOT trim separator, so users can save spaces-only values
    // (e.g. " ", "  ", " - ", etc.).
    $pdvCombinatoSearchLimitRaw = isset($pdvConfigPost['combinato']['search_limit'])
        ? (int) $pdvConfigPost['combinato']['search_limit']
        : (int) ($pdvConfigCurrent['combinato']['search_limit'] ?? 50);
    $pdvSearchLimitAllowed = [50, 100, 150, 200, 20000];
    $pdvCombinatoSearchLimit = in_array($pdvCombinatoSearchLimitRaw, $pdvSearchLimitAllowed, true) ? $pdvCombinatoSearchLimitRaw : 50;
    $pdvCombinatoSeparator = isset($pdvConfigPost['combinato']['separator'])
        ? (string) $pdvConfigPost['combinato']['separator']
        : (($pdvConfigCurrent['combinato']['separator'] ?? ' - '));
    // If user types separator wrapped in quotes (e.g. " - "), strip only outer quotes.
    if (strlen($pdvCombinatoSeparator) >= 2) {
        $firstChar = $pdvCombinatoSeparator[0];
        $lastChar = $pdvCombinatoSeparator[strlen($pdvCombinatoSeparator) - 1];
        if (($firstChar === '"' && $lastChar === '"') || ($firstChar === "'" && $lastChar === "'")) {
            $pdvCombinatoSeparator = substr($pdvCombinatoSeparator, 1, -1);
        }
    }
    $pdvCascataDisplay = isset($pdvConfigPost['cascata']['punto_vendita_display']) ? strtolower(trim((string) $pdvConfigPost['cascata']['punto_vendita_display'])) : ($pdvConfigCurrent['cascata']['punto_vendita_display'] ?? 'insegna_indirizzo');
    $pdvCascataDisplay = in_array($pdvCascataDisplay, ['indirizzo_only', 'indirizzo_insegna', 'insegna_indirizzo'], true) ? $pdvCascataDisplay : 'insegna_indirizzo';
    $pdvCascataGeographicMode = isset($pdvConfigPost['cascata']['geographic_mode']) ? strtolower(trim((string) $pdvConfigPost['cascata']['geographic_mode'])) : ($pdvConfigCurrent['cascata']['geographic_mode'] ?? 'provincia');
    $pdvCascataGeographicMode = in_array($pdvCascataGeographicMode, ['provincia', 'regione'], true) ? $pdvCascataGeographicMode : 'provincia';
    $newConfig['pdv_config'] = [
        'mode' => $pdvConfigMode,
        'combinato' => [
            'label_fields' => $pdvCombinatoLabelFields,
            'separator' => $pdvCombinatoSeparator,
            'search_limit' => $pdvCombinatoSearchLimit,
        ],
        'cascata' => [
            'geographic_mode' => $pdvCascataGeographicMode,
            'punto_vendita_display' => $pdvCascataDisplay,
        ],
    ];
    $consensiTextsPost = $_POST['consensi_texts'] ?? [];
    $consensiTextsDefaults = $content_defaults['consensi_texts'] ?? [];
    $consensiTextsCurrent = $content_current['consensi_texts'] ?? $consensiTextsDefaults;
    $newConfig['consensi_texts'] = [
        'privacy' => trim((string)($consensiTextsPost['privacy'] ?? $consensiTextsCurrent['privacy'] ?? $consensiTextsDefaults['privacy'] ?? '')),
        'marketing' => trim((string)($consensiTextsPost['marketing'] ?? $consensiTextsCurrent['marketing'] ?? $consensiTextsDefaults['marketing'] ?? '')),
        'profilazione' => trim((string)($consensiTextsPost['profilazione'] ?? $consensiTextsCurrent['profilazione'] ?? $consensiTextsDefaults['profilazione'] ?? '')),
    ];
    if ($newConfig['consensi_texts']['privacy'] === '') {
        $newConfig['consensi_texts']['privacy'] = $consensiTextsDefaults['privacy'] ?? 'Dichiaro di aver preso visione e di accettare {{PRIVACY_LINK}} *';
    }
    if ($newConfig['consensi_texts']['marketing'] === '') {
        $newConfig['consensi_texts']['marketing'] = $consensiTextsDefaults['marketing'] ?? 'Autorizzo il trattamento dei dati per finalità di marketing (i.e. attività promozionale, commerciale e pubblicitaria)';
    }
    if ($newConfig['consensi_texts']['profilazione'] === '') {
        $newConfig['consensi_texts']['profilazione'] = $consensiTextsDefaults['profilazione'] ?? 'Autorizzo il trattamento dei dati per finalità di profilazione (per ricevere comunicazioni personalizzate in base ai miei gusti e interessi)';
    }
    $newConfig['help_form_enabled'] = isset($_POST['help_form_enabled']);
    $newConfig['participation_page_enabled'] = isset($_POST['participation_page_enabled']);
    $newConfig['form_container']['mode'] = $postText('form_container_mode', $content_current['form_container']['mode'] ?? ($content_defaults['form_container']['mode'] ?? 'color'));
    $newConfig['form_container']['color'] = $postText('form_container_color', $content_current['form_container']['color'] ?? ($content_defaults['form_container']['color'] ?? CONCORSO_COLOR_WHITE));
    $newConfig['form_container']['opacity'] = max(0, min(100, $postInt('form_container_opacity', (int) ($content_current['form_container']['opacity'] ?? ($content_defaults['form_container']['opacity'] ?? 100)))));
    $newConfig['form_container']['image'] = $postText('form_container_image', $content_current['form_container']['image'] ?? ($content_defaults['form_container']['image'] ?? ''));
    $formContainerRadiusPost = $_POST['form_container_radius'] ?? [];
    $formContainerRadiusCurrent = $content_current['form_container']['radius'] ?? ($content_defaults['form_container']['radius'] ?? ['top' => 0, 'right' => 0, 'bottom' => 12, 'left' => 12]);
    if (!is_array($formContainerRadiusPost)) {
        $formContainerRadiusPost = [];
    }
    if (!is_array($formContainerRadiusCurrent)) {
        $formContainerRadiusCurrent = ['top' => 0, 'right' => 0, 'bottom' => 12, 'left' => 12];
    }
    $newConfig['form_container']['radius'] = [
        'top' => max(0, min(80, (int) ($formContainerRadiusPost['top'] ?? $formContainerRadiusCurrent['top'] ?? 0))),
        'right' => max(0, min(80, (int) ($formContainerRadiusPost['right'] ?? $formContainerRadiusCurrent['right'] ?? 0))),
        'bottom' => max(0, min(80, (int) ($formContainerRadiusPost['bottom'] ?? $formContainerRadiusCurrent['bottom'] ?? 12))),
        'left' => max(0, min(80, (int) ($formContainerRadiusPost['left'] ?? $formContainerRadiusCurrent['left'] ?? 12))),
    ];
    $newConfig['public_page_containers'] = manager_collect_public_page_container_styles_from_post($content_current, $content_defaults);
    $thankyou_messages_defaults = $content_defaults['thankyou_messages'] ?? [];
    $thankyou_messages_current = $content_current['thankyou_messages'] ?? $thankyou_messages_defaults;
    $newConfig['thankyou_messages'] = [
        'instantwin_win_html' => $postText(
            'thankyou_instantwin_win_html',
            $thankyou_messages_current['instantwin_win_html'] ?? ($thankyou_messages_defaults['instantwin_win_html'] ?? '')
        ),
        'instantwin_loss_html' => $postText(
            'thankyou_instantwin_loss_html',
            $thankyou_messages_current['instantwin_loss_html'] ?? ($thankyou_messages_defaults['instantwin_loss_html'] ?? '')
        ),
        'estrazione_html' => $postText(
            'thankyou_estrazione_html',
            $thankyou_messages_current['estrazione_html'] ?? ($thankyou_messages_defaults['estrazione_html'] ?? '')
        ),
    ];
    $email_templates_defaults = $content_defaults['email_templates'] ?? [];
    $email_templates_current = $content_current['email_templates'] ?? $email_templates_defaults;
    $newConfig['email_templates'] = [
        'partecipazione' => [
            'subject' => $postText(
                'email_partecipazione_subject',
                $email_templates_current['partecipazione']['subject'] ?? ($email_templates_defaults['partecipazione']['subject'] ?? '')
            ),
            'body' => contest_normalize_summernote_font_html($postText(
                'email_partecipazione_body',
                $email_templates_current['partecipazione']['body'] ?? ($email_templates_defaults['partecipazione']['body'] ?? '')
            )),
        ],
        'instantwin_confirm' => [
            'subject' => $postText(
                'email_instantwin_confirm_subject',
                $email_templates_current['instantwin_confirm']['subject'] ?? ($email_templates_defaults['instantwin_confirm']['subject'] ?? '')
            ),
            'body' => contest_normalize_summernote_font_html($postText(
                'email_instantwin_confirm_body',
                $email_templates_current['instantwin_confirm']['body'] ?? ($email_templates_defaults['instantwin_confirm']['body'] ?? '')
            )),
        ],
        'instantwin_loss' => [
            'subject' => $postText(
                'email_instantwin_loss_subject',
                $email_templates_current['instantwin_loss']['subject'] ?? ($email_templates_defaults['instantwin_loss']['subject'] ?? '')
            ),
            'body' => contest_normalize_summernote_font_html($postText(
                'email_instantwin_loss_body',
                $email_templates_current['instantwin_loss']['body'] ?? ($email_templates_defaults['instantwin_loss']['body'] ?? '')
            )),
        ],
        'instantwin_cancel' => [
            'subject' => $postText(
                'email_instantwin_cancel_subject',
                $email_templates_current['instantwin_cancel']['subject'] ?? ($email_templates_defaults['instantwin_cancel']['subject'] ?? '')
            ),
            'body' => contest_normalize_summernote_font_html($postText(
                'email_instantwin_cancel_body',
                $email_templates_current['instantwin_cancel']['body'] ?? ($email_templates_defaults['instantwin_cancel']['body'] ?? '')
            )),
        ],
        'instantwin_prize' => [
            'subject' => $postText(
                'email_instantwin_prize_subject',
                $email_templates_current['instantwin_prize']['subject'] ?? ($email_templates_defaults['instantwin_prize']['subject'] ?? '')
            ),
            'body' => contest_normalize_summernote_font_html($postText(
                'email_instantwin_prize_body',
                $email_templates_current['instantwin_prize']['body'] ?? ($email_templates_defaults['instantwin_prize']['body'] ?? '')
            )),
        ],
    ];
    $emailSendingConfirm = isset($_POST['email_sending_instantwin_confirm']);
    $emailSendingPrize = isset($_POST['email_sending_instantwin_prize']);
    $emailSendingCancel = isset($_POST['email_sending_instantwin_cancel']);
    if ($scontrinoEnabledForIw) {
        if (!empty($newConfig['instantwin_auto_prize_with_receipt'])) {
            $emailSendingConfirm = false;
            $emailSendingCancel = false;
        } else {
            $emailSendingConfirm = true;
            $emailSendingCancel = true;
        }
        if (!$emailSendingConfirm && !$newConfig['instantwin_auto_prize_with_receipt'] && $emailSendingPrize) {
            $newConfig['instantwin_auto_prize_with_receipt'] = true;
            $emailSendingConfirm = false;
            $emailSendingCancel = false;
        }
    }
    $newConfig['email_sending'] = [
        'partecipazione' => isset($_POST['email_sending_partecipazione']),
        'instantwin_confirm' => $emailSendingConfirm,
        'instantwin_loss' => isset($_POST['email_sending_instantwin_loss']),
        'instantwin_cancel' => $emailSendingCancel,
        'instantwin_prize' => $emailSendingPrize,
    ];
    $smsProviderPost = strtolower(trim((string) ($_POST['sms_provider'] ?? 'mailup')));
    if ($smsProviderPost === '') {
        $smsProviderPost = 'mailup';
    }
    $smsCampaignCodePost = trim((string) ($_POST['sms_campaign_code'] ?? ''));
    $smsCampaignCodePost = preg_replace('/[\r\n]+/', ' ', $smsCampaignCodePost) ?? $smsCampaignCodePost;
    $smsCampaignCodePost = preg_replace('/\s+/', ' ', $smsCampaignCodePost) ?? $smsCampaignCodePost;
    if (mb_strlen($smsCampaignCodePost, 'UTF-8') > 100) {
        $smsCampaignCodePost = mb_substr($smsCampaignCodePost, 0, 100, 'UTF-8');
    }
    $newConfig['sms'] = [
        'enabled' => isset($_POST['sms_enabled']),
        'provider' => $smsProviderPost,
        'campaign_code' => $smsCampaignCodePost,
    ];
    $newConfig['sms_sending'] = [
        'partecipazione' => isset($_POST['sms_sending_partecipazione']),
        'instantwin_confirm' => isset($_POST['sms_sending_instantwin_confirm']),
        'instantwin_loss' => isset($_POST['sms_sending_instantwin_loss']),
        'instantwin_cancel' => isset($_POST['sms_sending_instantwin_cancel']),
        'instantwin_prize' => isset($_POST['sms_sending_instantwin_prize']),
    ];
    $smsTemplateFieldMap = [
        'partecipazione' => ['post' => 'sms_template_partecipazione', 'label' => 'Conferma partecipazione'],
        'instantwin_confirm' => ['post' => 'sms_template_instantwin_confirm', 'label' => 'Vincita in attesa'],
        'instantwin_loss' => ['post' => 'sms_template_instantwin_loss', 'label' => 'Nessuna vincita'],
        'instantwin_cancel' => ['post' => 'sms_template_instantwin_cancel', 'label' => 'Annullamento vincita'],
        'instantwin_prize' => ['post' => 'sms_template_instantwin_prize', 'label' => 'Premio vincita'],
    ];
    $smsTemplatesCurrent = is_array($content_current['sms_templates'] ?? null) ? $content_current['sms_templates'] : [];
    $smsTemplatesDefaults = is_array($content_defaults['sms_templates'] ?? null) ? $content_defaults['sms_templates'] : [];
    $smsTemplatesNew = [];
    foreach ($smsTemplateFieldMap as $type => $meta) {
        $fallback = (string) ($smsTemplatesCurrent[$type] ?? ($smsTemplatesDefaults[$type] ?? ''));
        $v = $postText($meta['post'], $fallback);
        $clean = gst_sms_sanitize_template_text($v, null);
        $smsSeptets = gst_sms_gsm7_septet_count($clean);
        if ($smsSeptets > GST_SMS_TEMPLATE_MAX_SEPTETS) {
            $errors[] = sprintf(
                'Messaggio SMS (%s): massimo %d unità GSM (ne hai %d).',
                $meta['label'],
                GST_SMS_TEMPLATE_MAX_SEPTETS,
                $smsSeptets
            );
        }
        $smsTemplatesNew[$type] = $clean;
    }
    $newConfig['sms_templates'] = $smsTemplatesNew;
    $emailBccByInsegnaText = trim((string) ($_POST['email_bcc_instantwin_prize_by_insegna'] ?? ''));
    $emailBccByInsegna = [];
    if ($emailBccByInsegnaText !== '') {
        $rows = preg_split('/\r\n|\r|\n/', $emailBccByInsegnaText) ?: [];
        foreach ($rows as $row) {
            $line = trim((string) $row);
            if ($line === '') {
                continue;
            }
            $parts = preg_split('/\s*[:=]\s*/', $line, 2);
            if (!$parts || count($parts) < 2) {
                continue;
            }
            $insegnaKey = trim((string) ($parts[0] ?? ''));
            $emailsCsv = trim((string) ($parts[1] ?? ''));
            if ($insegnaKey === '' || $emailsCsv === '') {
                continue;
            }
            $emailBccByInsegna[$insegnaKey] = $emailsCsv;
        }
    }
    $newConfig['email_bcc'] = $content_current['email_bcc'] ?? ($content_defaults['email_bcc'] ?? []);
    if (!is_array($newConfig['email_bcc'])) {
        $newConfig['email_bcc'] = [];
    }
    $newConfig['email_bcc']['instantwin_prize'] = [
        'enabled' => isset($_POST['email_bcc_instantwin_prize_enabled']),
        'use_pdv_email' => isset($_POST['email_bcc_instantwin_prize_use_pdv_email']),
        'global' => trim((string) ($_POST['email_bcc_instantwin_prize_global'] ?? '')),
        'by_insegna' => $emailBccByInsegna,
    ];
    
    // Opzione per allegare PDF voucher all'email Instant Win Premio
    $newConfig['email_instantwin_prize_attach_pdf'] = isset($_POST['email_instantwin_prize_attach_pdf']);
    $voucherBlockStyleDefaults = $content_defaults['email_voucher_block_style'] ?? ['border_color' => CONCORSO_COLOR_SUCCESS, 'bg_color' => '#f0f0f0', 'text_color' => CONCORSO_COLOR_SUCCESS];
    $voucherBorder = trim((string) ($_POST['email_voucher_block_border_color'] ?? $content_current['email_voucher_block_style']['border_color'] ?? $voucherBlockStyleDefaults['border_color']));
    $voucherBg = trim((string) ($_POST['email_voucher_block_bg_color'] ?? $content_current['email_voucher_block_style']['bg_color'] ?? $voucherBlockStyleDefaults['bg_color']));
    $voucherText = trim((string) ($_POST['email_voucher_block_text_color'] ?? $content_current['email_voucher_block_style']['text_color'] ?? $voucherBlockStyleDefaults['text_color']));
    $newConfig['email_voucher_block_style'] = [
        'border_color' => (preg_match(CONCORSO_REGEX_HEX3_8, $voucherBorder) ? $voucherBorder : $voucherBlockStyleDefaults['border_color']),
        'bg_color' => (preg_match(CONCORSO_REGEX_HEX3_8, $voucherBg) ? $voucherBg : $voucherBlockStyleDefaults['bg_color']),
        'text_color' => (preg_match(CONCORSO_REGEX_HEX3_8, $voucherText) ? $voucherText : $voucherBlockStyleDefaults['text_color']),
    ];
    $voucherDisplayMode = strtolower(trim((string) ($_POST['email_voucher_code_display'] ?? ($content_current['email_voucher_code_display'] ?? ($content_defaults['email_voucher_code_display'] ?? 'text')))));
    $newConfig['email_voucher_code_display'] = in_array($voucherDisplayMode, ['text', 'barcode', 'both'], true) ? $voucherDisplayMode : 'text';

    if (!function_exists('prize_email_normalize_template_entry')) {
        require_once __DIR__ . CONCORSO_PRIZE_IMAGE_GENERATOR;
    }
    $prizeTemplateUploadDir = $baseDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'premi' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
    $prizeTemplateBasePath = 'uploads/premi/templates/';
    $existingPrizeEmailTemplates = is_array($content_current['prize_email_templates'] ?? null)
        ? $content_current['prize_email_templates']
        : [];
    $newPrizeEmailTemplates = [];
    $postedPrizeEmailTemplates = $_POST['prize_email_template'] ?? [];
    if (is_array($postedPrizeEmailTemplates)) {
        foreach ($postedPrizeEmailTemplates as $idx => $row) {
            if (!is_array($row)) {
                continue;
            }
            $key = trim((string) ($row['key'] ?? ''));
            if ($key === '') {
                continue;
            }
            $removeTemplate = !empty($row['remove_template']);
            $previousPath = trim((string) ($existingPrizeEmailTemplates[$key]['template_path'] ?? ''));
            $templatePath = trim((string) ($row['template_path'] ?? ''));
            $uploadFile = concorso_get_indexed_upload('upload_prize_email_template', $idx);
            if ($uploadFile !== null) {
                $slug = preg_replace('/[^a-z0-9_]+/i', '_', str_replace('|', '_', $key));
                $uploadedPath = concorso_handle_indexed_upload(
                    $uploadFile,
                    $prizeTemplateUploadDir,
                    $prizeTemplateBasePath,
                    ['png', 'jpg', 'jpeg', 'webp', 'gif'],
                    'premio_tpl_' . $slug,
                    $errors,
                    $hasManagerUploadSizeError,
                    $configAssetUploadMaxBytes,
                    $configAssetUploadMaxLabel
                );
                if ($uploadedPath !== null) {
                    if ($previousPath !== '' && $previousPath !== $uploadedPath) {
                        prize_image_delete_template_file($previousPath);
                    }
                    $templatePath = $uploadedPath;
                }
            } elseif ($removeTemplate) {
                if ($previousPath !== '') {
                    prize_image_delete_template_file($previousPath);
                }
                $templatePath = '';
            } elseif ($templatePath === '' && $previousPath !== '') {
                $templatePath = $previousPath;
            }
            $newPrizeEmailTemplates[$key] = prize_email_normalize_template_entry([
                'template_path' => $templatePath,
                'overlay' => [
                    'enabled' => !empty($row['overlay_enabled']),
                    'x' => $row['overlay_x'] ?? 200,
                    'y' => $row['overlay_y'] ?? 350,
                    'font_size' => $row['overlay_font_size'] ?? 24,
                    'color' => $row['overlay_color'] ?? CONCORSO_COLOR_BLACK,
                ],
                'email' => [
                    'custom_body_enabled' => !empty($row['custom_body_enabled']),
                    'body' => (string) ($row['email_body'] ?? ''),
                    'voucher_code_display' => (string) ($row['voucher_code_display'] ?? 'inherit'),
                ],
            ]);
        }
    }
    $finalPrizeEmailTemplates = count($newPrizeEmailTemplates) > 0
        ? $newPrizeEmailTemplates
        : $existingPrizeEmailTemplates;
    $keptPrizeTemplatePaths = [];
    foreach ($finalPrizeEmailTemplates as $entry) {
        $normalizedPrizeTpl = prize_email_normalize_template_entry($entry);
        if ($normalizedPrizeTpl['template_path'] !== '') {
            $keptPrizeTemplatePaths[] = $normalizedPrizeTpl['template_path'];
        }
    }
    if (count($newPrizeEmailTemplates) > 0) {
        foreach ($existingPrizeEmailTemplates as $oldEntry) {
            if (!is_array($oldEntry)) {
                continue;
            }
            $oldPath = trim((string) ($oldEntry['template_path'] ?? ''));
            if ($oldPath === '') {
                continue;
            }
            if (!in_array($oldPath, $keptPrizeTemplatePaths, true)) {
                prize_image_delete_template_file($oldPath);
            }
        }
    }
    prize_image_cleanup_orphan_templates($keptPrizeTemplatePaths);
    prize_image_cleanup_legacy_preview_overlays();
    $pdoPrizeImageCleanup = null;
    try {
        if (isset($connection) && $connection instanceof PDO) {
            $pdoPrizeImageCleanup = $connection;
        } elseif (function_exists('conn_pdo')) {
            $pdoPrizeImageCleanup = conn_pdo();
        }
    } catch (Throwable $e) {
        $pdoPrizeImageCleanup = null;
    }
    if ($pdoPrizeImageCleanup instanceof PDO) {
        if (count($keptPrizeTemplatePaths) === 0) {
            prize_image_cleanup_all_generated_wins();
        } else {
            foreach ($finalPrizeEmailTemplates as $tplKey => $entry) {
                $normalizedFinalTpl = prize_email_normalize_template_entry($entry);
                if ($normalizedFinalTpl['template_path'] === '') {
                    prize_image_cleanup_generated_for_template_key($pdoPrizeImageCleanup, (string) $tplKey);
                }
            }
            if (count($newPrizeEmailTemplates) > 0) {
                foreach ($newPrizeEmailTemplates as $tplKey => $entry) {
                    $normalizedNewTpl = prize_email_normalize_template_entry($entry);
                    $normalizedPrevTpl = prize_email_normalize_template_entry($existingPrizeEmailTemplates[$tplKey] ?? []);
                    $templateReplaced = $normalizedPrevTpl['template_path'] !== ''
                        && $normalizedNewTpl['template_path'] !== ''
                        && $normalizedPrevTpl['template_path'] !== $normalizedNewTpl['template_path'];
                    if ($templateReplaced) {
                        prize_image_cleanup_generated_for_template_key($pdoPrizeImageCleanup, (string) $tplKey);
                    }
                }
            }
        }
    }
    $newConfig['prize_email_templates'] = $finalPrizeEmailTemplates;

    $pages_config = $content_current['pages'] ?? ($content_defaults['pages'] ?? []);
    if (!is_array($pages_config)) {
        $pages_config = [];
    }
    $allowedWaitingAlign = ['left', 'center', 'right', 'justify'];
    $used_waiting_images = [];

    $waiting_page_result = concorso_process_config_page_containers(
        'waiting_page',
        'waiting_page_present',
        'waiting_page_align',
        'waiting_page_containers',
        'waiting_page_container_bg_upload',
        'waiting_page_container_image_upload',
        'waiting',
        $waitingPageUploadDir,
        $waitingPageBasePath,
        $pages_config,
        $content_defaults,
        $allowedWaitingAlign,
        $used_waiting_images,
        $postText,
        $maybe_decode_waiting_html,
        $errors,
        $hasManagerUploadSizeError,
        $configAssetUploadMaxBytes,
        $configAssetUploadMaxLabel
    );
    $waiting_page_containers = $waiting_page_result['containers'];
    $waiting_page_align = $waiting_page_result['align'];

    $participation_page_result = concorso_process_config_page_containers(
        'participation_page',
        'participation_page_present',
        'participation_page_align',
        'participation_page_containers',
        'participation_page_container_bg_upload',
        'participation_page_container_image_upload',
        'participation',
        $registrationUploadDir,
        $registrationBasePath,
        $pages_config,
        $content_defaults,
        $allowedWaitingAlign,
        $used_waiting_images,
        $postText,
        $maybe_decode_waiting_html,
        $errors,
        $hasManagerUploadSizeError,
        $configAssetUploadMaxBytes,
        $configAssetUploadMaxLabel
    );
    $participation_page_containers = $participation_page_result['containers'];
    $participation_page_align = $participation_page_result['align'];

    // Processa le pagine thank-you per instantwin (WIN)
    $thankyou_instantwin_win_result = concorso_process_config_page_containers(
        'thankyou_instantwin_win_page',
        'thankyou_instantwin_win_page_present',
        'thankyou_instantwin_win_page_align',
        'thankyou_instantwin_win_page_containers',
        'thankyou_instantwin_win_page_container_bg_upload',
        'thankyou_instantwin_win_page_container_image_upload',
        'thankyou_win',
        $thankyouUploadDir,
        $thankyouBasePath,
        $pages_config,
        $content_defaults,
        $allowedWaitingAlign,
        $used_waiting_images,
        $postText,
        $maybe_decode_waiting_html,
        $errors,
        $hasManagerUploadSizeError,
        $configAssetUploadMaxBytes,
        $configAssetUploadMaxLabel,
        true
    );
    $thankyou_instantwin_win_containers = $thankyou_instantwin_win_result['containers'];
    $thankyou_instantwin_win_align = $thankyou_instantwin_win_result['align'];

    // Processa le pagine thank-you per instantwin (LOSS)
    $thankyou_instantwin_loss_result = concorso_process_config_page_containers(
        'thankyou_instantwin_loss_page',
        'thankyou_instantwin_loss_page_present',
        'thankyou_instantwin_loss_page_align',
        'thankyou_instantwin_loss_page_containers',
        'thankyou_instantwin_loss_page_container_bg_upload',
        'thankyou_instantwin_loss_page_container_image_upload',
        'thankyou_loss',
        $thankyouUploadDir,
        $thankyouBasePath,
        $pages_config,
        $content_defaults,
        $allowedWaitingAlign,
        $used_waiting_images,
        $postText,
        $maybe_decode_waiting_html,
        $errors,
        $hasManagerUploadSizeError,
        $configAssetUploadMaxBytes,
        $configAssetUploadMaxLabel,
        true
    );
    $thankyou_instantwin_loss_containers = $thankyou_instantwin_loss_result['containers'];
    $thankyou_instantwin_loss_align = $thankyou_instantwin_loss_result['align'];

    $thankyou_instantwin_quiz_failed_result = concorso_process_config_page_containers(
        'thankyou_instantwin_quiz_failed_page',
        'thankyou_instantwin_quiz_failed_page_present',
        'thankyou_instantwin_quiz_failed_page_align',
        'thankyou_instantwin_quiz_failed_page_containers',
        'thankyou_instantwin_quiz_failed_page_container_bg_upload',
        'thankyou_instantwin_quiz_failed_page_container_image_upload',
        'thankyou_quiz_failed',
        $thankyouUploadDir,
        $thankyouBasePath,
        $pages_config,
        $content_defaults,
        $allowedWaitingAlign,
        $used_waiting_images,
        $postText,
        $maybe_decode_waiting_html,
        $errors,
        $hasManagerUploadSizeError,
        $configAssetUploadMaxBytes,
        $configAssetUploadMaxLabel,
        true
    );
    $thankyou_instantwin_quiz_failed_containers = $thankyou_instantwin_quiz_failed_result['containers'];
    $thankyou_instantwin_quiz_failed_align = $thankyou_instantwin_quiz_failed_result['align'];

    // Processa le pagine thank-you per estrazione
    $thankyou_estrazione_result = concorso_process_config_page_containers(
        'thankyou_estrazione_page',
        'thankyou_estrazione_page_present',
        'thankyou_estrazione_page_align',
        'thankyou_estrazione_page_containers',
        'thankyou_estrazione_page_container_bg_upload',
        'thankyou_estrazione_page_container_image_upload',
        'thankyou_estrazione',
        $thankyouUploadDir,
        $thankyouBasePath,
        $pages_config,
        $content_defaults,
        $allowedWaitingAlign,
        $used_waiting_images,
        $postText,
        $maybe_decode_waiting_html,
        $errors,
        $hasManagerUploadSizeError,
        $configAssetUploadMaxBytes,
        $configAssetUploadMaxLabel,
        true
    );
    $thankyou_estrazione_containers = $thankyou_estrazione_result['containers'];
    $thankyou_estrazione_align = $thankyou_estrazione_result['align'];

    $thankyou_estrazione_quiz_failed_result = concorso_process_config_page_containers(
        'thankyou_estrazione_quiz_failed_page',
        'thankyou_estrazione_quiz_failed_page_present',
        'thankyou_estrazione_quiz_failed_page_align',
        'thankyou_estrazione_quiz_failed_page_containers',
        'thankyou_estrazione_quiz_failed_page_container_bg_upload',
        'thankyou_estrazione_quiz_failed_page_container_image_upload',
        'thankyou_estrazione_qfail',
        $thankyouUploadDir,
        $thankyouBasePath,
        $pages_config,
        $content_defaults,
        $allowedWaitingAlign,
        $used_waiting_images,
        $postText,
        $maybe_decode_waiting_html,
        $errors,
        $hasManagerUploadSizeError,
        $configAssetUploadMaxBytes,
        $configAssetUploadMaxLabel,
        true
    );
    $thankyou_estrazione_quiz_failed_containers = $thankyou_estrazione_quiz_failed_result['containers'];
    $thankyou_estrazione_quiz_failed_align = $thankyou_estrazione_quiz_failed_result['align'];

    $quiz_choice_page_result = concorso_process_config_page_containers(
        'quiz_choice_page',
        'quiz_choice_page_present',
        'quiz_choice_page_align',
        'quiz_choice_page_containers',
        'quiz_choice_page_container_bg_upload',
        'quiz_choice_page_container_image_upload',
        'quiz_choice',
        $quizPageUploadDir,
        $quizPageBasePath,
        $pages_config,
        $content_defaults,
        $allowedWaitingAlign,
        $used_waiting_images,
        $postText,
        $maybe_decode_waiting_html,
        $errors,
        $hasManagerUploadSizeError,
        $configAssetUploadMaxBytes,
        $configAssetUploadMaxLabel
    );
    $quiz_choice_page_containers = $quiz_choice_page_result['containers'];
    $quiz_choice_page_align = $quiz_choice_page_result['align'];
    if (isset($_POST['quiz_choice_page_present'])) {
        if (!isset($pages_config['quiz_choice_page']) || !is_array($pages_config['quiz_choice_page'])) {
            $pages_config['quiz_choice_page'] = [];
        }
        $labelStd = trim((string) ($_POST['quiz_choice_label_standard'] ?? ''));
        $labelAudio = trim((string) ($_POST['quiz_choice_label_audio'] ?? ''));
        $labelSubmit = trim((string) ($_POST['quiz_choice_label_submit'] ?? ''));
        $defaultsLabels = is_array($content_defaults['pages']['quiz_choice_page']['choice_labels'] ?? null)
            ? $content_defaults['pages']['quiz_choice_page']['choice_labels']
            : [];
        $pages_config['quiz_choice_page']['choice_labels'] = [
            'standard' => $labelStd !== '' ? $labelStd : (string) ($defaultsLabels['standard'] ?? 'Testo (domande scritte)'),
            'audio' => $labelAudio !== '' ? $labelAudio : (string) ($defaultsLabels['audio'] ?? 'Audio (ascolto, poi risposte A/B/C/D)'),
            'submit' => $labelSubmit !== '' ? $labelSubmit : (string) ($defaultsLabels['submit'] ?? 'Avvia quiz'),
        ];
    }

    $quiz_page_header_result = concorso_process_config_page_containers(
        'quiz_page',
        'quiz_page_present',
        'quiz_page_align',
        'quiz_page_containers',
        'quiz_page_container_bg_upload',
        'quiz_page_container_image_upload',
        'quiz_page',
        $quizPageUploadDir,
        $quizPageBasePath,
        $pages_config,
        $content_defaults,
        $allowedWaitingAlign,
        $used_waiting_images,
        $postText,
        $maybe_decode_waiting_html,
        $errors,
        $hasManagerUploadSizeError,
        $configAssetUploadMaxBytes,
        $configAssetUploadMaxLabel
    );
    $quiz_page_containers = $quiz_page_header_result['containers'];
    $quiz_page_align = $quiz_page_header_result['align'];
    if (isset($_POST['quiz_qblock_present'])) {
        if (!isset($pages_config['quiz_page']) || !is_array($pages_config['quiz_page'])) {
            $pages_config['quiz_page'] = [];
        }
        $existingQb = $content_current['pages']['quiz_page']['question_block_style'] ?? ($content_defaults['pages']['quiz_page']['question_block_style'] ?? []);
        if (!is_array($existingQb)) {
            $existingQb = [];
        }
        $qblock_mode = (isset($_POST['quiz_qblock_bg_mode']) && $_POST['quiz_qblock_bg_mode'] === 'image') ? 'image' : 'color';
        $qblock_image = trim((string) ($_POST['quiz_qblock_bg_image'] ?? ($existingQb['background']['image'] ?? '')));
        $qblock_upload = concorso_handle_config_upload('upload_quiz_qblock_bg', $quizPageUploadDir, $quizPageBasePath, $errors, $hasManagerUploadSizeError, $configAssetUploadMaxBytes, $configAssetUploadMaxLabel, ['png', 'jpg', 'jpeg']);
        if ($qblock_upload !== null) {
            $qblock_image = $qblock_upload;
        }
        if ($qblock_mode !== 'image') {
            $qblock_image = '';
        }
        $qblock_color = trim((string) ($_POST['quiz_qblock_bg_color'] ?? ''));
        if ($qblock_color === '' || !preg_match('/^#?[0-9A-Fa-f]{3,6}$/', $qblock_color)) {
            $qblock_color = (string) ($existingQb['background']['color'] ?? CONCORSO_COLOR_WHITE);
        }
        if ($qblock_color !== '' && $qblock_color[0] !== '#') {
            $qblock_color = '#' . $qblock_color;
        }
        $existing_inner = is_array($existingQb['inner_blocks'] ?? null) ? $existingQb['inner_blocks'] : [];
        $inner_blocks = [];
        $inner_defaults = ['Domanda {{N}}.', '', 'Risposta', ''];
        for ($ib = 0; $ib < 4; $ib++) {
            $content = trim((string) ($_POST["quiz_inner_{$ib}_content"] ?? ''));
            if ($content === '' && isset($existing_inner[$ib]['content'])) {
                $content = (string) $existing_inner[$ib]['content'];
            }
            if ($content === '' && isset($inner_defaults[$ib]) && $inner_defaults[$ib] !== '') {
                $content = $inner_defaults[$ib];
            }
            $inner_blocks[] = [
                'content' => $content,
                'style' => [
                    'padding' => max(0, (int) ($_POST["quiz_inner_{$ib}_pad"] ?? ($existing_inner[$ib]['style']['padding'] ?? 4))),
                    'margin_bottom' => max(0, (int) ($_POST["quiz_inner_{$ib}_mb"] ?? ($existing_inner[$ib]['style']['margin_bottom'] ?? 4))),
                    'text_align' => in_array($_POST["quiz_inner_{$ib}_align"] ?? '', ['left', 'center', 'right', 'justify'], true)
                        ? $_POST["quiz_inner_{$ib}_align"]
                        : ($existing_inner[$ib]['style']['text_align'] ?? 'left'),
                    'background_color' => trim((string) ($_POST["quiz_inner_{$ib}_bg"] ?? ($existing_inner[$ib]['style']['background_color'] ?? ''))),
                    'radius' => max(0, (int) ($_POST["quiz_inner_{$ib}_radius"] ?? ($existing_inner[$ib]['style']['radius'] ?? 0))),
                ],
            ];
        }
        $pages_config['quiz_page']['question_block_style'] = array_merge($existingQb, [
            'content' => contest_normalize_summernote_font_html(trim((string) ($_POST['quiz_qblock_html'] ?? ($existingQb['content'] ?? '')))),
            'inner_blocks' => $inner_blocks,
            'answer_input_style' => (isset($_POST['quiz_answer_input_style']) && $_POST['quiz_answer_input_style'] === 'square') ? 'square' : 'radio',
            'answer_tick_color' => trim((string) ($_POST['quiz_answer_tick_color'] ?? ($existingQb['answer_tick_color'] ?? '#333333'))),
            'submit_button_text' => trim((string) ($_POST['quiz_submit_button_text'] ?? ($existingQb['submit_button_text'] ?? 'Invia risposte'))),
            'submit_button_bg_color' => trim((string) ($_POST['quiz_submit_button_bg_color'] ?? ($existingQb['submit_button_bg_color'] ?? CONCORSO_COLOR_PRIMARY))),
            'submit_button_align' => in_array($_POST['quiz_submit_button_align'] ?? '', ['left', 'center', 'right'], true)
                ? $_POST['quiz_submit_button_align']
                : ($existingQb['submit_button_align'] ?? 'center'),
            'background' => [
                'mode' => $qblock_mode,
                'color' => $qblock_color,
                'image' => $qblock_image,
                'opacity' => max(0, min(100, (int) ($_POST['quiz_qblock_bg_opacity'] ?? ($existingQb['background']['opacity'] ?? 100)))),
            ],
            'style' => [
                'margin' => [
                    'top' => (int) ($_POST['quiz_qblock_margin_top'] ?? ($existingQb['style']['margin']['top'] ?? 0)),
                    'right' => (int) ($_POST['quiz_qblock_margin_right'] ?? ($existingQb['style']['margin']['right'] ?? 0)),
                    'bottom' => (int) ($_POST['quiz_qblock_margin_bottom'] ?? ($existingQb['style']['margin']['bottom'] ?? 12)),
                    'left' => (int) ($_POST['quiz_qblock_margin_left'] ?? ($existingQb['style']['margin']['left'] ?? 0)),
                ],
                'radius' => [
                    'top' => (int) ($_POST['quiz_qblock_radius_tl'] ?? ($existingQb['style']['radius']['top'] ?? 8)),
                    'right' => (int) ($_POST['quiz_qblock_radius_tr'] ?? ($existingQb['style']['radius']['right'] ?? 8)),
                    'bottom' => (int) ($_POST['quiz_qblock_radius_br'] ?? ($existingQb['style']['radius']['bottom'] ?? 8)),
                    'left' => (int) ($_POST['quiz_qblock_radius_bl'] ?? ($existingQb['style']['radius']['left'] ?? 8)),
                ],
                'padding' => [
                    'top' => (int) ($_POST['quiz_qblock_pad_top'] ?? ($existingQb['style']['padding']['top'] ?? 16)),
                    'right' => (int) ($_POST['quiz_qblock_pad_right'] ?? ($existingQb['style']['padding']['right'] ?? 16)),
                    'bottom' => (int) ($_POST['quiz_qblock_pad_bottom'] ?? ($existingQb['style']['padding']['bottom'] ?? 16)),
                    'left' => (int) ($_POST['quiz_qblock_pad_left'] ?? ($existingQb['style']['padding']['left'] ?? 16)),
                ],
            ],
        ]);
    } elseif (isset($pages_config['quiz_page']) && is_array($pages_config['quiz_page'])) {
        $existingQb = $content_current['pages']['quiz_page']['question_block_style'] ?? ($content_defaults['pages']['quiz_page']['question_block_style'] ?? []);
        if (is_array($existingQb) && !empty($existingQb)) {
            $pages_config['quiz_page']['question_block_style'] = $existingQb;
        }
    }

    $waiting_page_ended_result = concorso_process_config_page_containers(
        'waiting_page_ended',
        'waiting_page_ended_present',
        'waiting_page_ended_align',
        'waiting_page_ended_containers',
        'waiting_page_ended_container_bg_upload',
        'waiting_page_ended_container_image_upload',
        'waiting_ended',
        $waitingPageUploadDir,
        $waitingPageBasePath,
        $pages_config,
        $content_defaults,
        $allowedWaitingAlign,
        $used_waiting_images,
        $postText,
        $maybe_decode_waiting_html,
        $errors,
        $hasManagerUploadSizeError,
        $configAssetUploadMaxBytes,
        $configAssetUploadMaxLabel
    );
    $waiting_page_ended_containers = $waiting_page_ended_result['containers'];
    $waiting_page_ended_align = $waiting_page_ended_result['align'];

    $form_page_result = concorso_process_config_page_containers(
        'form_page',
        'form_page_present',
        'form_page_align',
        'form_page_containers',
        'form_page_container_bg_upload',
        'form_page_container_image_upload',
        'form',
        $registrationUploadDir,
        $registrationBasePath,
        $pages_config,
        $content_defaults,
        $allowedWaitingAlign,
        $used_waiting_images,
        $postText,
        $maybe_decode_waiting_html,
        $errors,
        $hasManagerUploadSizeError,
        $configAssetUploadMaxBytes,
        $configAssetUploadMaxLabel
    );
    $form_page_containers = $form_page_result['containers'];
    $form_page_align = $form_page_result['align'];
    concorso_apply_posted_page_button_footer('form_page', $pages_config);
    concorso_apply_posted_page_button_footer('participation_page', $pages_config);

    $help_page_result = concorso_process_config_page_containers(
        'help_page',
        'help_page_present',
        'help_page_align',
        'help_page_containers',
        'help_page_container_bg_upload',
        'help_page_container_image_upload',
        'help',
        $registrationUploadDir,
        $registrationBasePath,
        $pages_config,
        $content_defaults,
        $allowedWaitingAlign,
        $used_waiting_images,
        $postText,
        $maybe_decode_waiting_html,
        $errors,
        $hasManagerUploadSizeError,
        $configAssetUploadMaxBytes,
        $configAssetUploadMaxLabel
    );
    $help_page_containers = $help_page_result['containers'];
    $help_page_align = $help_page_result['align'];

    if (!empty($pages_config)) {
        $newConfig['pages'] = $pages_config;
    }
    if ($custom_fields_provided) {
        $newConfig['custom_fields'] = $custom_fields;

        try {
            require_once __DIR__ . CONCORSO_APP_FUNCTIONS;
            $pdo = conn_pdo();
            
            if ($pdo) {

                $current_custom_fields = $content_current['custom_fields'] ?? [];
                if (!is_array($current_custom_fields)) {
                    $current_custom_fields = [];
                }
                

                $current_ids = [];
                foreach ($current_custom_fields as $field) {
                    if (is_array($field) && isset($field['id'])) {
                        $current_ids[] = $field['id'];
                    }
                }
                

                $new_ids = [];
                foreach ($custom_fields as $field) {
                    if (is_array($field) && isset($field['id'])) {
                        $new_ids[] = $field['id'];
                    }
                }
                

                $to_delete_raw = array_diff($current_ids, $new_ids);
                $to_delete = array_diff($to_delete_raw, array_keys($custom_field_renames));
                if (!empty($to_delete)) {
                    error_log("Champs personnalisés à supprimer: " . implode(', ', $to_delete));
                }
                // Rinominare le colonne quando l'id del campo cambia (es. Eta -> Etaaaa)
                foreach ($custom_field_renames as $rename_old_id => $rename_new_id) {
                    $rename_old_col = preg_replace(CONCORSO_REGEX_ALNUM_UNDERSCORE, '_', strtolower($rename_old_id));
                    $rename_new_col = preg_replace(CONCORSO_REGEX_ALNUM_UNDERSCORE, '_', strtolower($rename_new_id));
                    if ($rename_old_col === '' || $rename_new_col === '' || $rename_old_col === $rename_new_col) {
                        continue;
                    }
                    try {
                        $check_old = $pdo->query("SHOW COLUMNS FROM partecipazioni WHERE Field = '{$rename_old_col}'");
                        $check_new = $pdo->query("SHOW COLUMNS FROM partecipazioni WHERE Field = '{$rename_new_col}'");
                        if ($check_old && $check_old->rowCount() > 0 && (!$check_new || $check_new->rowCount() === 0)) {
                            $col_info = $check_old->fetch(PDO::FETCH_ASSOC);
                            $col_type = $col_info['Type'] ?? CONCORSO_SQL_VARCHAR255;
                            $col_null = (isset($col_info['Null']) && $col_info['Null'] === 'YES') ? 'NULL' : 'NOT NULL';
                            $def_val = $col_info['Default'] ?? null;
                            $col_default = ($def_val !== null && $def_val !== '' && strtoupper((string)$def_val) !== 'NULL') ? 'DEFAULT ' . $pdo->quote($def_val) : 'DEFAULT NULL';
                            $new_label = '';
                            foreach ($custom_fields as $f) {
                                if (isset($f['id']) && preg_replace(CONCORSO_REGEX_ALNUM_UNDERSCORE, '_', strtolower($f['id'])) === $rename_new_col) {
                                    $new_label = $f['label'] ?? '';
                                    break;
                                }
                            }
                            $col_comment = $new_label !== '' ? "COMMENT " . $pdo->quote('Campo personalizzato: ' . $new_label) : '';
                            $modify_sql = "ALTER TABLE partecipazioni CHANGE COLUMN `{$rename_old_col}` `{$rename_new_col}` {$col_type} {$col_null} {$col_default} " . trim($col_comment);
                            $pdo->exec($modify_sql);
                        }
                    } catch (Throwable $e) {
                        error_log("Errore rinomina colonna {$rename_old_col} -> {$rename_new_col}: " . $e->getMessage());
                    }
                }

                foreach ($to_delete as $field_id) {
                    $column_name = preg_replace(CONCORSO_REGEX_ALNUM_UNDERSCORE, '_', strtolower($field_id));
                    

                    try {

                        $check_stmt = $pdo->query("SHOW COLUMNS FROM partecipazioni LIKE '{$column_name}'");
                        if ($check_stmt->rowCount() > 0) {
                            $pdo->exec("ALTER TABLE partecipazioni DROP COLUMN `{$column_name}`");
                            error_log("Colonne '{$column_name}' supprimée avec succès");
                        }
                    } catch (Exception $e) {
                        error_log("Erreur suppression colonne {$column_name}: " . $e->getMessage());
                    }
                    

                    $old_column_name = 'custom_' . $column_name;
                    try {

                        $check_old_stmt = $pdo->query("SHOW COLUMNS FROM partecipazioni LIKE '{$old_column_name}'");
                        if ($check_old_stmt->rowCount() > 0) {
                            $pdo->exec("ALTER TABLE partecipazioni DROP COLUMN `{$old_column_name}`");
                            error_log("Ancienne colonne '{$old_column_name}' supprimée avec succès");
                        }
                    } catch (Exception $e) {
                        error_log("Erreur suppression ancienne colonne {$old_column_name}: " . $e->getMessage());
                    }
                }
                

                foreach ($custom_fields as $field) {
                    if (!is_array($field) || !isset($field['id'])) {
                        continue;
                    }
                    
                    $field_id = $field['id'];
                    $column_name = preg_replace(CONCORSO_REGEX_ALNUM_UNDERSCORE, '_', strtolower($field_id));
                    $field_type = strtolower($field['type'] ?? 'varchar');
                    $max_length = isset($field['max_length']) ? (int)$field['max_length'] : (isset($field['exact_length']) ? (int)$field['exact_length'] : null);
                    

                    $sql_type = CONCORSO_SQL_VARCHAR255;
                    if ($field_type === 'int') {
                        $sql_type = 'INT';
                    } elseif ($field_type === 'text') {
                        $sql_type = 'TEXT';
                    } elseif ($field_type === 'date') {
                        $sql_type = 'DATE';
                    } elseif ($field_type === 'boolean') {
                        $sql_type = 'TINYINT(1)';
                    } elseif ($field_type === 'varchar') {
                        if ($max_length !== null && $max_length > 0) {
                            $sql_type = 'VARCHAR(' . min(65535, max(1, $max_length)) . ')';
                        } else {
                            $sql_type = CONCORSO_SQL_VARCHAR255;
                        }
                    }
                    

                    $column_exists = false;
                    try {
                        $check_stmt = $pdo->query("SHOW COLUMNS FROM partecipazioni LIKE '{$column_name}'");
                        $column_exists = $check_stmt->rowCount() > 0;
                    } catch (Exception $e) {
                        // Colonna non esistente
                    }
                    
                    if (!$column_exists) {
                        try {
                            $safe_label = addslashes($field['label']);
                            $pdo->exec("ALTER TABLE partecipazioni ADD COLUMN `{$column_name}` {$sql_type} NULL DEFAULT NULL COMMENT 'Campo personalizzato: {$safe_label}'");
                        } catch (Exception $e) {
                            error_log("Erreur création colonne {$column_name}: " . $e->getMessage());
                        }
                    } else {
                        try {

                            $current_col = $pdo->query("SHOW COLUMNS FROM partecipazioni WHERE Field = '{$column_name}'")->fetch(PDO::FETCH_ASSOC);
                            $current_type = $current_col['Type'] ?? '';
                            $expected_comment = 'Campo personalizzato: ' . addslashes($field['label']);
                            $current_comment = $current_col['Comment'] ?? '';
                            $type_changed = stripos($current_type, $sql_type) === false;
                            $comment_changed = $current_comment !== $expected_comment;
                            if ($type_changed || $comment_changed) {
                                $safe_label = addslashes($field['label']);
                                $pdo->exec("ALTER TABLE partecipazioni MODIFY COLUMN `{$column_name}` {$sql_type} NULL DEFAULT NULL COMMENT 'Campo personalizzato: {$safe_label}'");
                            }
                        } catch (Exception $e) {
                            error_log("Erreur modification colonne {$column_name}: " . $e->getMessage());
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Erreur gestion colonnes custom fields: " . $e->getMessage());
        }
    } elseif (isset($content_current['custom_fields'])) {
        $newConfig['custom_fields'] = $content_current['custom_fields'];
    } elseif (isset($content_defaults['custom_fields'])) {
        $newConfig['custom_fields'] = $content_defaults['custom_fields'];
    }

    // Stile footer
    $newConfig['footer_style'] = [
        'mode' => $postText('footer_style_mode', $content_current['footer_style']['mode'] ?? ($content_defaults['footer_style']['mode'] ?? 'color')),
        'background' => $postText('footer_style_background', $content_current['footer_style']['background'] ?? ($content_defaults['footer_style']['background'] ?? CONCORSO_COLOR_WHITE)),
        'image' => $postText('footer_style_image', $content_current['footer_style']['image'] ?? ($content_defaults['footer_style']['image'] ?? '')),
        'opacity' => max(0, min(100, $postInt('footer_style_opacity', (int) ($content_current['footer_style']['opacity'] ?? ($content_defaults['footer_style']['opacity'] ?? 100))))),
        'image_position_vertical' => $postText('footer_style_image_position_vertical', $content_current['footer_style']['image_position_vertical'] ?? ($content_defaults['footer_style']['image_position_vertical'] ?? 'center')),
        'image_position_horizontal' => $postText('footer_style_image_position_horizontal', $content_current['footer_style']['image_position_horizontal'] ?? ($content_defaults['footer_style']['image_position_horizontal'] ?? 'center')),
        'image_size' => $postText('footer_style_image_size', $content_current['footer_style']['image_size'] ?? ($content_defaults['footer_style']['image_size'] ?? 'cover')),
        'image_size_custom' => $postText('footer_style_image_size_custom', $content_current['footer_style']['image_size_custom'] ?? ($content_defaults['footer_style']['image_size_custom'] ?? '')),
        'image_repeat' => $postText('footer_style_image_repeat', $content_current['footer_style']['image_repeat'] ?? ($content_defaults['footer_style']['image_repeat'] ?? 'no-repeat')),
        'text_primary' => $postText('footer_style_text_primary', $content_current['footer_style']['text_primary'] ?? ($content_defaults['footer_style']['text_primary'] ?? CONCORSO_COLOR_TEXT)),
        'text_secondary' => $postText('footer_style_text_secondary', $content_current['footer_style']['text_secondary'] ?? ($content_defaults['footer_style']['text_secondary'] ?? CONCORSO_COLOR_SECONDARY)),
        'link' => $postText('footer_style_link', $content_current['footer_style']['link'] ?? ($content_defaults['footer_style']['link'] ?? CONCORSO_COLOR_PRIMARY)),
        'link_hover' => $postText('footer_style_link_hover', $content_current['footer_style']['link_hover'] ?? ($content_defaults['footer_style']['link_hover'] ?? '#0a58ca')),
        'border_color' => $postText('footer_style_border_color', $content_current['footer_style']['border_color'] ?? ($content_defaults['footer_style']['border_color'] ?? CONCORSO_COLOR_BLACK)),
        'border_opacity' => max(0, min(100, $postInt('footer_style_border_opacity', (int) ($content_current['footer_style']['border_opacity'] ?? ($content_defaults['footer_style']['border_opacity'] ?? 10))))),
        'border_width' => isset($_POST['footer_style_border_enabled']) && $_POST['footer_style_border_enabled'] === '1' 
            ? max(0, min(20, $postInt('footer_style_border_width', (int) ($content_current['footer_style']['border_width'] ?? ($content_defaults['footer_style']['border_width'] ?? 1)))))
            : 0,
        'border_sides' => (isset($_POST['footer_style_border_enabled']) && $_POST['footer_style_border_enabled'] === '1' && isset($_POST['footer_style_border_sides']) && is_array($_POST['footer_style_border_sides']))
            ? array_intersect($_POST['footer_style_border_sides'], ['top', 'right', 'bottom', 'left'])
            : [],
    ];

    $footerLayoutBlockKeys = [
        'contest_title', 'contest_dates', 'prize_total',
        'links_regolamento', 'links_privacy', 'links_cookie_policy', 'links_assistenza',
        'promoter', 'delegate', 'legal_block_1', 'legal_block_2',
    ];
    $footerLayoutBlocks = [];
    foreach ($footerLayoutBlockKeys as $blockKey) {
        $footerLayoutBlocks[$blockKey] = isset($_POST['footer_layout_block_' . $blockKey]) && $_POST['footer_layout_block_' . $blockKey] === '1';
    }
    // Link Cookie Policy solo se cookie banner attivo
    if (!$ccEnabled) {
        $footerLayoutBlocks['links_cookie_policy'] = false;
    }
    $footerLayoutTemplate = $postText('footer_layout_template', $content_current['footer_layout']['template'] ?? ($content_defaults['footer_layout']['template'] ?? 'three_columns'));
    if (!in_array($footerLayoutTemplate, ['three_columns', 'centered_compact'], true)) {
        $footerLayoutTemplate = 'three_columns';
    }
    $newConfig['footer_layout'] = [
        'template' => $footerLayoutTemplate,
        'show_logo' => (isset($_POST['footer_layout_show_logo']) && $_POST['footer_layout_show_logo'] === '1')
            || (trim($postText('footer_layout_logo_image', '')) !== '' && isset($_FILES['upload_footer_logo_image']) && ($_FILES['upload_footer_logo_image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK),
        'use_contest_logo' => isset($_POST['footer_layout_use_contest_logo']) && $_POST['footer_layout_use_contest_logo'] === '1',
        'logo_image' => $postText('footer_layout_logo_image', $content_current['footer_layout']['logo_image'] ?? ($content_defaults['footer_layout']['logo_image'] ?? '')),
        'logo_height_px' => max(24, min(200, $postInt('footer_layout_logo_height_px', (int) ($content_current['footer_layout']['logo_height_px'] ?? ($content_defaults['footer_layout']['logo_height_px'] ?? 60))))),
        'font_family' => trim($postText('footer_layout_font_family', $content_current['footer_layout']['font_family'] ?? '')),
        'font_size' => contest_resolve_font_size($postText('footer_layout_font_size', $content_current['footer_layout']['font_size'] ?? '')),
        'blocks' => $footerLayoutBlocks,
        'texts' => [
            'contest_title' => trim((string) ($_POST['footer_layout_text_contest_title'] ?? '')),
            'intro_line' => trim((string) ($_POST['footer_layout_text_intro_line'] ?? '')),
            'legal_html_1' => contest_normalize_summernote_font_html(trim((string) ($_POST['footer_layout_text_legal_html_1'] ?? ''))),
            'legal_html_2' => contest_normalize_summernote_font_html(trim((string) ($_POST['footer_layout_text_legal_html_2'] ?? ''))),
            'link_regolamento_label' => $postText('footer_layout_text_link_regolamento_label', $content_current['footer_layout']['texts']['link_regolamento_label'] ?? ($content_defaults['footer_layout']['texts']['link_regolamento_label'] ?? 'Regolamento completo')),
            'link_privacy_label' => $postText('footer_layout_text_link_privacy_label', $content_current['footer_layout']['texts']['link_privacy_label'] ?? ($content_defaults['footer_layout']['texts']['link_privacy_label'] ?? 'Privacy Policy')),
            'link_cookie_policy_label' => $postText('footer_layout_text_link_cookie_policy_label', $content_current['footer_layout']['texts']['link_cookie_policy_label'] ?? ($content_defaults['footer_layout']['texts']['link_cookie_policy_label'] ?? 'Cookie Policy')),
            'link_assistenza_label' => $postText('footer_layout_text_link_assistenza_label', $content_current['footer_layout']['texts']['link_assistenza_label'] ?? ($content_defaults['footer_layout']['texts']['link_assistenza_label'] ?? 'Assistenza')),
        ],
    ];
    $newConfig['footer_layout'] = normalize_footer_layout($newConfig['footer_layout'], $content_defaults['footer_layout'] ?? []);

    $isEmail = static function (string $value): bool {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    };
    $isDate = static function (string $value): bool {
        $dt = DateTime::createFromFormat('Y-m-d', $value);
        return $dt !== false && $dt->format('Y-m-d') === $value;
    };
    $isHexColor = static function (string $value): bool {
        return (bool) preg_match('/^#?[0-9a-fA-F]{6}$/', trim($value));
    };

    if (trim($newConfig['name']) === '') {
        $errors[] = 'Il nome del concorso è obbligatorio.';
    }
    if ($dateFormatSelected === 'custom' && trim($newConfig['date_format']) === '') {
        $errors[] = 'Inserisci un formato data personalizzato valido.';
    }
    if ($newConfig['start_date'] === '' || !$isDate($newConfig['start_date'])) {
        $errors[] = 'Data inizio operatività non valida (formato YYYY-MM-DD).';
    }
    if ($newConfig['end_date'] === '' || !$isDate($newConfig['end_date'])) {
        $errors[] = 'Data fine operatività non valida (formato YYYY-MM-DD).';
    }
    if ($isDate($newConfig['start_date']) && $isDate($newConfig['end_date'])) {
        if (strtotime($newConfig['end_date']) < strtotime($newConfig['start_date'])) {
            $errors[] = 'La data fine operatività deve essere successiva o uguale alla data inizio operatività.';
        }
    }
    if ($newConfig['display_start_date'] !== '' && !$isDate($newConfig['display_start_date'])) {
        $errors[] = 'Data inizio visualizzata non valida (formato YYYY-MM-DD).';
    }
    if ($newConfig['display_end_date'] !== '' && !$isDate($newConfig['display_end_date'])) {
        $errors[] = 'Data fine visualizzata non valida (formato YYYY-MM-DD).';
    }
    if ($isDate($newConfig['display_start_date']) && $isDate($newConfig['display_end_date'])) {
        if (strtotime($newConfig['display_end_date']) < strtotime($newConfig['display_start_date'])) {
            $errors[] = 'La data fine visualizzata deve essere successiva o uguale alla data inizio visualizzata.';
        }
    }
    if ($newConfig['base_url'] !== '' && !filter_var($newConfig['base_url'], FILTER_VALIDATE_URL)) {
        $errors[] = 'Base URL non valida.';
    }
    if ($newConfig['min_spend'] < 0) {
        $errors[] = 'La spesa minima non può essere negativa.';
    }
    if ($newConfig['min_spend'] > 100000) {
        $errors[] = 'La spesa minima non può superare 100.000.';
    }
    $minUploadBytes = $maxUploadMinMb * 1024 * 1024;
    $maxUploadBytes = $maxUploadMaxMb * 1024 * 1024;
    if ($newConfig['max_file_size'] < $minUploadBytes || $newConfig['max_file_size'] > $maxUploadBytes) {
        $errors[] = 'Seleziona un limite di upload tra ' . $maxUploadMinMb . ' MB e ' . $maxUploadMaxMb . ' MB.';
    }
    $requireReceiptTypes = ($fields_enabled['scontrino'] ?? false) === true;
    if ($requireReceiptTypes && empty($newConfig['receipt_allowed_extensions'])) {
        $errors[] = 'Seleziona almeno un tipo di file consentito per lo scontrino.';
    }
    $bgColor = $newConfig['background_color'] ?? '';
    if ($bgColor !== '' && !$isHexColor($bgColor)) {
        $errors[] = 'Colore di sfondo non valido (usa HEX).';
    }
    foreach ($newConfig['colors'] as $colorVal) {
        if ($colorVal !== '' && !$isHexColor($colorVal)) {
            $errors[] = 'Inserisci codici colore HEX validi per la palette.';
            break;
        }
    }
    if (($newConfig['form_container']['mode'] ?? 'color') === 'color') {
        $fcColor = $newConfig['form_container']['color'] ?? '';
        if ($fcColor !== '' && !$isHexColor($fcColor)) {
            $errors[] = 'Colore container form non valido (usa HEX).';
        }
    }
    if (isset($newConfig['public_page_containers']) && is_array($newConfig['public_page_containers'])) {
        foreach ($newConfig['public_page_containers'] as $pageContainerStyle) {
            if (!is_array($pageContainerStyle) || (($pageContainerStyle['mode'] ?? 'color') !== 'color')) {
                continue;
            }
            $pageContainerColor = $pageContainerStyle['color'] ?? '';
            if ($pageContainerColor !== '' && !$isHexColor($pageContainerColor)) {
                $errors[] = 'Colore container pagina pubblica non valido (usa HEX).';
                break;
            }
        }
    }

    // Validazioni email (SMTP validato nell'AJAX smtp_env_save.php)
    if ($newConfig['helpdesk_email'] !== '' && !$isEmail($newConfig['helpdesk_email'])) {
        $errors[] = 'Email assistenza non valida.';
    }
    // FAQ configurabili (faq_json in testa al form = affidabile anche con form molto grandi)
    if (isset($_POST['faq_present']) || isset($_POST['faq_json'])) {
        $new_faq = [];
        $faq_json_raw = trim((string) ($_POST['faq_json'] ?? ''));
        if ($faq_json_raw !== '') {
            $decoded_faq = json_decode($faq_json_raw, true);
            if (is_array($decoded_faq)) {
                $new_faq = concorso_normalize_faq_list($decoded_faq);
            }
        }
        if (empty($new_faq) && isset($_POST['faq_q'])) {
            $faq_q = $_POST['faq_q'] ?? [];
            $faq_a = $_POST['faq_a'] ?? [];
            $faq_order = $_POST['faq_order'] ?? [];
            $legacy = [];
            $maxFaq = max(count($faq_q), count($faq_a), count($faq_order));
            for ($i = 0; $i < $maxFaq; $i++) {
                $legacy[] = [
                    'q' => $faq_q[$i] ?? '',
                    'a' => $faq_a[$i] ?? '',
                    'order' => $faq_order[$i] ?? null,
                ];
            }
            $new_faq = concorso_normalize_faq_list($legacy);
        }
        $newConfig['faq'] = $new_faq;
    } else {
        $newConfig['faq'] = $content_current['faq'] ?? ($content_defaults['faq'] ?? []);
    }

    if (isset($_POST['gestione_dati_visible_columns_present']) || isset($_POST['gestione_dati_visible_columns_json'])) {
        require_once __DIR__ . '/../res/inc/gestione_dati_context.inc.php';
        require_once __DIR__ . '/../res/inc/gestione_dati_columns.inc.php';
        $pdo_gd_cols = isset($connection) && $connection instanceof PDO ? $connection : (function_exists('conn_pdo') ? conn_pdo() : null);
        $gd_cols_raw = trim((string) ($_POST['gestione_dati_visible_columns_json'] ?? ''));
        if ($pdo_gd_cols instanceof PDO && $gd_cols_raw !== '') {
            $decoded_gd_cols = json_decode($gd_cols_raw, true);
            if (is_array($decoded_gd_cols)) {
                $validatedGdCols = gestione_dati_validate_visible_columns_map(
                    $decoded_gd_cols,
                    $pdo_gd_cols,
                    $newConfig,
                    $content_defaults
                );
                if ($validatedGdCols !== []) {
                    $newConfig['gestione_dati_visible_columns'] = $validatedGdCols;
                }
            }
        }
    } elseif (!empty($content_current['gestione_dati_visible_columns']) && is_array($content_current['gestione_dati_visible_columns'])) {
        $newConfig['gestione_dati_visible_columns'] = $content_current['gestione_dati_visible_columns'];
    }

    if (isset($_POST['faq_present']) || isset($_POST['help_page_style_present'])) {
        $pickHelpPageHex = static function (string $postKey, array $currentStyle, string $section, string $field): string {
            if (!isset($_POST[$postKey])) {
                return trim((string) ($currentStyle[$section][$field] ?? ''));
            }
            $raw = trim((string) $_POST[$postKey]);
            if ($raw === '') {
                return '';
            }
            return preg_match(CONCORSO_REGEX_HEX6, $raw) ? strtolower($raw) : trim((string) ($currentStyle[$section][$field] ?? ''));
        };
        $pickHelpPageBg = static function (string $postKey, array $currentStyle, string $section, string $field) use ($pickHelpPageHex): string {
            if (!isset($_POST[$postKey])) {
                return trim((string) ($currentStyle[$section][$field] ?? ''));
            }
            $raw = strtolower(trim((string) $_POST[$postKey]));
            if ($raw === '') {
                return '';
            }
            if ($raw === 'transparent') {
                return 'transparent';
            }
            return $pickHelpPageHex($postKey, $currentStyle, $section, $field);
        };
        $currentHelpStyle = is_array($content_current['help_page_style'] ?? null)
            ? $content_current['help_page_style']
            : ($content_defaults['help_page_style'] ?? []);
        if (!is_array($currentHelpStyle)) {
            $currentHelpStyle = [];
        }
        $currentAcc = is_array($currentHelpStyle['accordion'] ?? null) ? $currentHelpStyle['accordion'] : [];
        $currentForm = is_array($currentHelpStyle['form'] ?? null) ? $currentHelpStyle['form'] : [];
        $newConfig['help_page_style'] = [
            'font_family' => trim($postText('help_page_font_family', $currentHelpStyle['font_family'] ?? '')),
            'font_size' => contest_resolve_font_size($postText('help_page_font_size', $currentHelpStyle['font_size'] ?? '')),
            'accordion' => [
                'header_bg' => $pickHelpPageBg('help_accordion_header_bg', $currentHelpStyle, 'accordion', 'header_bg'),
                'header_text' => $pickHelpPageHex('help_accordion_header_text', $currentHelpStyle, 'accordion', 'header_text'),
                'header_bg_open' => $pickHelpPageBg('help_accordion_header_bg_open', $currentHelpStyle, 'accordion', 'header_bg_open'),
                'header_text_open' => $pickHelpPageHex('help_accordion_header_text_open', $currentHelpStyle, 'accordion', 'header_text_open'),
                'body_bg' => $pickHelpPageBg('help_accordion_body_bg', $currentHelpStyle, 'accordion', 'body_bg'),
                'body_text' => $pickHelpPageHex('help_accordion_body_text', $currentHelpStyle, 'accordion', 'body_text'),
                'title_text' => $pickHelpPageHex('help_accordion_title_text', $currentHelpStyle, 'accordion', 'title_text'),
                'title_font_size' => contest_resolve_font_size($postText(
                    'help_accordion_title_font_size',
                    (string) ($currentAcc['title_font_size'] ?? '')
                )),
                'title_margin_top' => max(0, min(120, (int) ($_POST['help_accordion_title_margin_top'] ?? ($currentAcc['title_margin_top'] ?? 0)))),
                'border_color' => $pickHelpPageHex('help_accordion_border_color', $currentHelpStyle, 'accordion', 'border_color'),
            ],
            'form' => [
                'heading_text' => $pickHelpPageHex('help_form_heading_text', $currentHelpStyle, 'form', 'heading_text'),
                'subtitle_text' => $pickHelpPageHex('help_form_subtitle_text', $currentHelpStyle, 'form', 'subtitle_text'),
                'field_text' => $pickHelpPageHex('help_form_field_text', $currentHelpStyle, 'form', 'field_text'),
                'placeholder_text' => $pickHelpPageHex('help_form_placeholder_text', $currentHelpStyle, 'form', 'placeholder_text'),
                'field_bg' => $pickHelpPageBg('help_form_field_bg', $currentHelpStyle, 'form', 'field_bg'),
                'field_border' => $pickHelpPageHex('help_form_field_border', $currentHelpStyle, 'form', 'field_border'),
                'checkbox' => $pickHelpPageHex('help_form_checkbox', $currentHelpStyle, 'form', 'checkbox'),
                'consent_text' => $pickHelpPageHex('help_form_consent_text', $currentHelpStyle, 'form', 'consent_text'),
                'consent_link' => $pickHelpPageHex('help_form_consent_link', $currentHelpStyle, 'form', 'consent_link'),
                'consent_link_hover' => $pickHelpPageHex('help_form_consent_link_hover', $currentHelpStyle, 'form', 'consent_link_hover'),
            ],
        ];
    } else {
        $newConfig['help_page_style'] = $content_current['help_page_style'] ?? ($content_defaults['help_page_style'] ?? []);
        if (array_key_exists('help_page_font_family', $_POST) || array_key_exists('help_page_font_size', $_POST)) {
            if (!is_array($newConfig['help_page_style'])) {
                $newConfig['help_page_style'] = [];
            }
            if (array_key_exists('help_page_font_family', $_POST)) {
                $newConfig['help_page_style']['font_family'] = trim((string) $_POST['help_page_font_family']);
            }
            if (array_key_exists('help_page_font_size', $_POST)) {
                $newConfig['help_page_style']['font_size'] = contest_resolve_font_size((string) $_POST['help_page_font_size']);
            }
        }
    }

    // Gestione upload file e sostituzione percorsi
    $contestSlug = $newConfig['name'] ?? '';
    $contestSlug = strtolower(trim($contestSlug));
    $contestSlug = preg_replace('/[^a-z0-9]+/i', '_', $contestSlug);
    $contestSlug = trim($contestSlug, '_');
    if ($contestSlug === '') {
        $contestSlug = 'concorso';
    }
    $uploadedFiles = [
        'upload_logo' => ['path' => ['logo'], 'dir' => $logoUploadDir, 'base' => $logoBasePath, 'static_name' => 'logo'],
        'upload_logo_mobile' => ['path' => ['logo_mobile'], 'dir' => $logoUploadDir, 'base' => $logoBasePath, 'static_name' => 'logo_mobile'],
        'upload_header_image' => ['path' => ['header_image'], 'dir' => $headerUploadDir, 'base' => $headerBasePath, 'static_name' => 'header'],
        'upload_header_image_mobile' => ['path' => ['header_image_mobile'], 'dir' => $headerUploadDir, 'base' => $headerBasePath, 'static_name' => 'header_mobile'],
        'upload_favicon' => ['path' => ['favicon'], 'dir' => $faviconUploadDir, 'base' => $faviconBasePath, 'static_name' => 'favicon'],
        'upload_favicon_mobile' => ['path' => ['favicon_mobile'], 'dir' => $faviconUploadDir, 'base' => $faviconBasePath, 'static_name' => 'favicon_mobile'],
        'upload_mail_banner' => ['path' => ['mail_banner'], 'dir' => $emailUploadDir, 'base' => $emailBasePath, 'static_name' => 'banner'],
        // Image di sfondo dei "cards" (registration/waiting/thankyou): la consideriamo parte della sezione registration
        'upload_form_container_image' => ['path' => ['form_container', 'image'], 'dir' => $registrationUploadDir, 'base' => $registrationBasePath, 'static_name' => 'form_container'],
        'upload_footer_image' => ['path' => ['footer_style', 'image'], 'dir' => $footerUploadDir, 'base' => $footerBasePath, 'static_name' => 'footer_background'],
        'upload_footer_logo_image' => ['path' => ['footer_layout', 'logo_image'], 'dir' => $footerUploadDir, 'base' => $footerBasePath, 'static_name' => 'footer_logo'],
        'upload_button_primary_image' => ['path' => ['buttons', 'primary', 'image'], 'dir' => $registrationUploadDir, 'base' => $registrationBasePath, 'static_name' => 'button_primary'],
        'upload_button_primary_hover_image' => ['path' => ['buttons', 'primary', 'hover_image'], 'dir' => $registrationUploadDir, 'base' => $registrationBasePath, 'static_name' => 'button_primary_hover'],
        'upload_button_secondary_image' => ['path' => ['buttons', 'secondary', 'image'], 'dir' => $registrationUploadDir, 'base' => $registrationBasePath, 'static_name' => 'button_secondary'],
        'upload_button_secondary_hover_image' => ['path' => ['buttons', 'secondary', 'hover_image'], 'dir' => $registrationUploadDir, 'base' => $registrationBasePath, 'static_name' => 'button_secondary_hover'],
        'upload_background_default' => ['path' => ['backgrounds', 'default'], 'dir' => $backgroundUploadDir, 'base' => $backgroundBasePath, 'static_name' => 'background_default'],
        'upload_background_mobile' => ['path' => ['backgrounds', 'mobile'], 'dir' => $backgroundUploadDir, 'base' => $backgroundBasePath, 'static_name' => 'background_mobile'],
        'upload_background_tablet' => ['path' => ['backgrounds', 'tablet'], 'dir' => $backgroundUploadDir, 'base' => $backgroundBasePath, 'static_name' => 'background_tablet'],
        'upload_background_laptop' => ['path' => ['backgrounds', 'laptop'], 'dir' => $backgroundUploadDir, 'base' => $backgroundBasePath, 'static_name' => 'background_laptop'],
        'upload_background_desktop' => ['path' => ['backgrounds', 'desktop'], 'dir' => $backgroundUploadDir, 'base' => $backgroundBasePath, 'static_name' => 'background_desktop'],
        'upload_background_ultrawide' => ['path' => ['backgrounds', 'ultrawide'], 'dir' => $backgroundUploadDir, 'base' => $backgroundBasePath, 'static_name' => 'background_ultrawide'],
        'upload_privacy_pdf' => [
            'path' => ['privacy_link'],
            'dir' => $pdfUploadDir,
            'base' => $pdfBasePath,
            'allowed' => ['pdf'],
            'static_name' => "privacy_{$contestSlug}.pdf",
        ],
        'upload_cookie_policy_pdf' => [
            'path' => ['cookie_policy_link'],
            'dir' => $pdfUploadDir,
            'base' => $pdfBasePath,
            'allowed' => ['pdf'],
            'static_name' => "cookie_policy_{$contestSlug}.pdf",
        ],
        'upload_rules_pdf' => [
            'path' => ['rules_link'],
            'dir' => $pdfUploadDir,
            'base' => $pdfBasePath,
            'allowed' => ['pdf'],
            'static_name' => "regolamento_{$contestSlug}.pdf",
        ],
    ];
    foreach (manager_public_page_container_definitions() as $pageContainerKey => $_pageContainerMeta) {
        $uploadedFiles[manager_public_page_container_upload_input($pageContainerKey)] = [
            'path' => ['public_page_containers', $pageContainerKey, 'image'],
            'dir' => $registrationUploadDir,
            'base' => $registrationBasePath,
            'allowed' => ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'],
            'static_name' => 'public_page_container_' . preg_replace('/[^a-z0-9_]+/i', '_', $pageContainerKey),
        ];
    }

    $setByPath = static function (array &$config, array $path, string $value): void {
        $ref = &$config;
        foreach ($path as $segment) {
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }
            $ref = &$ref[$segment];
        }
        $ref = $value;
    };

    foreach ($uploadedFiles as $inputName => $info) {
        $allowed = $info['allowed'] ?? null;
        $targetName = $info['static_name'] ?? null;
        $nameResolver = $info['name_resolver'] ?? null;
        $newValue = concorso_handle_config_upload($inputName, $info['dir'], $info['base'], $errors, $hasManagerUploadSizeError, $configAssetUploadMaxBytes, $configAssetUploadMaxLabel, $allowed, $targetName, $nameResolver);
        if ($newValue !== null) {
            $setByPath($newConfig, $info['path'], $newValue);
            if (
                ($info['path'][0] ?? '') === 'public_page_containers'
                && isset($info['path'][1])
                && (($info['path'][2] ?? '') === 'image')
            ) {
                $newConfig['public_page_containers'][$info['path'][1]]['mode'] = 'image';
            }
        }
    }

    $customFontFamiliesPosted = [];
    if (isset($_POST['custom_font_family'])) {
        if (is_array($_POST['custom_font_family'])) {
            foreach ($_POST['custom_font_family'] as $familyPosted) {
                $customFontFamiliesPosted[] = trim((string) $familyPosted);
            }
        } else {
            $customFontFamiliesPosted[] = trim((string) $_POST['custom_font_family']);
        }
    }
    $customFontFiles = [];
    if (isset($_FILES['upload_custom_font']) && is_array($_FILES['upload_custom_font'])) {
        $uploadBag = $_FILES['upload_custom_font'];
        // Righe multiple: upload_custom_font[] (una riga = un file + nome famiglia)
        if (isset($uploadBag['name']) && is_array($uploadBag['name'])) {
            $count = count($uploadBag['name']);
            for ($fi = 0; $fi < $count; $fi++) {
                $customFontFiles[] = [
                    'name' => (string) ($uploadBag['name'][$fi] ?? ''),
                    'type' => (string) ($uploadBag['type'][$fi] ?? ''),
                    'tmp_name' => (string) ($uploadBag['tmp_name'][$fi] ?? ''),
                    'error' => (int) ($uploadBag['error'][$fi] ?? UPLOAD_ERR_NO_FILE),
                    'size' => (int) ($uploadBag['size'][$fi] ?? 0),
                ];
            }
        } elseif (($uploadBag['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $customFontFiles[] = $uploadBag;
        }
    }
    $fontItems = is_array($newConfig['fonts']['items'] ?? null) ? $newConfig['fonts']['items'] : [];
    foreach ($customFontFiles as $fontIndex => $fontFileRow) {
        if ((int) ($fontFileRow['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $familyForFile = trim((string) ($customFontFamiliesPosted[$fontIndex] ?? ''));
        if ($familyForFile === '') {
            $familyForFile = contest_font_family_from_filename((string) ($fontFileRow['name'] ?? ''));
        }
        if ($familyForFile === '') {
            $errors[] = 'Indica il nome famiglia per il font caricato (o usa un nome file valido).';
            continue;
        }
        // Re-inject single-file shape for the shared uploader helper
        $_FILES['__upload_custom_font_one'] = $fontFileRow;
        $customFontUploaded = concorso_handle_config_upload(
            '__upload_custom_font_one',
            $customFontUploadDir,
            $customFontBasePath,
            $errors,
            $hasManagerUploadSizeError,
            $configAssetUploadMaxBytes,
            $configAssetUploadMaxLabel,
            ['woff2', 'woff', 'ttf', 'otf'],
            null,
            static function (array $file, string $ext): string {
                // Keep original filename so content.json paths stay stable across local/prod saves.
                $original = (string) ($file['name'] ?? 'font');
                $base = pathinfo($original, PATHINFO_FILENAME);
                $slug = trim((string) preg_replace('/[^a-zA-Z0-9._-]+/', '-', $base), '.-_');
                if ($slug === '') {
                    $slug = 'font';
                }
                return $slug . '.' . $ext;
            }
        );
        unset($_FILES['__upload_custom_font_one']);
        if ($customFontUploaded === null) {
            continue;
        }
        $familyKey = function_exists('mb_strtolower')
            ? mb_strtolower($familyForFile, 'UTF-8')
            : strtolower($familyForFile);
        $replaced = false;
        foreach ($fontItems as $fi => $existingItem) {
            if (!is_array($existingItem)) {
                continue;
            }
            $existingFamily = trim((string) ($existingItem['family'] ?? ''));
            $existingKey = function_exists('mb_strtolower')
                ? mb_strtolower($existingFamily, 'UTF-8')
                : strtolower($existingFamily);
            $existingFile = function_exists('asset_path_to_relative')
                ? asset_path_to_relative(trim((string) ($existingItem['file'] ?? '')))
                : trim((string) ($existingItem['file'] ?? ''));
            if ($existingKey === $familyKey || $existingFile === $customFontUploaded) {
                $fontItems[$fi] = [
                    'id' => (string) ($existingItem['id'] ?? ('font_' . substr(sha1($familyForFile . '|' . $customFontUploaded), 0, 12))),
                    'family' => $familyForFile,
                    'file' => $customFontUploaded,
                ];
                $replaced = true;
                break;
            }
        }
        if (!$replaced) {
            $fontItems[] = [
                'id' => 'font_' . substr(sha1($familyForFile . '|' . $customFontUploaded), 0, 12),
                'family' => $familyForFile,
                'file' => $customFontUploaded,
            ];
        }
    }
    if ($fontItems !== []) {
        $newConfig['fonts'] = normalize_fonts_config(['items' => $fontItems]);
    }

    $fontsForResolve = ['fonts' => normalize_fonts_config($newConfig['fonts'] ?? [])];
    $newConfig['fonts'] = $fontsForResolve['fonts'];
    $resolvedFormFieldFont = contest_resolve_font_family((string) ($newConfig['form_field_font'] ?? ''), $fontsForResolve);
    if ($resolvedFormFieldFont === '') {
        unset($newConfig['form_field_font']);
    } else {
        $newConfig['form_field_font'] = $resolvedFormFieldFont;
    }
    $resolvedFormFieldFontSize = contest_resolve_font_size((string) ($newConfig['form_field_font_size'] ?? ''));
    if ($resolvedFormFieldFontSize === '') {
        unset($newConfig['form_field_font_size']);
    } else {
        $newConfig['form_field_font_size'] = $resolvedFormFieldFontSize;
    }
    foreach (['form_page', 'participation_page'] as $buttonFooterPageKey) {
        if (!isset($newConfig['pages'][$buttonFooterPageKey]) || !is_array($newConfig['pages'][$buttonFooterPageKey])) {
            continue;
        }
        $resolvedBtnFont = contest_resolve_font_family(
            (string) ($newConfig['pages'][$buttonFooterPageKey]['button_footer_text_font'] ?? ''),
            $fontsForResolve
        );
        if ($resolvedBtnFont === '') {
            unset($newConfig['pages'][$buttonFooterPageKey]['button_footer_text_font']);
        } else {
            $newConfig['pages'][$buttonFooterPageKey]['button_footer_text_font'] = $resolvedBtnFont;
        }
    }
    if (isset($newConfig['footer_layout']) && is_array($newConfig['footer_layout'])) {
        $resolvedFooterFont = contest_resolve_font_family((string) ($newConfig['footer_layout']['font_family'] ?? ''), $fontsForResolve);
        $newConfig['footer_layout']['font_family'] = $resolvedFooterFont;
        $newConfig['footer_layout']['font_size'] = contest_resolve_font_size((string) ($newConfig['footer_layout']['font_size'] ?? ''));
    }
    if (isset($newConfig['help_page_style']) && is_array($newConfig['help_page_style'])) {
        $resolvedHelpFont = contest_resolve_font_family((string) ($newConfig['help_page_style']['font_family'] ?? ''), $fontsForResolve);
        $newConfig['help_page_style']['font_family'] = $resolvedHelpFont;
        $newConfig['help_page_style']['font_size'] = contest_resolve_font_size((string) ($newConfig['help_page_style']['font_size'] ?? ''));
    }

    $resolvedPublicPageContainerStyles = manager_resolve_public_page_container_styles($newConfig, $content_defaults);
    foreach (manager_public_page_container_definitions() as $pageContainerKey => $pageContainerMeta) {
        $pageContainerStyle = $resolvedPublicPageContainerStyles[$pageContainerKey] ?? [];
        if (!is_array($pageContainerStyle) || (($pageContainerStyle['mode'] ?? 'color') !== 'image')) {
            continue;
        }
        if (trim((string) ($pageContainerStyle['image'] ?? '')) === '') {
            $pageContainerLabel = (string) ($pageContainerMeta['label'] ?? $pageContainerKey);
            $errors[] = 'Container pagina - ' . $pageContainerLabel . ': seleziona un\'immagine oppure imposta la modalita su Colore.';
        }
    }

    $renamePdfIfNeeded = static function (string $currentPath, string $targetName) use ($pdfUploadDir, $pdfBasePath): ?string {
        $currentPath = trim($currentPath);
        if ($currentPath === '') {
            return null;
        }
        $pathOnly = parse_url($currentPath, PHP_URL_PATH);
        if (!is_string($pathOnly)) {
            return null;
        }
        $pathOnly = ltrim($pathOnly, '/');
        if (strpos($pathOnly, CONCORSO_ASSETS_PDF) !== 0) {
            return null;
        }
        $filename = basename($pathOnly);
        if ($filename === $targetName) {
            return null;
        }
        if (in_array($filename, ['privacy_concorso_generico.pdf', 'regolamento_concorso_generico.pdf'], true)) {
            return null;
        }
        $relative = substr($pathOnly, strlen(CONCORSO_ASSETS_PDF));
        $relative = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative), DIRECTORY_SEPARATOR);
        if ($relative === '' || strpos($relative, '..') !== false || preg_match(CONCORSO_REGEX_WIN_DRIVE, $relative)) {
            return null;
        }
        $sourcePath = rtrim($pdfUploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relative;
        if (!is_file($sourcePath)) {
            return null;
        }
        $targetName = basename($targetName);
        $targetPath = rtrim($pdfUploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $targetName;
        if (is_file($targetPath)) {
            @unlink($targetPath);
        }
        if (!@rename($sourcePath, $targetPath)) {
            return null;
        }
        return rtrim($pdfBasePath, '/') . '/' . $targetName;
    };

    $privacyPath = $newConfig['privacy_link'] ?? '';
    $rulesPath = $newConfig['rules_link'] ?? '';
    $cookiePolicyPath = $newConfig['cookie_policy_link'] ?? '';
    $newPrivacyPath = $renamePdfIfNeeded($privacyPath, "privacy_{$contestSlug}.pdf");
    if ($newPrivacyPath !== null) {
        $newConfig['privacy_link'] = $newPrivacyPath;
    }
    $newRulesPath = $renamePdfIfNeeded($rulesPath, "regolamento_{$contestSlug}.pdf");
    if ($newRulesPath !== null) {
        $newConfig['rules_link'] = $newRulesPath;
    }
    $newCookiePolicyPath = $renamePdfIfNeeded($cookiePolicyPath, "cookie_policy_{$contestSlug}.pdf");
    if ($newCookiePolicyPath !== null) {
        $newConfig['cookie_policy_link'] = $newCookiePolicyPath;
    }

    if (empty($errors)) {
        $oldImages = concorso_collect_config_images($content_current);
        $newImages = concorso_collect_config_images($newConfig);
        foreach ($oldImages as $oldPath => $_) {
            if (!isset($newImages[$oldPath])) {
                concorso_delete_config_image($oldPath, $baseDir, $uploadDir, $pdfUploadDir);
            }
        }
        $json = json_encode($newConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            $errors[] = 'Impossibile serializzare la configurazione.';
        } else {
            // DEBUG - vedere cosa viene scritto nel JSON per custom_fields
            if (isset($newConfig['custom_fields'])) {
                error_log("WRITING TO JSON - custom_fields: " . json_encode($newConfig['custom_fields'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
            $written = @file_put_contents($contentPath, $json);
            if ($written === false) {
                $errors[] = 'Errore nel salvataggio del file content.json.';
            } else {
                if (!empty($creatingNewContentJson)) {
                    require_once __DIR__ . '/../../app/logic/contest_security.php';
                    $pdo_sec_new = isset($connection) && $connection instanceof PDO ? $connection : (function_exists('conn_pdo') ? conn_pdo() : null);
                    if ($pdo_sec_new instanceof PDO) {
                        contest_security_reset_to_defaults($pdo_sec_new);
                    }
                    contest_security_codice_protection_set_enabled(false);
                }
                // Modalità codice Instant Win: aggiorna codici_premio in base alla modalità scelta
                if ($contestMode === 'instantwin') {
                    $pdo_codici = function_exists('conn_pdo') ? conn_pdo() : null;
                    if ($pdo_codici instanceof PDO) {
                        try {
                            if (in_array($postedCodicePartMode, ['pool', 'combinazione'], true)) {
                                // Rimettere disponibili i codici_premio non utilizzati (per estrazione instant win)
                                // Imposta a NULL id_codice_partecipazione per tutti i codici non utilizzati,
                                // anche se sono stati assegnati in modalità "vincita diretta" precedentemente
                                // Rimuove la pre-assignazione per permettere l'estrazione casuale
                                $stmt = $pdo_codici->prepare('
                                    UPDATE codici_premio
                                    SET id_codice_partecipazione = NULL
                                    WHERE used = 0
                                ');
                                $stmt->execute();
                            } elseif ($postedCodicePartMode === 'direct_win') {
                                // Vincita diretta: assegnare ogni codice_premio al codice_partecipazione corrispondente (ordine crescente)
                                $stmt_premio = $pdo_codici->query('SELECT id FROM codici_premio WHERE isActive = 1 ORDER BY id ASC');
                                $stmt_cp = $pdo_codici->query('SELECT id FROM codici_partecipazione ORDER BY id ASC');
                                $premio_ids = $stmt_premio ? $stmt_premio->fetchAll(PDO::FETCH_COLUMN) : [];
                                $cp_ids = $stmt_cp ? $stmt_cp->fetchAll(PDO::FETCH_COLUMN) : [];
                                $update_stmt = $pdo_codici->prepare('UPDATE codici_premio SET id_codice_partecipazione = :id_cp WHERE id = :id_premio');
                                $n = min(count($premio_ids), count($cp_ids));
                                for ($i = 0; $i < $n; $i++) {
                                    $update_stmt->execute([':id_cp' => (int) $cp_ids[$i], ':id_premio' => (int) $premio_ids[$i]]);
                                }
                            }
                        } catch (Exception $e) {
                            error_log('Concorso save: codici_premio modality update failed: ' . $e->getMessage());
                        }
                    }
                }
                // Sincronizza tabella premi con la config (solo Instant Win)
                if ($contestMode === 'instantwin' && isset($newConfig['premi']) && is_array($newConfig['premi'])) {
                    $pdo_premi = isset($connection) && $connection instanceof PDO ? $connection : (function_exists('conn_pdo') ? conn_pdo() : null);
                    if ($pdo_premi instanceof PDO) {
                        try {
                            if (!empty($newConfig['instantwin_per_cedi']) && is_array($newConfig['premi_matrix'] ?? null)) {
                                require_once $baseDir . '/app/logic/instantwin_cedi/cedi_core.php';
                                require_once $baseDir . '/app/logic/instantwin.php';
                                $syncStart = $newConfig['start_date'] ?? ($content_current['start_date'] ?? null);
                                $syncEnd = $newConfig['end_date'] ?? ($content_current['end_date'] ?? null);
                                $customWeekPlan = !empty($newConfig['premi_matrix_weeks_custom']) ? $customWeekPlanForSave : null;
                                if (!empty($newConfig['premi_matrix_weeks_custom']) && $customWeekPlan === null) {
                                    $syncErrors = [];
                                } else {
                                    $matrixForSync = is_array($newConfig['premi_matrix']) ? $newConfig['premi_matrix'] : [];
                                    $cediPremiTruncated = false;
                                    $cediPremiSynced = false;
                                    $syncErrors = instantwin_cedi_sync_premi_matrix(
                                        $pdo_premi,
                                        $matrixForSync,
                                        $syncStart,
                                        $syncEnd,
                                        $customWeekPlan,
                                        $cediSchemaConfig,
                                        $cediPremiTruncated,
                                        $cediPremiSynced
                                    );
                                }
                                foreach ($syncErrors as $syncMsg) {
                                    $errors[] = $syncMsg;
                                }
                                if (empty($syncErrors) && !empty($cediPremiSynced)) {
                                    if (function_exists('instantwin_cedi_weekly_budget_enabled')
                                        && instantwin_cedi_weekly_budget_enabled($pdo_premi)) {
                                        $quotaFrom = date('Y-m-d');
                                        $syncStartQ = $syncStart ?? date('Y-m-d');
                                        if ($quotaFrom < $syncStartQ) {
                                            $quotaFrom = $syncStartQ;
                                        }
                                        if (function_exists('instantwin_cedi_daily_quota_enabled')
                                            && instantwin_cedi_daily_quota_enabled($pdo_premi)
                                            && function_exists('instantwin_cedi_sync_daily_quota_targets')) {
                                            instantwin_cedi_sync_daily_quota_targets($pdo_premi, $quotaFrom);
                                        } elseif (function_exists('instantwin_sync_remaining_quota_targets')) {
                                            instantwin_sync_remaining_quota_targets($pdo_premi, $quotaFrom);
                                        }
                                    }
                                    $messages[] = 'Matrice premi Ce.Di. sincronizzata su tabella premi e budget settimanale.';
                                    if (!empty($cediPremiTruncated)) {
                                        $messages[] = 'Tabella premi azzerata (TRUNCATE): nuovi id da 1 (nessun premio assegnato in DB).';
                                    }
                                }
                            } elseif (empty($newConfig['instantwin_per_cedi'])) {
                            require_once $baseDir . '/app/logic/instantwin_cedi/cedi_core.php';
                            instantwin_cedi_purge_cedi_premi_unassigned($pdo_premi);
                            $config_premi = [];
                            foreach ($newConfig['premi'] as $p) {
                                $v = isset($p['valore']) ? (float)$p['valore'] : 0;
                                $q = isset($p['quantita_totale']) ? (int)$p['quantita_totale'] : 0;
                                $t = isset($p['tipo_premio']) ? trim((string)$p['tipo_premio']) : 'Premio';
                                $active = !isset($p['is_active']) || $p['is_active'] === true || $p['is_active'] === '1' || $p['is_active'] === 1;
                                // Valore 0 consentito (premi omaggio / senza importo monetario)
                                if ($v >= 0 && $q > 0) {
                                    $config_premi[] = ['valore' => $v, 'tipo_premio' => $t, 'quantita_totale' => $q, 'is_active' => $active];
                                }
                            }
                            if (!empty($config_premi)) {
                                $stmt_existing = $pdo_premi->query('SELECT id, quantita_assegnati FROM premi WHERE codice_cedi IS NULL OR TRIM(codice_cedi) = \'\' ORDER BY id ASC');
                                $existing = $stmt_existing ? $stmt_existing->fetchAll(PDO::FETCH_ASSOC) : [];
                                $n_config = count($config_premi);
                                $n_existing = count($existing);
                                // Aggiorna le righe esistenti (per indice)
                                $stmt_update = $pdo_premi->prepare('UPDATE premi SET valore = :valore, tipo_premio = :tipo, quantita_totale = :qt, is_active = :is_active WHERE id = :id');
                                for ($i = 0; $i < min($n_config, $n_existing); $i++) {
                                    $assigned = (int)($existing[$i]['quantita_assegnati'] ?? 0);
                                    $qt = (int)$config_premi[$i]['quantita_totale'];
                                    if ($qt < $assigned) {
                                        $qt = $assigned;
                                    }
                                    $want_active = !empty($config_premi[$i]['is_active']) ? 1 : 0;
                                    // Instant Win: non disattivare un premio se restano ancora unita' da distribuire (evita is_active=0 con stock residuo).
                                    if ($assigned < $qt) {
                                        $want_active = 1;
                                    }
                                    $stmt_update->execute([
                                        ':valore' => $config_premi[$i]['valore'],
                                        ':tipo' => $config_premi[$i]['tipo_premio'],
                                        ':qt' => $qt,
                                        ':is_active' => $want_active,
                                        ':id' => (int)$existing[$i]['id']
                                    ]);
                                    // Se la quantità è ridotta e ci sono codici_premio in eccesso, eliminare gli inutilizzati
                                    $prize_id = (int)$existing[$i]['id'];
                                    $stmt_count = $pdo_premi->prepare('SELECT COUNT(*) FROM codici_premio WHERE prize_id = ?');
                                    $stmt_count->execute([$prize_id]);
                                    $cp_count = (int)$stmt_count->fetchColumn();
                                    if ($cp_count > $qt) {
                                        $excess = $cp_count - $qt;
                                        $pdo_premi->prepare('DELETE FROM codici_premio WHERE prize_id = ? AND used = 0 ORDER BY id DESC LIMIT ' . (int)$excess)->execute([$prize_id]);
                                    }
                                }
                                // Inserisci nuove righe
                                if ($n_config > $n_existing) {
                                    $stmt_insert = $pdo_premi->prepare('INSERT INTO premi (valore, tipo_premio, quantita_totale, quantita_assegnati, is_active, data_creazione) VALUES (:valore, :tipo, :qt, 0, :is_active, NOW())');
                                    for ($i = $n_existing; $i < $n_config; $i++) {
                                        $ins_qt = (int)$config_premi[$i]['quantita_totale'];
                                        $ins_active = !empty($config_premi[$i]['is_active']) ? 1 : 0;
                                        if ($ins_qt > 0) {
                                            $ins_active = 1;
                                        }
                                        $stmt_insert->execute([
                                            ':valore' => $config_premi[$i]['valore'],
                                            ':tipo' => $config_premi[$i]['tipo_premio'],
                                            ':qt' => $ins_qt,
                                            ':is_active' => $ins_active
                                        ]);
                                    }
                                }
                                // Rimuovi righe in eccesso (solo se quantita_assegnati=0): elimina prima codici_premio, poi premi
                                if ($n_existing > $n_config) {
                                    for ($i = $n_config; $i < $n_existing; $i++) {
                                        $prize_id_del = (int)($existing[$i]['id'] ?? 0);
                                        instantwin_cedi_delete_premio_if_unassigned($pdo_premi, $prize_id_del);
                                    }
                                }
                            } else {
                                // Premi config vuota: eliminare tutti i premi standard non assegnati
                                $stmt_existing = $pdo_premi->query('SELECT id, quantita_assegnati FROM premi WHERE codice_cedi IS NULL OR TRIM(codice_cedi) = \'\' ORDER BY id ASC');
                                $existing = $stmt_existing ? $stmt_existing->fetchAll(PDO::FETCH_ASSOC) : [];
                                foreach ($existing as $row) {
                                    instantwin_cedi_delete_premio_if_unassigned($pdo_premi, (int)($row['id'] ?? 0));
                                }
                            }
                            // Resequenza premi: id 1, 2, 3... per leggibilità
                            $stmt_all = $pdo_premi->query('SELECT id FROM premi ORDER BY id ASC');
                            $all_ids = $stmt_all ? $stmt_all->fetchAll(PDO::FETCH_COLUMN) : [];
                            $need_reseq = false;
                            foreach ($all_ids as $idx => $old_id) {
                                if ((int)$old_id !== $idx + 1) {
                                    $need_reseq = true;
                                    break;
                                }
                            }
                            if ($need_reseq && count($all_ids) > 0) {
                                $offset = 10000;
                                $pdo_premi->exec(CONCORSO_SQL_FK_CHECKS_OFF);
                                try {
                                    foreach ($all_ids as $old_id) {
                                        $oid = (int)$old_id;
                                        $pdo_premi->prepare('UPDATE codici_premio SET prize_id = ? WHERE prize_id = ?')->execute([$oid + $offset, $oid]);
                                        $stmt_part = $pdo_premi->prepare('UPDATE partecipazioni SET prize_id = ? WHERE prize_id = ?');
                                        if ($stmt_part) {
                                            $stmt_part->execute([$oid + $offset, $oid]);
                                        }
                                    }
                                    $pdo_premi->exec('UPDATE premi SET id = id + ' . (int)$offset);
                                    foreach ($all_ids as $idx => $old_id) {
                                        $oid = (int)$old_id;
                                        $new_id = $idx + 1;
                                        $pdo_premi->prepare('UPDATE premi SET id = ? WHERE id = ?')->execute([$new_id, $oid + $offset]);
                                        $pdo_premi->prepare('UPDATE codici_premio SET prize_id = ? WHERE prize_id = ?')->execute([$new_id, $oid + $offset]);
                                        $stmt_part = $pdo_premi->prepare('UPDATE partecipazioni SET prize_id = ? WHERE prize_id = ?');
                                        if ($stmt_part) {
                                            $stmt_part->execute([$new_id, $oid + $offset]);
                                        }
                                    }
                                    $pdo_premi->exec('ALTER TABLE premi AUTO_INCREMENT = ' . (count($all_ids) + 1));
                                } finally {
                                    $pdo_premi->exec(CONCORSO_SQL_FK_CHECKS_ON);
                                }
                            }
                            }
                        } catch (Exception $e) {
                            error_log('Concorso save: premi sync failed: ' . $e->getMessage());
                            $errors[] = 'Errore sincronizzazione premi: ' . $e->getMessage();
                        }
                    }
                }
                // Instant Win per PDV: ripartisce le quantità dei premi (tabella `premi`) sui magazzini in `pdv_premio_budget` + quote giornaliere PDV.
                // Eseguito dopo il salvataggio JSON e la sync `premi`, anche se la connessione usata sopra non era disponibile (usa conn_pdo in fallback).
                if (empty($errors) && $contestMode === 'instantwin' && !empty($newConfig['instantwin_per_pdv'])) {
                    $pdo_pdv = isset($connection) && $connection instanceof PDO ? $connection : (function_exists('conn_pdo') ? conn_pdo() : null);
                    if ($pdo_pdv instanceof PDO) {
                        try {
                            require_once $baseDir . '/app/logic/instantwin_pdv/instantwin_pdv.php';
                            $iwPdvRebuilt = instantwin_pdv_rebuild_after_config_save($pdo_pdv, $newConfig);
                            if ($iwPdvRebuilt) {
                                $messages[] = 'Instant Win per PDV: ripartizione iniziale applicata (quota_giornaliera e pdv_premio_budget).';
                            } else {
                                $messages[] = 'Instant Win per PDV: ripartizione non modificata (quota_giornaliera / pdv_premio_budget già popolati).';
                            }
                        } catch (Throwable $e) {
                            error_log('Concorso save: instantwin PDV rebuild failed: ' . $e->getMessage());
                            $errors[] = 'Ripartizione Instant Win per PDV: ' . $e->getMessage();
                        }
                    }
                }
                // Disattivazione Instant Win per PDV: TRUNCATE tabelle ausiliarie (stesse del cleanup manuale).
                if (!empty($content_current['instantwin_per_pdv']) && empty($newConfig['instantwin_per_pdv'])) {
                    $pdo_pdv_off = isset($connection) && $connection instanceof PDO ? $connection : (function_exists('conn_pdo') ? conn_pdo() : null);
                    if ($pdo_pdv_off instanceof PDO) {
                        try {
                            require_once $baseDir . '/app/logic/instantwin_pdv/instantwin_pdv.php';
                            instantwin_pdv_truncate_auxiliary_tables($pdo_pdv_off);
                            $messages[] = 'Instant Win per PDV disattivato: tabella pdv_premio_budget svuotata (TRUNCATE).';
                        } catch (Throwable $e) {
                            error_log('Concorso save: instantwin PDV truncate failed: ' . $e->getMessage());
                            $errors[] = 'Svuotamento tabelle Instant Win per PDV: ' . $e->getMessage();
                        }
                    }
                }
                // Se Lunghezza codice è modificata e codici_partecipazione ha dati: svuotare la tabella
                $oldCodiceLen = (int)($content_current['instantwin_codice_length'] ?? ($content_defaults['instantwin_codice_length'] ?? 12));
                $newCodiceLen = (int)($newConfig['instantwin_codice_length'] ?? $oldCodiceLen);
                if ($oldCodiceLen !== $newCodiceLen) {
                    $pdo_cp = isset($connection) && $connection instanceof PDO ? $connection : (function_exists('conn_pdo') ? conn_pdo() : null);
                    if ($pdo_cp instanceof PDO) {
                        try {
                            $cp_count = (int)$pdo_cp->query(CONCORSO_SQL_COUNT_CODICI)->fetchColumn();
                            if ($cp_count > 0) {
                                // codici_premio ha FK id_codice_partecipazione -> codici_partecipazione: annullare i riferimenti prima di svuotare
                                $pdo_cp->exec('UPDATE codici_premio SET id_codice_partecipazione = NULL WHERE id_codice_partecipazione IS NOT NULL');
                                $pdo_cp->exec('DELETE FROM codici_partecipazione');
                                $messages[] = "Lunghezza codice modificata ({$oldCodiceLen} → {$newCodiceLen}): la tabella codici_partecipazione è stata svuotata ({$cp_count} codici rimossi). Reimporta i codici in Gestione Dati.";
                            }
                        } catch (Exception $e) {
                            error_log('Concorso save: codici_partecipazione empty failed: ' . $e->getMessage());
                            $errors[] = 'Errore durante lo svuotamento di codici_partecipazione: ' . $e->getMessage();
                        }
                    }
                }
                if (empty($errors)) {
                    $messages[] = 'Configurazione aggiornata correttamente.';
                    require_once $baseDir . '/app/logic/instantwin/manager_env.php';
                    $envSync = instantwin_manager_env_sync_app_env_from_published(!empty($newConfig['published']));
                    if (!empty($envSync['message'])) {
                        $messages[] = (string) $envSync['message'];
                    }
                    $content_current = $newConfig;
                    // Redirect per evitare il popup di reinvio modulo (PRG), conservando lo step attivo
                    $_SESSION['concorso_messages'] = $messages;
                    $_SESSION['concorso_errors'] = $errors;
                    $redirectUrl = $_SERVER['REQUEST_URI'];
                    $saveStep = isset($_POST['concorso_step']) ? trim((string)$_POST['concorso_step']) : '';
                    if (preg_match('/^(?:[1-9]|1[01])$/', $saveStep)) {
                        $parsed = parse_url($redirectUrl);
                        $path = $parsed['path'] ?? $redirectUrl;
                        $query = [];
                        if (!empty($parsed['query'])) {
                            parse_str($parsed['query'], $query);
                        }
                        $query['concorso_step'] = $saveStep;
                        $redirectUrl = $path . '?' . http_build_query($query);
                    }
                    header(CONCORSO_LOCATION_HEADER . $redirectUrl);
                    exit;
                }
            }
        }
    }
    if (!empty($errors)) {
        // Ne pas écraser custom_fields avec [] en cas d'erreur de validation (ex. nom dupliqué)
        if (isset($newConfig['custom_fields']) && empty($newConfig['custom_fields']) && !empty($content_current['custom_fields'])) {
            unset($newConfig['custom_fields']);
        }
        // En cas d'erreur validation premi: conserver les données POST pour l'affichage (éviter que la ligne invalide disparaisse)
        if ((isset($_POST['faq_present']) || isset($_POST['faq_json'])) && !empty($_POST['faq_json'])) {
            $decoded_faq_error = json_decode((string) $_POST['faq_json'], true);
            if (is_array($decoded_faq_error)) {
                $newConfig['faq'] = concorso_normalize_faq_list($decoded_faq_error);
            }
        }
        if (!empty($has_premi_validation_error) && isset($_POST['premi']) && is_array($_POST['premi'])) {
            $premi_from_post = [];
            foreach ($_POST['premi'] as $idx => $p) {
                $v = isset($p['valore']) ? trim((string)$p['valore']) : '';
                $t = isset($p['tipo_premio']) ? trim((string)$p['tipo_premio']) : '';
                $q = isset($p['quantita_totale']) ? (int)$p['quantita_totale'] : 0;
                $act = isset($p['is_active']) && ($p['is_active'] === '1' || $p['is_active'] === 1 || $p['is_active'] === true);
                $premi_from_post[] = [
                    'valore' => $v,
                    'tipo_premio' => $t !== '' ? $t : 'Premio',
                    'quantita_totale' => $q,
                    'is_active' => $act,
                ];
            }
            $newConfig['premi'] = $premi_from_post;
        }
        if (!$hasManagerUploadSizeError) {
            $content_current = array_replace_recursive($content_current, $newConfig);
        }
        $waiting_preview_name = $content_current['name'] ?? ($content_defaults['name'] ?? 'Concorso');
        $waiting_preview_date_format = $content_current['date_format'] ?? ($content_defaults['date_format'] ?? CONCORSO_DATE_FORMAT);
        $_waiting_preview_dates = contest_manager_preview_formatted_dates($content_current, $content_defaults, $waiting_preview_date_format);
        $waiting_preview_start = $_waiting_preview_dates['start'];
        $waiting_preview_end = $_waiting_preview_dates['end'];
        $waiting_preview_start_raw = $_waiting_preview_dates['start_raw'];
        $waiting_preview_end_raw = $_waiting_preview_dates['end_raw'];
        unset($_waiting_preview_dates);
        $waiting_preview_min_spend = number_format((float) ($content_current['min_spend'] ?? ($content_defaults['min_spend'] ?? 0)), 2, ',', '.');
        $waiting_preview_rules_link = $content_current['rules_link'] ?? ($content_defaults['rules_link'] ?? '#');
        $public_page_container_styles = manager_resolve_public_page_container_styles($content_current, $content_defaults);
        $_waitingBtnPreview = resolve_button_colors($content_current);
        $waiting_preview_primary = (string) ($_waitingBtnPreview['primary']['bg'] ?? CONCORSO_COLOR_PRIMARY);
        $waiting_preview_primary_hover = (string) ($_waitingBtnPreview['primary']['hover_bg'] ?? $waiting_preview_primary);
        $waiting_preview_primary_text = (string) ($_waitingBtnPreview['primary']['text_color'] ?? CONCORSO_COLOR_WHITE);
        $waiting_preview_secondary = (string) ($_waitingBtnPreview['secondary']['bg'] ?? CONCORSO_COLOR_SECONDARY);
        $waiting_preview_secondary_hover = (string) ($_waitingBtnPreview['secondary']['hover_bg'] ?? $waiting_preview_secondary);
        $waiting_preview_secondary_text = (string) ($_waitingBtnPreview['secondary']['text_color'] ?? CONCORSO_COLOR_WHITE);
        unset($_waitingBtnPreview);
        $waiting_preview_text = $content_current['colors']['text'] ?? ($content_defaults['colors']['text'] ?? CONCORSO_COLOR_TEXT);
        $thankyou_preview_prize_amount = number_format((float) ($content_current['prize_total'] ?? ($content_defaults['prize_total'] ?? 0)), 2, ',', '.');
        $thankyou_preview_prize_win_amount = $thankyou_preview_prize_amount;
        if ($preview_pdo instanceof PDO) {
            try {
                $stmtPreviewPrize = $preview_pdo->prepare('SELECT valore, tipo_premio FROM premi WHERE IS_ACTIVE = 1 ORDER BY ID ASC LIMIT 1');
                if ($stmtPreviewPrize && $stmtPreviewPrize->execute()) {
                    $previewPrizeRow = $stmtPreviewPrize->fetch(PDO::FETCH_ASSOC);
                    if (is_array($previewPrizeRow)) {
                        if (($previewPrizeRow['valore'] ?? '') !== '') {
                            $thankyou_preview_prize_amount = number_format((float) $previewPrizeRow['valore'], 2, ',', '.');
                            $thankyou_preview_prize_win_amount = $thankyou_preview_prize_amount;
                        }
                        $thankyou_preview_prize_type = trim((string) ($previewPrizeRow['tipo_premio'] ?? ''));
                        if ($thankyou_preview_prize_type === '') {
                            $thankyou_preview_prize_type = 'Premio';
                        }
                    }
                }
            } catch (Exception $e) {
                // ignore preview prize refresh errors after save
            }
        }
        $email_preview_prize_amount = $thankyou_preview_prize_win_amount;
        $thankyou_messages_current = $content_current['thankyou_messages'] ?? $thankyou_messages_defaults;
        $thankyou_instantwin_win_html = $resolve_thankyou_message(
            $thankyou_messages_current['instantwin_win_html'] ?? '',
            $thankyou_messages_defaults['instantwin_win_html'] ?? ''
        );
        $thankyou_instantwin_loss_html = $resolve_thankyou_message(
            $thankyou_messages_current['instantwin_loss_html'] ?? '',
            $thankyou_messages_defaults['instantwin_loss_html'] ?? ''
        );
        $thankyou_estrazione_html = $resolve_thankyou_message(
            $thankyou_messages_current['estrazione_html'] ?? '',
            $thankyou_messages_defaults['estrazione_html'] ?? ''
        );

        $waiting_page_state = $build_waiting_page_state('waiting_page');
        $waiting_page_align_value = $waiting_page_state['align_value'];
        $waiting_page_containers = $waiting_page_state['containers'];
        $waiting_preview_containers = $waiting_page_state['preview_containers'];

        $participation_page_state = $build_waiting_page_state('participation_page');
        $participation_page_align_value = $participation_page_state['align_value'];
        $participation_page_containers = $participation_page_state['containers'];
        $participation_page_preview_containers = $participation_page_state['preview_containers'];

        $waiting_page_ended_state = $build_waiting_page_state('waiting_page_ended');
        $waiting_page_ended_align_value = $waiting_page_ended_state['align_value'];
        $waiting_page_ended_containers = $waiting_page_ended_state['containers'];
        $waiting_preview_ended_containers = $waiting_page_ended_state['preview_containers'];

        $form_page_state = $build_waiting_page_state('form_page');
        $form_page_align_value = $form_page_state['align_value'];
        $form_page_containers = $form_page_state['containers'];
        $form_preview_containers = $form_page_state['preview_containers'];

        $help_page_state = $build_waiting_page_state('help_page');
        $help_page_align_value = $help_page_state['align_value'];
        $help_page_containers = $help_page_state['containers'];
        $help_page_preview_containers = $help_page_state['preview_containers'];

        $thankyou_instantwin_win_state = $build_waiting_page_state('thankyou_instantwin_win_page');
        $thankyou_instantwin_win_align_value = $thankyou_instantwin_win_state['align_value'];
        $thankyou_instantwin_win_containers = $thankyou_instantwin_win_state['containers'];
        $thankyou_instantwin_win_preview_containers = $thankyou_instantwin_win_state['preview_containers'];
        $thankyou_instantwin_win_title = $thankyou_instantwin_win_state['title'] ?? CONCORSO_THANKYOU_TITLE;

        $thankyou_instantwin_loss_state = $build_waiting_page_state('thankyou_instantwin_loss_page');
        $thankyou_instantwin_loss_align_value = $thankyou_instantwin_loss_state['align_value'];
        $thankyou_instantwin_loss_containers = $thankyou_instantwin_loss_state['containers'];
        $thankyou_instantwin_loss_preview_containers = $thankyou_instantwin_loss_state['preview_containers'];
        $thankyou_instantwin_loss_title = $thankyou_instantwin_loss_state['title'] ?? CONCORSO_THANKYOU_TITLE;

        $thankyou_instantwin_quiz_failed_state = $build_waiting_page_state('thankyou_instantwin_quiz_failed_page');
        $thankyou_instantwin_quiz_failed_align_value = $thankyou_instantwin_quiz_failed_state['align_value'];
        $thankyou_instantwin_quiz_failed_containers = $thankyou_instantwin_quiz_failed_state['containers'];
        $thankyou_instantwin_quiz_failed_preview_containers = $thankyou_instantwin_quiz_failed_state['preview_containers'];
        $thankyou_instantwin_quiz_failed_title = $thankyou_instantwin_quiz_failed_state['title'] ?? CONCORSO_THANKYOU_TITLE;

        $thankyou_estrazione_state = $build_waiting_page_state('thankyou_estrazione_page');
        $thankyou_estrazione_align_value = $thankyou_estrazione_state['align_value'];
        $thankyou_estrazione_containers = $thankyou_estrazione_state['containers'];
        $thankyou_estrazione_preview_containers = $thankyou_estrazione_state['preview_containers'];
        $thankyou_estrazione_title = $thankyou_estrazione_state['title'] ?? CONCORSO_THANKYOU_TITLE;

        $thankyou_estrazione_quiz_failed_state = $build_waiting_page_state('thankyou_estrazione_quiz_failed_page');
        $thankyou_estrazione_quiz_failed_align_value = $thankyou_estrazione_quiz_failed_state['align_value'];
        $thankyou_estrazione_quiz_failed_containers = $thankyou_estrazione_quiz_failed_state['containers'];
        $thankyou_estrazione_quiz_failed_preview_containers = $thankyou_estrazione_quiz_failed_state['preview_containers'];
        $thankyou_estrazione_quiz_failed_title = $thankyou_estrazione_quiz_failed_state['title'] ?? CONCORSO_THANKYOU_TITLE;

        $quiz_page_state = $build_waiting_page_state('quiz_page');
        $quiz_page_align_value = $quiz_page_state['align_value'];
        $quiz_page_containers = $quiz_page_state['containers'];
        $quiz_page_preview_containers = $quiz_page_state['preview_containers'];
        $quiz_qblock = is_array($content_current['pages']['quiz_page']['question_block_style'] ?? null)
            ? $content_current['pages']['quiz_page']['question_block_style']
            : (is_array($content_defaults['pages']['quiz_page']['question_block_style'] ?? null)
                ? $content_defaults['pages']['quiz_page']['question_block_style']
                : []);

        $quiz_choice_page_state = $build_waiting_page_state('quiz_choice_page');
        $quiz_choice_page_align_value = $quiz_choice_page_state['align_value'];
        $quiz_choice_page_containers = $quiz_choice_page_state['containers'];
        $quiz_choice_page_preview_containers = $quiz_choice_page_state['preview_containers'];
        $quiz_choice_labels = is_array($content_current['pages']['quiz_choice_page']['choice_labels'] ?? null)
            ? $content_current['pages']['quiz_choice_page']['choice_labels']
            : (is_array($content_defaults['pages']['quiz_choice_page']['choice_labels'] ?? null)
                ? $content_defaults['pages']['quiz_choice_page']['choice_labels']
                : ['standard' => 'Testo (domande scritte)', 'audio' => 'Audio (ascolto, poi risposte A/B/C/D)', 'submit' => 'Avvia quiz']);

        if (empty($thankyou_instantwin_win_containers) && !empty($thankyou_instantwin_win_html)) {
            $thankyou_instantwin_win_html_with_title = '<h1 class="fs-1 fw-bold text-uppercase mb-3">' . CONCORSO_THANKYOU_TITLE . '</h1>' . $thankyou_instantwin_win_html;
            $thankyou_instantwin_win_containers = [[
                'id' => 'container-1',
                'align' => $thankyou_instantwin_win_align_value,
                'html' => $thankyou_instantwin_win_html_with_title,
                'content_type' => 'text',
                'image' => ['src' => '', 'alt' => '', 'width' => ''],
                'background' => $waiting_container_default_background,
                'margin' => $waiting_container_default_margin,
                'radius' => $waiting_container_default_radius,
            ]];
            $thankyou_instantwin_win_preview_html = contest_replace_placeholders($thankyou_instantwin_win_html, [
                CONCORSO_PH_CONTEST_NAME => $waiting_preview_name,
                CONCORSO_PH_START_DATE => $waiting_preview_start,
                CONCORSO_PH_END_DATE => $waiting_preview_end,
                CONCORSO_PH_MIN_SPEND => $waiting_preview_min_spend,
                CONCORSO_PH_REGOLAMENTO => $waiting_preview_rules_link,
                '{{PRIZE_WIN_AMOUNT}}' => '€25,00',
                '{{PRIZE_TYPE}}' => $thankyou_preview_prize_type,
            ]);
            $thankyou_instantwin_win_preview_containers = [[
                'align' => $thankyou_instantwin_win_align_value,
                'html' => '<h1 class="fs-1 fw-bold text-uppercase mb-3">' . CONCORSO_THANKYOU_TITLE . '</h1>' . $thankyou_instantwin_win_preview_html,
                'content_type' => 'text',
                'image' => ['src' => '', 'alt' => '', 'width' => ''],
                'background' => $waiting_container_default_background,
                'margin' => $waiting_container_default_margin,
                'radius' => $waiting_container_default_radius,
            ]];
        }

        if (empty($thankyou_instantwin_loss_containers) && !empty($thankyou_instantwin_loss_html)) {
            $thankyou_instantwin_loss_html_with_title = '<h1 class="fs-1 fw-bold text-uppercase mb-3">' . CONCORSO_THANKYOU_TITLE . '</h1>' . $thankyou_instantwin_loss_html;
            $thankyou_instantwin_loss_containers = [[
                'id' => 'container-1',
                'align' => $thankyou_instantwin_loss_align_value,
                'html' => $thankyou_instantwin_loss_html_with_title,
                'content_type' => 'text',
                'image' => ['src' => '', 'alt' => '', 'width' => ''],
                'background' => $waiting_container_default_background,
                'margin' => $waiting_container_default_margin,
                'radius' => $waiting_container_default_radius,
            ]];
            $thankyou_instantwin_loss_preview_html = contest_replace_placeholders($thankyou_instantwin_loss_html, [
                CONCORSO_PH_CONTEST_NAME => $waiting_preview_name,
                CONCORSO_PH_START_DATE => $waiting_preview_start,
                CONCORSO_PH_END_DATE => $waiting_preview_end,
                CONCORSO_PH_MIN_SPEND => $waiting_preview_min_spend,
                CONCORSO_PH_REGOLAMENTO => $waiting_preview_rules_link,
            ]);
            $thankyou_instantwin_loss_preview_containers = [[
                'align' => $thankyou_instantwin_loss_align_value,
                'html' => '<h1 class="fs-1 fw-bold text-uppercase mb-3">' . CONCORSO_THANKYOU_TITLE . '</h1>' . $thankyou_instantwin_loss_preview_html,
                'content_type' => 'text',
                'image' => ['src' => '', 'alt' => '', 'width' => ''],
                'background' => $waiting_container_default_background,
                'margin' => $waiting_container_default_margin,
                'radius' => $waiting_container_default_radius,
            ]];
        }

        if (empty($thankyou_estrazione_containers) && !empty($thankyou_estrazione_html)) {
            $thankyou_estrazione_html_with_title = '<h1 class="fs-1 fw-bold text-uppercase mb-3">' . CONCORSO_THANKYOU_TITLE . '</h1>' . $thankyou_estrazione_html;
            $thankyou_estrazione_containers = [[
                'id' => 'container-1',
                'align' => $thankyou_estrazione_align_value,
                'html' => $thankyou_estrazione_html_with_title,
                'content_type' => 'text',
                'image' => ['src' => '', 'alt' => '', 'width' => ''],
                'background' => $waiting_container_default_background,
                'margin' => $waiting_container_default_margin,
                'radius' => $waiting_container_default_radius,
            ]];
            $thankyou_estrazione_preview_html = contest_replace_placeholders($thankyou_estrazione_html, [
                CONCORSO_PH_CONTEST_NAME => $waiting_preview_name,
                CONCORSO_PH_START_DATE => $waiting_preview_start,
                CONCORSO_PH_END_DATE => $waiting_preview_end,
                CONCORSO_PH_MIN_SPEND => $waiting_preview_min_spend,
                CONCORSO_PH_REGOLAMENTO => $waiting_preview_rules_link,
            ]);
            $thankyou_estrazione_preview_containers = [[
                'align' => $thankyou_estrazione_align_value,
                'html' => '<h1 class="fs-1 fw-bold text-uppercase mb-3">' . CONCORSO_THANKYOU_TITLE . '</h1>' . $thankyou_estrazione_preview_html,
                'content_type' => 'text',
                'image' => ['src' => '', 'alt' => '', 'width' => ''],
                'background' => $waiting_container_default_background,
                'margin' => $waiting_container_default_margin,
                'radius' => $waiting_container_default_radius,
            ]];
        }
    }
}

// Pulizia automatica dei CSV orfani (gestione_*.csv) più vecchi di 24 ore
$upload_dir_csv = __DIR__ . CONCORSO_CSV_UPLOAD_DIR;
if (is_dir($upload_dir_csv)) {
    $maxAge = 24 * 3600; // 24 ore
    $now = time();
    $currentSessionPath = isset($_SESSION['csv_import_gestione']['path']) ? (string)$_SESSION['csv_import_gestione']['path'] : '';
    foreach (glob($upload_dir_csv . 'gestione_*.csv') ?: [] as $file) {
        if ($file === $currentSessionPath) {
            continue; // Non eliminare il file della sessione attuale
        }
        if (($now - filemtime($file)) > $maxAge) {
            @unlink($file);
        }
    }
}

// Clear Gestione Dati preview (must run before any output so header() works)
if (isset($_GET['clear_gestione_preview']) && $_GET['clear_gestione_preview'] === '1') {
    if (isset($_SESSION['csv_import_gestione']['path']) && file_exists((string)$_SESSION['csv_import_gestione']['path'])) {
        @unlink((string)$_SESSION['csv_import_gestione']['path']);
    }
    unset($_SESSION['csv_import_gestione']);
    $_SESSION['csv_import_gestione'] = null;
    $cleanParams = $_GET;
    unset($cleanParams['clear_gestione_preview']);
    $baseUrl = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $cleanUrl = !empty($cleanParams) ? ($baseUrl . '?' . http_build_query($cleanParams)) : $baseUrl;
    header(CONCORSO_LOCATION_HEADER . $cleanUrl);
    exit;
}

// ---- Output della pagina (dopo aver gestito eventuali redirect) ----
include "_header.php";
include "res/inc/header.php";

?>
<style>
<?php
$_waitingBtnFonts = is_array($content_current['buttons'] ?? null) ? $content_current['buttons'] : [];
$_waitingPrimaryFont = contest_resolve_font_family((string) ($_waitingBtnFonts['primary']['font'] ?? ''), $content_current);
$_waitingSecondaryFont = contest_resolve_font_family((string) ($_waitingBtnFonts['secondary']['font'] ?? ''), $content_current);
$_waitingPrimaryFontSize = contest_resolve_font_size((string) ($_waitingBtnFonts['primary']['font_size'] ?? ''));
$_waitingSecondaryFontSize = contest_resolve_font_size((string) ($_waitingBtnFonts['secondary']['font_size'] ?? ''));
$_waitingPrimaryFontCss = $_waitingPrimaryFont !== ''
    ? "'" . manager_safe_html($_waitingPrimaryFont) . "', sans-serif"
    : 'Arial, Helvetica, sans-serif';
$_waitingSecondaryFontCss = $_waitingSecondaryFont !== ''
    ? "'" . manager_safe_html($_waitingSecondaryFont) . "', sans-serif"
    : 'Arial, Helvetica, sans-serif';
echo ".waiting-page-section .note-editor .note-editable,.waiting-page-preview{";
echo "--waiting-primary:" . manager_safe_html($waiting_preview_primary) . ";";
echo "--waiting-primary-hover:" . manager_safe_html($waiting_preview_primary_hover) . ";";
echo "--waiting-primary-text:" . manager_safe_html($waiting_preview_primary_text) . ";";
echo "--waiting-secondary:" . manager_safe_html($waiting_preview_secondary) . ";";
echo "--waiting-secondary-hover:" . manager_safe_html($waiting_preview_secondary_hover) . ";";
echo "--waiting-secondary-text:" . manager_safe_html($waiting_preview_secondary_text) . ";";
echo "--waiting-btn-primary-font:" . $_waitingPrimaryFontCss . ";";
echo "--waiting-btn-secondary-font:" . $_waitingSecondaryFontCss . ";";
if ($_waitingPrimaryFontSize !== '') {
    echo "--waiting-btn-primary-font-size:" . manager_safe_html($_waitingPrimaryFontSize) . ";";
}
if ($_waitingSecondaryFontSize !== '') {
    echo "--waiting-btn-secondary-font-size:" . manager_safe_html($_waitingSecondaryFontSize) . ";";
}
echo "--waiting-text:" . manager_safe_html($waiting_preview_text) . ";";
echo "color:var(--waiting-text);}";
unset($_waitingBtnFonts, $_waitingPrimaryFont, $_waitingSecondaryFont, $_waitingPrimaryFontSize, $_waitingSecondaryFontSize, $_waitingPrimaryFontCss, $_waitingSecondaryFontCss);
?>
<?php include "concorso/concorso.css"; ?>
</style>

<div class="app-main__outer">
<div class="app-main__inner">

    <div class="row">
        <div class="col-12">
            <div class="card mb-4 config-shell-card">
                <div class="card-header config-header">
                    <div class="config-header-main">
                        <div class="config-title-block">
                            <span class="config-title-icon"><i class="fa fa-cog"></i></span>
                            <div>
                                <h5 class="card-title mb-0">Configurazione concorso</h5>
                                <p class="config-title-help mb-0">Gestisci impostazioni principali, anteprime e pubblicazione del concorso.</p>
                            </div>
                        </div>
                    <div class="d-flex align-items-center config-publicato">
                        <label class="mb-0 mr-2 config-publicato-label">Pubblicato</label>
                        <select name="published" id="config-published-select" form="config-form" class="form-control form-control-sm config-published-select">
                            <?php $is_pub = $content_current['published'] ?? ($content_defaults['published'] ?? false); ?>
                            <option value="1" <?php echo $is_pub ? 'selected' : ''; ?>>Sì</option>
                            <option value="0" <?php echo !$is_pub ? 'selected' : ''; ?>>No</option>
                        </select>
                        <span class="config-status-pill <?php echo $is_pub ? 'config-status-pill--online' : 'config-status-pill--draft'; ?>">
                            <i class="fa <?php echo $is_pub ? 'fa-globe' : 'fa-eye-slash'; ?> mr-1"></i><?php echo $is_pub ? 'Online' : 'Bozza'; ?>
                        </span>
                    </div>
                    </div>
                    <div class="config-header-actions">
                    <?php
                        $test_base = rtrim(((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
                        $contest_mode_preview = strtolower((string)($content_current['contest_mode'] ?? ($content_defaults['contest_mode'] ?? 'estrazione')));
                        $is_instantwin_preview = ($contest_mode_preview === 'instantwin');
                        $quiz_enabled_preview = !empty($content_current['quiz_enabled'] ?? ($content_defaults['quiz_enabled'] ?? false));
                        $quiz_cfg_preview = array_merge(
                            (array)($content_defaults['quiz'] ?? []),
                            (array)($content_current['quiz'] ?? [])
                        );
                        $show_quiz_fail_preview = $quiz_enabled_preview && !empty($quiz_cfg_preview['require_all_correct']);
                        $participation_page_enabled_preview = array_key_exists('participation_page_enabled', $content_current)
                            ? (bool) $content_current['participation_page_enabled']
                            : (bool) ($content_defaults['participation_page_enabled'] ?? false);
                        $shared_thankyou_preview = $is_instantwin_preview && !empty($content_current['instantwin_shared_thankyou_page'] ?? ($content_defaults['instantwin_shared_thankyou_page'] ?? false));
                        $qr_access_enabled_preview = !empty($content_current['qr_access']['enabled'] ?? ($content_defaults['qr_access']['enabled'] ?? false));
                    ?>
                    <div id="config-anteprima-wrap" class="config-preview-panel">
                        <div class="config-panel-heading">
                            <span><i class="fa fa-eye mr-1"></i>Anteprima le pagine</span>
                        </div>
                    <?php if (!$is_pub): ?>
                    <div class="config-anteprima-wrap config-anteprima-preview" data-published="0">
                        <a href="<?php echo htmlspecialchars($test_base . '/public/registrazione?preview=1'); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-anteprima">Registrazione</a>
                        <a href="<?php echo htmlspecialchars($test_base . '/public/partecipazione?preview=1'); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-anteprima anteprima-participation-page <?php echo $participation_page_enabled_preview ? '' : 'd-none'; ?>" data-saved-enabled="<?php echo $participation_page_enabled_preview ? '1' : '0'; ?>">Partecipazione</a>
                        <a href="<?php echo htmlspecialchars($test_base . '/public/attesa?preview=1'); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-anteprima">Attesa</a>
                        <a href="<?php echo htmlspecialchars($test_base . '/public/concorso-terminato?preview=1'); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-anteprima">Fine</a>
                        <span class="anteprima-thankyou-btns" data-mode="instantwin" style="<?php echo $is_instantwin_preview ? '' : CONCORSO_CSS_DISPLAY_NONE; ?>">
                        <a href="<?php echo htmlspecialchars($test_base . '/manager/anteprima-ringraziamento?type=instantwin_win'); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-anteprima anteprima-iw-shared-or-win"><?php echo $shared_thankyou_preview ? 'Thank you' : 'Thank you Vincita'; ?></a>
                        <a href="<?php echo htmlspecialchars($test_base . '/manager/anteprima-ringraziamento?type=instantwin_loss'); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-anteprima anteprima-iw-loss-only <?php echo $shared_thankyou_preview ? 'd-none' : ''; ?>">Thank you Non vincita</a>
                        <span class="anteprima-quiz-fail-preview-only" style="<?php echo $show_quiz_fail_preview ? '' : CONCORSO_CSS_DISPLAY_NONE; ?>">
                        <a href="<?php echo htmlspecialchars($test_base . '/manager/anteprima-ringraziamento?type=instantwin_quiz_failed'); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">Thank you Quiz non superato</a>
                        </span>
                        </span>
                        <span class="anteprima-thankyou-btns" data-mode="estrazione" style="<?php echo $is_instantwin_preview ? CONCORSO_CSS_DISPLAY_NONE : ''; ?>">
                        <a href="<?php echo htmlspecialchars($test_base . '/manager/anteprima-ringraziamento?type=estrazione'); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-anteprima">Thank you</a>
                        <span class="anteprima-quiz-fail-preview-only" style="<?php echo ($show_quiz_fail_preview && !$is_instantwin_preview) ? '' : CONCORSO_CSS_DISPLAY_NONE; ?>">
                        <a href="<?php echo htmlspecialchars($test_base . '/manager/anteprima-ringraziamento?type=estrazione_quiz_failed'); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">Thank you Quiz non superato</a>
                        </span>
                        </span>
                        <a href="<?php echo htmlspecialchars($test_base . '/public/assistenza?preview=1'); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-anteprima">Assistenza / FAQ</a>
                        <span class="anteprima-qr-verify-only <?php echo $qr_access_enabled_preview ? '' : 'd-none'; ?>" data-saved-enabled="<?php echo $qr_access_enabled_preview ? '1' : '0'; ?>">
                        <a href="<?php echo htmlspecialchars($test_base . '/public/verifica?preview=1&preview_error=qr_missing', ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary anteprima-qr-error">Verify: Codice assente</a>
                        <a href="<?php echo htmlspecialchars($test_base . '/public/verifica?preview=1&preview_error=qr_invalid', ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary anteprima-qr-error">Verify: Non valido</a>
                        <a href="<?php echo htmlspecialchars($test_base . '/public/verifica?preview=1&preview_error=qr_rate', ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary anteprima-qr-error">Verify: Rate limit</a>
                        </span>
                        <span class="anteprima-quiz-preview-only" style="<?php echo $quiz_enabled_preview ? '' : CONCORSO_CSS_DISPLAY_NONE; ?>">
                        <a href="<?php echo htmlspecialchars($test_base . '/manager/anteprima-quiz'); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-anteprima">Quiz</a>
                        </span>
                    </div>
                    <?php endif; ?>
                    </div>
                    <div class="config-reset-save-btns">
                        <div class="config-panel-heading">
                            <span><i class="fa fa-save mr-1"></i>Azioni</span>
                        </div>
                        <?php if (file_exists($contentPath)): ?>
                        <button type="button" id="reset-config-btn" class="btn btn-outline-danger btn-sm flex-fill">
                            <i class="fa fa-trash mr-1"></i> Elimina configurazione
                        </button>
                        <?php endif; ?>
                        <button type="submit" form="config-form" class="btn btn-primary btn-sm flex-fill config-save-btn">
                            <i class="fa fa-save mr-1"></i> Salva configurazione
                        </button>
                    </div>
                    </div>
                </div>
                <div class="card-body" id="config-concorso-card-body">
                    <div id="config-form-alerts">
                    <?php foreach ($messages as $msg): ?>
                        <?php
                            $msgStr = (string) $msg;
                            $isEnvWarn = str_starts_with($msgStr, 'Attenzione:');
                            $alertClass = $isEnvWarn ? 'alert-warning' : 'alert-success';
                        ?>
                        <div class="alert <?php echo $alertClass; ?> mb-3"><?php echo manager_safe_html($msgStr); ?></div>
                    <?php endforeach; ?>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible mb-3">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Chiudi">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <ul class="mb-0 pl-3">
                                <?php foreach ($errors as $err): ?>
                                    <li><?php echo manager_safe_html($err); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    </div>

                    <?php
                        $config_overview_mode = strtolower((string)($content_current['contest_mode'] ?? ($content_defaults['contest_mode'] ?? 'estrazione')));
                        $config_overview_mode_label = $config_overview_mode === 'instantwin' ? 'Instant Win' : 'Estrazione';
                        $config_overview_name = trim((string)($content_current['name'] ?? ''));
                        $config_overview_start = trim((string)($content_current['start_date'] ?? ($content_defaults['start_date'] ?? '')));
                        $config_overview_end = trim((string)($content_current['end_date'] ?? ($content_defaults['end_date'] ?? '')));
                        $config_overview_duration = 'Da impostare';
                        $config_overview_duration_help = 'Inserisci data inizio e data fine nella configurazione base.';
                        if ($config_overview_start !== '' && $config_overview_end !== '') {
                            try {
                                $config_overview_start_date = new DateTime($config_overview_start);
                                $config_overview_end_date = new DateTime($config_overview_end);
                                $config_overview_days = ((int) $config_overview_start_date->diff($config_overview_end_date)->format('%r%a')) + 1;
                                if ($config_overview_days > 0) {
                                    $config_overview_duration = $config_overview_days === 1 ? '1 giorno' : $config_overview_days . ' giorni';
                                    $config_overview_duration_help = 'Durata calcolata da data inizio a data fine incluse.';
                                }
                            } catch (Exception $e) {
                                $config_overview_duration = 'Date da verificare';
                                $config_overview_duration_help = 'Controlla data inizio e data fine nella configurazione base.';
                            }
                        }
                    ?>
                    <div class="config-overview mb-3">
                        <div class="config-overview-card">
                            <span class="config-overview-icon"><i class="fa fa-calendar"></i></span>
                            <div>
                                <span class="config-overview-label">Durata concorso</span>
                                <strong><?php echo manager_safe_html($config_overview_duration); ?></strong>
                                <small><?php echo manager_safe_html($config_overview_duration_help); ?></small>
                            </div>
                        </div>
                        <div class="config-overview-card">
                            <span class="config-overview-icon"><i class="fa fa-trophy"></i></span>
                            <div>
                                <span class="config-overview-label">Concorso</span>
                                <strong><?php echo manager_safe_html($config_overview_name !== '' ? $config_overview_name : 'Nome da impostare'); ?></strong>
                                <small>Nome usato nelle pagine pubbliche.</small>
                            </div>
                        </div>
                        <div class="config-overview-card">
                            <span class="config-overview-icon"><i class="fa fa-random"></i></span>
                            <div>
                                <span class="config-overview-label">Modalita</span>
                                <strong><?php echo manager_safe_html($config_overview_mode_label); ?></strong>
                                <small>Modificabile nello step Configurazione.</small>
                            </div>
                        </div>
                    </div>

                    <div class="config-wizard mb-3" id="config-wizard">
                        <?php $is_pub_header = $content_current['published'] ?? ($content_defaults['published'] ?? false); ?>
                        <?php if ($is_pub_header): ?>
                        <div class="alert alert-success py-2 mb-3 mb-md-2" role="status"><i class="fa fa-globe mr-1"></i> Sito in produzione</div>
                        <?php endif; ?>
                        <div class="config-wizard-header">
                            <div>
                                <div class="config-wizard-title"><i class="fa fa-map mr-2"></i>Percorso guidato</div>
                                <p class="config-wizard-help mb-0">Sposta tra le sezioni quando serve. Le modifiche vengono salvate con il pulsante Salva configurazione.</p>
                            </div>
                            <div class="config-wizard-count text-muted small" id="wizard-progress-text"></div>
                        </div>
                        <div class="wizard-progress" aria-hidden="true">
                            <div class="wizard-progress-bar" id="wizard-progress-bar"></div>
                        </div>
                        <?php
                        // Calcolare se l'onglet "Gestione Dati" deve essere nascosto
                        // Nascondere se: mode estrazione AND PDV disabilitato AND codice_partecipazione disabilitato
                        $fieldsEnabledForTab = $content_current['fields_enabled'] ?? [];
                        $pdvEnabledForTab = !empty($fieldsEnabledForTab['prov_pdv']) || !empty($fieldsEnabledForTab['citta_pdv']) || !empty($fieldsEnabledForTab['indirizzo_pdv']);
                        $codicePartecipazioneEnabledForTab = !empty($fieldsEnabledForTab['codice_partecipazione']);
                        $contestModeForTab = strtolower((string)($content_current['contest_mode'] ?? ($content_defaults['contest_mode'] ?? 'estrazione')));
                        $isInstantwinModeForTab = ($contestModeForTab === 'instantwin');
                        $quizEnabledForWizardTab = !empty($content_current['quiz_enabled'] ?? false);
                        $shouldHideGestioneDatiTab = (!$isInstantwinModeForTab && !$pdvEnabledForTab && !$codicePartecipazioneEnabledForTab && !$quizEnabledForWizardTab);
                        $iwPdvPersForWizardTab = $isInstantwinModeForTab;
                        ?>
                        <div class="wizard-steps" id="wizard-steps" role="tablist" aria-label="Passaggi configurazione">
                            <span class="wizard-step" role="button" tabindex="0" data-step="1">Configurazione</span>
                            <span class="wizard-step iw-pdv-personalizzazione-wizard-step" role="button" tabindex="0" data-step="11" style="<?php echo $iwPdvPersForWizardTab ? '' : CONCORSO_CSS_DISPLAY_NONE; ?>">Instant Win</span>
                            <span class="wizard-step" role="button" tabindex="0" data-step="2">Stili</span>
                            <span class="wizard-step" role="button" tabindex="0" data-step="3">Branding</span>
                            <span class="wizard-step" role="button" tabindex="0" data-step="4">Registrazione</span>
                            <span class="wizard-step quiz-wizard-step" role="button" tabindex="0" data-step="10" style="<?php echo $quizEnabledForWizardTab ? '' : CONCORSO_CSS_DISPLAY_NONE; ?>">Quiz</span>
                            <span class="wizard-step" role="button" tabindex="0" data-step="5">Thank-you</span>
                            <span class="wizard-step" role="button" tabindex="0" data-step="6">Pagina attesa</span>
                            <span class="wizard-step" role="button" tabindex="0" data-step="7">Assistenza / FAQ</span>
                            <span class="wizard-step" role="button" tabindex="0" data-step="8">Email utenti</span>
                            <span class="wizard-step gestione-dati-tab" role="button" tabindex="0" data-step="9" style="<?php echo $shouldHideGestioneDatiTab ? CONCORSO_CSS_DISPLAY_NONE : ''; ?>" data-pdv-enabled="<?php echo $pdvEnabledForTab ? 'true' : 'false'; ?>" data-codice-enabled="<?php echo $codicePartecipazioneEnabledForTab ? 'true' : 'false'; ?>" data-instantwin-mode="<?php echo $isInstantwinModeForTab ? 'true' : 'false'; ?>">Gestione Dati</span>
                        </div>
                    </div>

                    <?php
                    $stats_codici_partecipazione = 0;
                    $pdo_cp_stats = isset($connection) && $connection instanceof PDO ? $connection : (function_exists('conn_pdo') ? conn_pdo() : null);
                    if ($pdo_cp_stats instanceof PDO) {
                        try {
                            $stats_codici_partecipazione = (int)$pdo_cp_stats->query(CONCORSO_SQL_COUNT_CODICI)->fetchColumn();
                        } catch (Exception $e) {
                            $stats_codici_partecipazione = 0;
                        }
                    }
                    $initial_codice_length = (int)($content_current['instantwin_codice_length'] ?? ($content_defaults['instantwin_codice_length'] ?? 12));
                    ?>
                    <form method="post" novalidate enctype="multipart/form-data" id="config-form" data-codici-partecipazione-count="<?php echo (int)$stats_codici_partecipazione; ?>" data-initial-codice-length="<?php echo (int)$initial_codice_length; ?>" data-config-upload-max-bytes="<?php echo (int)$configAssetUploadMaxBytes; ?>" data-config-upload-max-label="<?php echo manager_safe_html($configAssetUploadMaxLabel); ?>">
                        <input type="hidden" name="save_config" value="1">
                        <?php
                        $wizard_step = (string)($_POST['concorso_step'] ?? $_GET['concorso_step'] ?? '1');
                        $wizard_step = preg_match('/^(?:[1-9]|1[01])$/', $wizard_step) ? $wizard_step : '1';
                        ?>
                        <input type="hidden" name="concorso_step" id="concorso-step-input" value="<?php echo htmlspecialchars($wizard_step, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php
                            $faq_json_initial = json_encode(
                                is_array($content_current['faq'] ?? null) ? $content_current['faq'] : [],
                                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                            );
                            if ($faq_json_initial === false) {
                                $faq_json_initial = '[]';
                            }
                        ?>
                        <input type="hidden" name="faq_present" value="1">
                        <input type="hidden" name="faq_json" id="config-faq-json" value="<?php echo manager_safe_html($faq_json_initial); ?>">
                        <?php
                            $gd_visible_columns_initial = $content_current['gestione_dati_visible_columns'] ?? [];
                            if (!is_array($gd_visible_columns_initial)) {
                                $gd_visible_columns_initial = [];
                            }
                            $gd_visible_columns_json_initial = json_encode(
                                $gd_visible_columns_initial,
                                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                            );
                            if ($gd_visible_columns_json_initial === false) {
                                $gd_visible_columns_json_initial = '{}';
                            }
                        ?>
                        <input type="hidden" name="gestione_dati_visible_columns_present" value="1">
                        <input type="hidden" name="gestione_dati_visible_columns_json" id="config-gd-columns-json" value="<?php echo manager_safe_html($gd_visible_columns_json_initial); ?>">
                        <input type="hidden" name="help_page_style_present" value="1">
                        <div class="config-columns">
                            <?php include "concorso/step-configurazione.php"; ?>
                            <?php include "concorso/step-palette-footer.php"; ?>
                            <?php include "concorso/step-branding-sfondo.php"; ?>
                            <?php include "concorso/step-form-campi.php"; ?>
                            <?php include "concorso/step-quiz.php"; ?>
                            <?php include "concorso/step-thank-you.php"; ?>
                            <?php include "concorso/step-waiting-page.php"; ?>
                            <?php include "concorso/step-faq.php"; ?>
                            <?php include "concorso/step-email-utenti.php"; ?>
                        </div>
                    </form>
                    <!-- Step 11 hors du config-form : sauvegarde .env AJAX + premi aggiuntivi PDV -->
                    <div class="config-columns">
                        <?php include "concorso/step-iw-pdv-personalizzazione.php"; ?>
                    </div>
                    <!-- Step 10b hors du config-form : CRUD domande quiz (form indépendants) -->
                    <div class="config-columns">
                        <?php include "concorso/step-quiz-domande.php"; ?>
                    </div>
                    <!-- Step 9 hors du config-form pour que "Anteprima & Mapping" soumette bien le formulaire CSV et pas la config -->
                    <div class="config-columns">
                        <?php include "concorso/step-gestione-dati.php"; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</div>

<?php include "_footer.php"; ?>

<script>
// Gestione reset configurazione - doit être après le chargement de jQuery et SweetAlert2
jQuery(document).ready(function($) {
    var $resetBtn = $('#reset-config-btn');
    if ($resetBtn.length === 0) {
        return; // Le bouton n'existe pas, ne rien faire
    }
    
    $resetBtn.on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (typeof Swal === 'undefined') {
            alert('Errore: SweetAlert2 non è caricato. Ricarica la pagina.');
            return;
        }
        
        Swal.fire({
            title: 'Attenzione!',
            html: <?php
                $reset_custom_count = count($content_current['custom_fields'] ?? []);
                $reset_items = '<li>Eliminerà il file <code>content.json</code> (verrà ripristinato quello di default)</li><li>Ripristinerà <strong>Sicurezza</strong> (blacklist email/domini/IP, journal, impostazioni e protezione codici disattivata)</li><li>Eliminerà tutte le immagini nella cartella <code>public/assets/img</code> (tranne <code>default_img</code>)</li><li>Eliminerà i font personalizzati in <code>public/assets/fonts/custom</code></li><li>Eliminerà i PDF in <code>public/assets/pdf</code> (tranne <code>default_pdf</code>)</li><li>Svuoterà <code>public/uploads/scontrini</code></li><li>Svuoterà la cartella <code>manager/uploads/csv</code> (file CSV temporanei)</li>';
                if ($reset_custom_count > 0) {
                    $reset_items .= '<li>Eliminerà i campi personalizzati (' . (int)$reset_custom_count . ')</li>';
                }
                $reset_items .= '</ul><p class="text-danger"><strong>Perderai tutte le configurazioni personalizzate!</strong></p>';
                $fields_enabled_reset = $content_current['fields_enabled'] ?? [];
                $pdv_enabled_reset = !empty($fields_enabled_reset['prov_pdv']) || !empty($fields_enabled_reset['citta_pdv']) || !empty($fields_enabled_reset['indirizzo_pdv']);
                $codice_enabled_reset = !empty($fields_enabled_reset['codice_partecipazione']);
                $contest_mode_reset = strtolower((string)($content_current['contest_mode'] ?? ($content_defaults['contest_mode'] ?? 'estrazione')));
                $is_instantwin_reset = ($contest_mode_reset === 'instantwin');
                $reset_count_pdv = 0;
                $reset_count_cp = 0;
                $reset_count_cpremio = 0;
                $reset_count_premi = 0;
                $reset_count_quiz_domande = 0;
                $pdo_reset_modal = isset($connection) && $connection instanceof PDO ? $connection : (function_exists('conn_pdo') ? conn_pdo() : null);
                if ($pdo_reset_modal instanceof PDO) {
                    try {
                        $reset_count_pdv = (int)$pdo_reset_modal->query('SELECT COUNT(*) FROM punti_vendita')->fetchColumn();
                        $reset_count_cp = (int)$pdo_reset_modal->query(CONCORSO_SQL_COUNT_CODICI)->fetchColumn();
                        $reset_count_cpremio = (int)$pdo_reset_modal->query('SELECT COUNT(*) FROM codici_premio')->fetchColumn();
                        $reset_count_premi = (int)$pdo_reset_modal->query('SELECT COUNT(*) FROM premi')->fetchColumn();
                    } catch (Exception $e) { /* ignore */ }
                    try {
                        $reset_count_quiz_domande = (int)$pdo_reset_modal->query('SELECT COUNT(*) FROM quiz_domande')->fetchColumn();
                    } catch (Exception $e) { /* tabella assente o non creata */ }
                }
                $reset_tables_html = '';
                $show_reset_tables_optional = ($pdv_enabled_reset && $reset_count_pdv > 0)
                    || ($codice_enabled_reset && $reset_count_cp > 0)
                    || ($is_instantwin_reset && ($reset_count_cpremio > 0 || $reset_count_premi > 0))
                    || ($reset_count_quiz_domande > 0);
                if ($show_reset_tables_optional) {
                    $reset_tables_html = '<div class="mt-3 pt-3 border-top"><p class="mb-2"><strong>Opzionalmente, svuota anche queste tabelle:</strong></p><div id="reset-tables-container" class="text-left">';
                    if ($pdv_enabled_reset && $reset_count_pdv > 0) {
                        $reset_tables_html .= '<label class="d-block"><input type="checkbox" name="reset_tables_ui[]" value="punti_vendita"> Punti Vendita (PDV) — ' . number_format($reset_count_pdv) . ' righe</label>';
                    }
                    if ($codice_enabled_reset && $reset_count_cp > 0) {
                        $reset_tables_html .= '<label class="d-block"><input type="checkbox" name="reset_tables_ui[]" value="codici_partecipazione"> Codici Partecipazione — ' . number_format($reset_count_cp) . ' righe</label>';
                    }
                    if ($is_instantwin_reset && $reset_count_cpremio > 0) {
                        $reset_tables_html .= '<label class="d-block"><input type="checkbox" name="reset_tables_ui[]" value="codici_premio"> Codici Premio — ' . number_format($reset_count_cpremio) . ' righe</label>';
                    }
                    if ($is_instantwin_reset && $reset_count_premi > 0) {
                        $reset_tables_html .= '<label class="d-block"><input type="checkbox" name="reset_tables_ui[]" value="premi"> Premi (tipi) — ' . number_format($reset_count_premi) . ' righe</label>';
                    }
                    if ($reset_count_quiz_domande > 0) {
                        $reset_tables_html .= '<label class="d-block"><input type="checkbox" name="reset_tables_ui[]" value="quiz_domande"> Quiz domande — ' . number_format($reset_count_quiz_domande) . ' righe</label>';
                    }
                    $reset_tables_html .= '</div></div>';
                }
                $full_html = '<div class="text-left"><p><strong>Stai per resettare la configurazione del concorso.</strong></p><p>Questa azione:</p><ul class="text-left">' . $reset_items . $reset_tables_html . '</div>';
                echo json_encode($full_html);
            ?>,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sì, resetta',
            cancelButtonText: 'Annulla',
            reverseButtons: true,
            focusCancel: true,
            preConfirm: function() {
                var checked = [];
                var container = document.getElementById('reset-tables-container');
                if (container) {
                    container.querySelectorAll('input:checked').forEach(function(cb) {
                        checked.push(cb.value);
                    });
                }
                return { reset_tables: checked };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                var form = $('<form>', {
                    'method': 'POST',
                    'action': window.location.pathname + window.location.search
                });
                form.append($('<input>', { 'type': 'hidden', 'name': 'reset_config', 'value': '1' }));
                if (result.value && result.value.reset_tables && result.value.reset_tables.length > 0) {
                    result.value.reset_tables.forEach(function(val) {
                        form.append($('<input>', { 'type': 'hidden', 'name': 'reset_tables[]', 'value': val }));
                    });
                }
                $('body').append(form);
                form.submit();
            }
        });
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js" integrity="sha384-BSxuMLxX+FCbTdYec3TbXlnMGEEM2QXTFdtDaveen71o+jswm2J36+xFqp8k4VHM" crossorigin="anonymous"></script>
<script>
<?php include "concorso/concorso-config.php"; ?>
</script>
<script>
<?php include "concorso/concorso.js"; ?>
</script>
<script>
<?php include "concorso/concorso-inline.php"; ?>
</script>
<?php
// Scripts IW : après jQuery (footer) — sinon le clic « Salva .env » ne fait rien
if (empty($iwEnvSaveUrl) && !empty($iwEnvAjaxBase)) {
    $iwEnvSaveUrl = $iwEnvAjaxBase . 'instantwin_env_save.php';
}
include __DIR__ . '/inc/instantwin_env_scripts.php';
if (!empty($pdvIwPanelReady) && !empty($extraPrizesPlanIw)) {
    include __DIR__ . '/inc/pdv_iw_repartition_scripts.php';
}
?>
<script>
jQuery(function ($) {
    function syncSmsCredentialsPanel() {
        var on = $('#sms_enabled').is(':checked');
        $('#sms_mailup_credentials_panel').toggleClass('d-none', !on);
    }
    $('#sms_enabled').on('change', syncSmsCredentialsPanel);
    syncSmsCredentialsPanel();

    $('#btn_smtp_env_save').on('click', function () {
        var $btn = $(this);
        var $out = $('#smtp_env_save_result');
        if ($btn.prop('disabled')) {
            return;
        }
        $btn.prop('disabled', true);
        $out.removeClass('text-success text-danger').text('Salvataggio…');
        var data = {};
        $('#formSmtpEnv').find('input[name], select[name]').each(function () {
            data[this.name] = $(this).val() || '';
        });
        $.ajax({
            url: 'res/inc/smtp_env_save.php',
            method: 'POST',
            dataType: 'json',
            data: data
        }).done(function (res) {
            if (res && res.success) {
                $out.addClass('text-success').text(res.message || 'Salvato.');
                if (res.profiles && typeof res.profiles === 'object') {
                    smtpProfilesState.profiles = res.profiles;
                }
                if (res.profile) {
                    smtpProfilesState.active_profile = res.profile;
                    $('#smtp_env_SMTP_PROFILE').val(res.profile);
                }
                var pwdSet = !!(res.password_set);
                var $pwd = $('#smtp_env_SMTP_PASSWORD');
                var $pwdLabel = $('label[for="smtp_env_SMTP_PASSWORD"]');
                $pwd.val('');
                if (pwdSet) {
                    if (!$pwdLabel.find('.badge').length) {
                        $pwdLabel.append(' <span class="badge badge-success ml-1">impostato</span>');
                    }
                    $pwd.attr('placeholder', '(già impostato — lascia vuoto per non modificare)');
                }
                // Aggiorna cache profilo corrente dai campi (password solo flag)
                var currentProfile = ($('#smtp_env_SMTP_PROFILE').val() || 'general').toString();
                if (!smtpProfilesState.profiles[currentProfile]) {
                    smtpProfilesState.profiles[currentProfile] = { values: {}, password_set: false, has_saved: true };
                }
                smtpProfilesState.profiles[currentProfile].password_set = pwdSet
                    || !!(smtpProfilesState.profiles[currentProfile].password_set);
                smtpProfilesState.profiles[currentProfile].has_saved = true;
                ['SMTP_HOST', 'SMTP_PORT', 'SMTP_USERNAME', 'SMTP_FROM_EMAIL', 'SMTP_FROM_NAME'].forEach(function (key) {
                    var $el = $('#smtp_env_' + key);
                    if ($el.length) {
                        smtpProfilesState.profiles[currentProfile].values[key] = ($el.val() || '').toString();
                    }
                });
                smtpProfilesState.profiles[currentProfile].values.SMTP_PASSWORD = '';
            } else {
                $out.addClass('text-danger').text((res && res.message) ? res.message : 'Salvataggio fallito.');
            }
        }).fail(function (xhr) {
            var msg = 'Errore di rete.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            $out.addClass('text-danger').text(msg);
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    var smtpProfilesState = { active_profile: 'general', labels: {}, profiles: {} };
    (function initSmtpProfilesUi() {
        var rawEl = document.getElementById('smtp-profiles-data');
        if (rawEl) {
            try {
                var parsed = JSON.parse(rawEl.textContent || '{}');
                if (parsed && typeof parsed === 'object') {
                    smtpProfilesState.active_profile = parsed.active_profile || 'general';
                    smtpProfilesState.labels = parsed.labels || {};
                    smtpProfilesState.profiles = parsed.profiles || {};
                }
            } catch (e) { /* ignore */ }
        }

        function applySmtpProfileToForm(profileId) {
            var pack = smtpProfilesState.profiles[profileId];
            if (!pack || !pack.values) {
                return;
            }
            var values = pack.values;
            ['SMTP_HOST', 'SMTP_PORT', 'SMTP_USERNAME', 'SMTP_FROM_EMAIL', 'SMTP_FROM_NAME'].forEach(function (key) {
                var $el = $('#smtp_env_' + key);
                if ($el.length) {
                    $el.val(values[key] != null ? values[key] : '');
                }
            });
            var $pwd = $('#smtp_env_SMTP_PASSWORD');
            var $pwdLabel = $('label[for="smtp_env_SMTP_PASSWORD"]');
            if ($pwd.length) {
                $pwd.val('');
                $pwdLabel.find('.badge').remove();
                if (pack.password_set) {
                    $pwdLabel.append(' <span class="badge badge-success ml-1">impostato</span>');
                    $pwd.attr('placeholder', '(già impostato — lascia vuoto per non modificare)');
                } else {
                    $pwd.attr('placeholder', '');
                }
            }
            $('#smtp_env_save_result').removeClass('text-success text-danger').text('');
        }

        $('#smtp_env_SMTP_PROFILE').on('change', function () {
            var $select = $(this);
            var previous = ($select.data('prev-profile') || smtpProfilesState.active_profile || 'general').toString();
            var profileId = ($select.val() || 'general').toString();
            // Conserva in memoria le modifiche non ancora salvate del profilo lasciato
            if (!smtpProfilesState.profiles[previous]) {
                smtpProfilesState.profiles[previous] = { values: {}, password_set: false, has_saved: false };
            }
            ['SMTP_HOST', 'SMTP_PORT', 'SMTP_USERNAME', 'SMTP_FROM_EMAIL', 'SMTP_FROM_NAME'].forEach(function (key) {
                var $el = $('#smtp_env_' + key);
                if ($el.length) {
                    smtpProfilesState.profiles[previous].values[key] = ($el.val() || '').toString();
                }
            });
            var $pwd = $('#smtp_env_SMTP_PASSWORD');
            if ($pwd.length && ($pwd.val() || '').toString().trim() !== '') {
                // Password digitata ma non ancora salvata: non la teniamo in chiaro nel cache JS
                smtpProfilesState.profiles[previous].password_set = true;
            }
            applySmtpProfileToForm(profileId);
            $select.data('prev-profile', profileId);
        }).data('prev-profile', ($('#smtp_env_SMTP_PROFILE').val() || 'general').toString());
    })();

    $('#btn_sms_env_save').on('click', function () {
        var $btn = $(this);
        var $out = $('#sms_env_save_result');
        if ($btn.prop('disabled')) {
            return;
        }
        $btn.prop('disabled', true);
        $out.removeClass('text-success text-danger').text('Salvataggio…');
        var data = {};
        $('#formMailupSmsEnv').find('input[name]').each(function () {
            data[this.name] = $(this).val() || '';
        });
        data.MAILUP_SMS_TEST_RECIPIENT = $('#sms_test_recipient').val() || '';
        $.ajax({
            url: 'res/inc/mailup_sms_env_save.php',
            method: 'POST',
            dataType: 'json',
            data: data
        }).done(function (res) {
            if (res && res.success) {
                $out.addClass('text-success').text(res.message || 'Salvato.');
                // Aggiorna badge « impostato » dopo salvataggio password/secret
                $('#formMailupSmsEnv input[type="password"]').each(function () {
                    var $input = $(this);
                    var val = ($input.val() || '').trim();
                    var $label = $('label[for="' + $input.attr('id') + '"]');
                    if (val !== '') {
                        if (!$label.find('.badge').length) {
                            $label.append(' <span class="badge badge-success ml-1">impostato</span>');
                        }
                        $input.val('').attr('placeholder', '(già impostato — lascia vuoto per non modificare)');
                    }
                });
            } else {
                $out.addClass('text-danger').text((res && res.message) ? res.message : 'Salvataggio fallito.');
            }
        }).fail(function (xhr) {
            var msg = 'Errore di rete.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            $out.addClass('text-danger').text(msg);
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    function markListSecretSet() {
        var $input = $('#sms_env_MAILUP_SMS_LIST_SECRET');
        if (!$input.length) {
            return;
        }
        var $label = $('label[for="sms_env_MAILUP_SMS_LIST_SECRET"]');
        if ($label.length && !$label.find('.badge').length) {
            $label.append(' <span class="badge badge-success ml-1">impostato</span>');
        }
        $input.val('').attr('placeholder', '(già impostato — lascia vuoto per non modificare)');
    }

    $('#btn_sms_ensure_secret').on('click', function () {
        var $btn = $(this);
        var $out = $('#sms_ensure_secret_result');
        if ($btn.prop('disabled')) {
            return;
        }
        $btn.prop('disabled', true);
        $out.removeClass('text-success text-danger').text('Generazione in corso…');
        var data = {
            MAILUP_SMS_LIST_ID: $('#sms_env_MAILUP_SMS_LIST_ID').val() || '',
            MAILUP_SMS_LIST_GUID: $('#sms_env_MAILUP_SMS_LIST_GUID').val() || '',
            MAILUP_SMS_USERNAME: $('#sms_env_MAILUP_SMS_USERNAME').val() || '',
            MAILUP_SMS_PASSWORD: $('#sms_env_MAILUP_SMS_PASSWORD').val() || ''
        };
        $.ajax({
            url: 'res/inc/mailup_sms_ensure_secret.php',
            method: 'POST',
            dataType: 'json',
            data: data
        }).done(function (res) {
            if (res && res.success) {
                $out.addClass('text-success').text(res.message || 'List Secret OK.');
                markListSecretSet();
                // Password field: se compilata, badge impostato dopo generazione
                var $pwd = $('#sms_env_MAILUP_SMS_PASSWORD');
                if (($pwd.val() || '').trim() !== '') {
                    var $pl = $('label[for="sms_env_MAILUP_SMS_PASSWORD"]');
                    if ($pl.length && !$pl.find('.badge').length) {
                        $pl.append(' <span class="badge badge-success ml-1">impostato</span>');
                    }
                    $pwd.val('').attr('placeholder', '(già impostato — lascia vuoto per non modificare)');
                }
            } else {
                $out.addClass('text-danger').text((res && res.message) ? res.message : 'Generazione fallita.');
            }
        }).fail(function (xhr) {
            var msg = 'Errore di rete.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            $out.addClass('text-danger').text(msg);
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    $('#btn_sms_test_send').on('click', function () {
        var $btn = $(this);
        var $out = $('#sms_test_result');
        if ($btn.prop('disabled')) {
            return;
        }
        $btn.prop('disabled', true);
        $out.removeClass('text-success text-danger').text('Invio in corso…');
        $.ajax({
            url: 'res/inc/test_mailup_sms.php',
            method: 'POST',
            dataType: 'json',
            data: { recipient: $('#sms_test_recipient').val() || '' }
        }).done(function (res) {
            if (res && res.success) {
                $out.addClass('text-success').text(res.message || 'SMS inviato.');
            } else {
                $out.addClass('text-danger').text((res && res.message) ? res.message : 'Invio fallito.');
            }
        }).fail(function (xhr) {
            var msg = 'Errore di rete.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            $out.addClass('text-danger').text(msg);
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });
});
</script>

</div>
</div>

<!-- Modal Preview Pages -->
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="max-width: 95%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">Preview</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0" style="height: 80vh;">
                <iframe id="previewIframe" src="" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="refreshPreview()">
                    <i class="fa fa-refresh mr-1"></i>Actualiser
                </button>
            </div>
        </div>
</div>

</div>




