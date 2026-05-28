<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Job;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats()
    {
        $totalUsers      = User::count();
        $newUsersToday   = User::whereDate('created_at', today())->count();
        $activeJobs      = Job::where('status', 'open')->count();
        $totalRevenue    = Payment::where('status', 'completed')->sum('platform_fee');
        $revenueToday    = Payment::where('status', 'completed')
                                   ->whereDate('created_at', today())
                                   ->sum('platform_fee');
        $pendingPayments = Payment::where('status', 'pending')->count();
        $flaggedJobs     = Job::where('is_flagged', true)->count();
        $disputes        = Payment::where('status', 'disputed')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_users'        => $totalUsers,
                'new_users_today'    => $newUsersToday,
                'active_jobs'        => $activeJobs,
                'total_revenue'      => round($totalRevenue, 2),
                'revenue_today'      => round($revenueToday, 2),
                'pending_payments'   => $pendingPayments,
                'flagged_jobs'       => $flaggedJobs,
                'open_disputes'      => $disputes,
            ],
        ]);
    }

    public function revenueChart(Request $request)
    {
        $period = $request->get('period', '30'); // days

        $data = Payment::where('status', 'completed')
            ->where('created_at', '>=', now()->subDays((int)$period))
            ->selectRaw('DATE(created_at) as date, SUM(platform_fee) as revenue, COUNT(*) as transactions')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function recentActivity()
    {
        $recentUsers = User::latest()->take(5)->get(['id', 'name', 'email', 'created_at', 'role']);
        $recentJobs  = Job::with('client:id,name')->latest()->take(5)->get(['id', 'title', 'budget', 'status', 'created_at', 'client_id']);
        $recentPays  = Payment::with(['payer:id,name', 'payee:id,name'])->latest()->take(5)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'recent_users'    => $recentUsers,
                'recent_jobs'     => $recentJobs,
                'recent_payments' => $recentPays,
            ],
        ]);
    }
}
