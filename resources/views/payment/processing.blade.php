{{-- resources/views/payment/processing.blade.php --}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <link rel="icon" href="{{ asset('images/dollar.png') }}" type="image/png" sizes="16x16">
    <title>Pembayaran Sedang Diproses</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* (Gunakan style yang sama dengan payment.pending.blade.php atau sesuaikan) */
        body {
            background: #e6f7ff;
            font-family: 'Poppins', sans-serif;
            text-align: center;
            padding-top: 50px;
        }

        .container {
            max-width: 580px;
            margin: 50px auto 0;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .processing-icon {
            font-size: 48px;
            color: #17a2b8;
            margin-bottom: 20px;
        }

        .title {
            font-size: 22px;
            color: #333;
            font-weight: bold;
            margin-bottom: 16px;
        }

        .description {
            font-size: 16px;
            color: #777;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .order-id-label {
            font-weight: bold;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin-top: 20px;
            text-decoration: none;
            border-radius: 5px;
            background-color: #6c757d;
            color: #fff;
        }

        .loading-dots span {
            display: inline-block;
            width: 8px;
            height: 8px;
            margin: 0 2px;
            background-color: #17a2b8;
            border-radius: 50%;
            animation: blink 1.4s infinite both;
        }

        .loading-dots span:nth-child(2) {
            animation-delay: .2s;
        }

        .loading-dots span:nth-child(3) {
            animation-delay: .4s;
        }

        @keyframes blink {

            0%,
            80%,
            100% {
                opacity: 0;
            }

            40% {
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="processing-icon">&#8635;</div>
        <h1 class="title">Pembayaran Sedang Diproses</h1>
        <p class="description">
            {{ $message ?? 'Pembayaran Anda sedang dikonfirmasi oleh sistem. Halaman ini akan diperbarui secara otomatis.' }}
            <span class="loading-dots"><span></span><span></span><span></span></span>
        </p>
        <p class="order-id-label">
            ID Transaksi Anda: <strong>{{ $orderId ?? 'N/A' }}</strong>
        </p>
        <a href="{{ url('/') }}" class="btn">Kembali ke Beranda</a>
    </div>

    <script>
        const orderId = "{{ $orderId ?? '' }}";
        let pollingInterval;

        function checkStatus() {
            if (!orderId) {
                console.error("Order ID tidak ditemukan untuk polling status.");
                clearInterval(pollingInterval);
                return;
            }

            fetch(`/check-payment-status/${orderId}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Status check response:', data);
                    if (data.status === 'success' && data.is_final) {
                        // Jika status sudah final (paid atau failed), hentikan polling dan redirect
                        clearInterval(pollingInterval);
                        if (data.redirect_url) {
                            window.location.href = data.redirect_url;
                        }
                    } else if (data.status === 'success' && data.payment_status_code === 'pending') {
                        // Jika status berubah menjadi pending (misal dari unknown),
                        // kita bisa arahkan ke halaman pending jika ingin tampilan berbeda.
                        // Untuk saat ini, biarkan polling berjalan.
                    }
                })
                .catch(error => {
                    console.error('Error checking payment status:', error);
                    // Pertimbangkan untuk menghentikan polling setelah beberapa kali error
                });
        }

        // Mulai polling jika orderId ada
        if (orderId) {
            // Cek status pertama kali setelah 3 detik
            setTimeout(checkStatus, 3000);
            // Lanjutkan polling setiap 8 detik
            pollingInterval = setInterval(checkStatus, 8000);
        }

        // Opsional: Hentikan polling jika tab tidak aktif
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                clearInterval(pollingInterval);
            } else {
                if (orderId) {
                    checkStatus();
                    pollingInterval = setInterval(checkStatus, 8000);
                }
            }
        });
    </script>
</body>

</html>
