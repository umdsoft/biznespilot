# Todo List Tizimi - Texnik Hujjat

## Umumiy Ma'lumot

Bu hujjat BiznePilot CRM tizimidagi Todo List modulining to'liq texnik tavsifini o'z ichiga oladi. Tizim biznes egasi va jamoasi uchun vazifalarni boshqarish imkonini beradi.

---

## Database Strukturasi

### 1. `todos` - Asosiy vazifalar jadvali

```sql
Schema::create('todos', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('business_id');
    $table->uuid('created_by');
    $table->uuid('assigned_to')->nullable();
    $table->uuid('parent_id')->nullable(); // Sub-task uchun
    $table->string('title');
    $table->text('description')->nullable();
    $table->enum('type', ['personal', 'team', 'process'])->default('personal');
    $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
    $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
    $table->timestamp('due_date')->nullable();
    $table->timestamp('reminder_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->integer('order')->default(0);
    $table->boolean('is_recurring')->default(false);
    $table->uuid('recurrence_id')->nullable();
    $table->uuid('template_id')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

### 2. `todo_recurrences` - Takrorlanish qoidalari

```sql
Schema::create('todo_recurrences', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('business_id');
    $table->uuid('todo_id');
    $table->enum('frequency', ['daily', 'weekly', 'monthly', 'yearly']);
    $table->integer('interval')->default(1);
    $table->json('days_of_week')->nullable(); // [1,3,5] = Du, Cho, Ju
    $table->integer('day_of_month')->nullable();
    $table->date('start_date');
    $table->date('end_date')->nullable();
    $table->date('next_occurrence');
    $table->enum('generation_mode', ['advance', 'on_time'])->default('on_time');
    $table->integer('occurrences_count')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

### 3. `todo_templates` - Shablonlar

```sql
Schema::create('todo_templates', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('business_id');
    $table->string('name');
    $table->text('description')->nullable();
    $table->enum('category', ['onboarding', 'sales', 'operations', 'marketing', 'custom'])->default('custom');
    $table->string('icon')->nullable();
    $table->string('color')->nullable();
    $table->boolean('is_active')->default(true);
    $table->integer('usage_count')->default(0);
    $table->timestamps();
});
```

### 4. `todo_template_items` - Shablon elementlari

```sql
Schema::create('todo_template_items', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('template_id');
    $table->uuid('parent_id')->nullable();
    $table->string('title');
    $table->text('description')->nullable();
    $table->integer('order')->default(0);
    $table->string('default_assignee_role')->nullable();
    $table->integer('due_days_offset')->nullable();
    $table->timestamps();
});
```

---

## Model Strukturasi

### Todo Model (`app/Models/Todo.php`)

**Relationships:**
```php
public function business(): BelongsTo
public function creator(): BelongsTo      // created_by -> users
public function assignee(): BelongsTo     // assigned_to -> users
public function parent(): BelongsTo       // parent_id -> todos
public function subtasks(): HasMany       // todos where parent_id = this.id
public function recurrence(): BelongsTo   // recurrence_id -> todo_recurrences
public function template(): BelongsTo     // template_id -> todo_templates
```

**Scopes:**
```php
public function scopeRootLevel($query)    // parent_id = null
public function scopePending($query)      // status = pending
public function scopeOverdue($query)      // due_date < now && status != completed
public function scopeToday($query)        // due_date = today
public function scopeMyTodos($query, $userId)
public function scopePersonal($query)     // type = personal
public function scopeTeam($query)         // type = team
```

**Accessors:**
```php
public function getIsOverdueAttribute(): bool
public function getProgressAttribute(): int  // Sub-task completion %
public function getIsCompletedAttribute(): bool
```

### TodoRecurrence Model (`app/Models/TodoRecurrence.php`)

**Methods:**
```php
public function calculateNextOccurrence(): Carbon
public function shouldGenerate(): bool
public function generateNextTodo(): ?Todo
```

**Scopes:**
```php
public function scopeActive($query)
public function scopeDue($query)
```

### TodoTemplate Model (`app/Models/TodoTemplate.php`)

**Relationships:**
```php
public function business(): BelongsTo
public function items(): HasMany          // todo_template_items
public function rootItems(): HasMany      // items where parent_id = null
```

**Methods:**
```php
public function createTodosFromTemplate(?User $assignee = null): Collection
```

**Muhim:** `teamMembers()` metodini ishlatish kerak (`members()` emas!):
```php
// TO'G'RI:
$business->teamMembers()->wherePivot('role', $role)->first();

// NOTO'G'RI:
$business->members()->where('role', $role)->first();
```

---

## Controller va Routes

### Routes (`routes/web.php`)

```php
// Todo routes
Route::prefix('business')->middleware(['auth', 'business'])->group(function () {
    // Todos
    Route::get('/todos', [TodoController::class, 'index'])->name('business.todos.index');
    Route::get('/todos/dashboard', [TodoController::class, 'dashboard'])->name('business.todos.dashboard');
    Route::post('/todos', [TodoController::class, 'store'])->name('business.todos.store');
    Route::get('/todos/{todo}', [TodoController::class, 'show'])->name('business.todos.show');
    Route::put('/todos/{todo}', [TodoController::class, 'update'])->name('business.todos.update');
    Route::delete('/todos/{todo}', [TodoController::class, 'destroy'])->name('business.todos.destroy');
    Route::post('/todos/{todo}/toggle', [TodoController::class, 'toggle'])->name('business.todos.toggle');

    // Subtasks
    Route::post('/todos/{todo}/subtasks/{subtask}/toggle', [TodoController::class, 'toggleSubtask'])
        ->name('business.todos.subtasks.toggle');

    // Recurrence
    Route::post('/todos/{todo}/recurrence', [TodoController::class, 'storeRecurrence'])
        ->name('business.todos.recurrence.store');

    // Todo Recurrences
    Route::put('/todo-recurrences/{recurrence}', [TodoController::class, 'updateRecurrence'])
        ->name('business.todo-recurrences.update');
    Route::delete('/todo-recurrences/{recurrence}', [TodoController::class, 'destroyRecurrence'])
        ->name('business.todo-recurrences.destroy');

    // Templates
    Route::get('/todo-templates', [TodoTemplateController::class, 'index'])
        ->name('business.todo-templates.index');
    Route::post('/todo-templates', [TodoTemplateController::class, 'store'])
        ->name('business.todo-templates.store');
    Route::get('/todo-templates/{template}', [TodoTemplateController::class, 'show'])
        ->name('business.todo-templates.show');
    Route::put('/todo-templates/{template}', [TodoTemplateController::class, 'update'])
        ->name('business.todo-templates.update');
    Route::delete('/todo-templates/{template}', [TodoTemplateController::class, 'destroy'])
        ->name('business.todo-templates.destroy');
    Route::post('/todo-templates/{template}/apply', [TodoTemplateController::class, 'apply'])
        ->name('business.todo-templates.apply');
    Route::post('/todo-templates/{template}/duplicate', [TodoTemplateController::class, 'duplicate'])
        ->name('business.todo-templates.duplicate');
});
```

### TodoController (`app/Http/Controllers/TodoController.php`)

**Asosiy metodlar:**
- `index()` - Sahifa + filtrlangan vazifalar
- `dashboard()` - JSON API (Dashboard widget uchun)
- `store()` - Yangi vazifa yaratish
- `update()` - Vazifani yangilash
- `destroy()` - Vazifani o'chirish
- `toggle()` - Bajarildi/Bajarilmadi
- `toggleSubtask()` - Sub-task toggle
- `storeRecurrence()` - Takrorlash qo'shish
- `updateRecurrence()` - Takrorlashni yangilash
- `destroyRecurrence()` - Takrorlashni o'chirish

---

## Frontend Komponentlar

### Sahifalar

| Fayl | Tavsif |
|------|--------|
| `resources/js/Pages/Business/Todos/Index.vue` | Asosiy vazifalar sahifasi |
| `resources/js/Pages/Business/Todos/Templates.vue` | Shablonlar boshqaruvi |

### Komponentlar

| Fayl | Tavsif |
|------|--------|
| `resources/js/components/todos/TodoModal.vue` | Vazifa yaratish/tahrirlash modal |
| `resources/js/components/todos/RecurrenceModal.vue` | Takrorlash sozlamalari modal |
| `resources/js/components/todos/TemplateModal.vue` | Shablon yaratish/tahrirlash modal |
| `resources/js/components/todos/DashboardWidget.vue` | Dashboard widget |

### Layout O'zgarishlari

**BusinessLayout.vue** ga qo'shilgan:
- Sidebar'da "Todo List" link
- `todoStats` polling (30 soniyada bir)
- Overdue badge ko'rsatish

---

## Cron Job / Scheduled Task

### GenerateRecurringTodos Command

**Fayl:** `app/Console/Commands/GenerateRecurringTodos.php`

**Ishga tushirish:**
```bash
php artisan todos:generate-recurring
php artisan todos:generate-recurring --force  # Majburiy yaratish
```

**Schedule (`routes/console.php`):**
```php
Schedule::command('todos:generate-recurring')
    ->dailyAt('06:00')
    ->timezone('Asia/Tashkent')
    ->name('generate-recurring-todos')
    ->onOneServer();
```

---

## Muhim Texnik Eslatmalar

### 1. JavaScript Hoisting Muammosi

Vue 3 Composition API da `watch` callback'ida funksiya chaqirilganda, u funksiya **OLDIN** e'lon qilingan bo'lishi kerak:

```javascript
// TO'G'RI:
const resetForm = () => { /* ... */ };

watch(() => props.todo, (newTodo) => {
    if (!newTodo) resetForm();  // resetForm yuqorida e'lon qilingan
}, { immediate: true });

// NOTO'G'RI:
watch(() => props.todo, (newTodo) => {
    if (!newTodo) resetForm();  // XATO: resetForm hali e'lon qilinmagan
}, { immediate: true });

const resetForm = () => { /* ... */ };
```

**Bu muammo tuzatilgan fayllar:**
- `TodoModal.vue`
- `RecurrenceModal.vue`
- `TemplateModal.vue`

### 2. Business Model Relationship

Business modelda jamoa a'zolarini olish uchun `teamMembers()` metodini ishlatish kerak:

```php
// TO'G'RI:
$business->teamMembers()->get();
$business->teamMembers()->wherePivot('role', 'manager')->first();

// NOTO'G'RI:
$business->members()->get();  // Bunday metod YO'Q!
```

### 3. UUID Primary Key

Barcha jadvallar UUID primary key ishlatadi. Modelda:

```php
use HasUuid;

protected $keyType = 'string';
public $incrementing = false;
```

---

## API Response Formatlari

### Dashboard API (`GET /business/todos/dashboard`)

```json
{
    "todos": [
        {
            "id": "uuid",
            "title": "Vazifa nomi",
            "priority": "high",
            "is_completed": false,
            "due_time": "14:00"
        }
    ],
    "stats": {
        "total_today": 5,
        "completed_today": 2,
        "overdue": 1,
        "progress": 40
    }
}
```

### Index API (Inertia Props)

```php
[
    'todos' => [
        'overdue' => [...],
        'today' => [...],
        'tomorrow' => [...],
        'this_week' => [...],
        'later' => [...],
        'no_date' => [...]
    ],
    'stats' => [
        'total' => 10,
        'overdue' => 2,
        'today' => 3,
        'completed_today' => 1,
        'my_todos' => 5
    ],
    'teamMembers' => [...],
    'templates' => [...],
    'types' => [...],
    'priorities' => [...],
    'statuses' => [...],
    'filter' => 'all',
    'statusFilter' => 'active'
]
```

---

## Fayllar Ro'yxati

### Backend

| Fayl | Turi |
|------|------|
| `database/migrations/xxxx_create_todos_table.php` | Migration |
| `database/migrations/xxxx_create_todo_recurrences_table.php` | Migration |
| `database/migrations/xxxx_create_todo_templates_table.php` | Migration |
| `database/migrations/xxxx_create_todo_template_items_table.php` | Migration |
| `app/Models/Todo.php` | Model |
| `app/Models/TodoRecurrence.php` | Model |
| `app/Models/TodoTemplate.php` | Model |
| `app/Models/TodoTemplateItem.php` | Model |
| `app/Http/Controllers/TodoController.php` | Controller |
| `app/Http/Controllers/TodoTemplateController.php` | Controller |
| `app/Console/Commands/GenerateRecurringTodos.php` | Command |
| `routes/web.php` | Routes (qo'shilgan) |
| `routes/console.php` | Schedule (qo'shilgan) |

### Frontend

| Fayl | Turi |
|------|------|
| `resources/js/Pages/Business/Todos/Index.vue` | Page |
| `resources/js/Pages/Business/Todos/Templates.vue` | Page |
| `resources/js/components/todos/TodoModal.vue` | Component |
| `resources/js/components/todos/RecurrenceModal.vue` | Component |
| `resources/js/components/todos/TemplateModal.vue` | Component |
| `resources/js/components/todos/DashboardWidget.vue` | Component |
| `resources/js/layouts/BusinessLayout.vue` | Layout (o'zgartirilgan) |

---

## Test Qilish

### Backend Test

```bash
# Migratsiyalar
php artisan migrate

# Recurring todos generatsiyasi
php artisan todos:generate-recurring --force

# Routes ro'yxati
php artisan route:list --name=business.todo
```

### Frontend Test

1. `/business/todos` - Vazifalar sahifasi
2. `/business/todo-templates` - Shablonlar sahifasi
3. Dashboard widget - `/business/dashboard`

---

## Xatolar va Yechimlari

| Xato | Yechim |
|------|--------|
| `BadMethodCallException: Call to undefined method Business::members()` | `members()` ni `teamMembers()` ga o'zgartiring |
| `Cannot access 'resetForm' before initialization` | `resetForm` funksiyasini `watch` dan OLDIN e'lon qiling |
| `Route [business.todos.index] not defined` | `routes/web.php` ga todo routes qo'shing |

---

## Versiya

- **Yaratilgan:** 2026-01-09
- **Laravel:** 11.x
- **Vue:** 3.x
- **Inertia:** 1.x

---

*Bu hujjat BiznePilot CRM Todo List tizimining to'liq texnik tavsifidir.*
