<?php

declare(strict_types=1);

/**
 * Ordine configurabile dei campi standard (esclusi i consensi, sempre in coda).
 * CAP e Provincia condividono lo stesso slot (XOR).
 */

if (!function_exists('gst_standard_fields_consensi')) {
    /** @return list<string> */
    function gst_standard_fields_consensi(): array
    {
        return ['privacy', 'marketing', 'profilazione'];
    }
}

if (!function_exists('gst_standard_fields_default_order')) {
    /**
     * Ordine predefinito senza consensi. Slot CAP/Provincia = "cap_provincia".
     *
     * @return list<string>
     */
    function gst_standard_fields_default_order(): array
    {
        return [
            'nome',
            'cognome',
            'email',
            'prefisso_cellulare',
            'cap_provincia',
            'citta',
            'indirizzo',
            'fidelity',
            'indirizzo_pdv',
            'importo',
            'data_scontrino',
            'scontrino',
            'codice_partecipazione',
        ];
    }
}

if (!function_exists('gst_standard_fields_order_labels')) {
    /** @return array<string, string> */
    function gst_standard_fields_order_labels(): array
    {
        return [
            'nome' => 'Nome',
            'cognome' => 'Cognome',
            'email' => 'Email',
            'prefisso_cellulare' => 'Prefisso / Cellulare',
            'cap_provincia' => 'CAP / Provincia',
            'citta' => 'Città',
            'indirizzo' => 'Indirizzo',
            'fidelity' => 'Codice fidelity',
            'indirizzo_pdv' => 'Punto vendita',
            'importo' => 'Importo scontrino',
            'data_scontrino' => 'Data scontrino',
            'scontrino' => 'Foto scontrino',
            'codice_partecipazione' => 'Codice partecipazione',
            'privacy' => 'Privacy',
            'marketing' => 'Marketing',
            'profilazione' => 'Profilazione',
        ];
    }
}

if (!function_exists('gst_standard_fields_legacy_position_names')) {
    /**
     * Mappa posizioni storiche (pre-reorder) → nome campo, per migrare position_order numerici.
     *
     * @return array<int, string>
     */
    function gst_standard_fields_legacy_position_names(): array
    {
        return [
            1 => 'nome',
            2 => 'cognome',
            3 => 'email',
            4 => 'prefisso_cellulare',
            5 => 'cap',
            6 => 'citta',
            7 => 'indirizzo',
            8 => 'fidelity',
            9 => 'indirizzo_pdv',
            10 => 'importo',
            11 => 'data_scontrino',
            12 => 'scontrino',
            13 => 'codice_partecipazione',
            14 => 'privacy',
            15 => 'marketing',
            16 => 'profilazione',
        ];
    }
}

if (!function_exists('gst_standard_fields_normalize_order')) {
    /**
     * Valida e completa l'ordine (senza consensi). Accetta "cap"/"provincia" → "cap_provincia".
     *
     * @param mixed $order
     * @return list<string>
     */
    function gst_standard_fields_normalize_order($order): array
    {
        $allowed = gst_standard_fields_default_order();
        $allowedFlip = array_flip($allowed);
        $consensi = array_flip(gst_standard_fields_consensi());
        $out = [];
        $seen = [];

        if (!is_array($order)) {
            return $allowed;
        }

        foreach ($order as $raw) {
            $key = strtolower(trim((string) $raw));
            if ($key === '' || isset($consensi[$key])) {
                continue;
            }
            if ($key === 'cap' || $key === 'provincia') {
                $key = 'cap_provincia';
            }
            if ($key === 'cellulare' || $key === 'prefisso') {
                $key = 'prefisso_cellulare';
            }
            if (!isset($allowedFlip[$key]) || isset($seen[$key])) {
                continue;
            }
            $out[] = $key;
            $seen[$key] = true;
        }

        foreach ($allowed as $key) {
            if (!isset($seen[$key])) {
                $out[] = $key;
            }
        }

        return $out;
    }
}

if (!function_exists('gst_apply_standard_fields_order')) {
    /**
     * Riassegna le position sulla mappa standard. Consensi sempre in coda.
     *
     * @param array<string, array<string, mixed>> $standard_fields_map
     * @param mixed $order
     * @return array<string, array<string, mixed>>
     */
    function gst_apply_standard_fields_order(array $standard_fields_map, $order): array
    {
        $normalized = gst_standard_fields_normalize_order($order);
        $posByName = [];
        $pos = 1;

        foreach ($normalized as $key) {
            if ($key === 'cap_provincia') {
                $posByName['cap'] = $pos;
                $posByName['provincia'] = $pos;
                $pos++;
                continue;
            }
            $posByName[$key] = $pos;
            $pos++;
        }

        foreach (gst_standard_fields_consensi() as $consenso) {
            $posByName[$consenso] = $pos;
            $pos++;
        }

        foreach ($standard_fields_map as $letter => $info) {
            $name = (string) ($info['name'] ?? '');
            if ($name !== '' && isset($posByName[$name])) {
                $standard_fields_map[$letter]['position'] = $posByName[$name];
            }
        }

        return $standard_fields_map;
    }
}

if (!function_exists('gst_standard_fields_position_after_to_order')) {
    /**
     * Costruisce la mappa legacy position_after → position_order (dopo il campo = position + 1).
     *
     * @param array<string, array<string, mixed>> $standard_fields_map
     * @return array<string, int>
     */
    function gst_standard_fields_position_after_to_order(array $standard_fields_map): array
    {
        $byName = [];
        foreach ($standard_fields_map as $info) {
            $name = (string) ($info['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $byName[$name] = (int) ($info['position'] ?? 0) + 1;
        }
        if (isset($byName['prefisso_cellulare'])) {
            $byName['cellulare'] = $byName['prefisso_cellulare'];
        }
        return $byName;
    }
}

if (!function_exists('gst_migrate_numeric_position_order')) {
    /**
     * Converte un position_order numerico legacy in before_s_* / after_s_* (mappa storica fissa).
     *
     * @param mixed $value
     * @return mixed
     */
    function gst_migrate_numeric_position_order($value)
    {
        if (!is_numeric($value)) {
            return $value;
        }
        $n = (int) $value;
        if ($n < 1) {
            return $value;
        }
        $legacy = gst_standard_fields_legacy_position_names();
        if (isset($legacy[$n])) {
            return 'before_s_' . $legacy[$n];
        }
        if (isset($legacy[$n - 1])) {
            return 'after_s_' . $legacy[$n - 1];
        }
        return 'after_s_fidelity';
    }
}

if (!function_exists('gst_migrate_custom_fields_position_orders')) {
    /**
     * @param array<int, mixed> $custom_fields
     * @return array<int, mixed>
     */
    function gst_migrate_custom_fields_position_orders(array $custom_fields): array
    {
        foreach ($custom_fields as $i => $field) {
            if (!is_array($field) || !array_key_exists('position_order', $field)) {
                continue;
            }
            $migrated = gst_migrate_numeric_position_order($field['position_order']);
            if ($migrated !== $field['position_order']) {
                $custom_fields[$i]['position_order'] = $migrated;
            }
        }
        return $custom_fields;
    }
}

if (!function_exists('gst_standard_field_position_by_name')) {
    /**
     * @param array<string, array<string, mixed>> $standard_fields_map
     */
    function gst_standard_field_position_by_name(array $standard_fields_map, string $name): ?int
    {
        foreach ($standard_fields_map as $info) {
            if (($info['name'] ?? '') === $name) {
                return (int) ($info['position'] ?? 0);
            }
        }
        return null;
    }
}
