<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'uuid' => $this->uuid,
            'unit_type' => $this->unit_type,
            'status' => $this->status,
            'customerUuid ' => $this->customer->uuid,
            'taskCourierName' => $this->unit_type,
            'startPointAddress' => $this->startPoint->address,
            'endPointAddress' => $this->endPoint->address,
            'updated_at' => $this->updated_at,
        ];
    }
}
