<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deliverable;
use App\Models\ContentGeneration;
use App\Models\Todo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DeliverableController extends Controller
{
    /**
     * Deliverables ro'yxati
     */
    public function index(Request $request): JsonResponse
    {
        $business = $this->resolveBusiness();
        if (!$business) return response()->json(['success' => false, 'message' => 'Biznes topilmadi'], 422);

        $query = Deliverable::where('business_id', $business->id)
            ->orderByDesc('created_at');

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('agent')) {
            $query->where('agent', $request->agent);
        }

        $deliverables = $query->limit($request->input('limit', 20))->get();

        return response()->json(['success' => true, 'data' => $deliverables]);
    }

    /**
     * Bitta deliverable tafsiloti
     */
    public function show(string $id): JsonResponse
    {
        $business = $this->resolveBusiness();
        $deliverable = Deliverable::where('id', $id)->where('business_id', $business->id)->first();

        if (!$deliverable) {
            return response()->json(['success' => false, 'message' => 'Topilmadi'], 404);
        }

        return response()->json(['success' => true, 'data' => $deliverable]);
    }

    /**
     * Deliverable ni tasdiqlash
     */
    public function approve(string $id): JsonResponse
    {
        $business = $this->resolveBusiness();
        $deliverable = Deliverable::where('id', $id)->where('business_id', $business->id)->first();

        if (!$deliverable) {
            return response()->json(['success' => false, 'message' => 'Topilmadi'], 404);
        }

        $deliverable->approve();

        // Tasdiqlangandan keyin bajarish
        $result = $this->executeDeliverable($deliverable);

        return response()->json([
            'success' => true,
            'message' => 'Tasdiqlandi va bajarildi!',
            'result' => $result,
        ]);
    }

    /**
     * Deliverable ni rad etish
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        $business = $this->resolveBusiness();
        $deliverable = Deliverable::where('id', $id)->where('business_id', $business->id)->first();

        if (!$deliverable) {
            return response()->json(['success' => false, 'message' => 'Topilmadi'], 404);
        }

        $deliverable->reject($request->input('feedback'));

        return response()->json(['success' => true, 'message' => 'Rad etildi']);
    }

    /**
     * Deliverable ni bajarish — turga qarab amallarni bajarish
     */
    private function executeDeliverable(Deliverable $deliverable): string
    {
        try {
            $result = match ($deliverable->type) {
                'content_plan' => $this->executeContentPlan($deliverable),
                'lead_responses' => $this->executeLeadResponses($deliverable),
                'follow_up_plan' => $this->executeFollowUpPlan($deliverable),
                default => 'Ko\'rib chiqish uchun saqlandi',
            };

            $deliverable->markCompleted();
            return $result;
        } catch (\Exception $e) {
            Log::error('Deliverable execute xato', ['id' => $deliverable->id, 'error' => $e->getMessage()]);
            return 'Bajarishda xatolik: ' . $e->getMessage();
        }
    }

    /**
     * Kontent rejani content_generations jadvaliga qo'shish
     */
    private function executeContentPlan(Deliverable $deliverable): string
    {
        $posts = $deliverable->data['posts'] ?? [];
        $created = 0;

        foreach ($posts as $post) {
            if (isset($post['raw_text'])) continue; // Parse bo'lmagan

            try {
                ContentGeneration::create([
                    'business_id' => $deliverable->business_id,
                    'user_id' => $deliverable->user_id ?? Auth::id(),
                    'topic' => $post['sarlavha'] ?? ($post['title'] ?? 'Kontent'),
                    'generated_content' => $post['matn'] ?? ($post['caption'] ?? ''),
                    'generated_hashtags' => $post['hashtaglar'] ?? ($post['hashtags'] ?? []),
                    'content_type' => $this->mapContentType($post['turi'] ?? ($post['type'] ?? 'post')),
                    'target_channel' => $post['kanal'] ?? ($post['channel'] ?? 'instagram'),
                    'purpose' => 'engage',
                    'status' => 'completed',
                    'ai_model' => 'agent-imronbek',
                ]);
                $created++;
            } catch (\Exception $e) {
                Log::warning('Content create xato', ['error' => $e->getMessage()]);
            }
        }

        return "{$created} ta post kontent rejaga qo'shildi";
    }

    /**
     * Lid javoblarni vazifalar sifatida saqlash
     */
    private function executeLeadResponses(Deliverable $deliverable): string
    {
        $responses = $deliverable->data['responses'] ?? [];
        $created = 0;

        foreach ($responses as $resp) {
            try {
                Todo::create([
                    'business_id' => $deliverable->business_id,
                    'created_by' => $deliverable->user_id ?? Auth::id(),
                    'title' => ($resp['lead_name'] ?? 'Lid') . ' ga javob yuborish',
                    'description' => $resp['response_text'] ?? '',
                    'status' => 'pending',
                    'priority' => ($resp['lead_score'] ?? 0) > 60 ? 'high' : 'medium',
                    'due_date' => now()->addDay(),
                ]);
                $created++;
            } catch (\Exception $e) {
                Log::warning('Lead response todo xato', ['error' => $e->getMessage()]);
            }
        }

        return "{$created} ta lid javobi vazifalar bo'limiga qo'shildi";
    }

    /**
     * Follow-up rejani vazifalar sifatida saqlash
     */
    private function executeFollowUpPlan(Deliverable $deliverable): string
    {
        $followUps = $deliverable->data['follow_ups'] ?? [];
        $created = 0;

        foreach ($followUps as $fu) {
            try {
                Todo::create([
                    'business_id' => $deliverable->business_id,
                    'created_by' => $deliverable->user_id ?? Auth::id(),
                    'title' => ($fu['lid_nomi'] ?? 'Lid') . ' — follow-up ketma-ketligi',
                    'description' => json_encode($fu['xabarlar'] ?? [], JSON_UNESCAPED_UNICODE),
                    'status' => 'pending',
                    'priority' => 'high',
                    'due_date' => now()->addDay(),
                ]);
                $created++;
            } catch (\Exception $e) {
                Log::warning('Follow-up todo xato', ['error' => $e->getMessage()]);
            }
        }

        return "{$created} ta follow-up reja vazifalar bo'limiga qo'shildi";
    }

    private function mapContentType(string $type): string
    {
        return match (mb_strtolower($type)) {
            'post', 'instagram_post', 'telegram_post' => 'post',
            'story', 'stories' => 'story',
            'reel', 'reels', 'reels_script' => 'reel',
            'carousel' => 'carousel',
            'article', 'blog', 'blog_article' => 'article',
            'ad', 'reklama' => 'ad',
            default => 'post',
        };
    }

    private function resolveBusiness(): ?object
    {
        $user = Auth::user();
        $businessId = session('current_business_id');

        if ($businessId) {
            $business = \App\Models\Business::find($businessId);
            if ($business) return $business;
        }

        return $user->business ?? $user->businesses()->first();
    }
}
