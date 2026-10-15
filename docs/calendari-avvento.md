# Calendari d’Avvento

La sezione `manager/calendari-avvento` è riservata a `pm_admin`, come Concorso.
Configura il calendario della campagna corrente, mantenendo indipendente la configurazione del concorso.

1. Impostare titolo, introduzione e date (da 1 a 62 giorni inclusi).
2. Salvare in bozza per aggiornare l’elenco delle caselle dopo un cambio di date.
3. Caricare logo e sfondi desktop/mobile, scegliere il colore principale.
4. Configurare ogni casella con titolo premio, descrizione, codice, immagine premio,
   immagine casella e immagine attiva opzionale, anche GIF.
5. Usare **Calendario e premi** nel riquadro Anteprima: si apre una pagina riservata con tutte le caselle
   aperte oppure con una data simulata. Non salva né pubblica la configurazione.
6. Impostare **Pubblicato: Sì** e usare **Salva configurazione** quando tutte le caselle hanno un premio.
   La pagina è disponibile su `public/calendario-avvento`.

## Logica ripresa dal progetto Pasqua

- Intervallo inclusivo, caselle numerate da 1.
- Solo il giorno corrente è aperto, secondo il fuso `Europe/Rome`.
- Il controllo è ripetuto sul server anche quando si apre direttamente il link a un premio.
- Giorno inesistente: 404; passato: 410; futuro: 423; premio mancante: 422.
- Il premio è condiviso per giorno, senza estrazioni, registrazioni o consumo del codice.
- Il barcode EAN-13 segue la normalizzazione del servizio Pasqua: prime 12 cifre
  del codice, completamento tramite CRC32 quando necessario, check digit ricalcolato.
  Il codice originale rimane visibile sotto il barcode.
- L’apertura di tutte le caselle e la simulazione della data sono consentite solo
  nell’anteprima del manager; i parametri pubblici non possono attivarle.

## Persistenza e asset

La configurazione viene salvata atomicamente in `app/config/advent.json` (non
versionato e non accessibile via Apache). Non sono necessarie modifiche al database.
Le immagini caricate finiscono in `uploads/calendari/`, con nomi casuali e verifica
del formato; sono ammessi PNG, JPG, WebP e GIF fino a 6 MB per file, nei limiti PHP
del server. La modifica dell’intervallo conserva i premi fuori dal nuovo periodo.
Le immagini rimosse dalla configurazione non vengono cancellate dal filesystem.

Gli asset e i premi della campagna Coop/Pasqua sono riferimenti: non vengono
pubblicati automaticamente nella nuova campagna.

## Verifica

`php tests/advent_calendar_test.php` verifica stati, intervallo, fuso, accessi
diretti, protezione delle bozze, validazione, barcode e risoluzione delle route.
Controllare inoltre dal manager: salvataggio, anteprima, cambio date, upload,
pubblicazione, visualizzazione desktop/mobile e navigazione da tastiera.
