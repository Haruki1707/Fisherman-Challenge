<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    use HasFactory;

    protected $guarded = [
        'id'
    ];

    protected $appends = [
        'full_address'
    ];

    // Hides data that we don't want to send to the frontend
    protected $hidden = [
        'id',
        'address',
        'street',
        'street_id',
        'city',
        'city_id',
        'created_at',
        'updated_at',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function street(): BelongsTo
    {
        return $this->belongsTo(Street::class);
    }

    // Builds a string with the full address
    public function getFullAddressAttribute(): string
    {
        return "$this->address {$this->street->name}, {$this->city->name}";
    }
}
