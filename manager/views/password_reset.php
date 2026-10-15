<?php include('_header.php'); ?>

<link href="res/css/login.css" rel="stylesheet">

<div class="container">
	<div class="row">	
	  <div class="col-sm-12 text-center">
	  	<img src="res/img/logo-rssa.png" alt="" style="width:240px;margin:40px auto 20px auto">
	  </div>

<?php if ($login->passwordResetLinkIsValid() == true) { ?>

<form method="post" action="password_reset.php" name="new_password_form" class="form-signin" role="form" autocomplete="off">
	
    <input type='hidden' name='user_name' value='<?php echo htmlspecialchars((string)($_GET['user_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>' />
	<input type='hidden' name='user_password_reset_hash' value='<?php echo htmlspecialchars((string)($_GET['verification_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>' />

	<input id="user_password_new" type="password" name="user_password_new" pattern=".{6,}" class="form-control" autocomplete="off" placeholder="Nuova Password" />
	<input id="user_password_repeat" type="password" name="user_password_repeat" pattern=".{6,}" class="form-control" autocomplete="off" placeholder="Ripeti Password" />
	
    <input type="submit" name="submit_new_password" class="btn" value="Reimposta Password &rsaquo;" />
    
</form>

<?php } else { ?>

<form method="post" action="password_reset.php" name="password_reset_form" class="form-signin" role="form" autocomplete="off">
	
    <p class="cntr">Inserisci il tuo username. Riceverai una mail con i dettagli per poter reimpostare la tua password.</p><br>
    
	<input id="user_name" type="text" name="user_name" class="form-control" />
	<input type="submit" name="request_password_reset" class="btn" value="Reimposta Password &rsaquo;" />
    
</form>

<?php } ?>

	  <div class="cntr text-center">
      <br /><a href="index.php">&lsaquo; Torna alla pagina di Login</a>
      </div>

	</div>
</div>

<?php include('_footer.php'); ?>