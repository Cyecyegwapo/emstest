<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Events;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('location');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->dateTime('registration_deadline');
            $table->integer('max_participants')->nullable();
            $table->enum('status', [
                Events::STATUS_DRAFT,
                Events::STATUS_PUBLISHED,
                Events::STATUS_CANCELLED,
                Events::STATUS_COMPLETED
            ])->default(Events::STATUS_DRAFT);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('category_id')->nullable()->constrained('event_categories');
            $table->string('featured_image')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
