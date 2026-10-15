# Manager Concorso BUON NATALE COOP

## 📋 Description

Système de gestion administratif pour le concours BUON NATALE COOP, permettant de surveiller les participants, les prix distribués et les statistiques du concours.

## 🚀 Fonctionnalités

### Dashboard Principal
- **Statistiques en temps réel** : Participants, participations, vincitori, premi rimanenti
- **Top 10 PDV** : Classement des points de vente par nombre de participations
- **Filtres avancés** : Par statut (vincitori/non vincitori), recherche textuelle
- **Export CSV** : Partecipanti, Vincitori, PDV

### Système d'Emails
- **Configuration Mailtrap** : Envoi d'emails via Mailtrap pour le développement
- **Notifications automatiques** : Alertes pour les administrateurs
- **Rapports quotidiens** : Statistiques quotidiennes automatiques
- **Templates responsifs** : Emails optimisés pour tous les clients

### Gestion des Données
- **Export complet** : Tous les participants avec leurs participations
- **Export vincitori** : Liste détaillée des gagnants avec codes vouchers
- **Export PDV** : Statistiques par point de vente
- **Données temps réel** : Mise à jour automatique des statistiques

## 🔧 Configuration

### Base de Données
Le manager utilise la configuration centralisée du concours :
- **Fichier** : `manager/config/config.php`
- **Connexion** : Utilise `conn_pdo()` du système centralisé
- **Tables** : `utenti`, `partecipazioni`, `vouchers`, `premi`, `pdv`
- **Tables d'authentification** : `manager_users`, `manager_sessions`, `users`

### Emails Mailtrap
```php
define("MAILTRAP_HOST", "sandbox.smtp.mailtrap.io");
define("MAILTRAP_PORT", 2525);
define("MAILTRAP_USERNAME", "2bfb26e4c97d14");
define("MAILTRAP_PASSWORD", "9ad0e7c0cc8e7b");
```

## 📁 Structure des Fichiers

```
manager/
├── config/
│   └── config.php              # Configuration centralisée
├── libraries/
│   └── email_manager.php       # Système d'emails Mailtrap
├── res/inc/
│   ├── export_partecipanti.php # Export participants
│   ├── export_vincitori.php    # Export gagnants
│   └── export_pdv.php          # Export PDV
├── views/
│   └── homepage.php            # Dashboard principal
├── test_emails.php             # Test des emails
└── index.php                   # Point d'entrée
```

## 🎯 Utilisation

### Accès au Manager
1. Naviguer vers `/manager/`
2. Se connecter avec les identifiants administrateur
3. Accéder au dashboard principal

### Test des Emails
1. Aller sur `/manager/test_emails.php`
2. Configurer l'email destinataire
3. Tester les notifications et rapports quotidiens

### Export des Données
1. Sur le dashboard, cliquer sur les boutons d'export
2. Les fichiers CSV sont téléchargés automatiquement
3. Format compatible Excel avec encodage UTF-8

## 📊 Statistiques Disponibles

### Dashboard Principal
- **Totale Partecipanti** : Nombre total d'utilisateurs inscrits
- **Totale Partecipazioni** : Nombre total de participations
- **Vincitori** : Nombre total de gagnants
- **Premi Rimanenti** : Nombre de prix encore disponibles

### Top 10 PDV
- **Classement** : Par nombre de participations
- **Taux de victoire** : Pourcentage de gagnants par PDV
- **Détails** : Ville, province, adresse complète

## 🔐 Sécurité

- **Authentification** : Système de connexion sécurisé
- **Sessions** : Gestion des sessions utilisateur
- **Validation** : Vérification des données d'entrée
- **Logs** : Enregistrement des actions importantes

## 📧 Templates d'Emails

### Notification Manager
- **Design** : Template responsive avec header COOP
- **Contenu** : Titre, message, données détaillées
- **Footer** : Informations système et timestamp

### Rapport Quotidien
- **Statistiques** : Participations, vincitori, premi
- **Format** : Tableau structuré des données
- **Fréquence** : Automatique ou manuel

## 🛠️ Maintenance

### Logs
Les emails envoyés sont loggés dans les logs PHP :
```php
error_log("EMAIL MANAGER INVIATO - Destinatario: $to, Oggetto: $subject");
```

### Monitoring
- Surveiller les statistiques quotidiennes
- Vérifier les exports régulièrement
- Contrôler les emails automatiques

## 🔄 Intégration

Le manager s'intègre parfaitement avec :
- **Système principal** : Utilise la même base de données
- **Configuration centralisée** : Partage les paramètres
- **Fonctions communes** : Réutilise les utilitaires

## 📞 Support

Pour toute question ou problème :
1. Vérifier les logs PHP
2. Contrôler la configuration Mailtrap
3. Tester les exports manuellement
4. Vérifier les permissions de fichiers

---

**BUON NATALE COOP** - Manager v1.0  
Concorso a premi Puglia e Basilicata
