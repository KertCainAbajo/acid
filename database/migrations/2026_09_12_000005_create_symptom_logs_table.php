<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('symptom_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('symptom_type')->index();
            $table->unsignedTinyInteger('intensity');
            $table->dateTime('time')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('symptom_logs');
    }
};
