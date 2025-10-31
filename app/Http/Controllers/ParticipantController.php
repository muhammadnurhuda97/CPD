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
use Illuminate\Support\Str; // Pastikan Str di-import


class ParticipantController extends Controller
{
    public function showForm(Request $request, Notification $notification)
    {
        // Ambil ref_p DARI URL HALAMAN INI
        $ref_p = $request->query('ref_p');
        // Ambil affiliate_id dari URL atau session jika perlu di form
        $affiliateId = $request->query('affiliate_id') ?? session('affiliate_id');

        // Kirim $notification dan $ref_p ke view form
        return view('participants.form', compact('notification', 'ref_p', 'affiliateId'));
    }
    public function store(Request $request, WhatsAppNotificationService $waService)
    {
        Log::info('ParticipantController@store: Request received', $request->except('password', '_token'));

        $notificationId = $request->input('notification_id');
        $notification = Notification::find($notificationId);

        // --- Validasi Waktu Pendaftaran ---
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
        } else {
            Log::error('ParticipantController@store: Notification not found!', ['notification_id' => $notificationId]);
            return redirect()->back()
                ->withErrors(['event_expired' => 'Event tidak ditemukan.'])
                ->withInput();
        }

        // --- Sanitasi Nomor WhatsApp ---
        $whatsapp = preg_replace('/\D/', '', $request->input('whatsapp'));
        if (substr($whatsapp, 0, 2) === '62') {
            $whatsapp = '0' . substr($whatsapp, 2);
        }
        $request->merge(['whatsapp' => $whatsapp]);

        // --- Cek Peserta Existing ---
        $existingParticipant = Participant::where('whatsapp', $whatsapp)
            ->where('notification_id', $notificationId)
            ->first();

        if ($existingParticipant) {
            // Jika sudah lunas, jangan biarkan daftar lagi
            if ($existingParticipant->payment_status === 'paid') {
                Log::warning('ParticipantController@store: Existing participant already paid.', ['whatsapp' => $whatsapp, 'notification_id' => $notificationId]);
                return redirect()->back()
                    ->withErrors(['whatsapp' => 'Nomor WhatsApp ini sudah terdaftar dan lunas untuk event ini.'])
                    ->withInput();
            }
            // Jika berbayar tapi belum lunas, arahkan ke halaman pilihan bayar
            if ($notification->is_paid) {
                Log::info('ParticipantController@store: Existing unpaid participant for paid event. Redirecting to payment choice.', ['participant_id' => $existingParticipant->id]);
                return redirect()->route('payment.choice', ['participant' => $existingParticipant->id]);
            }
            // Jika gratis dan sudah terdaftar, arahkan ke sukses
            Log::info('ParticipantController@store: Existing participant for free event. Redirecting to success page.', ['participant_id' => $existingParticipant->id]);
            return redirect()->route('participant.success', ['participant' => $existingParticipant->id]);
        }

        // --- Validasi Input ---
        $request->validate([
            'name' => 'required|string|max:255',
            'business' => 'required|string|max:255',
            'email' => 'required|email:rfc,dns|max:255',
            'whatsapp' => 'required|string|max:20', // Validasi nomor yang sudah disanitasi
            'city' => 'required|string|max:255',
            'event_type' => 'required|string|in:webinar,workshop',
            'notification_id' => 'required|exists:notifications,id',
        ]);

        // --- Tangkap Referral Peserta ---
        $referred_by_id = $request->input('ref_p');
        if (!is_numeric($referred_by_id)) {
            Log::warning('Invalid ref_p detected', ['ref_p' => $referred_by_id]);
            $referred_by_id = null;
        }

        Log::info('DEBUG: ref_p captured from URL', ['ref_p_value' => $referred_by_id]);

        $referrerParticipant = null;
        if ($referred_by_id) {
            Log::info('DEBUG: Attempting to find referrer', [
                'id' => $referred_by_id,
                'notification_id' => $notificationId
            ]);

            $referrerParticipant = Participant::where('id', $referred_by_id)
                ->where('notification_id', $notificationId)
                ->first();

            if ($referrerParticipant) {
                Log::info('DEBUG: Referrer FOUND', ['referrer_id' => $referrerParticipant->id]);
            } else {
                Log::warning('DEBUG: Referrer NOT FOUND or wrong event', [
                    'searched_id' => $referred_by_id,
                    'event_id' => $notificationId
                ]);
            }
        } else {
            Log::info('DEBUG: ref_p is not numeric or not present', ['ref_p_value' => $referred_by_id]); // <-- LOG 5
        }

