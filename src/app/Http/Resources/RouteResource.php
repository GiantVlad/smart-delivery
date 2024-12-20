<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RouteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'pointId' => $this->point_id,
            'pointAddress' => $this->whenLoaded('point') ? $this->point->address : '',
            'sequence' => $this->sequence,
            'type' => $this->point_type,
        ];
    }
}
