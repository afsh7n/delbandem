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
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author');
            $table->text('description')->nullable();
            $table->string('voice_file_name')->nullable();
            $table->string('image_file_name')->nullable();
            $table->integer('total_rates')->default(1);
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->decimal('rate', 2, 1)->default(0)->min(0)->max(5);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};
