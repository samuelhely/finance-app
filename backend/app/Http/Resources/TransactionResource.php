<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'type' => $this->type,
            'payment_method' => $this->payment_method,
            'description' => $this->description,
            'notes' => $this->notes,
            'amount' => $this->amount,
            'date' => $this->date,
            'occurrence_status' => $this->occurrence_status,
            'source_type' => $this->source_type,
            'account' => $this->whenLoaded('account', fn () => new AccountResource($this->account)),
            'category' => $this->whenLoaded('category', fn () => new CategoryResource($this->category)),
            'card' => $this->whenLoaded('card', fn () => new CardResource($this->card)),
            'tags' => $this->whenLoaded('tags', fn () => TagResource::collection($this->tags)),
        ];
    }
}
