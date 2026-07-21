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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('structure_id')->constrained('structures')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->unique()
                ->constrained('users')->nullOnDelete();
            $table->foreignId('instructor_id')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->string('last_name');
            $table->string('first_name');
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('phone')->nullable();
            $table->string('phone_secondary')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('neph')->nullable();

            $table->string('license_category')->default('B');
            $table->string('course_type')->default('normal');
            $table->string('lifecycle_stage')->default('prospect');
            $table->string('dossier_status')->default('incomplete');

            $table->date('registered_at')->nullable();
            $table->timestamps();

            $table->index(['structure_id', 'lifecycle_stage']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
