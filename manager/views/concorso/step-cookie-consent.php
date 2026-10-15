<?php
/**
 * Pannello Cookie banner & tracking — Step Configurazione.
 * Visibile solo il master toggle se disabled; il resto appare con progressive disclosure.
 */
require_once dirname(__DIR__, 3) . '/app/inc/cookie_consent.php';

$ccDefaults = is_array($content_defaults['cookie_consent'] ?? null) ? $content_defaults['cookie_consent'] : [];
$ccCurrent = cookie_consent_normalize(
    $content_current['cookie_consent'] ?? [],
    $ccDefaults
);
$ccEnabled = !empty($ccCurrent['enabled']);
$ccBanner = is_array($ccCurrent['banner'] ?? null) ? $ccCurrent['banner'] : [];
$ccStyle = is_array($ccCurrent['style'] ?? null) ? $ccCurrent['style'] : [];
$ccTracking = is_array($ccCurrent['tracking'] ?? null) ? $ccCurrent['tracking'] : [];
$ccCookies = is_array($ccCurrent['cookies'] ?? null) ? $ccCurrent['cookies'] : [];
$ccPages = is_array($ccCurrent['pages'] ?? null) ? $ccCurrent['pages'] : [];
$pageLabels = cookie_consent_default_page_keys();
$ccColorsCustom = !empty(($content_current['style_customization'] ?? [])['cookie_banner_colors']);
$cookiePolicyCurrent = trim((string) ($content_current['cookie_policy_link'] ?? ($content_defaults['cookie_policy_link'] ?? '')));
$cookiePolicyFallback = (string) ($content_defaults['cookie_policy_link'] ?? '');

