# Villa Privée

## Description du Projet

Villa Privée est une plateforme de gestion de locations de villas de luxe développée avec Symfony 6. Elle permet aux propriétaires de publier leurs villas et aux utilisateurs de les réserver.

### Fonctionnalités Principales

- Système d'authentification complet
- Gestion des villas (CRUD)
- Système de réservation
- Gestion des avis et notes
- Système de favoris
- Interface d'administration
- Tests unitaires et fonctionnels

## Installation

### Prérequis

- PHP 8.1 ou supérieur
- Composer
- Symfony CLI
- MySQL 8.0

### Installation avec Docker

1. Cloner le projet
```bash
git clone https://github.com/votre-repo/villa-privee.git
cd villa-privee
```

2. Lancer les conteneurs Docker
```bash
docker compose build --no-cache
docker compose up -d
```

3. Installer les dépendances dans le conteneur
```bash
docker compose exec php composer install
```

4. Accès au site
```bash
http://localhost
```

### Charger les Fixtures

Le projet inclut deux types de fixtures :
- `UserFixtures` : Crée des utilisateurs de test (admin, propriétaires, utilisateurs normaux)
- `AppFixtures` : Crée des données de base pour l'application

Pour charger les fixtures en local :
```bash
php bin/console doctrine:fixtures:load
```

Pour charger les fixtures avec Docker :
```bash
docker compose exec php php bin/console doctrine:fixtures:load
```

Utilisateurs créés par les fixtures :
- Admin : admin@example.com / Azerty11
- Propriétaire : owner@example.com / Azerty11
- Utilisateur : user@example.com / Azerty11


## Tests

### Configuration des Tests

1. Créer la base de données de test
```bash
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test
```

### Exécuter les Tests

En local :
```bash
# Exécuter tous les tests
php bin/phpunit

# Exécuter un fichier de test spécifique
php bin/phpunit tests/Controller/VillaControllerTest.php

# Exécuter une méthode de test spécifique
php bin/phpunit --filter testShowVillaAsOwner
```

## Structure du Projet

- `config/` : Configuration de l'application
- `src/` : Code source de l'application
  - `Controller/` : Contrôleurs
  - `Entity/` : Entités Doctrine
  - `Repository/` : Repositories
  - `Security/` : Classes de sécurité
  - `DataFixtures/` : Données de test
- `templates/` : Templates Twig
- `tests/` : Tests unitaires et fonctionnels
- `docker/` : Configuration Docker
- `migrations/` : Migrations de base de données

## Licence

Ce projet est sous licence MIT. Voir le fichier LICENSE pour plus de détails.