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
            'lat' => $this->whenLoaded('point') ? $this->point->lat : null,
            'lng' => $this->whenLoaded('point') ? $this->point->long : null,
            'sequence' => $this->sequence,
            'type' => $this->point_type,
        ];
    }
}
