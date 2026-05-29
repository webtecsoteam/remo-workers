<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->withCount(['jobs', 'payments'])
                       ->orderBy($request->get('sort_by', 'created_at'), $request->get('sort_dir', 'desc'))
                       ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $users,
        ]);
    }

    public function show($id)
    {
        $user = User::withCount(['jobs', 'payments'])
                    ->with(['skills', 'profile'])
                    ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $user,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'      => 'sometimes|string|max:255',
            'email'     => "sometimes|email|unique:users,email,{$id}",
            'role'      => 'sometimes|in:client,freelancer,admin',
            'joined_at' => 'sometimes|date',
        ]);

        $joinedAt = null;
        if (array_key_exists('joined_at', $validated)) {
            $joinedAt = $validated['joined_at'];
            unset($validated['joined_at']);
        }

        $user->update($validated);

        if ($joinedAt !== null) {
            if ($user->role !== 'freelancer') {
                return response()->json([
                    'success' => false,
                    'message' => 'Join date can only be adjusted for freelancers.',
                ], 422);
            }

            $user->created_at = $joinedAt;
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data'    => $user->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete admin accounts.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }

    public function suspend($id)
    {
        $user = User::findOrFail($id);
        $user->update(['status' => 'suspended']);

        return response()->json([
            'success' => true,
            'message' => "User {$user->name} has been suspended.",
        ]);
    }

    public function activate($id)
    {
        $user = User::findOrFail($id);
        $user->update(['status' => 'active']);

        return response()->json([
            'success' => true,
            'message' => "User {$user->name} has been activated.",
        ]);
    }

    public function verify($id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_verified' => true, 'verified_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => "User {$user->name} has been verified.",
        ]);
    }

    public function jobs($id)
    {
        $user = User::findOrFail($id);

        $jobs = $user->jobs()
                     ->with('proposals:id,job_id,status')
                     ->latest()
                     ->paginate(10);

        return response()->json([
            'success' => true,
            'data'    => $jobs,
        ]);
    }

    public function payments($id)
    {
        $user = User::findOrFail($id);

        $payments = $user->payments()
                         ->with(['payer:id,name', 'payee:id,name'])
                         ->latest()
                         ->paginate(10);

        return response()->json([
            'success' => true,
            'data'    => $payments,
        ]);
    }
}
