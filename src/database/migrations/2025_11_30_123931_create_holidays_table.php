<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('courier_holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->unsignedInteger('reason_code')->default(0);
            $table->foreignId('courier_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['courier_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courier_holidays');
    }
};
