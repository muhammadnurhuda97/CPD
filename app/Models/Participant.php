<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'business',
        'email',
        'whatsapp',
        'city',
        'affiliate_id',
        'event_type',
        'payment_status', // Cukup satu
        'order_id',       // Untuk menyimpan ID order Midtrans
        'is_paid',        // Flag lunas
    ];

    /**
     * Relasi ke tabel Transaction.
     * Seorang peserta bisa memiliki banyak transaksi (jika ada retry pembayaran).
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Relasi ke model Notification untuk mendapatkan detail harga event.
     * Pastikan model Notification Anda ada dan memiliki data yang relevan.
     */
    public function notificationEvent()
    {
        return $this->belongsTo(Notification::class, 'event_type', 'event_type');
    }
}
