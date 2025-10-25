<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Participant;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Jobs\SendWhatsAppReminder;
use App\Jobs\CheckPaymentStatusAndNotify;
use App\Jobs\SendUnpaidWorkshopReminder;

class WhatsAppNotificationService
{
    public function sendPaidConfirmation(Participant $participant)
    {
        if ($participant->notified_paid) {
            Log::info("WANS: Notifikasi lunas sudah terkirim sebelumnya untuk participant {$participant->id}.");
            return;
        }

        $notification = $participant->notification;

        if (!$notification) {
            Log::warning("WANS: Data notifikasi tidak ditemukan untuk Participant ID '{$participant->id}'.");
            return;
        }

        $eventName = $notification->event;
        $eventDateWIB = Carbon::parse($notification->event_date . ' ' . $notification->event_time, 'Asia/Jakarta');
        $eventDateWIBFormatted = $eventDateWIB->translatedFormat('l, d F Y');
        $eventTimeWIB = $eventDateWIB->format('H.i') . ' WIB';

        $locationDetailsWithLink = '';
        if ($participant->event_type === 'webinar') {
            $locationDetailsWithLink = "- 🖥️ Lokasi: *Online via Zoom*\n- Link Acara: *{$notification->zoom}*\n\n";
        } elseif ($participant->event_type === 'workshop') {
            $locationDetailsWithLink = "- 📍 Lokasi: *{$notification->location_name}*\n" .
                "- Alamat: *{$notification->location_address}*\n" .
                "- Link Gmaps: *{$notification->location}*\n\n";
        }

        $message = "Halo Kak {$participant->name}, 🎉 Selamat! Pembayaran Anda untuk acara *{$eventName}* telah berhasil kami terima.\n\n" .
            "*Detail Acara:*\n" .
            "- Acara: *{$eventName}*\n" .
            "- 📅 Hari, Tanggal: *{$eventDateWIBFormatted}*\n" .
            "- ⏰ Jam: *{$eventTimeWIB}*\n" .
            $locationDetailsWithLink .
            "Sampai jumpa di acara ya! 🙏🏻";

        $payload = [
            'messageType' => 'text',
            'to' => $participant->whatsapp,
            'body' => $message,
        ];

        $this->sendToStarsender($payload);
        Log::info("WANS: Mengirim notifikasi LUNAS ke PESERTA -> {$participant->whatsapp} untuk event '{$eventName}'.");

        $participant->notified_paid = true;
        $participant->save();
    }

