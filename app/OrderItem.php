<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = 'order_item';

    protected $fillable = ['quantity', 'cost', 'org_type'];
    
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            $item->attributes['type'] = $item->type;
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function canceledBy()
    {
        return $this->belongsTo(User::class);
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function getNameAttribute()
    {
        if ($this->item) {
            return $this->item->name;
        }

        return ucwords($this->type);
    }
}
