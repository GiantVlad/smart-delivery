<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Timeslot duration in minutes
    |--------------------------------------------------------------------------
    |
    |
    */

    'slot_duration' => env('SETTINGS_SLOT_DURATION', 240),
    'working_hours' => ['from' => "8:00", 'to' => "22:00"],
    'default_capacity_per_slot' => 5,
];
