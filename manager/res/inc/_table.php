<?php

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    header('Content-Type: application/json; charset=utf-8');

    include(__DIR__ . "/../../config/config.php");

    // Forcer l'encoding comme dans l'export
    $connection->exec("SET NAMES utf8mb4");
    $connection->exec("SET CHARACTER SET utf8mb4");
    $connection->exec("SET collation_connection = 'utf8mb4_unicode_ci'");

    // Check user is logged in
    if (!isset($_SESSION["user_id"])) {
        http_response_code(401);
        $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 0;
        echo json_encode([
            "data" => [],
            "recordsTotal" => 0,
            "recordsFiltered" => 0,
            "draw" => $draw,
            "error" => "Sessione scaduta. Effettua nuovamente il login."
        ]);
        exit;
    }

    // Table principale : utenti
    $primaryKey = 'ID_CLIENTE';

    // Helper per uniformare le label boolean
    function formatFlag($value, $labelTrue, $labelFalse)
    {
        if ((int)$value === 1) {
            return '<span class="badge bg-success">'.$labelTrue.'</span>';
        }
        return '<span class="badge bg-danger">'.$labelFalse.'</span>';
    }

    function formatEmail(?string $value): string
    {
        $trimmed = trim((string)$value);
        if ($trimmed === '') {
            return '<span class="text-muted">-</span>';
        }

        return htmlspecialchars($trimmed, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    // Paramètres DataTables
    $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $length = $length > 0 ? $length : 10;
    $search = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';
    $order_column = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0; // default ID desc
    $order_dir = isset($_POST['order'][0]['dir']) && strtolower($_POST['order'][0]['dir']) === 'asc' ? 'ASC' : 'DESC';

    // Mapping des colonnes pour le tri
    $columns_map = [
        'u.id_cliente',
        'u.nome',
        'u.cognome',
        'u.email',
        "CONCAT(COALESCE(u.prefisso, ''), ' ', COALESCE(u.cellulare, ''))",
        'u.privacy',
        'u.marketing',
        'u.profilazione',
        'part.totale_partecipazioni',
        'u.id_cliente'
    ];

    if (!isset($columns_map[$order_column])) {
        $order_column = 0; // ID
    }
    $order_by = $columns_map[$order_column];

    // Construction de la clause WHERE
    $where_conditions = [];
    $params = [];

    if ($search !== '') {
        $like = "%$search%";
        $where_conditions[] = "(CAST(u.id_cliente AS CHAR) LIKE ?
            OR u.nome LIKE ?
            OR u.cognome LIKE ?
            OR u.email LIKE ?
            OR u.cellulare LIKE ?
            OR CONCAT(COALESCE(u.prefisso, ''), ' ', COALESCE(u.cellulare, '')) LIKE ?
            OR CAST(COALESCE(part.totale_partecipazioni, 0) AS CHAR) LIKE ?)";
        $params = array_fill(0, 7, $like);
    }

    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

    $join_clause = "LEFT JOIN (
            SELECT id_cliente, COUNT(*) AS totale_partecipazioni
            FROM partecipazioni
            GROUP BY id_cliente
        ) part ON part.id_cliente = u.id_cliente";

    // Compte total et filtré
    $total_records = (int)$connection->query("SELECT COUNT(*) FROM utenti")->fetchColumn();

    if (!empty($where_clause)) {
        $count_stmt = $connection->prepare("SELECT COUNT(*) FROM utenti u $join_clause $where_clause");
        $count_stmt->execute($params);
        $filtered_records = (int)$count_stmt->fetchColumn();
    } else {
        $filtered_records = $total_records;
    }

    // Requête principale avec pagination
    $sql = "SELECT
                u.id_cliente,
                u.nome,
                u.cognome,
                u.email,
                u.prefisso,
                u.cellulare,
                u.privacy,
                u.marketing,
                u.profilazione,
                COALESCE(part.totale_partecipazioni, 0) AS totale_partecipazioni
            FROM utenti u
            $join_clause
            $where_clause
            ORDER BY $order_by $order_dir
            LIMIT ? OFFSET ?";

    $stmt = $connection->prepare($sql);
    $paramIndex = 1;
    foreach ($params as $value) {
        $stmt->bindValue($paramIndex, $value, PDO::PARAM_STR);
        $paramIndex++;
    }
    $stmt->bindValue($paramIndex, (int)$length, PDO::PARAM_INT);
    $paramIndex++;
    $stmt->bindValue($paramIndex, (int)$start, PDO::PARAM_INT);

    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formater les données comme DataTables les attend
    $data = [];
    foreach ($rows as $row) {
        $partecipazioni_count = (int)($row['totale_partecipazioni'] ?? 0);
        if ($partecipazioni_count > 0) {
            $partecipazioni_html = '<button class="btn btn-sm btn-outline-secondary btnPartecipazioni" data-user="' . (int)$row['id_cliente'] . '" data-count="' . $partecipazioni_count . '">' . $partecipazioni_count . ' <i class="fa fa-eye ms-1"></i></button>';
        } else {
            $partecipazioni_html = '<span class="badge bg-secondary">0</span>';
        }

        $prefisso = (string)($row['prefisso'] ?? '');
        $cellulareRaw = (string)($row['cellulare'] ?? '');
        $cellulareDisplay = formatPhoneWithPrefix($prefisso, $cellulareRaw);

        $data[] = [
            'DT_RowId' => 'row_' . $row['id_cliente'],
            'id_cliente' => (int)$row['id_cliente'],
            'nome' => htmlspecialchars($row['nome'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'cognome' => htmlspecialchars($row['cognome'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'email' => formatEmail($row['email'] ?? null),
            'cellulare' => htmlspecialchars($cellulareDisplay, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'privacy' => formatFlag($row['privacy'], 'Accettata', 'Non accettata'),
            'marketing' => formatFlag($row['marketing'], 'Consentito', 'Non consentito'),
            'profilazione' => formatFlag($row['profilazione'], 'Attiva', 'Non attiva'),
            'partecipazioni' => $partecipazioni_html,
            'storico' => '<button class="btn btn-sm btn-outline-primary btnFidelityHistory" data-user="' . (int)$row['id_cliente'] . '">Storico</button>'
        ];
    }

    $result = [
        'data' => $data,
        'recordsTotal' => $total_records,
        'recordsFiltered' => $filtered_records,
        'draw' => isset($_POST['draw']) ? (int)$_POST['draw'] : 1
    ];

    echo json_encode($result);
?>
