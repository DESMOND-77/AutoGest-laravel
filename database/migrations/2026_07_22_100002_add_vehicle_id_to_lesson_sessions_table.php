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
        Schema::table('lesson_sessions', function (Blueprint $table) {
            $table->foreignId('vehicle_id')->nullable()->after('instructor_id')
                ->constrained('vehicles')->nullOnDelete();

            $table->index(['structure_id', 'vehicle_id', 'scheduled_date'], 'lesson_sessions_vehicle_lookup_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_sessions', function (Blueprint $table) {
            $table->dropIndex('lesson_sessions_vehicle_lookup_index');
            $table->dropConstrainedForeignId('vehicle_id');
        });
    }
};
