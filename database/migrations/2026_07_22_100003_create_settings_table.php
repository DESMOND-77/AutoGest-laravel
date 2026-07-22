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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('structure_id')->unique()->constrained('structures')->cascadeOnDelete();

            $table->string('display_name')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('support_email')->nullable();
            $table->string('timezone')->default('Africa/Libreville');
            $table->string('currency')->default('FCFA');
            $table->string('default_theme')->default('light');
            $table->json('notification_preferences')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
