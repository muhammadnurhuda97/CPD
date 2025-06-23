<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Participant;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Jobs\SendWhatsAppReminder;

class WhatsAppNotificationService
{
    /**
     * Mengirim notifikasi pendaftaran event (gratis atau setelah lunas) ke peserta.
     */
    public function sendToParticipant(Participant $participant, $affiliateId)
    {
        $eventType = $participant->event_type;
        $notification = Notification::where('event_type', $eventType)->latest()->first();
        if (!$notification) {
            Log::warning("WANS: Notification data not found for event_type '{$eventType}'.");
            return;
        }

        $eventName = $notification->event;
        $eventDateWIB = Carbon::parse($notification->event_date . ' ' . $notification->event_time, 'Asia/Jakarta');
        $eventDateWIBFormatted = $eventDateWIB->translatedFormat('l, d F Y');
        $eventTimeWIB = $eventDateWIB->format('H.i') . ' WIB';

        $locationDetailsWithLink = '';
        $locationDetailsWithoutLink = '';
        if ($eventType === 'webinar') {
            $locationDetailsWithLink = "- 🖥️ Lokasi: *Online via Zoom*\n- Link Acara: *{$notification->zoom}*\n\n";
            $locationDetailsWithoutLink = "- 🖥️ Lokasi: *Online via Zoom*\n\n";
        } elseif ($eventType === 'workshop') {
            $locationDetailsWithLink = "- 📍 Lokasi: *{$notification->location_name}*\n" .
                "- Alamat: *{$notification->location_address}*\n" .
                "- Link Gmaps: *{$notification->location}*\n\n";
            $locationDetailsWithoutLink = "- 📍 Lokasi: *{$notification->location_name}*\n" .
                "- Alamat: *{$notification->location_address}*\n\n";
        }

        $affiliateName = 'Tim Support';
        // Mengambil nomor admin dari konfigurasi yang sudah di-parse ke array
        $adminWhatsappNumbers = config('services.admin.whatsapp', ['082245342997']);
        $supportContact = $adminWhatsappNumbers[0] ?? '082245342997'; // Gunakan nomor pertama sebagai default support contact

        if ($affiliateId && strtolower($affiliateId) !== 'admin') {
            $affiliate = User::where('username', $affiliateId)->first();
            if ($affiliate) {
                $affiliateName = $affiliate->name;
                $supportContact = $affiliate->whatsapp ?? $supportContact;
            }
        } else {
            $affiliateName = 'Admin';
        }

        $messageTemplate = function ($title, $extraText = '', $showLink = false) use (
            $participant,
            $eventDateWIBFormatted,
            $eventTimeWIB,
            $locationDetailsWithLink,
            $locationDetailsWithoutLink,
            $affiliateName,
            $supportContact
        ) {
            $locationText = $showLink ? $locationDetailsWithLink : $locationDetailsWithoutLink;

            $defaultExtraText = "Pastikan Anda bergabung tepat waktu agar tidak ketinggalan informasi penting.";
            $finalExtraText = !empty($extraText) ? $extraText : $defaultExtraText;

            $includesSupport = stripos($finalExtraText, 'Kontak Support') !== false;
            $includesNote = stripos($finalExtraText, 'Note:') !== false;

            $message = "Halo Kak {$participant->name}, {$title}\n\n" .
                "- 📅 Hari, Tanggal: *{$eventDateWIBFormatted}*\n" .
                "- ⏰ Jam: *{$eventTimeWIB}*\n" .
                $locationText .
                $finalExtraText . "\n\n";

            if (!$includesSupport) {
                $message .= "📞 Kontak Support: *{$affiliateName}* - *{$supportContact}*\n\n";
            }

            if (!$includesNote) {
                $message .= "Note: *Jika tautan tidak berfungsi, balas pesan ini.*";
            }

            return $message;
        };

        $eventDateUTC = $eventDateWIB->copy()->timezone('UTC');
        $timestampUTC = $eventDateUTC->timestamp * 1000;
        $reminder1DayBeforeTimestamp = $eventDateWIB->copy()->subDay()->setTime(18, 0)->timezone('UTC')->timestamp * 1000;
        $followUpAfterEventTimestamp = $eventDateWIB->copy()->setTime(21, 30)->timezone('UTC')->timestamp * 1000;

        $schedules = [
            '1_day_before' => $reminder1DayBeforeTimestamp,
            '30_min_before' => $timestampUTC - (30 * 60 * 1000),
            'post_event_fixed_time' => $followUpAfterEventTimestamp,
        ];

        $messages = [
            [
                'body' => $messageTemplate(
                    'selamat! 🎉 Anda berhasil mendaftar di acara *' . $eventName . '*'
                )
            ],
            [
                'body' => $messageTemplate(
                    '😊 Pengingat acara *' . $eventName . '* besok!',
                    'Sampai jumpa besok ya!'
                ),
                'schedule' => $schedules['1_day_before']
            ],
            [
                'body' => $messageTemplate(
                    '🚀 Acara *' . $eventName . '* dimulai dalam 30 menit!',
                    '',
                    true
                ),
                'schedule' => $schedules['30_min_before']
            ],
            [
                'body' => $messageTemplate(
                    '🎉 Acara *' . $eventName . '* telah selesai! Kami berharap Anda mendapat manfaat!',
                    'Terima kasih telah hadir!'
                ),
                'schedule' => $schedules['post_event_fixed_time']
            ],
        ];

        foreach ($messages as $msg) {
            $payload = [
                'messageType' => 'text',
                'to' => $participant->whatsapp,
                'body' => $msg['body'],
            ];
            if (isset($msg['schedule'])) {
                $payload['schedule'] = $msg['schedule'];
            }
            $this->sendToStarsender($payload);
        }
    }

