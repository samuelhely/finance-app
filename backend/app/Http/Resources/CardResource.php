<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'brand' => $this->brand,
            'four_digits' => $this->last_four_digits,
            'limit' => $this->limit,
            'closing_day' => $this->closing_day,
            'due_day' => $this->due_day,
            'is_active' => $this->is_active,
        ];
    }
}
