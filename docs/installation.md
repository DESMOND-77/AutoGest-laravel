# Installation

Ce guide décrit l'installation d'Auto-GestBoard en environnement de développement local.

## Prérequis

| Outil        | Version minimale | Notes |
| ------------- | ------------------ | ----- |
| PHP           | 8.2 (8.5 recommandé) | Extensions requises : `mbstring`, `dom`, `fileinfo`, `mysql`/`pdo_mysql`, `bcmath`, `redis` |
| Composer      | 2.x                 | Gestionnaire de dépendances PHP |
| Node.js       | 20.x ou supérieur   | Pour la compilation des assets (Vite) |
| npm           | 10.x ou supérieur   | Fourni avec Node.js |
| MySQL         | 8.x                 | Base de données principale |
| Redis         | 6.x ou supérieur    | Cache, sessions, files d'attente |

## Étapes d'installation

### 1. Cloner le dépôt

```bash
git clone https://github.com/DESMOND-77/AutoGest.git
cd AutoGest
```

### 2. Installer les dépendances

```bash
composer install
npm install
```

### 3. Configurer l'environnement

```bash
cp .env.example .env
php artisan key:generate
```

Éditez ensuite `.env` pour renseigner vos identifiants de base de données et de Redis locaux (voir [Variables d'environnement](../README.md#variables-denvironnement) dans le README).

### 4. Créer la base de données

Créez une base de données MySQL vide correspondant à `DB_DATABASE` dans votre `.env` (par défaut `autoecole_jh_laravel`) :

```bash
mysql -u root -p -e "CREATE DATABASE autoecole_jh_laravel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 5. Exécuter les migrations et le seeding

```bash
php artisan migrate --seed
```

Le seeder crée les quatre rôles applicatifs (`superadmin`, `admin`, `moniteur`, `eleve`). Pour des données de démonstration complètes, consultez les factories disponibles dans `database/factories/` et `app/Domain/*/Database/Factories/`.

### 6. Compiler les assets front-end

```bash
npm run build
```

### 7. Lancer l'application

```bash
composer run dev
```

Cette commande démarre en parallèle : le serveur PHP intégré (`php artisan serve`), le worker de file d'attente, `php artisan pail` (logs en direct) et Vite en mode watch. L'application est alors accessible sur `http://localhost:8001` (ou la valeur d'`APP_URL` dans votre `.env`).

## Import des données historiques (optionnel)

Si vous migrez depuis l'ancienne application PHP procédurale, une commande dédiée importe les inscriptions et paiements historiques :

```bash
php artisan import:legacy-students {structure_id} {chemin/vers/le/dossier/data}
```

Le dossier cible doit contenir un fichier `inscription.csv` au format de l'export historique. Cette commande est idempotente : elle peut être relancée sans créer de doublons.

## Vérification de l'installation

```bash
php artisan test --compact
```

Si la suite de tests passe intégralement, l'installation est fonctionnelle. En cas d'échec lié à la base de données de test, vérifiez que les identifiants dans `phpunit.xml` correspondent à une base MySQL accessible localement (`autoecole_jh_laravel_test`).

## Problèmes fréquents

| Symptôme | Cause probable | Solution |
| --------- | ---------------- | --------- |
| `Unable to locate file in Vite manifest` | Assets non compilés | Exécutez `npm run build` ou `npm run dev` |
| `SQLSTATE[HY000] [2002] Connection refused` | MySQL non démarré ou identifiants incorrects | Vérifiez `DB_HOST`/`DB_PORT`/`DB_USERNAME`/`DB_PASSWORD` dans `.env` |
| Erreur de connexion Redis | Redis non démarré | Démarrez le service Redis local, ou basculez temporairement `SESSION_DRIVER`/`CACHE_STORE`/`QUEUE_CONNECTION` sur `file`/`sync` |
| `No application encryption key has been specified` | `APP_KEY` non généré | `php artisan key:generate` |

Pour la configuration de production, voir [docs/deployment.md](deployment.md).
