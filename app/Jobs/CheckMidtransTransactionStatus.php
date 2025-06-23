<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Midtrans\Config;
use Midtrans\Transaction as MidtransSdkTransaction;
use App\Models\Order;
use App\Models\Participant;
use App\Services\TransactionHandlerService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

class CheckMidtransTransactionStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3; //
    public $backoff = [60, 300, 900]; // Retry: 1m, 5m, 15m //

    public function handle(TransactionHandlerService $transactionHandlerService) //
    {
        Log::info('CheckMidtransTransactionStatus Job: Running...');

        Config::$serverKey = config('midtrans.server_key'); //
        Config::$isProduction = filter_var(config('midtrans.is_production'), FILTER_VALIDATE_BOOLEAN); //
        Config::$isSanitized = filter_var(config('midtrans.is_sanitized'), FILTER_VALIDATE_BOOLEAN); //
        Config::$is3ds = filter_var(config('midtrans.is_3ds'), FILTER_VALIDATE_BOOLEAN); //

        try {
            $pendingOrders = Order::where('status', 'pending')->get(); //
            if ($pendingOrders->isEmpty()) { //
                Log::info('CheckMidtransTransactionStatus Job: No pending product orders to check.');
            } else {
                Log::info('CheckMidtransTransactionStatus Job: Checking ' . $pendingOrders->count() . ' pending product orders.');
                foreach ($pendingOrders as $order) { //
                    $this->processEntityStatus($order, 'order', $transactionHandlerService);
                }
            }

            $pendingParticipants = Participant::where('payment_status', 'pending')->get(); //
            if ($pendingParticipants->isEmpty()) { //
                Log::info('CheckMidtransTransactionStatus Job: No pending event participants to check.');
            } else {
                Log::info('CheckMidtransTransactionStatus Job: Checking ' . $pendingParticipants->count() . ' pending event participants.');
                foreach ($pendingParticipants as $participant) { //
                    $this->processEntityStatus($participant, 'participant', $transactionHandlerService);
                }
            }
        } catch (\Exception $e) {
            Log::error('CheckMidtransTransactionStatus Job: Unhandled error in handle() method.', ['error' => $e->getMessage()]); //
        }

        Log::info('CheckMidtransTransactionStatus Job: Finished.');
        DB::disconnect(); // Penting agar koneksi DB tidak numpuk //
    }

    // ===== KODE YANG DIPERBAIKI ADA DI DALAM METODE INI =====
    private function processEntityStatus($entity, string $entityType, TransactionHandlerService $transactionHandlerService) //
    {
        $orderId = $entity->order_id; //

        if (empty($orderId)) { //
            Log::warning("CheckMidtransTransactionStatus Job: Entity (ID: {$entity->id}, Type: {$entityType}) has empty order_id. Skipping.");
            return;
        }

        Log::info("CheckMidtransTransactionStatus Job: [Attempt {$this->attempts()}] Checking Midtrans status for {$entityType} - orderId: {$orderId}");

        try {
            $midtransStatusResponse = MidtransSdkTransaction::status($orderId); //

            Log::info("CheckMidtransTransactionStatus Job: Midtrans API response for orderId '{$orderId}'", (array) $midtransStatusResponse);

            $midtransTransactionStatus = $midtransStatusResponse->transaction_status ?? null; //
            $midtransGrossAmount = $midtransStatusResponse->gross_amount ?? null; //

            if ($midtransTransactionStatus && $midtransGrossAmount !== null) { //
                $transactionHandlerService->handleTransactionStatus(
                    $orderId,
                    $midtransTransactionStatus,
                    (float) $midtransGrossAmount
                );
            } else {
                Log::warning("CheckMidtransTransactionStatus Job: Incomplete Midtrans response for orderId '{$orderId}'.", ['response' => (array) $midtransStatusResponse]);
            }
        } catch (\Exception $e) {
            // ** AWAL PERUBAHAN **
            // Cek jika pesan error mengandung '404', yang menandakan transaksi belum dibuat di Midtrans.
            // Ini adalah kondisi normal jika pengguna belum memilih metode bayar.
            if (strpos($e->getMessage(), '404') !== false) {
                Log::warning("CheckMidtransTransactionStatus Job: Midtrans returned 404 for orderId '{$orderId}'. Transaction not yet initiated by user. The job will retry based on its schedule.", [
                    'message' => $e->getMessage()
                ]);
                // Kita return di sini agar job tidak ditandai sebagai 'failed'.
                // Worker akan mencobanya lagi nanti sesuai properti $backoff.
                return;
            }
            // ** AKHIR PERUBAHAN **

            // Untuk error lainnya, catat sebagai error kritis dan lempar kembali agar job ditandai 'failed'.
            Log::error("CheckMidtransTransactionStatus Job: Unhandled Exception for orderId '{$orderId}'.", ['message' => $e->getMessage()]);
            throw $e;
        }
    }
}