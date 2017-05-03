<?php

namespace App;

use App\Events\RoomDeleted;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use SoftDeletes, Revisionable;

    protected $guarded = [];

    protected $attributes = [
        'capacity' => 4,
    ];

    protected $dates = [
        'key_received_at',
        'checked_in_at',
    ];

    protected static $logAttributes = [
        'name',
        'description',
        'notes',
        'hotelName',
    ];

    protected $appends = [
        'hotelName',
    ];

    protected $with = [
        'hotel',
    ];

    protected $events = [
        'deleted' => RoomDeleted::class,
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function hotel()
    {
        return $this->belongsTo(Item::class, 'hotel_id');
    }

    public function tickets()
    {
        return $this->belongsToMany(Ticket::class, 'room_assignments')->withTimestamps()->active();
    }

    public function scopeForUser($query, $user = null)
    {
        $user = $user ?: Auth::user();

        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->where('organization_id', $user->organization_id);
    }

    public function getAssignedAttribute()
    {
        return $this->tickets->count();
    }

    public function getCapacityAttribute($capacity)
    {
        return number_format($capacity);
    }

    public function getIsAtCapacityAttribute()
    {
        return (bool) ! $this->remaining;
    }

    public function getRemainingAttribute()
    {
        return $this->capacity - $this->assigned;
    }

    public function getHotelNameAttribute()
    {
        return $this->hotel->name ?? '';
    }

    public function getIsKeyReceivedAttribute()
    {
        return (bool) $this->key_received_at;
    }

    public function getIsCheckedInAttribute()
    {
        return (bool) $this->checked_in_at;
    }

    public function setIsCheckedInAttribute($is_checked_in)
    {
        $this->checked_in_at = $is_checked_in ? \Carbon\Carbon::now() : null;
    }

    public function setIsKeyReceivedAttribute($is_key_received)
    {
        $this->key_received_at = $is_key_received ? \Carbon\Carbon::now() : null;
    }

    public function checkIn()
    {
        $this->update([
            'is_checked_in' => true
        ]);
    }

    public function keyReceived()
    {
        $this->update([
            'key_received_at' => \Carbon\Carbon::now(),
        ]);
    }
}
