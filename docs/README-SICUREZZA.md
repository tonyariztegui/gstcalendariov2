# Sicurezza — Guide administrateur

Documentation du module **Sicurezza** du manager GST : blocages, limites anti-abus, protection des codes de participation et journal d’audit.

**Fichier technique principal :** `app/logic/contest_security.php`  
**Interface manager :** `manager/views/security.php` (route `security`)

---

## Accès

| Rôle | Menu « Sicurezza » | Accès à la page |
|------|-------------------|-----------------|
| `pm_admin` | Oui | Complet |
| `pm_op` et autres | Non | Refusé (`access_denied`) |

L’ancienne route `email_blacklist` redirige vers `security?tab=emails`.

---

## Vue d’ensemble des onglets

| Onglet | Rôle |
|--------|------|
| **Emails** | Blacklist d’adresses e-mail (blocage manuel) |
| **Domini** | Blacklist de domaines e-mail (ex. `spam.com`) |
| **IP** | Blacklist d’adresses IP |
| **Regole** | Paramètres globaux + activation protection codici par concorso |
| **Journal** | Historique des événements de sécurité (lecture seule) |

### Emails / Domini / IP

Pour chaque type de liste :

- **Ajouter** une entrée avec une note optionnelle (motif du blocage)
- **Rechercher** et paginer la liste
- **Débloquer** (icône cadenas) avec confirmation

Les blocages manuels s’appliquent immédiatement à la prochaine tentative d’inscription.

### Regole — cartes de statut

En haut de l’onglet, des cartes indiquent quelles règles sont **Attiva** ou **Inattiva** :

- Blacklist email (toujours active si la table existe)
- Domaines / IP bloqués (actif si au moins une entrée)
- Limites inscriptions (IP, email, cooldown)
- **Protezione codici partecipazione** (voir ci-dessous)
- Règles liées au concorso (fidelity, Instant Win) — informatives

---

## Protezione codice partecipazione

Protection anti brute-force sur le **codici di partecipazione** (codes invalides répétés).

### Activation (par concorso)

1. Onglet **Regole** → cocher **« Attiva protezione codici per questo concorso »**
2. Configurer les seuils
3. **Salva impostazioni**

Le toggle est enregistré dans **`content.json`** :

```json
"codice_partecipazione_security_enabled": true
```

Les seuils numériques sont dans la table **`security_settings`** (globaux à l’application).

### Conditions pour que la protection s’applique

Toutes doivent être remplies :

| Condition | Où |
|-----------|-----|
| Toggle activé | `content.json` |
| Champ codice abilitato | Concorso → Form campi |
| Mode `instantwin` ou `estrazione` | Configuration concorso |
| `codice_fail_max_per_window` > 0 | Sicurezza → Regole |

### Paramètres (défauts)

| Paramètre | Défaut | Description |
|-----------|--------|-------------|
| Max tentativi errati / finestra | 5 | Nombre d’échecs avant blocage |
| Finestra (secondi) | 900 | Fenêtre glissante (15 min) |
| 1° blocco (secondi) | 300 | 5 minutes |
| 2° blocco (secondi) | 1800 | 30 minutes |
| 3° blocco (secondi) | 86400 | 24 heures |
| Blacklist auto dopo N blocchi | 4 | Au 4ᵉ blocage → blacklist IP + email |

**Valeur 0** sur « Max tentativi » = protection codici désactivée (même si le toggle est coché).

### Comportement

```text
Échec code (compté) → compteur IP + email
  → si seuil non atteint : message + « ti restano N tentativi »
  → si seuil atteint : blocage temporaire (durée selon niveau 1/2/3)
  → après N blocages : ajout auto en blacklist IP + email
Inscription réussie → reset des compteurs pour cet IP et cet email
```

**Comptent comme échec :** format incorrect, code invalide, code déjà utilisé.  
**Ne comptent pas :** champ vide, erreur technique DB.

**Activation en cours de concorso :** effet immédiat ; les compteurs repartent de zéro (pas d’historique rétroactif).

### Messages affichés au participant (si protection active)

| Situation | Message (résumé) |
|-----------|------------------|
| Tentatives restantes | Erreur codice + « Attenzione: ti restano N tentativi… » |
| Blocage temporaire | « Accesso temporaneamente bloccato. Riprova tra X minuti » + assistenza |
| Blacklist | « Non è possibile completare la registrazione » + assistenza |
| Autres blocages sécurité | Message adapté avec lien assistenza (`/assistenza` ou `helpdesk_email`) |

