<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Participant;
use App\Models\Notification;
use App\Services\WhatsAppNotificationService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\User;
use App\Jobs\CheckPaymentStatusAndNotify;

class ParticipantController extends Controller
{
    public function store(Request $request, WhatsAppNotificationService $waService)
    {
        Log::info('ParticipantController@store: Request received', $request->except('password', '_token'));

        $notificationId = $request->input('notification_id');
        $notification = Notification::find($notificationId);

        if ($notification) {
            Log::info("===== TIME CHECK START =====");
            $now = Carbon::now('Asia/Jakarta');
            Log::info("1. Waktu Server Saat Ini (WIB): " . $now->toDateTimeString());

            $eventDateTime = Carbon::parse($notification->event_date . ' ' . $notification->event_time, 'Asia/Jakarta');
            Log::info("2. Waktu Event dari DB: " . $eventDateTime->toDateTimeString());

            $registrationCloseTime = $eventDateTime->copy()->subMinutes(30);
            Log::info("3. Batas Waktu Pendaftaran Dihitung: " . $registrationCloseTime->toDateTimeString());

            if ($now->greaterThanOrEqualTo($registrationCloseTime)) {
                $isPastEvent = $now->greaterThanOrEqualTo($eventDateTime);
                $errorMessage = $isPastEvent ? 'Event ini telah berakhir.' : 'Pendaftaran untuk event ini sudah ditutup.';

                Log::error("4. KEPUTUSAN: PENDAFTARAN DITOLAK. Alasan: " . $errorMessage);
                Log::info("===== TIME CHECK END =====");

                return redirect()->back()
                    ->withErrors(['event_expired' => $errorMessage . ' Untuk info lebih lanjut silakan hubungi admin.'])
                    ->withInput();
            }
            Log::info("4. KEPUTUSAN: PENDAFTARAN DITERIMA. Waktu pendaftaran masih valid.");
            Log::info("===== TIME CHECK END =====");
        }

        $whatsapp = preg_replace('/\D/', '', $request->input('whatsapp'));
        if (substr($whatsapp, 0, 2) === '62') {
            $whatsapp = '0' . substr($whatsapp, 2);
        }
        $request->merge(['whatsapp' => $whatsapp]);

        $existingParticipant = Participant::where('whatsapp', $whatsapp)
            ->where('notification_id', $notificationId)
            ->first();

        if ($existingParticipant) {
            if ($existingParticipant->payment_status === 'paid') {
                return redirect()->back()
                    ->withErrors(['whatsapp' => 'Nomor WhatsApp ini sudah terdaftar dan lunas untuk event ini.'])
                    ->withInput();
            }
            if ($notification && $notification->is_paid) {
                return redirect()->route('payment.initiate', [
                    'type' => 'event',
                    'identifier' => $existingParticipant->id,
                    'price' => $notification->price,
                ]);
            }
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'business' => 'required|string|max:255',
            'email' => 'required|email:rfc,dns|max:255',
            'whatsapp' => 'required|string|max:20',
            'city' => 'required|string|max:255',
            'event_type' => 'required|string|in:webinar,workshop',
            'notification_id' => 'required|exists:notifications,id',
        ]);

        $affiliateUsername = $request->input('affiliate_id');
        $affiliate = User::where('username', $affiliateUsername)->first();
        $affiliateId = $affiliate ? $affiliate->id : User::where('username', 'admin')->first()->id;

        $participant = Participant::create([
            'name' => $request->input('name'),
            'business' => $request->input('business'),
            'email' => $request->input('email'),
            'whatsapp' => $whatsapp,
            'city' => $request->input('city'),
            'event_type' => $request->input('event_type'),
            'affiliate_id' => $affiliateId,
            'notification_id' => $notificationId,
            'payment_status' => 'unpaid',
            'is_paid' => 0,
        ]);

        Log::info('ParticipantController@store: Participant created successfully', ['participant_id' => $participant->id]);
        $waService->sendPostEventMessage($participant);

        if ($notification->is_paid) {
            CheckPaymentStatusAndNotify::dispatch($participant->id)->delay(now()->addMinutes(5));
            Log::info("Job CheckPaymentStatusAndNotify dikirimkan untuk ID peserta: {$participant->id} untuk dijalankan dalam 5 menit.");
            return redirect()->route('payment.initiate', [
                'type' => 'event',
                'identifier' => $participant->id,
                'price' => $notification->price,
            ]);
        } else {
            $waService->sendPaidConfirmation($participant);
            $waService->sendAdminNotification($participant, $request->input('event_type'));

            $adminUser = User::where('username', 'admin')->first();
            if ($adminUser && $affiliateId !== $adminUser->id) {
                $waService->sendAffiliateNotification($affiliateId, $participant, $request->input('event_type'));
            }

            return redirect()->back()->with('success', 'Selamat Anda berhasil mendaftar!');
        }
    }
}
