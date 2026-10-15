<?php
/**
 * AJAX endpoint per recuperare province e città dalla tabella istat_cities_it
 */

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

include_once __DIR__ . '/../../app/inc/config.php';
include_once __DIR__ . '/../../app/inc/functions.php';

try {
    $pdo = conn_pdo();

    $action = $_GET['action'] ?? '';

    if ($action === 'province') {
        $search = isset($_GET['q']) ? trim((string) $_GET['q']) : '';

        $query = "SELECT DISTINCT denominazione_unita_territoriale_sovracomunale AS provincia
                  FROM istat_cities_it
                  WHERE denominazione_unita_territoriale_sovracomunale IS NOT NULL
                  AND denominazione_unita_territoriale_sovracomunale != ''";

        $params = [];
        if ($search !== '') {
            $query .= ' AND denominazione_unita_territoriale_sovracomunale LIKE :search';
            $params[':search'] = '%' . $search . '%';
        }

        $query .= ' ORDER BY denominazione_unita_territoriale_sovracomunale ASC';

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $province = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode(['success' => true, 'data' => $province]);

    } elseif ($action === 'cities') {
        $provincia = isset($_GET['provincia']) ? trim((string) $_GET['provincia']) : '';
        $search = isset($_GET['q']) ? trim((string) $_GET['q']) : '';

        $query = "SELECT DISTINCT denominazione_italiano AS citta
                  FROM istat_cities_it
                  WHERE denominazione_italiano IS NOT NULL
                  AND denominazione_italiano != ''";

        $params = [];
        if ($provincia !== '') {
            $query .= ' AND denominazione_unita_territoriale_sovracomunale = :provincia';
            $params[':provincia'] = $provincia;
        }
        if ($search !== '') {
            $query .= ' AND denominazione_italiano LIKE :search';
            // Sans provincia (mode CAP): préfixe C% / CE% ; avec provincia: contient %q%
            $params[':search'] = ($provincia === '')
                ? ($search . '%')
                : ('%' . $search . '%');
        }

        // Senza provincia: richiede un termine di ricerca (evita dump di tutte le città)
        if ($provincia === '' && $search === '') {
            $query .= ' ORDER BY denominazione_italiano ASC LIMIT 50';
        } else {
            $query .= ' ORDER BY denominazione_italiano ASC';
            if ($provincia === '') {
                $query .= ' LIMIT 100';
            }
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $cities = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode(['success' => true, 'data' => $cities]);

    } else {
        echo json_encode(['success' => false, 'error' => 'Azione non valida']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

