<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use Carbon\Carbon;

class WorkshopController extends Controller
{
    public function index(Request $request, Notification $notification)
    {
        // Panggil logVisitor() di sini
        (new VisitorController)->logVisitor(); // BARU: Menambahkan baris ini

        $affiliateId = $request->query('affiliate_id');

        if ($affiliateId) {
            session(['affiliate_id' => $affiliateId]);
        }

        $eventDate = Carbon::parse($notification->event_date);
        $eventTime = $notification->event_time;

        Carbon::setLocale('id');
        $formattedEventDate = $eventDate->translatedFormat('l, d F Y');
        $eventDatetime = $eventDate->toDateString() . ' ' . $eventTime;

        return view('landingpage.workshop', compact(
            'formattedEventDate',
            'eventDatetime',
            'affiliateId',
            'notification'
        ));
    }
}
