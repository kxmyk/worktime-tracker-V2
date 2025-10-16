# Worktime Tracker

## Wymagania
- PHP 8.4+
- MySQL
- Composer
- Symfony CLI

## Instalacja

1. **Sklonuj repozytorium:**
   ```sh
   git clone https://github.com/kxmyk/worktime-tracker-V2.git
   cd worktime-tracker-V2
   ```

2. **Zmień plik `.env` i ustaw połączenie do bazy danych:**
   ```env
   DATABASE_URL="mysql://root:@127.0.0.1:3306/worktime_tracker_db?serverVersion=8.0.32&charset=utf8mb4"
   ```

3. **Zainstaluj zależności:**
   ```sh
   composer install
   ```

4. **Utwórz bazę danych i wykonaj migracje:**
   ```sh
   symfony console doctrine:database:create
   symfony console doctrine:migrations:migrate
   ```

5. **Uruchom aplikację:**
   ```sh
   symfony serve
   ```

## Testowanie API

## Import kolekcji Postman
Możesz zaimportować poniższą kolekcję do Postmana:

````worktime-tracker.postman_collection````

## Testowanie jednostkowe

Konfiguracja środowiska testowego

Przed uruchomieniem testów skopiuj plik `.env.test.example` do `.env.test`:

```sh
cp .env.test.example .env.test
```

Aby uruchomić testy:
```sh
vendor/bin/phpunit --testdox
```