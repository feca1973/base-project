# Base Project

Dockerizált alap projektváz:

- Nuxt publikus frontend
- Nuxt admin felület
- Symfony backend API
- Docker Compose alapú infrastruktúra saját nginx proxyval

A projekt gyökere a repositoryn belül: `site/`.

Nuxt projektkönyvtárak:

- `frontend/nuxt`: publikus Nuxt frontend projekt.
- `admin/nuxt`: Nuxt admin projekt.

Mindkét Nuxt projektben a belső `app/` könyvtár a Nuxt 4 forráskönyvtára.

## Szolgáltatások

- `proxy`: nginx belépési pont.
- `frontend`: publikus Nuxt frontend.
- `admin`: Nuxt admin felület.
- `backend`: Symfony nginx HTTP réteg.
- `php`: Symfony/PHP-FPM alkalmazáskonténer.
- `mysql`: üres MySQL adatbázis.
- `redis`: Redis.
- `elasticsearch`: kereső infrastruktúra.
- `phpmyadmin`: lokális adatbázis-adminisztráció.
- `mailpit`: lokális/dev mailer, csak `docker-compose.override.yml` használatával.

Nincs külön cron vagy Symfony Messenger worker konténer.

## Lokális dev indítás

```bash
cd site
cp .env.example .env
docker compose config --quiet
docker compose run --rm --no-deps php composer install
docker compose up -d --build
```

Dev URL-ek:

- Frontend: `http://localhost:8080/`
- Backend API: `http://localhost:8080/api/`
- API healthcheck: `http://localhost:8080/api/health`
- Admin: `http://localhost:8080/admin/`
- Mailpit: `http://localhost:8080/mailpit/`
- phpMyAdmin: `http://localhost:8081/`

Hasznos parancsok:

```bash
docker compose logs -f
docker compose logs -f php
docker compose logs -f frontend
docker compose logs -f admin
docker compose down
```

Teljes lokális adat törlése csak akkor, ha az adatbázis/volume tartalom is
törölhető:

```bash
docker compose down -v
```

## Production indítási modell

A production compose önálló fájl:

```bash
cd site
cp .env.production.example .env.production
docker compose --env-file .env.production -f docker-compose.prod.yml config --quiet
docker compose --env-file .env.production -f docker-compose.prod.yml up -d --build
```

Production domainek:

- Frontend: `https://example.com/`
- API: `https://api.example.com/`
- Admin: `https://admin.example.com/`

A production compose HTTP-n publikálja a proxy containert a `HTTP_PORT`
értékén. TLS/cert kezeléshez a szerver oldali beállítást külön kell véglegesíteni
vagy a proxy containert később HTTPS-re kell bővíteni.

Production phpMyAdmin csak külön profillal indul:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml --profile tools up -d phpmyadmin
```

## Adatbázis

Az adatbázis induláskor üres. A MySQL a `.env` vagy `.env.production` alapján
hozza létre:

- adatbázis: `base_project`
- user: `base_project_user`

Az új adatmodell és a Symfony migrationök
tervezési lépésben készülnek el.

## Backend

Symfony parancs futtatása:

```bash
docker compose exec php php bin/console
```

Migration futtatása, amikor lesznek migrációk:

```bash
docker compose exec php php bin/console doctrine:migrations:migrate
```

API healthcheck:

```bash
curl http://localhost:8080/api/health
```

## Production image build ellenőrzés

```bash
docker build --target production -t base-project-backend:production backend
docker build --target nginx-production -t base-project-backend-nginx:production backend
docker build --target production -t base-project-frontend:production frontend
docker build --target production -t base-project-admin:production admin
```
