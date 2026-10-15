<?php
$thankyouIsInstantwin = ($contestMode ?? '') === 'instantwin';
$sharedThankyouPage = $thankyouIsInstantwin && !empty($content_current['instantwin_shared_thankyou_page'] ?? ($content_defaults['instantwin_shared_thankyou_page'] ?? false));
?>
<?php if ($thankyouIsInstantwin): ?>
<div class="card mt-3 mb-3 config-anchor config-step configurazione-section" data-step="5" id="section-thank-you-shared-toggle">
    <div class="card-body py-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="instantwin_shared_thankyou_page" name="instantwin_shared_thankyou_page" value="1" <?php echo $sharedThankyouPage ? 'checked' : ''; ?>>
            <label class="form-check-label" for="instantwin_shared_thankyou_page">
                <strong>Thank-you unica</strong> (stessa pagina per vincita e non vincita)
            </label>
        </div>
        <small class="form-text text-muted mb-0">Le email Instant Win restano separate (vincita / non vincita). Se attivo, si usa il contenuto della pagina Thank-you qui sotto per entrambi i casi.</small>
    </div>
</div>
<?php endif; ?>
<div class="card mt-3 mb-4 config-anchor config-step configurazione-section waiting-page-section thank-you-section thank-you-section-win <?php echo $thankyouIsInstantwin ? '' : 'd-none'; ?>" data-step="5" id="section-thank-you-win">
    <div class="card-body">
        <div class="thank-you-heading configurazione-hero">
            <div>
                <div class="section-title configurazione-title mb-1" id="thankyou-win-title">
                    <i class="fa fa-trophy mr-2"></i><?php echo $sharedThankyouPage ? 'Thank-you' : 'Thank-you WIN'; ?>
                </div>
                <div class="thank-you-heading-note" id="thankyou-win-note"><?php echo $sharedThankyouPage
                    ? 'Pagina unica mostrata dopo Instant Win (vincita o non vincita). Il risultato è comunicato via email.'
                    : 'Pagina mostrata quando l\'utente vince nell\'Instant Win.'; ?></div>
            </div>
            <span class="thank-you-status-badge configurazione-badge">Instant Win</span>
        </div>
        <div class="configurazione-panel">
            <div class="configurazione-panel-header">
                <span class="configurazione-panel-icon"><i class="fa fa-trophy"></i></span>
                <div>
                    <h6 id="thankyou-win-panel-title"><?php echo $sharedThankyouPage ? 'Container pagina - Thank-you' : 'Container pagina - Thank-you WIN'; ?></h6>
                    <p>Personalizza lo sfondo del box principale prima di modificare i contenuti interni.</p>
                </div>
            </div>
        <?php
        echo manager_render_public_page_container_style_editor([
            'page_key' => 'thankyou_instantwin_win_page',
            'label' => $sharedThankyouPage ? 'Thank-you' : 'Thank-you WIN',
            'style' => $public_page_container_styles['thankyou_instantwin_win_page'] ?? [],
            'base_dir' => $baseDir,
        ]);
        ?>
        </div>
        <?php echo $render_thankyou_tab('thankyou-win', 'thankyou_instantwin_win_page', $thankyou_instantwin_win_align_value, $thankyou_instantwin_win_containers, $thankyou_instantwin_win_preview_containers, true); ?>
    </div>
</div>

<?php
    $quizEnabledThankyouStep = !empty($content_current['quiz_enabled'] ?? false);
    $quiz_cfg_thankyou_step = array_merge(
        (array) ($content_defaults['quiz'] ?? []),
        (array) ($content_current['quiz'] ?? [])
    );
    $quizRequireAllCorrectThankyouStep = !empty($quiz_cfg_thankyou_step['require_all_correct']);
