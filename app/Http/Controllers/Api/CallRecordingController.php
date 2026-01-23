<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\CallLog;
use App\Services\CallRecordingService;
use Illuminate\Http\Request;

/**
 * CallRecordingController - Qo'ng'iroq yozuvlari URL larini olish
 *
 * UTEL va OnlinePBX to'g'ridan-to'g'ri URL qaytaradi.
 * Streaming/proxy kerak emas - frontend URL ni to'g'ridan-to'g'ri ishlatadi.
 */
class CallRecordingController extends Controller
{
    use HasCurrentBusiness;

    protected CallRecordingService $recordingService;

    public function __construct(CallRecordingService $recordingService)
    {
        $this->recordingService = $recordingService;
    }

    /**
     * Get recording URL for a call
     * GET /api/v1/calls/{callId}/recording
     *
     * Response: { "success": true, "recording_url": "https://..." }
     */
    public function getUrl(Request $request, string $callId)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['success' => false, 'error' => 'Biznes topilmadi'], 404);
        }

        $callLog = CallLog::where('id', $callId)
            ->where('business_id', $business->id)
            ->first();

        if (!$callLog) {
            return response()->json(['success' => false, 'error' => 'Qo\'ng\'iroq topilmadi'], 404);
        }

        $recordingUrl = $this->recordingService->getRecordingUrl($callLog);

        if ($recordingUrl) {
            return response()->json([
                'success' => true,
                'recording_url' => $recordingUrl,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'Yozuv topilmadi',
        ]);
    }

    /**
     * Check if recording exists
     * GET /api/v1/calls/{callId}/recording/check
     */
    public function check(Request $request, string $callId)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['available' => false]);
        }

        $callLog = CallLog::where('id', $callId)
            ->where('business_id', $business->id)
            ->first();

        if (!$callLog) {
            return response()->json(['available' => false]);
        }

        $hasRecording = $this->recordingService->hasRecording($callLog);

        return response()->json([
            'available' => $hasRecording,
            'recording_url' => $hasRecording ? $callLog->recording_url : null,
        ]);
    }

    /**
     * Get recording info (metadata)
     * GET /api/v1/calls/{callId}/recording/info
     */
    public function info(Request $request, string $callId)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $callLog = CallLog::where('id', $callId)
            ->where('business_id', $business->id)
            ->first();

        if (!$callLog) {
            return response()->json(['error' => 'Qo\'ng\'iroq topilmadi'], 404);
        }

        $recordingUrl = $this->recordingService->getRecordingUrl($callLog);

        return response()->json([
            'call_id' => $callLog->id,
            'provider' => $callLog->provider,
            'duration' => $callLog->duration,
            'has_recording' => !empty($recordingUrl),
            'recording_url' => $recordingUrl,
        ]);
    }
}
