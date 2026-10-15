<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/gestione_dati_context.inc.php';
require_once __DIR__ . '/gestione_dati_validation.inc.php';
require_once __DIR__ . '/gestione_dati_crud_schema.inc.php';

gestione_dati_require_auth_json();

if (!isset($connection) || !$connection) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB non disponibile.']);
    exit;
}

$ctx = gestione_dati_load_content();
$content = $ctx['content'];
$defaults = $ctx['defaults'];

$action = strtolower(trim((string) ($_POST['action'] ?? $_GET['action'] ?? '')));
$table = gestione_dati_safe_table_name((string) ($_POST['table'] ?? $_GET['table'] ?? ''), $content, $defaults);
$id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);

if ($table === null) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Tabella non consentita.']);
    exit;
}

function gd_json_ok(array $payload = []): void
{
    echo json_encode(array_merge(['success' => true], $payload));
    exit;
}

function gd_json_error(string $message, int $code = 400, array $extra = []): void
{
    http_response_code($code);
    echo json_encode(array_merge(['success' => false, 'error' => $message], $extra));
    exit;
}

try {
    switch ($action) {
        case 'get':
            if ($id <= 0) {
                gd_json_error('ID non valido.');
            }
            gd_json_ok(['record' => gestione_dati_crud_fetch_record($connection, $table, $id)]);
            break;

        case 'meta':
            gd_json_ok(['meta' => gestione_dati_crud_build_meta($connection, $table, $content, $defaults)]);
            break;

        case 'create':
            $record = gestione_dati_crud_read_input($table, $connection);
            $errors = gestione_dati_crud_validate($connection, $table, $record, $content, $defaults, null);
            if (!empty($errors)) {
                gd_json_error(implode(' ', $errors), 422, ['errors' => $errors]);
            }
            $newId = gestione_dati_crud_insert($connection, $table, $record);
            gd_json_ok(['id' => $newId, 'message' => 'Record creato.']);
            break;

        case 'update':
            if ($id <= 0) {
                gd_json_error('ID non valido.');
            }
            $existing = gestione_dati_crud_fetch_record($connection, $table, $id);
            if ($existing === null) {
                gd_json_error('Record non trovato.', 404);
            }
            $record = gestione_dati_crud_read_input($table, $connection);
            $errors = gestione_dati_crud_validate($connection, $table, $record, $content, $defaults, $id, $existing);
            if (!empty($errors)) {
                gd_json_error(implode(' ', $errors), 422, ['errors' => $errors]);
            }
            gestione_dati_crud_update($connection, $table, $id, $record, $existing);
            gd_json_ok(['message' => 'Record aggiornato.']);
            break;

        case 'delete':
            if ($id <= 0) {
                gd_json_error('ID non valido.');
            }
            $existing = gestione_dati_crud_fetch_record($connection, $table, $id);
            if ($existing === null) {
                gd_json_error('Record non trovato.', 404);
            }
            $err = gestione_dati_crud_delete($connection, $table, $id, $existing);
            if ($err !== null) {
                gd_json_error($err, 422);
            }
            gd_json_ok(['message' => 'Record eliminato.']);
            break;

        default:
            gd_json_error('Azione non valida.', 400);
    }
} catch (Throwable $e) {
    error_log('gestione_dati_crud: ' . $e->getMessage());
    gd_json_error('Errore server: ' . $e->getMessage(), 500);
}