?>
<div class="card mt-3 mb-4 config-anchor config-step configurazione-section waiting-page-section thank-you-section thank-you-section-lose <?php echo ($thankyouIsInstantwin && (!$sharedThankyouPage || ($quizEnabledThankyouStep && $quizRequireAllCorrectThankyouStep))) ? '' : 'd-none'; ?>" data-step="5" id="section-thank-you-lose">
    <div class="card-body">
        <div id="thankyou-lose-only-block" class="<?php echo $sharedThankyouPage ? 'd-none' : ''; ?>">
        <div class="thank-you-heading configurazione-hero">
            <div>
                <div class="section-title configurazione-title mb-1"><i class="fa fa-times-circle mr-2"></i>Thank-you LOSE</div>
                <div class="thank-you-heading-note">Pagina mostrata quando l'utente partecipa ma non vince nell'Instant Win.</div>
            </div>
            <span class="thank-you-status-badge configurazione-badge">Instant Win</span>
        </div>
        <p class="small text-muted mb-3 <?php echo ($thankyouIsInstantwin && $quizEnabledThankyouStep && $quizRequireAllCorrectThankyouStep) ? '' : 'd-none'; ?>" id="thankyou-lose-iw-quiz-hint">Messaggio per chi ha superato il quiz ma non ha vinto l'Instant Win. Se "Richiedi tutte le risposte corrette" e' attivo nel passo Quiz, chi non supera il quiz vede la sezione sotto.</p>
        <div class="configurazione-panel">
            <div class="configurazione-panel-header">
                <span class="configurazione-panel-icon"><i class="fa fa-times-circle"></i></span>
                <div>
                    <h6>Container pagina - Thank-you LOSE</h6>
                    <p>Personalizza lo sfondo del box principale prima di modificare i contenuti interni.</p>
                </div>
            </div>
        <?php
        echo manager_render_public_page_container_style_editor([
            'page_key' => 'thankyou_instantwin_loss_page',
            'label' => 'Thank-you LOSE',
            'style' => $public_page_container_styles['thankyou_instantwin_loss_page'] ?? [],
            'base_dir' => $baseDir,
        ]);
        ?>
        </div>
        <?php echo $render_thankyou_tab('thankyou-lose', 'thankyou_instantwin_loss_page', $thankyou_instantwin_loss_align_value, $thankyou_instantwin_loss_containers, $thankyou_instantwin_loss_preview_containers, true); ?>
        </div>
        <div id="thankyou-quiz-failed-editor-block" class="<?php echo ($thankyouIsInstantwin && $quizEnabledThankyouStep && $quizRequireAllCorrectThankyouStep) ? '' : 'd-none'; ?>">
            <hr class="my-4">
            <div class="section-title mb-2"><i class="fa fa-exclamation-triangle mr-2 text-warning"></i>THANK-YOU - Quiz non superato (Instant Win + quiz)</div>
            <p class="small text-muted mb-3">Mostrata quando il quiz e' attivo, "Richiedi tutte le risposte corrette" e' si e il partecipante non raggiunge l'esito richiesto. Modifica liberamente titoli e testi nei container sotto.</p>
            <div class="configurazione-panel">
                <div class="configurazione-panel-header">
                    <span class="configurazione-panel-icon"><i class="fa fa-exclamation-triangle"></i></span>
                    <div>
                        <h6>Container pagina - Quiz non superato</h6>
                        <p>Configura lo stile della pagina mostrata a chi non supera il quiz richiesto.</p>
                    </div>
                </div>
            <?php
            echo manager_render_public_page_container_style_editor([
                'page_key' => 'thankyou_instantwin_quiz_failed_page',
                'label' => 'Thank-you quiz non superato',
                'style' => $public_page_container_styles['thankyou_instantwin_quiz_failed_page'] ?? [],
                'base_dir' => $baseDir,
            ]);
            ?>
            </div>
            <?php echo $render_thankyou_tab('thankyou-quiz-failed', 'thankyou_instantwin_quiz_failed_page', $thankyou_instantwin_quiz_failed_align_value, $thankyou_instantwin_quiz_failed_containers, $thankyou_instantwin_quiz_failed_preview_containers, false); ?>
        </div>
    </div>
</div>

<div class="card mt-3 mb-4 config-anchor config-step configurazione-section waiting-page-section thank-you-section thank-you-section-partecipazione <?php echo $thankyouIsInstantwin ? 'd-none' : ''; ?>" data-step="5" id="section-thank-you-partecipazione">
    <div class="card-body">
        <div class="thank-you-heading configurazione-hero">
            <div>
                <div class="section-title configurazione-title mb-1"><i class="fa fa-check-circle mr-2"></i>Thank-you partecipazione</div>
                <div class="thank-you-heading-note">Pagina mostrata dopo una partecipazione registrata correttamente.</div>
            </div>
            <span class="thank-you-status-badge configurazione-badge">Estrazione</span>
        </div>
        <div class="configurazione-panel">
            <div class="configurazione-panel-header">
                <span class="configurazione-panel-icon"><i class="fa fa-check-circle"></i></span>
                <div>
                    <h6>Container pagina - Thank-you partecipazione</h6>
                    <p>Personalizza lo sfondo del box principale prima di modificare i contenuti interni.</p>
                </div>
            </div>
        <?php
        echo manager_render_public_page_container_style_editor([
            'page_key' => 'thankyou_estrazione_page',
            'label' => 'Thank-you partecipazione',
            'style' => $public_page_container_styles['thankyou_estrazione_page'] ?? [],
            'base_dir' => $baseDir,
        ]);
        ?>
        </div>
        <?php echo $render_thankyou_tab('thankyou-partecipazione', 'thankyou_estrazione_page', $thankyou_estrazione_align_value, $thankyou_estrazione_containers, $thankyou_estrazione_preview_containers, true); ?>
        <div id="thankyou-estrazione-quiz-failed-editor-block" class="<?php echo (!$thankyouIsInstantwin && $quizEnabledThankyouStep && $quizRequireAllCorrectThankyouStep) ? '' : 'd-none'; ?>">
            <hr class="my-4">
            <div class="section-title mb-2"><i class="fa fa-exclamation-triangle mr-2 text-warning"></i>THANK-YOU - Quiz non superato (Estrazione + quiz)</div>
            <p class="small text-muted mb-3">Mostrata quando il quiz e' attivo, "Richiedi tutte le risposte corrette" e' si e il partecipante non raggiunge l'esito richiesto. Modifica liberamente titoli e testi nei container sotto.</p>
            <div class="configurazione-panel">
                <div class="configurazione-panel-header">
                    <span class="configurazione-panel-icon"><i class="fa fa-exclamation-triangle"></i></span>
                    <div>
                        <h6>Container pagina - Quiz non superato</h6>
                        <p>Configura lo stile della pagina mostrata a chi non supera il quiz richiesto.</p>
                    </div>
                </div>
            <?php
            echo manager_render_public_page_container_style_editor([
                'page_key' => 'thankyou_estrazione_quiz_failed_page',
                'label' => 'Thank-you quiz non superato (estrazione)',
                'style' => $public_page_container_styles['thankyou_estrazione_quiz_failed_page'] ?? [],
                'base_dir' => $baseDir,
            ]);
            ?>
            </div>
            <?php echo $render_thankyou_tab('thankyou-estrazione-qfail', 'thankyou_estrazione_quiz_failed_page', $thankyou_estrazione_quiz_failed_align_value, $thankyou_estrazione_quiz_failed_containers, $thankyou_estrazione_quiz_failed_preview_containers, false); ?>
        </div>
    </div>
</div>
