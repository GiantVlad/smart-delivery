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
            'unitType' => $this->unit_type,
            'status' => $this->status,
            'customerEmail' => $this->whenLoaded('customer') ? $this->customer->email : '',
            'taskCourierName' => $this->task->courier->name ?? 'Undefined',
            'startPointAddress' => $this->startPoint->address,
            'endPointAddress' => $this->endPoint->address,
            'startPointId' => $this->startPoint->id,
            'endPointId' => $this->endPoint->id,
            'updated_at' => $this->updated_at,
        ];
    }
}
