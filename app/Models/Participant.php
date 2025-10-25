<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'business',
        'email',
        'whatsapp',
        'city',
        'affiliate_id',
        'event_type',
        'notification_id',
        'payment_status',
        'order_id',
        'is_paid',
        'notified_registered',
        'notified_unpaid',
        'notified_paid',
        'reminder_scheduled',
        'paid_reminder_scheduled',
        'post_event_reminder_scheduled',
    ];

    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }

    /**
     * Accessor untuk mendapatkan username affiliate secara dinamis.
     * Ini membuat kita bisa memanggil $participant->affiliate_username di view.
     */
    public function getAffiliateUsernameAttribute()
    {
        // Jika affiliate_id adalah angka, cari user berdasarkan ID.
        if (is_numeric($this->affiliate_id)) {
            $user = User::find($this->affiliate_id);
            return $user ? $user->username : 'N/A';
        }

        // Jika bukan angka (berarti teks/username dari data lama), langsung kembalikan.
        return $this->affiliate_id;
    }
    public function affiliateUser()
    {
        return $this->belongsTo(User::class, 'affiliate_id');
    }
}
