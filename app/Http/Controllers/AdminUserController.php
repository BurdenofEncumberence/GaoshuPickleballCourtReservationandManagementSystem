<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $role = (string) $request->query('role', 'all');
        $status = (string) $request->query('status', 'all');
        $search = trim((string) $request->query('search', ''));

        $query = User::query();

        if ($role === 'employees') {
            $query->whereIn('role', ['admin', 'manager', 'staff']);
        } elseif ($role !== 'all' && in_array($role, ['admin', 'manager', 'staff', 'player'], true)) {
            $query->where('role', $role);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'deactivated') {
            $query->where('is_active', false);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%");
            });
        }

        $users = $query
            ->orderByRaw("CASE role WHEN 'admin' THEN 1 WHEN 'manager' THEN 2 WHEN 'staff' THEN 3 ELSE 4 END")
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $kpis = [
            'total' => User::count(),
            'employees' => User::whereIn('role', ['admin', 'manager', 'staff'])->count(),
            'active' => User::where('is_active', true)->count(),
            'deactivated' => User::where('is_active', false)->count(),
            'players' => User::where('role', 'player')->orWhereNull('role')->count(),
        ];

        return view('admin.users.index', [
            'users' => $users,
            'role' => $role,
            'status' => $status,
            'search' => $search,
            'kpis' => $kpis,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(['staff', 'manager', 'admin', 'player'])],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $firstName = User::titleCaseName($validated['first_name']);
        $middleName = User::titleCaseName($validated['middle_name'] ?? null);
        $lastName = User::titleCaseName($validated['last_name']);
        $fullName = implode(' ', array_filter([$firstName, $middleName, $lastName]));

        $newUser = User::create([
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'name' => $fullName,
            'email' => $validated['email'],
            'role' => $validated['role'],
            'is_active' => true,
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        $roleLabel = ucfirst($newUser->role);
        ActivityLog::record(
            "Added {$roleLabel} Account",
            "Created new {$roleLabel} account for {$newUser->name} ({$newUser->email})."
        );

        return redirect()
            ->route('admin.users.index')
            ->with('status', "{$roleLabel} account for {$newUser->name} has been created and activated.");
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot modify the role of your own signed-in account.');
        }

        $validated = $request->validate([
            'role' => ['required', Rule::in(['admin', 'manager', 'staff', 'player'])],
        ]);

        $oldRole = ucfirst($user->role ?: 'player');
        $newRole = ucfirst($validated['role']);

        $user->update(['role' => $validated['role']]);

        ActivityLog::record(
            'Updated User Role',
            "Changed {$user->name} ({$user->email}) role from {$oldRole} to {$newRole}."
        );

        return back()->with('status', "Updated {$user->name}'s role to {$newRole}.");
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot deactivate your own currently signed-in account.');
        }

        $newStatus = ! (bool) ($user->is_active ?? true);
        $user->update(['is_active' => $newStatus]);

        $actionLabel = $newStatus ? 'Activated User Account' : 'Deactivated User Account';
        $stateWord = $newStatus ? 'activated' : 'deactivated';

        ActivityLog::record(
            $actionLabel,
            ucfirst($stateWord) . " {$user->role} account for {$user->name} ({$user->email})."
        );

        return back()->with('status', "Account for {$user->name} has been {$stateWord}.");
    }
}
