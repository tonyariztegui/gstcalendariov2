<?php

/**
 * Lettura / scrittura variabili MAILUP_SMS_* nel file .env (manager Configurazione).
 */

require_once __DIR__ . '/instantwin/manager_env.php';

if (!function_exists('mailup_sms_env_schema')) {
    /**
     * @return list<array{key:string,label:string,hint:string,type:string,group:string,secret?:bool,placeholder?:string}>
     */
    function mailup_sms_env_schema(): array
    {
        return [
            // group "send" — necessari per l'invio
            [
                'key' => 'MAILUP_SMS_ACCOUNT_ID',
                'label' => 'Account ID',
                'hint' => 'ID numerico del conto MailUp (es. se l’utente è m59484, l’Account ID è spesso 59484). Obbligatorio nell’URL di invio SMS.',
                'type' => 'text',
                'group' => 'send',
                'placeholder' => 'es. 59484',
            ],
            [
                'key' => 'MAILUP_SMS_LIST_ID',
                'label' => 'List ID',
                'hint' => 'ID della lista MailUp usata per gli SMS transazionali. Obbligatorio.',
                'type' => 'text',
                'group' => 'send',
                'placeholder' => 'es. 1',
            ],
            [
                'key' => 'MAILUP_SMS_LIST_GUID',
                'label' => 'List GUID',
                'hint' => 'GUID della lista (codice univoco MailUp). Obbligatorio nel body di invio e per creare il ListSecret.',
                'type' => 'text',
                'group' => 'send',
                'placeholder' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
            ],
            [
                'key' => 'MAILUP_SMS_LIST_SECRET',
                'label' => 'List Secret',
                'hint' => 'Chiave API della lista che autorizza l’invio SMS. Senza ListSecret valido l’API rifiuta l’invio. Campo mascherato.',
                'type' => 'password',
                'group' => 'send',
                'secret' => true,
                'placeholder' => '',
            ],
            [
                'key' => 'MAILUP_SMS_SENDER',
                'label' => 'Sender SMS',
                'hint' => 'Mittente alfanumerico mostrato sul telefono (max 11 caratteri, solo lettere e cifre). Deve essere richiesto e approvato da MailUp e dall’operatore telefonico: l’attivazione può richiedere alcuni giorni lavorativi. Se vuoto o non ancora approvato, MailUp può usare un numero breve o l’invio può fallire.',
                'type' => 'text',
                'group' => 'send',
                'placeholder' => 'es. Sisa',
            ],
            // group "secret" — solo per generare/recuperare ListSecret
            [
                'key' => 'MAILUP_SMS_USERNAME',
                'label' => 'Username MailUp',
                'hint' => 'Login della console MailUp (es. m12345). Serve solo per creare o leggere il ListSecret con Basic Auth.',
                'type' => 'text',
                'group' => 'secret',
                'placeholder' => 'es. m12345',
            ],
            [
                'key' => 'MAILUP_SMS_PASSWORD',
                'label' => 'Password MailUp',
                'hint' => 'Password dell’account MailUp. Usata solo con lo username per gestire il ListSecret. Non viene mostrata dopo il salvataggio.',
                'type' => 'password',
                'group' => 'secret',
                'secret' => true,
                'placeholder' => '',
            ],
        ];
    }
}

if (!function_exists('mailup_sms_env_fields_by_group')) {
    /**
     * @return list<array{key:string,label:string,hint:string,type:string,group:string,secret?:bool,placeholder?:string}>
     */
    function mailup_sms_env_fields_by_group(string $group): array
    {
        $out = [];
        foreach (mailup_sms_env_schema() as $field) {
            if (($field['group'] ?? '') === $group) {
                $out[] = $field;
            }
        }
        return $out;
    }
}

if (!function_exists('mailup_sms_env_all_keys')) {
    /**
     * Chiavi .env gestite (include destinatario test, non mostrato nel loop credenziali).
     *
     * @return list<string>
     */
    function mailup_sms_env_all_keys(): array
    {
        return array_merge(mailup_sms_env_keys(), ['MAILUP_SMS_TEST_RECIPIENT']);
    }
}

if (!function_exists('mailup_sms_env_keys')) {
    /**
     * @return list<string>
     */
    function mailup_sms_env_keys(): array
    {
        return array_values(array_map(static function (array $f): string {
            return (string) $f['key'];
        }, mailup_sms_env_schema()));
    }
}

if (!function_exists('mailup_sms_env_values_for_form')) {
    /**
     * Valori attuali da .env (o getenv). I secret restano valorizzati server-side
     * ma in UI si mostrano vuoti con flag « già impostato ».
     *
     * @return array{values: array<string, string>, set: array<string, bool>}
     */
    function mailup_sms_env_values_for_form(): array
    {
        $parsed = instantwin_manager_env_parse_file();
        $values = [];
        $set = [];
        foreach (mailup_sms_env_all_keys() as $key) {
            $v = '';
            if (array_key_exists($key, $parsed)) {
                $v = (string) $parsed[$key];
            } else {
                $g = getenv($key);
                if (is_string($g) && $g !== '') {
                    $v = $g;
                }
            }
            $values[$key] = $v;
            $set[$key] = $v !== '';
        }
        return ['values' => $values, 'set' => $set];
    }
}

