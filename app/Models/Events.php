<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Events extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'location',
        'start_date',
        'end_date',
        'registration_deadline',
        'max_participants',
        'status',
        'created_by',
        'featured_image',
        'category_id',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'registration_deadline' => 'datetime',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_COMPLETED = 'completed';

    // Relationship with user who created the event
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relationship with category
    public function category()
    {
        return $this->belongsTo(EventCategory::class, 'category_id');
    }

    // Relationship with registrations
    public function registrations()
    {
        return $this->hasMany(EventRegistration::class);
    }

    // Scope for upcoming events
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', now())
                     ->where('status', self::STATUS_PUBLISHED);
    }

    // Scope for past events
    public function scopePast($query)
    {
        return $query->where('end_date', '<', now());
    }

    // Check if registration is open
    public function isRegistrationOpen()
    {
        return now()->lt($this->registration_deadline) &&
               $this->status === self::STATUS_PUBLISHED &&
               ($this->max_participants === null || $this->registrations()->count() < $this->max_participants);
    }

    // Get available slots
    public function availableSlots()
    {
        if ($this->max_participants === null) {
            return null; // Unlimited
        }

        return max(0, $this->max_participants - $this->registrations()->count());
    }
}
