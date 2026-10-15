<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../../app/inc/functions.php';
require_once __DIR__ . '/../../../public/assets/mail/email_system.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'Sessione scaduta.';
    exit;
}

$request = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$type = isset($request['type']) ? strtolower(trim((string) $request['type'])) : '';
if ($type === 'instantwin_win') {
    $type = 'instantwin_confirm';
}

if ($type === '') {
    http_response_code(400);
    echo 'Tipo email mancante.';
    exit;
}

$name = isset($request['name']) ? (string) $request['name'] : 'Mario Rossi';
$prize = isset($request['prize']) ? (string) $request['prize'] : '30.00';
$voucher = isset($request['voucher']) ? (string) $request['voucher'] : 'ABC123';
$body_override = isset($request['body']) ? (string) $request['body'] : '';
// Valore corrente del banner dal form manager (può essere vuoto prima del salvataggio)
$mail_banner_override = isset($request['mail_banner']) ? trim((string) $request['mail_banner']) : null;
// Override colori blocco voucher per preview in tempo reale (prima del salvataggio)
$voucher_block_style_override = null;
if (isset($request['voucher_block_border_color']) || isset($request['voucher_block_bg_color']) || isset($request['voucher_block_text_color'])) {
    $normalizeHex = static function ($v) {
        $v = trim((string) $v);
        if ($v === '') return null;
        if (preg_match('/^[0-9A-Fa-f]{3,8}$/', $v)) {
            return '#' . $v;
        }
        return $v;
    };
    $ob = $normalizeHex($request['voucher_block_border_color'] ?? '');
    $og = $normalizeHex($request['voucher_block_bg_color'] ?? '');
    $ot = $normalizeHex($request['voucher_block_text_color'] ?? '');
    if ($ob !== null || $og !== null || $ot !== null) {
        $voucher_block_style_override = array_filter([
            'border_color' => $ob,
            'bg_color' => $og,
            'text_color' => $ot,
        ]);
    }
}

// Preview nel manager: l'iframe ha come base l'URL della pagina manager (es. /gst-con/manager/...).
// Per far caricare le immagini (banner, logo) usiamo percorsi relativi: ../public/assets/...
// così il browser risolve correttamente senza dipendere da base_url.

if (isset($contest_config) && is_array($contest_config)) {
    // Se il manager ha "svuotato" il banner, mostrare il default (default_img/banner.png)
    if ($mail_banner_override !== null) {
        $path = $mail_banner_override !== ''
            ? $mail_banner_override
            : 'assets/img/default_img/banner.png';
        $contest_config['mail_banner'] = function_exists('normalize_mail_banner_path')
            ? normalize_mail_banner_path($path)
            : $path;
    }
}

/**
 * Converte i percorsi relativi (assets/...) in percorsi relativi alla pagina manager:
 * ../public/assets/... così l'iframe (srcdoc) risolve correttamente le immagini.
 */
