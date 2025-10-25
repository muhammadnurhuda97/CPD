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

    public $tries = 3;
    public $backoff = [60, 300, 900];

    public function handle(TransactionHandlerService $transactionHandlerService)
    {
        Log::info('Job CheckMidtransTransactionStatus: Berjalan...');

        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = filter_var(config('midtrans.is_production'), FILTER_VALIDATE_BOOLEAN);
        Config::$isSanitized = filter_var(config('midtrans.is_sanitized'), FILTER_VALIDATE_BOOLEAN);
        Config::$is3ds = filter_var(config('midtrans.is_3ds'), FILTER_VALIDATE_BOOLEAN);

        try {
            $pendingOrders = Order::where('status', 'pending')->get();
            if ($pendingOrders->isEmpty()) {
                Log::info('Job CheckMidtransTransactionStatus: Tidak ada pesanan produk pending untuk dicek.');
            } else {
                Log::info('Job CheckMidtransTransactionStatus: Memeriksa ' . $pendingOrders->count() . ' pesanan produk pending.');
                foreach ($pendingOrders as $order) {
                    $this->processEntityStatus($order, 'order', $transactionHandlerService);
                }
            }

            $pendingParticipants = Participant::with('notification')->where('payment_status', 'pending')->get();
            if ($pendingParticipants->isEmpty()) {
                Log::info('Job CheckMidtransTransactionStatus: Tidak ada peserta event pending untuk dicek.');
            } else {
                Log::info('Job CheckMidtransTransactionStatus: Memeriksa ' . $pendingParticipants->count() . ' peserta event pending.');
                foreach ($pendingParticipants as $participant) {
                    $this->processEntityStatus($participant, 'participant', $transactionHandlerService);
                }
            }
        } catch (\Exception $e) {
            Log::error('Job CheckMidtransTransactionStatus: Error tidak terduga di metode handle().', ['error' => $e->getMessage()]);
        }

        Log::info('Job CheckMidtransTransactionStatus: Selesai.');
        DB::disconnect();
    }

    private function processEntityStatus($entity, string $entityType, TransactionHandlerService $transactionHandlerService)
    {
        $orderId = $entity->order_id;

        if (empty($orderId)) {
            Log::warning("Job CheckMidtransTransactionStatus: Entitas (ID: {$entity->id}, Tipe: {$entityType}) tidak memiliki order_id. Dilewati.");
            return;
        }

        Log::info("Job CheckMidtransTransactionStatus: [Percobaan {$this->attempts()}] Memeriksa status Midtrans untuk {$entityType} - orderId: {$orderId}");

        try {
            $midtransStatusResponse = MidtransSdkTransaction::status($orderId);
            Log::info("Job CheckMidtransTransactionStatus: Respon API Midtrans untuk orderId '{$orderId}'", (array) $midtransStatusResponse);

            $midtransTransactionStatus = $midtransStatusResponse->transaction_status ?? null;
            $midtransGrossAmount = $midtransStatusResponse->gross_amount ?? null;

            if ($midtransTransactionStatus && $midtransGrossAmount !== null) {
                $transactionHandlerService->handleTransactionStatus(
                    $orderId,
                    $midtransTransactionStatus,
                    (float) $midtransGrossAmount,
                    $entity
                );
            } else {
                Log::warning("Job CheckMidtransTransactionStatus: Respon Midtrans tidak lengkap untuk orderId '{$orderId}'.", ['response' => (array) $midtransStatusResponse]);
            }
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), '500') !== false || strpos($e->getMessage(), '502') !== false || strpos($e->getMessage(), '503') !== false) {
                Log::warning("Job CheckMidtransTransactionStatus: API Midtrans tidak tersedia sementara (500/502/503) untuk orderId '{$orderId}'. Job akan dilepas kembali ke antrean untuk dicoba lagi nanti.", [
                    'message' => $e->getMessage()
                ]);
                $this->release(600);
                return;
            }

            if (strpos($e->getMessage(), '404') !== false) {
                Log::warning("Job CheckMidtransTransactionStatus: Midtrans mengembalikan 404 untuk orderId '{$orderId}'. Transaksi belum diinisiasi oleh pengguna. Mencoba ulang sesuai jadwal.", [
                    'message' => $e->getMessage()
                ]);
                throw $e;
            }

            Log::error("Job CheckMidtransTransactionStatus: Exception tidak terduga untuk orderId '{$orderId}'.", ['message' => $e->getMessage()]);
            throw $e;
        }
    }
}
