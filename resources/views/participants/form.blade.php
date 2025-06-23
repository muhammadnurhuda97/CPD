{{-- resources/views/participants/webinar.blade.php --}}
@php
    \Carbon\Carbon::setLocale('id');
    $eventType = request()->query('event_type', 'webinar');
    $notification = \App\Models\Notification::where('event_type', $eventType)->latest()->first();
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pendaftaran</title>
    <link rel="stylesheet" href="{{ asset('css/form.css') }}">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="formbold-main-wrapper">
        <div class="card">
            <div class="formbold-form-wrapper">
                <div class="formbold-event-wrapper">
                    <span>WORKSHOP</span>

                    <h3>{{ $notification ? $notification->event : 'Workshop Digital Marketing Autosmart' }}</h3>
                    <img src="{{ asset('storage/' . $notification->banner) }}" style="width: 100%;" alt="Banner">
                    <h4>Apa yang akan Anda Pelajari</h4>
                    <p>
                        Dalam
                        <strong>{{ $notification ? $notification->event : 'Workshop Digital Marketing Autosmart' }}</strong>,
                        ini akan mengupas tuntas Rahasia Pemasaran Secara Otomatis. Anda akan diajarkan & Praktek
                        Langsung Cara Mendapatkan 10.000 Data Calon Pelanggan Anda secara Otomatis dan melakukan Promosi
                        Otomatis pula kepada mereka. Siap membawa pemasaran bisnis Anda ke level berikutnya?
                    </p>
                    <div class="formbold-event-details">
                        <h5>Event Details</h5>
                        <ul>
                            <li>📅
                                <strong>{{ \Carbon\Carbon::parse($notification->event_date)->translatedFormat('l, d F Y') }}</strong>
                            </li>
                            <li>⏰ <strong>{{ date('H.i', strtotime($notification->event_time)) }} WIB</strong></li>
                            <li>📍 <strong>Online Via Zoom Meeting</strong></li>
                            <li>🏷️ <strong>Marketing & tech</strong></li>
                        </ul>
                    </div>
                </div>

                <!-- Form Registrasi -->
                <form method="POST" action="{{ route('participants.store') }}" id="registrationForm" novalidate>
                    @csrf
                    <h4 class="formbold-form-title">Register now</h4>

                    <div class="formbold-input-flex">
                        <div>
                            <label for="name" class="formbold-form-label">Nama Lengkap <span>*</span></label>
                            <input type="text" name="name" id="name" class="formbold-form-input"
                                value="{{ old('name') }}" required placeholder="Nama lengkap Anda" />
                        </div>
                        <div>
                            <label for="business" class="formbold-form-label">Bisnis/Usaha <span>*</span></label>
                            <input type="text" name="business" id="business" class="formbold-form-input"
                                value="{{ old('business') }}" required placeholder="Nama bisnis Anda" />
                        </div>
                    </div>

                    <div class="formbold-input-flex">
                        <div>
                            <label for="email" class="formbold-form-label">Email <span>*</span></label>
                            <input type="email" name="email" id="email" class="formbold-form-input"
                                value="{{ old('email') }}" required placeholder="email@email.com" />
                        </div>
                        <div>
                            <label for="whatsapp" class="formbold-form-label">No. WhatsApp <span>*</span></label>
                            <input type="text" name="whatsapp" id="whatsapp" class="formbold-form-input"
                                value="{{ old('whatsapp') }}" required placeholder="08123456789"
                                oninput="formatWhatsappNumber(event)" />
                        </div>
                    </div>

                    <div>
                        <label for="city" class="formbold-form-label">Kota Asal <span>*</span></label>
                        <input type="text" name="city" id="city" class="formbold-form-input"
                            value="{{ old('city') }}" required placeholder="Gresik, Jawa Timur" />
                    </div>

                    <input type="hidden" name="affiliate_id" value="{{ session('affiliate_id') }}">
                    <input type="hidden" name="event_type" value="{{ $event_type }}">

                    <p class="formbold-policy">
                        Dengan mengisi formulir ini dan mengklik kirim, Anda menyetujui <a href="#">kebijakan
                            privasi kami</a>.
                    </p>

                    <button type="submit" class="formbold-btn">
                        {{ $notification->is_paid ? 'Lanjutkan Pembayaran' : 'Kirim Pendaftaran' }}
                    </button>
                </form>

                <!-- Error Handling with SweetAlert2 -->
                @if ($errors->has('whatsapp'))
                    <script>
                        Swal.fire({
                            title: "Nomor WhatsApp Sudah Terdaftar!",
                            text: "{{ $errors->first('whatsapp') }}",
                            icon: "error",
                            confirmButtonText: "OK"
                        });
                    </script>
                @endif

                @if ($errors->has('email'))
                    <script>
                        Swal.fire({
                            title: "Email Tidak Valid!",
                            text: "{{ $errors->first('email') }}",
                            icon: "error",
                            confirmButtonText: "OK"
                        });
                    </script>
                @endif

                @if (session('success'))
                    <script>
                        Swal.fire({
                            title: "Pendaftaran Berhasil!",
                            text: "{{ session('success') }}",
                            icon: "success",
                            confirmButtonText: "OK"
                        });
                    </script>
                @endif

                <!-- Script Validasi -->
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const form = document.getElementById('registrationForm');
                        const requiredInputs = form.querySelectorAll('input[required]');

                        requiredInputs.forEach(input => {
                            input.addEventListener('invalid', function(e) {
                                e.target.setCustomValidity('Harap isi bagian ini');
                            });

                            input.addEventListener('input', function(e) {
                                e.target.setCustomValidity('');
                            });
                        });

                        form.addEventListener('submit', function(e) {
                            let allValid = true;

                            requiredInputs.forEach(input => {
                                if (!input.checkValidity()) {
                                    input.reportValidity();
                                    allValid = false;
                                }
                            });

                            if (!allValid) {
                                e.preventDefault();
                            }
                        });
                    });

                    function formatWhatsappNumber(event) {
                        const input = event.target;
                        let value = input.value.replace(/\D/g, '');

                        if (value.startsWith('62')) {
                            value = '0' + value.slice(2);
                        }

                        if (value.length <= 4) {
                            input.value = value;
                        } else if (value.length <= 8) {
                            input.value = value.replace(/(\d{4})(\d{1,4})/, '$1-$2');
                        } else {
                            input.value = value.replace(/(\d{4})(\d{4})(\d{1,4})/, '$1-$2-$3');
                        }
                    }
                </script>
            </div>
        </div>
    </div>
</body>

</html>
