<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\EmployeeOnboardingPlan;
use App\Models\EmployeeOnboardingTask;
use App\Models\User;
use App\Services\HR\OnboardingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Onboarding API Controller
 *
 * Yangi hodimlar onboarding jarayoni uchun API.
 * 30-60-90 kun metodologiyasiga asoslangan.
 */
class OnboardingController extends Controller
{
    public function __construct(
        protected OnboardingService $onboardingService
    ) {}

    /**
     * Barcha onboarding rejalar ro'yxati
     */
    public function index(Request $request, string $businessId): JsonResponse
    {
        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        $perPage = $request->input('per_page', 20);
        $status = $request->input('status'); // active, completed, paused

        $query = EmployeeOnboardingPlan::where('business_id', $businessId)
            ->with(['user:id,name,email', 'mentor:id,name', 'manager:id,name'])
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        $plans = $query->paginate($perPage);

        $plans->getCollection()->transform(function ($plan) {
            return [
                'id' => $plan->id,
                'user' => $plan->user ? [
                    'id' => $plan->user->id,
                    'name' => $plan->user->name,
                    'email' => $plan->user->email,
                ] : null,
                'mentor' => $plan->mentor ? [
                    'id' => $plan->mentor->id,
                    'name' => $plan->mentor->name,
                ] : null,
                'manager' => $plan->manager ? [
                    'id' => $plan->manager->id,
                    'name' => $plan->manager->name,
                ] : null,
                'status' => $plan->status,
                'status_label' => $this->getStatusLabel($plan->status),
                'progress' => $plan->progress ?? 0,
                'start_date' => $plan->start_date?->format('d.m.Y'),
                'expected_end_date' => $plan->expected_end_date?->format('d.m.Y'),
                'days_elapsed' => $plan->start_date ? $plan->start_date->diffInDays(now()) : 0,
                'current_phase' => $this->getCurrentPhase($plan),
                'milestones' => [
                    'day_30' => [
                        'completed' => $plan->day_30_completed,
                        'score' => $plan->day_30_score,
                        'completed_at' => $plan->day_30_completed_at?->format('d.m.Y'),
                    ],
                    'day_60' => [
                        'completed' => $plan->day_60_completed,
                        'score' => $plan->day_60_score,
                        'completed_at' => $plan->day_60_completed_at?->format('d.m.Y'),
                    ],
                    'day_90' => [
                        'completed' => $plan->day_90_completed,
                        'score' => $plan->day_90_score,
                        'completed_at' => $plan->day_90_completed_at?->format('d.m.Y'),
                    ],
                ],
                'created_at' => $plan->created_at->format('d.m.Y'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $plans,
        ]);
    }

    /**
     * Bitta onboarding rejasi tafsilotlari
     */
    public function show(Request $request, string $businessId, string $planId): JsonResponse
    {
        $plan = EmployeeOnboardingPlan::where('business_id', $businessId)
            ->where('id', $planId)
            ->with(['user', 'mentor', 'manager', 'tasks'])
            ->first();

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Onboarding reja topilmadi',
            ], 404);
        }

