<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event',
        'event_city',
        'event_type',
        'event_date', // Tambahkan ini
        'event_time', // Tambahkan ini
        'zoom',
        'location',
        'location_name',
        'location_address',
    ];

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
