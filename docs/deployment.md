# Déploiement en production

## Prérequis serveur

- PHP 8.2+ avec les extensions `mbstring`, `dom`, `fileinfo`, `pdo_mysql`, `bcmath`, `redis`, `opcache`
- MySQL 8.x (ou service managé compatible)
- Redis 6.x+ (cache, sessions, files d'attente)
- Un serveur web (Nginx recommandé) ou une plateforme PaaS compatible Laravel
- Un worker de file d'attente supervisé (Supervisor, systemd, ou équivalent managé)
- Un accès HTTPS (certificat TLS valide) — obligatoire, l'application gère des données personnelles d'élèves

## Variables d'environnement de production

Reprenez `.env.example` comme base et ajustez au minimum :

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-domaine.tld

DB_CONNECTION=mysql
DB_HOST=<hôte-mysql-production>
DB_DATABASE=<nom-base-production>
DB_USERNAME=<utilisateur-dédié>
DB_PASSWORD=<mot-de-passe-fort>

SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=<hôte-redis-production>
REDIS_PASSWORD=<mot-de-passe-redis>

MAIL_MAILER=smtp
MAIL_HOST=<hôte-smtp>
MAIL_FROM_ADDRESS="no-reply@votre-domaine.tld"
```

> **Important** : `APP_DEBUG=false` est obligatoire en production — un mode debug actif expose la configuration, les identifiants de connexion et la structure interne de l'application.

## Procédure de déploiement

```bash
# 1. Récupérer le code
git pull origin main

# 2. Dépendances (sans les paquets de développement)
composer install --no-dev --optimize-autoloader

# 3. Assets front-end
npm ci
npm run build

# 4. Migrations
php artisan migrate --force

# 5. Caches de production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 6. Redémarrer les workers de file d'attente pour prendre en compte le nouveau code
php artisan queue:restart
```

## Déploiement via Laravel Cloud

Le projet est compatible avec [Laravel Cloud](https://cloud.laravel.com/), qui automatise la majorité des étapes ci-dessus (build, migrations, scaling, certificats TLS). Connectez le dépôt GitHub, renseignez les variables d'environnement listées ci-dessus dans l'interface, et laissez Laravel Cloud gérer le déploiement continu depuis la branche `main`.

## Tâches planifiées

Le planificateur Laravel doit être exécuté en continu (cron ou équivalent managé) :

```cron
* * * * * cd /chemin/vers/AutoGest && php artisan schedule:run >> /dev/null 2>&1
```

La commande `fleet:check-alerts` (alertes véhicules : contrôle technique / assurance expirant sous 30 jours) est notamment pilotée par ce planificateur.

## Files d'attente

Les événements métier (paiement reçu, changement d'étape d'un élève, alertes flotte) déclenchent des jobs asynchrones. Un worker doit tourner en continu :

```bash
php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
```

En production, supervisez ce processus (Supervisor, systemd, ou le mécanisme de process management de votre plateforme) pour un redémarrage automatique en cas d'échec.

## Sauvegardes

> **TODO : Compléter avec les informations spécifiques au projet** — stratégie de sauvegarde de la base de données (fréquence, rétention, test de restauration) et des fichiers uploadés (`storage/app/`) à définir selon l'hébergeur choisi.

## Checklist avant mise en production

- [ ] `APP_ENV=production` et `APP_DEBUG=false`
- [ ] `APP_KEY` généré et différent de l'environnement de développement
- [ ] HTTPS actif avec certificat valide
- [ ] Base de données de production distincte de la base de test/développement
- [ ] Sauvegardes automatisées configurées et testées
- [ ] Worker de file d'attente et planificateur (`schedule:run`) actifs
- [ ] Compte Super-Admin créé avec un mot de passe fort (le compte de démonstration `password` ne doit **jamais** être utilisé en production)
- [ ] Monitoring des erreurs applicatives en place (voir `storage/logs/laravel.log` ou un service externe)
- [ ] `php artisan test --compact` passe sur la version déployée avant bascule du trafic

Voir aussi [docs/database.md](database.md) pour le détail du schéma et [SECURITY.md](../SECURITY.md) pour la politique de sécurité.