    /**
     * Menggunakan logika fallback: coba H-2 Jam, jika sudah lewat, coba H-30 Menit.
     * Logika H+6 Jam SUDAH DIHAPUS dari sini.
     */
    public function schedulePaidEventReminders(Participant $participant)
    {
        if ($participant->paid_reminder_scheduled) {
            Log::info("WANS: Pengingat pra-acara untuk peserta lunas (ID: {$participant->id}) sudah dijadwalkan sebelumnya.");
            return;
        }

        $notification = $participant->notification;
        if (!$notification) {
            Log::warning("WANS: Gagal menjadwalkan reminder, data notifikasi tidak ditemukan untuk Participant ID '{$participant->id}'.");
            return;
        }

        $eventName = $notification->event;
        $eventDateWIB = Carbon::parse($notification->event_date . ' ' . $notification->event_time, 'Asia/Jakarta');

        $locationDetailsWithLink = "- 📍 Lokasi: *{$notification->location_name}*\n" .
            "- Alamat: *{$notification->location_address}*\n" .
            "- Link Gmaps: *{$notification->location}*\n\n";

        // Hitung waktu jadwal
        $scheduleTimeH2 = $eventDateWIB->copy()->subHours(2);
        $scheduleTimeH30 = $eventDateWIB->copy()->subMinutes(30);

        $messageToSend = null;
        $scheduleTimestamp = null;
        $reminderType = '';

        // Prioritas 1: Jadwalkan reminder H-2 Jam jika waktunya masih valid.
        if ($scheduleTimeH2->isFuture()) {
            $reminderType = 'H-2 Jam';
            $scheduleTimestamp = $scheduleTimeH2->timezone('UTC')->timestamp * 1000;
            $messageToSend = "Halo kak {$participant->name} 🤗\n\n" .
                "Sekedar mengingatkan ya kak untuk hadir di acara *{$eventName}* yang akan dilaksanakan pada:\n\n" .
                "- Hari / Tanggal : *{$eventDateWIB->translatedFormat('l, d F Y')}*\n" .
                "- Pukul : *{$eventDateWIB->format('H.i')} WIB*\n" .
                $locationDetailsWithLink .
                "Jangan lupa konfirmasi kehadiran ke petugas saat tiba di lokasi ya kak 🙏🏻\n\n" .
                "Sampai ketemu di lokasi acara ya kak 🤗";

            // Prioritas 2 (Fallback): Jika H-2 jam sudah lewat, jadwalkan reminder H-30 Menit.
        } elseif ($scheduleTimeH30->isFuture()) {
            $reminderType = 'H-30 Menit';
            $scheduleTimestamp = $scheduleTimeH30->timezone('UTC')->timestamp * 1000;
            $messageToSend = "Halo kak {$participant->name} 🤗\n\n" .
                "Acara *{$eventName}* akan dimulai sekitar 30 menit lagi!\n\n" .
                "- Pukul : *{$eventDateWIB->format('H.i')} WIB*\n" .
                $locationDetailsWithLink .
                "Kami tunggu kehadirannya ya kak. Hati-hati di jalan! 🙏🏻";
        }

        // Jika ada pesan yang perlu dijadwalkan (baik H-2 atau H-30)
        if ($messageToSend && $scheduleTimestamp) {
            $payload = [
                'messageType' => 'text',
                'to'          => $participant->whatsapp,
                'body'        => $messageToSend,
                'schedule'    => $scheduleTimestamp,
            ];
            $this->sendToStarsender($payload);
            Log::info("WANS: Menjadwalkan reminder LUNAS ({$reminderType}) di Starsender untuk peserta -> {$participant->whatsapp}");

            // Tandai bahwa reminder pra-acara sudah dijadwalkan
            $participant->paid_reminder_scheduled = true;
            $participant->save();
        } else {
            Log::info("WANS: Tidak ada jadwal reminder pra-acara yang valid untuk peserta lunas {$participant->id} karena waktu acara sudah terlalu dekat.");
        }
    }

    public function sendPostEventMessage(Participant $participant)
    {
        $notification = $participant->notification;
        if (!$notification) {
            Log::warning("WANS (After-Event): Gagal, data notifikasi tidak ditemukan untuk Participant ID '{$participant->id}'.");
            return;
        }

        $eventDateWIB = Carbon::parse($notification->event_date . ' ' . $notification->event_time, 'Asia/Jakarta');
        $scheduleTimestamp = $eventDateWIB->copy()->addHours(6)->timezone('UTC')->timestamp * 1000;

        if (Carbon::createFromTimestampMs($scheduleTimestamp)->isFuture()) {
            $message = "Halo kak {$participant->name} 🤗\n\n" .
                "Terimakasih sudah hadir di acara workshop Giat Muda Entrepreneur dengan tema Strategi Pemasaran Di Era Digital 🎉\n\n" .
                "Semoga acara workshop nya bermanfaat bagi kak {$participant->name} untuk pengembangan bisnis nya di era digital saat ini 😊🤲🏻\n\n" .
                "Apa ada hal yang ingin ditanyakan lebih lanjut mengenai acara workshop nya kak ?\n\n" .
                "Salam\n" .
                "Giat Muda Entrepreneur";

            $payload = [
                'messageType' => 'text',
                'to'          => $participant->whatsapp,
                'body'        => $message,
                'schedule'    => $scheduleTimestamp,
            ];

            $this->sendToStarsender($payload);
            Log::info("WANS: Menjadwalkan pesan After-Event (H+6 Jam) di Starsender untuk peserta ID {$participant->id}.");
        }
    }
    public function schedulePostEventReminderForNonPaid(Participant $participant)
    {
        // Pengecekan sederhana untuk mencegah duplikasi jika job berjalan ulang
        if ($participant->paid_reminder_scheduled) {
            return;
        }

        $notification = $participant->notification;
        if (!$notification) {
            Log::warning("WANS: Gagal menjadwalkan reminder H+6, notifikasi tidak ditemukan untuk Participant ID '{$participant->id}'.");
            return;
        }

        $eventDateWIB = Carbon::parse($notification->event_date . ' ' . $notification->event_time, 'Asia/Jakarta');
        $scheduleTimestamp = $eventDateWIB->copy()->addHours(6)->timezone('UTC')->timestamp * 1000;

        if (Carbon::createFromTimestampMs($scheduleTimestamp)->isFuture()) {
            $message = "Halo kak {$participant->name} 🤗\n\n" .
                "Terimakasih sudah hadir di acara workshop Giat Muda Entrepreneur dengan tema Strategi Pemasaran Di Era Digital 🎉\n\n" .
                "Semoga acara workshop nya bermanfaat bagi kak {$participant->name} untuk pengembangan bisnis nya di era digital saat ini 😊🤲🏻\n\n" .
                "Apa ada hal yang ingin ditanyakan lebih lanjut mengenai acara workshop nya kak ?\n\n" .
                "Salam\n" .
                "Giat Muda Entrepreneur";

            $payload = [
                'messageType' => 'text',
                'to'          => $participant->whatsapp,
                'body'        => $message,
                'schedule'    => $scheduleTimestamp,
            ];

            $this->sendToStarsender($payload);
            Log::info("WANS: Menjadwalkan reminder H+6 Jam (NON-PAID) di Starsender untuk peserta -> {$participant->whatsapp}");
        }
    }


