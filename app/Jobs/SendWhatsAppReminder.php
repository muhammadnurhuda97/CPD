<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\WhatsAppNotificationService; // PENTING: Import Service
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\Transaction; // PENTING: Import model Transaction

class SendWhatsAppReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $whatsappNumber;
    protected $itemName;
    protected $itemType;
    protected $amount;
    protected $expiryTime;
    protected $orderId;
    protected $notificationType; // 'pending_reminder_1', 'pending_reminder_2', 'expired', 'cancel_follow_up', etc.
    protected $paymentDetails; // Detail pembayaran untuk pesan

    /**
     * Create a new job instance.
     *
     * @param string $whatsappNumber
     * @param string $itemName
     * @param string $itemType
     * @param float $amount
     * @param Carbon|null $expiryTime
     * @param string $orderId
     * @param string $notificationType
     * @param array $paymentDetails
     */
    public function __construct(string $whatsappNumber, string $itemName, string $itemType, float $amount, ?Carbon $expiryTime, string $orderId, string $notificationType, array $paymentDetails = [])
    {
        $this->whatsappNumber = $whatsappNumber;
        $this->itemName = $itemName;
        $this->itemType = $itemType;
        $this->amount = $amount;
        $this->expiryTime = $expiryTime;
        $this->orderId = $orderId;
        $this->notificationType = $notificationType;
        $this->paymentDetails = $paymentDetails;
    }

    /**
     * Execute the job.
     *
     * @param WhatsAppNotificationService $waService
     * @return void
     */
    public function handle(WhatsAppNotificationService $waService)
    {
        Log::info("Executing SendWhatsAppReminder Job for orderId: {$this->orderId}, type: {$this->notificationType}");

        // PENTING: Cek status transaksi saat ini sebelum mengirim pengingat.
        // Jika sudah lunas ('paid') atau sudah gagal secara definitif ('failed'), jangan kirim pengingat.
        $transaction = Transaction::where('order_id', $this->orderId)->first();
        if ($transaction && ($transaction->status === 'paid' || $transaction->status === 'failed')) {
            Log::info("Skipping reminder for {$this->orderId} as status is no longer pending ({$transaction->status}).");
            return; // Jangan kirim pengingat jika sudah bayar/gagal final
        }

        // Format jumlah uang
        $formattedAmount = "Rp " . number_format($this->amount, 0, ',', '.');

        // Format waktu kadaluarsa jika tersedia
        $expiryTimeFormatted = $this->expiryTime ? $this->expiryTime->translatedFormat('l, d F Y H.i') . ' WIB' : 'Waktu tidak tersedia';

        // Menyusun detail pembayaran
        $paymentType = $this->paymentDetails['payment_type'] ?? 'transfer';
        $vaNumber = $this->paymentDetails['va_number'] ?? '-';
        $bank = $this->paymentDetails['bank'] ?? '-';
        $qrisContent = $this->paymentDetails['qris_content'] ?? null;

        $paymentDetailsMessage = "";
        if ($paymentType === 'va_number' || $paymentType === 'bank_transfer') {
            $paymentDetailsMessage = "Metode: *Transfer Virtual Account {$bank}*\nNomor VA: *{$vaNumber}*\n";
        } elseif ($paymentType === 'qris') {
            $paymentDetailsMessage = "Metode: *QRIS*\n";
            $paymentDetailsMessage .= "Silakan scan QRIS ini: " . ($qrisContent ? $qrisContent . "\n" : "[INSTRUKSI SCAN DI APLIKASI E-WALLET ANDA]\n");
        }

        // Menentukan jenis pesan berdasarkan tipe notifikasi
        switch ($this->notificationType) {
            case 'pending_reminder_1':
                $message = "🔔 Halo, ini pengingat pertama! Pembayaran Anda untuk *{$this->itemName}* ({$this->itemType}) sebesar *{$formattedAmount}* masih menunggu penyelesaian. \n\n{$paymentDetailsMessage}Batas waktu: *{$expiryTimeFormatted}*\n\nSegera selesaikan pembayaran untuk mengamankan pesanan/pendaftaran Anda. Terima kasih!";
                break;
            case 'pending_reminder_2':
                $message = "🚨 Peringatan terakhir! Batas waktu pembayaran Anda untuk *{$this->itemName}* ({$this->itemType}) sebesar *{$formattedAmount}* akan segera berakhir. \n\n{$paymentDetailsMessage}Batas waktu: *{$expiryTimeFormatted}*\n\nSegera selesaikan sebelum kedaluwarsa! Jangan sampai kesempatan ini terlewat.";
                break;
            case 'expired':
                $message = "❌ Pemberitahuan: Batas waktu pembayaran untuk *{$this->itemName}* ({$this->itemType}) sebesar *{$formattedAmount}* telah kadaluarsa. \n\nMohon maaf, pesanan/pendaftaran Anda dibatalkan. Jika Anda masih ingin melanjutkan, silakan lakukan pemesanan/pendaftaran ulang.";
                break;
            case 'cancel_follow_up':
                $message = "Halo, kami melihat Anda membatalkan pembayaran untuk *{$this->itemName}* ({$this->itemType}) sebesar *{$formattedAmount}*. Apakah ada kendala? Kami siap membantu. Silakan coba lagi jika Anda berubah pikiran.";
                break;
                // Tambahkan case lain jika ada notifikasi follow-up untuk status 'deny' atau yang lain
        }

        // Mengirim pesan jika ada
        if (!empty($message)) {
            $waService->sendToStarsender(['messageType' => 'text', 'to' => $this->whatsappNumber, 'body' => $message]);
        }
    }
}
