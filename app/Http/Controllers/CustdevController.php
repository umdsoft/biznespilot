<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\CustdevSurvey;
use App\Models\CustdevQuestion;
use App\Models\CustdevResponse;
use App\Models\CustdevAnswer;
use App\Models\DreamBuyer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CustdevController extends Controller
{
    /**
     * Display a listing of surveys
     */
    public function index(Request $request)
    {
        $business = $request->user()->currentBusiness;

        $surveys = CustdevSurvey::forBusiness($business->id)
            ->with(['questions', 'dreamBuyer'])
            ->withCount(['responses', 'completedResponses'])
            ->latest()
            ->get();

        return Inertia::render('Business/Custdev/Index', [
            'surveys' => $surveys,
        ]);
    }

    /**
     * Show the form for creating a new survey
     */
    public function create(Request $request)
    {
        $business = $request->user()->currentBusiness;

        $dreamBuyers = DreamBuyer::where('business_id', $business->id)
            ->select('id', 'name', 'description')
            ->get();

        $defaultQuestions = CustdevSurvey::getDefaultQuestions();

        return Inertia::render('Business/Custdev/Create', [
            'dreamBuyers' => $dreamBuyers,
            'defaultQuestions' => $defaultQuestions,
        ]);
    }

    /**
     * Store a newly created survey
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'dream_buyer_id' => 'nullable|exists:dream_buyers,id',
            'welcome_message' => 'nullable|string',
            'thank_you_message' => 'nullable|string',
            'collect_contact' => 'nullable|boolean',
            'anonymous' => 'nullable|boolean',
            'estimated_time' => 'nullable|integer|min:1|max:60',
            'response_limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
            'theme_color' => 'nullable|string|max:20',
            'questions' => 'required|array|min:1',
            'questions.*.type' => 'required|string|in:text,textarea,select,multiselect,rating,scale',
            'questions.*.question' => 'required|string',
            'questions.*.category' => 'nullable|string',
            'questions.*.description' => 'nullable|string',
            'questions.*.placeholder' => 'nullable|string',
            'questions.*.options' => 'nullable|array',
            'questions.*.is_required' => 'nullable|boolean',
            'questions.*.is_default' => 'nullable|boolean',
            'questions.*.icon' => 'nullable|string',
            'questions.*.settings' => 'nullable|array',
        ]);

        $business = $request->user()->currentBusiness;

        DB::beginTransaction();

        try {
            // Auto-create Dream Buyer for this survey
            $dreamBuyer = DreamBuyer::create([
                'business_id' => $business->id,
                'name' => $request->title . ' - Ideal Mijoz',
                'description' => 'CustDev so\'rovnomasi asosida avtomatik yaratilgan profil',
                'priority' => 'medium',
                'is_primary' => false,
            ]);

            // Create survey linked to Dream Buyer
            $survey = CustdevSurvey::create([
                'business_id' => $business->id,
                'dream_buyer_id' => $dreamBuyer->id,
                'title' => $request->title,
                'description' => $request->description,
                'welcome_message' => $request->welcome_message ?? 'Salom! Ushbu qisqa so\'rovnomani to\'ldirishingizni so\'raymiz. Sizning fikringiz biz uchun juda muhim.',
                'thank_you_message' => $request->thank_you_message ?? 'Rahmat! Sizning javoblaringiz biz uchun juda qimmatli. Yaxshi kun tilaymiz!',
                'collect_contact' => $request->collect_contact ?? false,
                'anonymous' => $request->anonymous ?? true,
                'estimated_time' => $request->estimated_time ?? 5,
                'response_limit' => $request->response_limit,
                'expires_at' => $request->expires_at,
                'theme_color' => $request->theme_color ?? '#6366f1',
                'status' => 'active',
            ]);

            // Create questions
            foreach ($request->questions as $index => $questionData) {
                CustdevQuestion::create([
                    'survey_id' => $survey->id,
                    'type' => $questionData['type'],
                    'category' => $questionData['category'] ?? 'custom',
                    'question' => $questionData['question'],
                    'description' => $questionData['description'] ?? null,
                    'placeholder' => $questionData['placeholder'] ?? null,
                    'options' => $questionData['options'] ?? null,
                    'is_required' => $questionData['is_required'] ?? true,
                    'is_default' => $questionData['is_default'] ?? false,
                    'icon' => $questionData['icon'] ?? null,
                    'settings' => $questionData['settings'] ?? null,
                    'order' => $index + 1,
                ]);
            }

            DB::commit();

            return redirect()->route('business.custdev.index')
                ->with('success', 'So\'rovnoma muvaffaqiyatli yaratildi!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Xatolik yuz berdi: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified survey
     */
    public function show(Request $request, CustdevSurvey $custdev)
    {
        $this->authorize('view', $custdev);

        $custdev->load(['questions', 'dreamBuyer']);

        // Get recent responses
        $recentResponses = $custdev->responses()
            ->with('answers.question')
            ->completed()
            ->latest()
            ->take(10)
            ->get();

        // Get basic stats
        $stats = [
            'total_views' => $custdev->views_count,
            'total_responses' => $custdev->responses()->count(),
            'completed_responses' => $custdev->completedResponses()->count(),
            'completion_rate' => $custdev->responses_count > 0
                ? round(($custdev->completedResponses()->count() / $custdev->responses_count) * 100)
                : 0,
            'avg_time' => $custdev->completedResponses()->avg('time_spent') ?? 0,
        ];

        return Inertia::render('Business/Custdev/Show', [
            'survey' => $custdev,
            'recentResponses' => $recentResponses,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for editing the survey
     */
    public function edit(Request $request, CustdevSurvey $custdev)
    {
        $this->authorize('update', $custdev);

        $business = $request->user()->currentBusiness;

        $custdev->load('questions');

        $dreamBuyers = DreamBuyer::where('business_id', $business->id)
            ->select('id', 'name', 'description')
            ->get();

        $defaultQuestions = CustdevSurvey::getDefaultQuestions();

        return Inertia::render('Business/Custdev/Edit', [
            'survey' => $custdev,
            'dreamBuyers' => $dreamBuyers,
            'defaultQuestions' => $defaultQuestions,
        ]);
    }

    /**
     * Update the specified survey
     */
    public function update(Request $request, CustdevSurvey $custdev)
    {
        $this->authorize('update', $custdev);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'dream_buyer_id' => 'nullable|exists:dream_buyers,id',
            'welcome_message' => 'nullable|string',
            'thank_you_message' => 'nullable|string',
            'collect_contact' => 'nullable|boolean',
            'anonymous' => 'nullable|boolean',
            'estimated_time' => 'nullable|integer|min:1|max:60',
            'response_limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
            'theme_color' => 'nullable|string|max:20',
            'questions' => 'required|array|min:1',
            'questions.*.id' => 'nullable|exists:custdev_questions,id',
            'questions.*.type' => 'required|string|in:text,textarea,select,multiselect,rating,scale',
            'questions.*.question' => 'required|string',
            'questions.*.category' => 'nullable|string',
            'questions.*.description' => 'nullable|string',
            'questions.*.placeholder' => 'nullable|string',
            'questions.*.options' => 'nullable|array',
            'questions.*.is_required' => 'nullable|boolean',
            'questions.*.is_default' => 'nullable|boolean',
            'questions.*.icon' => 'nullable|string',
            'questions.*.settings' => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {
            // Update survey
            $custdev->update([
                'dream_buyer_id' => $request->dream_buyer_id,
                'title' => $request->title,
                'description' => $request->description,
                'welcome_message' => $request->welcome_message,
                'thank_you_message' => $request->thank_you_message,
                'collect_contact' => $request->collect_contact ?? false,
                'anonymous' => $request->anonymous ?? true,
                'estimated_time' => $request->estimated_time ?? 3,
                'response_limit' => $request->response_limit,
                'expires_at' => $request->expires_at,
                'theme_color' => $request->theme_color ?? '#6366f1',
            ]);

            // Get existing question IDs
            $existingIds = $custdev->questions()->pluck('id')->toArray();
            $submittedIds = collect($request->questions)->pluck('id')->filter()->toArray();

            // Delete removed questions
            $toDelete = array_diff($existingIds, $submittedIds);
            if (!empty($toDelete)) {
                CustdevQuestion::whereIn('id', $toDelete)->delete();
            }

            // Update or create questions
            foreach ($request->questions as $index => $questionData) {
                if (!empty($questionData['id'])) {
                    // Update existing
                    CustdevQuestion::where('id', $questionData['id'])->update([
                        'type' => $questionData['type'],
                        'category' => $questionData['category'] ?? 'custom',
                        'question' => $questionData['question'],
                        'description' => $questionData['description'] ?? null,
                        'placeholder' => $questionData['placeholder'] ?? null,
                        'options' => $questionData['options'] ?? null,
                        'is_required' => $questionData['is_required'] ?? true,
                        'is_default' => $questionData['is_default'] ?? false,
                        'icon' => $questionData['icon'] ?? null,
                        'settings' => $questionData['settings'] ?? null,
                        'order' => $index + 1,
                    ]);
                } else {
                    // Create new
                    CustdevQuestion::create([
                        'survey_id' => $custdev->id,
                        'type' => $questionData['type'],
                        'category' => $questionData['category'] ?? 'custom',
                        'question' => $questionData['question'],
                        'description' => $questionData['description'] ?? null,
                        'placeholder' => $questionData['placeholder'] ?? null,
                        'options' => $questionData['options'] ?? null,
                        'is_required' => $questionData['is_required'] ?? true,
                        'is_default' => $questionData['is_default'] ?? false,
                        'icon' => $questionData['icon'] ?? null,
                        'settings' => $questionData['settings'] ?? null,
                        'order' => $index + 1,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('business.custdev.edit', ['custdev' => $custdev->id])
                ->with('success', 'So\'rovnoma muvaffaqiyatli yangilandi!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Xatolik yuz berdi: ' . $e->getMessage());
        }
    }

    /**
     * Toggle survey status (activate/pause)
     */
    public function toggleStatus(Request $request, CustdevSurvey $custdev)
    {
        $this->authorize('update', $custdev);

        $newStatus = $custdev->status === 'active' ? 'paused' : 'active';

        $custdev->update(['status' => $newStatus]);

        $message = $newStatus === 'active'
            ? 'So\'rovnoma faollashtirildi!'
            : 'So\'rovnoma to\'xtatildi!';

        return back()->with('success', $message);
    }

    /**
     * Delete the survey
     */
    public function destroy(Request $request, CustdevSurvey $custdev)
    {
        $this->authorize('delete', $custdev);

        $custdev->delete();

        return redirect()->route('business.custdev.index')
            ->with('success', 'So\'rovnoma o\'chirildi!');
    }

    /**
     * Show survey results/analytics
     */
    public function results(Request $request, CustdevSurvey $custdev)
    {
        $this->authorize('view', $custdev);

        $custdev->load('questions');

        // Get all completed responses with answers
        $responses = $custdev->responses()
            ->with(['answers.question'])
            ->completed()
            ->latest()
            ->get();

        // Aggregate analytics for each question
        $questionAnalytics = [];

        foreach ($custdev->questions as $question) {
            $answers = CustdevAnswer::where('question_id', $question->id)
                ->whereHas('response', function ($q) {
                    $q->where('status', 'completed');
                })
                ->get();

            $analytics = [
                'question' => $question,
                'total_answers' => $answers->count(),
                'data' => [],
            ];

            if (in_array($question->type, ['select', 'multiselect'])) {
                // Count selections
                $optionCounts = [];
                foreach ($answers as $answer) {
                    $selected = $answer->selected_options ?? [];
                    foreach ($selected as $option) {
                        $optionCounts[$option] = ($optionCounts[$option] ?? 0) + 1;
                    }
                }
                arsort($optionCounts);
                $analytics['data'] = $optionCounts;
            } elseif (in_array($question->type, ['rating', 'scale'])) {
                // Calculate average and distribution
                $ratings = $answers->pluck('rating_value')->filter();
                $analytics['average'] = $ratings->avg() ?? 0;
                $analytics['distribution'] = $ratings->countBy()->sortKeys()->toArray();
            } else {
                // Text answers - just collect them
                $analytics['answers'] = $answers->pluck('answer')->filter()->values()->toArray();
            }

            $questionAnalytics[] = $analytics;
        }

        // Overall stats
        $stats = [
            'total_views' => $custdev->views_count,
            'total_started' => $custdev->responses()->count(),
            'total_completed' => $responses->count(),
            'completion_rate' => $custdev->responses()->count() > 0
                ? round(($responses->count() / $custdev->responses()->count()) * 100)
                : 0,
            'avg_time' => round($responses->avg('time_spent') ?? 0),
            'devices' => $responses->groupBy('device_type')->map->count(),
        ];

        return Inertia::render('Business/Custdev/Results', [
            'survey' => $custdev,
            'responses' => $responses,
            'questionAnalytics' => $questionAnalytics,
            'stats' => $stats,
        ]);
    }

    /**
     * Export responses to CSV
     */
    public function export(Request $request, CustdevSurvey $custdev)
    {
        $this->authorize('view', $custdev);

        $responses = $custdev->responses()
            ->with(['answers.question'])
            ->completed()
            ->get();

        $questions = $custdev->questions()->orderBy('order')->get();

        // Build CSV
        $headers = ['ID', 'Sana', 'Ism', 'Telefon', 'Email', 'Vaqt (soniya)'];
        foreach ($questions as $question) {
            $headers[] = $question->question;
        }

        $rows = [];
        foreach ($responses as $response) {
            $row = [
                $response->id,
                $response->completed_at?->format('Y-m-d H:i'),
                $response->respondent_name ?? '-',
                $response->respondent_phone ?? '-',
                $response->respondent_email ?? '-',
                $response->time_spent,
            ];

            foreach ($questions as $question) {
                $answer = $response->answers->firstWhere('question_id', $question->id);
                $row[] = $answer ? $answer->getDisplayValue() : '-';
            }

            $rows[] = $row;
        }

        // Generate CSV content
        $csv = implode(',', array_map(fn($h) => '"' . str_replace('"', '""', $h) . '"', $headers)) . "\n";
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $row)) . "\n";
        }

        $filename = 'custdev_' . $custdev->slug . '_' . date('Y-m-d') . '.csv';

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Sync survey results with Dream Buyer profile
     */
    public function syncToDreamBuyer(Request $request, CustdevSurvey $custdev)
    {
        $this->authorize('update', $custdev);

        if (!$custdev->dream_buyer_id) {
            return back()->with('error', 'Bu so\'rovnoma Ideal Mijoz profiliga bog\'lanmagan!');
        }

        $dreamBuyer = DreamBuyer::find($custdev->dream_buyer_id);

        if (!$dreamBuyer) {
            return back()->with('error', 'Ideal Mijoz profili topilmadi!');
        }

        // Aggregate answers by category
        $categoryData = [];
        $questions = $custdev->questions;

        foreach ($questions as $question) {
            if (!$question->category || $question->category === 'custom') {
                continue;
            }

            $answers = CustdevAnswer::where('question_id', $question->id)
                ->whereHas('response', fn($q) => $q->where('status', 'completed'))
                ->get();

            $values = [];

            if (in_array($question->type, ['select', 'multiselect'])) {
                foreach ($answers as $answer) {
                    if ($answer->selected_options) {
                        $values = array_merge($values, $answer->selected_options);
                    }
                }
                $values = array_unique($values);
            } else {
                foreach ($answers as $answer) {
                    if ($answer->answer) {
                        $values[] = $answer->answer;
                    }
                }
            }

            if (!empty($values)) {
                $categoryData[$question->category] = array_merge(
                    $categoryData[$question->category] ?? [],
                    $values
                );
            }
        }

        // Update Dream Buyer fields
        $updateData = [];

        if (!empty($categoryData['where_spend_time'])) {
            $existing = $dreamBuyer->where_spend_time ? explode("\n", $dreamBuyer->where_spend_time) : [];
            $merged = array_unique(array_merge($existing, $categoryData['where_spend_time']));
            $updateData['where_spend_time'] = implode("\n", $merged);
        }

        if (!empty($categoryData['info_sources'])) {
            $existing = $dreamBuyer->info_sources ? explode("\n", $dreamBuyer->info_sources) : [];
            $merged = array_unique(array_merge($existing, $categoryData['info_sources']));
            $updateData['info_sources'] = implode("\n", $merged);
        }

        if (!empty($categoryData['frustrations'])) {
            $existing = $dreamBuyer->frustrations ? explode("\n", $dreamBuyer->frustrations) : [];
            $merged = array_unique(array_merge($existing, $categoryData['frustrations']));
            $updateData['frustrations'] = implode("\n", $merged);
        }

        if (!empty($categoryData['dreams'])) {
            $existing = $dreamBuyer->dreams ? explode("\n", $dreamBuyer->dreams) : [];
            $merged = array_unique(array_merge($existing, $categoryData['dreams']));
            $updateData['dreams'] = implode("\n", $merged);
        }

        if (!empty($categoryData['fears'])) {
            $existing = $dreamBuyer->fears ? explode("\n", $dreamBuyer->fears) : [];
            $merged = array_unique(array_merge($existing, $categoryData['fears']));
            $updateData['fears'] = implode("\n", $merged);
        }

        if (!empty($updateData)) {
            $dreamBuyer->update($updateData);
        }

        return back()->with('success', 'Ma\'lumotlar Ideal Mijoz profiliga sinxronlandi!');
    }
}
