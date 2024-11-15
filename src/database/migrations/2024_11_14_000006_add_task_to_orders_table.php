<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropColumns('orders', ['courier_id']);
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('task_id')->nullable();
            $table->unsignedBigInteger('start_point_id')->default(0);
            $table->unsignedBigInteger('end_point_id')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropColumns('orders', ['task_id', 'start_point_id', 'end_point_id']);
    }
};
