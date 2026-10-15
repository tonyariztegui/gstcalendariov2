<?php

/** @var PDO $connection */
/** @var string $ipSearch */
/** @var int $ipPage */
/** @var array $ipRows */
/** @var int $ipTotal */
/** @var int $ipTotalPages */
?>
<div class="row mb-3">
    <div class="col-md-4">
        <div class="card border-left-danger shadow-sm h-100">
            <div class="card-body py-3">
                <div class="text-muted small">IP bloccati</div>
                <div class="h4 mb-0"><?php echo (int) $ipTotal; ?></div>
            </div>
        </div>
    </div>
</div>

<p class="text-muted mb-3">
    Blocca manualmente un indirizzo IP identificato come abusivo. Le iscrizioni da questo IP saranno rifiutate.
</p>

<div class="row">
    <div class="col-lg-5 mb-4">
        <div class="card border">
            <div class="card-body">
                <h5 class="card-title mb-3">Blocca un IP</h5>
                <form method="post" action="security">
                    <input type="hidden" name="action" value="ip_add">
                    <input type="hidden" name="tab" value="ips">
                    <input type="hidden" name="ip_search" value="<?php echo htmlspecialchars($ipSearch, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="ip_page" value="<?php echo (int) $ipPage; ?>">
                    <div class="form-group">
                        <label for="block-ip">Indirizzo IP</label>
                        <input type="text" class="form-control" id="block-ip" name="ip_address" maxlength="45" required placeholder="es. 203.0.113.42">
                    </div>
                    <div class="form-group">
                        <label for="block-ip-note">Nota (opzionale)</label>
                        <input type="text" class="form-control" id="block-ip-note" name="note" maxlength="500">
                    </div>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa fa-ban mr-1"></i> Aggiungi IP
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7 mb-4">
        <form method="get" action="security" class="mb-3">
            <input type="hidden" name="tab" value="ips">
            <label class="sr-only" for="ip-blocklist-search">Cerca IP o nota</label>
            <div class="input-group">
                <input type="search" class="form-control" id="ip-blocklist-search" name="ip_search" value="<?php echo htmlspecialchars($ipSearch, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Cerca IP o nota...">
                <div class="input-group-append">
                    <button type="submit" class="btn btn-outline-primary">Cerca</button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-bordered mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>IP</th>
                        <th>Nota</th>
                        <th>Data</th>
                        <th class="text-center" style="width: 90px;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($ipRows === []): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">Nessun IP in blocklist.</td></tr>
                    <?php else: ?>
                        <?php foreach ($ipRows as $row): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($row['ip_address'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                <td><?php echo htmlspecialchars($row['note'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-center">
                                    <form method="post" action="security" class="d-inline security-remove-form" data-confirm="Rimuovere questo IP dalla blocklist?">
                                        <input type="hidden" name="action" value="ip_remove">
                                        <input type="hidden" name="tab" value="ips">
                                        <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                        <input type="hidden" name="ip_search" value="<?php echo htmlspecialchars($ipSearch, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="ip_page" value="<?php echo (int) $ipPage; ?>">
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-security-remove"><i class="fa fa-unlock"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
