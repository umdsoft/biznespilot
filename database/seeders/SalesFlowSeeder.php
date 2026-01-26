<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\InstagramAccount;
use App\Models\InstagramAutomation;
use App\Models\InstagramAutomationTrigger;
use App\Models\InstagramFlowEdge;
use App\Models\InstagramFlowNode;
use App\Models\Integration;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * SalesFlowSeeder - Sotuv Voronkasi (Sales Funnel) namunasini yaratadi
 *
 * Bu seeder Instagram chatbot tizimini test qilish uchun
 * to'liq ishlaydigan sotuv avtomatizatsiyasini yaratadi.
 *
 * Flow tuzilishi:
 * ┌─────────────────┐
 * │  START TRIGGER  │ (keyword: start, boshlash, salom)
 * └────────┬────────┘
 *          │
 *          ▼
 * ┌─────────────────┐
 * │  MENYU TUGMALAR │ "Assalomu alaykum! Xizmatlarimiz:"
 * │ [Narxlar][Aloqa]│
 * └────┬───────┬────┘
 *      │       │
 *      ▼       ▼
 * ┌────────┐ ┌────────┐
 * │ NARXLAR│ │ ALOQA  │
 * └────┬───┘ └────┬───┘
 *      │         │
 *      ▼         ▼
 * ┌─────────────────┐
 * │   YAKUNIY XABAR │
 * └─────────────────┘
 *
 * @example php artisan db:seed --class=SalesFlowSeeder
 */
class SalesFlowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 SalesFlowSeeder ishga tushdi...');

        // 1. Business va Instagram Account olish/yaratish
        $account = $this->getOrCreateInstagramAccount();

        $this->command->info("✅ Instagram Account: {$account->username} ({$account->id})");

        // 2. Eski test automation ni o'chirish (agar mavjud bo'lsa)
        $this->cleanupOldAutomations($account);

        // 3. Asosiy sotuv avtomatizatsiyasini yaratish
        $automation = $this->createSalesAutomation($account);

        $this->command->info("✅ Automation yaratildi: {$automation->name}");

        // 4. Trigger yaratish
        $this->createTrigger($automation);

        $this->command->info('✅ Trigger yaratildi: start, boshlash, salom');

        // 5. Flow nodes va edges yaratish
        $nodesData = $this->createFlowNodes($automation);

        $this->command->info('✅ Flow nodes yaratildi: ' . count($nodesData['nodes']));

        $this->createFlowEdges($automation, $nodesData);

        $this->command->info('✅ Flow edges yaratildi');

        // 6. Flow data ni automation ga saqlash
        $this->updateAutomationFlowData($automation, $nodesData);

        $this->command->info('✅ Flow data saqlandi');

        // 7. Yakuniy xabar
        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════════');
        $this->command->info('🎉 SALES FLOW MUVAFFAQIYATLI YARATILDI!');
        $this->command->info('═══════════════════════════════════════════');
        $this->command->info("Automation ID: {$automation->id}");
        $this->command->info("Account: @{$account->username}");
        $this->command->info('');
        $this->command->info('Test qilish uchun:');
        $this->command->info('  1. Instagram DM ga "start" yozing');
        $this->command->info('  2. Yoki "salom" yozing');
        $this->command->info('  3. Tugmalarni bosib flow ni sinab ko\'ring');
        $this->command->newLine();
    }

    /**
     * Get or create Instagram account for testing
     */
    protected function getOrCreateInstagramAccount(): InstagramAccount
    {
        // Avval mavjud akkauntni qidirish
        $account = InstagramAccount::first();

        if ($account) {
            return $account;
        }

        // Mavjud business olish yoki yaratish
        $business = Business::first();

        if (! $business) {
            $business = Business::create([
                'id' => Str::uuid(),
                'name' => 'Test Business',
                'slug' => 'test-business',
                'industry' => 'E-commerce',
                'status' => 'active',
            ]);
        }

        // Integration yaratish
        $integration = Integration::create([
            'id' => Str::uuid(),
            'business_id' => $business->id,
            'type' => 'meta_ads',
            'name' => 'Test Meta Integration',
            'status' => 'connected',
            'is_active' => true,
            'credentials' => json_encode([
                'access_token' => 'test_access_token_for_seeder',
            ]),
            'connected_at' => now(),
        ]);

        // Instagram account yaratish
        return InstagramAccount::create([
            'id' => Str::uuid(),
            'business_id' => $business->id,
            'integration_id' => $integration->id,
            'instagram_id' => '17841400000000001',
            'username' => 'test_business_bot',
            'name' => 'Test Business Bot',
            'followers_count' => 1000,
            'follows_count' => 100,
            'media_count' => 50,
            'is_primary' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Cleanup old test automations
     */
    protected function cleanupOldAutomations(InstagramAccount $account): void
    {
        $oldAutomations = InstagramAutomation::where('account_id', $account->id)
            ->where('name', 'like', '%Sotuv Boti%')
            ->get();

        foreach ($oldAutomations as $automation) {
            // Related records ni o'chirish
            InstagramFlowNode::where('automation_id', $automation->id)->delete();
            InstagramFlowEdge::where('automation_id', $automation->id)->delete();
            InstagramAutomationTrigger::where('automation_id', $automation->id)->delete();
            $automation->forceDelete();
        }
    }

    /**
     * Create main sales automation
     */
    protected function createSalesAutomation(InstagramAccount $account): InstagramAutomation
    {
        return InstagramAutomation::create([
            'id' => Str::uuid(),
            'account_id' => $account->id,
            'name' => '🛒 Asosiy Sotuv Boti',
            'description' => 'Instagram DM orqali avtomatik sotuv voronkasi. Foydalanuvchi "start" yozganda ishga tushadi.',
            'status' => InstagramAutomation::STATUS_ACTIVE,
            'type' => InstagramAutomation::TYPE_KEYWORD,
            'is_flow_based' => true,
            'is_ai_enabled' => false,
            'trigger_count' => 0,
            'conversion_count' => 0,
            'settings' => [
                'welcome_delay' => 1,
                'typing_indicator' => true,
                'track_conversions' => true,
            ],
        ]);
    }

    /**
     * Create automation trigger
     */
    protected function createTrigger(InstagramAutomation $automation): void
    {
        InstagramAutomationTrigger::create([
            'id' => Str::uuid(),
            'automation_id' => $automation->id,
            'trigger_type' => InstagramAutomationTrigger::TYPE_KEYWORD_DM,
            'keywords' => ['start', 'boshlash', 'salom', 'привет', 'hi', 'hello'],
            'case_sensitive' => false,
            'exact_match' => false,
        ]);
    }

    /**
     * Create flow nodes
     *
     * @return array{nodes: array, nodeIds: array}
     */
    protected function createFlowNodes(InstagramAutomation $automation): array
    {
        $nodes = [];
        $nodeIds = [];

        // ═══════════════════════════════════════════
        // NODE A: Start Trigger
        // ═══════════════════════════════════════════
        $nodeA_id = (string) Str::uuid();
        $nodeIds['trigger'] = $nodeA_id;

        $nodeA = InstagramFlowNode::create([
            'id' => Str::uuid(),
            'automation_id' => $automation->id,
            'node_id' => $nodeA_id,
            'node_type' => 'trigger_keyword_dm',
            'data' => [
                'keywords' => 'start, boshlash, salom, hi, hello',
                'exact_match' => 'contains',
                'label' => 'Start Trigger',
            ],
            'position' => ['x' => 250, 'y' => 50],
        ]);
        $nodes[] = $this->formatNodeForFlowData($nodeA);

        // ═══════════════════════════════════════════
        // NODE B: Menyu (Tugmalar bilan)
        // ═══════════════════════════════════════════
        $nodeB_id = (string) Str::uuid();
        $nodeIds['menu'] = $nodeB_id;

        $nodeB = InstagramFlowNode::create([
            'id' => Str::uuid(),
            'automation_id' => $automation->id,
            'node_id' => $nodeB_id,
            'node_type' => 'action_send_buttons',
            'data' => [
                'message' => "Assalomu alaykum, {name}! 👋\n\nBizning xizmatlarimiz bilan tanishing.\nQuyidagi tugmalardan birini tanlang:",
                'buttons' => [
                    [
                        'title' => '💰 Narxlar',
                        'payload' => 'FLOW:' . ($nodeC_id = (string) Str::uuid()),
                    ],
                    [
                        'title' => '📞 Bog\'lanish',
                        'payload' => 'FLOW:' . ($nodeD_id = (string) Str::uuid()),
                    ],
                    [
                        'title' => '📚 Kurslar',
                        'payload' => 'FLOW:' . ($nodeE_id = (string) Str::uuid()),
                    ],
                    [
                        'title' => '❓ Savol berish',
                        'payload' => 'ACTION:human_handoff',
                    ],
                ],
                'label' => 'Asosiy Menyu',
            ],
            'position' => ['x' => 250, 'y' => 200],
        ]);
        $nodes[] = $this->formatNodeForFlowData($nodeB);

        // Store generated node IDs
        $nodeIds['prices'] = $nodeC_id;
        $nodeIds['contact'] = $nodeD_id;
        $nodeIds['courses'] = $nodeE_id;

        // ═══════════════════════════════════════════
        // NODE C: Narxlar sahifasi
        // ═══════════════════════════════════════════
        $nodeC = InstagramFlowNode::create([
            'id' => Str::uuid(),
            'automation_id' => $automation->id,
            'node_id' => $nodeC_id,
            'node_type' => 'action_send_dm',
            'data' => [
                'message' => "💰 Bizning xizmat narxlarimiz:\n\n"
                    . "📱 SMM boshqaruvi - 200\$/oy\n"
                    . "💻 Dasturlash kursi - 300\$ (2 oy)\n"
                    . "🎨 Dizayn kursi - 150\$ (1 oy)\n"
                    . "📊 Marketing konsultatsiya - 50\$/soat\n\n"
                    . "Batafsil ma'lumot uchun \"kurs\" deb yozing!",
                'label' => 'Narxlar',
            ],
            'position' => ['x' => 50, 'y' => 400],
        ]);
        $nodes[] = $this->formatNodeForFlowData($nodeC);

        // ═══════════════════════════════════════════
        // NODE D: Aloqa sahifasi
        // ═══════════════════════════════════════════
        $nodeD = InstagramFlowNode::create([
            'id' => Str::uuid(),
            'automation_id' => $automation->id,
            'node_id' => $nodeD_id,
            'node_type' => 'action_send_dm',
            'data' => [
                'message' => "📞 Biz bilan bog'lanish:\n\n"
                    . "☎️ Telefon: +998 90 123 45 67\n"
                    . "📱 Telegram: @biznespilot_support\n"
                    . "📧 Email: info@biznespilot.uz\n"
                    . "🌐 Sayt: biznespilot.uz\n\n"
                    . "Ish vaqti: Du-Ju 9:00 - 18:00\n\n"
                    . "Menejerimiz tez orada siz bilan bog'lanadi! 👨‍💼",
                'label' => 'Aloqa',
            ],
            'position' => ['x' => 250, 'y' => 400],
        ]);
        $nodes[] = $this->formatNodeForFlowData($nodeD);

        // ═══════════════════════════════════════════
        // NODE E: Kurslar sahifasi (tugmalar bilan)
        // ═══════════════════════════════════════════
        $nodeE = InstagramFlowNode::create([
            'id' => Str::uuid(),
            'automation_id' => $automation->id,
            'node_id' => $nodeE_id,
            'node_type' => 'action_send_buttons',
            'data' => [
                'message' => "📚 Bizning kurslarimiz:\n\nQaysi kurs haqida ko'proq bilmoqchisiz?",
                'buttons' => [
                    [
                        'title' => '💻 Dasturlash',
                        'payload' => 'FLOW:' . ($nodeF_id = (string) Str::uuid()),
                    ],
                    [
                        'title' => '📱 SMM',
                        'payload' => 'FLOW:' . ($nodeG_id = (string) Str::uuid()),
                    ],
                    [
                        'title' => '🔙 Orqaga',
                        'payload' => 'FLOW:' . $nodeB_id,
                    ],
                ],
                'label' => 'Kurslar Menyusi',
            ],
            'position' => ['x' => 450, 'y' => 400],
        ]);
        $nodes[] = $this->formatNodeForFlowData($nodeE);

        $nodeIds['programming'] = $nodeF_id;
        $nodeIds['smm'] = $nodeG_id;

        // ═══════════════════════════════════════════
        // NODE F: Dasturlash kursi detallari
        // ═══════════════════════════════════════════
        $nodeF = InstagramFlowNode::create([
            'id' => Str::uuid(),
            'automation_id' => $automation->id,
            'node_id' => $nodeF_id,
            'node_type' => 'action_send_buttons',
            'data' => [
                'message' => "💻 Dasturlash Kursi\n\n"
                    . "📅 Davomiyligi: 2 oy\n"
                    . "💰 Narxi: 300\$\n"
                    . "👨‍🏫 Ustoz: Jahongir Rahimov\n\n"
                    . "📋 Dastur:\n"
                    . "• HTML, CSS, JavaScript\n"
                    . "• React/Vue.js\n"
                    . "• Node.js backend\n"
                    . "• Real loyihalar\n\n"
                    . "🎁 Bonus: Portfolio + Sertifikat",
                'buttons' => [
                    [
                        'title' => '✅ Ro\'yxatdan o\'tish',
                        'payload' => 'ACTION:register_programming',
                    ],
                    [
                        'title' => '🔙 Orqaga',
                        'payload' => 'FLOW:' . $nodeE_id,
                    ],
                ],
                'label' => 'Dasturlash Kursi',
            ],
            'position' => ['x' => 350, 'y' => 600],
        ]);
        $nodes[] = $this->formatNodeForFlowData($nodeF);

        // ═══════════════════════════════════════════
        // NODE G: SMM kursi detallari
        // ═══════════════════════════════════════════
        $nodeG = InstagramFlowNode::create([
            'id' => Str::uuid(),
            'automation_id' => $automation->id,
            'node_id' => $nodeG_id,
            'node_type' => 'action_send_buttons',
            'data' => [
                'message' => "📱 SMM Marketing Kursi\n\n"
                    . "📅 Davomiyligi: 1 oy\n"
                    . "💰 Narxi: 200\$/oy\n"
                    . "👩‍🏫 Ustoz: Nilufar Karimova\n\n"
                    . "📋 Dastur:\n"
                    . "• Instagram algoritmlari\n"
                    . "• Content yaratish\n"
                    . "• Reels va Stories\n"
                    . "• Reklama sozlash\n"
                    . "• Tahlil va hisobotlar\n\n"
                    . "🎁 Bonus: SMM tools + Shablonlar",
                'buttons' => [
                    [
                        'title' => '✅ Ro\'yxatdan o\'tish',
                        'payload' => 'ACTION:register_smm',
                    ],
                    [
                        'title' => '🔙 Orqaga',
                        'payload' => 'FLOW:' . $nodeE_id,
                    ],
                ],
                'label' => 'SMM Kursi',
            ],
            'position' => ['x' => 550, 'y' => 600],
        ]);
        $nodes[] = $this->formatNodeForFlowData($nodeG);

        // ═══════════════════════════════════════════
        // NODE H: Tag qo'shish (konversiya tracking)
        // ═══════════════════════════════════════════
        $nodeH_id = (string) Str::uuid();
        $nodeIds['add_tag'] = $nodeH_id;

        $nodeH = InstagramFlowNode::create([
            'id' => Str::uuid(),
            'automation_id' => $automation->id,
            'node_id' => $nodeH_id,
            'node_type' => 'action_add_tag',
            'data' => [
                'tag' => 'interested',
                'label' => 'Tag: Interested',
            ],
            'position' => ['x' => 250, 'y' => 150],
        ]);
        $nodes[] = $this->formatNodeForFlowData($nodeH);

        return [
            'nodes' => $nodes,
            'nodeIds' => $nodeIds,
        ];
    }

    /**
     * Format node model for flow_data JSON
     */
    protected function formatNodeForFlowData(InstagramFlowNode $node): array
    {
        return [
            'node_id' => $node->node_id,
            'node_type' => $node->node_type,
            'data' => $node->data,
            'position' => $node->position,
        ];
    }

    /**
     * Create flow edges (connections between nodes)
     */
    protected function createFlowEdges(InstagramAutomation $automation, array $nodesData): void
    {
        $nodeIds = $nodesData['nodeIds'];
        $edges = [];

        // Trigger -> Tag (har bir foydalanuvchiga "interested" tag)
        $edges[] = InstagramFlowEdge::create([
            'id' => Str::uuid(),
            'automation_id' => $automation->id,
            'edge_id' => (string) Str::uuid(),
            'source_node_id' => $nodeIds['trigger'],
            'target_node_id' => $nodeIds['add_tag'],
            'source_handle' => null,
        ]);

        // Tag -> Menu
        $edges[] = InstagramFlowEdge::create([
            'id' => Str::uuid(),
            'automation_id' => $automation->id,
            'edge_id' => (string) Str::uuid(),
            'source_node_id' => $nodeIds['add_tag'],
            'target_node_id' => $nodeIds['menu'],
            'source_handle' => null,
        ]);

        // Menu tugmalari orqali edges (payload orqali handle qilinadi)
        // Bu yerda edge kerak emas chunki payload orqali navigate qilinadi
    }

    /**
     * Update automation with flow_data JSON
     */
    protected function updateAutomationFlowData(InstagramAutomation $automation, array $nodesData): void
    {
        // Edges ni ham olish
        $edges = InstagramFlowEdge::where('automation_id', $automation->id)->get();

        $flowData = [
            'nodes' => $nodesData['nodes'],
            'edges' => $edges->map(function ($edge) {
                return [
                    'edge_id' => $edge->edge_id,
                    'source_node_id' => $edge->source_node_id,
                    'target_node_id' => $edge->target_node_id,
                    'source_handle' => $edge->source_handle,
                ];
            })->toArray(),
            'metadata' => [
                'version' => '1.0',
                'created_at' => now()->toISOString(),
                'created_by' => 'SalesFlowSeeder',
            ],
        ];

        $automation->update([
            'flow_data' => $flowData,
        ]);
    }
}
