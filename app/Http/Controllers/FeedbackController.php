<?php

namespace App\Http\Controllers;

use App\Models\FeedbackAttachment;
use App\Models\FeedbackReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FeedbackController extends Controller
{
    /**
     * Submit new feedback
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:bug,suggestion,question,other',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'page_url' => 'nullable|string|max:500',
            'browser_info' => 'nullable|string|max:500',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:5120|mimes:jpg,jpeg,png,gif,webp,pdf,txt,doc,docx,xls,xlsx',
        ]);

        $user = Auth::user();
        $businessId = session('current_business_id');

        // Create feedback report
        $feedback = FeedbackReport::create([
            'user_id' => $user->id,
            'business_id' => $businessId,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'status' => FeedbackReport::STATUS_PENDING,
            'priority' => FeedbackReport::PRIORITY_MEDIUM,
            'page_url' => $validated['page_url'] ?? null,
            'browser_info' => $validated['browser_info'] ?? null,
            'metadata' => [
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
                'submitted_at' => now()->toISOString(),
            ],
        ]);

        // Handle attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $fileName = $file->getClientOriginalName();
                $filePath = $file->store('feedback/' . $feedback->id, 'public');

                FeedbackAttachment::create([
                    'feedback_report_id' => $feedback->id,
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'file_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        $feedback->load('attachments');

        return response()->json([
            'success' => true,
            'message' => 'Xabaringiz muvaffaqiyatli yuborildi! Tez orada ko\'rib chiqamiz.',
            'feedback' => [
                'id' => $feedback->id,
                'type' => $feedback->type,
                'type_label' => $feedback->type_label,
                'title' => $feedback->title,
                'status' => $feedback->status,
                'status_label' => $feedback->status_label,
                'created_at' => $feedback->created_at->format('d.m.Y H:i'),
            ],
        ]);
    }

    /**
     * Get user's feedback history
     */
    public function myFeedback(Request $request)
    {
        $user = Auth::user();

        $feedbacks = FeedbackReport::where('user_id', $user->id)
            ->with('attachments')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $feedbacks->getCollection()->transform(fn($f) => [
            'id' => $f->id,
            'type' => $f->type,
            'type_label' => $f->type_label,
            'type_color' => $f->type_color,
            'title' => $f->title,
            'description' => Str::limit($f->description, 100),
            'status' => $f->status,
            'status_label' => $f->status_label,
            'status_color' => $f->status_color,
            'admin_notes' => $f->admin_notes,
            'resolved_at' => $f->resolved_at?->format('d.m.Y H:i'),
            'created_at' => $f->created_at->format('d.m.Y H:i'),
            'attachments_count' => $f->attachments->count(),
        ]);

        return response()->json($feedbacks);
    }

    /**
     * Get feedback types and metadata
     */
    public function getTypes()
    {
        return response()->json([
            'types' => FeedbackReport::TYPES,
            'type_colors' => FeedbackReport::TYPE_COLORS,
        ]);
    }
}
