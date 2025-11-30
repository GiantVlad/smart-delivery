<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Slots\SlotManager;
use App\Http\Requests\CreateSlotForDayRequest;
use App\Http\Requests\UpdateSlotRequest;
use App\Http\Resources\SlotResource;
use App\Models\Slot;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Nette\Schema\ValidationException;

class SlotController extends Controller
{
    public function getSlots(SlotManager $slot): JsonResource
    {
        $slots = $slot->getAll();

        return SlotResource::collection($slots);
    }
    public function getAvailableByDate(string $date, SlotManager $slot): JsonResource
    {
        try {
            $date = Carbon::parse($date);
        } catch (\Throwable $e) {
            throw new ValidationException('Invalid date');
        }

        if ($date->lt('today')) {
            throw new ValidationException('Date in the past');
        }

        $slots = $slot->getAvailable($date);

        return SlotResource::collection($slots);
    }

    public function generateDefault(SlotManager $slotManager): JsonResource
    {
        $slots = $slotManager->generateDefaultSlots();

        return SlotResource::collection($slots);
    }

    public function createForDay(CreateSlotForDayRequest $request, SlotManager $slotManager): JsonResource
    {
        $slots = $slotManager->cloneDefaultSlotsForDate(Carbon::parse($request->date));

        return SlotResource::collection($slots);
    }

    public function updateCapacity(UpdateSlotRequest $request): JsonResource
    {
        $slot = Slot::findOrFail((int)$request->id);
        $slot->available = max(0, ((int)$request->capacity - $slot->capacity) + $slot->available);
        $slot->capacity = (int)$request->capacity;
        $slot->save();

        return SlotResource::make($slot);
    }
}