if (!function_exists('mailup_sms_env_validate_submission')) {
    /**
     * @param array<string, mixed> $in
     * @param array<string, string> $existing
     * @return array{ok:bool,message?:string,values?:array<string,string>}
     */
    function mailup_sms_env_validate_submission(array $in, array $existing = []): array
    {
        $out = [];
        foreach (mailup_sms_env_schema() as $field) {
            $key = (string) $field['key'];
            $isSecret = !empty($field['secret']);
            $raw = trim((string) ($in[$key] ?? ''));

            if ($isSecret && $raw === '') {
                // Lascia invariato se già presente
                if (isset($existing[$key]) && $existing[$key] !== '') {
                    $out[$key] = $existing[$key];
                } else {
                    $out[$key] = '';
                }
                continue;
            }

            if ($key === 'MAILUP_SMS_SENDER' && $raw !== '') {
                $clean = preg_replace('/[^a-zA-Z0-9]/', '', $raw) ?? '';
                if (strlen($clean) > 11) {
                    $clean = substr($clean, 0, 11);
                }
                $out[$key] = $clean;
                continue;
            }

            // Evita caratteri pericolosi per .env (newline / #)
            if (strpbrk($raw, "\r\n#") !== false) {
                return ['ok' => false, 'message' => 'Valore non valido per ' . $key . ' (caratteri non ammessi).'];
            }

            $out[$key] = $raw;
        }

        // Destinatario test: campo UI unico (#sms_test_recipient), opzionale
        $testRaw = trim((string) ($in['MAILUP_SMS_TEST_RECIPIENT'] ?? ''));
        if ($testRaw !== '') {
            if (!function_exists('gst_sms_normalize_recipient_e164')) {
                require_once dirname(__DIR__) . '/inc/sms_mailup.inc.php';
            }
            $phone = function_exists('gst_sms_normalize_recipient_e164')
                ? gst_sms_normalize_recipient_e164($testRaw)
                : (preg_replace('/[^\d+]/', '', $testRaw) ?? '');
            if ($phone === '' || strpbrk($phone, "\r\n#") !== false) {
                return ['ok' => false, 'message' => 'Valore non valido per MAILUP_SMS_TEST_RECIPIENT.'];
            }
            $out['MAILUP_SMS_TEST_RECIPIENT'] = $phone;
        } elseif (isset($existing['MAILUP_SMS_TEST_RECIPIENT'])) {
            $out['MAILUP_SMS_TEST_RECIPIENT'] = (string) $existing['MAILUP_SMS_TEST_RECIPIENT'];
        } else {
            $out['MAILUP_SMS_TEST_RECIPIENT'] = '';
        }

        return ['ok' => true, 'values' => $out];
    }
}

if (!function_exists('mailup_sms_env_render_fields')) {
    /**
     * Render HTML campi di un gruppo (usato in step-configurazione).
     *
     * @param list<array<string,mixed>> $fields
     * @param array<string,string> $values
     * @param array<string,bool> $set
     */
    function mailup_sms_env_render_fields(array $fields, array $values, array $set): void
    {
        foreach ($fields as $smsField) {
            $fkey = (string) ($smsField['key'] ?? '');
            if ($fkey === '') {
                continue;
            }
            $flabel = (string) ($smsField['label'] ?? $fkey);
            $fhint = (string) ($smsField['hint'] ?? '');
            $ftype = (($smsField['type'] ?? 'text') === 'password') ? 'password' : 'text';
            $isSecret = !empty($smsField['secret']);
            $placeholder = (string) ($smsField['placeholder'] ?? '');
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
            ?>
            <div class="form-group col-md-6">
                <label for="sms_env_<?php echo $esc($fkey); ?>">
                    <?php echo $esc($flabel); ?>
                    <?php if ($fhint !== ''): ?>
                        <span class="text-muted ml-1" data-toggle="tooltip" title="<?php echo $esc($fhint); ?>"><i class="fa fa-question-circle"></i></span>
                    <?php endif; ?>
                    <?php if ($hasValue && $isSecret): ?>
                        <span class="badge badge-success ml-1">impostato</span>
                    <?php endif; ?>
                </label>
                <input
                    type="<?php echo $ftype; ?>"
                    class="form-control"
                    id="sms_env_<?php echo $esc($fkey); ?>"
                    name="<?php echo $esc($fkey); ?>"
                    value="<?php echo $esc($inputValue); ?>"
                    placeholder="<?php echo $esc($placeholder); ?>"
                    autocomplete="<?php echo $isSecret ? 'new-password' : 'off'; ?>"
                    <?php echo $fkey === 'MAILUP_SMS_SENDER' ? 'maxlength="11"' : ''; ?>
                >
            </div>
            <?php
        }
    }
}
