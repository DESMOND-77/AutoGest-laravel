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
        Schema::create('lesson_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('structure_id')->constrained('structures')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();

            $table->string('type');
            $table->date('scheduled_date');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('location')->nullable();
            $table->string('presence')->default('planned');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['structure_id', 'instructor_id', 'scheduled_date']);
            $table->index(['structure_id', 'student_id', 'scheduled_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_sessions');
    }
};
