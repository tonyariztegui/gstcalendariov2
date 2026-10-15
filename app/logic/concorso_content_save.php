<?php

/**
 * Sauvegarde Concorso: $newConfig part des defaults; certaines clés ne sont pas dans le POST.
 */

if (!function_exists('concorso_content_keys_preserved_from_current')) {
    /**
     * Flags gérés hors du form Concorso (es. Sicurezza). Non presenti in POST → non azzerare.
     *
     * @return list<string>
     */
    function concorso_content_keys_preserved_from_current(): array
    {
        return [
            'codice_partecipazione_security_enabled',
        ];
    }
}

if (!function_exists('concorso_apply_keys_preserved_from_current')) {
    /**
     * @param array<string, mixed> $newConfig
     * @param array<string, mixed> $contentCurrent
     * @return array<string, mixed>
     */
    function concorso_apply_keys_preserved_from_current(array $newConfig, array $contentCurrent): array
    {
        foreach (concorso_content_keys_preserved_from_current() as $key) {
            if (array_key_exists($key, $contentCurrent)) {
                $newConfig[$key] = $contentCurrent[$key];
            }
        }

        return $newConfig;
    }
}

if (!function_exists('concorso_preserve_cedi_schema_from_current')) {
    /**
     * Schema Ce.Di. solo in POST se distribuzione = cedi; altrimenti tieni il current.
     *
     * @param array<string, mixed> $newConfig
     * @param array<string, mixed> $contentCurrent
     * @param array<string, mixed> $contentDefaults
     * @return array<string, mixed>
     */
    function concorso_preserve_cedi_schema_from_current(array $newConfig, array $contentCurrent, array $contentDefaults): array
    {
        foreach (['cedi_definitions', 'cedi_prize_values', 'cedi_prize_definitions'] as $key) {
            $fallback = $contentDefaults[$key] ?? [];
            $newConfig[$key] = is_array($contentCurrent[$key] ?? null) ? $contentCurrent[$key] : $fallback;
        }

        return $newConfig;
    }
}

if (!function_exists('concorso_preserve_premi_matrix_from_current')) {
    /**
     * @param array<string, mixed> $newConfig
     * @param array<string, mixed> $contentCurrent
     * @return array<string, mixed>
     */
    function concorso_preserve_premi_matrix_from_current(array $newConfig, array $contentCurrent): array
    {
        $newConfig['premi_matrix'] = is_array($contentCurrent['premi_matrix'] ?? null)
            ? $contentCurrent['premi_matrix']
            : [];
        $newConfig['premi_matrix_weeks_custom'] = !empty($contentCurrent['premi_matrix_weeks_custom']);

        return $newConfig;
    }
}

if (!function_exists('concorso_content_unassigned_default_keys')) {
    /**
     * Chiavi top-level dei defaults mai assegnate a $newConfig['…'] nel save Concorso.
     *
     * @param array<string, mixed> $defaults
     * @return list<string>
     */
    function concorso_content_unassigned_default_keys(string $concorsoPhpSource, array $defaults): array
    {
        preg_match_all("/\\\$newConfig\\['([a-zA-Z0-9_]+)'\\]/", $concorsoPhpSource, $matches);
        $assigned = array_unique($matches[1] ?? []);
        $assigned = array_merge($assigned, concorso_content_keys_preserved_from_current());
        $assigned = array_unique($assigned);
        $missing = [];
        foreach (array_keys($defaults) as $key) {
            if (!is_string($key) || str_starts_with($key, '_comment')) {
                continue;
            }
            if (!in_array($key, $assigned, true)) {
                $missing[] = $key;
            }
        }

        return $missing;
    }
}
