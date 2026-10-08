
# 🎵 Musique API

API REST développée en PHP avec le framework Slim permettant de gérer et de consulter des données musicales.

Le projet utilise une base de données MySQL et propose différents endpoints pour accéder aux albums, artistes, notes et classements.

## 🛠️ Technologies utilisées

- PHP 8
- Slim Framework
- MySQL
- Composer
- PDO
- JSON
- Git / GitHub
- Alwaysdata (hébergement)

## 📁 Structure du projet

```text
Musique_api/
├── app/
│   ├── dependencies.php
│   ├── middleware.php
│   ├── repositories.php
│   ├── routes.php
│   └── settings.php
├── public/
│   └── index.php
├── src/
│   ├── Application/
│   ├── Domain/
│   └── Entity/
├── composer.json
└── README.md
```

## 🚀 Installation

### 1. Cloner le dépôt

```bash
git clone URL_DU_DEPOT
cd Musique_api
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configurer la base de données

Configurer les paramètres de connexion MySQL selon l'environnement utilisé.

La base de données doit contenir les tables nécessaires au fonctionnement de l'API.

### 4. Démarrer le serveur

```bash
php -S localhost:8080 -t public
```

L'API est alors accessible à l'adresse :

http://localhost:8080/api

## 🌐 API en ligne

https://arnaudddddd.alwaysdata.net/api/albums

## 📡 Endpoints REST

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | /api/albums | Liste des albums |
| GET | /api/ratings | Liste des notes |
| GET | /api/rankings/artists | Classement des artistes |
| GET | /api/duel/albums/{firstId}/{secondId} | Comparaison de deux albums |

### Exemples

Récupérer les albums :

```http
GET /api/albums
```

Récupérer les notes :

```http
GET /api/ratings
```

Obtenir le classement des artistes :

```http
GET /api/rankings/artists?limit=10
```

Comparer deux albums :

```http
GET /api/duel/albums/1/2
```

## ⭐ Fonctionnalités spécifiques

### 1. Classement des artistes

L'API propose un classement des artistes selon leurs évaluations.

Le paramètre `limit` permet de limiter le nombre de résultats retournés.

Exemple :

```http
GET /api/rankings/artists?limit=5
```

### 2. Duel entre deux albums

Cette fonctionnalité permet de comparer deux albums à partir de leurs identifiants.

Les deux identifiants sont transmis directement dans l'URL.

Exemple :

```http
GET /api/duel/albums/1/2
```

## 🏗️ Architecture

Le projet utilise une architecture séparant :

- Les routes HTTP
- La logique applicative
- Les repositories
- Les entités
- L'accès aux données MySQL

Les repositories centralisent les requêtes vers la base de données.

Les réponses de l'API sont transmises au format JSON.

## 👨‍💻 Auteur

**Arnaud Jancart**

Projet développé dans le cadre du BTS CIEL, option Informatique et Réseaux.
