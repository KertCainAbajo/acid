<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('food_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('food_name');
            $table->string('category')->index();
            $table->dateTime('time')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_logs');
    }
};
