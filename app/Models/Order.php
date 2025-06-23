<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'order_id', // Ini penting, untuk mencocokkan Midtrans order_id
        'amount',
        'status', // pending, paid, failed, dll.
        // Tambahkan kolom lain yang relevan dari tabel 'orders' Anda jika ada
        // Contoh: 'quantity', 'shipping_address', dll.
    ];

    /**
     * Relasi ke tabel users (user yang membuat order)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke tabel products (produk yang dibeli)
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
