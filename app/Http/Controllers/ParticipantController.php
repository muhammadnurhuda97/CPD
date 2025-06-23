<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Participant;
use App\Models\Notification;
use App\Services\WhatsAppNotificationService;

class ParticipantController extends Controller
{
    public function store(Request $request, WhatsAppNotificationService $waService)
    {
        Log::info('ParticipantController@store: Request received', $request->except('password', '_token'));

        // Normalisasi nomor WhatsApp (hapus semua selain angka)
        $whatsapp = preg_replace('/\D/', '', $request->input('whatsapp'));

        // Ganti prefix '62' dengan '0' untuk nomor WA lokal Indonesia
        if (substr($whatsapp, 0, 2) === '62') {
            $whatsapp = '0' . substr($whatsapp, 2);
        }

        $request->merge(['whatsapp' => $whatsapp]);

        Log::info('ParticipantController@store: WhatsApp number normalized', ['normalized_whatsapp' => $whatsapp]);

        $eventType = $request->input('event_type');

        // Cek apakah sudah ada peserta dengan WA & event_type yang sama
        $existingParticipant = Participant::where('whatsapp', $whatsapp)
            ->where('event_type', $eventType)
            ->first();

        if ($existingParticipant) {
            if ($existingParticipant->payment_status === 'paid') {
                return redirect()->back()
                    ->withErrors(['whatsapp' => 'Nomor WhatsApp ini sudah terdaftar dan lunas untuk event ini.'])
                    ->withInput();
            }

            Log::info("Existing unpaid/pending participant found for WA {$whatsapp}. Redirecting to payment.", ['participant_id' => $existingParticipant->id]);

            $notification = Notification::where('event_type', $eventType)->latest()->first();
            $price = $notification ? $notification->price : 0;

            if ($notification && $notification->is_paid) {
                return redirect()->route('payment.initiate', [
                    'type' => 'event',
                    'identifier' => $existingParticipant->id,
                    'price' => $price,
                ]);
            } else {
                return redirect()->back()->with('success', 'Anda sudah terdaftar pada event gratis ini!');
            }
        }

        // Validasi input
        $request->validate([
            'name'        => 'required|string|max:255',
            'business'    => 'required|string|max:255',
            'email'       => 'required|email:rfc,dns|max:255',
            'whatsapp'    => 'required|string|max:20|unique:participants,whatsapp,NULL,id,event_type,' . $eventType,
            'city'        => 'required|string|max:255',
            'event_type'  => 'required|string|in:webinar,workshop',
        ], [
            'whatsapp.unique' => 'Nomor WhatsApp ini sudah terdaftar untuk event ini!',
            'email.email'     => 'Format email Anda sepertinya salah. Pastikan menggunakan format yang benar (contoh: nama@domain.com).',
            'email.required'  => 'Email wajib diisi.',
            'name.required'   => 'Nama lengkap wajib diisi.',
            'business.required' => 'Nama bisnis/usaha wajib diisi.',
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'city.required'   => 'Kota asal wajib diisi.',
        ]);

        Log::info('ParticipantController@store: Validation successful');

        $affiliateId = $request->input('affiliate_id', 'admin');

        $notification = Notification::where('event_type', $eventType)->latest()->first();
        $isPaidEvent = $notification && $notification->is_paid;
        $price = $notification ? $notification->price : 0;

        // Simpan data peserta baru
        $participant = Participant::create([
            'name'           => $request->input('name'),
            'business'       => $request->input('business'),
            'email'          => $request->input('email'),
            'whatsapp'       => $whatsapp,
            'city'           => $request->input('city'),
            'event_type'     => $eventType,
            'affiliate_id'   => $affiliateId,
            'payment_status' => 'unpaid',
            'is_paid'        => 0,
        ]);

        Log::info('ParticipantController@store: Participant created successfully', ['participant_id' => $participant->id]);

        if ($isPaidEvent) {
            Log::info("ParticipantController@store: Paid event '{$eventType}' detected. Redirecting to payment.", ['participant_id' => $participant->id, 'price' => $price]);

            return redirect()->route('payment.initiate', [
                'type' => 'event',
                'identifier' => $participant->id,
                'price' => $price,
            ]);
        }

        // Event gratis: kirim notifikasi WA dan tampilkan pesan sukses
        Log::info('ParticipantController@store: Free event detected. Sending notifications.', ['participant_id' => $participant->id]);

        $waService->sendToParticipant($participant, $affiliateId);
        $waService->sendAdminNotification($participant, $eventType);
        if ($affiliateId !== 'admin') {
            $waService->sendAffiliateNotification($affiliateId, $participant, $eventType);
        }

        return redirect()->back()->with('success', 'Selamat Anda berhasil mendaftar!');
    }
}