    public function sendAdminNotification($participant, $eventType)
    {
        $eventName = Notification::where('event_type', $eventType)->latest()->first()->event ?? 'Event Tidak Ditemukan';
        $affiliateName = User::where('username', $participant->affiliate_id)->first()->name ?? 'Affiliate Tidak Ditemukan';

        $adminMessage = "Peserta baru telah mendaftar acara *{$eventName}*!\n\n" .
            "Nama: *{$participant->name}*\n" .
            "No. WhatsApp: *{$participant->whatsapp}*\n" .
            "Pengundang: *{$affiliateName}*\n\n" .
            "Segera verifikasi dan tindak lanjuti pendaftaran ini.";

        // Mengambil nomor admin dari konfigurasi yang sudah di-parse ke array
        $adminNumbers = config('services.admin.whatsapp', ['082245342997']);

        // Loop untuk setiap nomor admin dan kirim pesan terpisah
        foreach ($adminNumbers as $number) {
            if (!empty(trim($number))) {
                $this->sendToStarsender(['messageType' => 'text', 'to' => trim($number), 'body' => $adminMessage]);
            }
        }
    }

    public function sendAffiliateNotification($affiliateId, $participant, $eventType)
    {
        $eventName = Notification::where('event_type', $eventType)->latest()->first()->event ?? 'Event Tidak Ditemukan';
        $affiliate = User::where('username', $affiliateId)->first();

        if ($affiliate && $affiliate->whatsapp) {
            $affiliateMessage = "🎉 Wah, Selamat *{$affiliate->name}*👋\n\n" .
                "Anda telah berhasil mengundang *{$participant->name}* ke acara *{$eventName}*.\n" .
                "Whatsapp: *{$participant->whatsapp}*\n" .
                "E-mail: *{$participant->email}*\n\n" .
                "Jangan lupa follow up ya! Siapa tahu jadi rejeki kamu! 🏆";

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
            $itemName = ucfirst($entity->event_type) . ' Event';
            $orderId = $entity->order_id;
        } elseif ($entity instanceof Order) {
            $recipientWhatsapp = $entity->user->whatsapp ?? null;
            $recipientName = $entity->user->name ?? 'Pelanggan';
            $itemName = $entity->product->name ?? 'Produk';
            $orderId = $entity->order_id;
        }

        if (is_null($recipientWhatsapp) || empty($orderId)) {
            Log::warning("WANS: Cannot send pending payment details. Recipient WhatsApp or Order ID is missing.", ['entity_id' => $entity->id]);
            return;
        }

        // Mengambil nomor admin dari konfigurasi yang sudah di-parse ke array
        $adminWhatsappNumbers = config('services.admin.whatsapp', ['082245342997']);
        // Menggabungkan untuk tampilan pesan (format "nomor1 atau nomor2")
        $adminPhoneNumberForDisplay = implode(' atau ', array_filter(array_map('trim', $adminWhatsappNumbers)));
        if (empty($adminPhoneNumberForDisplay)) {
            $adminPhoneNumberForDisplay = '082245342997'; // Fallback jika array kosong
        }

        $message  = "Halo {$recipientName},\n\n";
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
        Log::info("WANS: Sent initial pending payment details to {$recipientName} for orderId {$orderId}.");
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
        $itemName = ($entity instanceof Participant) ? (ucfirst($entity->event_type) . ' Event') : ($entity->product->name ?? 'Produk');
        $itemType = ($entity instanceof Participant) ? 'event' : 'produk';

        if (is_null($recipientWhatsapp)) return;

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
        $reminder1Time = $transactionCreationTime->copy()->addHours(6);
        if ($reminder1Time->isFuture() && $reminder1Time->lessThan($expiryTime)) {
            dispatch(new SendWhatsAppReminder($recipientWhatsapp, $itemName, $itemType, $amount, $expiryTime, $orderId, 'pending_reminder_1', $paymentDetailsForJob))->delay($reminder1Time);
            Log::info("WANS: Scheduled first pending reminder for {$orderId} at {$reminder1Time->toDateTimeString()}.");
        }

        $reminder2Time = $expiryTime->copy()->subHours(2);
        if ($reminder2Time->isFuture() && $reminder2Time->greaterThan(now()) && $reminder2Time->greaterThan($reminder1Time)) {
            dispatch(new SendWhatsAppReminder($recipientWhatsapp, $itemName, $itemType, $amount, $expiryTime, $orderId, 'pending_reminder_2', $paymentDetailsForJob))->delay($reminder2Time);
            Log::info("WANS: Scheduled second pending reminder for {$orderId} at {$reminder2Time->toDateTimeString()}.");
        }
    }

