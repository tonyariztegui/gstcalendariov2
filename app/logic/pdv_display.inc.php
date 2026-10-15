<?php
/**
 * Formattazione label punto vendita (stesse regole Form & campi / pdv_config).
 * Usato dagli email placeholder {{PDV}}.
 */

if (!function_exists('pdv_resolve_label_field_value')) {
    /**
     * @param array<string, mixed> $pdvRow
     */
    function pdv_resolve_label_field_value(string $key, array $pdvRow): string
    {
        $ragsoc = trim((string) ($pdvRow['ragsoc'] ?? ''));
        $insegna = trim((string) ($pdvRow['insegna'] ?? ''));
        return match ($key) {
            'ragsoc' => $ragsoc !== '' ? $ragsoc : $insegna,
            'insegna' => $insegna !== '' ? $insegna : $ragsoc,
            'indirizzo' => trim((string) ($pdvRow['indirizzo'] ?? '')),
            'citta' => trim((string) ($pdvRow['citta'] ?? '')),
            'prov' => trim((string) ($pdvRow['prov'] ?? '')),
            'regione' => trim((string) ($pdvRow['regione'] ?? '')),
            'cap' => trim((string) ($pdvRow['cap'] ?? '')),
            default => '',
        };
    }
}

if (!function_exists('pdv_format_display_label')) {
    /**
     * @param array<string, mixed> $pdvRow
     * @param array<string, mixed>|null $pdvConfig
     */
    function pdv_format_display_label(array $pdvRow, ?array $pdvConfig = null): string
    {
        if ($pdvConfig === null) {
            global $contest_config, $content_config, $content_defaults;
            $pdvConfig = [];
            if (is_array($contest_config ?? null) && isset($contest_config['pdv_config']) && is_array($contest_config['pdv_config'])) {
                $pdvConfig = $contest_config['pdv_config'];
            } elseif (is_array($content_config ?? null) && isset($content_config['pdv_config']) && is_array($content_config['pdv_config'])) {
                $pdvConfig = $content_config['pdv_config'];
            } elseif (is_array($content_defaults ?? null) && isset($content_defaults['pdv_config']) && is_array($content_defaults['pdv_config'])) {
                $pdvConfig = $content_defaults['pdv_config'];
            }
        }

        $mode = strtolower(trim((string) ($pdvConfig['mode'] ?? 'combinato')));
        $ragsoc = trim((string) ($pdvRow['ragsoc'] ?? ''));
        $insegna = trim((string) ($pdvRow['insegna'] ?? ''));
        $indirizzo = trim((string) ($pdvRow['indirizzo'] ?? ''));
        $storeName = $ragsoc !== '' ? $ragsoc : $insegna;

        if ($mode === 'cascata') {
            $cascataDisplay = strtolower(trim((string) ($pdvConfig['cascata']['punto_vendita_display'] ?? 'insegna_indirizzo')));
            if ($cascataDisplay === 'insegna_indirizzo' && $indirizzo !== '') {
                return ($storeName !== '' ? $storeName : 'PDV') . ' - ' . $indirizzo;
            }
            if ($cascataDisplay === 'indirizzo_insegna' && $storeName !== '') {
                return ($indirizzo !== '' ? $indirizzo . ' - ' : '') . $storeName;
            }
            // indirizzo_only (ou fallback)
            if ($indirizzo !== '') {
                return $indirizzo;
            }
            return $storeName !== '' ? $storeName : '';
        }

        // combinato
        $labelFields = $pdvConfig['combinato']['label_fields'] ?? ['ragsoc', 'indirizzo', 'citta', 'prov'];
        if (!is_array($labelFields) || $labelFields === []) {
            $labelFields = ['ragsoc', 'indirizzo', 'citta', 'prov'];
        }
        $separator = (string) ($pdvConfig['combinato']['separator'] ?? ' - ');
        $parts = [];
        foreach ($labelFields as $field) {
            $v = pdv_resolve_label_field_value((string) $field, $pdvRow);
            if ($v !== '') {
                $parts[] = $v;
            }
        }
        $display = implode($separator, $parts);
        if ($display === '') {
            $display = $storeName !== '' ? $storeName : $indirizzo;
        }
        return $display;
    }
}

if (!function_exists('pdv_lookup_by_codice')) {
    /**
     * @return array<string, mixed>|null
     */
    function pdv_lookup_by_codice(PDO $pdo, string $codice): ?array
    {
        $codice = trim($codice);
        if ($codice === '') {
            return null;
        }
        try {
            $stmt = $pdo->prepare(
                'SELECT id, codice, ragsoc, insegna, indirizzo, citta, prov, regione, cap
                 FROM punti_vendita
                 WHERE codice = ?
                 LIMIT 1'
            );
            $stmt->execute([$codice]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            error_log('pdv_lookup_by_codice: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('pdv_resolve_email_label')) {
    /**
     * Risolve la label PDV per gli email a partire dal codice partecipazione (id_pdv_concorso).
     */
    function pdv_resolve_email_label(?string $codice, ?string $insegnaFallback = null): string
    {
        $codice = trim((string) $codice);
        $fallback = trim((string) $insegnaFallback);

        if ($codice !== '') {
            try {
                if (!function_exists('conn_pdo')) {
                    require_once dirname(__DIR__) . '/inc/functions.php';
                }
                $pdo = conn_pdo();
                if ($pdo instanceof PDO) {
                    $row = pdv_lookup_by_codice($pdo, $codice);
                    if (is_array($row)) {
                        $label = pdv_format_display_label($row);
                        if ($label !== '') {
                            return $label;
                        }
                    }
                }
            } catch (Throwable $e) {
                error_log('pdv_resolve_email_label: ' . $e->getMessage());
            }
        }

        return $fallback;
    }
}
