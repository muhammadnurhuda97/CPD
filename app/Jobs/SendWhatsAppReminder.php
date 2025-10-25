<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\WhatsAppNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\Transaction;

class SendWhatsAppReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $whatsappNumber;
    protected $itemName;
    protected $itemType;
    protected $amount;
    protected $expiryTime;
    protected $orderId;
    protected $notificationType;
    protected $paymentDetails;

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

    public function handle(WhatsAppNotificationService $waService)
    {
        Log::info("Menjalankan Job SendWhatsAppReminder untuk orderId: {$this->orderId}, tipe: {$this->notificationType}");

        $transaction = Transaction::where('order_id', $this->orderId)->first();
        if ($transaction && ($transaction->status === 'paid' || $transaction->status === 'failed')) {
            Log::info("Melewatkan pengingat untuk {$this->orderId} karena status bukan lagi pending ({$transaction->status}).");
            return;
        }
        $formattedAmount = "Rp " . number_format($this->amount, 0, ',', '.');
        $expiryTimeFormatted = $this->expiryTime ? $this->expiryTime->translatedFormat('l, d F Y H:i') . ' WIB' : 'Waktu tidak tersedia';

        $paymentType = $this->paymentDetails['payment_type'] ?? 'transfer';
        $vaNumber = $this->paymentDetails['va_number'] ?? '-';
        $bank = $this->paymentDetails['bank'] ?? '-';
        $qrisContent = $this->paymentDetails['qris_content'] ?? null;
        $billerCode = $this->paymentDetails['biller_code'] ?? null;
        $billKey = $this->paymentDetails['bill_key'] ?? null;
        $paymentCode = $this->paymentDetails['payment_code'] ?? null;

        $paymentDetailsMessage = "";
        if (in_array($paymentType, ['bank_transfer', 'permata'])) {
            $paymentDetailsMessage = "Metode: *Transfer Virtual Account {$bank}*\nNomor VA: *{$vaNumber}*\n";
        } elseif ($paymentType === 'echannel') {
            $paymentDetailsMessage = "Metode: *Mandiri Bill Payment*\nKode Perusahaan: *{$billerCode}*\nKode Pembayaran: *{$billKey}*\n";
        } elseif ($paymentType === 'cstore') {
            $paymentDetailsMessage = "Metode: *Pembayaran Gerai*\nKode Pembayaran: *{$paymentCode}*\n";
        } elseif ($paymentType === 'qris') {
            $paymentDetailsMessage = "Metode: *QRIS*\n";
            $paymentDetailsMessage .= "Silakan scan QRIS pada halaman pembayaran Anda.\n";
        }

        $message = null;
        switch ($this->notificationType) {
            case 'pending_reminder_1':
                $message = "🔔 Halo, ini pengingat pertama! Pembayaran Anda untuk produk *{$this->itemName}* sebesar *{$formattedAmount}* masih menunggu penyelesaian. \n\n{$paymentDetailsMessage}Batas waktu: *{$expiryTimeFormatted}*\n\nSegera selesaikan pembayaran untuk mengamankan pesanan Anda. Terima kasih!";
                break;
            case 'pending_reminder_2':
                $message = "🚨 Peringatan terakhir! Batas waktu pembayaran Anda untuk produk *{$this->itemName}* sebesar *{$formattedAmount}* akan segera berakhir. \n\n{$paymentDetailsMessage}Batas waktu: *{$expiryTimeFormatted}*\n\nSegera selesaikan sebelum kedaluwarsa!";
                break;
            case 'expired':
                $message = "❌ Pemberitahuan: Batas waktu pembayaran untuk *{$this->itemName}* ({$this->itemType}) sebesar *{$formattedAmount}* telah kadaluarsa. \n\nMohon maaf, pesanan/pendaftaran Anda dibatalkan. Jika Anda masih ingin melanjutkan, silakan lakukan pemesanan/pendaftaran ulang.";
                break;
            case 'cancel_follow_up':
                $message = "Halo, kami melihat Anda membatalkan pembayaran untuk *{$this->itemName}* ({$this->itemType}) sebesar *{$formattedAmount}*. Apakah ada kendala? Kami siap membantu. Silakan coba lagi jika Anda berubah pikiran.";
                break;
        }

        if (!empty($message)) {
            $waService->sendToStarsender(['messageType' => 'text', 'to' => $this->whatsappNumber, 'body' => $message]);
        }
    }
}
