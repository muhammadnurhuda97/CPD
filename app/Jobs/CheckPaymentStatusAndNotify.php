<?php

namespace App\Jobs;

use App\Models\Participant;
use App\Services\WhatsAppNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckPaymentStatusAndNotify implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $participantId;

    public function __construct(int $participantId)
    {
        $this->participantId = $participantId;
    }

    public function handle(WhatsAppNotificationService $waService)
    {
        Log::info("Job: Memeriksa status pembayaran untuk peserta ID: {$this->participantId}");
        $participant = Participant::find($this->participantId);

        if (!$participant) {
            Log::warning("Job: Peserta ID {$this->participantId} tidak ditemukan.");
            return;
        }

        if (in_array($participant->payment_status, ['unpaid', 'pending'])) {
            if ($participant->event_type === 'workshop' && $participant->notification->is_paid) {
                // Menjadwalkan reminder H-2 Jam (via Job)
                $waService->sendUnpaidWorkshopReminder($participant);
                Log::info("Job: Reminder workshop H-2 jam telah dijadwalkan untuk peserta ID {$this->participantId}.");
            }
        } else {
            Log::info("Job: Status peserta ID {$this->participantId} adalah '{$participant->payment_status}'. Tidak perlu kirim notifikasi unpaid/pending.");
        }
    }
}
