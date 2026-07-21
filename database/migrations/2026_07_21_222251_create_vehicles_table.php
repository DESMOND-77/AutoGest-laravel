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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('structure_id')->constrained('structures')->cascadeOnDelete();

            $table->string('plate');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('category')->default('B');
            $table->string('color')->nullable();
            $table->unsignedInteger('mileage')->default(0);
            $table->string('status')->default('active');
            $table->date('technical_inspection_expires_at')->nullable();
            $table->date('insurance_expires_at')->nullable();
            $table->timestamps();

            $table->unique(['structure_id', 'plate']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
