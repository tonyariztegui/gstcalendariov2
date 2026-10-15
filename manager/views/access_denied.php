<?php
$scripts = '';
include("_header.php");
include("res/inc/header.php");
?>

<div class="app-main__outer">
    <div class="app-main__inner">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card mt-5">
                    <div class="card-body text-center py-5">
                        <div class="mb-4">
                            <i class="fa fa-lock fa-4x text-danger"></i>
                        </div>
                        <h3 class="text-danger mb-3">Accesso Negato</h3>
                        <p class="text-muted mb-4">
                            Non hai i permessi necessari per accedere a questa pagina.<br>
                            Contatta l'amministratore se ritieni di dover avere accesso.
                        </p>
                        <a href="index.php" class="btn btn-primary">
                            <i class="fa fa-home mr-2"></i>Torna alla Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("_footer.php"); ?>


