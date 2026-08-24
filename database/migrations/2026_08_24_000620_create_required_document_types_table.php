<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('required_document_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('structure_id')->constrained('structures')->cascadeOnDelete();
            $table->string('label');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['structure_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('required_document_types');
    }
};
