<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\Transaction;
use App\Models\Order;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Jobs\SendWhatsAppReminder;

class TransactionHandlerService
{
    protected $waNotificationService;
    protected $midtransService;

    public function __construct(WhatsAppNotificationService $waNotificationService, MidtransService $midtransService)
    {
        $this->waNotificationService = $waNotificationService;
        $this->midtransService = $midtransService;
    }

    public function handleTransactionStatus(string $orderId, string $midtransTransactionStatus, float $grossAmount): bool
    {
        Log::info("THS: Processing orderId: {$orderId}, midtrans_status: {$midtransTransactionStatus}, amount: {$grossAmount}");

        $entity = null;
        $entityType = null;

        // Deteksi entitas berdasarkan pola order_id
        // Urutan penting: coba deteksi format PROD- baru dulu, lalu Event, lalu prod-order- lama

        // MEMBUAT REGEX AGAR LEBIH LONGGAR UNTUK PRODUK BARU
        // PERBAIKAN: Mengubah regex agar lebih spesifik untuk suffix (alphanumeric 3 karakter)
        if (preg_match('#^PROD-(PD|PF)-(\d{6})-(\d+)-([A-Z0-9]{3})$#', $orderId, $matches)) {
            Log::info("THS: OrderId matched PROD- (new product) format. OrderId: {$orderId}");
            $entityType = 'order';
            $entity = Order::with('user', 'product')->where('order_id', $orderId)->first();
            // Tambahkan logging jika entity tidak ditemukan
            if (!$entity) {
                Log::error("THS: Order entity with order_id {$orderId} not found in DB after regex match (PROD- format).");
            }
        } elseif (preg_match('#^ORD-(WOR|WEB|EVT)-(\d{6})-(\d+)-([A-Z0-9]{3})$#', $orderId, $matches)) {
            Log::info("THS: OrderId matched ORD- (event) format. OrderId: {$orderId}");
            $entityType = 'participant';
            $participantId = (int)$matches[3];
            $entity = Participant::find($participantId);
            if (!$entity) {
                Log::error("THS: Participant entity with ID {$participantId} for order_id {$orderId} not found in DB after regex match (ORD- format).");
            }
        } elseif (Str::startsWith($orderId, 'prod-order-')) {
            Log::info("THS: OrderId matched prod-order- (old product) format. OrderId: {$orderId}");
            $entityType = 'order';
            $entity = Order::with('user', 'product')->where('order_id', $orderId)->first();
            if (!$entity) {
                Log::error("THS: Order entity with order_id {$orderId} not found in DB after Str::startsWith match (prod-order- format).");
            }
        } else {
            // Jika tidak ada format yang dikenali sama sekali
            Log::error("THS: Invalid or unidentifiable orderId format: {$orderId}. No matching regex/prefix found.");
            return false;
        }

        if (!$entity) {
            // Ini akan menangani kasus di mana regex cocok, tapi entity tidak ditemukan di DB.
            // Sebelumnya error ini hanya muncul jika tidak ada regex yang cocok sama sekali.
            Log::error("THS: Entity could not be loaded for orderId: {$orderId}. Check DB record existence and relationships. Type attempted: {$entityType}.");
            return false;
        }

        // Midtrans status mapping ke status lokal
        $localTransactionStatus = 'unknown';
        switch (strtolower($midtransTransactionStatus)) {
            case 'capture':
            case 'settlement':
                $localTransactionStatus = 'paid';
                break;
            case 'pending':
                $localTransactionStatus = 'pending';
                break;
            case 'deny':
            case 'cancel':
            case 'expire':
                $localTransactionStatus = 'failed';
                break;
            default:
                Log::warning("THS: Unmapped Midtrans status '{$midtransTransactionStatus}' for orderId {$orderId}.");
                break;
        }

        Log::info("THS: Mapped midtrans_status '{$midtransTransactionStatus}' to local_status '{$localTransactionStatus}' for orderId {$orderId}.");

        $existingTransaction = Transaction::where('order_id', $orderId)->first();

        // Mencegah logika/notifikasi ganda jika status tidak berubah
        if ($existingTransaction && $existingTransaction->status === $localTransactionStatus) {
            Log::info("THS: No local status change for orderId: {$orderId} (current: {$localTransactionStatus}). Skipping redundant logic.");
            return true;
        }

        if ($localTransactionStatus === 'unknown') {
            return true;
        }

        // --- BLOK UNTUK STATUS PAID / FAILED ---
        if ($localTransactionStatus === 'paid' || $localTransactionStatus === 'failed') {
            $statusToSet = $localTransactionStatus;

            if ($entityType === 'participant') {
                $entity->payment_status = $statusToSet;
                $entity->is_paid = $statusToSet === 'paid' ? 1 : 0;
            } elseif ($entityType === 'order') {
                $entity->status = $statusToSet;
            }
            $entity->save();

            // Kirim notifikasi HANYA jika ini adalah perubahan status pertama kali
            if (!$existingTransaction || $existingTransaction->status !== $statusToSet) {
                if ($statusToSet === 'paid') {
                    if ($entityType === 'participant') {
                        Log::info("THS: Attempting to send 'paid' notifications for participant {$entity->id}...");
                        $this->waNotificationService->sendToParticipant($entity, $entity->affiliate_id);
                        $this->waNotificationService->sendAdminNotification($entity, $entity->event_type);
                        if ($entity->affiliate_id !== 'admin') {
                            $this->waNotificationService->sendAffiliateNotification($entity->affiliate_id, $entity, $entity->event_type);
                        }
                    } elseif ($entityType === 'order') {
                        Log::info("THS: Attempting to send 'paid' notifications for product order {$entity->id}...");
                        $this->waNotificationService->sendProductPurchaseSuccessNotification($entity);
                        $this->waNotificationService->sendAdminNotificationForProductOrder($entity);
                    }
                } elseif ($statusToSet === 'failed') {
                    Log::info("THS: Attempting to send 'failed' notification for orderId {$orderId}...");
                    if ($entityType === 'participant') {
                        $this->waNotificationService->sendEventFollowUpCancellation($entity);
                    } elseif ($entityType === 'order') {
                        $this->waNotificationService->sendProductFollowUpCancellation($entity);
                    }
                }
            }

            // --- BLOK UNTUK STATUS PENDING ---
        } elseif ($localTransactionStatus === 'pending') {
            if ($entityType === 'participant') {
                $entity->payment_status = 'pending';
            } elseif ($entityType === 'order') {
                $entity->status = 'pending';
            }
            $entity->save();

            try {
                $midtransDetails = $this->midtransService->checkPaymentStatus($orderId);

                if ($midtransDetails) {
                    $paymentType = $midtransDetails->payment_type ?? 'unknown';
                    $grossAmount = (float)($midtransDetails->gross_amount ?? $grossAmount);
                    $expiryTime = isset($midtransDetails->expiry_time) ? Carbon::parse($midtransDetails->expiry_time) : null;
                    $vaNumber = null;
                    $bank = null;
                    $qrisContent = null;
                    $paymentCode = $midtransDetails->payment_code ?? null;
                    $billerCode = $midtransDetails->biller_code ?? null;
                    $billKey = $midtransDetails->bill_key ?? null;

                    if (!empty($midtransDetails->va_numbers)) {
                        $vaNumber = $midtransDetails->va_numbers[0]->va_number ?? null;
                        $bank = $midtransDetails->va_numbers[0]->bank ?? null;
                    } elseif (isset($midtransDetails->permata_va_number)) {
                        $bank = 'permata';
                        $vaNumber = $midtransDetails->permata_va_number; // Perbaikan typo
                    }

                    if ($paymentType === 'qris' && isset($midtransDetails->actions)) {
                        foreach ($midtransDetails->actions as $action) {
                            if (($action->name ?? '') === 'generate-qr-code' && isset($action->url)) {
                                $qrisContent = $action->url;
                                break;
                            }
                        }
                    }

                    Log::info("THS: Attempting to send 'pending' payment details notification for orderId {$orderId}...");
                    $this->waNotificationService->sendPendingPaymentDetails(
                        $entity,
                        $paymentType,
                        $grossAmount,
                        $vaNumber,
                        $bank,
                        $qrisContent,
                        $paymentCode,
                        $billerCode,
                        $billKey,
                        $expiryTime
                    );

                    if ($expiryTime) {
                        Log::info("THS: Attempting to schedule follow-up reminders for orderId {$orderId}...");
                        $this->waNotificationService->schedulePendingFollowUps(
                            $entity,
                            $paymentType,
                            $grossAmount,
                            $vaNumber,
                            $bank,
                            $qrisContent,
                            $paymentCode,
                            $billerCode,
                            $billKey,
                            $expiryTime,
                            $orderId
                        );
                    }
                } else {
                    Log::info("THS: Snap may not have been opened. No Midtrans details to process for orderId {$orderId}.");
                }
            } catch (\Exception $e) {
                Log::error("THS: Error getting Midtrans details for pending {$orderId}: " . $e->getMessage());
            }
        }

        // Memastikan record transaksi di database lokal selalu terupdate
        $transactionData = ['amount' => $grossAmount, 'status' => $localTransactionStatus];
        if ($entityType === 'participant') {
            $transactionData['participant_id'] = $entity->id;
        } elseif ($entityType === 'order') {
            $transactionData['user_id'] = $entity->user_id;
        }

        Transaction::updateOrCreate(['order_id' => $orderId], $transactionData);
        Log::info("THS: Transaction record for orderId: {$orderId} ensured with status: {$localTransactionStatus}.");

        return true;
    }
}