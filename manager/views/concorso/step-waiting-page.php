<?php
$participationPageEnabled = array_key_exists('participation_page_enabled', $content_current)
    ? (bool) $content_current['participation_page_enabled']
    : (bool) ($content_defaults['participation_page_enabled'] ?? false);
$participation_preview_base = rtrim(((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$participation_preview_url = $participation_preview_base . '/public/partecipazione?preview=1';
?>
<div class="card mt-3 mb-4 config-anchor config-step configurazione-section waiting-page-section waiting-ui-section" data-step="6" id="section-waiting">
    <div class="card-body">
        <div class="configurazione-hero waiting-ui-heading">
            <div>
                <div class="section-title configurazione-title"><i class="fa fa-clock-o mr-2"></i>Pagina attesa</div>
                <p class="text-muted small mb-0">Configura le pagine pubbliche mostrate prima dell'apertura, durante l'accesso intermedio e dopo la chiusura.</p>
            </div>
            <span class="configurazione-badge"><i class="fa fa-hourglass-half mr-1"></i> Pagine di stato</span>
        </div>

        <div class="configurazione-panel waiting-ui-panel">
            <div class="configurazione-panel-header">
                <span class="configurazione-panel-icon"><i class="fa fa-paint-brush"></i></span>
                <div>
                    <h6>Container pagina - Pagina attesa</h6>
                    <p>Personalizza lo sfondo del box principale mostrato prima dell'inizio del concorso.</p>
                </div>
            </div>
            <?php
                echo manager_render_public_page_container_style_editor([
                    'page_key' => 'waiting_page',
                    'label' => 'Pagina attesa',
                    'style' => $public_page_container_styles['waiting_page'] ?? [],
                    'base_dir' => $baseDir,
                ]);
            ?>
        </div>

        <div class="configurazione-panel waiting-ui-panel">
            <div class="configurazione-panel-header">
                <span class="configurazione-panel-icon"><i class="fa fa-th-large"></i></span>
                <div>
                    <h6>Contenuti pagina attesa</h6>
                    <p>Gestisci container, testi, immagini e anteprima della pagina mostrata prima dell'apertura.</p>
                </div>
            </div>
            <?php
                echo manager_render_page_container_editor([
                    'tab_id' => 'waiting',
                    'page_key' => 'waiting_page',
                    'align_value' => $waiting_page_align_value,
                    'containers' => $waiting_page_containers,
                    'preview_containers' => $waiting_preview_containers,
                    'preview_id' => 'waiting-page-preview',
                    'default_background' => $waiting_container_default_background,
                    'default_margin' => $waiting_container_default_margin,
                    'default_radius' => $waiting_container_default_radius,
                    'base_dir' => $baseDir,
                    'placeholders' => [
                        'contest_name' => $waiting_preview_name,
                        'start_date' => $waiting_preview_start,
                        'end_date' => $waiting_preview_end,
                        'min_spend' => $waiting_preview_min_spend,
                        'regolamento_link' => $waiting_preview_rules_link,
                    ],
                ]);
            ?>
        </div>

        <div class="configurazione-panel waiting-ui-panel" id="section-participation-page">
            <div class="configurazione-panel-header">
                <span class="configurazione-panel-icon"><i class="fa fa-toggle-on"></i></span>
                <div>
                    <h6>Pagina partecipazione</h6>
                    <p>Attiva una pagina intermedia quando il concorso e' aperto, prima del form di registrazione.</p>
                </div>
            </div>
            <div class="d-flex align-items-start justify-content-between flex-wrap waiting-ui-toggle-row">
                <div class="text-muted small pr-md-3">Se disattivata, la pagina pubblica porta direttamente al form di registrazione.</div>
                <div class="custom-control custom-switch mt-2 mt-md-0">
                    <input type="checkbox" class="custom-control-input" id="participation_page_enabled" name="participation_page_enabled" value="1" <?php echo $participationPageEnabled ? 'checked' : ''; ?>>
                    <label class="custom-control-label font-weight-bold" for="participation_page_enabled">Abilita pagina partecipazione</label>
                </div>
            </div>
        </div>

        <div id="participation-page-preview-actions" class="configurazione-panel waiting-ui-panel waiting-ui-panel-actions<?php echo $participationPageEnabled ? '' : ' d-none'; ?>">
            <a href="<?php echo manager_safe_html($participation_preview_url); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                <i class="fa fa-external-link mr-1"></i> Anteprima pagina completa
            </a>
            <small class="text-muted mb-0">Anteprima live a destra mentre modifichi i container. Salva per aggiornare la pagina pubblica.</small>
        </div>

        <div id="participation-page-editor-wrap" class="<?php echo $participationPageEnabled ? '' : 'd-none'; ?>">
            <div class="configurazione-panel waiting-ui-panel">
                <div class="configurazione-panel-header">
                    <span class="configurazione-panel-icon"><i class="fa fa-paint-brush"></i></span>
                    <div>
                        <h6>Container pagina - Pagina partecipazione</h6>
                        <p>Personalizza lo sfondo del box principale della pagina intermedia.</p>
                    </div>
                </div>
                <?php
                    echo manager_render_public_page_container_style_editor([
                        'page_key' => 'participation_page',
                        'label' => 'Pagina partecipazione',
                        'style' => $public_page_container_styles['participation_page'] ?? [],
                        'base_dir' => $baseDir,
                    ]);
                ?>
            </div>

            <div class="configurazione-panel waiting-ui-panel">
                <div class="configurazione-panel-header">
                    <span class="configurazione-panel-icon"><i class="fa fa-th-large"></i></span>
                    <div>
                        <h6>Contenuti pagina partecipazione</h6>
                        <p>Gestisci container, testi, immagini e anteprima della pagina intermedia.</p>
                    </div>
                </div>
                <?php
                    echo manager_render_page_container_editor([
                        'tab_id' => 'participation-page',
                        'page_key' => 'participation_page',
                        'align_value' => $participation_page_align_value,
                        'containers' => $participation_page_containers,
                        'preview_containers' => $participation_page_preview_containers,
                        'preview_id' => 'participation-page-preview',
                        'default_background' => $waiting_container_default_background,
                        'default_margin' => $waiting_container_default_margin,
                        'default_radius' => $waiting_container_default_radius,
                        'base_dir' => $baseDir,
                        'placeholders' => [
                            'contest_name' => $waiting_preview_name,
                            'start_date' => $waiting_preview_start,
                            'end_date' => $waiting_preview_end,
                            'min_spend' => $waiting_preview_min_spend,
                            'regolamento_link' => $waiting_preview_rules_link,
                        ],
                    ]);
                ?>
            </div>
        </div>

        <div class="configurazione-panel waiting-ui-panel" id="section-waiting-ended">
            <div class="configurazione-panel-header">
                <span class="configurazione-panel-icon"><i class="fa fa-paint-brush"></i></span>
                <div>
                    <h6>Container pagina - Pagina attesa fine</h6>
                    <p>Personalizza lo sfondo del box principale mostrato dopo la chiusura del concorso.</p>
                </div>
            </div>
            <?php
                echo manager_render_public_page_container_style_editor([
                    'page_key' => 'waiting_page_ended',
                    'label' => 'Pagina attesa - Fine',
                    'style' => $public_page_container_styles['waiting_page_ended'] ?? [],
                    'base_dir' => $baseDir,
                ]);
            ?>
        </div>

        <div class="configurazione-panel waiting-ui-panel mb-0">
            <div class="configurazione-panel-header">
                <span class="configurazione-panel-icon"><i class="fa fa-th-large"></i></span>
                <div>
                    <h6>Contenuti pagina attesa fine</h6>
                    <p>Gestisci container, testi, immagini e anteprima della pagina mostrata dopo la chiusura.</p>
                </div>
            </div>
            <?php
                echo manager_render_page_container_editor([
                    'tab_id' => 'waiting-ended',
                    'page_key' => 'waiting_page_ended',
                    'align_value' => $waiting_page_ended_align_value,
                    'containers' => $waiting_page_ended_containers,
                    'preview_containers' => $waiting_preview_ended_containers,
                    'preview_id' => 'waiting-page-ended-preview',
                    'default_background' => $waiting_container_default_background,
                    'default_margin' => $waiting_container_default_margin,
                    'default_radius' => $waiting_container_default_radius,
                    'base_dir' => $baseDir,
                    'placeholders' => [
                        'contest_name' => $waiting_preview_name,
                        'start_date' => $waiting_preview_start,
                        'end_date' => $waiting_preview_end,
                        'min_spend' => $waiting_preview_min_spend,
                        'regolamento_link' => $waiting_preview_rules_link,
                    ],
                ]);
            ?>
        </div>
    </div>
</div>
