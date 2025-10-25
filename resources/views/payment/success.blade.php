{{--  resources\views\payment\success.blade.php  --}}

{{--  @dd($transactionInfo)  --}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <link rel="icon" href="{{ asset('images/dollar.png') }}" type="image/png" sizes="16x16">
    <title>Pembayaran Berhasil</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* ... (CSS Anda yang sudah ada di sini, tetap sama) ... */
        body {
            background: #dcf0fa;
        }

        .container {
            max-width: 580px;
            margin-top: 50px;
            margin-right: auto;
            margin-bottom: 0px;
            margin-left: auto;
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
            height: 650px;
        }

        .paper {
            background: #ffffff;
            font-family: 'Poppins', sans-serif;
            position: absolute;
            z-index: 2;
            margin: 0px 12px;
            margin-top: -12px;
            animation: print 5000ms cubic-bezier(0.68, -0.55, 0.265, 0.9) forwards;
            -moz-animation: print 5000ms cubic-bezier(0.68, -0.55, 0.265, 0.9) forwards;
            -webkit-animation: print 5000ms cubic-bezier(0.68, -0.55, 0.265, 0.9) forwards;
        }

        .main-contents {
            margin: 0 12px;
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

        .success-icon {
            text-align: center;
            font-size: 48px;
            height: 72px;
            background: #359d00;
            border-radius: 50%;
            width: 72px;
            height: 72px;
            margin: 16px auto;
            color: #fff;
        }

        .success-title {
            font-size: 22px;
            font-family: 'Poppins', sans-serif;
            text-align: center;
            color: #666;
            font-weight: bold;
            margin-bottom: 16px;
        }

        .success-description {
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
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
        }

        .complement {
            font-size: 18px;
            margin-bottom: 8px;
            color: #32a852;
        }

        @keyframes print {
            0% {
                transform: translateY(-90%);
            }

            100% {
                transform: translateY(0%);
            }
        }

        @-webkit-keyframes print {
            0% {
                -webkit-transform: translateY(-90%);
            }

            100% {
                -webkit-transform: translateY(0%);
            }
        }

        @-moz-keyframes print {
            0% {
                -moz-transform: translateY(-90%);
            }

            100% {
                -moz-transform: translateY(0%);
            }
        }

        @-ms-keyframes print {
            0% {
                -ms-transform: translateY(-90%);
            }

            100% {
                -ms-transform: translateY(0%);
            }
        }

        /* RESPONSIVE */
        @media screen and (max-width: 480px) {
            .container {
                margin-top: 80px;
                margin-right: auto;
                margin-bottom: 0px;
                margin-left: auto;
                padding: 0 10px;
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

            .success-icon {
                font-size: 40px;
                width: 60px;
                height: 60px;
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
                    <div class="success-icon">&#10004;</div>
                    <div class="success-title">
                        Pembayaran Selesai!
                    </div>
                    <div class="success-description">
                        Terima kasih <br>
                        <h1>{{ $transactionInfo['user_name'] ?? 'Pelanggan' }}</h1>
                        Pembayaran Anda untuk <strong>{{ $transactionInfo['name'] ?? 'produk atau event' }}</strong>
                        sebesar Rp{{ number_format($transactionInfo['amount'] ?? 0, 0, ',', '.') }} berhasil.
                        Info selanjutnya akan dikirim via WhatsApp.
                    </div>
                    <div class="order-details">
                        <div class="order-number-label">
                            ID {{ $transactionInfo['type'] === 'produk' ? 'Order' : 'Peserta' }}
                        </div>
                        <div class="order-number">
                            {{ $transactionInfo['id'] ?? 'N/A' }}
                        </div>
                        <div class="complement">Terima kasih!</div>
                        {{--  <div class="text-center">
                            <a href="/" class="btn btn-primary">Kembali ke Beranda</a>
                        </div>  --}}
                    </div>

                </div>
                <div class="jagged-edge"></div>
            </div>
        </div>
    </div>
</body>

</html>
