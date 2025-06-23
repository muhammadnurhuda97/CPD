<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use Carbon\Carbon;

class WorkshopController extends Controller
{
    public function index(Request $request)
    {
        // Ambil data notifikasi terakhir untuk event_type 'workshop'
        $notification = Notification::where('event_type', 'workshop')->latest()->first();

        // Jika tidak ada data Workshop, tampilkan halaman 404
        if (!$notification) {
            abort(404, 'Workshop tidak tersedia saat ini.');
        }

        // Cek apakah ada affiliate_id di URL
        $affiliateId = $request->query('affiliate_id');

        // Jika ada affiliate_id, simpan di session atau kirim ke view
        if ($affiliateId) {
            session(['affiliate_id' => $affiliateId]);
        }

        // Format tanggal dan waktu
        $eventDate = Carbon::parse($notification->event_date);
        $eventTime = $notification->event_time;

        // Set locale Carbon ke bahasa Indonesia
        Carbon::setLocale('id');
        $formattedEventDate = $eventDate->translatedFormat('l, d F Y');
        $eventDatetime = $eventDate->toDateString() . ' ' . $eventTime;

        // Kirim ke view
        return view('landingpage.workshop', compact(
            'formattedEventDate',
            'eventDatetime',
            'affiliateId',
            'notification' // Kirim seluruh data notifikasi ke view
        ));
    }
}
