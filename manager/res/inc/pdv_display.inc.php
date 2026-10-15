<?php

if (!function_exists('manager_normalize_pdv_row')) {
  function manager_normalize_pdv_row(array $row): array
  {
    $out = [];
    foreach ($row as $key => $value) {
      $out[strtolower((string) $key)] = $value;
    }
    if (!isset($out['cod'])) {
      $out['cod'] = trim((string) ($out['codice'] ?? ''));
    }

    return $out;
  }
}

if (!function_exists('manager_pdv_display_codice')) {
  function manager_pdv_display_codice(array $pdv): string
  {
    $pdv = manager_normalize_pdv_row($pdv);
    return trim((string) ($pdv['cod'] ?? ($pdv['codice'] ?? '')));
  }
}

if (!function_exists('manager_pdv_select_label')) {
  function manager_pdv_select_label(array $pdv): string
  {
    $pdv = manager_normalize_pdv_row($pdv);
    $nome = trim((string) ($pdv['ragsoc'] ?? ''));
    if ($nome === '') {
      $nome = trim((string) ($pdv['insegna'] ?? ''));
    }
    $addrBits = array_filter([
      trim((string) ($pdv['indirizzo'] ?? '')),
      trim((string) ($pdv['citta'] ?? '')),
      trim((string) ($pdv['cap'] ?? '')),
    ], static fn(string $v): bool => $v !== '');
    $line = $nome;
    if ($addrBits !== []) {
      $line = ($line !== '' ? $line . ' — ' : '') . implode(' - ', $addrBits);
    }
    $cod = trim((string) ($pdv['cod'] ?? ''));
    if ($cod !== '') {
      $line = ($line !== '' ? $line : $cod) . ' [' . $cod . ']';
    }

    return $line !== '' ? $line : '—';
  }
}

if (!function_exists('manager_render_pdv_cell_compact_html')) {
  function manager_render_pdv_cell_compact_html(array $row): string
  {
    $escape = static function (string $value): string {
      if (function_exists('manager_safe_html')) {
        return manager_safe_html($value);
      }

      return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    };

    $pdv = manager_normalize_pdv_row($row);
    $pdvNome = trim((string) ($pdv['ragsoc'] ?? ''));
    if ($pdvNome === '') {
      $pdvNome = trim((string) ($pdv['insegna'] ?? ''));
    }
    $addrBits = array_filter([
      trim((string) ($pdv['indirizzo'] ?? '')),
      trim((string) ($pdv['citta'] ?? '')),
      trim((string) ($pdv['cap'] ?? '')),
    ], static fn(string $v): bool => $v !== '');
    $pdvIndirizzoLine = implode(' - ', $addrBits);
    $pdvCod = manager_pdv_display_codice($pdv);
    $pdvHasAny = $pdvNome !== '' || $pdvIndirizzoLine !== '' || $pdvCod !== '';

    if (!$pdvHasAny) {
      return '<span class="text-muted small">—</span>';
    }

    $metaBits = [];
    if ($pdvIndirizzoLine !== '') {
      $metaBits[] = $pdvIndirizzoLine;
    }
    if ($pdvCod !== '') {
      $metaBits[] = 'Codice: ' . $pdvCod;
    }

    ob_start();
    ?>
    <div class="stats-pdv-pdv-compact">
      <?php if ($pdvNome !== ''): ?>
        <div class="stats-pdv-pdv-compact-name"><?php echo $escape($pdvNome); ?></div>
      <?php endif; ?>
      <?php if ($metaBits !== []): ?>
        <div class="stats-pdv-pdv-compact-meta"><?php echo $escape(implode(' · ', $metaBits)); ?></div>
      <?php endif; ?>
    </div>
    <?php

    return (string) ob_get_clean();
  }
}

