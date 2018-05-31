<?php

namespace App;

use Laravel\Scout\Searchable;
use App\Observers\TicketObserver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Jobs\Waiver\RequestWaiverSignature;
use Illuminate\Database\Eloquent\SoftDeletes;
use Facades\App\Contracts\Printing\Factory as Printer;

class Ticket extends OrderItem
{
    use SoftDeletes, Searchable, Revisionable;

    protected $table = 'order_items';

    protected $type = 'ticket';

    protected $guarded = [];

    protected $attributes = [
        'agegroup' => 'student',
    ];

    protected $casts = [
        'ticket_data' => 'collection',
        'checked_in_at' => 'datetime',
    ];

    protected static $logAttributes = [
        'first_name',
        'last_name',
        'roomId',
    ];

    protected $appends = [
        'name',
        'first_name',
        'last_name',
        'roomId',
    ];

    protected $with = [
        'roomAssignment',
    ];

    protected $observables = [
        'canceled',
    ];

    protected static function boot()
    {
        parent::boot();

        static::observe(TicketObserver::class);

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
            return $query->forOrganization($user->organization);
        }

        return $query->where('user_id', $user->id);
    }

    public function scopeForOrganization($query, $organization)
    {
        return $query->whereExists(function ($q) use ($organization) {
            $q->select(\DB::raw(1))
                ->from('orders')
                ->whereRaw('orders.id = owner_id and owner_type = "App\\\Order"')
                ->where('orders.organization_id', $organization->id);
        });
    }

    public function scopeUnassigned($query)
    {
        return $query->doesntHave('rooms');
    }

    public function scopeFilter($query, $filters)
    {
        return $filters->apply($query);
    }

    public function scopeOrderByPersonName($query)
    {
        $query->orderBySub(
            Person::select('last_name')->whereRaw('person_id = people.id')
        )->orderBySub(
            Person::select('first_name')->whereRaw('person_id = people.id')
        )->with('person');
    }

    public function waiver()
    {
        return $this->hasOne(Waiver::class)->latest();
    }

    public function completedWaivers()
    {
        return $this->waivers()->complete();
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

    public function room()
    {
        return $this->hasOne(Room::class, 'id', 'last_room_id');
    }

    public function scopeWithRoom($query)
    {
        $query->addSubSelect('last_room_id', RoomAssignment::select('room_id')
            ->whereRaw('ticket_id = order_items.id')
            ->latest()
        )->with('room');
    }

    public function getNameAttribute()
    {
        return $this->person && strlen($this->person->first_name)
               ? $this->person->name
               : "Ticket #{$this->id}";
    }

    public function getFirstNameAttribute()
    {
        if ($this->person && strlen($this->person->first_name)) {
            return $this->person->first_name;
        }
    }

    public function getLastNameAttribute()
    {
        if ($this->person && strlen($this->person->last_name)) {
            return $this->person->last_name;
        }
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

    public function checkin()
    {
        $this->update(['is_checked_in' => true]);
    }

    public function uncheckin()
    {
        $this->update(['is_checked_in' => false]);
    }

    public function cancel()
    {
        $this->update([
            'canceled_at' => $this->freshTimestamp(),
            'canceled_by_id' => auth()->id(),
        ]);

        $this->fireModelEvent('canceled', false);
    }

    public function toSearchableArray()
    {
        return [
            'name' => $this->person->name,
            'organization_id' => $this->owner->organization_id,
            'parent_email' => $this->owner->user->email ?: $this->owner->user->person->email,
            'parent_name' => $this->owner->user->person->name,
            'is_canceled' => $this->is_canceled,
            'agegroup' => $this->agegroup,
        ];
    }

    public function createWaiver($provider = 'adobesign')
    {
        if ($this->waivers()->count()) {
            return $this->waivers()->latest();
        }

        $waiver = $this->waiver()->create([
            'provider' => $provider,
        ]);

        RequestWaiverSignature::dispatch($waiver);

        return $waiver;
    }

    public function printWristband($printer, $driver = null)
    {
        Printer::driver($driver)->print(
            $printer,
            url()->signedRoute('tickets.wristband.show', $this),
            [
                'title' => $this->name,
                'source' => 'PCC Check In',
            ]
        );
    }

    public function setPersonAttribute($person)
    {
        if (is_array($person)) {
            $person = optional($this->person)->exists
                ? tap($this->person->fill($person))->save()
                : Person::create($person);
        }

        $this->person()->associate($person);
    }

    public function setPriceInDollarsAttribute($price)
    {
        $this->price = $price * 100;
    }

    public function getUnassignedSortAttribute()
    {
        return vsprintf('%02d__%s__%s__%s__%s', [
            $this->person->grade == 0 ? 99 : $this->person->grade,
            $this->person->gender == 'M' ? 'z' : 'a',
            $this->agegroup == 'leader' ? 'z' : 'a',
            $this->person->first_name,
            $this->person->last_name,
        ]);
    }

    public function getAssignedSortAttribute()
    {
        return vsprintf('%s__%02d__%s__%s__%s', [
            $this->agegroup == 'leader' ? 'a' : 'z',
            $this->person->grade == 0 ? 99 : $this->person->grade,
            $this->person->gender == 'M' ? 'z' : 'a',
            $this->person->first_name,
            $this->person->last_name,
        ]);
    }
}
