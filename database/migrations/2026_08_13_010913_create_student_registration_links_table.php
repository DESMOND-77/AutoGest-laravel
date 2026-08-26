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
        Schema::create('student_registration_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('structure_id')->constrained('structures')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('label')->nullable();

            // Only the hash is stored - the plain token exists nowhere at
            // rest, so a database leak alone never yields a working public
            // registration link (see docs/features/student-public-registration.md).
            $table->string('token_hash', 64)->unique();

            $table->unsignedInteger('usage_count')->default(0);
            $table->unsignedInteger('max_uses')->nullable();

            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            $table->index('expires_at');
            $table->index('revoked_at');
            // Public token lookups happen before any tenant context exists
            // (see StudentRegistrationLinkService::validate), so token_hash
            // needs its own unique index rather than relying on a
            // structure_id-scoped one.
            $table->index(['structure_id', 'revoked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_registration_links');
    }
};
