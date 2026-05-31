<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $timeRanges = $this->time_ranges ?? [];
        if (empty($timeRanges) && $this->date && $this->from && $this->to) {
            $timeRanges = [[
                'slot_id' => null,
                'date' => $this->date,
                'from' => $this->from,
                'to' => $this->to,
            ]];
        }

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'unitType' => $this->unit_type,
            'status' => $this->status,
            'customerEmail' => $this->whenLoaded('customer') ? $this->customer->email : '',
            'taskCourierName' => $this->task->courier->name ?? 'Undefined',
            'startPointAddress' => $this->startPoint?->address,
            'endPointAddress' => $this->endPoint?->address,
            'startPointId' => $this->startPoint?->id,
            'endPointId' => $this->endPoint?->id,
            'date' => $this->date,
            'from' => $this->from,
            'to' => $this->to,
            'timeRanges' => $timeRanges,
            'updated_at' => Carbon::parse($this->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}
