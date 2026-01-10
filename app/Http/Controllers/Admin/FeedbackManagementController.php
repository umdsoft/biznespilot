<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeedbackAttachment;
use App\Models\FeedbackReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class FeedbackManagementController extends Controller
{
    /**
     * Display feedback list
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $type = $request->input('type');
        $status = $request->input('status');
        $priority = $request->input('priority');
        $search = $request->input('search');

        $query = FeedbackReport::with(['user:id,name,email', 'business:id,name', 'attachments'])
            ->orderBy('created_at', 'desc');

        if ($type && $type !== 'all') {
            $query->where('type', $type);
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($priority && $priority !== 'all') {
            $query->where('priority', $priority);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $feedbacks = $query->paginate($perPage);

        $feedbacks->getCollection()->transform(fn($f) => [
            'id' => $f->id,
            'type' => $f->type,
            'type_label' => $f->type_label,
            'type_color' => $f->type_color,
            'title' => $f->title,
            'description' => $f->description,
            'status' => $f->status,
            'status_label' => $f->status_label,
            'status_color' => $f->status_color,
            'priority' => $f->priority,
            'priority_label' => $f->priority_label,
            'priority_color' => $f->priority_color,
            'page_url' => $f->page_url,
            'user' => $f->user ? [
                'id' => $f->user->id,
                'name' => $f->user->name,
                'email' => $f->user->email,
            ] : null,
            'business' => $f->business ? [
                'id' => $f->business->id,
                'name' => $f->business->name,
            ] : null,
            'attachments' => $f->attachments->map(fn($a) => [
                'id' => $a->id,
                'file_name' => $a->file_name,
                'file_type' => $a->file_type,
                'file_size' => $a->formatted_size,
                'url' => $a->url,
                'is_image' => $a->is_image,
            ]),
            'admin_notes' => $f->admin_notes,
            'resolved_at' => $f->resolved_at?->format('d.m.Y H:i'),
            'created_at' => $f->created_at->format('d.m.Y H:i'),
        ]);

        // Get statistics
        $stats = $this->getStats();

        return Inertia::render('Admin/Feedback/Index', [
            'feedbacks' => $feedbacks,
            'stats' => $stats,
            'filters' => [
                'type' => $type,
                'status' => $status,
                'priority' => $priority,
                'search' => $search,
            ],
            'types' => FeedbackReport::TYPES,
            'statuses' => FeedbackReport::STATUSES,
            'priorities' => FeedbackReport::PRIORITIES,
        ]);
    }

    /**
     * Get feedback statistics
     */
    protected function getStats(): array
    {
        return [
            'total' => FeedbackReport::count(),
            'pending' => FeedbackReport::pending()->count(),
            'in_progress' => FeedbackReport::inProgress()->count(),
            'resolved' => FeedbackReport::resolved()->count(),
            'bugs' => FeedbackReport::ofType(FeedbackReport::TYPE_BUG)->unresolved()->count(),
            'suggestions' => FeedbackReport::ofType(FeedbackReport::TYPE_SUGGESTION)->unresolved()->count(),
            'urgent' => FeedbackReport::byPriority(FeedbackReport::PRIORITY_URGENT)->unresolved()->count(),
            'today' => FeedbackReport::whereDate('created_at', today())->count(),
            'this_week' => FeedbackReport::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];
    }

    /**
     * Show single feedback
     */
    public function show(FeedbackReport $feedback)
    {
        $feedback->load(['user:id,name,email', 'business:id,name', 'attachments', 'resolvedBy:id,name']);

        return Inertia::render('Admin/Feedback/Show', [
            'feedback' => [
                'id' => $feedback->id,
                'type' => $feedback->type,
                'type_label' => $feedback->type_label,
                'type_color' => $feedback->type_color,
                'title' => $feedback->title,
                'description' => $feedback->description,
                'status' => $feedback->status,
                'status_label' => $feedback->status_label,
                'status_color' => $feedback->status_color,
                'priority' => $feedback->priority,
                'priority_label' => $feedback->priority_label,
                'priority_color' => $feedback->priority_color,
                'page_url' => $feedback->page_url,
                'browser_info' => $feedback->browser_info,
                'metadata' => $feedback->metadata,
                'user' => $feedback->user ? [
                    'id' => $feedback->user->id,
                    'name' => $feedback->user->name,
                    'email' => $feedback->user->email,
                ] : null,
                'business' => $feedback->business ? [
                    'id' => $feedback->business->id,
                    'name' => $feedback->business->name,
                ] : null,
                'attachments' => $feedback->attachments->map(fn($a) => [
                    'id' => $a->id,
                    'file_name' => $a->file_name,
                    'file_type' => $a->file_type,
                    'file_size' => $a->formatted_size,
                    'url' => $a->url,
                    'is_image' => $a->is_image,
                ]),
                'admin_notes' => $feedback->admin_notes,
                'resolved_by' => $feedback->resolvedBy ? [
                    'id' => $feedback->resolvedBy->id,
                    'name' => $feedback->resolvedBy->name,
                ] : null,
                'resolved_at' => $feedback->resolved_at?->format('d.m.Y H:i'),
                'created_at' => $feedback->created_at->format('d.m.Y H:i'),
                'updated_at' => $feedback->updated_at->format('d.m.Y H:i'),
            ],
            'statuses' => FeedbackReport::STATUSES,
            'priorities' => FeedbackReport::PRIORITIES,
        ]);
    }

    /**
     * Update feedback status
     */
    public function updateStatus(Request $request, FeedbackReport $feedback)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,resolved,closed',
            'admin_notes' => 'nullable|string|max:5000',
        ]);

        $updateData = ['status' => $validated['status']];

        if (isset($validated['admin_notes'])) {
            $updateData['admin_notes'] = $validated['admin_notes'];
        }

        if ($validated['status'] === FeedbackReport::STATUS_RESOLVED) {
            $updateData['resolved_by'] = Auth::id();
            $updateData['resolved_at'] = now();
        }

        $feedback->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Status yangilandi',
            'feedback' => [
                'id' => $feedback->id,
                'status' => $feedback->status,
                'status_label' => $feedback->status_label,
                'status_color' => $feedback->status_color,
            ],
        ]);
    }

    /**
     * Update feedback priority
     */
    public function updatePriority(Request $request, FeedbackReport $feedback)
    {
        $validated = $request->validate([
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        $feedback->update(['priority' => $validated['priority']]);

        return response()->json([
            'success' => true,
            'message' => 'Muhimlik darajasi yangilandi',
            'feedback' => [
                'id' => $feedback->id,
                'priority' => $feedback->priority,
                'priority_label' => $feedback->priority_label,
                'priority_color' => $feedback->priority_color,
            ],
        ]);
    }

    /**
     * Add admin note
     */
    public function addNote(Request $request, FeedbackReport $feedback)
    {
        $validated = $request->validate([
            'note' => 'required|string|max:2000',
        ]);

        $feedback->addNote($validated['note']);

        return response()->json([
            'success' => true,
            'message' => 'Izoh qo\'shildi',
            'admin_notes' => $feedback->fresh()->admin_notes,
        ]);
    }

    /**
     * Delete feedback
     */
    public function destroy(FeedbackReport $feedback)
    {
        // Delete attachments first (files will be deleted by model event)
        $feedback->attachments()->delete();
        $feedback->delete();

        return response()->json([
            'success' => true,
            'message' => 'Feedback o\'chirildi',
        ]);
    }

    /**
     * Get analytics data
     */
    public function analytics(Request $request)
    {
        $period = $request->input('period', '30'); // days

        // Feedback by type over time
        $byType = FeedbackReport::select(
            'type',
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', now()->subDays($period))
            ->groupBy('type', 'date')
            ->orderBy('date')
            ->get()
            ->groupBy('type');

        // Feedback by status
        $byStatus = FeedbackReport::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Average resolution time (in hours)
        $avgResolutionTime = FeedbackReport::whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
            ->value('avg_hours');

        // Most active reporters
        $topReporters = FeedbackReport::select('user_id', DB::raw('COUNT(*) as count'))
            ->with('user:id,name,email')
            ->groupBy('user_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Daily submissions
        $dailySubmissions = FeedbackReport::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', now()->subDays($period))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'by_type' => $byType,
            'by_status' => $byStatus,
            'avg_resolution_hours' => round($avgResolutionTime ?? 0, 1),
            'top_reporters' => $topReporters,
            'daily_submissions' => $dailySubmissions,
            'stats' => $this->getStats(),
        ]);
    }
}
