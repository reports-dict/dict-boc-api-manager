<?php

namespace App\Http\Controllers;

use App\Models\Transmission;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransmissionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transmission::with('sender:id,name,username')->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transmissions = $query->paginate(20)->through(fn ($t) => [
            'id'              => $t->id,
            'type'            => $t->type,
            'status'          => $t->status,
            'date_from'       => $t->date_from->toDateString(),
            'date_to'         => $t->date_to->toDateString(),
            'records_count'   => $t->records_count,
            'success_count'   => $t->success_count,
            'failed_count'    => $t->failed_count,
            'duplicate_count' => $t->duplicate_count,
            'triggered_by'    => $t->triggered_by,
            'sent_by'         => $t->sender?->name,
            'created_at'      => $t->created_at->format('Y-m-d H:i'),
        ]);

        return Inertia::render('Transmissions/Index', [
            'transmissions' => $transmissions,
            'filters'       => $request->only(['type', 'status', 'date_from', 'date_to']),
        ]);
    }

    public function download(Transmission $transmission)
    {
        $payloads = $transmission->records()
            ->orderBy('id')
            ->pluck('payload')
            ->map(fn ($p) => is_array($p) ? $p : json_decode($p, true))
            ->values()
            ->all();

        $json     = json_encode(['data' => $payloads], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = "transmission_{$transmission->id}_{$transmission->type}_{$transmission->date_from->toDateString()}.json";

        return response($json, 200, [
            'Content-Type'        => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function show(Transmission $transmission)
    {
        $records = $transmission->records()
            ->orderByDesc('id')
            ->paginate(50)
            ->through(fn ($r) => [
                'id'               => $r->id,
                'container_no'     => $r->container_no,
                'status'           => $r->status,
                'response_message' => $r->response_message,
                'payload'          => $r->payload,
                'created_at'       => $r->created_at->format('Y-m-d H:i:s'),
            ]);

        return Inertia::render('Transmissions/Show', [
            'transmission' => [
                'id'              => $transmission->id,
                'type'            => $transmission->type,
                'status'          => $transmission->status,
                'date_from'       => $transmission->date_from->toDateString(),
                'date_to'         => $transmission->date_to->toDateString(),
                'records_count'   => $transmission->records_count,
                'success_count'   => $transmission->success_count,
                'failed_count'    => $transmission->failed_count,
                'duplicate_count' => $transmission->duplicate_count,
                'triggered_by'    => $transmission->triggered_by,
                'response_summary' => $transmission->response_summary,
                'created_at'      => $transmission->created_at->format('Y-m-d H:i'),
            ],
            'records' => $records,
        ]);
    }
}
