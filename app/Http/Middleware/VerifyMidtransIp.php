<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyMidtransIp
{
    /**
     * Daftar IP Midtrans untuk lingkungan SANDBOX.
     * Sumber: Dokumentasi resmi Midtrans
     * @var array
     */
    private $sandboxIps = [
        '103.208.232.0/24',
        '103.208.233.0/24',
    ];

    /**
     * Daftar IP Midtrans untuk lingkungan PRODUCTION.
     * Sumber: Dokumentasi resmi Midtrans
     * @var array
     */
    private $productionIps = [
        '103.12.16.0/24',
        '103.12.17.0/24',
    ];

    /**
     * Middleware handler.
     */
    public function handle(Request $request, Closure $next)
    {
        // Ambil IP asli dari header Cloudflare, fallback ke Laravel default
        $requestIp = $request->header('CF-Connecting-IP') ?? $request->ip();

        // Jika IP filtering dimatikan di .env (opsional)
        if (!config('services.midtrans.ip_filter_enabled', true)) {
            Log::info('[MidtransWebhook] IP filtering dimatikan. Lanjut.');
            return $next($request);
        }

        // Validasi IP
        if (!$this->isValidIp($requestIp)) {
            Log::warning("[MidtransWebhook] IP tidak diizinkan: {$requestIp}");
            abort(403, 'Forbidden: Invalid IP address');
        }

        Log::info("[MidtransWebhook] IP terverifikasi: {$requestIp}");
        return $next($request);
    }

    /**
     * Cek apakah IP termasuk dalam daftar yang diizinkan sesuai environment.
     */
    private function isValidIp($ip)
    {
        // === AWAL PERUBAHAN LOGIKA ===
        // Pilih daftar IP yang akan digunakan berdasarkan konfigurasi midtrans
        $allowedIps = config('midtrans.is_production')
            ? $this->productionIps
            : $this->sandboxIps;

        Log::info('[MidtransWebhook] Verifikasi IP menggunakan mode: ' . (config('midtrans.is_production') ? 'PRODUCTION' : 'SANDBOX'));
        // === AKHIR PERUBAHAN LOGIKA ===

        foreach ($allowedIps as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Periksa apakah IP ada dalam rentang (CIDR).
     */
    private function ipInRange($ip, $range)
    {
        // Logika ini tetap sama, tidak perlu diubah
        list($subnet, $mask) = explode('/', $range);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = ~((1 << (32 - $mask)) - 1);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}
