<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

/** Bitácora de acciones sensibles (/admin/auditoria). Middleware 'admin'. */
class AdminAuditLogController extends Controller
{
    public function index(Request $request)
    {
        $action = array_key_exists((string) $request->query('accion'), AuditLog::ACTION_LABELS) ? $request->query('accion') : null;

        return view('admin.audit', [
            'logs' => AuditLog::with('user')
                ->when($action, fn ($query) => $query->where('action', $action))
                ->latest('id')
                ->paginate(30)
                ->withQueryString(),
            'actions' => AuditLog::ACTION_LABELS,
            'action' => $action,
        ]);
    }
}
