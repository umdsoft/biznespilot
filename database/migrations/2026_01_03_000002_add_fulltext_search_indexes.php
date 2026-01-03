<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * PERFORMANCE: Adding FULLTEXT indexes for search optimization
     * Expected improvement: 10-100x faster text search queries
     */
    public function up(): void
    {
        // Activity logs - search by description
        if (Schema::hasTable('activity_logs') && $this->hasColumn('activity_logs', 'description')) {
            if (!$this->fullTextIndexExists('activity_logs', 'activity_logs_description_fulltext')) {
                DB::statement('ALTER TABLE activity_logs ADD FULLTEXT INDEX activity_logs_description_fulltext (description)');
            }
        }

        // AI Insights - search by title and description
        if (Schema::hasTable('ai_insights')) {
            if ($this->hasColumn('ai_insights', 'title') && !$this->fullTextIndexExists('ai_insights', 'ai_insights_title_fulltext')) {
                DB::statement('ALTER TABLE ai_insights ADD FULLTEXT INDEX ai_insights_title_fulltext (title)');
            }
            if ($this->hasColumn('ai_insights', 'description') && !$this->fullTextIndexExists('ai_insights', 'ai_insights_description_fulltext')) {
                DB::statement('ALTER TABLE ai_insights ADD FULLTEXT INDEX ai_insights_description_fulltext (description)');
            }
            // Combined search on title and description
            if ($this->hasColumn('ai_insights', 'title') && $this->hasColumn('ai_insights', 'description')) {
                if (!$this->fullTextIndexExists('ai_insights', 'ai_insights_search_fulltext')) {
                    DB::statement('ALTER TABLE ai_insights ADD FULLTEXT INDEX ai_insights_search_fulltext (title, description)');
                }
            }
        }

        // Competitors - search by name and description
        if (Schema::hasTable('competitors')) {
            if ($this->hasColumn('competitors', 'name') && !$this->fullTextIndexExists('competitors', 'competitors_name_fulltext')) {
                DB::statement('ALTER TABLE competitors ADD FULLTEXT INDEX competitors_name_fulltext (name)');
            }
            if ($this->hasColumn('competitors', 'description') && !$this->fullTextIndexExists('competitors', 'competitors_description_fulltext')) {
                DB::statement('ALTER TABLE competitors ADD FULLTEXT INDEX competitors_description_fulltext (description)');
            }
        }

        // Dream buyers - search by name and description
        if (Schema::hasTable('dream_buyers')) {
            if ($this->hasColumn('dream_buyers', 'name') && !$this->fullTextIndexExists('dream_buyers', 'dream_buyers_name_fulltext')) {
                DB::statement('ALTER TABLE dream_buyers ADD FULLTEXT INDEX dream_buyers_name_fulltext (name)');
            }
            if ($this->hasColumn('dream_buyers', 'description') && !$this->fullTextIndexExists('dream_buyers', 'dream_buyers_description_fulltext')) {
                DB::statement('ALTER TABLE dream_buyers ADD FULLTEXT INDEX dream_buyers_description_fulltext (description)');
            }
        }

        // Content posts - search by content
        if (Schema::hasTable('content_posts')) {
            if ($this->hasColumn('content_posts', 'content') && !$this->fullTextIndexExists('content_posts', 'content_posts_content_fulltext')) {
                DB::statement('ALTER TABLE content_posts ADD FULLTEXT INDEX content_posts_content_fulltext (content)');
            }
            if ($this->hasColumn('content_posts', 'title') && !$this->fullTextIndexExists('content_posts', 'content_posts_title_fulltext')) {
                DB::statement('ALTER TABLE content_posts ADD FULLTEXT INDEX content_posts_title_fulltext (title)');
            }
        }

        // Chatbot messages - search by content
        if (Schema::hasTable('chatbot_messages')) {
            if ($this->hasColumn('chatbot_messages', 'content') && !$this->fullTextIndexExists('chatbot_messages', 'chatbot_messages_content_fulltext')) {
                DB::statement('ALTER TABLE chatbot_messages ADD FULLTEXT INDEX chatbot_messages_content_fulltext (content)');
            }
        }

        // Leads - search by name, email, phone, notes
        if (Schema::hasTable('leads')) {
            if ($this->hasColumn('leads', 'name') && !$this->fullTextIndexExists('leads', 'leads_name_fulltext')) {
                DB::statement('ALTER TABLE leads ADD FULLTEXT INDEX leads_name_fulltext (name)');
            }
            if ($this->hasColumn('leads', 'notes') && !$this->fullTextIndexExists('leads', 'leads_notes_fulltext')) {
                DB::statement('ALTER TABLE leads ADD FULLTEXT INDEX leads_notes_fulltext (notes)');
            }
        }

        // Customers - search by name
        if (Schema::hasTable('customers')) {
            if ($this->hasColumn('customers', 'name') && !$this->fullTextIndexExists('customers', 'customers_name_fulltext')) {
                DB::statement('ALTER TABLE customers ADD FULLTEXT INDEX customers_name_fulltext (name)');
            }
        }

        // Chatbot knowledge base - search by question and answer
        if (Schema::hasTable('chatbot_knowledge')) {
            if ($this->hasColumn('chatbot_knowledge', 'question') && !$this->fullTextIndexExists('chatbot_knowledge', 'chatbot_knowledge_question_fulltext')) {
                DB::statement('ALTER TABLE chatbot_knowledge ADD FULLTEXT INDEX chatbot_knowledge_question_fulltext (question)');
            }
            if ($this->hasColumn('chatbot_knowledge', 'answer') && !$this->fullTextIndexExists('chatbot_knowledge', 'chatbot_knowledge_answer_fulltext')) {
                DB::statement('ALTER TABLE chatbot_knowledge ADD FULLTEXT INDEX chatbot_knowledge_answer_fulltext (answer)');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop fulltext indexes
        $this->dropFullTextIndexIfExists('activity_logs', 'activity_logs_description_fulltext');
        $this->dropFullTextIndexIfExists('ai_insights', 'ai_insights_title_fulltext');
        $this->dropFullTextIndexIfExists('ai_insights', 'ai_insights_description_fulltext');
        $this->dropFullTextIndexIfExists('ai_insights', 'ai_insights_search_fulltext');
        $this->dropFullTextIndexIfExists('competitors', 'competitors_name_fulltext');
        $this->dropFullTextIndexIfExists('competitors', 'competitors_description_fulltext');
        $this->dropFullTextIndexIfExists('dream_buyers', 'dream_buyers_name_fulltext');
        $this->dropFullTextIndexIfExists('dream_buyers', 'dream_buyers_description_fulltext');
        $this->dropFullTextIndexIfExists('content_posts', 'content_posts_content_fulltext');
        $this->dropFullTextIndexIfExists('content_posts', 'content_posts_title_fulltext');
        $this->dropFullTextIndexIfExists('chatbot_messages', 'chatbot_messages_content_fulltext');
        $this->dropFullTextIndexIfExists('leads', 'leads_name_fulltext');
        $this->dropFullTextIndexIfExists('leads', 'leads_notes_fulltext');
        $this->dropFullTextIndexIfExists('customers', 'customers_name_fulltext');
        $this->dropFullTextIndexIfExists('chatbot_knowledge', 'chatbot_knowledge_question_fulltext');
        $this->dropFullTextIndexIfExists('chatbot_knowledge', 'chatbot_knowledge_answer_fulltext');
    }

    /**
     * Check if column exists in table
     */
    private function hasColumn(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }

    /**
     * Check if fulltext index exists
     */
    private function fullTextIndexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }

    /**
     * Drop fulltext index if exists
     */
    private function dropFullTextIndexIfExists(string $table, string $indexName): void
    {
        if (Schema::hasTable($table) && $this->fullTextIndexExists($table, $indexName)) {
            DB::statement("ALTER TABLE {$table} DROP INDEX {$indexName}");
        }
    }
};
