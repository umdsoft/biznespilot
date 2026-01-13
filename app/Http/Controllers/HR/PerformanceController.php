<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\EmployeeGoal;
use App\Models\KpiTemplate;
use App\Models\PerformanceReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PerformanceController extends Controller
{
    use HasCurrentBusiness;

    /**
     * Display performance dashboard
     */
    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        $userId = $request->get('user_id', Auth::id());

        // Get employee goals
        $goals = EmployeeGoal::where('business_id', $business->id)
            ->where('user_id', $userId)
            ->with(['kpiTemplate', 'creator'])
            ->orderBy('due_date', 'asc')
            ->get()
            ->map(fn($goal) => [
                'id' => $goal->id,
                'title' => $goal->title,
                'description' => $goal->description,
                'status' => $goal->status,
                'status_label' => $goal->status_label,
                'progress' => $goal->progress,
                'target_value' => $goal->target_value,
                'current_value' => $goal->current_value,
                'measurement_unit' => $goal->measurement_unit,
                'start_date' => $goal->start_date->format('d.m.Y'),
                'due_date' => $goal->due_date->format('d.m.Y'),
                'kpi_name' => $goal->kpiTemplate ? $goal->kpiTemplate->name : null,
                'created_by' => $goal->creator->name ?? null,
            ]);

        // Get performance reviews
        $reviews = PerformanceReview::where('business_id', $business->id)
            ->where('user_id', $userId)
            ->with(['reviewer'])
            ->orderBy('review_date', 'desc')
            ->get()
            ->map(fn($review) => [
                'id' => $review->id,
                'review_period' => $review->review_period,
                'review_date' => $review->review_date->format('d.m.Y'),
                'status' => $review->status,
                'status_label' => $review->status_label,
                'overall_rating' => $review->overall_rating,
                'reviewer_name' => $review->reviewer->name ?? null,
            ]);

        // Get employees list for goal assignment
        $employees = $business->users()
            ->select('users.id', 'users.name')
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ]);

        // Stats
        $stats = [
            'active_goals' => EmployeeGoal::where('business_id', $business->id)
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->count(),
            'completed_goals' => EmployeeGoal::where('business_id', $business->id)
                ->where('user_id', $userId)
                ->where('status', 'completed')
                ->count(),
            'pending_reviews' => PerformanceReview::where('business_id', $business->id)
                ->where('user_id', $userId)
                ->where('status', 'draft')
                ->count(),
            'average_progress' => EmployeeGoal::where('business_id', $business->id)
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->avg('progress') ?? 0,
        ];

        return Inertia::render('HR/Performance/Index', [
            'goals' => $goals,
            'reviews' => $reviews,
            'stats' => $stats,
            'employees' => $employees,
            'currentUserId' => $userId,
        ]);
    }

    /**
     * Store new goal
     */
    public function storeGoal(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'kpi_template_id' => 'nullable|exists:hr_kpi_templates,id',
            'start_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:start_date',
            'target_value' => 'nullable|numeric',
            'measurement_unit' => 'nullable|string|max:50',
        ]);

        EmployeeGoal::create([
            'business_id' => $business->id,
            'user_id' => $validated['user_id'],
            'kpi_template_id' => $validated['kpi_template_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'start_date' => $validated['start_date'],
            'due_date' => $validated['due_date'],
            'target_value' => $validated['target_value'] ?? null,
            'measurement_unit' => $validated['measurement_unit'] ?? null,
            'status' => 'active',
            'progress' => 0,
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', 'Maqsad muvaffaqiyatli yaratildi');
    }

    /**
     * Update goal progress
     */
    public function updateGoal(Request $request, EmployeeGoal $goal)
    {
        $business = $this->getCurrentBusiness();

        if (!$business || $goal->business_id !== $business->id) {
            return back()->with('error', 'Ruxsat yo\'q');
        }

        $validated = $request->validate([
            'progress' => 'required|integer|min:0|max:100',
            'current_value' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        $goal->update([
            'progress' => $validated['progress'],
            'current_value' => $validated['current_value'] ?? $goal->current_value,
            'notes' => $validated['notes'] ?? $goal->notes,
        ]);

        // Auto-complete if 100%
        if ($validated['progress'] >= 100) {
            $goal->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }

        return back()->with('success', 'Maqsad yangilandi');
    }

    /**
     * KPI Templates management
     */
    public function kpiTemplates()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        $templates = KpiTemplate::where('business_id', $business->id)
            ->orderBy('category')
            ->get()
            ->map(fn($template) => [
                'id' => $template->id,
                'name' => $template->name,
                'description' => $template->description,
                'category' => $template->category,
                'category_label' => $template->category_label,
                'measurement_unit' => $template->measurement_unit,
                'target_value' => $template->target_value,
                'frequency' => $template->frequency,
                'frequency_label' => $template->frequency_label,
                'is_active' => $template->is_active,
            ]);

        return Inertia::render('HR/Performance/KpiTemplates', [
            'templates' => $templates,
        ]);
    }

    /**
     * Store KPI template
     */
    public function storeKpiTemplate(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return back()->with('error', 'Biznes topilmadi');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:sales,productivity,quality,customer_satisfaction',
            'measurement_unit' => 'required|in:percentage,number,currency',
            'target_value' => 'nullable|numeric',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly,annually',
        ]);

        KpiTemplate::create([
            'business_id' => $business->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'],
            'measurement_unit' => $validated['measurement_unit'],
            'target_value' => $validated['target_value'] ?? null,
            'frequency' => $validated['frequency'],
            'is_active' => true,
        ]);

        return back()->with('success', 'KPI shablon yaratildi');
    }
}
