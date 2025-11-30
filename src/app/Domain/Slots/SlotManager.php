<?php

namespace App\Domain\Slots;

use App\Dto\SlotDto;
use App\Exceptions\GeneralDomainException;
use App\Models\Slot;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class SlotManager
{
    public function getAll(): array
    {
        $slots = [];
        $start = new \DateTime(Config::get('settings.working_hours')['from']);
        $end = new \DateTime(Config::get('settings.working_hours')['to']);
        $interval = new \DateInterval('PT'. Config::get('settings.slot_duration') .'M');

        $current = clone $start;

        while ($current < $end) {
            $from = $current->format('H:i');
            $current->add($interval);
            $to = $current->format('H:i');
            $slots[] = new SlotDTO($from, $to);
        }

        return $slots;
    }

    public function getAvailable($date): Collection
    {
        $slots = Slot::where('date', $date->toDateString())->get();
        if ($slots->isEmpty()) {
            return $this->cloneDefaultSlotsForDate($date);
        }
        return $slots->where('available', '>', 0);
    }

    public function generateDefaultSlots(): Collection
    {
        $timeSlots = $this->getAll();
        $toCreate = [];
        foreach ($timeSlots as $slot) {
            $toCreate[] = [
                'from' => $slot->from,
                'to' => $slot->to,
                'capacity' => Config::get('settings.default_capacity_per_slot'),
                'available' => Config::get('settings.default_capacity_per_slot'),
                'created_at' => now(),
                'updated_at' => now(),
                'date' => null,
            ];
        }

        DB::transaction(function () use ($toCreate) {
            Slot::whereNull('date')->delete();
            Slot::insert($toCreate);
        });

        return Slot::all();
    }

    /**
     * @throws GeneralDomainException
     */
    public function cloneDefaultSlotsForDate(Carbon $date): Collection
    {
        $defaultsSlots = Slot::whereNull('date')->get();
        if ($defaultsSlots->isEmpty()) {
            throw new GeneralDomainException('No default slots found');
        }
        $newSlots = [];
        $now = now();

        foreach ($defaultsSlots as $slot) {
            $newSlots[] = [
                'from' => $slot->from,
                'to' => $slot->to,
                'capacity' => $slot->capacity,
                'available' => $slot->capacity,
                'date' => $date->toDateString(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($newSlots)) {
            DB::transaction(function () use ($newSlots, $date) {
                Slot::where('date', $date->toDateString())->delete();
                Slot::insert($newSlots);
            });
        }

        return Slot::where('date', $date->toDateString())->get();
    }
}
