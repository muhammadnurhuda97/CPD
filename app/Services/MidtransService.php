<?php

namespace App\Services;

use Midtrans\Snap;
use Midtrans\Transaction as MidtransTransaction;
use Midtrans\Config;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$clientKey = config('midtrans.client_key');
        Config::$isProduction = filter_var(config('midtrans.is_production', false), FILTER_VALIDATE_BOOLEAN);
        Config::$isSanitized = filter_var(config('midtrans.is_sanitized', true), FILTER_VALIDATE_BOOLEAN);
        Config::$is3ds = filter_var(config('midtrans.is_3ds', true), FILTER_VALIDATE_BOOLEAN);

        if (empty(Config::$serverKey) || empty(Config::$clientKey)) {
            Log::critical('MidtransService: ServerKey atau ClientKey belum di-set pada konfigurasi Midtrans!');
        }
    }

    /**
     * Membuat transaksi Snap dan mengembalikan snap_token
     *
     * @param string $orderId
     * @param float $grossAmount
     * @param array $customerDetails
     * @return string|null
     */
    public function createTransaction(string $orderId, float $grossAmount, array $customerDetails): ?string
    {
        $transactionDetails = [
            'order_id' => $orderId,
            'gross_amount' => $grossAmount,
        ];

        $itemDetails = [
            [
                'id' => 'item1',
                'price' => $grossAmount,
                'quantity' => 1,
                'name' => 'Pendaftaran Event',
            ],
        ];

        $billingAddress = [
            'first_name' => $customerDetails['first_name'] ?? '',
            'last_name'  => $customerDetails['last_name'] ?? '',
            'email'      => $customerDetails['email'] ?? '',
            'phone'      => $customerDetails['phone'] ?? '',
        ];

        $transactionData = [
            'transaction_details' => $transactionDetails,
            'item_details' => $itemDetails,
            'customer_details' => $billingAddress,
        ];

        try {
            Log::info('MidtransService: Sending transaction data to Midtrans', [
                'transaction_data' => $transactionData
            ]);

            $snapToken = Snap::getSnapToken($transactionData);

            Log::info('MidtransService: Snap Token received', [
                'snap_token' => $snapToken
            ]);

            return $snapToken;
        } catch (\Exception $e) {
            Log::error("MidtransService: Error during Snap transaction: " . $e->getMessage(), [
                'order_id' => $orderId
            ]);
            return null;
        }
    }

    /**
     * Mengecek status pembayaran dari Midtrans
     *
     * @param string $orderId
     * @return \stdClass|null|false
     */
    public function checkPaymentStatus(string $orderId)
    {
        try {
            $status = MidtransTransaction::status($orderId);
            return $status;
        } catch (\Exception $e) {
            $message = $e->getMessage();

            if (strpos($message, '404') !== false) {
                Log::warning("MidtransService: Transaksi belum ditemukan untuk orderId {$orderId}. Kemungkinan Snap belum dibuka.");
                return null;
            }

            Log::error("MidtransService: Gagal mendapatkan status transaksi untuk orderId {$orderId}.", [
                'message' => $message
            ]);
            return false;
        }
    }
}
