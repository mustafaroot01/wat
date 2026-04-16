<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Admin;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('admin:id,name,email')
            ->orderBy('created_at', 'desc');

        // Filter by admin
        if ($request->has('admin_id') && $request->admin_id) {
            $query->where('admin_id', $request->admin_id);
        }

        // Filter by action
        if ($request->has('action') && $request->action) {
            $query->where('action', $request->action);
        }

        // Filter by model type
        if ($request->has('model_type') && $request->model_type) {
            $query->where('model_type', $request->model_type);
        }

        // Filter by date range
        if ($request->has('from_date') && $request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Search in description
        if ($request->has('search') && $request->search) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $logs = $query->paginate($request->get('per_page', 50));

        return response()->json($logs);
    }

    /**
     * Get filter options (admins, actions, model types)
     */
    public function filterOptions()
    {
        $admins = Admin::where('is_super_admin', false)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        $actions = ActivityLog::select('action')
            ->distinct()
            ->pluck('action')
            ->toArray();

        $modelTypes = ActivityLog::select('model_type')
            ->distinct()
            ->pluck('model_type')
            ->toArray();

        return response()->json([
            'admins'      => $admins,
            'actions'     => $actions,
            'model_types' => $modelTypes,
        ]);
    }

    /**
     * Show detailed log entry
     */
    public function show(ActivityLog $log)
    {
        $log->load('admin:id,name,email');
        
        return response()->json([
            'success' => true,
            'data'    => $log,
        ]);
    }
}
