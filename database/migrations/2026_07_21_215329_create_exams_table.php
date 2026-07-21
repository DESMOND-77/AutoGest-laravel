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
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('structure_id')->constrained('structures')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();

            $table->string('type');
            $table->date('exam_date');
            $table->string('location')->nullable();
            $table->string('inspector')->nullable();
            $table->string('result')->default('pending');
            $table->unsignedInteger('fault_count')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['structure_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
