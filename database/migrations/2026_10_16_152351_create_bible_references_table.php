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
        Schema::create('bible_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('translation');
            $table->string('book_slug');
            $table->unsignedSmallInteger('start_chapter');
            $table->unsignedSmallInteger('start_verse')->nullable();
            $table->unsignedSmallInteger('end_chapter')->nullable();
            $table->unsignedSmallInteger('end_verse')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['document_id', 'position']);
            $table->index(['translation', 'book_slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bible_references');
    }
};
