<?php

namespace App\Http\Controllers;

use App\Services\DataFetchService;
use App\Services\EmailReportService;
use App\Services\TransmissionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SendController extends Controller
{
    public function __construct(
        private TransmissionService $transmissionService,
        private DataFetchService $fetchService,
        private EmailReportService $emailService,
    ) {}

    public function index()
    {
        $yesterday = Carbon::yesterday();
        return Inertia::render('Send/Index', [
            'defaultDateFrom' => $yesterday->startOfDay()->format('Y-m-d\TH:i'),
            'defaultDateTo'   => $yesterday->endOfDay()->format('Y-m-d\TH:i'),
        ]);
    }

    private function parseContainers(Request $request): array
    {
        if (!$request->filled('containers')) {
            return [];
        }
        return array_values(array_filter(
            array_map('trim', preg_split('/[\s,]+/', $request->containers)),
            fn($c) => $c !== ''
        ));
    }

    public function preview(Request $request)
    {
        $request->validate([
            'types'      => ['required', 'array'],
            'types.*'    => ['in:discharge,load,release,receive'],
            'date_from'  => ['required', 'date'],
            'date_to'    => ['required', 'date', 'gte:date_from'],
            'containers' => ['nullable', 'string'],
        ]);

        $from       = Carbon::parse($request->date_from);
        $to         = Carbon::parse($request->date_to);
        $containers = $this->parseContainers($request);
        $counts     = [];

        foreach ($request->types as $type) {
            try {
                $rows           = $this->fetchService->fetch($type, $from, $to, $containers);
                $counts[$type]  = count($rows);
            } catch (\Throwable $e) {
                $counts[$type]  = ['error' => $e->getMessage()];
            }
        }

        return response()->json(['counts' => $counts]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'types'      => ['required', 'array', 'min:1'],
            'types.*'    => ['in:discharge,load,release,receive'],
            'date_from'  => ['required', 'date'],
            'date_to'    => ['required', 'date', 'gte:date_from'],
            'containers' => ['nullable', 'string'],
            'send_email' => ['boolean'],
        ]);

        $from          = Carbon::parse($request->date_from);
        $to            = Carbon::parse($request->date_to);
        $user          = $request->user();
        $containers    = $this->parseContainers($request);
        $transmissions = [];
        $ids           = [];

        foreach ($request->types as $type) {
            $transmission    = $this->transmissionService->run($type, $from, $to, $user, 'manual', $containers);
            $transmissions[] = $transmission;
            $ids[]           = $transmission->id;
        }

        if ($request->boolean('send_email')) {
            $this->emailService->send($transmissions, 'manual');
        }

        $redirectId = count($ids) === 1 ? $ids[0] : null;

        if ($redirectId) {
            return redirect()->route('transmissions.show', $redirectId)
                ->with('success', 'Transmission started successfully.');
        }

        return redirect()->route('transmissions.index')
            ->with('success', 'Transmissions started successfully.');
    }
}
