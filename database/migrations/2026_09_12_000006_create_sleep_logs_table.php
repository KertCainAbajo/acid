<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sleep_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('sleep_hours', 4, 2);
            $table->unsignedTinyInteger('sleep_quality');
            $table->unsignedTinyInteger('stress_level');
            $table->date('logged_on')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'logged_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sleep_logs');
    }
};
