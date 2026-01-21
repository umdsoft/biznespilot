<?php

namespace App\Jobs\HR;

use App\Events\HR\WorkAnniversary;
use App\Models\Business;
use App\Services\HR\HRAlertService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CheckWorkAnniversariesJob - Ish yilliklarini tekshirish
 *
 * Bu job har kuni ishga tushadi va:
 * - Bugungi ish yilliklarini topadi
 * - Yaqin keladigan yilliklarni (7 kun ichida) bildiradi
 * - Milestone yilliklar (1, 3, 5, 10 yil) uchun maxsus alert
 * - WorkAnniversary eventini dispatch qiladi
 */
class CheckWorkAnniversariesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 300;

    // Milestone yillar
    protected array $milestoneYears = [1, 2, 3, 5, 7, 10, 15, 20, 25];

    public function __construct(
        public ?string $businessId = null
    ) {}

    public function handle(HRAlertService $alertService): void
    {
        Log::info('CheckWorkAnniversariesJob boshlandi', [
            'business_id' => $this->businessId,
        ]);

        if ($this->businessId) {
            $this->processForBusiness($alertService, $this->businessId);
        } else {
            $this->processForAllBusinesses($alertService);
        }

        Log::info('CheckWorkAnniversariesJob yakunlandi');
    }

    protected function processForAllBusinesses(HRAlertService $alertService): void
    {
        $businesses = Business::where('status', 'active')->pluck('id');

        foreach ($businesses as $businessId) {
            try {
                $this->processForBusiness($alertService, $businessId);
            } catch (\Exception $e) {
                Log::error('Biznes work anniversaries xatosi', [
                    'business_id' => $businessId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function processForBusiness(HRAlertService $alertService, string $businessId): void
    {
        $business = Business::find($businessId);
        if (!$business) {
            return;
        }

        $today = Carbon::today();
        $upcomingDate = Carbon::today()->addDays(7);

        // Bugun va yaqin 7 kun ichidagi yilliklarni topish
        $employees = DB::table('business_user')
            ->join('users', 'business_user.user_id', '=', 'users.id')
            ->where('business_user.business_id', $businessId)
            ->whereNotNull('business_user.accepted_at')
            ->select([
                'users.id as user_id',
                'users.name',
                'users.email',
                'business_user.accepted_at as start_date',
            ])
            ->get();

        $todayAnniversaries = 0;
        $upcomingAnniversaries = 0;

        foreach ($employees as $employee) {
            $startDate = Carbon::parse($employee->start_date);
            $yearsWorked = $startDate->diffInYears($today);

            // Bugun yillikmi tekshirish
            if ($startDate->isSameDay($today, true)) {
                $this->handleTodayAnniversary($alertService, $business, $employee, $yearsWorked);
                $todayAnniversaries++;
            }
            // Yaqin 7 kun ichida yillik bormi
            elseif ($this->isAnniversaryInRange($startDate, $today, $upcomingDate)) {
                $this->handleUpcomingAnniversary($alertService, $business, $employee, $yearsWorked + 1, $startDate);
                $upcomingAnniversaries++;
            }
        }

        Log::info('Biznes work anniversaries yakunlandi', [
            'business_id' => $businessId,
            'employees_checked' => $employees->count(),
            'today_anniversaries' => $todayAnniversaries,
            'upcoming_anniversaries' => $upcomingAnniversaries,
        ]);
    }

    protected function isAnniversaryInRange(Carbon $startDate, Carbon $rangeStart, Carbon $rangeEnd): bool
    {
        // Bu yilgi anniversary sanasi
        $thisYearAnniversary = $startDate->copy()->year(Carbon::now()->year);

        // Agar bu yil o'tgan bo'lsa, keyingi yilni tekshirish
        if ($thisYearAnniversary->isPast()) {
            $thisYearAnniversary->addYear();
        }

        return $thisYearAnniversary->between($rangeStart, $rangeEnd);
    }

    protected function handleTodayAnniversary(
        HRAlertService $alertService,
        Business $business,
        object $employee,
        int $yearsWorked
    ): void {
        $isMilestone = in_array($yearsWorked, $this->milestoneYears);

        // Event dispatch
        event(new WorkAnniversary(
            \App\Models\User::find($employee->user_id),
            $business,
            $yearsWorked,
            Carbon::parse($employee->start_date)
        ));

        // Alert yaratish
        $priority = $isMilestone ? 'medium' : 'low';
        $title = $isMilestone
            ? "Muhim ish yilligi - {$yearsWorked} yil!"
            : "Ish yilligi - {$yearsWorked} yil";

        $message = "{$employee->name} bugun kompaniyada {$yearsWorked} yil ishlayotganini nishonlaydi!";

        $alertService->createAlert(
            $business,
            'work_anniversary',
            $title,
            $message,
            [
                'priority' => $priority,
                'user_id' => null, // HR va barcha
                'related_user_id' => $employee->user_id,
                'is_celebration' => true,
                'data' => [
                    'employee_id' => $employee->user_id,
                    'employee_name' => $employee->name,
                    'years_worked' => $yearsWorked,
                    'start_date' => $employee->start_date,
                    'is_milestone' => $isMilestone,
                    'milestone_message' => $this->getMilestoneMessage($yearsWorked),
                ],
            ]
        );

        // Hodimga ham alohida tabrik
        $alertService->createAlert(
            $business,
            'work_anniversary_personal',
            "Tabriklaymiz! {$yearsWorked} yillik ish yilligingiz!",
            "Siz kompaniyada {$yearsWorked} yil ishladingiz. Jamoamizning bir qismi ekanligingiz uchun minnatdormiz!",
            [
                'priority' => 'low',
                'user_id' => $employee->user_id,
                'is_celebration' => true,
                'data' => [
                    'years_worked' => $yearsWorked,
                    'is_milestone' => $isMilestone,
                ],
            ]
        );

        Log::info('Work anniversary celebrated', [
            'business_id' => $business->id,
            'employee_id' => $employee->user_id,
            'years' => $yearsWorked,
            'is_milestone' => $isMilestone,
        ]);
    }

    protected function handleUpcomingAnniversary(
        HRAlertService $alertService,
        Business $business,
        object $employee,
        int $upcomingYears,
        Carbon $startDate
    ): void {
        // Faqat milestone yilliklar uchun oldindan eslatma
        if (!in_array($upcomingYears, $this->milestoneYears)) {
            return;
        }

        $anniversaryDate = $startDate->copy()->addYears($upcomingYears);
        $daysUntil = Carbon::today()->diffInDays($anniversaryDate);

        // Faqat 7 kun qolganda eslatma
        if ($daysUntil !== 7) {
            return;
        }

        $alertService->createAlert(
            $business,
            'work_anniversary_upcoming',
            "Yaqinlashayotgan yillik - {$upcomingYears} yil",
            "{$employee->name} ning {$upcomingYears} yillik ish yilligi 1 hafta ichida",
            [
                'priority' => 'low',
                'user_id' => null, // HR
                'related_user_id' => $employee->user_id,
                'data' => [
                    'employee_id' => $employee->user_id,
                    'employee_name' => $employee->name,
                    'upcoming_years' => $upcomingYears,
                    'anniversary_date' => $anniversaryDate->format('Y-m-d'),
                    'days_until' => $daysUntil,
                ],
            ]
        );
    }

    protected function getMilestoneMessage(int $years): ?string
    {
        return match($years) {
            1 => "Birinchi yil muvaffaqiyatli yakunlandi!",
            2 => "Ikki yillik tajriba!",
            3 => "Uch yillik sadoqat!",
            5 => "Besh yillik fidoyilik - Bronza yubiley!",
            7 => "Yetti yillik ishonch!",
            10 => "O'n yillik muvaffaqiyat - Kumush yubiley!",
            15 => "O'n besh yillik tajriba - Kristall yubiley!",
            20 => "Yigirma yillik sadoqat - Oltin yubiley!",
            25 => "Yigirma besh yillik xizmat - Platina yubiley!",
            default => null,
        };
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CheckWorkAnniversariesJob muvaffaqiyatsiz', [
            'business_id' => $this->businessId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
