<?php

namespace App\Console\Commands;

use App\Models\TodoRecurrence;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateRecurringTodos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'todos:generate-recurring {--force : Force generation regardless of schedule}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Takrorlanadigan vazifalarni yaratish';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Takrorlanadigan vazifalarni tekshirish...');

        $force = $this->option('force');
        $generatedCount = 0;
        $errorCount = 0;

        // Get all active recurrences that should generate
        $recurrences = TodoRecurrence::active()
            ->with(['todo', 'todo.subtasks'])
            ->get();

        $this->info("Jami {$recurrences->count()} ta faol takrorlanish topildi.");

        $progressBar = $this->output->createProgressBar($recurrences->count());
        $progressBar->start();

        foreach ($recurrences as $recurrence) {
            try {
                // Check if should generate
                if (! $force && ! $recurrence->shouldGenerate()) {
                    $progressBar->advance();

                    continue;
                }

                // Generate new todo
                $newTodo = $recurrence->generateNextTodo();

                if ($newTodo) {
                    $generatedCount++;
                    Log::info('Recurring todo generated', [
                        'recurrence_id' => $recurrence->id,
                        'new_todo_id' => $newTodo->id,
                        'title' => $newTodo->title,
                    ]);
                }
            } catch (\Exception $e) {
                $errorCount++;
                Log::error('Failed to generate recurring todo', [
                    'recurrence_id' => $recurrence->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Xatolik: {$recurrence->id} - {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Yaratildi: {$generatedCount} ta vazifa");

        if ($errorCount > 0) {
            $this->warn("Xatoliklar: {$errorCount}");
        }

        $this->info('Tayyor!');

        return self::SUCCESS;
    }
}
