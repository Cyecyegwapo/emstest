<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'status',
        'attendance',
        'notes',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELLED = 'cancelled';

    // Attendance constants
    const ATTENDANCE_PENDING = 'pending';
    const ATTENDANCE_PRESENT = 'present';
    const ATTENDANCE_ABSENT = 'absent';

    // Relationship with event
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    // Relationship with user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
