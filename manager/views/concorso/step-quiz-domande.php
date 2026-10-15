<?php
/**
 * Step 10 (parte 2) – CRUD domande + layout containers (fuori da #config-form).
 */
$quizEnabledDomande = !empty($content_current['quiz_enabled'] ?? false);
?>
<div class="card mt-0 mb-4 config-anchor config-step configurazione-section quiz-domande-section" data-step="10" id="section-quiz-domande" data-should-hide="<?php echo $quizEnabledDomande ? 'false' : 'true'; ?>">
    <div class="card-body">
        <?php if (!$quizEnabledDomande): ?>
            <p class="small text-muted mb-0">Abilita il quiz nel passo Configurazione e salva per gestire domande e layout pagina.</p>
        <?php else: ?>
            <?php
            $GLOBALS['__quiz_concorso_embed'] = true;
            include __DIR__ . '/../quiz_domande.php';
            $GLOBALS['__quiz_concorso_embed'] = false;
            ?>
        <?php endif; ?>
    </div>
</div>
