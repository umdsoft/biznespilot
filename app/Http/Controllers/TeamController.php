<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\User;
use App\Models\Business;
use App\Models\BusinessUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class TeamController extends Controller
{
    use HasCurrentBusiness;

    /**
     * Get team members for the current business
     * Returns JSON for API requests, Inertia page for regular requests
     */
    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Biznes topilmadi'], 404);
            }
            return redirect()->route('login');
        }

        $membersData = $this->getMembersData($business);

        // Return JSON for API/AJAX requests
        if ($request->wantsJson()) {
            return response()->json($membersData);
        }

        // Return Inertia page for regular requests
        return Inertia::render('HR/Team/Index', $membersData);
    }

    /**
     * Helper method to get members data
     */
    private function getMembersData($business)
    {
        // Get business owner
        $owner = $business->owner;
        $ownerData = null;
        if ($owner) {
            $ownerData = [
                'id' => 'owner',
                'user_id' => $owner->id,
                'name' => $owner->name,
                'phone' => $owner->phone ?? $owner->login ?? $owner->email,
                'role' => 'owner',
                'role_label' => 'Biznes egasi',
                'department' => null,
                'department_label' => null,
                'status' => 'active',
                'is_owner' => true,
                'joined_at' => $business->created_at?->format('d.m.Y H:i'),
                'created_at' => $business->created_at?->format('d.m.Y H:i'),
            ];
        }

        // Get team members
        $members = BusinessUser::where('business_id', $business->id)
            ->with(['user:id,name,phone,login'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'user_id' => $member->user_id,
                    'name' => $member->user->name ?? null,
                    'phone' => $member->user->phone ?? $member->user->login ?? null,
                    'role' => $member->role,
                    'role_label' => $member->role_label,
                    'department' => $member->department,
                    'department_label' => $member->department_label,
                    'status' => 'active',
                    'is_owner' => false,
                    'joined_at' => $member->joined_at?->format('d.m.Y H:i'),
                    'created_at' => $member->created_at?->format('d.m.Y H:i'),
                ];
            })
            ->toArray();

        // Add owner at the beginning
        if ($ownerData) {
            array_unshift($members, $ownerData);
        }

        return [
            'members' => $members,
            'departments' => BusinessUser::DEPARTMENTS,
            'roles' => BusinessUser::ROLES,
        ];
    }

    /**
     * Add a new team member (create user with phone/password)
     */
    public function invite(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|min:12|max:12',
            'password' => ['required', 'confirmed', Password::min(6)],
            'department' => 'required|in:' . implode(',', array_keys(BusinessUser::DEPARTMENTS)),
        ], [
            'name.required' => 'F.I.O kiritilishi shart',
            'phone.required' => 'Telefon raqam kiritilishi shart',
            'phone.min' => 'Telefon raqam 12 ta raqamdan iborat bo\'lishi kerak',
            'password.required' => 'Parol kiritilishi shart',
            'password.confirmed' => 'Parollar mos kelmayapti',
            'password.min' => 'Parol kamida 6 ta belgidan iborat bo\'lishi kerak',
            'department.required' => 'Bo\'lim tanlanishi shart',
        ]);

        // Check if user with this phone already exists
        $existingUser = User::where('phone', $validated['phone'])
            ->orWhere('login', $validated['phone'])
            ->first();

        if ($existingUser) {
            // Check if already a member of this business
            $existingMember = BusinessUser::where('business_id', $business->id)
                ->where('user_id', $existingUser->id)
                ->first();

            if ($existingMember) {
                return response()->json([
                    'error' => 'Bu telefon raqam bilan foydalanuvchi allaqachon jamoada mavjud'
                ], 422);
            }

            return response()->json([
                'error' => 'Bu telefon raqam bilan foydalanuvchi allaqachon tizimda ro\'yxatdan o\'tgan'
            ], 422);
        }

        // Create new user
        $user = User::create([
            'name' => $validated['name'],
            'login' => $validated['phone'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
        ]);

        // Add to team
        $member = BusinessUser::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => 'member', // All team members are "member" role
            'department' => $validated['department'],
            'joined_at' => now(),
            'accepted_at' => now(),
            'invited_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Xodim muvaffaqiyatli qo\'shildi',
            'member' => [
                'id' => $member->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'role' => $member->role,
                'role_label' => $member->role_label,
                'department' => $member->department,
                'department_label' => $member->department_label,
                'status' => 'active',
                'joined_at' => $member->joined_at->format('d.m.Y H:i'),
            ],
        ]);
    }

    /**
     * Update team member
     */
    public function update(Request $request, BusinessUser $member)
    {
        $business = $this->getCurrentBusiness();

        if (!$business || $member->business_id !== $business->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $validated = $request->validate([
            'department' => 'sometimes|in:' . implode(',', array_keys(BusinessUser::DEPARTMENTS)),
            'role' => 'sometimes|in:' . implode(',', array_keys(BusinessUser::ROLES)),
        ]);

        $member->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'A\'zo ma\'lumotlari yangilandi',
            'member' => [
                'id' => $member->id,
                'role' => $member->role,
                'role_label' => $member->role_label,
                'department' => $member->department,
                'department_label' => $member->department_label,
            ],
        ]);
    }

    /**
     * Remove team member
     */
    public function remove(BusinessUser $member)
    {
        $business = $this->getCurrentBusiness();

        if (!$business || $member->business_id !== $business->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        // Cannot remove owner
        if ($member->role === 'owner') {
            return response()->json(['error' => 'Biznes egasini o\'chirib bo\'lmaydi'], 422);
        }

        // Get the user before deleting member
        $user = $member->user;

        // Delete team membership
        $member->delete();

        // Check if user has other business memberships or owns any business
        $otherMemberships = BusinessUser::where('user_id', $user->id)->count();
        $ownedBusinesses = Business::where('user_id', $user->id)->count();

        // If user has no other associations, delete the user too
        if ($otherMemberships === 0 && $ownedBusinesses === 0) {
            $user->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'A\'zo jamoadan o\'chirildi',
        ]);
    }

    /**
     * Reset member password
     */
    public function resetPassword(Request $request, BusinessUser $member)
    {
        $business = $this->getCurrentBusiness();

        if (!$business || $member->business_id !== $business->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(6)],
        ], [
            'password.required' => 'Yangi parol kiritilishi shart',
            'password.confirmed' => 'Parollar mos kelmayapti',
            'password.min' => 'Parol kamida 6 ta belgidan iborat bo\'lishi kerak',
        ]);

        $member->user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Parol muvaffaqiyatli o\'zgartirildi',
        ]);
    }
}
