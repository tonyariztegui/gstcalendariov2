<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_name']) || $_SESSION['user_name'] !== 'pm_admin') {
    include('_header.php');
    include('res/inc/header.php');
    include('access_denied.php');
    include('_footer.php');
    exit;
}

include('res/inc/utenze.php');
require_once __DIR__ . '/../../app/logic/contest_security.php';

$allowedTabs = ['emails', 'domains', 'ips', 'rules', 'log'];
$activeTab = strtolower(trim((string) ($_GET['tab'] ?? $_POST['tab'] ?? 'emails')));
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'emails';
}

$buildSecurityRedirect = static function (string $tab, array $extra = []): string {
    $params = array_merge(['tab' => $tab], $extra);
    foreach ($params as $k => $v) {
        if ($v === '' || $v === null || ($k !== 'tab' && (int) $v <= 0)) {
            unset($params[$k]);
        }
    }

    return 'security' . ($params !== [] ? '?' . http_build_query($params) : '');
};

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = trim((string) ($_POST['action'] ?? ''));
    $createdBy = trim((string) ($_SESSION['user_name'] ?? ''));
    $redirectTab = trim((string) ($_POST['tab'] ?? 'emails'));
    if (!in_array($redirectTab, $allowedTabs, true)) {
        $redirectTab = 'emails';
    }
    $flash = ['type' => 'danger', 'message' => 'Azione non riconosciuta.'];

    switch ($action) {
        case 'email_add':
            $result = email_blacklist_add(
                $connection,
                trim((string) ($_POST['email'] ?? '')),
                trim((string) ($_POST['note'] ?? '')),
                $createdBy
            );
            $flash = ['type' => $result['ok'] ? 'success' : 'danger', 'message' => $result['message']];
            $redirectTab = 'emails';
            $extra = [];
            if (trim((string) ($_POST['search'] ?? '')) !== '') {
                $extra['search'] = trim((string) $_POST['search']);
            }
            if ((int) ($_POST['page'] ?? 1) > 1) {
                $extra['page'] = (int) $_POST['page'];
            }
            $_SESSION['security_flash'] = $flash;
            header('Location: ' . $buildSecurityRedirect($redirectTab, $extra));
            exit;

        case 'email_remove':
            $removed = email_blacklist_remove($connection, (int) ($_POST['id'] ?? 0));
            $flash = [
                'type' => $removed ? 'success' : 'danger',
                'message' => $removed ? 'Email rimossa dalla blacklist.' : 'Impossibile rimuovere la voce selezionata.',
            ];
            $redirectTab = 'emails';
            $extra = [];
            if (trim((string) ($_POST['search'] ?? '')) !== '') {
                $extra['search'] = trim((string) $_POST['search']);
            }
            if ((int) ($_POST['page'] ?? 1) > 1) {
                $extra['page'] = (int) $_POST['page'];
            }
            $_SESSION['security_flash'] = $flash;
            header('Location: ' . $buildSecurityRedirect($redirectTab, $extra));
            exit;

        case 'domain_add':
            $result = contest_security_domain_add(
                $connection,
                trim((string) ($_POST['domain'] ?? '')),
                trim((string) ($_POST['note'] ?? '')),
                $createdBy
            );
            $_SESSION['security_flash'] = ['type' => $result['ok'] ? 'success' : 'danger', 'message' => $result['message']];
            header('Location: ' . $buildSecurityRedirect('domains', array_filter([
                'domain_search' => trim((string) ($_POST['domain_search'] ?? '')),
                'domain_page' => (int) ($_POST['domain_page'] ?? 1) > 1 ? (int) $_POST['domain_page'] : null,
            ])));
            exit;

        case 'domain_remove':
            $removed = contest_security_domain_remove($connection, (int) ($_POST['id'] ?? 0));
            $_SESSION['security_flash'] = [
                'type' => $removed ? 'success' : 'danger',
                'message' => $removed ? 'Dominio rimosso.' : 'Impossibile rimuovere.',
            ];
            header('Location: ' . $buildSecurityRedirect('domains', array_filter([
                'domain_search' => trim((string) ($_POST['domain_search'] ?? '')),
                'domain_page' => (int) ($_POST['domain_page'] ?? 1) > 1 ? (int) $_POST['domain_page'] : null,
            ])));
            exit;

        case 'ip_add':
            $result = contest_security_ip_add(
                $connection,
                trim((string) ($_POST['ip_address'] ?? '')),
                trim((string) ($_POST['note'] ?? '')),
                $createdBy
            );
            $_SESSION['security_flash'] = ['type' => $result['ok'] ? 'success' : 'danger', 'message' => $result['message']];
            header('Location: ' . $buildSecurityRedirect('ips', array_filter([
                'ip_search' => trim((string) ($_POST['ip_search'] ?? '')),
                'ip_page' => (int) ($_POST['ip_page'] ?? 1) > 1 ? (int) $_POST['ip_page'] : null,
            ])));
            exit;

        case 'ip_remove':
            $removed = contest_security_ip_remove($connection, (int) ($_POST['id'] ?? 0));
            $_SESSION['security_flash'] = [
                'type' => $removed ? 'success' : 'danger',
                'message' => $removed ? 'IP rimosso.' : 'Impossibile rimuovere.',
            ];
            header('Location: ' . $buildSecurityRedirect('ips', array_filter([
                'ip_search' => trim((string) ($_POST['ip_search'] ?? '')),
                'ip_page' => (int) ($_POST['ip_page'] ?? 1) > 1 ? (int) $_POST['ip_page'] : null,
            ])));
            exit;

        case 'save_settings':
            $toggleResult = contest_security_codice_protection_set_enabled(isset($_POST['codice_partecipazione_security_enabled']));
            contest_security_save_settings($connection, [
                'registration_ip_max_per_day' => $_POST['registration_ip_max_per_day'] ?? 0,
                'registration_email_max_per_day' => $_POST['registration_email_max_per_day'] ?? 0,
                'registration_cooldown_seconds' => $_POST['registration_cooldown_seconds'] ?? 0,
                'log_retention_days' => $_POST['log_retention_days'] ?? 90,
                'codice_fail_max_per_window' => $_POST['codice_fail_max_per_window'] ?? 0,
                'codice_fail_window_seconds' => $_POST['codice_fail_window_seconds'] ?? 0,
                'codice_lockout_level1_seconds' => $_POST['codice_lockout_level1_seconds'] ?? 0,
                'codice_lockout_level2_seconds' => $_POST['codice_lockout_level2_seconds'] ?? 0,
                'codice_lockout_level3_seconds' => $_POST['codice_lockout_level3_seconds'] ?? 0,
                'codice_blacklist_at_strike' => $_POST['codice_blacklist_at_strike'] ?? 4,
            ]);
            if (!$toggleResult['ok']) {
                $_SESSION['security_flash'] = ['type' => 'danger', 'message' => $toggleResult['message']];
            } else {
                $_SESSION['security_flash'] = ['type' => 'success', 'message' => 'Impostazioni di sicurezza salvate.'];
            }
            header('Location: ' . $buildSecurityRedirect('rules'));
            exit;
    }

    $_SESSION['security_flash'] = $flash;
    header('Location: ' . $buildSecurityRedirect($redirectTab));
    exit;
}

