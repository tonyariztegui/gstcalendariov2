(function () {
  "use strict";

  var cfgBootstrap = window.__gstCookieConfig || {};
  var CONSENT_COOKIE_NAME = (cfgBootstrap.consentCookieName && String(cfgBootstrap.consentCookieName).trim())
    ? String(cfgBootstrap.consentCookieName).trim()
    : "gst_cookie_consent";
  var LEGACY_CONSENT_COOKIE_NAME = "gst_cookie_consent";
  var CONSENT_VERSION = 1;
  var NINETY_DAYS_SECONDS = 60 * 60 * 24 * 90;

  function getCookie(name) {
    var match = document.cookie.match(new RegExp("(^|;\\s*)" + name.replace(/[.*+?^${}()|[\]\\]/g, "\\$&") + "=([^;]*)"));
    return match ? decodeURIComponent(match[2]) : null;
  }

  function setCookie(name, value, maxAgeSeconds) {
    var secure = window.location.protocol === "https:" ? "; Secure" : "";
    document.cookie =
      name +
      "=" +
      encodeURIComponent(value) +
      "; Path=/" +
      "; Max-Age=" +
      String(maxAgeSeconds) +
      "; SameSite=Lax" +
      secure;
  }

  function deleteCookie(name) {
    var secure = window.location.protocol === "https:" ? "; Secure" : "";
    var expires = "; Expires=Thu, 01 Jan 1970 00:00:00 GMT; Max-Age=0; SameSite=Lax" + secure;
    // Host-only
    document.cookie = name + "=; Path=/" + expires;
    // Même host explicite + domaine racine (.example.it) — Meta pose _fbp / _fbc ainsi
    var host = String(window.location.hostname || "");
    if (host) {
      document.cookie = name + "=; Path=/; Domain=" + host + expires;
      var parts = host.split(".");
      if (parts.length >= 2) {
        document.cookie = name + "=; Path=/; Domain=." + parts.slice(-2).join(".") + expires;
      }
      if (parts.length >= 3) {
        document.cookie = name + "=; Path=/; Domain=." + parts.slice(-3).join(".") + expires;
      }
    }
  }

  function clearMarketingCookies() {
    var names = ["_ga", "_gid", "_gat", "_gcl_au", "_fbp", "_fbc", "_ttp", "_tt_enable_cookie", "ttcsid"];
    for (var i = 0; i < names.length; i += 1) {
      deleteCookie(names[i]);
    }
    var all = document.cookie ? document.cookie.split(";") : [];
    for (var j = 0; j < all.length; j += 1) {
      var key = (all[j].split("=")[0] || "").trim();
      if (
        key.indexOf("_ga_") === 0 ||
        key.indexOf("_fbp") === 0 ||
        key.indexOf("_fbc") === 0 ||
        key.indexOf("ttsid_") === 0 ||
        key.indexOf("ttcsid_") === 0
      ) {
        deleteCookie(key);
      }
    }
    try {
      if (window.ttq) {
        if (typeof window.ttq.revokeConsent === "function") {
          window.ttq.revokeConsent();
        }
        if (typeof window.ttq.disableCookie === "function") {
          window.ttq.disableCookie();
        }
      }
    } catch (e) {
      /* ignore */
    }
    window.__gstMetaLoaded = false;
    window.__gstTikTokLoaded = false;
    window.__gstGtmLoaded = false;
  }

  function safeJsonParse(value) {
    try {
      return JSON.parse(value);
    } catch (e) {
      return null;
    }
  }

  function readConsent() {
    var raw = getCookie(CONSENT_COOKIE_NAME);
    if (!raw && CONSENT_COOKIE_NAME !== LEGACY_CONSENT_COOKIE_NAME) {
      raw = getCookie(LEGACY_CONSENT_COOKIE_NAME);
    }
    if (!raw) return null;
    var parsed = safeJsonParse(raw);
    if (!parsed || typeof parsed !== "object" || parsed.v !== CONSENT_VERSION) return null;
    return {
      v: CONSENT_VERSION,
      necessary: true,
      marketing: !!parsed.marketing,
      ts: typeof parsed.ts === "string" ? parsed.ts : null
    };
  }

  function writeConsent(consent) {
    var payload = {
      v: CONSENT_VERSION,
      necessary: true,
      marketing: !!consent.marketing,
      ts: new Date().toISOString()
    };
    setCookie(CONSENT_COOKIE_NAME, JSON.stringify(payload), NINETY_DAYS_SECONDS);
    if (CONSENT_COOKIE_NAME !== LEGACY_CONSENT_COOKIE_NAME) {
      deleteCookie(LEGACY_CONSENT_COOKIE_NAME);
    }
    if (!payload.marketing) {
      clearMarketingCookies();
    }
    return payload;
  }

  function byId(id) {
    return document.getElementById(id);
  }

  function show(el) {
    if (!el) return;
    el.classList.remove("cc-hidden");
    el.removeAttribute("hidden");
  }

  function hide(el) {
    if (!el) return;
    el.classList.add("cc-hidden");
    el.setAttribute("hidden", "hidden");
  }

  function lockScroll() {
    document.documentElement.classList.add("cc-no-scroll");
    document.body.classList.add("cc-no-scroll");
  }

  function unlockScroll() {
    document.documentElement.classList.remove("cc-no-scroll");
    document.body.classList.remove("cc-no-scroll");
  }

  function hasMarketingConsent() {
    var consent = readConsent();
    return !!(consent && consent.marketing);
  }

  function trackingAllowedOnPage() {
    var cfg = window.__gstCookieConfig || {};
    return cfg.trackingEnabledOnPage !== false;
  }

  function getConfiguredGtmId() {
    var cfg = window.__gstCookieConfig || {};
    return cfg.gtmContainerId ? String(cfg.gtmContainerId).trim() : "";
  }

  function getConfiguredMetaId() {
    var cfg = window.__gstCookieConfig || {};
    return cfg.metaPixelId ? String(cfg.metaPixelId).trim() : "";
  }

  function getConfiguredTikTokId() {
    var cfg = window.__gstCookieConfig || {};
    return cfg.tiktokPixelId ? String(cfg.tiktokPixelId).trim() : "";
  }

  function isGtmLoaded() {
    if (window.__gstGtmLoaded === true) return true;
    var scripts = document.getElementsByTagName("script");
    for (var i = 0; i < scripts.length; i += 1) {
      var src = scripts[i] && scripts[i].src ? scripts[i].src : "";
      if (src.indexOf("googletagmanager.com/gtm.js") !== -1) {
        window.__gstGtmLoaded = true;
        return true;
      }
    }
    return false;
  }

  function loadGtmIfNeeded() {
    if (!trackingAllowedOnPage()) return;
    var gtmId = getConfiguredGtmId();
    if (!gtmId || isGtmLoaded()) return;

    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
      "gtm.start": new Date().getTime(),
      event: "gtm.js"
    });

    var firstScript = document.getElementsByTagName("script")[0];
    if (!firstScript || !firstScript.parentNode) return;

    var script = document.createElement("script");
    script.async = true;
    script.src = "https://www.googletagmanager.com/gtm.js?id=" + encodeURIComponent(gtmId);
    firstScript.parentNode.insertBefore(script, firstScript);
    window.__gstGtmLoaded = true;
  }

  function loadMetaIfNeeded() {
    if (!trackingAllowedOnPage()) return;
    var pixelId = getConfiguredMetaId();
    if (!pixelId) return;

    if (window.fbq) {
      // Réactivation marketing sans reload : renvoyer PageView pour recréer _fbp
      if (window.__gstMetaLoaded !== true) {
        window.fbq("track", "PageView");
        window.__gstMetaLoaded = true;
      }
      return;
    }

    !(function (f, b, e, v, n, t, s) {
      if (f.fbq) return;
      n = f.fbq = function () {
        n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments);
      };
      if (!f._fbq) f._fbq = n;
      n.push = n;
      n.loaded = true;
      n.version = "2.0";
      n.queue = [];
      t = b.createElement(e);
      t.async = true;
      t.src = v;
      s = b.getElementsByTagName(e)[0];
      s.parentNode.insertBefore(t, s);
    })(window, document, "script", "https://connect.facebook.net/en_US/fbevents.js");

    window.fbq("init", pixelId);
    window.fbq("track", "PageView");
    window.__gstMetaLoaded = true;
  }

  function loadTikTokIfNeeded() {
    if (!trackingAllowedOnPage()) return;
    var pixelId = getConfiguredTikTokId();
    if (!pixelId || !/^[A-Za-z0-9]{6,64}$/.test(pixelId)) return;

    if (window.ttq && typeof window.ttq.page === "function") {
      try {
        if (typeof window.ttq.grantConsent === "function") {
          window.ttq.grantConsent();
        }
        if (typeof window.ttq.enableCookie === "function") {
          window.ttq.enableCookie();
        }
      } catch (e) {
        /* ignore */
      }
      if (window.__gstTikTokLoaded !== true) {
        window.ttq.page();
        window.__gstTikTokLoaded = true;
      }
      return;
    }

    !(function (w, d, t) {
      w.TiktokAnalyticsObject = t;
      var ttq = (w[t] = w[t] || []);
      ttq.methods = [
        "page",
        "track",
        "identify",
        "instances",
        "debug",
        "on",
        "off",
        "once",
        "ready",
        "alias",
        "group",
        "enableCookie",
        "disableCookie",
        "holdConsent",
        "revokeConsent",
        "grantConsent"
      ];
      ttq.setAndDefer = function (obj, method) {
        obj[method] = function () {
          obj.push([method].concat(Array.prototype.slice.call(arguments, 0)));
        };
      };
      for (var i = 0; i < ttq.methods.length; i += 1) {
        ttq.setAndDefer(ttq, ttq.methods[i]);
      }
      ttq.instance = function (id) {
        var inst = ttq._i[id] || [];
        for (var n = 0; n < ttq.methods.length; n += 1) {
          ttq.setAndDefer(inst, ttq.methods[n]);
        }
        return inst;
      };
      ttq.load = function (id, opts) {
        var url = "https://analytics.tiktok.com/i18n/pixel/events.js";
        ttq._i = ttq._i || {};
        ttq._i[id] = [];
        ttq._i[id]._u = url;
        ttq._t = ttq._t || {};
        ttq._t[id] = +new Date();
        ttq._o = ttq._o || {};
        ttq._o[id] = opts || {};
        var script = document.createElement("script");
        script.type = "text/javascript";
        script.async = true;
        script.src = url + "?sdkid=" + id + "&lib=" + t;
        var first = document.getElementsByTagName("script")[0];
        first.parentNode.insertBefore(script, first);
      };
      ttq.load(pixelId);
      ttq.page();
      window.__gstTikTokLoaded = true;
    })(window, document, "ttq");
  }

  function loadTrackingIfNeeded() {
    if (!hasMarketingConsent()) return;
    loadGtmIfNeeded();
    loadMetaIfNeeded();
    loadTikTokIfNeeded();
  }

  function initConsentUi() {
    var banner = byId("cc-banner");
    var backdrop = byId("cc-backdrop");
    var modal = byId("cc-modal");
    var prefsLink = byId("cc-open-preferences");

    var btnAcceptAll = byId("cc-accept-all");
    var btnCustomize = byId("cc-customize");
    var btnCloseBanner = byId("cc-close-banner");
    var btnSave = byId("cc-save");
    var btnCloseModal = byId("cc-close-modal");
    var toggleMarketing = byId("cc-toggle-marketing");

    function openModal() {
      show(backdrop);
      show(modal);
      lockScroll();
      if (toggleMarketing) {
        toggleMarketing.checked = hasMarketingConsent();
      }
    }

    function closeModal() {
      hide(modal);
      hide(backdrop);
      unlockScroll();
    }

    function applyConsentAndSave(consent) {
      var wantMarketing = !!(consent && consent.marketing);
      var hadMarketing = hasMarketingConsent();
      var hadTrackingScripts = !!(
        window.__gstMetaLoaded ||
        window.__gstTikTokLoaded ||
        window.__gstGtmLoaded ||
        window.fbq ||
        window.ttq
      );
      writeConsent(consent);
      if (wantMarketing) {
        loadTrackingIfNeeded();
        hide(banner);
        closeModal();
        return;
      }
      hide(banner);
      closeModal();
      // Ricarica per scaricare Meta/TikTok/GTM dalla pagina; i cookie tecnici restano
      if (hadMarketing || hadTrackingScripts) {
        window.location.reload();
      }
    }

    if (prefsLink) {
      prefsLink.addEventListener("click", function (e) {
        e.preventDefault();
        openModal();
      });
    }

    if (btnAcceptAll) {
      btnAcceptAll.addEventListener("click", function () {
        applyConsentAndSave({ marketing: true });
      });
    }

    if (btnCustomize) {
      btnCustomize.addEventListener("click", function () {
        openModal();
      });
    }

    if (btnCloseBanner) {
      btnCloseBanner.addEventListener("click", function () {
        applyConsentAndSave({ marketing: false });
      });
    }

    if (btnSave) {
      btnSave.addEventListener("click", function () {
        applyConsentAndSave({ marketing: !!(toggleMarketing && toggleMarketing.checked) });
      });
    }

    if (btnCloseModal) {
      btnCloseModal.addEventListener("click", closeModal);
    }

    if (backdrop) {
      backdrop.addEventListener("click", closeModal);
    }

    var existing = readConsent();
    if (existing || !banner) {
      hide(banner);
      return;
    }
    show(banner);
  }

  function init() {
    window.gstConsent = {
      hasMarketingConsent: hasMarketingConsent,
      openPreferences: function () {
        var el = document.getElementById("cc-open-preferences");
        if (el) el.click();
      }
    };

    if (!hasMarketingConsent()) {
      clearMarketingCookies();
    } else {
      loadTrackingIfNeeded();
    }

    initConsentUi();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
