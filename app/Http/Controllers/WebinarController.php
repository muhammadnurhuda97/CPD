<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use Carbon\Carbon;

class WebinarController extends Controller
{
    public function index(Request $request)
    {
        // Ambil data notifikasi terakhir (misalnya webinar)
        $notification = Notification::where('event_type', 'webinar')->latest()->first();

        // Jika tidak ada data Webinar, tampilkan halaman 404
        if (!$notification) {
            abort(404, 'Webinar tidak tersedia saat ini.');
        }

        // Cek apakah ada affiliate_id di URL
        $affiliateId = $request->query('affiliate_id');  // Mengambil affiliate_id dari URL query string

        // Jika ada affiliate_id, simpan di session atau kirim ke view
        if ($affiliateId) {
            session(['affiliate_id' => $affiliateId]);  // Menyimpan affiliate_id ke dalam session
        }

        // Format tanggal dan waktu
        $eventDate = Carbon::parse($notification->event_date);
        $eventTime = $notification->event_time;

        // Mengatur locale Carbon ke bahasa Indonesia
        Carbon::setLocale('id');

        // Format tanggal dengan menggunakan translatedFormat()
        $formattedEventDate = $eventDate->translatedFormat('l, d F Y'); // Format: 21 Maret 2021

        // Gabungkan tanggal dan waktu menjadi satu string
        $eventDatetime = $eventDate->toDateString() . ' ' . $eventTime;

        // Kirim ke view untuk digunakan dalam countdown dan menampilkan tanggal
        return view('landingpage.webinar', compact(
            'formattedEventDate',
            'eventDatetime',
            'affiliateId',
            'notification' // Variabel ini sekarang dikirim ke view
        ));
    }
}
