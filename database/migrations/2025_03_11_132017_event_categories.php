<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('event_category_id')->constrained()->onDelete('cascade');
            $table->enum('event_type', ['one_time', 'multi_day', 'multi_day_with_sub_events']);
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->foreignId('parent_event_id')->nullable()->constrained('events')->onDelete('cascade');
            $table->boolean('is_sub_event')->default(false);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
