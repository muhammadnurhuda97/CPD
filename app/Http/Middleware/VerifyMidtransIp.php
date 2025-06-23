<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyMidtransIp
{
    /**
     * Daftar IP Midtrans yang sah.
     */
    private $validIps = [
        '185.101.193.0/24', // IP range Midtrans (contoh)
        '103.18.2.0/24',     // IP range Midtrans (contoh)
        // Tambahkan IP Midtrans lain sesuai dengan daftar resmi mereka
    ];

    /**
     * Periksa apakah IP pengakses merupakan IP yang valid.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $requestIp = $request->ip();

        // Cek apakah IP pengakses termasuk dalam rentang IP yang valid
        if (!$this->isValidIp($requestIp)) {
            // Jika IP tidak valid, return 403 Forbidden
            abort(403, 'Forbidden: Invalid IP address');
        }

        return $next($request);
    }

    /**
     * Cek apakah IP pengakses termasuk dalam rentang IP yang valid.
     *
     * @param string $ip
     * @return bool
     */
    private function isValidIp($ip)
    {
        foreach ($this->validIps as $validIpRange) {
            if ($this->ipInRange($ip, $validIpRange)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cek apakah sebuah IP berada dalam rentang IP.
     *
     * @param string $ip
     * @param string $range
     * @return bool
     */
    private function ipInRange($ip, $range)
    {
        list($subnet, $mask) = explode('/', $range);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = ~((1 << (32 - $mask)) - 1);

        return ($ipLong & $maskLong) == ($subnetLong & $maskLong);
    }
}
