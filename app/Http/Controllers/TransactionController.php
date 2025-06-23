<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user(); // Dapatkan user yang sedang login

        // Memulai query dengan relasi yang diperlukan untuk kedua role
        // 'user' penting untuk mengetahui siapa pemilik transaksi
        // 'participant' untuk transaksi event
        // 'order.product' untuk transaksi produk
        $query = Transaction::with('user', 'participant', 'order.product')->latest();

        $filterType = null; // Default filter type
        $pageTitle = 'Catatan Transaksi'; // Default title

        // Logika filter dan judul berdasarkan role
        if ($user->role === 'admin') {
            // Admin bisa melihat semua transaksi dan menggunakan filter jenis
            $filterType = $request->query('type'); // 'event', 'product', atau null untuk semua

            if ($filterType === 'event') {
                $query->whereNotNull('participant_id');
            } elseif ($filterType === 'product') {
                $query->whereNotNull('user_id')->whereHas('order');
            }
            $pageTitle = 'Semua Transaksi'; // Judul khusus admin
        } else { // Jika role adalah 'user'
            // User hanya melihat transaksinya sendiri
            $query->where('user_id', $user->id); // Filter berdasarkan user_id yang sedang login
            $pageTitle = 'Riwayat Transaksi'; // Judul khusus user
            // Untuk user, kita tidak akan menampilkan filter dropdown di view,
            // tapi variabel $filterType tetap ada jika diperlukan di masa depan.
        }

        $transactions = $query->get();

        // Kirim data transaksi, filter yang aktif, dan judul halaman ke view
        return view('dashboard.transaksi.index', compact('transactions', 'filterType', 'pageTitle'));
    }
}