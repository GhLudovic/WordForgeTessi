# WordForge

Backend d'un **dictionnaire collaboratif** : les joueurs proposent des mots,
et chaque mot inconnu est validé par un vote des 7 premiers joueurs.

> Test technique Symfony. Ce README sera enrichi au fil des étapes (modèle de
> données, endpoints, fixtures de tokens). En l'état, il couvre l'infrastructure
> et le lancement du projet.

## Stack

| Composant      | Version            |
|----------------|--------------------|
| PHP            | 8.4 (FPM)          |
| Symfony        | 8.1                |
| Doctrine ORM   | + migrations       |
| PostgreSQL     | 16                 |
| Serveur web    | nginx 1.27         |
| Tests          | PHPUnit 13         |
| Orchestration  | Docker Compose     |

## Prérequis

- Docker + Docker Compose v2

Aucune installation de PHP/Composer sur la machine hôte n'est nécessaire : tout
s'exécute dans les conteneurs.

## Démarrage

```bash
# 1. Construire et démarrer toute la stack
docker compose up -d --build

# 2. Installer les dépendances PHP (vendor/ n'est pas versionné)
docker compose exec php composer install
```

L'application répond alors sur **http://localhost:8080**.

> À ce stade aucune route métier n'est définie : `GET /` renvoie un 404 Symfony.
> C'est attendu et confirme que la chaîne nginx → PHP-FPM → Symfony fonctionne.

### Ports exposés

| Service    | Hôte   | Conteneur |
|------------|--------|-----------|
| nginx      | `8080` | `80`      |
| PostgreSQL | `5433` | `5432`    |

Le port Postgres hôte est `5433` (et non `5432`) pour éviter les conflits avec
une instance déjà présente. Ces ports sont surchargeables via `NGINX_PORT` et
`POSTGRES_PORT`.

## Configuration de l'environnement

La connexion à la base est pilotée par les variables `POSTGRES_*` définies dans
`.env` (versionné, valeurs de développement). Elles alimentent à la fois le
service `database` de `docker-compose.yml` et la `DATABASE_URL` de Symfony.

Précédence des sources de configuration :

- **En conteneur** : `docker-compose.yml` injecte `DATABASE_URL` comme variable
  d'environnement réelle (hôte `database`). Elle est **prioritaire** sur les
  fichiers `.env`, ce qui garantit une connexion correcte même si un
  `.env.local` traîne dans le projet.
- **Sur l'hôte** (outils Symfony lancés hors conteneur) : créer un `.env.local`
  (non versionné) pointant sur `127.0.0.1:5433`. Un exemple est fourni dans
  le dépôt local.

## Tests

```bash
docker compose exec php php bin/phpunit
```

Chaque test est isolé dans une transaction annulée à la fin
(`dama/doctrine-test-bundle`) : la base de test n'est jamais polluée entre les
cas.

## Structure du projet

```
docker/            # Dockerfiles (php-fpm, nginx) et configuration
config/            # Configuration Symfony (packages, routes, services)
src/               # Code applicatif (Entity, Repository, …)
migrations/        # Migrations Doctrine
tests/             # Tests PHPUnit
docker-compose.yml # Orchestration de la stack
```
