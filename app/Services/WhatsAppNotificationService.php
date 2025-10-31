<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Participant;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
// Note: Jobs tidak perlu di-use di sini karena Service ini yang dipanggil oleh Jobs/Controllers

class WhatsAppNotificationService
{
    // ======================================
    // CORE SENDER METHOD
    // ======================================

    /**
     * Fungsi inti untuk mengirim pesan ke API Starsender.
     * @param array $messageData Data lengkap untuk API Starsender (termasuk to, body, messageType, tDate*, tTime*).
     * @return void
     */
    public function sendToStarsender(array $messageData)
    {
        $curl = curl_init();
        $apiKey = config('services.starsender.api_key');
        $apiUrl = config('services.starsender.url');

        // Validasi konfigurasi
        if (empty($apiKey) || empty($apiUrl)) {
            Log::error('WANS: Starsender URL atau API Key tidak dikonfigurasi.');
            return;
        }

        // Set default messageType jika tidak ada
        if (!isset($messageData['messageType'])) {
            $messageData['messageType'] = 'text';
        }

        Log::debug('WANS: Preparing to send message via Starsender.', $messageData);

        // Konfigurasi cURL
        curl_setopt_array($curl, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($messageData),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: ' . $apiKey,],
        ]);

        // Eksekusi & Handle Response/Error
        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            Log::error('WANS: Starsender CURL Error: ' . curl_error($curl), ['to' => $messageData['to'] ?? 'N/A']);
        } else {
            Log::info('WANS: Starsender API response: ' . $response, ['to' => $messageData['to'] ?? 'N/A']);
        }
        curl_close($curl);
    }

    // ======================================
    // PARTICIPANT NOTIFICATIONS
    // ======================================

    /**
     * UPDATE: Mengirim notifikasi awal ke peserta setelah pendaftaran berhasil.
     * @param Participant $participant
     * @return void
     */
    public function sendPostEventMessage(Participant $participant)
    {
        // 1. Validasi & Load Data
        if ($participant->notified_registered) {
            Log::info("WANS: Post-registration message already sent for participant {$participant->id}.");
            return;
        }
        $participant->loadMissing('notification');
        if (!$participant->notification) {
            Log::warning("WANS: Notification data missing for Participant ID '{$participant->id}' in sendPostEventMessage.");
            return;
        }

        // 2. Ambil detail event & admin contact
        $eventName = $participant->notification->event;
        $eventDateWIB = Carbon::parse($participant->notification->event_date . ' ' . $participant->notification->event_time, 'Asia/Jakarta');
        $eventDateWIBFormatted = $eventDateWIB->translatedFormat('l, d F Y');
        $eventTimeWIB = $eventDateWIB->format('H.i') . ' WIB';

        $adminNumbersArray = config('services.admin.whatsapp', ['082245342997']);
        $adminPhoneNumberForDisplay = implode(' / ', array_filter(array_map('trim', $adminNumbersArray)));
        if (empty($adminPhoneNumberForDisplay)) $adminPhoneNumberForDisplay = 'Admin';

        // 3. Susun Pesan Awal
        $message = "🎉 *Pendaftaran Awal Berhasil!* 🎉\n\n" .
            "Yth. *{$participant->name}*,\n\n" .
            "Terima kasih telah melakukan pendaftaran awal untuk event:\n" .
            "✨ *$eventName*\n" .
            "🗓️ Hari/Tanggal: $eventDateWIBFormatted\n" .
            "⏰ Waktu: $eventTimeWIB\n\n";

        // 4. Tambah pesan berbeda tergantung event berbayar atau gratis
        if ($participant->notification->is_paid) {
            // Jika Berbayar
            $message .= "Metode pembayaran Anda (*" . ucfirst($participant->payment_method ?? 'N/A') . "*) telah kami catat.\n\n";

            if ($participant->payment_method === 'cash') {
                $message .= "Silakan lakukan pembayaran tunai sesuai instruksi di halaman berikutnya.\n\n";
            } else { // Asumsi midtrans/pending
                $message .= "Silakan selesaikan pembayaran online Anda.\n\n";
            }

            // --- PERUBAHAN TEKS REFERRAL ---
            if ($participant->notification->referral_discount_amount > 0) {
                $discountAmount = number_format($participant->notification->referral_discount_amount, 0, ',', '.');
                // Generate Link Referral langsung di sini
                $routeName = $participant->notification->event_type === 'webinar' ? 'webinar.form' : 'workshop.form';
                $refLink = route($routeName, ['notification' => $participant->notification_id, 'ref_p' => $participant->id]);

                $message .= "💡 *Info Menarik:* Gunakan link referral Anda berikut ini untuk mendapatkan *cashback/diskon sebesar Rp {$discountAmount}*!\n" .
                    "$refLink\n\n" .
                    "(Diskon/cashback akan diproses setelah teman Anda mendaftar & lunas, dan pembayaran Anda sendiri terkonfirmasi).\n\n";
            }
        } else {
            // Jika Gratis (Logika ini tetap untuk alur ParticipantController::store event gratis)
            $message .= "Pendaftaran Anda sudah kami terima. Informasi detail acara akan kami informasikan mendekati hari pelaksanaan.\n\n";
        }

        $message .= "Jika ada pertanyaan, silakan hubungi kami di $adminPhoneNumberForDisplay.\n\n" .
            "Hormat kami,\n" .
            "Panitia Event";

        // 5. Kirim Pesan
        $messageData = ['to' => $participant->whatsapp, 'body' => $message];
        $this->sendToStarsender($messageData);

        // 6. Update Flag (Save dilakukan oleh caller)
        $participant->notified_registered = true;
        Log::info("WANS: Post-registration message sent to participant {$participant->id}.");
    }

    /**
     * UPDATE: Mengirim konfirmasi Lunas ke Peserta (Ditambah Link Referral).
     * @param Participant $participant Peserta yang lunas.
     * @return void
     */
    public function sendPaidConfirmation(Participant $participant)
    {
        // 1. Validasi & Load Data
        if ($participant->notified_paid) {
            Log::info("WANS: Paid confirmation already sent for participant {$participant->id}.");
            return;
        }
        $participant->loadMissing('notification');
        if (!$participant->notification) {
            Log::warning("WANS: Notification data missing for Participant ID '{$participant->id}' in sendPaidConfirmation.");
            return;
        }

        // 2. Ambil detail event
        $eventName = $participant->notification->event;
        $eventDateWIBFormatted = Carbon::parse($participant->notification->event_date)->translatedFormat('l, d F Y');
        $eventTimeWIB = Carbon::parse($participant->notification->event_time, 'Asia/Jakarta')->format('H.i') . ' WIB';

        // 3. Generate Link Referral (jika ada diskon)
        $refLink = null;
        $referralMessagePart = "";
        if ($participant->notification->referral_discount_amount > 0) {
            $routeName = $participant->notification->event_type === 'webinar' ? 'webinar.form' : 'workshop.form';
            $refLink = route($routeName, ['notification' => $participant->notification_id, 'ref_p' => $participant->id]);
            $discountAmount = number_format($participant->notification->referral_discount_amount, 0, ',', '.');
            $referralMessagePart = "\n---\n" .
                "💰 *Program Referral Spesial Untuk Anda!* 💰\n\n" .
                "Dapatkan *cashback/diskon sebesar Rp {$discountAmount}* untuk setiap teman yang berhasil mendaftar dan lunas melalui link unik Anda:\n\n" .
                "$refLink\n\n" .
                "Bagikan link ini sebanyak-banyaknya!";
        }

        // 4. Susun Pesan Lunas
        $message = "✅ *Konfirmasi Pembayaran Berhasil* ✅\n\n" .
            "Yth. *{$participant->name}*,\n\n" .
            "Pembayaran Anda untuk event:\n" .
            "✨ *$eventName*\n" .
            "🗓️ Tanggal: $eventDateWIBFormatted\n" .
            "⏰ Waktu: $eventTimeWIB\n" .
            "telah kami terima dan konfirmasi.\n\n";

        // 5. Tambahkan Detail Lokasi/Zoom
        if ($participant->event_type === 'webinar' && $participant->notification->zoom) {
            $message .= "Berikut detail akses acara:\n- 🖥️ Lokasi: *Online via Zoom*\n- Link Acara: {$participant->notification->zoom}\n\n";
        } elseif ($participant->event_type === 'workshop' && $participant->notification->location_name) {
            $message .= "Berikut detail lokasi acara:\n- 📍 Tempat: *{$participant->notification->location_name}*\n";
            if ($participant->notification->location_address) {
                $message .= "- Alamat: {$participant->notification->location_address}\n";
            }
            if ($participant->notification->location) {
                $message .= "- Link Google Maps: {$participant->notification->location}\n";
            }
            $message .= "\n";
        }

        $message .= "Mohon simpan pesan ini sebagai bukti pendaftaran Anda yang sah.";

        // 6. Gabungkan dengan Bagian Referral
        $message .= $referralMessagePart;

        $message .= "\n\nTerima kasih atas partisipasi Anda. Sampai jumpa di acara!\n\n" .
            "Hormat kami,\nPanitia Event";

        // 7. Kirim Pesan
        $messageData = ['to' => $participant->whatsapp, 'body' => $message];
        $this->sendToStarsender($messageData);

        // 8. Tandai sudah terkirim (Save dilakukan oleh caller)
        $participant->notified_paid = true;
        Log::info("WANS: Paid confirmation sent to participant {$participant->id}. Flag notified_paid set to true.");

        // 9. JADWALKAN SEMUA REMINDER SETELAH LUNAS
        $this->schedulePaidReminder($participant, $refLink); // Jadwalkan H-2 Jam
        $this->schedulePostEventReminder($participant); // <-- PINDAHKAN H+6 KE SINI
    }

    // ======================================
    // REFERRER NOTIFICATIONS (NEW CATEGORY)
    // ======================================

    /**
     * NOTIFIKASI BARU: Mengirim pesan ke Referrer (Peserta Pengundang) saat invitee lunas.
     * @param Participant $invitee Peserta baru yang pembayarannya lunas.
     * @param Participant $referrer Peserta yang mengundang invitee.
     * @return void
     */
    public function sendReferrerNotification(Participant $invitee, Participant $referrer)
    {
        // 1. Validasi Referrer
        if (empty($referrer->whatsapp)) {
            Log::warning("WANS: Referrer WhatsApp number is missing, cannot send notification.", ['referrer_id' => $referrer->id]);
            return;
        }

        // 2. Load data event dari invitee
        $invitee->loadMissing('notification');
        if (!$invitee->notification) {
            Log::error("WANS: Notification data missing for invitee when sending to referrer.", ['invitee_id' => $invitee->id]);
            return;
        }

        // 3. Ambil detail event dan diskon
        $eventName = $invitee->notification->event ?? 'Event';
        $discountAmount = number_format($invitee->notification->referral_discount_amount ?? 0, 0, ',', '.');

        // 4. Susun Pesan berdasarkan Status Pembayaran Invitee
        $message = "";
        if ($invitee->is_paid || $invitee->payment_status === 'paid') {
            // --- PESAN SAAT LUNAS ---
            $message = "🎉 *Referral Berhasil, {$referrer->name}!* 🎉\n\n" .
                "Teman Anda, *{$invitee->name}* ({$invitee->whatsapp}), telah berhasil *LUNAS* untuk event *$eventName*.\n\n" .
                "✅ *Diskon Referral sebesar Rp {$discountAmount} telah tercatat atas nama Anda!*\n\n";

            if ($invitee->payment_method === 'cash') {
                $message .= "*Penting:* Diskon ini akan valid dan dihitung jika Anda (sebagai pengundang) juga hadir pada saat event berlangsung.\n\n";
            } else {
                $message .= "Diskon akan dikalkulasi dan dibayarkan setiap hari Jumat.\n\n";
            }
            $message .= "Terus bagikan link undangan Anda untuk mendapatkan lebih banyak diskon!";
        } else {
            // --- PESAN SAAT BARU DAFTAR (BELUM LUNAS) ---
            $statusMessage = "Menunggu Pembayaran";
            if ($invitee->payment_method === 'cash') {
                $statusMessage = "Menunggu Bayar Tunai";
            }

            $message = "🔔 *Referral Baru Masuk, {$referrer->name}!* 🔔\n\n" .
                "Teman Anda, *{$invitee->name}* ({$invitee->whatsapp}), telah mendaftar event *$eventName* melalui link Anda.\n\n" .
                "Status: *{$statusMessage}*\n\n" .
                "Anda akan mendapatkan diskon *Rp {$discountAmount}* jika pembayaran teman Anda terkonfirmasi LUNAS.\n\n" .
                "Bantu ingatkan teman Anda untuk segera menyelesaikan pembayaran ya! 😉";
        }

        // 5. Siapkan data & Kirim
        $messageData = ['to' => $referrer->whatsapp, 'body' => $message];
        $this->sendToStarsender($messageData);

        // 6. Logging
        Log::info("WANS: Referrer notification sent successfully.", [
            'referrer_id' => $referrer->id,
            'invitee_id' => $invitee->id,
            'status_sent' => $invitee->is_paid ? 'LUNAS' : 'PENDING'
        ]);
    }

    // ======================================
    // AFFILIATE NOTIFICATIONS
    // ======================================

    /**
     * Mengirim notifikasi ke Affiliate saat ada pendaftar baru lunas melalui linknya.
     * @param int $affiliateUserId ID User affiliate.
     * @param Participant $participant Data peserta yang mendaftar.
     * @param string $eventType Tipe event (webinar/workshop).
     * @return void
     */
    public function sendAffiliateNotification(int $affiliateUserId, Participant $participant, string $eventType)
    {
        $affiliate = User::find($affiliateUserId);
        if (!$affiliate || !$affiliate->whatsapp) {
            Log::warning("WANS: Affiliate not found or missing WhatsApp number.", ['affiliate_user_id' => $affiliateUserId]);
            return;
        }
        $participant->loadMissing(['notification', 'referrer']); // Load event & referrer

        $eventName = $participant->notification->event ?? ($eventType == 'webinar' ? 'Webinar' : 'Workshop');

        // --- LOGIKA STATUS BARU ---
        $statusMessage = "Status Tidak Dikenal";
        if ($participant->is_paid || $participant->payment_status === 'paid') {
            $statusMessage = "LUNAS";
        } elseif ($participant->payment_method === 'cash') {
            $statusMessage = "Menunggu Bayar Tunai";
        } elseif ($participant->payment_method === 'midtrans') {
            $statusMessage = "Menunggu Bayar Non-Tunai";
        } else {
            $statusMessage = $participant->notification->is_paid ? "Pending" : "GRATIS (Lunas)";
        }
        // -------------------------

        $message = "🎉 *Lead Baru, {$affiliate->name}!* 🎉\n\n" .
            "Anda mendapatkan lead baru untuk event *$eventName*:\n" .
            "- Nama: *{$participant->name}*\n" .
            "- WhatsApp: {$participant->whatsapp}\n" .
            "- Kota: {$participant->city}\n" .
            "- Status Pembayaran: *{$statusMessage}*\n\n";

        // Tambah info referrer jika ada
        if ($participant->referrer) {
            $referrerName = $participant->referrer->name ?? 'N/A';
            $message .= "*Diundang oleh:* Peserta {$referrerName}\n\n";

            // Info komisi tambahan HANYA jika invitee sudah lunas
            if ($participant->is_paid && $participant->notification && $participant->notification->participant_referral_commission > 0) {
                $commissionAmount = number_format($participant->notification->participant_referral_commission, 0, ',', '.');
                $message .= "💰 *Bonus Lunas:* Anda mendapatkan *komisi tambahan Rp {$commissionAmount}* karena referral peserta ini lunas!\n\n";
            }
        }

        // Tampilkan aksi berdasarkan status
        if ($participant->is_paid) {
            $message .= "Semoga menjadi konversi yang berkah!";
        } else {
            $message .= "Jangan lupa follow up ya! 💪";
        }

        $messageData = ['to' => $affiliate->whatsapp, 'body' => $message];
        $this->sendToStarsender($messageData);
        Log::info("WANS: Affiliate notification sent to {$affiliate->name} for participant {$participant->id}. Status: {$statusMessage}");
    }

    // ======================================
    // ADMIN NOTIFICATIONS
    // ======================================

    /**
     * Mengirim notifikasi ke Admin saat ada pendaftar baru (lunas/belum).
     * @param Participant $participant Data peserta.
     * @param string $eventType Tipe event.
     * @return void
     */
    public function sendAdminNotification(Participant $participant, string $eventType)
    {
        $adminNumbers = config('services.admin.whatsapp', []);
        if (empty($adminNumbers)) {
            Log::warning("WANS: Admin WhatsApp numbers not configured in config/services.php.");
            return;
        }

        $participant->loadMissing(['notification', 'affiliateUser', 'referrer']);
        $eventName = $participant->notification->event ?? ($eventType == 'webinar' ? 'Webinar' : 'Workshop');
        $affiliateName = $participant->affiliateUser->name ?? 'N/A';
        $affiliateWhatsapp = $participant->affiliateUser->whatsapp ?? 'N/A';

        // --- LOGIKA STATUS BARU ---
        $statusMessage = "Status Tidak Dikenal";
        if ($participant->is_paid || $participant->payment_status === 'paid') {
            $statusMessage = "LUNAS";
        } elseif ($participant->payment_method === 'cash') {
            $statusMessage = "Menunggu Bayar Tunai";
        } elseif ($participant->payment_method === 'midtrans') {
            $statusMessage = "Menunggu Bayar Non-Tunai";
        } else {
            // Fallback untuk pendaftaran gratis (yang langsung 'paid' tapi 'is_paid' mungkin 0 di awal)
            $statusMessage = $participant->notification->is_paid ? "Pending" : "GRATIS (Lunas)";
        }
        // -------------------------

        $adminMessage = "🔔 *Pendaftar Baru ({$statusMessage}) - {$eventName}* 🔔\n\n" . // Status di judul
            "Nama: *{$participant->name}*\n" .
            "No. WA: {$participant->whatsapp}\n" .
            "Order ID: *{$participant->order_id}*\n" .
            "Affiliate: *{$affiliateName}* ({$affiliateWhatsapp})\n";

        if ($participant->referrer) {
            $referrerName = $participant->referrer->name ?? 'N/A';
            $adminMessage .= "Referrer (Peserta): *{$referrerName}*\n";
        }

        // Tampilkan aksi berdasarkan status
        if ($participant->payment_status === 'pending_cash_verification') {
            $adminMessage .= "\n*Aksi Diperlukan:* Konfirmasi pembayaran tunai di Laporan Referral.";
        } elseif ($participant->payment_status === 'paid') {
            $adminMessage .= "\nPembayaran telah dikonfirmasi.";
        } else {
            $adminMessage .= "\nMenunggu pembayaran dari peserta.";
        }

        foreach ($adminNumbers as $number) {
            if (!empty(trim($number))) {
                $messageData = ['to' => trim($number), 'body' => $adminMessage];
                $this->sendToStarsender($messageData);
            }
        }
        Log::info("WANS: Admin notification sent for participant {$participant->id}. Status: {$statusMessage}");
    }

    /**
     * Mengirim notifikasi ke Admin saat ada pesanan produk baru masuk (Lunas).
     * @param Order $order
     * @return void
     */
    public function sendNewProductOrderAdminNotification(Order $order)
    {
        $order->load(['user', 'product']);
        $user = $order->user;
        $product = $order->product;

        if (!$user || !$product) {
            Log::error("WANS: Gagal mengirim notif admin order produk, data user/produk tidak lengkap.", ['order_id' => $order->id]);
            return;
        }

        $adminMessage = "📦 *Pesanan Produk LUNAS* 📦\n\n" . // Menekankan Lunas
            "Order ID: *{$order->order_id}*\n" .
            "Produk: *{$product->name}*\n" .
            "Jumlah: Rp " . number_format($order->amount, 0, ',', '.') . "\n\n" .
            "Dipesan oleh:\n" .
            "- Nama: *{$user->name}*\n" .
            "- Email: *{$user->email}*\n" .
            "- No. WA: *{$user->whatsapp}*\n\n" .
            "Status Order: *{$order->status}*\n" . // Tampilkan status order (completed/paid)
            "Mohon segera diproses jika perlu.";

        $adminNumbers = config('services.admin.whatsapp', []);
        if (empty($adminNumbers)) {
            Log::warning("WANS: Admin WhatsApp numbers not configured.");
            return;
        }

        foreach ($adminNumbers as $number) {
            if (!empty(trim($number))) {
                $this->sendToStarsender(['messageType' => 'text', 'to' => trim($number), 'body' => $adminMessage]);
            }
        }
        Log::info("WANS: Mengirim notifikasi pesanan produk LUNAS ke admin untuk order {$order->id}.");
    }

    // ======================================
    // REMINDER MESSAGES & SCHEDULERS
    // ======================================

    /**
     * Mengirim reminder ke peserta yang belum bayar.
     * @param Participant $participant
     * @return void
     */
    public function sendUnpaidReminder(Participant $participant)
    {
        // Jangan kirim jika sudah lunas, flag notified_unpaid sudah true, atau data event tidak ada
        if ($participant->is_paid || $participant->notified_unpaid || !$participant->notification) {
            Log::info("WANS: Unpaid reminder skipped for participant {$participant->id}.");
            return;
        }

        $eventName = $participant->notification->event;
        // Arahkan ke halaman pilihan bayar, bukan langsung Midtrans
        $paymentLink = route('payment.choice', ['participant' => $participant->id]);

        $message = "⏳ *Reminder Pembayaran - {$eventName}* ⏳\n\n" .
            "Yth. {$participant->name},\n" .
            "Kami mengingatkan bahwa pembayaran Anda untuk event *$eventName* masih menunggu penyelesaian.\n\n" .
            "Agar terdaftar sepenuhnya dan tidak kehilangan tempat, mohon segera selesaikan pembayaran melalui link berikut:\n" .
            "$paymentLink\n\n" .
            "Jika Anda sudah membayar atau memilih metode tunai, mohon abaikan pesan ini. Terima kasih.";

        $messageData = ['to' => $participant->whatsapp, 'body' => $message];
        $this->sendToStarsender($messageData);

        // Tandai sudah dikirimi reminder & simpan
        $participant->notified_unpaid = true;
        $participant->save();
        Log::info("WANS: Unpaid reminder sent to participant {$participant->id}.");
    }

    /**
     * UPDATE: Menjadwalkan Reminder H-2 Jam (Ditambah Link Referral).
     * @param Participant $participant Peserta yang akan dikirimi reminder.
     * @param string|null $refLink Link referral yang sudah digenerate (opsional).
     * @return void
     */
    public function schedulePaidReminder(Participant $participant, $refLink = null)
    {
        // 1. Jangan jadwalkan jika sudah pernah atau notifikasi belum terhubung
        if ($participant->paid_reminder_scheduled || !$participant->notification) {
            Log::info("WANS: Paid reminder skipped for {$participant->name} (already scheduled or notification missing).");
            return;
        }

        try {
            // 2. Hitung waktu pengiriman (H-2 Jam sebelum event)
            $eventDateTime = Carbon::parse($participant->notification->event_date . ' ' . $participant->notification->event_time, 'Asia/Jakarta');
            $sendTime = $eventDateTime->copy()->subHours(2);

            // 3. Jangan jadwalkan jika waktu pengiriman sudah lewat
            if ($sendTime->isPast()) {
                Log::warning("WANS: Paid reminder send time is in the past for {$participant->name}. Skipping.", ['send_time' => $sendTime->toDateTimeString()]);
                // Set flag true agar tidak dijadwalkan lagi
                $participant->paid_reminder_scheduled = true;
                $participant->save();
                return;
            }

            // 4. Siapkan bagian pesan referral (hanya jika ada diskon)
            $referralMessagePart = "";
            if ($participant->notification->referral_discount_amount > 0) {
                if (!$refLink) {
                    $routeName = $participant->notification->event_type === 'webinar' ? 'webinar.form' : 'workshop.form';
                    $refLink = route($routeName, ['notification' => $participant->notification_id, 'ref_p' => $participant->id]);
                }
                $referralMessagePart = "\n\nPS: Masih ada kesempatan ajak teman & dapatkan diskon! Bagikan link ini:\n" . $refLink;
            }

            // 5. Susun pesan reminder
            $message = "🔔 *Reminder: Event {$participant->notification->event} Dimulai 2 Jam Lagi!* 🔔\n\n" . // Judul lebih jelas
                "Yth. *{$participant->name}*,\n\n" .
                "Sebagai pengingat, event *{$participant->notification->event}* akan dimulai pukul *" . $eventDateTime->format('H.i') . " WIB* hari ini.\n\n";

            // 6. Tambahkan detail akses (Zoom/Lokasi)
            if ($participant->notification->event_type === 'webinar' && $participant->notification->zoom) {
                $message .= "Pastikan Anda sudah siap dan bergabung melalui link Zoom berikut:\n{$participant->notification->zoom}\n\n";
            } elseif ($participant->notification->event_type === 'workshop' && $participant->notification->location_name) {
                $message .= "Acara akan dilaksanakan di:\n- Tempat: *{$participant->notification->location_name}*\n";
                if ($participant->notification->location_address) {
                    $message .= "- Alamat: {$participant->notification->location_address}\n";
                }
                if ($participant->notification->location) {
                    $message .= "- Link Google Maps: {$participant->notification->location}\n";
                }
                $message .= "\nMohon hadir tepat waktu.\n";
            }

            $message .= "Kami tunggu kehadiran Anda!";

            // 7. Gabungkan dengan bagian referral (jika ada)
            $message .= $referralMessagePart;

            // 8. Siapkan data untuk Starsender (termasuk penjadwalan)
            $messageData = [
                'to' => $participant->whatsapp,
                'body' => $message,
                'tDate' => $sendTime->format('Y-m-d'), // Tanggal pengiriman
                'tTime' => $sendTime->format('H:i:s')   // Waktu pengiriman (format H:i:s)
            ];

            // 9. Kirim (jadwalkan) via Starsender
            $this->sendToStarsender($messageData);

            // 10. Tandai bahwa reminder sudah dijadwalkan & simpan
            $participant->paid_reminder_scheduled = true;
            $participant->save();
            Log::info("WANS: Paid reminder (H-2) scheduled via Starsender for participant {$participant->id} at {$sendTime->toDateTimeString()}");
        } catch (\Exception $e) {
            // Tangkap error jika terjadi saat penjadwalan
            Log::error("WANS: Failed to schedule paid reminder for participant {$participant->id}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * PERBAIKAN: Menjadwalkan reminder setelah event selesai (misal: H+6 Jam).
     * @param Participant $participant
     * @return void
     */
    public function schedulePostEventReminder(Participant $participant)
    {
        // 1. Validasi
        if ($participant->post_event_reminder_scheduled || !$participant->notification) {
            Log::info("WANS: Post-event reminder skipped for {$participant->name} (already scheduled or notification missing).");
            return;
        }

        try {
            // 2. Hitung waktu event
            $eventDateTime = Carbon::parse($participant->notification->event_date . ' ' . $participant->notification->event_time, 'Asia/Jakarta');

            // 3. Hitung waktu kirim (H+6 Jam dari event MULAI)
            $sendTime = $eventDateTime->copy()->addHours(6);

            // 4. Cek Waktu Kirim (Hanya jadwalkan jika waktunya di masa depan)
            if ($sendTime->isPast()) {
                Log::warning("WANS: Post-event reminder send time is in the past for {$participant->name}. Skipping.", [
                    'p_id' => $participant->id,
                    'send_time' => $sendTime->toDateTimeString()
                ]);
                $participant->post_event_reminder_scheduled = true; // Tandai agar tidak dijadwalkan lagi
                // $participant->save(); // Save dilakukan oleh caller (sendPaidConfirmation)
                return; // Jangan kirim pesan yang sudah basi
            }

            // 5. Susun pesan
            $message = "👋 *Terima Kasih Atas Kehadiran Anda!* 🙏\n\n" .
                "Yth. *{$participant->name}*,\n\n" .
                "Kami mengucapkan terima kasih atas partisipasi Anda dalam event *{$participant->notification->event}*.\n\n" .
                "Semoga ilmu dan pengalaman yang Anda dapatkan bermanfaat.\n\n" .
                "Nantikan informasi event kami selanjutnya!\n\n" .
                "Salam Sukses,\nPanitia Event";

            // 6. Siapkan data penjadwalan
            $messageData = [
                'messageType' => 'text',
                'to' => $participant->whatsapp,
                'body' => $message,
                'tDate' => $sendTime->format('Y-m-d'),
                'tTime' => $sendTime->format('H:i:s')
            ];

            // 7. Kirim (jadwalkan)
            $this->sendToStarsender($messageData);

            // 8. Tandai sudah dijadwalkan (Save dilakukan oleh caller)
            //    ===== PERBAIKAN TYPO =====
            $participant->post_event_reminder_scheduled = true;
            //    ==========================

            Log::info("WANS: Post-event reminder scheduled via Starsender for participant {$participant->id} target time {$sendTime->toDateTimeString()}");
        } catch (\Exception $e) {
            Log::error("WANS: Failed to schedule post-event reminder for participant {$participant->id}", [
                'error' => $e->getMessage(),
            ]);
        }
    }
} // End of class