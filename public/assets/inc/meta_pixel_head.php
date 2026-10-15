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
$pixelId = trim((string) ($cc['tracking']['meta_pixel_id'] ?? ''));
if ($pixelId === '' || !preg_match('/^\d+$/', $pixelId)) {
    return;
}
$pixelEsc = htmlspecialchars($pixelId, ENT_QUOTES, 'UTF-8');
?>
<!-- Meta Pixel -->
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init','<?php echo $pixelEsc; ?>');
fbq('track','PageView');
window.__gstMetaLoaded = true;
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=<?php echo $pixelEsc; ?>&ev=PageView&noscript=1"/></noscript>
<!-- End Meta Pixel -->
