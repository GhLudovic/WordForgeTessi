# WordForge [![wakatime](https://wakatime.com/badge/user/8da17994-0e2d-4414-aad8-be7b3cf6f984/project/fdbcc112-e6a4-4f25-a97d-4cc4680b7d9f.svg)](https://wakatime.com/badge/user/8da17994-0e2d-4414-aad8-be7b3cf6f984/project/fdbcc112-e6a4-4f25-a97d-4cc4680b7d9f)

Backend d'un **dictionnaire collaboratif**. Les joueurs proposent des mots ; un mot
inconnu passe en `pending` et est tranché par le vote des **7 premiers** joueurs :
`accepted` si la majorité vote `yes`, `rejected` sinon (nombre impair ⇒ pas d'ex-aequo).
Toutes les routes sont protégées par un Bearer token identifiant le joueur courant.

## Stack

| Composant      | Version          |
|----------------|------------------|
| PHP            | 8.4 (FPM)        |
| Symfony        | 8.1              |
| Doctrine ORM   | + migrations     |
| PostgreSQL     | 16               |
| Serveur web    | nginx 1.27       |
| Tests          | PHPUnit 13       |
| Orchestration  | Docker Compose   |

## Démarrage

Prérequis : **Docker + Docker Compose v2** (aucune installation de PHP/Composer sur l'hôte).

```bash
# 1. Construire et démarrer toute la stack
docker compose up -d --build

# 2. Installer les dépendances PHP (vendor/ n'est pas versionné)
docker compose exec php composer install

# 3. Créer le schéma de base de données
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

# 4. Charger les joueurs de test (tokens fixes, voir ci-dessous)
docker compose exec php php bin/console doctrine:fixtures:load --no-interaction
```

L'application répond sur **http://localhost:8080**.

| Service    | Hôte   | Conteneur | Surcharge       |
|------------|--------|-----------|-----------------|
| nginx      | `8080` | `80`      | `NGINX_PORT`    |
| PostgreSQL | `5433` | `5432`    | `POSTGRES_PORT` |

> Le port Postgres hôte est `5433` (pas `5432`) pour éviter les conflits avec une
> instance existante. En cas de conflit sur 8080/5433 :
> `POSTGRES_PORT=5434 NGINX_PORT=8081 docker compose up -d`.

### Configuration de l'environnement

La connexion à la base est pilotée par les variables `POSTGRES_*` de `.env` (versionné,
valeurs de dev), qui alimentent à la fois le service `database` de `docker-compose.yml`
et la `DATABASE_URL`. En conteneur, `docker-compose.yml` impose `DATABASE_URL` (hôte
`database`), **prioritaire** sur les fichiers `.env`. Pour les outils lancés depuis
l'hôte, créer un `.env.local` (non versionné) pointant sur `127.0.0.1:5433`.

## Authentification — tokens de test

Chaque requête doit inclure le header :

```
Authorization: Bearer <token>
```

Token absent ou invalide ⇒ **401 JSON**. Joueurs chargés par les fixtures :

| Joueur | Token         |
|--------|---------------|
| alice  | `token-alice` |
| bob    | `token-bob`   |
| carol  | `token-carol` |

## Endpoints

Réponses JSON, codes HTTP explicites. `BASE=http://localhost:8080`, `AUTH="Authorization: Bearer token-alice"`.

### `GET /api/me` — joueur courant

Pratique pour valider un token.

- **200** `{"id":1,"username":"alice"}` · **401** sans token valide.

```bash
curl -i -H "$AUTH" "$BASE/api/me"
```

### `GET /api/words/{value}` — vérifier un mot

- **200** `{"value":"licorne","status":"pending"}` — `status` ∈ `accepted` / `pending` / `rejected` / `unknown` (mot absent).

```bash
curl -i -H "$AUTH" "$BASE/api/words/licorne"
```

### `POST /api/words` — proposer un mot

Crée un mot inconnu en `pending` (auteur = joueur courant).

- Payload : `{"value":"licorne"}`
- **201** `{"id":1,"value":"licorne","status":"pending"}`
- **409** si le mot existe déjà (quel que soit son statut)
- **422** `{"error":"...","violations":[...]}` si invalide (espace, > 32 caractères, vide)

```bash
curl -i -H "$AUTH" -H "Content-Type: application/json" \
  -d '{"value":"licorne"}' "$BASE/api/words"
```

### `GET /api/votes/pending` — obtenir un vote en cours

Retourne un mot que le joueur courant peut voter (cf. critères d'éligibilité).

- **200** `{"id":1,"value":"licorne"}` · **204** si aucun mot éligible.

```bash
curl -i -H "Authorization: Bearer token-bob" "$BASE/api/votes/pending"
```

### `POST /api/words/{id}/votes` — voter pour un mot

- Payload : `{"value":"yes"}` ou `{"value":"no"}`
- **201** `{"wordId":1,"status":"pending"}` — `status` du mot après le vote (résolu au 7ᵉ)
- **403** inéligible (mot non `pending`, son propre mot, déjà voté, quota atteint)
- **404** mot inexistant · **422** valeur ≠ `yes`/`no`

```bash
curl -i -H "Authorization: Bearer token-bob" -H "Content-Type: application/json" \
  -d '{"value":"yes"}' "$BASE/api/words/1/votes"
```

## Choix techniques

**Architecture en services applicatifs + Events synchrones — pas de CQRS ni Messenger.**
Le périmètre (un test de quelques heures, noté sur la clarté) ne justifie pas la
sur-structuration : contrôleurs fins, logique métier dans `WordService` / `VoteService`,
DTOs en entrée (`#[MapRequestPayload]`) et en sortie. CQRS et un bus de messages
auraient ajouté de l'indirection sans bénéfice ici ; ils seraient pertinents sur un
domaine plus riche ou un besoin d'asynchronisme réel (absent ici).

**Validation extensible (étape 4).** Chaque règle implémente `WordConstraintInterface`,
taguée automatiquement (`#[AutoconfigureTag]`) et collectée par `WordValidator` via
`#[AutowireIterator]`. **Ajouter une règle = ajouter une classe**, sans toucher à
l'existant ni à la configuration.

**Éligibilité au vote via un Symfony Voter.** `VoteEligibilityVoter` (attribut
`CAST_VOTE`, sujet `Word`) encapsule les 4 critères (mot `pending`, non-auteur, non
déjà voté, quota non atteint). Il est **réutilisé** : le service « obtenir un vote »
filtre via `Security::isGranted`, et « voter » autorise via `denyAccessUnlessGranted`.
*Security Voter* à ne pas confondre avec l'entité `Vote`.

**Sélection du mot via Strategy.** `WordSelectionStrategyInterface` + deux
implémentations ; la stratégie active est un alias dans `config/services.yaml`.
Défaut **`ClosestToQuotaStrategy`** (le mot le plus voté) : fait converger les votes
vers une résolution rapide plutôt que de les éparpiller et laisser des mots stagner.
Alternative `OldestPendingStrategy` (équité FIFO).

**Résolution au 7ᵉ vote via un Event synchrone.** `VoteService::castVote` émet
`VoteCastEvent` ; le listener `ResolveWordOnVoteCast` tranche le mot. L'événement est
dispatché **dans la transaction**, donc le listener s'exécute de façon synchrone, sous
le même verrou — la résolution reste atomique tout en découplant l'enregistrement du
vote de sa résolution.

**Gestion de la concurrence.** `castVote` ouvre une transaction, pose un **verrou
pessimiste** (`SELECT … FOR UPDATE`) sur le mot, **re-vérifie le statut dans le verrou**
(un mot résolu entre-temps est refusé en 409), puis enregistre et résout. Filet de
sécurité en base : contrainte d'**unicité `(player, word)`** (un seul vote par joueur
et par mot) et quota impair (pas d'ex-aequo).

**Gestion d'erreurs uniforme.** `ApiExceptionSubscriber` traduit les exceptions sous
`/api` en JSON cohérent (`{error, violations?}`, codes 409/422/404/500). La sécurité
reste au firewall : 401 via l'entry point du `TokenAuthenticator`, 403 via
`JsonAccessDeniedHandler`.

## Démarche

- **TDD strict** : cycle RED → GREEN → REFACTOR, le test écrit et montré en échec avant
  l'implémentation. Pas de mock de la base (vraie DB, transactions annulées par test via
  `dama/doctrine-test-bundle`).
- **Conventional Commits** (en français) reflétant les phases (`test(...)` en RED,
  `feat(...)` en GREEN, `refactor(...)` ensuite) ; **une PR par unité fonctionnelle**.

## Tests

```bash
# Préparer la base de test (une fois)
docker compose exec php php bin/console doctrine:database:create --env=test --if-not-exists
docker compose exec php php bin/console doctrine:migrations:migrate --env=test --no-interaction

# Lancer la suite
docker compose exec php composer test
```

## Qualité

```bash
docker compose exec php composer cs-fix    # PHP-CS-Fixer (@Symfony + strict types)
docker compose exec php composer phpstan   # PHPStan niveau 8
```

## Structure du projet

```
src/
  Controller/        # contrôleurs fins (un par action)
  Service/           # WordService, VoteService (logique métier)
  Dto/               # objets d'entrée (MapRequestPayload) et de sortie
  Entity/ Enum/      # Player, Word, Vote ; WordStatus, VoteValue
  Repository/        # accès Doctrine
  Validation/        # WordValidator + contraintes taguées (extensible)
  Voting/            # VotingPolicy (quota) + stratégies de sélection
  Security/          # TokenAuthenticator, Voter d'éligibilité, access denied handler
  Event/ EventListener/   # VoteCastEvent → résolution du mot
  Exception/ EventSubscriber/  # exceptions de domaine + réponses JSON
docker/              # Dockerfiles (php-fpm, nginx) et configuration
config/ migrations/ tests/
docker-compose.yml
```
