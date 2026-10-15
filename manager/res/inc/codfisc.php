<?php

include("cfisc.class.php");

$cognome = $_POST['cognome'] ?? '';
$nome = $_POST['nome'] ?? '';
$dn = $_POST['dn'] ?? '';
$sesso = $_POST['sesso'] ?? '';
$luogo_ct = $_POST['luogo_ct'] ?? '';
$luogo_pr = $_POST['luogo_pr'] ?? '';
$luogo = $_POST['luogo'] ?? '';

$CF = new CodiceFiscale();
//$codice = $CF->Calcola("pallino", "pinco", "25-12-2005", "M", "milano", "mi");
		
/**/
$dt = explode("/", $dn);
$dn = $dt[0]."-".$dt[1]."-".$dt[2];
$ln = explode(" - ", $luogo);
		
$codice = $CF->Calcola($cognome, $nome, $dn, $sesso, $luogo_ct, $luogo_pr);
/**/

echo $codice;

?>
