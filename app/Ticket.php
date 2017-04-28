<?php

namespace App;

use Auth;
use App\Waiver;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Builder;
use Collective\Html\Eloquent\FormAccessible;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends OrderItem
{
    use FormAccessible, SoftDeletes, Searchable, Revisionable;

    protected $table = 'order_items';

    protected $type = 'ticket';

    protected $guarded = [];

    protected $attributes = [
        'agegroup' => 'student',
    ];

    protected $casts = [
        'ticket_data' => 'collection'
    ];

    protected $dates = [
        'checked_in_at'
    ];

    protected static $logAttributes = [
        'name',
        'roomId'
    ];

    protected $appends = [
        'name',
        'roomId',
    ];

    protected $with = [
        'roomAssignment',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('type', function (Builder $builder) {
            $builder->where('type', '=', 'ticket');
        });
    }

    public function scopeForUser($query, $user = null)
    {
        $user = $user ?? Auth::user();

        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->isChurchAdmin()) {
            return $query->whereHas('order.organization', function ($q) use ($user) {
                $q->where('id', $user->organization_id);
            });
        }

        return $query->where('user_id', $user->id);
    }

    public function scopeUnassigned($query)
    {
        return $query->doesntHave('rooms');
    }

    public function waiver()
    {
        return $this->hasOne(Waiver::class)->latest();
    }

    public function waivers()
    {
        return $this->hasMany(Waiver::class);
    }

    public function roomAssignment()
    {
        return $this->hasOne(RoomAssignment::class)->latest();
    }

    public function roomAssignments()
    {
        return $this->hasMany(RoomAssignment::class);
    }

    public function rooms()
    {
        return $this->belongsToMany(Room::class, 'room_assignments')->withTimestamps();
    }

    /*-------------- getters -----------------*/
    public function getNameAttribute()
    {
        return $this->person && strlen($this->person->first_name)
               ? $this->person->name
               : "Ticket #{$this->id}";
    }

    public function getRoomIdAttribute()
    {
        return $this->roomAssignment->room_id ?? null;
    }

    public function getAttribute($key)
    {
        $attribute = parent::getAttribute($key);

        if (is_null($attribute) && $this->exists && isset($this->ticket_data)) {
            $attribute = $this->ticket_data->get($key);
        }

        if (is_null($attribute) && $this->exists && $this->relationLoaded('person')) {
            $attribute = $this->person->$key;
        }

        return $attribute;
    }

    public function getIsCheckedInAttribute()
    {
        return (bool) $this->checked_in_at;
    }

    public function setIsCheckedInAttribute($is_checked_in)
    {
        $this->checked_in_at = $is_checked_in ? \Carbon\Carbon::now() : null;
    }

    public function checkIn()
    {
        $this->update(['is_checked_in' => true]);
    }

    public function cancel(User $user = null)
    {
        $this->canceled_at = \Carbon\Carbon::now();
        $this->canceled_by_id = $user->id;
        $this->save();
    }

    public function toSearchableArray()
    {
        return [
            'name' => $this->person->name,
            'organization_id' => $this->order->organization_id,
        ];
    }
}
