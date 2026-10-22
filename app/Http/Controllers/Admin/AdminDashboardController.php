<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AdminAccounts;

/** Panel general del administrador global (/admin). Middleware 'admin'. */
class AdminDashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'totals' => AdminAccounts::totals(),
            'recentAccounts' => User::with(['latestSubscription.plan', 'clinic'])
                ->latest()
                ->limit(8)
                ->get(),
            'pendingAccounts' => User::where('account_status', User::ACCOUNT_PENDING)
                ->orderBy('activates_at')
                ->limit(8)
                ->get(),
            'recentAudit' => AuditLog::with('user')->latest('id')->limit(6)->get(),
        ]);
    }
}
