---
name: Render.com deployment for PHP+MariaDB apps on Replit
description: How this project (and similar PHP+MariaDB Replit apps) get deployed to Render.com, since Render has no nix-shell support.
---

Render.com does not support Replit's nix-shell workflow pattern. For a PHP+MariaDB app that runs both processes as siblings (like this project's `start.sh`), the working approach is a single Docker container:

- Base image: `php:8.3-cli-bookworm` (Debian-based, so `apt-get install mariadb-server` works)
- Install `pdo_mysql`/`mysqli` via `docker-php-ext-install` (needs `default-libmysqlclient-dev` + `pkg-config` as build deps)
- A Render-specific start script (separate from the Replit `start.sh`) initializes MariaDB's datadir on first run (`mariadb-install-db`/`mysql_install_db` fallback chain), starts `mysqld_safe --user=root` (root because container runs as root, unlike Replit's user), creates the DB/user, writes the app's local DB config, runs idempotent schema migration, then `exec php -S 0.0.0.0:$PORT` in the foreground (must be the final `exec`'d process for Docker signal handling).
- The PHP server must bind to `$PORT` (Render injects/uses this), not a hardcoded port — set via `render.yaml` envVars if the user needs a specific port value.
- `render.yaml` blueprint: `env: docker`, `dockerfilePath`, `envVars.PORT`, optional `disk` mount at `/var/lib/mysql` for persistence (paid plans only — free plan loses DB data on every deploy/restart, this is a real limitation to warn users about).

**Why:** Confirmed by building and running the actual Docker image locally (`docker build` + `docker run` + `curl` returning HTTP 200, tables created, PHP server up) before telling the user it would work — don't just hand-wave Docker configs for unfamiliar host platforms without a local verification pass when `docker` is available in the sandbox.

**How to apply:** Any time a Replit PHP/MariaDB (or similar multi-process) app needs deployment to a platform without nix-shell support (Render, Railway, Fly.io, etc.), reach for the single-container Docker pattern above, and validate the image locally via `docker build`/`docker run` if the sandbox has a docker daemon before reporting success.
