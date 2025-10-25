{{-- resources\views\payment\midtrans.blade.php --}}
<!DOCTYPE html>
<html>

<head>
    <title>Selesaikan Pembayaran</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        body {
            font-family: sans-serif;
            text-align: center;
            padding-top: 50px;
            background-color: #f8f9fa;
            color: #333;
        }

        h1 {
            color: #0056b3;
        }

        p {
            margin-bottom: 10px;
        }

        .loading-spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-left: 4px solid #007bff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .instruction-box {
            background-color: #e9ecef;
            border-left: 5px solid #007bff;
            margin: 20px auto;
            padding: 15px 20px;
            max-width: 400px;
            border-radius: 5px;
            text-align: left;
        }
    </style>

    @php
        $isProduction = config('midtrans.is_production');
        $snapJsUrl = $isProduction
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    @endphp

    <script type="text/javascript" src="{{ $snapJsUrl }}" data-client-key="{{ config('midtrans.client_key') }}"></script>
</head>

<body>
    <h1>Memproses Pembayaran Anda...</h1>
    <p>Silakan tunggu sebentar, halaman pembayaran akan segera muncul.</p>

    <div class="instruction-box">
        <p><strong>Detail Pesanan:</strong></p>
        @if ($entityType === 'product')
            <p>Order ID: <strong>{{ $order->order_id ?? 'N/A' }}</strong></p>
            <p>Produk: <strong>{{ $product->name ?? 'N/A' }}</strong></p>
            <p>Total: <strong>Rp{{ number_format($order->amount ?? $product->price, 0, ',', '.') }}</strong></p>
        @elseif ($entityType === 'event')
            <p>Peserta ID: <strong>{{ $participant->id ?? 'N/A' }}</strong></p>
            <p>Event: <strong>{{ $participant->event_type ?? 'N/A' }}</strong></p>
            <p>Total: <strong>Rp{{ number_format($price ?? 0, 0, ',', '.') }}</strong></p>
        @endif
    </div>

    <div class="loading-spinner"></div>

    <script type="text/javascript">
        const snapToken = "{{ $snapToken }}";
        const successRoute = "{{ route('payment.success') }}";
        const errorRoute = "{{ route('payment.error.page') }}";
        const orderId = "{{ $orderId ?? '' }}";

        const getUrlWithParams = (baseRoute, orderIdParam) => {
            let url = `${baseRoute}?order_id=${encodeURIComponent(orderIdParam)}`;
            return url;
        };

        window.onload = function() {
            if (typeof snap !== 'undefined') {
                snap.pay(snapToken, {
                    onSuccess: function(result) {
                        console.log('Payment Success:', result);
                        window.location.href = getUrlWithParams(successRoute, orderId);
                    },
                    onPending: function(result) {
                        console.log('Payment Pending:', result);
                        window.location.href = getUrlWithParams(successRoute, orderId);
                    },
                    onError: function(result) {
                        console.error('Payment Error:', result);
                        window.location.href = getUrlWithParams(errorRoute, orderId);
                    },
                    onClose: function() {
                        console.warn('Pembayaran ditutup oleh pengguna, mengarahkan ke halaman pending.');
                        window.location.href = getUrlWithParams(successRoute, orderId);
                    }
                });
            } else {
                alert("Midtrans Snap.js tidak berhasil dimuat. Silakan coba lagi.");
                console.error("Midtrans Snap.js is not defined.");
            }
        };
    </script>
</body>

</html>