    /**
     * Mengirim notifikasi pengingat tunai di lokasi untuk workshop berbayar yang belum lunas.
     * Ini dipanggil dari Job CheckPaymentStatusAndNotify.
     */
    public function sendUnpaidWorkshopReminder(Participant $participant)
    {
        if ($participant->payment_status === 'paid' || $participant->reminder_scheduled) {
            Log::info("WANS: Peserta sudah lunas atau reminder sudah terjadwal, tidak memproses reminder tunai untuk participant {$participant->id}.");
            return;
        }
        $notification = $participant->notification;
        if (!$notification) {
            Log::warning("WANS: Data notifikasi tidak ditemukan untuk Participant ID '{$participant->id}'.");
            return;
        }
        $eventDateWIB = Carbon::parse($notification->event_date . ' ' . $notification->event_time, 'Asia/Jakarta');
        $executionTime = $eventDateWIB->copy()->subHours(2);

        // Jika waktu eksekusi H-2 Jam masih di masa depan
        if ($executionTime->isFuture()) {
            // JADWALKAN JOB seperti biasa
            SendUnpaidWorkshopReminder::dispatch($participant->id)->delay($executionTime);
            Log::info("WANS: Menjadwalkan JOB SendUnpaidWorkshopReminder untuk participant {$participant->id} agar berjalan pada {$executionTime->toDateTimeString()}");

            $participant->reminder_scheduled = true;
            $participant->save();
        } else {
            // JIKA WAKTU SUDAH MEPET, LANGSUNG KIRIM REMINDER TUNAI SAAT ITU JUGA
            Log::warning("WANS: Waktu acara sudah terlalu dekat untuk participant {$participant->id}. Mengirim reminder tunai sekarang.");
            $this->sendUnpaidWorkshopReminderMessage($participant); // <-- Panggil fungsi pengirim pesan

            // Tetap tandai agar tidak dikirim dua kali
            $participant->reminder_scheduled = true;
            $participant->save();
        }
    }

