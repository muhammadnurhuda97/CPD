<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <link rel="icon" href="{{ asset('images/dollar.png') }}" type="image/png">
    <title>Pembayaran Tertunda</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f9;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .page-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .container {
            background-color: #fff;
            padding: 25px 35px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 550px;
            width: 100%;
        }

        .icon {
            font-size: 48px;
            color: #ffc107;
            margin-bottom: 15px;
        }

        h1 {
            font-size: 22px;
            color: #1f2d3d;
            margin-bottom: 10px;
            font-weight: 600;
        }

        p {
            font-size: 15px;
            color: #5a6a7a;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .payment-details-box {
            border: 1px solid #e9ecef;
            padding: 20px;
            margin-top: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: left;
            background-color: #fdfdfd;
        }

        .payment-details-box h3 {
            font-size: 16px;
            margin-top: 0;
            margin-bottom: 15px;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .payment-details-box p {
            font-size: 14px;
            margin-bottom: 10px;
        }

        .payment-details-box strong {
            color: #1f2d3d;
        }

        .payment-info {
            font-size: 1.2em;
            color: #007bff;
            font-weight: 600;
            word-wrap: break-word;
            user-select: all;
            cursor: pointer;
        }

        .actions {
            margin-top: 25px;
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
            font-weight: 500;
            border: 1px solid transparent;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            text-align: center;
        }

        .btn-primary {
            background-color: #007bff;
            color: #fff;
            border-color: #007bff;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: #fff;
            border-color: #6c757d;
        }

        .qris-image {
            max-width: 250px;
            width: 100%;
            height: auto;
            margin: 10px auto;
            display: block;
            border: 1px solid #ccc;
            padding: 5px;
            border-radius: 8px;
        }

        .footer-note {
            font-size: 13px;
            color: #888;
            margin-top: 20px;
        }

        .status-message {
            font-weight: 500;
            padding: 8px;
            border-radius: 4px;
            margin-top: 15px;
        }

        .qris-note {
            background-color: #fff3cd;
            border-left: 4px solid #ffeeba;
            padding: 10px;
            font-size: 13px;
            text-align: center;
            margin-top: 15px;
            border-radius: 4px;
        }

        @media screen and (max-width: 600px) {
            .page-wrapper {
                padding: 0;
                align-items: flex-start;
            }

            .container {
                box-shadow: none;
                border-radius: 0;
                padding: 20px 15px;
            }

            h1 {
                font-size: 20px;
            }

            p {
                font-size: 14px;
            }

            .actions {
                flex-direction: column-reverse;
                gap: 10px;
            }

            .actions .btn {
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>

<body>
    <div class="page-wrapper">
        <div class="container">
            <div class="icon">&#9203;</div>
            <h1>Pembayaran Tertunda</h1>
            <p>
                Hai <strong>{{ $transactionInfo['user_name'] ?? 'Pelanggan' }}</strong>, pembayaran Anda untuk
                <strong>{{ $transactionInfo['name'] ?? 'item Anda' }}</strong> sedang menunggu.
            </p>

            @if (!empty($paymentDetails) && $paymentDetails['transaction_status'] === 'pending')
                <div class="payment-details-box">
                    <h3>Instruksi Pembayaran</h3>
                    <p>Jumlah Tagihan: <strong
                            class="payment-info">Rp{{ number_format($paymentDetails['gross_amount'] ?? 0, 0, ',', '.') }}</strong>
                    </p>
                    <p>Metode Pembayaran:
                        <strong>{{ ucfirst(str_replace('_', ' ', $paymentDetails['payment_type'])) }}</strong>
                    </p>

                    @if ($paymentDetails['payment_type'] === 'echannel' && !empty($paymentDetails['biller_code']))
                        <p>Kode Perusahaan (Biller Code): <br><strong class="payment-info copy-to-clipboard"
                                title="Klik untuk salin">{{ $paymentDetails['biller_code'] }}</strong></p>
                        <p>Kode Pembayaran (Bill Key): <br><strong class="payment-info copy-to-clipboard"
                                title="Klik untuk salin">{{ $paymentDetails['bill_key'] }}</strong></p>
                    @elseif(!empty($paymentDetails['bank']) && !empty($paymentDetails['va_number']))
                        <p>Bank Tujuan: <strong>{{ strtoupper($paymentDetails['bank']) }}</strong></p>
                        <p>Nomor Virtual Account: <br><strong class="payment-info copy-to-clipboard"
                                title="Klik untuk salin">{{ $paymentDetails['va_number'] }}</strong></p>
                    @elseif($paymentDetails['payment_type'] === 'cstore' && !empty($paymentDetails['payment_code']))
                        <p>Kode Pembayaran (Indomaret/Alfamart): <br><strong class="payment-info copy-to-clipboard"
                                title="Klik untuk salin">{{ $paymentDetails['payment_code'] }}</strong></p>
                        <p>Tunjukkan kode ini kepada kasir.</p>
                    @elseif($paymentDetails['payment_type'] === 'qris')
                        @if (!empty($paymentDetails['qris_url']))
                            <p><strong>QRIS:</strong></p>
                            <p>Silakan scan kode QR di bawah ini menggunakan aplikasi e-wallet atau mobile banking Anda.
                            </p>
                            <img src="{{ $paymentDetails['qris_url'] }}" alt="QRIS Payment" class="qris-image">
                        @else
                            <div class="qris-note">
                                <strong>Kode QRIS sedang dibuat.</strong><br>
                                Silakan klik tombol "Cek Ulang Status" di bawah dalam beberapa saat untuk
                                menampilkannya.
                            </div>
                        @endif
                    @endif

                    @if (!empty($paymentDetails['expiry_time']))
                        <p>Batas Waktu:
                            <br><strong>{{ \Carbon\Carbon::parse($paymentDetails['expiry_time'])->translatedFormat('l, d F Y H:i') }}
                                WIB</strong>
                        </p>
                    @endif
                </div>
            @else
                <p style="font-style: italic; color: #888;">Klik "Cek Ulang Status" untuk memuat instruksi pembayaran.
                </p>
            @endif

            <p class="footer-note">Detail pembayaran juga akan/telah dikirim melalui WhatsApp.</p>

            <div class="actions">
                {{-- ===== AWAL PERBAIKAN ===== --}}
                {{-- Mengubah dari form POST menjadi link GET agar sesuai dengan route --}}
                <a href="{{ route('payment.cancel', ['orderId' => $transactionInfo['id']]) }}"
                    class="btn btn-secondary"
                    onclick="return confirm('Apakah Anda yakin ingin membatalkan pembayaran ini dan memilih metode lain?');">
                    Ganti Metode Bayar
                </a>
                {{-- ===== AKHIR PERBAIKAN ===== --}}

                <button id="checkStatusBtn" class="btn btn-primary">Cek Ulang Status</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Fungsi untuk menyalin teks ke clipboard
            const copyableElements = document.querySelectorAll(".copy-to-clipboard");
            copyableElements.forEach(element => {
                element.addEventListener("click", function(event) {
                    const textToCopy = event.target.textContent.trim();
                    if (!textToCopy) return;
                    navigator.clipboard.writeText(textToCopy).then(() => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil Disalin!',
                            text: `'${textToCopy}' telah disalin.`,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }).catch(err => {
                        console.error("Gagal menyalin: ", err);
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Gagal menyalin teks.'
                        });
                    });
                });
            });

            // Fungsi untuk tombol cek status
            const checkStatusBtn = document.getElementById('checkStatusBtn');
            if (checkStatusBtn) {
                checkStatusBtn.addEventListener('click', function() {
                    Swal.fire({
                        title: 'Memeriksa Status...',
                        text: 'Halaman akan dimuat ulang dengan status terbaru.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    // Redirect ke halaman status yang akan memproses dan menampilkan hasil yang benar
                    window.location.href =
                        "{{ route('payment.success', ['order_id' => $transactionInfo['id'] ?? '']) }}";
                });
            }
        });
    </script>
</body>

</html>
