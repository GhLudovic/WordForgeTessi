# WordForge

Backend d'un **dictionnaire collaboratif** : les joueurs proposent des mots,
et chaque mot inconnu est validé par un vote des 7 premiers joueurs.

> Test technique Symfony. Ce README est enrichi au fil des étapes. En l'état :
> infrastructure, modèle de données et authentification par Bearer token.

## Stack

| Composant     | Version        |
| ------------- | -------------- |
| PHP           | 8.4 (FPM)      |
| Symfony       | 8.1            |
| Doctrine ORM  | + migrations   |
| PostgreSQL    | 16             |
| Serveur web   | nginx 1.27     |
| Tests         | PHPUnit 13     |
| Orchestration | Docker Compose |

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

# 3. Créer le schéma de base de données
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

# 4. Charger les joueurs de test (tokens fixes, voir « Authentification »)
docker compose exec php php bin/console doctrine:fixtures:load --no-interaction
```

L'application répond alors sur **http://localhost:8080**.

> Toutes les routes exigent un Bearer token (voir « Authentification ») :
> `GET /api/me` sans token renvoie un **401 JSON**.

### Ports exposés

| Service    | Hôte   | Conteneur |
| ---------- | ------ | --------- |
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

## Authentification

Toutes les routes sont protégées par un Bearer token qui identifie le joueur
courant :

```
Authorization: Bearer <token>
```

Un token absent ou invalide renvoie un **401 JSON**. Tokens de test (chargés par
les fixtures) :

| Joueur | Token         |
| ------ | ------------- |
| alice  | `token-alice` |
| bob    | `token-bob`   |
| carol  | `token-carol` |

`GET /api/me` renvoie le joueur courant — pratique pour valider un token :

```bash
# 401 sans token
curl -i http://localhost:8080/api/me

# 200 avec un token valide
curl -i -H "Authorization: Bearer token-alice" http://localhost:8080/api/me
# → {"id":1,"username":"alice"}
```

## API

Toutes les routes exigent un Bearer token ; réponses JSON cohérentes.

| Action                   | Route                       | Réponses                                                                                   |
| ------------------------ | --------------------------- | ------------------------------------------------------------------------------------------ |
| Vérifier un mot          | `GET /api/words/{value}`    | `200 {value, status}` — status : `accepted`/`pending`/`rejected`/`unknown`                 |
| Proposer un mot          | `POST /api/words` `{value}` | `201 {id, value, status}` · `409` si déjà existant · `422 {error, violations}` si invalide |
| Obtenir un vote en cours | `GET /api/votes/pending`    | `200 {id, value}` · `204` si aucun mot éligible                                            |

### Sélection du mot à voter (Strategy)

`GET /api/votes/pending` choisit, parmi les mots éligibles, lequel proposer via une
stratégie configurable (`App\Voting\Selection\WordSelectionStrategyInterface`, alias
dans `config/services.yaml`) :

- **`ClosestToQuotaStrategy`** (défaut) — le mot ayant le plus de votes.
  **Justification** : fait converger les votes vers une résolution rapide (un mot
  proche du quota est tranché plus tôt) plutôt que de les éparpiller et de laisser
  des mots stagner en `pending`.
- `OldestPendingStrategy` — le mot le plus ancien (équité FIFO).

## Tests

Préparer la base de test (une fois), puis lancer la suite — dans le conteneur :

```bash
docker compose exec php php bin/console doctrine:database:create --env=test --if-not-exists
docker compose exec php php bin/console doctrine:migrations:migrate --env=test --no-interaction
docker compose exec php composer test
```

Chaque test est isolé dans une transaction annulée à la fin
(`dama/doctrine-test-bundle`) : la base de test n'est jamais polluée entre les
cas.

## Qualité

```bash
docker compose exec php composer cs-fix    # PHP-CS-Fixer (@Symfony + strict types)
docker compose exec php composer phpstan   # PHPStan niveau 8
```

## Structure du projet

```
docker/            # Dockerfiles (php-fpm, nginx) et configuration
config/            # Configuration Symfony (packages, routes, services)
src/               # Code applicatif (Entity, Repository, …)
migrations/        # Migrations Doctrine
tests/             # Tests PHPUnit
docker-compose.yml # Orchestration de la stack
```
