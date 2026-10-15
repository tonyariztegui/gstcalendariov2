GUIDA RAPIDA ASSET GRAFICI

Documento allineato al manager Concorso (step Branding, Form & campi, Footer, Email, Quiz).

Layout base del sito
- Sfondo full page.
- Logo in alto (e/o header bandiera, se attivato).
- Box centrale (rettangolo/card) con contenuto principale.
- Footer in basso.

Pagine e struttura visiva
- Welcome:
  - box centrale con testo introduttivo.
  - nessun form.
- Waiting:
  - box centrale con titolo, testo breve e pulsante.
  - possibile immagine hero dentro il container (editor HTML).
- Waiting ended:
  - stessa struttura della waiting.
  - messaggio di concorso terminato.
- Registrazione:
  - box centrale con campi form, checkbox privacy/consensi, pulsante invio.
  - eventualmente immagine decorativa nel box (container form o pagina).
- Quiz scelta modalita:
  - box centrale con titolo, breve istruzione, pulsante continua.
- Quiz domande:
  - box centrale con blocchi domanda + risposte + pulsante invio.
- Thank-you:
  - box centrale con messaggio finale.
  - varianti visive: conferma, vincita, non vincita.

---

Asset obbligatori

Minimo consigliato per avviare un concorso con aspetto coerente.

| File (nome salvato) | Dimensioni consigliate | Formato | Manager (step / campo) |
|---------------------|------------------------|---------|-------------------------|
| `logo.png` | 800×400 | PNG trasparente, ratio 2:1 | Step 2 Branding > Logo |
| `logo_mobile.png` | 480×240 | PNG (se vuoto usa logo desktop) | Step 2 Branding > Logo mobile |
| `background_default.jpg` | 1920×1080 | JPG | Step 2 Branding > Background default |
| `background_mobile.jpg` | 1080×1920 | JPG | Step 2 Branding > Background mobile |
| `background_tablet.jpg` | 1280×800 | JPG | Step 2 Branding > Background tablet |
| `background_laptop.jpg` | 1366×768 | JPG | Step 2 Branding > Background laptop |
| `background_desktop.jpg` | 1920×1080 | JPG | Step 2 Branding > Background desktop |
| `background_ultrawide.jpg` | 3440×1440 | JPG | Step 2 Branding > Background ultrawide |
| `mail_banner.jpg` | 600×200 | JPG | Step 2 Branding > Banner email |

Note sui nomi file: in upload il manager salva con nomi fissi (`logo`, `logo_mobile`, `background_default`, `banner`, ecc.) mantenendo l’estensione caricata.

---

Asset opzionali – Branding

| File | Dimensioni consigliate | Formato | Manager (step / campo) |
|------|------------------------|---------|-------------------------|
| `header.png` / `.jpg` | 1920×280 | PNG o JPG, bandiera panoramica | Step 2 Branding > Header desktop (solo se modalita Logo = Header o Logo + Header) |
| `header_mobile.png` | 1080×480 | PNG o JPG | Step 2 Branding > Header mobile |
| `favicon.png` | 32×32 | PNG o ICO quadrato | Step 2 Branding > Favicon |
| `favicon_mobile.png` | 180×180 | PNG (Apple touch icon) | Step 2 Branding > Favicon mobile |

---

Asset opzionali – Layout (box, footer, pulsanti)

| Asset | Dimensioni consigliate | Manager (step / campo) |
|-------|------------------------|-------------------------|
| `form_container.*` | 1200×800 | Step 4 Form & campi > Container form (modalita Immagine) |
| `footer_background.*` | es. 1920×300 (fascia orizzontale) | Step 3 Palette/Footer > Stile footer (modalita Immagine) — un solo file, non desktop/mobile separati |
| `footer_logo.*` | quadrato o 2:1; altezza display 24–200 px nel manager | Step 3 Footer > Personalizzazione footer > Logo footer |
| Container pagina pubblica (ciascuna pagina) | 1200×800 | Step dedicato per pagina (es. Registrazione step 4, Assistenza step 7, Waiting step 6, Thank-you step 5, Quiz step 10) — vedi tabella sotto |
| `button_primary.*` / `button_primary_hover.*` | ratio pulsante ~3:1 o 4:1, PNG trasparente se testo nascosto | Step 3 Palette/Footer > Bottoni (modalita Immagine) |
| `button_secondary.*` / `button_secondary_hover.*` | come sopra | Step 3 Palette/Footer > Bottoni secondario |

Container pagina pubblica (modalita Immagine, 1200×800 ciascuno se usati):
- Registrazione (`public_page_container_registration`)
- Assistenza / FAQ (`help`)
- Pagina attesa, partecipazione, attesa fine
- Thank-you (WIN, LOSE, quiz non superato, partecipazione, estrazione)
- Quiz intestazione e pagina scelta

---

Asset opzionali – Contenuto (editor / email / quiz)

| Tipo | Dimensioni consigliate | Note |
|------|------------------------|------|
| Hero waiting / thank-you | 1200×600 | Non e un upload con nome fisso: immagine inserita nell’editor container (Summernote) nelle pagine Waiting o Thank-you |
| Immagini premio email | 600×400 | Step Email > impostazioni premio per tipo; placeholder `{{PRIZE_IMAGE_BLOCK}}` |
| Sfondo blocco quiz | 1200×800 (suggerito) | Step 10 Quiz > sfondo blocco domande (modalita Immagine) |
| Form trasparente | — | Non e un file: impostare il container form su colore trasparente (opacita 0) nel manager |

---

Tabella riepilogo dimensioni

| Uso | Larghezza × altezza |
|-----|---------------------|
| Logo desktop | 800 × 400 |
| Logo mobile | 480 × 240 |
| Header desktop | 1920 × 280 |
| Header mobile | 1080 × 480 |
| Favicon | 32 × 32 |
| Favicon mobile | 180 × 180 |
| Banner email | 600 × 200 |
| Background default / desktop | 1920 × 1080 |
| Background mobile | 1080 × 1920 |
| Background tablet | 1280 × 800 |
| Background laptop | 1366 × 768 |
| Background ultrawide | 3440 × 1440 |
| Container form / pagine / quiz | 1200 × 800 |
| Footer background | es. 1920 × 300 |
| Hero container waiting/thank-you | 1200 × 600 |
| Immagine premio email | 600 × 400 |

---

Linee guida pratiche

Formati e limiti
- Formati accettati per le immagini: PNG, JPG, JPEG, GIF, WebP, SVG (dove previsto dall’upload).
- Limite upload asset nel manager: **2 MB** per file (configurazione attuale).
- Logo: preferire **PNG trasparente**, ratio **2:1** (coerente con l’anteprima manager).

Composizione
- Tenere il centro delle immagini abbastanza pulito per testi e pulsanti del box centrale.
- Evitare testi piccoli incorporati negli sfondi: nei layout reali diventano poco leggibili.
- Safe area: soggetti importanti non sui bordi; l’immagine puo essere ritagliata su alcuni dispositivi (`cover`).
- Niente testo/logo «stampato» negli sfondi principali: titoli e pulsanti sono elementi separati del layout.

Cosa non e un file
- **Form trasparente**: opzione colore nel container form, non un asset da caricare.
- **Hero waiting/thank-you**: contenuto HTML nel container editor, non file `waiting_container_hero.jpg` nella root del concorso.

Applicabilita
- Sfondi pagina: `background_*`
- Sfondi box: `form_container`, container pagina pubblica, footer background
- Hero decorative: immagini dentro i container waiting/thank-you (editor)
