/**
 * Verifica validità del codice di controllo del codice fiscale.
 * Il valore vuoto è "valido" per semplificare la logica di verifica
 * dell'input, assumendo che l'eventuale l'obbligatorietà del campo
 * sia oggetto di un controllo e retroazione distinti.
 * Per aggiornamenti e ulteriori info v. http://www.icosaedro.it/cf-pi
 * @author Umberto Salsi <salsi@icosaedro.it>
 * @version 2016-12-05
 * @param string cf Codice fiscale da controllare.
 * @return string Stringa vuota se il codice di controllo è
 * corretto oppure il valore è vuoto, altrimenti un messaggio
 * che descrive perché il valore non può essere valido.
 */
function ControllaCF(cf)
{
	cf = cf.toUpperCase();
	if( cf == '' )  return '';
	if( ! /^[0-9A-Z]{16}$/.test(cf) )
		return "Il codice fiscale deve contenere 16 tra lettere e cifre.";
	var map = [1, 0, 5, 7, 9, 13, 15, 17, 19, 21, 1, 0, 5, 7, 9, 13, 15, 17,
		19, 21, 2, 4, 18, 20, 11, 3, 6, 8, 12, 14, 16, 10, 22, 25, 24, 23];
	var s = 0;
	for(var i = 0; i < 15; i++){
		var c = cf.charCodeAt(i);
		if( c < 65 )
			c = c - 48;
		else
			c = c - 55;
		if( i % 2 == 0 )
			s += map[c];
		else
			s += c < 10? c : c - 10;
	}
	var atteso = String.fromCharCode(65 + s % 26);
	if( atteso != cf.charAt(15) )
		return false;
	return "";
}