$ccColor = static function (string $key, array $style, string $fallback) {
    $v = trim((string) ($style[$key] ?? $fallback));
    return preg_match('/^#[0-9A-Fa-f]{6}$/', $v) ? $v : $fallback;
};
?>
<style>
.cc-preview-switch {
  position: relative;
  display: inline-block;
  width: 36px;
  height: 20px;
  flex-shrink: 0;
  background: #ccc;
  border-radius: 20px;
  vertical-align: middle;
}
.cc-preview-switch::before {
  content: "";
  position: absolute;
  height: 14px;
  width: 14px;
  left: 3px;
  top: 3px;
  background: #fff;
  border-radius: 50%;
}
</style>
<div class="configurazione-panel cookie-consent-panel" id="cookie-consent-panel">
    <div class="configurazione-panel-header">
        <span class="configurazione-panel-icon"><i class="fa fa-shield"></i></span>
        <div>
            <h6>Cookie banner &amp; tracking</h6>
            <p>Attiva solo se il concorso usa cookie analytics/marketing (GTM, Meta Pixel).</p>
        </div>
    </div>

    <div class="custom-control custom-switch mb-2">
        <input type="checkbox" class="custom-control-input" id="cookie_consent_enabled" name="cookie_consent_enabled" value="1"<?php echo $ccEnabled ? ' checked' : ''; ?>>
        <label class="custom-control-label font-weight-bold" for="cookie_consent_enabled">Attiva cookie banner &amp; tracking per questo concorso</label>
    </div>
    <p class="small text-muted mb-0">Se disattivo, nessun banner e nessun tag sulle pagine pubbliche.</p>

    <div id="cookie-consent-details" class="<?php echo $ccEnabled ? '' : 'd-none'; ?>">
        <hr class="my-3">

        <div class="custom-control custom-switch mb-3">
            <input type="checkbox" class="custom-control-input" id="cookie_consent_sync_marketing" name="cookie_consent_sync_marketing" value="1"<?php echo !empty($ccCurrent['sync_registration_marketing']) ? ' checked' : ''; ?>>
            <label class="custom-control-label" for="cookie_consent_sync_marketing">Sincronizza con consenso marketing in registrazione</label>
        </div>

        <div class="section-subtitle">Tracking (tag)</div>
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="cookie_consent_gtm">GTM Container ID</label>
                <input type="text" class="form-control" id="cookie_consent_gtm" name="cookie_consent_gtm" placeholder="GTM-XXXXXXX" value="<?php echo manager_safe_html((string) ($ccTracking['gtm_container_id'] ?? '')); ?>">
            </div>
            <div class="form-group col-md-4">
                <label for="cookie_consent_meta">Meta Pixel ID</label>
                <input type="text" class="form-control" id="cookie_consent_meta" name="cookie_consent_meta" placeholder="1234567890" value="<?php echo manager_safe_html((string) ($ccTracking['meta_pixel_id'] ?? '')); ?>">
            </div>
            <div class="form-group col-md-4">
                <label for="cookie_consent_tiktok">TikTok Pixel ID</label>
                <input type="text" class="form-control" id="cookie_consent_tiktok" name="cookie_consent_tiktok" placeholder="CN0A983C77U67KV9JDC0" value="<?php echo manager_safe_html((string) ($ccTracking['tiktok_pixel_id'] ?? '')); ?>">
            </div>
        </div>
        <p class="small text-muted mb-2">I tag vengono caricati solo dopo il consenso marketing e solo sulle pagine attive sotto. Se GTM include già Meta/TikTok, lascia vuoti i campi Pixel per evitare doppio caricamento.</p>

        <div class="section-subtitle">Pagine (banner / tag)</div>
        <div class="table-responsive mb-3">
            <table class="table table-sm table-bordered mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Pagina</th>
                        <th class="text-center" style="width:120px;">Banner</th>
                        <th class="text-center" style="width:120px;">Tag GTM/Meta</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pageLabels as $pageKey => $pageLabel): ?>
                        <?php
                        $pageCfg = is_array($ccPages[$pageKey] ?? null) ? $ccPages[$pageKey] : ['banner' => true, 'tracking' => true];
                        ?>
                        <tr>
                            <td><?php echo manager_safe_html($pageLabel); ?></td>
                            <td class="text-center">
                                <input type="checkbox" name="cookie_consent_page_banner[<?php echo manager_safe_html($pageKey); ?>]" value="1"<?php echo !empty($pageCfg['banner']) ? ' checked' : ''; ?>>
                            </td>
                            <td class="text-center">
                                <input type="checkbox" name="cookie_consent_page_tracking[<?php echo manager_safe_html($pageKey); ?>]" value="1"<?php echo !empty($pageCfg['tracking']) ? ' checked' : ''; ?>>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="section-subtitle">Elenco cookie tecnici</div>
        <?php
        $consentCookieNameUi = cookie_consent_resolve_storage_name($ccCurrent);
        ?>
        <p class="small text-muted mb-2">
            Cookie tecnici obbligatori del sito pubblico: <code>PHPSESSID</code> (non rinominabile) e il cookie preferenze
            (default <code>gst_cookie_consent</code> — puoi rinominarlo nella colonna Nome, poi salva).
            Non sono eliminabili. Finalità / durata / fornitore restano modificabili per la Cookie Policy.
        </p>
        <div id="cc-cookies-technical" class="cc-cookie-rows mb-2" data-kind="technical">
            <?php
            $techRows = cookie_consent_ensure_core_technical(
                is_array($ccCookies['technical'] ?? null) ? $ccCookies['technical'] : [],
                $consentCookieNameUi
            );
            foreach ($techRows as $i => $row):
                $coreKind = cookie_consent_core_technical_kind(is_array($row) ? $row : [], $consentCookieNameUi);
                $nameReadonly = $coreKind === 'session';
            ?>
            <div class="form-row align-items-end cc-cookie-row mb-2"<?php echo $coreKind ? ' data-cc-core="' . manager_safe_html($coreKind) . '"' : ''; ?>>
                <div class="form-group col-md-3 mb-1">
                    <label class="small mb-0">Nome</label>
                    <input type="text" class="form-control form-control-sm<?php echo $coreKind === 'consent' ? ' js-cc-consent-name' : ''; ?>" name="cookie_consent_tech_name[]" value="<?php echo manager_safe_html((string) ($row['name'] ?? '')); ?>"<?php echo $nameReadonly ? ' readonly' : ''; ?><?php echo $coreKind === 'consent' ? ' pattern="[A-Za-z0-9_-]+" maxlength="64" title="Solo lettere, numeri, _ e -"' : ''; ?>>
                </div>
                <div class="form-group col-md-3 mb-1"><label class="small mb-0">Finalità</label><input type="text" class="form-control form-control-sm" name="cookie_consent_tech_purpose[]" value="<?php echo manager_safe_html((string) ($row['purpose'] ?? '')); ?>"></div>
                <div class="form-group col-md-2 mb-1"><label class="small mb-0">Durata</label><input type="text" class="form-control form-control-sm" name="cookie_consent_tech_duration[]" value="<?php echo manager_safe_html((string) ($row['duration'] ?? '')); ?>"></div>
                <div class="form-group col-md-3 mb-1"><label class="small mb-0">Fornitore</label><input type="text" class="form-control form-control-sm" name="cookie_consent_tech_provider[]" value="<?php echo manager_safe_html((string) ($row['provider'] ?? '')); ?>"></div>
                <div class="form-group col-md-1 mb-1">
                    <?php if ($coreKind): ?>
                        <span class="btn btn-sm btn-outline-secondary disabled" title="Cookie tecnico obbligatorio" aria-disabled="true">&times;</span>
                    <?php else: ?>
                        <button type="button" class="btn btn-sm btn-outline-danger cc-cookie-remove" title="Rimuovi">&times;</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary mb-3" id="cc-add-tech-cookie">+ Aggiungi cookie tecnico</button>

        <div class="section-subtitle">Cookie analytics / marketing</div>
        <p class="small text-muted mb-3">
            I cookie impostati da GTM / Meta dipendono dai tag configurati nel container (non sono noti a priori e non sono rinominabili da qui).
            Il cliente deve indicarli nel documento Privacy / Cookie Policy, oppure accettare i cookie sul sito e verificarli in Ispezione → Applicazione → Cookie.
        </p>

        <div class="section-subtitle">Cookie Policy PDF (opzionale)</div>
        <p class="small text-muted mb-2">Solo se Privacy e Cookie Policy sono documenti separati. Il link nel footer si attiva da <strong>Branding</strong> → Link Cookie Policy.</p>
        <div class="form-group col-md-6 px-0">
            <label>Cookie Policy PDF</label>
            <input type="hidden" name="cookie_policy_link" id="cookie_policy_link" value="<?php echo manager_safe_html($cookiePolicyCurrent); ?>" data-fallback="<?php echo manager_safe_html($cookiePolicyFallback); ?>">
            <div class="custom-file">
                <input type="file" name="upload_cookie_policy_pdf" id="upload_cookie_policy_pdf" class="custom-file-input" accept="application/pdf">
                <label class="custom-file-label" for="upload_cookie_policy_pdf">Scegli PDF...</label>
            </div>
            <small class="form-text text-muted mt-1">Attuale: <span id="current_cookie_policy_pdf"><?php echo manager_safe_html($cookiePolicyCurrent !== '' ? $cookiePolicyCurrent : '—'); ?></span></small>
        </div>

        <div class="row">
            <div class="col-lg-7">
                <div class="section-subtitle">Testi banner</div>
                <div class="form-group">
                    <label for="cookie_consent_text">Messaggio</label>
                    <textarea class="form-control js-cc-preview-input" id="cookie_consent_text" name="cookie_consent_text" rows="3"><?php echo manager_safe_html((string) ($ccBanner['text'] ?? '')); ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Bottone Accetta</label>
                        <input type="text" class="form-control js-cc-preview-input" name="cookie_consent_accept_label" id="cookie_consent_accept_label" value="<?php echo manager_safe_html((string) ($ccBanner['accept_label'] ?? '')); ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Bottone Preferenze</label>
                        <input type="text" class="form-control js-cc-preview-input" name="cookie_consent_preferences_label" id="cookie_consent_preferences_label" value="<?php echo manager_safe_html((string) ($ccBanner['preferences_label'] ?? '')); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Salva preferenze</label>
                        <input type="text" class="form-control js-cc-preview-input" name="cookie_consent_save_label" id="cookie_consent_save_label" value="<?php echo manager_safe_html((string) ($ccBanner['save_label'] ?? '')); ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Label link privacy</label>
                        <input type="text" class="form-control js-cc-preview-input" name="cookie_consent_policy_label" id="cookie_consent_policy_label" value="<?php echo manager_safe_html((string) ($ccBanner['policy_link_label'] ?? '')); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Titolo cookie necessari</label>
                        <input type="text" class="form-control js-cc-preview-input" name="cookie_consent_necessary_title" id="cookie_consent_necessary_title" value="<?php echo manager_safe_html((string) ($ccBanner['necessary_title'] ?? '')); ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Titolo cookie marketing</label>
                        <input type="text" class="form-control js-cc-preview-input" name="cookie_consent_marketing_title" id="cookie_consent_marketing_title" value="<?php echo manager_safe_html((string) ($ccBanner['marketing_title'] ?? '')); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Descrizione cookie necessari</label>
                    <textarea class="form-control js-cc-preview-input" name="cookie_consent_necessary_desc" id="cookie_consent_necessary_desc" rows="2"><?php echo manager_safe_html((string) ($ccBanner['necessary_desc'] ?? '')); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Descrizione cookie marketing</label>
                    <textarea class="form-control js-cc-preview-input" name="cookie_consent_marketing_desc" id="cookie_consent_marketing_desc" rows="2"><?php echo manager_safe_html((string) ($ccBanner['marketing_desc'] ?? '')); ?></textarea>
                </div>

                <div class="section-subtitle">Colori</div>
                <div class="custom-control custom-switch mb-2">
                    <input type="checkbox" class="custom-control-input" id="style_customization_cookie_banner_colors" name="style_customization_cookie_banner_colors" value="1"<?php echo $ccColorsCustom ? ' checked' : ''; ?>>
                    <label class="custom-control-label" for="style_customization_cookie_banner_colors">Personalizza colori banner cookies</label>
                </div>
                <p class="small text-muted mb-2" id="cc-colors-palette-hint"<?php echo $ccColorsCustom ? ' style="display:none;"' : ''; ?>>Disattivo: allineato alla palette — <strong>Primario</strong> bottoni principali/bordo, <strong>Secondario</strong> link e bottone preferenze, <strong>Terziario</strong> hover link/preferenze, <strong>Testo</strong> testo.</p>
                <div id="cc-colors-panel"<?php echo $ccColorsCustom ? '' : ' style="display:none;"'; ?>>
                <div class="form-row">
                    <?php
                    $colorFields = [
                        'bg_color' => 'Sfondo',
                        'text_color' => 'Testo',
                        'link_color' => 'Link',
                        'border_color' => 'Bordo',
                        'button_bg' => 'Bottone primario sfondo',
                        'button_text' => 'Bottone primario testo',
                        'button_secondary_bg' => 'Bottone secondario sfondo',
                        'button_secondary_text' => 'Bottone secondario testo',
                    ];
                    foreach ($colorFields as $ck => $clabel):
                        $cval = $ccColor($ck, $ccStyle, '#0d6efd');
                    ?>
                    <div class="form-group col-md-6 col-lg-4">
                        <label class="small mb-0"><?php echo manager_safe_html($clabel); ?></label>
                        <div class="input-group input-group-sm">
                            <input type="color" class="form-control js-cc-color" data-cc-style="<?php echo manager_safe_html($ck); ?>" value="<?php echo manager_safe_html($cval); ?>" style="max-width:3rem;padding:0.15rem;">
                            <input type="text" class="form-control form-control-sm js-cc-color-hex" name="cookie_consent_style_<?php echo manager_safe_html($ck); ?>" data-cc-style="<?php echo manager_safe_html($ck); ?>" value="<?php echo manager_safe_html($cval); ?>">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="section-subtitle">Anteprima</div>
                <div id="cc-manager-preview" class="cc-manager-preview border rounded p-2 bg-light sticky-top" style="top:1rem;">
                    <div class="cc-preview-banner p-3 mb-2" id="cc-preview-banner">
                        <p class="mb-2 small" style="margin:0;">
                            <span id="cc-prev-text"></span>
                            <a href="#" id="cc-prev-policy" onclick="return false;"></a>
                        </p>
                        <div class="d-flex flex-wrap" style="gap:0.4rem;">
                            <button type="button" class="btn btn-sm" id="cc-prev-prefs" disabled></button>
                            <button type="button" class="btn btn-sm" id="cc-prev-accept" disabled></button>
                        </div>
                    </div>
                    <div class="cc-preview-modal border rounded p-3 bg-white small" id="cc-preview-modal">
                        <strong id="cc-prev-modal-title" class="d-block mb-3"></strong>
                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <div class="font-weight-bold" id="cc-prev-nec-title"></div>
                                <span class="small font-weight-bold" id="cc-prev-nec-badge" style="opacity:.85;">Sempre attivi</span>
                            </div>
                            <div class="text-muted" id="cc-prev-nec-desc"></div>
                        </div>
                        <div class="mb-2">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <div class="font-weight-bold" id="cc-prev-mkt-title"></div>
                                <span class="cc-preview-switch" id="cc-prev-mkt-switch" aria-hidden="true"></span>
                            </div>
                            <div class="text-muted" id="cc-prev-mkt-desc"></div>
                        </div>
                        <button type="button" class="btn btn-sm mt-2" id="cc-prev-save" disabled></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
