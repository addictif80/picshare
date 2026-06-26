# PicShare

Plateforme de partage de photos pour événements.

## Installation

### Prérequis
- PHP >= 8.1 avec extensions : GD, PDO, ZIP, JSON
- MySQL >= 8.0 / MariaDB >= 10.6
- Composer
- Serveur Apache avec mod_rewrite

### Étapes

1. **Cloner/déposer les fichiers** sur le serveur

2. **Installer les dépendances** :
   ```bash
   composer install --no-dev
   ```

3. **Permissions** :
   ```bash
   chmod -R 755 public/uploads storage
   chmod -R 777 public/uploads storage
   ```

4. **Accéder à l'installeur** : `https://votre-domaine.com/install/`

5. **Configurer** dans Administration → Paramètres :
   - SMTP (emails)
   - Stripe (paiements)
   - Filigrane
   - Logo du site
   - Tarification

6. **Supprimer le dossier install** après installation pour la sécurité.

## Tâche CRON (nettoyage automatique)

Ajouter dans le cron du serveur (nettoyage quotidien des albums expirés) :

```
0 3 * * * curl -s -X POST https://votre-domaine.com/admin/cleanup -H "X-Cron-Key: VOTRE_CLE"
```

Ou configurer via l'interface admin → "Nettoyage auto".

## Structure

```
picshare/
├── config/         Configuration DB et app
├── install/        Assistant d'installation
├── public/         Assets CSS/JS + uploads (servis via PHP)
├── src/            Code PHP (PSR-4)
│   ├── Controllers/
│   ├── Core/
│   ├── Helpers/
│   └── Services/
├── storage/        Archives ZIP + QR codes (non public)
├── vendor/         Dépendances Composer
├── views/          Templates PHP
└── index.php       Point d'entrée
```

## Stripe

URL webhook à configurer dans le dashboard Stripe :
`https://votre-domaine.com/webhook/stripe`

Événements à activer : `checkout.session.completed`

## CyberPanel

1. Créer un site PHP 8.1+
2. Déposer les fichiers dans le dossier public_html
3. Activer mod_rewrite (activé par défaut sur CyberPanel/OpenLiteSpeed)
4. Configurer le fichier `.htaccess` (déjà fourni)
