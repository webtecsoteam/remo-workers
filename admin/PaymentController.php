<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['payer:id,name,email', 'payee:id,name,email', 'job:id,title']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('transaction_id', 'like', "%{$request->search}%")
                  ->orWhereHas('payer', fn($q2) => $q2->where('name', 'like', "%{$request->search}%"))
                  ->orWhereHas('payee', fn($q2) => $q2->where('name', 'like', "%{$request->search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('method')) {
            $query->where('payment_method', $request->method);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $payments = $query->orderBy($request->get('sort_by', 'created_at'), $request->get('sort_dir', 'desc'))
                          ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $payments,
        ]);
    }

    public function show($id)
    {
        $payment = Payment::with([
            'payer',
            'payee',
            'job',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $payment,
        ]);
    }

    public function refund(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Only completed payments can be refunded.',
            ], 422);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $payment->update([
            'status'        => 'refunded',
            'refunded_at'   => now(),
            'refund_reason' => $request->reason,
        ]);

        // TODO: Trigger actual refund via payment gateway (Stripe, PayPal, etc.)

        return response()->json([
            'success' => true,
            'message' => 'Refund processed successfully.',
        ]);
    }

    public function resolveDispute(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        $request->validate([
            'resolution'   => 'required|in:favor_client,favor_freelancer,partial',
            'notes'        => 'nullable|string|max:1000',
        ]);

        $payment->update([
            'status'             => 'resolved',
            'dispute_resolved_at'=> now(),
            'dispute_resolution' => $request->resolution,
            'dispute_notes'      => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dispute resolved successfully.',
        ]);
    }

    public function summary()
    {
        $summary = [
            'total_volume'       => Payment::where('status', 'completed')->sum('amount'),
            'total_fees'         => Payment::where('status', 'completed')->sum('platform_fee'),
            'pending_amount'     => Payment::where('status', 'pending')->sum('amount'),
            'refunded_amount'    => Payment::where('status', 'refunded')->sum('amount'),
            'active_disputes'    => Payment::where('status', 'disputed')->count(),
            'by_method'          => Payment::where('status', 'completed')
                                           ->selectRaw('payment_method, SUM(amount) as total, COUNT(*) as count')
                                           ->groupBy('payment_method')
                                           ->get(),
        ];

        return response()->json([
            'success' => true,
            'data'    => $summary,
        ]);
    }
}
