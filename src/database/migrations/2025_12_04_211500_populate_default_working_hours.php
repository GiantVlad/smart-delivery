<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $days = [
            ['day' => 'monday', 'from' => '08:00', 'to' => '17:00'],
            ['day' => 'tuesday', 'from' => '08:00', 'to' => '17:00'],
            ['day' => 'wednesday', 'from' => '08:00', 'to' => '17:00'],
            ['day' => 'thursday', 'from' => '08:00', 'to' => '17:00'],
            ['day' => 'friday', 'from' => '08:00', 'to' => '17:00'],
        ];

        foreach ($days as $day) {
            DB::table('working_hours')->updateOrInsert(
                ['day' => $day['day'], 'courier_id' => null],
                [
                    'from' => $day['from'],
                    'to' => $day['to'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('working_hours')
            ->whereNull('courier_id')
            ->whereIn('day', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'])
            ->delete();
    }
};
