<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $query = Activity::query()
            ->with(['actor:id,name,email,role'])
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $q = trim((string) $request->search);
            $query->where(function ($qry) use ($q) {
                $qry->where('description', 'like', "%{$q}%")
                    ->orWhere('target_table', 'like', "%{$q}%")
                    ->orWhere('action_type', 'like', "%{$q}%")
                    ->orWhere('action_on', 'like', "%{$q}%")
                    ->orWhere('ip_address', 'like', "%{$q}%")
                    ->orWhere('device', 'like', "%{$q}%")
                    ->orWhere('location', 'like', "%{$q}%")
                    ->orWhereHas('actor', function ($actor) use ($q) {
                        $actor->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%");
                    });
            });
        }

        if ($request->filled('action_type')) {
            $query->where('action_type', $request->action_type);
        }

        if ($request->filled('target_table')) {
            $query->where('target_table', $request->target_table);
        }

        if ($request->filled('action_by')) {
            $query->where('action_by', $request->action_by);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('ip_address')) {
            $query->where('ip_address', 'like', '%'.$request->ip_address.'%');
        }

        $activities = $query->paginate(25)->withQueryString();

        $actionTypes = Activity::query()
            ->select('action_type')
            ->distinct()
            ->orderBy('action_type')
            ->pluck('action_type')
            ->merge(Activity::actionTypes())
            ->unique()
            ->sort()
            ->values();

        $targetTables = Activity::query()
            ->whereNotNull('target_table')
            ->select('target_table')
            ->distinct()
            ->orderBy('target_table')
            ->pluck('target_table');

        return view('admin.activities.index', compact('activities', 'actionTypes', 'targetTables'));
    }
}