    public function sendEventFollowUpCancellation(Participant $participant)
    {
        $eventName = Notification::where('event_type', $participant->event_type)->latest()->first()->event ?? 'Event Tidak Ditemukan';
        // Mengambil nomor admin dari konfigurasi yang sudah di-parse ke array
        $adminWhatsappNumbers = config('services.admin.whatsapp', ['082245342997']);
        // Menggabungkan untuk tampilan pesan (format "nomor1 atau nomor2")
        $adminPhoneNumberForDisplay = implode(' atau ', array_filter(array_map('trim', $adminWhatsappNumbers)));
        if (empty($adminPhoneNumberForDisplay)) {
            $adminPhoneNumberForDisplay = '082245342997'; // Fallback jika array kosong
        }

        $message = "Halo {$participant->name},\n\n" .
            "Kami melihat pembayaran Anda untuk pendaftaran acara *{$eventName}* tidak berhasil atau dibatalkan. 😟\n\n" .
            "Anda bisa coba daftar/bayar lagi atau hubungi kami di {$adminPhoneNumberForDisplay} untuk bantuan.\n\n" .
            "Terima kasih!";

        $this->sendToStarsender(['messageType' => 'text', 'to' => $participant->whatsapp, 'body' => $message]);
        Log::info("WANS: Sent cancellation follow-up to participant {$participant->id} for event.");
    }

