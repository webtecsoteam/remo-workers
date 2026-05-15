<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Job;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function overview(Request $request)
    {
        $period = $request->get('period', 30); // days

        $from = now()->subDays($period);

        return response()->json([
            'success' => true,
            'data' => [
                'users' => [
                    'total'       => User::count(),
                    'new'         => User::where('created_at', '>=', $from)->count(),
                    'freelancers' => User::where('role', 'freelancer')->count(),
                    'clients'     => User::where('role', 'client')->count(),
                    'suspended'   => User::where('status', 'suspended')->count(),
                    'verified'    => User::where('is_verified', true)->count(),
                ],
                'jobs' => [
                    'total'    => Job::count(),
                    'open'     => Job::where('status', 'open')->count(),
                    'closed'   => Job::where('status', 'closed')->count(),
                    'new'      => Job::where('created_at', '>=', $from)->count(),
                    'flagged'  => Job::where('is_flagged', true)->count(),
                ],
                'revenue' => [
                    'total_fees'    => Payment::where('status', 'completed')->sum('platform_fee'),
                    'period_fees'   => Payment::where('status', 'completed')->where('created_at', '>=', $from)->sum('platform_fee'),
                    'total_volume'  => Payment::where('status', 'completed')->sum('amount'),
                    'period_volume' => Payment::where('status', 'completed')->where('created_at', '>=', $from)->sum('amount'),
                ],
            ],
        ]);
    }

    public function users(Request $request)
    {
        $period = (int)$request->get('period', 30);

        $signups = User::where('created_at', '>=', now()->subDays($period))
            ->selectRaw('DATE(created_at) as date, role, COUNT(*) as count')
            ->groupBy('date', 'role')
            ->orderBy('date')
            ->get();

        $topFreelancers = User::where('role', 'freelancer')
            ->withSum(['payments as earned' => fn($q) => $q->where('status', 'completed')], 'amount')
            ->orderByDesc('earned')
            ->take(10)
            ->get(['id', 'name', 'email', 'is_verified', 'created_at']);

        $topClients = User::where('role', 'client')
            ->withSum(['sentPayments as spent' => fn($q) => $q->where('status', 'completed')], 'amount')
            ->orderByDesc('spent')
            ->take(10)
            ->get(['id', 'name', 'email', 'is_verified', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => [
                'signups'         => $signups,
                'top_freelancers' => $topFreelancers,
                'top_clients'     => $topClients,
            ],
        ]);
    }

    public function jobs(Request $request)
    {
        $period = (int)$request->get('period', 30);

        $postings = Job::where('created_at', '>=', now()->subDays($period))
            ->selectRaw('DATE(created_at) as date, status, COUNT(*) as count')
            ->groupBy('date', 'status')
            ->orderBy('date')
            ->get();

        $byCategory = Job::selectRaw('category, COUNT(*) as count, AVG(budget) as avg_budget')
            ->groupBy('category')
            ->orderByDesc('count')
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'postings'    => $postings,
                'by_category' => $byCategory,
            ],
        ]);
    }

    public function revenue(Request $request)
    {
        $period = (int)$request->get('period', 30);

        $daily = Payment::where('status', 'completed')
            ->where('created_at', '>=', now()->subDays($period))
            ->selectRaw('DATE(created_at) as date, SUM(amount) as volume, SUM(platform_fee) as fees, COUNT(*) as transactions')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $byMethod = Payment::where('status', 'completed')
            ->selectRaw('payment_method, SUM(amount) as volume, SUM(platform_fee) as fees, COUNT(*) as transactions')
            ->groupBy('payment_method')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'daily'     => $daily,
                'by_method' => $byMethod,
            ],
        ]);
    }

    public function export(Request $request)
    {
        $request->validate([
            'type'     => 'required|in:users,jobs,payments,revenue',
            'format'   => 'required|in:csv,json',
            'date_from'=> 'nullable|date',
            'date_to'  => 'nullable|date',
        ]);

        // In production, dispatch a queued job and return a download URL
        // For simplicity, returning direct data here

        $data = match ($request->type) {
            'users'    => User::whereBetween('created_at', [$request->date_from ?? '2000-01-01', $request->date_to ?? now()])->get(),
            'jobs'     => Job::whereBetween('created_at', [$request->date_from ?? '2000-01-01', $request->date_to ?? now()])->get(),
            'payments' => Payment::whereBetween('created_at', [$request->date_from ?? '2000-01-01', $request->date_to ?? now()])->get(),
            default    => collect(),
        };

        if ($request->format === 'csv') {
            // Return CSV headers for streaming in production
            return response()->json(['success' => true, 'message' => 'Export dispatched. Download will be emailed.']);
        }

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }
}
