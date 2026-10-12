# Fejlesztési szabályok

## 1. Általános kódolási szabályok

### 1.1 PHP fájl hossz limit

- Maximum 400 sor fájlonként, kivéve Doctrine entity-k.
- Ha egy service vagy controller eléri a 400 sort, refaktorálás szükséges.

### 1.2 PHP sorhossz limit

- PHP fájlokban egy sor sem lehet hosszabb 120 karakternél.
- Hosszabb kifejezést sortöréssel és olvasható behúzással kell tagolni.

### 1.3 Vue/Nuxt komponens hossz limit

- Maximum 300-400 sor komponensenként.
- Ha meghaladná, child komponensekre kell bontani.
- Vonatkozik az adminra és a publikus frontendre is.

### 1.4 Import szabály PHP-ban

- Ne használj inline fully-qualified class neveket.
- Mindig `use` statement kerüljön a fájl elejére.

```php
// Rossz
$date = new \DateTime();
throw new \InvalidArgumentException('...');

// Jo
use DateTime;
use InvalidArgumentException;

$date = new DateTime();
throw new InvalidArgumentException('...');
```

### 1.5 Dátum mezők az adatbázisban

- Minden dátum típusú mező Unix timestamp `INTEGER` formátumban legyen.
- Vonatkozik Doctrine entity-kre, migrationökre, API response-okra és
  konfigurációs értékekre.
- ISO string dátumformátum nem megengedett adatbázis szinten.

## 2. Tesztelési szabályok

### 2.1 Teszt létrehozás kötelezettség

- Minden új Symfony entity vagy jelentősebb funkcionalitás létrehozása után
  kötelező rákérdezni, hogy kell-e hozzá teszt.
- Ha igen:
  - API tesztek: `tests/Api/`
  - Unit tesztek: `tests/Entity/`, `tests/Service/`, `tests/Repository/`
  - Legalább a kritikus üzleti logikát fedje le.
  - Minden teszt legyen önálló és független.

### 2.2 Symfony PHPUnit best practice-ek

- Ne hívd a `static::createClient()`-et `setUp()`-ban.
- Új entity persist után azonnal `flush()` következzen, ha query-ben használod.
- Tesztadat legyen egyedi.
- `assertArrayHasKey()` mindig két paramétert kapjon.

## 3. Architektúra

### 3.1 Controller szabály

- A Symfony controller kizárólag a request továbbítását, service meghívását és
  a response visszaadását végezheti.
- Controllerben üzleti logika, validáció, normalizálás, adatlekérdezés és
  response-adat összeállítás nem lehet.
- Controllerben private metódusok nem lehetnek.
- A közös vagy összetettebb működés service-be, mapperbe vagy helperbe
  kerüljön.

### 3.2 Adatbázis indexelés és query optimalizálás

- Minden új MySQL táblánál és jelentősebb új lekérdezésnél meg kell tervezni a
  szükséges indexeket.
- Az indexeket a tényleges query minták alapján kell kialakítani.
- Kerülni kell az N+1 query problémát.
- Nagy listáknál kötelező pagination vagy explicit limit.
- Csak a szükséges mezőket és kapcsolatokat szabad lekérni.
- Backend lekérdezéseknél a Doctrine ORM/repository alapértelmezett megoldás.
- Natív SQL/DBAL query csak indokolt kivételként használható.
- Natív SQL/DBAL esetén kötelező a paraméterkötés.

### 3.3 Logikai fájlszervezés

- Ha egy entityhez, API végponthoz, művelethez vagy frontend funkcióhoz több
  összetartozó fájl tartozik, akkor ezeket jól elnevezett domain vagy feature
  mappába kell szervezni.
- Backend példák: `src/Api/Page/`, `src/Application/Content/`,
  `src/Domain/Menu/`.
- Frontend példák: `components/content/`, `composables/api/`,
  `middleware/auth/`.
- Kerülni kell a lapos, mindent gyűjtő mappákat.

### 3.4 Kódismétlés kerülése

- Nem lehet ugyanazt a működést több helyen duplikálni.
- Új megoldás előtt ellenőrizni kell, van-e már meglévő service, mapper,
  helper, composable, middleware, query vagy komponens, amely újrahasználható.
- Új absztrakciót csak akkor kell létrehozni, ha tényleges duplikációt,
  bonyolultságot vagy felelősségi keveredést szüntet meg.

## 4. Kommunikáció

- A fejlesztés során minden kommunikáció magyar nyelven történik: a válaszok,
  a köztes állapotjelentések, a kérdések és az összefoglalók is.
- Akkor is magyarul kell válaszolni, ha a kérdés, a kód vagy egy eszköz
  kimenete más nyelvű.
