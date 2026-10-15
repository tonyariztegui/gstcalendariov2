<?php

/**
 * Scheda Email bloccate (blacklist).
 * Variabili attese: $connection, $flash, $searchTerm, $page, $perPage, $rows, $totalRecords, $totalPages, $startRecord, $endRecord, $buildSecurityUrl
 */
?>
<div class="row mb-3">
    <div class="col-md-4">
        <div class="card border-left-primary shadow-sm h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Email bloccate</div>
                <div class="h4 mb-0"><?php echo (int) $totalRecords; ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-5 mb-4">
        <div class="card border">
            <div class="card-body">
                <h5 class="card-title mb-3">Blocca un indirizzo email</h5>
                <form method="post" action="security">
                    <input type="hidden" name="action" value="email_add">
                    <input type="hidden" name="tab" value="emails">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="page" value="<?php echo (int) $page; ?>">
                    <div class="form-group">
                        <label for="blacklist-email">Email</label>
                        <input type="email" class="form-control" id="blacklist-email" name="email" maxlength="254" required placeholder="es. utente@example.com">
                    </div>
                    <div class="form-group">
                        <label for="blacklist-note">Nota (opzionale)</label>
                        <input type="text" class="form-control" id="blacklist-note" name="note" maxlength="500" placeholder="Motivo del blocco">
                    </div>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa fa-ban mr-1"></i> Aggiungi alla blacklist
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7 mb-4">
        <form method="get" action="security" class="form-inline mb-3">
            <input type="hidden" name="tab" value="emails">
            <label class="sr-only" for="email-blacklist-search">Cerca email o nota</label>
            <div class="input-group w-100">
                <input type="search" class="form-control" id="email-blacklist-search" name="search" value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Cerca email o nota...">
                <div class="input-group-append">
                    <button type="submit" class="btn btn-outline-primary">Cerca</button>
                    <?php if ($searchTerm !== ''): ?>
                        <a href="<?php echo htmlspecialchars($buildSecurityUrl('emails'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-bordered mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Email</th>
                        <th>Nota</th>
                        <th>Data</th>
                        <th>Da</th>
                        <th class="text-center" style="width: 90px;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rows === []): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Nessuna email in blacklist.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($row['note'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($row['created_by'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-center">
                                    <form method="post" action="security" class="d-inline security-remove-form" data-confirm="Rimuovere questa email dalla blacklist?">
                                        <input type="hidden" name="action" value="email_remove">
                                        <input type="hidden" name="tab" value="emails">
                                        <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="page" value="<?php echo (int) $page; ?>">
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-security-remove" title="Sblocca"><i class="fa fa-unlock"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalRecords > 0): ?>
            <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
                <small class="text-muted">Visualizzati <?php echo (int) $startRecord; ?>–<?php echo (int) $endRecord; ?> di <?php echo (int) $totalRecords; ?></small>
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Paginazione emails">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo htmlspecialchars($buildSecurityUrl('emails', $page - 1, $searchTerm), ENT_QUOTES, 'UTF-8'); ?>">&laquo;</a>
                            </li>
                            <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                                <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo htmlspecialchars($buildSecurityUrl('emails', $p, $searchTerm), ENT_QUOTES, 'UTF-8'); ?>"><?php echo (int) $p; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo htmlspecialchars($buildSecurityUrl('emails', $page + 1, $searchTerm), ENT_QUOTES, 'UTF-8'); ?>">&raquo;</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
