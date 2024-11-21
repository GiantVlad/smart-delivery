<?php

use App\Enums\RoutePointTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->string('point_type')->default(RoutePointTypeEnum::START->value);
        });
    }

    public function down(): void
    {
        Schema::dropColumns('routes', ['point_type']);
    }
};
