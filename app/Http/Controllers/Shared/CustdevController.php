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

        if (str_contains($path, 'hr')) {
            return 'hr';
        }

        return 'business';
    }

    /**
     * Get view prefix based on panel type
     */
    private function getViewPrefix(Request $request): string
    {
        return match ($this->getPanelType($request)) {
            'marketing' => 'Marketing',
            'hr' => 'HR',
            default => 'Business',
        };
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

        $panelType = $this->getPanelType($request);

        $surveys = CustdevSurvey::forBusiness($business->id)
            ->where('panel_type', $panelType)
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

        $panelType = $this->getPanelType($request);
        $defaultQuestions = $panelType === 'hr'
            ? $this->getHRDefaultQuestions()
            : CustdevSurvey::getDefaultQuestions();

        return Inertia::render($this->getViewPrefix($request).'/Custdev/Create', [
            'dreamBuyers' => $dreamBuyers,
            'defaultQuestions' => $defaultQuestions,
        ]);
    }

    /**
     * HR uchun default savollar — psixologik va professional baholash anketa.
     *
     * Metodologiya: Industrial-Organizational Psychology asosida.
     * Kategoriyalar: motivatsiya, mas'uliyat, stress, jamoa, o'sish, halollik, yetakchilik
     * Har bir javob nomzodning ishlash qobiliyatini psixologik jihatdan baholashga yordam beradi.
     */
    private function getHRDefaultQuestions(): array
    {
        return [
            // ===== ASOSIY MA'LUMOTLAR =====
            [
                'type' => 'text',
                'category' => 'personal_info',
                'question' => "To'liq ismingiz",
                'is_required' => true,
            ],
            [
                'type' => 'phone',
                'category' => 'personal_info',
                'question' => 'Telefon raqamingiz',
                'placeholder' => '+998 90 123 45 67',
                'is_required' => true,
            ],
            [
                'type' => 'select',
                'category' => 'experience',
                'question' => 'Siz qancha vaqt davomida shu sohada ishlayapsiz?',
                'options' => ['1 yildan kam', '1-3 yil', '3-5 yil', '5-10 yil', '10 yildan ko\'p'],
                'is_required' => true,
            ],

            // ===== OILAVIY HOLAT (barqarorlik va mas'uliyat ko'rsatkichi) =====
            [
                'type' => 'select',
                'category' => 'family',
                'question' => 'Oilaviy holatingiz',
                'options' => ['Turmush qurmagan', 'Turmush qurgan', 'Ajrashgan', 'Javob bermayman'],
                'description' => 'Oilaviy barqarorlik — ish o\'rnida uzoq ishlash ehtimolini ko\'rsatadi.',
                'is_required' => true,
            ],
            [
                'type' => 'select',
                'category' => 'family',
                'question' => 'Farzandlaringiz bormi?',
                'options' => ['Yo\'q', '1 ta', '2 ta', '3 va undan ko\'p', 'Javob bermayman'],
                'description' => 'Farzandli xodimlar ko\'pincha barqarorroq va mas\'uliyatliroq bo\'ladi, lekin vaqt chegaralari bo\'lishi mumkin.',
                'is_required' => false,
            ],
            [
                'type' => 'select',
                'category' => 'family',
                'question' => 'Oilangiz sizning ish joyingizni o\'zgartirishingizni qo\'llab-quvvatlayaptimi?',
                'options' => [
                    'Ha, to\'liq qo\'llab-quvvatlaydi',
                    'Ha, lekin ba\'zi shartlar bilan (masalan, ish vaqti)',
                    'Hali muhokama qilmadik',
                    'Javob bermayman',
                ],
                'description' => 'Oilaviy qo\'llab-quvvatlash — ishga moslashish va uzoq muddatli ishlash kafolati.',
                'is_required' => false,
            ],

            // ===== MOTIVATSIYA VA MAQSAD (nima uchun ishlaydi?) =====
            [
                'type' => 'textarea',
                'category' => 'motivation',
                'question' => 'Nima uchun hozirgi ish joyingizni tark etmoqchisiz yoki yangi ish qidiryapsiz?',
                'description' => 'Bu savol nomzodning haqiqiy motivatsiyasini ochib beradi — pul, o\'sish, muhit yoki muammo.',
                'is_required' => true,
            ],
            [
                'type' => 'textarea',
                'category' => 'motivation',
                'question' => '5 yildan keyin o\'zingizni qayerda ko\'rasiz? Kasbiy maqsadingiz nima?',
                'description' => 'Uzoq muddatli fikrlash qobiliyati va ambitsiya darajasini ko\'rsatadi.',
                'is_required' => true,
            ],

            // ===== MAS\'ULIYAT VA ISHONCHLILK =====
            [
                'type' => 'textarea',
                'category' => 'responsibility',
                'question' => 'Ishda qiyin vazifa berilganda va muddat yaqin bo\'lganda siz odatda nima qilasiz? Haqiqiy misolda tushuntiring.',
                'description' => 'Stress ostida ishlash qobiliyati, mas\'uliyatni qabul qilish va hal qilish yondashuvini ko\'rsatadi.',
                'is_required' => true,
            ],
            [
                'type' => 'select',
                'category' => 'responsibility',
                'question' => 'Agar ish vaqtida xatolik qilsangiz, odatda nima qilasiz?',
                'options' => [
                    'Darhol rahbarga aytaman va tuzatish yo\'lini taklif qilaman',
                    'O\'zim tuzatishga harakat qilaman, kerak bo\'lsa yordam so\'rayman',
                    'Boshqalar sezmaguncha o\'zim tuzataman',
                    'Bu xatolik ekanini bilmasam, e\'tibor bermayman',
                ],
                'description' => 'Halollik va mas\'uliyat darajasi — eng muhim ko\'rsatkich.',
                'is_required' => true,
            ],

            // ===== STRESS VA EMOTSIONAL BARQARORLIK =====
            [
                'type' => 'scale',
                'category' => 'stress_resilience',
                'question' => 'Stress ostida ishlash qobiliyatingizni 1 dan 10 gacha baholang',
                'description' => '1 = juda qiyin, 10 = stress menga ta\'sir qilmaydi. O\'zini baholash darajasi ham muhim.',
                'is_required' => true,
            ],
            [
                'type' => 'textarea',
                'category' => 'stress_resilience',
                'question' => 'Hayotingizdagi eng qiyin professional vaziyatni tasvirlab bering. Qanday hal qildingiz?',
                'description' => 'Real tajriba — muammoni hal qilish qobiliyati va emotsional yetuklik ko\'rsatkichi.',
                'is_required' => true,
            ],

            // ===== JAMOAVIY ISH VA MUNOSABATLAR =====
            [
                'type' => 'select',
                'category' => 'teamwork',
                'question' => 'Jamoada ishlashda sizga eng qiyin nima?',
                'options' => [
                    'Boshqalar bilan fikr kelishmovchiligi',
                    'Boshqalarning ishini kutish',
                    'O\'z fikrimi himoya qilish',
                    'Boshqalarning ishiga ishonish',
                    'Menga qiyin narsa yo\'q, jamoada yaxshi ishlayman',
                ],
                'description' => 'Jamoaviy dinamikadagi zaif tomonni aniqlaydi.',
                'is_required' => true,
            ],
            [
                'type' => 'textarea',
                'category' => 'teamwork',
                'question' => 'Hamkasbingiz bilan kelishmovchilik bo\'lganda odatda qanday hal qilasiz? Misol keltiring.',
                'description' => 'Konflikt hal qilish uslubi — muzokarachilik yoki konfrontatsiya.',
                'is_required' => true,
            ],

            // ===== O\'Z-O\'ZINI BAHOLASH VA O\'SISH =====
            [
                'type' => 'textarea',
                'category' => 'self_awareness',
                'question' => 'O\'zingizning eng katta kamchiligingiz nima deb hisoblaysiz? Bu kamchilikni bartaraf etish uchun nima qilyapsiz?',
                'description' => 'O\'z-o\'zini tanqid qilish qobiliyati — emotsional intellektning asosiy ko\'rsatkichi.',
                'is_required' => true,
            ],
            [
                'type' => 'rating',
                'category' => 'self_awareness',
                'question' => 'O\'z professional darajangizni 1 dan 5 gacha baholang (1=boshlang\'ich, 5=ekspert)',
                'description' => 'O\'z kuchini to\'g\'ri baholash — adekvat yoki shishgan ego.',
                'is_required' => true,
            ],

            // ===== YETAKCHILIK VA TASHABBUSKORLIK =====
            [
                'type' => 'select',
                'category' => 'leadership',
                'question' => 'Yangi loyiha yoki vazifa berilganda odatda nima qilasiz?',
                'options' => [
                    'Darhol reja tuzaman va boshlаyman',
                    'Avval batafsil o\'rganaman, keyin boshlаyman',
                    'Rahbardan aniq ko\'rsatma kutaman',
                    'Jamoadan maslahat so\'rayman va birga boshlаymiz',
                ],
                'description' => 'Tashabbuskorlik va mustaqillik darajasi — "thinker" yoki "doer" ekanini ko\'rsatadi.',
                'is_required' => true,
            ],
            [
                'type' => 'textarea',
                'category' => 'leadership',
                'question' => 'Ishda o\'zingiz tashabbuskorlik ko\'rsatgan va natija bergan biror voqeani aytib bering.',
                'description' => 'Real misol — so\'z emas, ish bilan isbotlash.',
                'is_required' => false,
            ],

            // ===== QADRIYATLAR VA HALOLLIK =====
            [
                'type' => 'select',
                'category' => 'values',
                'question' => 'Sizning uchun ishda eng muhim narsa nima?',
                'options' => [
                    'Yaxshi ish haqi va moddiy barqarorlik',
                    'Kasbiy o\'sish va yangi narsalar o\'rganish',
                    'Yaxshi jamoa va ish muhiti',
                    'Erkinlik va mustaqil ishlash imkoniyati',
                    'Kompaniyaning missiyasi va maqsadlarga ishonch',
                ],
                'description' => 'Asosiy qadriyat — kompaniya madaniyatiga moslikni aniqlaydi.',
                'is_required' => true,
            ],
            [
                'type' => 'select',
                'category' => 'values',
                'question' => 'Agar rahbaringiz noto\'g\'ri qaror qilayotganini bilsangiz, nima qilasiz?',
                'options' => [
                    'Ochiqchasiga fikrimni aytaman, hatto rahbar bo\'lsa ham',
                    'Yakkama-yakka suhbatda ehtiyotkorlik bilan aytaman',
                    'Jamoada muhokama qilishni taklif qilaman',
                    'Rahbar biladi, aralashmayman',
                ],
                'description' => 'Halollik, jasurlik va munosabat uslubi — kompaniya uchun muhim.',
                'is_required' => true,
            ],

            // ===== AMALIY MA'LUMOTLAR =====
            [
                'type' => 'select',
                'category' => 'availability',
                'question' => 'Qachondan ishga kirishingiz mumkin?',
                'options' => ['Darhol', '1 hafta ichida', '2 hafta ichida', '1 oy ichida', 'Kelishiladi'],
                'is_required' => true,
            ],
            [
                'type' => 'select',
                'category' => 'work_format',
                'question' => 'Qaysi ish formatini afzal ko\'rasiz?',
                'options' => ['Ofisda', 'Masofadan (remote)', 'Aralash (hybrid)', 'Farqi yo\'q'],
                'is_required' => true,
            ],
        ];
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
            'questions.*.type' => 'required|string|in:text,phone,textarea,select,multiselect,rating,scale',
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

        $panelType = $this->getPanelType($request);

        try {
            // HR panelda DreamBuyer yaratilmaydi
            $dreamBuyerId = null;
            if ($panelType !== 'hr') {
                $dreamBuyer = DreamBuyer::create([
                    'business_id' => $business->id,
                    'name' => $request->title.' - Ideal Mijoz',
                    'description' => 'CustDev so\'rovnomasi asosida avtomatik yaratilgan profil',
                    'priority' => 'medium',
                    'is_primary' => false,
                ]);
                $dreamBuyerId = $dreamBuyer->id;
            }

            // Create survey
            $survey = CustdevSurvey::create([
                'business_id' => $business->id,
                'panel_type' => $panelType,
                'dream_buyer_id' => $dreamBuyerId,
                'title' => $request->title,
                'description' => $request->description,
                'welcome_message' => $request->welcome_message ?? ($panelType === 'hr'
                    ? 'Assalomu alaykum! Ushbu anketa sizning professional ko\'nikmalaringiz va shaxsiy xususiyatlaringizni baholash uchun mo\'ljallangan. Iltimos, har bir savolga ochiq va halol javob bering.'
                    : 'Salom! Ushbu qisqa so\'rovnomani to\'ldirishingizni so\'raymiz. Sizning fikringiz biz uchun juda muhim.'),
                'thank_you_message' => $request->thank_you_message ?? ($panelType === 'hr'
                    ? 'Javoblaringiz uchun katta rahmat! Biz sizning arizangizni ko\'rib chiqamiz va tez orada siz bilan bog\'lanamiz.'
                    : 'Rahmat! Sizning javoblaringiz biz uchun juda qimmatli. Yaxshi kun tilaymiz!'),
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

        $panelType = $this->getPanelType($request);
        $defaultQuestions = $panelType === 'hr'
            ? $this->getHRDefaultQuestions()
            : CustdevSurvey::getDefaultQuestions();

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
            'questions.*.type' => 'required|string|in:text,phone,textarea,select,multiselect,rating,scale',
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
     * Export individual response as PDF
     */
    public function exportResponsePdf(Request $request, $id, $responseId)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $survey = CustdevSurvey::where('business_id', $business->id)
            ->where('id', $id)
            ->with('questions')
            ->firstOrFail();

        $response = $survey->responses()
            ->with(['answers.question'])
            ->where('id', $responseId)
            ->firstOrFail();

        $html = view('pdf.survey-response', [
            'survey' => $survey,
            'response' => $response,
            'business' => $business,
        ])->render();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        $pdf->setPaper('a4', 'portrait');

        $name = \Illuminate\Support\Str::slug($response->respondent_name ?: 'anonim');
        $filename = "{$name}_{$survey->slug}_" . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
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
