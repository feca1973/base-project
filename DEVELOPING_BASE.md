# Fejlesztési belépési alap

Ez a fájl a Base Project projekt fejlesztési belépési protokollja.
Új chatben vagy új fejlesztési feladat előtt ezt kell először elolvasni, és
ez alapján kell eldönteni, milyen további dokumentumokat kell megnyitni.

## Olvasási sorrend

1. `DEVELOPING_BASE.md`
2. `RULES.md`
3. `README.md`
4. A konkrét feladathoz tartozó kód és konfigurációs fájlok
5. Később létrejövő `docs/*.md` vagy tervfájlok, ha a feladat érinti őket

## Dokumentumok szerepe

- `RULES.md`: kötelező fejlesztési szabályok. A benne leírt kódolási,
  tesztelési, controller és kommunikációs szabályokat minden fejlesztésnél
  be kell tartani.
- `README.md`: projektindítás, Docker szolgáltatások, dev és production
  parancsok.
- `docs/*.md`: későbbi döntési jegyzetek, migrációs tervek és részletes
  architektúra dokumentációk.

## Projektstruktúra

- `frontend/nuxt`: publikus Nuxt 3 frontend (SSR).
- `admin/nuxt`: Nuxt 3 admin felület (SPA, `/admin` baseURL).
- `backend/symfony`: Symfony backend és API.
- `docker/proxy`: nginx belépési pont.
- `mysql/mysql-init`: MySQL inicializálási hookok. Alapból nincs séma vagy
  üzleti adat betöltve.

## Konfliktuskezelés

Ha a dokumentumok, a kód aktuális állapota vagy a felhasználói kérés
ellentmond egymásnak, nem szabad találgatni.

Ilyenkor:

- röviden jelezni kell az ellentmondást,
- konkrét fájlra vagy szabályra kell hivatkozni,
- fejlesztés előtt kérdezni kell,
- csak egyértelmű döntés után szabad módosítani.

A felhasználó aktuális, explicit döntése felülírhat korábbi projektjegyzetet,
de ha ez a `RULES.md` kötelező fejlesztési szabályaival ütközik, azt külön
jelezni kell.

## Kötelező alapelvek

- A fejlesztés és kommunikáció magyarul történjen.
- A publikus Nuxt frontend és a Nuxt admin ne kommunikáljon közvetlenül az
  adatbázissal vagy külső üzleti integrációkkal; ezek a Symfony backendbe
  tartoznak.
- A backend felel az üzleti logikáért, jogosultságkezelésért, validálásért,
  auditálható műveletekért és adatmodellért.
- A deployment modell egy git repositoryból, egy szerveren futtatott Docker
  stackből indul ki.
- Nem építünk előre multi-tenant SaaS struktúrát, amíg erről nincs külön
  döntés.
- Új admin funkció csak akkor készüljön, ha a feladat kifejezetten admin
  működést kér.

## Munkakezdés előtt

Fejlesztés előtt ellenőrizni kell:

- van-e git repository,
- dirty-e a munkakönyvtár,
- melyik alkalmazás érintett: `backend/symfony`, `frontend/nuxt`, `admin/nuxt`
  vagy Docker/config,
- van-e releváns döntési jegyzet a `docs/` könyvtárban,
- dependency vagy Docker érintettség esetén milyen ellenőrzés arányos a
  módosítással.

Meglévő, nem a feladathoz tartozó módosítást nem szabad visszavonni.

## Docker és futtatás

- Teljes Docker stacket csak akkor szabad indítani, ha a feladat ezt
  kifejezetten kéri, vagy a működés ellenőrzéséhez szükséges.
- Alap compose ellenőrzéshez használható: `docker compose config --quiet`.
- Lokális telepítés és indítás belépési pontja: `./install.sh`.
- A lokális dev compose a Dockerfile-ok `dev` targetjeit használja.
- A production compose a Dockerfile-ok `production` targetjeit használja.
- A compose gateway modellben a Symfony PHP-FPM service neve `php`, a Symfony
  nginx service neve `backend`.
- Lokális dev compose-ban a `docker-compose.override.yml` adja hozzá a Mailpit
  service-t.
- Cron vagy Messenger worker konténer jelenleg nincs a backend mellett.

## Fejlesztési sorrend új funkciónál

1. Értelmezd a feladatot a projektkontextus alapján.
2. Azonosítsd az érintett rétegeket: backend, frontend, admin, adatmodell vagy
   infrastruktúra.
3. Kérdezz, ha domain döntés vagy architekturális döntés hiányzik.
4. Készíts szűk, célzott módosítást.
5. Futtass arányos ellenőrzést.
6. A végén jelezd, mit módosítottál, mit ellenőriztél, és mi maradt nyitva.