    /**
     * FUNGSI BARU: Berisi teks reminder H-2 Jam untuk peserta yang belum lunas.
     * Fungsi ini dipanggil LANGSUNG oleh SendUnpaidWorkshopReminder Job.
     */
    public function sendUnpaidWorkshopReminderMessage(Participant $participant)
    {
        Log::info("WANS-DEBUG: Masuk ke fungsi sendUnpaidWorkshopReminderMessage untuk Participant ID: {$participant->id}");
        Log::info("WANS-DEBUG: Data Peserta:", $participant->toArray());
        Log::info("WANS-DEBUG: Mengecek relasi 'notification'...");

        $notification = $participant->notification;
        if (!$notification) {
            Log::warning("WANS: Gagal mengirim reminder tunai, data notifikasi tidak ditemukan untuk Participant ID '{$participant->id}'. Fungsi berhenti.");
            return;
        }

        Log::info("WANS-DEBUG: Relasi 'notification' ditemukan. Lanjut membuat pesan.");

        $eventName = $notification->event;
        Carbon::setLocale('id');
        $eventDateWIB = Carbon::parse($notification->event_date . ' ' . $notification->event_time, 'Asia/Jakarta');
        $eventDateWIBFormatted = $eventDateWIB->translatedFormat('l, d F Y');
        $eventTimeWIB = $eventDateWIB->format('H.i') . ' WIB';

        $locationDetailsWithLink = "- 📍 Lokasi: *{$notification->location_name}*\n" .
            "- Alamat: *{$notification->location_address}*\n" .
            "- Link Gmaps: *{$notification->location}*\n\n";

        $message =
            "Halo kak {$participant->name} 🤗\n\n" .
            "Sekedar mengingatkan ya kak untuk hadir di acara *{$eventName}* dengan tema Strategi Pemasaran Di Era Digital yang akan dilaksanakan pada :\n\n" .
            "- Hari / Tanggal : *{$eventDateWIBFormatted}*\n" .
            "- Pukul : *{$eventTimeWIB}*\n" .
            $locationDetailsWithLink .
            "💵 *Apabila belum melakukan pembayaran, kakak juga bisa melakukan pembayaran secara tunai di lokasi acara ya.*\n\n" .
            "Jangan lupa konfirmasi kehadiran ke petugas saat tiba di lokasi ya kak 🙏🏻\n\n" .
            "Sampai ketemu di lokasi acara ya kak 🤗\n\n" .
            "Salam\n" .
            "Giat Muda Entrepreneur";

        $payload = [
            'messageType' => 'text',
            'to'          => $participant->whatsapp,
            'body'        => $message,
        ];

        Log::info("WANS-DEBUG: Pesan sudah dibuat, siap dikirim ke Starsender.");

        $this->sendToStarsender($payload);

        Log::info("WANS: Mengirim reminder pembayaran tunai H-2 Jam ke PESERTA -> {$participant->whatsapp} untuk event '{$eventName}'.");
    }

    public function sendAdminNotification($participant)
    {
        $eventName = $participant->notification->event ?? 'Event Tidak Ditemukan';
        $affiliateUser = User::find($participant->affiliate_id);
        $affiliateName = $affiliateUser ? $affiliateUser->name : 'Tanpa Afiliasi';

        $adminMessage = "Peserta baru lunas untuk acara *{$eventName}*!\n\n" .
            "Nama: *{$participant->name}*\n" .
            "No. WhatsApp: *{$participant->whatsapp}*\n" .
            "Pengundang: *{$affiliateName}*\n\n" .
            "Segera verifikasi dan tindak lanjuti pendaftaran ini.";

        $adminNumbers = config('services.admin.whatsapp', ['082245342997']);

        Log::info("WANS: Mempersiapkan pengiriman notifikasi LUNAS ke ADMIN -> " . implode(', ', $adminNumbers));

        foreach ($adminNumbers as $number) {
            if (!empty(trim($number))) {
                $this->sendToStarsender(['messageType' => 'text', 'to' => trim($number), 'body' => $adminMessage]);
            }
        }
    }

    public function sendAffiliateNotification($affiliateId, $participant, $eventType)
    {
        $eventName = $participant->notification->event ?? 'Event Tidak Ditemukan';
        $affiliate = User::find($affiliateId);

        if ($affiliate && $affiliate->whatsapp) {
            $affiliateMessage = "🎉 Selamat Kak *{$affiliate->name}*!\n\n" .
                "Peserta undangan Anda, *{$participant->name}*, telah melunasi pembayaran untuk acara *{$eventName}*.";

            Log::info("WANS: Mempersiapkan pengiriman notifikasi LUNAS ke AFFILIATE -> {$affiliate->whatsapp}");

            $this->sendToStarsender(['messageType' => 'text', 'to' => $affiliate->whatsapp, 'body' => $affiliateMessage]);
        }
    }

