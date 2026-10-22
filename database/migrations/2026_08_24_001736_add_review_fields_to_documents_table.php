<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('required_document_type_id')->nullable()->after('documentable_id')
                ->constrained('required_document_types')->nullOnDelete();
            $table->string('review_status')->default('pending')->after('is_current');
            $table->text('rejection_reason')->nullable()->after('review_status');
            $table->foreignId('reviewed_by_id')->nullable()->after('rejection_reason')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_id');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('required_document_type_id');
            $table->dropConstrainedForeignId('reviewed_by_id');
            $table->dropColumn(['review_status', 'rejection_reason', 'reviewed_at']);
        });
    }
};
