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

class PaymentController extends Controller
{
    protected $midtransService;
    protected $transactionHandlerService;

    public function __construct(
        MidtransService $midtransService,
        TransactionHandlerService $transactionHandlerService
    ) {
        $this->midtransService = $midtransService;
        $this->transactionHandlerService = $transactionHandlerService;
    }

    public function initiatePayment(Request $request, string $type, $identifier)
    {
        Log::info("🔁 [PaymentController] Memulai inisiasi pembayaran. Tipe: '{$type}', Identifier: '{$identifier}'.");

        $customerDetails = [];
        $orderId = '';
        $grossAmount = 0;
        $viewData = [];

        if ($type === 'event') {
            // Validasi price dari query parameter
            $grossAmount = $request->query('price');
            if (!$grossAmount || !is_numeric($grossAmount) || $grossAmount <= 0) {
                Log::error("Invalid or missing price parameter for event payment initiation.");
                return redirect()->route('payment.error')->with('error', 'Jumlah pembayaran tidak valid.');
            }

            $participant = Participant::findOrFail($identifier);
            $date = Carbon::now()->format('dmy');
            $eventTypeCode = strtoupper(substr($participant->event_type, 0, 3));
            $uniqueSuffix = strtoupper(Str::random(3));
            $orderId = "ORD-{$eventTypeCode}-{$date}-" . str_pad($participant->id, 4, '0', STR_PAD_LEFT) . "-{$uniqueSuffix}";
            $participant->order_id = $orderId;
            $participant->payment_status = 'pending';
            $participant->save();

            $fullName = $participant->name;
            $nameParts = explode(' ', $fullName, 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';
            $customerDetails = [
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'email'      => $participant->email,
                'phone'      => $participant->whatsapp,
            ];

            $viewData['participant'] = $participant;
            $viewData['price'] = (float) $grossAmount;
            $viewData['entityType'] = 'event';
        } elseif ($type === 'product') {

            if (!Auth::check()) {
                Log::info("🔁 [PaymentController] Pengguna belum login untuk checkout produk. Menyimpan intendedUrl dan redirect ke login.");
                session(['url.intended' => $request->fullUrl()]);
                return redirect()->route('login')->with('error', 'Silakan login atau daftar terlebih dahulu untuk melanjutkan.');
            }

            $product = Product::where('slug', $identifier)->firstOrFail();
            $user = Auth::user();

            // Cek kelengkapan profil dengan konsisten simpan intendedUrl
            if (!($user->address && $user->city && $user->zip && $user->whatsapp && $user->date_of_birth)) {
                session(['url.intended' => route('payment.initiate', ['type' => 'product', 'identifier' => $identifier])]);
                return redirect()->route('profile.edit')->with('error', 'Mohon lengkapi data profil Anda sebelum melanjutkan pembayaran.');
            }

            $fullName = $user->name;
            $nameParts = explode(' ', $fullName, 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';
            $customerDetails = [
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'email'      => $user->email,
                'phone'      => $user->whatsapp,
            ];

            // LOGIKA UNTUK FORMAT ORDER ID PRODUK MENGGUNAKAN 'CATEGORY' DARI DB
            $date = Carbon::now()->format('dmy');
            // Menentukan tipe produk (PD/PF) berdasarkan nilai exact dari kolom 'category' di DB
            // Menggunakan strtolower() untuk memastikan perbandingan tidak case-sensitive
            $productTypeCode = (strtolower($product->category) === 'produk digital') ? 'PD' : 'PF';

            $userPaddedId = str_pad($user->id, 4, '0', STR_PAD_LEFT);
            $uniqueSuffix = strtoupper(Str::random(3));
            $orderId = "PROD-{$productTypeCode}-{$date}-{$userPaddedId}-{$uniqueSuffix}"; // Variabel $orderId diisi di sini


            $order = Order::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'amount' => $product->price,
                'status' => 'pending',
                'order_id' => $orderId, // Menggunakan variabel $orderId yang sudah terisi
            ]);

            $grossAmount = $order->amount;

            $viewData['product'] = $product;
            $viewData['order'] = $order;
            $viewData['entityType'] = 'product';
        } else {
            Log::error("⚠️ Gagal inisiasi pembayaran - tipe tidak valid: {$type}");
            return redirect()->route('payment.error')->with('error', 'Tipe pembayaran tidak valid.');
        }

        // Cek apakah orderId sudah terisi sebelum digunakan
        if (empty($orderId) || (float)$grossAmount <= 0) {
            Log::error("⚠️ Gagal inisiasi pembayaran - orderId atau grossAmount tidak valid.", ['orderId' => $orderId, 'grossAmount' => $grossAmount]);
            return redirect()->route('payment.error')->with('error', 'Informasi pembayaran tidak lengkap atau jumlah tidak valid.');
        }

        $snapToken = $this->midtransService->createTransaction($orderId, (float)$grossAmount, $customerDetails);

        if (!$snapToken) {
            Log::error("PaymentController: Failed to get SnapToken for orderId: {$orderId}.");
            return redirect()->route('payment.error')->with('error', 'Gagal membuat transaksi pembayaran. Mohon coba lagi.');
        }

        $viewData['snapToken'] = $snapToken;
        $viewData['customerDetails'] = $customerDetails;
        $viewData['orderId'] = $orderId; // Kirim orderId ke view

        return view('payment.midtrans', $viewData);
    }

