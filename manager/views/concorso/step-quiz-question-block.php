<?php
/**
 * Step 10 – Template blocco domanda (dentro #config-form). Salvato con Salva configurazione.
 */
if (!isset($quiz_qblock) || !is_array($quiz_qblock)) {
    $quiz_qblock = is_array($content_current['pages']['quiz_page']['question_block_style'] ?? null)
        ? $content_current['pages']['quiz_page']['question_block_style']
        : (is_array($content_defaults['pages']['quiz_page']['question_block_style'] ?? null)
            ? $content_defaults['pages']['quiz_page']['question_block_style']
            : []);
}
?>
<div class="configurazione-panel quiz-ui-panel mt-3" id="quiz-question-block-panel">
    <input type="hidden" name="quiz_qblock_present" value="1">
    <div class="configurazione-panel-header">
        <span class="configurazione-panel-icon"><i class="fa fa-clone"></i></span>
        <div>
            <h6>Blocco domanda (template)</h6>
            <p>Stile e layout ripetuto per ogni domanda del quiz. Salvato con <strong>Salva configurazione</strong>.</p>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label class="small">Sfondo</label>
                    <select name="quiz_qblock_bg_mode" class="form-control form-control-sm quiz-qblock-bg-mode">
                        <option value="color" <?php echo ($quiz_qblock['background']['mode'] ?? 'color') === 'color' ? 'selected' : ''; ?>>Colore</option>
                        <option value="image" <?php echo ($quiz_qblock['background']['mode'] ?? '') === 'image' ? 'selected' : ''; ?>>Immagine</option>
                    </select>
                </div>
            </div>
            <div class="form-row quiz-qblock-bg-color-row">
                <div class="form-group col-6">
                    <label class="small">Colore</label>
                    <input type="color" class="form-control form-control-sm d-inline-block" style="width:2.5rem; height:1.8rem;" id="quiz_qblock_bg_color_swatch" value="<?php echo htmlspecialchars($quiz_qblock['background']['color'] ?? '#ffffff', ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="text" name="quiz_qblock_bg_color" id="quiz_qblock_bg_color" class="form-control form-control-sm d-inline-block ml-1" style="width:6rem;" value="<?php echo htmlspecialchars($quiz_qblock['background']['color'] ?? '#ffffff', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group col-6">
                    <label class="small">Trasparenza %</label>
                    <input type="number" name="quiz_qblock_bg_opacity" class="form-control form-control-sm" min="0" max="100" value="<?php echo (int) ($quiz_qblock['background']['opacity'] ?? 100); ?>">
                </div>
            </div>
            <div class="form-row quiz-qblock-bg-image-row">
                <div class="form-group col-12">
                    <label class="small">Immagine di sfondo</label>
                    <input type="hidden" name="quiz_qblock_bg_image" id="quiz_qblock_bg_image" value="<?php echo htmlspecialchars($quiz_qblock['background']['image'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="custom-file mt-1">
                        <input type="file" name="upload_quiz_qblock_bg" class="custom-file-input" accept="image/png,image/jpeg,image/jpg">
                        <label class="custom-file-label">Scegli immagine...</label>
                    </div>
                    <?php $qb_img = trim((string) ($quiz_qblock['background']['image'] ?? '')); if ($qb_img !== '') { ?>
                    <small class="form-text text-muted mt-1">Attuale: <?php echo htmlspecialchars($qb_img, ENT_QUOTES); ?></small>
                    <div class="mt-1"><img src="../public/<?php echo htmlspecialchars($qb_img, ENT_QUOTES); ?>" alt="" class="img-thumbnail" style="max-height:60px;"></div>
                    <?php } ?>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-4"><label class="small">Margine sotto</label><input type="number" name="quiz_qblock_margin_bottom" class="form-control form-control-sm" min="0" value="<?php echo (int) ($quiz_qblock['style']['margin']['bottom'] ?? 12); ?>"></div>
                <div class="form-group col-4"><label class="small">Angolo (px)</label><input type="number" name="quiz_qblock_radius_tl" class="form-control form-control-sm" min="0" value="<?php echo (int) ($quiz_qblock['style']['radius']['top'] ?? 8); ?>"></div>
                <div class="form-group col-4"><label class="small">Padding (px)</label><input type="number" name="quiz_qblock_pad_top" class="form-control form-control-sm" min="0" value="<?php echo (int) ($quiz_qblock['style']['padding']['top'] ?? 16); ?>"></div>
            </div>
            <label class="small mt-2">Contenuto blocco – placeholder</label>
            <p class="small text-muted mb-1"><strong>{{DOMANDA_N}}</strong> · <strong>{{TESTO_DOMANDA}}</strong> · <strong>{{RISPOSTA}}</strong> · <strong>{{OPZIONI}}</strong></p>
            <textarea name="quiz_qblock_html" id="quiz_qblock_html" class="form-control quiz-qblock-editor" rows="4"><?php
            $quiz_qblock_content = $quiz_qblock['content'] ?? '';
            if (trim($quiz_qblock_content) === '') {
                $quiz_qblock_content = "<p>{{DOMANDA_N}}</p>\n<p>{{TESTO_DOMANDA}}</p>\n<p>{{RISPOSTA}}</p>\n{{OPZIONI}}";
            }
            if (strpos($quiz_qblock_content, '</textarea>') !== false) {
                $quiz_qblock_content = str_replace('</textarea>', '&lt;/textarea&gt;', $quiz_qblock_content);
            }
            echo htmlspecialchars($quiz_qblock_content, ENT_QUOTES, 'UTF-8');
            ?></textarea>
            <div class="form-row py-2">
                <div class="form-group col-12 col-md-5 mb-0">
                    <label class="small font-weight-bold">Tipo pulsante risposta</label>
                    <select name="quiz_answer_input_style" class="form-control form-control-sm">
                        <option value="radio" <?php echo ($quiz_qblock['answer_input_style'] ?? 'radio') === 'radio' ? 'selected' : ''; ?>>Rotondo (radio)</option>
                        <option value="square" <?php echo ($quiz_qblock['answer_input_style'] ?? '') === 'square' ? 'selected' : ''; ?>>Quadrato (checkbox)</option>
                    </select>
                </div>
                <div class="form-group col-12 col-md-4 mb-0">
                    <label class="small font-weight-bold">Colore tick</label>
                    <div class="d-flex align-items-center">
                        <input type="color" class="form-control form-control-sm mr-1" style="width:2.2rem;height:1.8rem;" id="quiz_answer_tick_color_swatch" value="<?php echo htmlspecialchars($quiz_qblock['answer_tick_color'] ?? '#333333', ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="text" name="quiz_answer_tick_color" id="quiz_answer_tick_color" class="form-control form-control-sm" style="width:6rem;" value="<?php echo htmlspecialchars($quiz_qblock['answer_tick_color'] ?? '#333333', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
            </div>
            <div class="form-row py-2 border-top mt-2">
                <div class="col-12"><label class="small font-weight-bold">Pulsante Invia risposte</label></div>
                <div class="form-group col-md-4">
                    <label class="small">Testo</label>
                    <input type="text" name="quiz_submit_button_text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($quiz_qblock['submit_button_text'] ?? 'Invia risposte', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group col-md-4">
                    <label class="small">Colore</label>
                    <div class="d-flex align-items-center">
                        <input type="color" class="form-control form-control-sm mr-1" style="width:2.2rem;height:1.8rem;" id="quiz_submit_btn_color_swatch" value="<?php echo htmlspecialchars($quiz_qblock['submit_button_bg_color'] ?? '#0d6efd', ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="text" name="quiz_submit_button_bg_color" id="quiz_submit_button_bg_color" class="form-control form-control-sm" style="width:6rem;" value="<?php echo htmlspecialchars($quiz_qblock['submit_button_bg_color'] ?? '#0d6efd', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
                <div class="form-group col-md-4">
                    <label class="small">Posizione</label>
                    <select name="quiz_submit_button_align" class="form-control form-control-sm">
                        <option value="left" <?php echo ($quiz_qblock['submit_button_align'] ?? '') === 'left' ? 'selected' : ''; ?>>Sinistra</option>
                        <option value="center" <?php echo ($quiz_qblock['submit_button_align'] ?? 'center') === 'center' ? 'selected' : ''; ?>>Centro</option>
                        <option value="right" <?php echo ($quiz_qblock['submit_button_align'] ?? '') === 'right' ? 'selected' : ''; ?>>Destra</option>
                    </select>
                </div>
            </div>
            <?php
            $inner_blocks = $quiz_qblock['inner_blocks'] ?? array_fill(0, 4, ['content' => '', 'style' => ['padding' => 4, 'margin_bottom' => 4, 'text_align' => 'left', 'background_color' => '', 'radius' => 0]]);
            for ($ib = 0; $ib < 4; $ib++) {
                $ibs = $inner_blocks[$ib]['style'] ?? [];
                echo '<input type="hidden" name="quiz_inner_' . $ib . '_content" value="' . htmlspecialchars($inner_blocks[$ib]['content'] ?? '', ENT_QUOTES) . '">';
                echo '<input type="hidden" name="quiz_inner_' . $ib . '_pad" value="' . (int) ($ibs['padding'] ?? 4) . '">';
                echo '<input type="hidden" name="quiz_inner_' . $ib . '_mb" value="' . (int) ($ibs['margin_bottom'] ?? 4) . '">';
                echo '<input type="hidden" name="quiz_inner_' . $ib . '_align" value="' . htmlspecialchars($ibs['text_align'] ?? 'left', ENT_QUOTES) . '">';
                echo '<input type="hidden" name="quiz_inner_' . $ib . '_bg" value="' . htmlspecialchars($ibs['background_color'] ?? '', ENT_QUOTES) . '">';
                echo '<input type="hidden" name="quiz_inner_' . $ib . '_radius" value="' . (int) ($ibs['radius'] ?? 0) . '">';
            }
            ?>
            <input type="hidden" name="quiz_qblock_margin_top" value="0">
            <input type="hidden" name="quiz_qblock_margin_right" value="0">
            <input type="hidden" name="quiz_qblock_margin_left" value="0">
            <input type="hidden" name="quiz_qblock_radius_tr" value="<?php echo (int) ($quiz_qblock['style']['radius']['right'] ?? 8); ?>">
            <input type="hidden" name="quiz_qblock_radius_br" value="<?php echo (int) ($quiz_qblock['style']['radius']['bottom'] ?? 8); ?>">
            <input type="hidden" name="quiz_qblock_radius_bl" value="<?php echo (int) ($quiz_qblock['style']['radius']['left'] ?? 8); ?>">
            <input type="hidden" name="quiz_qblock_pad_right" value="<?php echo (int) ($quiz_qblock['style']['padding']['right'] ?? 16); ?>">
            <input type="hidden" name="quiz_qblock_pad_bottom" value="<?php echo (int) ($quiz_qblock['style']['padding']['bottom'] ?? 16); ?>">
            <input type="hidden" name="quiz_qblock_pad_left" value="<?php echo (int) ($quiz_qblock['style']['padding']['left'] ?? 16); ?>">
        </div>
        <div class="col-lg-6">
            <label class="small font-weight-bold d-block mb-2">Anteprima pagina quiz</label>
            <div id="quiz-page-preview" class="waiting-page-preview border rounded" style="min-height: 50vh;">
                <iframe id="quiz-page-preview-iframe" title="Preview pagina quiz" src="ajax/preview_quiz.php?wizard=1" style="width: 100%; height: 50vh; border: none;"></iframe>
            </div>
            <small class="form-text text-muted">Aggiornamento live mentre modifichi; salva per applicare in produzione.</small>
        </div>
    </div>
</div>
