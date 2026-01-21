<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Camera Attendance Webhook Controller
 *
 * Kamera tizimidan kelgan davomat ma'lumotlarini qabul qilish.
 * Facial recognition, badge scanner yoki boshqa qurilmalar
 * bu endpoint'ga webhook yuboradi.
 */
class CameraAttendanceController extends Controller
{
    /**
     * Kameradan check-in/check-out webhook qabul qilish
     *
     * POST /api/hr/attendance/camera-webhook
     *
     * Body:
     * {
     *   "business_id": "uuid",
     *   "employee_code": "EMP001" | null,
     *   "badge_id": "BADGE123" | null,
     *   "face_id": "FACE_UUID" | null,
     *   "event_type": "check_in" | "check_out",
     *   "device_id": "CAM001",
     *   "device_location": "Main Entrance",
     *   "timestamp": "2024-01-21T09:00:00Z",
     *   "confidence": 0.95,
     *   "photo_url": "https://..." | null
     * }
     */
    public function webhook(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|uuid|exists:businesses,id',
            'employee_code' => 'nullable|string',
            'badge_id' => 'nullable|string',
            'face_id' => 'nullable|string',
            'event_type' => 'required|in:check_in,check_out',
            'device_id' => 'required|string|max:50',
            'device_location' => 'nullable|string|max:255',
            'timestamp' => 'nullable|date',
            'confidence' => 'nullable|numeric|min:0|max:1',
            'photo_url' => 'nullable|url',
            'api_key' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning('CameraAttendance: Validation failed', [
                'errors' => $validator->errors()->toArray(),
                'input' => $request->except(['api_key']),
            ]);

            return response()->json([
                'success' => false,
                'message' => "Ma'lumotlar noto'g'ri",
                'errors' => $validator->errors(),
            ], 422);
        }

        // API key tekshirish
        $business = Business::find($request->business_id);
        if (!$this->validateApiKey($business, $request->api_key)) {
            Log::warning('CameraAttendance: Invalid API key', [
                'business_id' => $request->business_id,
                'device_id' => $request->device_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'API kaliti noto\'g\'ri',
            ], 401);
        }

        // Hodimni topish
        $user = $this->findEmployee(
            $business,
            $request->employee_code,
            $request->badge_id,
            $request->face_id
        );

        if (!$user) {
            Log::warning('CameraAttendance: Employee not found', [
                'business_id' => $request->business_id,
                'employee_code' => $request->employee_code,
                'badge_id' => $request->badge_id,
                'face_id' => $request->face_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Hodim topilmadi',
                'data' => [
                    'employee_code' => $request->employee_code,
                    'badge_id' => $request->badge_id,
                ],
            ], 404);
        }

        // Davomat yozuvini yaratish
        $timestamp = $request->timestamp ? now()->parse($request->timestamp) : now();
        $eventType = $request->event_type;

        try {
            $attendance = $this->recordAttendance(
                $business,
                $user,
                $eventType,
                $timestamp,
                [
                    'device_id' => $request->device_id,
                    'device_location' => $request->device_location,
                    'confidence' => $request->confidence,
                    'photo_url' => $request->photo_url,
                    'source' => 'camera',
                ]
            );

            Log::info('CameraAttendance: Event recorded', [
                'user_id' => $user->id,
                'event_type' => $eventType,
                'device_id' => $request->device_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => $eventType === 'check_in' ? 'Check-in qabul qilindi' : 'Check-out qabul qilindi',
                'data' => [
                    'attendance_id' => $attendance->id,
                    'employee' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'employee_code' => $this->getEmployeeCode($business, $user),
                    ],
                    'event_type' => $eventType,
                    'timestamp' => $timestamp->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('CameraAttendance: Failed to record', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Davomat yozilishda xatolik',
            ], 500);
        }
    }