Sans protection codici, les blocages sécurité affichent un message générique sur le champ codice (anti-énumération).

---

## Limiti anti-abuso (iscrizioni)

Limitent les **inscriptions réussies** (pas les codes invalides).

| Paramètre | Défaut | Description |
|-----------|--------|-------------|
| Max iscrizioni per IP / 24h | 0 (off) | Compte les lignes dans `partecipazioni` |
| Max iscrizioni per email / 24h | 0 (off) | Via jointure `utenti` |
| Cooldown tra tentativi (secondi) | 0 (off) | Délai minimum entre deux inscriptions depuis la même IP |
| Conservazione journal (giorni) | 90 | Rétention du journal + purge auto |

**Prérequis IP :** la colonne `partecipazioni.ip_address` doit exister. Sinon, les limites IP sont ignorées (avertissement affiché dans l’UI).

---

## Tables base de données

Les tables sont créées automatiquement au premier accès à Sicurezza ou à la première inscription contrôlée (`CREATE TABLE IF NOT EXISTS` dans `contest_security_ensure_tables()`).

### `email_blacklist`

Blocage manuel ou automatique par adresse e-mail.

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | INT UNSIGNED | Clé primaire |
| `email` | VARCHAR(254) | Adresse (unique, normalisée minuscules) |
| `note` | VARCHAR(500) | Motif optionnel |
| `created_at` | DATETIME | Date d’ajout |
| `created_by` | VARCHAR(100) | Utilisateur manager (ex. `pm_admin`) |

### `email_domain_blocklist`

Blocage de tous les e-mails d’un domaine (`*@domaine.it`).

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | INT UNSIGNED | Clé primaire |
| `domain` | VARCHAR(253) | Domaine (unique) |
| `note` | VARCHAR(500) | Motif optionnel |
| `created_at` | DATETIME | Date d’ajout |
| `created_by` | VARCHAR(100) | Utilisateur manager |

### `ip_blocklist`

Blocage par adresse IP.

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | INT UNSIGNED | Clé primaire |
| `ip_address` | VARCHAR(45) | IPv4 ou IPv6 (unique) |
| `note` | VARCHAR(500) | Motif optionnel |
| `created_at` | DATETIME | Date d’ajout |
| `created_by` | VARCHAR(100) | Utilisateur manager |

### `security_settings`

Paramètres clé/valeur (limites et seuils codici).

| Colonne | Type | Description |
|---------|------|-------------|
| `setting_key` | VARCHAR(64) | Clé primaire |
| `setting_value` | VARCHAR(255) | Valeur |
| `updated_at` | DATETIME | Dernière modification |

**Clés utilisées :**

| Clé | Défaut |
|-----|--------|
| `registration_ip_max_per_day` | 0 |
| `registration_email_max_per_day` | 0 |
| `registration_cooldown_seconds` | 0 |
| `log_retention_days` | 90 |
| `codice_fail_max_per_window` | 5 |
| `codice_fail_window_seconds` | 900 |
| `codice_lockout_level1_seconds` | 300 |
| `codice_lockout_level2_seconds` | 1800 |
| `codice_lockout_level3_seconds` | 86400 |
| `codice_blacklist_at_strike` | 4 |

### `security_event_log`

Journal d’audit (purge automatique selon `log_retention_days`).

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | BIGINT UNSIGNED | Clé primaire |
| `event_type` | VARCHAR(64) | Type d’événement (voir ci-dessous) |
| `email_masked` | VARCHAR(254) | E-mail masquée (ex. `u***r@domain.com`) |
| `ip_address` | VARCHAR(45) | IP de la tentative |
| `detail` | VARCHAR(500) | Détail technique (sans le code deviné) |
| `created_at` | DATETIME | Horodatage |

**Types d’événements (`event_type`) :**

| Type | Libellé journal | Signification |
|------|-----------------|---------------|
| `email_blacklist` | Email in blacklist | Inscription bloquée — e-mail en liste |
| `domain_blocked` | Dominio bloccato | Domaine e-mail bloqué |
| `ip_blocked` | IP bloccato | IP en liste |
| `rate_limit_ip` | Limite IP / giorno | Trop d’inscriptions depuis cette IP / 24h |
| `rate_limit_email` | Limite email / giorno | Trop d’inscriptions pour cet e-mail / 24h |
| `rate_limit_cooldown` | Cooldown IP | Nouvelle tentative trop rapide (même IP) |
| `invalid_codice` | Codice non valido | Tentative de code participation invalide |
| `codice_lockout` | Blocco temporaneo codici | Blocage temporaire IP/e-mail déclenché |
| `codice_auto_blacklist` | Blacklist auto (codici) | Escalade → blacklist automatique |