include('_header.php');
include('res/inc/header.php');
include('res/inc/sanitize.inc.php');

$_title = 'Sicurezza';

$flash = $_SESSION['security_flash'] ?? null;
unset($_SESSION['security_flash']);

contest_security_ensure_tables($connection);

$perPage = 25;

// Emails tab data
$searchTerm = trim((string) ($_GET['search'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$totalRecords = email_blacklist_count($connection, $searchTerm);
$totalPages = max(1, (int) ceil($totalRecords / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;
$rows = email_blacklist_list($connection, $searchTerm, $perPage, $offset);
$startRecord = $totalRecords > 0 ? $offset + 1 : 0;
$endRecord = $totalRecords > 0 ? min($offset + $perPage, $totalRecords) : 0;

$buildSecurityUrl = static function (string $tab, int $targetPage = 1, string $search = '') use ($perPage): string {
    $params = ['tab' => $tab];
    if ($search !== '') {
        $params['search'] = $search;
    }
    if ($targetPage > 1) {
        $params['page'] = $targetPage;
    }

    return 'security?' . http_build_query($params);
};

// Domains
$domainSearch = trim((string) ($_GET['domain_search'] ?? ''));
$domainPage = max(1, (int) ($_GET['domain_page'] ?? 1));
$domainTotal = contest_security_domain_count($connection, $domainSearch);
$domainTotalPages = max(1, (int) ceil($domainTotal / $perPage));
if ($domainPage > $domainTotalPages) {
    $domainPage = $domainTotalPages;
}
$domainOffset = ($domainPage - 1) * $perPage;
$domainRows = contest_security_domain_list($connection, $domainSearch, $perPage, $domainOffset);
$domainStart = $domainTotal > 0 ? $domainOffset + 1 : 0;
$domainEnd = $domainTotal > 0 ? min($domainOffset + $perPage, $domainTotal) : 0;

// IPs
$ipSearch = trim((string) ($_GET['ip_search'] ?? ''));
$ipPage = max(1, (int) ($_GET['ip_page'] ?? 1));
$ipTotal = contest_security_ip_count($connection, $ipSearch);
$ipTotalPages = max(1, (int) ceil($ipTotal / $perPage));
if ($ipPage > $ipTotalPages) {
    $ipPage = $ipTotalPages;
}
$ipOffset = ($ipPage - 1) * $perPage;
$ipRows = contest_security_ip_list($connection, $ipSearch, $perPage, $ipOffset);

// Rules
$activeRules = contest_security_get_active_rules($connection);
$securitySettings = contest_security_get_settings($connection);
global $contest_config;
$contestConfigArr = is_array($contest_config ?? null) ? $contest_config : [];
$codiceProtectionContext = contest_security_codice_protection_get_context($connection, $contestConfigArr);
$codiceProtectionEnabled = !empty($contestConfigArr['codice_partecipazione_security_enabled']);

// Log
$logSearch = trim((string) ($_GET['log_search'] ?? ''));
$logPage = max(1, (int) ($_GET['log_page'] ?? 1));
$logTotal = contest_security_log_count($connection, $logSearch);
$logTotalPages = max(1, (int) ceil($logTotal / $perPage));
if ($logPage > $logTotalPages) {
    $logPage = $logTotalPages;
}
$logOffset = ($logPage - 1) * $perPage;
$logRows = contest_security_log_list($connection, $logSearch, $perPage, $logOffset);
$logStart = $logTotal > 0 ? $logOffset + 1 : 0;
$logEnd = $logTotal > 0 ? min($logOffset + $perPage, $logTotal) : 0;

$tabLabels = [
    'emails' => 'Email',
    'domains' => 'Domini',
    'ips' => 'IP',
    'rules' => 'Regole',
    'log' => 'Registro',
];

?>

<div class="app-main__outer">
    <div class="app-main__inner">
        <div class="tabs-animation">
            <div class="card mb-3">
                <div class="card-header-tab card-header">
                    <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
                        <i class="header-icon fa fa-shield-alt icon-gradient bg-happy-green"></i>
                        <?php echo htmlspecialchars($_title, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (is_array($flash) && !empty($flash['message'])): ?>
                        <div class="alert alert-<?php echo htmlspecialchars((string) ($flash['type'] ?? 'info'), ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars((string) $flash['message'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>

                    <p class="text-muted mb-4">
                        Centro di controllo sicurezza del concorso : blacklist, limiti anti-abuso e registro eventi.
                        Le iscrizioni bloccate mostrano un messaggio generico all'utente (anti-enumerazione).
                    </p>

                    <ul class="nav nav-tabs mb-4" role="tablist">
                        <?php foreach ($tabLabels as $tabId => $tabLabel): ?>
                            <li class="nav-item">
                                <a
                                    class="nav-link <?php echo $activeTab === $tabId ? 'active' : ''; ?>"
                                    href="security?tab=<?php echo urlencode($tabId); ?>"
                                ><?php echo htmlspecialchars($tabLabel, ENT_QUOTES, 'UTF-8'); ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="tab-content">
                        <?php if ($activeTab === 'emails'): ?>
                            <?php include __DIR__ . '/security/tab-emails.php'; ?>
                        <?php elseif ($activeTab === 'domains'): ?>
                            <?php include __DIR__ . '/security/tab-domains.php'; ?>
                        <?php elseif ($activeTab === 'ips'): ?>
                            <?php include __DIR__ . '/security/tab-ips.php'; ?>
                        <?php elseif ($activeTab === 'rules'): ?>
                            <?php include __DIR__ . '/security/tab-rules.php'; ?>
                        <?php else: ?>
                            <?php include __DIR__ . '/security/tab-log.php'; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    function confirmSecurityRemove(form) {
        var msg = form.getAttribute('data-confirm') || 'Confermi la rimozione?';
        if (typeof Swal === 'undefined') {
            if (window.confirm(msg)) {
                form.submit();
            }
            return;
        }
        Swal.fire({
            title: '',
            text: msg,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#48b461',
            confirmButtonText: 'Conferma',
            cancelButtonText: 'Annulla',
            reverseButtons: true
        }).then(function (result) {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }

    document.addEventListener('click', function (event) {
        var btn = event.target.closest('.btn-security-remove');
        if (!btn) {
            return;
        }
        event.preventDefault();
        var form = btn.closest('form');
        if (form) {
            confirmSecurityRemove(form);
        }
    });
})();
</script>

<?php include('_footer.php'); ?>
