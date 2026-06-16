<?php

namespace App\Http\Controllers;

use App\Models\Transmission;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today()->toDateString();

        $typeStats = collect(['discharge', 'load', 'release', 'receive'])
            ->mapWithKeys(function ($type) use ($today) {
                $latest = Transmission::where('type', $type)
                    ->whereDate('created_at', $today)
                    ->latest()
                    ->first();

                $todayCount = Transmission::where('type', $type)
                    ->whereDate('created_at', $today)
                    ->sum('success_count');

                return [$type => [
                    'today_count' => (int) $todayCount,
                    'last_status' => $latest?->status,
                    'last_sent'   => $latest?->created_at?->format('H:i'),
                ]];
            });

        $recentTransmissions = Transmission::with('sender:id,name,username')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($t) => [
                'id'            => $t->id,
                'type'          => $t->type,
                'status'        => $t->status,
                'date_from'     => $t->date_from->toDateString(),
                'date_to'       => $t->date_to->toDateString(),
                'records_count' => $t->records_count,
                'success_count' => $t->success_count,
                'triggered_by'  => $t->triggered_by,
                'sent_by'       => $t->sender?->name,
                'created_at'    => $t->created_at->format('Y-m-d H:i'),
            ]);

        return Inertia::render('Dashboard', [
            'typeStats'           => $typeStats,
            'recentTransmissions' => $recentTransmissions,
        ]);
    }
}
