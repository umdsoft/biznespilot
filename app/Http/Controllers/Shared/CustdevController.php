<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\CustdevAnswer;
use App\Models\CustdevQuestion;
use App\Models\CustdevSurvey;
use App\Models\DreamBuyer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CustdevController extends Controller
{
    use HasCurrentBusiness;

    /**
     * Detect panel type from route prefix
     */
    private function getPanelType(Request $request): string
    {
        $path = $request->path();

        if (str_contains($path, 'marketing')) {
            return 'marketing';
        }

        return 'business';
    }

    /**
     * Get view prefix based on panel type
     */
    private function getViewPrefix(Request $request): string
    {
        return $this->getPanelType($request) === 'marketing' ? 'Marketing' : 'Business';
    }

    /**
     * Get route prefix for redirects
     */
    private function getRoutePrefix(Request $request): string
    {
        return $this->getPanelType($request) === 'marketing' ? 'marketing.custdev' : 'business.custdev';
    }

    /**
     * Authorize survey belongs to current business
     */
    private function authorizeSurvey($surveyId)
    {
        $business = $this->getCurrentBusiness();

        return CustdevSurvey::where('business_id', $business->id)
            ->where('id', $surveyId)
            ->firstOrFail();
    }

    /**
     * Display a listing of surveys
     */
    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $surveys = CustdevSurvey::forBusiness($business->id)
            ->with(['questions', 'dreamBuyer'])
            ->withCount(['responses', 'completedResponses'])
            ->latest()
            ->get();

        return Inertia::render($this->getViewPrefix($request).'/Custdev/Index', [
            'surveys' => $surveys,
        ]);
    }

    /**
     * Show the form for creating a new survey
     */
    public function create(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $dreamBuyers = DreamBuyer::where('business_id', $business->id)
            ->select('id', 'name', 'description')
            ->get();

        $defaultQuestions = CustdevSurvey::getDefaultQuestions();

        return Inertia::render($this->getViewPrefix($request).'/Custdev/Create', [
            'dreamBuyers' => $dreamBuyers,
            'defaultQuestions' => $defaultQuestions,
        ]);
    }

    /**
     * Store a newly created survey
     */
    public function store(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

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

        DB::beginTransaction();

        try {
            // Auto-create Dream Buyer for this survey
            $dreamBuyer = DreamBuyer::create([
                'business_id' => $business->id,
                'name' => $request->title.' - Ideal Mijoz',
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
                'collect_contact' => $request->boolean('collect_contact'),
                'anonymous' => $request->boolean('anonymous', true),
                'estimated_time' => $request->estimated_time ?? 5,
                'response_limit' => $request->response_limit,
                'expires_at' => $request->expires_at,
                'theme_color' => $request->theme_color ?? '#6366f1',
                'status' => 'draft',
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

            return redirect()->route($this->getRoutePrefix($request).'.show', $survey)
                ->with('success', 'So\'rovnoma muvaffaqiyatli yaratildi!');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Xatolik yuz berdi: '.$e->getMessage()]);
        }
    }

    /**
     * Display the specified survey
     */
    public function show(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $survey = CustdevSurvey::where('business_id', $business->id)
            ->where('id', $id)
            ->with(['questions', 'dreamBuyer'])
            ->withCount(['responses', 'completedResponses'])
            ->firstOrFail();

        // Get recent responses
        $recentResponses = $survey->responses()
            ->with('answers.question')
            ->completed()
            ->latest()
            ->take(10)
            ->get();

        // Get basic stats
        $totalResponses = $survey->responses()->count();
        $completedCount = $survey->completedResponses()->count();

        $stats = [
            'total_views' => $survey->views_count,
            'total_responses' => $totalResponses,
            'completed_responses' => $completedCount,
            'completion_rate' => $totalResponses > 0
                ? round(($completedCount / $totalResponses) * 100)
                : 0,
            'avg_time' => $survey->completedResponses()->avg('time_spent') ?? 0,
        ];

        return Inertia::render($this->getViewPrefix($request).'/Custdev/Show', [
            'survey' => $survey,
            'recentResponses' => $recentResponses,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for editing the survey
     */
    public function edit(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $survey = CustdevSurvey::where('business_id', $business->id)
            ->where('id', $id)
            ->with('questions')
            ->firstOrFail();

        $dreamBuyers = DreamBuyer::where('business_id', $business->id)
            ->select('id', 'name', 'description')
            ->get();

        $defaultQuestions = CustdevSurvey::getDefaultQuestions();

        return Inertia::render($this->getViewPrefix($request).'/Custdev/Create', [
            'survey' => $survey,
            'dreamBuyers' => $dreamBuyers,
            'defaultQuestions' => $defaultQuestions,
            'isEdit' => true,
        ]);
    }

    /**
     * Update the specified survey
     */
    public function update(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $survey = CustdevSurvey::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

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
            'questions' => 'nullable|array',
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
            $survey->update([
                'dream_buyer_id' => $request->dream_buyer_id,
                'title' => $request->title,
                'description' => $request->description,
                'welcome_message' => $request->welcome_message,
                'thank_you_message' => $request->thank_you_message,
                'collect_contact' => $request->boolean('collect_contact'),
                'anonymous' => $request->boolean('anonymous'),
                'estimated_time' => $request->estimated_time,
                'response_limit' => $request->response_limit,
                'expires_at' => $request->expires_at,
                'theme_color' => $request->theme_color ?? '#6366f1',
            ]);

            // Update questions if provided
            if ($request->has('questions')) {
                $existingIds = $survey->questions()->pluck('id')->toArray();
                $submittedIds = collect($request->questions)->pluck('id')->filter()->toArray();

                // Delete removed questions
                $toDelete = array_diff($existingIds, $submittedIds);
                if (! empty($toDelete)) {
                    CustdevQuestion::whereIn('id', $toDelete)->delete();
                }

                // Update or create questions
                foreach ($request->questions as $index => $questionData) {
                    if (! empty($questionData['id'])) {
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
                }
            }

            DB::commit();

            return redirect()->route($this->getRoutePrefix($request).'.show', $survey)
                ->with('success', 'So\'rovnoma muvaffaqiyatli yangilandi!');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Xatolik yuz berdi: '.$e->getMessage()]);
        }
    }

    /**
     * Toggle survey status (activate/pause)
     */
    public function toggleStatus(Request $request, $id)
    {
        $survey = $this->authorizeSurvey($id);

        $newStatus = $survey->status === 'active' ? 'paused' : 'active';
        $survey->update(['status' => $newStatus]);

        $message = $newStatus === 'active'
            ? 'So\'rovnoma faollashtirildi!'
            : 'So\'rovnoma to\'xtatildi!';

        return back()->with('success', $message);
    }

    /**
     * Delete the survey
     */
    public function destroy(Request $request, $id)
    {
        $survey = $this->authorizeSurvey($id);

        $survey->delete();

        return redirect()->route($this->getRoutePrefix($request).'.index')
            ->with('success', 'So\'rovnoma o\'chirildi!');
    }

    /**
     * Show survey results/analytics
     */
    public function results(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $survey = CustdevSurvey::where('business_id', $business->id)
            ->where('id', $id)
            ->with('questions')
            ->firstOrFail();

        // Get all completed responses with answers
        $responses = $survey->responses()
            ->with(['answers.question'])
            ->completed()
            ->latest()
            ->get();

        // Aggregate analytics for each question
        $questionAnalytics = [];

        foreach ($survey->questions as $question) {
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
                $ratings = $answers->pluck('rating_value')->filter();
                $analytics['average'] = $ratings->avg() ?? 0;
                $analytics['distribution'] = $ratings->countBy()->sortKeys()->toArray();
            } else {
                $analytics['answers'] = $answers->pluck('answer')->filter()->values()->toArray();
            }

            $questionAnalytics[] = $analytics;
        }

        // Overall stats
        $totalStarted = $survey->responses()->count();
        $stats = [
            'total_views' => $survey->views_count,
            'total_started' => $totalStarted,
            'total_completed' => $responses->count(),
            'completion_rate' => $totalStarted > 0
                ? round(($responses->count() / $totalStarted) * 100)
                : 0,
            'avg_time' => round($responses->avg('time_spent') ?? 0),
            'devices' => $responses->groupBy('device_type')->map->count(),
        ];

        return Inertia::render($this->getViewPrefix($request).'/Custdev/Results', [
            'survey' => $survey,
            'responses' => $responses,
            'questionAnalytics' => $questionAnalytics,
            'stats' => $stats,
        ]);
    }

    /**
     * Export responses to CSV (streaming)
     */
    public function export(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $survey = CustdevSurvey::where('business_id', $business->id)
            ->where('id', $id)
            ->with(['questions', 'responses.answers'])
            ->firstOrFail();

        $filename = 'custdev_'.$survey->slug.'_'.now()->format('Y-m-d').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($survey) {
            $file = fopen('php://output', 'w');

            // BOM for UTF-8
            fwrite($file, "\xEF\xBB\xBF");

            // Header row
            $header = ['ID', 'Ism', 'Email', 'Telefon', 'Hudud', 'Qurilma', 'Vaqt (soniya)', 'Status', 'Sana'];
            foreach ($survey->questions as $q) {
                $header[] = $q->question;
            }
            fputcsv($file, $header);

            // Data rows
            foreach ($survey->responses as $response) {
                $row = [
                    $response->id,
                    $response->respondent_name ?? 'Anonim',
                    $response->respondent_email ?? '',
                    $response->respondent_phone ?? '',
                    $response->respondent_region ?? '',
                    $response->device_type ?? '',
                    $response->time_spent ?? 0,
                    $response->status,
                    $response->created_at->format('Y-m-d H:i'),
                ];

                foreach ($survey->questions as $question) {
                    $answer = $response->answers->where('question_id', $question->id)->first();
                    if ($answer) {
                        if ($answer->selected_options) {
                            $row[] = implode(', ', $answer->selected_options);
                        } elseif ($answer->rating_value !== null) {
                            $row[] = $answer->rating_value;
                        } else {
                            $row[] = $answer->answer ?? '';
                        }
                    } else {
                        $row[] = '';
                    }
                }

                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Sync survey results with Dream Buyer profile
     */
    public function syncToDreamBuyer(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $survey = CustdevSurvey::where('business_id', $business->id)
            ->where('id', $id)
            ->with(['dreamBuyer', 'questions'])
            ->firstOrFail();

        if (! $survey->dreamBuyer) {
            return back()->with('error', 'Bu so\'rovnoma Ideal Mijoz profiliga bog\'lanmagan!');
        }

        $dreamBuyer = $survey->dreamBuyer;

        // Aggregate answers by category from completed responses
        $categoryData = [];

        foreach ($survey->questions as $question) {
            if (! $question->category || $question->category === 'custom') {
                continue;
            }

            $answers = CustdevAnswer::where('question_id', $question->id)
                ->whereHas('response', fn ($q) => $q->where('status', 'completed'))
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

            if (! empty($values)) {
                $categoryData[$question->category] = array_merge(
                    $categoryData[$question->category] ?? [],
                    $values
                );
            }
        }

        // Update Dream Buyer fields
        $updateData = [];
        $fieldMap = ['where_spend_time', 'info_sources', 'frustrations', 'dreams', 'fears'];

        foreach ($fieldMap as $field) {
            if (! empty($categoryData[$field])) {
                $existing = $dreamBuyer->$field ? explode("\n", $dreamBuyer->$field) : [];
                $merged = array_unique(array_merge($existing, $categoryData[$field]));
                $updateData[$field] = implode("\n", $merged);
            }
        }

        if (! empty($updateData)) {
            $dreamBuyer->update($updateData);
        }

        return back()->with('success', 'Ma\'lumotlar Ideal Mijoz profiliga sinxronlandi!');
    }
}
