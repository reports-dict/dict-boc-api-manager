<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::latest('created_at');

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->filled('search')) {
            $query->where('message', 'like', '%' . $request->search . '%');
        }

        $logs = $query->paginate(25)->through(fn ($log) => [
            'id'         => $log->id,
            'level'      => $log->level,
            'message'    => $log->message,
            'context'    => $log->context,
            'created_at' => $log->created_at->format('Y-m-d H:i:s'),
        ]);

        return Inertia::render('Logs/Index', [
            'logs'    => $logs,
            'filters' => $request->only(['level', 'date', 'search']),
        ]);
    }
}
