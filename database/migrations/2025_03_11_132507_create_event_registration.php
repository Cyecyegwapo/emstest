<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\EventRegistration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->enum('status', [
                EventRegistration::STATUS_PENDING,
                EventRegistration::STATUS_CONFIRMED,
                EventRegistration::STATUS_CANCELLED
            ])->default(EventRegistration::STATUS_PENDING);
            $table->enum('attendance', [
                EventRegistration::ATTENDANCE_PENDING,
                EventRegistration::ATTENDANCE_PRESENT,
                EventRegistration::ATTENDANCE_ABSENT
            ])->default(EventRegistration::ATTENDANCE_PENDING);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Unique constraint to prevent duplicate registrations
            $table->unique(['event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
    }
};
