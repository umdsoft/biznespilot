<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BusinessManagementController extends Controller
{
    /**
     * Display a listing of businesses
     */
    public function index()
    {
        $businesses = Business::with('owner')
            ->withCount(['customers', 'campaigns'])
            ->get()
            ->map(function ($business) {
                return [
                    'id' => $business->id,
                    'name' => $business->name,
                    'industry' => $business->industry,
                    'status' => $business->status,
                    'owner_name' => $business->owner->name ?? 'N/A',
                    'owner_email' => $business->owner->email ?? 'N/A',
                    'customers_count' => $business->customers_count,
                    'campaigns_count' => $business->campaigns_count,
                    'created_at' => $business->created_at->diffForHumans(),
                    'created_at_raw' => $business->created_at->toDateTimeString(),
                ];
            });

        $stats = [
            'total' => Business::count(),
            'active' => Business::where('status', 'active')->count(),
            'inactive' => Business::where('status', 'inactive')->count(),
            'this_month' => Business::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        return Inertia::render('Admin/Businesses/Index', [
            'businesses' => $businesses,
            'stats' => $stats,
        ]);
    }

    /**
     * Display a specific business
     */
    public function show(Business $business)
    {
        $business->load(['owner', 'customers', 'campaigns', 'chatbot_conversations']);

        $businessData = [
            'id' => $business->id,
            'name' => $business->name,
            'industry' => $business->industry,
            'description' => $business->description,
            'status' => $business->status,
            'website' => $business->website,
            'phone' => $business->phone,
            'email' => $business->email,
            'address' => $business->address,
            'created_at' => $business->created_at->format('d M Y'),
            'owner' => [
                'id' => $business->owner->id,
                'name' => $business->owner->name,
                'email' => $business->owner->email,
                'phone' => $business->owner->phone,
            ],
        ];

        $stats = [
            'total_customers' => $business->customers()->count(),
            'total_campaigns' => $business->campaigns()->count(),
            'active_campaigns' => $business->campaigns()->whereIn('status', ['running', 'scheduled'])->count(),
            'total_conversations' => $business->chatbot_conversations()->count(),
            'pending_conversations' => $business->chatbot_conversations()->where('status', 'pending')->count(),
        ];

        // Recent activity
        $recentCustomers = $business->customers()
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'created_at' => $customer->created_at->diffForHumans(),
            ]);

        $recentCampaigns = $business->campaigns()
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($campaign) => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'type' => $campaign->type,
                'status' => $campaign->status,
                'created_at' => $campaign->created_at->diffForHumans(),
            ]);

        return Inertia::render('Admin/Businesses/Show', [
            'business' => $businessData,
            'stats' => $stats,
            'recentCustomers' => $recentCustomers,
            'recentCampaigns' => $recentCampaigns,
        ]);
    }

    /**
     * Update business status
     */
    public function updateStatus(Request $request, Business $business)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,inactive',
        ]);

        $business->update([
            'status' => $validated['status'],
        ]);

        return redirect()->back()->with('success', 'Business status yangilandi');
    }

    /**
     * Delete a business
     */
    public function destroy(Business $business)
    {
        $businessName = $business->name;

        // Delete business (cascade will handle related records)
        $business->delete();

        return redirect()->route('admin.businesses.index')
            ->with('success', "{$businessName} biznesi muvaffaqiyatli o'chirildi");
    }
}
