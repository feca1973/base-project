# Base Project

Dockerizált alap projektváz:

- Nuxt 3 publikus frontend (SSR)
- Nuxt 3 admin felület (SPA, `/admin` alatt)
- Symfony 7.4 backend API
- Docker Compose alapú infrastruktúra saját nginx proxyval

Nuxt projektkönyvtárak:

- `frontend/nuxt`: publikus Nuxt frontend projekt.
- `admin/nuxt`: Nuxt admin projekt.

Mindkét Nuxt projekt a Nuxt 3 alap könyvtárszerkezetét használja
(`app.vue`, `pages/`, `assets/` közvetlenül a projekt gyökerében).

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

## Lokális dev környezet

Előfeltétel: Docker (compose pluginnal) és curl. Minden más (PHP, Composer,
Node) konténerben fut.

A dev mód az alapértelmezett: a `docker compose` automatikusan betölti a
`docker-compose.yml` és a `docker-compose.override.yml` fájlt. Utóbbi adja
hozzá a Mailpitet és a dev proxy konfigurációt. A forráskód bind mounttal van
a konténerekben, így a változások azonnal látszanak (hot reload).

### Telepítés (első alkalommal)

A repository gyökeréből:

```bash
./install.sh [projekt_nev]
```

A projektnév alapértelmezése a mappa neve. Ebből lesznek a konténernevek, a
compose projektnév és az adatbázis neve.

Az installer:

1. Ha még nincs root `.env`, létrehozza a `.env.example` alapján: projektnév,
   véletlen `APP_SECRET` és MySQL jelszavak, szabad host portok
   (`local-port-finder.sh`). Meglévő `.env`-et nem ír felül.
2. Buildeli az image-eket, és lefuttatja a `composer install`-t.
3. Elindítja a konténereket, és megvárja, hogy fussanak.
4. Lefuttatja a Doctrine migrationöket. Friss telepítésnél ez létrehozza a
   `users` és `magic_login_tokens` táblát és az `admin@nexcode.hu` super admin
   usert.
5. Megvárja, hogy az API, a frontend, az admin és a Mailpit válaszoljon.

Az első futás pár percig tart. Az installer újrafuttatható.

### Indítás és leállítás (telepítés után)

Indítás:

```bash
docker compose up -d
```

A két Nuxt konténer minden induláskor `npm ci`-t futtat, ezért indítás után
kell nekik egy kis idő. Addig a frontend és az admin 502-es hibát adhat.
Követés: `docker compose logs -f frontend admin`.

Leállítás:

```bash
docker compose down
```

### Dev URL-ek

A port az installer kimenetében és a root `.env` `HTTP_PORT` értékében
látszik (ha szabad, 8080):

- Frontend: `http://localhost:8080/`
- Backend API: `http://localhost:8080/api/`
- API healthcheck: `http://localhost:8080/api/health`
- Admin: `http://localhost:8080/admin/`
- Mailpit: `http://localhost:8080/mailpit/`
- phpMyAdmin: `http://localhost:8081/`

### Hasznos parancsok

```bash
docker compose ps
docker compose logs -f
docker compose logs -f php
docker compose logs -f frontend
docker compose logs -f admin
```

### Teljes újratelepítés

Csak akkor, ha az adatbázis és a volume-ok tartalma is törölhető:

```bash
docker compose down -v
rm .env
./install.sh
```

## Production indítási modell

A production compose önálló fájl:

```bash
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

A MySQL a `.env` vagy `.env.production` alapján hozza létre az adatbázist és
az adatbázis usert. Az `install.sh` a projektnévből képzi a nevüket, a
`.env.example` alapértéke `base_project` / `base_project_user`.

A sémát a Doctrine migrationök építik fel (`backend/symfony/migrations/`).
A kezdő migration létrehozza:

- a `users` és a `magic_login_tokens` táblát,
- az `admin@nexcode.hu` usert `ROLE_SUPER_ADMIN` szerepkörrel. Jelszó mezője
  nincs: a magic linkes belépéshez a `MagicLoginToken` entitás megvan, de maga
  a belépési folyamat még nincs megvalósítva.

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
