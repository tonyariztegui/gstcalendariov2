<?php

declare(strict_types=1);

if (!defined('CONCORSO_PATH_PUBLIC')) {
    define('CONCORSO_PATH_PUBLIC', '/public/');
}
if (!defined('CONCORSO_COLOR_WHITE')) {
    define('CONCORSO_COLOR_WHITE', '#ffffff');
}
if (!defined('CONCORSO_COLOR_SUCCESS')) {
    define('CONCORSO_COLOR_SUCCESS', '#198754');
}
if (!defined('CONCORSO_COLOR_PRIMARY')) {
    define('CONCORSO_COLOR_PRIMARY', '#0d6efd');
}
if (!defined('CONCORSO_COLOR_SECONDARY')) {
    define('CONCORSO_COLOR_SECONDARY', '#6c757d');
}
if (!defined('CONCORSO_COLOR_TEXT')) {
    define('CONCORSO_COLOR_TEXT', '#212529');
}
if (!defined('CONCORSO_COLOR_BLACK')) {
    define('CONCORSO_COLOR_BLACK', '#000000');
}
if (!defined('CONCORSO_DATE_FORMAT')) {
    define('CONCORSO_DATE_FORMAT', 'd/m/Y');
}
if (!defined('CONCORSO_PRIZE_IMAGE_GENERATOR')) {
    define('CONCORSO_PRIZE_IMAGE_GENERATOR', '/../../app/logic/prize_image_generator.php');
}
if (!defined('CONCORSO_APP_FUNCTIONS')) {
    define('CONCORSO_APP_FUNCTIONS', '/../../app/inc/functions.php');
}
if (!defined('CONCORSO_CSV_UPLOAD_DIR')) {
    define('CONCORSO_CSV_UPLOAD_DIR', '/../uploads/csv/');
}
if (!defined('CONCORSO_UPLOADS_CONFIG')) {
    define('CONCORSO_UPLOADS_CONFIG', 'uploads/config/');
}
if (!defined('CONCORSO_ASSETS_PDF')) {
    define('CONCORSO_ASSETS_PDF', 'assets/pdf/');
}
if (!defined('CONCORSO_PH_CONTEST_NAME')) {
    define('CONCORSO_PH_CONTEST_NAME', '{{CONTEST_NAME}}');
}
if (!defined('CONCORSO_PH_START_DATE')) {
    define('CONCORSO_PH_START_DATE', '{{START_DATE}}');
}
if (!defined('CONCORSO_PH_END_DATE')) {
    define('CONCORSO_PH_END_DATE', '{{END_DATE}}');
}
if (!defined('CONCORSO_PH_MIN_SPEND')) {
    define('CONCORSO_PH_MIN_SPEND', '{{MIN_SPEND}}');
}
if (!defined('CONCORSO_PH_REGOLAMENTO')) {
    define('CONCORSO_PH_REGOLAMENTO', '{{REGOLAMENTO_LINK}}');
}
if (!defined('CONCORSO_THANKYOU_TITLE')) {
    define('CONCORSO_THANKYOU_TITLE', 'GRAZIE PER AVER PARTECIPATO!');
}
if (!defined('CONCORSO_QUIZ_LABEL_STANDARD')) {
    define('CONCORSO_QUIZ_LABEL_STANDARD', 'Testo (domande scritte)');
}
if (!defined('CONCORSO_QUIZ_LABEL_AUDIO')) {
    define('CONCORSO_QUIZ_LABEL_AUDIO', 'Audio (ascolto, poi risposte A/B/C/D)');
}
if (!defined('CONCORSO_QUIZ_LABEL_SUBMIT')) {
    define('CONCORSO_QUIZ_LABEL_SUBMIT', 'Avvia quiz');
}
if (!defined('CONCORSO_LOCATION_HEADER')) {
    define('CONCORSO_LOCATION_HEADER', 'Location: ');
}
if (!defined('CONCORSO_SQL_FK_CHECKS_OFF')) {
    define('CONCORSO_SQL_FK_CHECKS_OFF', 'SET FOREIGN_KEY_CHECKS = 0');
}
if (!defined('CONCORSO_SQL_FK_CHECKS_ON')) {
    define('CONCORSO_SQL_FK_CHECKS_ON', 'SET FOREIGN_KEY_CHECKS = 1');
}
if (!defined('CONCORSO_REGEX_WHITESPACE')) {
    define('CONCORSO_REGEX_WHITESPACE', '/\s+/');
}
if (!defined('CONCORSO_REGEX_ALNUM_UNDERSCORE')) {
    define('CONCORSO_REGEX_ALNUM_UNDERSCORE', '/[^a-z0-9_]/i');
}
if (!defined('CONCORSO_REGEX_HEX6')) {
    define('CONCORSO_REGEX_HEX6', '/^#[0-9A-Fa-f]{6}$/');
}
if (!defined('CONCORSO_REGEX_HEX3_8')) {
    define('CONCORSO_REGEX_HEX3_8', '/^#[0-9A-Fa-f]{3,8}$/');
}
if (!defined('CONCORSO_REGEX_WIN_DRIVE')) {
    define('CONCORSO_REGEX_WIN_DRIVE', '/^[a-zA-Z]:/');
}
if (!defined('CONCORSO_CSS_DISPLAY_NONE')) {
    define('CONCORSO_CSS_DISPLAY_NONE', 'display: none;');
}
if (!defined('CONCORSO_SQL_VARCHAR255')) {
    define('CONCORSO_SQL_VARCHAR255', 'VARCHAR(255)');
}
if (!defined('CONCORSO_SQL_COUNT_CODICI')) {
    define('CONCORSO_SQL_COUNT_CODICI', 'SELECT COUNT(*) FROM codici_partecipazione');
}
