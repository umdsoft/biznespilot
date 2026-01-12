<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\TodoTemplate;
use App\Models\TodoTemplateItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TodoTemplateController extends Controller
{
    use HasCurrentBusiness;

    /**
     * Display templates index page
     */
    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('business.index')
                ->with('error', 'Avval biznes tanlang');
        }

        $category = $request->get('category', 'all');

        $query = TodoTemplate::where('business_id', $business->id)
            ->withCount('items')
            ->orderBy('usage_count', 'desc')
            ->orderBy('name');

        if ($category !== 'all') {
            $query->byCategory($category);
        }

        $templates = $query->get()->map(fn($t) => $this->formatTemplateForResponse($t));

        return Inertia::render('Business/Todos/Templates', [
            'templates' => $templates,
            'categories' => TodoTemplate::CATEGORIES,
            'categoryIcons' => TodoTemplate::CATEGORY_ICONS,
            'currentCategory' => $category,
        ]);
    }

    /**
     * Get all templates (API)
     */
    public function list(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $templates = TodoTemplate::where('business_id', $business->id)
            ->active()
            ->withCount('items')
            ->orderBy('usage_count', 'desc')
            ->get()
            ->map(fn($t) => $this->formatTemplateForResponse($t));

        return response()->json([
            'templates' => $templates,
        ]);
    }

    /**
     * Format template for response
     */
    protected function formatTemplateForResponse(TodoTemplate $template): array
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'description' => $template->description,
            'category' => $template->category,
            'category_label' => $template->category_label,
            'icon' => $template->category_icon,
            'color' => $template->color,
            'is_active' => $template->is_active,
            'items_count' => $template->items_count ?? $template->items()->count(),
            'usage_count' => $template->usage_count,
            'created_at' => $template->created_at->format('d.m.Y'),
        ];
    }

    /**
     * Get single template with items
     */
    public function show(TodoTemplate $template)
    {
        $business = $this->getCurrentBusiness();

        if (!$business || $template->business_id !== $business->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $template->load(['rootItems.children']);

        return response()->json([
            'template' => $this->formatTemplateForResponse($template),
            'items' => $this->formatItemsHierarchy($template->rootItems),
        ]);
    }

    /**
     * Format items with hierarchy
     */
    protected function formatItemsHierarchy($items): array
    {
        return $items->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'order' => $item->order,
                'default_assignee_role' => $item->default_assignee_role,
                'role_label' => $item->role_label,
                'due_days_offset' => $item->due_days_offset,
                'due_days_label' => $item->due_days_label,
                'children' => $this->formatItemsHierarchy($item->children),
            ];
        })->toArray();
    }

    /**
     * Store a new template
     */
    public function store(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return response()->json(['error' => 'Biznes topilmadi'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:onboarding,sales,operations,marketing,custom',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'items' => 'nullable|array',
            'items.*.title' => 'required|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.default_assignee_role' => 'nullable|in:owner,manager,operator',
            'items.*.due_days_offset' => 'nullable|integer',
            'items.*.children' => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {
            $template = TodoTemplate::create([
                'business_id' => $business->id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'category' => $validated['category'],
                'icon' => $validated['icon'] ?? null,
                'color' => $validated['color'] ?? null,
                'is_active' => true,
            ]);

            // Create items
            if (!empty($validated['items'])) {
                $this->createItemsFromArray($template, $validated['items'], null);
            }

            DB::commit();

            $template->loadCount('items');

            return response()->json([
                'success' => true,
                'message' => 'Shablon yaratildi',
                'template' => $this->formatTemplateForResponse($template),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Xatolik: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create items from array recursively
     */
    protected function createItemsFromArray(TodoTemplate $template, array $items, ?string $parentId): void
    {
        foreach ($items as $index => $itemData) {
            $item = TodoTemplateItem::create([
                'template_id' => $template->id,
                'parent_id' => $parentId,
                'title' => $itemData['title'],
                'description' => $itemData['description'] ?? null,
                'order' => $index,
                'default_assignee_role' => $itemData['default_assignee_role'] ?? null,
                'due_days_offset' => $itemData['due_days_offset'] ?? null,
            ]);

            // Create children
            if (!empty($itemData['children'])) {
                $this->createItemsFromArray($template, $itemData['children'], $item->id);
            }
        }
    }

    /**
     * Update a template
     */
    public function update(Request $request, TodoTemplate $template)
    {
        $business = $this->getCurrentBusiness();

        if (!$business || $template->business_id !== $business->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category' => 'sometimes|in:onboarding,sales,operations,marketing,custom',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
        ]);

        $template->update($validated);
        $template->loadCount('items');

        return response()->json([
            'success' => true,
            'message' => 'Shablon yangilandi',
            'template' => $this->formatTemplateForResponse($template),
        ]);
    }

    /**
     * Delete a template
     */
    public function destroy(TodoTemplate $template)
    {
        $business = $this->getCurrentBusiness();

        if (!$business || $template->business_id !== $business->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Shablon o\'chirildi',
        ]);
    }

    /**
     * Duplicate a template
     */
    public function duplicate(TodoTemplate $template)
    {
        $business = $this->getCurrentBusiness();

        if (!$business || $template->business_id !== $business->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $newTemplate = $template->duplicate();
        $newTemplate->loadCount('items');

        return response()->json([
            'success' => true,
            'message' => 'Shablon nusxalandi',
            'template' => $this->formatTemplateForResponse($newTemplate),
        ]);
    }

    /**
     * Apply template - create todos from template
     */
    public function apply(Request $request, TodoTemplate $template)
    {
        $business = $this->getCurrentBusiness();

        if (!$business || $template->business_id !== $business->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $validated = $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
            'type' => 'nullable|in:personal,team,process',
            'base_date' => 'nullable|date',
        ]);

        try {
            $baseDate = isset($validated['base_date']) ? \Carbon\Carbon::parse($validated['base_date']) : null;

            $todos = $template->createTodosFromTemplate(
                $validated['assigned_to'] ?? null,
                $validated['type'] ?? null,
                $baseDate
            );

            return response()->json([
                'success' => true,
                'message' => "{$todos->count()} ta vazifa yaratildi",
                'todos_count' => $todos->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Xatolik: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ==================== Template Items ====================

    /**
     * Add item to template
     */
    public function addItem(Request $request, TodoTemplate $template)
    {
        $business = $this->getCurrentBusiness();

        if (!$business || $template->business_id !== $business->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $validated = $request->validate([
            'parent_id' => 'nullable|exists:todo_template_items,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'default_assignee_role' => 'nullable|in:owner,manager,operator',
            'due_days_offset' => 'nullable|integer',
        ]);

        $item = $template->addItem($validated, $validated['parent_id'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Element qo\'shildi',
            'item' => [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'order' => $item->order,
                'default_assignee_role' => $item->default_assignee_role,
                'role_label' => $item->role_label,
                'due_days_offset' => $item->due_days_offset,
                'due_days_label' => $item->due_days_label,
                'children' => [],
            ],
        ]);
    }

    /**
     * Update template item
     */
    public function updateItem(Request $request, TodoTemplate $template, TodoTemplateItem $item)
    {
        $business = $this->getCurrentBusiness();

        if (!$business || $template->business_id !== $business->id || $item->template_id !== $template->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'default_assignee_role' => 'nullable|in:owner,manager,operator',
            'due_days_offset' => 'nullable|integer',
        ]);

        $item->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Element yangilandi',
            'item' => [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'order' => $item->order,
                'default_assignee_role' => $item->default_assignee_role,
                'role_label' => $item->role_label,
                'due_days_offset' => $item->due_days_offset,
                'due_days_label' => $item->due_days_label,
            ],
        ]);
    }

    /**
     * Delete template item
     */
    public function deleteItem(TodoTemplate $template, TodoTemplateItem $item)
    {
        $business = $this->getCurrentBusiness();

        if (!$business || $template->business_id !== $business->id || $item->template_id !== $template->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Element o\'chirildi',
        ]);
    }

    /**
     * Reorder template items
     */
    public function reorderItems(Request $request, TodoTemplate $template)
    {
        $business = $this->getCurrentBusiness();

        if (!$business || $template->business_id !== $business->id) {
            return response()->json(['error' => 'Ruxsat yo\'q'], 403);
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:todo_template_items,id',
            'items.*.order' => 'required|integer|min:0',
            'items.*.parent_id' => 'nullable|exists:todo_template_items,id',
        ]);

        DB::beginTransaction();

        try {
            foreach ($validated['items'] as $itemData) {
                TodoTemplateItem::where('id', $itemData['id'])
                    ->where('template_id', $template->id)
                    ->update([
                        'order' => $itemData['order'],
                        'parent_id' => $itemData['parent_id'] ?? null,
                    ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tartib saqlandi',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Xatolik: ' . $e->getMessage(),
            ], 500);
        }
    }
}
