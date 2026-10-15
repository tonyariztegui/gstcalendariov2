<?php
if (!isset($contest_config) || !is_array($contest_config)) {
    return;
}
require_once dirname(__DIR__, 3) . '/app/inc/cookie_consent.php';
$cc = is_array($contest_config['cookie_consent'] ?? null) ? $contest_config['cookie_consent'] : [];
if (!cookie_consent_is_tracking_active_on_page($cc)) {
    return;
}
if (!cookie_consent_has_marketing_from_request()) {
    return;
}
$gtmId = trim((string) ($cc['tracking']['gtm_container_id'] ?? ''));
if ($gtmId === '' || !preg_match('/^GTM-[A-Z0-9]+$/i', $gtmId)) {
    return;
}
$gtmIdEsc = htmlspecialchars($gtmId, ENT_QUOTES, 'UTF-8');
?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo $gtmIdEsc; ?>"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
