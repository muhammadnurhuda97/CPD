<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <link rel="icon" href="{{ asset('images/dollar.png') }}" type="image/png" sizes="16x16">
    <title>Pembayaran Gagal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            background: #ffe6e6;
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }

        .container {
            max-width: 580px;
            width: 100%;
            overflow: hidden;
            align-items: center;
        }

        .printer-top {
            z-index: 1;
            border: 6px solid #666666;
            height: 6px;
            border-bottom: 0;
            border-radius: 6px 6px 0 0;
            background: #333333;
        }

        .printer-bottom {
            z-index: 0;
            border: 6px solid #666666;
            height: 20px;
            border-top: 0;
            border-radius: 0 0 6px 6px;
            background: #333333;
        }

        .paper-container {
            position: relative;
            overflow: hidden;
            height: auto;
        }

        .paper {
            background: #ffffff;
            font-family: 'Poppins', sans-serif;
            position: relative;
            z-index: 2;
            margin: 0px 12px;
            margin-top: -12px;
            animation: print 1.5s ease-out;
        }

        .main-contents {
            margin: 0 12px;
            padding: 24px;
        }

        .text-center {
            text-align: center;
        }

        .btn {
            display: inline-block;
            text-decoration: none;
            font-weight: 600;
            border-radius: 5px;
            padding: 12px 25px;
            color: white;
            margin-top: 15px;
            border: none;
            cursor: pointer;
            transition: opacity 0.2s;
            font-size: 1rem;
        }

        .btn-primary {
            background-color: #3498db;
        }

        .btn-retry {
            background-color: #27ae60;
            margin-right: 10px;
        }

        .btn:hover {
            opacity: 0.8;
        }

        .jagged-edge {
            position: relative;
            height: 20px;
            width: 100%;
            margin-top: -1px;
        }

        .jagged-edge:after {
            content: "";
            display: block;
            position: absolute;
            left: 0;
            right: 0;
            height: 20px;
            background: linear-gradient(45deg, transparent 33.333%, #ffffff 33.333%, #ffffff 66.667%, transparent 66.667%), linear-gradient(-45deg, transparent 33.333%, #ffffff 33.333%, #ffffff 66.667%, transparent 66.667%);
            background-size: 16px 40px;
            background-position: 0 -20px;
        }

        .error-icon {
            text-align: center;
            font-size: 48px;
            height: 72px;
            background: #dc3545;
            border-radius: 50%;
            width: 72px;
            height: 72px;
            margin: 16px auto;
            color: #fff;
            line-height: 72px;
        }

        .success-title {
            font-size: 22px;
            text-align: center;
            color: #666;
            font-weight: bold;
            margin-bottom: 16px;
        }

        .success-description {
            font-size: 16px;
            line-height: 21px;
            color: #999;
            text-align: center;
            margin-bottom: 24px;
        }

        .order-details {
            text-align: center;
            color: #333;
            font-weight: bold;
            padding-bottom: 20px
        }

        .order-number-label {
            font-size: 18px;
            margin-bottom: 8px;
        }

        .order-number {
            border-top: 1px solid #ccc;
            border-bottom: 1px solid #ccc;
            line-height: 48px;
            font-size: 48px;
            padding: 8px 0;
            margin-bottom: 10px;
            word-wrap: break-word;
        }

        .complement {
            font-size: 18px;
            margin-bottom: 8px;
            color: #dc3545;
        }

        @keyframes print {
            0% {
                transform: translateY(-100%);
            }

            100% {
                transform: translateY(0%);
            }
        }

        @media screen and (max-width: 480px) {
            .container {
                margin: 20px 0;
            }

            .success-title {
                font-size: 18px;
            }

            .success-description {
                font-size: 14px;
            }

            .order-number {
                font-size: 28px;
            }

            .order-number-label,
            .complement {
                font-size: 14px;
            }

            .error-icon {
                font-size: 40px;
                width: 60px;
                height: 60px;
                line-height: 60px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="printer-top"></div>
        <div class="paper-container">
            <div class="printer-bottom"></div>
            <div class="paper">
                <div class="main-contents">
                    <div class="error-icon">&#10006;</div>
                    <div class="success-title">Pembayaran Gagal!</div>
                    <div class="success-description">
                        {{-- Pesan error dinamis --}}
                        <strong>{{ $message ?? 'Terjadi kesalahan saat memproses pembayaran Anda.' }}</strong>
                    </div>

                    {{-- ===== AWAL PERBAIKAN ===== --}}
                    {{-- Cek menggunakan !empty() agar lebih aman dan tidak error jika key 'id' tidak ada --}}
                    @if (!empty($transactionInfo['id']))
                        <div class="order-details">
                            <div class="order-number-label">ID Transaksi</div>
                            <div class="order-number">{{ $transactionInfo['id'] }}</div>
                        </div>
                    @endif
                    {{-- ===== AKHIR PERBAIKAN ===== --}}

                    <div class="complement">Terima kasih atas pengertiannya.</div>

                    <div class="text-center">
                        {{-- Tampilkan tombol "Coba Lagi" jika URL untuk mencoba lagi tersedia --}}
                        @if (isset($retryUrl))
                            <a href="{{ $retryUrl }}" class="btn btn-retry">{{ $retry_text ?? 'Coba Lagi' }}</a>
                        @endif
                        <a href="{{ url('/') }}" class="btn btn-primary">Kembali ke Beranda</a>
                    </div>
                </div>
                <div class="jagged-edge"></div>
            </div>
        </div>
    </div>
</body>

</html>
