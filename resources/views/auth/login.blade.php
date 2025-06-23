<x-guest-layout>
    <x-auth-card>
        <x-slot name="logo">
        </x-slot>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        {{--  <!-- Validation Errors -->
        <x-auth-validation-errors class="mb-4" :errors="$errors" />  --}}

        <div class="wrapper">
            <form method="POST" action="{{ route('login') }}" id="loginForm">
                @csrf
                <h2>Login</h2>

                <!-- Email input -->
                <div class="input-field">
                    <input type="email" name="email" id="email" required>
                    <label for="email">Enter your email</label>
                </div>

                <!-- Password input -->
                <div class="input-field">
                    <input type="password" name="password" id="password" required>
                    <label for="password">Enter your password</label>
                </div>

                <!-- Remember Me checkbox -->
                <!-- Remember Me checkbox -->
                <div class="forget">
                    <label for="remember">
                        <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                        <p>Remember me</p>
                    </label>
                </div>


                <!-- Submit button -->
                <button type="submit">Login</button>

                <!-- Register link -->
                <div class="register">
                    <p>Tidak punya account? <a class="underline text-sm text-gray-600 hover:text-gray-900 mb-4"
                            href="{{ route('register') }}">
                            {{ __('Daftar disini') }}
                        </a>
                    </p>
                </div>
            </form>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const form = document.getElementById('loginForm');
                    const requiredFields = form.querySelectorAll('input[required]');

                    requiredFields.forEach(input => {
                        input.addEventListener('invalid', function(event) {
                            const value = input.value;

                            if (event.target.validity.valueMissing) {
                                event.target.setCustomValidity('Harap di isi bagian ini');
                            } else if (input.type === 'email') {
                                if (!value.includes('@')) {
                                    event.target.setCustomValidity('Email harus mengandung karakter "@"');
                                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                                    event.target.setCustomValidity(
                                        'Format email tidak valid, contoh: email@domain.com');
                                }
                            }
                        });

                        input.addEventListener('input', function() {
                            input.setCustomValidity('');
                        });
                    });
                });
            </script>

        </div>


        @if ($errors->any())
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Login gagal',
                    text: '{{ $errors->first() }}',
                    showConfirmButton: true
                });
            </script>
        @endif

        @if (session('status') == 'login-success')
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Login berhasil',
                    text: 'Selamat datang kembali!',
                    showConfirmButton: false,
                    timer: 2000,
                    willClose: () => {
                        window.location.href = "{{ route('user.dashboard') }}"; // Ganti sesuai route-mu
                    }
                });
            </script>
        @endif
    </x-auth-card>
</x-guest-layout>
