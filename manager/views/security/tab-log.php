<?php

/** @var string $logSearch */
/** @var int $logPage */
/** @var array $logRows */
/** @var int $logTotal */
/** @var int $logTotalPages */
/** @var int $logStart */
/** @var int $logEnd */
?>
<p class="text-muted mb-3">
    Registro delle iscrizioni bloccate per motivi di sicurezza. Le email sono mascherate (es. <code>u***r@domain.com</code>).
</p>

<form method="get" action="security" class="mb-3">
    <input type="hidden" name="tab" value="log">
    <label class="sr-only" for="security-log-search">Cerca tipo, email, IP</label>
    <div class="input-group">
        <input type="search" class="form-control" id="security-log-search" name="log_search" value="<?php echo htmlspecialchars($logSearch, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Cerca tipo, email, IP...">
        <div class="input-group-append">
            <button type="submit" class="btn btn-outline-primary">Cerca</button>
            <?php if ($logSearch !== ''): ?>
                <a href="security?tab=log" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </div>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped table-bordered table-sm mb-0">
        <thead class="thead-light">
            <tr>
                <th>Data</th>
                <th>Tipo</th>
                <th>Email</th>
                <th>IP</th>
                <th>Dettaglio</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($logRows === []): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Nessun evento registrato.</td></tr>
            <?php else: ?>
                <?php foreach ($logRows as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars(contest_security_event_type_label($row['event_type']), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($row['email_masked'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($row['ip_address'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($row['detail'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($logTotal > 0): ?>
    <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
        <small class="text-muted">Visualizzati <?php echo (int) $logStart; ?>–<?php echo (int) $logEnd; ?> di <?php echo (int) $logTotal; ?></small>
        <?php if ($logTotalPages > 1): ?>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php for ($p = max(1, $logPage - 2); $p <= min($logTotalPages, $logPage + 2); $p++): ?>
                        <li class="page-item <?php echo $p === $logPage ? 'active' : ''; ?>">
                            <a class="page-link" href="security?tab=log&amp;log_page=<?php echo (int) $p; ?><?php echo $logSearch !== '' ? '&amp;log_search=' . urlencode($logSearch) : ''; ?>"><?php echo (int) $p; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
<?php endif; ?>
