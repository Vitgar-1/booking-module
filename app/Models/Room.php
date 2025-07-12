<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'capacity',
        'price_per_night',
        'description'
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
