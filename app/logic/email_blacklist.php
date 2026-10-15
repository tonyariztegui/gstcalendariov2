<?php
/**
 * Blacklist email: blocca la partecipazione al concorso per indirizzi specifici.
 */

if (!function_exists('email_blacklist_normalize')) {
    function email_blacklist_normalize(string $email): string
    {
        return strtolower(trim($email));
    }
}

if (!function_exists('email_blacklist_ensure_table')) {
    function email_blacklist_ensure_table(PDO $pdo): void
    {
        static $done = [];
        $cid = spl_object_id($pdo);
        if (isset($done[$cid])) {
            return;
        }
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `email_blacklist` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `email` VARCHAR(254) NOT NULL,
                `note` VARCHAR(500) NULL DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `created_by` VARCHAR(100) NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_email_blacklist_email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $done[$cid] = true;
    }
}

if (!function_exists('email_blacklist_is_blocked')) {
    function email_blacklist_is_blocked(PDO $pdo, string $email): bool
    {
        $email = email_blacklist_normalize($email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        email_blacklist_ensure_table($pdo);
        $stmt = $pdo->prepare('SELECT 1 FROM email_blacklist WHERE email = :email LIMIT 1');
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }
}

if (!function_exists('email_blacklist_add')) {
    /**
     * @return array{ok: bool, message: string}
     */
    function email_blacklist_add(PDO $pdo, string $email, ?string $note = null, ?string $createdBy = null): array
    {
        $email = email_blacklist_normalize($email);
        if ($email === '') {
            return ['ok' => false, 'message' => 'Inserisci un indirizzo email.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Indirizzo email non valido.'];
        }
        if (strlen($email) > 254) {
            return ['ok' => false, 'message' => 'Email troppo lunga (max 254 caratteri).'];
        }

        $note = $note !== null ? trim($note) : '';
        if (strlen($note) > 500) {
            $note = mb_substr($note, 0, 500, 'UTF-8');
        }
        $createdBy = $createdBy !== null ? trim($createdBy) : '';
        if (strlen($createdBy) > 100) {
            $createdBy = mb_substr($createdBy, 0, 100, 'UTF-8');
        }

        email_blacklist_ensure_table($pdo);

        try {
            $stmt = $pdo->prepare('
                INSERT INTO email_blacklist (email, note, created_by)
                VALUES (:email, :note, :created_by)
            ');
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->bindValue(':note', $note !== '' ? $note : null, $note !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':created_by', $createdBy !== '' ? $createdBy : null, $createdBy !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->execute();

            return ['ok' => true, 'message' => 'Email aggiunta alla blacklist.'];
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                return ['ok' => false, 'message' => 'Questa email è già in blacklist.'];
            }
            error_log('email_blacklist_add: ' . $e->getMessage());

            return ['ok' => false, 'message' => 'Errore durante il salvataggio.'];
        }
    }
}

if (!function_exists('email_blacklist_remove')) {
    function email_blacklist_remove(PDO $pdo, int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        email_blacklist_ensure_table($pdo);
        $stmt = $pdo->prepare('DELETE FROM email_blacklist WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}

if (!function_exists('email_blacklist_count')) {
    function email_blacklist_count(PDO $pdo, string $search = ''): int
    {
        email_blacklist_ensure_table($pdo);
        $search = trim($search);
        if ($search === '') {
            return (int) $pdo->query('SELECT COUNT(*) FROM email_blacklist')->fetchColumn();
        }
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM email_blacklist WHERE email LIKE :search OR note LIKE :search');
        $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }
}

if (!function_exists('email_blacklist_list')) {
    /**
     * @return list<array{id: int, email: string, note: ?string, created_at: string, created_by: ?string}>
     */
    function email_blacklist_list(PDO $pdo, string $search = '', int $limit = 50, int $offset = 0): array
    {
        email_blacklist_ensure_table($pdo);
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        $search = trim($search);

        if ($search === '') {
            $stmt = $pdo->prepare('
                SELECT id, email, note, created_at, created_by
                FROM email_blacklist
                ORDER BY created_at DESC, id DESC
                LIMIT :limit OFFSET :offset
            ');
        } else {
            $stmt = $pdo->prepare('
                SELECT id, email, note, created_at, created_by
                FROM email_blacklist
                WHERE email LIKE :search OR note LIKE :search
                ORDER BY created_at DESC, id DESC
                LIMIT :limit OFFSET :offset
            ');
            $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rows[] = [
                'id' => (int) ($row['id'] ?? 0),
                'email' => (string) ($row['email'] ?? ''),
                'note' => isset($row['note']) ? (string) $row['note'] : null,
                'created_at' => (string) ($row['created_at'] ?? ''),
                'created_by' => isset($row['created_by']) ? (string) $row['created_by'] : null,
            ];
        }

        return $rows;
    }
}
