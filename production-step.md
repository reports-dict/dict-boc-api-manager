# Production Deployment — DICT-BOC API Bridge

## Prerequisites (one-time, before first deployment)

### 1. Install Docker on the Ubuntu server
```bash
sudo apt update && sudo apt install -y ca-certificates curl gnupg
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list
sudo apt update && sudo apt install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
sudo usermod -aG docker $USER   # log out and back in after this
```

### 2. Set up MySQL (skip if already running)
This app expects MySQL in a Docker network named `mysql8_default`.
If MySQL is managed by a separate compose project (`mysql8`), make sure it is running.
To create the network manually if needed:
```bash
docker network create mysql8_default
```
Then create the application database:
```bash
docker exec mysql8-mysql-1 mysql -u root -proot -e "CREATE DATABASE IF NOT EXISTS container_monitoring_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 3. Clone the repository
```bash
git clone <your-repo-url> /opt/dict-boc
cd /opt/dict-boc
```

### 4. Create the `.env` file
```bash
cp .env.example .env
nano .env
```

Set these values in `.env`:
```env
APP_NAME="DICT-BOC API Bridge"
APP_ENV=production
APP_KEY=                        # leave blank — generated in step 6
APP_DEBUG=false
APP_URL=http://<server-ip>:8080

DB_CONNECTION=mysql
DB_HOST=mysql8-mysql-1
DB_PORT=3306
DB_DATABASE=container_monitoring_system
DB_USERNAME=root
DB_PASSWORD=root

DB_SQLSRV_HOST=192.168.11.211  # SQL Server IP
DB_SQLSRV_PORT=1433
DB_SQLSRV_DATABASE=sparcsn4
DB_SQLSRV_USERNAME=tosreports
DB_SQLSRV_PASSWORD=tosreports
```

### 5. Build and start containers
```bash
docker compose build
docker compose up -d
```

### 6. Install dependencies and build frontend
```bash
docker exec dict_boc_app composer install --no-dev --optimize-autoloader
docker exec dict_boc_app npm ci
docker exec dict_boc_app npm run build
```

### 7. Generate app key and run first-time setup
```bash
docker exec dict_boc_app php artisan key:generate
docker exec dict_boc_app php artisan storage:link
docker exec dict_boc_app php artisan migrate --force
docker exec dict_boc_app php artisan db:seed --force
docker exec dict_boc_app php artisan config:cache
docker exec dict_boc_app php artisan route:cache
docker exec dict_boc_app php artisan view:cache
```

### 8. Configure the app
Log in at `http://<server-ip>:8080` using the seeded admin credentials,
then go to **Settings** and fill in:
- API Base URL and token (BOC Customs API endpoint)
- Auto-send schedule time
- Email report recipients and SMTP password

---

## After `git pull origin main`

Run these commands every time you deploy an update:

```bash
# 1. Pull latest code
git pull origin main

# 2. Rebuild images (handles PHP/system-level changes in Dockerfile)
docker compose build

# 3. Restart all containers
docker compose up -d

# 4. Install/update PHP dependencies
docker exec dict_boc_app composer install --no-dev --optimize-autoloader

# 5. Rebuild frontend assets
docker exec dict_boc_app npm ci
docker exec dict_boc_app npm run build

# 6. Run any new database migrations
docker exec dict_boc_app php artisan migrate --force

# 7. Refresh Laravel caches
docker exec dict_boc_app php artisan config:cache
docker exec dict_boc_app php artisan route:cache
docker exec dict_boc_app php artisan view:cache
```

---

## Useful commands

```bash
# Check all running containers
docker ps

# View scheduler logs (auto-send activity)
docker logs dict_boc_scheduler -f

# View app/PHP-FPM logs
docker logs dict_boc_app -f

# View nginx logs
docker logs dict_boc_nginx -f

# Run auto-send manually (all types, yesterday)
docker exec dict_boc_app php artisan cms:send --type=all

# Run auto-send for a specific date range
docker exec dict_boc_app php artisan cms:send --type=receive --from=2026-06-15 --to=2026-06-15

# Clear Laravel caches (if config changes not taking effect)
docker exec dict_boc_app php artisan config:clear
docker exec dict_boc_app php artisan cache:clear

# Stop all containers
docker compose down
```

---

## Container overview

| Container | Role |
|---|---|
| `dict_boc_app` | PHP-FPM — serves the Laravel application |
| `dict_boc_nginx` | Nginx — handles HTTP on port 8080 |
| `dict_boc_scheduler` | Runs `php artisan schedule:work` — fires auto-send at the configured time |
| `mysql8-mysql-1` | MySQL — application database (external compose project) |
