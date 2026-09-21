# Production container deployment

The existing `docker-compose.yml` remains the development stack. The
production stack uses `docker-compose.production.yml`: a multi-stage build
creates frontend assets and a PHP-FPM app image, while Nginx serves static
files and forwards application requests to PHP-FPM. The host reverse proxy
continues to reach the site at `127.0.0.1:3001`; PostgreSQL is not published
on a host port.

The production image does not contain `.env`, `storage/`, or uploaded
`public/images/`. At runtime, `storage/` and `public/images/` are mounted from
the host. This preserves source-page archives, Laravel storage, and uploaded
images. The app's startup command does not run migrations or seeders.

## Build and check without replacing the live stack

From the repository root:

```sh
docker compose -f docker-compose.production.yml config --quiet
docker compose -f docker-compose.production.yml build web nginx
docker compose -f docker-compose.production.yml run --rm --no-deps web php artisan about
docker compose -f docker-compose.production.yml run --rm --no-deps nginx nginx -t
```

These commands build/check images without stopping the currently running
development service. The one-off Laravel command uses the existing `.env` but
does not change the database.

## Back up and explicitly migrate

Before a deployment that includes schema changes, make a database backup and
review the pending migration list. Migrations are an explicit operator action,
not part of container startup:

```sh
docker exec openshoewiki-db pg_dump -U postgres openshoewiki > openshoewiki-before-deploy.sql
docker compose -f docker-compose.production.yml run --rm --no-deps web php artisan migrate:status
docker compose -f docker-compose.production.yml run --rm --no-deps web php artisan migrate --force
```

Do not run `db:seed` as a routine deployment step. Review any new migration and
its rollback/data-preservation implications first.

## Cut over

Once the production images and any required migrations are verified:

```sh
docker compose -f docker-compose.production.yml up -d db web nginx
curl --fail http://127.0.0.1:3001/healthz
curl --fail --output /dev/null --write-out '%{http_code}\n' https://osw.ktrestoration.xyz/
```

The web container changes from the development PHP server to PHP-FPM, and the
new Nginx container takes over the same loopback-only host port. Expect a brief
HTTP interruption while Compose replaces the web container.

## Roll back the serving image

If the production service fails its checks, stop its web and Nginx containers,
then restore the existing development web service. This does not remove the
database or its data:

```sh
docker compose -f docker-compose.production.yml stop nginx web
docker compose up -d web
```

Never use `docker compose down -v` for this rollback. Database files remain in
`./dev`, source-page archives in `./storage`, and uploaded images in
`./public/images`.
