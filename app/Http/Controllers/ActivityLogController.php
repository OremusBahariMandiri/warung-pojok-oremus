<?php

namespace App\Http\Controllers;

use App\Models\ActivityLogs;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of the activity logs.
     */
    public function index(Request $request)
    {
        $query = ActivityLogs::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('module')) {
            $query->where('module', strtoupper($request->module));
        }

        if ($request->filled('action')) {
            $query->where('action', strtoupper($request->action));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $logs = $query->paginate(20);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $logs,
            ]);
        }

        return view('pages.activity_logs.index', compact('logs'));
    }

    /**
     * Display the specified activity log.
     */
    public function show(Request $request, ActivityLogs $activityLog)
    {
        $activityLog->load('user');

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $activityLog,
            ]);
        }

        return view('pages.activity_logs.show', compact('activityLog'));
    }
}
