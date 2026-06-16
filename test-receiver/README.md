# DICT-BOC Test Receiver

A standalone PHP + MySQL endpoint simulator for testing the DICT-BOC API Bridge
before connecting to the real Philippine Customs System API.

---

## What It Does

- Accepts POST requests on 4 endpoints matching the Customs API paths
- Validates Bearer token and optional IP whitelist
- Stores every received batch + individual records in MySQL (`receiver_batches`, `receiver_records` tables)
- Returns JSON responses in the exact format expected by `TransmissionService.php`
- Simulates success / duplicate / fail responses so you can test the sender's error handling
- Provides a browser-based admin UI to inspect all received data

---

## Quick Start — Docker (Recommended)

```bash
# Build the image (run from inside the test-receiver/ folder)
docker build -t dict-boc-receiver .

# Run on port 8090
docker run -d -p 8090:80 --name dict-boc-receiver dict-boc-receiver

# Admin UI
open http://localhost:8090
```

To persist the SQLite database across container restarts:

```bash
docker run -d -p 8090:80 \
  -v "$(pwd)/receiver.sqlite:/var/www/html/receiver.sqlite" \
  --name dict-boc-receiver dict-boc-receiver
```

---

## Quick Start — Plain Apache/Nginx

1. Copy this folder to your web server's document root (e.g. `/var/www/html/receiver/`)
2. Ensure PHP 7.4+ is installed with `pdo_sqlite` enabled
3. Set directory write permission so PHP can create `receiver.sqlite`:
   ```bash
   chmod 775 /var/www/html/receiver/
   chown www-data:www-data /var/www/html/receiver/
   ```
4. Visit `http://your-server/receiver/`

---

## Configuration

Edit **`config.php`** before building/deploying:

| Constant | Default | Description |
|----------|---------|-------------|
| `RECEIVER_TOKEN` | `test-token-1234` | Must match the API Token set in DICT-BOC Bridge Settings |
| `RESPONSE_MODE` | `success` | `success` / `duplicate` / `fail` / `mixed` |
| `IP_WHITELIST` | `[]` | Empty = allow all. Add sender IPs to restrict. |
| `DB_PATH` | `./receiver.sqlite` | Path to the SQLite database file |

### Response Modes

| Mode | Behaviour |
|------|-----------|
| `success` | Every record returns `{"status":"success"}` |
| `duplicate` | Every record returns `{"status":"duplicate"}` |
| `fail` | Every record returns `{"status":"failed"}` |
| `mixed` | Cycles: success → duplicate → failed (tests partial-success handling) |

---

## Endpoints

| Type | URL |
|------|-----|
| Discharge | `POST /api/discharge.php` |
| Load | `POST /api/load.php` |
| Release | `POST /api/release.php` |
| Receive | `POST /api/receive.php` |

### Request Format

```
POST /api/discharge.php
Authorization: Bearer test-token-1234
Content-Type: application/json

[
  { "container_no": "ABCD1234567", ... },
  { "container_no": "EFGH7654321", ... }
]
```

### Response Format

```json
{
  "status": "success",
  "results": [
    { "status": "success",   "message": "Record received" },
    { "status": "duplicate", "message": "Record already exists" }
  ]
}
```

### Error Responses

| Scenario | HTTP | Body |
|----------|------|------|
| Missing token | 401 | `{"status":"error","message":"Missing Token"}` |
| Invalid token | 401 | `{"status":"error","message":"Invalid Token"}` |
| Invalid JSON | 400 | `{"status":"error","message":"Invalid Request Format"}` |
| IP blocked | 403 | `{"status":"error","message":"IP NOT WHITELISTED"}` |

---

## Integration with DICT-BOC API Bridge

1. Start the receiver on `http://<receiver-host>:8090`
2. In the Bridge Settings page, set each endpoint:
   - `http://<receiver-host>:8090/api/discharge.php`
   - `http://<receiver-host>:8090/api/load.php`
   - `http://<receiver-host>:8090/api/release.php`
   - `http://<receiver-host>:8090/api/receive.php`
3. Set the API Token in Bridge Settings to the value in `RECEIVER_TOKEN` (default: `test-token-1234`)
4. Trigger a manual send from the Bridge
5. Check `http://<receiver-host>:8090` to confirm data arrived

---

## Testing Scenarios

| Test | How |
|------|-----|
| Happy path | `RESPONSE_MODE = 'success'`, send → Bridge shows all success |
| Duplicate handling | `RESPONSE_MODE = 'duplicate'`, send → Bridge shows duplicate_count > 0 |
| Failure handling | `RESPONSE_MODE = 'fail'`, send → Bridge shows failed transmission |
| Partial success | `RESPONSE_MODE = 'mixed'`, send → Bridge shows mixed per-record statuses |
| Bad token | Change Bridge API token to wrong value → Bridge logs 401 in activity log |
| IP restriction | Add Bridge server IP to `IP_WHITELIST`, test from different IP → 403 |

---

## Admin UI Features

- **Stats row** — total records received per type
- **Filter** — view batches by type (All / Discharge / Load / Release / Receive)
- **Batch table** — ID, type, record count, sender IP, token hint, timestamp
- **Record detail** — expand each batch to see individual container records with simulated status
- **Payload view** — click [show JSON] to inspect the raw JSON payload for any record
- **Delete** — remove individual batches or clear all data

---

## File Structure

```
test-receiver/
├── config.php          ← Edit this first
├── inc/
│   ├── db.php          ← SQLite connection + schema bootstrap
│   └── api.php         ← Auth, body parsing, batch storage, response building
├── api/
│   ├── discharge.php   ← POST endpoint
│   ├── load.php        ← POST endpoint
│   ├── release.php     ← POST endpoint
│   └── receive.php     ← POST endpoint
├── index.php           ← Admin UI
├── Dockerfile          ← php:8.2-apache, port 80
├── .htaccess           ← Blocks direct access to sqlite/config
└── README.md
```

The SQLite file `receiver.sqlite` is auto-created on the first incoming request.
