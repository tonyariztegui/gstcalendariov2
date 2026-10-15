<?php

/** @var PDO $connection */
/** @var callable $buildSecurityUrl */
/** @var string $domainSearch */
/** @var int $domainPage */
/** @var array $domainRows */
/** @var int $domainTotal */
/** @var int $domainTotalPages */
/** @var int $domainStart */
/** @var int $domainEnd */
?>
<div class="row mb-3">
    <div class="col-md-4">
        <div class="card border-left-warning shadow-sm h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Domini bloccati</div>
                <div class="h4 mb-0"><?php echo (int) $domainTotal; ?></div>
            </div>
        </div>
    </div>
</div>

<p class="text-muted mb-3">
    Blocca tutte le email di un dominio (es. <code>mailinator.com</code>). Utile contro email temporanee.
</p>

<div class="row">
    <div class="col-lg-5 mb-4">
        <div class="card border">
            <div class="card-body">
                <h5 class="card-title mb-3">Blocca un dominio</h5>
                <form method="post" action="security">
                    <input type="hidden" name="action" value="domain_add">
                    <input type="hidden" name="tab" value="domains">
                    <input type="hidden" name="domain_search" value="<?php echo htmlspecialchars($domainSearch, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="domain_page" value="<?php echo (int) $domainPage; ?>">
                    <div class="form-group">
                        <label for="block-domain">Dominio</label>
                        <input type="text" class="form-control" id="block-domain" name="domain" maxlength="253" required placeholder="es. tempmail.com">
                    </div>
                    <div class="form-group">
                        <label for="block-domain-note">Nota (opzionale)</label>
                        <input type="text" class="form-control" id="block-domain-note" name="note" maxlength="500">
                    </div>
                    <button type="submit" class="btn btn-warning text-dark">
                        <i class="fa fa-ban mr-1"></i> Aggiungi dominio
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7 mb-4">
        <form method="get" action="security" class="mb-3">
            <input type="hidden" name="tab" value="domains">
            <label class="sr-only" for="domain-blocklist-search">Cerca dominio o nota</label>
            <div class="input-group">
                <input type="search" class="form-control" id="domain-blocklist-search" name="domain_search" value="<?php echo htmlspecialchars($domainSearch, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Cerca dominio o nota...">
                <div class="input-group-append">
                    <button type="submit" class="btn btn-outline-primary">Cerca</button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-bordered mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Dominio</th>
                        <th>Nota</th>
                        <th>Data</th>
                        <th class="text-center" style="width: 90px;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($domainRows === []): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">Nessun dominio in blocklist.</td></tr>
                    <?php else: ?>
                        <?php foreach ($domainRows as $row): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($row['domain'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                <td><?php echo htmlspecialchars($row['note'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-center">
                                    <form method="post" action="security" class="d-inline security-remove-form" data-confirm="Rimuovere questo dominio dalla blocklist?">
                                        <input type="hidden" name="action" value="domain_remove">
                                        <input type="hidden" name="tab" value="domains">
                                        <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                        <input type="hidden" name="domain_search" value="<?php echo htmlspecialchars($domainSearch, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="domain_page" value="<?php echo (int) $domainPage; ?>">
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-security-remove"><i class="fa fa-unlock"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($domainTotal > 0 && $domainTotalPages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination pagination-sm mb-0">
                    <?php for ($p = max(1, $domainPage - 2); $p <= min($domainTotalPages, $domainPage + 2); $p++): ?>
                        <li class="page-item <?php echo $p === $domainPage ? 'active' : ''; ?>">
                            <a class="page-link" href="security?tab=domains&amp;domain_page=<?php echo (int) $p; ?><?php echo $domainSearch !== '' ? '&amp;domain_search=' . urlencode($domainSearch) : ''; ?>"><?php echo (int) $p; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>
