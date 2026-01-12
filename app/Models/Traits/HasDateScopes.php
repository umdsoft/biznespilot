<?php

namespace App\Models\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Provides common date-based scoping functionality
 * Reduces code duplication across models that need date filtering
 */
trait HasDateScopes
{
    /**
     * Get the date column to use for date scopes.
     * Override this method in your model if you need a different column.
     */
    protected function getDateColumn(): string
    {
        return $this->dateColumn ?? 'created_at';
    }

    /**
     * Scope a query to records from today.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate($this->getDateColumn(), Carbon::today());
    }

    /**
     * Scope a query to records from yesterday.
     */
    public function scopeYesterday(Builder $query): Builder
    {
        return $query->whereDate($this->getDateColumn(), Carbon::yesterday());
    }

    /**
     * Scope a query to records from this week.
     */
    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween($this->getDateColumn(), [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek(),
        ]);
    }

    /**
     * Scope a query to records from last week.
     */
    public function scopeLastWeek(Builder $query): Builder
    {
        return $query->whereBetween($this->getDateColumn(), [
            Carbon::now()->subWeek()->startOfWeek(),
            Carbon::now()->subWeek()->endOfWeek(),
        ]);
    }

    /**
     * Scope a query to records from this month.
     */
    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth($this->getDateColumn(), Carbon::now()->month)
            ->whereYear($this->getDateColumn(), Carbon::now()->year);
    }

    /**
     * Scope a query to records from last month.
     */
    public function scopeLastMonth(Builder $query): Builder
    {
        $lastMonth = Carbon::now()->subMonth();
        return $query->whereMonth($this->getDateColumn(), $lastMonth->month)
            ->whereYear($this->getDateColumn(), $lastMonth->year);
    }

    /**
     * Scope a query to records from this year.
     */
    public function scopeThisYear(Builder $query): Builder
    {
        return $query->whereYear($this->getDateColumn(), Carbon::now()->year);
    }

    /**
     * Scope a query to records from last year.
     */
    public function scopeLastYear(Builder $query): Builder
    {
        return $query->whereYear($this->getDateColumn(), Carbon::now()->subYear()->year);
    }

    /**
     * Scope a query to records within a date range.
     */
    public function scopeDateRange(Builder $query, $startDate, $endDate): Builder
    {
        $start = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $end = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);

        return $query->whereBetween($this->getDateColumn(), [
            $start->startOfDay(),
            $end->endOfDay(),
        ]);
    }

    /**
     * Scope a query to records from a specific date.
     */
    public function scopeOnDate(Builder $query, $date): Builder
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        return $query->whereDate($this->getDateColumn(), $date);
    }

    /**
     * Scope a query to records from the last N days.
     */
    public function scopeLastDays(Builder $query, int $days): Builder
    {
        return $query->where($this->getDateColumn(), '>=', Carbon::now()->subDays($days));
    }

    /**
     * Scope a query to records from the last N months.
     */
    public function scopeLastMonths(Builder $query, int $months): Builder
    {
        return $query->where($this->getDateColumn(), '>=', Carbon::now()->subMonths($months));
    }

    /**
     * Scope a query to records from a specific month and year.
     */
    public function scopeForMonth(Builder $query, int $month, ?int $year = null): Builder
    {
        $year = $year ?? Carbon::now()->year;
        return $query->whereMonth($this->getDateColumn(), $month)
            ->whereYear($this->getDateColumn(), $year);
    }

    /**
     * Scope a query to records for a specific year.
     */
    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->whereYear($this->getDateColumn(), $year);
    }
}
