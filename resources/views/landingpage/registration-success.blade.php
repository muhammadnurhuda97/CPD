{{-- resources/views/landingpage/registration-success.blade.php --}}

{{-- Anda bisa @extends layout landing page jika ada --}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Berhasil</title>
    {{-- Ganti dengan path CSS Bootstrap atau Tailwind Anda --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- Font Awesome (jika belum ada di layout) --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f4f4f9;
        }

        .success-card {
            max-width: 600px;
            margin: 50px auto;
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
        <div class="card shadow-sm success-card">
            <div class="card-body text-center p-4 p-md-5">
                {{-- Icon Sukses --}}
                <img src="https://cdn-icons-png.flaticon.com/512/190/190411.png" alt="Success" width="80"
                    class="mb-3">

                {{-- Pesan Sukses --}}
                <h1 class="h3 fw-bold">Pendaftaran Berhasil!</h1>
                <p class="text-muted">
                    Terima kasih, <strong>{{ $participant->name }}</strong>. Anda telah berhasil terdaftar untuk event
                    <strong>"{{ $participant->notification->event ?? 'Event' }}"</strong>.
                    @if ($participant->notification && $participant->notification->is_paid && $participant->payment_status !== 'paid')
                        {{-- Pesan tambahan jika event berbayar tapi belum lunas (baru selesai daftar, belum pilih bayar) --}}
                        Silakan lanjutkan ke proses pemilihan pembayaran.
                    @else
                        {{-- Pesan default untuk event gratis atau yang sudah lunas --}}
                        Informasi selanjutnya akan kami kirimkan melalui WhatsApp.
                    @endif
                </p>

                {{-- Bagian Referral Link (Hanya tampil jika ada nilai diskon di event) --}}
                @if ($participant->notification && $participant->notification->referral_discount_amount > 0)
                    <hr class="my-4">
                    <div class="alert alert-info mb-3 text-start">
                        <h5 class="alert-heading h6"><i class="fas fa-gift me-2"></i>Dapatkan Potongan Harga!</h5>
                        <p class="mb-0 small">
                            Ajak teman Anda bergabung dan dapatkan diskon/cashback sebesar
                            <strong>Rp
                                {{ number_format($participant->notification->referral_discount_amount, 0, ',', '.') }}</strong>
                            untuk setiap orang yang berhasil mendaftar melalui link di bawah ini.
                        </p>
                    </div>

                    {{-- Kode PHP untuk membuat link referral --}}
                    @php
                        // Tentukan nama rute form pendaftaran berdasarkan tipe event
                        $route_name =
                            optional($participant->notification)->event_type === 'webinar'
                                ? 'webinar.form'
                                : 'workshop.form';
                        // Generate URL menggunakan helper route(), tambahkan parameter notifikasi dan ref_p
                        $referral_link = route($route_name, [
                            'notification' => $participant->notification_id,
                            'ref_p' => $participant->id, // ID unik peserta sebagai parameter referral
                        ]);
                    @endphp

                    {{-- Form Group untuk menampilkan dan menyalin link --}}
                    <div class="form-group text-start referral-link-group">
                        <label for="referral_link" class="form-label small fw-bold">Link Undangan Anda:</label>
                        <input type="text" id="referral_link" class="form-control form-control-sm"
                            value="{{ $referral_link }}" readonly>
                        <button class="btn btn-sm btn-outline-secondary" onclick="copyReferralLink()">
                            <i class="fas fa-copy me-1"></i> Salin
                        </button>
                    </div>
                @endif

                {{-- Tombol Kembali atau Lanjut --}}
                <div class="text-center mt-4">
                    @if (
                        $participant->notification &&
                            $participant->notification->is_paid &&
                            $participant->payment_status === 'pending_choice')
                        {{-- Jika event berbayar dan status menunggu pilihan, tampilkan tombol lanjut --}}
                        <a href="{{ route('payment.choice', ['participant' => $participant->id]) }}"
                            class="btn btn-primary">Lanjutkan ke Pembayaran</a>
                    @else
                        {{-- Jika event gratis atau sudah selesai, tampilkan tombol kembali --}}
                        <a href="{{ url('/') }}" class="btn btn-secondary">Kembali ke Beranda</a>
                    @endif
                </div>

            </div>
        </div>
    </div>

    {{-- Script JavaScript untuk menyalin link --}}
    <script>
        function copyReferralLink() {
            var copyText = document.getElementById("referral_link");
            copyText.select();
            copyText.setSelectionRange(0, 99999); // For mobile devices

            // Gunakan navigator.clipboard API modern jika tersedia
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(copyText.value).then(function() {
                    alert("Link referral berhasil disalin!");
                }, function(err) {
                    // Fallback jika API gagal (misal: karena izin atau konteks tidak aman)
                    try {
                        document.execCommand("copy");
                        alert("Link referral berhasil disalin!");
                    } catch (e) {
                        alert("Gagal menyalin link. Coba salin manual.");
                    }
                });
            } else {
                // Fallback untuk browser lama
                try {
                    document.execCommand("copy");
                    alert("Link referral berhasil disalin!");
                } catch (e) {
                    alert("Gagal menyalin link. Coba salin manual.");
                }
            }
        }
    </script>
</body>

</html>
