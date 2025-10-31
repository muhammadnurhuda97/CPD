<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MidtransService;
use App\Services\TransactionHandlerService;
use App\Models\Participant;
use App\Models\Product;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Midtrans\Config;
use Midtrans\Notification as MidtransNotificationSdk;
use Midtrans\Transaction as MidtransSdkTransaction;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Notification as EventNotificationModel;
use App\Services\WhatsAppNotificationService;

class PaymentController extends Controller
{
    protected $midtransService;
    protected $transactionHandlerService;
    protected $waService;


    public function __construct(
        MidtransService $midtransService,
        TransactionHandlerService $transactionHandlerService,
        WhatsAppNotificationService $waService // Inject di Constructor
    ) {
        $this->midtransService = $midtransService;
        $this->transactionHandlerService = $transactionHandlerService;
        $this->waService = $waService; // Simpan service-nya
    }

    public function initiatePayment(Request $request, string $type, $identifier)
    {
        Log::info("🔁 [PaymentController] Memulai inisiasi pembayaran. Tipe: '{$type}', Identifier: '{$identifier}'.");

        if (!in_array($type, ['event', 'product'])) {
            return redirect()->route('payment.error.page')->with('message', 'Tipe pembayaran tidak valid.');
        }

        $customerDetails = [];
        $orderId = '';
        $grossAmount = 0;
        $itemName = 'Pembayaran';
        $viewData = [];

        if ($type === 'event') {
            $grossAmount = $request->query('price');
            if (!$grossAmount || !is_numeric($grossAmount) || $grossAmount <= 0) {
                return redirect()->route('payment.error.page')->with('message', 'Jumlah pembayaran tidak valid.');
            }

            $participant = Participant::with('notification')->findOrFail($identifier);

            if ($participant->notification && Carbon::parse($participant->notification->event_date . ' ' . $participant->notification->event_time, 'Asia/Jakarta')->isPast()) {
                return redirect()->route('payment.error.page')->with('message', 'Event ini telah berakhir. Pembayaran tidak dapat dilanjutkan.');
            }

            $itemName = $participant->notification->event ?? 'Event';
            $date = Carbon::now()->format('dmy');
            $eventTypeCode = strtoupper(substr($participant->event_type, 0, 3));
            $uniqueSuffix = strtoupper(Str::random(3));
            $orderId = "ORD-{$eventTypeCode}-{$date}-" . str_pad($participant->id, 4, '0', STR_PAD_LEFT) . "-{$uniqueSuffix}";

            $participant->order_id = $orderId;
            $participant->payment_status = 'pending';
            $participant->save();

            $fullName = $participant->name;
            $nameParts = explode(' ', $fullName, 2);
            $customerDetails = [
                'first_name' => $nameParts[0],
                'last_name'  => $nameParts[1] ?? '',
                'email'      => $participant->email,
                'phone'      => $participant->whatsapp,
            ];
            $viewData = [
                'participant' => $participant,
                'price' => (float) $grossAmount,
                'entityType' => 'event',
                'onCloseUrl' => route('webinar.form', ['notification' => $participant->notification_id]), // BARU: Tambahkan URL ini
            ];
        } elseif ($type === 'product') {
            $product = Product::where('slug', $identifier)->firstOrFail();
            if (!Auth::check()) {
                session(['url.intended' => $request->fullUrl()]);
                return redirect()->route('login')->with('error', 'Silakan login atau daftar terlebih dahulu untuk melanjutkan.');
            }

            $user = Auth::user();
            $itemName = $product->name;
            $uniqueSuffix = strtoupper(Str::random(3));
            $orderId = 'PROD-' . $product->id . '-' . $user->id . '-' . $uniqueSuffix;
            $order = Order::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'amount' => $product->price,
                'status' => 'pending',
                'order_id' => $orderId
            ]);
            $grossAmount = $order->amount;
            $customerDetails = ['first_name' => $user->name, 'email' => $user->email, 'phone' => $user->whatsapp];
            $viewData = [
                'order' => $order,
                'product' => $product,
                'entityType' => 'product',
                'onCloseUrl' => route('products.detail', ['slug' => $product->slug]), // BARU: Tambahkan URL ini
            ];
        }

        $snapToken = $this->midtransService->createTransaction($orderId, (float)$grossAmount, $customerDetails, $itemName);
        if (!$snapToken) {
            return redirect()->route('payment.error.page')->with('message', 'Gagal membuat transaksi pembayaran. Mohon coba lagi.');
        }

        $viewData['snapToken'] = $snapToken;
        $viewData['orderId'] = $orderId;
        return view('payment.midtrans', $viewData);
    }


    public function paymentSuccess(Request $request)
    {
        $orderId = $request->query('order_id');
        if (!$orderId) {
            return redirect()->route('payment.error.page')->with(['message' => 'Transaksi belum selesai atau dibatalkan.']);
        }

        try {
            $midtransStatusResponse = $this->midtransService->checkPaymentStatus($orderId);
            if ($midtransStatusResponse && isset($midtransStatusResponse->transaction_status)) {
                $this->transactionHandlerService->handleTransactionStatus($orderId, $midtransStatusResponse->transaction_status, (float)($midtransStatusResponse->gross_amount ?? 0));
            }
        } catch (\Exception $e) {
            Log::warning("Gagal memeriksa status Midtrans untuk orderId {$orderId} (mungkin belum ada): " . $e->getMessage());
            $midtransStatusResponse = null;
        }

        $transaction = Transaction::where('order_id', $orderId)->first();
        $transactionInfo = $this->getTransactionInfo($transaction, $request);

        if ($transaction && $transaction->status === 'paid') {
            return view('payment.success', compact('transactionInfo'));
        }

        if ($transaction && in_array($transaction->status, ['failed', 'closed'])) {
            $message = $transaction->status === 'closed'
                ? 'Masa berlaku pembayaran Anda telah berakhir.'
                : 'Pembayaran Anda gagal atau dibatalkan.';

            $retryUrl = null;
            if (isset($transactionInfo['is_expired']) && !$transactionInfo['is_expired']) {
                $message .= ' Silakan coba bayar lagi.';
                $retryUrl = route('payment.cancel', ['orderId' => $orderId]);
            } else if (isset($transactionInfo['is_expired']) && $transactionInfo['is_expired']) {
                $message = 'Event ini telah berakhir, pembayaran tidak dapat dilanjutkan.';
            }

            return view('payment.error', compact('message', 'transactionInfo', 'retryUrl'));
        }

        $paymentDetails = $this->getPaymentDetailsFromMidtransResponse($midtransStatusResponse);
        return view('payment.pending', compact('transactionInfo', 'paymentDetails'));
    }

    public function cancelAndRetry(Request $request, $orderId)
    {
        Log::info("User requested to cancel and retry payment for orderId: {$orderId}");

        try {
            MidtransSdkTransaction::cancel($orderId);
            Log::info("Midtrans cancel API call successful for {$orderId}");
        } catch (\Exception $e) {
            Log::warning("Could not cancel transaction {$orderId} on Midtrans (might be already final).", ['error' => $e->getMessage()]);
        }

        $participant = null;
        $order = null;
        $isEvent = false;

        $pattern = '#^ORD-(WOR|WEB|EVT)-(\d{6})-(\d+)-([A-Z0-9]+)$#';
        if (preg_match($pattern, $orderId, $matches)) {
            $isEvent = true;
            $participant = Participant::find((int)$matches[3]);
        } elseif (preg_match('#^PROD-.+$#', $orderId)) { // BARU: Menangani order produk
            $order = Order::with('product')->where('order_id', $orderId)->first();
            if ($order && $order->product) {
                return redirect()->route('payment.initiate', [
                    'type' => 'product',
                    'identifier' => $order->product->slug // Menggunakan slug produk sebagai identifier
                ]);
            }
        }

        if ($isEvent && $participant) {
            $notification = EventNotificationModel::find($participant->notification_id);
            $price = $notification ? $notification->price : 0;
            Log::info("Redirecting participant {$participant->id} to re-initiate payment for event.");
            return redirect()->route('payment.initiate', [
                'type' => 'event',
                'identifier' => $participant->id,
                'price' => $price,
            ]);
        }

        Log::error("Cancel & Retry failed: Could not find entity or appropriate redirect for orderId: {$orderId}");
        return redirect()->route('payment.error.page')->with('message', 'Pembatalan tidak dapat diproses atau item terkait tidak ditemukan.');
    }

    public function handleMidtransCallback(Request $request)
    {
        Log::info('Midtrans Callback Received (Full Payload):', $request->all());

        // Set konfigurasi Midtrans di sini untuk memastikan konsistensi
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');

        try {
            // Buat objek notifikasi dari payload yang masuk
            $notification = new MidtransNotificationSdk();

            // Ambil detail penting dari notifikasi
            $orderId = $notification->order_id;
            $transactionStatus = $notification->transaction_status;
            $grossAmount = $notification->gross_amount;

            // Panggil service handler untuk memproses logika utama
            $this->transactionHandlerService->handleTransactionStatus($orderId, $transactionStatus, (float) $grossAmount);

            // Jika semua berhasil, kirim respons OK
            return response()->json(['message' => 'Notification processed successfully.'], 200);
        } catch (\Exception $e) {
            // Jika terjadi error APAPUN selama proses, tangkap di sini
            Log::error("Error processing Midtrans webhook for Order ID: " . ($notification->order_id ?? 'N/A'), [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                // 'trace' => $e->getTraceAsString() // Uncomment ini jika butuh trace lengkap
            ]);

            // Kirim respons error ke Midtrans agar mereka tidak mencoba lagi terus-menerus
            return response()->json(['message' => 'Error processing notification.'], 500);
        }
    }

    public function checkPaymentStatus(Request $request, $orderId)
    {
        Log::info("AJAX checkPaymentStatus called for orderId: {$orderId}");
        $midtransStatusResponse = null;

        try {
            $midtransStatusResponse = $this->midtransService->checkPaymentStatus($orderId);
            if ($midtransStatusResponse && isset($midtransStatusResponse->transaction_status)) {
                $this->transactionHandlerService->handleTransactionStatus(
                    $orderId,
                    $midtransStatusResponse->transaction_status,
                    (float)($midtransStatusResponse->gross_amount ?? 0)
                );
            }
        } catch (\Exception $e) {
            Log::error("AJAX checkPaymentStatus: Exception for {$orderId}: " . $e->getMessage());
        }

        $transaction = Transaction::where('order_id', $orderId)->first();

        if (!$transaction) {
            return response()->json(['status' => 'error', 'message' => 'Transaksi belum ditemukan di sistem kami.'], 404);
        }

        $isPaid = $transaction->status === 'paid';
        $isFailedOrClosed = in_array($transaction->status, ['failed', 'closed']);

        $paymentDetails = $this->getPaymentDetailsFromMidtransResponse($midtransStatusResponse);

        $response = [
            'status' => 'success',
            'payment_status_code' => $transaction->status,
            'is_final' => $isPaid || $isFailedOrClosed,
            'redirect_url' => null,
            'payment_details' => $paymentDetails
        ];

        if ($isPaid || $isFailedOrClosed) {
            $response['redirect_url'] = route('payment.success', ['order_id' => $transaction->order_id]);
        }

        return response()->json($response);
    }

    public function paymentErrorPage(Request $request)
    {
        $message = $request->session()->get('message', 'Terjadi kesalahan saat proses pembayaran.');
        $transactionInfo = $this->getTransactionInfo(null, $request);
        return view('payment.error', compact('message', 'transactionInfo'));
    }

    private function getTransactionInfo(?Transaction $transaction, Request $request): array
    {
        $orderId = $transaction->order_id ?? $request->query('order_id');
        if (!$orderId) return [];

        $info = [
            'id' => $orderId,
            'amount' => (float)($transaction->amount ?? 0),
            'user_name' => 'Pelanggan',
            'name' => 'Pesanan Anda',
            'type' => 'transaksi',
            'is_expired' => true,
        ];

        $pattern = '#^ORD-(WOR|WEB|EVT)-(\d{6})-(\d+)-([A-Z0-9]+)$#';
        if (preg_match($pattern, $orderId, $matches)) {
            $entity = Participant::with('notification')->find((int)$matches[3]);
            if ($entity) {
                $info['user_name'] = $entity->name;
                if ($entity->notification) {
                    $info['name'] = $entity->notification->event ?? 'Event';
                    // ===== PERBAIKAN LOGIKA WAKTU DI SINI JUGA =====
                    $info['is_expired'] = Carbon::parse($entity->notification->event_date . ' ' . $entity->notification->event_time, 'Asia/Jakarta')->isPast();
                }
                $info['type'] = 'event';
            }
        } elseif (preg_match('#^PROD-.+$#', $orderId)) {
            $entity = Order::with('user', 'product')->where('order_id', $orderId)->first();
            if ($entity) {
                $info['user_name'] = $entity->user->name ?? 'Pelanggan';
                $info['name'] = $entity->product->name ?? 'Produk';
                $info['type'] = 'produk';
                $info['is_expired'] = false;
            }
        }
        return $info;
    }

    private function getPaymentDetailsFromMidtransResponse($midtransResponse): array
    {
        if (!is_object($midtransResponse) || !isset($midtransResponse->transaction_status)) return [];

        $details = [
            'payment_type' => $midtransResponse->payment_type ?? 'Tidak diketahui',
            'transaction_status' => $midtransResponse->transaction_status,
            'gross_amount' => (float)($midtransResponse->gross_amount ?? 0),
            'expiry_time'  => $midtransResponse->expiry_time ?? null,
            'bank' => null,
            'va_number' => null,
            'payment_code' => null,
            'qris_url' => null,
            'biller_code' => null,
            'bill_key' => null,
        ];

        if (isset($midtransResponse->va_numbers) && !empty($midtransResponse->va_numbers)) {
            $details['bank'] = strtoupper($midtransResponse->va_numbers[0]->bank ?? '');
            $details['va_number'] = $midtransResponse->va_numbers[0]->va_number ?? null;
        } elseif (isset($midtransResponse->permata_va_number)) {
            $details['bank'] = 'PERMATA';
            $details['va_number'] = $midtransResponse->permata_va_number;
        } elseif (($midtransResponse->payment_type ?? '') === 'echannel') {
            $details['bank'] = 'Mandiri';
            $details['biller_code'] = $midtransResponse->biller_code ?? null;
            $details['bill_key'] = $midtransResponse->bill_key ?? null;
        }

        if (isset($midtransResponse->payment_code)) {
            $details['payment_code'] = $midtransResponse->payment_code;
        }

        if (isset($midtransResponse->actions)) {
            foreach ($midtransResponse->actions as $action) {
                if (isset($action->name) && $action->name === 'generate-qr-code' && isset($action->url)) {
                    $details['qris_url'] = $action->url;
                    break;
                }
            }
        }
        return $details;
    }

    public function showPaymentChoice(Participant $participant)
    {
        // Eager load relasi notification (event)
        $participant->load('notification');

        if (!$participant->notification) {
            Log::error('showPaymentChoice: Notification not found for participant.', ['participant_id' => $participant->id]);
            abort(404, 'Detail event tidak ditemukan.');
        }

        if (!$participant->notification->is_paid) {
            Log::warning('showPaymentChoice: Attempted to access payment choice for a free event.', ['participant_id' => $participant->id]);
            // Jika event gratis, arahkan ke halaman sukses saja
            return redirect()->route('participant.success', ['participant' => $participant->id]);
        }

        // Jika sudah pernah memilih atau sudah lunas, redirect sesuai status
        if ($participant->payment_status === 'paid') {
            Log::info('showPaymentChoice: Participant already paid.', ['participant_id' => $participant->id]);
            return redirect()->route('payment.success', ['order_id' => $participant->order_id]); // Asumsi order_id sudah ada
        }
        if ($participant->payment_method === 'cash' && $participant->payment_status === 'pending_cash_verification') {
            Log::info('showPaymentChoice: Participant chose cash, redirecting to cash instructions.', ['participant_id' => $participant->id]);
            return redirect()->route('payment.cash.instructions', ['participant' => $participant->id]); // Rute ini akan dibuat nanti
        }
        // Bisa ditambahkan pengecekan untuk redirect ke Midtrans jika sudah memilih 'midtrans' dan belum bayar

        Log::info('showPaymentChoice: Displaying payment choice page.', ['participant_id' => $participant->id]);
        return view('payment.choice', compact('participant'));
    }

    /**
     * Memproses pilihan metode pembayaran dari user.
     */
    public function selectPaymentMethod(Request $request, Participant $participant) // Hapus $waService dari argumen jika sudah di constructor
    {
        $request->validate([
            'payment_method' => 'required|in:midtrans,cash',
        ]);

        $method = $request->input('payment_method');
        Log::info('selectPaymentMethod: Processing payment method selection.', ['participant_id' => $participant->id, 'method' => $method]);

        // Load relasi yang dibutuhkan untuk notifikasi
        $participant->loadMissing(['notification', 'affiliateUser', 'referrer']);

        if (!$participant->notification || !$participant->notification->is_paid) {
            Log::error('selectPaymentMethod: Invalid event data or free event.', ['participant_id' => $participant->id]);
            return redirect()->route('payment.error.page')->with('message', 'Event tidak valid untuk pemilihan pembayaran.');
        }

        // 1. Set status di objek
        $participant->payment_method = $method;
        if ($method === 'cash') {
            $participant->payment_status = 'pending_cash_verification';
        } else {
            $participant->payment_status = 'pending';
        }
        $participant->notified_registered = false; // Reset flag notif pendaftaran

        // 2. Kirim Notifikasi Awal (ke Peserta, Admin, Affiliate, Referrer)
        try {
            // 2a. Ke Peserta (Pendaftaran Awal Berhasil + Link Referral)
            $this->waService->sendPostEventMessage($participant);
            Log::info('selectPaymentMethod: Initial post-registration notification sent.', ['participant_id' => $participant->id]);

            // 2b. Ke Admin (Pendaftar BARU, status: Menunggu Tunai / Pending)
            $this->waService->sendAdminNotification($participant, $participant->event_type);
            Log::info('selectPaymentMethod: Initial Admin notification sent.', ['participant_id' => $participant->id]);

            // 2c. Ke Affiliate (Pendaftar BARU, status: Menunggu Tunai / Pending)
            if ($participant->affiliate_id) {
                // $participant->affiliateUser sudah di-load
                $this->waService->sendAffiliateNotification($participant->affiliate_id, $participant, $participant->event_type);
                Log::info('selectPaymentMethod: Initial Affiliate notification sent.', ['participant_id' => $participant->id, 'affiliate_id' => $participant->affiliate_id]);
            }

            // 2d. Ke Referrer (Pendaftar BARU, status: Menunggu Tunai / Pending)
            //    (Kita perlu fungsi WA baru untuk ini, atau modifikasi sendReferrerNotification)
            //    Untuk sekarang, kita panggil sendReferrerNotification (walaupun pesannya mungkin "Lunas", kita perbaiki di WA Service)
            if ($participant->referrer) {
                // $participant->referrer sudah di-load
                // KITA ASUMSIKAN sendReferrerNotification BISA MENANGANI STATUS BELUM LUNAS
                $this->waService->sendReferrerNotification($participant, $participant->referrer);
                Log::info('selectPaymentMethod: Initial Referrer notification sent.', ['participant_id' => $participant->id, 'referrer_id' => $participant->referrer->id]);
            }
        } catch (\Exception $e) {
            Log::error('selectPaymentMethod: Failed to send initial notifications.', [
                'participant_id' => $participant->id,
                'error' => $e->getMessage()
            ]);
        }

        // 3. Simpan semua perubahan (status + flag)
        $participant->save();
        Log::info('selectPaymentMethod: Participant status and notification flags saved.', ['participant_id' => $participant->id]);

        // 4. Arahkan ke langkah selanjutnya
        if ($method === 'cash') {
            Log::info('selectPaymentMethod: Redirecting to cash payment instructions.', ['participant_id' => $participant->id]);
            return redirect()->route('payment.cash.instructions', ['participant' => $participant->id]);
        } else {
            Log::info('selectPaymentMethod: Redirecting to Midtrans initiation.', ['participant_id' => $participant->id]);
            return redirect()->route('payment.initiate', [
                'type' => 'event',
                'identifier' => $participant->id,
                'price' => $participant->notification->price
            ]);
        }
    }

    // --- Rute baru untuk halaman instruksi cash (akan dibuat view-nya nanti) ---
    public function showCashInstructions(Participant $participant)
    {
        $participant->load('notification'); // Eager load data event

        // Validasi: Pastikan peserta memang memilih 'cash' dan statusnya sesuai
        if ($participant->payment_method !== 'cash' || $participant->payment_status !== 'pending_cash_verification') {
            Log::warning('showCashInstructions: Invalid access attempt.', [
                'participant_id' => $participant->id,
                'status' => $participant->payment_status,
                'method' => $participant->payment_method
            ]);
            // Redirect kembali ke halaman pilihan jika status/metode tidak cocok
            return redirect()->route('payment.choice', ['participant' => $participant->id]);
        }

        if (!$participant->notification) {
            Log::error('showCashInstructions: Notification not found.', ['participant_id' => $participant->id]);
            abort(404, 'Detail event tidak ditemukan.');
        }

        Log::info('showCashInstructions: Displaying cash instructions.', ['participant_id' => $participant->id]);
        return view('payment.cash_instructions', compact('participant'));
    }
}
