<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
ob_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../../app/inc/functions.php';
require_once __DIR__ . '/../../../public/assets/mail/email_system.php';

// Template partecipazione
if (!function_exists('getPartecipazioneEmailTemplate')) {
    require_once __DIR__ . '/../../../app/logic/estrazione/mail/partecipazione_email.php';
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Sessione scaduta. Effettua nuovamente il login.'
    ]);
    exit;
}

$participationId = isset($_REQUEST['participation_id']) ? (int)$_REQUEST['participation_id'] : 0;
if ($participationId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID partecipazione non valido.'
    ]);
    exit;
}

$stmt = $connection->prepare("
    SELECT
        p.id,
        p.has_won,
        p.confermato,
        p.annullato,
        p.prize_amount,
        p.prize_id,
        p.id_codice_premio,
        p.email_inviato,
        p.email_error,
        p.id_pdv_concorso,
        p.insegna,
        u.nome,
        u.cognome,
        u.email
    FROM partecipazioni p
    LEFT JOIN utenti u ON u.id_cliente = p.id_cliente
    WHERE p.id = ?
");
$stmt->execute([$participationId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode([
        'success' => false,
        'message' => 'Partecipazione non trovata.'
    ]);
    exit;
}

$hasWon = (int)($row['has_won'] ?? 0) === 1;
$isConfirmed = (int)($row['confermato'] ?? 0) === 1;
$isCancelled = (int)($row['annullato'] ?? 0) === 1;

$prizeAmount = $row['prize_amount'];
$prizeId = isset($row['prize_id']) ? (int)$row['prize_id'] : 0;
if (($prizeAmount === null || $prizeAmount === '' || (float)$prizeAmount <= 0) && $prizeId > 0) {
    $stmtPrize = $connection->prepare('SELECT valore FROM premi WHERE id = ?');
    $stmtPrize->execute([$prizeId]);
    $fallbackPrize = $stmtPrize->fetchColumn();
    if ($fallbackPrize !== false && $fallbackPrize !== null && $fallbackPrize !== '') {
        $prizeAmount = $fallbackPrize;
    }
}
if ($prizeAmount === null || $prizeAmount === '') {
    $prizeAmount = 0;
}

// Recuperare codice_premio se disponibile
$codice_premio = null;
$id_codice_premio = !empty($row['id_codice_premio']) ? (int)$row['id_codice_premio'] : null;
if ($id_codice_premio) {
    $stmtCodice = $connection->prepare('SELECT codice FROM codici_premio WHERE id = ? LIMIT 1');
    $stmtCodice->execute([$id_codice_premio]);
    $codiceRow = $stmtCodice->fetch(PDO::FETCH_ASSOC);
    if ($codiceRow && !empty($codiceRow['codice'])) {
        $codice_premio = trim((string)$codiceRow['codice']);
    }
}

$email = trim((string)($row['email'] ?? ''));
$nome = trim((string)($row['nome'] ?? ''));
$cognome = trim((string)($row['cognome'] ?? ''));

$displayName = $nome !== '' ? $nome : $cognome;
if ($displayName === '') {
    $displayName = $email !== '' ? strtok($email, '@') : 'Partecipante';
}

$contestMode = isset($contest_mode) ? strtolower((string)$contest_mode) : '';
$fields_enabled = $contest_config['fields_enabled'] ?? [];
$require_receipt = ($fields_enabled['scontrino'] ?? true) === true;
$autoPrizeWithReceipt = (bool)($contest_config['instantwin_auto_prize_with_receipt'] ?? false);
$autoPrizeFlow = $autoPrizeWithReceipt && $require_receipt;

$emailType = 'partecipazione';
$emailLabel = 'Conferma partecipazione';

if ($contestMode === 'instantwin') {
    $emailType = resolve_instantwin_outcome_email_type($hasWon, $isConfirmed, $isCancelled, $autoPrizeFlow);
    $emailLabel = resolve_instantwin_outcome_email_label($emailType);
}

try {
    $templates = function_exists('get_email_templates_config') ? get_email_templates_config() : [];
    $emailHtml = '';
    $subject = '';

    // Utility: URL assoluti per l'iframe di anteprima (banner, logo, ecc.)
    // Calcolo dalla richiesta HTTP per funzionare in locale E in produzione
    if (!function_exists('convertRelativeToAbsoluteUrls')) {
        function convertRelativeToAbsoluteUrls($html) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $scriptPath = dirname(dirname(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''))));
            $baseUrl = rtrim($protocol . '://' . $host . $scriptPath, '/');

            $convertPath = function ($url) use ($baseUrl) {
                $url = trim($url);
                if ($url === '' || (isset($url[0]) && $url[0] === '#') || preg_match('#^[a-z][a-z0-9+\.\-]*:#i', $url)) {
                    return $url;
                }
                $clean = ltrim($url, '/');
                $absolute = $baseUrl . '/' . $clean;
                return htmlspecialchars($absolute, ENT_QUOTES, 'UTF-8');
            };

            return preg_replace_callback(
                '/\b(src|href)=(["\'])([^"\']+)\2/i',
                function ($matches) use ($convertPath) {
                    $updated = $convertPath($matches[3]);
                    return $matches[1] . '=' . $matches[2] . $updated . $matches[2];
                },
                $html
            );
        }
    }

    $pdvLabel = '';
    if (function_exists('pdv_resolve_email_label')) {
        $pdvLabel = pdv_resolve_email_label(
            isset($row['id_pdv_concorso']) ? (string) $row['id_pdv_concorso'] : null,
            isset($row['insegna']) ? (string) $row['insegna'] : null
        );
    }

    $placeholders_text = build_email_placeholders($displayName, $prizeAmount, null, false, $pdvLabel);
    $placeholders_html = build_email_placeholders($displayName, $prizeAmount, null, true, $pdvLabel);

    switch ($emailType) {
        case 'instantwin_win':
        case 'instantwin_confirm':
            include_once __DIR__ . '/../../../app/logic/instantwin/mail/instantwin_confirm_email.php';
            $template = $templates['instantwin_confirm'] ?? [];
            $subject_default = 'Vincita da confermare - ' . ($contest_config['name'] ?? 'Concorso');
            $body_template = normalize_email_html((string) ($template['body'] ?? ''));
            $body_html = replace_email_placeholders($body_template, $placeholders_html);
            $emailHtml = getInstantWinConfirmEmailTemplate($displayName, $prizeAmount, $body_html);
            $subject_template = (string) ($template['subject'] ?? '');
            $subject = trim(replace_email_placeholders($subject_template !== '' ? $subject_template : $subject_default, $placeholders_text)) ?: $subject_default;
            break;
        case 'instantwin_loss':
            include_once __DIR__ . '/../../../app/logic/instantwin/mail/instantwin_loss_email.php';
            $template = $templates['instantwin_loss'] ?? [];
            $subject_default = 'Grazie per la partecipazione - ' . ($contest_config['name'] ?? 'Concorso');
            $body_template = normalize_email_html((string) ($template['body'] ?? ''));
            $body_html = replace_email_placeholders($body_template, $placeholders_html);
            $emailHtml = getInstantWinLossEmailTemplate($displayName, $body_html);
            $subject_template = (string) ($template['subject'] ?? '');
            $subject = trim(replace_email_placeholders($subject_template !== '' ? $subject_template : $subject_default, $placeholders_text)) ?: $subject_default;
            break;
        case 'instantwin_prize':
            include_once __DIR__ . '/../../../app/logic/instantwin/mail/instantwin_prize_email.php';
            require_once __DIR__ . '/../../../app/logic/prize_image_generator.php';
            $template = $templates['instantwin_prize'] ?? [];
            $amountFormatted = is_numeric($prizeAmount) ? number_format((float) $prizeAmount, 2, ',', '.') : $prizeAmount;
            $prizeIdForBody = !empty($row['prize_id']) ? (int) $row['prize_id'] : 0;
            global $content_config;
            $voucherDisplayMode = function_exists('prize_email_resolve_voucher_display_mode')
                ? prize_email_resolve_voucher_display_mode($connection, $prizeIdForBody, is_array($content_config ?? null) ? $content_config : null)
                : get_email_voucher_code_display_mode();
            $placeholders_text = build_email_placeholders($displayName, $amountFormatted, $codice_premio, false, $pdvLabel);
            $placeholders_html = build_email_placeholders($displayName, $amountFormatted, $codice_premio, true, $pdvLabel);
            
            // Costruire il blocco voucher se disponibile
            $voucher_block_html = $codice_premio !== null && $codice_premio !== '' ? build_voucher_code_block_html((string) $codice_premio, null, $voucherDisplayMode, false) : '';
            $placeholders_html['{{VOUCHER_CODE_BLOCK}}'] = $voucher_block_html;
            $placeholders_text['{{VOUCHER_CODE_BLOCK}}'] = $codice_premio !== null ? $codice_premio : '';

            $prizeImageResolved = prize_image_resolve_for_email(
                !empty($row['id_codice_premio']) ? (int) $row['id_codice_premio'] : null,
                !empty($row['prize_id']) ? (int) $row['prize_id'] : null,
                $connection
            );
            $prizeImagePath = is_array($prizeImageResolved) ? trim((string) ($prizeImageResolved['path'] ?? '')) : '';
            $prizeTypeLabel = is_array($prizeImageResolved) ? trim((string) ($prizeImageResolved['tipo_premio'] ?? '')) : '';
            if ($prizeImagePath === '' && $prizeIdForBody > 0) {
                $tplForPrize = prize_email_template_entry_for_prize($connection, $prizeIdForBody, null);
                if ($tplForPrize['template_path'] !== '') {
                    $prizeImagePath = $tplForPrize['template_path'];
                    if (!empty($tplForPrize['overlay']['enabled'])) {
                        $rendered = prize_image_render_overlay_preview(
                            $prizeImagePath,
                            $tplForPrize['overlay'],
                            $codice_premio !== null && $codice_premio !== '' ? $codice_premio : 'ABC123'
                        );
                        if ($rendered !== null && $rendered !== '') {
                            $prizeImagePath = $rendered;
                        }
                    }
                }
            }
            $placeholders_html['{{PRIZE_IMAGE_BLOCK}}'] = build_prize_image_block_html($prizeImagePath, $prizeTypeLabel !== '' ? $prizeTypeLabel : 'Premio');
            $placeholders_html['{{PRIZE_TYPE}}'] = htmlspecialchars($prizeTypeLabel !== '' ? $prizeTypeLabel : 'Premio', ENT_QUOTES, 'UTF-8');
            
            $subject_default = 'Conferma vittoria - ' . ($contest_config['name'] ?? 'Concorso');
            $body_template = normalize_email_html((string) ($template['body'] ?? ''));
            if ($prizeIdForBody > 0 && $connection instanceof PDO) {
                global $content_config;
                $resolvedBody = prize_email_resolve_body_for_prize(
                    $connection,
                    $prizeIdForBody,
                    is_array($content_config ?? null) ? $content_config : null,
                    $body_template
                );
                if (trim($resolvedBody) !== '') {
                    $body_template = normalize_email_html($resolvedBody);
                }
            }
            $body_html = replace_email_placeholders($body_template, $placeholders_html);
            $emailHtml = getInstantWinPrizeEmailTemplate($displayName, $amountFormatted, $codice_premio, $body_html);
            $subject_template = (string) ($template['subject'] ?? '');
            $subject = trim(replace_email_placeholders($subject_template !== '' ? $subject_template : $subject_default, $placeholders_text)) ?: $subject_default;
            break;
        case 'instantwin_cancel':
            include_once __DIR__ . '/../../../app/logic/instantwin/mail/instantwin_cancelled_email.php';
            $template = $templates['instantwin_cancel'] ?? [];
            $subject_default = 'Vittoria annullata - ' . ($contest_config['name'] ?? 'Concorso');
            $body_template = normalize_email_html((string) ($template['body'] ?? ''));
            $body_html = replace_email_placeholders($body_template, $placeholders_html);
            $emailHtml = getInstantWinCancelledEmailTemplate($displayName, $body_html);
            $subject_template = (string) ($template['subject'] ?? '');
            $subject = trim(replace_email_placeholders($subject_template !== '' ? $subject_template : $subject_default, $placeholders_text)) ?: $subject_default;
            break;
        case 'partecipazione':
        default:
            include_once __DIR__ . '/../../../app/logic/estrazione/mail/partecipazione_email.php';
            $template = $templates['partecipazione'] ?? [];
            $subject_default = 'Conferma partecipazione - ' . ($contest_config['name'] ?? 'Concorso');
            $body_template = normalize_email_html((string) ($template['body'] ?? ''));
            $body_html = replace_email_placeholders($body_template, $placeholders_html);
            $emailHtml = getPartecipazioneEmailTemplate($displayName, $body_html);
            $subject_template = (string) ($template['subject'] ?? '');
            $subject = trim(replace_email_placeholders($subject_template !== '' ? $subject_template : $subject_default, $placeholders_text)) ?: $subject_default;
            break;
    }

    if ($emailHtml === '') {
        throw new Exception('Template email vuoto');
    }

    $emailHtml = convertRelativeToAbsoluteUrls($emailHtml);

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'email_type' => $emailType,
        'email_type_label' => $emailLabel,
        'subject' => $subject,
        'html' => $emailHtml
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log("Errore get_last_email_preview.php (ID: $participationId): " . $e->getMessage());
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore nel recupero dell\'anteprima: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
    ], JSON_UNESCAPED_UNICODE);
}
