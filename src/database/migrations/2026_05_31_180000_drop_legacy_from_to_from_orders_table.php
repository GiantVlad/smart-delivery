<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'time_ranges') && Schema::hasColumn('orders', 'from') && Schema::hasColumn('orders', 'to')) {
            DB::table('orders')
                ->select(['id', 'date', 'from', 'to', 'time_ranges'])
                ->orderBy('id')
                ->chunkById(200, function ($orders): void {
                    foreach ($orders as $order) {
                        if (! empty($order->time_ranges) || empty($order->from) || empty($order->to)) {
                            continue;
                        }

                        DB::table('orders')
                            ->where('id', $order->id)
                            ->update([
                                'time_ranges' => json_encode([[
                                    'slot_id' => null,
                                    'date' => $order->date,
                                    'from' => $order->from,
                                    'to' => $order->to,
                                ]], JSON_THROW_ON_ERROR),
                            ]);
                    }
                });
        }

        Schema::table('orders', function (Blueprint $table): void {
            if (Schema::hasColumn('orders', 'from')) {
                $table->dropColumn('from');
            }
            if (Schema::hasColumn('orders', 'to')) {
                $table->dropColumn('to');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'from')) {
                $table->string('from')->default('8:00')->after('end_point_id');
            }
            if (! Schema::hasColumn('orders', 'to')) {
                $table->string('to')->default('12:00')->after('end_point_id');
            }
        });
    }
};
