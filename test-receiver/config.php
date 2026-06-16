<?php

// ── MySQL connection (same server as DICT-BOC API Bridge) ─────────────────
define('DB_HOST',     'mysql8-mysql-1');
define('DB_PORT',     '3306');
define('DB_NAME',     'container_monitoring_system');
define('DB_USER',     'root');
define('DB_PASS',     'root');

// ── API security ──────────────────────────────────────────────────────────
// Must match the API Token set in DICT-BOC API Bridge → Settings
define('RECEIVER_TOKEN', 'test-token-1234');

// ── Response simulation ───────────────────────────────────────────────────
// 'success'   — all records accepted
// 'duplicate' — all records returned as duplicate
// 'fail'      — all records returned as failed
// 'mixed'     — cycles: success / duplicate / failed per record
define('RESPONSE_MODE', 'success');

// ── IP whitelist ──────────────────────────────────────────────────────────
// Empty array = allow all IPs. Example: ['192.168.1.50']
define('IP_WHITELIST', []);
