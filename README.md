# Movie Database

A local PHP and MySQL application for browsing films, casts, actor filmographies, and anonymous reviews.

## Requirements

- Docker Engine or Docker Desktop
- Docker Compose v2

The application container uses PHP 8.4 with Apache, `pdo_mysql`, and `mbstring`. MySQL 8.4 is used for the database and enforces the review-rating `CHECK` constraint.

## Start locally

1. Create a local environment file and set non-production passwords:

	```sh
	cp .env.example .env
	```

2. Start the application and database:

	```sh
	docker compose up --build
	```

3. Open `http://localhost:8081` (or the port configured by `APP_PORT` in `.env`).

On the first start, MySQL imports `database/schema.sql` and `database/seed.sql`. The sample catalog includes fictional films, actors, cast relationships, and reviews. Initialization scripts run only when the MySQL data volume is empty; subsequent starts preserve the database.

To stop the services while retaining data, press `Ctrl+C` and run:

```sh
docker compose down
```

To follow application or database logs:

```sh
docker compose logs -f web
docker compose logs -f db
```

Removing the database volume permanently deletes the local database and all reviews:

```sh
docker compose down -v
```

## Configuration

| Variable | Default / purpose |
| :--- | :--- |
| `APP_PORT` | `8081`, host port for the web application. |
| `DB_GATEWAY_IP` | `172.17.0.1`, Docker's default bridge gateway. If `docker network inspect bridge` reports another gateway, update this value in `.env`. |
| `DB_HOST_PORT` | `3307`, host-side MySQL port bound only to `DB_GATEWAY_IP`. |
| `DB_HOST` | Set by Compose to `host.docker.internal`, resolved to Docker's host gateway. |
| `DB_PORT` | Set by Compose to `DB_HOST_PORT`; the container's MySQL port remains `3306`. |
| `DB_NAME` | Set by Compose to `moviedatabase`. |
| `DB_USER` | Local application database user; default `movieapp`. |
| `DB_PASSWORD` | Required local application database password. |
| `DB_ROOT_PASSWORD` | Required local MySQL root password, used only by the database service. |

`.env` is local-only and ignored by Git. The example credentials are for development only; replace them before sharing a development environment. Do not use them in production.
The MySQL port is bound only to the configured Docker bridge gateway so the PHP container can reach it without exposing the database on every host interface.
Check the bridge gateway with `docker network inspect bridge --format '{{(index .IPAM.Config 0).Gateway}}'`; if it differs from `DB_GATEWAY_IP`, update `.env` to match.

## Database changes

The initialization SQL files are mounted into MySQL's initialization directory. They are not rerun against an existing volume. For schema changes to an existing database, apply a reviewed migration or SQL change explicitly. Do not remove the data volume unless deleting the local data is intended.

## Security notes

- PHP uses PDO prepared statements and reads database credentials from environment variables.
- Apache applies a restrictive Content Security Policy, disables directory listings and TRACE, limits request bodies, and blocks the `backend`, `database`, `Docs`, `docker`, and `.git` directories, dotfiles, SQL, and project documentation/config files.
- PHP disables displayed errors and version disclosure; sessions use strict mode and `HttpOnly`/`SameSite=Lax` cookies. Review submissions are HTML-escaped, validated on the server, and protected by a session-backed CSRF token.
- This project is intended for local development. It does not include user accounts, moderation, rate limiting, or production deployment hardening.
- Compose publishes the web port on all host interfaces to support development-container port forwarding. On a shared or LAN-connected machine, restrict access with host firewall rules; do not expose this development service publicly.