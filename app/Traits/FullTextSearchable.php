<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * PERFORMANCE: Full-text search trait for Eloquent models
 *
 * Usage in model:
 * use FullTextSearchable;
 *
 * protected $searchableColumns = ['title', 'description'];
 *
 * Usage in controller:
 * $results = Model::search('search query')->get();
 * $results = Model::search('search query', ['title'])->paginate(20);
 */
trait FullTextSearchable
{
    /**
     * Scope for full-text search
     *
     * @param  string  $term  Search term
     * @param  array|null  $columns  Columns to search (defaults to $searchableColumns)
     * @param  string  $mode  Search mode: 'natural', 'boolean', 'expansion'
     */
    public function scopeSearch(Builder $query, string $term, ?array $columns = null, string $mode = 'natural'): Builder
    {
        if (empty(trim($term))) {
            return $query;
        }

        $columns = $columns ?? $this->getSearchableColumns();

        if (empty($columns)) {
            return $query;
        }

        $columnList = implode(', ', $columns);
        $term = $this->sanitizeSearchTerm($term);

        // Build the MATCH AGAINST query based on mode
        $matchQuery = match ($mode) {
            'boolean' => "MATCH({$columnList}) AGAINST(? IN BOOLEAN MODE)",
            'expansion' => "MATCH({$columnList}) AGAINST(? WITH QUERY EXPANSION)",
            default => "MATCH({$columnList}) AGAINST(?)",
        };

        // For boolean mode, prepare the term
        if ($mode === 'boolean') {
            $term = $this->prepareBooleanTerm($term);
        }

        return $query->whereRaw($matchQuery, [$term])
            ->selectRaw("*, {$matchQuery} as search_score", [$term])
            ->orderByDesc('search_score');
    }

    /**
     * Scope for searching with LIKE fallback when fulltext is not available
     */
    public function scopeSearchLike(Builder $query, string $term, ?array $columns = null): Builder
    {
        if (empty(trim($term))) {
            return $query;
        }

        $columns = $columns ?? $this->getSearchableColumns();

        if (empty($columns)) {
            return $query;
        }

        $term = '%'.$this->sanitizeSearchTerm($term).'%';

        return $query->where(function ($q) use ($columns, $term) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'LIKE', $term);
            }
        });
    }

    /**
     * Scope for combined fulltext + LIKE search (fallback)
     */
    public function scopeSmartSearch(Builder $query, string $term, ?array $columns = null): Builder
    {
        if (empty(trim($term))) {
            return $query;
        }

        $columns = $columns ?? $this->getSearchableColumns();

        if (empty($columns)) {
            return $query;
        }

        // Try fulltext first, fallback to LIKE for short terms
        if (strlen($term) >= 3) {
            try {
                return $this->scopeSearch($query, $term, $columns);
            } catch (\Exception $e) {
                // Fallback to LIKE if fulltext fails
                return $this->scopeSearchLike($query, $term, $columns);
            }
        }

        return $this->scopeSearchLike($query, $term, $columns);
    }

    /**
     * Get searchable columns from model property
     */
    protected function getSearchableColumns(): array
    {
        return $this->searchableColumns ?? [];
    }

    /**
     * Sanitize search term to prevent SQL injection
     */
    protected function sanitizeSearchTerm(string $term): string
    {
        // Remove special characters that could break the query
        $term = preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $term);

        return trim($term);
    }

    /**
     * Prepare term for boolean mode search
     */
    protected function prepareBooleanTerm(string $term): string
    {
        $words = preg_split('/\s+/', trim($term));
        $prepared = [];

        foreach ($words as $word) {
            if (strlen($word) >= 2) {
                // Add + to require the word, * for wildcard
                $prepared[] = '+'.$word.'*';
            }
        }

        return implode(' ', $prepared);
    }
}
