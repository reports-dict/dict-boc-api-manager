<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\Transmission;
use Illuminate\Support\Facades\Crypt;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use Throwable;

class EmailReportService
{
    public function send(array $transmissions, string $triggeredBy = 'manual'): void
    {
        $recipients = $this->parseRecipients(Setting::get('email_report_recipients', ''));
        if (empty($recipients)) {
            return;
        }

        $encryptedPassword = Setting::get('email_smtp_password', '');
        if (empty($encryptedPassword)) {
            $this->log('warning', 'Email report skipped: SMTP password not configured.');
            return;
        }

        try {
            $password = Crypt::decryptString($encryptedPassword);
        } catch (Throwable) {
            $password = $encryptedPassword;
        }

        /** @var Transmission $first */
        $first     = $transmissions[0] ?? null;
        $dateFrom  = $first?->date_from->format('Y-m-d') ?? now()->toDateString();
        $dateTo    = $first?->date_to->format('Y-m-d') ?? now()->toDateString();
        $dateRange = $dateFrom === $dateTo ? $dateFrom : "{$dateFrom} to {$dateTo}";
        $label     = $triggeredBy === 'auto' ? 'Auto-Send' : 'Manual';
        $subject   = sprintf('[DICT-BOC] Transmission Report - %s (%s)', $dateFrom, $label);

        $html = $this->buildHtml($transmissions, $dateRange, $label);

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.office365.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'reports-dict@anflocor.com';
            $mail->Password   = $password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('reports-dict@anflocor.com', 'DICT-BOC API Manager');
            foreach ($recipients as $address) {
                $mail->addAddress($address);
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html;
            $mail->AltBody = $this->buildPlainText($transmissions, $dateRange, $label);

            $mail->send();

            $this->log('info', "Email report sent to " . implode(', ', $recipients), [
                'triggered_by' => $triggeredBy,
                'date_range'   => $dateRange,
            ]);
        } catch (Throwable $e) {
            $this->log('error', 'Email report failed: ' . $e->getMessage(), [
                'triggered_by' => $triggeredBy,
            ]);
        }
    }

    private function parseRecipients(string $raw): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/[\s,]+/', $raw)),
            fn($e) => str_contains($e, '@')
        ));
    }

    private function buildHtml(array $transmissions, string $dateRange, string $label): string
    {
        $rows = '';
        $totalRecords = $totalSuccess = $totalDuplicate = $totalFailed = 0;

        foreach ($transmissions as $t) {
            $status  = $t->status ?? 'unknown';
            $color   = match ($status) {
                'success'  => '#16a34a',
                'partial'  => '#d97706',
                'failed'   => '#dc2626',
                default    => '#6b7280',
            };
            $records   = (int) ($t->records_count ?? 0);
            $success   = (int) ($t->success_count ?? 0);
            $duplicate = (int) ($t->duplicate_count ?? 0);
            $failed    = (int) ($t->failed_count ?? 0);

            $totalRecords   += $records;
            $totalSuccess   += $success;
            $totalDuplicate += $duplicate;
            $totalFailed    += $failed;

            $rows .= sprintf('
                <tr>
                    <td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;text-transform:capitalize;">%s</td>
                    <td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;">
                        <span style="color:%s;font-weight:600;">%s</span>
                    </td>
                    <td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;text-align:center;">%d</td>
                    <td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;text-align:center;color:#16a34a;">%d</td>
                    <td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;text-align:center;color:#d97706;">%d</td>
                    <td style="padding:8px 12px;border-bottom:1px solid #e5e7eb;text-align:center;color:#dc2626;">%d</td>
                </tr>',
                htmlspecialchars($t->type ?? ''),
                $color,
                htmlspecialchars(ucfirst($status)),
                $records, $success, $duplicate, $failed
            );
        }

        return sprintf('
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;font-size:13px;color:#374151;margin:0;padding:20px;">
    <div style="max-width:600px;margin:0 auto;">
        <h2 style="color:#1e3a5f;margin-bottom:4px;">DICT-BOC Transmission Report</h2>
        <p style="color:#6b7280;margin-top:0;margin-bottom:20px;">%s &nbsp;|&nbsp; %s</p>

        <table style="width:100%%;border-collapse:collapse;border:1px solid #e5e7eb;border-radius:6px;overflow:hidden;">
            <thead>
                <tr style="background:#f9fafb;">
                    <th style="padding:8px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;border-bottom:2px solid #e5e7eb;">Type</th>
                    <th style="padding:8px 12px;text-align:left;font-size:11px;text-transform:uppercase;color:#6b7280;border-bottom:2px solid #e5e7eb;">Status</th>
                    <th style="padding:8px 12px;text-align:center;font-size:11px;text-transform:uppercase;color:#6b7280;border-bottom:2px solid #e5e7eb;">Total</th>
                    <th style="padding:8px 12px;text-align:center;font-size:11px;text-transform:uppercase;color:#6b7280;border-bottom:2px solid #e5e7eb;">Success</th>
                    <th style="padding:8px 12px;text-align:center;font-size:11px;text-transform:uppercase;color:#6b7280;border-bottom:2px solid #e5e7eb;">Duplicate</th>
                    <th style="padding:8px 12px;text-align:center;font-size:11px;text-transform:uppercase;color:#6b7280;border-bottom:2px solid #e5e7eb;">Failed</th>
                </tr>
            </thead>
            <tbody>%s</tbody>
            <tfoot>
                <tr style="background:#f9fafb;font-weight:600;">
                    <td style="padding:8px 12px;" colspan="2">Total</td>
                    <td style="padding:8px 12px;text-align:center;">%d</td>
                    <td style="padding:8px 12px;text-align:center;color:#16a34a;">%d</td>
                    <td style="padding:8px 12px;text-align:center;color:#d97706;">%d</td>
                    <td style="padding:8px 12px;text-align:center;color:#dc2626;">%d</td>
                </tr>
            </tfoot>
        </table>

        <p style="margin-top:20px;font-size:11px;color:#9ca3af;">
            Sent at %s &nbsp;|&nbsp; DICT-BOC API Manager
        </p>
    </div>
</body>
</html>',
            htmlspecialchars($dateRange),
            htmlspecialchars($label),
            $rows,
            $totalRecords, $totalSuccess, $totalDuplicate, $totalFailed,
            now()->format('Y-m-d H:i:s')
        );
    }

    private function buildPlainText(array $transmissions, string $dateRange, string $label): string
    {
        $lines = ["DICT-BOC Transmission Report", "Date Range: {$dateRange} | {$label}", str_repeat('-', 50)];
        foreach ($transmissions as $t) {
            $lines[] = sprintf(
                '%-12s %-10s  Total:%-4d  Success:%-4d  Duplicate:%-4d  Failed:%-4d',
                strtoupper($t->type ?? ''),
                strtoupper($t->status ?? ''),
                $t->records_count ?? 0,
                $t->success_count ?? 0,
                $t->duplicate_count ?? 0,
                $t->failed_count ?? 0
            );
        }
        $lines[] = str_repeat('-', 50);
        $lines[] = 'Sent at ' . now()->format('Y-m-d H:i:s');
        return implode("\n", $lines);
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
