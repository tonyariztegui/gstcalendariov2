/**
 * Events custom DA (Meta / TikTok) sui CTA delle pagine con Tag GTM/Meta attivi.
 * Richiede consenso marketing (gstConsent) + trackingEnabledOnPage.
 */
(function () {
  "use strict";

  var cfg = window.__gstCookieConfig || {};
  if (cfg.trackingEnabledOnPage === false) {
    return;
  }

  var META_EVENTS = {
    partecipa: "Click_Partecipa_InForma",
    invia: "Click_Invia_InForma"
  };
  var TIKTOK_EVENTS = {
    partecipa: "Click Partecipa InForma",
    invia: "Click Invia e Partecipa InForma"
  };

  function canTrack() {
    return !!(
      window.gstConsent &&
      typeof window.gstConsent.hasMarketingConsent === "function" &&
      window.gstConsent.hasMarketingConsent()
    );
  }

  function fire(kind) {
    if (!canTrack() || (kind !== "partecipa" && kind !== "invia")) {
      return;
    }
    var metaEvent = META_EVENTS[kind];
    var ttEvent = TIKTOK_EVENTS[kind];

    if (metaEvent && typeof window.fbq === "function") {
      window.fbq("trackCustom", metaEvent);
    }
    if (ttEvent && window.ttq && typeof window.ttq.track === "function") {
      window.ttq.track(ttEvent, {});
    }

    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
      event: kind === "partecipa" ? "gst_click_partecipa" : "gst_click_invia",
      gst_meta_event: metaEvent,
      gst_tiktok_event: ttEvent
    });
  }

  function textOf(el) {
    return String((el && (el.innerText || el.textContent || el.value)) || "")
      .replace(/\s+/g, " ")
      .trim()
      .toLowerCase();
  }

  function hrefOf(el) {
    return String((el && el.getAttribute && el.getAttribute("href")) || "").toLowerCase();
  }

  function isCtaCandidate(el) {
    if (!el || el.nodeType !== 1) {
      return false;
    }
    if (el.getAttribute && el.getAttribute("data-gst-track")) {
      return true;
    }
    if (el.id === "submitBtn") {
      return true;
    }
    if (el.matches) {
      if (
        el.matches(
          "a.btn-success, button.btn-success, a.contest-block-cta--primary, button.contest-block-cta--primary, a.btn-partecipa-nuovo, button.btn-partecipa-nuovo, .logo-stack a.btn.btn-lg, .logo-stack button.btn.btn-lg"
        )
      ) {
        return true;
      }
    }
    return !!(el.classList && el.classList.contains("btn-success"));
  }

  function resolveKind(el) {
    var forced = el.getAttribute ? el.getAttribute("data-gst-track") : "";
    if (forced === "partecipa" || forced === "invia") {
      return forced;
    }

    if (el.id === "submitBtn") {
      return "invia";
    }
    if (el.closest && el.closest("#registration-form") && (el.type === "submit" || el.matches("button[type='submit']"))) {
      return "invia";
    }
    if (el.closest && el.closest("#help-form") && (el.type === "submit" || el.matches("button[type='submit'], button.btn-success"))) {
      return "invia";
    }

    var href = hrefOf(el);
    var text = textOf(el);
    if (/registrazione|partecipazione/.test(href)) {
      return "partecipa";
    }
    if (/partecipa|iscriv/.test(text)) {
      return "partecipa";
    }
    if (/invia/.test(text)) {
      return "invia";
    }

    if (document.body.classList.contains("participation-page") && isCtaCandidate(el)) {
      if (!/regolamento|privacy|cookie|assistenza/.test(text + " " + href)) {
        return "partecipa";
      }
    }

    return null;
  }

  function onClick(e) {
    var el = e.target && e.target.closest ? e.target.closest("a, button, input[type='submit']") : null;
    if (!el || !isCtaCandidate(el)) {
      return;
    }
    var kind = resolveKind(el);
    if (kind) {
      fire(kind);
    }
  }

  document.addEventListener("click", onClick, true);

  window.gstTrack = {
    fire: fire,
    partecipa: function () {
      fire("partecipa");
    },
    invia: function () {
      fire("invia");
    }
  };
})();
