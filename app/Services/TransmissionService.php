<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Transmission;
use App\Models\TransmissionRecord;
use App\Models\User;
use Carbon\Carbon;
use Throwable;

class TransmissionService
{
    public function __construct(
        private DataFetchService $fetcher,
        private DataTransformService $transformer,
        private ApiSenderService $sender,
    ) {}

    public function run(
        string $type,
        Carbon $from,
        Carbon $to,
        ?User $user = null,
        string $triggeredBy = 'manual',
        array $containers = []
    ): Transmission {
        $transmission = Transmission::create([
            'type'         => $type,
            'status'       => 'pending',
            'date_from'    => $from->toDateString(),
            'date_to'      => $to->toDateString(),
            'triggered_by' => $triggeredBy,
            'sent_by'      => $user?->id,
        ]);

        $this->log('info', "Starting {$type} transmission #{$transmission->id}", [
            'transmission_id' => $transmission->id,
            'date_from'       => $from->toDateString(),
            'date_to'         => $to->toDateString(),
        ]);

        try {
            $rows      = $this->fetcher->fetch($type, $from, $to, $containers);
            $payload   = $this->transformer->transform($type, $rows);
            $total     = count($payload);

            $transmission->update(['records_count' => $total]);

            if ($total === 0) {
                $transmission->update(['status' => 'success']);
                $this->log('info', "No records found for {$type} #{$transmission->id}");
                return $transmission;
            }

            $result = $this->sender->send($type, $payload);

            $successCount   = 0;
            $failedCount    = 0;
            $duplicateCount = 0;

            $responseBody = $result['body'];
            $overallStatus = 'failed';

            if ($result['success']) {
                if (!is_array($responseBody)) {
                    // HTTP 2xx but non-JSON body — API returned an error page
                    $failedCount   = $total;
                    $overallStatus = 'failed';
                    $this->log('error', "API returned non-JSON response for {$type} #{$transmission->id}", [
                        'transmission_id' => $transmission->id,
                        'http_code'       => $result['http_code'],
                        'body_preview'    => substr((string) $responseBody, 0, 500),
                    ]);
                } elseif (empty($responseBody['results'] ?? [])) {
                    $successCount  = $total;
                    $overallStatus = 'success';
                    foreach ($payload as $item) {
                        TransmissionRecord::create([
                            'transmission_id'  => $transmission->id,
                            'container_no'     => $item['container_no'],
                            'payload'          => $item,
                            'status'           => 'success',
                            'response_message' => null,
                        ]);
                    }
                } else {
                    $results = $responseBody['results'];
                    // Index results by container_no when available; fall back to positional for error entries
                    $byContainer   = [];
                    $unkeyed       = [];
                    foreach ($results as $res) {
                        if (!empty($res['container_no'])) {
                            $byContainer[$res['container_no']] = $res;
                        } else {
                            $unkeyed[] = $res;
                        }
                    }
                    $unkeyedCursor = 0;

                    foreach ($payload as $item) {
                        $containerNo  = $item['container_no'] ?? '';
                        if (isset($byContainer[$containerNo])) {
                            $recordResult = $byContainer[$containerNo];
                        } elseif (isset($unkeyed[$unkeyedCursor])) {
                            $recordResult = $unkeyed[$unkeyedCursor++];
                        } else {
                            $recordResult = ['status' => 'success'];
                        }

                        $status = $this->resolveRecordStatus($recordResult);

                        match ($status) {
                            'success'   => $successCount++,
                            'duplicate' => $duplicateCount++,
                            default     => $failedCount++,
                        };

                        TransmissionRecord::create([
                            'transmission_id'  => $transmission->id,
                            'container_no'     => $containerNo,
                            'payload'          => $item,
                            'status'           => $status,
                            'response_message' => $recordResult['message'] ?? null,
                        ]);
                    }

                    $overallStatus = $failedCount === 0 ? 'success' : ($successCount > 0 ? 'partial' : 'failed');
                }
            } else {
                $failedCount = $total;
                $this->log('error', "API call failed for {$type} #{$transmission->id}: {$result['message']}", [
                    'transmission_id' => $transmission->id,
                    'http_code'       => $result['http_code'],
                    'response'        => $responseBody,
                ]);
            }

            $transmission->update([
                'status'          => $overallStatus,
                'success_count'   => $successCount,
                'failed_count'    => $failedCount,
                'duplicate_count' => $duplicateCount,
                'response_summary' => $responseBody,
            ]);

            $this->log('info', "Completed {$type} transmission #{$transmission->id}: {$overallStatus}", [
                'transmission_id' => $transmission->id,
                'total'           => $total,
                'success'         => $successCount,
                'failed'          => $failedCount,
                'duplicates'      => $duplicateCount,
            ]);
        } catch (Throwable $e) {
            $transmission->update(['status' => 'failed']);
            $this->log('error', "Exception in {$type} transmission #{$transmission->id}: {$e->getMessage()}", [
                'transmission_id' => $transmission->id,
                'trace'           => $e->getTraceAsString(),
            ]);
        }

        return $transmission->fresh();
    }

    private function resolveRecordStatus(array|string $result): string
    {
        $lower = strtolower(is_string($result) ? $result : ($result['status'] ?? ''));

        if (str_contains($lower, 'duplicate')) return 'duplicate';
        if (str_contains($lower, 'success') || str_contains($lower, 'ok')) return 'success';
        // 'error' from the real API maps to our internal 'failed'
        return 'failed';
    }

    private function log(string $level, string $message, array $context = []): void
    {
        ActivityLog::create([
            'level'   => $level,
            'message' => $message,
            'context' => $context ?: null,
        ]);
    }
}