    public function sendProductFollowUpCancellation($order)
    {
        $userName = $order->user->name ?? 'Pelanggan';
        $productName = $order->product->name ?? 'Produk';
        // Mengambil nomor admin dari konfigurasi yang sudah di-parse ke array
        $adminWhatsappNumbers = config('services.admin.whatsapp', ['082245342997']);
        // Menggabungkan untuk tampilan pesan (format "nomor1 atau nomor2")
        $adminPhoneNumberForDisplay = implode(' atau ', array_filter(array_map('trim', $adminWhatsappNumbers)));
        if (empty($adminPhoneNumberForDisplay)) {
            $adminPhoneNumberForDisplay = '082245342997'; // Fallback jika array kosong
        }

        if (is_null($order->user->whatsapp)) return;

        $message = "Halo {$userName},\n\n" .
            "Pembayaran Anda untuk produk *{$productName}* tidak berhasil atau dibatalkan. 😔\n\n" .
            "Anda bisa coba pesan/bayar lagi atau hubungi kami di {$adminPhoneNumberForDisplay} jika butuh bantuan.\n\n" .
            "Terima kasih!";

        $this->sendToStarsender(['messageType' => 'text', 'to' => $order->user->whatsapp, 'body' => $message]);
        Log::info("WANS: Sent cancellation follow-up to user {$userName} for product {$productName}.");
    }

    public function sendAdminNotificationForCancellation($participant)
    {
        $eventName = Notification::where('event_type', $participant->event_type)->latest()->first()->event ?? 'Event Tidak Ditemukan';
        $affiliateName = User::where('username', $participant->affiliate_id)->first()->name ?? 'Tanpa Afiliasi';

        $adminMessage = "❌ *Transaksi Dibatalkan/Gagal* ❌\n\n" .
            "Transaksi untuk acara *{$eventName}* telah dibatalkan atau gagal.\n\n" .
            "Nama Peserta: *{$participant->name}*\n" .
            "No. WhatsApp: *{$participant->whatsapp}*\n" .
            "Order ID: *{$participant->order_id}*\n" .
            "Pengundang: *{$affiliateName}*";

        // Mengambil nomor admin dari konfigurasi yang sudah di-parse ke array
        $adminNumbers = config('services.admin.whatsapp', ['082245342997']);

        // Loop untuk setiap nomor admin dan kirim pesan terpisah
        foreach ($adminNumbers as $number) {
            if (!empty(trim($number))) {
                $this->sendToStarsender(['messageType' => 'text', 'to' => trim($number), 'body' => $adminMessage]);
            }
        }
        Log::info("WANS: Sent cancellation admin notification for orderId {$participant->order_id}.");
    }

    public function sendProductPurchaseSuccessNotification(Order $order)
    {
        $user = $order->user;
        $product = $order->product;

        if (!$user || !$product || !$user->whatsapp) {
            return;
        }

        // Mengambil nomor admin dari konfigurasi yang sudah di-parse ke array
        $adminWhatsappNumbers = config('services.admin.whatsapp', ['082245342997']);
        // Menggabungkan untuk tampilan pesan (format "nomor1 atau nomor2")
        $adminPhoneNumberForDisplay = implode(' atau ', array_filter(array_map('trim', $adminWhatsappNumbers)));
        if (empty($adminPhoneNumberForDisplay)) {
            $adminPhoneNumberForDisplay = '082245342997'; // Fallback jika array kosong
        }

        $message = "✅ *Pembayaran Berhasil*\n\n" .
            "Halo {$user->name},\n\n" .
            "Terima kasih! Pembayaran Anda untuk produk *{$product->name}* telah kami terima.\n\n" .
            "Detail produk atau link download akan segera kami proses.\n\n" .
            "Jika ada pertanyaan, hubungi kami di {$adminPhoneNumberForDisplay}.\n\n" .
            "Terima kasih!";

        $this->sendToStarsender(['messageType' => 'text', 'to' => $user->whatsapp, 'body' => $message]);
        Log::info("WANS: Mengirim notifikasi pembelian produk berhasil ke user {$user->id}.");
    }

    /**
     * Mengirim notifikasi ke admin jika ada pembelian produk baru.
     */
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

        // Mengambil nomor admin dari konfigurasi yang sudah di-parse ke array
        $adminNumbers = config('services.admin.whatsapp', ['082245342997']);

        // Loop untuk setiap nomor admin dan kirim pesan terpisah
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
            Log::error('WANS: Starsender URL or API Key is not configured. Please check config/services.php and your .env file.');
            return;
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($messageData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: ' . $apiKey,
            ],
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