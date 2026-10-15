<?php 

    $hide_global_notices = true;
	include('_header.php'); 
	include("res/inc/utenze.php");

    $flashErrors = $_SESSION['flash_errors'] ?? [];
    $flashMessages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_errors'], $_SESSION['flash_messages']);

?>

<style>
.login-wrapper{
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#f5f6f8;
    padding:32px 16px;
}
.login-card{
    width:100%;
    max-width:520px;
    box-shadow:0 18px 40px rgba(15,23,42,0.08);
    border-radius:16px;
}
.login-card .card-header{
    background:#f8fafc;
    border-bottom:1px solid #e5e8ef;
}
</style>

<div class="login-wrapper">
    <div class="card login-card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <span class="badge badge-primary p-3 mr-2" style="font-size:16px;"><i class="fa fa-lock"></i></span>
                <div>
                    <div class="font-weight-semibold">Accedi al Manager</div>
                    <small class="text-muted">Inserisci le tue credenziali</small>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php 
                $errorsToShow = !empty($flashErrors) ? $flashErrors : ($login->errors ?? []); 
                $messagesToShow = !empty($flashMessages) ? $flashMessages : ($login->messages ?? []);
            ?>
            <?php if (!empty($errorsToShow)) { ?>
                <div class="alert alert-danger">
                    <?php foreach ($errorsToShow as $error) { echo '<div>' . htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') . '</div>'; } ?>
                </div>
            <?php } ?>
            <?php if (!empty($messagesToShow)) { ?>
                <div class="alert alert-info">
                    <?php foreach ($messagesToShow as $message) { echo '<div>' . htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') . '</div>'; } ?>
                </div>
            <?php } ?>
            <form role="form" method="post" name="loginform" autocomplete="off" action="index.php">
                <div class="form-group">
                    <label for="user_name">Username</label>
                    <input name="user_name" id="user_name" placeholder="Username" type="text" class="form-control" autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="user_password">Password</label>
                    <input name="user_password" id="user_password" placeholder="Password" type="password" class="form-control" autocomplete="current-password">
                </div>
                <input type="hidden" name="login" value="Accedi">
                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-primary btn-lg">Login</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('_footer.php'); ?>
