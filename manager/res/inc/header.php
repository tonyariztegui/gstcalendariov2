<?php
$currentPage = isset($_GET['pg']) && $_GET['pg'] !== '' ? $_GET['pg'] : 'dashboard';
$isInstantWin = isset($contest_mode) && strtolower((string)$contest_mode) === 'instantwin';

// Verifica se l'utente è pm_admin
$isAdmin = !empty($_SESSION['user_name']) && $_SESSION['user_name'] === 'pm_admin';

$sidebarLinks = [
    [
        'id' => 'dashboard',
        'label' => 'Dashboard',
        'description' => 'Indicatori generali',
        'href' => 'dashboard',
        'icon' => 'fa-tachometer-alt',
        'match' => ['dashboard', 'home', ''],
        'pill' => ''
    ],
    [
        'id' => 'partecipazioni',
        'label' => 'Partecipazioni',
        'description' => 'Monitoraggio accessi',
        'href' => 'partecipazioni',
        'icon' => 'fa-user-check',
        'match' => ['partecipazioni'],
        'pill' => null
    ],
    [
        'id' => 'utenti',
        'label' => 'Utenti',
        'description' => 'Profilazione',
        'href' => 'utenti',
        'icon' => 'fa-users',
        'match' => ['utenti'],
        'pill' => null
    ],
];

// Aggiungi Concorso e Sicurezza solo se l'utente è pm_admin
if ($isAdmin) {
    $sidebarLinks[] = [
        'id' => 'security',
        'label' => 'Sicurezza',
        'description' => 'Blacklist e anti-abuso',
        'href' => 'security',
        'icon' => 'fa-shield-alt',
        'match' => ['security', 'email_blacklist'],
        'pill' => null
    ];
    $sidebarLinks[] = [
        'id' => 'concorso',
        'label' => 'Concorso',
        'description' => 'Configurazione contenuti',
        'href' => 'concorso',
        'icon' => 'fa-cog',
        'match' => ['concorso'],
        'pill' => null
    ];
}

if ($isInstantWin) {
    $sidebarLinks[] = [
        'id' => 'premi',
        'label' => 'Premi',
        'description' => 'Catalogo ricompense',
        'href' => 'premi',
        'icon' => 'fa-gift',
        'match' => ['premi'],
        'pill' => null
    ];
}

// Rimosso set_password dal drawer
$isActiveLink = static function (array $link) use ($currentPage): bool {
    return in_array($currentPage, $link['match'], true);
};
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm dashboard-navbar">
    <div class="container-fluid">
        <div class="d-flex align-items-center">
            <button class="navbar-toggler sidebar-toggler d-lg-none mr-2" type="button" aria-controls="sidebarNavCollapse" aria-expanded="false" aria-label="Apri menu laterale">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand d-flex align-items-center font-weight-bold text-primary" href="dashboard">
                <span>GST Manager</span>
            </a>
        </div>
        <div class="d-flex align-items-center ml-auto topbar-inline">
            <span class="text-muted small text-uppercase tracking-wide mr-3 d-none d-sm-inline">Area Manager</span>
            <div class="dropdown">
                <button class="btn btn-light btn-sm drawer-toggle d-flex align-items-center justify-content-center" type="button" id="actionsDrawer" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-ellipsis-v mr-2"></i>
                    <span>Menu</span>
                </button>
                <div class="dropdown-menu dropdown-menu-right shadow-sm actions-drawer" aria-labelledby="actionsDrawer">
                    <a class="dropdown-item" href="password">
                        <i class="fa fa-lock mr-2 text-muted"></i> Password
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="dashboard?logout">
                        <i class="fa fa-sign-out mr-2"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<div class="dashboard-wrapper d-flex">
    <aside class="dashboard-sidebar border-right bg-white">
        <div class="sidebar-inner d-lg-block" id="sidebarNavCollapse">
            <div class="sidebar-profile media align-items-center mb-4">
                <div class="avatar avatar-sm mr-3">M</div>
                <div class="media-body">
                    <p class="mb-0 font-weight-semibold">Manager</p>
                    <small class="text-muted">Dashboard concorso</small>
                </div>
            </div>
            <div class="list-group list-group-flush sidebar-nav" id="sidebarNavList">
                <?php foreach ($sidebarLinks as $link) {
                    $isActive = $isActiveLink($link);
                ?>
                <a href="<?php echo $link['href']; ?>" class="list-group-item list-group-item-action d-flex align-items-center <?php echo $isActive ? 'active' : ''; ?>" data-sidebar-label="<?php echo strtolower($link['label'].' '.$link['description']); ?>">
                    <span class="sidebar-icon mr-3">
                        <i class="fa <?php echo $link['icon']; ?>"></i>
                    </span>
                    <span class="flex-fill">
                        <span class="d-block font-weight-semibold"><?php echo $link['label']; ?></span>
                        <small class="text-muted d-block"><?php echo $link['description']; ?></small>
                    </span>
                    <?php if (!empty($link['pill'])) { ?>
                        <span class="badge badge-soft-primary"><?php echo $link['pill']; ?></span>
                    <?php } ?>
                </a>
                <?php } ?>
            </div>
        </div>
    </aside>
    <div class="sidebar-backdrop d-lg-none" id="sidebarBackdrop"></div>
    <div class="dashboard-content flex-grow-1 bg-light">
        <div class="dashboard-content__inner container-fluid py-4">
