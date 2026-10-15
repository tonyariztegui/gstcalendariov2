<?php

    include("res/inc/utenze.php");
    include("res/inc/sanitize.inc.php");

    $mex = '';
    $errors = '';

    if (isset($_POST['update_pwd'])) {
        $opwd = sanitize_sql_string($_POST['opwd'] ?? '');
        $pwd = sanitize_sql_string($_POST['pwd'] ?? '');
        $rpwd = sanitize_sql_string($_POST['rpwd'] ?? '');

        $user_id = (int) ($_SESSION['user_id'] ?? 0);
        $select_password = $connection->prepare('SELECT user_password_hash FROM manager_users WHERE user_id = :user_id');
        $select_password->execute([':user_id' => $user_id]);
        $pw = $select_password->fetch(PDO::FETCH_OBJ);

        if (!$pw || !password_verify($opwd, $pw->user_password_hash)) {
            $errors = "Inserisci correttamente la tua vecchia Password";
        }

        if ($errors === '' && strlen($pwd) < 8) {
            $errors = "La password deve essere lunga almeno 8 caratteri";
        }
        if ($errors === '' && !preg_match("#[0-9]+#", $pwd)) {
            $errors = "La password deve includere almeno un numero";
        }
        if ($errors === '' && !preg_match("#[a-zA-Z]+#", $pwd)) {
            $errors = "La password deve includere almeno una lettera";
        }
        if ($errors === '' && !preg_match('/[\'\/~`\!@#\$%\^&\*\(\)_\-\+=\{\}\[\]\|;:"\<\>,\.\?\\\]/', $pwd)) {
            $errors = "La password deve includere almeno una carattere speciale";
        }
        if ($errors === '' && $pwd != $rpwd) {
            $errors = "Le password inserite non corrispondono";
        }
        if ($errors === '' && $pwd == "") {
            $errors = "Inserisci la tua password 2 volte";
        }

        if ($errors === '') {
            $hash_cost_factor = '10';
            $password = password_hash($pwd, PASSWORD_DEFAULT, array('cost' => $hash_cost_factor));
            $update_password = $connection->prepare('UPDATE manager_users SET user_password_hash = :password WHERE user_id = :user_id');
            $update_password->execute([':password' => $password, ':user_id' => $user_id]);
            header('Location: password?ok=1');
            exit;
        }

        $mex = '<div class="alert alert-danger mb-3">'.$errors.'</div>';
    }

    if (isset($_GET['ok'])) {
        $mex = '<div class="alert alert-success mb-3">Password modificata con successo</div>';
    }

    include("_header.php");
    include("res/inc/header.php");

?>

<style>
.pwd-card{max-width:100%;margin:0 auto;}
.pwd-card .form-label{font-weight:600;color:#374151;}
</style>

<div class="app-main__outer">
    <div class="app-main__inner">
        <div class="tabs-animation">
            <div class="card mb-3 pwd-card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="card-header-title font-size-lg font-weight-normal">
                        <i class="fa fa-lock mr-2"></i> Modifica la tua password
                    </div>
                    <button type="submit" form="pwdForm" class="btn btn-success btn-sm">
                        <i class="fa fa-save mr-1"></i> Aggiorna Password
                    </button>
                </div>
                <div class="card-body">
                    <?php echo $mex; ?>
                    <form method="post" name="update_pwd" id="pwdForm">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="opwd" class="form-label">Password attuale</label>
                                <input type="password" name="opwd" id="opwd" class="form-control" placeholder="Inserisci la tua Password attuale">
                            </div>
                            <div class="col-md-6">
                                <label for="pwd" class="form-label">Nuova password</label>
                                <input type="password" name="pwd" id="pwd" class="form-control" placeholder="Inserisci la tua nuova Password">
                            </div>
                            <div class="col-md-6">
                                <label for="rpwd" class="form-label">Ripeti password</label>
                                <input type="password" name="rpwd" id="rpwd" class="form-control" placeholder="Ripeti la tua nuova Password">
                            </div>
                        </div>
                        <input type="hidden" name="update_pwd" value="1">
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</div>

<?php include("_footer.php"); ?>