    public function handleMidtransCallback(Request $request)
    {
        Log::info('Midtrans Callback Received (Full Payload):', $request->all());

        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = filter_var(env('MIDTRANS_IS_PRODUCTION'), FILTER_VALIDATE_BOOLEAN);
        Config::$isSanitized = filter_var(env('MIDTRANS_IS_SANITIZED', true), FILTER_VALIDATE_BOOLEAN);
        Config::$is3ds = filter_var(env('MIDTRANS_IS_3DS', true), FILTER_VALIDATE_BOOLEAN);


        try {
            $notification = new MidtransNotificationSdk();

            $orderId = $notification->order_id;
            $transactionStatus = $notification->transaction_status;
            $grossAmount = $notification->gross_amount;

            Log::info("Processing Midtrans webhook via SDK for orderId: {$orderId}, status: {$transactionStatus}, amount: {$grossAmount}");

            $success = $this->transactionHandlerService->handleTransactionStatus(
                $orderId,
                $transactionStatus,
                (float) $grossAmount
            );

            if ($success) {
                return response()->json(['message' => 'Notification processed successfully'], 200);
            } else {
                return response()->json(['message' => 'Failed to process notification (HandlerService error)'], 400);
            }
        } catch (\Exception $e) {
            Log::error("Error processing Midtrans webhook with SDK: " . $e->getMessage(), ['trace' => substr($e->getTraceAsString(), 0, 1000)]);
            return response()->json(['message' => 'Error processing notification: ' . $e->getMessage()], 500);
        }
    }

    public function paymentSuccess(Request $request)
    {
        $orderId = $request->query('order_id');
        $statusFromSnap = $request->query('status');
        Log::info("Gerbang Pembayaran diakses untuk orderId: {$orderId} dengan Snap status: '{$statusFromSnap}'");

        if (!$orderId) {
            return redirect()->route('payment.error')->with(['message' => 'Informasi transaksi tidak ditemukan.']);
        }

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
            Log::error("Gerbang Pembayaran: Gagal cek status Midtrans untuk {$orderId}: " . $e->getMessage());
        }

        $transaction = Transaction::where('order_id', $orderId)->first();

        if ($transaction && $transaction->status === 'paid') {
            $transactionInfo = $this->getTransactionInfo($transaction);
            return view('payment.success', compact('transactionInfo'));
        }
        if ($transaction && $transaction->status === 'failed') {
            $transactionInfo = $this->getTransactionInfo($transaction);
            return view('payment.error', ['message' => 'Pembayaran Anda gagal atau dibatalkan.', 'transactionInfo' => $transactionInfo]);
        }

        if ($statusFromSnap === 'success' && (!$transaction || $transaction->status !== 'paid')) {
            Log::warning("Gerbang: Status Snap 'success' tapi DB belum 'paid' untuk {$orderId}. Menampilkan halaman processing.");
            return view('payment.processing', ['orderId' => $orderId]);
        }

