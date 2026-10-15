<?php

/**
 * Lettura / scrittura variabili SMTP_* nel file .env (manager Configurazione).
 * I segreti non vanno in content.json.
 */

require_once __DIR__ . '/instantwin/manager_env.php';

if (!function_exists('smtp_env_schema')) {
    /**
     * @return list<array{key:string,label:string,hint:string,type:string,secret?:bool,placeholder?:string}>
     */
    function smtp_env_schema(): array
    {
        return [
            [
                'key' => 'SMTP_HOST',
                'label' => 'SMTP host',
                'hint' => 'Hostname del server SMTP (es. smtp.dominio.it). Salvato solo nel file .env.',
                'type' => 'text',
                'placeholder' => 'es. smtp.server.tld',
            ],
            [
                'key' => 'SMTP_PORT',
                'label' => 'SMTP port',
                'hint' => 'Porta SMTP. 587 = STARTTLS, 465 = SMTPS. Salvato solo nel file .env.',
                'type' => 'number',
                'placeholder' => '587',
            ],
            [
                'key' => 'SMTP_USERNAME',
                'label' => 'Username SMTP',
                'hint' => 'Utente di autenticazione SMTP. Salvato solo nel file .env.',
                'type' => 'text',
                'placeholder' => 'es. user_smtp',
            ],
            [
                'key' => 'SMTP_PASSWORD',
                'label' => 'Password SMTP',
                'hint' => 'Password SMTP. Non viene mostrata dopo il salvataggio. Salvata solo nel file .env.',
                'type' => 'password',
                'secret' => true,
                'placeholder' => '',
            ],
            [
                'key' => 'SMTP_FROM_EMAIL',
                'label' => 'Mittente email',
                'hint' => 'Indirizzo From delle email del concorso. Salvato solo nel file .env.',
                'type' => 'email',
                'placeholder' => 'es. no-reply@dominio.it',
            ],
            [
                'key' => 'SMTP_FROM_NAME',
                'label' => 'Nome mittente',
                'hint' => 'Nome From visibile dal destinatario. Salvato solo nel file .env.',
                'type' => 'text',
                'placeholder' => 'es. Team Concorso',
            ],
        ];
    }
}

if (!function_exists('smtp_env_keys')) {
    /**
     * @return list<string>
     */
    function smtp_env_keys(): array
    {
        return array_values(array_map(static function (array $f): string {
            return (string) $f['key'];
        }, smtp_env_schema()));
    }
}

if (!function_exists('smtp_env_json_key_map')) {
    /**
     * Chiavi .env → chiavi legacy in content.json.
     *
     * @return array<string, string>
     */
    function smtp_env_json_key_map(): array
    {
        return [
            'SMTP_HOST' => 'smtp_host',
            'SMTP_PORT' => 'smtp_port',
            'SMTP_USERNAME' => 'smtp_username',
            'SMTP_PASSWORD' => 'smtp_password',
            'SMTP_FROM_EMAIL' => 'smtp_from_email',
            'SMTP_FROM_NAME' => 'smtp_from_name',
        ];
    }
}

if (!function_exists('smtp_env_blank_json_smtp')) {
    /**
     * @return array<string, mixed>
     */
    function smtp_env_blank_json_smtp(): array
    {
        return [
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_from_email' => '',
            'smtp_from_name' => '',
            'smtp_reply_to' => '',
        ];
    }
}

if (!function_exists('smtp_env_strip_json_secrets')) {
    /**
     * @param array<string, mixed> $content
     * @return array<string, mixed>
     */
    function smtp_env_strip_json_secrets(array $content): array
    {
        return array_merge($content, smtp_env_blank_json_smtp());
    }
}