### `codice_lockout`

État des compteurs et blocages temporaires pour la protection codici (une ligne par IP **et** une par e-mail).

| Colonne | Type | Description |
|---------|------|-------------|
| `entity_type` | ENUM(`ip`,`email`) | Type d’entité (clé composite) |
| `entity_value` | VARCHAR(254) | IP ou e-mail normalisé (clé composite) |
| `failure_count` | INT UNSIGNED | Échecs dans la fenêtre courante |
| `window_started_at` | DATETIME | Début de la fenêtre glissante |
| `strike_level` | TINYINT UNSIGNED | Nombre de blocages déjà appliqués |
| `locked_until` | DATETIME | Fin du blocage temporaire (NULL si libre) |
| `updated_at` | DATETIME | Dernière mise à jour |

### Table existante utilisée (non créée par Sicurezza)

**`partecipazioni`** — colonne `ip_address` : nécessaire pour les limites IP et le cooldown. Les inscriptions réussies y sont comptées pour les rate limits.

---

## Ordre de contrôle à l’inscription

Lors d’un POST sur le formulaire d’inscription (`do_registration.php`) :

1. Validation des champs (dont codice partecipazione)
2. Si protection codici active : vérification blocage temporaire IP/e-mail
3. Si code invalide : enregistrement échec + message tentatives restantes
4. Contrôles sécurité globaux : blacklist, domaines, IP, rate limits inscriptions
5. Si tout OK : enregistrement + reset compteurs codici

---

## Configuration hors base de données

| Fichier | Clé | Rôle |
|---------|-----|------|
| `app/config/content.json` | `codice_partecipazione_security_enabled` | Toggle protection codici par concorso |
| `app/config/content.json` | `helpdesk_email` | Contact affiché dans les messages assistenza |
| `app/config/content.json` | `form_fields.codice_partecipazione` | Champ codice activé ou non |

---

## Bonnes pratiques

- Commencer avec **5 tentatives / 15 min** pour la protection codici.
- Vérifier le **Journal** après un pic de trafic ou des signalements participants.
- Pour débloquer un participant blacklisté par erreur : onglets **Emails** et **IP** → retirer l’entrée auto (`note` : « Auto: troppi tentativi codice »).
- Désactiver le toggle codici en cours de concorso arrête les nouveaux comptages ; les blocages temporaires déjà en cours expirent selon `locked_until`.

---

## Réinitialisation automatique (nouveau concorso / reset)

La Sicurezza est **remise à zéro** automatiquement dans ces cas :

| Événement | Où | Actions |
|-----------|-----|---------|
| **Reset configurazione concorso** | Concorso → reset (`reset_config=1`) | `TRUNCATE` blacklist email/domini/IP, journal, `codice_lockout` ; `security_settings` → défauts ; `content.json` supprimé → toggle codici **off** |
| **Première sauvegarde** `content.json` | Premier enregistrement Concorso (fichier absent) | Même reset DB + `codice_partecipazione_security_enabled = false` dans le nouveau fichier |

Fonction : `contest_security_reset_to_defaults()` dans `app/logic/contest_security.php`.

**Valeurs par défaut restaurées :** limites inscriptions à 0, journal 90 jours, seuils codici (5 / 900 s / blocages 300-1800-86400 / blacklist au 4ᵉ strike), toggle protection codici **désactivé**.

---

## Dépannage

| Problème | Cause probable | Action |
|----------|----------------|--------|
| Limites IP inactives | Colonne `ip_address` absente | Migrer / ajouter la colonne sur `partecipazioni` |
| Protection codici « Inattiva » | Toggle off, champ codice off, ou max = 0 | Vérifier Regole + Concorso |
| Participant bloqué à tort | Faux positif ou IP partagée | Retirer de blacklist / attendre fin `locked_until` |
| Pas d’événements dans le journal | Rétention courte ou aucun blocage | Augmenter `log_retention_days` |

---

*Dernière mise à jour : module Sicurezza GST Manager — protection codici partecipazione et tables `codice_lockout` / `security_*`.*
