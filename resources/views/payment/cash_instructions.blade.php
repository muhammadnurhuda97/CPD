<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instruksi Pembayaran Tunai</title>
    {{-- Ganti dengan path CSS Bootstrap atau Tailwind Anda --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f4f9;
        }

        .instruction-card {
            max-width: 600px;
            margin: 50px auto;
        }

        .order-id {
            font-family: monospace;
            font-weight: bold;
            background-color: #e9ecef;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
        }

        .referral-link-group {
            position: relative;
        }

        .referral-link-group input {
            padding-right: 90px;
        }

        .referral-link-group button {
            position: absolute;
            right: 5px;
            top: 30px;
        }

        /* Disesuaikan agar tombol pas */
    </style>
</head>

<body>
    <div class="container">
        <div class="card shadow-sm instruction-card">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <img src="https://cdn-icons-png.flaticon.com/512/684/684831.png" alt="Waiting" width="70">
                    {{-- Ganti dengan ikon yang sesuai --}}
                    <h2 class="h4 fw-bold mt-3">Menunggu Pembayaran Tunai</h2>
                </div>

                <p class="text-center text-muted">Terima kasih, {{ $participant->name }}. Pendaftaran Anda untuk event
                    <strong>"{{ $participant->notification->event }}"</strong> telah kami terima.
                </p>

                <div class="alert alert-warning text-center">
                    Silakan selesaikan pembayaran sebesar <strong class="fs-5">Rp
                        {{ number_format($participant->notification->price, 0, ',', '.') }}</strong> secara tunai kepada
                    panitia.
                </div>

                <p class="text-center">Tunjukkan Order ID berikut saat melakukan pembayaran:</p>
                <p class="text-center fs-5 mb-4">
                    <span class="order-id">{{ $participant->order_id ?? 'Belum tergenerate' }}</span>
                    {{-- Kita perlu memastikan order_id digenerate saat memilih metode tunai atau saat pendaftaran awal --}}
                    <button class="btn btn-sm btn-outline-secondary ms-2"
                        onclick="copyOrderId('{{ $participant->order_id ?? '' }}')">Salin ID</button>
                </p>


                {{-- Bagian Referral Link (Sama seperti halaman sukses) --}}
                @if (!is_null($participant->notification))
                    <hr class="my-4">
                    <div class="alert alert-info mb-3">
                        <h5 class="alert-heading h6"><i class="fas fa-gift me-2"></i>Dapatkan Potongan Harga!</h5>
                        <p class="mb-0 small">Ajak teman Anda bergabung dan dapatkan diskon <strong>Rp
                                {{ number_format($participant->notification->referral_discount_amount, 0, ',', '.') }}</strong>
                            untuk setiap pendaftar baru melalui link di bawah ini.</p>
                    </div>

                    @php
                        $route_name =
                            $participant->notification->event_type === 'webinar' ? 'webinar.form' : 'workshop.form';
                        $referral_link = route($route_name, [
                            'notification' => $participant->notification_id,
                            'ref_p' => $participant->id,
                        ]);
                    @endphp

                    <div class="form-group text-start referral-link-group">
                        <label for="referral_link" class="form-label small fw-bold">Link Undangan Anda:</label>
                        <input type="text" id="referral_link" class="form-control form-control-sm"
                            value="{{ $referral_link }}" readonly>
                        <button class="btn btn-sm btn-outline-secondary" onclick="copyReferralLink()">Salin
                            Link</button>
                    </div>
                @endif

                <div class="text-center mt-4">
                    <a href="{{ url('/') }}" class="btn btn-sm btn-secondary">Kembali ke Beranda</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyOrderId(orderId) {
            if (!orderId) {
                alert("Order ID belum tersedia.");
                return;
            }
            navigator.clipboard.writeText(orderId).then(function() {
                alert("Order ID berhasil disalin!");
            }, function(err) {
                alert("Gagal menyalin Order ID.");
            });
        }

        function copyReferralLink() {
            var copyText = document.getElementById("referral_link");
            copyText.select();
            copyText.setSelectionRange(0, 99999); // For mobile devices
            navigator.clipboard.writeText(copyText.value).then(function() {
                alert("Link referral berhasil disalin!");
            }, function(err) {
                alert("Gagal menyalin link referral.");
            });
        }
    </script>
    {{-- Font Awesome (jika belum ada di layout) --}}
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script> {{-- Ganti dengan kit Anda --}}
</body>

</html>
