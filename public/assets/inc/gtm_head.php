<?php
/**
 * GTM head — solo se cookie_consent enabled, tracking attivo sulla pagina, consenso marketing, ID valorizzato.
 */
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
<!-- Google Tag Manager -->
<script>
window.dataLayer = window.dataLayer || [];
(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});
var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';
j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo $gtmIdEsc; ?>');
window.__gstGtmLoaded = true;
</script>
<!-- End Google Tag Manager -->
