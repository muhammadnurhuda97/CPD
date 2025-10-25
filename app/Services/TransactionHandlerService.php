<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\Transaction;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TransactionHandlerService
{
    protected $waNotificationService;
    protected $midtransService;

    public function __construct(WhatsAppNotificationService $waNotificationService, MidtransService $midtransService)
    {
        $this->waNotificationService = $waNotificationService;
        $this->midtransService = $midtransService;
    }

    public function handleTransactionStatus(string $orderId, string $midtransTransactionStatus, float $grossAmount, $entityFromJob = null): bool
    {
        Log::info("THS: Processing orderId: {$orderId}, midtrans_status: {$midtransTransactionStatus}, amount: {$grossAmount}");

        $entity = $entityFromJob;
        $entityType = null;
        if (!$entity) {
            $pattern = '#^ORD-(WOR|WEB|EVT)-(\d{6})-(\d+)-([A-Z0-9]+)$#';
            if (preg_match($pattern, $orderId, $matches)) {
                $entityType = 'participant';
                $entity = Participant::with('notification')->find((int)$matches[3]);
            } elseif (preg_match('#^PROD-.+$#', $orderId)) {
                $entityType = 'order';
                $entity = Order::with('user', 'product')->where('order_id', $orderId)->first();
            }
        } else {
            if ($entity instanceof Participant) $entityType = 'participant';
            elseif ($entity instanceof Order) $entityType = 'order';
        }

        if (!$entity) {
            Log::error("THS: Could not find entity for orderId: {$orderId}");
            $adminNumbers = config('services.admin.whatsapp', []);
            foreach ($adminNumbers as $number) {
                if (!empty(trim($number))) {
                    $this->waNotificationService->sendToStarsender([
                        'messageType' => 'text',
                        'to' => trim($number),
                        'body' => "🚨 ALERT SISTEM 🚨\n\nSistem menerima notifikasi pembayaran untuk Order ID: *{$orderId}*, namun tidak dapat menemukan data Peserta atau Order yang sesuai di database.\n\nMohon segera periksa log dan webhook dari Midtrans."
                    ]);
                }
            }

            return false;
        }

        $localStatus = 'unknown';
        switch (strtolower($midtransTransactionStatus)) {
            case 'capture':
            case 'settlement':
                $localStatus = 'paid';
                break;
            case 'pending':
                $localStatus = 'pending';
                break;
            case 'deny':
            case 'cancel':
                $localStatus = 'failed';
                break;
            case 'expire':
                $localStatus = 'closed';
                break;
        }

        $existingTransaction = Transaction::where('order_id', $orderId)->first();

        // Hanya proses jika status berubah
        if (!$existingTransaction || $existingTransaction->status !== $localStatus) {

            // Update status di tabel Order atau Participant
            if (in_array($localStatus, ['paid', 'failed', 'closed', 'pending'])) {
                if ($entityType === 'participant') {
                    $entity->payment_status = $localStatus;
                    $entity->is_paid = ($localStatus === 'paid');
                } elseif ($entityType === 'order') {
                    $entity->status = $localStatus;
                }
                $entity->save();
            }

            // Kirim notifikasi berdasarkan status baru
            if ($localStatus === 'paid') {
                if ($entityType === 'participant') {
                    // 1. Kirim konfirmasi instan
                    $this->waNotificationService->sendPaidConfirmation($entity);

                    // 2. JADWALKAN SEMUA REMINDER MASA DEPAN (PERUBAHAN DI SINI)
                    $this->waNotificationService->schedulePaidEventReminders($entity);

                    // 3. Notifikasi ke admin dan afiliasi
                    $this->waNotificationService->sendAdminNotification($entity, $entity->event_type);
                    $adminUser = User::where('username', 'admin')->first();
                    if ($adminUser && $entity->affiliate_id != $adminUser->id) {
                        $this->waNotificationService->sendAffiliateNotification($entity->affiliate_id, $entity, $entity->event_type);
                    }
                } elseif ($entityType === 'order') {
                    $this->waNotificationService->sendProductPurchaseSuccessNotification($entity);
                    $this->waNotificationService->sendAdminNotificationForProductOrder($entity);
                }
            } elseif ($localStatus === 'pending') {
                $midtransDetails = $this->midtransService->checkPaymentStatus($orderId);
                if ($midtransDetails) {
                    $details = $this->extractMidtransDetails($midtransDetails);
                    $this->waNotificationService->sendPendingPaymentDetails($entity, $details['payment_type'], $grossAmount, $details['va_number'], $details['payment_channel'], $details['qris_url'], $details['payment_code'], $details['biller_code'], $details['bill_key'], $details['expiry_time']);
                    if ($details['expiry_time']) {
                        $this->waNotificationService->schedulePendingFollowUps($entity, $details['payment_type'], $grossAmount, $details['va_number'], $details['payment_channel'], $details['qris_url'], $details['payment_code'], $details['biller_code'], $details['bill_key'], $details['expiry_time'], $orderId);
                    }
                }
            }
            // ==========================================================
            // == BLOK IMPLEMENTASI BARU UNTUK STATUS GAGAL & DITUTUP  ==
            // ==========================================================
            elseif (in_array($localStatus, ['failed', 'closed'])) {
                $midtransDetails = $this->midtransService->checkPaymentStatus($orderId);
                $paymentType = $midtransDetails->payment_type ?? '';

                // Kondisi khusus jika transaksi expired dan merupakan QRIS
                if ($localStatus === 'closed' && strtolower($midtransTransactionStatus) === 'expire' && strtolower($paymentType) === 'qris') {
                    // Kirim notifikasi QRIS kedaluwarsa khusus
                    if ($entityType === 'participant') {
                        $this->waNotificationService->sendEventQrisExpiredNotification($entity); //
                    }
                } else {
                    // Untuk semua kasus 'failed' dan 'closed' lainnya
                    if ($entityType === 'participant') {
                        $this->waNotificationService->sendAdminNotificationForCancellation($entity); //
                    } elseif ($entityType === 'order') {
                        $this->waNotificationService->sendProductFollowUpCancellation($entity); //
                    }
                }
            }
        }

        // Selalu update atau buat record di tabel transactions
        $midtransDetailsOnUpdate = $this->midtransService->checkPaymentStatus($orderId);
        $detailsForDb = $midtransDetailsOnUpdate ? $this->extractMidtransDetails($midtransDetailsOnUpdate) : [];
        $transactionData = array_merge(['amount' => $grossAmount, 'status' => $localStatus], $detailsForDb);
        if ($entityType === 'participant') {
            $transactionData['participant_id'] = $entity->id;
        } elseif ($entityType === 'order') {
            $transactionData['user_id'] = $entity->user_id;
        }
        Transaction::updateOrCreate(['order_id' => $orderId], $transactionData);

        return true;
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
