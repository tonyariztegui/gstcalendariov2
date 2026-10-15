# Quiz – Guide de restauration du projet

Ce document décrit la structure du module quiz et le code à réappliquer pour restaurer les améliorations (alignement des options A/B/C/D, espacement, centrage du testo domanda) après une réinitialisation du projet.

---

## 1. Structure des fichiers quiz

### Fichiers à conserver (ne pas supprimer)

| Fichier | Rôle |
|---------|------|
| `app/inc/config.php` | Helpers quiz dont `get_quiz_question_options_html` (HTML des options + normalisation). |
| `app/logic/quiz_flow.php` | Logique et rendu de la page quiz publique (formulaire, CSS options, testo domanda). |
| `app/logic/thank-you.php` | Logique de la page remerciement (après quiz ou envoi). |
| `manager/ajax/preview_quiz.php` | Prévisualisation du quiz dans le manager (même rendu que le quiz public). |
| `manager/ajax/set_quiz_preview_draft.php` | Sauvegarde du brouillon de prévisualisation quiz. |
| `manager/views/quiz_domande.php` | Interface de gestion des domande (liste / édition). |
| `public/quiz_flow.php` | Point d’entrée public du quiz (inclut `app/logic/quiz_flow.php`). |
| `public/thank-you.php` | Point d’entrée public de la page remerciement. |
| `public/assets/sql/quiz_tables.sql` | Schéma des tables `quiz_domande` et `quiz_tentativi`. |
| `public/assets/quiz/README.txt` | Notes sur les assets quiz (optionnel). |

### Schéma des entrées

```
Public:
  public/quiz_flow.php     → require app/logic/quiz_flow.php
  public/thank-you.php    → require app/logic/thank-you.php

Manager:
  manager/views/quiz_domande.php   → liste/édition domande
  manager/ajax/preview_quiz.php     → iframe prévisualisation
  manager/ajax/set_quiz_preview_draft.php → sauvegarde brouillon
```

---

## 2. Contenu complet des fichiers à conserver

Ci-dessous le contenu de chaque fichier à recréer si besoin. Pour `app/inc/config.php`, seul le code de la fonction quiz est à réappliquer (voir §4.1).

### `public/quiz_flow.php`

```php
<?php
// Punto di ingresso pubblico per il quiz: delega alla logica interna
require_once __DIR__ . '/../app/logic/quiz_flow.php';
```

### `public/thank-you.php`

```php
<?php
// Punto di ingresso pubblico per la pagina thank-you (dopo quiz o registrazione)
require_once __DIR__ . '/../app/logic/thank-you.php';
```

### `public/assets/sql/quiz_tables.sql`