function convertToRelativePreviewUrls($html) {
    $convertPath = function ($url) {
        $url = trim($url);
        if ($url === '' || $url[0] === '#' || preg_match('#^[a-z][a-z0-9+\.\-]*:#i', $url)) {
            return $url;
        }
        // Già relativo tipo ../public/... → lasciare com'è
        if (preg_match('#^\.\./#', $url)) {
            return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        }
        // URL assoluto: correggi /public/uploads/ → ../../uploads/ per l'iframe manager
        if (preg_match('#^https?://#i', $url)) {
            $path = (string) (parse_url($url, PHP_URL_PATH) ?: '');
            if (preg_match('#/public/(uploads/.+)$#i', $path, $uploadMatch)) {
                return htmlspecialchars('../../' . $uploadMatch[1], ENT_QUOTES, 'UTF-8');
            }
            if (preg_match('#/(uploads/.+)$#i', $path, $uploadMatch)) {
                return htmlspecialchars('../../' . $uploadMatch[1], ENT_QUOTES, 'UTF-8');
            }

            return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        }
        // uploads/ è alla radice progetto, non in public/
        $clean = ltrim($url, '/');
        if (strpos($clean, 'uploads/') === 0) {
            return htmlspecialchars('../../' . $clean, ENT_QUOTES, 'UTF-8');
        }
        // Relativo tipo assets/... → ../public/assets/... (base = pagina manager)
        $relative = '../public/' . $clean;
        return htmlspecialchars($relative, ENT_QUOTES, 'UTF-8');
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

$templates = function_exists('get_email_templates_config') ? get_email_templates_config() : [];
$emailHtml = '';

switch ($type) {
    case 'partecipazione':
        include_once __DIR__ . '/../../../app/logic/estrazione/mail/partecipazione_email.php';
        $template = $templates['partecipazione'] ?? [];
        $placeholders_html = build_email_placeholders($name, null, null, true);
        $body_template = $body_override !== '' ? $body_override : (string) ($template['body'] ?? '');
        $body_template = normalize_email_html((string) $body_template);
        $body_html = replace_email_placeholders($body_template, $placeholders_html);
        $emailHtml = getPartecipazioneEmailTemplate($name, $body_html);
        break;
    case 'instantwin_confirm':
        include_once __DIR__ . '/../../../app/logic/instantwin/mail/instantwin_confirm_email.php';
        $template = $templates['instantwin_confirm'] ?? [];
        $placeholders_html = build_email_placeholders($name, $prize, null, true);
        $body_template = $body_override !== '' ? $body_override : (string) ($template['body'] ?? '');
        $body_template = normalize_email_html((string) $body_template);
        $body_html = replace_email_placeholders($body_template, $placeholders_html);
        $emailHtml = getInstantWinConfirmEmailTemplate($name, $prize, $body_html);
        break;
    case 'instantwin_loss':
        include_once __DIR__ . '/../../../app/logic/instantwin/mail/instantwin_loss_email.php';
        $template = $templates['instantwin_loss'] ?? [];
        $placeholders_html = build_email_placeholders($name, null, null, true);
        $body_template = $body_override !== '' ? $body_override : (string) ($template['body'] ?? '');
        $body_template = normalize_email_html((string) $body_template);
        $body_html = replace_email_placeholders($body_template, $placeholders_html);
        $emailHtml = getInstantWinLossEmailTemplate($name, $body_html);
        break;
    case 'instantwin_prize':
        include_once __DIR__ . '/../../../app/logic/instantwin/mail/instantwin_prize_email.php';
        require_once __DIR__ . '/../../../app/logic/prize_image_generator.php';
        $template = $templates['instantwin_prize'] ?? [];
        $amountFormatted = is_numeric($prize) ? number_format((float) $prize, 2, ',', '.') : (string) $prize;
        $prizeEmailKey = isset($request['prize_email_key']) ? trim((string) $request['prize_email_key']) : '';
        $entryForKey = $prizeEmailKey !== '' ? prize_email_template_entry_for_key($prizeEmailKey) : prize_email_normalize_template_entry([]);

        $voucherDisplayMode = '';
        if (isset($request['voucher_display_mode'])) {
            $voucherDisplayMode = strtolower(trim((string) $request['voucher_display_mode']));
        }
        if ($prizeEmailKey !== '' && isset($request['voucher_code_display'])) {
            $perPrizeMode = strtolower(trim((string) $request['voucher_code_display']));
            if ($perPrizeMode !== '' && $perPrizeMode !== 'inherit') {
                $voucherDisplayMode = $perPrizeMode;
            } elseif (($perPrizeMode === '' || $perPrizeMode === 'inherit') && $voucherDisplayMode === '') {
                $storedMode = (string) (($entryForKey['email'] ?? [])['voucher_code_display'] ?? 'inherit');
                if ($storedMode !== 'inherit') {
                    $voucherDisplayMode = $storedMode;
                }
            }
        } elseif ($voucherDisplayMode === '' && $prizeEmailKey !== '') {
            $storedMode = (string) (($entryForKey['email'] ?? [])['voucher_code_display'] ?? 'inherit');
            if ($storedMode !== 'inherit') {
                $voucherDisplayMode = $storedMode;
            }
        }
        if ($voucherDisplayMode === '') {
            $voucherDisplayMode = get_email_voucher_code_display_mode();
        } else {
            $voucherDisplayMode = get_email_voucher_code_display_mode($voucherDisplayMode);
        }

        $previewVoucher = $voucher;
        if (in_array($voucherDisplayMode, ['barcode', 'both'], true)) {
            $previewVoucher = '9750080022003';
        }

        $placeholders_html = build_email_placeholders($name, $amountFormatted, $previewVoucher, true, 'Negozio Esempio - Via Roma 1 - Roma - RM');
        $voucher_block_html = $previewVoucher !== '' ? build_voucher_code_block_html($previewVoucher, $voucher_block_style_override, $voucherDisplayMode, false) : '';
        $placeholders_html['{{VOUCHER_CODE_BLOCK}}'] = $voucher_block_html;
        $previewPrizeImagePath = isset($request['prize_image_template_path'])
            ? trim((string) $request['prize_image_template_path'])
            : '';
        $previewPrizeType = 'Premio esempio';
        if ($previewPrizeImagePath === '' && $prizeEmailKey !== '') {
            $previewPrizeImagePath = $entryForKey['template_path'];
        }
        if ($previewPrizeImagePath === '') {
            foreach (prize_email_templates_from_config() as $tplEntry) {
                $normalizedTpl = prize_email_normalize_template_entry($tplEntry);
                if ($normalizedTpl['template_path'] !== '') {
                    $previewPrizeImagePath = $normalizedTpl['template_path'];
                    break;
                }
            }
        }
        $previewOverlay = $prizeEmailKey !== '' ? $entryForKey['overlay'] : ['enabled' => false];
        if ($prizeEmailKey === '') {
            foreach (prize_email_templates_from_config() as $tplEntry) {
                $normalizedTpl = prize_email_normalize_template_entry($tplEntry);
                if ($normalizedTpl['template_path'] !== '' && $normalizedTpl['template_path'] === $previewPrizeImagePath) {
                    $previewOverlay = $normalizedTpl['overlay'];
                    break;
                }
            }
        }
        if (isset($request['overlay_enabled'])) {
            $previewOverlay = prize_email_normalize_overlay_config([
                'enabled' => filter_var($request['overlay_enabled'], FILTER_VALIDATE_BOOLEAN),
                'x' => $request['overlay_x'] ?? $previewOverlay['x'] ?? 200,
                'y' => $request['overlay_y'] ?? $previewOverlay['y'] ?? 350,
                'font_size' => $request['overlay_font_size'] ?? $previewOverlay['font_size'] ?? 24,
                'color' => $request['overlay_color'] ?? $previewOverlay['color'] ?? '#000000',
            ]);
        }
        $previewImageForBlock = $previewPrizeImagePath;
        if ($previewPrizeImagePath !== '' && !empty($previewOverlay['enabled'])) {
            $renderedPreview = prize_image_render_overlay_preview(
                $previewPrizeImagePath,
                $previewOverlay,
                $previewVoucher !== '' ? $previewVoucher : 'ABC123'
            );
            if ($renderedPreview !== null && $renderedPreview !== '') {
                $previewImageForBlock = $renderedPreview;
            }
        }
        $placeholders_html['{{PRIZE_IMAGE_BLOCK}}'] = build_prize_image_block_html($previewImageForBlock, $previewPrizeType);
        $placeholders_html['{{PRIZE_TYPE}}'] = htmlspecialchars($previewPrizeType, ENT_QUOTES, 'UTF-8');
        if ($body_override !== '') {
            $body_template = $body_override;
        } elseif ($prizeEmailKey !== '' && !empty($entryForKey['email']['custom_body_enabled']) && trim((string) ($entryForKey['email']['body'] ?? '')) !== '') {
            $body_template = (string) $entryForKey['email']['body'];
        } else {
            $body_template = (string) ($template['body'] ?? '');
        }
        $body_template = normalize_email_html((string) $body_template);
        $body_html = replace_email_placeholders($body_template, $placeholders_html);
        $emailHtml = getInstantWinPrizeEmailTemplate($name, $amountFormatted, $voucher, $body_html);
        break;
    case 'instantwin_cancel':
        include_once __DIR__ . '/../../../app/logic/instantwin/mail/instantwin_cancelled_email.php';
        $template = $templates['instantwin_cancel'] ?? [];
        $placeholders_html = build_email_placeholders($name, $prize, null, true);
        $body_template = $body_override !== '' ? $body_override : (string) ($template['body'] ?? '');
        $body_template = normalize_email_html((string) $body_template);
        $body_html = replace_email_placeholders($body_template, $placeholders_html);
        $emailHtml = getInstantWinCancelledEmailTemplate($name, $body_html);
        break;
    default:
        http_response_code(400);
        echo 'Tipo email non valido.';
        exit;
}

if ($emailHtml === '') {
    http_response_code(500);
    echo 'Template email vuoto.';
    exit;
}

echo convertToRelativePreviewUrls($emailHtml);
exit;
?>
