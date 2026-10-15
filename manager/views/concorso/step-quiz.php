<?php
/**
 * Step 10 – Quiz (nel percorso: dopo Form, prima Thank-you)
 */
if (!isset($connection) || !$connection instanceof PDO) {
    if (!function_exists('conn_pdo')) {
        require_once __DIR__ . '/../../../app/inc/config.php';
        require_once __DIR__ . '/../../../app/inc/db.php';
    }
    $connection = conn_pdo();
}

$quizEnabledConcorso = !empty($content_current['quiz_enabled'] ?? false);
$quiz_cfg_ui = $content_current['quiz'] ?? ($content_defaults['quiz'] ?? ['num_questions' => 4, 'mode' => 'standard', 'require_all_correct' => true]);
if (!is_array($quiz_cfg_ui)) {
    $quiz_cfg_ui = ['num_questions' => 4, 'mode' => 'standard', 'require_all_correct' => true];
}
?>
<div class="card mt-3 mb-4 config-anchor config-step configurazione-section quiz-ui-section" data-step="10" id="section-quiz" data-should-hide="<?php echo $quizEnabledConcorso ? 'false' : 'true'; ?>">
    <div class="card-body">
        <?php if (!empty($_GET['quiz_ok'])): ?>
            <div class="alert alert-success py-2 mb-3">Quiz: operazione salvata.</div>
        <?php endif; ?>
        <div class="configurazione-hero quiz-ui-heading">
            <div>
                <div class="section-title configurazione-title"><i class="fa fa-question-circle mr-2"></i>Quiz</div>
                <p class="text-muted small mb-0">Configura percorso, regole, domande e anteprima della pagina quiz.</p>
            </div>
            <span class="configurazione-badge"><i class="fa fa-check-square-o mr-1"></i> Quiz partecipante</span>
        </div>

        <div id="config-quiz-options-panel" class="configurazione-panel quiz-ui-panel" style="<?php echo $quizEnabledConcorso ? '' : 'display: none;'; ?>">
            <div class="configurazione-panel-header">
                <span class="configurazione-panel-icon"><i class="fa fa-sliders"></i></span>
                <div>
                    <h6>Impostazioni quiz</h6>
                    <p>Definisci quante domande mostrare e come valutare il percorso del partecipante.</p>
                </div>
            </div>
            <div class="form-row align-items-end">
                <div class="form-group col-md-4 mb-0">
                    <label class="small">Domande per sessione (1–20) <span class="text-muted ml-1" data-toggle="tooltip" title="Numero di domande estratte a caso per ogni tentativo."><i class="fa fa-question-circle"></i></span></label>
                    <input type="number" name="quiz_num_questions" class="form-control" min="1" max="20" value="<?php echo (int) ($quiz_cfg_ui['num_questions'] ?? 4); ?>">
                </div>
                <div class="form-group col-md-4 mb-0">
                    <label class="small">Tipo di percorso quiz <span class="text-muted ml-1" data-toggle="tooltip" title="Standard = solo domande a testo. Audio = ascolto del file audio, poi domande con risposte A/B/C/D. Scelta = schermata in cui si sceglie tra percorso testo o audio, poi le domande di quel tipo."><i class="fa fa-question-circle"></i></span></label>
                    <?php
                    $quiz_mode_val = $quiz_cfg_ui['mode'] ?? 'standard';
                    if (!in_array($quiz_mode_val, ['standard', 'audio', 'choice'], true)) {
                        $quiz_mode_val = 'standard';
                    }
                    ?>
                    <select name="quiz_mode" id="config-quiz-mode-select" class="form-control">
                        <option value="standard" <?php echo $quiz_mode_val === 'standard' ? 'selected' : ''; ?>>Solo standard (testo)</option>
                        <option value="audio" <?php echo $quiz_mode_val === 'audio' ? 'selected' : ''; ?>>Solo audio (ascolto, poi risposte A/B/C/D)</option>
                        <option value="choice" <?php echo $quiz_mode_val === 'choice' ? 'selected' : ''; ?>>Scelta tra testo e audio</option>
                    </select>
                </div>
                <div class="form-group col-md-4 mb-0">
                    <div class="custom-control custom-checkbox w-100">
                        <input type="checkbox" class="custom-control-input" id="quiz_require_all_correct" name="quiz_require_all_correct" value="1" <?php echo !empty($quiz_cfg_ui['require_all_correct']) ? 'checked' : ''; ?>>
                        <label class="custom-control-label small" for="quiz_require_all_correct">Richiedi tutte le risposte corrette <span class="text-muted ml-1" data-toggle="tooltip" title="Se disattivato, basta almeno una risposta corretta per superare il quiz (se configurato così nel flusso)."><i class="fa fa-question-circle"></i></span></label>
                    </div>
                </div>
            </div>
            <p class="small text-muted mb-0 mt-2">Salva con il pulsante in fondo alla pagina (stesso salvataggio del concorso).</p>
        </div>

        <?php if (!$quizEnabledConcorso): ?>
            <div class="alert alert-secondary mb-0">
                Quiz disattivato. Per abilitarlo usa il passo <strong>Configurazione</strong>, poi torna qui per domande e impostazioni.
            </div>
        <?php endif; ?>
        <?php
        $quiz_layout_embed = true;
        include __DIR__ . '/step-quiz-layout.php';
        ?>
    </div>
</div>
