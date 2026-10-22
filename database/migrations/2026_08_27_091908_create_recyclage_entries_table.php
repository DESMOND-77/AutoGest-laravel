<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recyclage_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('structure_id')->constrained('structures')->cascadeOnDelete();
            $table->string('full_name');
            $table->string('motif');
            $table->string('phone')->nullable();
            $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('session_date');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recyclage_entries');
    }
};
