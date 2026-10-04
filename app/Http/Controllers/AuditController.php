<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::with('user')
            ->when($request->action, fn ($q, $a) => $q->where('action', 'like', "%{$a}%"))
            ->when($request->user_id, fn ($q, $u) => $q->where('user_id', $u))
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        return view('audit.index', compact('logs'));
    }
}
