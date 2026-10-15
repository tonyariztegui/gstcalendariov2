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
$pixelId = trim((string) ($cc['tracking']['tiktok_pixel_id'] ?? ''));
// TikTok Pixel ID: alfanumerico (es. CN0A983C77U67KV9JDC0)
if ($pixelId === '' || !preg_match('/^[A-Za-z0-9]{6,64}$/', $pixelId)) {
    return;
}
$pixelEsc = htmlspecialchars($pixelId, ENT_QUOTES, 'UTF-8');
?>
<!-- TikTok Pixel -->
<script>
!function (w, d, t) {
  w.TiktokAnalyticsObject = t;
  var ttq = w[t] = w[t] || [];
  ttq.methods = ["page", "track", "identify", "instances", "debug", "on", "off", "once", "ready", "alias", "group", "enableCookie", "disableCookie", "holdConsent", "revokeConsent", "grantConsent"];
  ttq.setAndDefer = function (t, e) {
    t[e] = function () {
      t.push([e].concat(Array.prototype.slice.call(arguments, 0)));
    };
  };
  for (var i = 0; i < ttq.methods.length; i++) {
    ttq.setAndDefer(ttq, ttq.methods[i]);
  }
  ttq.instance = function (t) {
    for (var e = ttq._i[t] || [], n = 0; n < ttq.methods.length; n++) {
      ttq.setAndDefer(e, ttq.methods[n]);
    }
    return e;
  };
  ttq.load = function (e, n) {
    var r = "https://analytics.tiktok.com/i18n/pixel/events.js";
    var o = n && n.partner;
    ttq._i = ttq._i || {};
    ttq._i[e] = [];
    ttq._i[e]._u = r;
    ttq._t = ttq._t || {};
    ttq._t[e] = +new Date;
    ttq._o = ttq._o || {};
    ttq._o[e] = n || {};
    n = document.createElement("script");
    n.type = "text/javascript";
    n.async = true;
    n.src = r + "?sdkid=" + e + "&lib=" + t;
    e = document.getElementsByTagName("script")[0];
    e.parentNode.insertBefore(n, e);
  };
  ttq.load('<?php echo $pixelEsc; ?>');
  ttq.page();
  window.__gstTikTokLoaded = true;
}(window, document, 'ttq');
</script>
<!-- End TikTok Pixel -->
