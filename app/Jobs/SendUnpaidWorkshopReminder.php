<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Participant;
use App\Services\WhatsAppNotificationService;
use Illuminate\Support\Facades\Log;

class SendUnpaidWorkshopReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $participantId;

    public function __construct(int $participantId)
    {
        $this->participantId = $participantId;
    }

    public function handle(WhatsAppNotificationService $waService)
    {
        $participant = Participant::find($this->participantId);

        if (!$participant) {
            Log::warning("JobReminder: Peserta ID {$this->participantId} tidak ditemukan.");
            return;
        }

        // Pengecekan final tepat sebelum mengirim
        if ($participant->payment_status === 'paid') {
            Log::info("JobReminder: Peserta ID {$this->participantId} sudah lunas. Tidak mengirim reminder tunai.");
            return;
        }

        // PERBAIKAN: Memastikan Job memanggil fungsi yang benar untuk MENGIRIM PESAN.
        $waService->sendUnpaidWorkshopReminderMessage($participant);

        Log::info("JobReminder: Proses pengiriman reminder pembayaran tunai H-2 Jam untuk Peserta ID {$this->participantId} selesai.");
    }
}
