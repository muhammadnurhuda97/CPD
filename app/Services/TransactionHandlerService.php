<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Participant;
use App\Models\Order;
use App\Models\Membership; // Pastikan Membership di-import
use App\Models\User; // Pastikan User di-import
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\WhatsAppNotificationService; // Pastikan Service WA di-import
use Carbon\Carbon;

class TransactionHandlerService
{
    protected $waService;

    public function __construct(WhatsAppNotificationService $waService)
    {
        $this->waService = $waService;
    }

    public function handleTransactionStatus($orderId, $transactionStatus, $grossAmount)
    {
        Log::info("THS: Processing orderId: {$orderId}, midtrans_status: {$transactionStatus}, amount: " . $grossAmount);

        // Cari transaksi yang ada atau buat baru jika belum tercatat
        $transaction = Transaction::firstOrNew(['order_id' => $orderId]);

        // Jika transaksi baru, isi data awal
        if (!$transaction->exists) {
            $transaction->amount = $grossAmount;
            // Coba tentukan user_id atau participant_id dari orderId jika memungkinkan
            // (Logika ini perlu disesuaikan dengan format Order ID Anda)
            // Contoh sederhana:
            if (preg_match('/^ORD-(WOR|WEB|EVT)-(\d{6})-(\d+)-([A-Z0-9]+)$/', $orderId, $matches)) {
                $transaction->participant_id = (int)$matches[3];
            } elseif (preg_match('/^PROD-(\d+)-(\d+)-([A-Z0-9]+)$/', $orderId, $matches)) {
                $transaction->user_id = (int)$matches[2];
                // Cari Order berdasarkan orderId untuk link ke product
                $order = Order::where('order_id', $orderId)->first();
                if ($order) {
                    $transaction->order_model_id = $order->id; // Link ke tabel orders
                    $transaction->order_model_type = Order::class;
                }
            }
            $transaction->status = 'pending'; // Status awal
        }

        // Jangan proses jika status sudah final (paid/failed/cancelled/expired)
        if (in_array($transaction->status, ['paid', 'failed', 'cancelled', 'expired'])) {
            Log::info("THS: Transaction {$orderId} already has final status '{$transaction->status}'. Skipping update.");
            return;
        }

        $participant = null;
        $order = null;
        $entityUpdated = false;

        // --- Logika Update Status Utama ---
        DB::beginTransaction();
        try {
            // Update status transaksi berdasarkan status Midtrans
            if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
                $transaction->status = 'paid';
            } elseif ($transactionStatus == 'pending') {
                $transaction->status = 'pending';
            } elseif ($transactionStatus == 'deny' || $transactionStatus == 'cancel' || $transactionStatus == 'expire') {
                $transaction->status = ($transactionStatus == 'expire') ? 'expired' : 'failed';
            } else {
                $transaction->status = 'unknown'; // Tangani status lain jika perlu
            }

            $transaction->save(); // Simpan status transaksi

            // --- Update Status Entitas Terkait (Participant/Order) ---
            // 1. Jika ini transaksi event (berdasarkan participant_id di transaksi atau format orderId)
            if ($transaction->participant_id) {
                $participant = Participant::find($transaction->participant_id);
                if ($participant && $participant->payment_status !== 'paid') { // Hanya update jika belum paid
                    if ($transaction->status === 'paid') {
                        $participant->payment_status = 'paid';
                        $participant->is_paid = 1; // Pastikan is_paid juga diupdate
                        $participant->payment_method = 'midtrans'; // Set metode pembayaran
                        $participant->save();
                        $entityUpdated = true;
                        Log::info("THS: Participant {$participant->id} status updated to 'paid'.");
                    } elseif ($transaction->status === 'failed' || $transaction->status === 'expired') {
                        // Reset status agar bisa coba bayar lagi? Atau set ke 'failed'?
                        // Untuk sekarang kita biarkan status participant sesuai pilihan awal (pending_choice/pending_cash)
                        // $participant->payment_status = 'failed';
                        // $participant->save();
                        // Log::info("THS: Participant {$participant->id} payment failed/expired.");
                    }
                }
            }
            // 2. Jika ini transaksi produk (berdasarkan user_id/order_model_id di transaksi atau format orderId)
            elseif ($transaction->order_model_id && $transaction->order_model_type == Order::class) {
                $order = Order::find($transaction->order_model_id);
                if ($order && $order->status !== 'completed') { // Hanya update jika belum completed
                    if ($transaction->status === 'paid') {
                        $order->status = 'completed'; // Atau 'paid', 'processing' tergantung alur produk Anda
                        $order->save();
                        $entityUpdated = true;
                        Log::info("THS: Order {$order->id} status updated to 'completed'.");
                        // Di sini bisa ditambahkan logika pemberian akses produk digital, dll.
                        // $this->grantProductAccess($order->user_id, $order->product_id);
                    } elseif ($transaction->status === 'failed' || $transaction->status === 'expired') {
                        $order->status = 'failed'; // Set status order gagal
                        $order->save();
                        Log::info("THS: Order {$order->id} payment failed/expired.");
                    }
                }
            }

            DB::commit(); // Simpan semua perubahan jika berhasil

            // --- Kirim Notifikasi HANYA JIKA status menjadi 'paid' ---
            if ($transaction->status === 'paid' && $entityUpdated) {
                if ($participant) { // Jika ini pembayaran event participant
                    Log::info("THS: Sending WA notifications for successful participant payment {$participant->id}.");

                    // 1. Kirim notif lunas ke peserta (dengan link referral H-2 & H+6)
                    $this->waService->sendPaidConfirmation($participant);

                    // 2. KEMBALIKAN NOTIF ADMIN (LUNAS)
                    $this->waService->sendAdminNotification($participant, $participant->event_type);

                    // 3. KEMBALIKAN NOTIF AFFILIATE (LUNAS)
                    if ($participant->affiliate_id) {
                        $this->waService->sendAffiliateNotification($participant->affiliate_id, $participant, $participant->event_type);
                    }

                    // 4. Kirim notif ke referrer (LUNAS)
                    if ($participant->referred_by_participant_id) {
                        $referrer = Participant::find($participant->referred_by_participant_id);
                        if ($referrer) {
                            $this->waService->sendReferrerNotification($participant, $referrer);
                            Log::info("THS: Referrer notification triggered for referrer {$referrer->id}.");
                        }
                    }

                    $participant->save();
                } elseif ($order) { // Jika ini pembayaran order produk
                    Log::info("THS: Sending WA notifications for successful product order {$order->id}.");
                    // Kirim notif admin untuk order produk baru
                    $this->waService->sendNewProductOrderAdminNotification($order);
                    // Kirim notif konfirmasi ke user pembeli? (Buat fungsi baru jika perlu)
                    // $this->waService->sendProductOrderConfirmation($order->user, $order);
                    // Kirim notif komisi ke affiliate? (Jika ada sistem komisi produk)
                }
            }
        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan perubahan jika ada error
            Log::error("THS: Error processing transaction status for Order ID {$orderId}.", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Re-throw exception agar Midtrans tahu ada masalah (jika ini dari webhook)
            // throw $e; // Hati-hati, ini bisa menyebabkan Midtrans retry terus jika error persisten
        }
    }