    public function sendPendingPaymentDetails(
        $entity,
        string $paymentType,
        float $amount,
        ?string $vaNumber,
        ?string $bank,
        ?string $qrisContent,
        ?string $paymentCode,
        ?string $billerCode,
        ?string $billKey,
        ?Carbon $expiryTime
    ) {
        $recipientWhatsapp = null;
        $recipientName = '';
        $itemName = '';
        $orderId = null;

        if ($entity instanceof Participant) {
            $recipientWhatsapp = $entity->whatsapp;
            $recipientName = $entity->name;
            $itemName = $entity->notification->event ?? 'Event';
            $orderId = $entity->order_id;
        } elseif ($entity instanceof Order) {
            $recipientWhatsapp = $entity->user->whatsapp ?? null;
            $recipientName = $entity->user->name ?? 'Pelanggan';
            $itemName = $entity->product->name ?? 'Produk';
            $orderId = $entity->order_id;
        }

        if (is_null($recipientWhatsapp) || empty($orderId)) {
            Log::warning("WANS: Tidak dapat mengirim detail pembayaran. No WhatsApp atau Order ID hilang.", ['entity_id' => $entity->id]);
            return;
        }

        $adminWhatsappNumbers = config('services.admin.whatsapp', ['082245342997']);
        $adminPhoneNumberForDisplay = implode(' atau ', array_filter(array_map('trim', $adminWhatsappNumbers)));
        if (empty($adminPhoneNumberForDisplay)) {
            $adminPhoneNumberForDisplay = '082245342997';
        }

        $message  = "Halo Kak {$recipientName},\n\n";
        $message .= "Pembayaran Anda untuk *{$itemName}* sebesar *Rp " . number_format($amount, 0, ',', '.') . "* sedang menunggu. \n\n";
        $message .= "*Detail Pembayaran:*\n";

        switch (strtolower($paymentType)) {
            case 'bank_transfer':
            case 'permata':
                $message .= "- Metode: *Transfer Virtual Account " . ($bank ? strtoupper($bank) : '') . "*\n";
                $message .= "- Nomor VA: *{$vaNumber}*\n";
                break;
            case 'echannel':
                $message .= "- Metode: *Mandiri Bill Payment*\n";
                $message .= "- Kode Perusahaan: *{$billerCode}*\n";
                $message .= "- Kode Pembayaran: *{$billKey}*\n";
                break;
            case 'cstore':
                $message .= "- Metode: *Pembayaran Gerai (Indomaret/Alfamart)*\n";
                $message .= "- Kode Pembayaran: *{$paymentCode}*\n";
                $message .= "- Tunjukkan kode ini kepada kasir.\n";
                break;
            case 'qris':
                $message .= "- Metode: *QRIS*\n";
                if (!empty($qrisContent)) {
                    $message .= "- Silakan scan QR Code pada link berikut:\n";
                    $message .= "{$qrisContent}\n";
                } else {
                    $message .= "- Kode QRIS sedang dibuat. Silakan periksa kembali halaman pembayaran Anda untuk melakukan scan.\n";
                }
                break;
            default:
                $message .= "- Metode: *Lainnya ({$paymentType})*\n";
                $message .= "- Silakan selesaikan pembayaran sesuai instruksi yang muncul di halaman pembayaran.\n";
                break;
        }

        if ($expiryTime) {
            $message .= "\n- Batas Waktu Pembayaran: *{$expiryTime->translatedFormat('l, d F Y H:i')} WIB*\n";
        }

        $message .= "\nMohon segera selesaikan pembayaran sebelum batas waktu.\n\n";
        $statusUrl = route('payment.success', ['order_id' => $orderId]);
        $message .= "Untuk memeriksa status, kunjungi: *{$statusUrl}*\n\n";
        $message .= "Jika ada kendala, hubungi kami di {$adminPhoneNumberForDisplay}.\n\nTerima kasih!";

        $this->sendToStarsender(['messageType' => 'text', 'to' => $recipientWhatsapp, 'body' => $message]);
        Log::info("WANS: Mengirim detail pembayaran PENDING ke PESERTA -> {$recipientWhatsapp} untuk OrderId {$entity->order_id}.");
    }

