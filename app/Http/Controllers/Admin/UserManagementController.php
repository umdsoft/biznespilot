<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Carbon\Carbon;

class UserManagementController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index()
    {
        $users = User::withCount('businesses')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'login' => $user->login,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $this->getUserRole($user),
                'businesses_count' => $user->businesses_count,
                'two_factor_enabled' => $user->two_factor_enabled,
                'last_login_at' => $user->last_login_at
                    ? Carbon::parse($user->last_login_at)->diffForHumans()
                    : null,
                'last_login_at_raw' => $user->last_login_at,
                'created_at' => $user->created_at->format('d.m.Y'),
                'created_at_raw' => $user->created_at,
            ]);

        $stats = [
            'total' => User::count(),
            'admins' => User::role(['admin', 'super_admin'])->count(),
            'owners' => User::role('owner')->count(),
            'this_month' => User::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        return Inertia::render('Admin/Users/Create');
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'login' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', 'string', 'in:user,owner,admin,super_admin'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'login' => $validated['login'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        // Assign role
        $user->assignRole($validated['role']);

        return redirect()->route('admin.users.index')
            ->with('success', 'Foydalanuvchi muvaffaqiyatli yaratildi!');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $user->load(['businesses', 'roles']);

        return Inertia::render('Admin/Users/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'login' => $user->login,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $this->getUserRole($user),
                'roles' => $user->roles->pluck('name'),
                'two_factor_enabled' => $user->two_factor_enabled,
                'last_login_at' => $user->last_login_at
                    ? Carbon::parse($user->last_login_at)->format('d.m.Y H:i')
                    : null,
                'last_login_ip' => $user->last_login_ip,
                'created_at' => $user->created_at->format('d.m.Y H:i'),
                'businesses' => $user->businesses->map(fn($business) => [
                    'id' => $business->id,
                    'name' => $business->name,
                    'industry' => $business->industry,
                    'role' => $business->pivot->role ?? 'member',
                ]),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'login' => $user->login,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $this->getUserRole($user),
            ],
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'login' => ['required', 'string', 'max:255', 'unique:users,login,' . $user->id],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', Password::defaults()],
            'role' => ['required', 'string', 'in:user,owner,admin,super_admin'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'login' => $validated['login'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ]);

        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        // Sync role
        $user->syncRoles([$validated['role']]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Foydalanuvchi muvaffaqiyatli yangilandi!');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        // Prevent deleting super_admin
        if ($user->hasRole('super_admin')) {
            return back()->with('error', 'Super admin o\'chirilishi mumkin emas!');
        }

        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return back()->with('error', 'O\'zingizni o\'chira olmaysiz!');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Foydalanuvchi muvaffaqiyatli o\'chirildi!');
    }

    /**
     * Toggle user status (ban/unban).
     */
    public function toggleStatus(Request $request, User $user)
    {
        $request->validate([
            'locked_until' => ['nullable', 'date'],
        ]);

        if ($user->locked_until) {
            $user->update(['locked_until' => null]);
            $message = 'Foydalanuvchi blokdan chiqarildi!';
        } else {
            $user->update([
                'locked_until' => $request->locked_until ?? now()->addDays(30),
            ]);
            $message = 'Foydalanuvchi bloklandi!';
        }

        return back()->with('success', $message);
    }

    /**
     * Get user's primary role.
     */
    protected function getUserRole(User $user): string
    {
        if ($user->hasRole('super_admin')) return 'super_admin';
        if ($user->hasRole('admin')) return 'admin';
        if ($user->hasRole('owner')) return 'owner';
        return 'user';
    }
}
