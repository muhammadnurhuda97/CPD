<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use Carbon\Carbon;
// Tambahkan VisitorController jika belum ada
use App\Http\Controllers\VisitorController;

class WebinarController extends Controller
{
    public function index(Request $request, Notification $notification)
    {
        (new VisitorController)->logVisitor();

        $affiliateId = $request->query('affiliate_id');
        if ($affiliateId) {
            session(['affiliate_id' => $affiliateId]);
        }

        // ===== AMBIL ref_p =====
        $ref_p = $request->query('ref_p');
        // ========================

        $eventDate = Carbon::parse($notification->event_date);
        $eventTime = $notification->event_time;
        Carbon::setLocale('id');
        $formattedEventDate = $eventDate->translatedFormat('l, d F Y');
        $eventDatetime = $eventDate->toDateString() . ' ' . $eventTime;

        // ===== KIRIM ref_p KE VIEW =====
        return view('landingpage.webinar', compact(
            'formattedEventDate',
            'eventDatetime',
            'affiliateId',
            'notification',
            'ref_p' // Tambahkan ini
        ));
        // ==============================
    }
}
