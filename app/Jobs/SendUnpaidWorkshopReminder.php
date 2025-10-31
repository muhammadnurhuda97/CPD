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
            Log::warning("JobReminder: Peserta ID {$this->participantId} tidak ditemukan (SendUnpaidWorkshopReminder).");
            return;
        }

        // Pengecekan final tepat sebelum mengirim
        // Perbaiki pengecekan status: jangan kirim jika sudah lunas ATAU jika metodenya bukan workshop
        if ($participant->is_paid || $participant->payment_status === 'paid' || $participant->event_type !== 'workshop') {
            Log::info("JobReminder: Peserta ID {$this->participantId} sudah lunas atau bukan workshop. Tidak mengirim reminder unpaid.");
            return;
        }

        // --- PERBAIKAN PEMANGGILAN FUNGSI ---
        // $waService->sendUnpaidWorkshopReminderMessage($participant); // <-- SALAH (jika ini dari kode Anda)
        $waService->sendUnpaidReminder($participant); // <-- BENAR
        // ------------------------------------

        Log::info("JobReminder: Proses pengiriman reminder unpaid workshop untuk Peserta ID {$this->participantId} selesai.");
    }
}
