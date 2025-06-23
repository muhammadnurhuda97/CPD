<x-guest-layout>
    <x-auth-card>
        <x-slot name="logo">
        </x-slot>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <!-- Validation Errors -->
        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <div class="wrapper">
            <form method="POST" id="myForm" action="{{ route('register') }}">
                @csrf

                <!-- Input Nama -->
                <div class="input-field relative">
                    <x-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')"
                        required autofocus />
                    <x-label for="name" :value="__('Nama')" />
                </div>

                <!-- Username -->
                <input type="hidden" name="username" value="{{ old('username') }}" />

                <!-- Phone -->
                <div class="input-field relative">
                    <x-input id="whatsapp" class="block mt-1 w-full" type="text" name="whatsapp" :value="old('whatsapp')"
                        required oninput="formatWhatsappNumber(event)" />
                    <x-label for="whatsapp" :value="__('Nomor WhatsApp Aktif')" />
                </div>

                <!-- Input Email -->
                <div class="input-field relative">
                    <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')"
                        required />
                    <x-label for="email" :value="__('Email')" />
                </div>

                <!-- Input Password -->
                <div class="input-field relative">
                    <x-input id="password" class="block mt-1 w-full" type="password" name="password" required
                        autocomplete="new-password" />
                    <x-label for="password" :value="__('Password')" />
                </div>

                <!-- Confirm Password -->
                <div class="input-field relative">
                    <x-input id="password_confirmation" class="block mt-1 w-full" type="password"
                        name="password_confirmation" required />
                    <x-label for="password_confirmation" :value="__('Confirm Password')" />
                </div>

                <input type="hidden" name="affiliate_id" value="{{ $affiliateId }}" />

                <div class="mt-4 flex flex-col items-center w-full forget">
                    <span>Sudah daftar?
                        <a class="underline text-sm text-gray-600 hover:text-gray-900 mb-4" href="{{ route('login') }}">
                            {{ __('Login disini') }}
                        </a></span>
                </div>

                <x-button class="register-btn w-full">
                    {{ __('Daftar') }}
                </x-button>
            </form>
            @if ($errors->has('password'))
                <script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Konfirmasi password tidak cocok',
                        confirmButtonText: 'OK'
                    });
                </script>
            @endif
        </div>

        <script>
            function formatWhatsappNumber(event) {
                const input = event.target;
                let value = input.value.replace(/\D/g, ''); // Hapus semua non-digit

                // Ganti awalan 62 ke 0 jika perlu
                if (value.startsWith('62')) {
                    value = '0' + value.slice(2);
                }

                // Format menjadi 0878-7786-6741
                if (value.length <= 4) {
                    input.value = value;
                } else if (value.length <= 8) {
                    input.value = value.replace(/(\d{4})(\d{1,4})/, '$1-$2');
                } else {
                    input.value = value.replace(/(\d{4})(\d{4})(\d{1,4})/, '$1-$2-$3');
                }
            }

            document.getElementById('myForm').addEventListener('submit', function(e) {
                const input = document.getElementById('whatsappInput');
                let value = input.value.replace(/\D/g, ''); // Hapus semua non-digit

                // Ganti awalan 62 ke 0
                if (value.startsWith('62')) {
                    value = '0' + value.slice(2);
                }

                input.value = value; // Simpan hanya angka (tanpa strip) ke backend
            });
        </script>

    </x-auth-card>
</x-guest-layout>
