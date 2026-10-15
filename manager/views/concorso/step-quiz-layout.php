<?php
/**
 * Step 10 – Layout pagine quiz (dentro #config-form): scelta + intestazione domande.
 */
if (!isset($quiz_layout_embed)) {
    $quiz_layout_embed = false;
}
$quizLayoutEnabled = !empty($content_current['quiz_enabled'] ?? false);
$quizModeLayout = $quiz_cfg_ui['mode'] ?? ($content_current['quiz']['mode'] ?? 'standard');
if (!in_array($quizModeLayout, ['standard', 'audio', 'choice'], true)) {
    $quizModeLayout = 'standard';
}
$quizChoiceModeActive = ($quizModeLayout === 'choice');
?>
<?php if ($quizLayoutEnabled): ?>

    <div id="quiz-choice-layout-wrap" class="<?php echo $quizChoiceModeActive ? '' : 'd-none'; ?>">
        <div class="configurazione-panel quiz-ui-panel mt-3">
            <div class="configurazione-panel-header">
                <span class="configurazione-panel-icon"><i class="fa fa-code-fork"></i></span>
                <div>
                    <h6>Pagina scelta (testo / audio)</h6>
                    <p>Visibile al partecipante solo con percorso &quot;Scelta tra testo e audio&quot;. Stesso editor dei container Thank-you / Attesa.</p>
                </div>
            </div>
            <?php
            echo manager_render_public_page_container_style_editor([
                'page_key' => 'quiz_choice_page',
                'label' => 'Quiz – Pagina scelta',
                'style' => $public_page_container_styles['quiz_choice_page'] ?? [],
                'base_dir' => $baseDir,
            ]);
            ?>
        </div>
        <div class="configurazione-panel quiz-ui-panel">
            <div class="configurazione-panel-header">
                <span class="configurazione-panel-icon"><i class="fa fa-th-large"></i></span>
                <div>
                    <h6>Contenuti pagina scelta</h6>
                    <p>Testi e immagini mostrati prima della scelta tra percorso testo o audio.</p>
                </div>
            </div>
            <div class="form-row mb-3">
                <div class="form-group col-md-4">
                    <label class="small">Etichetta opzione Testo</label>
                    <input type="text" name="quiz_choice_label_standard" class="form-control form-control-sm" value="<?php echo manager_safe_html((string) ($quiz_choice_labels['standard'] ?? '')); ?>">
                </div>
                <div class="form-group col-md-4">
                    <label class="small">Etichetta opzione Audio</label>
                    <input type="text" name="quiz_choice_label_audio" class="form-control form-control-sm" value="<?php echo manager_safe_html((string) ($quiz_choice_labels['audio'] ?? '')); ?>">
                </div>
                <div class="form-group col-md-4">
                    <label class="small">Testo pulsante</label>
                    <input type="text" name="quiz_choice_label_submit" class="form-control form-control-sm" value="<?php echo manager_safe_html((string) ($quiz_choice_labels['submit'] ?? '')); ?>">
                </div>
            </div>
            <?php
            echo manager_render_page_container_editor([
                'tab_id' => 'quiz-choice',
                'page_key' => 'quiz_choice_page',
                'align_value' => $quiz_choice_page_align_value,
                'containers' => $quiz_choice_page_containers,
                'preview_containers' => $quiz_choice_page_preview_containers,
                'preview_id' => 'quiz-choice-page-preview',
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
            <p class="small text-muted mt-2 mb-0">L&apos;anteprima a destra mostra i container; le opzioni radio e il pulsante usano le etichette sopra.</p>
        </div>
    </div>

    <div class="configurazione-panel quiz-ui-panel mt-3">
        <div class="configurazione-panel-header">
            <span class="configurazione-panel-icon"><i class="fa fa-object-group"></i></span>
            <div>
                <h6>Intestazione pagina domande</h6>
                <p>Container sopra l&apos;elenco delle domande (titolo quiz, istruzioni). Salvato con <strong>Salva configurazione</strong>.</p>
            </div>
        </div>
        <?php
        echo manager_render_public_page_container_style_editor([
            'page_key' => 'quiz_page',
            'label' => 'Quiz – Intestazione',
            'style' => $public_page_container_styles['quiz_page'] ?? [],
            'base_dir' => $baseDir,
        ]);
        echo manager_render_page_container_editor([
            'tab_id' => 'quiz-page',
            'page_key' => 'quiz_page',
            'align_value' => $quiz_page_align_value,
            'containers' => $quiz_page_containers,
            'preview_containers' => $quiz_page_preview_containers,
            'preview_id' => 'quiz-page-header-preview',
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

    <?php include __DIR__ . '/step-quiz-question-block.php'; ?>
<?php endif; ?>
