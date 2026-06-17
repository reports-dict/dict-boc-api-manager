<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\EmailReportService;
use App\Services\TransmissionService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendDataCommand extends Command
{
    protected $signature = 'cms:send
        {--type=all : Type to send (discharge|load|release|receive|all)}
        {--from=   : Date from (Y-m-d). Defaults to yesterday.}
        {--to=     : Date to (Y-m-d). Defaults to yesterday.}';

    protected $description = 'Fetch data from SQL Server and transmit to the Customs API';

    public function __construct(
        private TransmissionService $service,
        private EmailReportService $emailService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('type') === 'all') {
            $raw   = Setting::get('auto_send_types', 'discharge,load,release,receive');
            $types = array_values(array_filter(explode(',', $raw)));
            if (empty($types)) {
                $this->warn('No data types enabled for auto-send. Exiting.');
                return self::SUCCESS;
            }
        } else {
            $types = [$this->option('type')];
        }

        $from = $this->option('from')
            ? Carbon::parse($this->option('from'))
            : Carbon::yesterday()->startOfDay();

        $to = $this->option('to')
            ? Carbon::parse($this->option('to'))
            : Carbon::yesterday()->endOfDay();

        $this->info("Running transmissions for: " . implode(', ', $types));
        $this->info("Date range: {$from->toDateString()} → {$to->toDateString()}");

        $transmissions = [];
        foreach ($types as $type) {
            $this->line("  → {$type}...");
            $transmission    = $this->service->run($type, $from, $to, null, 'auto');
            $transmissions[] = $transmission;
            $this->line("    Status: {$transmission->status} | Records: {$transmission->records_count}");
        }

        if (Setting::get('email_report_enabled') === '1') {
            $this->line('  → Sending email report…');
            $this->emailService->send($transmissions, 'auto');
        }

        $this->info('Done.');
        return self::SUCCESS;
    }
}