    private function extractMidtransDetails($midtransDetails): array
    {
        if (!is_object($midtransDetails)) return [];
        $vaNumber = null;
        $bank = null;
        if (isset($midtransDetails->va_numbers) && is_array($midtransDetails->va_numbers) && count($midtransDetails->va_numbers) > 0) {
            $vaNumber = $midtransDetails->va_numbers[0]->va_number ?? null;
            $bank = $midtransDetails->va_numbers[0]->bank ?? null;
        } elseif (isset($midtransDetails->permata_va_number)) {
            $vaNumber = $midtransDetails->permata_va_number;
            $bank = 'permata';
        }
        $qrisUrl = null;
        if (($midtransDetails->payment_type ?? '') === 'qris' && isset($midtransDetails->actions)) {
            foreach ($midtransDetails->actions as $action) {
                if (($action->name ?? '') === 'generate-qr-code' && isset($action->url)) {
                    $qrisUrl = $action->url;
                    break;
                }
            }
        }
        return [
            'payment_type' => $midtransDetails->payment_type ?? 'unknown',
            'payment_channel' => $bank,
            'va_number' => $vaNumber,
            'qris_url' => $qrisUrl,
            'payment_code' => $midtransDetails->payment_code ?? null,
            'biller_code' => $midtransDetails->biller_code ?? null,
            'bill_key' => $midtransDetails->bill_key ?? null,
            'expiry_time' => isset($midtransDetails->expiry_time) ? Carbon::parse($midtransDetails->expiry_time) : null,
        ];
    }
}
