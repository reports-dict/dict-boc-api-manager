<?php
require_once __DIR__ . '/inc/db.php';

// ── Actions ────────────────────────────────────────────────────────────────
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'clear') {
        db()->exec("DELETE FROM receiver_records");
        db()->exec("DELETE FROM receiver_batches");
        header('Location: index.php');
        exit;
    }
    if ($_GET['action'] === 'delete' && isset($_GET['id'])) {
        $id = (int) $_GET['id'];
        db()->prepare("DELETE FROM receiver_records WHERE batch_id = ?")->execute([$id]);
        db()->prepare("DELETE FROM receiver_batches WHERE id = ?")->execute([$id]);
        header('Location: index.php');
        exit;
    }
}

// ── Data ───────────────────────────────────────────────────────────────────
$stats = db()->query("
    SELECT type, COUNT(*) AS batches, COALESCE(SUM(count),0) AS total_records
    FROM receiver_batches GROUP BY type
")->fetchAll();

$statsByType = [];
foreach ($stats as $s) {
    $statsByType[$s['type']] = $s;
}

$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;
$filter  = $_GET['type'] ?? '';

$where   = $filter ? "WHERE type = " . db()->quote($filter) : '';
$total   = (int) db()->query("SELECT COUNT(*) FROM receiver_batches $where")->fetchColumn();
$pages   = max(1, (int) ceil($total / $perPage));

$batches = db()->query("
    SELECT * FROM receiver_batches $where
    ORDER BY id DESC
    LIMIT $perPage OFFSET $offset
")->fetchAll();

$tokenHint = defined('RECEIVER_TOKEN') ? substr(RECEIVER_TOKEN, 0, 4) . '****' : '—';
$mode      = defined('RESPONSE_MODE')  ? RESPONSE_MODE : '—';
$whitelist = defined('IP_WHITELIST') && !empty(IP_WHITELIST)
    ? implode(', ', IP_WHITELIST)
    : 'All IPs allowed';

$types = ['discharge', 'load', 'release', 'receive'];
$modeColors = [
    'success'   => '#16a34a',
    'duplicate' => '#ca8a04',
    'fail'      => '#dc2626',
    'mixed'     => '#7c3aed',
];
$modeColor = $modeColors[$mode] ?? '#6b7280';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DICT-BOC Test Receiver</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: system-ui, -apple-system, sans-serif; font-size: 13px;
         background: #0f172a; color: #e2e8f0; min-height: 100vh; }

  /* Header */
  .header { background: #1e293b; border-bottom: 1px solid #334155;
            padding: 14px 24px; display: flex; align-items: center; gap: 16px; }
  .header h1 { font-size: 15px; font-weight: 600; color: #f8fafc; }
  .header .sub { font-size: 11px; color: #94a3b8; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 9999px;
           font-size: 11px; font-weight: 600; letter-spacing: .3px; }

  /* Layout */
  .main { max-width: 1200px; margin: 0 auto; padding: 20px 24px; }

  /* Config bar */
  .config-bar { background: #1e293b; border: 1px solid #334155; border-radius: 8px;
                padding: 12px 16px; display: flex; gap: 24px; flex-wrap: wrap;
                margin-bottom: 20px; align-items: center; }
  .config-bar .item { display: flex; flex-direction: column; gap: 2px; }
  .config-bar .label { font-size: 10px; text-transform: uppercase; letter-spacing: .5px;
                       color: #64748b; }
  .config-bar .value { font-size: 12px; font-weight: 500; color: #e2e8f0; }

  /* Stats */
  .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;
           margin-bottom: 20px; }
  .stat-card { background: #1e293b; border: 1px solid #334155; border-radius: 8px;
               padding: 14px 16px; }
  .stat-card .type { font-size: 10px; text-transform: uppercase; letter-spacing: .5px;
                     color: #64748b; margin-bottom: 6px; }
  .stat-card .num { font-size: 22px; font-weight: 700; color: #38bdf8; }
  .stat-card .sub { font-size: 11px; color: #64748b; margin-top: 2px; }

  /* Toolbar */
  .toolbar { display: flex; gap: 8px; align-items: center; margin-bottom: 12px;
             flex-wrap: wrap; }
  .toolbar a { padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 500;
               text-decoration: none; border: 1px solid #334155; color: #cbd5e1;
               background: #1e293b; transition: background .15s; }
  .toolbar a:hover { background: #334155; }
  .toolbar a.active { background: #0ea5e9; border-color: #0ea5e9; color: #fff; }
  .toolbar .spacer { flex: 1; }
  .toolbar .clear-btn { background: #7f1d1d; border-color: #991b1b; color: #fca5a5; }
  .toolbar .clear-btn:hover { background: #991b1b; }

  /* Table */
  .table-wrap { background: #1e293b; border: 1px solid #334155; border-radius: 8px;
                overflow: hidden; }
  table { width: 100%; border-collapse: collapse; }
  th { background: #0f172a; color: #94a3b8; font-size: 11px; text-transform: uppercase;
       letter-spacing: .4px; padding: 10px 14px; text-align: left;
       border-bottom: 1px solid #334155; }
  td { padding: 10px 14px; border-bottom: 1px solid #1e293b; vertical-align: top; }
  tr:last-child td { border-bottom: none; }
  tr:hover td { background: #243044; }
  .mono { font-family: ui-monospace, monospace; font-size: 11px; }
  .type-pill { display: inline-block; padding: 2px 8px; border-radius: 4px;
               font-size: 11px; font-weight: 600; }
  .type-discharge { background: #0c4a6e; color: #7dd3fc; }
  .type-load      { background: #14532d; color: #86efac; }
  .type-release   { background: #451a03; color: #fdba74; }
  .type-receive   { background: #3b0764; color: #d8b4fe; }

  /* Actions */
  .act-link { color: #38bdf8; text-decoration: none; font-size: 11px; }
  .act-link:hover { text-decoration: underline; }
  .act-del  { color: #f87171; }

  /* Expanded records */
  details > summary { cursor: pointer; color: #38bdf8; font-size: 12px;
                      list-style: none; }
  details > summary::-webkit-details-marker { display: none; }
  details > summary::before { content: '▶ '; font-size: 9px; }
  details[open] > summary::before { content: '▼ '; }

  .records-table { margin-top: 8px; width: 100%; border-collapse: collapse;
                   font-size: 12px; }
  .records-table th { background: #0f172a; color: #64748b; font-size: 10px;
                      padding: 6px 10px; text-align: left; }
  .records-table td { padding: 6px 10px; border-top: 1px solid #334155;
                      vertical-align: top; }
  .status-success   { color: #4ade80; font-weight: 600; }
  .status-duplicate { color: #facc15; font-weight: 600; }
  .status-failed    { color: #f87171; font-weight: 600; }

  .payload-toggle { cursor: pointer; color: #94a3b8; font-size: 11px; }
  .payload-box { background: #0f172a; border: 1px solid #334155; border-radius: 4px;
                 padding: 8px; margin-top: 4px; font-family: monospace; font-size: 11px;
                 color: #94a3b8; white-space: pre-wrap; word-break: break-all;
                 max-height: 200px; overflow-y: auto; display: none; }

  /* Pagination */
  .pagination { display: flex; gap: 6px; margin-top: 16px; justify-content: center; }
  .pagination a { padding: 5px 10px; border-radius: 5px; font-size: 12px;
                  text-decoration: none; border: 1px solid #334155;
                  color: #cbd5e1; background: #1e293b; }
  .pagination a.active { background: #0ea5e9; border-color: #0ea5e9; color: #fff; }
  .pagination a:hover:not(.active) { background: #334155; }

  /* Empty state */
  .empty { text-align: center; padding: 48px; color: #475569; }
  .empty .icon { font-size: 36px; margin-bottom: 12px; }
</style>
</head>
<body>

<div class="header">
  <div>
    <h1>DICT-BOC Test Receiver</h1>
    <div class="sub">Standalone endpoint simulator — records transmissions from DICT-BOC API Bridge</div>
  </div>
  <div style="margin-left:auto">
    <span class="badge" style="background:<?= htmlspecialchars($modeColor) ?>22;
          color:<?= htmlspecialchars($modeColor) ?>;
          border:1px solid <?= htmlspecialchars($modeColor) ?>44">
      MODE: <?= strtoupper(htmlspecialchars($mode)) ?>
    </span>
  </div>
</div>

<div class="main">

  <!-- Config summary -->
  <div class="config-bar">
    <div class="item"><div class="label">Response Mode</div>
      <div class="value" style="color:<?= htmlspecialchars($modeColor) ?>">
        <?= htmlspecialchars($mode) ?>
      </div>
    </div>
    <div class="item"><div class="label">Token (hint)</div>
      <div class="value mono"><?= htmlspecialchars($tokenHint) ?></div></div>
    <div class="item"><div class="label">IP Whitelist</div>
      <div class="value"><?= htmlspecialchars($whitelist) ?></div></div>
    <div class="item"><div class="label">DB</div>
      <div class="value mono" title="<?= htmlspecialchars(DB_HOST) ?>:<?= htmlspecialchars(DB_PORT) ?>">
        <?= htmlspecialchars(DB_NAME) ?> @ <?= htmlspecialchars(DB_HOST) ?>
      </div>
    </div>
    <div style="margin-left:auto;font-size:11px;color:#475569">
      Edit <code style="color:#94a3b8">config.php</code> to change settings
    </div>
  </div>

  <!-- Stats -->
  <div class="stats">
    <?php foreach ($types as $t): $s = $statsByType[$t] ?? null; ?>
    <div class="stat-card">
      <div class="type"><?= $t ?></div>
      <div class="num"><?= $s ? number_format($s['total_records']) : '0' ?></div>
      <div class="sub"><?= $s ? number_format($s['batches']) . ' batch' . ($s['batches'] != 1 ? 'es' : '') : 'no data' ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Toolbar -->
  <div class="toolbar">
    <a href="index.php" class="<?= $filter === '' ? 'active' : '' ?>">All</a>
    <?php foreach ($types as $t): ?>
    <a href="index.php?type=<?= $t ?>" class="<?= $filter === $t ? 'active' : '' ?>">
      <?= ucfirst($t) ?>
    </a>
    <?php endforeach; ?>
    <div class="spacer"></div>
    <span style="color:#64748b;font-size:11px"><?= number_format($total) ?> batch<?= $total != 1 ? 'es' : '' ?></span>
    <a href="index.php?action=clear"
       class="clear-btn"
       onclick="return confirm('Delete ALL batches and records? This cannot be undone.')">
      Clear All
    </a>
  </div>

  <!-- Batches table -->
  <div class="table-wrap">
    <?php if (empty($batches)): ?>
    <div class="empty">
      <div class="icon">📭</div>
      <div>No transmissions received yet.</div>
      <div style="margin-top:6px;font-size:12px">
        Point the DICT-BOC API Bridge endpoints at this receiver and trigger a send.
      </div>
    </div>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Type</th>
          <th>Records</th>
          <th>Sender IP</th>
          <th>Token</th>
          <th>Received At</th>
          <th>Detail</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($batches as $batch):
            $recs = db()->prepare("SELECT * FROM receiver_records WHERE batch_id = ? ORDER BY id");
            $recs->execute([$batch['id']]);
            $records = $recs->fetchAll();
        ?>
        <tr>
          <td class="mono"><?= $batch['id'] ?></td>
          <td>
            <span class="type-pill type-<?= htmlspecialchars($batch['type']) ?>">
              <?= htmlspecialchars($batch['type']) ?>
            </span>
          </td>
          <td><?= number_format($batch['count']) ?></td>
          <td class="mono"><?= htmlspecialchars($batch['ip'] ?? '—') ?></td>
          <td class="mono"><?= htmlspecialchars($batch['token_hint'] ?? '—') ?></td>
          <td class="mono"><?= htmlspecialchars($batch['received_at']) ?></td>
          <td>
            <?php if ($records): ?>
            <details>
              <summary><?= count($records) ?> record<?= count($records) != 1 ? 's' : '' ?></summary>
              <table class="records-table">
                <thead>
                  <tr><th>#</th><th>Container No</th><th>Sim Status</th><th>Payload</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($records as $ri => $rec): ?>
                  <tr>
                    <td class="mono"><?= $rec['id'] ?></td>
                    <td class="mono"><?= htmlspecialchars($rec['container_no'] ?? '—') ?></td>
                    <td>
                      <span class="status-<?= htmlspecialchars($rec['sim_status']) ?>">
                        <?= htmlspecialchars($rec['sim_status']) ?>
                      </span>
                    </td>
                    <td>
                      <span class="payload-toggle"
                            onclick="var b=this.nextElementSibling;b.style.display=b.style.display==='block'?'none':'block'">
                        [show JSON]
                      </span>
                      <div class="payload-box"><?= htmlspecialchars(
                          json_encode(json_decode($rec['payload']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                      ) ?></div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </details>
            <?php else: ?>
            <span style="color:#475569">—</span>
            <?php endif; ?>
          </td>
          <td>
            <a class="act-link act-del"
               href="index.php?action=delete&id=<?= $batch['id'] ?>"
               onclick="return confirm('Delete this batch?')">delete</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <!-- Pagination -->
  <?php if ($pages > 1): ?>
  <div class="pagination">
    <?php for ($p = 1; $p <= $pages; $p++): ?>
    <a href="?page=<?= $p ?><?= $filter ? '&type=' . urlencode($filter) : '' ?>"
       class="<?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>

  <div style="text-align:center;margin-top:24px;color:#334155;font-size:11px">
    DICT-BOC Test Receiver &bull; Pure PHP + SQLite &bull;
    Endpoints: /api/discharge.php &bull; /api/load.php &bull; /api/release.php &bull; /api/receive.php
  </div>

</div>
</body>
</html>