    public function schedulePendingFollowUps(
        $entity,
        string $paymentType,
        float $amount,
        ?string $vaNumber,
        ?string $bank,
        ?string $qrisContent,
        ?string $paymentCode,
        ?string $billerCode,
        ?string $billKey,
        Carbon $expiryTime,
        string $orderId
    ) {
        $recipientWhatsapp = ($entity instanceof Participant) ? $entity->whatsapp : ($entity->user->whatsapp ?? null);
        $itemName = ($entity instanceof Participant) ? ($entity->notification->event ?? 'Event') : ($entity->product->name ?? 'Produk');
        $itemType = ($entity instanceof Participant) ? 'event' : 'produk';

        if (is_null($recipientWhatsapp)) {
            return;
        }

        $paymentDetailsForJob = [
            'payment_type' => $paymentType,
            'va_number' => $vaNumber,
            'bank' => $bank,
            'qris_content' => $qrisContent,
            'payment_code' => $paymentCode,
            'biller_code' => $billerCode,
            'bill_key' => $billKey
        ];

        $transactionCreationTime = $expiryTime->copy()->subHours(config('services.midtrans.va_expiry_hours', 24));

        if ($itemType === 'event') {
            // Ambil tanggal dan waktu event dari entity, gabungkan jadi Carbon instance
            $eventDate = $entity->event_date ?? null; // misal '2025-09-10'
            $eventTime = $entity->event_time ?? null; // misal '14:00:00'

            if ($eventDate && $eventTime) {
                $eventDateTime = Carbon::parse("{$eventDate} {$eventTime}");
            } elseif ($eventDate) {
                $eventDateTime = Carbon::parse($eventDate)->endOfDay();
            } else {
                $eventDateTime = null;
            }

            // Reminder 4 jam setelah transaksi dibuat
            $reminderTime = $transactionCreationTime->copy()->addHours(4);

            // Kirim reminder hanya jika reminderTime sebelum eventDateTime dan masih di masa depan
            if ($reminderTime->isFuture() && (!$eventDateTime || $reminderTime->lessThan($eventDateTime))) {
                dispatch(new SendWhatsAppReminder(
                    $recipientWhatsapp,
                    $itemName,
                    $itemType,
                    $amount,
                    $expiryTime,
                    $orderId,
                    'event_pending_reminder',
                    $paymentDetailsForJob
                ))->delay($reminderTime);

                Log::info("WANS: Menjadwalkan pengingat event transaksi untuk {$orderId} pada {$reminderTime->toDateTimeString()}.");
            }
        } else {
            // Logika produk tetap sama
            $reminder1Time = $transactionCreationTime->copy()->addHours(6);
            if ($reminder1Time->isFuture() && $reminder1Time->lessThan($expiryTime)) {
                dispatch(new SendWhatsAppReminder(
                    $recipientWhatsapp,
                    $itemName,
                    $itemType,
                    $amount,
                    $expiryTime,
                    $orderId,
                    'pending_reminder_1',
                    $paymentDetailsForJob
                ))->delay($reminder1Time);
                Log::info("WANS: Menjadwalkan pengingat produk pertama untuk {$orderId} pada {$reminder1Time->toDateTimeString()}.");
            }

            $reminder2Time = $expiryTime->copy()->subHours(2);
            if ($reminder2Time->isFuture() && $reminder2Time->greaterThan(now()) && $reminder2Time->greaterThan($reminder1Time)) {
                dispatch(new SendWhatsAppReminder(
                    $recipientWhatsapp,
                    $itemName,
                    $itemType,
                    $amount,
                    $expiryTime,
                    $orderId,
                    'pending_reminder_2',
                    $paymentDetailsForJob
                ))->delay($reminder2Time);
                Log::info("WANS: Menjadwalkan pengingat produk kedua untuk {$orderId} pada {$reminder2Time->toDateTimeString()}.");
            }
        }
    }

    public function sendEventQrisExpiredNotification(Participant $participant)
    {
        $eventName = $participant->notification->event ?? 'Event';

        $paymentLink = route('payment.success', ['order_id' => $participant->order_id]);

        $message = "Halo Kak {$participant->name},\n\n" .
            "Pembayaran QRIS Anda untuk event *{$eventName}* telah kedaluwarsa. 😟\n\n" .
            "Jika Anda ingin mencoba melakukan pembayaran lagi, silakan kunjungi kembali link pembayaran Anda di bawah ini:\n" .
            "{$paymentLink}\n\n" .
            "Terima kasih!";

        $this->sendToStarsender(['messageType' => 'text', 'to' => $participant->whatsapp, 'body' => $message]);
        Log::info("WANS: Mengirim notifikasi QRIS expired ke peserta {$participant->id} untuk event '{$eventName}'.");
    }

    public function sendProductFollowUpCancellation($order)
    {
        $userName = $order->user->name ?? 'Pelanggan';
        $productName = $order->product->name ?? 'Produk';
        $adminWhatsappNumbers = config('services.admin.whatsapp', ['082245342997']);
        $adminPhoneNumberForDisplay = implode(' atau ', array_filter(array_map('trim', $adminWhatsappNumbers)));
        if (empty($adminPhoneNumberForDisplay)) {
            $adminPhoneNumberForDisplay = '082245342997';
        }

        if (is_null($order->user->whatsapp)) return;

        $message = "Halo Kak {$userName},\n\n" .
            "Pembayaran Anda untuk produk *{$productName}* tidak berhasil atau dibatalkan. 😔\n\n" .
            "Anda bisa coba pesan/bayar lagi atau hubungi kami di {$adminPhoneNumberForDisplay} jika butuh bantuan.\n\n" .
            "Terima kasih!";

        $this->sendToStarsender(['messageType' => 'text', 'to' => $order->user->whatsapp, 'body' => $message]);
        Log::info("WANS: Mengirim follow-up pembatalan ke user {$userName} untuk produk {$productName}.");
    }