if (!function_exists('smtp_env_json_has_smtp_secrets')) {
    /**
     * @param array<string, mixed> $content
     */
    function smtp_env_json_has_smtp_secrets(array $content): bool
    {
        foreach (['smtp_host', 'smtp_username', 'smtp_password', 'smtp_from_email', 'smtp_from_name'] as $key) {
            if (trim((string) ($content[$key] ?? '')) !== '') {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('smtp_env_values_from_json')) {
    /**
     * @param array<string, mixed> $content
     * @return array<string, string>
     */
    function smtp_env_values_from_json(array $content): array
    {
        $out = [];
        foreach (smtp_env_json_key_map() as $envKey => $jsonKey) {
            $raw = $content[$jsonKey] ?? '';
            $out[$envKey] = is_scalar($raw) ? trim((string) $raw) : '';
        }
        return $out;
    }
}

if (!function_exists('smtp_env_values_from_env_only')) {
    /**
     * @return array<string, string>
     */
    function smtp_env_values_from_env_only(): array
    {
        $parsed = instantwin_manager_env_parse_file();
        $values = [];
        foreach (smtp_env_keys() as $key) {
            $v = '';
            if (array_key_exists($key, $parsed)) {
                $v = trim((string) $parsed[$key]);
            } else {
                $g = getenv($key);
                if (is_string($g) && $g !== '') {
                    $v = trim($g);
                }
            }
            $values[$key] = $v;
        }
        return $values;
    }
}

if (!function_exists('smtp_env_has_transport')) {
    /**
     * Host + username + password presenti nel .env (non nel JSON).
     */
    function smtp_env_has_transport(): bool
    {
        $v = smtp_env_values_from_env_only();
        return ($v['SMTP_HOST'] ?? '') !== ''
            && ($v['SMTP_USERNAME'] ?? '') !== ''
            && ($v['SMTP_PASSWORD'] ?? '') !== '';
    }
}

if (!function_exists('smtp_env_values_for_form')) {
    /**
     * Valori per il form: .env, poi fallback JSON (migrazione). I secret non vanno in HTML.
     *
     * @param array<string, mixed> $jsonFallback
     * @return array{values: array<string, string>, set: array<string, bool>}
     */
    function smtp_env_values_for_form(array $jsonFallback = []): array
    {
        $fromEnv = smtp_env_values_from_env_only();
        $fromJson = smtp_env_values_from_json($jsonFallback);
        $values = [];
        $set = [];
        foreach (smtp_env_keys() as $key) {
            $v = $fromEnv[$key] ?? '';
            if ($v === '' && ($fromJson[$key] ?? '') !== '') {
                $v = $fromJson[$key];
            }
            $values[$key] = $v;
            $set[$key] = $v !== '';
        }
        return ['values' => $values, 'set' => $set];
    }
}

if (!function_exists('smtp_env_validate_submission')) {
    /**
     * @param array<string, mixed> $in
     * @param array<string, string> $existing
     * @return array{ok:bool,message?:string,values?:array<string,string>}
     */
    function smtp_env_validate_submission(array $in, array $existing = []): array
    {
        $out = [];
        foreach (smtp_env_schema() as $field) {
            $key = (string) $field['key'];
            $isSecret = !empty($field['secret']);
            $raw = trim((string) ($in[$key] ?? ''));

            if ($isSecret && $raw === '') {
                $out[$key] = (isset($existing[$key]) && $existing[$key] !== '')
                    ? (string) $existing[$key]
                    : '';
                continue;
            }

            if (strpbrk($raw, "\r\n#") !== false) {
                return ['ok' => false, 'message' => 'Valore non valido per ' . $key . ' (caratteri non ammessi).'];
            }

            if ($key === 'SMTP_PORT') {
                if ($raw === '') {
                    $raw = '587';
                }
                if (!ctype_digit($raw) || (int) $raw <= 0) {
                    return ['ok' => false, 'message' => 'La porta SMTP deve essere un numero positivo.'];
                }
                $out[$key] = (string) (int) $raw;
                continue;
            }

            if ($key === 'SMTP_FROM_EMAIL' && $raw !== '' && !filter_var($raw, FILTER_VALIDATE_EMAIL)) {
                return ['ok' => false, 'message' => 'Mittente email SMTP non valido.'];
            }

            $out[$key] = $raw;
        }

        $host = $out['SMTP_HOST'] ?? '';
        $user = $out['SMTP_USERNAME'] ?? '';
        $pass = $out['SMTP_PASSWORD'] ?? '';
        if ($host === '' && ($user !== '' || $pass !== '')) {
            return ['ok' => false, 'message' => 'Specificare un host SMTP quando sono indicati username o password.'];
        }

        return ['ok' => true, 'values' => $out];
    }
}

if (!function_exists('smtp_env_persist_stripped_content_json')) {
    /**
     * Svuota smtp_* in content.json se il file è scrivibile.
     *
     * @param array<string, mixed>|null $content
     */
    function smtp_env_persist_stripped_content_json(string $contentPath, ?array $content = null): bool
    {
        if ($content === null) {
            if (!is_readable($contentPath)) {
                return false;
            }
            $raw = file_get_contents($contentPath);
            if ($raw === false || $raw === '') {
                return false;
            }
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                return false;
            }
            $content = $decoded;
        }
        if (!smtp_env_json_has_smtp_secrets($content)) {
            return true;
        }
        if (!is_writable($contentPath)) {
            return false;
        }
        $stripped = smtp_env_strip_json_secrets($content);
        $json = json_encode($stripped, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($json) || $json === '') {
            return false;
        }
        return file_put_contents($contentPath, $json . "\n", LOCK_EX) !== false;
    }
}

if (!function_exists('smtp_env_maybe_migrate_json')) {
    /**
     * Copia SMTP da content.json verso .env se .env è vuoto, poi svuota i segreti JSON.
     *
     * @param array<string, mixed> $content
     * @return array<string, mixed>
     */
    function smtp_env_maybe_migrate_json(array $content, string $contentPath): array
    {
        $existing = smtp_env_values_from_env_only();
        $fromJson = smtp_env_values_from_json($content);
        $toWrite = [];
        foreach (smtp_env_keys() as $key) {
            if (($existing[$key] ?? '') === '' && ($fromJson[$key] ?? '') !== '') {
                $toWrite[$key] = $fromJson[$key];
            }
        }
        $wrote = false;
        if ($toWrite !== []) {
            $wrote = instantwin_manager_env_write($toWrite);
            if ($wrote) {
                foreach ($toWrite as $k => $v) {
                    instantwin_manager_env_apply_runtime((string) $k, (string) $v);
                }
            }
        }
        if (($wrote || smtp_env_has_transport()) && smtp_env_json_has_smtp_secrets($content)) {
            $stripped = smtp_env_strip_json_secrets($content);
            if (smtp_env_persist_stripped_content_json($contentPath, $content)) {
                return $stripped;
            }
        }
        return $content;
    }
}

if (!function_exists('smtp_env_profile_ids')) {
    /**
     * @return list<string>
     */
    function smtp_env_profile_ids(): array
    {
        return ['general', 'aruba', 'sendgrid'];
    }
}

if (!function_exists('smtp_env_profile_labels')) {
    /**
     * @return array<string, string>
     */
    function smtp_env_profile_labels(): array
    {
        return [
            'general' => 'Generale',
            'aruba' => 'Aruba',
            'sendgrid' => 'SendGrid',
        ];
    }
}

if (!function_exists('smtp_env_normalize_profile_id')) {
    function smtp_env_normalize_profile_id(string $profile): string
    {
        $profile = strtolower(trim($profile));
        return in_array($profile, smtp_env_profile_ids(), true) ? $profile : 'general';
    }
}

if (!function_exists('smtp_env_profile_prefix')) {
    function smtp_env_profile_prefix(string $profile): string
    {
        $profile = smtp_env_normalize_profile_id($profile);
        return 'SMTP_' . strtoupper($profile);
    }
}

if (!function_exists('smtp_env_profile_field_suffixes')) {
    /**
     * SMTP_HOST → HOST, etc.
     *
     * @return list<string>
     */
    function smtp_env_profile_field_suffixes(): array
    {
        return ['HOST', 'PORT', 'USERNAME', 'PASSWORD', 'FROM_EMAIL', 'FROM_NAME'];
    }
}

if (!function_exists('smtp_env_active_key_for_suffix')) {
    function smtp_env_active_key_for_suffix(string $suffix): string
    {
        return 'SMTP_' . strtoupper($suffix);
    }
}

if (!function_exists('smtp_env_profile_env_key')) {
    function smtp_env_profile_env_key(string $profile, string $suffix): string
    {
        return smtp_env_profile_prefix($profile) . '_' . strtoupper($suffix);
    }
}

if (!function_exists('smtp_env_profile_defaults')) {
    /**
     * Defaults host/port (e username hint SendGrid). Nessuna password in chiaro.
     *
     * @return array<string, string> chiavi SMTP_* attive
     */
    function smtp_env_profile_defaults(string $profile): array
    {
        $profile = smtp_env_normalize_profile_id($profile);
        $blank = [
            'SMTP_HOST' => '',
            'SMTP_PORT' => '587',
            'SMTP_USERNAME' => '',
            'SMTP_PASSWORD' => '',
            'SMTP_FROM_EMAIL' => '',
            'SMTP_FROM_NAME' => '',
        ];
        return match ($profile) {
            'aruba' => array_merge($blank, [
                'SMTP_HOST' => 'smtps.aruba.it',
                'SMTP_PORT' => '587',
            ]),
            'sendgrid' => array_merge($blank, [
                'SMTP_HOST' => 'smtp.sendgrid.net',
                'SMTP_PORT' => '587',
                'SMTP_USERNAME' => 'apikey',
            ]),
            default => $blank,
        };
    }
}

if (!function_exists('smtp_env_read_env_key')) {
    function smtp_env_read_env_key(string $key, ?array $parsed = null): string
    {
        if ($parsed === null) {
            $parsed = instantwin_manager_env_parse_file();
        }
        if (array_key_exists($key, $parsed)) {
            return trim((string) $parsed[$key]);
        }
        $g = getenv($key);
        if (is_string($g) && $g !== '') {
            return trim($g);
        }
        return '';
    }
}

if (!function_exists('smtp_env_active_profile')) {
    function smtp_env_active_profile(?array $parsed = null): string
    {
        return smtp_env_normalize_profile_id(smtp_env_read_env_key('SMTP_PROFILE', $parsed));
    }
}

if (!function_exists('smtp_env_read_profile_values')) {
    /**
     * Valori SMTP_* per un profilo (da chiavi prefissate + defaults se host/port vuoti).
     *
     * @return array{values: array<string, string>, set: array<string, bool>, has_saved: bool}
     */
    function smtp_env_read_profile_values(string $profile, ?array $parsed = null): array
    {
        $profile = smtp_env_normalize_profile_id($profile);
        if ($parsed === null) {
            $parsed = instantwin_manager_env_parse_file();
        }
        $defaults = smtp_env_profile_defaults($profile);
        $values = [];
        $set = [];
        $hasSaved = false;
        foreach (smtp_env_profile_field_suffixes() as $suffix) {
            $activeKey = smtp_env_active_key_for_suffix($suffix);
            $profileKey = smtp_env_profile_env_key($profile, $suffix);
            $raw = smtp_env_read_env_key($profileKey, $parsed);
            if ($raw !== '') {
                $hasSaved = true;
            }
            if ($raw === '' && in_array($suffix, ['HOST', 'PORT'], true)) {
                $raw = (string) ($defaults[$activeKey] ?? '');
            }
            if ($raw === '' && $suffix === 'USERNAME' && $profile === 'sendgrid') {
                $raw = (string) ($defaults[$activeKey] ?? '');
            }
            $values[$activeKey] = $raw;
            $set[$activeKey] = smtp_env_read_env_key($profileKey, $parsed) !== '';
        }
        // Password: set flag from stored profile key only; never expose value in UI
        if (!empty($set['SMTP_PASSWORD'])) {
            $values['SMTP_PASSWORD'] = smtp_env_read_env_key(smtp_env_profile_env_key($profile, 'PASSWORD'), $parsed);
        }

        return ['values' => $values, 'set' => $set, 'has_saved' => $hasSaved];
    }
}

if (!function_exists('smtp_env_profiles_bundle_for_ui')) {
    /**
     * Dati per select + JS (password mai inviata al browser, solo flag).
     *
     * @return array{
     *   active_profile: string,
     *   labels: array<string,string>,
     *   profiles: array<string, array{values: array<string,string>, password_set: bool, has_saved: bool}>
     * }
     */
    function smtp_env_profiles_bundle_for_ui(?array $parsed = null): array
    {
        if ($parsed === null) {
            $parsed = instantwin_manager_env_parse_file();
        }
        $profiles = [];
        foreach (smtp_env_profile_ids() as $profileId) {
            $bundle = smtp_env_read_profile_values($profileId, $parsed);
            $safeValues = $bundle['values'];
            $safeValues['SMTP_PASSWORD'] = '';
            $profiles[$profileId] = [
                'values' => $safeValues,
                'password_set' => !empty($bundle['set']['SMTP_PASSWORD']),
                'has_saved' => !empty($bundle['has_saved']),
            ];
        }

        return [
            'active_profile' => smtp_env_active_profile($parsed),
            'labels' => smtp_env_profile_labels(),
            'profiles' => $profiles,
        ];
    }
}

if (!function_exists('smtp_env_migrate_active_into_general_if_needed')) {
    /**
     * Se SMTP_* attivo esiste e SMTP_GENERAL_* è vuoto, copia in general e setta SMTP_PROFILE.
     */
    function smtp_env_migrate_active_into_general_if_needed(): void
    {
        $parsed = instantwin_manager_env_parse_file();
        $active = smtp_env_values_from_env_only();
        $hasActive = ($active['SMTP_HOST'] ?? '') !== ''
            || ($active['SMTP_USERNAME'] ?? '') !== ''
            || ($active['SMTP_FROM_EMAIL'] ?? '') !== '';
        if (!$hasActive) {
            return;
        }

        $generalHostKey = smtp_env_profile_env_key('general', 'HOST');
        $generalAlready = smtp_env_read_env_key($generalHostKey, $parsed) !== ''
            || smtp_env_read_env_key(smtp_env_profile_env_key('general', 'USERNAME'), $parsed) !== '';
        $toWrite = [];
        if (!$generalAlready) {
            foreach (smtp_env_profile_field_suffixes() as $suffix) {
                $activeKey = smtp_env_active_key_for_suffix($suffix);
                $profileKey = smtp_env_profile_env_key('general', $suffix);
                $val = (string) ($active[$activeKey] ?? '');
                if ($val !== '') {
                    $toWrite[$profileKey] = $val;
                }
            }
        }
        $profileFlag = smtp_env_read_env_key('SMTP_PROFILE', $parsed);
        if ($profileFlag === '') {
            $toWrite['SMTP_PROFILE'] = 'general';
        }
        if ($toWrite === []) {
            return;
        }
        if (instantwin_manager_env_write($toWrite)) {
            foreach ($toWrite as $k => $v) {
                instantwin_manager_env_apply_runtime((string) $k, (string) $v);
            }
        }
    }
}

if (!function_exists('smtp_env_profile_values_to_write')) {
    /**
     * Converte valori SMTP_* validati in chiavi profilo + chiavi attive + SMTP_PROFILE.
     *
     * @param array<string, string> $activeValues
     * @return array<string, string>
     */
    function smtp_env_profile_values_to_write(string $profile, array $activeValues): array
    {
        $profile = smtp_env_normalize_profile_id($profile);
        $out = ['SMTP_PROFILE' => $profile];
        foreach (smtp_env_profile_field_suffixes() as $suffix) {
            $activeKey = smtp_env_active_key_for_suffix($suffix);
            $val = (string) ($activeValues[$activeKey] ?? '');
            $out[$activeKey] = $val;
            $out[smtp_env_profile_env_key($profile, $suffix)] = $val;
        }
        return $out;
    }
}

if (!function_exists('smtp_env_render_fields')) {
    /**
     * @param list<array<string, mixed>> $fields
     * @param array<string, string> $values
     * @param array<string, bool> $set
     */
    function smtp_env_render_fields(array $fields, array $values, array $set): void
    {
        foreach ($fields as $smtpField) {
            $fkey = (string) ($smtpField['key'] ?? '');
            if ($fkey === '') {
                continue;
            }
            $flabel = (string) ($smtpField['label'] ?? $fkey);
            $fhint = (string) ($smtpField['hint'] ?? '');
            $ftype = (string) ($smtpField['type'] ?? 'text');
            if (!in_array($ftype, ['text', 'password', 'email', 'number'], true)) {
                $ftype = 'text';
            }
            $isSecret = !empty($smtpField['secret']);
            $placeholder = (string) ($smtpField['placeholder'] ?? '');
            $hasValue = !empty($set[$fkey]);
            $inputValue = $isSecret ? '' : (string) ($values[$fkey] ?? '');
            if ($isSecret && $hasValue) {
                $placeholder = '(già impostato — lascia vuoto per non modificare)';
            }
            $esc = static function (string $s): string {
                return function_exists('manager_safe_html')
                    ? manager_safe_html($s)
                    : htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            };
            $colClass = ($fkey === 'SMTP_PORT') ? 'col-md-3' : 'col-md-6';
            ?>
            <div class="form-group <?php echo $colClass; ?>">
                <label for="smtp_env_<?php echo $esc($fkey); ?>">
                    <?php echo $esc($flabel); ?>
                    <?php if ($fhint !== ''): ?>
                        <span class="text-muted ml-1" data-toggle="tooltip" title="<?php echo $esc($fhint); ?>"><i class="fa fa-question-circle"></i></span>
                    <?php endif; ?>
                    <?php if ($hasValue && $isSecret): ?>
                        <span class="badge badge-success ml-1">impostato</span>
                    <?php endif; ?>
                </label>
                <input
                    type="<?php echo $esc($ftype); ?>"
                    class="form-control"
                    id="smtp_env_<?php echo $esc($fkey); ?>"
                    name="<?php echo $esc($fkey); ?>"
                    value="<?php echo $esc($inputValue); ?>"
                    placeholder="<?php echo $esc($placeholder); ?>"
                    autocomplete="<?php echo $isSecret ? 'new-password' : 'off'; ?>"
                    <?php echo $ftype === 'number' ? 'min="1" step="1"' : ''; ?>
                >
            </div>
            <?php
        }
    }
}
