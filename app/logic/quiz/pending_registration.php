<?php
/**
 * Registrazione differita (solo quiz attivo): dati in sessione fino al completamento quiz,
 * poi INSERT utenti + partecipazioni + email come dopo la registrazione senza quiz.
 */

if (!defined('GST_QUIZ_PENDING_SESSION_KEY')) {
    define('GST_QUIZ_PENDING_SESSION_KEY', 'gst_quiz_pending_registration');
}

if (!function_exists('gst_quiz_pending_max_age_seconds')) {
    function gst_quiz_pending_max_age_seconds(): int
    {
        global $contest_config;
        $m = (int) (($contest_config['quiz'] ?? [])['pending_session_minutes'] ?? 45);
        return max(5, min(24 * 60, $m)) * 60;
    }
}

if (!function_exists('gst_quiz_pending_is_expired')) {
    function gst_quiz_pending_is_expired(?array $box): bool
    {
        if (!is_array($box) || empty($box['created_at'])) {
            return true;
        }
        return (time() - (int) $box['created_at']) > gst_quiz_pending_max_age_seconds();
    }
}

if (!function_exists('gst_quiz_pending_touch')) {
    function gst_quiz_pending_touch(): void
    {
        if (!empty($_SESSION[GST_QUIZ_PENDING_SESSION_KEY]) && is_array($_SESSION[GST_QUIZ_PENDING_SESSION_KEY])) {
            $_SESSION[GST_QUIZ_PENDING_SESSION_KEY]['last_touch'] = time();
        }
    }
}

