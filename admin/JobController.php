<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $query = Job::with('client:id,name,email');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('flagged')) {
            $query->where('is_flagged', (bool)$request->flagged);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $jobs = $query->withCount('proposals')
                      ->orderBy($request->get('sort_by', 'created_at'), $request->get('sort_dir', 'desc'))
                      ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $jobs,
        ]);
    }

    public function show($id)
    {
        $job = Job::with([
            'client:id,name,email,avatar_url',
            'proposals.freelancer:id,name,email,avatar_url',
        ])->withCount('proposals')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $job,
        ]);
    }

    public function approve($id)
    {
        $job = Job::findOrFail($id);
        $job->update(['status' => 'open', 'approved_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Job approved and published.',
        ]);
    }

    public function reject($id)
    {
        $job = Job::findOrFail($id);
        $job->update(['status' => 'rejected']);

        return response()->json([
            'success' => true,
            'message' => 'Job has been rejected.',
        ]);
    }

    public function close($id)
    {
        $job = Job::findOrFail($id);
        $job->update(['status' => 'closed']);

        return response()->json([
            'success' => true,
            'message' => 'Job has been closed.',
        ]);
    }

    public function flag($id)
    {
        $job = Job::findOrFail($id);
        $job->update(['is_flagged' => !$job->is_flagged]);

        $action = $job->is_flagged ? 'flagged' : 'unflagged';

        return response()->json([
            'success' => true,
            'message' => "Job has been {$action}.",
        ]);
    }

    public function destroy($id)
    {
        $job = Job::findOrFail($id);
        $job->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job deleted successfully.',
        ]);
    }
}
