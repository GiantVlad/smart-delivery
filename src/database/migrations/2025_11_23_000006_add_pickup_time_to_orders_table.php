<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('to')->after('end_point_id')->default('12:00');
            $table->string('from')->after('end_point_id')->default('8:00');
            $table->date('date')->default(now())->after('end_point_id');
        });
    }

    public function down(): void
    {
        Schema::dropColumns('orders', ['date', 'from', 'to']);
    }
};
