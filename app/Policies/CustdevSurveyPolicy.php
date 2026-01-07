<?php

namespace App\Policies;

use App\Models\CustdevSurvey;
use App\Models\User;

class CustdevSurveyPolicy
{
    /**
     * Determine whether the user can view the survey.
     */
    public function view(User $user, CustdevSurvey $survey): bool
    {
        return $user->currentBusiness && $user->currentBusiness->id === $survey->business_id;
    }

    /**
     * Determine whether the user can update the survey.
     */
    public function update(User $user, CustdevSurvey $survey): bool
    {
        return $user->currentBusiness && $user->currentBusiness->id === $survey->business_id;
    }

    /**
     * Determine whether the user can delete the survey.
     */
    public function delete(User $user, CustdevSurvey $survey): bool
    {
        return $user->currentBusiness && $user->currentBusiness->id === $survey->business_id;
    }
}