        // Vazifalarni bosqichlarga ajratish
        $tasksByPhase = $plan->tasks->groupBy('phase');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $plan->id,
                'user' => $plan->user ? [
                    'id' => $plan->user->id,
                    'name' => $plan->user->name,
                    'email' => $plan->user->email,
                ] : null,
                'mentor' => $plan->mentor ? [
                    'id' => $plan->mentor->id,
                    'name' => $plan->mentor->name,
                ] : null,
                'manager' => $plan->manager ? [
                    'id' => $plan->manager->id,
                    'name' => $plan->manager->name,
                ] : null,
                'status' => $plan->status,
                'status_label' => $this->getStatusLabel($plan->status),
                'progress' => $plan->progress ?? 0,
                'start_date' => $plan->start_date?->format('d.m.Y'),
                'expected_end_date' => $plan->expected_end_date?->format('d.m.Y'),
                'days_elapsed' => $plan->start_date ? $plan->start_date->diffInDays(now()) : 0,
                'current_phase' => $this->getCurrentPhase($plan),
                'milestones' => [
                    'day_30' => [
                        'completed' => $plan->day_30_completed,
                        'score' => $plan->day_30_score,
                        'completed_at' => $plan->day_30_completed_at?->format('d.m.Y'),
                    ],
                    'day_60' => [
                        'completed' => $plan->day_60_completed,
                        'score' => $plan->day_60_score,
                        'completed_at' => $plan->day_60_completed_at?->format('d.m.Y'),
                    ],
                    'day_90' => [
                        'completed' => $plan->day_90_completed,
                        'score' => $plan->day_90_score,
                        'completed_at' => $plan->day_90_completed_at?->format('d.m.Y'),
                    ],
                ],
                'feedback' => [
                    'mentor' => $plan->mentor_feedback,
                    'manager' => $plan->manager_feedback,
                    'employee' => $plan->employee_feedback,
                ],
                'tasks_by_phase' => [
                    'day_30' => $this->formatTasks($tasksByPhase->get('day_30', collect())),
                    'day_60' => $this->formatTasks($tasksByPhase->get('day_60', collect())),
                    'day_90' => $this->formatTasks($tasksByPhase->get('day_90', collect())),
                ],
                'final_notes' => $plan->final_notes,
                'created_at' => $plan->created_at->format('d.m.Y H:i'),
            ],
        ]);
    }

    /**
     * Yangi onboarding reja yaratish
     */
    public function store(Request $request, string $businessId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'mentor_id' => 'nullable|exists:users,id',
            'manager_id' => 'nullable|exists:users,id',
            'start_date' => 'required|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => "Ma'lumotlar noto'g'ri",
                'errors' => $validator->errors(),
            ], 422);
        }

        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        // Mavjud faol reja borligini tekshirish
        $existingPlan = EmployeeOnboardingPlan::where('business_id', $businessId)
            ->where('user_id', $request->user_id)
            ->where('status', EmployeeOnboardingPlan::STATUS_ACTIVE)
            ->exists();

        if ($existingPlan) {
            return response()->json([
                'success' => false,
                'message' => 'Bu hodim uchun faol onboarding reja mavjud',
            ], 422);
        }

        $startDate = Carbon::parse($request->start_date);

        $plan = EmployeeOnboardingPlan::create([
            'business_id' => $businessId,
            'user_id' => $request->user_id,
            'mentor_id' => $request->mentor_id,
            'manager_id' => $request->manager_id,
            'start_date' => $startDate,
            'expected_end_date' => $startDate->copy()->addDays(90),
            'status' => EmployeeOnboardingPlan::STATUS_ACTIVE,
            'progress' => 0,
            'final_notes' => $request->notes,
        ]);

        // Standart vazifalarni yaratish
        $this->createDefaultTasks($plan);

        return response()->json([
            'success' => true,
            'message' => 'Onboarding reja muvaffaqiyatli yaratildi',
            'data' => [
                'id' => $plan->id,
            ],
        ], 201);
    }

    /**
     * Onboarding rejani yangilash
     */
    public function update(Request $request, string $businessId, string $planId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mentor_id' => 'nullable|exists:users,id',
            'manager_id' => 'nullable|exists:users,id',
            'status' => 'nullable|in:active,completed,paused',
            'final_notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => "Ma'lumotlar noto'g'ri",
                'errors' => $validator->errors(),
            ], 422);
        }

        $plan = EmployeeOnboardingPlan::where('business_id', $businessId)
            ->where('id', $planId)
            ->first();

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Onboarding reja topilmadi',
            ], 404);
        }

        $plan->update($request->only(['mentor_id', 'manager_id', 'status', 'final_notes']));

        return response()->json([
            'success' => true,
            'message' => 'Onboarding reja yangilandi',
        ]);
    }

    /**
     * Vazifa statusini yangilash
     */
    public function updateTaskStatus(Request $request, string $businessId, string $planId, string $taskId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,in_progress,completed,skipped',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => "Ma'lumotlar noto'g'ri",
                'errors' => $validator->errors(),
            ], 422);
        }

        $task = EmployeeOnboardingTask::where('id', $taskId)
            ->whereHas('plan', function ($q) use ($businessId, $planId) {
                $q->where('business_id', $businessId)
                    ->where('id', $planId);
            })
            ->first();

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Vazifa topilmadi',
            ], 404);
        }

        $task->update([
            'status' => $request->status,
            'completion_notes' => $request->notes,
            'completed_at' => $request->status === 'completed' ? now() : null,
            'completed_by' => $request->status === 'completed' ? auth()->id() : null,
        ]);

        // Reja progressini yangilash
        $task->plan->updateProgress();

        return response()->json([
            'success' => true,
            'message' => 'Vazifa statusi yangilandi',
            'data' => [
                'task_status' => $task->status,
                'plan_progress' => $task->plan->progress,
            ],
        ]);
    }

    /**
     * Vazifa qo'shish
     */
    public function addTask(Request $request, string $businessId, string $planId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'phase' => 'required|in:day_30,day_60,day_90',
            'due_date' => 'nullable|date',
            'assigned_role' => 'required|in:employee,mentor,manager,hr',
            'priority' => 'nullable|in:low,medium,high',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => "Ma'lumotlar noto'g'ri",
                'errors' => $validator->errors(),
            ], 422);
        }

        $plan = EmployeeOnboardingPlan::where('business_id', $businessId)
            ->where('id', $planId)
            ->first();

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Onboarding reja topilmadi',
            ], 404);
        }

        // Eng katta order ni topish
        $maxOrder = EmployeeOnboardingTask::where('plan_id', $planId)
            ->where('phase', $request->phase)
            ->max('order') ?? 0;

        $task = EmployeeOnboardingTask::create([
            'plan_id' => $planId,
            'title' => $request->title,
            'description' => $request->description,
            'phase' => $request->phase,
            'due_date' => $request->due_date,
            'assigned_role' => $request->assigned_role,
            'status' => EmployeeOnboardingTask::STATUS_PENDING,
            'order' => $maxOrder + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vazifa qo\'shildi',
            'data' => [
                'id' => $task->id,
                'title' => $task->title,
            ],
        ], 201);
    }

    /**
     * Milestone yakunlash
     */
    public function completeMilestone(Request $request, string $businessId, string $planId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'milestone' => 'required|in:30,60,90',
            'score' => 'required|numeric|min:0|max:100',
            'feedback' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => "Ma'lumotlar noto'g'ri",
                'errors' => $validator->errors(),
            ], 422);
        }

        $plan = EmployeeOnboardingPlan::where('business_id', $businessId)
            ->where('id', $planId)
            ->first();

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Onboarding reja topilmadi',
            ], 404);
        }

        $milestone = $request->milestone;
        $method = "completeDay{$milestone}";

        if (method_exists($plan, $method)) {
            $plan->$method($request->score, $request->feedback);
        }

        return response()->json([
            'success' => true,
            'message' => "{$milestone}-kun bosqichi yakunlandi",
        ]);
    }

    /**
     * Onboarding statistikasi
     */
    public function statistics(Request $request, string $businessId): JsonResponse
    {
        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        // Status bo'yicha taqsimot
        $statusDistribution = EmployeeOnboardingPlan::where('business_id', $businessId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // O'rtacha bajarilish vaqti
        $avgCompletionDays = EmployeeOnboardingPlan::where('business_id', $businessId)
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->selectRaw('AVG(DATEDIFF(completed_at, start_date)) as avg_days')
            ->value('avg_days') ?? 0;

        // O'rtacha milestone ballari
        $avgScores = EmployeeOnboardingPlan::where('business_id', $businessId)
            ->selectRaw('
                AVG(day_30_score) as day_30_avg,
                AVG(day_60_score) as day_60_avg,
                AVG(day_90_score) as day_90_avg
            ')
            ->first();

        // Kechikkan vazifalar soni
        $overdueTasksCount = EmployeeOnboardingTask::whereHas('plan', function ($q) use ($businessId) {
            $q->where('business_id', $businessId)
                ->where('status', 'active');
        })
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'status_distribution' => [
                    'active' => [
                        'count' => $statusDistribution['active'] ?? 0,
                        'label' => 'Faol',
                    ],
                    'completed' => [
                        'count' => $statusDistribution['completed'] ?? 0,
                        'label' => 'Yakunlangan',
                    ],
                    'paused' => [
                        'count' => $statusDistribution['paused'] ?? 0,
                        'label' => "To'xtatilgan",
                    ],
                ],
                'avg_completion_days' => round($avgCompletionDays),
                'milestone_avg_scores' => [
                    'day_30' => round($avgScores->day_30_avg ?? 0, 1),
                    'day_60' => round($avgScores->day_60_avg ?? 0, 1),
                    'day_90' => round($avgScores->day_90_avg ?? 0, 1),
                ],
                'overdue_tasks_count' => $overdueTasksCount,
            ],
        ]);
    }

    /**
     * Status label
     */
    protected function getStatusLabel(string $status): string
    {
        return match ($status) {
            'active' => 'Faol',
            'completed' => 'Yakunlangan',
            'paused' => "To'xtatilgan",
            default => $status,
        };
    }

    /**
     * Joriy bosqich
     */
    protected function getCurrentPhase(EmployeeOnboardingPlan $plan): string
    {
        if (!$plan->start_date) {
            return 'day_30';
        }

        $daysElapsed = $plan->start_date->diffInDays(now());

        return match (true) {
            $daysElapsed <= 30 => 'day_30',
            $daysElapsed <= 60 => 'day_60',
            default => 'day_90',
        };
    }

    /**
     * Vazifalarni formatlash
     */
    protected function formatTasks($tasks): array
    {
        return $tasks->map(fn($task) => [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'status_label' => $this->getTaskStatusLabel($task->status),
            'assigned_role' => $task->assigned_role,
            'assigned_role_label' => $this->getAssignedToLabel($task->assigned_role),
            'due_date' => $task->due_date?->format('d.m.Y'),
            'is_overdue' => $task->due_date && $task->due_date->isPast() && $task->status === 'pending',
            'completed_at' => $task->completed_at?->format('d.m.Y H:i'),
            'order' => $task->order,
        ])->sortBy('order')->values()->toArray();
    }

    /**
     * Task status label
     */
    protected function getTaskStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Kutilmoqda',
            'in_progress' => 'Jarayonda',
            'completed' => 'Bajarildi',
            'skipped' => "O'tkazib yuborildi",
            default => $status,
        };
    }

    /**
     * Assigned to label
     */
    protected function getAssignedToLabel(string $assignedTo): string
    {
        return match ($assignedTo) {
            'employee' => 'Hodim',
            'mentor' => 'Mentor',
            'manager' => 'Rahbar',
            'hr' => 'HR',
            default => $assignedTo,
        };
    }

    /**
     * Standart vazifalarni yaratish
     */
    protected function createDefaultTasks(EmployeeOnboardingPlan $plan): void
    {
        $defaultTasks = [
            // Day 30 tasks
            [
                'title' => "Kompaniya madaniyati va qadriyatlarini o'rganish",
                'phase' => 'day_30',
                'assigned_role' => 'employee',
                'order' => 1,
            ],
            [
                'title' => "Jamoa a'zolari bilan tanishish",
                'phase' => 'day_30',
                'assigned_role' => 'employee',
                'order' => 2,
            ],
            [
                'title' => "Ish jarayonlari va toollarni o'rganish",
                'phase' => 'day_30',
                'assigned_role' => 'employee',
                'order' => 3,
            ],
            [
                'title' => 'Birinchi haftalik 1-on-1 uchrashuvni o\'tkazish',
                'phase' => 'day_30',
                'assigned_role' => 'manager',
                'order' => 4,
            ],
            [
                'title' => 'Mentor bilan tanishuv uchrashuvini o\'tkazish',
                'phase' => 'day_30',
                'assigned_role' => 'mentor',
                'order' => 5,
            ],

            // Day 60 tasks
            [
                'title' => 'Birinchi mustaqil loyihani yakunlash',
                'phase' => 'day_60',
                'assigned_role' => 'employee',
                'order' => 1,
            ],
            [
                'title' => '60 kunlik feedback uchrashuvini o\'tkazish',
                'phase' => 'day_60',
                'assigned_role' => 'manager',
                'order' => 2,
            ],
            [
                'title' => 'Jamoaviy loyihada ishtirok etish',
                'phase' => 'day_60',
                'assigned_role' => 'employee',
                'order' => 3,
            ],

            // Day 90 tasks
            [
                'title' => 'Rivojlanish rejasini tuzish',
                'phase' => 'day_90',
                'assigned_role' => 'employee',
                'order' => 1,
            ],
            [
                'title' => '90 kunlik baholash uchrashuvini o\'tkazish',
                'phase' => 'day_90',
                'assigned_role' => 'manager',
                'order' => 2,
            ],
            [
                'title' => 'Probation davrini yakunlash',
                'phase' => 'day_90',
                'assigned_role' => 'hr',
                'order' => 3,
            ],
        ];

        foreach ($defaultTasks as $task) {
            EmployeeOnboardingTask::create([
                'plan_id' => $plan->id,
                'status' => EmployeeOnboardingTask::STATUS_PENDING,
                ...$task,
            ]);
        }
    }
}