    /**
     * Bugungi kamera orqali yozilgan davomatlar
     */
    public function todayEvents(Request $request, string $businessId): JsonResponse
    {
        $business = Business::find($businessId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Biznes topilmadi',
            ], 404);
        }

        $events = AttendanceRecord::where('business_id', $businessId)
            ->whereDate('date', today())
            ->where('source', 'camera')
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'user' => [
                    'id' => $a->user->id,
                    'name' => $a->user->name,
                ],
                'event_type' => $a->check_out ? 'check_out' : 'check_in',
                'check_in' => $a->check_in?->format('H:i'),
                'check_out' => $a->check_out?->format('H:i'),
                'device_location' => $a->metadata['device_location'] ?? null,
                'confidence' => $a->metadata['confidence'] ?? null,
            ]);

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }

    /**
     * Kamera qurilmalarini ro'yxatga olish
     */
    public function registerDevice(Request $request, string $businessId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:50',
            'device_name' => 'required|string|max:255',
            'device_location' => 'required|string|max:255',
            'device_type' => 'required|in:camera,badge_reader,biometric',
            'ip_address' => 'nullable|ip',
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

        // Qurilmalarni business settings'da saqlash
        $settings = $business->settings ?? [];
        $devices = $settings['attendance_devices'] ?? [];

        $devices[$request->device_id] = [
            'device_id' => $request->device_id,
            'device_name' => $request->device_name,
            'device_location' => $request->device_location,
            'device_type' => $request->device_type,
            'ip_address' => $request->ip_address,
            'registered_at' => now()->toISOString(),
            'is_active' => true,
        ];

        $settings['attendance_devices'] = $devices;
        $business->update(['settings' => $settings]);

        return response()->json([
            'success' => true,
            'message' => 'Qurilma ro\'yxatga olindi',
            'data' => [
                'device_id' => $request->device_id,
            ],
        ]);
    }

    /**
     * API kalitini tekshirish
     */
    protected function validateApiKey(Business $business, string $apiKey): bool
    {
        $settings = $business->settings ?? [];
        $storedKey = $settings['camera_api_key'] ?? null;

        // Agar kalit o'rnatilmagan bo'lsa, har qanday kalitni qabul qilish (dev uchun)
        if (!$storedKey && config('app.env') === 'local') {
            return true;
        }

        return $storedKey === $apiKey;
    }

    /**
     * Hodimni topish (employee_code, badge_id yoki face_id orqali)
     */
    protected function findEmployee(
        Business $business,
        ?string $employeeCode,
        ?string $badgeId,
        ?string $faceId
    ): ?User {
        // Employee code bo'yicha qidirish
        if ($employeeCode) {
            $businessUser = BusinessUser::where('business_id', $business->id)
                ->where('employee_code', $employeeCode)
                ->first();

            if ($businessUser) {
                return User::find($businessUser->user_id);
            }
        }

        // Badge ID bo'yicha qidirish
        if ($badgeId) {
            $businessUser = BusinessUser::where('business_id', $business->id)
                ->where('badge_id', $badgeId)
                ->first();

            if ($businessUser) {
                return User::find($businessUser->user_id);
            }
        }

        // Face ID bo'yicha qidirish
        if ($faceId) {
            $businessUser = BusinessUser::where('business_id', $business->id)
                ->where('face_id', $faceId)
                ->first();

            if ($businessUser) {
                return User::find($businessUser->user_id);
            }
        }

        return null;
    }

    /**
     * Davomat yozuvini yaratish yoki yangilash
     */
    protected function recordAttendance(
        Business $business,
        User $user,
        string $eventType,
        $timestamp,
        array $metadata = []
    ): AttendanceRecord {
        $today = $timestamp->toDateString();

        // Bugungi mavjud yozuvni topish
        $attendance = AttendanceRecord::where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if (!$attendance) {
            // Yangi yozuv yaratish
            $attendance = AttendanceRecord::create([
                'business_id' => $business->id,
                'user_id' => $user->id,
                'date' => $today,
                'check_in' => $eventType === 'check_in' ? $timestamp : null,
                'check_out' => $eventType === 'check_out' ? $timestamp : null,
                'source' => $metadata['source'] ?? 'camera',
                'status' => 'present',
                'metadata' => $metadata,
            ]);
        } else {
            // Mavjud yozuvni yangilash
            $updateData = [
                'metadata' => array_merge($attendance->metadata ?? [], $metadata),
            ];

            if ($eventType === 'check_in' && !$attendance->check_in) {
                $updateData['check_in'] = $timestamp;
            } elseif ($eventType === 'check_out') {
                $updateData['check_out'] = $timestamp;

                // Ishlangan vaqtni hisoblash
                if ($attendance->check_in) {
                    $checkIn = $attendance->check_in;
                    $workMinutes = $checkIn->diffInMinutes($timestamp);
                    $updateData['work_hours'] = round($workMinutes / 60, 2);
                }
            }

            $attendance->update($updateData);
        }

        return $attendance->fresh();
    }

    /**
     * Hodim kodini olish
     */
    protected function getEmployeeCode(Business $business, User $user): ?string
    {
        $businessUser = BusinessUser::where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->first();

        return $businessUser?->employee_code;
    }
}