    public function sendAdminNotificationForCancellation(Participant $participant)
    {
        $eventName = $participant->notification->event ?? 'Event Tidak Ditemukan';
        $affiliateName = User::where('username', $participant->affiliate_id)->first()->name ?? 'Tanpa Afiliasi';

        $adminMessage = "❌ *Transaksi Dibatalkan/Gagal* ❌\n\n" .
            "Transaksi untuk acara *{$eventName}* telah dibatalkan atau gagal.\n\n" .
            "Nama Peserta: *{$participant->name}*\n" .
            "No. WhatsApp: *{$participant->whatsapp}*\n" .
            "Order ID: *{$participant->order_id}*\n";

        $adminNumbers = config('services.admin.whatsapp', ['082245342997']);

        foreach ($adminNumbers as $number) {
            if (!empty(trim($number))) {
                $this->sendToStarsender(['messageType' => 'text', 'to' => trim($number), 'body' => $adminMessage]);
            }
        }
        Log::info("WANS: Mengirim notifikasi pembatalan ke admin untuk orderId {$participant->order_id}.");
    }

    public function sendProductPurchaseSuccessNotification(Order $order)
    {
        $user = $order->user;
        $product = $order->product;

        if (!$user || !$product || !$user->whatsapp) {
            return;
        }

        $adminWhatsappNumbers = config('services.admin.whatsapp', ['082245342997']);
        $adminPhoneNumberForDisplay = implode(' atau ', array_filter(array_map('trim', $adminWhatsappNumbers)));
        if (empty($adminPhoneNumberForDisplay)) {
            $adminPhoneNumberForDisplay = '082245342997';
        }

        $message = "✅ *Pembayaran Berhasil*\n\n" .
            "Halo Kak {$user->name},\n\n" .
            "Terima kasih! Pembayaran Anda untuk produk *{$product->name}* telah kami terima.\n\n" .
            "Detail produk atau link download akan segera kami proses.\n\n" .
            "Jika ada pertanyaan, hubungi kami di {$adminPhoneNumberForDisplay}.\n\n" .
            "Terima kasih!";

        $this->sendToStarsender(['messageType' => 'text', 'to' => $user->whatsapp, 'body' => $message]);
        Log::info("WANS: Mengirim notifikasi pembelian produk berhasil ke user {$user->id}.");
    }

    public function sendAdminNotificationForProductOrder(Order $order)
    {
        $user = $order->user;
        $product = $order->product;

        if (!$user || !$product) {
            return;
        }

        $adminMessage = "📦 *Ada Pesanan Produk Baru!*\n\n" .
            "Produk: *{$product->name}*\n" .
            "Harga: *Rp " . number_format($order->amount, 0, ',', '.') . "*\n\n" .
            "Dipesan oleh:\n" .
            "- Nama: *{$user->name}*\n" .
            "- Email: *{$user->email}*\n" .
            "- No. WA: *{$user->whatsapp}*\n\n" .
            "Mohon segera diproses.";

        $adminNumbers = config('services.admin.whatsapp', ['082245342997']);

        foreach ($adminNumbers as $number) {
            if (!empty(trim($number))) {
                $this->sendToStarsender(['messageType' => 'text', 'to' => trim($number), 'body' => $adminMessage]);
            }
        }
        Log::info("WANS: Mengirim notifikasi pesanan produk baru ke admin untuk order {$order->id}.");
    }

    public function sendToStarsender(array $messageData)
    {
        $curl = curl_init();
        $apiKey = config('services.starsender.api_key');
        $apiUrl = config('services.starsender.url');

        if (empty($apiKey) || empty($apiUrl)) {
            Log::error('WANS: Starsender URL atau API Key tidak dikonfigurasi.');
            return;
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($messageData),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: ' . $apiKey,],
        ]);

        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            Log::error('WANS: Starsender CURL Error: ' . curl_error($curl));
        } else {
            Log::info("WANS: Starsender API response: " . $response);
        }
        curl_close($curl);
    }
}