```sql
-- Quiz: domande (testo/audio/video) e tentativi
-- Eseguire questo script per abilitare il modulo quiz nel concorso.

-- Domande quiz: tipo = text | audio | video; risposta_corretta = A|B|C|D
CREATE TABLE IF NOT EXISTS `quiz_domande` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `testo` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Testo della domanda (sempre presente)',
  `tipo` enum('text','audio','video') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text' COMMENT 'Tipo richiesta: text, audio, video',
  `audio_file` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Percorso file audio (se tipo=audio)',
  `video_file` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Percorso/URL video (se tipo=video)',
  `risposta_a` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `risposta_b` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `risposta_c` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `risposta_d` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `risposta_corretta` enum('A','B','C','D') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'A',
  `ordine` int(11) NOT NULL DEFAULT 0 COMMENT 'Ordine di visualizzazione',
  `attiva` tinyint(1) NOT NULL DEFAULT 1,
  `creata_il` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `aggiornata_il` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_attiva_ordine` (`attiva`,`ordine`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Un tentativo per id_cliente (un quiz completato per partecipazione)
CREATE TABLE IF NOT EXISTS `quiz_tentativi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL COMMENT 'Utente (da partecipazioni -> utenti)',
  `token_partecipazione` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Token della partecipazione collegata',
  `quiz_mode` enum('standard','audio') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'standard',
  `corrette` int(11) NOT NULL DEFAULT 0,
  `totali` int(11) NOT NULL DEFAULT 0,
  `esito` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=superato (es. tutte corrette o soglia), 0=non superato',
  `risposte_json` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'JSON id_domanda => A|B|C|D',
  `creata_il` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cliente_token` (`id_cliente`,`token_partecipazione`),
  KEY `idx_token` (`token_partecipazione`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### `public/assets/quiz/README.txt`

```text
Cartella per i file audio e video del quiz.

OPZIONE 1 – Upload dal Manager
  Manager → Quiz – Domande: nel form di ogni domanda usa il pulsante
  "Carica audio" o "Carica video". Il file viene salvato qui e il percorso
  (assets/quiz/nome-file.ext) viene inserito automaticamente.

OPZIONE 2 – Caricamento manuale
  Carica i file in questa cartella (es. via FTP), poi nel Manager
  (Quiz – Domande) inserisci nel campo "File audio" o "File video":
    assets/quiz/nome-file.mp3
    assets/quiz/nome-file.mp4
  (percorso relativo alla cartella "public" del sito).

Formati consigliati: audio MP3/WAV/OGG/M4A, video MP4/WebM.
```

### `app/logic/quiz_flow.php`

(Voir le fichier dans le projet : `app/logic/quiz_flow.php`, ~370 lignes. Contient déjà le CSS des options et le `<span class="quiz-testo-domanda">` pour le centrage. En restauration, copier tout le fichier depuis le projet ou réappliquer uniquement les deux lignes indiquées en §4.2 et §4.3.)

### `app/logic/thank-you.php`

(Voir le fichier dans le projet : `app/logic/thank-you.php`, ~185 lignes.)

### `manager/ajax/preview_quiz.php`

(Voir le fichier dans le projet : `manager/ajax/preview_quiz.php`, ~123 lignes. Même structure que quiz_flow pour le quiz + CSS options + testo domanda en span.)

### `manager/ajax/set_quiz_preview_draft.php`

(Voir le fichier dans le projet : `manager/ajax/set_quiz_preview_draft.php`, ~138 lignes.)

### `manager/views/quiz_domande.php`

(Voir le fichier dans le projet : `manager/views/quiz_domande.php`, ~866 lignes. Page manager liste/édition domande + containers quiz + preview iframe.)

**Important :** Si vous effacez le projet, sauvegardez d’abord une copie de ces 5 fichiers (par ex. dans un dossier `backup_quiz/` ou un commit Git). Ce README ne contient pas leur contenu intégral pour rester lisible ; il contient en revanche tout le code à **réappliquer** dans `app/inc/config.php` et les lignes à modifier dans quiz_flow et preview_quiz (§4). Vous pouvez aussi sauvegarder ce README avec une copie des 5 fichiers dans un dossier à part : ainsi vous aurez tout au même endroit.

---

## Liste backup quiz (fichiers à copier avant d’effacer le projet)

Copier ces fichiers dans un dossier `backup_quiz/` en gardant l’arborescence, ou faire un commit Git dédié.

**Arborescence :**

```
backup_quiz/
├── app/inc/config.php
├── app/logic/quiz_flow.php
├── app/logic/thank-you.php
├── manager/ajax/preview_quiz.php
├── manager/ajax/set_quiz_preview_draft.php
├── manager/views/quiz_domande.php
├── public/quiz_flow.php
├── public/thank-you.php
├── public/assets/sql/quiz_tables.sql
├── public/assets/quiz/README.txt
└── docs/README-QUIZ-RESTORE.md
```

**Liste plate :**

| # | Fichier |
|---|---------|
| 1 | `app/inc/config.php` |
| 2 | `app/logic/quiz_flow.php` |
| 3 | `app/logic/thank-you.php` |
| 4 | `manager/ajax/preview_quiz.php` |
| 5 | `manager/ajax/set_quiz_preview_draft.php` |
| 6 | `manager/views/quiz_domande.php` |
| 7 | `public/quiz_flow.php` |
| 8 | `public/thank-you.php` |
| 9 | `public/assets/sql/quiz_tables.sql` |
| 10 | `public/assets/quiz/README.txt` |
| 11 | `docs/README-QUIZ-RESTORE.md` |

---

## 3. Améliorations incluses

- **Alignement des options** : cases, lettres A/B/C/D et tiret alignés verticalement (grille CSS 5 colonnes).
- **Format fixe** : `Lettre (espace) tiret (espace) réponse` (ex. `A – No`).
- **Normalisation des réponses** : trim + suppression des espaces/tirets en début de texte en base.
- **Centrage du testo domanda** : le texte de la question hérite de l’alignement du bloc template (pas de `<p>` imbriqué).

---

## 4. Code à réappliquer

### 4.1 `app/inc/config.php` – Fonction `get_quiz_question_options_html`

**Emplacement** : remplacer toute la fonction du même nom (bloc qui génère le HTML des options A/B/C/D).

```php
/**
 * HTML delle opzioni di risposta (A, B, C, D) per una domanda. Usato nel placeholder {{OPZIONI}}.
 * $q: riga domanda (id, risposta_a, risposta_b, risposta_c, risposta_d); $answer_style: 'radio'|'square'; $disabled: true in preview.
 */
if (!function_exists('get_quiz_question_options_html')) {
function get_quiz_question_options_html(array $q, string $answer_style, bool $disabled = false): string {
    $id = (int)($q['id'] ?? 0);
    $name = 'q[' . $id . ']';
    $dis = $disabled ? ' disabled' : '';
    $answer_class = ($answer_style === 'square') ? ' quiz-answer-square' : '';
    $normalize = function ($s) {
        $s = trim((string) $s);
        $s = preg_replace('/^[\s\-–—]+/u', '', $s);
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    };
    $a = $normalize($q['risposta_a'] ?? '');
    $b = $normalize($q['risposta_b'] ?? '');
    $c = $normalize($q['risposta_c'] ?? '');
    $d = $normalize($q['risposta_d'] ?? '');
    $html = '<span class="form-group mt-0 quiz-opzioni-list' . $answer_class . '" style="display:inline-block;text-align:inherit">';
    $html .= '<span class="form-check quiz-opzioni-row"><input type="radio" name="' . $name . '" value="A" class="form-check-input" id="q' . $id . '_A"' . $dis . '><label class="form-check-label" for="q' . $id . '_A"><span class="quiz-option-letter">A</span><span class="quiz-option-sep"> – </span><span class="quiz-option-text">' . $a . '</span></label></span>';
    $html .= '<span class="form-check quiz-opzioni-row"><input type="radio" name="' . $name . '" value="B" class="form-check-input" id="q' . $id . '_B"' . $dis . '><label class="form-check-label" for="q' . $id . '_B"><span class="quiz-option-letter">B</span><span class="quiz-option-sep"> – </span><span class="quiz-option-text">' . $b . '</span></label></span>';
    $html .= '<span class="form-check quiz-opzioni-row"><input type="radio" name="' . $name . '" value="C" class="form-check-input" id="q' . $id . '_C"' . $dis . '><label class="form-check-label" for="q' . $id . '_C"><span class="quiz-option-letter">C</span><span class="quiz-option-sep"> – </span><span class="quiz-option-text">' . $c . '</span></label></span>';
    $html .= '<span class="form-check quiz-opzioni-row"><input type="radio" name="' . $name . '" value="D" class="form-check-input" id="q' . $id . '_D"' . $dis . '><label class="form-check-label" for="q' . $id . '_D"><span class="quiz-option-letter">D</span><span class="quiz-option-sep"> – </span><span class="quiz-option-text">' . $d . '</span></label></span>';
    $html .= '</span>';
    return $html;
}
}
```

---

### 4.2 `app/logic/quiz_flow.php` – Deux modifications

**A) Ligne du bloc testo domanda** (rechercher la ligne qui contient `$testo_domanda_html .= '<p class="mb-0">'` et la remplacer par) :

```php
if (!empty($q['testo'])) { $testo_domanda_html .= '<span class="quiz-testo-domanda d-block mb-0" style="text-align:inherit">' . nl2br(htmlspecialchars($q['testo'], ENT_QUOTES, 'UTF-8')) . '</span>'; }
```

**B) Ligne du style CSS des options** (rechercher le `<style>` qui contient `.quiz-opzioni-list` et le remplacer par) :

