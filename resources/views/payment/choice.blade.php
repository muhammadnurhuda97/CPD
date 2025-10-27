<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Metode Pembayaran</title>
    {{-- Ganti dengan path CSS Bootstrap atau Tailwind Anda --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .payment-card {
            max-width: 500px;
            width: 100%;
        }

        .method-button {
            display: block;
            width: 100%;
            text-align: left;
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
        }

        .method-button strong {
            font-size: 1.1rem;
        }

        .method-button span {
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
</head>

<body>
    <div class="card shadow-sm payment-card">
        <div class="card-header bg-primary text-white text-center">
            <h4 class="mb-0">Pilih Metode Pembayaran</h4>
        </div>
        <div class="card-body p-4">
            <p class="text-center mb-1">Anda mendaftar untuk:</p>
            <h5 class="text-center fw-bold mb-3">{{ $participant->notification->event }}</h5>
            <p class="text-center fs-4 fw-bold text-danger mb-4">
                Total: Rp {{ number_format($participant->notification->price, 0, ',', '.') }}
            </p>

            <form action="{{ route('payment.select', ['participant' => $participant->id]) }}" method="POST">
                @csrf

                <button type="submit" name="payment_method" value="cash"
                    class="btn btn-outline-secondary method-button">
                    <strong><i class="fas fa-money-bill-wave me-2"></i> Bayar Tunai</strong><br>
                    <span>Lakukan pembayaran langsung ke panitia.</span>
                </button>

                <button type="submit" name="payment_method" value="midtrans"
                    class="btn btn-outline-primary method-button">
                    <strong><i class="fas fa-credit-card me-2"></i> Transfer / E-Wallet / QRIS</strong><br>
                    <span>Pembayaran online otomatis & aman.</span>
                </button>

            </form>

            <div class="text-center mt-3">
                <small class="text-muted">Pendaftaran Anda sudah tercatat. Anda dapat menyelesaikan pembayaran
                    nanti.</small>
            </div>
        </div>
    </div>

    {{-- Font Awesome (jika belum ada di layout) --}}
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script> {{-- Ganti dengan kit Anda --}}
</body>

</html>