        // --- Tentukan Affiliate ID (LOGIKA BARU untuk Multi-Level) ---
        $finalAffiliateId = null;
        if ($referrerParticipant) {
            // Jika diundang oleh peserta lain (B), warisi affiliate_id dari B
            $finalAffiliateId = $referrerParticipant->affiliate_id;
            Log::info('ParticipantController@store: Inheriting affiliate ID from referrer participant.', [
                'referrer_id' => $referrerParticipant->id,
                'inherited_affiliate_id' => $finalAffiliateId
            ]);
        } else {
            // Jika tidak ada referrer peserta (A diundang Affiliate X), ambil dari form/session
            $affiliateUsername = $request->input('affiliate_id'); // Dari form (hidden input)
            // Coba cari berdasarkan username DULU
            $affiliateUser = User::where('username', $affiliateUsername)->first();
            // Jika tidak ketemu username, coba cari berdasarkan ID (untuk backward compatibility jika ada)
            if (!$affiliateUser && is_numeric($affiliateUsername)) {
                $affiliateUser = User::find($affiliateUsername);
            }

            if ($affiliateUser) {
                $finalAffiliateId = $affiliateUser->id; // Selalu simpan ID User
                Log::info('ParticipantController@store: Affiliate determined from form/session.', ['input_affiliate' => $affiliateUsername, 'affiliate_user_id' => $finalAffiliateId]);
            }
        }

        // Default ke admin HANYA jika tidak ada referrer DAN tidak ada affiliate valid di form/session
        if (is_null($finalAffiliateId)) {
            $adminUser = User::where('username', 'admin')->firstOrFail();
            $finalAffiliateId = $adminUser->id;
            Log::warning('ParticipantController@store: No valid referrer or affiliate found. Defaulting to admin.', ['admin_id' => $finalAffiliateId]);
        }


        // --- Buat Data Peserta Baru ---
        $participant = Participant::create([
            'name' => $request->input('name'),
            'business' => $request->input('business'),
            'email' => $request->input('email'),
            'whatsapp' => $whatsapp, // Gunakan nomor yang sudah disanitasi
            'city' => $request->input('city'),
            'event_type' => $request->input('event_type'),
            'affiliate_id' => $finalAffiliateId, // Gunakan affiliate_id User (selalu ID)
            'notification_id' => $notificationId,
            'payment_status' => $notification->is_paid ? 'pending_choice' : 'paid', // Status awal baru atau langsung paid jika gratis
            'is_paid' => $notification->is_paid ? 0 : 1, // Langsung dianggap lunas jika gratis
            'referred_by_participant_id' => $referrerParticipant ? $referrerParticipant->id : null, // Simpan ID perujuk jika valid
            'payment_method' => null, // Awalnya null
            'order_id' => null, // Awalnya null, akan diupdate
        ]);

        // Generate dan Update Order ID setelah Participant dibuat (agar dapat ID-nya)
        $date = Carbon::now('Asia/Jakarta')->format('dmy');
        $eventTypeCode = strtoupper(substr($participant->event_type, 0, 3));
        $uniqueSuffix = strtoupper(Str::random(3));
        $finalOrderId = "ORD-{$eventTypeCode}-{$date}-" . str_pad($participant->id, 4, '0', STR_PAD_LEFT) . "-{$uniqueSuffix}";
        $participant->order_id = $finalOrderId;
        $participant->save(); // Simpan lagi untuk update Order ID

        Log::info('ParticipantController@store: Participant created successfully', ['participant_id' => $participant->id, 'order_id' => $participant->order_id, 'final_affiliate_id' => $finalAffiliateId]);

        // --- Tentukan Langkah Berikutnya Berdasarkan Tipe Event ---
        if ($notification->is_paid) {
            // Event Berbayar: Arahkan ke Halaman Pilihan Pembayaran
            Log::info('ParticipantController@store: Paid event. Redirecting to payment choice.', ['participant_id' => $participant->id]);
            return redirect()->route('payment.choice', ['participant' => $participant->id]);
        } else {
            // Event Gratis: (Alur ini tetap, karena event gratis langsung LUNAS)
            Log::info('ParticipantController@store: Free event. Sending confirmations and redirecting to success page.', ['participant_id' => $participant->id]);
            $waService->sendPaidConfirmation($participant); // Kirim konfirmasi lunas (yg menjadwalkan H-2 & H+6)
            $waService->sendAdminNotification($participant, $request->input('event_type'));

            // Kirim notif ke affiliate hanya jika bukan admin
            $adminUser = User::where('username', 'admin')->first();
            // Gunakan $finalAffiliateId yang sudah pasti ID user
            if ($adminUser && $finalAffiliateId !== $adminUser->id) {
                // Tidak perlu load relasi lagi karena $finalAffiliateId sudah ID
                $waService->sendAffiliateNotification($finalAffiliateId, $participant, $request->input('event_type'));
            }

            return redirect()->route('participant.success', ['participant' => $participant->id]);
        }
    }
}