function gestione_dati_crud_fetch_record(PDO $pdo, string $table, int $id): ?array
{
    $sql = match ($table) {
        'punti_vendita' => 'SELECT * FROM punti_vendita WHERE id = ? LIMIT 1',
        'codici_partecipazione' => 'SELECT * FROM codici_partecipazione WHERE id = ? LIMIT 1',
        'codici_premio' => 'SELECT * FROM codici_premio WHERE id = ? LIMIT 1',
        'quiz_domande' => 'SELECT * FROM quiz_domande WHERE id = ? LIMIT 1',
        default => '',
    };
    if ($sql === '') {
        return null;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function gestione_dati_crud_meta(PDO $pdo, string $table, array $content): array
{
    return gestione_dati_crud_build_meta($pdo, $table, $content, []);
}

function gestione_dati_crud_read_input(string $table, PDO $pdo): array
{
    $base = match ($table) {
        'punti_vendita' => [
            'codice' => trim((string) ($_POST['codice'] ?? '')),
            'ragsoc' => trim((string) ($_POST['ragsoc'] ?? '')),
            'indirizzo' => trim((string) ($_POST['indirizzo'] ?? '')),
            'citta' => trim((string) ($_POST['citta'] ?? '')),
            'prov' => trim((string) ($_POST['prov'] ?? '')),
            'cap' => trim((string) ($_POST['cap'] ?? '')),
            'regione' => trim((string) ($_POST['regione'] ?? '')),
            'insegna' => trim((string) ($_POST['insegna'] ?? '')),
            'active' => !empty($_POST['active']) ? 1 : 0,
        ],
        'codici_partecipazione' => [
            'codice' => trim((string) ($_POST['codice'] ?? '')),
            'isActive' => !empty($_POST['isActive']) ? 1 : 0,
        ],
        'codici_premio' => [
            'codice' => trim((string) ($_POST['codice'] ?? '')),
            'prize_id' => (int) ($_POST['prize_id'] ?? 0),
            'isActive' => !empty($_POST['isActive']) ? 1 : 0,
            'id_codice_partecipazione' => ($_POST['id_codice_partecipazione'] ?? '') !== '' ? (int) $_POST['id_codice_partecipazione'] : null,
        ],
        'quiz_domande' => [
            'testo' => trim((string) ($_POST['testo'] ?? '')),
            'tipo' => in_array($_POST['tipo'] ?? '', ['text', 'audio', 'video'], true) ? $_POST['tipo'] : 'text',
            'risposta_a' => trim((string) ($_POST['risposta_a'] ?? '')),
            'risposta_b' => trim((string) ($_POST['risposta_b'] ?? '')),
            'risposta_c' => trim((string) ($_POST['risposta_c'] ?? '')),
            'risposta_d' => trim((string) ($_POST['risposta_d'] ?? '')),
            'risposta_corretta' => strtoupper(trim((string) ($_POST['risposta_corretta'] ?? 'A'))),
            'ordine' => (int) ($_POST['ordine'] ?? 0),
            'attiva' => !empty($_POST['attiva']) ? 1 : 0,
        ],
        default => [],
    };

    if ($table === 'punti_vendita') {
        if (gestione_dati_table_has_column($pdo, 'punti_vendita', 'codice_cedi')) {
            $base['codice_cedi'] = trim((string) ($_POST['codice_cedi'] ?? ''));
        }
        if (gestione_dati_table_has_column($pdo, 'punti_vendita', 'email_pdv')) {
            $base['email_pdv'] = trim((string) ($_POST['email_pdv'] ?? ''));
        }
    }

    return $base;
}

/**
 * @param array<string, mixed>|null $existing
 * @return list<string>
 */
function gestione_dati_crud_validate(PDO $pdo, string $table, array $record, array $content, array $defaults, ?int $id, ?array $existing = null): array
{
    $ctx = gestione_dati_resolve_context($pdo, $content, $defaults);

    switch ($table) {
        case 'punti_vendita':
            return gestione_dati_validate_pdv($pdo, $record, $id, $ctx, $content, $defaults);

        case 'codici_partecipazione':
            if ($existing && (int) ($existing['used'] ?? 0) === 1) {
                return [];
            }
            return gestione_dati_validate_codice_partecipazione($record['codice'] ?? '', $pdo, $content, $defaults, $id);

        case 'codici_premio':
            if ($existing && ((int) ($existing['used'] ?? 0) === 1 || !empty($existing['id_partecipazione']))) {
                return [];
            }
            return gestione_dati_validate_codice_premio(
                $pdo,
                $record['codice'] ?? '',
                (int) ($record['prize_id'] ?? 0),
                $record['id_codice_partecipazione'] ?? null,
                $ctx,
                $id
            );

        case 'quiz_domande':
            return gestione_dati_validate_quiz_domanda($record);

        default:
            return ['Tabella non supportata.'];
    }
}

function gestione_dati_crud_pdv_insert_sql(PDO $pdo, array $record): array
{
    $cols = ['codice', 'ragsoc', 'indirizzo', 'citta', 'prov', 'cap', 'regione', 'insegna', 'active'];
    $vals = [
        $record['codice'], $record['ragsoc'], $record['indirizzo'], $record['citta'],
        $record['prov'], $record['cap'], $record['regione'], $record['insegna'], $record['active'],
    ];
    foreach (['codice_cedi', 'email_pdv', 'iw_traffic_weight'] as $opt) {
        if (array_key_exists($opt, $record) && gestione_dati_table_has_column($pdo, 'punti_vendita', $opt)) {
            $cols[] = $opt;
            $vals[] = $opt === 'iw_traffic_weight' ? (float) $record[$opt] : $record[$opt];
        }
    }
    $ph = implode(',', array_fill(0, count($cols), '?'));
    return ['INSERT INTO punti_vendita (' . implode(',', $cols) . ') VALUES (' . $ph . ')', $vals];
}

function gestione_dati_crud_pdv_update_sql(PDO $pdo, array $record, int $id): array
{
    $sets = ['codice=?', 'ragsoc=?', 'indirizzo=?', 'citta=?', 'prov=?', 'cap=?', 'regione=?', 'insegna=?', 'active=?'];
    $vals = [
        $record['codice'], $record['ragsoc'], $record['indirizzo'], $record['citta'],
        $record['prov'], $record['cap'], $record['regione'], $record['insegna'], $record['active'],
    ];
    foreach (['codice_cedi', 'email_pdv', 'iw_traffic_weight'] as $opt) {
        if (array_key_exists($opt, $record) && gestione_dati_table_has_column($pdo, 'punti_vendita', $opt)) {
            $sets[] = $opt . '=?';
            $vals[] = $opt === 'iw_traffic_weight' ? (float) $record[$opt] : $record[$opt];
        }
    }
    $vals[] = $id;
    return ['UPDATE punti_vendita SET ' . implode(',', $sets) . ' WHERE id=?', $vals];
}

function gestione_dati_crud_insert(PDO $pdo, string $table, array $record): int
{
    switch ($table) {
        case 'punti_vendita':
            [$sql, $params] = gestione_dati_crud_pdv_insert_sql($pdo, $record);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return (int) $pdo->lastInsertId();

        case 'codici_partecipazione':
            $stmt = $pdo->prepare('INSERT INTO codici_partecipazione (codice, isActive, used) VALUES (?, ?, 0)');
            $stmt->execute([$record['codice'], $record['isActive']]);
            return (int) $pdo->lastInsertId();

        case 'codici_premio':
            $stmt = $pdo->prepare('INSERT INTO codici_premio (codice, prize_id, isActive, used, id_codice_partecipazione) VALUES (?, ?, ?, 0, ?)');
            $stmt->execute([
                $record['codice'],
                $record['prize_id'],
                $record['isActive'],
                $record['id_codice_partecipazione'],
            ]);
            return (int) $pdo->lastInsertId();

        case 'quiz_domande':
            $stmt = $pdo->prepare('INSERT INTO quiz_domande (testo, tipo, risposta_a, risposta_b, risposta_c, risposta_d, risposta_corretta, ordine, attiva) VALUES (?,?,?,?,?,?,?,?,?)');
            $stmt->execute([
                $record['testo'], $record['tipo'], $record['risposta_a'], $record['risposta_b'],
                $record['risposta_c'], $record['risposta_d'], $record['risposta_corretta'],
                $record['ordine'], $record['attiva'],
            ]);
            return (int) $pdo->lastInsertId();

        default:
            throw new RuntimeException('Tabella non supportata.');
    }
}

/**
 * @param array<string, mixed> $existing
 */
function gestione_dati_crud_update(PDO $pdo, string $table, int $id, array $record, array $existing): void
{
    switch ($table) {
        case 'punti_vendita':
            [$sql, $params] = gestione_dati_crud_pdv_update_sql($pdo, $record, $id);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return;

        case 'codici_partecipazione':
            if ((int) ($existing['used'] ?? 0) === 1) {
                $stmt = $pdo->prepare('UPDATE codici_partecipazione SET isActive=? WHERE id=?');
                $stmt->execute([$record['isActive'], $id]);
            } else {
                $stmt = $pdo->prepare('UPDATE codici_partecipazione SET codice=?, isActive=? WHERE id=?');
                $stmt->execute([$record['codice'], $record['isActive'], $id]);
            }
            return;

        case 'codici_premio':
            if ((int) ($existing['used'] ?? 0) === 1 || !empty($existing['id_partecipazione'])) {
                $stmt = $pdo->prepare('UPDATE codici_premio SET isActive=? WHERE id=?');
                $stmt->execute([$record['isActive'], $id]);
            } else {
                $stmt = $pdo->prepare('UPDATE codici_premio SET codice=?, prize_id=?, isActive=?, id_codice_partecipazione=? WHERE id=?');
                $stmt->execute([
                    $record['codice'], $record['prize_id'], $record['isActive'],
                    $record['id_codice_partecipazione'], $id,
                ]);
            }
            return;

        case 'quiz_domande':
            $stmt = $pdo->prepare('UPDATE quiz_domande SET testo=?, tipo=?, risposta_a=?, risposta_b=?, risposta_c=?, risposta_d=?, risposta_corretta=?, ordine=?, attiva=? WHERE id=?');
            $stmt->execute([
                $record['testo'], $record['tipo'], $record['risposta_a'], $record['risposta_b'],
                $record['risposta_c'], $record['risposta_d'], $record['risposta_corretta'],
                $record['ordine'], $record['attiva'], $id,
            ]);
            return;
    }
}

/**
 * @param array<string, mixed> $existing
 */
function gestione_dati_crud_delete(PDO $pdo, string $table, int $id, array $existing): ?string
{
    switch ($table) {
        case 'punti_vendita':
            $codice = trim((string) ($existing['codice'] ?? ''));
            if ($codice !== '') {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM partecipazioni WHERE id_pdv_concorso = ?');
                $stmt->execute([$codice]);
                if ((int) $stmt->fetchColumn() > 0) {
                    $upd = $pdo->prepare('UPDATE punti_vendita SET active = 0 WHERE id = ?');
                    $upd->execute([$id]);
                    return null;
                }
            }
            $pdo->prepare('DELETE FROM punti_vendita WHERE id = ?')->execute([$id]);
            gestione_dati_reset_auto_increment_if_empty($pdo, 'punti_vendita');
            return null;

        case 'codici_partecipazione':
            if ((int) ($existing['used'] ?? 0) === 1) {
                return 'Impossibile eliminare un codice già utilizzato.';
            }
            $stmtRef = $pdo->prepare('SELECT COUNT(*) FROM codici_premio WHERE id_codice_partecipazione = ?');
            $stmtRef->execute([$id]);
            if ((int) $stmtRef->fetchColumn() > 0) {
                return 'Codice collegato a codici premio: eliminazione non consentita.';
            }
            $pdo->prepare('DELETE FROM codici_partecipazione WHERE id = ?')->execute([$id]);
            gestione_dati_reset_auto_increment_if_empty($pdo, 'codici_partecipazione');
            return null;

        case 'codici_premio':
            if ((int) ($existing['used'] ?? 0) === 1 || !empty($existing['id_partecipazione'])) {
                return 'Impossibile eliminare un codice premio già assegnato.';
            }
            $pdo->prepare('DELETE FROM codici_premio WHERE id = ?')->execute([$id]);
            gestione_dati_reset_auto_increment_if_empty($pdo, 'codici_premio');
            return null;

        case 'quiz_domande':
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM quiz_tentativi');
            try {
                $stmt->execute();
            } catch (Throwable $e) {
                $pdo->prepare('DELETE FROM quiz_domande WHERE id = ?')->execute([$id]);
                gestione_dati_reset_auto_increment_if_empty($pdo, 'quiz_domande');
                return null;
            }
            $pdo->prepare('DELETE FROM quiz_domande WHERE id = ?')->execute([$id]);
            gestione_dati_reset_auto_increment_if_empty($pdo, 'quiz_domande');
            return null;

        default:
            return 'Tabella non supportata.';
    }
}
