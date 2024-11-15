<?php

use App\Enums\CourierStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('point_id');
            $table->unsignedInteger('sequence');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
