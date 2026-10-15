<?php
/**
 * Markup banner cookie + modal (incluso da render_public_footer se enabled).
 * Variabili attese: $contest_config, $cc (normalize già fatto).
 */
if (!isset($cc) || !is_array($cc)) {
    return;
}

require_once dirname(__DIR__, 3) . '/app/inc/cookie_consent.php';

$showBanner = cookie_consent_is_banner_active_on_page($cc);
$showTracking = cookie_consent_is_tracking_active_on_page($cc);
$banner = is_array($cc['banner'] ?? null) ? $cc['banner'] : [];
$style = cookie_consent_resolve_runtime_style(is_array($contest_config ?? null) ? $contest_config : []);
$tracking = is_array($cc['tracking'] ?? null) ? $cc['tracking'] : [];
$privacyHref = cookie_consent_policy_href(is_array($contest_config ?? null) ? $contest_config : []);
if ($privacyHref === '') {
    $privacyHref = '#';
}

$cssVars = [
    '--cc-bg' => (string) ($style['bg_color'] ?? '#ffffff'),
    '--cc-text' => (string) ($style['text_color'] ?? '#333333'),
    '--cc-link' => (string) ($style['link_color'] ?? '#6c757d'),
    '--cc-link-hover' => (string) ($style['link_hover'] ?? '#0a58ca'),
    '--cc-border' => (string) ($style['border_color'] ?? '#0d6efd'),
    '--cc-btn-bg' => (string) ($style['button_bg'] ?? '#0d6efd'),
    '--cc-btn-text' => (string) ($style['button_text'] ?? '#ffffff'),
    '--cc-btn2-bg' => (string) ($style['button_secondary_bg'] ?? '#ffffff'),
    '--cc-btn2-text' => (string) ($style['button_secondary_text'] ?? '#6c757d'),
    '--cc-btn2-hover' => (string) ($style['button_secondary_hover'] ?? '#0a58ca'),
];
$styleAttr = '';
foreach ($cssVars as $k => $v) {
    $styleAttr .= $k . ':' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . ';';
}

$gtmId = trim((string) ($tracking['gtm_container_id'] ?? ''));
$metaId = trim((string) ($tracking['meta_pixel_id'] ?? ''));
$tiktokId = trim((string) ($tracking['tiktok_pixel_id'] ?? ''));
$consentCookieName = cookie_consent_resolve_storage_name($cc);
?>
<link rel="stylesheet" href="assets/css/cookie-consent.css">
<style id="cc-runtime-vars">:root { <?php echo $styleAttr; ?> }</style>
<script>
window.__gstCookieConfig = {
  consentCookieName: <?php echo json_encode($consentCookieName, JSON_UNESCAPED_SLASHES); ?>,
  gtmContainerId: <?php echo json_encode($gtmId, JSON_UNESCAPED_SLASHES); ?>,
  metaPixelId: <?php echo json_encode($metaId, JSON_UNESCAPED_SLASHES); ?>,
  tiktokPixelId: <?php echo json_encode($tiktokId, JSON_UNESCAPED_SLASHES); ?>,
  trackingEnabledOnPage: <?php echo $showTracking ? 'true' : 'false'; ?>
};
</script>

<?php if ($showBanner): ?>
<div id="cc-banner" class="cc-banner cc-hidden" hidden style="<?php echo $styleAttr; ?>">
    <button type="button" id="cc-close-banner" class="cc-banner__close" aria-label="Chiudi">&times;</button>
    <div class="cc-banner__content">
        <p class="cc-banner__text">
            <?php echo htmlspecialchars((string) ($banner['text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
            <?php if ($privacyHref !== '#'): ?>
                <a class="cc-banner__policy" href="<?php echo htmlspecialchars($privacyHref, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                    <?php echo htmlspecialchars((string) ($banner['policy_link_label'] ?? 'Privacy & Cookie Policy'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endif; ?>
        </p>
        <div class="cc-banner__actions">
            <button type="button" id="cc-customize" class="cc-btn cc-btn--ghost">
                <?php echo htmlspecialchars((string) ($banner['preferences_label'] ?? 'Le mie preferenze'), ENT_QUOTES, 'UTF-8'); ?>
            </button>
            <button type="button" id="cc-accept-all" class="cc-btn cc-btn--primary">
                <?php echo htmlspecialchars((string) ($banner['accept_label'] ?? 'Accetto tutti i cookies'), ENT_QUOTES, 'UTF-8'); ?>
            </button>
        </div>
    </div>
</div>

<div id="cc-backdrop" class="cc-backdrop cc-hidden" hidden></div>
<div id="cc-modal" class="cc-modal cc-hidden" hidden role="dialog" aria-modal="true" aria-labelledby="cc-modal-title" style="<?php echo $styleAttr; ?>">
    <div class="cc-modal__panel">
        <div class="cc-modal__header">
            <h2 id="cc-modal-title"><?php echo htmlspecialchars((string) ($banner['preferences_label'] ?? 'Preferenze cookie'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <button type="button" id="cc-close-modal" class="cc-banner__close" aria-label="Chiudi">&times;</button>
        </div>
        <div class="cc-modal__body">
            <div class="cc-option">
                <div class="cc-option__head">
                    <strong><?php echo htmlspecialchars((string) ($banner['necessary_title'] ?? 'Cookie necessari'), ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span class="cc-option__badge">Sempre attivi</span>
                </div>
                <p><?php echo htmlspecialchars((string) ($banner['necessary_desc'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="cc-option">
                <div class="cc-option__head">
                    <strong><?php echo htmlspecialchars((string) ($banner['marketing_title'] ?? 'Cookie analytics e marketing'), ENT_QUOTES, 'UTF-8'); ?></strong>
                    <label class="cc-switch">
                        <input type="checkbox" id="cc-toggle-marketing">
                        <span class="cc-switch__slider"></span>
                    </label>
                </div>
                <p><?php echo htmlspecialchars((string) ($banner['marketing_desc'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
        <div class="cc-modal__footer">
            <button type="button" id="cc-save" class="cc-btn cc-btn--primary">
                <?php echo htmlspecialchars((string) ($banner['save_label'] ?? 'Salva preferenze'), ENT_QUOTES, 'UTF-8'); ?>
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="assets/js/cookie-consent.js" defer></script>
<?php if ($showTracking): ?>
<script src="assets/js/tracking-events.js" defer></script>
<?php endif; ?>
