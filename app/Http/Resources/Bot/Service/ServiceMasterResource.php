<?php

namespace App\Http\Resources\Bot\Service;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceMasterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'avatar_url' => $this->avatar_url,
            'specializations' => $this->specializations,
            'experience_years' => $this->experience_years,
            'bio' => $this->bio,
            'warranty_months' => $this->warranty_months,
            'rating_avg' => (float) $this->rating_avg,
            'rating_count' => $this->rating_count,
            'completed_jobs' => $this->completed_jobs,
            'hourly_rate' => $this->hourly_rate ? (float) $this->hourly_rate : null,
            'is_available' => $this->is_available,
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
            ])),
        ];
    }
}