```php
<style>.quiz-opzioni-list .quiz-opzioni-row{display:grid;grid-template-columns:1.15em 1.2em 0.35em auto 1fr;gap:0;align-items:center;}.quiz-opzioni-list .form-check-input{grid-column:1;width:1.15em;min-width:1.15em;margin:0;}.quiz-opzioni-list .form-check-label{grid-column:2/-1;display:contents;}.quiz-opzioni-list .form-check-label .quiz-option-letter{grid-column:2;}.quiz-opzioni-list .form-check-label .quiz-option-sep{grid-column:4;padding-right:0.25em;}.quiz-opzioni-list .form-check-label .quiz-option-text{grid-column:5;margin:0;padding:0;}</style>
```

---

### 4.3 `manager/ajax/preview_quiz.php` – Deux modifications

**A) Ligne du bloc testo domanda** (même remplacement que dans quiz_flow.php) :

```php
if (!empty($q['testo'])) { $testo_domanda_html .= '<span class="quiz-testo-domanda d-block mb-0" style="text-align:inherit">' . nl2br(htmlspecialchars($q['testo'], ENT_QUOTES, 'UTF-8')) . '</span>'; }
```

**B) Ligne qui affiche le style CSS des options** (remplacer le `echo '<style>.quiz-opzioni-list...` par) :

