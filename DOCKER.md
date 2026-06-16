# Docker Commands — DICT-BOC API Bridge

---

## Prerequisites

- Docker Desktop running
- The external MySQL container (`mysql8-mysql-1`) must be up before starting the sender app

```bash
# Verify the MySQL container is running
docker ps --filter "name=mysql8-mysql-1"
```

---

## 1 — DICT-BOC API Bridge (Sender)

The sender app uses `docker-compose` and runs on **http://localhost:8080**.

### Build & Start

```bash
# From the project root
docker-compose up -d --build
```

### First-Time Setup (run once after first build)

```bash
# Run database migrations
docker exec dict_boc_app php artisan migrate --force

# Seed the database (creates admin user + default settings)
docker exec dict_boc_app php artisan db:seed --force

# Generate app key (if APP_KEY is empty in .env)
docker exec dict_boc_app php artisan key:generate
```

### Default Login

| Field | Value |
|-------|-------|
| Username | `admin` |
| Password | `Admin@1234` |

### Start / Stop / Restart

```bash
docker-compose start        # start existing containers
docker-compose stop         # stop without removing
docker-compose restart      # restart both services
docker-compose down         # stop and remove containers (data in MySQL is preserved)
docker-compose down --rmi all  # also remove built images (forces full rebuild next time)
```

### View Logs

```bash
docker-compose logs -f              # both services
docker-compose logs -f app          # PHP-FPM only
docker-compose logs -f nginx        # Nginx only
```

### Shell Access

```bash
docker exec -it dict_boc_app bash
```

### Rebuild After Code Changes

```bash
docker-compose up -d --build
```

> Frontend asset changes (JS/CSS) do **not** require a rebuild — Vite hot-reload runs on the host.
> Run `npm run build` on the host and the container picks up the compiled files automatically.

---

## 2 — Test Receiver

The test receiver is a standalone container that simulates the Customs API.
It runs on **http://localhost:8090** and shares the same MySQL database.

### Build

```bash
# From the project root
cd test-receiver
docker build -t dict-boc-receiver .
```

### Start

```powershell
docker run -d -p 8090:80 --network mysql8_default --name dict-boc-receiver dict-boc-receiver
docker run -d -p 8090:80 --network mysql8_default --name dict-boc-receiver dict-boc-receiver

```

> `--network mysql8_default` lets the receiver reach `mysql8-mysql-1` on the same Docker network as the sender.

### Stop / Remove

```bash
docker stop dict-boc-receiver
docker rm dict-boc-receiver
```

### Rebuild After Config Changes

```bash
docker stop dict-boc-receiver && docker rm dict-boc-receiver
docker build -t dict-boc-receiver ./test-receiver
docker run -d -p 8090:80 --network mysql8_default --name dict-boc-receiver dict-boc-receiver
```

### View Logs

```bash
docker logs -f dict-boc-receiver
```

---

## 3 — Integration: Point the Sender at the Receiver

1. Open the Bridge at **http://localhost:8080** → log in → go to **Settings**
2. Set **API Token** to `test-token-1234` (or whatever is in `test-receiver/config.php`)
3. Set each endpoint URL:

| Type | URL |
|------|-----|
| Discharge | `http://dict-boc-receiver/api/discharge.php` |
| Load | `http://dict-boc-receiver/api/load.php` |
| Release | `http://dict-boc-receiver/api/release.php` |
| Receive | `http://dict-boc-receiver/api/receive.php` |

> Use the container name `dict-boc-receiver` (not `localhost`) so that the sender container resolves it over the Docker network.

4. Go to **Send Data** → select types → click **Send**
5. Check **http://localhost:8090** to see the received batches

---

## 4 — Changing the Receiver Response Mode

Edit `test-receiver/config.php`:

```php
define('RESPONSE_MODE', 'success');    // all records succeed
define('RESPONSE_MODE', 'duplicate');  // all records come back as duplicate
define('RESPONSE_MODE', 'fail');       // all records fail
define('RESPONSE_MODE', 'mixed');      // cycles: success → duplicate → failed
```

Then rebuild the receiver:

```bash
docker stop dict-boc-receiver && docker rm dict-boc-receiver
docker build -t dict-boc-receiver ./test-receiver
docker run -d -p 8090:80 --network mysql8_default --name dict-boc-receiver dict-boc-receiver
```

---

## 5 — Quick Reference

| Task | Command |
|------|---------|
| Start everything | `docker-compose up -d --build` then receiver run command |
| Sender app URL | http://localhost:8080 |
| Receiver admin UI | http://localhost:8090 |
| Sender shell | `docker exec -it dict_boc_app bash` |
| Sender logs | `docker-compose logs -f` |
| Receiver logs | `docker logs -f dict-boc-receiver` |
| Run artisan command | `docker exec dict_boc_app php artisan <command>` |
| Clear Laravel cache | `docker exec dict_boc_app php artisan optimize:clear` |