if (!function_exists('gst_persist_pending_registration')) {
    /**
     * @return array{ok:bool, token?:string, id_cliente?:int, user_id?:int, participation_id?:int, error?:string}
     */
    function gst_persist_pending_registration(PDO $c_pdo): array
    {
        global $contest_config;

        $box = $_SESSION[GST_QUIZ_PENDING_SESSION_KEY] ?? null;
        if (!is_array($box) || empty($box['payload']) || !is_array($box['payload'])) {
            return ['ok' => false, 'error' => 'Sessione registrazione assente.'];
        }
        if (gst_quiz_pending_is_expired($box)) {
            unset($_SESSION[GST_QUIZ_PENDING_SESSION_KEY]);
            return ['ok' => false, 'error' => 'Sessione scaduta. Riparti dalla registrazione.'];
        }

        $P = $box['payload'];
        $nome = (string) ($P['nome'] ?? '');
        $cognome = (string) ($P['cognome'] ?? '');
        $email = (string) ($P['email'] ?? '');
        $prefisso = (string) ($P['prefisso'] ?? '');
        $cellulare = (string) ($P['cellulare'] ?? '');
        $privacy = (int) ($P['privacy'] ?? 0);
        $marketing = (int) ($P['marketing'] ?? 0);
        $profilazione = (int) ($P['profilazione'] ?? 0);
        $provincia = (string) ($P['provincia'] ?? '');
        $citta = (string) ($P['citta'] ?? '');
        $cap = preg_replace('/\D+/', '', (string) ($P['cap'] ?? ''));
        if (strlen($cap) > 5) {
            $cap = substr($cap, 0, 5);
        }
        $indirizzo = (string) ($P['indirizzo'] ?? '');
        $fidelity = (string) ($P['fidelity'] ?? '');
        $custom_fields_values = is_array($P['custom_fields_values'] ?? null) ? $P['custom_fields_values'] : [];
        $custom_fields_config = is_array($P['custom_fields_config'] ?? null) ? $P['custom_fields_config'] : [];
        $contest_mode = (string) ($P['contest_mode'] ?? '');
        $has_codice_partecipazione = !empty($P['has_codice_partecipazione']);
        $codice_partecipazione = (string) ($P['codice_partecipazione'] ?? '');
        $codicePdv = (string) ($P['codicePdv'] ?? '');
        $insegna_coop = (string) ($P['insegna_coop'] ?? '');
        $receipt_filename = (string) ($P['receipt_filename'] ?? '');
        $receipt_original = (string) ($P['receipt_original'] ?? '');
        $importo_db_value = $P['importo_db_value'] ?? null;
        $data_scontrino = (string) ($P['data_scontrino'] ?? '');
        $ip_address = (string) ($P['ip_address'] ?? '');
        $user_agent = (string) ($P['user_agent'] ?? '');

        $columns_check = $c_pdo->query("SHOW COLUMNS FROM utenti LIKE 'provincia'")->fetch();
        $has_provincia = $columns_check !== false;
        $columns_check = $c_pdo->query("SHOW COLUMNS FROM utenti LIKE 'cap'")->fetch();
        $has_cap = $columns_check !== false;
        $columns_check = $c_pdo->query("SHOW COLUMNS FROM utenti LIKE 'citta'")->fetch();
        $has_citta = $columns_check !== false;
        $columns_check = $c_pdo->query("SHOW COLUMNS FROM utenti LIKE 'indirizzo'")->fetch();
        $has_indirizzo = $columns_check !== false;
        $columns_check = $c_pdo->query("SHOW COLUMNS FROM utenti LIKE 'fidelity'")->fetch();
        $has_fidelity = $columns_check !== false;

        $fidelity_rules = $contest_config['fidelity'] ?? [];
        $fidelity_unique_participation = !empty($fidelity_rules['unique_participation']);
        if ($fidelity_unique_participation && $has_fidelity && $fidelity !== '') {
            $stmt_fidelity_used = $c_pdo->prepare('
                SELECT 1
                FROM partecipazioni p
                JOIN utenti u ON u.id_cliente = p.id_cliente
                WHERE u.fidelity = :fidelity
                LIMIT 1
            ');
            $stmt_fidelity_used->bindValue(':fidelity', $fidelity, PDO::PARAM_STR);
            $stmt_fidelity_used->execute();
            if ($stmt_fidelity_used->fetchColumn()) {
                return ['ok' => false, 'error' => 'Questa carta fidelity ha già partecipato al concorso.'];
            }
        }

        $columns = ['nome', 'cognome', 'email', 'prefisso', 'cellulare', 'privacy', 'marketing', 'profilazione', 'attivo'];
        $placeholders = [':nome', ':cognome', ':email', ':prefisso', ':cellulare', ':privacy', ':marketing', ':profilazione', ':attivo'];
        if ($has_provincia) {
            $columns[] = 'provincia';
            $placeholders[] = ':provincia';
        }
        if ($has_cap) {
            $columns[] = 'cap';
            $placeholders[] = ':cap';
        }
        if ($has_citta) {
            $columns[] = 'citta';
            $placeholders[] = ':citta';
        }
        if ($has_indirizzo) {
            $columns[] = 'indirizzo';
            $placeholders[] = ':indirizzo';
        }
        if ($has_fidelity) {
            $columns[] = 'fidelity';
            $placeholders[] = ':fidelity';
        }

        $sql = 'INSERT INTO utenti (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $stmt_new_user = $c_pdo->prepare($sql);
        $stmt_new_user->bindValue(':nome', $nome, PDO::PARAM_STR);
        $stmt_new_user->bindValue(':cognome', $cognome, PDO::PARAM_STR);
        $stmt_new_user->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt_new_user->bindValue(':prefisso', $prefisso, PDO::PARAM_STR);
        $stmt_new_user->bindValue(':cellulare', $cellulare, PDO::PARAM_STR);
        $stmt_new_user->bindValue(':privacy', $privacy, PDO::PARAM_INT);
        $stmt_new_user->bindValue(':marketing', $marketing, PDO::PARAM_INT);
        $stmt_new_user->bindValue(':profilazione', $profilazione, PDO::PARAM_INT);
        $stmt_new_user->bindValue(':attivo', 1, PDO::PARAM_INT);
        if ($has_provincia) {
            $stmt_new_user->bindValue(':provincia', $provincia !== '' ? $provincia : null, PDO::PARAM_STR);
        }
        if ($has_cap) {
            $stmt_new_user->bindValue(':cap', $cap !== '' ? $cap : null, PDO::PARAM_STR);
        }
        if ($has_citta) {
            $stmt_new_user->bindValue(':citta', $citta !== '' ? $citta : null, PDO::PARAM_STR);
        }
        if ($has_indirizzo) {
            $stmt_new_user->bindValue(':indirizzo', $indirizzo !== '' ? $indirizzo : null, PDO::PARAM_STR);
        }
        if ($has_fidelity) {
            $stmt_new_user->bindValue(':fidelity', $fidelity !== '' ? $fidelity : null, PDO::PARAM_STR);
        }
        if (!$stmt_new_user->execute()) {
            return ['ok' => false, 'error' => 'Errore creazione utente.'];
        }
        $user_id = (int) $c_pdo->lastInsertId();

        $token = bin2hex(random_bytes(32));
        $codice_partecipazione_col = $has_codice_partecipazione ? ', codice_partecipazione' : '';
        $codice_partecipazione_val = $has_codice_partecipazione ? ', :codice_partecipazione' : '';

        $stmt_partecipation = $c_pdo->prepare('
        INSERT INTO partecipazioni (
            id_cliente, token, data_partecipazione, importo_scontrino, data_scontrino,
            file_scontrino, file_originale, id_pdv_concorso, insegna, ip_address, user_agent,
            confermato, annullato, has_won' . $codice_partecipazione_col . '
    ) VALUES (
            :id_cliente, :token, :data_partecipazione, :importo_scontrino, :data_scontrino,
            :file_scontrino, :file_originale, :id_pdv_concorso, :insegna, :ip_address, :user_agent,
            :confermato, :annullato, :has_won' . $codice_partecipazione_val . '
        )
    ');

        $stmt_partecipation->bindValue(':id_cliente', $user_id, PDO::PARAM_INT);
        $stmt_partecipation->bindValue(':token', $token, PDO::PARAM_STR);
        $currentDateTime = new DateTime('now', new DateTimeZone('Europe/Rome'));
        $stmt_partecipation->bindValue(':data_partecipazione', $currentDateTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
        $stmt_partecipation->bindValue(':importo_scontrino', $importo_db_value !== null ? $importo_db_value : '0.00', PDO::PARAM_STR);
        $stmt_partecipation->bindValue(':data_scontrino', $data_scontrino !== '' ? $data_scontrino : $currentDateTime->format('Y-m-d'), PDO::PARAM_STR);
        $stmt_partecipation->bindValue(':file_scontrino', $receipt_filename, PDO::PARAM_STR);
        $stmt_partecipation->bindValue(':file_originale', $receipt_original, PDO::PARAM_STR);
        $stmt_partecipation->bindValue(':id_pdv_concorso', $codicePdv, PDO::PARAM_STR);
        $stmt_partecipation->bindValue(':insegna', $insegna_coop, PDO::PARAM_STR);
        $stmt_partecipation->bindValue(':ip_address', $ip_address, PDO::PARAM_STR);
        $stmt_partecipation->bindValue(':user_agent', $user_agent, PDO::PARAM_STR);
        $stmt_partecipation->bindValue(':confermato', 0, PDO::PARAM_INT);
        $stmt_partecipation->bindValue(':annullato', 0, PDO::PARAM_INT);
        $stmt_partecipation->bindValue(':has_won', 0, PDO::PARAM_INT);
        if ($has_codice_partecipazione) {
            $stmt_partecipation->bindValue(':codice_partecipazione', $codice_partecipazione, PDO::PARAM_STR);
        }

        $codice_already_used = false;
        if ($has_codice_partecipazione && $codice_partecipazione !== '') {
            $c_pdo->beginTransaction();
            try {
                $stmt_lock = $c_pdo->prepare('SELECT id, used FROM codici_partecipazione WHERE codice = :codice FOR UPDATE');
                $stmt_lock->bindValue(':codice', $codice_partecipazione, PDO::PARAM_STR);
                $stmt_lock->execute();
                $lock_row = $stmt_lock->fetch(PDO::FETCH_ASSOC);
                if (!$lock_row || (int) ($lock_row['used'] ?? 0) === 1) {
                    $c_pdo->rollBack();
                    $codice_already_used = true;
                }
            } catch (Throwable $e) {
                $c_pdo->rollBack();
                error_log('gst_persist_pending_registration lock: ' . $e->getMessage());
                $codice_already_used = true;
            }
        }

        $participation_ok = false;
        if (!$codice_already_used) {
            $participation_ok = $stmt_partecipation->execute();
            if (!$participation_ok && $has_codice_partecipazione && $codice_partecipazione !== '') {
                $c_pdo->rollBack();
            }
        }

        if (!$participation_ok) {
            return ['ok' => false, 'error' => $codice_already_used ? 'Codice partecipazione non più valido.' : 'Errore salvataggio partecipazione.'];
        }

        $participation_id = (int) $c_pdo->lastInsertId();

        if ($has_codice_partecipazione && $codice_partecipazione !== '') {
            try {
                $stmt_update_codice = $c_pdo->prepare('
                    UPDATE codici_partecipazione 
                    SET used = 1,
                        id_partecipazione = :id_partecipazione,
                        id_cliente = :id_cliente,
                        data_utilizzo = NOW(),
                        isActive = 0
                    WHERE codice = :codice 
                      AND used = 0
                ');
                $stmt_update_codice->bindValue(':id_partecipazione', $participation_id, PDO::PARAM_INT);
                $stmt_update_codice->bindValue(':id_cliente', $user_id, PDO::PARAM_INT);
                $stmt_update_codice->bindValue(':codice', $codice_partecipazione, PDO::PARAM_STR);
                $stmt_update_codice->execute();
                $c_pdo->commit();
            } catch (Throwable $e) {
                $c_pdo->rollBack();
                error_log('gst_persist_pending_registration codice: ' . $e->getMessage());
            }
        }

        if (!empty($custom_fields_values) && !empty($custom_fields_config)) {
            $update_fields = [];
            $update_values = [':id' => $participation_id];
            foreach ($custom_fields_config as $customField) {
                if (!is_array($customField) || !isset($customField['id'])) {
                    continue;
                }
                $customId = preg_replace('/[^a-z0-9_-]/i', '', (string) ($customField['id'] ?? ''));
                if ($customId === '') {
                    continue;
                }
                $column_name = preg_replace('/[^a-z0-9_]/i', '_', strtolower($customId));
                $customValue = $custom_fields_values[$customId] ?? null;
                try {
                    $col_check = $c_pdo->query("SHOW COLUMNS FROM partecipazioni LIKE '{$column_name}'")->fetch();
                    if ($col_check !== false) {
                        $param_name = ':' . preg_replace('/[^a-z0-9_]/i', '_', strtolower($customId));
                        $update_fields[] = "`{$column_name}` = {$param_name}";
                        $update_values[$param_name] = $customValue !== null && $customValue !== '' ? $customValue : null;
                    }
                } catch (Throwable $e) {
                    error_log("gst_persist custom column {$column_name}: " . $e->getMessage());
                }
            }
            if (!empty($update_fields)) {
                try {
                    $update_sql = 'UPDATE partecipazioni SET ' . implode(', ', $update_fields) . ' WHERE id = :id';
                    $stmt_update = $c_pdo->prepare($update_sql);
                    foreach ($update_values as $key => $value) {
                        if ($key === ':id') {
                            $stmt_update->bindValue($key, $value, PDO::PARAM_INT);
                        } else {
                            $fieldId = str_replace(':', '', $key);
                            $fieldId = preg_replace('/[^a-z0-9_]/i', '', strtolower($fieldId));
                            $fieldType = null;
                            foreach ($custom_fields_config as $cf) {
                                if (isset($cf['id']) && preg_replace('/[^a-z0-9_-]/i', '', strtolower($cf['id'])) === $fieldId) {
                                    $typeMapping = [
                                        'text' => 'varchar', 'textarea' => 'text', 'number' => 'int', 'email' => 'varchar',
                                        'date' => 'date', 'int' => 'int', 'varchar' => 'varchar', 'boolean' => 'boolean', 'bool' => 'boolean',
                                    ];
                                    $rawType = strtolower(trim((string) ($cf['type'] ?? 'varchar')));
                                    $fieldType = $typeMapping[$rawType] ?? 'varchar';
                                    $fieldType = in_array($fieldType, ['int', 'varchar', 'text', 'date', 'boolean'], true) ? $fieldType : 'varchar';
                                    break;
                                }
                            }
                            if ($fieldType === 'date') {
                                if ($value !== null && $value !== '') {
                                    $dateValue = trim((string) $value);
                                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateValue)) {
                                        $stmt_update->bindValue($key, $dateValue, PDO::PARAM_STR);
                                    } else {
                                        $stmt_update->bindValue($key, null, PDO::PARAM_NULL);
                                    }
                                } else {
                                    $stmt_update->bindValue($key, null, PDO::PARAM_NULL);
                                }
                            } elseif ($fieldType === 'boolean') {
                                $boolValue = ($value === '1' || $value === 'on' || $value === true || $value === 'true') ? 1 : 0;
                                $stmt_update->bindValue($key, $boolValue, PDO::PARAM_INT);
                            } elseif ($fieldType === 'int') {
                                $intValue = is_numeric($value) ? (int) $value : null;
                                $stmt_update->bindValue($key, $intValue, PDO::PARAM_INT);
                            } else {
                                $stmt_update->bindValue($key, $value, PDO::PARAM_STR);
                            }
                        }
                    }
                    $stmt_update->execute();
                } catch (Throwable $e) {
                    error_log('gst_persist custom fields: ' . $e->getMessage());
                }
            }
        }

        unset($_SESSION[GST_QUIZ_PENDING_SESSION_KEY]);

        return [
            'ok' => true,
            'token' => $token,
            'id_cliente' => $user_id,
            'user_id' => $user_id,
            'participation_id' => $participation_id,
        ];
    }
}
