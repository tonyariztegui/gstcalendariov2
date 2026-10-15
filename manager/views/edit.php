<?php include('_header.php'); ?>

<div id="wrapper">

<?php //include("res/inc/utenze.php"); ?>

<div class="content">

<h3>Il tuo Profilo</h3><hr>

	<div class="row">

		<div class="col-md-6">
		<form method="post" action="edit.php" name="user_edit_form_name">
			<strong>Modifica il tuo nome utente</strong> (Username attuale: <?php echo htmlspecialchars((string) ($_SESSION['user_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>)<br><br>
			<input id="user_name" type="text" name="user_name" class="form-control" pattern="[a-zA-Z0-9]{2,64}" style="width:400px" /><br>
			<input type="submit" name="user_edit_submit_name" class="btn btn-info btn-raised btn-sm" value="Modifica Username &rsaquo;" />
		</form>
		</div>

		<div class="col-md-6">
		<form method="post" action="edit.php" name="user_edit_form_email">
			<strong>Modifica il tuo indirizzo email</strong> (Indirizzo attuale: <?php echo htmlspecialchars((string) ($_SESSION['user_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>)<br><br>
			<input id="user_email" type="email" name="user_email" class="form-control" style="width:400px" /><br>
			<input type="submit" name="user_edit_submit_email" class="btn btn-info btn-raised btn-sm" value="Modifica Email &rsaquo;" />
		</form>
		</div>

	</div>

	<hr>

	<div class="row">

		<form method="post" action="edit.php" name="user_edit_form_password">
			
			<div class="col-md-12">
			
			<strong>Modifica la tua Password</strong><br><br>
			Vecchia Password<br>
			<input id="user_password_old" type="password" name="user_password_old" autocomplete="off" class="form-control" style="width:400px" /><br>
			
			</div>
			<div class="col-md-6">
		
			Nuova Password<br>
			<input id="user_password_new" type="password" name="user_password_new" autocomplete="off" class="form-control" style="width:400px" /><br>

			</div>
			<div class="col-md-6">
		
			Ripeti Password<br>
			<input id="user_password_repeat" type="password" name="user_password_repeat" autocomplete="off" class="form-control" style="width:400px" /><br>
			
			</div>

			<input type="submit" name="user_edit_submit_password" class="btn btn-info btn-raised btn-sm" value="Modifica Password &rsaquo;" />
		</form>
	
	</div>

</div>
</div>

<?php include('_footer.php'); ?>
