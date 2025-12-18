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
        Schema::create('user_story_listens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('story_id')->constrained('stories')->onDelete('cascade');
            $table->integer('listened_seconds')->default(0)->comment('تعداد ثانیه‌های گوش داده شده');
            $table->boolean('is_completed')->default(false)->comment('آیا استوری کامل گوش داده شده');
            $table->timestamp('opened_at')->nullable()->comment('زمان باز شدن استوری');
            $table->timestamp('last_listened_at')->nullable()->comment('آخرین زمان گوش دادن');
            $table->timestamps();

            // Ensure one record per user-story combination
            $table->unique(['user_id', 'story_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_story_listens');
    }
};