if (!function_exists('manager_render_pdv_cell_html')) {
  /**
   * @param 'card'|'compact' $variant
   */
  function manager_render_pdv_cell_html(array $row, string $variant = 'card'): string
  {
    if ($variant === 'compact') {
      return manager_render_pdv_cell_compact_html($row);
    }
    $escape = static function (string $value): string {
      if (function_exists('manager_safe_html')) {
        return manager_safe_html($value);
      }

      return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    };

    $pdv = manager_normalize_pdv_row($row);
    $pdvNome = trim((string) ($pdv['ragsoc'] ?? ''));
    if ($pdvNome === '') {
      $pdvNome = trim((string) ($pdv['insegna'] ?? ''));
    }
    $addrBits = array_filter([
      trim((string) ($pdv['indirizzo'] ?? '')),
      trim((string) ($pdv['citta'] ?? '')),
      trim((string) ($pdv['cap'] ?? '')),
    ], static fn(string $v): bool => $v !== '');
    $pdvIndirizzoLine = implode(' - ', $addrBits);
    $pdvCod = manager_pdv_display_codice($pdv);
    $pdvHasAny = $pdvNome !== '' || $pdvIndirizzoLine !== '' || $pdvCod !== '';

    ob_start();
    ?>
    <div class="premi-pdv-wrap">
      <?php if ($pdvHasAny): ?>
        <div class="premi-pdv-box">
          <?php if ($pdvNome !== ''): ?>
            <span class="premi-pdv-name"><?php echo $escape($pdvNome); ?></span>
          <?php endif; ?>
          <?php if ($pdvIndirizzoLine !== ''): ?>
            <span class="premi-pdv-address"><?php echo $escape($pdvIndirizzoLine); ?></span>
          <?php endif; ?>
          <?php if ($pdvCod !== ''): ?>
            <span class="premi-pdv-codice">Codice: <?php echo $escape($pdvCod); ?></span>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="premi-pdv-box">
          <span class="text-muted small">—</span>
        </div>
      <?php endif; ?>
    </div>
    <?php

    return (string) ob_get_clean();
  }
}

if (!function_exists('manager_stats_pdv_table_styles')) {
  function manager_stats_pdv_table_styles(): string
  {
    return <<<'CSS'
.stats-pdv-pdv-table {
    table-layout: fixed;
    width: 100%;
}
.stats-pdv-pdv-table col.col-pdv { width: 46%; }
.stats-pdv-pdv-table col.col-part { width: 18%; }
.stats-pdv-pdv-table col.col-win { width: 14%; }
.stats-pdv-pdv-table col.col-rate { width: 22%; }
.stats-pdv-pdv-table td,
.stats-pdv-pdv-table th {
    vertical-align: middle;
}
.stats-pdv-pdv-table td:first-child,
.stats-pdv-pdv-table th:first-child {
    text-align: left;
}
.stats-pdv-pdv-compact {
    line-height: 1.35;
    max-width: 100%;
}
.stats-pdv-pdv-compact-name {
    font-weight: 700;
    color: #212529;
    font-size: 0.9rem;
}
.stats-pdv-pdv-compact-meta {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 0.15rem;
    word-break: break-word;
}
@media (max-width: 767.98px) {
    .stats-pdv-pdv-table col.col-pdv { width: 40%; }
    .stats-pdv-pdv-table col.col-part { width: 20%; }
    .stats-pdv-pdv-table col.col-win { width: 18%; }
    .stats-pdv-pdv-table col.col-rate { width: 22%; }
    .stats-pdv-pdv-compact-meta { font-size: 0.75rem; }
}
CSS;
  }
}

if (!function_exists('manager_pdv_cell_styles')) {
  function manager_pdv_cell_styles(): string
  {
    return <<<'CSS'
.premi-pdv-wrap {
    min-width: 200px;
    max-width: 300px;
}
.premi-pdv-box {
    background: #f1f3f5;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 0.65rem 0.75rem;
    text-align: center;
}
.premi-pdv-box .premi-pdv-name {
    font-weight: 700;
    color: #212529;
    font-size: 0.9rem;
    line-height: 1.25;
    margin-bottom: 0.4rem;
    display: block;
}
.premi-pdv-box .premi-pdv-address {
    font-size: 0.82rem;
    color: #495057;
    line-height: 1.35;
    margin-bottom: 0.35rem;
    display: block;
}
.premi-pdv-box .premi-pdv-codice {
    font-size: 0.8rem;
    color: #495057;
    margin-bottom: 0;
}
CSS;
  }
}