        $transactionInfo = $this->getTransactionInfo($transaction, $request);
        $paymentDetails = $this->getPaymentDetailsFromMidtransResponse($midtransStatusResponse);
        Log::info("Gerbang Pembayaran: Menampilkan halaman pending untuk {$orderId} dengan detail:", $paymentDetails);
        return view('payment.pending', compact('transactionInfo', 'paymentDetails'));
    }

    public function cancelAndRetry(Request $request, $orderId)
    {
        Log::info("User requested to cancel and retry payment for orderId: {$orderId}");

        try {
            MidtransSdkTransaction::cancel($orderId);
            Log::info("Midtrans cancel API call successful for {$orderId}");
        } catch (\Exception $e) {
            Log::warning("Could not cancel transaction {$orderId} on Midtrans.", ['error' => $e->getMessage()]);
        }

        $participant = null;
        $order = null; // Inisialisasi $order
        $isEvent = false;

        // Deteksi apakah orderId adalah event atau produk (format baru atau lama)
        if (preg_match('#^ORD-(WOR|WEB|EVT)-(\d{6})-(\d+)-([A-Z0-9]{3})$#', $orderId, $matches)) {
            $isEvent = true;
            $participant = Participant::find((int)$matches[3]);
            if ($participant) {
                $participant->payment_status = 'cancelled';
                $participant->save();
            }
        } elseif (Str::startsWith($orderId, 'PROD-') || Str::startsWith($orderId, 'prod-order-')) { // Mencakup format baru dan lama
            $order = Order::where('order_id', $orderId)->first();
            if ($order) {
                $order->status = 'cancelled';
                $order->save();
            }
        }

        // Update status di tabel transaksi
        Transaction::where('order_id', $orderId)->update(['status' => 'failed']);


        // Redirect logic setelah pembatalan
        if ($isEvent && $participant) {
            $notification = EventNotificationModel::where('event_type', $participant->event_type)->latest()->first();
            $price = $notification ? $notification->price : 0;
            Log::info("Redirecting participant {$participant->id} to re-initiate payment after cancellation for event.");
            return redirect()->route('payment.initiate', [
                'type' => 'event',
                'identifier' => $participant->id,
                'price' => $price,
            ]);
        } elseif ($order) { // Jika ini transaksi produk (order sudah ditemukan)
            Log::info("Redirecting user to re-initiate product payment after cancellation for orderId {$orderId}.");
            return redirect()->route('payment.initiate', [
                'type' => 'product',
                'identifier' => $order->product->slug, // Gunakan slug produk untuk inisiasi ulang
            ]);
        }

        Log::error("Cancel & Retry failed: Could not find entity or appropriate redirect for orderId: {$orderId}");
        return redirect()->route('payment.error')->with('message', 'Pembatalan tidak dapat diproses atau item terkait tidak ditemukan.');
    }

    public function paymentError(Request $request)
    {
        $message = $request->session()->get('message', 'Terjadi kesalahan saat proses pembayaran.');
        $transactionInfo = $this->getTransactionInfo(null, $request);
        Log::info("Displaying payment error page.", ['message' => $message, 'transactionInfo' => $transactionInfo]);
        return view('payment.error', compact('message', 'transactionInfo'));
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
        $isFailed = $transaction->status === 'failed';

        // Tambahkan detail pembayaran ke dalam response
        $paymentDetails = $this->getPaymentDetailsFromMidtransResponse($midtransStatusResponse);

        $response = [
            'status' => 'success',
            'payment_status_code' => $transaction->status,
            'is_final' => $isPaid || $isFailed,
            'redirect_url' => null,
            'payment_details' => $paymentDetails
        ];

        if ($isPaid) {
            $response['redirect_url'] = route('payment.success', ['order_id' => $transaction->order_id]);
        } elseif ($isFailed) {
            $response['redirect_url'] = route('payment.error', ['order_id' => $transaction->order_id]);
        }

        return response()->json($response);
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
        ];
        if (isset($midtransResponse->va_numbers) && !empty($midtransResponse->va_numbers)) {
            $details['bank'] = strtoupper($midtransResponse->va_numbers[0]->bank ?? '');
            $details['va_number'] = $midtransResponse->va_numbers[0]->va_number ?? null;
        } elseif (isset($midtransResponse->permata_va_number)) {
            $details['bank'] = 'PERMATA';
            $details['va_number'] = $midtransResponse->permata_va_number;
        } elseif ($midtransResponse->payment_type === 'echannel') {
            $details['bank'] = 'MANDIRI';
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

    private function getTransactionInfo(?Transaction $transaction, ?Request $request = null): array
    {
        if (!$transaction && !$request) return [];
        $orderId = $transaction->order_id ?? $request->query('order_id');
        $amount = (float)($transaction->amount ?? $request->query('price', 0));
        $info = [
            'id' => $orderId,
            'amount' => $amount,
            'user_name' => 'Pelanggan',
            'name' => 'Pesanan Anda',
            'type' => 'transaksi',
        ];

        // Deteksi tipe entitas dan ambil info
        if (preg_match('#^ORD-(WOR|WEB|EVT)-(\d{6})-(\d+)-([A-Z0-9]{3})$#', $orderId, $matches)) {
            // Ini adalah format event
            $entity = Participant::find((int)$matches[3]);
            if ($entity) {
                $info['user_name'] = $entity->name;
                $info['name'] = $entity->event_type ? (ucfirst($entity->event_type) . ' Event') : 'Event';
                $info['type'] = 'event';
            }
        } elseif (preg_match('#^PROD-(PD|PF)-(\d{6})-(\d+)-([A-Z0-9]{3})$#', $orderId, $matches)) {
            // Ini adalah format produk (PD/PF)
            $entity = Order::with('user', 'product')->where('order_id', $orderId)->first();
            if ($entity) {
                $info['user_name'] = $entity->user->name ?? 'Pelanggan';
                $info['name'] = $entity->product->name ?? 'Produk';
                $info['type'] = 'produk';
            }
        } else {
            // Fallback untuk order_id lama prod-order-...
            $entity = Order::with('user', 'product')->where('order_id', $orderId)->first();
            if ($entity) {
                $info['user_name'] = $entity->user->name ?? 'Pelanggan';
                $info['name'] = $entity->product->name ?? 'Produk';
                $info['type'] = 'produk';
            }
        }
        return $info;
    }
}
