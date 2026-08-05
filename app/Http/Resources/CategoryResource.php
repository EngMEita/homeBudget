<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'household_id' => $this->household_id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'type' => $this->type,
            'is_active' => $this->is_active,
        ];
    }
}
