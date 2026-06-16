<?php

require_once __DIR__ . '/db.php';

// ---------------------------------------------------------------------------
// Output a JSON response and stop execution
// ---------------------------------------------------------------------------
function respond(int $code, array $body): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($body);
    exit;
}

// ---------------------------------------------------------------------------
// Validate Bearer token and IP whitelist
// ---------------------------------------------------------------------------
function authenticate(): void
{
    // IP whitelist check
    if (!empty(IP_WHITELIST)) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!in_array($ip, IP_WHITELIST, true)) {
            respond(403, ['status' => 'error', 'message' => 'IP not whitelisted', 'your_ip' => $ip]);
        }
    }

    // Bearer token check
    $header = $_SERVER['HTTP_AUTHORIZATION']
           ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
           ?? '';

    if (empty($header)) {
        // Some servers pass it differently
        $header = apache_request_headers()['Authorization'] ?? '';
    }

    if (empty($header)) {
        respond(401, ['status' => 'error', 'message' => 'Missing Authorization token']);
    }

    if (!preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
        respond(401, ['status' => 'error', 'message' => 'Missing Authorization token']);
    }

    $token = trim($m[1]);

    if ($token !== RECEIVER_TOKEN) {
        respond(401, ['status' => 'error', 'message' => 'Unauthorized (Invalid token)']);
    }
}

// ---------------------------------------------------------------------------
// Decode the raw POST body as JSON
// ---------------------------------------------------------------------------
function getBody(): array
{
    $raw = file_get_contents('php://input');

    if (empty($raw)) {
        respond(400, ['status' => 'error', 'message' => 'Invalid Request Format']);
    }

    $data = json_decode($raw, true);

    if (!is_array($data)) {
        respond(400, ['status' => 'error', 'message' => 'Invalid Request Format']);
    }

    // Unwrap {"data": [...]} envelope
    if (isset($data['data']) && is_array($data['data'])) {
        return $data['data'];
    }

    return $data;
}

// ---------------------------------------------------------------------------
// Determine simulated status for a single record index
// ---------------------------------------------------------------------------
function simStatus(int $index): string
{
    $mode = RESPONSE_MODE;

    if ($mode === 'mixed') {
        $cycle = $index % 3;
        return match ($cycle) {
            0 => 'success',
            1 => 'duplicate',
            default => 'failed',
        };
    }

    return match ($mode) {
        'duplicate' => 'duplicate',
        'fail'      => 'failed',
        default     => 'success',
    };
}

// ---------------------------------------------------------------------------
// Store batch + records in MySQL, build and return response body
// matching the real Customs API format exactly
// ---------------------------------------------------------------------------
function receiveBatch(string $type, array $records): array
{
    $ip        = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $authHdr   = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    preg_match('/Bearer\s+(.{1,6})/i', $authHdr, $m);
    $tokenHint = isset($m[1]) ? $m[1] . '...' : '';

    $db = db();

    $stmt = $db->prepare(
        "INSERT INTO receiver_batches (type, count, ip, token_hint) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$type, count($records), $ip, $tokenHint]);
    $batchId = (int) $db->lastInsertId();

    $recInsert = $db->prepare(
        "INSERT INTO receiver_records (batch_id, container_no, payload, sim_status) VALUES (?, ?, ?, ?)"
    );

    $results       = [];
    $successCount  = 0;
    $dupCount      = 0;
    $failedCount   = 0;

    foreach ($records as $i => $record) {
        $simStatus   = simStatus($i);
        $containerNo = $record['container_no'] ?? null;

        $recInsert->execute([$batchId, $containerNo, json_encode($record), $simStatus]);

        switch ($simStatus) {
            case 'duplicate':
                $dupCount++;
                $results[] = [
                    'status'       => 'duplicate',
                    'container_no' => $containerNo,
                ];
                break;

            case 'failed':
                $failedCount++;
                $results[] = [
                    'status'  => 'error',
                    'message' => 'Validation error: required field missing',
                ];
                break;

            default: // success
                $successCount++;
                $results[] = [
                    'status'       => 'success',
                    'insert_id'    => $batchId * 1000 + $i,
                    'container_no' => $containerNo,
                ];
                break;
        }
    }

    return [
        'status'  => 'Success',
        'summary' => [
            'total'     => count($records),
            'success'   => $successCount,
            'duplicate' => $dupCount,
            'failed'    => $failedCount,
        ],
        'results' => $results,
    ];
}
