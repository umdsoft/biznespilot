<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Business;
use App\Models\CustdevSurvey;
use App\Models\CustdevQuestion;
use App\Models\DreamBuyer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CustdevController extends Controller
{
    use HasCurrentBusiness;

    public function index()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        $surveys = CustdevSurvey::forBusiness($business->id)
            ->with(['questions', 'dreamBuyer'])
            ->withCount(['responses', 'completedResponses'])
            ->latest()
            ->get();

        return Inertia::render('Marketing/Custdev/Index', [
            'surveys' => $surveys,
        ]);
    }

    public function create()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        $dreamBuyers = DreamBuyer::where('business_id', $business->id)
            ->select('id', 'name', 'description')
            ->get();

        $defaultQuestions = CustdevSurvey::getDefaultQuestions();

        return Inertia::render('Marketing/Custdev/Create', [
            'dreamBuyers' => $dreamBuyers,
            'defaultQuestions' => $defaultQuestions,
        ]);
    }

    public function store(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
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
            $dreamBuyer = DreamBuyer::create([
                'business_id' => $business->id,
                'name' => $request->title . ' - Ideal Mijoz',
                'description' => 'CustDev so\'rovnomasi asosida avtomatik yaratilgan profil',
                'priority' => 'medium',
                'is_primary' => false,
            ]);

            $survey = CustdevSurvey::create([
                'business_id' => $business->id,
                'dream_buyer_id' => $dreamBuyer->id,
                'title' => $request->title,
                'description' => $request->description,
                'welcome_message' => $request->welcome_message,
                'thank_you_message' => $request->thank_you_message,
                'collect_contact' => $request->boolean('collect_contact'),
                'anonymous' => $request->boolean('anonymous'),
                'estimated_time' => $request->estimated_time,
                'response_limit' => $request->response_limit,
                'expires_at' => $request->expires_at,
                'theme_color' => $request->theme_color ?? 'purple',
                'status' => 'draft',
            ]);

            foreach ($request->questions as $order => $questionData) {
                CustdevQuestion::create([
                    'custdev_survey_id' => $survey->id,
                    'type' => $questionData['type'],
                    'question' => $questionData['question'],
                    'category' => $questionData['category'] ?? null,
                    'description' => $questionData['description'] ?? null,
                    'placeholder' => $questionData['placeholder'] ?? null,
                    'options' => $questionData['options'] ?? null,
                    'is_required' => $questionData['is_required'] ?? true,
                    'is_default' => $questionData['is_default'] ?? false,
                    'icon' => $questionData['icon'] ?? null,
                    'settings' => $questionData['settings'] ?? null,
                    'order' => $order,
                ]);
            }

            DB::commit();

            return redirect()->route('marketing.custdev.show', $survey)
                ->with('success', 'So\'rovnoma muvaffaqiyatli yaratildi!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Xatolik yuz berdi: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        $survey = CustdevSurvey::where('business_id', $business->id)
            ->where('id', $id)
            ->with(['questions', 'dreamBuyer', 'responses.answers'])
            ->withCount(['responses', 'completedResponses'])
            ->firstOrFail();

        return Inertia::render('Marketing/Custdev/Show', [
            'survey' => $survey,
        ]);
    }

    public function edit($id)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
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

        return Inertia::render('Marketing/Custdev/Create', [
            'survey' => $survey,
            'dreamBuyers' => $dreamBuyers,
            'defaultQuestions' => $defaultQuestions,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        $survey = CustdevSurvey::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'welcome_message' => 'nullable|string',
            'thank_you_message' => 'nullable|string',
            'collect_contact' => 'nullable|boolean',
            'anonymous' => 'nullable|boolean',
            'estimated_time' => 'nullable|integer|min:1|max:60',
            'theme_color' => 'nullable|string|max:20',
        ]);

        $survey->update([
            'title' => $request->title,
            'description' => $request->description,
            'welcome_message' => $request->welcome_message,
            'thank_you_message' => $request->thank_you_message,
            'collect_contact' => $request->boolean('collect_contact'),
            'anonymous' => $request->boolean('anonymous'),
            'estimated_time' => $request->estimated_time,
            'theme_color' => $request->theme_color,
        ]);

        return redirect()->route('marketing.custdev.show', $survey)
            ->with('success', 'So\'rovnoma yangilandi!');
    }

    public function destroy($id)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        $survey = CustdevSurvey::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $survey->delete();

        return redirect()->route('marketing.custdev.index')
            ->with('success', 'So\'rovnoma o\'chirildi!');
    }

    /**
     * Display survey results
     */
    public function results($id)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        $survey = CustdevSurvey::where('business_id', $business->id)
            ->where('id', $id)
            ->with(['questions', 'dreamBuyer', 'responses.answers'])
            ->firstOrFail();

        $responses = $survey->responses()
            ->with('answers')
            ->latest()
            ->get();

        return Inertia::render('Marketing/Custdev/Results', [
            'survey' => $survey,
            'responses' => $responses,
        ]);
    }

    /**
     * Export survey results
     */
    public function export($id)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        $survey = CustdevSurvey::where('business_id', $business->id)
            ->where('id', $id)
            ->with(['questions', 'responses.answers'])
            ->firstOrFail();

        // Generate CSV export
        $filename = 'custdev_' . $survey->slug . '_' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($survey) {
            $file = fopen('php://output', 'w');

            // Header row
            $header = ['Respondent', 'Email', 'Telefon', 'Hudud', 'Qurilma', 'Vaqt (soniya)', 'Status', 'Sana'];
            foreach ($survey->questions as $q) {
                $header[] = $q->question;
            }
            fputcsv($file, $header);

            // Data rows
            foreach ($survey->responses as $response) {
                $row = [
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
     * Sync responses to Dream Buyer profile
     */
    public function syncToDreamBuyer($id)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        $survey = CustdevSurvey::where('business_id', $business->id)
            ->where('id', $id)
            ->with(['dreamBuyer', 'questions', 'responses.answers'])
            ->firstOrFail();

        if (!$survey->dreamBuyer) {
            return back()->with('error', 'So\'rovnoma Ideal Mijoz profiliga bog\'lanmagan');
        }

        // Sync logic here - aggregate answers to dream buyer profile
        $dreamBuyer = $survey->dreamBuyer;
        $completedResponses = $survey->responses->where('status', 'completed');

        foreach ($survey->questions as $question) {
            $categoryMap = [
                'where_spend_time' => 'where_spend_time',
                'info_sources' => 'info_sources',
                'frustrations' => 'frustrations',
                'dreams' => 'dreams',
                'fears' => 'fears',
            ];

            if (isset($categoryMap[$question->category])) {
                $answers = [];
                foreach ($completedResponses as $response) {
                    $answer = $response->answers->where('question_id', $question->id)->first();
                    if ($answer && $answer->answer) {
                        $answers[] = $answer->answer;
                    }
                }

                if (!empty($answers)) {
                    $existing = $dreamBuyer->{$categoryMap[$question->category]} ?? [];
                    $merged = array_unique(array_merge($existing, $answers));
                    $dreamBuyer->{$categoryMap[$question->category]} = $merged;
                }
            }
        }

        $dreamBuyer->save();

        return back()->with('success', 'Ma\'lumotlar Ideal Mijoz profiliga sinxronlandi!');
    }

    /**
     * Toggle survey status
     */
    public function toggleStatus($id)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        $survey = CustdevSurvey::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $survey->status = $survey->status === 'active' ? 'paused' : 'active';
        $survey->save();

        return back()->with('success', 'Status yangilandi!');
    }
}
