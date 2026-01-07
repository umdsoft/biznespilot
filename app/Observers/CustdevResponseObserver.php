<?php

namespace App\Observers;

use App\Models\CustdevResponse;
use App\Models\CustdevAnswer;
use App\Models\DreamBuyer;

class CustdevResponseObserver
{
    /**
     * Handle the CustdevResponse "updated" event.
     * When response is marked as completed, sync data to Dream Buyer
     */
    public function updated(CustdevResponse $response): void
    {
        // Only sync when status changes to completed
        if ($response->isDirty('status') && $response->status === 'completed') {
            $this->syncToDreamBuyer($response);
        }
    }

    /**
     * Sync response data to Dream Buyer profile
     */
    protected function syncToDreamBuyer(CustdevResponse $response): void
    {
        $survey = $response->survey;

        if (!$survey || !$survey->dream_buyer_id) {
            return;
        }

        $dreamBuyer = DreamBuyer::find($survey->dream_buyer_id);

        if (!$dreamBuyer) {
            return;
        }

        // Get all answers for this response
        $answers = $response->answers()->with('question')->get();

        // Aggregate data by category
        $categoryData = [];

        foreach ($answers as $answer) {
            $question = $answer->question;

            if (!$question || !$question->category || $question->category === 'custom' || $question->category === 'satisfaction') {
                continue;
            }

            $category = $question->category;
            $values = [];

            if (in_array($question->type, ['select', 'multiselect'])) {
                $values = $answer->selected_options ?? [];
            } elseif ($answer->answer) {
                $values = [$answer->answer];
            }

            if (!empty($values)) {
                if (!isset($categoryData[$category])) {
                    $categoryData[$category] = [];
                }
                $categoryData[$category] = array_merge($categoryData[$category], $values);
            }
        }

        // Build update data - merge with existing
        $updateData = [];

        $categoryMapping = [
            'where_spend_time' => 'where_spend_time',
            'info_sources' => 'info_sources',
            'frustrations' => 'frustrations',
            'dreams' => 'dreams',
            'fears' => 'fears',
            'communication_preferences' => 'communication_preferences',
            'daily_routine' => 'daily_routine',
            'happiness_triggers' => 'happiness_triggers',
        ];

        foreach ($categoryMapping as $category => $field) {
            if (!empty($categoryData[$category])) {
                $existing = $dreamBuyer->$field ? explode("\n", $dreamBuyer->$field) : [];
                $existing = array_filter($existing); // Remove empty
                $merged = array_unique(array_merge($existing, $categoryData[$category]));
                $updateData[$field] = implode("\n", $merged);
            }
        }

        if (!empty($updateData)) {
            $dreamBuyer->update($updateData);
        }
    }
}
