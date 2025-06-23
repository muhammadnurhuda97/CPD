<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        Log::info("🟦 [LoginController] Proses login dimulai untuk email: " . $request->email);

        if (Auth::attempt($request->only('email', 'password'), $request->has('remember'))) {
            $request->session()->regenerate();
            session()->flash('status', 'login-success');

            $user = Auth::user();
            Log::info("🟦 [LoginController] Pengguna '{$user->name}' berhasil login.");

            if ($user->role == 'admin') {
                Log::info("🟦 [LoginController] Pengguna adalah admin, mengarahkan ke admin dashboard.");
                return redirect()->route('admin.dashboard');
            }

            // Cek apakah ada tujuan spesifik (misalnya, dari halaman checkout)
            $intendedUrl = session()->pull('url.intended');
            if ($intendedUrl) {
                Log::info("🟦 [LoginController] Ditemukan intendedUrl: {$intendedUrl}");
                // Cek apakah tujuannya adalah alur pembayaran/checkout
                if (Str::contains($intendedUrl, '/checkout/') || Str::contains($intendedUrl, '/payment/')) {
                    Log::info("🟦 [LoginController] IntendedUrl adalah halaman checkout/payment, mengarahkan ke 'check.profile.completion'.");
                    // Simpan kembali URL tujuan agar bisa diakses di rute `check.profile.completion`
                    session(['url.intended' => $intendedUrl]);
                    return redirect()->route('check.profile.completion');
                }
                Log::info("🟦 [LoginController] Mengarahkan pengguna ke intendedUrl: " . $intendedUrl);
                return redirect($intendedUrl);
            }

            Log::info("🟦 [LoginController] Tidak ada intendedUrl, mengarahkan ke dashboard pengguna.");
            return redirect()->route('user.dashboard');
        }

        Log::warning("🟦 [LoginController] Percobaan login gagal untuk email: " . $request->email);
        return back()->withErrors(['login_error' => 'Email atau kata sandi yang Anda masukkan salah.'])->withInput($request->only('email', 'remember'));
    }


    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