```php
echo '<style>.quiz-opzioni-list .quiz-opzioni-row{display:grid;grid-template-columns:1.15em 1.2em 0.35em auto 1fr;gap:0;align-items:center;}.quiz-opzioni-list .form-check-input{grid-column:1;width:1.15em;min-width:1.15em;margin:0;}.quiz-opzioni-list .form-check-label{grid-column:2/-1;display:contents;}.quiz-opzioni-list .form-check-label .quiz-option-letter{grid-column:2;}.quiz-opzioni-list .form-check-label .quiz-option-sep{grid-column:4;padding-right:0.25em;}.quiz-opzioni-list .form-check-label .quiz-option-text{grid-column:5;margin:0;padding:0;}</style>';
```

---

## 5. Base de données

Exécuter le script SQL pour créer les tables quiz si besoin :

- **Fichier** : `public/assets/sql/quiz_tables.sql`
- **Tables** : `quiz_domande`, `quiz_tentativi`

```sql
-- Voir le contenu complet dans public/assets/sql/quiz_tables.sql
```

---

## 5. Points d’entrée publics (à conserver tels quels)

**`public/quiz_flow.php`** :

```php
<?php
// Punto di ingresso pubblico per il quiz: delega alla logica interna
require_once __DIR__ . '/../app/logic/quiz_flow.php';
```

**`public/thank-you.php`** :

```php
<?php
// Punto di ingresso pubblico per la pagina thank-you (dopo quiz o registrazione)
require_once __DIR__ . '/../app/logic/thank-you.php';
```

---

## 7. Ordre de restauration recommandé

1. Vérifier que les fichiers listés en §1 existent (créer les points d’entrée et le SQL si besoin).
2. Réappliquer le code de **§4.1** dans `app/inc/config.php`.
3. Réappliquer les deux modifications **§4.2** dans `app/logic/quiz_flow.php`.
4. Réappliquer les deux modifications **§4.3** dans `manager/ajax/preview_quiz.php`.
5. Exécuter `public/assets/sql/quiz_tables.sql` si les tables quiz n’existent pas.

Après cela, le quiz doit retrouver l’alignement des options, l’espacement cohérent et le centrage du testo domanda selon le template.
