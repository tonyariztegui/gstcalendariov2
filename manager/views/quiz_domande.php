<?php
if (!isset($GLOBALS['__quiz_concorso_embed'])) {
    $GLOBALS['__quiz_concorso_embed'] = false;
}
$__quiz_concorso_embed = !empty($GLOBALS['__quiz_concorso_embed']);

if (!function_exists('quiz_manage_url')) {
    /**
     * URL gestione domande: standalone (quiz_domande) o integrato in Concorso (step 10).
     */
    function quiz_manage_url(string $query = ''): string
    {
        $embed = !empty($GLOBALS['__quiz_concorso_embed']);
        $base = $embed ? 'index.php?pg=concorso&concorso_step=10' : 'index.php?pg=quiz_domande';
        if ($query === '') {
            return $base;
        }
        return $base . '&' . ltrim($query, '&');
    }
}

if (!function_exists('quiz_delete_url')) {
    function quiz_delete_url(int $id): string
    {
        global $__quiz_concorso_embed;
        $u = 'index.php?pg=quiz_domande&delete=' . $id;
        if (!empty($__quiz_concorso_embed)) {
            $u .= '&return=concorso';
        }
        return $u;
    }
}

$connection = $connection ?? conn_pdo();
$msg = '';
$err = '';

// Create table if not exists
$connection->exec("
    CREATE TABLE IF NOT EXISTS quiz_domande (
        id int(11) NOT NULL AUTO_INCREMENT,
        testo text NOT NULL,
        tipo enum('text','audio','video') NOT NULL DEFAULT 'text',
        audio_file varchar(500) DEFAULT NULL,
        video_file varchar(500) DEFAULT NULL,
        risposta_a varchar(500) NOT NULL DEFAULT '',
        risposta_b varchar(500) NOT NULL DEFAULT '',
        risposta_c varchar(500) NOT NULL DEFAULT '',
        risposta_d varchar(500) NOT NULL DEFAULT '',
        risposta_corretta enum('A','B','C','D') NOT NULL DEFAULT 'A',
        ordine int(11) NOT NULL DEFAULT 0,
        attiva tinyint(1) NOT NULL DEFAULT 1,
        creata_il datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        aggiornata_il datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_attiva_ordine (attiva, ordine)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$connection->exec("
    CREATE TABLE IF NOT EXISTS quiz_tentativi (
        id int(11) NOT NULL AUTO_INCREMENT,
        id_cliente int(11) NOT NULL,
        token_partecipazione varchar(64) NOT NULL,
        quiz_mode enum('standard','audio') NOT NULL DEFAULT 'standard',
        corrette int(11) NOT NULL DEFAULT 0,
        totali int(11) NOT NULL DEFAULT 0,
        esito tinyint(1) NOT NULL DEFAULT 0,
        risposte_json text DEFAULT NULL,
        creata_il datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_cliente_token (id_cliente, token_partecipazione),
        KEY idx_token (token_partecipazione)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// Delete
if (isset($_GET['delete']) && (int)$_GET['delete'] > 0) {
    $id = (int)$_GET['delete'];
    $stmt = $connection->prepare('DELETE FROM quiz_domande WHERE id = ?');
    $stmt->execute([$id]);
    $msg = 'Domanda eliminata.';
    if (($_GET['return'] ?? '') === 'concorso') {
        header('Location: index.php?pg=concorso&concorso_step=10&quiz_ok=1');
    } else {
        header('Location: index.php?pg=quiz_domande&ok=1');
    }
    exit;
}

// Cartella upload file audio quiz (path assoluto dalla root del progetto)
$err_upload_dir = '';
$project_root = dirname(__DIR__, 2);
$quiz_upload_dir = $project_root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'quiz';
if (!is_dir($quiz_upload_dir)) {
    if (!@mkdir($quiz_upload_dir, 0755, true)) {
        $err_upload_dir = 'Impossibile creare la cartella public/assets/quiz. Verifica i permessi.';
    }
}

// Save (insert/update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_quiz_domanda'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $testo = trim((string)($_POST['testo'] ?? ''));
    $tipo = in_array($_POST['tipo'] ?? '', ['text', 'audio'], true) ? $_POST['tipo'] : 'text';
    $audio_file = trim((string)($_POST['audio_file'] ?? ''));
    $video_file = '';

    $allowed_audio_ext = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'webm'];

    // Upload file audio (se caricato; solo se tipo = audio)
    if ($tipo === 'audio' && !empty($_FILES['audio_upload']['name'])) {
        $up_err = $_FILES['audio_upload']['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($up_err !== UPLOAD_ERR_OK) {
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE => 'Il file audio è troppo grande (limite PHP).',
                UPLOAD_ERR_FORM_SIZE => 'Il file audio è troppo grande.',
                UPLOAD_ERR_PARTIAL => 'Il file audio è stato caricato solo in parte.',
                UPLOAD_ERR_NO_FILE => 'Nessun file audio inviato.',
                UPLOAD_ERR_NO_TMP_DIR => 'Cartella temporanea mancante sul server.',
                UPLOAD_ERR_CANT_WRITE => 'Impossibile scrivere il file audio su disco.',
                UPLOAD_ERR_EXTENSION => 'Un\'estensione PHP ha bloccato l\'upload.',
            ];
            $err = $upload_errors[$up_err] ?? 'Errore upload audio (codice ' . $up_err . ').';
        } elseif (!empty($err_upload_dir)) {
            $err = $err_upload_dir;
        } else {
            $ext = strtolower(pathinfo($_FILES['audio_upload']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_audio_ext, true)) {
                $err = 'Formato audio non consentito. Usa: ' . implode(', ', $allowed_audio_ext);
            } else {
                $safe_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['audio_upload']['name']));
                $safe_name = date('YmdHis') . '_' . $safe_name;
                $dest = $quiz_upload_dir . DIRECTORY_SEPARATOR . $safe_name;
                if (move_uploaded_file($_FILES['audio_upload']['tmp_name'], $dest)) {
                    $audio_file = 'assets/quiz/' . $safe_name;
                } else {
                    $err = 'Impossibile salvare il file audio in public/assets/quiz. Verifica che la cartella esista e sia scrivibile.';
                }
            }
        }
    }
    if ($tipo === 'text') {
        $audio_file = '';
    }
    $risposta_a = trim((string)($_POST['risposta_a'] ?? ''));
    $risposta_b = trim((string)($_POST['risposta_b'] ?? ''));
    $risposta_c = trim((string)($_POST['risposta_c'] ?? ''));
    $risposta_d = trim((string)($_POST['risposta_d'] ?? ''));
    $risposta_corretta = in_array($_POST['risposta_corretta'] ?? '', ['A', 'B', 'C', 'D']) ? $_POST['risposta_corretta'] : 'A';
    $ordine = (int)($_POST['ordine'] ?? 0);
    $attiva = isset($_POST['attiva']) ? 1 : 0;
    if (empty($err)) {
        if ($tipo === 'text' && $testo === '') {
            $err = 'Il testo della domanda è obbligatorio per il tipo Testo.';
        } elseif ($tipo === 'audio' && $testo === '' && $audio_file === '') {
            $err = 'Per il tipo Audio inserisci il testo della domanda oppure un file audio (percorso, URL o caricamento).';
        }
    }
    if (empty($err)) {
        if ($id > 0) {
            $stmt = $connection->prepare("
                UPDATE quiz_domande SET testo=?, tipo=?, audio_file=?, video_file=?,
                risposta_a=?, risposta_b=?, risposta_c=?, risposta_d=?,
                risposta_corretta=?, ordine=?, attiva=?
                WHERE id=?
            ");
            $stmt->execute([$testo, $tipo, $audio_file, $video_file, $risposta_a, $risposta_b, $risposta_c, $risposta_d, $risposta_corretta, $ordine, $attiva, $id]);
            $msg = 'Domanda aggiornata.';
            if (!empty($_POST['quiz_return_concorso'])) {
                header('Location: index.php?pg=concorso&concorso_step=10&quiz_ok=1');
                exit;
            }
        } else {
            $stmt = $connection->prepare("
                INSERT INTO quiz_domande (testo, tipo, audio_file, video_file, risposta_a, risposta_b, risposta_c, risposta_d, risposta_corretta, ordine, attiva)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->execute([$testo, $tipo, $audio_file, $video_file, $risposta_a, $risposta_b, $risposta_c, $risposta_d, $risposta_corretta, $ordine, $attiva]);
            $msg = 'Domanda aggiunta.';
            if (!empty($_POST['quiz_return_concorso'])) {
                header('Location: index.php?pg=concorso&concorso_step=10&quiz_ok=1');
                exit;
            }
        }
    }
}

if (!$__quiz_concorso_embed) {
    include("_header.php");
    include("res/inc/header.php");
}
include_once __DIR__ . '/../res/inc/sanitize.inc.php';

$edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$row_edit = null;
if ($edit_id > 0) {
    $stmt = $connection->prepare('SELECT * FROM quiz_domande WHERE id = ?');
    $stmt->execute([$edit_id]);
    $row_edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

$list = $connection->query("SELECT id, testo, tipo, risposta_corretta, ordine, attiva FROM quiz_domande ORDER BY ordine ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="<?php echo $__quiz_concorso_embed ? 'quiz-domande-embed' : 'container-fluid'; ?>">
    <div class="<?php echo $__quiz_concorso_embed ? 'configurazione-panel quiz-domande-panel' : 'card mt-3 mb-4'; ?>">
        <div class="card-body">
            <div class="<?php echo $__quiz_concorso_embed ? 'configurazione-panel-header quiz-domande-heading' : 'section-title'; ?>">
                <?php if ($__quiz_concorso_embed): ?>
                    <span class="configurazione-panel-icon"><i class="fa fa-question-circle"></i></span>
                    <div>
                        <h6>Gestione domande</h6>
                        <p>Aggiungi o modifica le domande del quiz (testo, audio, risposte A/B/C/D). Il layout della pagina si configura nel passo Quiz sopra.</p>
                    </div>
                <?php else: ?>
                    <i class="fa fa-question-circle mr-2"></i>Gestione domande
                <?php endif; ?>
            </div>
            <?php if (isset($_GET['ok'])): ?>
                <div class="alert alert-success">Operazione completata.</div>
            <?php endif; ?>
            <?php if ($msg): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if ($err): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <div class="row">
                <div class="col-md-5 col-lg-5">
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong><?php echo $row_edit ? 'Modifica domanda' : 'Nuova domanda'; ?></strong>
                            <a href="<?php echo htmlspecialchars(quiz_manage_url(), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-success"><i class="fa fa-plus mr-1"></i>Nuova domanda</a>
                        </div>
                        <div class="card-body">
                    <form method="post" enctype="multipart/form-data" action="<?php echo $__quiz_concorso_embed ? 'index.php?pg=quiz_domande' : ''; ?>">
                        <input type="hidden" name="save_quiz_domanda" value="1">
                        <?php if ($__quiz_concorso_embed): ?><input type="hidden" name="quiz_return_concorso" value="1"><?php endif; ?>
                        <?php if ($row_edit): ?>
                            <input type="hidden" name="id" value="<?php echo (int)$row_edit['id']; ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label>Testo domanda <span class="text-muted font-weight-normal">(obbligatorio se tipo Testo; con Audio può essere vuoto se c'è il file audio)</span></label>
                            <textarea name="testo" class="form-control" rows="3"><?php echo htmlspecialchars($row_edit['testo'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Tipo</label>
                            <select name="tipo" id="quiz-domanda-tipo" class="form-control">
                                <option value="text" <?php echo in_array(($row_edit['tipo'] ?? 'text'), ['text', 'video'], true) ? 'selected' : ''; ?>>Testo</option>
                                <option value="audio" <?php echo ($row_edit['tipo'] ?? '') === 'audio' ? 'selected' : ''; ?>>Audio</option>
                            </select>
                            <?php if (($row_edit['tipo'] ?? '') === 'video'): ?>
                            <small class="form-text text-warning">Questa domanda era di tipo &quot;video&quot; (non più supportato). Salva come Testo o Audio.</small>
                            <?php endif; ?>
                        </div>
                        <div class="form-group" id="quiz-domanda-audio-fields" style="<?php echo ($row_edit['tipo'] ?? '') === 'audio' ? '' : 'display:none;'; ?>">
                            <label>File audio (percorso, URL o carica file)</label>
                            <div class="input-group mb-1">
                                <input type="text" name="audio_file" id="audio_file_path" class="form-control" value="<?php echo htmlspecialchars($row_edit['audio_file'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="assets/quiz/audio.mp3 oppure https://...">
                                <div class="custom-file ml-2" style="max-width: 220px;">
                                    <input type="file" name="audio_upload" id="audio_upload" class="custom-file-input" accept=".mp3,.wav,.ogg,.m4a,audio/*">
                                    <label class="custom-file-label" for="audio_upload">Carica audio</label>
                                </div>
                            </div>
                            <small class="form-text text-muted">Carica un file (MP3, WAV, OGG, M4A): verrà salvato in <code>public/assets/quiz/</code> e il percorso verrà inserito automaticamente. Oppure scrivi il percorso (es. <code>assets/quiz/file.mp3</code>) o un URL.</small>
                        </div>
                        <div class="form-group">
                            <label>Risposta A</label>
                            <input type="text" name="risposta_a" class="form-control" value="<?php echo htmlspecialchars($row_edit['risposta_a'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Risposta B</label>
                            <input type="text" name="risposta_b" class="form-control" value="<?php echo htmlspecialchars($row_edit['risposta_b'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Risposta C</label>
                            <input type="text" name="risposta_c" class="form-control" value="<?php echo htmlspecialchars($row_edit['risposta_c'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Risposta D</label>
                            <input type="text" name="risposta_d" class="form-control" value="<?php echo htmlspecialchars($row_edit['risposta_d'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Risposta corretta</label>
                            <select name="risposta_corretta" class="form-control">
                                <option value="A" <?php echo ($row_edit['risposta_corretta'] ?? '') === 'A' ? 'selected' : ''; ?>>A</option>
                                <option value="B" <?php echo ($row_edit['risposta_corretta'] ?? '') === 'B' ? 'selected' : ''; ?>>B</option>
                                <option value="C" <?php echo ($row_edit['risposta_corretta'] ?? '') === 'C' ? 'selected' : ''; ?>>C</option>
                                <option value="D" <?php echo ($row_edit['risposta_corretta'] ?? '') === 'D' ? 'selected' : ''; ?>>D</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Ordine</label>
                            <input type="number" name="ordine" class="form-control" value="<?php echo (int)($row_edit['ordine'] ?? 0); ?>">
                            <small class="form-text text-muted">Ordina l'elenco delle domande in gestione (numeri crescenti prima). Nel quiz pubblico le domande del turno sono scelte a caso: l'ordine qui non determina la sequenza mostrata al partecipante.</small>
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" name="attiva" class="form-check-input" id="attiva" value="1" <?php echo ($row_edit['attiva'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="attiva">Attiva</label>
                        </div>
                        <button type="submit" class="btn btn-primary"><?php echo $row_edit ? 'Salva modifiche' : 'Aggiungi domanda'; ?></button>
                        <?php if ($row_edit): ?>
                            <a href="<?php echo htmlspecialchars(quiz_manage_url(), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Annulla</a>
                        <?php else: ?>
                            <a href="<?php echo htmlspecialchars(quiz_manage_url(), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">Svuota form</a>
                        <?php endif; ?>
                    </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-7 col-lg-7">
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                            <span>Elenco domande</span>
                            <?php
                            $perPage = 10;
                            $totalDomande = count($list);
                            $totalPages = $totalDomande > $perPage ? (int)ceil($totalDomande / $perPage) : 1;
                            if ($totalDomande > $perPage): ?>
                            <span class="small text-muted" id="elenco-domande-page-info"><?php echo $totalDomande; ?> domande Â· pagina 1 di <?php echo $totalPages; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-0">
                            <div class="waiting-containers-scroll" id="elenco-domande-list">
                                <?php foreach ($list as $index => $r):
                                    $pageNum = (int)floor($index / $perPage) + 1;
                                ?>
                                <div class="waiting-container-item card mb-3 mx-2 mt-2 domanda-list-item" data-container-index="<?php echo (int)$index; ?>" data-page="<?php echo $pageNum; ?>">
                                    <div class="card-header d-flex justify-content-between align-items-center py-2">
                                        <strong class="waiting-container-title">Domanda <?php echo (int)$index + 1; ?></strong>
                                        <div class="waiting-container-actions d-flex align-items-center">
                                            <span class="small text-muted mr-2">Ord. <?php echo (int)$r['ordine']; ?> Â· <?php echo htmlspecialchars($r['tipo'], ENT_QUOTES, 'UTF-8'); ?> Â· Corretta: <?php echo htmlspecialchars($r['risposta_corretta'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <a href="<?php echo htmlspecialchars(quiz_manage_url('id=' . (int) $r['id']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-outline-primary ml-1" title="Modifica">Modifica</a>
                                            <a href="#" class="btn btn-sm btn-outline-danger ml-1 btn-quiz-domanda-delete" title="Elimina" data-href="<?php echo htmlspecialchars(quiz_delete_url((int) $r['id']), ENT_QUOTES, 'UTF-8'); ?>"><i class="fa fa-trash"></i></a>
                                        </div>
                                    </div>
                                    <div class="card-body py-2">
                                        <p class="mb-0 small text-muted"><?php echo htmlspecialchars(mb_substr($r['testo'], 0, 80) . (mb_strlen($r['testo']) > 80 ? '…' : ''), ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($totalDomande > $perPage): ?>
                            <nav class="p-2 px-3 border-top" aria-label="Paginazione elenco domande">
                                <ul class="pagination pagination-sm mb-0 justify-content-center flex-wrap" id="elenco-domande-pagination">
                                    <li class="page-item disabled" id="elenco-domande-prev"><a class="page-link" href="#" tabindex="-1">‹ Prec.</a></li>
                                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                    <li class="page-item <?php echo $p === 1 ? 'active' : ''; ?>" data-page="<?php echo $p; ?>"><a class="page-link" href="#"><?php echo $p; ?></a></li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo $totalPages <= 1 ? 'disabled' : ''; ?>" id="elenco-domande-next"><a class="page-link" href="#">Succ. ›</a></li>
                                </ul>
                            </nav>
                            <?php endif; ?>
                            <?php if (empty($list)): ?>
                                <p class="p-3 text-muted mb-0 small">Nessuna domanda. Compila il form a sinistra e clicca Aggiungi domanda.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$scripts = ($scripts ?? '') . '<script>
$(function() {
    function toggleQuizDomandaAudioFields() {
        var isAudio = $("#quiz-domanda-tipo").val() === "audio";
        $("#quiz-domanda-audio-fields").toggle(isAudio);
    }
    $("#quiz-domanda-tipo").on("change", toggleQuizDomandaAudioFields);
    toggleQuizDomandaAudioFields();
    $(document).on("click", ".btn-quiz-domanda-delete", function(e) {
        e.preventDefault();
        var href = $(this).data("href");
        if (!href) return;
        if (typeof Swal === "undefined") {
            if (window.confirm("Eliminare questa domanda?")) {
                window.location.href = href;
            }
            return;
        }
        Swal.fire({
            title: "",
            text: "Eliminare questa domanda?",
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#48b461",
            confirmButtonText: "Conferma",
            cancelButtonText: "Annulla",
            reverseButtons: true
        }).then(function(result) {
            if (result.isConfirmed) {
                window.location.href = href;
            }
        });
    });
    (function elencoDomandePagination() {
        var $list = $("#elenco-domande-list");
        var $pagination = $("#elenco-domande-pagination");
        var $pageInfo = $("#elenco-domande-page-info");
        if (!$list.length || !$pagination.length) return;
        var totalPages = $pagination.find(".page-item[data-page]").length;
        var totalDomande = $list.find(".domanda-list-item").length;
        function goToPage(page) {
            page = Math.max(1, Math.min(page, totalPages));
            $list.find(".domanda-list-item").each(function() {
                $(this).toggle(parseInt($(this).data("page"), 10) === page);
            });
            $pagination.find(".page-item[data-page]").removeClass("active").each(function() {
                if (parseInt($(this).data("page"), 10) === page) $(this).addClass("active");
            });
            $("#elenco-domande-prev").toggleClass("disabled", page <= 1);
            $("#elenco-domande-next").toggleClass("disabled", page >= totalPages);
            if ($pageInfo.length) $pageInfo.text(totalDomande + " domande Â· pagina " + page + " di " + totalPages);
        }
        goToPage(1);
        $pagination.on("click", ".page-item[data-page] a", function(e) {
            e.preventDefault();
            if ($(this).closest(".page-item").hasClass("active")) return;
            goToPage(parseInt($(this).closest(".page-item").data("page"), 10));
        });
        $pagination.on("click", "#elenco-domande-prev a", function(e) {
            e.preventDefault();
            if ($(this).closest(".page-item").hasClass("disabled")) return;
            var current = $pagination.find(".page-item[data-page].active").data("page");
            goToPage(current - 1);
        });
        $pagination.on("click", "#elenco-domande-next a", function(e) {
            e.preventDefault();
            if ($(this).closest(".page-item").hasClass("disabled")) return;
            var current = $pagination.find(".page-item[data-page].active").data("page");
            goToPage(current + 1);
        });
    })();
});
</script>';
if (!$__quiz_concorso_embed) {
    include("_footer.php");
}
?>
