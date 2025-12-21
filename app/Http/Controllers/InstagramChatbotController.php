<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\InstagramAccount;
use App\Models\InstagramAutomation;
use App\Models\InstagramFlowNode;
use App\Models\InstagramFlowEdge;
use App\Models\InstagramAutomationTemplate;
use App\Services\InstagramChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InstagramChatbotController extends Controller
{
    public function __construct(
        protected InstagramChatbotService $chatbotService
    ) {}

    /**
     * Display Instagram Chatbot dashboard
     */
    public function index(Request $request): Response
    {
        $business = $this->getCurrentBusiness($request);
        $account = $this->getSelectedAccount($request);

        return Inertia::render('Business/InstagramChatbot/Index', [
            'business' => $business,
            'account' => $account,
            'hasAccount' => $account !== null,
        ]);
    }

    /**
     * Get dashboard stats
     */
    public function getDashboard(Request $request): JsonResponse
    {
        $account = $this->getSelectedAccount($request);
        if (!$account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        try {
            $stats = $this->chatbotService->getDashboardStats($account->id);
            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get automations list
     */
    public function getAutomations(Request $request): JsonResponse
    {
        $account = $this->getSelectedAccount($request);
        if (!$account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        try {
            $automations = $this->chatbotService->getAutomations($account->id);
            return response()->json(['automations' => $automations]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create automation
     */
    public function createAutomation(Request $request): JsonResponse
    {
        $account = $this->getSelectedAccount($request);
        if (!$account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:keyword,comment,story_mention,story_reply,dm,welcome',
            'status' => 'nullable|in:active,paused,draft',
            'is_ai_enabled' => 'boolean',
            'triggers' => 'required|array|min:1',
            'triggers.*.trigger_type' => 'required|string',
            'triggers.*.keywords' => 'nullable|array',
            'triggers.*.media_id' => 'nullable|string',
            'actions' => 'required|array|min:1',
            'actions.*.action_type' => 'required|string',
            'actions.*.message_template' => 'nullable|string',
            'actions.*.delay_seconds' => 'nullable|integer',
        ]);

        try {
            $automation = $this->chatbotService->createAutomation($account->id, $validated);
            return response()->json([
                'success' => true,
                'automation' => $automation,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update automation
     */
    public function updateAutomation(Request $request, int $id): JsonResponse
    {
        $account = $this->getSelectedAccount($request);
        if (!$account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'type' => 'in:keyword,comment,story_mention,story_reply,dm,welcome',
            'status' => 'in:active,paused,draft',
            'is_ai_enabled' => 'boolean',
            'triggers' => 'array',
            'triggers.*.trigger_type' => 'required|string',
            'triggers.*.keywords' => 'nullable|array',
            'actions' => 'array',
            'actions.*.action_type' => 'required|string',
            'actions.*.message_template' => 'nullable|string',
        ]);

        try {
            $automation = $this->chatbotService->updateAutomation($id, $validated);
            return response()->json([
                'success' => true,
                'automation' => $automation,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete automation
     */
    public function deleteAutomation(Request $request, int $id): JsonResponse
    {
        try {
            $this->chatbotService->deleteAutomation($id);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Toggle automation status
     */
    public function toggleAutomation(Request $request, int $id): JsonResponse
    {
        try {
            $automation = $this->chatbotService->toggleAutomation($id);
            return response()->json([
                'success' => true,
                'status' => $automation->status,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get conversations
     */
    public function getConversations(Request $request): JsonResponse
    {
        $account = $this->getSelectedAccount($request);
        if (!$account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        $filters = [
            'status' => $request->input('status'),
            'needs_human' => $request->boolean('needs_human'),
            'tag' => $request->input('tag'),
            'search' => $request->input('search'),
            'per_page' => $request->input('per_page', 20),
        ];

        try {
            $conversations = $this->chatbotService->getConversations($account->id, $filters);
            return response()->json($conversations);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get conversation detail
     */
    public function getConversation(Request $request, int $id): JsonResponse
    {
        try {
            $conversation = $this->chatbotService->getConversation($id);
            return response()->json($conversation);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Send message
     */
    public function sendMessage(Request $request, int $conversationId): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        try {
            $message = $this->chatbotService->sendMessage($conversationId, $validated['message']);
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get trigger types
     */
    public function getTriggerTypes(): JsonResponse
    {
        return response()->json([
            'trigger_types' => $this->chatbotService->getTriggerTypes(),
        ]);
    }

    /**
     * Get action types
     */
    public function getActionTypes(): JsonResponse
    {
        return response()->json([
            'action_types' => $this->chatbotService->getActionTypes(),
        ]);
    }

    /**
     * Get quick replies
     */
    public function getQuickReplies(Request $request): JsonResponse
    {
        $account = $this->getSelectedAccount($request);
        if (!$account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        try {
            $quickReplies = $this->chatbotService->getQuickReplies($account->id);
            return response()->json(['quick_replies' => $quickReplies]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create quick reply
     */
    public function createQuickReply(Request $request): JsonResponse
    {
        $account = $this->getSelectedAccount($request);
        if (!$account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'shortcut' => 'nullable|string|max:50',
        ]);

        try {
            $quickReply = $this->chatbotService->createQuickReply($account->id, $validated);
            return response()->json([
                'success' => true,
                'quick_reply' => $quickReply,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get node types for flow builder
     */
    public function getNodeTypes(): JsonResponse
    {
        return response()->json([
            'node_types' => InstagramFlowNode::getNodeTypes(),
        ]);
    }

    /**
     * Get templates
     */
    public function getTemplates(): JsonResponse
    {
        $templates = InstagramAutomationTemplate::active()->get();

        // If no templates in DB, return defaults
        if ($templates->isEmpty()) {
            $templates = collect(InstagramAutomationTemplate::getDefaultTemplates());
        }

        return response()->json(['templates' => $templates]);
    }

    /**
     * Create flow-based automation
     */
    public function createFlowAutomation(Request $request): JsonResponse
    {
        $account = $this->getSelectedAccount($request);
        if (!$account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,paused,draft',
            'nodes' => 'required|array|min:1',
            'nodes.*.node_id' => 'required|string',
            'nodes.*.node_type' => 'required|string',
            'nodes.*.position' => 'required|array',
            'nodes.*.data' => 'nullable|array',
            'edges' => 'array',
            'edges.*.edge_id' => 'required|string',
            'edges.*.source_node_id' => 'required|string',
            'edges.*.target_node_id' => 'required|string',
            'edges.*.source_handle' => 'nullable|string',
        ]);

        try {
            // Determine type from first trigger node
            $type = 'keyword';
            foreach ($validated['nodes'] as $node) {
                if (str_starts_with($node['node_type'], 'trigger_')) {
                    $typeMap = [
                        'trigger_keyword_dm' => 'keyword',
                        'trigger_keyword_comment' => 'comment',
                        'trigger_story_mention' => 'story_mention',
                        'trigger_story_reply' => 'story_reply',
                        'trigger_new_follower' => 'welcome',
                    ];
                    $type = $typeMap[$node['node_type']] ?? 'keyword';
                    break;
                }
            }

            // Create automation
            $automation = InstagramAutomation::create([
                'instagram_account_id' => $account->id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'] ?? 'draft',
                'type' => $type,
                'is_flow_based' => true,
                'flow_data' => [
                    'nodes' => $validated['nodes'],
                    'edges' => $validated['edges'] ?? [],
                ],
            ]);

            // Create flow nodes
            foreach ($validated['nodes'] as $node) {
                InstagramFlowNode::create([
                    'automation_id' => $automation->id,
                    'node_id' => $node['node_id'],
                    'node_type' => $node['node_type'],
                    'data' => $node['data'] ?? [],
                    'position' => $node['position'],
                ]);
            }

            // Create flow edges
            if (!empty($validated['edges'])) {
                foreach ($validated['edges'] as $edge) {
                    InstagramFlowEdge::create([
                        'automation_id' => $automation->id,
                        'edge_id' => $edge['edge_id'],
                        'source_node_id' => $edge['source_node_id'],
                        'target_node_id' => $edge['target_node_id'],
                        'source_handle' => $edge['source_handle'] ?? null,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'automation' => $automation->load(['flowNodes', 'flowEdges']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update flow-based automation
     */
    public function updateFlowAutomation(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'status' => 'in:active,paused,draft',
            'nodes' => 'array',
            'nodes.*.node_id' => 'required|string',
            'nodes.*.node_type' => 'required|string',
            'nodes.*.position' => 'required|array',
            'nodes.*.data' => 'nullable|array',
            'edges' => 'array',
            'edges.*.edge_id' => 'required|string',
            'edges.*.source_node_id' => 'required|string',
            'edges.*.target_node_id' => 'required|string',
            'edges.*.source_handle' => 'nullable|string',
        ]);

        try {
            $automation = InstagramAutomation::findOrFail($id);

            // Determine type from first trigger node
            $type = $automation->type;
            if (!empty($validated['nodes'])) {
                foreach ($validated['nodes'] as $node) {
                    if (str_starts_with($node['node_type'], 'trigger_')) {
                        $typeMap = [
                            'trigger_keyword_dm' => 'keyword',
                            'trigger_keyword_comment' => 'comment',
                            'trigger_story_mention' => 'story_mention',
                            'trigger_story_reply' => 'story_reply',
                            'trigger_new_follower' => 'welcome',
                        ];
                        $type = $typeMap[$node['node_type']] ?? 'keyword';
                        break;
                    }
                }
            }

            $automation->update([
                'name' => $validated['name'] ?? $automation->name,
                'description' => $validated['description'] ?? $automation->description,
                'status' => $validated['status'] ?? $automation->status,
                'type' => $type,
                'flow_data' => [
                    'nodes' => $validated['nodes'] ?? [],
                    'edges' => $validated['edges'] ?? [],
                ],
            ]);

            // Update nodes
            if (isset($validated['nodes'])) {
                $automation->flowNodes()->delete();
                foreach ($validated['nodes'] as $node) {
                    InstagramFlowNode::create([
                        'automation_id' => $automation->id,
                        'node_id' => $node['node_id'],
                        'node_type' => $node['node_type'],
                        'data' => $node['data'] ?? [],
                        'position' => $node['position'],
                    ]);
                }
            }

            // Update edges
            if (isset($validated['edges'])) {
                $automation->flowEdges()->delete();
                foreach ($validated['edges'] as $edge) {
                    InstagramFlowEdge::create([
                        'automation_id' => $automation->id,
                        'edge_id' => $edge['edge_id'],
                        'source_node_id' => $edge['source_node_id'],
                        'target_node_id' => $edge['target_node_id'],
                        'source_handle' => $edge['source_handle'] ?? null,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'automation' => $automation->fresh(['flowNodes', 'flowEdges']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get flow automation detail
     */
    public function getFlowAutomation(Request $request, int $id): JsonResponse
    {
        try {
            $automation = InstagramAutomation::with(['flowNodes', 'flowEdges'])->findOrFail($id);

            return response()->json([
                'automation' => [
                    'id' => $automation->id,
                    'name' => $automation->name,
                    'description' => $automation->description,
                    'status' => $automation->status,
                    'is_flow_based' => $automation->is_flow_based,
                    'trigger_count' => $automation->trigger_count,
                    'conversion_count' => $automation->conversion_count,
                ],
                'nodes' => $automation->flowNodes->map(fn($n) => [
                    'node_id' => $n->node_id,
                    'node_type' => $n->node_type,
                    'position' => $n->position,
                    'data' => $n->data ?? [],
                ]),
                'edges' => $automation->flowEdges->map(fn($e) => [
                    'edge_id' => $e->edge_id,
                    'source_node_id' => $e->source_node_id,
                    'target_node_id' => $e->target_node_id,
                    'source_handle' => $e->source_handle,
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get current business
     */
    protected function getCurrentBusiness(Request $request): ?Business
    {
        return Business::find(session('current_business_id'));
    }

    /**
     * Get selected Instagram account
     */
    protected function getSelectedAccount(Request $request): ?InstagramAccount
    {
        $business = $this->getCurrentBusiness($request);
        if (!$business) {
            return null;
        }

        return InstagramAccount::where('business_id', $business->id)
            ->where('is_primary', true)
            ->first()
            ?? InstagramAccount::where('business_id', $business->id)->first();
    }
}
